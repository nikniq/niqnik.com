<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WpPostController extends Controller
{
    public function index(Request $request)
    {
        if (! config('posts.enabled', true)) {
            abort(404);
        }

        $model = config('posts.model', \App\Models\WpPost::class);

        $posts = $model::query()
            ->published()
            ->orderBy('post_date', 'desc')
            ->paginate(10);

        return view('posts.index', compact('posts'));
    }

    public function show($slug)
    {
        if (! config('posts.enabled', true)) {
            abort(404);
        }

        $model = config('posts.model', \App\Models\WpPost::class);

        $post = $model::where('post_name', $slug)
            ->published()
            ->firstOrFail();

        return view('posts.show', compact('post'));
    }
}
