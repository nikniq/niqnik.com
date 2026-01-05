<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class CheckMediaFiles extends Command
{
    protected $signature = 'media:check-files';
    protected $description = 'List media DB records whose files are missing on the configured disk.';

    public function handle(): int
    {
        $missing = 0;

        Media::chunk(100, function ($items) use (&$missing) {
            foreach ($items as $m) {
                $disk = $m->disk ?? 'public';
                $path = $m->path ?? '';
                $exists = false;
                try {
                    $exists = Storage::disk($disk)->exists($path);
                } catch (\Exception $e) {
                    $this->error("[{$m->id}] error checking disk {$disk}: {$e->getMessage()}");
                    $missing++;
                    continue;
                }

                if (! $exists) {
                    $url = '';
                    try {
                        $url = Storage::disk($disk)->url($path);
                    } catch (\Exception $e) {
                        $url = 'url-error: '.$e->getMessage();
                    }

                    $this->line("[{$m->id}] missing - disk={$disk} path={$path} url={$url}");
                    $missing++;
                }
            }
        });

        if ($missing === 0) {
            $this->info('All media files present.');
            return 0;
        }

        $this->error("Found {$missing} missing media files.");
        return 2;
    }
}
