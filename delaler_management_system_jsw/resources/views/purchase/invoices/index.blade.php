@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-invoice-dollar me-2"></i> Purchase Invoices
            </h4>
            <div class="d-flex gap-2">
                <button id="btnExportPurchaseExcel" class="btn btn-success shadow-sm fw-bold">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
                <a href="{{ route('purchase.invoices.generate') }}" class="btn btn-primary shadow-sm fw-bold">
                    <i class="fas fa-plus me-1"></i> Add Purchase Bill
                </a>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="row mb-4">
            <div class="col-md-5">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary me-2 shadow-sm" id="btnRefreshPurchases" title="Refresh List">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchPurchase" class="form-control border-start-0"
                            placeholder="Search by Invoice No or Supplier...">
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="d-flex align-items-center justify-content-end">
                    <select class="form-select shadow-sm me-2" id="supplierFilterSelect" style="width: 250px;">
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers ?? [] as $supplier)
                            <option value="{{ Crypt::encryptString($supplier->id) }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>

                    <select class="form-select shadow-sm" id="statusFilterSelect" style="width: 180px;">
                        <option value="">All Statuses</option>
                        <option value="1">Finalized</option>
                        <option value="0">Draft</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0" id="purchaseTableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="recordPaymentForm" action="{{ route('purchase.invoices.payment') }}" method="POST">
                    @csrf
                    <input type="hidden" name="purchase_id" id="payment_purchase_id">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="recordPaymentModalLabel">Record Payment Outlay</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold d-block">Outstanding Balance: <span class="text-danger fw-bold fs-5" id="outstandingBalanceDisplay">₹0.00</span></label>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label fw-bold">Payment Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" required>
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
                        <button type="submit" class="btn btn-success fw-bold">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/purchase/invoices/purchase_invoice.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
