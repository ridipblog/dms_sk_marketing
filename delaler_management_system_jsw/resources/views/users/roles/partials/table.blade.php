<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th class="border-0 rounded-start ps-4">Name</th>
                <th class="border-0">Phone</th>
                <th class="border-0">Email</th>
                <th class="border-0 text-center">Status</th>
                <th class="border-0 rounded-end text-end pe-4">Actions</th>
            </tr>
        </thead>
        <tbody class="border-top-0">
            @forelse($users ?? [] as $user)
                <tr>
                    <td class="ps-4 fw-medium text-dark">{{ $user->name ?? 'N/A' }}</td>
                    <td class="text-muted">{{ $user->phone ?? 'N/A' }}</td>
                    <td class="text-muted">{{ $user->email ?? 'N/A' }}</td>
                    <td class="text-center">
                        @if (($user->status ?? null) === 'active')
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Active</span>
                        @elseif(($user->status ?? null) === 'inactive')
                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Inactive</span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Blocked</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-primary shadow-sm btn-manage-roles"
                            data-id="{{ Crypt::encryptString($user->id ?? null) }}" title="Manage Roles">
                            <i class="fas fa-user-shield me-1"></i> Manage Roles
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-users-slash fs-1 mb-3 text-light"></i>
                            <h5 class="fw-bold mb-0">No users found</h5>
                            <p class="small mb-0">Adjust your search filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center p-3 border-top bg-light rounded-bottom">
    <div class="text-muted small">
        Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
    </div>
    <div class="pagination-wrapper">
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>
