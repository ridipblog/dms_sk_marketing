@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-upload me-2"></i> User Uploads
            </h4>
            <div>
                <a href="{{ route('users.uploads.template') }}" class="btn btn-outline-primary shadow-sm fw-bold me-2">
                    <i class="fas fa-download me-1"></i> Download Template
                </a>
                <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#uploadUserModal">
                    <i class="fas fa-upload me-1"></i> Upload Excel
                </button>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchUploads" class="form-control border-start-0" placeholder="Search by file name...">
                </div>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-light border shadow-sm" id="btnRefreshList" title="Refresh List">
                    <i class="fas fa-sync-alt text-primary"></i>
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0" id="uploadTableContainer">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                    <h5 class="fw-bold">Loading Upload History...</h5>
                </div>
            </div>
        </div>
    </div>

    @include('users.uploads.partials.upload_modal')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/users/uploads/upload.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
