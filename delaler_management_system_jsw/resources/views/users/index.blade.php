@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-users me-2"></i> User Management
            </h4>
            <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-plus me-1"></i> Add New User
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-md-8 mb-3 mb-md-0">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchUser" class="form-control border-start-0"
                        placeholder="Search users by name, phone, email, or designation...">
                </div>
            </div>
            <div class="col-md-4">
                <select id="statusFilter" class="form-select shadow-sm">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0" id="userTableContainer">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                    <h5 class="fw-bold">Loading Users...</h5>
                </div>
            </div>
        </div>
    </div>
    
    @include('users.partials.create_modal')
    @include('users.partials.edit_modal')
    
    <!-- Container for dynamic view modal -->
    <div id="viewModalContainer"></div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/users/user.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
