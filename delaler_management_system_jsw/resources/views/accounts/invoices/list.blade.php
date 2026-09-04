<style>
    .table-invoice-list tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-invoice-list tbody tr:hover {
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

    .table-invoice-list tbody tr:hover .hover-actions-left {
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
    <table class="table table-sm table-hover align-middle mb-0 text-nowrap table-invoice-list"
        style="min-width: max-content; font-size: 0.75rem;">
        <thead class="table-light">
            <tr>
                <th>Invoice No</th>
                <th>User Invoice No</th>
                <th>Due Date</th>
                <th>Buyer Name</th>
                <th>Ship To Name</th>
                <th>Quantity (MT)</th>
                <th>Amt w/o GST (₹)</th>
                <th>GST (₹)</th>
                <th>Chargeable (₹)</th>
                <th>Total Products</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($invoices) && $invoices->count() > 0)
                @foreach ($invoices as $invoice)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-bold text-success">{{ $invoice->invoice_no }}</span><br>
                                    <small
                                        class="text-muted">{{ \Carbon\Carbon::parse($invoice->invoice_generate_date)->format('d M Y') }}</small>
                                </div>
                                <div class="hover-actions-left">
                                    @if ($invoice->invoice_status == 0 && ($invoice->manual_amount_update ?? 0) != 1)
                                        <button class="btn btn-action-icon btn-outline-success generate-invoice-btn"
                                            title="Generate Invoice" data-id="{{ Crypt::encryptString($invoice->id) }}">
                                            <i class="fas fa-file-invoice"></i>
                                        </button>
                                    @endif
                                    @if (($invoice->manual_amount_update ?? 0) != 1)
                                        <a href="{{ route('accounts.invoices.view', Crypt::encryptString($invoice->id)) }}"
                                            class="btn btn-action-icon btn-outline-info view-invoice-btn"
                                            title="View Invoice">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                    @if ($invoice->invoice_status == 0 && ($invoice->manual_amount_update ?? 0) != 1)
                                        <a href="{{ route('accounts.invoices.generate', Crypt::encryptString($invoice->id)) }}"
                                            class="btn btn-action-icon btn-outline-warning"
                                            title="Edit Invoice">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    <button class="btn btn-action-icon btn-outline-primary"
                                        title="Print Invoice">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button class="btn btn-action-icon btn-outline-danger delete-invoice-btn"
                                        title="Delete Invoice" data-id="{{ Crypt::encryptString($invoice->id) }}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">{{ $invoice->user_invoice_no ?? '-' }}</span>
                        </td>
                        <td>
                            @if($invoice->due_date)
                                @php
                                    $isOverdue = \Carbon\Carbon::parse($invoice->due_date)->startOfDay()->lt(now()->startOfDay());
                                @endphp
                                <span class="badge {{ $isOverdue ? 'bg-danger' : 'bg-info text-dark' }}">
                                    {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $invoice->buyer->dealer->dealer_name ?? 'N/A' }}</td>
                        <td>{{ $invoice->shipTo->dealer->dealer_name ?? 'N/A' }}</td>
                        <td>{{ number_format($invoice->total_quantity, 3) }}</td>
                        <td class="fw-bold text-primary">₹ {{ inr($invoice->total_amount) }}</td>
                        <td class="fw-bold text-danger">₹ {{ inr($invoice->total_gst_amount) }}</td>
                        <td class="fw-bold text-dark">₹ {{ inr($invoice->chargeable_amount) }}</td>
                        <td>{{ $invoice->no_of_goods ?? 0 }}</td>
                        <td>
                            @if (($invoice->manual_amount_update ?? 0) == 1)
                                <span class="badge bg-info text-dark">Manual Amount Update</span>
                            @elseif (($invoice->invoice_status ?? null) == 1)
                                <span class="badge bg-success">Finalized</span>
                            @else
                                <span class="badge bg-warning text-dark">Not Finalized</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                @if ($invoice->invoice_status == 0 && ($invoice->manual_amount_update ?? 0) != 1)
                                    <button
                                        class="btn btn-sm btn-outline-success generate-invoice-btn d-flex align-items-center fw-bold"
                                        title="Generate Invoice" data-id="{{ Crypt::encryptString($invoice->id) }}">
                                        <i class="fas fa-file-invoice me-1"></i> Generate
                                    </button>
                                @endif
                                @if (($invoice->manual_amount_update ?? 0) != 1)
                                    <a href="{{ route('accounts.invoices.view', Crypt::encryptString($invoice->id)) }}"
                                        class="btn btn-sm btn-outline-info view-invoice-btn d-flex align-items-center fw-bold"
                                        title="View Invoice">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                @endif
                                @if ($invoice->invoice_status == 0 && ($invoice->manual_amount_update ?? 0) != 1)
                                    <a href="{{ route('accounts.invoices.generate', Crypt::encryptString($invoice->id)) }}"
                                        class="btn btn-sm btn-outline-warning d-flex align-items-center fw-bold"
                                        title="Edit Invoice">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                @endif
                                <button
                                    class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center px-2"
                                    title="Print Invoice">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button
                                    class="btn btn-sm btn-outline-danger delete-invoice-btn d-flex align-items-center fw-bold"
                                    title="Delete Invoice" data-id="{{ Crypt::encryptString($invoice->id) }}">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">No invoices found.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
@if (isset($invoices) && $invoices->hasPages())
    <div class="p-3 border-top">
        {{ $invoices->links('pagination::bootstrap-5') }}
    </div>
@endif
