<!-- VOUCHERS TAB CONTENT -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-secondary">Upload Vouchers Data</h6>
        <a href="{{ route('accounts.upload.vouchers.template') }}" class="btn btn-sm btn-outline-primary shadow-sm fw-bold">
            <i class="fas fa-download me-1"></i> Download Template
        </a>
    </div>
    <div class="card-body">
        <form id="uploadVoucherForm" enctype="multipart/form-data">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="voucherExcelFile" class="form-label text-muted small fw-bold">Select Excel File (.xlsx, .csv)</label>
                        <input class="form-control form-control-lg bg-light" type="file" id="voucherExcelFile" name="excel_file" accept=".xlsx, .xls, .csv" required>
                    </div>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm" id="btnUploadVoucher">
                        <i class="fas fa-cloud-upload-alt me-2"></i> Upload & Process
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Vouchers Upload List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom pb-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-secondary">Recent Voucher Uploads</h6>
        <div class="d-flex">
            <button class="btn btn-sm btn-outline-secondary me-2 shadow-sm" id="btnRefreshVoucherList" title="Refresh List">
                <i class="fas fa-sync-alt"></i>
            </button>
            <div class="input-group shadow-sm" style="max-width: 250px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" id="searchVoucherFiles" class="form-control border-start-0 form-control-sm" placeholder="Search files...">
            </div>
        </div>
    </div>
    <div class="card-body p-0" id="voucherUploadsTableContainer">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>
