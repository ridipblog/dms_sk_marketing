<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="editCategoryModalLabel">
                    <i class="fas fa-edit me-2"></i> Edit Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="editCategoryForm" action="" method="POST">
                @csrf
                <input type="hidden" id="edit_category_id" name="category_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="edit_category_name" class="form-label fw-bold text-muted">Category Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="edit_category_name"
                                name="category_name" placeholder="Enter category name" required>
                        </div>
                        <div class="col-md-12">
                            <label for="edit_category_code" class="form-label fw-bold text-muted">Category Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="edit_category_code"
                                name="category_code" placeholder="e.g. CAT-001" required>
                        </div>
                        <div class="col-md-12">
                            <label for="edit_status" class="form-label fw-bold text-muted">Status <span
                                    class="text-danger">*</span></label>
                            <select class="form-select bg-light" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="edit_description" class="form-label fw-bold text-muted">Description</label>
                            <textarea class="form-control bg-light" id="edit_description" name="description" rows="3"
                                placeholder="Enter category description"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary fw-bold px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnUpdateCategory">
                        <i class="fas fa-save me-2"></i> Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
