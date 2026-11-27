<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 11.1.2 — Controller Moderasi Admin (PPLTIMB-152).
 * Hanya dapat diakses oleh admin melalui middleware peran:admin.
 */
class ModerasiController extends Controller
{
    /**
     * Tampilkan antrean laporan untuk admin dengan filter status.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status'); // pending, resolved, rejected, sengketa
        
        $query = Report::with(['reporter'])->latest();

        if ($status === 'sengketa') {
            $query->where('reportable_type', Report::TYPE_ORDER);
        } elseif (in_array($status, [Report::STATUS_PENDING, Report::STATUS_RESOLVED, Report::STATUS_REJECTED])) {
            $query->where('status', $status);
        }

        $reports = $query->get();

        // Hitung statistik untuk badge tab filter
        $counts = [
            'all'      => Report::count(),
            'pending'  => Report::where('status', Report::STATUS_PENDING)->count(),
            'sengketa' => Report::where('reportable_type', Report::TYPE_ORDER)->where('status', Report::STATUS_PENDING)->count(),
            'resolved' => Report::where('status', Report::STATUS_RESOLVED)->count(),
        ];

        return view('admin.moderasi.index', compact('reports', 'counts', 'status'));
    }

    /**
     * Tampilkan detail laporan tunggal untuk moderasi.
     */
    public function show(Report $report): View
    {
        $report->load(['reporter', 'resolver']);
        $target = $report->target;

        return view('admin.moderasi.show', compact('report', 'target'));
    }

    /**
     * Aksi Admin: Nonaktifkan barang yang dilaporkan.
     */
    public function deactiveListing(Request $request, Report $report): RedirectResponse
    {
        if ($report->reportable_type !== Report::TYPE_LISTING) {
            return back()->with('error', 'Laporan bukan merupakan laporan barang.');
        }

        $listing = Listing::findOrFail($report->reportable_id);

        DB::transaction(function () use ($report, $listing, $request) {
            // Ubah status listing menjadi dihapus
            $listing->update(['status' => Listing::DIHAPUS]);

            $report->update([
                'status'       => Report::STATUS_RESOLVED,
                'action_taken' => Report::ACTION_DEACTIVATE_LISTING,
                'admin_notes'  => $request->input('notes', 'Barang dinonaktifkan oleh admin karena melanggar aturan.'),
                'resolved_by'  => $request->user()->id,
                'resolved_at'  => now(),
            ]);
        });

        return redirect()->route('admin.moderasi.index')
            ->with('success', "Barang '{$listing->title}' berhasil dinonaktifkan.");
    }

    /**
     * Aksi Admin: Nonaktifkan/bekukan akun pengguna yang dilaporkan.
     */
    public function deactiveUser(Request $request, Report $report): RedirectResponse
    {
        if ($report->reportable_type !== Report::TYPE_USER) {
            return back()->with('error', 'Laporan bukan merupakan laporan akun pengguna.');
        }

        $user = User::findOrFail($report->reportable_id);

        // Jangan izinkan admin menonaktifkan sesama admin
        if ($user->hasRole(User::ADMIN)) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun admin.');
        }

        DB::transaction(function () use ($report, $user, $request) {
            $user->update(['is_active' => false]);

            $report->update([
                'status'       => Report::STATUS_RESOLVED,
                'action_taken' => Report::ACTION_DEACTIVATE_USER,
                'admin_notes'  => $request->input('notes', 'Akun dibekukan oleh admin karena pelanggaran.'),
                'resolved_by'  => $request->user()->id,
                'resolved_at'  => now(),
            ]);
        });

        return redirect()->route('admin.moderasi.index')
            ->with('success', "Akun pengguna '{$user->name}' berhasil dinonaktifkan/dibekukan.");
    }

    /**
     * Aksi Admin: Putusan Sengketa Pesanan.
     * Pilihan: cairkan ke penjual (payout_seller) ATAU refund ke pembeli (refund_buyer).
     */
    public function resolveDispute(Request $request, Report $report): RedirectResponse
    {
        if ($report->reportable_type !== Report::TYPE_ORDER) {
            return back()->with('error', 'Laporan bukan merupakan sengketa pesanan.');
        }

        $order = Order::with(['seller.profile', 'buyer.profile'])->findOrFail($report->reportable_id);
        $decision = $request->input('decision'); // payout_seller atau refund_buyer

        if (! in_array($decision, [Report::ACTION_PAYOUT_SELLER, Report::ACTION_REFUND_BUYER])) {
            return back()->with('error', 'Keputusan sengketa tidak valid.');
        }

        DB::transaction(function () use ($report, $order, $decision, $request) {
            if ($decision === Report::ACTION_PAYOUT_SELLER) {
                // Dana dicairkan ke penjual -> selesaikan pesanan
                $order->update([
                    'status'             => Order::STATUS_SELESAI,
                    'buyer_confirmed_at' => $order->buyer_confirmed_at ?? now(),
                ]);

                if ($order->seller?->profile) {
                    $order->seller->profile->increment('total_sold');
                }

                $catatan = 'Sengketa diputuskan: Dana dicairkan ke penjual oleh admin.';
            } else {
                // Dana dikembalikan ke pembeli -> batalkan pesanan & refund
                $order->update([
                    'status'        => Order::STATUS_DIBATALKAN,
                    'cancelled_at'  => now(),
                    'cancel_reason' => 'Diputuskan refund oleh admin: ' . ($request->input('notes') ?? 'Sengketa pembeli diterima'),
                ]);

                // Listing dikembalikan aktif jika sebelumnya dipesan
                if ($order->listing && $order->listing->status === Listing::DIPESAN) {
                    $order->listing->update(['status' => Listing::AKTIF]);
                }

                $catatan = 'Sengketa diputuskan: Dana dikembalikan ke pembeli (refund sandbox) oleh admin.';
            }

            $report->update([
                'status'       => Report::STATUS_RESOLVED,
                'action_taken' => $decision,
                'admin_notes'  => $request->input('notes') ?: $catatan,
                'resolved_by'  => $request->user()->id,
                'resolved_at'  => now(),
            ]);
        });

        return redirect()->route('admin.moderasi.index')
            ->with('success', 'Putusan sengketa pesanan #' . $order->order_number . ' berhasil diterapkan.');
    }

    /**
     * Aksi Admin: Abaikan/Tolak laporan (laporan tidak valid atau tidak melanggar aturan).
     */
    public function dismiss(Request $request, Report $report): RedirectResponse
    {
        $report->update([
            'status'       => Report::STATUS_REJECTED,
            'action_taken' => Report::ACTION_DISMISSED,
            'admin_notes'  => $request->input('notes', 'Laporan ditinjau dan dinyatakan tidak melanggar ketentuan.'),
            'resolved_by'  => $request->user()->id,
            'resolved_at'  => now(),
        ]);

        return redirect()->route('admin.moderasi.index')
            ->with('info', 'Laporan telah diabaikan/ditolak.');
    }
}
