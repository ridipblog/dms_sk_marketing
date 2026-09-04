@if (isset($invoiceDetails) && $invoiceDetails->count() > 0)
    <!-- Section 3: Added Items -->
    <div class="accordion-item border-0">
        <h2 class="accordion-header position-relative" id="headingAddedItems">
            <button class="accordion-button fw-bold text-success bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAddedItems" aria-expanded="true" aria-controls="collapseAddedItems">
                <span><i class="fas fa-list me-2"></i> 3. Added Items ({{ $invoiceDetails->count() }})</span>
            </button>
            <div class="position-absolute end-0 top-50 translate-middle-y me-5 pe-3" style="z-index: 10;">
                <button type="button" class="btn btn-sm btn-outline-primary shadow-sm fw-bold" id="refresh-items-btn" title="Refresh Items">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </h2>
        <div id="collapseAddedItems" class="accordion-collapse collapse show" aria-labelledby="headingAddedItems">
            <div class="accordion-body bg-light">
                @foreach ($invoiceDetails as $detail)
                    <form action="{{ route('accounts.invoices.store_item') }}" method="POST" class="edit-invoice-item-form mb-3 border-bottom pb-3"
                        data-id="{{ $detail->id }}">
                        @csrf
                        <input type="hidden" name="invoice_detail_id" value="{{ Crypt::encryptString($detail->id) }}">
                        <input type="hidden" name="invoice_id" value="{{ Crypt::encryptString($detail->invoice_id) }}">
                        <div class="row align-items-end">
                            <div class="col-12 col-md-3 mb-3">
                                <label class="form-label fw-bold">Select Product <span class="text-danger">*</span></label>
                                <select class="form-select edit-product-id" name="product_pricing_id" required>
                                    @if (isset($products))
                                        @foreach ($products as $productPricing)
                                            <option value="{{ $productPricing->id }}"
                                                data-price="{{ $productPricing->price_per_mt ?? 0 }}"
                                                data-gst="{{ $productPricing->product->gst ?? $productPricing->gst_percentage ?? 18 }}"
                                                data-stock="{{ $productPricing->product->stock_quantity ?? 0 }}"
                                                {{ $detail->product_pricing_id == $productPricing->id ? 'selected' : '' }}>
                                                {{ $productPricing->product->product_name ?? 'Unknown Product' }}
                                                (₹{{ $productPricing->price_per_mt ?? 0 }}/MT)
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-12 col-md-2 mb-3">
                                <label class="form-label fw-bold">Custom Price (₹/MT) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control edit-price" name="custom_price"
                                    value="{{ $detail->custom_price ?? ($detail->quantity > 0 ? number_format($detail->total_amount / $detail->quantity, 2, '.', '') : '') }}" placeholder="Enter Price/MT" required>
                            </div>

                            <div class="col-12 col-md-2 mb-3">
                                <label class="form-label fw-bold">Quantity (MT) <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" class="form-control edit-quantity" name="quantity"
                                    value="{{ $detail->quantity }}" required>
                            </div>

                            <div class="col-12 col-md-2 mb-3">
                                <label class="form-label fw-bold">Amount (excl. tax)</label>
                                <input type="number" step="0.01" class="form-control bg-light edit-rate-exclude"
                                    name="rate_exclude_tax" value="{{ $detail->total_amount }}" readonly>
                            </div>

                            <div class="col-12 col-md-2 mb-3">
                                <label class="form-label fw-bold">Amount (incl. tax)</label>
                                <input type="number" step="0.01" class="form-control bg-light edit-rate-include"
                                    name="rate_include_tax" value="{{ $detail->chargeable_amount }}" readonly>
                            </div>

                            <div class="col-12 col-md-1 mb-3 d-flex gap-1">
                                <button type="submit" class="btn btn-warning fw-bold p-2 flex-grow-1" title="Update Item">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger fw-bold delete-item-btn p-2" title="Delete Item">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@endif
