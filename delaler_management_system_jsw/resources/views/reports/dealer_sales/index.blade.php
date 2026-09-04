@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-chart-bar me-2"></i> Dealer Wise Sales Report
            </h4>
            <a href="javascript:void(0)" id="btnExportReport" class="btn btn-outline-success shadow-sm fw-bold">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>

        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @else
            <!-- Summary Metric Cards -->
            <div class="row mb-4 g-3">
                <div class="col-md-6 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Sales & Debit Transactions</span>
                                <h3 class="fw-bold text-primary my-1" id="cardTotalCount">0</h3>
                                <small class="text-muted"><i class="fas fa-file-invoice me-1"></i>Filtered Sales / Debit Vouchers</small>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Sales Amount</span>
                                <h3 class="fw-bold text-danger my-1" id="cardTotalAmount">₹ 0.00</h3>
                                <small class="text-muted"><i class="fas fa-coins me-1"></i>Combined Sales & Debit Total</small>
                            </div>
                            <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                                <i class="fas fa-rupee-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="row g-2 mb-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary me-2 shadow-sm" id="btnRefreshReport" title="Refresh Report">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="searchReport" class="form-control border-start-0"
                                placeholder="Search by Invoice No, Txn ID, Remarks...">
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white text-muted">
                                <i class="fas fa-calendar-alt me-1"></i> From
                            </span>
                            <input type="date" id="startDate" class="form-control" title="From Transaction Date">
                        </div>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white text-muted">
                                <i class="fas fa-calendar-alt me-1"></i> To
                            </span>
                            <input type="date" id="endDate" class="form-control" title="To Transaction Date">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center justify-content-end">
                        <select class="form-select select2 shadow-sm" id="dealerSelect" data-placeholder="Select Dealer..."
                            style="width: 100%;">
                            <option value=""></option>
                            @foreach ($dealerCompanies ?? [] as $dealerCompany)
                                <option value="{{ Crypt::encryptString($dealerCompany->id) }}">
                                    {{ $dealerCompany->dealer->dealer_name ?? $dealerCompany->company_name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0" id="reportTableContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@if (!isset($errorMessage))
    @push('scripts')
        <script type="module"
            src="{{ asset('js/reports/dealer_sales.js') }}?v={{ config('app.asset_version') }}"></script>
    @endpush
@endif
