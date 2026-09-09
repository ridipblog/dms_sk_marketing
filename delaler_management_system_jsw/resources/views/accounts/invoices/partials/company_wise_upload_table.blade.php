<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-muted small">
            <tr>
                <th class="ps-4">File Name</th>
                <th>Uploaded By</th>
                <th>Upload Date</th>
                <th>Status</th>
                <th>Records Processed</th>
                <th>Errors</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tracks as $track)
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
                            <i class="fas fa-file-invoice fa-lg"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold text-dark">{{ $track->file_name }}</p>
                            <small class="text-muted">Type: Company-Wise Invoice Upload</small>
                        </div>
                    </div>
                </td>
                <td>{{ $track->user->name ?? 'System' }}</td>
                <td>{{ $track->created_at->format('M d, Y h:i A') }}</td>
                <td>
                    @if($track->status == 'completed')
                        <span class="badge bg-success rounded-pill px-3 py-2 shadow-sm">Completed</span>
                    @elseif($track->status == 'failed')
                        <span class="badge bg-danger rounded-pill px-3 py-2 shadow-sm">Failed</span>
                    @elseif($track->status == 'pending')
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 shadow-sm">Pending</span>
                    @else
                        <span class="badge bg-info text-dark rounded-pill px-3 py-2 shadow-sm"><i class="fas fa-spinner fa-spin me-1"></i> Processing</span>
                    @endif
                </td>
                <td>{{ (int)$track->imported_rows }} / {{ (int)$track->total_rows }}</td>
                <td>
                    @if($track->failed_rows > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-danger shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#cwErrorLog{{ $track->id }}" aria-expanded="false" aria-controls="cwErrorLog{{ $track->id }}">
                                <i class="fas fa-exclamation-circle me-1"></i> View Log
                            </button>
                            @if($track->error_file_path)
                                <a href="{{ route('accounts.upload.errors.download', $track->id) }}" class="btn btn-sm btn-danger shadow-sm">
                                    <i class="fas fa-download me-1"></i> Download CSV
                                </a>
                            @endif
                        </div>
                    @else
                        <span class="text-muted small">-</span>
                    @endif
                </td>
            </tr>
            @if($track->failed_rows > 0 && $track->error_log)
            <tr class="collapse" id="cwErrorLog{{ $track->id }}">
                <td colspan="6" class="p-0 border-0">
                    <div class="p-3 bg-light text-danger small">
                        <strong>Error Log:</strong><br>
                        {!! nl2br(e($track->error_log)) !!}
                    </div>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle fa-2x mb-3 text-secondary"></i>
                    <p class="mb-0">No company-wise invoice upload history found.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($tracks->hasPages())
<div class="card-footer bg-white border-top py-3">
    <div class="d-flex justify-content-between align-items-center text-muted small">
        <span>Showing {{ $tracks->firstItem() ?? 0 }} to {{ $tracks->lastItem() ?? 0 }} of {{ $tracks->total() }} entries</span>
        {{ $tracks->links('pagination::bootstrap-4') }}
    </div>
</div>
@endif
