@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-money-check-alt me-2"></i> Payment Receipts
            </h4>
            {{-- <button class="btn btn-success shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addPaymentTrackModal">
                <i class="fas fa-plus me-1"></i> Add Payment Receipt
            </button> --}}
        </div>

        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @endif

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary me-2 shadow-sm" id="btnRefreshPayments" title="Refresh List">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchPayment" class="form-control border-start-0"
                            placeholder="Search by Invoice No or Dealer Name...">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-end">
                    <select class="form-select select2 shadow-sm" id="dealerSelect" data-placeholder="Select Dealer..."
                        style="width: 250px;">
                        <option value=""></option>
                        @foreach ($dealerCompanies ?? [] as $dealerCompany)
                            @php
                                $isSelected = isset($selectedDealerCompanyId) && $selectedDealerCompanyId == ($dealerCompany->id ?? '');
                            @endphp
                            <option value="{{ Crypt::encryptString($dealerCompany->id) }}" {{ $isSelected ? 'selected' : '' }}>
                                {{ $dealerCompany->dealer->dealer_name ?? 'N/A' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0" id="paymentTableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('accounts.payment_tracks.partials.add_modal')
    @include('accounts.payment_tracks.partials.edit_modal')
    @include('accounts.payment_tracks.partials.view_modal')
@endsection

@push('scripts')
    <script type="module"
        src="{{ asset('js/accounts/payment_tracks/payment_tracks.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
