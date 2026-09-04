@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ time() }}">
    <style>
        .compact-dashboard {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f6f9;
        }

        .filter-card {
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
        }

        .form-label-xs {
            font-size: 0.65rem;
            margin-bottom: 2px;
        }

        .form-select-sm,
        .form-control-sm {
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .metric-card {
            border-radius: 12px;
            color: #fff;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid compact-dashboard px-3 py-3">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 bg-white p-3 rounded-3 shadow-sm">
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white p-2 rounded-3 me-3 d-flex align-items-center justify-content-center"
                    style="width: 40px; height: 40px;">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark">
                        @if ($roleType === 'Admin')
                            Executive Admin Dashboard
                        @elseif($roleType === 'DM')
                            District / Branch Manager Dashboard
                        @elseif($roleType === 'ASM')
                            Area Sales Manager Dashboard
                        @elseif($roleType === 'ASO')
                            Assistant Section Officer Dashboard
                        @else
                            Dealer Dashboard
                        @endif
                    </h5>
                    <span class="text-muted text-xs">
                        Welcome, <strong>{{ Auth::user()->name ?? 'User' }}</strong> (Active Role:
                        {{ $activeRole ?? $roleType }})
                    </span>
                </div>
            </div>
        </div>

        <!-- Dynamic Role-Based Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 filter-card">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end">
                            <!-- Level 1: DM Filter (Shown ONLY for Admin) -->
                            @if ($roleType === 'Admin')
                                <div class="col-md col-sm-6">
                                    <label class="form-label-xs text-muted fw-bold">District / Branch Manager (DM)</label>
                                    <select class="form-select form-select-sm select2 admin-cascade-select"
                                        id="admin-filter-dm" data-role="DM" data-target="#admin-filter-asm">
                                        <option value="">All DMs / BMs</option>
                                        @if (isset($dms))
                                            @foreach ($dms as $dm)
                                                <option value="{{ $dm['id'] }}">{{ $dm['name'] }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            @endif

                            <!-- Level 2: ASM Filter (Shown for Admin and DM) -->
                            @if (in_array($roleType, ['Admin', 'DM']))
                                <div class="col-md col-sm-6">
                                    <label class="form-label-xs text-muted fw-bold">Area Sales Manager (ASM)</label>
                                    <select class="form-select form-select-sm select2 admin-cascade-select"
                                        id="admin-filter-asm" data-role="ASM" data-target="#admin-filter-aso">
                                        <option value="">All ASMs</option>
                                        @if (isset($asms))
                                            @foreach ($asms as $asm)
                                                <option value="{{ $asm['id'] }}">{{ $asm['name'] }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            @endif

                            <!-- Level 3: ASO Filter (Shown for Admin, DM and ASM) -->
                            @if (in_array($roleType, ['Admin', 'DM', 'ASM']))
                                <div class="col-md col-sm-6">
                                    <label class="form-label-xs text-muted fw-bold">Assistant Section Officer (ASO)</label>
                                    <select class="form-select form-select-sm select2 admin-cascade-select"
                                        id="admin-filter-aso" data-role="ASO" data-target="#admin-filter-dealer">
                                        <option value="">All ASOs</option>
                                        @if (isset($asos))
                                            @foreach ($asos as $aso)
                                                <option value="{{ $aso['id'] }}">{{ $aso['name'] }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            @endif

                            <!-- Level 4: Dealer Filter (Shown for Admin, DM, ASM and ASO) -->
                            @if (in_array($roleType, ['Admin', 'DM', 'ASM', 'ASO']))
                                <div class="col-md col-sm-6">
                                    <label class="form-label-xs text-muted fw-bold">Dealer</label>
                                    <select class="form-select form-select-sm select2" id="admin-filter-dealer"
                                        data-role="Dealer">
                                        <option value="">All Dealers</option>
                                        @if (isset($dealers))
                                            @foreach ($dealers as $dl)
                                                <option value="{{ $dl['id'] }}">{{ $dl['name'] }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            @endif

                            <!-- Date Range Filter (Always Shown) -->
                            <div class="col-md col-sm-12">
                                <label class="form-label-xs text-muted fw-bold">Date Range</label>
                                <input type="date" class="form-control form-control-sm" id="admin-filter-date"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Outstanding Amount Card -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card metric-card bg-gradient-warning border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="kpi-label text-white-50 text-uppercase fw-bold" style="letter-spacing: 1px;">Total
                                Outstanding Amount</span>
                            <h2 class="kpi-value mt-2 mb-0 text-white fw-extrabold" id="admin-kpi-outstanding"
                                style="font-size: 2.2rem;">
                                ₹ {{ number_format(($metrics['outstanding'] ?? 0) / 100000, 2) }} L
                            </h2>
                            <small class="text-white-50 mt-1 d-block"><i class="fas fa-filter me-1"></i>Filtered according
                                to selected role parameters</small>
                        </div>
                        <div class="metric-icon-box text-white" style="font-size: 3.5rem; opacity: 0.35;">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 5 Dealers Tables -->
        <div class="row g-3 mb-4">
            <!-- Top 5 Dealers with Outstanding Amount -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Top
                            5 Dealers with Outstanding Amount</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="top-outstanding-dealers-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Dealer Name</th>
                                        <th>Code</th>
                                        <th class="text-end">Outstanding Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topOutstandingDealers ?? [] as $index => $dl)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="fw-bold text-dark">{{ $dl->dealer_name }}</td>
                                            <td><span
                                                    class="badge bg-light text-dark">{{ $dl->dealer_code ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-danger">₹
                                                {{ number_format($dl->total_outstanding, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No outstanding data
                                                found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 5 Dealers with Paid Amount -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-check-circle text-success me-2"></i>Top 5
                            Dealers with Paid Amount</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="top-paid-dealers-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Dealer Name</th>
                                        <th>Code</th>
                                        <th class="text-end">Paid Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topPaidDealers ?? [] as $index => $dl)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="fw-bold text-dark">{{ $dl->dealer_name }}</td>
                                            <td><span
                                                    class="badge bg-light text-dark">{{ $dl->dealer_code ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-success">₹
                                                {{ number_format($dl->total_paid, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No paid data found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.dashboardRoleType = "{{ $roleType ?? 'Admin' }}";
    </script>
    <script type="module" src="{{ asset('js/dashboard/dashboard.js') }}?v={{ time() }}"></script>
@endpush
