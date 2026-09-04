<div class="table-responsive w-100"
    style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Product Name</th>
                <th class="text-end">Opening Stock (MT)</th>
                <th class="text-end">Sale (MT)</th>
                <th class="text-end">Purchase (MT)</th>
                <th class="text-end">Closing Stock (MT)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stockRecords ?? [] as $index => $rec)
                <tr>
                    <td>{{ $stockRecords->firstItem() + $loop->index }}</td>
                    <td class="fw-bold text-dark">
                        <i class="fas fa-calendar-day text-primary me-2"></i>
                        {{ $rec->date ? $rec->date->format('Y-m-d') : 'N/A' }}
                    </td>
                    <td>
                        <span class="fw-bold text-primary">
                            {{ $rec->product->product_name ?? 'N/A' }}
                        </span>
                        @if(!empty($rec->product->sku_code))
                            <br><small class="text-muted"><code>{{ $rec->product->sku_code }}</code></small>
                        @endif
                    </td>
                    <td class="text-end fw-bold text-info">
                        {{ number_format($rec->opening_stock, 3) }}
                    </td>
                    <td class="text-end fw-bold text-danger">
                        {{ number_format($rec->sale_quantity, 3) }}
                    </td>
                    <td class="text-end fw-bold text-success">
                        {{ number_format($rec->purchase_quantity, 3) }}
                    </td>
                    <td class="text-end fw-bold text-primary">
                        {{ number_format($rec->closing_stock, 3) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No daily product stock records found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-end pe-3">
    {{ $stockRecords->links('pagination::bootstrap-5') }}
</div>
