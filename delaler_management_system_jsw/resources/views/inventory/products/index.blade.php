@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-box me-2"></i> Product Management
            </h4>
            <div>
                <a href="javascript:void(0)" id="btnExportProducts" class="btn btn-outline-success shadow-sm fw-bold me-2">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </a>
                <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createProductModal">
                    <i class="fas fa-plus me-1"></i> Add New Product
                </button>
            </div>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-lg-4 col-md-12">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchProduct" class="form-control border-start-0"
                        placeholder="Search products by name, SKU, or HSN...">
                </div>
            </div>
            <div class="col-lg-2 col-md-3">
                <select id="categoryFilter" class="form-select shadow-sm">
                    <option value="">All Categories</option>
                    @foreach ($categories ?? [] as $category)
                        <option value="{{ $category->id ?? null }}">{{ $category->category_name ?? 'N/A' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <select id="statusFilter" class="form-select shadow-sm">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-light text-muted border-end-0">₹</span>
                    <input type="number" id="minPriceFilter" class="form-control" placeholder="Min Price" min="0">
                    <span class="input-group-text bg-light text-muted border-start-0 border-end-0">-</span>
                    <input type="number" id="maxPriceFilter" class="form-control" placeholder="Max Price" min="0">
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0" id="productTableContainer">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                    <h5 class="fw-bold">Loading Products...</h5>
                </div>
            </div>
        </div>
    </div>
    @include('inventory.products.partials.create_modal')
    @include('inventory.products.partials.edit_modal')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/inventory/products/product.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
