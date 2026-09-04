@extends('layouts.app')

@push('styles')
    <style>
        .finance-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .finance-card-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: #ffffff;
            border-radius: 12px 12px 0 0 !important;
        }
        .preview-card {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
        }
        .form-label {
            font-weight: 600;
            color: #334155;
            font-size: 0.875rem;
        }
        .input-group-text {
            background-color: #f1f5f9;
            color: #64748b;
        }
        .btn-save-finance {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-save-finance:hover {
            background: linear-gradient(135deg, #0a58ca 0%, #084298 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }
        .table-bank-details th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.825rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <!-- Page Title Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                    <i class="fas fa-university text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1 text-dark">Company Bank & Finance Details</h4>
                    <p class="text-muted small mb-0">Manage bank accounts for <strong>{{ $company->company_name ?? 'Active Company' }}</strong> displayed on invoices and receipts.</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Add Form & History Table -->
            <div class="col-12 col-lg-7 col-xl-8">
                <!-- Add New Bank Details Form Card -->
                <div class="card finance-card mb-4">
                    <div class="card-header finance-card-header py-3 px-4">
                        <h6 class="fw-bold mb-0 text-white">
                            <i class="fas fa-plus-circle me-2"></i> Add New Bank Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info small mb-4 d-flex align-items-center">
                            <i class="fas fa-info-circle me-2 fs-5"></i>
                            <div>
                                <strong>Automatic Status Rule:</strong> Adding a new bank account will automatically mark it as <strong>Active</strong> and set all existing bank accounts to <strong>Inactive</strong>.
                            </div>
                        </div>

                        <form id="companyBankForm" data-url="{{ route('settings.finance.store') }}">
                            @csrf

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name"
                                               placeholder="e.g. State Bank of India" required>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="account_holder_name" class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                                        <input type="text" class="form-control" id="account_holder_name" name="account_holder_name"
                                               value="{{ old('account_holder_name', $company->company_name ?? '') }}"
                                               placeholder="e.g. JSW Steel Ltd." required>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="account_no" class="form-label">Account Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                        <input type="text" class="form-control" id="account_no" name="account_no"
                                               placeholder="e.g. 384920194829" required>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="ifsc_code" class="form-label">IFSC Code <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                        <input type="text" class="form-control text-uppercase" id="ifsc_code" name="ifsc_code"
                                               placeholder="e.g. SBIN0001234" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="branch_name" class="form-label">Branch Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" class="form-control" id="branch_name" name="branch_name"
                                               placeholder="e.g. Main Branch, Mumbai">
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="swift_code" class="form-label">SWIFT Code / BIC</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                        <input type="text" class="form-control text-uppercase" id="swift_code" name="swift_code"
                                               placeholder="e.g. SBININBB123">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label for="upi_id" class="form-label">UPI ID / VPA</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-qrcode"></i></span>
                                        <input type="text" class="form-control" id="upi_id" name="upi_id"
                                               placeholder="e.g. company@sbi">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                <button type="submit" class="btn btn-primary btn-save-finance px-4 py-2" id="btnSaveFinance">
                                    <i class="fas fa-plus me-1"></i> Add & Set as Active Bank Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bank Details List Table Card -->
                <div class="card finance-card">
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-list me-2 text-primary"></i> Company Bank Details History
                        </h6>
                        <span class="badge bg-primary rounded-pill px-3 py-2">Total: {{ $bankDetailsList->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-hover table-bank-details mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Bank Name & Branch</th>
                                        <th>Account Holder</th>
                                        <th>Account No</th>
                                        <th>IFSC / SWIFT / UPI</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($bankDetailsList as $detail)
                                        <tr class="{{ $detail->status == 'active' ? 'table-primary bg-primary bg-opacity-10' : '' }}">
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark">{{ $detail->bank_name }}</div>
                                                <div class="small text-muted">{{ $detail->branch_name ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark">{{ $detail->account_holder_name }}</span>
                                            </td>
                                            <td>
                                                <span class="font-monospace text-dark">{{ $detail->account_no }}</span>
                                            </td>
                                            <td>
                                                <div class="small fw-bold">{{ $detail->ifsc_code }}</div>
                                                @if($detail->swift_code)
                                                    <div class="small text-muted">SWIFT: {{ $detail->swift_code }}</div>
                                                @endif
                                                @if($detail->upi_id)
                                                    <div class="small text-primary">UPI: {{ $detail->upi_id }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($detail->status == 'active')
                                                    <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i> Active</span>
                                                @else
                                                    <span class="badge bg-secondary px-3 py-2 rounded-pill">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                @if ($detail->status != 'active')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary btn-set-active fw-bold"
                                                            data-id="{{ $detail->id }}"
                                                            data-url="{{ route('settings.finance.set_active') }}">
                                                        <i class="fas fa-toggle-on me-1"></i> Set Active
                                                    </button>
                                                @else
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 small">Current Default</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle me-1"></i> No bank details registered yet. Add one above.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Invoice Display Preview -->
            <div class="col-12 col-lg-5 col-xl-4">
                <div class="card finance-card sticky-top" style="top: 85px;">
                    <div class="card-header bg-white py-3 px-4 border-0">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-eye me-2 text-primary"></i> Active Invoice Bank Preview
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">This preview displays how the currently <strong>Active Bank Account</strong> will appear on tax invoices and receipts.</p>
                        
                        <div class="preview-card p-3">
                            <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom">Company Bank Details</h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr>
                                    <td class="text-muted px-0" style="width: 35%;">Holder:</td>
                                    <td class="fw-bold px-0 text-dark" id="prev_holder">{{ $activeBankDetail->account_holder_name ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted px-0">Bank:</td>
                                    <td class="fw-bold px-0 text-dark" id="prev_bank">{{ $activeBankDetail->bank_name ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted px-0">Account No:</td>
                                    <td class="fw-bold px-0 text-dark" id="prev_acc">{{ $activeBankDetail->account_no ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted px-0">IFSC / Branch:</td>
                                    <td class="fw-bold px-0 text-dark" id="prev_ifsc">
                                        {{ $activeBankDetail->ifsc_code ?: 'N/A' }}
                                        @if(!empty($activeBankDetail->branch_name)) ({{ $activeBankDetail->branch_name }}) @endif
                                    </td>
                                </tr>
                                <tr id="prev_swift_row" class="{{ empty($activeBankDetail->swift_code) ? 'd-none' : '' }}">
                                    <td class="text-muted px-0">SWIFT:</td>
                                    <td class="fw-bold px-0 text-dark" id="prev_swift">{{ $activeBankDetail->swift_code ?: '' }}</td>
                                </tr>
                                <tr id="prev_upi_row" class="{{ empty($activeBankDetail->upi_id) ? 'd-none' : '' }}">
                                    <td class="text-muted px-0">UPI ID:</td>
                                    <td class="fw-bold px-0 text-dark" id="prev_upi">{{ $activeBankDetail->upi_id ?: '' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info small mt-4 mb-0 d-flex align-items-start">
                            <i class="fas fa-check-circle me-2 mt-1 fs-6"></i>
                            <div>
                                Newly generated and printed invoices for <strong>{{ $company->company_name ?? 'this company' }}</strong> will automatically snapshot these active bank details.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/settings/finance.js') }}?v={{ config('app.asset_version', '1.0') }}"></script>
@endpush
