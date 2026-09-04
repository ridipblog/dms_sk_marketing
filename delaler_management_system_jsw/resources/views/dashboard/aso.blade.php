<!-- Assistant Section Officer (ASO) Dashboard Section -->
<div class="row g-3 mb-4">
    <!-- Filter Panel -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 filter-card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-filter me-2 text-primary"></i>ASO Territory Filters ({{ $metrics['territory'] }})</h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0 reset-filters-btn"><i class="fas fa-undo me-1"></i>Reset Filters</button>
                </div>
                <div class="row g-2">
                    <!-- Assigned Dealer Select -->
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Select Dealer</label>
                        <select class="form-select form-select-sm" id="aso-filter-dealer">
                            <option value="">All Assigned Dealers</option>
                            @foreach($dealers as $dl)
                                <option value="{{ $dl['id'] }}">{{ $dl['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Status Filter -->
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Outstanding Status</label>
                        <select class="form-select form-select-sm" id="aso-filter-status">
                            <option value="">All Statuses</option>
                            <option value="Active">Active / Safe</option>
                            <option value="Warning">Warning (No ledger signature)</option>
                            <option value="Overdue">Overdue / Blocked</option>
                        </select>
                    </div>
                    <!-- Month Select -->
                    <div class="col-md-4 col-sm-12">
                        <label class="form-label-xs text-muted fw-bold">Working Month</label>
                        <select class="form-select form-select-sm" id="aso-filter-month">
                            <option value="2026-06" selected>June 2026 (Current)</option>
                            <option value="2026-05">May 2026</option>
                            <option value="2026-04">April 2026</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ASO KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Assigned Dealers -->
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-gradient-primary h-100 border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                    <span class="kpi-label text-white-50">Assigned Dealers</span>
                    <h3 class="kpi-value mt-1 mb-0">{{ $metrics['dealers_count'] }}</h3>
                    <small class="text-white-50"><i class="fas fa-route me-1"></i>Visit Cycle: 14 Days</small>
                </div>
                <div class="metric-icon-box">
                    <i class="fas fa-store"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Balance -->
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-gradient-warning h-100 border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                    <span class="kpi-label text-white-50">Territory Outstanding</span>
                    <h3 class="kpi-value mt-1 mb-0 text-white">₹ {{ number_format($metrics['outstanding'] / 100000, 2) }} L</h3>
                    <small class="text-white-50"><i class="fas fa-calendar-days me-1"></i>Due this week: ₹ 4.5 L</small>
                </div>
                <div class="metric-icon-box">
                    <i class="fas fa-money-check-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Collection Progress -->
    @php
        $totalVal = $metrics['collections'] + $metrics['outstanding'];
        $eff = $totalVal > 0 ? ($metrics['collections'] / $totalVal) * 100 : 100.0;
    @endphp
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-gradient-success h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="kpi-label text-white-50">Territory Collections</span>
                        <h4 class="kpi-value mt-1 mb-0">₹ {{ number_format($metrics['collections'] / 100000, 2) }} L</h4>
                        <small class="text-white-50">Collected Invoices</small>
                    </div>
                    <div class="metric-icon-box">
                        <i class="fas fa-hand-holding-dollar text-white"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="progress bg-white bg-opacity-25" style="height: 5px;">
                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $eff }}%"></div>
                    </div>
                    <span class="text-white-50 text-xs mt-1 d-inline-block">Collection Ratio: {{ number_format($eff, 1) }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Invoice Count -->
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-danger bg-gradient h-100 border-0 shadow-sm text-white">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                    <span class="kpi-label text-white-50" style="color: rgba(255,255,255,0.7) !important;">Overdue Invoices</span>
                    <h3 class="kpi-value mt-1 mb-0 text-white" style="color: white !important;">{{ $metrics['overdue_invoices'] }}</h3>
                    <small class="text-white bg-white bg-opacity-20 px-1.5 py-0.5 rounded text-xs">
                        Requires Ledger Follow-up
                    </small>
                </div>
                <div class="metric-icon-box" style="background: rgba(255,255,255,0.15);">
                    <i class="fas fa-file-invoice text-white"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ASO Charts Section -->
<div class="row g-3 mb-4">
    <!-- Dealer Outstanding Bar Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-column me-2 text-primary"></i>Dealer Outstanding Ledger (₹ Lakhs)</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 250px;">
                    <canvas id="asoDealerOutstandingChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Sales line chart -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-line me-2 text-success"></i>Monthly Brand Lift (MT)</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 250px;">
                    <canvas id="asoCategorySalesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ASO Dealers List & Orders -->
<div class="row g-3">
    <!-- Dealers Overview List -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-users-gear me-2 text-primary"></i>Assigned Dealers visit & ledger tracker</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="aso-dealers-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Dealer</th>
                                <th>Outstanding (₹)</th>
                                <th>Sales MT (June)</th>
                                <th>Last Visited</th>
                                <th class="pe-3 text-end">Ledger Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dealers as $dl)
                            @php
                                $statusBadge = $dl['status'] == 'Active' ? 'bg-success' : ($dl['status'] == 'Warning' ? 'bg-warning text-dark' : 'bg-danger');
                            @endphp
                            <tr data-status="{{ $dl['status'] }}" data-dealer-id="{{ $dl['id'] }}" data-dealer-name="{{ $dl['name'] }}">
                                <td class="ps-3 fw-bold text-dark" style="font-size: 0.8rem;">{{ $dl['name'] }}</td>
                                <td class="text-danger fw-bold" style="font-size: 0.8rem;">₹ {{ number_format($dl['outstanding']) }}</td>
                                <td style="font-size: 0.8rem;">{{ number_format($dl['sales'] / 50000, 1) }} MT</td>
                                <td class="text-muted" style="font-size: 0.8rem;">{{ $dl['last_visit'] }}</td>
                                <td class="pe-3 text-end">
                                    <span class="badge {{ $statusBadge }} bg-opacity-10 text-{{ str_contains($statusBadge, 'text-dark') ? 'warning' : ($dl['status'] == 'Active' ? 'success' : 'danger') }} border" style="font-size: 0.65rem;">
                                        @if($dl['status'] == 'Active') Verified @elseif($dl['status'] == 'Warning') Unconfirmed @else Overdue limit @endif
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders by ASO Dealers -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-receipt me-2 text-success"></i>Recent Territory Orders</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Order ID</th>
                                <th>Dealer</th>
                                <th>Value (₹)</th>
                                <th class="pe-3 text-end">Workflow Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_orders as $ord)
                            @php
                                $statusColor = str_contains($ord['status'], 'Approved') || str_contains($ord['status'], 'Shipped') ? 'success' : (str_contains($ord['status'], 'Pending') ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-dark" style="font-size: 0.75rem;">{{ $ord['order_no'] }}</td>
                                <td style="font-size: 0.75rem;">{{ $ord['dealer'] }}</td>
                                <td class="fw-bold" style="font-size: 0.75rem;">₹ {{ number_format($ord['amount']) }}</td>
                                <td class="pe-3 text-end">
                                    <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }}" style="font-size: 0.65rem;">{{ $ord['status'] }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
