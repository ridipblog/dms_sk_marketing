@if (isset($purchase->purchaseInvoiceDetails) && $purchase->purchaseInvoiceDetails->count() > 0)
    <div class="accordion-item border-0">
        <h2 class="accordion-header position-relative" id="headingAddedItems">
            <button class="accordion-button fw-bold text-success bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAddedItems" aria-expanded="true" aria-controls="collapseAddedItems">
                <span><i class="fas fa-list me-2"></i> 3. Added Bill Items ({{ $purchase->purchaseInvoiceDetails->count() }})</span>
            </button>
            <div class="position-absolute end-0 top-50 translate-middle-y me-5 pe-3" style="z-index: 10;">
                <button type="button" class="btn btn-sm btn-outline-primary shadow-sm fw-bold" id="refresh-items-btn" title="Refresh Items">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </h2>
        <div id="collapseAddedItems" class="accordion-collapse collapse show" aria-labelledby="headingAddedItems">
            <div class="accordion-body bg-light">
                @foreach ($purchase->purchaseInvoiceDetails as $detail)
                    <form action="{{ route('purchase.invoices.store_item') }}" method="POST" class="edit-purchase-item-form mb-3 border-bottom pb-3"
                        data-id="{{ $detail->id }}">
                        @csrf
                        <input type="hidden" name="purchase_detail_id" value="{{ Crypt::encryptString($detail->id) }}">
                        <input type="hidden" name="purchase_id" value="{{ Crypt::encryptString($detail->purchase_invoice_id) }}">
                        @php
                            $gstPercent = $detail->total_amount > 0 ? round(($detail->gst_amount / $detail->total_amount) * 100, 2) : 18.00;
                        @endphp
                        <div class="row align-items-end">
                            <div class="col-12 col-md-3 mb-3">
                                <label class="form-label fw-bold">Select Product <span class="text-danger">*</span></label>
                                <select class="form-select edit-product-id select2" name="product_id" required>
                                    @if (isset($products))
                                        @foreach ($products as $prod)
                                            <option value="{{ $prod->id }}"
                                                data-price="{{ $prod->base_price ?? 0 }}"
                                                {{ $detail->product_id == $prod->id ? 'selected' : '' }}>
                                                {{ $prod->product_name ?? 'Unknown Product' }}
                                                (SKU: {{ $prod->sku_code }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-12 col-md-2 mb-3">
                                <label class="form-label fw-bold">Quantity (MT) <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" class="form-control edit-quantity" name="quantity"
                                    value="{{ $detail->quantity }}" required>
                            </div>

                            <div class="col-12 col-md-2 mb-3">
                                <label class="form-label fw-bold">Rate (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control edit-rate" name="rate"
                                    value="{{ $detail->rate }}" required>
                            </div>

                            <div class="col-12 col-md-1 mb-3">
                                <label class="form-label fw-bold">GST (%)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control edit-gst-percentage" name="gst_percentage"
                                    value="{{ $gstPercent }}" required>
                            </div>

                            <div class="col-12 col-md-2 mb-3">
                                <label class="form-label fw-bold">Amount</label>
                                <input type="number" step="0.01" class="form-control bg-light edit-amount-exclude"
                                    value="{{ $detail->total_amount }}" readonly>
                            </div>

                            <div class="col-12 col-md-2 mb-3 d-flex gap-2">
                                <button type="submit" class="btn btn-warning fw-bold flex-grow-1" title="Update Item">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <button type="button" class="btn btn-danger fw-bold delete-item-btn" title="Delete Item"
                                    data-detail-id="{{ Crypt::encryptString($detail->id) }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                @endforeach

                <!-- Summary Totals -->
                <div class="card mt-3 border-0 shadow-sm">
                    <div class="card-body bg-white rounded">
                        <div class="row text-center text-md-start">
                            <div class="col-md-3 mb-2 mb-md-0">
                                <small class="text-muted d-block">TOTAL QUANTITY</small>
                                <span class="fw-bold fs-5 text-dark">{{ number_format($purchase->total_quantity, 3) }} MT</span>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <small class="text-muted d-block">AMOUNT EXCL. GST</small>
                                <span class="fw-bold fs-5 text-primary">₹{{ number_format($purchase->total_amount, 2) }}</span>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <small class="text-muted d-block">TOTAL GST AMOUNT</small>
                                <span class="fw-bold fs-5 text-danger">₹{{ number_format($purchase->total_gst_amount, 2) }}</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">TOTAL CHARGEABLE AMOUNT</small>
                                <span class="fw-bold fs-5 text-success">₹{{ number_format($purchase->chargeable_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endif
