@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-tags me-2"></i> Slab Management
            </h4>
            <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addSlabModal">
                <i class="fas fa-plus me-1"></i> Add New Slab
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchSlab" class="form-control border-start-0" placeholder="Search slabs...">
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0" id="slabTableContainer">
                <div class="table-responsive w-100" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: max-content;">
                        <thead class="table-light">
                            <tr>
                                <th>Slab Name</th>
                                <th>Minimum Days</th>
                                <th>Maximum Days</th>
                                <th>Discount (%)</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dummy Data Row -->
                            <tr>
                                <td><span class="fw-bold text-dark">0-5 Days (Quick Pay)</span></td>
                                <td>0</td>
                                <td>5</td>
                                <td><span class="fw-bold text-success">2.00%</span></td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" role="switch" checked title="Enabled">
                                    </div>
                                    <span class="badge bg-success ms-1">Enabled</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editSlabModal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="fw-bold text-dark">6-10 Days (Standard)</span></td>
                                <td>6</td>
                                <td>10</td>
                                <td><span class="fw-bold text-success">1.00%</span></td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" role="switch" title="Disabled">
                                    </div>
                                    <span class="badge bg-secondary ms-1">Disabled</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editSlabModal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('accounts.cash_discount_slabs.partials.add_modal')
    @include('accounts.cash_discount_slabs.partials.edit_modal')
@endsection
