<style>
    .table-voucher-list tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-voucher-list tbody tr:hover {
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

    .table-voucher-list tbody tr:hover .hover-actions-left {
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
    <table class="table table-sm table-hover align-middle mb-0 text-nowrap table-voucher-list"
        style="min-width: max-content; font-size: 0.85rem;">
        <thead class="table-light">
            <tr>
                <th>Transaction ID</th>
                <th>Transaction Date</th>
                <th>Voucher Type</th>
                <th>Payment Mode</th>
                <th class="text-end">Debit (₹)</th>
                <th class="text-end">Credit (₹)</th>
                <th class="text-end">Balance (₹)</th>
                <th>Remarks</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vouchers as $voucher)
                <tr>
                    <td>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-success">{{ $voucher->transaction_id }}</span>
                            @if (strtoupper($voucher->voucherTypeModel->name ?? '') !== 'SALES')
                                <div class="hover-actions-left">
                                    <button class="btn btn-action-icon btn-outline-warning edit-voucher-btn"
                                            data-id="{{ Crypt::encryptString($voucher->id) }}"
                                            title="Edit Voucher">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-action-icon btn-outline-danger delete-voucher-btn"
                                            data-id="{{ Crypt::encryptString($voucher->id) }}"
                                            title="Delete Voucher">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>{{ $voucher->transaction_date ? $voucher->transaction_date->format('d M Y') : '-' }}</td>
                    <td>
                        @php
                            $badgeClass = 'bg-secondary text-white';
                            $vTypeName = strtolower($voucher->voucherTypeModel->name ?? '');
                            if (str_contains($vTypeName, 'sale')) {
                                $badgeClass = 'bg-danger';
                            } elseif (str_contains($vTypeName, 'receipt')) {
                                $badgeClass = 'bg-success';
                            } elseif (str_contains($vTypeName, 'debit note')) {
                                $badgeClass = 'bg-warning text-dark';
                            } elseif (str_contains($vTypeName, 'credit note')) {
                                $badgeClass = 'bg-primary';
                            }

                            $isDebit = str_contains($vTypeName, 'sale') || str_contains($vTypeName, 'debit note');
                            $isCredit = str_contains($vTypeName, 'receipt') || str_contains($vTypeName, 'credit note');
                        @endphp
                        <span
                            class="badge {{ $badgeClass }}">{{ strtoupper($voucher->voucherTypeModel->name ?? 'UNKNOWN') }}</span>
                    </td>
                    <td>
                        <span
                            class="badge bg-info text-dark">{{ ucwords(str_replace('_', ' ', $voucher->payment_mode ?? '-')) }}</span>
                    </td>
                    <td class="text-end fw-bold text-danger">
                        {{ $isDebit ? inr($voucher->amount) : '-' }}
                    </td>
                    <td class="text-end fw-bold text-success">
                        {{ $isCredit ? inr($voucher->amount) : '-' }}
                    </td>
                    <td class="text-end fw-bold">
                        @if($voucher->balance_amount < 0)
                            <span class="text-success" title="Credit / Advance Balance">
                                {{ inr(abs($voucher->balance_amount)) }} <small class="text-muted">(Cr)</small>
                            </span>
                        @else
                            <span class="text-primary">
                                {{ inr($voucher->balance_amount) }} <small class="text-muted">(Dr)</small>
                            </span>
                        @endif
                    </td>
                    <td><small class="text-muted text-wrap"
                            style="max-width: 200px; display: inline-block;">{{ $voucher->remarks ?? '-' }}</small></td>
                    <td class="text-end">
                        @if (strtoupper($voucher->voucherTypeModel->name ?? '') !== 'SALES')
                            <button class="btn btn-sm btn-outline-warning edit-voucher-btn px-2 me-1" 
                                    data-id="{{ Crypt::encryptString($voucher->id) }}" 
                                    title="Edit Voucher">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-voucher-btn px-2" 
                                    data-id="{{ Crypt::encryptString($voucher->id) }}" 
                                    title="Delete Voucher">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        @else
                            <span class="text-muted" style="font-size: 0.75rem;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                        No vouchers found for this invoice.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
