<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $elektronik = DB::table('categories')->where('slug', 'elektronik')->first();
            $gadget = DB::table('categories')->where('slug', 'gadget')->first();

            if (! $elektronik && $gadget) {
                DB::table('categories')->where('id', $gadget->id)->update([
                    'slug' => 'elektronik', 'name' => 'Elektronik & Gadget', 'updated_at' => now(),
                ]);

                return;
            }

            if ($elektronik) {
                if ($gadget) {
                    DB::table('listings')->where('category_id', $gadget->id)->update(['category_id' => $elektronik->id]);
                    DB::table('categories')->where('id', $gadget->id)->delete();
                }

                DB::table('categories')->where('id', $elektronik->id)->update([
                    'name' => 'Elektronik & Gadget', 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Pemisahan otomatis tidak aman: asal kategori setiap barang tidak lagi diketahui.
    }
};
