<!-- View Payment Track Modal -->
<div class="modal fade" id="viewPaymentTrackModal" tabindex="-1" aria-labelledby="viewPaymentTrackModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-success text-white border-0 py-2"
                style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                <h6 class="modal-title fw-bold mb-0" id="viewPaymentTrackModalLabel" style="font-size: 0.9rem;">
                    <i class="fas fa-eye me-2 shadow-sm rounded-circle p-1 bg-white text-success"
                        style="font-size: 0.7rem;"></i> Payment Receipt Details
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">

                <!-- Summary Banner -->
                <div class="card border-0 shadow-sm mb-3 rounded-3"
                    style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid #28a745 !important;">
                    <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                        <div>
                            <p class="text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.55rem;"><i
                                    class="fas fa-receipt me-1 text-primary"></i> Transaction ID</p>
                            <div class="mb-0 fw-bold text-primary" id="view_transaction_id"
                                style="font-size: 0.8rem; letter-spacing: 0.5px;">-</div>
                        </div>
                        <div class="text-end">
                            <p class="text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.55rem;"><i
                                    class="fas fa-rupee-sign me-1 text-success"></i> Amount</p>
                            <div class="mb-0 fw-bold text-success" id="view_amount" style="font-size: 0.8rem;">-</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- General Info Card -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-3 border-primary rounded-3">
                            <div class="card-body p-3">
                                <div class="fw-bold text-primary border-bottom pb-1 mb-2" style="font-size: 0.75rem;"><i
                                        class="fas fa-info-circle me-2"></i> Payment Info</div>

                                <div class="mb-2 d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary p-1 rounded-circle me-2 text-center"
                                        style="width: 24px; height: 24px; line-height: 1.5; font-size: 0.65rem;">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.55rem;">
                                            Order No</p>
                                        <div class="mb-0 fw-bold text-dark" id="view_order_no"
                                            style="font-size: 0.75rem;">-</div>
                                    </div>
                                </div>
                                <div class="mb-2 d-flex align-items-center">
                                    <div class="bg-info bg-opacity-10 text-info p-1 rounded-circle me-2 text-center"
                                        style="width: 24px; height: 24px; line-height: 1.5; font-size: 0.65rem;">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.55rem;">
                                            Payment Mode</p>
                                        <div class="mb-0 fw-bold text-dark" id="view_payment_mode"
                                            style="font-size: 0.75rem;">-</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="bg-secondary bg-opacity-10 text-secondary p-1 rounded-circle me-2 text-center"
                                        style="width: 24px; height: 24px; line-height: 1.5; font-size: 0.65rem;">
                                        <i class="fas fa-weight-hanging"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.55rem;">
                                            Payment For MT</p>
                                        <div class="mb-0 fw-bold text-dark" id="view_payment_for_mt"
                                            style="font-size: 0.75rem;">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Details Card -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-3 border-warning rounded-3">
                            <div class="card-body p-3">
                                <div class="fw-bold text-warning border-bottom pb-1 mb-2" style="font-size: 0.75rem;"><i
                                        class="fas fa-chart-line me-2 text-dark"></i> <span class="text-dark">Financial
                                        Details</span></div>

                                <div class="p-2 bg-white rounded border shadow-sm">
                                    <div class="d-flex justify-content-between mb-2 align-items-center">
                                        <span class="text-muted fw-bold" style="font-size: 0.65rem;">PREVIOUS
                                            BALANCE</span>
                                        <span class="text-dark fw-bold" id="view_opening_balance"
                                            style="font-size: 0.75rem;">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 align-items-center">
                                        <span class="text-muted fw-bold" id="view_action_label"
                                            style="font-size: 0.65rem;">TRANSACTION</span>
                                        <span class="text-dark fw-bold" id="view_action_amount"
                                            style="font-size: 0.75rem;">-</span>
                                    </div>
                                    <hr class="my-2 border-dark opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-dark fw-bold" style="font-size: 0.75rem;">CLOSING
                                            BALANCE</span>
                                        <span class="text-primary fw-bold" id="view_closing_balance"
                                            style="font-size: 0.85rem;">-</span>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-6 d-flex align-items-center">
                                        <i class="fas fa-tags text-success me-2 opacity-75"
                                            style="font-size: 0.7rem;"></i>
                                        <div>
                                            <p class="text-muted mb-0 text-uppercase fw-bold"
                                                style="font-size: 0.55rem;">Voucher Type</p>
                                            <div class="mb-0 text-dark fw-bold" id="view_voucher_type"
                                                style="font-size: 0.75rem;">-</div>
                                        </div>
                                    </div>
                                    <div class="col-6 d-flex align-items-center">
                                        <i class="fas fa-calendar-alt text-primary me-2 opacity-75"
                                            style="font-size: 0.7rem;"></i>
                                        <div>
                                            <p class="text-muted mb-0 text-uppercase fw-bold"
                                                style="font-size: 0.55rem;">Date</p>
                                            <div class="mb-0 text-dark fw-bold" id="view_transaction_date"
                                                style="font-size: 0.75rem;">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm bg-white rounded-3">
                            <div class="card-body py-2 px-3">
                                <p class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.55rem;"><i
                                        class="fas fa-comment-alt me-1 text-secondary"></i> Remarks</p>
                                <div class="text-dark" id="view_remarks" style="font-size: 0.75rem;">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top-0 py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-bold shadow-sm"
                    data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
