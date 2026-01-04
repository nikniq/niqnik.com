<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $media = Media::orderBy('created_at', 'desc')->paginate(30);
        return view('admin.media.index', compact('media'));
    }

    public function create()
    {
        return view('admin.media.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,svg,webp', 'max:8192'],
        ]);

        $file = $request->file('file');
        $disk = 'public';
        $now = now();
        $path = 'wp-uploads/'.$now->format('Y').'/'.$now->format('m');
        $stored = $file->store($path, $disk);

        $media = Media::create([
            'disk' => $disk,
            'path' => $stored,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('admin.media.index')->with('success', 'Uploaded.');
    }

    public function destroy(Media $media)
    {
        try {
            if ($media->disk && Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
        } catch (\Exception $e) {
        }

        $media->delete();

        return redirect()->route('admin.media.index')->with('success', 'Deleted.');
    }
}
