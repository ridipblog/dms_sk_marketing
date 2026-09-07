@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-primary">
                <i class="fas fa-building me-2"></i> Company-Wise Invoice Upload
            </h4>
            <p class="text-muted small mb-0">Upload invoices company-wise using Company GST Number as unique identifier.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounts.invoices.company_wise_upload.template') }}" class="btn btn-outline-primary shadow-sm">
                <i class="fas fa-download me-1"></i> Download Excel Template
            </a>
            <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#uploadCompanyWiseModal">
                <i class="fas fa-upload me-1"></i> Upload Company Invoices
            </button>
        </div>
    </div>

    <!-- Instructions Card -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body p-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-info-circle text-primary me-2"></i>Instructions & Format</h6>
            <ul class="mb-0 text-muted small ps-3">
                <li>Excel / CSV file must contain columns: <strong>Dealer GST No, User Invoice No, Invoice Date, Product Name, Quantity, Rate (Without GST), GST Percentage, GST Type (intra/inter)</strong>.</li>
                <li><strong>Dealer GST No</strong> resolves the dealer record for the active company.</li>
                <li><strong>User Invoice No</strong> is the user-provided invoice number stored in <code>user_invoice_no</code>. System automatically generates system invoice numbers in <code>invoice_no</code>.</li>
                <li>Multiple items in Excel can share the same <strong>User Invoice No</strong> for one dealer, but the same User Invoice No cannot be assigned to multiple different dealers.</li>
                <li><strong>Product Name</strong> matches the item in the products table.</li>
                <li><strong>Rate (Without GST)</strong>, <strong>GST Percentage</strong> (e.g. 18.00), and <strong>GST Type</strong> (intra/inter) are used to calculate CGST, SGST, IGST, and total chargeable amounts.</li>
            </ul>
        </div>
    </div>

    <!-- Upload Tracks Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex align-items-center gap-3 mb-2 mb-md-0">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-history text-muted me-2"></i>Upload Tracking History</h6>
                    <button type="button" id="btnRefreshHistory" class="btn btn-sm btn-outline-primary shadow-sm" title="Refresh Table">
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
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Loading upload history...</p>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadCompanyWiseModal" tabindex="-1" aria-labelledby="uploadCompanyWiseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="uploadCompanyWiseModalLabel">
                    <i class="fas fa-file-upload me-2"></i> Upload Company-Wise Invoices
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="companyWiseUploadForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label fw-semibold">Select Excel / CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".csv, .xlsx, .xls" required>
                        <div class="form-text">Supported formats: CSV, XLSX, XLS (Max: 10MB). Ensure <strong>Dealer GST No</strong> is in the first column.</div>
                    </div>
                    <div id="uploadAlert" class="d-none alert mb-0"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnSubmit">
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
    window.CompanyWiseUploadConfig = {
        listUrl: "{{ route('accounts.invoices.company_wise_upload.list') }}",
        importUrl: "{{ route('accounts.invoices.company_wise_upload.import') }}",
        csrfToken: "{{ csrf_token() }}"
    };
</script>
<script src="{{ asset('js/accounts/invoices/company_wise_upload.js') }}?v={{ config('app.asset_version', '1.0') }}"></script>
@endpush
