@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-primary">
            <i class="fas fa-file-excel me-2"></i> Upload Accounts Data
        </h4>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="uploadTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab" aria-controls="invoices" aria-selected="true">
                <i class="fas fa-file-invoice me-1"></i> Invoices & Products
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="vouchers-tab" data-bs-toggle="tab" data-bs-target="#vouchers" type="button" role="tab" aria-controls="vouchers" aria-selected="false">
                <i class="fas fa-receipt me-1"></i> Payment Vouchers
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="uploadTabsContent">
        <!-- INVOICES TAB -->
        <div class="tab-pane fade show active" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
            @include('accounts.upload.partials.invoices_tab')
        </div>

        <!-- VOUCHERS TAB -->
        <div class="tab-pane fade" id="vouchers" role="tabpanel" aria-labelledby="vouchers-tab">
            @include('accounts.upload.partials.vouchers_tab')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/accounts/upload/upload.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
