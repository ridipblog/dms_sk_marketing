<div class="table-responsive w-100"
    style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th>Order No</th>
                <th>Dealer</th>
                <th>Credit Date</th>
                <th>Amount</th>
                <th>Quantity/Nos</th>
                <th>Reason</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($creditNotes ?? [] as $creditNote)
                @php
                    $dealerName = $creditNote->paymentTrack->invoice->buyer->dealer->dealer_name 
                        ?? $creditNote->paymentTrack->invoice->buyer->company_name 
                        ?? 'N/A';
                    $reason = $creditNote->cashDiscountSlab
                        ? $creditNote->cashDiscountSlab->slab_name
                        : $creditNote->paymentTrack->remarks ?? 'N/A';
                @endphp
                <tr>
                    <td><span
                            class="fw-bold text-success">{{ $creditNote->paymentTrack->invoice->invoice_no ?? 'N/A' }}</span>
                    </td>
                    <td>{{ $dealerName }}</td>
                    <td>{{ $creditNote->paymentTrack->transaction_date ? $creditNote->paymentTrack->transaction_date->format('d M Y') : 'N/A' }}
                    </td>
                    <td>₹ {{ inr($creditNote->amount) }}</td>
                    <td>{{ $creditNote->nos }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($reason, 30) }}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-info view-credit-note"
                            data-credit-note="{{ json_encode([
                                'order_no' => $creditNote->paymentTrack->invoice->invoice_no ?? 'N/A',
                                'dealer_name' => $dealerName,
                                'credit_date' => $creditNote->paymentTrack->transaction_date
                                    ? $creditNote->paymentTrack->transaction_date->format('d M Y')
                                    : 'N/A',
                                'amount' => '₹ ' . inr($creditNote->amount),
                                'nos' => $creditNote->nos,
                                'reason' => $reason,
                                'transaction_id' => $creditNote->paymentTrack->transaction_id ?? 'N/A',
                            ]) }}"
                            title="View Details">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No credit notes found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-end">
    {{ $creditNotes->links('pagination::bootstrap-5') }}
</div>
