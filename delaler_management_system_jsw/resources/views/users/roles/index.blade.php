@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fas fa-users-cog text-primary me-2"></i>Set Role and Company</h4>
                <p class="text-muted mb-0 small">Manage user access by assigning roles and companies.</p>
            </div>
        </div>

        @if (isset($errorMessage))
            <div class="alert alert-danger shadow-sm border-0 mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ $errorMessage }}
            </div>
        @endif

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i
                                    class="fas fa-search text-muted"></i></span>
                            <input type="text" id="searchUser" class="form-control border-start-0 ps-0 bg-light"
                                placeholder="Search users by name, phone, or email...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div id="userTableContainer">
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                        <h5 class="fw-bold">Loading Users...</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Container for dynamic modals -->
    <div id="modalContainer"></div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/users/user_role.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
