<div class="modal fade" id="uploadInventoryModal" tabindex="-1" aria-labelledby="uploadInventoryModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="uploadInventoryModalLabel">
                    <i class="fas fa-file-excel me-2"></i> Upload Inventory Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="uploadInventoryForm" action="{{ route('inventory.upload.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-cloud-upload-alt text-primary opacity-50 mb-3" style="font-size: 4rem;"></i>
                    <h5 class="fw-bold mb-3">Select Excel File</h5>
                    <p class="text-muted small mb-4">Please upload an `.xlsx` or `.csv` file formatted identically to the provided template.</p>
                    
                    <div class="mb-3">
                        <input class="form-control form-control-lg bg-light" type="file" id="excel_file" name="excel_file" accept=".xlsx, .xls, .csv" required>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary fw-bold px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnSubmitUpload">
                        <i class="fas fa-upload me-2"></i> Start Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
