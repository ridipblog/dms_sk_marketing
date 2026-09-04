@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-invoice me-2"></i> Generate Invoice
            </h4>
            <div class="d-flex gap-2">
                <button id="btnExportInvoicesExcel" class="btn btn-success shadow-sm fw-bold">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
                <a href="{{ route('accounts.invoices.generate') }}" class="btn btn-primary shadow-sm fw-bold">
                    <i class="fas fa-plus me-1"></i> Add Invoice
                </a>
            </div>
        </div>

        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @endif

        <div class="row mb-4">
            <div class="col-md-5">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary me-2 shadow-sm" id="btnRefreshInvoices" title="Refresh List">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInvoice" class="form-control border-start-0"
                            placeholder="Search by Invoice No or Dealer Name...">
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="d-flex align-items-center justify-content-end">
                    <select class="form-select select2 shadow-sm me-2" id="dealerSelect" data-placeholder="Select Dealer..."
                        style="width: 250px;">
                        <option value=""></option>
                        @foreach ($dealerCompanies ?? [] as $dealerCompany)
                            <option value="{{ Crypt::encryptString($dealerCompany->id) }}">{{ $dealerCompany->dealer->dealer_name ?? 'N/A' }}</option>
                        @endforeach
                    </select>

                    <select class="form-select shadow-sm" id="statusSelect" style="width: 180px;">
                        <option value="">All Statuses</option>
                        <option value="1">Finalized</option>
                        <option value="0">Not Finalized</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0" id="invoiceTableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('accounts.invoices.partials.view_modal')
@endsection

@push('scripts')
    <script type="importmap">
    {
        "imports": {
            "ReuseInvoiceModule": "{{ asset('js/accounts/invoices/reuseleinvoice.js') }}?v={{ config('app.asset_version') }}"
        }
    }
</script>
    <script>
        const finalizeInvoiceUrl = "{{ route('accounts.invoices.finalize') }}";
        const checkDeleteInvoiceUrl = "{{ route('accounts.invoices.check_delete') }}";
        const deleteInvoiceUrl = "{{ route('accounts.invoices.delete') }}";
    </script>
    <script type="module" src="{{ asset('js/accounts/invoices/invoice.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
