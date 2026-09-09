@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-success">
                <i class="fas fa-money-bill-wave me-2"></i> Upload Purchase Payments
            </h4>
            <p class="text-muted small mb-0">Upload payment entries against supplier purchase invoices via Excel / CSV.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchase.invoices.payment_upload.template') }}" class="btn btn-outline-success shadow-sm">
                <i class="fas fa-download me-1"></i> Download Excel Template
            </a>
            <button class="btn btn-success shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#uploadPurchasePaymentModal">
                <i class="fas fa-upload me-1"></i> Upload Purchase Payments
            </button>
        </div>
    </div>

    <!-- Instructions Card -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body p-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-info-circle text-success me-2"></i>Instructions & Format</h6>
            <ul class="mb-0 text-muted small ps-3">
                <li>Excel / CSV file must contain columns: <strong>User Invoice No, Payment Date, Amount, Payment Mode, Transaction Ref No, Remarks</strong>.</li>
                <li><strong>User Invoice No</strong> is used to locate the matching finalized purchase invoice record.</li>
                <li><strong>Payment Date</strong> format can be <code>DD-MM-YYYY</code> or <code>YYYY-MM-DD</code>.</li>
                <li><strong>Amount</strong> must be a positive number and cannot exceed the invoice's outstanding balance.</li>
                <li><strong>Payment Mode</strong> options: <code>bank_transfer</code>, <code>cash</code>, <code>cheque</code>, <code>upi</code>, <code>neft</code>.</li>
                <li>Payment entries automatically update paid amount, outstanding balance, invoice clearance status, and create purchase payment tracks.</li>
            </ul>
        </div>
    </div>

    <!-- Upload History Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex align-items-center gap-3 mb-2 mb-md-0">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-history text-muted me-2"></i>Purchase Payment Upload History</h6>
                    <button type="button" id="btnRefreshHistory" class="btn btn-sm btn-outline-success shadow-sm" title="Refresh Table">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control bg-light border-start-0" placeholder="Search by file name...">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0" id="tableContainer">
            <div class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Loading upload history...</p>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadPurchasePaymentModal" tabindex="-1" aria-labelledby="uploadPurchasePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="uploadPurchasePaymentModalLabel">
                    <i class="fas fa-file-upload me-2"></i> Upload Purchase Payments
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="purchasePaymentUploadForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label fw-semibold">Select Excel / CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".csv, .xlsx, .xls" required>
                        <div class="form-text">Supported formats: CSV, XLSX, XLS (Max: 10MB). First column must be <strong>User Invoice No</strong>.</div>
                    </div>
                    <div id="uploadAlert" class="d-none alert mb-0"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold" id="btnSubmit">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Start Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.PurchasePaymentUploadConfig = {
        listUrl: "{{ route('purchase.invoices.payment_upload.list') }}",
        importUrl: "{{ route('purchase.invoices.payment_upload.import') }}",
        csrfToken: "{{ csrf_token() }}"
    };
</script>
<script src="{{ asset('js/purchase/invoices/payment_upload.js') }}?v={{ config('app.asset_version', '1.0') }}"></script>
@endpush
