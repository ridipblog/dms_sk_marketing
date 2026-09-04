<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-edit me-2"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_user_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control shadow-sm" required
                            placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-control shadow-sm" required
                            placeholder="Enter email address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="edit_phone" class="form-control shadow-sm" required
                            placeholder="Enter phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Designation</label>
                        <input type="text" name="designation" id="edit_designation" class="form-control shadow-sm"
                            placeholder="Enter designation (optional)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Status <span class="text-danger">*</span></label>
                        <select name="status" id="edit_status" class="form-select shadow-sm" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning shadow-sm fw-bold text-dark" id="btnUpdateUser">
                        <i class="fas fa-save me-1"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
