<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelurahan_id')->nullable()->constrained('wilayah_kelurahan')->nullOnDelete();
            $table->string('avatar', 255)->nullable();
            $table->text('bio')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('total_sold')->default(0);
            $table->unsignedInteger('streak_count')->default(0);
            $table->date('streak_last_at')->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedInteger('total_points')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
