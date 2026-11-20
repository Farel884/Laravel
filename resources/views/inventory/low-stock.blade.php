@extends('layouts.app')

@section('title', 'Stok Menipis')
@section('page_heading', 'Peringatan Stok Menipis')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="font-weight-bold text-gray-800 mb-1">Daftar Restock</h5>
            <p class="text-muted small mb-0">Produk dengan jumlah stok sama dengan atau di bawah batas yang dipilih.</p>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.low-stock') }}" class="bg-light rounded p-3 mb-4">
                <div class="form-row align-items-end">
                    <div class="col-md-4">
                        <label for="threshold" class="small font-weight-bold">Batas stok menipis</label>
                        <input id="threshold" type="number" name="threshold" min="0" max="1000" class="form-control" value="{{ $threshold }}">
                    </div>
                    <div class="col-md-2 mt-3 mt-md-0">
                        <button type="submit" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-filter mr-1"></i>Terapkan
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Kode</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th class="text-center">Stok Tersisa</th>
                            @if (auth()->user()->role === 'admin')
                                <th class="text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td><span class="badge badge-light border">{{ $product->product_code }}</span></td>
                                <td class="font-weight-bold">{{ $product->name }}</td>
                                <td>{{ $product->category->name }}</td>
                                <td class="text-center"><span class="badge badge-danger badge-pill">{{ number_format($product->stock) }}</span></td>
                                @if (auth()->user()->role === 'admin')
                                    <td class="text-right">
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-box-open mr-1"></i>Restock
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role === 'admin' ? 5 : 4 }}" class="text-center py-5">
                                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                                    <p class="text-muted mb-0">Tidak ada produk dengan stok di bawah batas ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">{{ $products->links() }}</div>
        </div>
    </div>
@endsection
