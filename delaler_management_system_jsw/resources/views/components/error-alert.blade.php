@props(['message' => 'An unexpected error occurred.'])

<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4 mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-circle fs-4 me-3 text-danger"></i>
        <div>
            <h6 class="alert-heading mb-1 fw-bold">Oops! Something went wrong.</h6>
            <p class="mb-0">{{ $message }}</p>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
