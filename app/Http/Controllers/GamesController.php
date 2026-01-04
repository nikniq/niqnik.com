<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GamesController extends Controller
{
    public function index(Request $request)
    {
        if (! config('games.enabled')) {
            abort(404);
        }
        $category = config('games.category', 'Game');
        $search = trim((string) $request->query('q', ''));

        $productsQuery = Product::query()
            ->with('media')
            ->where('category', $category);

        if ($search !== '') {
            $productsQuery->where(function ($b) use ($search) {
                $b->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('product_code', 'like', '%' . $search . '%');
            });
        }

        $products = $productsQuery->orderBy('name')->get();

        if ($request->wantsJson()) {
            $payload = $products->map(function ($product) {
                $tileImg = null;
                if (! empty($product->media) && ! empty($product->media->path)) {
                    try { $tileImg = Storage::disk($product->media->disk)->url($product->media->path); } catch (\Exception $e) { $tileImg = null; }
                }
                if (! $tileImg) {
                    $desc = strtolower($product->description ?? '');
                    if (Str::contains($desc, ['space','asteroid','rocket','satellite','cosmic','galaxy'])) { $file = 'rocket.svg'; }
                    elseif (Str::contains($desc, ['puzz','puzzle','match','brain'])) { $file = 'puzzle.svg'; }
                    elseif (Str::contains($desc, ['race','racer','racing','car','drive'])) { $file = 'racer.svg'; }
                    else { $file = 'joystick.svg'; }
                    $m = Media::where('filename', $file)->latest()->first();
                    $tileImg = $m ? Storage::disk($m->disk)->url($m->path) : asset('assets/games/' . $file);
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'url' => $product->url ?? url('/shop/' . ($product->product_code ?? '')),
                    'isExternal' => ! empty($product->url),
                    'thumbnail' => $tileImg,
                ];
            });

            return response()->json($payload);
        }

        // Do not fall back to config list by default; only show DB products.

        return view('themes.games.frontpage', [
            'products' => $products,
            'q' => $search,
        ]);
    }
}
