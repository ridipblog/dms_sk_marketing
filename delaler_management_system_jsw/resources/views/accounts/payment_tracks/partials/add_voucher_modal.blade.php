<!-- Add Voucher Modal -->
<div class="modal fade" id="addVoucherModal" tabindex="-1" aria-labelledby="addVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVoucherModalLabel">Add New Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addVoucherForm">
                @csrf
                <input type="hidden" id="invoice_id" name="invoice_id"
                    value="{{ Crypt::encryptString($invoice->id) }}">
                <div class="modal-body">
                    <div id="modalErrorAlert"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount"
                            data-total-quantity="{{ $invoice->total_quantity }}"
                            data-chargeable-amount="{{ $invoice->chargeable_amount }}" required>
                    </div>

                    <div class="mb-3" id="payment_for_mt_container" style="display: none;">
                        <label class="form-label fw-bold">Payment for MT</label>
                        <input type="text" class="form-control bg-light" id="payment_for_mt" name="payment_for_mt"
                            readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Voucher Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="voucher_type_id" name="voucher_type_id" required>
                            <option value="">Select Voucher Type</option>
                            @foreach ($voucherTypes ?? [] as $type)
                                <option value="{{ $type->id ?? null }}">{{ $type->name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="number_of_days_container" style="display: none;">
                        <label class="form-label fw-bold">Number of Days</label>
                        <input type="number" class="form-control" id="number_of_days" name="number_of_days" min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_mode" name="payment_mode" required>
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
                        <input type="date" class="form-control" id="transaction_date" name="transaction_date"
                            value="{{ now()->format('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitVoucher">
                        Save Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
