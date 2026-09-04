@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header bg-white text-center py-4 border-0">
                        <h3 class="fw-bold text-primary my-2">{{ __('Welcome Back!') }}</h3>
                        <p class="text-muted small mb-0">Please enter your details to login</p>
                    </div>

                    <div class="card-body p-4 p-md-5 pt-0">
                        <form id="loginForm" method="POST" action="{{ route('login.submit') }}">
                            @csrf

                            <div class="form-floating mb-3">
                                <input id="phone_number" type="text" class="form-control" name="phone_number"
                                    value="{{ old('phone_number') }}" required autocomplete="tel" autofocus
                                    placeholder="1234567890">
                                <label for="phone_number" class="text-muted"><i
                                        class="fas fa-phone me-2"></i>{{ __('Phone Number') }}</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input id="password" type="password" class="form-control" name="password" required
                                    autocomplete="current-password" placeholder="Password">
                                <label for="password" class="text-muted"><i
                                        class="fas fa-lock me-2"></i>{{ __('Password') }}</label>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                        {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label text-muted" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>

                                @if (Route::has('password.request'))
                                    <a class="small text-decoration-none fw-bold" href="{{ route('password.request') }}">
                                        {{ __('Forgot Password?') }}
                                    </a>
                                @endif
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg py-2 fw-bold shadow-sm">
                                    {{ __('Login') }} <i class="fas fa-sign-in-alt ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    @if (Route::has('register'))
                        <div class="card-footer text-center py-3 bg-light border-0 rounded-bottom-lg">
                            <div class="small">
                                <span class="text-muted">Need an account?</span> <a href="{{ route('register') }}"
                                    class="text-decoration-none fw-bold">Sign up!</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/auth/auth.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
