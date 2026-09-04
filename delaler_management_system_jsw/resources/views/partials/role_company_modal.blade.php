@auth
    @php
        // Fetch the user's assigned companies and roles eagerly
$mappings = Auth::user()
    ->userRoleCompanies()
    ->with(['company', 'role'])
    ->get();

// Check if they need to be forced to select a context
$needsContext = !session()->has('active_map_id');
    @endphp

    @if ($mappings->count() > 1)
        <!-- Context Selection Modal -->
        <div class="modal fade" id="contextSelectionModal"
            {{ $needsContext ? 'data-bs-backdrop="static" data-bs-keyboard="false"' : '' }} tabindex="-1"
            aria-labelledby="contextSelectionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white border-0">
                        <h5 class="modal-title fw-bold" id="contextSelectionModalLabel">
                            <i class="fas fa-building me-2"></i> Select Role & Company
                        </h5>
                        @if (!$needsContext)
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        @endif
                    </div>

                    <form action="{{ route('set.active.context') }}" method="POST">
                        @csrf
                        <div class="modal-body p-4">
                            @if ($needsContext)
                                <div class="alert alert-warning mb-4 border-0 shadow-sm">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Please select your active company and
                                    role to continue.
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger mb-4 border-0 shadow-sm">
                                    <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                                </div>
                            @endif

                            @if ($errors->has('mapping_id'))
                                <div class="alert alert-danger mb-4 border-0 shadow-sm">
                                    <i class="fas fa-times-circle me-2"></i> {{ $errors->first('mapping_id') }}
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="mapping_id" class="form-label fw-bold text-muted">Select Context</label>
                                <select class="form-select form-select-lg" id="mapping_id" name="mapping_id" required>
                                    <option value="" disabled selected>-- Choose your context --</option>
                                    @foreach ($mappings ?? [] as $mapping)
                                        <option value="{{ $mapping->id ?? null }}"
                                            {{ session('active_map_id') == ($mapping->id ?? null) ? 'selected' : '' }}>
                                            {{ $mapping->company->company_name ?? 'Unknown Company' }}
                                            (Role: {{ $mapping->role->role_name ?? 'Unknown Role' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-0">
                            @if (!$needsContext)
                                <button type="button" class="btn btn-secondary fw-bold px-4"
                                    data-bs-dismiss="modal">Cancel</button>
                            @endif
                            <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">
                                Continue <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    @if ($needsContext)
                        var contextModal = new bootstrap.Modal(document.getElementById('contextSelectionModal'), {
                            backdrop: 'static',
                            keyboard: false
                        });
                        contextModal.show();
                    @endif
                });
            </script>
        @endpush
    @endif
@endauth
