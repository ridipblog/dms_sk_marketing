<!-- Summary Header Banner -->
<div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="small text-muted fw-semibold">
        <i class="fas fa-file-invoice text-danger me-1"></i> Total Debit Notes: <strong class="text-dark">{{ $totalCount ?? $debitNotes->total() }}</strong>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="small">
            <span class="text-muted">Base Total:</span> <strong class="text-primary">₹ {{ inr($totalBaseAmount ?? 0) }}</strong>
        </div>
        <div class="small">
            <span class="text-muted">GST Total:</span> <strong class="text-warning text-dark">₹ {{ inr($totalGstAmount ?? 0) }}</strong>
        </div>
        <div class="small bg-white px-3 py-1 rounded border shadow-sm">
            <span class="text-muted fw-bold">Sum of Total Amount:</span> <strong class="text-danger fs-6 ms-1">₹ {{ inr($totalAmount ?? 0) }}</strong>
        </div>
    </div>
</div>

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
        @if(count($debitNotes) > 0)
        <tfoot class="table-light fw-bold border-top">
            <tr>
                <td colspan="3" class="text-end text-dark">Total (All Filtered Records):</td>
                <td class="text-primary">₹ {{ inr($totalBaseAmount ?? 0) }}</td>
                <td class="text-warning text-dark">+₹ {{ inr($totalGstAmount ?? 0) }}</td>
                <td class="text-danger fw-bold fs-6">₹ {{ inr($totalAmount ?? 0) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

@if ($debitNotes->hasPages())
    <div class="p-3 border-top d-flex justify-content-between align-items-center text-muted small">
        <span>Showing {{ $debitNotes->firstItem() ?? 0 }} to {{ $debitNotes->lastItem() ?? 0 }} of {{ $debitNotes->total() }} entries</span>
        {{ $debitNotes->links('pagination::bootstrap-5') }}
    </div>
@endif
