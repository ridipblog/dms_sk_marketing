<div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-striped table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
        <thead class="table-light">
            <tr>
                <th class="ps-4">S.No</th>
                <th>Category Name</th>
                <th>Category Code</th>
                <th>Description</th>
                <th>Status</th>
                <th class="text-center pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories ?? [] as $index => $category)
                <tr>
                    <td class="ps-4 text-muted fw-bold">{{ $categories->firstItem() + $index }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $category->category_name ?? 'N/A' }}</div>
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ $category->category_code ?? 'N/A' }}</span></td>
                    <td><span class="text-muted text-truncate d-inline-block"
                            style="max-width: 150px;">{{ $category->description ?? 'N/A' }}</span></td>
                    <td>
                        @if (($category->status ?? 'N/A') === 'active')
                            <span
                                class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Active</span>
                        @elseif (($category->status ?? 'N/A') === 'inactive')
                            <span
                                class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">Inactive</span>
                        @else
                            <span
                                class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">Blocked</span>
                        @endif
                    </td>
                    <td class="text-center pe-4">
                        <button class="btn btn-sm btn-light text-primary btn-edit-category shadow-sm border"
                            data-id="{{ Crypt::encryptString($category->id) }}" title="Edit Category">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-box-open fs-1 mb-3 opacity-50"></i>
                            <h5 class="fw-bold">No Categories Found</h5>
                            <p class="mb-0">There are no categories matching your search criteria.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($categories->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-4 bg-light border-top gap-3">
        <div class="text-muted small">
            Showing <span class="fw-bold">{{ $categories->firstItem() }}</span> to <span
                class="fw-bold">{{ $categories->lastItem() }}</span> of <span
                class="fw-bold">{{ $categories->total() }}</span> entries
        </div>
        <div class="pagination-wrapper">
            {{ $categories->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
