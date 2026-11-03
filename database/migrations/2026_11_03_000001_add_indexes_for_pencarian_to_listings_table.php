<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->index(['status', 'price']);
            $table->index(['status', 'kelurahan_id']);
            $table->index(['status', 'published_at']);
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['status', 'price']);
            $table->dropIndex(['status', 'kelurahan_id']);
            $table->dropIndex(['status', 'published_at']);
            $table->dropIndex(['title']);
        });
    }
};

