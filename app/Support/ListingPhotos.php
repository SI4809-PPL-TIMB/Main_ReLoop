<?php

namespace App\Support;

use App\Models\Listing;
use App\Models\ListingPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Unggah foto barang: diperkecil (sisi terpanjang 1280 px) dan dikompres JPEG kualitas 80. */
class ListingPhotos
{
    private const SISI_MAKS = 1280;

    private const KUALITAS = 80;

    public static function simpan(Listing $listing, array $files): void
    {
        $urutan = (int) $listing->photos()->max('sort_order');
        $ada = $listing->photos()->exists();

        foreach (array_values($files) as $i => $file) {
            $path = 'listings/'.$listing->id.'/'.Str::uuid().'.jpg';
            Storage::disk('public')->put($path, self::kompres($file));
            $listing->photos()->create(['path' => $path, 'sort_order' => $ada ? $urutan + $i + 1 : $i]);
        }
    }

    public static function hapus(ListingPhoto $foto): void
    {
        Storage::disk('public')->delete($foto->path);
        $foto->delete();
    }

    private static function kompres(UploadedFile $file): string
    {
        $img = imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            $putar = [3 => 180, 6 => -90, 8 => 90][@exif_read_data($file->getRealPath())['Orientation'] ?? 1] ?? 0;
            $putar && ($img = imagerotate($img, $putar, 0));
        }

        [$w, $h] = [imagesx($img), imagesy($img)];
        $skala = min(1, self::SISI_MAKS / max($w, $h));
        $baru = imagecreatetruecolor((int) round($w * $skala), (int) round($h * $skala));
        imagefill($baru, 0, 0, imagecolorallocate($baru, 255, 255, 255)); // latar putih untuk PNG transparan
        imagecopyresampled($baru, $img, 0, 0, 0, 0, imagesx($baru), imagesy($baru), $w, $h);

        ob_start();
        imagejpeg($baru, null, self::KUALITAS);

        return ob_get_clean();
    }
}
