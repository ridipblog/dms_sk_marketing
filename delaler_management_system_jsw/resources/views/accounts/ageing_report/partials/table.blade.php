<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-striped table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="bg-light text-muted">
            <tr>
                <th class="ps-4">Invoice No</th>
                <th>Dealer Code & Name</th>
                <th>Invoice Date</th>
                <th>Due Date</th>
                <th class="text-end">Original Amount</th>
                <th class="text-end">Outstanding Amount</th>
                <th class="text-center">Ageing Days</th>
                <th class="text-center">Overdue Days</th>
                <th class="text-center">Ageing Bucket</th>
                <th class="text-end pe-4">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices ?? [] as $invoice)
                @php
                    $days = (int)$invoice->outstanding_days;
                    
                    if ($days <= 30) {
                        $badgeClass = 'bg-success bg-opacity-10 text-success border border-success';
                        $bucketLabel = '0-30 Days';
                        $daysClass = 'text-success fw-semibold';
                    } elseif ($days <= 60) {
                        $badgeClass = 'bg-info bg-opacity-10 text-info border border-info';
                        $bucketLabel = '31-60 Days';
                        $daysClass = 'text-info fw-semibold';
                    } elseif ($days <= 90) {
                        $badgeClass = 'bg-warning bg-opacity-10 text-warning border border-warning';
                        $bucketLabel = '61-90 Days';
                        $daysClass = 'text-warning fw-semibold';
                    } else {
                        $badgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger';
                        $bucketLabel = '90+ Days';
                        $daysClass = 'text-danger fw-bold';
                    }
                @endphp
                <tr>
                    <td class="ps-4">
                        @if(($invoice->manual_amount_update ?? 0) == 1)
                            <span class="fw-bold text-secondary">{{ $invoice->invoice_no }}</span>
                        @else
                            <a href="{{ route('accounts.invoices.view', Crypt::encryptString($invoice->id)) }}" class="fw-bold text-primary text-decoration-none">
                                {{ $invoice->invoice_no }}
                            </a>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 border border-secondary text-secondary me-1" style="font-size: 0.75rem;">
                            {{ $invoice->dealer_code }}
                        </span>
                        <span class="fw-semibold text-dark">{{ $invoice->dealer_name }}</span>
                    </td>
                    <td>{{ $invoice->invoice_generate_date ? \Carbon\Carbon::parse($invoice->invoice_generate_date)->format('d M Y') : 'N/A' }}</td>
                    <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : 'N/A' }}</td>
                    <td class="text-end">₹ {{ inr($invoice->chargeable_amount ?? 0) }}</td>
                    <td class="text-end fw-bold text-dark">₹ {{ inr($invoice->outstanding_amount ?? 0) }}</td>
                    <td class="text-center {{ $daysClass }}">{{ $days }} Days</td>
                    <td class="text-center">
                        @php
                            $overdue = (int)($invoice->overdue_days ?? 0);
                        @endphp
                        @if($overdue > 0)
                            <span class="text-danger fw-bold">{{ $overdue }} Days</span>
                        @else
                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1" style="font-size: 0.75rem;">Not Due</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $badgeClass }} px-2 py-1" style="font-size: 0.8rem; font-weight: 600;">
                            {{ $bucketLabel }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-1">
                            @if(($invoice->manual_amount_update ?? 0) != 1)
                                <a href="{{ route('accounts.invoices.view', Crypt::encryptString($invoice->id)) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                            @else
                                <span class="badge bg-light text-muted border px-2 d-inline-flex align-items-center" style="font-size: 0.75rem;"><i class="fas fa-keyboard me-1"></i> Manual</span>
                            @endif

                            @if($invoice->phone)
                                @php
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $invoice->phone);
                                    if (strlen($cleanPhone) === 10) {
                                        $cleanPhone = '91' . $cleanPhone;
                                    }
                                    
                                    $msg = "Dear " . $invoice->dealer_name . " (" . $invoice->dealer_code . "),\n\n"
                                         . "This is a friendly reminder that Invoice " . $invoice->invoice_no . " for ₹ " . number_format($invoice->outstanding_amount ?? 0, 2) . " is now " . ($invoice->overdue_days ?? 0) . " days overdue (Due Date: " . ($invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : 'N/A') . ").\n\n"
                                         . "Kindly clear the balance at your earliest convenience.\n\n"
                                         . "Thank you,\nJSW DMS Team";
                                    
                                    $waUrl = "https://wa.me/" . $cleanPhone . "?text=" . urlencode($msg);
                                @endphp
                                <a href="{{ $waUrl }}" target="_blank" class="btn btn-sm btn-success text-white" title="Send WhatsApp Reminder">
                                    <i class="fab fa-whatsapp"></i> Reminder
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="fas fa-search-dollar fs-1 mb-3 text-black-50"></i>
                        <h5 class="fw-bold">No outstanding invoices found</h5>
                        <p class="mb-0">All invoices matching this criteria are fully settled.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex flex-wrap justify-content-center justify-content-md-end px-4 py-3 bg-white border-top gap-2 ageing-pagination">
    {{ $invoices->links() }}
</div>
