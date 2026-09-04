@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-boxes me-2"></i> Product Stocks Ledger
            </h4>
        </div>

        <!-- Filters & Search -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('inventory.stocks.index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="search" class="form-label fw-bold">Search Product</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" id="search" class="form-control" 
                                value="{{ request('search') }}" placeholder="Search by name, SKU code...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="category_id" class="form-label fw-bold">Category</label>
                        <select name="category_id" id="category_id" class="form-select select2">
                            <option value="">All Categories</option>
                            @foreach ($categories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('inventory.stocks.index') }}" class="btn btn-outline-secondary fw-bold">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stock Ledger List Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap table-sm" style="font-size: 0.75rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Product Name</th>
                                <th>SKU Code</th>
                                <th>HSN Code</th>
                                <th>Size</th>
                                <th>Unit</th>
                                <th>Base Price</th>
                                <th>Current Stock</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (isset($products) && $products->count() > 0)
                                @foreach ($products as $product)
                                    <tr>
                                        <td><span class="badge bg-light text-dark border">{{ $product->category->category_name ?? 'N/A' }}</span></td>
                                        <td><span class="fw-bold text-dark fs-7">{{ $product->product_name }}</span></td>
                                        <td><code class="text-secondary">{{ $product->sku_code }}</code></td>
                                        <td>{{ $product->hsn_code ?? '-' }}</td>
                                        <td>{{ $product->size ?? '-' }}</td>
                                        <td>{{ $product->unit }}</td>
                                        <td class="fw-bold text-primary">₹ {{ number_format($product->base_price, 2) }}</td>
                                        <td>
                                            @if ($product->stock_quantity <= 0.001)
                                                <span class="badge bg-danger fs-8">Out of Stock</span>
                                            @elseif ($product->stock_quantity <= 10)
                                                <span class="badge bg-warning text-dark fs-8">{{ number_format($product->stock_quantity, 3) }} MT (Low)</span>
                                            @else
                                                <span class="badge bg-success fs-8">{{ number_format($product->stock_quantity, 3) }} MT</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-success adjust-stock-btn fw-bold"
                                                data-id="{{ Crypt::encryptString($product->id) }}"
                                                data-name="{{ $product->product_name }}"
                                                data-stock="{{ $product->stock_quantity }}"
                                                title="Adjust Inventory Stock">
                                                <i class="fas fa-sliders-h me-1"></i> Adjust
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No products found in stock ledger.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                @if (isset($products) && $products->hasPages())
                    <div class="p-3 border-top">
                        {{ $products->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="adjustStockForm" action="{{ route('inventory.stocks.adjust') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" id="adjust_product_id">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="adjustStockModalLabel">Adjust Inventory Stock</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold d-block">Product: <span class="text-primary" id="adjustProductNameDisplay">-</span></label>
                            <label class="form-label fw-bold d-block">Current Stock: <span class="text-success" id="adjustCurrentStockDisplay">0.000 MT</span></label>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="adjustment_type" class="form-label fw-bold">Adjustment Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="adjustment_type" name="adjustment_type" required>
                                    <option value="add">Add Stock (+)</option>
                                    <option value="subtract">Subtract Stock (-)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label fw-bold">Quantity (MT) <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" min="0.001" class="form-control" id="quantity" name="quantity" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label fw-bold">Adjustment Reason / Remarks <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Explain why the stock is being adjusted..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success fw-bold">Apply Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Open Adjust Stock Modal
            $('.adjust-stock-btn').click(function () {
                let encId = $(this).data('id');
                let name = $(this).data('name');
                let stock = parseFloat($(this).data('stock')) || 0;

                $('#adjust_product_id').val(encId);
                $('#adjustProductNameDisplay').text(name);
                $('#adjustCurrentStockDisplay').text(stock.toFixed(3) + ' MT');
                
                $('#adjustStockForm').trigger('reset');
                $('#adjustStockModal').modal('show');
            });

            // Submit Adjust Stock Form
            $('#adjustStockForm').submit(function (e) {
                e.preventDefault();
                let form = $(this);
                let btn = form.find('button[type="submit"]');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: form.attr('action'),
                    type: "POST",
                    data: form.serialize(),
                    success: function (response) {
                        btn.prop('disabled', false).text('Apply Adjustment');
                        if (response.success) {
                            $('#adjustStockModal').modal('hide');
                            alert(response.message);
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function () {
                        btn.prop('disabled', false).text('Apply Adjustment');
                        alert('Something went wrong. Please try again.');
                    }
                });
            });
        });
    </script>
@endpush
