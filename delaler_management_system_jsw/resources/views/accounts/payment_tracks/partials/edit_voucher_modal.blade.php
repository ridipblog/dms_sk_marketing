<!-- Edit Voucher Modal -->
<div class="modal fade" id="editVoucherModal" tabindex="-1" aria-labelledby="editVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVoucherModalLabel">Edit Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVoucherForm">
                @csrf
                <input type="hidden" id="edit_voucher_id" name="voucher_id">
                <div class="modal-body">
                    <div id="editModalErrorAlert"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount"
                            data-total-quantity="{{ $invoice->total_quantity }}"
                            data-chargeable-amount="{{ $invoice->chargeable_amount }}" required>
                    </div>

                    <div class="mb-3" id="edit_payment_for_mt_container" style="display: none;">
                        <label class="form-label fw-bold">Payment for MT</label>
                        <input type="text" class="form-control bg-light" id="edit_payment_for_mt" name="payment_for_mt"
                            readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Voucher Type</label>
                        <input type="text" class="form-control bg-light" id="edit_voucher_type_name" readonly>
                    </div>

                    <div class="mb-3" id="edit_number_of_days_container" style="display: none;">
                        <label class="form-label fw-bold">Number of Days</label>
                        <input type="number" class="form-control" id="edit_number_of_days" name="number_of_days" min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_payment_mode" name="payment_mode" required>
                            <option value="">Select Payment Mode</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="upi">UPI</option>
                            <option value="credit_note">Credit Note</option>
                            <option value="debit_note">Debit Note</option>
                            <option value="accounts">Accounts</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="entry">Entry</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Transaction Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_transaction_date" name="transaction_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="3" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnUpdateVoucher">
                        Update Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
