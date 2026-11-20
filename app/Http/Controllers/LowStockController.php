<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LowStockController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'threshold' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $threshold = (int) ($validated['threshold'] ?? 5);
        $products = Product::with('category')
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.low-stock', compact('products', 'threshold'));
    }
}
