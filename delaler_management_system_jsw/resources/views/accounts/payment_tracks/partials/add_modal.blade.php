<!-- Add Payment Track Modal -->
<div class="modal fade" id="addPaymentTrackModal" tabindex="-1" aria-labelledby="addPaymentTrackModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addPaymentTrackModalLabel"><i class="fas fa-plus-circle me-2"></i>Add Payment
                    Receipt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="addPaymentTrackForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Order No</label>
                            <select class="form-select" name="order_id" required>
                                <option value="">Select Order</option>
                                <option value="1">ORD-2024-001</option>
                                <option value="2">ORD-2024-002</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" name="transaction_id"
                                placeholder="e.g. TXN-987654321" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="amount" placeholder="0.00"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Voucher Type</label>
                            <select class="form-select voucher-type-select" name="voucher_type_id" required>
                                <option value="">Select Voucher Type</option>
                                @foreach ($voucherTypes as $vt)
                                    <option value="{{ $vt->id }}" data-name="{{ $vt->name }}">
                                        {{ $vt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 journal-type-container" style="display: none;">
                            <label class="form-label">Journal Type</label>
                            <select class="form-select journal-type-select" name="journal_type">
                                <option value="">Select Type</option>
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                            <div class="invalid-feedback">Please select Debit or Credit.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment For MT</label>
                            <input type="number" step="0.001" class="form-control payment-for-mt-input"
                                name="payment_for_mt" placeholder="0.000">
                            <div class="invalid-feedback">Payment For MT is required for Receipts.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Mode</label>
                            <select class="form-select" name="payment_mode" required>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                                <option value="cash">Cash</option>
                                <option value="credit_note">Credit Note</option>
                                <option value="debit_note">Debit Note</option>
                                <option value="accounts">Accounts</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transaction Date</label>
                            <input type="datetime-local" class="form-control" name="transaction_date"
                                value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>
