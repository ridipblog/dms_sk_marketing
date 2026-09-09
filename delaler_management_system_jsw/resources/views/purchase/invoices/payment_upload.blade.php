@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-success">
                <i class="fas fa-money-bill-wave me-2"></i> Upload Purchase Payments
            </h4>
            <p class="text-muted small mb-0">Upload payment entries against supplier purchase invoices.</p>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body p-4 text-center">
            <i class="fas fa-tools fa-3x text-muted mb-3"></i>
            <h5 class="fw-bold text-dark">Upload Purchase Payments Submodule</h5>
            <p class="text-muted max-w-600 mx-auto">This submodule for bulk uploading supplier purchase payments is ready in sidebar structure. Purchase Invoice upload is active.</p>
        </div>
    </div>
</div>
@endsection
