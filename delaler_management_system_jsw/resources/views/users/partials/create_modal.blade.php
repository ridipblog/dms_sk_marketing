<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="createUserForm" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control shadow-sm" required
                            placeholder="Enter full name" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control shadow-sm" required
                            placeholder="Enter email address" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="create_phone" class="form-control shadow-sm" required
                            placeholder="Enter phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Designation</label>
                        <input type="text" name="designation" class="form-control shadow-sm"
                            placeholder="Enter designation (optional)" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select shadow-sm" required disabled>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm fw-bold" id="btnSubmitUser">
                        <i class="fas fa-save me-1"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
