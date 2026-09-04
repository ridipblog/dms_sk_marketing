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

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12) !important;
        }

        .kpi-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            opacity: 0.85;
            letter-spacing: 0.5px;
        }

        .kpi-value {
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: -0.5px;
        }

        .metric-icon-box {
            font-size: 1.8rem;
            opacity: 0.25;
        }

        .chart-container {
            position: relative;
            width: 100%;
        }

        .table th {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #6c757d;
        }

        .table td {
            font-size: 0.8rem;
            padding: 0.6rem 0.75rem;
        }

        .badge {
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .hover-action:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%) !important;
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid compact-dashboard px-3 py-2">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 bg-white p-3 rounded-3 shadow-sm">
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white p-2 rounded-3 me-3 d-flex align-items-center justify-content-center"
                    style="width: 40px; height: 40px;">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark" id="dashboard-main-title">
                        @if ($roleType == 'Admin')
                            Executive Admin Dashboard
                        @elseif($roleType == 'BM')
                            Branch Performance Dashboard
                        @elseif($roleType == 'ASM')
                            Area Sales Dashboard
                        @elseif($roleType == 'ASO')
                            ASO Field Territory Dashboard
                        @else
                            Dealer Command Dashboard
                        @endif
                    </h5>
                    <span class="text-muted text-xs">
                        Welcome, <strong>{{ Auth::user()->name ?? 'Demo User' }}</strong> (Active Company:
                        {{ session('active_company_name') ?? 'JSW Steel' }})
                    </span>
                </div>
            </div>
        </div>

        <!-- Active Sub-Dashboard View Inclusion -->
        <div id="active-dashboard-wrapper">
            @if ($roleType === 'Admin')
                @include('dashboard.admin')
            @elseif($roleType === 'BM')
                @include('dashboard.bm')
            @elseif($roleType === 'ASM')
                @include('dashboard.asm')
            @elseif($roleType === 'ASO')
                @include('dashboard.aso')
            @elseif($roleType === 'Dealer')
                @include('dashboard.dealer')
            @else
                @include('dashboard.admin')
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Inject data to JavaScript dynamically -->
    <script>
        window.dashboardRoleType = "{{ $roleType }}";
        window.dashboardMetrics = @json($metrics);

        // Dynamic performance variables based on active template
        @if ($roleType == 'BM')
            window.asmPerformance = @json($asm_performance);
        @elseif ($roleType == 'ASM')
            window.asoScorecard = @json($aso_scorecard);
        @elseif ($roleType == 'ASO')
            window.dealersList = @json($dealers);
        @endif
    </script>
    <script type="module" src="{{ asset('js/dashboard/dashboard.js') }}?v={{ time() }}"></script>
@endpush
