@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-users me-2"></i> Dealers Management
            </h4>
            <div class="d-flex gap-2">
                <button id="btnExportExcel" class="btn btn-success shadow-sm fw-bold">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
                <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createDealerModal">
                    <i class="fas fa-plus me-1"></i> Add New Dealer
                </button>
            </div>
        </div>

        <div class="row g-2 mb-4 align-items-center">
            <div class="col-lg-4 col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchDealer" class="form-control border-start-0" placeholder="Search dealers by name, code, email, or phone...">
                </div>
            </div>
            <div class="col-lg-2 col-md-3">
                <select id="statusFilter" class="form-select shadow-sm">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <div class="input-group shadow-sm" title="No Payment From Date">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="font-size: 0.8rem;">From</span>
                    <input type="date" id="fromDate" class="form-control border-start-0 ps-1" style="font-size: 0.85rem;" placeholder="From Date">
                </div>
            </div>
            <div class="col-lg-2 col-md-3">
                <div class="input-group shadow-sm" title="No Payment To Date">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="font-size: 0.8rem;">To</span>
                    <input type="date" id="toDate" class="form-control border-start-0 ps-1" style="font-size: 0.85rem;" placeholder="To Date">
                </div>
            </div>
            <div class="col-lg-2 col-md-3">
                <button id="btnClearFilters" class="btn btn-outline-secondary w-100 shadow-sm fw-semibold" title="Clear All Filters">
                    <i class="fas fa-redo me-1"></i> Reset
                </button>
            </div>
        </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0" id="dealerTableContainer">
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                <h5 class="fw-bold">Loading Dealers...</h5>
            </div>
        </div>
    </div>
    </div>
    @include('dealers.partials.create_modal')
    @include('dealers.partials.edit_modal')
    @include('dealers.partials.view_modal')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/dealers/dealer.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
