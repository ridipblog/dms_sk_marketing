<style>
    .table-dealers-list tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-dealers-list tbody tr:hover {
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

    .table-dealers-list tbody tr:hover .hover-actions-left {
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
    <table class="table table-striped table-hover align-middle mb-0 text-nowrap table-dealers-list" style="min-width: max-content;">
        <thead class="bg-light text-muted">
            <tr>
                <th class="ps-4">Code</th>
                <th>Dealer Name</th>
                <th>Assigned ASO</th>
                <th>Contact Info</th>
                <th>Total Debits</th>
                <th>Total Credits</th>
                <th>Outstanding Balance</th>
                <th>Days</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dealers ?? [] as $dealer)
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-secondary">{{ $dealer->dealer_code ?? 'N/A' }}</span>
                            <div class="hover-actions-left">
                                @if(!empty($dealer->dealer_company_id))
                                    <a href="{{ route('accounts.payment_tracks.index', ['dealer_company_id' => Crypt::encryptString($dealer->dealer_company_id)]) }}"
                                        class="btn btn-action-icon btn-outline-primary" title="View Payment Receipts">
                                        <i class="fas fa-receipt"></i>
                                    </a>
                                    <a href="{{ route('dealers.scheme_amounts.index', ['dealer_company_id' => Crypt::encryptString($dealer->dealer_company_id)]) }}"
                                        class="btn btn-action-icon btn-outline-success" title="Add Scheme Amount">
                                        <i class="fas fa-percentage"></i>
                                    </a>
                                @endif
                                <button class="btn btn-action-icon btn-outline-info btn-view-dealer"
                                    data-id="{{ Crypt::encryptString($dealer->id) }}" title="View Dealer Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-action-icon btn-outline-primary btn-edit-dealer"
                                    data-id="{{ Crypt::encryptString($dealer->id) }}" title="Edit Dealer">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td class="fw-semibold text-dark">
                        @if(!empty($dealer->dealer_company_id))
                            <a href="{{ route('accounts.payment_tracks.index', ['dealer_company_id' => Crypt::encryptString($dealer->dealer_company_id)]) }}"
                               class="text-primary text-decoration-none fw-bold"
                               title="View Payment Receipts for {{ $dealer->dealer_name ?? 'Dealer' }}">
                                <i class="fas fa-receipt me-1 text-primary opacity-75"></i>{{ $dealer->dealer_name ?? 'N/A' }}
                            </a>
                        @else
                            {{ $dealer->dealer_name ?? 'N/A' }}
                        @endif
                    </td>
                    <td>
                        @if(!empty($dealer->aso_name))
                            <span class="badge bg-info text-dark"><i class="fas fa-user-tie me-1"></i> {{ $dealer->aso_name }}</span>
                        @else
                            <span class="text-muted" style="font-size: 0.8rem;">Unassigned</span>
                        @endif
                    </td>
                    <td>
                        <div class="text-dark" style="font-size: 0.85rem;">{{ $dealer->email ?? 'N/A' }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $dealer->phone ?? 'N/A' }}</div>
                    </td>
                    <td class="text-danger">
                        ₹ {{ number_format($dealer->total_debit ?? 0, 2) }}
                    </td>
                    <td class="text-success">
                        ₹ {{ number_format($dealer->total_credit ?? 0, 2) }}
                    </td>
                    <td>
                        @php
                            $outstanding = (float)($dealer->outstanding_balance ?? 0);
                            $outstandingDisplay = number_format(abs($outstanding), 2);
                            $outstandingType = $outstanding > 0 ? 'Dr' : ($outstanding < 0 ? 'Cr' : '');
                            $outstandingClass = $outstanding > 0 ? 'text-danger fw-bold' : ($outstanding < 0 ? 'text-success fw-bold' : 'text-secondary');
                        @endphp
                        <span class="{{ $outstandingClass }}">
                            ₹ {{ $outstandingDisplay }}
                            @if($outstandingType)
                                <small class="text-muted">({{ $outstandingType }})</small>
                            @endif
                        </span>
                    </td>
                    <td>
                        @php
                            $daysCount = (int)($dealer->days ?? 0);
                        @endphp
                        @if($daysCount > 0)
                            <span class="badge bg-warning text-dark px-2 py-1" title="Days Outstanding / Overdue">
                                <i class="fas fa-clock me-1"></i> {{ $daysCount }} {{ \Illuminate\Support\Str::plural('Day', $daysCount) }}
                            </span>
                        @else
                            <span class="text-muted" style="font-size: 0.85rem;">0 Days</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $statusClass = 'bg-secondary';
                            if (($dealer->status ?? '') === 'active') {
                                $statusClass = 'bg-success';
                            } elseif (($dealer->status ?? '') === 'inactive') {
                                $statusClass = 'bg-warning text-dark';
                            } elseif (($dealer->status ?? '') === 'blocked') {
                                $statusClass = 'bg-danger';
                            }
                        @endphp
                        <span class="badge {{ $statusClass }}">
                            {{ ucfirst($dealer->status ?? 'N/A') }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        @if(!empty($dealer->dealer_company_id))
                            <a href="{{ route('accounts.payment_tracks.index', ['dealer_company_id' => Crypt::encryptString($dealer->dealer_company_id)]) }}"
                                class="btn btn-sm btn-outline-primary me-1" title="View Payment Receipts">
                                <i class="fas fa-receipt"></i>
                            </a>
                            <a href="{{ route('dealers.scheme_amounts.index', ['dealer_company_id' => Crypt::encryptString($dealer->dealer_company_id)]) }}"
                                class="btn btn-sm btn-outline-success me-1" title="Add Scheme Amount">
                                <i class="fas fa-percentage"></i>
                            </a>
                        @endif
                        <button class="btn btn-sm btn-outline-info btn-view-dealer me-1"
                            data-id="{{ Crypt::encryptString($dealer->id) }}" title="View Dealer Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary btn-edit-dealer"
                            data-id="{{ Crypt::encryptString($dealer->id) }}" title="Edit Dealer">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="fas fa-search fs-1 mb-3 text-black-50"></i>
                        <h5 class="fw-bold">No dealers found</h5>
                        <p class="mb-0">Try adjusting your search criteria.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex flex-wrap justify-content-center justify-content-md-end px-4 py-3 bg-white border-top gap-2">
    {{ $dealers->links() }}
</div>
