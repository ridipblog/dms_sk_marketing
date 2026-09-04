<!-- Edit Slab Modal -->
<div class="modal fade" id="editSlabModal" tabindex="-1" aria-labelledby="editSlabModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editSlabModalLabel"><i class="fas fa-edit me-2"></i>Edit Cash Discount Slab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSlabForm">
                <input type="hidden" name="slab_id" id="edit_slab_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Slab Name</label>
                            <input type="text" class="form-control" name="slab_name" id="edit_slab_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Minimum Days</label>
                            <input type="number" class="form-control" name="minimum_days" id="edit_minimum_days" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maximum Days</label>
                            <input type="number" class="form-control" name="maximum_days" id="edit_maximum_days" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Discount Percent (%)</label>
                            <input type="number" step="0.01" class="form-control" name="discount_percent" id="edit_discount_percent" min="0" max="100" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="status" value="1" id="editSlabStatus">
                                <label class="form-check-label fw-bold text-success" for="editSlabStatus">Enable Slab</label>
                            </div>
                            <small class="text-muted d-block mt-1">When enabled, this discount slab can be applied to credit notes.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update Slab</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editStatusCheckbox = document.getElementById('editSlabStatus');
        const editStatusLabel = editStatusCheckbox.nextElementSibling;

        function updateEditStatusLabel() {
            if (editStatusCheckbox.checked) {
                editStatusLabel.textContent = 'Enable Slab';
                editStatusLabel.classList.remove('text-secondary');
                editStatusLabel.classList.add('text-success');
            } else {
                editStatusLabel.textContent = 'Disable Slab';
                editStatusLabel.classList.remove('text-success');
                editStatusLabel.classList.add('text-secondary');
            }
        }

        editStatusCheckbox.addEventListener('change', updateEditStatusLabel);
        
        // Also call on modal open if needed when backend populates it
        // $('#editSlabModal').on('shown.bs.modal', updateEditStatusLabel);
    });
</script>
