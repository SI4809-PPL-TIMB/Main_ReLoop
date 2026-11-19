<?php

namespace Database\Seeders;

use App\Models\Listing;
use RuntimeException;

/** Foto demo disimpan bersama proyek; seeding tidak membutuhkan jaringan. */
class DemoListingPhotos
{
    public static function attach(Listing $listing, string $filename): void
    {
        $path = 'assets/demo/barang/'.$filename.'.jpg';

        if (! is_file(public_path($path))) {
            throw new RuntimeException('Foto demo tidak ditemukan: '.$path);
        }

        $listing->photos()->updateOrCreate(
            ['path' => $path],
            ['sort_order' => 0],
        );
    }
}
