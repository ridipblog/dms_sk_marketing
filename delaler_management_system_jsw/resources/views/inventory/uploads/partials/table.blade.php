<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th class="ps-4">S.No</th>
                <th>File Name</th>
                <th>Upload Type</th>
                <th>Uploaded By</th>
                <th>Status</th>
                <th>Rows (Imp / Fail / Tot)</th>
                <th>Date & Time</th>
                <th class="text-center pe-4">Error Log</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tracks ?? [] as $index => $track)
                <tr>
                    <td class="ps-4 text-muted fw-bold">{{ $tracks->firstItem() + $index }}</td>
                    <td>
                        <div class="fw-bold text-dark"><i
                                class="fas fa-file-excel text-success me-2"></i>{{ $track->file_name ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <span
                            class="badge bg-info bg-opacity-10 text-info px-3 py-1 rounded border border-info border-opacity-25 text-uppercase"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            {{ $track->upload_type ?? 'inventory' }}
                        </span>
                    </td>
                    <td>
                        <span class="text-muted"><i
                                class="fas fa-user-circle me-1"></i>{{ $track->user->name ?? 'Unknown' }}</span>
                    </td>
                    <td>
                        @if ($track->status === 'completed')
                            <span
                                class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill shadow-sm"><i
                                    class="fas fa-check-circle me-1"></i>Completed</span>
                        @elseif ($track->status === 'processing')
                            <span
                                class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill shadow-sm"><i
                                    class="fas fa-spinner fa-spin me-1"></i>Processing</span>
                        @elseif ($track->status === 'failed')
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill shadow-sm"><i
                                    class="fas fa-times-circle me-1"></i>Failed</span>
                        @else
                            <span
                                class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill shadow-sm"><i
                                    class="fas fa-clock me-1"></i>Pending</span>
                        @endif
                    </td>
                    <td>
                        <div class="small fw-bold">
                            <span class="text-success">{{ $track->imported_rows ?? 0 }}</span> /
                            <span class="text-danger">{{ $track->failed_rows ?? 0 }}</span> /
                            <span class="text-muted">{{ $track->total_rows ?? 0 }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="small text-muted">{{ $track->created_at->format('d M, Y h:i A') }}</div>
                    </td>
                    <td class="text-center pe-4">
                        @if ($track->error_log)
                            <button class="btn btn-sm btn-light text-danger shadow-sm border"
                                onclick="Swal.fire({title: 'Error Log', html: '<pre style=\'text-align:left; font-size:12px; white-space: pre-wrap;\'>{{ e($track->error_log) }}</pre>', width: '600px'})"
                                title="View Errors">
                                <i class="fas fa-exclamation-triangle"></i>
                            </button>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-history fs-1 mb-3 opacity-50"></i>
                            <h5 class="fw-bold">No Upload History</h5>
                            <p class="mb-0">You haven't uploaded any inventory files yet.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (isset($tracks) && $tracks->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-4 bg-light border-top gap-3">
        <div class="text-muted small">
            Showing <span class="fw-bold">{{ $tracks->firstItem() }}</span> to <span
                class="fw-bold">{{ $tracks->lastItem() }}</span> of <span
                class="fw-bold">{{ $tracks->total() }}</span> entries
        </div>
        <div class="pagination-wrapper">
            {{ $tracks->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
