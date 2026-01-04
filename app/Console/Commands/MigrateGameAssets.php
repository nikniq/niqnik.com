<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class MigrateGameAssets extends Command
{
    protected $signature = 'games:migrate-assets {--move : Move files instead of copying}';
    protected $description = 'Migrate game asset files from public_html/assets/games to storage/app/public/games and create storage symlink if needed.';

    public function handle(): int
    {
        $files = new Filesystem();

        $source = public_path('assets/games');
        $destRoot = storage_path('app/public/wp-uploads');

        if (! $files->isDirectory($source)) {
            $this->error("Source directory not found: {$source}");
            return 1;
        }

        if (! $files->isDirectory($destRoot)) {
            $files->makeDirectory($destRoot, 0755, true);
            $this->info("Created destination: {$destRoot}");
        }

        $items = $files->files($source);
        if (empty($items)) {
            $this->info('No files found to migrate.');
            return 0;
        }

        foreach ($items as $item) {
            $name = $item->getFilename();
            $src = $item->getPathname();

            $year = date('Y');
            $month = date('m');
            $subdir = $year . DIRECTORY_SEPARATOR . $month;
            $destDir = $destRoot . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;

            if (! $files->isDirectory($destDir)) {
                $files->makeDirectory($destDir, 0755, true);
            }

            $target = $destDir . DIRECTORY_SEPARATOR . $name;

            if ($this->option('move')) {
                $files->move($src, $target);
                $this->info("Moved: {$name}");
            } else {
                $files->copy($src, $target);
                $this->info("Copied: {$name}");
            }

            // Record media entry using storage disk 'public' and path like wp-uploads/YYYY/MM/filename
            $storagePath = 'wp-uploads/' . $year . '/' . $month . '/' . $name;
            try {
                $mime = $files->mimeType($target);
            } catch (\Exception $e) {
                $mime = null;
            }
            $size = $files->size($target) ?? null;

            \App\Models\Media::create([
                'disk' => 'public',
                'path' => $storagePath,
                'filename' => $name,
                'mime_type' => $mime,
                'size' => $size,
            ]);
        }

        // Ensure public storage link exists (public/storage -> storage/app/public)
        $this->callSilent('storage:link');
        $this->info('Checked/created storage symlink (php artisan storage:link)');

        $this->info('Migration complete.');

        return 0;
    }
}
