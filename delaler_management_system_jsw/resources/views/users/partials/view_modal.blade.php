<div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user me-2"></i>User Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th class="bg-light w-35">Name</th>
                            <td>{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Email</th>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Phone</th>
                            <td>{{ $user->phone }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Designation</th>
                            <td>{{ $user->designation ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Status</th>
                            <td>
                                @if($user->status === 'active')
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">Active</span>
                                @elseif($user->status === 'inactive')
                                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 rounded-pill">Inactive</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 rounded-pill">Blocked</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Created At</th>
                            <td>{{ $user->created_at ? $user->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Last Updated</th>
                            <td>{{ $user->updated_at ? $user->updated_at->format('d M Y, h:i A') : 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
