<?php

namespace App\Support;

use Illuminate\Database\Migrations\Migrator as BaseMigrator;
use Illuminate\Support\Collection;

/**
 * CustomMigrator mengatasi limitasi fungsi native glob() pada PHP di macOS/Linux
 * ketika path direktori project mengandung karakter bracket seperti "[GDP]".
 */
class CustomMigrator extends BaseMigrator
{
    /**
     * Get all of the migration files in a given path.
     *
     * @param  string|array  $paths
     * @return array<string, string>
     */
    public function getMigrationFiles($paths)
    {
        return (new Collection($paths))
            ->flatMap(function ($path) {
                if (str_ends_with($path, '.php')) {
                    return [$path];
                }

                $files = $this->files->glob($path.'/*_*.php');

                // Fallback jika glob() mengembalikan kosong karena karakter khusus di path direktori
                if (empty($files) && is_dir($path)) {
                    $realFiles = [];
                    foreach (scandir($path) as $file) {
                        if (preg_match('/^\d+.*\.php$/', $file)) {
                            $realFiles[] = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $file;
                        }
                    }
                    return $realFiles;
                }

                return $files;
            })
            ->filter()
            ->values()
            ->keyBy(fn ($file) => $this->getMigrationName($file))
            ->sortBy(fn ($file, $key) => $key)
            ->all();
    }
}
