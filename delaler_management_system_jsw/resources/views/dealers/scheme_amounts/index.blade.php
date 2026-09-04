@extends('layouts.app')

@push('styles')
    <style>
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .header-title {
            font-weight: 700;
            color: #1e293b;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-3">
        <!-- Page Title & Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="header-title mb-1">
                    <i class="fas fa-percentage text-primary me-2"></i> Dealer Scheme Amount Management
                </h4>
                <p class="text-muted small mb-0">Record and manage scheme amounts assigned to dealers.</p>
            </div>
        </div>

        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @endif

        <div class="row g-4">
            <!-- Add Scheme Amount Form Column -->
            <div class="col-12 col-lg-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold mb-0 text-primary">
                            <i class="fas fa-plus-circle me-1"></i> Add Dealer Scheme Amount
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="addSchemeAmountForm">
                            @csrf
                            <div class="mb-3">
                                <label for="dealer_company_id" class="form-label fw-bold small">Select Dealer <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="dealer_company_id" name="dealer_company_id" required>
                                    <option value="">Choose Dealer...</option>
                                    @foreach ($dealerCompanies as $dc)
                                        <option value="{{ $dc->id }}"
                                            {{ (isset($selectedDealerId) && $selectedDealerId == $dc->id) || request('dealer_company_id') == $dc->id ? 'selected' : '' }}>
                                            [{{ $dc->dealer->dealer_code ?? 'N/A' }}] {{ $dc->dealer->dealer_name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="month" class="form-label fw-bold small">Select Month <span class="text-danger">*</span></label>
                                    <select class="form-select" id="month" name="month" required>
                                        <option value="">Select Month...</option>
                                        @php
                                            $months = [
                                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                            ];
                                            $currentMonth = date('n');
                                        @endphp
                                        @foreach($months as $num => $name)
                                            <option value="{{ $num }}" {{ $currentMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="year" class="form-label fw-bold small">Select Year <span class="text-danger">*</span></label>
                                    <select class="form-select" id="year" name="year" required>
                                        @php
                                            $currentYear = date('Y');
                                        @endphp
                                        @for($y = $currentYear - 2; $y <= $currentYear + 2; $y++)
                                            <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="quantity" class="form-label fw-bold small">Quantity (MT) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.001" min="0.001" class="form-control" id="quantity" name="quantity" placeholder="e.g. 10.000" required>
                                </div>
                                <div class="col-6">
                                    <label for="rate_per_mt" class="form-label fw-bold small">Rate / MT (₹) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" min="0.01" class="form-control" id="rate_per_mt" name="rate_per_mt" placeholder="e.g. 500.00" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label fw-bold small">Scheme Amount (₹) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" placeholder="e.g. 5000.00" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="remarks" class="form-label fw-bold small">Remarks / Description</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter details or notes regarding this scheme amount..."></textarea>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm" id="btnSaveScheme">
                                    <i class="fas fa-save me-1"></i> Save Scheme Amount
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Scheme Amount Records List Column -->
            <div class="col-12 col-lg-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-list me-1"></i> Scheme Amount Records
                        </h6>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div id="dealerTotalSchemeBadgeContainer" class="d-none">
                                <span class="badge bg-success fs-6 px-3 py-2" id="dealerTotalSchemeBadge">
                                    Total Scheme Amount: ₹0.00
                                </span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold shadow-sm" id="btnExportSchemeExcel">
                                <i class="fas fa-file-excel me-1"></i> Export Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger fw-bold shadow-sm" id="btnExportSchemePdf">
                                <i class="fas fa-file-pdf me-1"></i> Export PDF
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div id="schemeAmountsTableContainer">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/dealers/scheme_amount.js') }}"></script>
@endpush
