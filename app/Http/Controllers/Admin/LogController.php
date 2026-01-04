<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class LogController extends Controller
{
    private const MAX_LINES = 200;

    public function index(Request $request): View
    {
        $path = storage_path('logs/laravel.log');
        $lines = [];
        $missing = false;

        try {
            if (File::exists($path)) {
                $contents = File::get($path);
                $lines = array_slice(array_reverse(preg_split('/\r?\n/', $contents)), 0, self::MAX_LINES);
                $lines = array_reverse(array_filter($lines, fn ($line) => $line !== ''));
            } else {
                $missing = true;
            }
        } catch (FileNotFoundException $e) {
            $missing = true;
        }

        return view('admin.logs.index', [
            'lines' => $lines,
            'missing' => $missing,
            'path' => $path,
            'maxLines' => self::MAX_LINES,
        ]);
    }
}
