@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-boxes me-2"></i> Daily Stock Report
            </h4>
            <a href="javascript:void(0)" id="btnExportReport" class="btn btn-outline-success shadow-sm fw-bold">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>

        <!-- Summary Metric Cards -->
        <div class="row mb-4 g-3">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Opening Stock</span>
                            <h4 class="fw-bold text-info my-1" id="cardOpeningStock">0.000 MT</h4>
                            <small class="text-muted"><i class="fas fa-boxes me-1"></i>Period Initial Stock</small>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                            <i class="fas fa-box-open fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Sale (MT)</span>
                            <h4 class="fw-bold text-danger my-1" id="cardTotalSale">0.000 MT</h4>
                            <small class="text-muted"><i class="fas fa-arrow-down me-1"></i>Period Sales Dispatch</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                            <i class="fas fa-truck-loading fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Purchase (MT)</span>
                            <h4 class="fw-bold text-success my-1" id="cardTotalPurchase">0.000 MT</h4>
                            <small class="text-muted"><i class="fas fa-arrow-up me-1"></i>Period Purchase Receipts</small>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                            <i class="fas fa-dolly fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Closing Stock</span>
                            <h4 class="fw-bold text-primary my-1" id="cardClosingStock">0.000 MT</h4>
                            <small class="text-muted"><i class="fas fa-warehouse me-1"></i>Current Stock Position</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                            <i class="fas fa-cubes fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-tabs-bordered mb-4" id="stockReportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-primary px-4 py-2" id="tabProductWise" data-type="product_wise" type="button" role="tab">
                    <i class="fas fa-boxes me-2"></i> Product-Wise Summary
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-secondary px-4 py-2" id="tabDateWise" data-type="date_wise" type="button" role="tab">
                    <i class="fas fa-calendar-alt me-2"></i> Product Date-Wise Log
                </button>
            </li>
        </ul>

        <!-- Filters Section -->
        <div class="row g-2 mb-4">
            <div class="col-md-3">
                <select class="form-select select2 shadow-sm" id="productSelect" data-placeholder="Filter by Product..." style="width: 100%;">
                    <option value="">All Products</option>
                    @foreach ($products ?? [] as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->product_name }} ({{ $product->sku_code ?? 'SKU' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary shadow-sm" id="btnRefreshReport" title="Refresh Report">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> From Date
                        </span>
                        <input type="date" id="startDate" class="form-control" title="From Date" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> To Date
                        </span>
                        <input type="date" id="endDate" class="form-control" title="To Date" value="{{ date('Y-m-t') }}">
                    </div>
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
    </div>
@endsection

@push('scripts')
    <script type="module"
        src="{{ asset('js/reports/daily_stock.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
