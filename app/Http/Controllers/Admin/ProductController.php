<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::orderBy('name')->paginate(12),
        ]);
    }

    public function create(): View
    {
        $media = \App\Models\Media::orderBy('created_at', 'desc')->limit(50)->get();
        return view('admin.products.create', compact('media'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! Schema::hasColumn('products', 'media_id')) {
            unset($data['media_id']);
        }
        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product added to the catalog.');
    }

    public function edit(Product $product): View
    {
        $media = \App\Models\Media::orderBy('created_at', 'desc')->limit(50)->get();
        return view('admin.products.edit', compact('product', 'media'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        if (! Schema::hasColumn('products', 'media_id')) {
            unset($data['media_id']);
        }
        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->licenses()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('status', 'Cannot delete a product with attached licenses.');
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product removed.');
    }
}
