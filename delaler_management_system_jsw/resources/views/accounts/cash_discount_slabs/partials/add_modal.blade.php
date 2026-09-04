<!-- Add Slab Modal -->
<div class="modal fade" id="addSlabModal" tabindex="-1" aria-labelledby="addSlabModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addSlabModalLabel"><i class="fas fa-plus-circle me-2"></i>Add Cash Discount Slab</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSlabForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Slab Name</label>
                            <input type="text" class="form-control" name="slab_name" placeholder="e.g. 0-5 Days" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Minimum Days</label>
                            <input type="number" class="form-control" name="minimum_days" placeholder="0" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maximum Days</label>
                            <input type="number" class="form-control" name="maximum_days" placeholder="5" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Discount Percent (%)</label>
                            <input type="number" step="0.01" class="form-control" name="discount_percent" placeholder="2.00" min="0" max="100" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="status" value="1" id="addSlabStatus" checked>
                                <label class="form-check-label fw-bold text-success" for="addSlabStatus">Enable Slab</label>
                            </div>
                            <small class="text-muted d-block mt-1">When enabled, this discount slab can be applied to credit notes.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Slab</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusCheckbox = document.getElementById('addSlabStatus');
        const statusLabel = statusCheckbox.nextElementSibling;

        statusCheckbox.addEventListener('change', function() {
            if (this.checked) {
                statusLabel.textContent = 'Enable Slab';
                statusLabel.classList.remove('text-secondary');
                statusLabel.classList.add('text-success');
            } else {
                statusLabel.textContent = 'Disable Slab';
                statusLabel.classList.remove('text-success');
                statusLabel.classList.add('text-secondary');
            }
        });
    });
</script>
