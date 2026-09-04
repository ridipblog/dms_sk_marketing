<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="editProductModalLabel">
                    <i class="fas fa-edit me-2"></i> Edit Product
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="editProductForm" action="" method="POST">
                @csrf
                <input type="hidden" id="edit_product_id" name="product_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_category_id" class="form-label fw-bold text-muted">Category <span
                                    class="text-danger">*</span></label>
                            <select class="form-select bg-light" id="edit_category_id" name="category_id" required>
                                <option value="" disabled>Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->category_name }} ({{ $category->category_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_product_name" class="form-label fw-bold text-muted">Product Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="edit_product_name" name="product_name"
                                placeholder="Enter product name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_sku_code" class="form-label fw-bold text-muted">SKU Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="edit_sku_code" name="sku_code"
                                placeholder="e.g. PRD-001" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_hsn_code" class="form-label fw-bold text-muted">HSN Code</label>
                            <input type="text" class="form-control bg-light" id="edit_hsn_code" name="hsn_code"
                                placeholder="Enter HSN code">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_size" class="form-label fw-bold text-muted">Size</label>
                            <input type="text" class="form-control bg-light" id="edit_size" name="size"
                                placeholder="e.g. 10mm">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_unit" class="form-label fw-bold text-muted">Unit</label>
                            <input type="text" class="form-control bg-light" id="edit_unit" name="unit"
                                placeholder="e.g. MT, PCS">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_base_price" class="form-label fw-bold text-muted">Base Price</label>
                            <input type="number" step="0.01" class="form-control bg-light" id="edit_base_price" name="base_price"
                                placeholder="0.00">
                        </div>
                        <div class="col-md-12">
                            <label for="edit_status" class="form-label fw-bold text-muted">Status <span
                                    class="text-danger">*</span></label>
                            <select class="form-select bg-light" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary fw-bold px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnUpdateProduct">
                        <i class="fas fa-save me-2"></i> Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
