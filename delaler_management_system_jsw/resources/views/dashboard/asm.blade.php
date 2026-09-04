<!-- Area Sales Manager (ASM) Dashboard Section -->
<div class="row g-3 mb-4">
    <!-- Filter Panel -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 filter-card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-filter me-2 text-primary"></i>Area Filters (West Zone Hierarchy)</h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0 reset-filters-btn"><i class="fas fa-undo me-1"></i>Reset Filters</button>
                </div>
                <div class="row g-2">
                    <!-- Level 3: Assistant Section Officer (ASO) -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Assistant Section Officer (ASO)</label>
                        <select class="form-select form-select-sm" id="asm-filter-aso">
                            <option value="">All ASOs</option>
                            @foreach($asos as $aso)
                                <option value="{{ $aso['id'] }}">{{ $aso['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Level 4: Dealer -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Dealer</label>
                        <select class="form-select form-select-sm" id="asm-filter-dealer">
                            <option value="">All Dealers</option>
                            @foreach($dealers as $dl)
                                <option value="{{ $dl['id'] }}" data-aso="{{ $dl['aso_id'] }}">{{ $dl['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Brand Category Segment -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">JSW Segment</label>
                        <select class="form-select form-select-sm" id="asm-filter-brand">
                            <option value="">All Segments</option>
                            <option value="neosteel">JSW NeoSteel (Rebars)</option>
                            <option value="cement">JSW Cement</option>
                            <option value="paints">JSW Paints</option>
                        </select>
                    </div>
                    <!-- Date Range -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Date Range</label>
                        <input type="date" class="form-control form-control-sm" id="asm-filter-date" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ASM KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Area Sales Card -->
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-gradient-primary h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="kpi-label text-white-50">Area Sales (YTD)</span>
                        <h4 class="kpi-value mt-2 mb-0">₹ {{ number_format($metrics['achievement'] / 100000, 2) }} L</h4>
                        <small class="text-white-50">Total Billing</small>
                    </div>
                    <div class="metric-icon-box">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress bg-white bg-opacity-25" style="height: 5px;">
                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $metrics['collection_efficiency'] }}%"></div>
                    </div>
                    <span class="text-white-50 text-xs mt-1 d-inline-block">Collection Ratio: {{ $metrics['collection_efficiency'] }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Area Outstanding Card -->
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-gradient-warning h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="kpi-label text-white-50">Area Outstanding</span>
                        <h4 class="kpi-value mt-2 mb-0">₹ {{ number_format($metrics['outstanding'] / 100000, 2) }} L</h4>
                        <small class="text-white-50">Pending Collections</small>
                    </div>
                    <div class="metric-icon-box">
                        <i class="fas fa-money-check-dollar text-white"></i>
                    </div>
                </div>
                <div class="mt-3 text-white-50 text-xs">
                    <span>Active Cash Collected: <strong>₹ {{ number_format($metrics['collections'] / 100000, 2) }} L</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Collection Efficiency Card -->
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-gradient-success h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="kpi-label text-white-50">Collection Efficiency</span>
                        <h4 class="kpi-value mt-2 mb-0">{{ $metrics['collection_efficiency'] }}%</h4>
                        <small class="text-white-50">Cash Recovery Ratio</small>
                    </div>
                    <div class="metric-icon-box">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress bg-white bg-opacity-25" style="height: 5px;">
                        <div class="progress-bar bg-light" role="progressbar" style="width: {{ $metrics['collection_efficiency'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Territory Strength Stats -->
    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 rounded-3">
            <div class="card-body p-3 d-flex flex-column justify-content-center">
                <h6 class="mb-2 fw-bold text-secondary text-uppercase" style="font-size: 0.65rem;">My Subordinates</h6>
                <div class="d-flex align-items-center justify-content-around mt-1">
                    <div class="text-center">
                        <h5 class="fw-bold text-primary mb-0">{{ $metrics['active_asos'] }}</h5>
                        <span class="text-muted text-xs">ASO Officers</span>
                    </div>
                    <div class="vr bg-light" style="height: 35px;"></div>
                    <div class="text-center">
                        <h5 class="fw-bold text-success mb-0">{{ $metrics['total_dealers'] }}</h5>
                        <span class="text-muted text-xs">Dealers Assigned</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ASM Charts Row -->
<div class="row g-3 mb-4">
    <!-- ASO Sales Performance Chart -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-bar me-2 text-primary"></i>ASO Sales Performance (₹ Lakhs)</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 250px;">
                    <canvas id="asmAsoChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Brand Category Product Mix Chart -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-cubes me-2 text-warning"></i>Product Brand Sales Volume Mix</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="chart-container" style="height: 230px; width: 100%;">
                    <canvas id="asmBrandChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ASM Tables Row -->
<div class="row g-3">
    <!-- ASO Scorecard -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-list-check me-2 text-info"></i>ASO Field Performance Scorecard</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="asm-aso-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ASO Name</th>
                                <th>Dealers</th>
                                <th>Sales (₹ L)</th>
                                <th>Outstanding (₹ L)</th>
                                <th class="pe-3 text-end">Visit Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aso_scorecard as $score)
                            <tr data-aso-id="{{ $score['id'] ?? '' }}" data-aso-name="{{ $score['name'] }}">
                                <td class="ps-3 fw-bold text-dark">
                                    <i class="fas fa-user me-2 text-muted"></i>{{ $score['name'] }}
                                </td>
                                <td>{{ $score['dealers_count'] }}</td>
                                <td class="text-success fw-bold">₹ {{ number_format($score['sales'], 1) }}</td>
                                <td class="text-danger fw-bold">₹ {{ number_format($score['outstanding'], 1) }}</td>
                                <td class="pe-3 text-end">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">{{ $score['visit_rate'] }}%</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Dealers by Sales -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-shop me-2 text-success"></i>Dealer Ledger & Sales Status</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="asm-dealer-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Dealer</th>
                                <th>Sales (₹ L)</th>
                                <th>Outstanding (₹ L)</th>
                                <th class="pe-3 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($top_dealers as $topD)
                            @php
                                $badgeClass = $topD['status'] == 'Active' ? 'bg-success' : ($topD['status'] == 'Warning' ? 'bg-warning text-dark' : 'bg-danger');
                            @endphp
                            <tr data-dealer-id="{{ $topD['id'] ?? '' }}" data-dealer-name="{{ $topD['name'] }}">
                                <td class="ps-3 fw-bold text-dark" style="font-size: 0.75rem;">{{ $topD['name'] }}</td>
                                <td class="text-primary fw-bold" style="font-size: 0.75rem;">₹ {{ number_format($topD['sales'], 1) }}</td>
                                <td class="text-muted" style="font-size: 0.75rem;">₹ {{ number_format($topD['outstanding'], 1) }}</td>
                                <td class="pe-3 text-end">
                                    <span class="badge {{ $badgeClass }} bg-opacity-10 text-{{ str_contains($badgeClass, 'text-dark') ? 'warning' : ($topD['status'] == 'Active' ? 'success' : 'danger') }} border" style="font-size: 0.6rem;">{{ $topD['status'] }}</span>
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
