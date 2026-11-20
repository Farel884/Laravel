<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $this->validatedFilters($request);

        $salesQuery = $this->filteredSalesQuery($validated)
            ->with(['product.category', 'user'])
            ->latest('sales.sold_at');

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

    public function export(Request $request): StreamedResponse
    {
        $validated = $this->validatedFilters($request);
        $sales = $this->filteredSalesQuery($validated)
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->select([
                'sales.sold_at',
                'products.product_code',
                'products.name as product_name',
                'sales.quantity',
                'sales.unit_price',
                'users.name as recorded_by',
            ])
            ->orderBy('sales.sold_at')
            ->orderBy('sales.id')
            ->cursor();

        return response()->streamDownload(function () use ($sales): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new \RuntimeException('Unable to open CSV output stream.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Kode Produk', 'Nama Produk', 'Jumlah', 'Harga Satuan', 'Total', 'Dicatat Oleh'], ',', '"', '');

            foreach ($sales as $sale) {
                $quantity = (int) $sale->quantity;
                $unitPrice = (float) $sale->unit_price;

                fputcsv($output, [
                    $sale->sold_at,
                    $this->spreadsheetSafeValue($sale->product_code),
                    $this->spreadsheetSafeValue($sale->product_name),
                    $quantity,
                    number_format($unitPrice, 2, '.', ''),
                    number_format($quantity * $unitPrice, 2, '.', ''),
                    $this->spreadsheetSafeValue($sale->recorded_by),
                ], ',', '"', '');
            }

            fclose($output);
        }, 'laporan-penjualan-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    /**
     * @return array{date_from?: string, date_to?: string, product_id?: int|string}
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);
    }

    /**
     * @param  array{date_from?: string, date_to?: string, product_id?: int|string}  $filters
     * @return Builder<Sale>
     */
    private function filteredSalesQuery(array $filters): Builder
    {
        return Sale::query()
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('sales.sold_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('sales.sold_at', '<=', $date))
            ->when($filters['product_id'] ?? null, fn (Builder $query, int|string $productId) => $query->where('sales.product_id', $productId));
    }

    private function spreadsheetSafeValue(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_match('/^\s*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
