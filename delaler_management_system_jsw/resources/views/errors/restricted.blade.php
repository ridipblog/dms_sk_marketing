@extends('layouts.app')

@push('styles')
    <style>
        .restricted-container {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .restricted-card {
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .restricted-icon-wrapper {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(220, 53, 69, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        .restricted-icon {
            font-size: 3rem;
            color: #dc3545;
        }
        .restricted-title {
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.75rem;
        }
        .restricted-text {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-back-home {
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .btn-back-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="restricted-container">
        <div class="restricted-card">
            <div class="restricted-icon-wrapper">
                <i class="fas fa-lock restricted-icon"></i>
            </div>
            <h1 class="restricted-title">Access Restricted</h1>
            <p class="restricted-text">
                Oops! It looks like you don't have the necessary permissions to view this page. If you believe this is an error, please contact your system administrator.
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-back-home">
                <i class="fas fa-arrow-left me-2"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
