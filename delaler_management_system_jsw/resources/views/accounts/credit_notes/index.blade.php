@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-invoice-dollar me-2"></i> Credit Notes
            </h4>
        </div>

        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @else
            <div class="row g-2 mb-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary me-2 shadow-sm" id="btnRefreshCreditNotes" title="Refresh List">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchCreditNote" class="form-control border-start-0" placeholder="Search by Invoice No or Reason...">
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white text-muted"><i class="fas fa-calendar-alt me-1"></i> From</span>
                            <input type="date" id="startDate" class="form-control" title="From Date">
                        </div>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white text-muted"><i class="fas fa-calendar-alt me-1"></i> To</span>
                            <input type="date" id="endDate" class="form-control" title="To Date">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center justify-content-end">
                        <select class="form-select select2 shadow-sm" id="dealerSelect" data-placeholder="Select Dealer..." style="width: 100%;">
                            <option value=""></option>
                            @foreach ($dealerCompanies ?? [] as $dealerCompany)
                                <option value="{{ \Illuminate\Support\Facades\Crypt::encryptString($dealerCompany->id) }}">{{ $dealerCompany->dealer->dealer_name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0" id="creditNoteTableContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if (!isset($errorMessage))
        @include('accounts.credit_notes.partials.view_modal')
    @endif
@endsection

@if (!isset($errorMessage))
@push('scripts')
    <script type="module"
        src="{{ asset('js/accounts/credit_notes/credit_note.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
@endif
