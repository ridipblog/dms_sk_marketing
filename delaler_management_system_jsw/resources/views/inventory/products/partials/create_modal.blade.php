<div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createProductModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="createProductModalLabel">
                    <i class="fas fa-plus-circle me-2"></i> Create New Product
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="createProductForm" action="{{ route('products.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-bold text-muted">Category <span
                                    class="text-danger">*</span></label>
                            <select class="form-select bg-light" id="category_id" name="category_id" required>
                                <option value="" selected disabled>Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->category_name }} ({{ $category->category_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="product_name" class="form-label fw-bold text-muted">Product Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="product_name" name="product_name"
                                placeholder="Enter product name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="sku_code" class="form-label fw-bold text-muted">SKU Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="sku_code" name="sku_code"
                                placeholder="e.g. PRD-001" required>
                        </div>
                        <div class="col-md-6">
                            <label for="hsn_code" class="form-label fw-bold text-muted">HSN Code</label>
                            <input type="text" class="form-control bg-light" id="hsn_code" name="hsn_code"
                                placeholder="Enter HSN code">
                        </div>
                        <div class="col-md-4">
                            <label for="size" class="form-label fw-bold text-muted">Size</label>
                            <input type="text" class="form-control bg-light" id="size" name="size"
                                placeholder="e.g. 10mm">
                        </div>
                        <div class="col-md-4">
                            <label for="unit" class="form-label fw-bold text-muted">Unit</label>
                            <input type="text" class="form-control bg-light" id="unit" name="unit"
                                placeholder="e.g. MT, PCS">
                        </div>
                        <div class="col-md-4">
                            <label for="base_price" class="form-label fw-bold text-muted">Base Price</label>
                            <input type="number" step="0.01" class="form-control bg-light" id="base_price" name="base_price"
                                placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary fw-bold px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnSubmitProduct">
                        <i class="fas fa-save me-2"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
