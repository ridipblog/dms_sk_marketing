<div class="table-responsive w-100"
    style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>SKU Code</th>
                <th class="text-end">Opening Stock (MT)</th>
                <th class="text-end">Total Sale (MT)</th>
                <th class="text-end">Total Purchase (MT)</th>
                <th class="text-end">Closing Stock (MT)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products ?? [] as $index => $prod)
                <tr>
                    <td>{{ $products->firstItem() + $loop->index }}</td>
                    <td class="fw-bold text-dark">
                        <i class="fas fa-box text-primary me-2"></i>
                        {{ $prod->product_name ?? 'N/A' }}
                    </td>
                    <td><code>{{ $prod->sku_code ?? 'N/A' }}</code></td>
                    <td class="text-end fw-bold text-info">
                        {{ number_format($prod->period_opening_stock ?? 0, 3) }}
                    </td>
                    <td class="text-end fw-bold text-danger">
                        {{ number_format($prod->period_sale ?? 0, 3) }}
                    </td>
                    <td class="text-end fw-bold text-success">
                        {{ number_format($prod->period_purchase ?? 0, 3) }}
                    </td>
                    <td class="text-end fw-bold text-primary">
                        {{ number_format($prod->period_closing_stock ?? 0, 3) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No products found for the selected company.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-end pe-3">
    {{ $products->links('pagination::bootstrap-5') }}
</div>
