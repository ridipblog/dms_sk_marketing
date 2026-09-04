@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Error Alert Component -->
        @if (isset($errorMessage))
            <div class="mb-3">
                <x-error-alert :message="$errorMessage" />
            </div>
        @endif

        <div id="errorAlertContainer"></div>

        @if (isset($invoice) && $invoice)
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <a href="{{ route('accounts.payment_tracks.index') }}" class="text-secondary me-2"><i
                                class="fas fa-arrow-left"></i></a>
                        Manage Vouchers - Invoice #{{ $invoice->invoice_no }}
                    </h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVoucherModal">
                        <i class="fas fa-plus me-1"></i> Add Voucher
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Voucher History</h6>
                    <div class="d-flex align-items-center gap-2">
                        <button id="btnExportVouchersExcel" class="btn btn-sm btn-success fw-bold" title="Export to Excel">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </button>
                        <button id="btnExportVouchersPdf" class="btn btn-sm btn-danger fw-bold" title="Export to PDF">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="btnRefreshVouchers" title="Refresh List">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="fas fa-search text-muted"></i></span>
                            <input type="text" id="searchVoucher" class="form-control border-start-0"
                                placeholder="Search vouchers...">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="voucherTableContainer">
                        <!-- Table loaded via AJAX -->
                    </div>
                </div>
            </div>
    </div>

    @include('accounts.payment_tracks.partials.add_voucher_modal')
    @include('accounts.payment_tracks.partials.edit_voucher_modal')
@else
    <div class="text-center py-5">
        <h5 class="text-muted">No Invoice Data Available</h5>
        <a href="{{ route('accounts.payment_tracks.index') }}" class="btn btn-primary mt-3">Back to List</a>
    </div>
    @endif
@endsection

@push('scripts')
    @if (isset($invoice) && $invoice)
        <script type="module" src="{{ asset('js/accounts/payment_tracks/voucher.js') }}?v={{ config('app.asset_version') }}"></script>
    @endif
@endpush
