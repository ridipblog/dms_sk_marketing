@extends('layouts.app')

@push('styles')
    <style>
        .settings-card {
            border-radius: 1rem !important;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .settings-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important;
        }
        .settings-card .card-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border-bottom: none;
        }
        .settings-card .card-header h6 {
            color: #ffffff !important;
            font-size: 1.15rem;
            letter-spacing: 0.5px;
        }
        .input-group-text {
            background-color: transparent;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: none;
        }
        .form-control:focus + .input-group-text, 
        .input-group:focus-within .input-group-text {
            border-color: #4e73df;
            color: #4e73df !important;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #224abe 0%, #1a368c 100%);
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.2)!important;
            color: white;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-6 col-xxl-5">
            
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                    <i class="fas fa-cog text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.5px;">Account Settings</h4>
                    <span class="text-muted small">Manage your security preferences</span>
                </div>
            </div>

            <div class="card border-0 shadow-lg settings-card">
                <div class="card-header py-4 text-center">
                    <h6 class="m-0 fw-bold"><i class="fas fa-shield-alt me-2"></i>Change Password</h6>
                    <p class="text-white-50 small mb-0 mt-1">Ensure your account is using a long, random password to stay secure.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form id="passwordForm" data-url="{{ route('settings.password.update') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="current_password" class="form-label fw-bold text-secondary small text-uppercase letter-spacing-1">Current Password</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-key text-muted"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0 bg-white" id="current_password" name="current_password" required placeholder="Enter current password">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="new_password" class="form-label fw-bold text-secondary small text-uppercase letter-spacing-1">New Password</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0 bg-white" id="new_password" name="new_password" required placeholder="Minimum 8 characters">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="new_password_confirmation" class="form-label fw-bold text-secondary small text-uppercase letter-spacing-1">Confirm Password</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0 bg-white" id="new_password_confirmation" name="new_password_confirmation" required placeholder="Retype new password">
                            </div>
                        </div>

                        <div class="d-grid mt-2">
                            <button type="submit" id="submitBtn" class="btn btn-gradient py-3 fw-bold fs-6">
                                <i class="fas fa-save me-2"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/settings/password.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
