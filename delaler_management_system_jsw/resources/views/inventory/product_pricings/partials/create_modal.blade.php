<div class="modal fade" id="createPricingModal" tabindex="-1" aria-labelledby="createPricingModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="createPricingModalLabel">
                    <i class="fas fa-plus-circle me-2"></i> Add Product Pricing
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="createPricingForm" action="{{ route('product_pricings.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="product_id" class="form-label fw-bold text-muted">Select Product <span
                                    class="text-danger">*</span></label>
                            <select class="form-select bg-light" id="product_id" name="product_id" required>
                                <option value="" selected disabled>Select a Product...</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->product_name }} (SKU: {{ $product->sku_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="price_per_mt" class="form-label fw-bold text-muted">Price per MT (₹) <span
                                    class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control bg-light" id="price_per_mt" name="price_per_mt"
                                placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label for="price_type" class="form-label fw-bold text-muted">Price Type</label>
                            <input type="text" class="form-control bg-light" id="price_type" name="price_type"
                                placeholder="e.g. Standard, Premium">
                        </div>
                        <div class="col-md-6">
                            <label for="gst_percentage" class="form-label fw-bold text-muted">GST Percentage (%)</label>
                            <input type="number" step="0.01" class="form-control bg-light" id="gst_percentage" name="gst_percentage"
                                placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="discount_amount" class="form-label fw-bold text-muted">Discount Amount (₹)</label>
                            <input type="number" step="0.01" class="form-control bg-light" id="discount_amount" name="discount_amount"
                                placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary fw-bold px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnSubmitPricing">
                        <i class="fas fa-save me-2"></i> Save Pricing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
