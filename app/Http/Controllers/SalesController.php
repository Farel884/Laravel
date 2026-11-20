<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $salesQuery = Sale::query()
            ->with(['product.category', 'user'])
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('sold_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('sold_at', '<=', $date))
            ->when($validated['product_id'] ?? null, fn ($query, $productId) => $query->where('product_id', $productId))
            ->latest('sold_at');

        $sales = (clone $salesQuery)->paginate(10)->withQueryString();
        $totalQuantity = (clone $salesQuery)->sum('quantity');
        $totalRevenue = (float) (clone $salesQuery)->sum(DB::raw('quantity * unit_price'));
        $transactionCount = (clone $salesQuery)->count();
        $products = Product::with('category')->orderBy('name')->get();

        $topProducts = (clone $salesQuery)
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->select('products.id', 'products.name')
            ->selectRaw('SUM(sales.quantity) as quantity_sold')
            ->selectRaw('SUM(sales.quantity * sales.unit_price) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get();

        return view('sales.index', compact(
            'sales',
            'products',
            'totalQuantity',
            'totalRevenue',
            'transactionCount',
            'topProducts',
        ));
    }

    public function create(): View
    {
        $products = Product::with('category')
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('sales.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'sold_at' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $product = Product::whereKey($validated['product_id'])->lockForUpdate()->firstOrFail();

            if ($validated['quantity'] > $product->stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah penjualan melebihi stok yang tersedia.',
                ]);
            }

            Sale::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $product->price,
                'sold_at' => $validated['sold_at'],
            ]);

            $product->decrement('stock', $validated['quantity']);
        });

        return redirect()->route('sales.index')
            ->with('success', 'Penjualan berhasil dicatat dan stok telah diperbarui.');
    }
}
