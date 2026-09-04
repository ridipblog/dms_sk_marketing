<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="createCategoryModalLabel">
                    <i class="fas fa-plus-circle me-2"></i> Create New Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="createCategoryForm" action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="category_name" class="form-label fw-bold text-muted">Category Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="category_name" name="category_name"
                                placeholder="Enter category name" required>
                        </div>
                        <div class="col-md-12">
                            <label for="category_code" class="form-label fw-bold text-muted">Category Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="category_code" name="category_code"
                                placeholder="e.g. CAT-001" required>
                        </div>
                        <div class="col-md-12">
                            <label for="description" class="form-label fw-bold text-muted">Description</label>
                            <textarea class="form-control bg-light" id="description" name="description" rows="3"
                                placeholder="Enter category description"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary fw-bold px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnSubmitCategory">
                        <i class="fas fa-save me-2"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
