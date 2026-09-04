@php
    $totalVal = ($metrics['collections'] ?? 0) + ($metrics['outstanding'] ?? 0);
    $collectionEfficiency = $totalVal > 0 ? (($metrics['collections'] ?? 0) / $totalVal) * 100 : 100.0;
@endphp
<!-- Executive Admin Dashboard Section -->
<div class="row g-3 mb-4">
    <!-- Filter Panel -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 filter-card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-filter me-2 text-primary"></i>Admin Hierarchy Filters</h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0 reset-filters-btn"><i class="fas fa-undo me-1"></i>Reset Filters</button>
                </div>
                <div class="row g-2 align-items-end">
                    <!-- Level 1: District / Branch Manager (DM) -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">District / Branch Manager (DM)</label>
                        <select class="form-select form-select-sm select2 admin-cascade-select" id="admin-filter-dm" data-role="DM" data-target="#admin-filter-asm" data-placeholder="All DMs / BMs">
                            <option value="">All DMs / BMs</option>
                            @if(isset($dms))
                                @foreach($dms as $dm)
                                    <option value="{{ $dm['id'] }}">{{ $dm['name'] }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <!-- Level 2: Area Sales Manager (ASM) -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Area Sales Manager (ASM)</label>
                        <select class="form-select form-select-sm select2 admin-cascade-select" id="admin-filter-asm" data-role="ASM" data-target="#admin-filter-aso" data-placeholder="All ASMs">
                            <option value="">All ASMs</option>
                            @if(isset($asms))
                                @foreach($asms as $asm)
                                    <option value="{{ $asm['id'] }}" data-dm="{{ $asm['dm_id'] ?? '' }}">{{ $asm['name'] }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <!-- Level 3: Assistant Section Officer (ASO) -->
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Assistant Section Officer (ASO)</label>
                        <select class="form-select form-select-sm select2 admin-cascade-select" id="admin-filter-aso" data-role="ASO" data-target="#admin-filter-dealer" data-placeholder="All ASOs">
                            <option value="">All ASOs</option>
                            @if(isset($asos))
                                @foreach($asos as $aso)
                                    <option value="{{ $aso['id'] }}" data-asm="{{ $aso['asm_id'] ?? '' }}">{{ $aso['name'] }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <!-- Level 4: Dealer -->
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Dealer</label>
                        <select class="form-select form-select-sm select2" id="admin-filter-dealer" data-role="Dealer" data-placeholder="All Dealers">
                            <option value="">All Dealers</option>
                            @if(isset($dealers))
                                @foreach($dealers as $dl)
                                    <option value="{{ $dl['id'] }}" data-aso="{{ $dl['aso_id'] ?? '' }}">{{ $dl['name'] }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <!-- Date Range -->
                    <div class="col-md-2 col-sm-12">
                        <label class="form-label-xs text-muted fw-bold">Date Range</label>
                        <input type="date" class="form-control form-control-sm" id="admin-filter-date" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin KPI Card - Total Outstanding Only -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card metric-card bg-gradient-warning border-0 shadow-sm rounded-3">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="kpi-label text-white-50 text-uppercase fw-bold" style="letter-spacing: 1px;">Total Outstanding Amount</span>
                    <h2 class="kpi-value mt-2 mb-0 text-white fw-extrabold" id="admin-kpi-outstanding" style="font-size: 2.2rem;">
                        ₹ {{ number_format(($metrics['outstanding'] ?? 0) / 100000, 2) }} L
                    </h2>
                    <small class="text-white-50 mt-1 d-block"><i class="fas fa-filter me-1"></i>Filtered according to selected DM, ASM, ASO & Dealer parameters</small>
                </div>
                <div class="metric-icon-box text-white" style="font-size: 3.5rem; opacity: 0.35;">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Charts & Performance Overview -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-bar me-2 text-primary"></i>Executive Regional & Sales Overview</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 280px;">
                    <canvas id="bmAsmChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-pie me-2 text-warning"></i>Ageing Summary</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 280px;">
                    <canvas id="bmAgeingChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Performance League Table -->
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-trophy me-2 text-success"></i>ASM & Territory Performance Summary</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="admin-asm-table">
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
                            @if(isset($asm_performance))
                                @foreach($asm_performance as $perf)
                                @php
                                    $totalV = ($perf['collections'] ?? 0) + ($perf['outstanding'] ?? 0);
                                    $eff = $totalV > 0 ? (($perf['collections'] ?? 0) / $totalV) * 100 : 100.0;
                                    $color = $eff >= 90 ? 'success' : ($eff >= 75 ? 'warning' : 'danger');
                                @endphp
                                <tr data-asm-id="{{ $perf['id'] ?? '' }}" data-asm-name="{{ $perf['name'] }}">
                                    <td class="ps-3 fw-bold text-dark">
                                        <i class="fas fa-user-tie me-2 text-muted"></i>{{ $perf['name'] }}
                                    </td>
                                    <td class="fw-bold text-primary">₹ {{ number_format(($perf['sales'] ?? 0) / 100000, 2) }} L</td>
                                    <td class="fw-bold text-success">₹ {{ number_format(($perf['collections'] ?? 0) / 100000, 2) }} L</td>
                                    <td class="text-danger fw-bold">₹ {{ number_format(($perf['outstanding'] ?? 0) / 100000, 2) }} L</td>
                                    <td class="pe-3 text-end" style="width: 250px;">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span class="me-2 text-muted text-xs" style="min-width: 35px;">{{ number_format($eff, 1) }}%</span>
                                            <div class="progress" style="height: 5px; width: 100px;">
                                                <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $eff }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
