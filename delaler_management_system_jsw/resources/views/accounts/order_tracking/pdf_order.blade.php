<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Wise Receipt</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #28a745; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #28a745; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; color: #666; }
        .info-section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; color: #333; font-weight: bold; }
        .text-right { text-align: right; }
        .summary-box { float: right; width: 40%; border: 1px solid #28a745; padding: 10px; background-color: #f9f9f9; }
        .summary-box table { border: none; margin-bottom: 0; }
        .summary-box th, .summary-box td { border: none; padding: 5px; }
        .summary-box th { width: 60%; text-align: left; background-color: transparent; }
        .summary-box td { text-align: right; font-weight: bold; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Order Payment Receipt</h2>
        <p>Transaction details for specific order.</p>
    </div>

    <div class="info-section">
        <strong>Order Number:</strong> {{ $orderNo }}<br>
        <strong>Receipt Generated:</strong> {{ date('d M Y, H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Transaction Date</th>
                <th>Transaction ID</th>
                <th>Mode</th>
                <th>Voucher Type</th>
                <th class="text-right">Received Amount</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit Amount</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dummyPaymentTracks as $track)
            <tr>
                <td>{{ date('d M Y, H:i', strtotime($track['transaction_date'])) }}</td>
                <td>{{ $track['transaction_id'] }}</td>
                <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $track['payment_mode']) }}</td>
                <td>{{ $track['voucher_type'] }}</td>
                <td class="text-right">{{ $track['received_amount'] ? number_format($track['received_amount'], 2) : '-' }}</td>
                <td class="text-right">{{ $track['debit'] ? number_format($track['debit'], 2) : '-' }}</td>
                <td class="text-right">{{ $track['credit_amount'] ? number_format($track['credit_amount'], 2) : '-' }}</td>
                <td class="text-right">{{ $track['balance'] ? number_format($track['balance'], 2) : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="summary-box">
            <table>
                <tr>
                    <th>Order Outstanding Balance:</th>
                    <td style="color: #d9534f;">{{ number_format($outstandingAmount, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
