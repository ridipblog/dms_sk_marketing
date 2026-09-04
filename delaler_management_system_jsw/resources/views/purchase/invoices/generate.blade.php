@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-invoice me-2"></i> Generate Purchase Invoice
            </h4>
            <div>
                @if (isset($purchase) && $purchase->status == 0)
                    <button type="button" class="btn btn-success shadow-sm fw-bold me-2" id="btnFinalizePurchase">
                        <i class="fas fa-file-invoice me-1"></i> Finalize Bill
                    </button>
                @endif
                <a href="{{ route('purchase.invoices.index') }}" class="btn btn-outline-secondary shadow-sm fw-bold">
                    <i class="fas fa-arrow-left me-1"></i> Back to Invoices
                </a>
            </div>
        </div>

        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @endif

        <div class="accordion shadow-sm" id="purchaseAccordion">

            <!-- Section 1: Purchase Header Details -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingPurchase">
                    <button class="accordion-button fw-bold text-primary bg-white {{ isset($purchase_id) ? 'collapsed' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#collapsePurchase"
                        aria-expanded="{{ isset($purchase_id) ? 'false' : 'true' }}" aria-controls="collapsePurchase">
                        <i class="fas fa-info-circle me-2"></i> 1. Purchase Bill Details
                    </button>
                </h2>
                <div id="collapsePurchase" class="accordion-collapse collapse {{ !isset($purchase_id) ? 'show' : '' }}"
                    aria-labelledby="headingPurchase">
                    <div class="accordion-body">
                        <form action="{{ route('purchase.invoices.store') }}" method="POST" id="generatePurchaseForm">
                            @csrf
                            <input type="hidden" name="purchase_id" id="purchase_id"
                                value="{{ isset($purchase) ? Crypt::encryptString($purchase->id) : '' }}">
                            <div class="row">
                                <div class="col-12 col-md-4 mb-3">
                                    <label for="supplier_id" class="form-label fw-bold">Select Supplier <span class="text-danger">*</span></label>
                                    <select class="form-select select2" id="supplier_id" name="supplier_id" required>
                                        <option value="">Choose Supplier...</option>
                                        @foreach ($suppliers ?? [] as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ isset($purchase) && $purchase->supplier_id == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4 mb-3">
                                    <label for="purchase_date" class="form-label fw-bold">Purchase Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date"
                                        value="{{ isset($purchase) ? $purchase->purchase_date->format('Y-m-d') : date('Y-m-d') }}" required>
                                </div>

                                <div class="col-12 col-md-4 mb-3">
                                    <label for="due_date" class="form-label fw-bold">Payment Due Date</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date"
                                        value="{{ isset($purchase) && $purchase->due_date ? $purchase->due_date->format('Y-m-d') : '' }}">
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="fas fa-save me-1"></i> Save Draft
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Section 2: Add Line Item -->
            @if (isset($purchase_id))
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="headingAddItem">
                        <button class="accordion-button fw-bold text-secondary bg-white collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseAddItem" aria-expanded="false"
                            aria-controls="collapseAddItem">
                            <i class="fas fa-plus-circle me-2"></i> 2. Add Bill Line Item
                        </button>
                    </h2>
                    <div id="collapseAddItem" class="accordion-collapse collapse" aria-labelledby="headingAddItem">
                        <div class="accordion-body">
                            <form action="{{ route('purchase.invoices.store_item') }}" method="POST" id="addPurchaseItemForm">
                                @csrf
                                <input type="hidden" name="purchase_id" value="{{ Crypt::encryptString($purchase_id) }}">
                                <div class="row align-items-end">
                                    <div class="col-12 col-md-3 mb-3">
                                        <label for="product_id" class="form-label fw-bold">Select Product <span class="text-danger">*</span></label>
                                        <select class="form-select select2" id="product_id" name="product_id" required>
                                            <option value="">Choose Product...</option>
                                            @foreach ($products ?? [] as $prod)
                                                <option value="{{ $prod->id }}" data-price="{{ $prod->base_price ?? 0 }}">
                                                    {{ $prod->product_name ?? 'Unknown Product' }}
                                                    (SKU: {{ $prod->sku_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3">
                                        <label for="quantity" class="form-label fw-bold">Quantity (MT) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.001" class="form-control" id="quantity" name="quantity" required>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3">
                                        <label for="rate" class="form-label fw-bold">Purchase Rate (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" id="rate" name="rate" required>
                                    </div>

                                    <div class="col-12 col-md-1 mb-3">
                                        <label for="gst_percentage" class="form-label fw-bold">GST (%)</label>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="gst_percentage" name="gst_percentage" value="18" required>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3">
                                        <label for="amount_display" class="form-label fw-bold">Total Amount</label>
                                        <input type="number" step="0.01" class="form-control bg-light" id="amount_display" readonly>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3 d-grid">
                                        <button type="submit" class="btn btn-success fw-bold">
                                            <i class="fas fa-plus me-1"></i> Add Item
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Section 3: Added Items Table (Dynamic AJAX) -->
            <div id="purchase-items-container">
                <!-- Rendered dynamically -->
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/purchase/invoices/purchase_invoice_generate.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
