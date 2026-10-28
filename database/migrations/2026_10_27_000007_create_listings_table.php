<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('kelurahan_id')->nullable()->constrained('wilayah_kelurahan')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 0);
            $table->boolean('is_negotiable')->default(true);
            $table->enum('condition', ['seperti_baru', 'baik', 'ada_minus']);
            $table->string('condition_note', 255)->nullable();
            $table->enum('status', ['aktif', 'dipesan', 'terjual', 'draft', 'dihapus'])->default('aktif');
            $table->unsignedInteger('view_count')->default(0);
            $table->string('material', 100)->nullable();
            $table->string('size', 100)->nullable();
            $table->string('usage_duration', 100)->nullable();
            $table->unsignedTinyInteger('quantity')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
