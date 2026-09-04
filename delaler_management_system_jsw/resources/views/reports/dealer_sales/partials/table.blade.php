<div class="table-responsive w-100"
    style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Dealer Name</th>
                <th>Order / Invoice No</th>
                <th>Transaction ID</th>
                <th>Transaction Date</th>
                <th>Voucher Type</th>
                <th class="text-end">Amount (₹)</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales ?? [] as $index => $sale)
                @php
                    $dealerName = $sale->invoice->buyer->dealer->dealer_name 
                        ?? $sale->invoice->buyer->company_name 
                        ?? 'N/A';
                    $voucherName = $sale->voucherTypeModel->name ?? $sale->payment_mode ?? 'Sales';
                @endphp
                <tr>
                    <td>{{ $sales->firstItem() + $loop->index }}</td>
                    <td class="fw-bold text-dark">{{ $dealerName }}</td>
                    <td>
                        <span class="fw-bold text-danger">
                            {{ $sale->invoice->invoice_no ?? 'N/A' }}
                        </span>
                    </td>
                    <td><code>{{ $sale->transaction_id ?? 'N/A' }}</code></td>
                    <td>{{ $sale->transaction_date ? $sale->transaction_date->format('d M Y') : 'N/A' }}</td>
                    <td><span class="badge bg-warning text-dark">{{ $voucherName }}</span></td>
                    <td class="text-end fw-bold text-danger">₹ {{ inr($sale->amount) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($sale->remarks, 35) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No sales or debit records found matching the filter criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-end pe-3">
    {{ $sales->links('pagination::bootstrap-5') }}
</div>
