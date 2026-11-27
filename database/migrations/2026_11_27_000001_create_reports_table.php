<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 11.1.2 — Migrasi tabel laporan dan moderasi sengketa (PPLTIMB-152).
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            
            // Tipe entitas yang dilaporkan: listing, user, order
            $table->enum('reportable_type', ['listing', 'user', 'order']);
            $table->unsignedBigInteger('reportable_id');
            
            $table->string('reason');
            $table->text('description');
            
            // Status penanganan oleh admin
            // pending: menunggu tinjauan, resolved: diselesaikan/ditindak, rejected: ditolak/diabaikan
            $table->enum('status', ['pending', 'resolved', 'rejected'])->default('pending');
            
            // Keputusan atau tindakan admin (mis. deactive_listing, deactivate_user, payout_seller, refund_buyer, dismissed)
            $table->string('action_taken')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
        });

        // Tambah kolom is_active pada users jika belum ada
        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('password');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');

        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
