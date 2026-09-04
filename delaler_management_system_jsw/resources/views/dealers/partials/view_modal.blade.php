<!-- View Dealer Modal -->
<div class="modal fade" id="viewDealerModal" tabindex="-1" aria-labelledby="viewDealerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <!-- Modal Header (Profile Style) -->
            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                <div class="d-flex align-items-center w-100 p-3 pt-2 text-white">
                    <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm me-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-0 fw-bold" id="view_dealer_name">Loading...</h4>
                        <p class="mb-0 text-white-50 small"><i class="fas fa-id-badge me-1"></i> <span id="view_dealer_code">...</span></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 bg-light">
                <div class="row g-4">
                    <!-- Contact Information -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3"><i class="fas fa-address-book me-2"></i>Contact Details</h6>
                                
                                <div class="mb-3 d-flex align-items-start">
                                    <i class="fas fa-envelope text-primary mt-1 me-2" style="width: 20px;"></i>
                                    <div>
                                        <p class="text-muted mb-0" style="font-size: 0.7rem; text-transform: uppercase;">Email Address</p>
                                        <div class="fw-bold text-dark" id="view_email" style="font-size: 0.9rem;">...</div>
                                    </div>
                                </div>
                                <div class="mb-3 d-flex align-items-start">
                                    <i class="fas fa-phone-alt text-success mt-1 me-2" style="width: 20px;"></i>
                                    <div>
                                        <p class="text-muted mb-0" style="font-size: 0.7rem; text-transform: uppercase;">Phone Number</p>
                                        <div class="fw-bold text-dark" id="view_phone" style="font-size: 0.9rem;">...</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-map-marker-alt text-danger mt-1 me-2" style="width: 20px;"></i>
                                    <div>
                                        <p class="text-muted mb-0" style="font-size: 0.7rem; text-transform: uppercase;">Address</p>
                                        <div class="fw-bold text-dark" id="view_address" style="font-size: 0.9rem;">...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax & Status Information -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3"><i class="fas fa-file-invoice-dollar me-2"></i>Tax & Status</h6>
                                
                                <div class="row g-3">
                                    <div class="col-6">
                                        <p class="text-muted mb-0" style="font-size: 0.7rem; text-transform: uppercase;">PAN Number</p>
                                        <div class="fw-bold text-dark" id="view_pan" style="font-size: 0.9rem;">...</div>
                                    </div>
                                    <div class="col-6">
                                        <p class="text-muted mb-0" style="font-size: 0.7rem; text-transform: uppercase;">GST Number</p>
                                        <div class="fw-bold text-dark" id="view_gst" style="font-size: 0.9rem;">...</div>
                                    </div>
                                    <div class="col-6">
                                        <p class="text-muted mb-0" style="font-size: 0.7rem; text-transform: uppercase;">Assigned ASO</p>
                                        <div class="fw-bold text-dark" id="view_aso" style="font-size: 0.9rem;">...</div>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <p class="text-muted mb-1" style="font-size: 0.7rem; text-transform: uppercase;">Account Status</p>
                                        <div id="view_status_container">
                                            <span class="badge bg-secondary">...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Associated Companies -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold text-secondary mb-0"><i class="fas fa-city me-2"></i>Associated Companies & Balances</h6>
                            </div>
                            <div class="card-body p-0">
                                <div id="view_companies_container" class="d-flex flex-column p-3 gap-2" style="max-height: 250px; overflow-y: auto;">
                                    <!-- JS will populate flex items here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
