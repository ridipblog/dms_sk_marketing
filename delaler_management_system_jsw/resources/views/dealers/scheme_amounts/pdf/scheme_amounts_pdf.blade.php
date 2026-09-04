<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Dealer Scheme Amounts Report</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .header h1 { margin: 0 0 4px 0; color: #0d6efd; font-size: 18px; text-transform: uppercase; font-weight: bold; }
        .header h2 { margin: 0; color: #333; font-size: 14px; }
        .header p { margin: 4px 0 0 0; color: #666; font-size: 10px; }
        .meta-container { width: 100%; margin-bottom: 15px; border: 1px solid #cbd5e1; padding: 8px 12px; background-color: #f8fafc; border-radius: 4px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { border: none; padding: 3px 6px; font-size: 10px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 9px; }
        table.data-table th { background-color: #0d6efd; color: #ffffff; font-weight: bold; text-transform: uppercase; }
        table.data-table tr:nth-child(even) { background-color: #f8fafc; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .footer { margin-top: 20px; text-align: right; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $company->company_name ?? 'COMPANY NAME' }}</h1>
        <h2>Dealer Scheme Amounts Report</h2>
        <p>Generated on {{ date('d-M-Y h:i A') }}</p>
    </div>

    <div class="meta-container">
        <table class="meta-table">
            <tr>
                <td style="width: 50%;">
                    <strong>Dealer Filter:</strong> 
                    {{ $selectedDealer ? '[' . ($selectedDealer->dealer->dealer_code ?? '') . '] ' . ($selectedDealer->dealer->dealer_name ?? '') : 'All Dealers' }}
                </td>
                <td style="width: 50%;">
                    <strong>Total Records:</strong> {{ count($schemeAmounts) }}
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Total Quantity:</strong> {{ number_format($totalQuantity, 3) }} MT
                </td>
                <td>
                    <strong>Total Scheme Amount:</strong> ₹{{ number_format($totalAmount, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 15%;">Dealer Code</th>
                <th style="width: 25%;">Dealer Name</th>
                <th style="width: 12%;">Month / Year</th>
                <th style="width: 12%;" class="text-end">Quantity (MT)</th>
                <th style="width: 13%;" class="text-end">Rate / MT (₹)</th>
                <th style="width: 18%;" class="text-end">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $months = [
                    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                ];
            @endphp
            @forelse($schemeAmounts as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->dealerCompany->dealer->dealer_code ?? 'N/A' }}</td>
                    <td><strong style="color: #1e293b;">{{ $item->dealerCompany->dealer->dealer_name ?? 'N/A' }}</strong></td>
                    <td>{{ $months[$item->month] ?? $item->month }} {{ $item->year }}</td>
                    <td class="text-end">{{ number_format($item->quantity, 3) }} MT</td>
                    <td class="text-end">₹{{ number_format($item->rate_per_mt, 2) }}</td>
                    <td class="text-end fw-bold" style="color: #0d6efd;">₹{{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No scheme amount records found.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($schemeAmounts) > 0)
            <tfoot>
                <tr style="background-color: #e2e8f0; font-weight: bold;">
                    <td colspan="4" class="text-end">Total:</td>
                    <td class="text-end">{{ number_format($totalQuantity, 3) }} MT</td>
                    <td></td>
                    <td class="text-end" style="color: #0d6efd;">₹{{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Dealer Scheme Amounts Report &bull; Dealer Management System
    </div>

</body>
</html>
