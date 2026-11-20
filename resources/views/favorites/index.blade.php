@extends('layouts.app')

@section('title', 'Produk Favorit')
@section('page_heading', 'Produk Favorit')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h5 class="font-weight-bold text-gray-800 mb-1">Koleksi Favoritmu</h5>
                <p class="text-muted small mb-0">Produk yang kamu simpan supaya gampang ditemukan lagi.</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-primary mt-3 mt-md-0">
                <i class="fas fa-boxes mr-1"></i>Lihat Semua Produk
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Kode</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td><span class="badge badge-light border">{{ $product->product_code }}</span></td>
                                <td class="font-weight-bold text-gray-800">{{ $product->name }}</td>
                                <td>{{ $product->category->name }}</td>
                                <td>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge badge-pill {{ $product->stock <= 5 ? 'badge-danger' : ($product->stock <= 20 ? 'badge-warning' : 'badge-success') }}">
                                        {{ number_format($product->stock) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('products.favorite.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Hapus {{ $product->name }} dari favorit">
                                            <i class="fas fa-heart mr-1"></i>Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="far fa-heart fa-2x text-gray-300 mb-3"></i>
                                    <p class="text-muted mb-0">Belum ada produk favorit.</p>
                                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-primary mt-3">Jelajahi Produk</a>
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
