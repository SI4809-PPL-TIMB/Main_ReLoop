<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 9.1.2 · Buat backend order setor (PPLTIMB-170).
 * Tabel order_setor untuk transaksi penyetoran sampah daur ulang ke mitra pengepul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_setor', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mitra_id')->constrained('mitra_pengepul')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->decimal('perkiraan_berat', 8, 2);
            $table->unsignedInteger('harga_per_kg'); // Harga dikunci saat order dibuat
            $table->unsignedBigInteger('total_estimasi'); // perkiraan_berat * harga_per_kg
            $table->enum('metode', ['jemput', 'antar']);
            $table->text('alamat_jemput')->nullable();
            $table->string('patokan', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->enum('status', ['menunggu', 'diterima', 'ditimbang', 'selesai', 'dibatalkan', 'ditolak'])
                  ->default('menunggu')
                  ->index();
            $table->decimal('berat_aktual', 8, 2)->nullable();
            $table->unsignedBigInteger('total_aktual')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('mitra_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_setor');
    }
};
