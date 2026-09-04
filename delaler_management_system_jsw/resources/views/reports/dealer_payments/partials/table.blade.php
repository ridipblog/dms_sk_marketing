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
                <th>Voucher / Mode</th>
                <th class="text-end">Amount (₹)</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments ?? [] as $index => $payment)
                @php
                    $dealerName = $payment->invoice->buyer->dealer->dealer_name 
                        ?? $payment->invoice->buyer->company_name 
                        ?? 'N/A';
                    $voucherName = $payment->voucherTypeModel->name ?? $payment->payment_mode ?? 'N/A';
                @endphp
                <tr>
                    <td>{{ $payments->firstItem() + $loop->index }}</td>
                    <td class="fw-bold text-dark">{{ $dealerName }}</td>
                    <td>
                        <span class="fw-bold text-primary">
                            {{ $payment->invoice->invoice_no ?? 'N/A' }}
                        </span>
                    </td>
                    <td><code>{{ $payment->transaction_id ?? 'N/A' }}</code></td>
                    <td>{{ $payment->transaction_date ? $payment->transaction_date->format('d M Y') : 'N/A' }}</td>
                    <td><span class="badge bg-info text-dark">{{ $voucherName }}</span></td>
                    <td class="text-end fw-bold text-success">₹ {{ inr($payment->amount) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($payment->remarks, 35) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No payment records found matching the filter criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-end pe-3">
    {{ $payments->links('pagination::bootstrap-5') }}
</div>
