@php
    $totalVal = $metrics['collections'] + $metrics['outstanding'];
    $collectionEfficiency = $totalVal > 0 ? ($metrics['collections'] / $totalVal) * 100 : 100.0;
@endphp
<!-- Branch Manager (BM) Dashboard Section -->
<div class="row g-3 mb-4">
    <!-- Filter Panel -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 filter-card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-filter me-2 text-primary"></i>Branch Filters
                        (Official Hierarchy)</h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0 reset-filters-btn"><i
                            class="fas fa-undo me-1"></i>Reset Filters</button>
                </div>
                <div class="row g-2">
                    <!-- Level 1: Area Sales Manager (ASM) -->
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Area Sales Manager (ASM)</label>
                        <select class="form-select form-select-sm" id="bm-filter-asm">
                            <option value="">All ASMs</option>
                            @foreach ($asms as $asm)
                                <option value="{{ $asm['id'] }}" data-region="{{ $asm['region'] }}">
                                    {{ $asm['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Level 2: Assistant Section Officer (ASO) -->
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Assistant Section Officer (ASO)</label>
                        <select class="form-select form-select-sm" id="bm-filter-aso">
                            <option value="">All ASOs</option>
                            @foreach ($asos as $aso)
                                <option value="{{ $aso['id'] }}" data-asm="{{ $aso['asm_id'] }}">{{ $aso['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Level 3: Dealer -->
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Dealer</label>
                        <select class="form-select form-select-sm" id="bm-filter-dealer">
                            <option value="">All Dealers</option>
                            @foreach ($dealers as $dl)
                                <option value="{{ $dl['id'] }}" data-aso="{{ $dl['aso_id'] }}">{{ $dl['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Date Range -->
                    <div class="col-md-2 col-sm-12">
                        <label class="form-label-xs text-muted fw-bold">Date Range</label>
                        <input type="date" class="form-control form-control-sm" id="bm-filter-date"
                            value="{{ date('Y-m-d') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BM KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Branch Sales Card -->
    <div class="col-xl-4 col-md-6">
        <div class="card metric-card bg-gradient-primary h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column justify-content-between p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="kpi-label text-white-50">Branch Sales (YTD)</span>
                        <h3 class="kpi-value mt-2 mb-0">₹ {{ number_format($metrics['achievement'] / 100000, 2) }} L
                        </h3>
                        <small class="text-white-50">Consolidated Billing</small>
                    </div>
                    <div class="metric-icon-box">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between text-white mb-1" style="font-size: 0.75rem;">
                        <span>Collection Efficiency</span>
                        <span>{{ number_format($collectionEfficiency, 1) }}%</span>
                    </div>
                    <div class="progress bg-white bg-opacity-25" style="height: 6px;">
                        <div class="progress-bar bg-white" role="progressbar"
                            style="width: {{ $collectionEfficiency }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Total Outstanding Card -->
    <div class="col-xl-4 col-md-6">
        <div class="card metric-card bg-gradient-warning h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column justify-content-between p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="kpi-label text-white-50">Branch Outstanding</span>
                        <h3 class="kpi-value mt-2 mb-0 text-white">₹
                            {{ number_format($metrics['outstanding'] / 100000, 2) }} L</h3>
                        <small class="text-white-50">Pending Collections</small>
                    </div>
                    <div class="metric-icon-box">
                        <i class="fas fa-hand-holding-usd text-white"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between text-white mb-1" style="font-size: 0.75rem;">
                        <span>Cash Collected</span>
                        <span>₹ {{ number_format($metrics['collections'] / 100000, 2) }} L</span>
                    </div>
                    <div class="progress bg-white bg-opacity-25" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar"
                            style="width: {{ $collectionEfficiency }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hierarchy Structure Stats Card -->
    <div class="col-xl-4 col-md-12">
        <div class="card bg-white border-0 shadow-sm h-100 rounded-3">
            <div class="card-body p-4">
                <h6 class="mb-3 fw-bold text-secondary text-uppercase" style="font-size: 0.7rem;">Official Hierarchy
                    Strength</h6>
                <div class="row g-3">
                    <div class="col-4 border-end text-center">
                        <div class="text-primary fw-bold fs-4">{{ $metrics['active_asms'] }}</div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.55rem;">Active ASMs</div>
                    </div>
                    <div class="col-4 border-end text-center">
                        <div class="text-success fw-bold fs-4">{{ $metrics['active_asos'] }}</div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.55rem;">Active ASOs</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-purple fw-bold fs-4 text-dark">{{ $metrics['total_dealers'] }}</div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.55rem;">Dealers</div>
                    </div>
                </div>
                <div class="mt-3 p-2 bg-light rounded text-center" style="font-size: 0.7rem;">
                    <i class="fas fa-circle-info text-info me-1"></i> Managed Branch: <strong>West India Branch</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BM Charts Row -->
<div class="row g-3 mb-4">
    <!-- Area Performance Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-bar me-2 text-primary"></i>ASM Sales &
                    Outstanding Performance (₹ Lakhs)</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 280px;">
                    <canvas id="bmAsmChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Ageing Chart -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-pie me-2 text-warning"></i>Outstanding
                    Ageing Breakdown</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="chart-container flex-grow-1" style="height: 200px;">
                    <canvas id="bmAgeingChart"></canvas>
                </div>
                <div class="mt-2 text-center" style="font-size: 0.7rem;">
                    <span class="badge bg-success bg-opacity-10 text-success me-1">0-30 Days</span>
                    <span class="badge bg-info bg-opacity-10 text-info me-1">31-60 Days</span>
                    <span class="badge bg-warning bg-opacity-10 text-warning me-1">61-90 Days</span>
                    <span class="badge bg-danger bg-opacity-10 text-danger">90+ Days</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BM ASM Performance Table -->
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-trophy me-2 text-success"></i>Area Sales
                    Manager (ASM) Performance League</h6>
                <a href="#" class="btn btn-sm btn-outline-primary" style="font-size: 0.7rem;"><i
                        class="fas fa-file-excel me-1"></i>Export Performance Reports</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="bm-asm-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ASM Name</th>
                                <th>Billing (₹ L)</th>
                                <th>Collections (₹ L)</th>
                                <th>Outstanding (₹ L)</th>
                                <th class="pe-3 text-end">Collection Eff.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($asm_performance as $perf)
                                @php
                                    $totalVal = $perf['collections'] + $perf['outstanding'];
                                    $eff = $totalVal > 0 ? ($perf['collections'] / $totalVal) * 100 : 100.0;
                                    $color = $eff >= 90 ? 'success' : ($eff >= 75 ? 'warning' : 'danger');
                                @endphp
                                <tr data-asm-id="{{ $perf['id'] ?? '' }}" data-asm-name="{{ $perf['name'] }}">
                                    <td class="ps-3 fw-bold text-dark">
                                        <i class="fas fa-user-tie me-2 text-muted"></i>{{ $perf['name'] }}
                                    </td>
                                    <td class="fw-bold text-primary">₹ {{ number_format($perf['sales'] / 100000, 2) }}
                                        L</td>
                                    <td class="fw-bold text-success">₹
                                        {{ number_format($perf['collections'] / 100000, 2) }} L</td>
                                    <td class="text-danger fw-bold">₹
                                        {{ number_format($perf['outstanding'] / 100000, 2) }} L</td>
                                    <td class="pe-3 text-end" style="width: 250px;">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span class="me-2 text-muted text-xs"
                                                style="min-width: 35px;">{{ number_format($eff, 1) }}%</span>
                                            <div class="progress" style="height: 5px; width: 100px;">
                                                <div class="progress-bar bg-{{ $color }}" role="progressbar"
                                                    style="width: {{ $eff }}%"></div>
                                            </div>
                                        </div>
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
