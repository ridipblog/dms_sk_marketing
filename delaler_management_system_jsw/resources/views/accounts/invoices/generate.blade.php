@extends('layouts.app')



@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-invoice me-2"></i> Generate New Invoice
            </h4>
            <div>
                @if (isset($invoice) && $invoice->invoice_status == 0)
                    <button type="button" class="btn btn-success shadow-sm fw-bold me-2 generate-invoice-btn"
                        data-id="{{ Crypt::encryptString($invoice_id) }}">
                        <i class="fas fa-file-invoice me-1"></i> Generate Invoice
                    </button>
                @endif
                <a href="{{ route('accounts.invoices.index') }}" class="btn btn-outline-secondary shadow-sm fw-bold">
                    <i class="fas fa-arrow-left me-1"></i> Back to Invoices
                </a>
            </div>
        </div>

        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @endif

        <div class="accordion shadow-sm" id="invoiceAccordion">

            <!-- Section 1: Invoice Details -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingInvoice">
                    <button
                        class="accordion-button fw-bold text-primary bg-white {{ isset($invoice_id) ? 'collapsed' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#collapseInvoice"
                        aria-expanded="{{ isset($invoice_id) ? 'false' : 'true' }}" aria-controls="collapseInvoice">
                        <i class="fas fa-file-invoice me-2"></i> 1. Invoice Details
                    </button>
                </h2>
                <div id="collapseInvoice" class="accordion-collapse collapse {{ !isset($invoice_id) ? 'show' : '' }}"
                    aria-labelledby="headingInvoice">
                    <div class="accordion-body">
                        <form action="{{ route('accounts.invoices.store') }}" method="POST" id="generateInvoiceForm">
                            @csrf
                            <input type="hidden" name="invoice_id" id="invoice_id"
                                value="{{ isset($invoice) ? Crypt::encryptString($invoice->id) : '' }}">
                            <div class="row">
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="user_invoice_no" class="form-label fw-bold">User Invoice No</label>
                                    <input type="text" class="form-control" id="user_invoice_no" name="user_invoice_no"
                                        value="{{ $invoice->user_invoice_no ?? '' }}" placeholder="Enter Manual Invoice No">
                                </div>

                                <div class="col-12 col-md-3 mb-3">
                                    <label for="buyer_id" class="form-label fw-bold">Select Buyer <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="buyer_id" name="buyer_id" required>
                                        <option value="">Choose Buyer...</option>
                                        @if (isset($dealerCompanies))
                                            @foreach ($dealerCompanies as $dealerCompany)
                                                <option value="{{ $dealerCompany->id ?? null }}"
                                                    {{ isset($invoice) && ($invoice->buyer_id ?? null) == ($dealerCompany->id ?? '') ? 'selected' : '' }}>
                                                    {{ $dealerCompany->dealer->dealer_name ?? null }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="col-12 col-md-3 mb-3">
                                    <label for="ship_to" class="form-label fw-bold">Ship To <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="ship_to" name="ship_to" required>
                                        <option value="">Choose Ship To...</option>
                                        @if (isset($dealerCompanies))
                                            @foreach ($dealerCompanies as $dealerCompany)
                                                <option value="{{ $dealerCompany->id ?? null }}"
                                                    {{ isset($invoice) && ($invoice->ship_to ?? null) == ($dealerCompany->id ?? '') ? 'selected' : '' }}>
                                                    {{ $dealerCompany->dealer->dealer_name ?? null }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="col-12 col-md-3 mb-3">
                                    <label for="invoice_generate_date" class="form-label fw-bold">Invoice Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="invoice_generate_date" name="invoice_generate_date"
                                        value="{{ isset($invoice->invoice_generate_date) ? \Carbon\Carbon::parse($invoice->invoice_generate_date)->format('Y-m-d') : date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="gst" class="form-label fw-bold">GST (%) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="gst" name="gst"
                                        value="{{ $invoice->gst ?? 18 }}" required>
                                </div>

                                <div class="col-12 col-md-3 mb-3">
                                    <label for="vehicle_no" class="form-label fw-bold">Motor/Vehicle No</label>
                                    <input type="text" class="form-control" id="vehicle_no" name="vehicle_no"
                                        value="{{ $invoice->vehicle_no ?? '' }}" placeholder="e.g. KA-01-AB-1234">
                                </div>

                                <div class="col-12 col-md-3 mb-3">
                                    <label for="delivery_note" class="form-label fw-bold">Delivery Note</label>
                                    <input type="text" class="form-control" id="delivery_note" name="delivery_note"
                                        value="{{ $invoice->delivery_note ?? '' }}" placeholder="Enter Delivery Note">
                                </div>

                                <div class="col-12 col-md-3 mb-3">
                                    <label for="destination" class="form-label fw-bold">Destination</label>
                                    <input type="text" class="form-control" id="destination" name="destination"
                                        value="{{ $invoice->destination ?? '' }}" placeholder="Enter Destination">
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="fas fa-save me-1"></i> Save Invoice
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Section 2: Add Item -->
            @if (isset($invoice_id))
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="headingAddItem">
                        <button class="accordion-button fw-bold text-secondary bg-white collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseAddItem" aria-expanded="false"
                            aria-controls="collapseAddItem">
                            <i class="fas fa-plus-circle me-2"></i> 2. Add Invoice Item
                        </button>
                    </h2>
                    <div id="collapseAddItem" class="accordion-collapse collapse" aria-labelledby="headingAddItem">
                        <div class="accordion-body">
                            <form action="{{ route('accounts.invoices.store_item') }}" method="POST"
                                id="addInvoiceItemForm">
                                @csrf
                                <input type="hidden" name="invoice_id"
                                    value="{{ isset($invoice_id) ? Crypt::encryptString($invoice_id) : '' }}">
                                <div class="row align-items-end">
                                    <div class="col-12 col-md-3 mb-3">
                                        <label for="product_pricing_id" class="form-label fw-bold">Select Product <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" id="product_pricing_id" name="product_pricing_id"
                                            required>
                                            <option value="">Choose Product...</option>
                                            @if (isset($products))
                                                @foreach ($products as $productPricing)
                                                    <option value="{{ $productPricing->id ?? null }}"
                                                        data-price="{{ $productPricing->price_per_mt ?? 0 }}"
                                                        data-gst="{{ $invoice->gst ?? 18 }}"
                                                        data-stock="{{ $productPricing->product->stock_quantity ?? 0 }}">
                                                        {{ $productPricing->product->product_name ?? 'Unknown Product' }}
                                                        (₹{{ $productPricing->price_per_mt ?? 0 }}/MT)
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3">
                                        <label for="custom_price" class="form-label fw-bold">Custom Price (₹/MT) <span
                                                class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="custom_price"
                                            name="custom_price" placeholder="Enter Price/MT" required>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3">
                                        <label for="quantity" class="form-label fw-bold">Quantity (MT) <span
                                                class="text-danger">*</span></label>
                                        <input type="number" step="0.001" class="form-control" id="quantity"
                                            name="quantity" required>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3">
                                        <label for="rate_exclude_tax" class="form-label fw-bold">Amount (excl. tax)</label>
                                        <input type="number" step="0.01" class="form-control bg-light"
                                            id="rate_exclude_tax" name="rate_exclude_tax" readonly>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3">
                                        <label for="rate_include_tax" class="form-label fw-bold">Amount (incl. tax)</label>
                                        <input type="number" step="0.01" class="form-control bg-light"
                                            id="rate_include_tax" name="rate_include_tax" readonly>
                                    </div>

                                    <div class="col-12 col-md-1 mb-3 d-grid">
                                        <button type="submit" class="btn btn-success fw-bold" title="Save Item">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Section 3: Added Items Container -->
            <div id="invoice-items-container">
                <!-- Rendered via AJAX -->
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script type="importmap">
        {
            "imports": {
                "ReuseInvoiceModule": "{{ asset('js/accounts/invoices/reuseleinvoice.js') }}?v={{ config('app.asset_version') }}"
            }
        }
    </script>
    <script>
        const fetchInvoiceItemsUrl = "{{ route('accounts.invoices.fetch_items') }}";
        const finalizeInvoiceUrl = "{{ route('accounts.invoices.finalize') }}";
    </script>
    <script type="module"
        src="{{ asset('js/accounts/invoices/invoice_generate.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
