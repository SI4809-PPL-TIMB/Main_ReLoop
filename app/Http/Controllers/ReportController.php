<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 11.1.2 — Controller Laporan untuk Pengguna (PPLTIMB-152).
 */
class ReportController extends Controller
{
    /**
     * Tampilkan riwayat laporan milik pengguna login.
     */
    public function index(Request $request): View
    {
        $reports = Report::with(['resolver'])
            ->where('reporter_id', $request->user()->id)
            ->latest()
            ->get();

        return view('lapor.index', compact('reports'));
    }

    /**
     * Tampilkan formulir dialog laporkan.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $type = $request->query('tipe'); // listing, user, order
        $id = $request->query('id');

        $target = null;
        if ($type === Report::TYPE_LISTING) {
            $target = Listing::with('user')->findOrFail($id);
        } elseif ($type === Report::TYPE_USER) {
            $target = User::findOrFail($id);
        } elseif ($type === Report::TYPE_ORDER) {
            $target = Order::with(['listing', 'seller', 'buyer'])->where('order_number', $id)->orWhere('id', $id)->firstOrFail();
        } else {
            return redirect()->back()->with('error', 'Tipe laporan tidak valid.');
        }

        return view('lapor.create', compact('type', 'target'));
    }

    /**
     * Simpan laporan baru dari pengguna.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reportable_type' => ['required', 'in:listing,user,order'],
            'reportable_id'   => ['required', 'integer'],
            'reason'          => ['required', 'string', 'max:100'],
            'description'     => ['required', 'string', 'max:1000'],
        ], [
            'reason.required'      => 'Pilih alasan pelaporan.',
            'description.required' => 'Jelaskan keterangan atau kronologi kejadian.',
            'description.max'      => 'Keterangan maksimal 1000 karakter.',
        ]);

        $user = $request->user();

        // Validasi keberadaan target
        if ($validated['reportable_type'] === Report::TYPE_LISTING) {
            $listing = Listing::findOrFail($validated['reportable_id']);
            if ($listing->user_id === $user->id) {
                return back()->with('error', 'Tidak dapat melaporkan barang milik sendiri.');
            }
        } elseif ($validated['reportable_type'] === Report::TYPE_USER) {
            $targetUser = User::findOrFail($validated['reportable_id']);
            if ($targetUser->id === $user->id) {
                return back()->with('error', 'Tidak dapat melaporkan akun sendiri.');
            }
        } elseif ($validated['reportable_type'] === Report::TYPE_ORDER) {
            $order = Order::findOrFail($validated['reportable_id']);
            if (! $order->isBuyer($user->id) && ! $order->isSeller($user->id)) {
                abort(403, 'Hanya pihak yang bertransaksi yang dapat melaporkan pesanan ini.');
            }
        }

        Report::create([
            'reporter_id'     => $user->id,
            'reportable_type' => $validated['reportable_type'],
            'reportable_id'   => $validated['reportable_id'],
            'reason'          => $validated['reason'],
            'description'     => $validated['description'],
            'status'          => Report::STATUS_PENDING,
        ]);

        return redirect()->route('laporan.index')
            ->with('success', 'Laporan berhasil dikirim. Tim moderasi akan meninjau secepatnya.');
    }
}
