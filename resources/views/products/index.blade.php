@extends('layouts.app')

@section('title', 'Data Produk')
@section('page_heading', 'Data Produk')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h5 class="font-weight-bold text-gray-800 mb-1">Daftar Produk</h5>
                <p class="text-muted small mb-0">Cari dan kelola seluruh produk yang tersedia.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="{{ route('favorites.index') }}" class="btn btn-outline-danger">
                    <i class="fas fa-heart mr-1"></i>Favorit
                </a>
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('products.create') }}" class="btn btn-primary ml-2">
                        <i class="fas fa-plus mr-2"></i>Tambah Produk
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="bg-light rounded p-3 mb-4">
            <div class="form-row align-items-end">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="small font-weight-bold text-gray-700" for="product-search">Cari Produk</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        </div>
                        <input id="product-search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Kode, nama, atau merek">
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="small font-weight-bold text-gray-700" for="category-filter">Kategori</label>
                    <select id="category-filter" name="category_id" class="form-control">
                        <option value="">Semua kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary btn-block" type="submit">
                        <i class="fas fa-filter mr-1"></i>Filter
                    </button>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="border-0">Gambar</th>
                        <th class="border-0">Kode</th>
                        <th class="border-0">Produk</th>
                        <th class="border-0">Kategori</th>
                        <th class="border-0">Merek</th>
                        <th class="border-0">Harga</th>
                        <th class="border-0 text-center">Stok</th>
                        <th class="border-0 text-center">Favorit</th>
                        @if (auth()->user()->role === 'admin')
                            <th class="border-0 text-right">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="Gambar Produk" class="img-thumbnail" style="max-width: 100px;">
                                @else
                                    <span class="text-muted">Tidak ada gambar</span>
                                @endif
                            </td>
                            <td><span class="badge badge-light border">{{ $product->product_code }}</span></td>
                            <td class="font-weight-bold text-gray-800">{{ $product->name }}</td>
                            <td><span class="text-muted">{{ $product->category->name }}</span></td>
                            <td>{{ $product->brand ?: '-' }}</td>
                            <td class="font-weight-bold">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="badge badge-pill {{ $product->stock <= 5 ? 'badge-danger' : ($product->stock <= 20 ? 'badge-warning' : 'badge-success') }}">
                                    {{ number_format($product->stock) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($product->favoritedBy->isNotEmpty())
                                    <form method="POST" action="{{ route('products.favorite.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit" title="Hapus dari favorit" aria-label="Hapus {{ $product->name }} dari favorit">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('products.favorite.store', $product) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Tambah ke favorit" aria-label="Tambah {{ $product->name }} ke favorit">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                            @if (auth()->user()->role === 'admin')
                                <td class="text-right">
                                    <div class="d-inline-flex align-items-center">
                                        <a class="btn btn-sm btn-outline-warning mr-2" href="{{ route('products.edit', $product) }}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('products.destroy', $product) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus produk?')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'admin' ? 9 : 8 }}" class="text-center py-5">
                                <i class="fas fa-box-open fa-2x text-gray-300 mb-3"></i>
                                <p class="text-muted mb-0">Belum ada produk yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-4">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection