<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('midtrans_id', 100)->unique()->nullable();
            $table->enum('method', ['qris', 'va', 'ewallet'])->nullable();
            $table->string('va_number', 50)->nullable();
            $table->string('bank', 20)->nullable();
            $table->decimal('amount', 12, 0);
            $table->decimal('platform_fee', 12, 0)->default(0);
            $table->enum('status', ['menunggu', 'berhasil', 'gagal', 'kedaluwarsa'])->default('menunggu');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
