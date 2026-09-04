<div class="modal fade" id="editDealerModal" tabindex="-1" aria-labelledby="editDealerModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="editDealerModalLabel">
                    <i class="fas fa-user-edit me-2"></i> Edit Dealer
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="editDealerForm" action="" method="POST">
                @csrf
                <input type="hidden" id="edit_dealer_id" name="dealer_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_dealer_name" class="form-label fw-bold text-muted">Dealer Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="edit_dealer_name" name="dealer_name"
                                placeholder="Enter dealer name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_dealer_code" class="form-label fw-bold text-muted">Dealer Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="edit_dealer_code" name="dealer_code"
                                placeholder="e.g. DLR-001" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label fw-bold text-muted">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control bg-light" id="edit_email" name="email"
                                placeholder="example@dealer.com" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_phone" class="form-label fw-bold text-muted">Phone Number <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" id="edit_phone" name="phone"
                                placeholder="Enter phone number" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label fw-bold text-muted">Status <span
                                    class="text-danger">*</span></label>
                            <select class="form-select bg-light" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_pan_number" class="form-label fw-bold text-muted">PAN Number</label>
                            <input type="text" class="form-control bg-light" id="edit_pan_number" name="pan_number"
                                placeholder="Enter PAN number">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_gst_number" class="form-label fw-bold text-muted">GST Number</label>
                            <input type="text" class="form-control bg-light" id="edit_gst_number" name="gst_number"
                                placeholder="Enter GST number">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_aso_id" class="form-label fw-bold text-muted">Assigned ASO <small class="text-muted fw-normal">(Optional)</small></label>
                            <select class="form-select select2 bg-light" id="edit_aso_id" name="aso_id" data-placeholder="-- Select ASO Officer (Optional) --">
                                <option value=""></option>
                                @foreach($asos ?? [] as $aso)
                                    <option value="{{ $aso['id'] }}">{{ $aso['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="edit_address" class="form-label fw-bold text-muted">Complete Address</label>
                            <textarea class="form-control bg-light" id="edit_address" name="address" rows="2"
                                placeholder="Enter dealer address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary fw-bold px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnUpdateDealer">
                        <i class="fas fa-save me-2"></i> Update Dealer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
