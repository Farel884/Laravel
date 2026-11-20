<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $products = $request->user()
            ->favoriteProducts()
            ->with('category')
            ->latest('product_user.created_at')
            ->paginate(10);

        return view('favorites.index', compact('products'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->user()->favoriteProducts()->syncWithoutDetaching([$product->id]);

        return back()->with('success', 'Produk ditambahkan ke favorit.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $request->user()->favoriteProducts()->detach($product->id);

        return back()->with('success', 'Produk dihapus dari favorit.');
    }
}
