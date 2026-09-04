@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-primary">
            <i class="fas fa-chart-line me-2"></i> Invoice Ageing Report
        </h4>
        <div class="d-flex gap-2">
            <button id="btnRefresh" class="btn btn-outline-secondary shadow-sm fw-bold">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
            <button id="btnExportCSV" class="btn btn-success shadow-sm fw-bold">
                <i class="fas fa-file-excel me-1"></i> Export to Excel/CSV
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Outstanding -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-gradient-primary text-white h-100">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-white-50 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Outstanding</span>
                        <h4 class="fw-bold my-1" id="total-outstanding-val">₹ 0.00</h4>
                        <small class="text-white-50"><i class="fas fa-coins me-1"></i>All Unpaid Invoices</small>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-3 p-3 text-white">
                        <i class="fas fa-file-invoice-dollar fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 0-30 Days -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-gradient-success text-white h-100">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-white-50 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">0-30 Days</span>
                        <h4 class="fw-bold my-1" id="range-30-val">₹ 0.00</h4>
                        <small class="text-white-50"><i class="fas fa-calendar-day me-1"></i>Fresh Outstanding</small>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-3 p-3 text-white">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 31-60 Days -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-gradient-info text-white h-100">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-white-50 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">31-60 Days</span>
                        <h4 class="fw-bold my-1" id="range-60-val">₹ 0.00</h4>
                        <small class="text-white-50"><i class="fas fa-clock me-1"></i>Overdue < 1 Month</small>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-3 p-3 text-white">
                        <i class="fas fa-exclamation-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 61-90 Days -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-gradient-warning text-white h-100">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-white-50 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">61-90 Days</span>
                        <h4 class="fw-bold my-1" id="range-90-val">₹ 0.00</h4>
                        <small class="text-white-50"><i class="fas fa-hourglass-half me-1"></i>Overdue < 2 Months</small>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-3 p-3 text-white">
                        <i class="fas fa-history fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 90+ Days -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-gradient-purple text-white h-100">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-white-50 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">90+ Days</span>
                        <h4 class="fw-bold my-1" id="range-90-plus-val">₹ 0.00</h4>
                        <small class="text-white-50"><i class="fas fa-ban me-1"></i>High Risk Overdue</small>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-3 p-3 text-white">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Dealer Filter -->
                <div class="col-md-4">
                    <label for="filterDealer" class="form-label fw-bold text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Select Dealer</label>
                    <select id="filterDealer" class="form-select select2" data-placeholder="All Dealers">
                        <option value="all" selected>All Dealers</option>
                        @foreach($dealers ?? [] as $dealer)
                            <option value="{{ $dealer->id }}">{{ $dealer->dealer_code }} - {{ $dealer->dealer_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Ageing Bucket Filter -->
                <div class="col-md-3">
                    <label for="filterBucket" class="form-label fw-bold text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Ageing Range</label>
                    <select id="filterBucket" class="form-select">
                        <option value="">All Ranges (0-90+ Days)</option>
                        <option value="0-30">0-30 Days (Fresh)</option>
                        <option value="31-60">31-60 Days</option>
                        <option value="61-90">61-90 Days</option>
                        <option value="90+">90+ Days (High Risk)</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-md-5">
                    <label for="searchQuery" class="form-label fw-bold text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Search Invoices</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchQuery" class="form-control border-start-0" placeholder="Search by Invoice No, Dealer Name or Code...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table Section -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0" id="ageingTableContainer">
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                <h5 class="fw-bold">Generating Ageing Report...</h5>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/accounts/ageing_report/ageing_report.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
