<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger Account - {{ isset($dealer) ? $dealer->dealer_name : 'Dealer' }}</title>
    <!-- Use standard Bootstrap for simple styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            color: #000;
        }

        .ledger-container {
            max-width: 900px;
            margin: 2rem auto;
            background: #fff;
            padding: 2rem 3rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header-text {
            text-align: center;
            line-height: 1.4;
            margin-bottom: 2rem;
        }

        .header-text h3 {
            font-weight: bold;
            margin-bottom: 0.2rem;
            font-size: 1.3rem;
        }

        .header-text p {
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .table-ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .table-ledger th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 8px 5px;
            font-weight: bold;
        }

        .table-ledger td {
            padding: 6px 5px;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-weight-bold {
            font-weight: bold;
        }

        .total-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }

        .grand-total-row td {
            border-bottom: 2px solid #000;
            font-weight: bold;
        }

        @media print {
            body {
                background: none;
                margin: 0;
            }

            .ledger-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    @if (isset($errorMessage))
        <div class="container mt-4 no-print">
            <x-error-alert :message="$errorMessage" />
        </div>
    @else
        <div class="container d-flex justify-content-between align-items-center mt-3 d-print-none">
            <a href="{{ route('accounts.dealer_statement.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Filters
            </a>
            <div>
                <button onclick="window.print()" class="btn btn-primary me-2">
                    <i class="fas fa-print"></i> Print Ledger
                </button>
                <button onclick="window.print()" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Save PDF
                </button>
            </div>
        </div>

        <div class="ledger-container">
            <div class="header-text">
                <h3>{{ strtoupper($company->company_name ?? 'COMPANY NAME') }}</h3>
                <p>{{ $company->address ?? 'Company Address' }}</p>
                <p>Ph: {{ $company->phone ?? 'N/A' }}{{ $company->email ? ' | Email: ' . $company->email : '' }}</p>
                @if(isset($company->gst_no) && $company->gst_no)
                    <p>GSTIN: {{ $company->gst_no }}</p>
                @endif

                <h4 class="font-weight-bold mt-4" style="font-size: 1.1rem;">
                    {{ $dealer->dealer_name ?? 'N/A' }}</h4>
                <p>Ledger Account</p>
                <p>{{ $dealer->address ?? 'N/A' }}<br>
                    {{ $dealer->city ?? 'Guwahati' }}, {{ $dealer->state ?? 'Assam' }}</p>

                <p class="font-weight-bold mt-3">{{ \Carbon\Carbon::parse($startDate)->format('j-M-y') }} to
                    {{ \Carbon\Carbon::parse($endDate)->format('j-M-y') }}</p>
            </div>

            <table class="table-ledger">
                <thead>
                    <tr>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 35%;">Particulars</th>
                        <th style="width: 15%;">Vch Type</th>
                        <th style="width: 18%;">Vch No.</th>
                        <th class="text-right" style="width: 10%;">Debit</th>
                        <th class="text-right" style="width: 10%;">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerData as $fyGroup)
                        @if ($loop->index > 0 || $fyGroup['opening_balance'] != 0)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($fyGroup['start_date'])->format('j-M-y') }}</td>
                                @if ($fyGroup['opening_balance'] > 0)
                                    <td class="font-weight-bold">To Opening Balance</td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right font-weight-bold">
                                        {{ inr($fyGroup['opening_balance']) }}</td>
                                    <td class="text-right"></td>
                                @else
                                    <td class="font-weight-bold">By Opening Balance</td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right"></td>
                                    <td class="text-right font-weight-bold">
                                        {{ inr(abs($fyGroup['opening_balance'])) }}</td>
                                @endif
                            </tr>
                        @endif

                        @foreach ($fyGroup['transactions'] as $txn)
                            <tr>
                                <td>{{ $txn['date'] }}</td>
                                <td>{!! $txn['particulars'] !!}</td>
                                <td>{{ $txn['vch_type'] }}</td>
                                <td>{{ $txn['vch_no'] }}</td>
                                <td class="text-right">{{ $txn['debit'] > 0 ? inr($txn['debit']) : '' }}
                                </td>
                                <td class="text-right">{{ $txn['credit'] > 0 ? inr($txn['credit']) : '' }}
                                </td>
                            </tr>
                        @endforeach

                        @php
                            $sumDebit = $fyGroup['total_debit'] + ($fyGroup['opening_balance'] > 0 ? $fyGroup['opening_balance'] : 0);
                            $sumCredit = $fyGroup['total_credit'] + ($fyGroup['opening_balance'] < 0 ? abs($fyGroup['opening_balance']) : 0);
                        @endphp

                        <!-- Total Before Closing Balance -->
                        <tr>
                            <td colspan="4" class="text-right pe-3 font-weight-bold">Total</td>
                            <td class="text-right font-weight-bold" style="border-top: 1px solid #000;">
                                {{ inr($sumDebit) }}
                            </td>
                            <td class="text-right font-weight-bold" style="border-top: 1px solid #000;">
                                {{ inr($sumCredit) }}
                            </td>
                        </tr>

                        <!-- Closing Balance Row -->
                        <tr>
                            <td></td>
                            @if ($fyGroup['closing_balance'] > 0)
                                <td class="font-weight-bold" style="padding-left: 2rem;">By Closing Balance</td>
                                <td></td>
                                <td></td>
                                <td class="text-right"></td>
                                <td class="text-right font-weight-bold">
                                    {{ inr($fyGroup['closing_balance']) }}</td>
                            @elseif($fyGroup['closing_balance'] < 0)
                                <td class="font-weight-bold" style="padding-left: 2rem;">To Closing Balance</td>
                                <td></td>
                                <td></td>
                                <td class="text-right font-weight-bold">
                                    {{ inr(abs($fyGroup['closing_balance'])) }}</td>
                                <td class="text-right"></td>
                            @else
                                <td class="font-weight-bold" style="padding-left: 2rem;">By Closing Balance</td>
                                <td></td>
                                <td></td>
                                <td class="text-right font-weight-bold">0.00</td>
                                <td class="text-right font-weight-bold">0.00</td>
                            @endif
                        </tr>

                        @php
                            $grandTotal = max(
                                $fyGroup['total_debit'] +
                                    ($fyGroup['opening_balance'] > 0 ? $fyGroup['opening_balance'] : 0),
                                $fyGroup['total_credit'] +
                                    ($fyGroup['opening_balance'] < 0 ? abs($fyGroup['opening_balance']) : 0),
                            );
                        @endphp

                        <!-- Grand Total Row -->
                        <tr class="grand-total-row">
                            <td colspan="4" class="text-right pe-3 font-weight-bold">Sum</td>
                            <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
                            <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">No transactions found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>

</html>
