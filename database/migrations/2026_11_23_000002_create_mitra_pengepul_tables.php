<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 8.1.2 · Data mitra pengepul.
 * - mitra_pengepul: identitas usaha + status verifikasi (dikelola admin)
 * - mitra_area: kecamatan yang dilayani
 * - mitra_harga: harga per kg untuk tiap kategori daur ulang
 * - mitra_jam: jam operasional per hari (hari tanpa baris = libur)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitra_pengepul', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nama_usaha', 150);
            $table->string('telepon', 20);
            $table->string('alamat', 255);
            $table->foreignId('kelurahan_id')->nullable()->constrained('wilayah_kelurahan')->nullOnDelete();
            $table->boolean('layanan_jemput')->default(false);
            $table->boolean('layanan_antar')->default(true);
            $table->string('status', 20)->default('menunggu')->index();
            $table->string('catatan_verifikasi', 255)->nullable();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mitra_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitra_pengepul')->cascadeOnDelete();
            $table->foreignId('kecamatan_id')->constrained('wilayah_kecamatan')->cascadeOnDelete();
            $table->unique(['mitra_id', 'kecamatan_id']);
            $table->index('kecamatan_id');
        });

        Schema::create('mitra_harga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitra_pengepul')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->unsignedInteger('harga_per_kg');
            $table->timestamps();
            $table->unique(['mitra_id', 'category_id']);
            $table->index('category_id');
        });

        Schema::create('mitra_jam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitra_pengepul')->cascadeOnDelete();
            $table->unsignedTinyInteger('hari'); // 1 = Senin … 7 = Minggu (ISO-8601)
            $table->time('buka');
            $table->time('tutup');
            $table->unique(['mitra_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_jam');
        Schema::dropIfExists('mitra_harga');
        Schema::dropIfExists('mitra_area');
        Schema::dropIfExists('mitra_pengepul');
    }
};
