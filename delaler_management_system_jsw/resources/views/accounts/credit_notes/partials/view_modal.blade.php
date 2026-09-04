<div class="modal fade" id="viewCreditNoteModal" tabindex="-1" aria-labelledby="viewCreditNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="viewCreditNoteModalLabel">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Credit Note Details
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-5 fw-bold text-muted">Order No:</div>
                            <div class="col-sm-7 fw-bold text-success" id="viewCreditOrderNo"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 fw-bold text-muted">Dealer Name:</div>
                            <div class="col-sm-7" id="viewCreditDealerName"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 fw-bold text-muted">Transaction ID:</div>
                            <div class="col-sm-7" id="viewCreditTransactionId"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 fw-bold text-muted">Credit Date:</div>
                            <div class="col-sm-7" id="viewCreditDate"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 fw-bold text-muted">Amount:</div>
                            <div class="col-sm-7 fw-bold" id="viewCreditAmount"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 fw-bold text-muted">Quantity/Nos:</div>
                            <div class="col-sm-7" id="viewCreditNos"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-5 fw-bold text-muted">Reason:</div>
                            <div class="col-sm-7" id="viewCreditReason" style="white-space: pre-wrap;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 bg-light">
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
