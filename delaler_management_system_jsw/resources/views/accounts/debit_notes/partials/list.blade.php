<div class="table-responsive w-100"
    style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th>Order No</th>
                <th>Dealer</th>
                <th>Debit Date</th>
                <th>Base Amount</th>
                <th>GST Amount (18%)</th>
                <th>Total Amount</th>
                <th>Quantity/Nos</th>
                <th>Reason</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($debitNotes ?? [] as $debitNote)
                @php
                    $dealerName = $debitNote->paymentTrack->invoice->buyer->dealer->dealer_name 
                        ?? $debitNote->paymentTrack->invoice->buyer->company_name 
                        ?? 'N/A';
                    $baseAmount = $debitNote->base_amount ?? ($debitNote->amount ? round($debitNote->amount / 1.18, 2) : 0);
                    $gstAmount = $debitNote->gst_amount ?? ($debitNote->amount ? round($debitNote->amount - $baseAmount, 2) : 0);
                @endphp
                <tr>
                    <td>
                        @if (!empty($debitNote->paymentTrack->invoice->user_invoice_no))
                            <span class="fw-bold text-danger">{{ $debitNote->paymentTrack->invoice->user_invoice_no }}</span>
                            <br><small class="text-secondary fw-semibold">Ref: {{ $debitNote->paymentTrack->invoice->invoice_no }}</small>
                        @else
                            <span class="fw-bold text-danger">{{ $debitNote->paymentTrack->invoice->invoice_no ?? 'N/A' }}</span>
                        @endif
                    </td>
                    <td>{{ $dealerName }}</td>
                    <td>{{ $debitNote->paymentTrack->transaction_date ? $debitNote->paymentTrack->transaction_date->format('d M Y') : 'N/A' }}</td>
                    <td>₹ {{ inr($baseAmount) }}</td>
                    <td><span class="text-muted">+₹ {{ inr($gstAmount) }}</span></td>
                    <td class="fw-bold text-dark">₹ {{ inr($debitNote->amount) }}</td>
                    <td>{{ $debitNote->nos }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($debitNote->reason, 30) }}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-info view-debit-note"
                            data-debit-note="{{ json_encode([
                                'order_no' => $debitNote->paymentTrack->invoice->invoice_no ?? 'N/A',
                                'dealer_name' => $dealerName,
                                'debit_date' => $debitNote->paymentTrack->transaction_date
                                    ? $debitNote->paymentTrack->transaction_date->format('d M Y')
                                    : 'N/A',
                                'base_amount' => '₹ ' . inr($baseAmount),
                                'gst_amount' => '₹ ' . inr($gstAmount),
                                'amount' => '₹ ' . inr($debitNote->amount),
                                'nos' => $debitNote->nos,
                                'reason' => $debitNote->reason,
                                'transaction_id' => $debitNote->paymentTrack->transaction_id ?? 'N/A',
                            ]) }}"
                            title="View Details">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">No debit notes found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-end">
    {{ $debitNotes->links('pagination::bootstrap-5') }}
</div>
