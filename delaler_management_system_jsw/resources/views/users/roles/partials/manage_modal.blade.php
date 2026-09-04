<div class="modal fade" id="manageRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user-shield me-2"></i> Manage Roles & Context
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-white p-3 rounded shadow-sm w-100 border-start border-primary border-4">
                        <h6 class="mb-1 text-muted small fw-bold">User Details</h6>
                        <h5 class="mb-0 fw-bold text-dark">{{ $user->name ?? 'Unknown User' }}</h5>
                        <p class="mb-0 text-muted small">{{ $user->email ?? '' }} | {{ $user->phone ?? '' }}</p>
                    </div>
                </div>

                 <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list me-2 text-primary"></i>Assigned Mappings</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Company</th>
                                        <th>Role</th>
                                        <th>Reporting To</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($mappings ?? [] as $mapping)
                                        <tr>
                                            <td class="ps-4 fw-medium">{{ $mapping->company->company_name ?? 'Unknown Company' }}</td>
                                            <td><span class="badge bg-info text-dark rounded-pill">{{ $mapping->role->role_name ?? 'Unknown Role' }}</span></td>
                                            <td>
                                                @if($mapping->parent)
                                                    <span class="text-dark fw-semibold" style="font-size: 0.8rem;">
                                                        <i class="fas fa-user-tie text-muted me-1"></i> {{ $mapping->parent->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted text-xs">None</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                @if($mapping->is_editable)
                                                    <button class="btn btn-sm btn-outline-primary btn-edit-mapping me-1"
                                                        data-id="{{ Crypt::encryptString($mapping->id ?? null) }}" 
                                                        title="Edit Mapping">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-secondary me-1" disabled
                                                        title="Cannot edit: mapping has associated invoices">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endif
                                                <button class="btn btn-sm btn-outline-danger btn-remove-mapping"
                                                    data-id="{{ Crypt::encryptString($mapping->id ?? null) }}" title="Remove Mapping">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle mb-2 fs-4 text-light"></i><br>
                                                No roles or companies assigned yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold text-dark mb-0" id="formCardHeader"><i class="fas fa-plus-circle me-2 text-success"></i>Assign New Role</h6>
                    </div>
                    <div class="card-body">
                        <form id="assignRoleForm">
                            @csrf
                            <input type="hidden" id="user_id" value="{{ Crypt::encryptString($user->id ?? null) }}">
                            <input type="hidden" id="editing_mapping_id" value="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">Select Company</label>
                                    <select class="form-select" id="company_id" name="company_id" required>
                                        <option value="" selected disabled>-- Choose Company --</option>
                                        @foreach($companies ?? [] as $company)
                                            <option value="{{ $company->id ?? null }}">{{ $company->company_name ?? 'N/A' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">Select Role</label>
                                    <select class="form-select" id="role_id" name="role_id" required>
                                        <option value="" selected disabled>-- Choose Role --</option>
                                        @foreach($roles ?? [] as $role)
                                            <option value="{{ $role->id ?? null }}">{{ $role->role_name ?? 'N/A' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12 d-none" id="reporting_container">
                                    <label class="form-label fw-bold text-muted small">Reporting To (Hierarchy Manager)</label>
                                    <select class="form-select select2-parent" id="parent_id" name="parent_id">
                                        <option value="" selected disabled>-- Choose parent user --</option>
                                    </select>
                                </div>
                                <div class="col-12 text-end mt-4">
                                    <button type="button" class="btn btn-secondary fw-bold shadow-sm me-2 d-none" id="btnCancelEdit">
                                        Cancel Edit
                                    </button>
                                    <button type="submit" class="btn btn-primary fw-bold shadow-sm" id="btnAssignRole">
                                        <i class="fas fa-check me-1"></i> Assign Context
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 bg-white">
                <button type="button" class="btn btn-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
