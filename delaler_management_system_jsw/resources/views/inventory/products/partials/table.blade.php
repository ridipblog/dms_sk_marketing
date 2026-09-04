<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th class="ps-4">S.No</th>
                <th>Product Info</th>
                <th>Category</th>
                <th>SKU Code</th>
                <th>HSN Code</th>
                <th>Details</th>
                <th>Current Stock</th>
                <th>Status</th>
                <th class="text-center pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products ?? [] as $index => $product)
                <tr>
                    <td class="ps-4 text-muted fw-bold">{{ $products->firstItem() + $index }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $product->product_name ?? 'N/A' }}</div>
                        @if ($product->base_price ?? 0)
                            <div class="small text-muted">Base Price: ₹{{ number_format($product->base_price, 2) }}</div>
                        @endif
                    </td>
                    <td><span
                            class="badge bg-light text-dark border">{{ $product->category->category_name ?? 'N/A' }}</span>
                    </td>
                    <td><span class="text-muted fw-bold">{{ $product->sku_code ?? 'N/A' }}</span></td>
                    <td><span class="text-muted">{{ $product->hsn_code ?? 'N/A' }}</span></td>
                    <td>
                        <div class="small text-muted">
                            Size: {{ $product->size ?? 'N/A' }}<br>
                            Unit: {{ $product->unit ?? 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <span class="fw-bold text-dark">{{ number_format($product->stock_quantity ?? 0, 3) }} {{ $product->unit ?? 'MT' }}</span>
                    </td>
                    <td>
                        @if (($product->status ?? 'N/A') === 'active')
                            <span
                                class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Active</span>
                        @elseif (($product->status ?? 'N/A') === 'inactive')
                            <span
                                class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">Inactive</span>
                        @else
                            <span
                                class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">Blocked</span>
                        @endif
                    </td>
                    <td class="text-center pe-4">
                        <button class="btn btn-sm btn-light text-primary btn-edit-product shadow-sm border"
                            data-id="{{ Crypt::encryptString($product->id) }}" title="Edit Product">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-box-open fs-1 mb-3 opacity-50"></i>
                            <h5 class="fw-bold">No Products Found</h5>
                            <p class="mb-0">There are no products matching your search criteria.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($products->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-4 bg-light border-top gap-3">
        <div class="text-muted small">
            Showing <span class="fw-bold">{{ $products->firstItem() }}</span> to <span
                class="fw-bold">{{ $products->lastItem() }}</span> of <span
                class="fw-bold">{{ $products->total() }}</span> entries
        </div>
        <div class="pagination-wrapper">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
