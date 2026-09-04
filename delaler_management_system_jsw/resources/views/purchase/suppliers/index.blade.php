@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-truck me-2"></i> Supplier Management
            </h4>
            <button class="btn btn-success shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="fas fa-plus me-1"></i> Register Supplier
            </button>
        </div>

        <!-- Filters & Search -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary me-2 shadow-sm" id="btnRefreshSuppliers" title="Refresh List">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchSupplier" class="form-control border-start-0"
                            placeholder="Search by Name, GSTIN, Phone or Email...">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-end">
                    <select class="form-select shadow-sm" id="statusFilterSelect" style="width: 180px;">
                        <option value="">All Statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0" id="supplierTableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('purchase.suppliers.partials.create_modal')
    @include('purchase.suppliers.partials.edit_modal')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/purchase/suppliers/supplier.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
