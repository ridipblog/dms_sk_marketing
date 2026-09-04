<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Account Statement - Invoice #{{ $invoice->invoice_no }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #0056b3; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 4px 0 0 0; color: #666; font-size: 10px; }
        .meta-container { width: 100%; margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; background-color: #fcfcfc; border-radius: 4px; }
        .meta-table { width: 100%; border-collapse: collapse; border: none; }
        .meta-table td { border: none; padding: 4px 8px; font-size: 10px; }
        .meta-table td strong { color: #111; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 6px 7px; text-align: left; font-size: 9px; }
        table.data-table th { background-color: #0056b3; color: #ffffff; font-weight: bold; text-transform: uppercase; }
        table.data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .badge-voucher { font-weight: bold; color: #0056b3; }
        .text-success { color: #198754; font-weight: bold; }
        .text-danger { color: #dc3545; font-weight: bold; }
        .footer { margin-top: 25px; text-align: right; font-size: 9px; color: #777; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h1 style="margin: 0 0 6px 0; color: #0056b3; font-size: 18px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">
            {{ $invoice->createdBy->company->company_name ?? '' }}
        </h1>
        <h2>Account Statement</h2>
        <p>Invoice #{{ $invoice->invoice_no }} &bull; Generated on {{ date('d-M-Y h:i A') }}</p>
    </div>

    <div class="meta-container">
        <table class="meta-table">
            <tr>
                <td style="width: 50%;"><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</td>
                <td style="width: 50%;"><strong>Dealer Name:</strong> {{ $invoice->buyer->dealer->dealer_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Invoice Date:</strong> {{ $invoice->invoice_generate_date ? \Carbon\Carbon::parse($invoice->invoice_generate_date)->format('d-M-Y') : 'N/A' }}</td>
                <td><strong>Dealer Code:</strong> {{ $invoice->buyer->dealer->dealer_code ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Chargeable Amount:</strong> ₹{{ number_format($invoice->chargeable_amount ?? 0, 2) }}</td>
                <td><strong>Outstanding Balance:</strong> ₹{{ number_format($invoice->invoicePayment->outstanding_amount ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 18%;">Transaction ID</th>
                <th style="width: 15%;">Voucher Type</th>
                <th style="width: 12%;">Mode</th>
                <th style="width: 16%;">Date</th>
                <th style="width: 14%;" class="text-end">Amount (₹)</th>
                <th style="width: 20%;" class="text-end">Balance (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vouchers as $index => $voucher)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong class="badge-voucher">{{ $voucher->transaction_id ?? 'N/A' }}</strong></td>
                    <td>{{ $voucher->voucherTypeModel->name ?? 'Sales' }}</td>
                    <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $voucher->payment_mode ?? '-') }}</td>
                    <td>{{ $voucher->transaction_date ? \Carbon\Carbon::parse($voucher->transaction_date)->format('d-M-Y h:i A') : '-' }}</td>
                    <td class="text-end text-success">₹{{ number_format($voucher->amount, 2) }}</td>
                    <td class="text-end">₹{{ number_format($voucher->balance_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No voucher records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Page 1 &bull; Auto-generated Account Statement &bull; Dealer Management System
    </div>

</body>
</html>
