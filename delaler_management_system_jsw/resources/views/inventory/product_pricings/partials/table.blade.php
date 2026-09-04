<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th class="ps-4">S.No</th>
                <th>Product Info</th>
                <th>Price / MT</th>
                <th>Taxes & Disc.</th>
                <th>Status</th>
                <th class="text-center pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pricings ?? [] as $index => $pricing)
                <tr>
                    <td class="ps-4 text-muted fw-bold">{{ $pricings->firstItem() + $index }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $pricing->product->product_name ?? 'N/A' }}</div>
                        <div class="small text-muted">SKU: {{ $pricing->product->sku_code ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="fw-bold text-primary">₹{{ number_format($pricing->price_per_mt, 2) }}</div>
                        <div class="small text-muted">{{ $pricing->price_type ?? 'Standard' }}</div>
                    </td>
                    <td>
                        <div class="small">
                            GST: <span
                                class="fw-bold">{{ $pricing->gst_percentage ? $pricing->gst_percentage . '%' : '0%' }}</span><br>
                            Discount: <span
                                class="text-danger">₹{{ number_format($pricing->discount_amount ?? 0, 2) }}</span>
                        </div>
                    </td>
                    <td>
                        @if ($pricing->status === 'active')
                            <span
                                class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Active</span>
                        @elseif ($pricing->status === 'inactive')
                            <span
                                class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">Inactive</span>
                        @else
                            <span
                                class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">Blocked</span>
                        @endif
                    </td>
                    <td class="text-center pe-4">
                        <button class="btn btn-sm btn-light text-primary btn-edit-pricing shadow-sm border"
                            data-id="{{ Crypt::encryptString($pricing->id) }}" title="Edit Pricing">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-tags fs-1 mb-3 opacity-50"></i>
                            <h5 class="fw-bold">No Pricing Records Found</h5>
                            <p class="mb-0">There are no pricing configurations matching your criteria.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($pricings->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-4 bg-light border-top gap-3">
        <div class="text-muted small">
            Showing <span class="fw-bold">{{ $pricings->firstItem() }}</span> to <span
                class="fw-bold">{{ $pricings->lastItem() }}</span> of <span
                class="fw-bold">{{ $pricings->total() }}</span> entries
        </div>
        <div class="pagination-wrapper">
            {{ $pricings->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
