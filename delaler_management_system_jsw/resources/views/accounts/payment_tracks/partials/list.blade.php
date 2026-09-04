<style>
    .table-payment-tracks tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-payment-tracks tbody tr:hover {
        background-color: #f8fafc !important;
    }

    .hover-actions-left {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out;
        margin-left: 8px;
    }

    .table-payment-tracks tbody tr:hover .hover-actions-left {
        opacity: 1;
        visibility: visible;
    }

    .btn-action-icon {
        padding: 3px 6px;
        font-size: 0.725rem;
        border-radius: 4px;
        line-height: 1;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
</style>

<div class="table-responsive w-100"
    style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-sm table-hover align-middle mb-0 text-nowrap table-payment-tracks"
        style="min-width: max-content; font-size: 0.75rem;">
        <thead class="table-light">
            <tr>
                <th>Invoice No</th>
                <th>Dealer Name</th>
                <th>Invoice Amount (₹)</th>
                <th>Total Vouchers</th>
                <th>Paid (₹)</th>
                <th>Debit Note (₹)</th>
                <th>Credit Note (₹)</th>
                <th>Balance (₹)</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $invoice)
                @php
                    $payment =
                        $invoice->invoicePayment ??
                        (object) [
                            'paid_amount' => 0,
                            'debit_note_amount' => 0,
                            'credit_note_amount' => 0,
                            'outstanding_amount' => 0,
                        ];
                    $paidAmount = $payment->paid_amount ?? 0;
                    $debitNoteAmount = $payment->debit_note_amount ?? 0;
                    $creditNoteAmount = $payment->credit_note_amount ?? 0;
                    $balance = $payment->outstanding_amount ?? 0;
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-bold text-success">{{ $invoice->invoice_no }}</span>
                                @if (($invoice->manual_amount_update ?? 0) == 1)
                                    <br><span class="badge bg-info text-dark" style="font-size: 0.65rem;">Manual Amount Update</span>
                                @endif
                            </div>
                            <div class="hover-actions-left">
                                @if(isset($invoice->buyer->dealer_id))
                                    <a href="{{ route('accounts.payment_tracks.ledger', Crypt::encryptString($invoice->buyer->dealer_id)) }}"
                                       class="btn btn-action-icon btn-outline-info" title="View Ledger" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endif
                                <a href="{{ route('accounts.payment_tracks.voucher', Crypt::encryptString($invoice->id)) }}"
                                   class="btn btn-action-icon btn-outline-success" title="Manage Vouchers">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </div>
                    </td>
                    <td>{{ $invoice->buyer->dealer->dealer_name ?? '-' }}</td>
                    <td class="fw-bold text-primary">₹ {{ inr($invoice->chargeable_amount) }}</td>
                    <td><span class="badge bg-secondary">{{ $invoice->payment_tracks_count }}</span></td>
                    <td class="fw-bold text-success">₹ {{ inr($paidAmount) }}</td>
                    <td class="text-warning text-dark">₹ {{ inr($debitNoteAmount) }}</td>
                    <td class="text-info text-dark">₹ {{ inr($creditNoteAmount) }}</td>
                    <td class="fw-bold {{ $balance <= 0 ? 'text-success' : 'text-danger' }}">₹
                        {{ inr($balance) }}</td>
                    <td class="text-end">
                        <a href="{{ route('accounts.payment_tracks.ledger', Crypt::encryptString($invoice->buyer->dealer_id)) }}" class="btn btn-sm btn-outline-info" title="View Ledger" target="_blank">
                            <i class="fas fa-eye"></i> View Ledger
                        </a>
                        <a href="{{ route('accounts.payment_tracks.voucher', Crypt::encryptString($invoice->id)) }}" class="btn btn-sm btn-outline-success" title="Manage Vouchers">
                            <i class="fas fa-list"></i> Manage Vouchers
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                        No invoices found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($invoices->hasPages())
    <div class="p-3 border-top">
        {{ $invoices->links('pagination::bootstrap-5') }}
    </div>
@endif
