@extends('layouts.app')

@push('styles')
    <style>
        .invoice-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 1rem;
        }

        .invoice-box {
            max-width: 1000px;
            min-width: 800px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
            color: #000;
            font-size: 13px;
            border: 1px solid #000;
            background-color: #fff;
            box-sizing: border-box;
        }

        .invoice-box p,
        .invoice-box td,
        .invoice-box th,
        .invoice-box div {
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .invoice-box p {
            margin-bottom: 2px;
        }

        .border-black {
            border-color: #000 !important;
        }

        .border-bottom-black {
            border-bottom: 1px solid #000;
        }

        .border-top-black {
            border-top: 1px solid #000;
        }

        .border-end-black {
            border-right: 1px solid #000;
        }

        .border-start-black {
            border-left: 1px solid #000;
        }

        .table-custom {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .table-custom th,
        .table-custom td {
            border: 1px solid #000;
            padding: 4px 8px;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .table-custom th {
            text-align: center;
            font-weight: bold;
        }

        /* Remove horizontal borders for item rows to mimic the screenshot */
        .table-items td {
            border-top: none;
            border-bottom: none;
        }

        .table-items tr:last-child td {
            border-bottom: 1px solid #000;
        }

        .fw-bold-custom {
            font-weight: 700;
        }

        .small-text {
            font-size: 11px;
        }

        .title-cell {
            font-size: 10px;
            color: #333;
            word-break: break-word;
        }

        .val-cell {
            font-weight: bold;
            font-size: 12px;
            word-break: break-word;
        }

        .watermark {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            color: rgba(255, 0, 0, 0.15);
            font-weight: bold;
            white-space: nowrap;
            z-index: 0;
            pointer-events: none;
            user-select: none;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #printableInvoice,
            #printableInvoice * {
                visibility: visible;
            }

            .invoice-responsive-wrapper {
                overflow: visible !important;
                padding: 0 !important;
            }

            #printableInvoice {
                position: absolute;
                left: 0;
                top: 0;
                width: 100% !important;
                min-width: 100% !important;
                max-width: 100% !important;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-2">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-invoice me-2"></i> View Invoice
            </h4>
            <div class="d-flex flex-wrap gap-2">
                <button onclick="window.print()" class="btn btn-primary fw-bold shadow-sm me-2">
                    <i class="fas fa-print me-1"></i> Print Invoice
                </button>
                <a href="{{ route('accounts.invoices.index') }}" class="btn btn-outline-secondary fw-bold shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        @if (isset($errorMessage))
            <x-error-alert :message="$errorMessage" />
        @else
            <!-- RESPONSIVE INVOICE CONTAINER WRAPPER -->
            <div class="invoice-responsive-wrapper">
                <!-- INVOICE CONTAINER -->
                <div class="invoice-box shadow-sm position-relative" id="printableInvoice">
                    @if($invoice->invoice_status == 0)
                        <div class="watermark">NOT FINALIZED</div>
                    @endif

                <!-- Header -->
                <div class="d-flex justify-content-between p-3 border-bottom-black">
                    <div style="width: 30%"></div>
                    <div class="text-center" style="width: 40%">
                        <h5 class="fw-bold-custom mb-0">Tax Invoice</h5>
                    </div>
                    <div class="text-end fw-bold-custom" style="width: 30%">
                        e-Invoice
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="d-flex justify-content-end p-3 border-bottom-black">
                    <div class="text-end">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('accounts.invoices.view', Crypt::encryptString($invoice->id))) }}"
                            alt="QR Code" style="height: 100px; width: 100px;">
                    </div>
                </div>

                <!-- Company & Invoice Details Split -->
                <div class="row g-0 border-bottom-black">

                    <!-- Left Side: Company/Buyer Info -->
                    <div class="col-6 border-end-black d-flex flex-column">

                        <!-- Seller -->
                        <div class="p-2 border-bottom-black flex-grow-1">
                            <h6 class="fw-bold-custom mb-1">{{ $invoice->createdBy->company->company_name ?? 'N/A' }}</h6>
                            <p>{!! nl2br(e($invoice->createdBy->company->address ?? 'N/A')) !!}</p>
                            <p>GSTIN/UIN: {{ $invoice->createdBy->company->gst_no ?? 'N/A' }}</p>
                            <p>State Name : {{ $invoice->createdBy->company->state_name ?? 'Assam' }}, Code :
                                {{ $invoice->createdBy->company->state_code ?? '18' }}</p>
                        </div>

                        <!-- Consignee -->
                        <div class="p-2 border-bottom-black flex-grow-1">
                            <p class="mb-0">Consignee (Ship to)</p>
                            <h6 class="fw-bold-custom mb-1">{{ $invoice->shipTo->dealer->dealer_name ?? 'N/A' }}</h6>
                            <p>{!! nl2br(e($invoice->shipTo->dealer->address ?? 'N/A')) !!}</p>
                            <table style="width: 100%;">
                                <tr>
                                    <td width="30%">GSTIN/UIN</td>
                                    <td>: {{ $invoice->shipTo->dealer->gst_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td>State Name</td>
                                    <td>: {{ $invoice->shipTo->dealer->state_name ?? 'Assam' }}, Code :
                                        {{ $invoice->shipTo->dealer->state_code ?? '18' }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Buyer -->
                        <div class="p-2 flex-grow-1">
                            <p class="mb-0">Buyer (Bill to)</p>
                            <h6 class="fw-bold-custom mb-1">{{ $invoice->buyer->dealer->dealer_name ?? 'N/A' }}</h6>
                            <p>{!! nl2br(e($invoice->buyer->dealer->address ?? 'N/A')) !!}</p>
                            <table style="width: 100%;">
                                <tr>
                                    <td width="30%">GSTIN/UIN</td>
                                    <td>: {{ $invoice->buyer->dealer->gst_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td>State Name</td>
                                    <td>: {{ $invoice->buyer->dealer->state_name ?? 'Assam' }}, Code :
                                        {{ $invoice->buyer->dealer->state_code ?? '18' }}</td>
                                </tr>
                            </table>
                        </div>

                    </div>

                    <!-- Right Side: Invoice Particulars -->
                    <div class="col-6 d-flex flex-column">
                        <div class="d-flex border-bottom-black">
                            <div class="p-1 border-end-black" style="width: 35%">
                                <div class="title-cell">Invoice No.</div>
                                <div class="val-cell">{{ $invoice->user_invoice_no ?? $invoice->invoice_no ?? 'N/A' }}</div>
                            </div>
                            <div class="p-1 border-end-black" style="width: 35%">
                                <div class="title-cell">e-Way Bill No.</div>
                                <div class="val-cell">{{ $invoice->eway_bill_no ?? 'N/A' }}</div>
                            </div>
                            <div class="p-1" style="width: 30%">
                                <div class="title-cell">Dated</div>
                                <div class="val-cell">
                                    {{ isset($invoice->invoice_generate_date) ? \Carbon\Carbon::parse($invoice->invoice_generate_date)->format('d-M-y') : 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex border-bottom-black">
                            <div class="p-1 border-end-black" style="width: 50%">
                                <div class="title-cell">Delivery Note</div>
                                <div class="val-cell">{{ $invoice->delivery_note ?? 'N/A' }}</div>
                            </div>
                            <div class="p-1" style="width: 50%">
                                <div class="title-cell">Mode/Terms of Payment</div>
                                <div class="val-cell">{{ $invoice->terms_of_payment ?? 'N/A' }}</div>
                            </div>
                        </div>

                        <div class="d-flex border-bottom-black">
                            <div class="p-1 border-end-black" style="width: 50%">
                                <div class="title-cell">Reference No. & Date.</div>
                                <div class="val-cell">{{ $invoice->reference_no ?? 'N/A' }}</div>
                            </div>
                            <div class="p-1" style="width: 50%">
                                <div class="title-cell">Other References</div>
                                <div class="val-cell">{{ $invoice->other_references ?? 'N/A' }}</div>
                            </div>
                        </div>

                        <div class="d-flex border-bottom-black">
                            <div class="p-1 border-end-black" style="width: 50%">
                                <div class="title-cell">Buyer's Order No.</div>
                                <div class="val-cell">{{ $invoice->buyers_order_no ?? 'N/A' }}</div>
                            </div>
                            <div class="p-1" style="width: 50%">
                                <div class="title-cell">Dated</div>
                                <div class="val-cell">
                                    {{ isset($invoice->buyers_order_date) ? \Carbon\Carbon::parse($invoice->buyers_order_date)->format('d-M-y') : 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex border-bottom-black">
                            <div class="p-1 border-end-black" style="width: 50%">
                                <div class="title-cell">Dispatch Doc No.</div>
                                <div class="val-cell">{{ $invoice->dispatch_doc_no ?? $invoice->user_invoice_no ?? $invoice->invoice_no ?? 'N/A' }}</div>
                            </div>
                            <div class="p-1" style="width: 50%">
                                <div class="title-cell">Delivery Note Date</div>
                                <div class="val-cell">
                                    {{ isset($invoice->delivery_note_date) ? \Carbon\Carbon::parse($invoice->delivery_note_date)->format('d-M-y') : 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex border-bottom-black">
                            <div class="p-1 border-end-black" style="width: 50%">
                                <div class="title-cell">Dispatched through</div>
                                <div class="val-cell">{{ $invoice->dispatched_through ?? 'By Road' }}</div>
                            </div>
                            <div class="p-1" style="width: 50%">
                                <div class="title-cell">Destination</div>
                                <div class="val-cell">{{ $invoice->destination ?? 'N/A' }}</div>
                            </div>
                        </div>

                        <div class="d-flex border-bottom-black">
                            <div class="p-1 border-end-black" style="width: 50%">
                                <div class="title-cell">Bill of Lading/LR-RR No.</div>
                                <div class="val-cell">{{ $invoice->bill_of_lading ?? 'N/A' }}</div>
                            </div>
                            <div class="p-1" style="width: 50%">
                                <div class="title-cell">Motor Vehicle No.</div>
                                <div class="val-cell">{{ $invoice->vehicle_no ?? $invoice->motor_vehicle_no ?? 'N/A' }}</div>
                            </div>
                        </div>

                        <div class="p-1 flex-grow-1">
                            <div class="title-cell">Terms of Delivery</div>
                            <div class="val-cell">{{ $invoice->terms_of_delivery ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="table-custom table-items border-top-0 border-start-0 border-end-0">
                    <thead>
                        <tr class="border-bottom-black">
                            <th width="3%">Sl No.</th>
                            <th width="42%">Description of Goods</th>
                            <th width="10%">HSN/SAC</th>
                            <th width="10%">Quantity</th>
                            <th width="10%">Rate<br><span class="small-text fw-normal">(Incl. of Tax)</span></th>
                            <th width="10%">Rate</th>
                            <th width="5%">per</th>
                            <th width="10%">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->invoiceDetails as $index => $detail)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="fw-bold-custom">{{ $detail->productPricing->product->product_name ?? 'N/A' }}
                                </td>
                                <td class="text-center">{{ $detail->productPricing->product->hsn_code ?? 'N/A' }}</td>
                                <td class="fw-bold-custom">{{ number_format($detail->quantity, 3) }} MT</td>
                                <td class="text-end">
                                    {{ $detail->quantity > 0 ? inr($detail->chargeable_amount / $detail->quantity) : '0.00' }}
                                </td>
                                <td class="text-end">
                                    {{ $detail->quantity > 0 ? inr($detail->total_amount / $detail->quantity) : '0.00' }}
                                </td>
                                <td class="text-center">MT</td>
                                <td class="text-end fw-bold-custom">{{ inr($detail->total_amount) }}</td>
                            </tr>
                        @endforeach

                        <!-- Blank rows for spacing -->
                        <tr>
                            <td style="color: transparent;">.</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td style="color: transparent;">.</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <!-- Tax Breakdown -->
                        <tr>
                            <td></td>
                            <td class="text-end">
                                <br>
                                <br>
                                <br>
                                <br>
                                Less :
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="fw-bold-custom text-end pt-5">
                                <br>
                                @if(($invoice->tax_type ?? 'intra') === 'inter')
                                    IGST {{ $invoice->gst ?? 18 }}%<br>
                                @else
                                    CGST {{ ($invoice->gst ?? 18) / 2 }}%<br>
                                    SGST {{ ($invoice->gst ?? 18) / 2 }}%<br>
                                @endif
                                Round Off
                            </td>
                            <td class="text-end pt-5">
                                <br>
                                @if(($invoice->tax_type ?? 'intra') === 'inter')
                                    {{ $invoice->gst ?? 18 }} %<br>
                                @else
                                    {{ ($invoice->gst ?? 18) / 2 }} %<br>
                                    {{ ($invoice->gst ?? 18) / 2 }} %
                                @endif
                            </td>
                            <td class="text-end fw-bold-custom pt-5 border-bottom-black">
                                {{ inr($invoice->total_amount) }}<br>
                                @if(($invoice->tax_type ?? 'intra') === 'inter')
                                    {{ inr($invoice->total_igst_amount ?? $invoice->total_gst_amount) }}<br>
                                @else
                                    {{ inr($invoice->total_cgst_amount) }}<br>
                                    {{ inr($invoice->total_sgst_amount) }}<br>
                                @endif
                                @php
                                    if (isset($invoice->round_of) && $invoice->round_of !== null) {
                                        $roundOff = (float)$invoice->round_of;
                                    } else {
                                        $calculatedTotal = $invoice->total_amount + ($invoice->tax_type === 'inter' ? ($invoice->total_igst_amount ?? $invoice->total_gst_amount) : ($invoice->total_cgst_amount + $invoice->total_sgst_amount));
                                        $finalRoundedAmount = round($calculatedTotal);
                                        $roundOff = $finalRoundedAmount - $calculatedTotal;
                                    }
                                @endphp
                                {{ $roundOff < 0 ? '(-)' : '+' }}{{ inr(abs($roundOff)) }}
                            </td>
                        </tr>

                        <!-- Total Row -->
                        <tr class="border-bottom-black border-top-black">
                            <td colspan="3" class="text-end fw-bold-custom">Total</td>
                            <td class="fw-bold-custom">{{ number_format($invoice->total_quantity, 3) }} MT</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="fw-bold-custom text-end fs-6">Rs
                                {{ inr($invoice->chargeable_amount) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Amount Chargeable in words -->
                <div class="p-2 border-bottom-black d-flex justify-content-between align-items-center">
                    <div>
                        <div class="title-cell">Amount Chargeable (in words)</div>
                        <div class="fw-bold-custom" style="font-size: 14px;">{{ $amountInWords }}</div>
                    </div>
                    <div class="fst-italic fw-bold-custom">
                        E. & O.E
                    </div>
                </div>

                <!-- Tax Summary Table -->
                @php
                    $isInter = ($invoice->tax_type ?? 'intra') === 'inter';
                @endphp
                <table class="table-custom border-top-0 border-start-0 border-end-0">
                    <thead>
                        @if($isInter)
                            <tr>
                                <th rowspan="2" class="text-center align-middle" width="40%">HSN/SAC</th>
                                <th rowspan="2" class="text-center align-middle" width="20%">Taxable Value</th>
                                <th colspan="2" class="text-center border-bottom-black">IGST</th>
                                <th rowspan="2" class="text-center align-middle" width="20%">Total Tax Amount</th>
                            </tr>
                            <tr>
                                <th class="text-center">Rate</th>
                                <th class="text-center">Amount</th>
                            </tr>
                        @else
                            <tr>
                                <th rowspan="2" class="text-center align-middle" width="30%">HSN/SAC</th>
                                <th rowspan="2" class="text-center align-middle" width="15%">Taxable Value</th>
                                <th colspan="2" class="text-center border-bottom-black">CGST</th>
                                <th colspan="2" class="text-center border-bottom-black">SGST/UTGST</th>
                                <th rowspan="2" class="text-center align-middle" width="15%">Total Tax Amount</th>
                            </tr>
                            <tr>
                                <th class="text-center">Rate</th>
                                <th class="text-center">Amount</th>
                                <th class="text-center">Rate</th>
                                <th class="text-center">Amount</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @if($isInter)
                            <tr>
                                <td>As per Invoice Details</td>
                                <td class="text-end">{{ inr($invoice->total_amount) }}</td>
                                <td class="text-center">{{ $invoice->gst ?? 18 }}%</td>
                                <td class="text-end">{{ inr($invoice->total_igst_amount ?? $invoice->total_gst_amount) }}</td>
                                <td class="text-end">{{ inr($invoice->total_gst_amount) }}</td>
                            </tr>
                            <tr class="border-bottom-black border-top-black">
                                <td class="text-end fw-bold-custom">Total</td>
                                <td class="text-end fw-bold-custom">{{ inr($invoice->total_amount) }}</td>
                                <td></td>
                                <td class="text-end fw-bold-custom">{{ inr($invoice->total_igst_amount ?? $invoice->total_gst_amount) }}</td>
                                <td class="text-end fw-bold-custom">{{ inr($invoice->total_gst_amount) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td>As per Invoice Details</td>
                                <td class="text-end">{{ inr($invoice->total_amount) }}</td>
                                <td class="text-center">{{ ($invoice->gst ?? 18) / 2 }}%</td>
                                <td class="text-end">{{ inr($invoice->total_cgst_amount) }}</td>
                                <td class="text-center">{{ ($invoice->gst ?? 18) / 2 }}%</td>
                                <td class="text-end">{{ inr($invoice->total_sgst_amount) }}</td>
                                <td class="text-end">{{ inr($invoice->total_gst_amount) }}</td>
                            </tr>
                            <tr class="border-bottom-black border-top-black">
                                <td class="text-end fw-bold-custom">Total</td>
                                <td class="text-end fw-bold-custom">{{ inr($invoice->total_amount) }}</td>
                                <td></td>
                                <td class="text-end fw-bold-custom">{{ inr($invoice->total_cgst_amount) }}</td>
                                <td></td>
                                <td class="text-end fw-bold-custom">{{ inr($invoice->total_sgst_amount) }}</td>
                                <td class="text-end fw-bold-custom">{{ inr($invoice->total_gst_amount) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <!-- Tax Amount in words -->
                <div class="p-2 border-bottom-black d-flex">
                    <span class="me-2">Tax Amount (in words) :</span>
                    <span class="fw-bold-custom">{{ $taxAmountInWords }}</span>
                </div>

                <!-- Footer Section (Declaration, Bank, Signature) -->
                <div class="row g-0">
                    <!-- Left Side: Declaration & Terms -->
                    <div class="col-6 border-end-black p-2 d-flex flex-column">
                        <div class="mb-3">
                            <span class="text-decoration-underline fw-bold-custom">Declaration</span>
                            <p>We declare that this invoice shows the actual price of the goods described and that all
                                particulars are true and correct</p>
                        </div>
                        <div>
                            <span class="fw-bold-custom">TERMS & CONDITION:</span>
                            <p>1. Goods once sold cannot be taken back or exchange.</p>
                        </div>

                        <div class="mt-auto text-center fw-bold-custom small">
                            <br><br>
                            SUBJECT TO GUWAHATI JURISDICTION<br>
                            This is a Computer Generated Invoice
                        </div>
                    </div>

                    <!-- Right Side: Bank & Signature -->
                    <div class="col-6 p-2 d-flex flex-column">
                        <div>
                            <span class="text-decoration-underline fw-bold-custom">Company's Bank Details</span>
                            @php
                                $bankDetail = $invoice->bankDetail ?? ($invoice->createdBy->company->bankDetail ?? null);
                            @endphp
                            <table style="width: 100%;">
                                <tr>
                                    <td width="35%">A/c Holder's Name</td>
                                    <td>: <b>{{ $bankDetail->account_holder_name ?? ($invoice->createdBy->company->company_name ?? 'N/A') }}</b></td>
                                </tr>
                                <tr>
                                    <td>Bank Name</td>
                                    <td>: <b>{{ $bankDetail->bank_name ?? 'N/A' }}</b></td>
                                </tr>
                                <tr>
                                    <td>A/c No.</td>
                                    <td>: <b>{{ $bankDetail->account_no ?? 'N/A' }}</b></td>
                                </tr>
                                <tr>
                                    <td>Branch & IFS Code</td>
                                    <td>: <b>{{ $bankDetail->ifsc_code ?? 'N/A' }}@if(!empty($bankDetail->branch_name)) ({{ $bankDetail->branch_name }})@endif</b></td>
                                </tr>
                                <tr>
                                    <td>SWIFT Code</td>
                                    <td>: <b>{{ $bankDetail->swift_code ?? '' }}</b></td>
                                </tr>
                            </table>
                        </div>

                        <div class="mt-auto text-end pe-3 pb-2 pt-5">
                            <span class="fw-bold-custom d-block mb-5">for
                                {{ $invoice->createdBy->company->company_name ?? 'N/A' }}</span>
                            <span class="small">Authorised Signatory</span>
                        </div>
                    </div>
                </div>

            </div>
            </div>
        @endif
    </div>
@endsection
