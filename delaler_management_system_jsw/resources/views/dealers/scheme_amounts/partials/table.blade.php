<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" id="schemeAmountsTable" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th class="ps-4">#</th>
                <th>Dealer Code</th>
                <th>Dealer Name</th>
                <th>Month / Year</th>
                <th class="text-end">Qty (MT)</th>
                <th class="text-end">Rate / MT (₹)</th>
                <th class="text-end">Scheme Amount (₹)</th>
                <th>Remarks</th>
                <th>Date</th>
                <th class="text-center pe-4">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($schemeAmounts as $index => $record)
                @php
                    $monthName = $record->month ? date('M', mktime(0, 0, 0, $record->month, 10)) : '';
                    $period = trim($monthName . ' ' . ($record->year ?? ''));
                @endphp
                <tr>
                    <td class="ps-4">{{ $index + 1 }}</td>
                    <td><span class="badge bg-secondary">{{ $record->dealerCompany->dealer->dealer_code ?? 'N/A' }}</span></td>
                    <td class="fw-bold">{{ $record->dealerCompany->dealer->dealer_name ?? 'N/A' }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $period ?: 'N/A' }}</span></td>
                    <td class="text-end">{{ $record->quantity !== null ? number_format($record->quantity, 3) : '-' }}</td>
                    <td class="text-end">{{ $record->rate_per_mt !== null ? '₹' . inr($record->rate_per_mt) : '-' }}</td>
                    <td class="text-end text-success fw-bold">₹{{ inr($record->amount) }}</td>
                    <td>{{ $record->remarks ?? '-' }}</td>
                    <td>{{ $record->created_at ? $record->created_at->format('d-M-Y h:i A') : 'N/A' }}</td>
                    <td class="text-center pe-4">
                        <button type="button" class="btn btn-sm btn-outline-danger delete-scheme-btn" data-id="{{ $record->id }}" title="Delete Record">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i> No scheme amount records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($schemeAmounts->hasPages())
    <div class="px-3 py-3 border-top d-flex justify-content-between align-items-center flex-wrap">
        <div class="small text-muted mb-2 mb-md-0">
            Showing {{ $schemeAmounts->firstItem() }} to {{ $schemeAmounts->lastItem() }} of {{ $schemeAmounts->total() }} entries
        </div>
        <div>
            {{ $schemeAmounts->links() }}
        </div>
    </div>
@endif
