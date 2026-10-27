<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah_kelurahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained('wilayah_kecamatan')->cascadeOnDelete();
            $table->string('nama', 100);
            $table->string('kode', 10)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah_kelurahan');
    }
};
