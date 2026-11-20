@extends('layouts.app')

@section('title', 'Laporan Penjualan')
@section('page_heading', 'Laporan Penjualan')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <p class="text-muted mb-3 mb-md-0">Ringkasan transaksi penjualan berdasarkan periode dan produk.</p>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Catat Penjualan
        </a>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pendapatan</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Produk Terjual</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalQuantity) }} unit</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Jumlah Transaksi</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($transactionCount) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('sales.index') }}">
                <div class="form-row align-items-end">
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold" for="date-from">Tanggal Mulai</label>
                        <input id="date-from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold" for="date-to">Tanggal Akhir</label>
                        <input id="date-to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold" for="product-filter">Produk</label>
                        <select id="product-filter" name="product_id" class="form-control">
                            <option value="">Semua produk</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>
                                    {{ $product->name }} - {{ $product->product_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
            <a href="{{ route('sales.export', request()->only(['date_from', 'date_to', 'product_id'])) }}" class="btn btn-outline-success">
                <i class="fas fa-file-csv mr-1"></i>Unduh CSV
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Penjualan</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Produk</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Harga</th>
                                    <th>Total</th>
                                    <th>Dicatat Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    <tr>
                                        <td>{{ $sale->sold_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $sale->product->name }}</div>
                                            <small class="text-muted">{{ $sale->product->product_code }}</small>
                                        </td>
                                        <td class="text-center">{{ number_format($sale->quantity) }}</td>
                                        <td>Rp {{ number_format((float) $sale->unit_price, 0, ',', '.') }}</td>
                                        <td class="font-weight-bold">Rp {{ number_format((float) $sale->quantity * (float) $sale->unit_price, 0, ',', '.') }}</td>
                                        <td>{{ $sale->user?->name ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">Belum ada transaksi penjualan pada filter ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">{{ $sales->links() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Produk Terlaris</h6>
                </div>
                <div class="card-body">
                    @forelse ($topProducts as $topProduct)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="font-weight-bold">{{ $topProduct->name }}</div>
                                <small class="text-muted">Rp {{ number_format((float) $topProduct->revenue, 0, ',', '.') }}</small>
                            </div>
                            <span class="badge badge-primary badge-pill">{{ number_format($topProduct->quantity_sold) }} unit</span>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0 py-4">Belum ada data produk terlaris.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
