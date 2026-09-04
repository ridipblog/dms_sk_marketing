<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-striped table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th class="ps-4">S.No</th>
                <th>Invoice No</th>
                <th>Supplier Name</th>
                <th>Quantity (MT)</th>
                <th>Amt w/o GST (₹)</th>
                <th>GST Amount (₹)</th>
                <th>Chargeable (₹)</th>
                <th>Paid Amount (₹)</th>
                <th>Outstanding (₹)</th>
                <th>Status</th>
                <th class="text-center pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices ?? [] as $index => $invoice)
                @php
                    $outstanding = $invoice->purchaseInvoicePayment->outstanding_amount ?? $invoice->chargeable_amount;
                    $paid = $invoice->purchaseInvoicePayment->paid_amount ?? 0.00;
                @endphp
                <tr>
                    <td class="ps-4 text-muted fw-bold">{{ $invoices->firstItem() + $index }}</td>
                    <td>
                        <span class="fw-bold text-success">{{ $invoice->invoice_no }}</span><br>
                        <small class="text-muted fw-semibold"><i class="far fa-calendar-alt me-1"></i>{{ \Carbon\Carbon::parse($invoice->purchase_date)->format('d M Y') }}</small>
                    </td>
                    <td>
                        <div class="fw-bold text-dark">{{ $invoice->supplier->name ?? 'N/A' }}</div>
                    </td>
                    <td><span class="fw-semibold text-secondary">{{ number_format($invoice->total_quantity, 3) }} MT</span></td>
                    <td class="fw-bold text-primary">₹{{ number_format($invoice->total_amount, 2) }}</td>
                    <td class="fw-bold text-danger">₹{{ number_format($invoice->total_gst_amount, 2) }}</td>
                    <td class="fw-bold text-dark">₹{{ number_format($invoice->chargeable_amount, 2) }}</td>
                    <td class="fw-bold text-success">₹{{ number_format($paid, 2) }}</td>
                    <td class="fw-bold text-warning">₹{{ number_format($outstanding, 2) }}</td>
                    <td>
                        @if ($invoice->status == 1)
                            @if (isset($invoice->purchaseInvoicePayment) && $invoice->purchaseInvoicePayment->clear_status == 1)
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Fully Paid</span>
                            @elseif ($paid > 0)
                                <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill">Partially Paid</span>
                            @else
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">Finalized</span>
                            @endif
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">Draft</span>
                        @endif
                    </td>
                    <td class="text-center pe-4">
                        <div class="d-flex justify-content-center gap-2">
                            @if ($invoice->status == 0)
                                <button class="btn btn-sm btn-light text-success finalize-purchase-btn shadow-sm border"
                                    title="Finalize Purchase Invoice" data-id="{{ Crypt::encryptString($invoice->id) }}">
                                    <i class="fas fa-check-circle"></i> Finalize
                                </button>
                                <a href="{{ route('purchase.invoices.generate', Crypt::encryptString($invoice->id)) }}"
                                    class="btn btn-sm btn-light text-warning shadow-sm border" title="Edit Draft">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            @else
                                <a href="{{ route('purchase.invoices.view', Crypt::encryptString($invoice->id)) }}"
                                    class="btn btn-sm btn-light text-info shadow-sm border" title="View Ledger">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @if ($outstanding > 0.01)
                                    <button class="btn btn-sm btn-light text-success record-payment-btn shadow-sm border"
                                        title="Record Payment" data-id="{{ Crypt::encryptString($invoice->id) }}"
                                        data-outstanding="{{ $outstanding }}">
                                        <i class="fas fa-hand-holding-usd"></i> Pay
                                    </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-file-invoice-dollar fs-1 mb-3 opacity-50"></i>
                            <h5 class="fw-bold">No Purchase Invoices Found</h5>
                            <p class="mb-0">There are no purchase invoices matching your search criteria.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (isset($invoices) && $invoices->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-4 bg-light border-top gap-3">
        <div class="text-muted small">
            Showing <span class="fw-bold">{{ $invoices->firstItem() }}</span> to <span
                class="fw-bold">{{ $invoices->lastItem() }}</span> of <span
                class="fw-bold">{{ $invoices->total() }}</span> entries
        </div>
        <div class="pagination-wrapper">
            {{ $invoices->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
