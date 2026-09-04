@extends('layouts.app')

@push('styles')
    <style>
        .invoice-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .invoice-header {
            background: linear-gradient(135deg, #f6f8fd 0%, #f1f5f9 100%);
            border-bottom: 2px solid #e2e8f0;
            border-radius: 12px 12px 0 0 !important;
            padding: 2rem;
        }

        .invoice-body {
            padding: 2rem;
        }

        .company-logo-placeholder {
            width: 60px;
            height: 60px;
            background: #4e73df;
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 0.4rem 1rem;
            border-radius: 50rem;
        }

        .table-invoice th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #64748b;
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-invoice td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .total-section {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <!-- Breadcrumb & Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0 text-gray-800">Order Details</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('accounts.order_tracking.index') }}"
                                class="text-decoration-none">Order Tracking</a></li>
                        <li class="breadcrumb-item active" aria-current="page">ORD-2024-001</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary shadow-sm" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> Print
                </button>
                <button class="btn btn-danger shadow-sm">
                    <i class="fas fa-file-pdf me-2"></i> Download PDF
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card invoice-card">
                    <!-- Header -->
                    <div class="card-header invoice-header">
                        <div class="row align-items-center">
                            <div class="col-sm-6 text-center text-sm-start mb-3 mb-sm-0">
                                <div
                                    class="d-flex align-items-center justify-content-center justify-content-sm-start gap-3">
                                    <div class="company-logo-placeholder shadow-sm">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                        <h4 class="fw-bold mb-0">Balaji Enterprises</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 text-center text-sm-end">
                                <h5 class="text-primary fw-bold mb-1">ORD-2024-001</h5>
                                <p class="text-muted mb-2">Date: 12 Jun 2024</p>
                                <span
                                    class="badge bg-success bg-opacity-10 text-success border border-success status-badge">
                                    <i class="fas fa-check-circle me-1"></i> Completed
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="card-body invoice-body">
                        <!-- Billing Info -->
                        <div class="row mb-5">
                            <div class="col-sm-6 mb-4 mb-sm-0">
                                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem;">Billed To
                                </h6>
                                <h6 class="fw-bold mb-1">Metro Hardware</h6>
                                <p class="text-muted mb-1">123, Main Market Road,</p>
                                <p class="text-muted mb-1">Andheri West, Mumbai - 400053</p>
                                <p class="text-muted mb-0"><i class="fas fa-phone-alt me-2"></i>+91 98765 43210</p>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem;">Payment
                                    Information</h6>
                                <p class="mb-1"><span class="text-muted">Payment Mode:</span> <span class="fw-bold">Bank
                                        Transfer</span></p>
                                <p class="mb-1"><span class="text-muted">Transaction ID:</span> <span
                                        class="fw-bold">TXN-987123456</span></p>
                                <p class="mb-0"><span class="text-muted">Due Date:</span> <span class="fw-bold">20 Jun
                                        2024</span></p>
                            </div>
                        </div>

                        <!-- Products Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-invoice mb-0">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="25%">Product Description</th>
                                        <th width="15%" class="text-center">Purchase Date</th>
                                        <th width="15%" class="text-center">Due Date (21 Days)</th>
                                        <th width="10%" class="text-center">Qty (MT)</th>
                                        <th width="15%" class="text-end">Base Price (₹)</th>
                                        <th width="15%" class="text-end">Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-muted">1</td>
                                        <td>
                                            <div class="fw-bold text-dark">JSW Neo Steel 550D</div>
                                            <div class="text-muted" style="font-size: 0.8rem;">Size: 12mm | HSN: 7214</div>
                                        </td>
                                        <td class="text-center">01 Jun 2024</td>
                                        <td class="text-center text-danger fw-bold">22 Jun 2024</td>
                                        <td class="text-center">30.000</td>
                                        <td class="text-end">66,666.67</td>
                                        <td class="text-end fw-bold">2,000,000.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals -->
                        <div class="row justify-content-end">
                            <div class="col-sm-6 col-md-5 col-lg-4">
                                <div class="total-section shadow-sm">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Subtotal</span>
                                        <span class="fw-bold text-dark">₹ 2,000,000.00</span>
                                    </div>
                                    <hr class="my-3 opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-uppercase text-primary" style="font-size: 0.85rem;">Grand
                                            Total</span>
                                        <span class="fw-bold text-primary" style="font-size: 1.25rem;">₹ 2,000,000.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                        <span class="fw-bold text-muted" style="font-size: 0.85rem;">Amount Paid</span>
                                        <span class="fw-bold text-success">₹ 2,002,000.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="fw-bold text-muted" style="font-size: 0.85rem;">Balance Due</span>
                                        <span class="fw-bold text-danger">₹ 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Late Payment Fine Example -->
                        <div class="row mt-5">
                            <div class="col-12">
                                <div class="card bg-white border border-danger shadow-sm">
                                    <div
                                        class="card-header bg-danger bg-opacity-10 text-danger fw-bold border-bottom border-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i> Late Payment Fine Calculation
                                        Example (Debit Note)
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3" style="font-size: 0.85rem;">
                                            If payment is made after the 21-day grace period, a fine is applied based on the
                                            outstanding amount and delay days.
                                        </p>
                                        <div class="row text-sm">
                                            <div class="col-md-6 border-end">
                                                <h6 class="fw-bold text-dark mb-2">Given Parameters:</h6>
                                                <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                                                    <li class="mb-1"><span class="text-muted">Total Amount
                                                            Billed:</span> <strong>₹ 2,000,000.00</strong></li>
                                                    <li class="mb-1"><span class="text-muted">Total Quantity:</span>
                                                        <strong>30 MT</strong></li>
                                                    <li class="mb-1"><span class="text-muted">Grace Period:</span>
                                                        <strong>21 Days</strong></li>
                                                    <li class="mb-1"><span class="text-muted">Delay Days (After 21
                                                            days):</span> <strong>4 Days</strong></li>
                                                    <li class="mb-1"><span class="text-muted">Paid Balance(
                                                            Late):</span> <strong>₹ 1,500,000.00</strong></li>
                                                    <li><span class="text-muted">Fine Amount per MT:</span> <strong>₹
                                                            30.00</strong></li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6 px-4">
                                                <h6 class="fw-bold text-dark mb-2">Step-by-Step Calculation:</h6>
                                                <ol class="text-muted ps-3 mb-0" style="font-size: 0.85rem;">
                                                    <li class="mb-2">
                                                        <strong>Find Penalty Quantity:</strong><br>
                                                        <code>(Total Qty / Total Amount) × Late Paid Balance</code><br>
                                                        <span class="text-dark">(30 / 2000000) × 1500000 = <strong>22.5
                                                                MT</strong></span>
                                                    </li>
                                                    <li>
                                                        <strong>Calculate Final Fine:</strong><br>
                                                        <code>Delay Days × Penalty Quantity × Fine per MT</code><br>
                                                        <span class="text-danger fw-bold">4 × 22.5 × 30 = ₹ 2,700.00</span>
                                                    </li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction History -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem;"><i
                                        class="fas fa-history me-1"></i> Transaction History (Voucher Flow)</h6>
                                <div class="table-responsive">
                                    <table class="table table-invoice mb-0 border">
                                        <thead>
                                            <tr>
                                                <th width="15%">Date</th>
                                                <th width="15%">Transaction ID</th>
                                                <th width="20%">Voucher Type</th>
                                                <th width="15%">Payment Mode</th>
                                                <th width="15%" class="text-end">Debit (₹)</th>
                                                <th width="15%" class="text-end">Credit (₹)</th>
                                                <th width="20%" class="text-end">Balance (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-muted">01 Jun 2024</td>
                                                <td class="fw-bold">INV-2001</td>
                                                <td><span
                                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger">SALE</span>
                                                </td>
                                                <td class="text-muted">-</td>
                                                <td class="text-end text-danger fw-bold">2,000,000.00</td>
                                                <td class="text-end text-muted">-</td>
                                                <td class="text-end fw-bold">2,000,000.00</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">05 Jun 2024</td>
                                                <td class="fw-bold">TXN-98712</td>
                                                <td><span
                                                        class="badge bg-success bg-opacity-10 text-success border border-success">RECEIPT</span>
                                                </td>
                                                <td class="text-muted">Bank Transfer</td>
                                                <td class="text-end text-muted">-</td>
                                                <td class="text-end text-success fw-bold">1,500,000.00</td>
                                                <td class="text-end fw-bold">500,000.00</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">26 Jun 2024</td>
                                                <td class="fw-bold">DN-001</td>
                                                <td><span
                                                        class="badge bg-warning bg-opacity-10 text-warning border border-warning">DEBIT
                                                        NOTE</span></td>
                                                <td class="text-muted">Late Payment Fine</td>
                                                <td class="text-end text-danger fw-bold">2,700.00</td>
                                                <td class="text-end text-muted">-</td>
                                                <td class="text-end fw-bold">502,700.00</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">27 Jun 2024</td>
                                                <td class="fw-bold">CN-001</td>
                                                <td><span
                                                        class="badge bg-info bg-opacity-10 text-info border border-info">CREDIT
                                                        NOTE</span></td>
                                                <td class="text-muted">Minor Adjustment</td>
                                                <td class="text-end text-muted">-</td>
                                                <td class="text-end text-success fw-bold">700.00</td>
                                                <td class="text-end fw-bold">502,000.00</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">28 Jun 2024</td>
                                                <td class="fw-bold">TXN-98713</td>
                                                <td><span
                                                        class="badge bg-success bg-opacity-10 text-success border border-success">RECEIPT</span>
                                                </td>
                                                <td class="text-muted">UPI</td>
                                                <td class="text-end text-muted">-</td>
                                                <td class="text-end text-success fw-bold">502,000.00</td>
                                                <td class="text-end fw-bold text-success">0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="bg-light rounded p-3 border-start border-4 border-warning">
                                    <h6 class="fw-bold mb-1"><i class="fas fa-info-circle me-2 text-warning"></i> Terms &
                                        Conditions</h6>
                                    <p class="text-muted mb-0" style="font-size: 0.8rem;">
                                        1. Payment is expected within 30 days of order completion.<br>
                                        2. Goods once sold will not be taken back.<br>
                                        3. Subject to local jurisdiction.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
