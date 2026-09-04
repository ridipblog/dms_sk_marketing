<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            @if($orderId)
                Order-Wise Transaction Flow (Dummy Data)
            @else
                Dealer-Wise Transaction Flow (Dummy Data)
            @endif
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        @if(!$orderId)
                        <th>Order No</th>
                        @endif
                        <th>Transaction Date</th>
                        <th>Transaction ID</th>
                        <th>Voucher Type</th>
                        <th>Debit (₹)</th>
                        <th>Credit (₹)</th>
                        <th>Balance (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$orderId)
                    <tr class="table-info fw-bold">
                        <td colspan="6" class="text-end">Opening Balance:</td>
                        <td class="text-primary">₹ 0.00</td>
                    </tr>
                    @endif
                    @forelse($dummyPaymentTracks as $track)
                    <tr>
                        @if(!$orderId)
                        <td><span class="fw-bold">{{ $track['order_no'] ?? '-' }}</span></td>
                        @endif
                        <td>{{ date('d M Y h:i A', strtotime($track['transaction_date'])) }}</td>
                        <td>{{ $track['transaction_id'] }}</td>
                        <td>
                            @if($track['voucher_type'] == 'SALE' || $track['voucher_type'] == 'DEBIT_NOTE / INTEREST')
                                <span class="badge bg-danger">{{ $track['voucher_type'] }}</span>
                            @else
                                <span class="badge bg-success">{{ $track['voucher_type'] }}</span>
                            @endif
                        </td>
                        <td class="text-danger fw-bold">
                            {{ $track['debit'] ? number_format($track['debit'], 2) : '-' }}
                        </td>
                        <td class="text-success fw-bold">
                            {{ $track['credit_amount'] ? number_format($track['credit_amount'], 2) : '-' }}
                        </td>
                        <td class="fw-bold">
                            ₹ {{ number_format($track['balance'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $orderId ? 6 : 7 }}" class="text-center py-4 text-muted">No transactions found</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($dummyPaymentTracks) > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        @if(!$orderId)
                        <td colspan="6" class="text-end">Closing Balance:</td>
                        <td class="text-primary">₹ 0.00</td>
                        @else
                        <td colspan="5" class="text-end">Outstanding Amount:</td>
                        <td class="text-primary">₹ 0.00</td>
                        @endif
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
