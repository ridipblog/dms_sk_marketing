<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-striped table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th class="ps-4">S.No</th>
                <th>Supplier Name</th>
                <th>GSTIN</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th>Status</th>
                <th class="text-center pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suppliers ?? [] as $index => $supplier)
                <tr>
                    <td class="ps-4 text-muted fw-bold">{{ $suppliers->firstItem() + $index }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $supplier->name }}</div>
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ $supplier->gstin ?? 'N/A' }}</span></td>
                    <td><span class="text-secondary fw-semibold">{{ $supplier->phone ?? 'N/A' }}</span></td>
                    <td><span class="text-secondary">{{ $supplier->email ?? 'N/A' }}</span></td>
                    <td>
                        <span class="text-muted text-truncate d-inline-block" style="max-width: 200px;" title="{{ $supplier->address }}">
                            {{ $supplier->address ?? 'N/A' }}
                        </span>
                    </td>
                    <td>
                        @if ($supplier->status == 1)
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Active</span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">Inactive</span>
                        @endif
                    </td>
                    <td class="text-center pe-4">
                        <button class="btn btn-sm btn-light text-primary edit-supplier-btn shadow-sm border"
                            data-id="{{ Crypt::encryptString($supplier->id) }}" title="Edit Supplier">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-users-slash fs-1 mb-3 opacity-50"></i>
                            <h5 class="fw-bold">No Suppliers Found</h5>
                            <p class="mb-0">There are no suppliers matching your search criteria.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (isset($suppliers) && $suppliers->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-4 bg-light border-top gap-3">
        <div class="text-muted small">
            Showing <span class="fw-bold">{{ $suppliers->firstItem() }}</span> to <span
                class="fw-bold">{{ $suppliers->lastItem() }}</span> of <span
                class="fw-bold">{{ $suppliers->total() }}</span> entries
        </div>
        <div class="pagination-wrapper">
            {{ $suppliers->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
