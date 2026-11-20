@extends('layouts.app')

@section('title', 'Catat Penjualan')
@section('page_heading', 'Catat Penjualan')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Tambah Transaksi Penjualan</h6>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($products->isEmpty())
                        <div class="alert alert-warning mb-0">
                            Tidak ada produk dengan stok tersedia. Tambahkan stok atau produk terlebih dahulu.
                        </div>
                    @else
                        <form method="POST" action="{{ route('sales.store') }}">
                            @csrf
                            <div class="form-group">
                                <label for="product_id" class="font-weight-bold">Produk</label>
                                <select id="product_id" name="product_id" class="form-control" required>
                                    <option value="">Pilih produk</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $product->stock }}" @selected(old('product_id') == $product->id)>
                                            {{ $product->name }} - Stok {{ $product->stock }} - Rp {{ number_format((float) $product->price, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="quantity" class="font-weight-bold">Jumlah Terjual</label>
                                    <input id="quantity" type="number" name="quantity" min="1" class="form-control" value="{{ old('quantity') }}" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="sold_at" class="font-weight-bold">Tanggal Penjualan</label>
                                    <input id="sold_at" type="datetime-local" name="sold_at" class="form-control" value="{{ old('sold_at', now()->format('Y-m-d\TH:i')) }}" required>
                                </div>
                            </div>
                            <div class="alert alert-light border">
                                <strong>Estimasi total:</strong>
                                <span id="estimated-total">Rp 0</span>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Simpan Penjualan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const productSelect = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity');
        const estimatedTotal = document.getElementById('estimated-total');

        function updateEstimate() {
            if (!productSelect || !quantityInput) {
                return;
            }

            const selected = productSelect.options[productSelect.selectedIndex];
            const price = Number(selected.dataset.price || 0);
            const quantity = Number(quantityInput.value || 0);
            const stock = Number(selected.dataset.stock || 0);

            if (quantity > stock && stock > 0) {
                quantityInput.setCustomValidity('Jumlah melebihi stok tersedia (' + stock + ').');
            } else {
                quantityInput.setCustomValidity('');
            }

            estimatedTotal.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(price * quantity);
        }

        productSelect?.addEventListener('change', updateEstimate);
        quantityInput?.addEventListener('input', updateEstimate);
        updateEstimate();
    </script>
@endpush
