@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-invoice-dollar me-2"></i> Purchase Invoice Details
            </h4>
            <div>
                <button type="button" class="btn btn-primary fw-bold shadow-sm me-2" onclick="window.print();">
                    <i class="fas fa-print me-1"></i> Print Bill
                </button>
                <a href="{{ route('purchase.invoices.index') }}" class="btn btn-outline-secondary shadow-sm fw-bold">
                    <i class="fas fa-arrow-left me-1"></i> Back to Ledger
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Left Side: Invoice Summary & Products List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <!-- Invoice Header details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted fw-bold">SUPPLIER (BILL FROM)</h6>
                                <h5 class="fw-bold text-dark mb-1">{{ $purchase->supplier->name }}</h5>
                                <p class="text-muted mb-0 small">
                                    <strong>GSTIN:</strong> {{ $purchase->supplier->gstin ?? 'N/A' }}<br>
                                    <strong>Phone:</strong> {{ $purchase->supplier->phone ?? 'N/A' }}<br>
                                    <strong>Address:</strong> {{ $purchase->supplier->address ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <h4 class="fw-bold text-success mb-1">{{ $purchase->invoice_no }}</h4>
                                <p class="text-muted mb-2 small">
                                    <strong>Purchase Date:</strong> {{ $purchase->purchase_date->format('d M Y') }}<br>
                                    <strong>Due Date:</strong> {{ $purchase->due_date ? $purchase->due_date->format('d M Y') : 'N/A' }}
                                </p>
                                <span class="badge bg-success fs-7">Finalized Bill</span>
                            </div>
                        </div>

                        <!-- Products Details table -->
                        <h6 class="text-muted fw-bold mb-3">BILL LINE ITEMS</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover align-middle table-sm" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th>SKU Code</th>
                                        <th class="text-center">Quantity (MT)</th>
                                        <th class="text-end">Rate / MT (₹)</th>
                                        <th class="text-end">Taxable Amt (₹)</th>
                                        <th class="text-end">GST Amt (18%) (₹)</th>
                                        <th class="text-end">Chargeable (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i = 1; @endphp
                                    @foreach ($purchase->purchaseInvoiceDetails as $detail)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td class="fw-bold text-dark">{{ $detail->product->product_name ?? 'Unknown Product' }}</td>
                                            <td>{{ $detail->product->sku_code ?? '-' }}</td>
                                            <td class="text-center">{{ number_format($detail->quantity, 3) }}</td>
                                            <td class="text-end">₹{{ number_format($detail->rate, 2) }}</td>
                                            <td class="text-end">₹{{ number_format($detail->total_amount, 2) }}</td>
                                            <td class="text-end text-danger">₹{{ number_format($detail->gst_amount, 2) }}</td>
                                            <td class="text-end fw-bold text-dark">₹{{ number_format($detail->chargeable_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals Section -->
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <table class="table table-sm table-borderless text-end mb-0" style="font-size: 0.8rem;">
                                    <tr>
                                        <td class="text-muted">Total Quantity:</td>
                                        <td class="fw-bold">{{ number_format($purchase->total_quantity, 3) }} MT</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Sub Total (excl. tax):</td>
                                        <td class="fw-bold">₹{{ number_format($purchase->total_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">CGST Amount:</td>
                                        <td class="fw-bold text-danger">₹{{ number_format($purchase->total_cgst_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">SGST Amount:</td>
                                        <td class="fw-bold text-danger">₹{{ number_format($purchase->total_sgst_amount, 2) }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold text-dark fs-6">Grand Total (incl. GST):</td>
                                        <td class="fw-bold text-success fs-6">₹{{ number_format($purchase->chargeable_amount, 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Outstanding Balances & Payment Tracks Log -->
            <div class="col-lg-4">
                <!-- Outstanding Ledger status card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-wallet me-2"></i> Accounts Payable Balance</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $pm = $purchase->purchaseInvoicePayment;
                            $outstanding = $pm->outstanding_amount ?? $purchase->chargeable_amount;
                            $paid = $pm->paid_amount ?? 0.00;
                        @endphp
                        <div class="row mb-3 text-center">
                            <div class="col-6 border-end">
                                <small class="text-muted d-block">TOTAL PAID</small>
                                <span class="fw-bold text-success fs-5">₹{{ number_format($paid, 2) }}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">OUTSTANDING</small>
                                <span class="fw-bold text-danger fs-5">₹{{ number_format($outstanding, 2) }}</span>
                            </div>
                        </div>

                        @if ($outstanding > 0.01)
                            <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" id="btnOpenPaymentModal">
                                <i class="fas fa-hand-holding-usd me-1"></i> Record Repayment
                            </button>
                        @else
                            <div class="alert alert-success text-center mb-0 fw-bold p-2">
                                <i class="fas fa-check-circle me-1"></i> Fully Paid Bill
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Repayment transaction tracks list -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-2"></i> Voucher Transaction History</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" style="font-size: 0.75rem;">
                            @if (isset($purchase->purchasePaymentTracks) && $purchase->purchasePaymentTracks->count() > 0)
                                @foreach ($purchase->purchasePaymentTracks as $track)
                                    <div class="list-group-item p-3 border-0 border-bottom">
                                        <div class="d-flex justify-content-between mb-1">
                                            @php
                                                $vName = $track->voucherType->name ?? 'N/A';
                                            @endphp
                                            <span class="fw-bold {{ $vName === 'PURCHASE' ? 'text-danger' : 'text-success' }}">
                                                {{ $vName }} VOUCHER
                                            </span>
                                            <span class="text-muted">
                                                {{ $track->transaction_date->format('d M Y H:i') }}
                                            </span>
                                        </div>
                                        <p class="mb-1 text-dark small">
                                            <strong>Amount:</strong> ₹{{ number_format($track->amount, 2) }}<br>
                                            <strong>Running Bal:</strong> ₹{{ number_format($track->balance_amount, 2) }}<br>
                                            <strong>Ref ID:</strong> {{ $track->transaction_id ?? '-' }} (Mode: {{ strtoupper($track->payment_mode) }})
                                        </p>
                                        @if ($track->remarks)
                                            <p class="mb-0 text-muted small italic">"{{ $track->remarks }}"</p>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4 text-muted">No vouchers logged.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Record Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="recordRepaymentForm" action="{{ route('purchase.invoices.payment') }}" method="POST">
                    @csrf
                    <input type="hidden" name="purchase_id" value="{{ Crypt::encryptString($purchase->id) }}">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="paymentModalLabel">Record Payment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold d-block">Outstanding Balance: <span class="text-danger fw-bold fs-5">₹{{ number_format($outstanding, 2) }}</span></label>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label fw-bold">Payment Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="{{ $outstanding }}" class="form-control" id="amount" name="amount" value="{{ $outstanding }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="payment_mode" class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_mode" name="payment_mode" required>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="upi">UPI</option>
                                    <option value="cash">Cash</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="transaction_date" class="form-label fw-bold">Transaction Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="transaction_id" class="form-label fw-bold">Reference / Transaction ID</label>
                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" placeholder="UTR, TXN No.">
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label fw-bold">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success fw-bold">Record Repayment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Open Payment Modal
            $('#btnOpenPaymentModal').click(function () {
                $('#paymentModal').modal('show');
            });

            // Submit Payment Form
            $('#recordRepaymentForm').submit(function (e) {
                e.preventDefault();
                let form = $(this);
                let btn = form.find('button[type="submit"]');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: form.attr('action'),
                    type: "POST",
                    data: form.serialize(),
                    success: function (response) {
                        btn.prop('disabled', false).text('Record Repayment');
                        if (response.success) {
                            $('#paymentModal').modal('hide');
                            alert(response.message);
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function () {
                        btn.prop('disabled', false).text('Record Repayment');
                        alert('Something went wrong. Please try again.');
                    }
                });
            });
        });
    </script>
@endpush
