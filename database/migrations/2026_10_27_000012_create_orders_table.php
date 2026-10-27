<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('listing_id')->constrained('listings');
            $table->foreignId('buyer_id')->constrained('users');
            $table->foreignId('seller_id')->constrained('users');
            $table->decimal('agreed_price', 12, 0);
            $table->enum('delivery_method', ['cod', 'ambil']);
            $table->text('delivery_address')->nullable();
            $table->enum('status', ['menunggu_bayar', 'dibayar', 'diserahkan', 'selesai', 'dibatalkan'])
                  ->default('menunggu_bayar');
            $table->timestamp('seller_confirmed_at')->nullable();
            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->timestamp('auto_complete_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index('buyer_id');
            $table->index('seller_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
