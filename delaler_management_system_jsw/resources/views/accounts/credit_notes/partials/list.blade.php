<!-- Summary Header Banner -->
<div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="small text-muted fw-semibold">
        <i class="fas fa-file-invoice text-success me-1"></i> Total Credit Notes: <strong class="text-dark">{{ $totalCount ?? $creditNotes->total() }}</strong>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="small bg-white px-3 py-1 rounded border shadow-sm">
            <span class="text-muted fw-bold">Sum of Total Amount:</span> <strong class="text-success fs-6 ms-1">₹ {{ inr($totalAmount ?? 0) }}</strong>
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
                    <td>
                        @if (!empty($creditNote->paymentTrack->invoice->user_invoice_no))
                            <span class="fw-bold text-success">{{ $creditNote->paymentTrack->invoice->user_invoice_no }}</span>
                            <br><small class="text-secondary fw-semibold">Ref: {{ $creditNote->paymentTrack->invoice->invoice_no }}</small>
                        @else
                            <span class="fw-bold text-success">{{ $creditNote->paymentTrack->invoice->invoice_no ?? 'N/A' }}</span>
                        @endif
                    </td>
                    <td>{{ $dealerName }}</td>
                    <td>{{ $creditNote->paymentTrack->transaction_date ? $creditNote->paymentTrack->transaction_date->format('d M Y') : 'N/A' }}
                    </td>
                    <td class="fw-bold text-dark">₹ {{ inr($creditNote->amount) }}</td>
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
        @if(count($creditNotes) > 0)
        <tfoot class="table-light fw-bold border-top">
            <tr>
                <td colspan="3" class="text-end text-dark">Total (All Filtered Records):</td>
                <td class="text-success fw-bold fs-6">₹ {{ inr($totalAmount ?? 0) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

@if ($creditNotes->hasPages())
    <div class="p-3 border-top d-flex justify-content-between align-items-center text-muted small">
        <span>Showing {{ $creditNotes->firstItem() ?? 0 }} to {{ $creditNotes->lastItem() ?? 0 }} of {{ $creditNotes->total() }} entries</span>
        {{ $creditNotes->links('pagination::bootstrap-5') }}
    </div>
@endif
