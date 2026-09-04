@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-primary">
            <i class="fas fa-file-invoice-dollar me-2"></i> Dealer Statement
        </h4>
    </div>

    @if (isset($errorMessage))
        <x-error-alert :message="$errorMessage" />
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <h5 class="mb-0 fw-bold text-secondary">Statement Filters</h5>
        </div>
        <div class="card-body">
            <form id="dealerStatementForm" method="POST" action="{{ route('accounts.payment_tracks.ledger') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="dealer_id" class="form-label fw-bold">Select Dealer <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="dealer_id" name="dealer_id" data-placeholder="Choose Dealer..." required>
                            <option value=""></option>
                            @foreach ($dealers ?? [] as $dealer)
                                <option value="{{ Crypt::encryptString($dealer->id) }}">{{ $dealer->dealer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="financial_year" class="form-label fw-bold">Financial Year</label>
                        <select class="form-select" id="financial_year" name="financial_year">
                            @php
                                $currentYear = date('Y');
                                $currentMonth = date('n');
                                $baseYear = $currentMonth >= 4 ? $currentYear : $currentYear - 1;
                            @endphp
                            <option value="{{ $baseYear }}-{{ $baseYear + 1 }}">Current FY ({{ $baseYear }}-{{ $baseYear + 1 }})</option>
                            <option value="{{ $baseYear - 1 }}-{{ $baseYear }}">Previous FY ({{ $baseYear - 1 }}-{{ $baseYear }})</option>
                            <option value="{{ $baseYear - 2 }}-{{ $baseYear - 1 }}">Old FY ({{ $baseYear - 2 }}-{{ $baseYear - 1 }})</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold">Date Range (If Custom)</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="start_date" name="start_date" disabled required>
                            <span class="input-group-text bg-light text-muted">To</span>
                            <input type="date" class="form-control" id="end_date" name="end_date" disabled required>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary fw-bold px-4" id="btnGenerate">
                        <i class="fas fa-file-invoice"></i> Generate Statement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/accounts/dealer_statement/dealer_statement.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
