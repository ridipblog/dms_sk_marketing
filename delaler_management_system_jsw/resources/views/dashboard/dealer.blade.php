<!-- Dealer Dashboard Section -->
<div class="row g-3 mb-4">
    <!-- Filter Panel -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 filter-card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-filter me-2 text-primary"></i>My Dealership Filters ({{ $metrics['dealer_name'] }})</h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0 reset-filters-btn"><i class="fas fa-undo me-1"></i>Reset Filters</button>
                </div>
                <div class="row g-2">
                    <!-- Segment Select -->
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Brand Segment</label>
                        <select class="form-select form-select-sm" id="dealer-filter-brand">
                            <option value="">All Segments (JSW Consolidated)</option>
                            <option value="neosteel">JSW NeoSteel (Rebars)</option>
                            <option value="cement">JSW Cement</option>
                            <option value="paints">JSW Paints</option>
                        </select>
                    </div>
                    <!-- Quarter Select -->
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label-xs text-muted fw-bold">Financial Period</label>
                        <select class="form-select form-select-sm" id="dealer-filter-period">
                            <option value="Q1-26" selected>FY 2026-27 - Q1 (Current)</option>
                            <option value="Q4-25">FY 2025-26 - Q4</option>
                            <option value="Q3-25">FY 2025-26 - Q3</option>
                        </select>
                    </div>
                    <!-- Document Type -->
                    <div class="col-md-4 col-sm-12">
                        <label class="form-label-xs text-muted fw-bold">Quick Document Link</label>
                        <select class="form-select form-select-sm" id="dealer-document-link" onchange="if(this.value) window.location.href=this.value;">
                            <option value="" selected>-- Go to ledger section --</option>
                            <option value="{{ route('accounts.invoices.index') }}">My Invoices</option>
                            <option value="{{ route('accounts.payment_tracks.index') }}">My Vouchers / Receipts</option>
                            <option value="{{ route('accounts.dealer_statement.index') }}">Full Statement of Account</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $totalVal = $metrics['collections'] + $metrics['outstanding'];
    $collectionEfficiency = $totalVal > 0 ? ($metrics['collections'] / $totalVal) * 100 : 100.0;
@endphp
<!-- Dealer KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Total Billing Card -->
    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 rounded-3 position-relative overflow-hidden">
            <div class="card-body p-4">
                <span class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Total Billing (Sales)</span>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">₹ {{ number_format($metrics['sales'] / 100000, 2) }} L</h4>
                        <small class="text-muted">Consolidated Billing</small>
                    </div>
                    <div class="credit-gauge-percentage text-primary fw-bold" style="font-size: 1rem;">
                        Eff: {{ number_format($collectionEfficiency, 1) }}%
                    </div>
                </div>
                <div class="progress mt-3" style="height: 6px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $collectionEfficiency }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Outstanding -->
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-gradient-primary h-100 border-0 shadow-sm text-white">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div>
                    <span class="kpi-label">Active Outstanding</span>
                    <h3 class="kpi-value mt-2 mb-0">₹ {{ number_format($metrics['outstanding'] / 100000, 2) }} L</h3>
                    <small class="text-white-50"><i class="fas fa-wallet me-1"></i>Pending Payments</small>
                </div>
                <div class="metric-icon-box">
                    <i class="fas fa-file-invoice-dollar text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Discount Slab Indicator -->
    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 rounded-3">
            <div class="card-body p-4">
                <span class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Active Cash Discount Slab</span>
                <div class="mt-2">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 mb-1 fw-bold">
                        <i class="fas fa-tag me-1"></i> Slab Tier 3 ({{ $metrics['earned_discount'] }}% Off)
                    </span>
                    <p class="text-muted mb-0 mt-2" style="font-size: 0.75rem;">
                        Buy <strong>{{ $metrics['next_slab_target'] }} MT</strong> more JSW NeoSteel to unlock **Tier 4 (3.0% discount)**
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue invoices indicator -->
    <div class="col-xl-3 col-md-6">
        <div class="card metric-card bg-gradient-warning h-100 border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div>
                    <span class="kpi-label text-white-50">Overdue Balance</span>
                    <h3 class="kpi-value mt-2 mb-0">₹ {{ number_format($metrics['overdue_amount'] / 100000, 2) }} L</h3>
                    <small class="text-white-50 bg-danger bg-opacity-25 px-1.5 py-0.5 rounded text-xs"><i class="fas fa-circle-exclamation me-1"></i>Action Required</small>
                </div>
                <div class="metric-icon-box">
                    <i class="fas fa-scale-unbalanced text-white"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dealer Charts Section -->
<div class="row g-3 mb-4">
    <!-- Monthly Buying Pattern Chart -->
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-line me-2 text-success"></i>My Monthly Lifting/Purchase Pattern (MT)</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 230px;">
                    <canvas id="dealerPurchaseChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dealer discount slabs and invoice tables -->
<div class="row g-3">
    <!-- Discount Slabs Progress table -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-percent me-2 text-warning"></i>My Volume Discount Scheme Slabs</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Slab Category</th>
                                <th>Monthly Target</th>
                                <th>Discount</th>
                                <th class="pe-3 text-end">Slab Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($discount_slabs as $slab)
                            @php
                                $statusColor = $slab['status'] == 'Unlocked' ? 'success' : ($slab['status'] == 'Active' ? 'primary' : 'muted');
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-dark" style="font-size: 0.75rem;">{{ $slab['tier'] }}</td>
                                <td style="font-size: 0.75rem;">{{ $slab['range'] }}</td>
                                <td class="fw-bold text-success" style="font-size: 0.75rem;">{{ $slab['rate'] }}</td>
                                <td class="pe-3 text-end">
                                    <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }}" style="font-size: 0.65rem;">{{ $slab['status'] }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invoices Table -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-file-pdf me-2 text-danger"></i>My Recent Invoices</h6>
                <a href="{{ route('accounts.invoices.index') }}" style="font-size: 0.7rem;" class="text-decoration-none">View Invoice Section</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Invoice No</th>
                                <th>Date</th>
                                <th>Amount (₹)</th>
                                <th>Due Date</th>
                                <th class="pe-3 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_invoices as $inv)
                            @php
                                $statusColor = $inv['status'] == 'Paid' ? 'success' : ($inv['status'] == 'Unpaid' ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-dark" style="font-size: 0.75rem;">
                                    <i class="far fa-file-pdf me-2 text-danger"></i>{{ $inv['invoice_no'] }}
                                </td>
                                <td style="font-size: 0.75rem;">{{ $inv['date'] }}</td>
                                <td class="fw-bold" style="font-size: 0.75rem;">₹ {{ number_format($inv['amount']) }}</td>
                                <td class="text-muted" style="font-size: 0.75rem;">{{ $inv['due_date'] }}</td>
                                <td class="pe-3 text-end">
                                    <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }}" style="font-size: 0.65rem;">{{ $inv['status'] }}</span>
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
