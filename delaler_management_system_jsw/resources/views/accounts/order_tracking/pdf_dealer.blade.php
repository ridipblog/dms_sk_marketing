<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dealer Wise PDF</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #0056b3; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; color: #666; }
        .info-section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; color: #333; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-box { float: right; width: 40%; border: 1px solid #0056b3; padding: 10px; background-color: #f9f9f9; }
        .summary-box table { border: none; margin-bottom: 0; }
        .summary-box th, .summary-box td { border: none; padding: 5px; }
        .summary-box th { width: 60%; text-align: left; background-color: transparent; }
        .summary-box td { text-align: right; font-weight: bold; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Dealer Payment Track Report</h2>
        <p>Comprehensive transaction history for the dealer.</p>
    </div>

    <div class="info-section">
        <strong>Dealer Company:</strong> {{ $companyName }}<br>
        <strong>Report Date:</strong> {{ date('d M Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Order No</th>
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
                <td>{{ $track['order_no'] }}</td>
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
                    <th>Opening Balance:</th>
                    <td>{{ number_format($openingBalance, 2) }}</td>
                </tr>
                <tr>
                    <th>Closing Balance<br><small>(Sum of Outstanding)</small>:</th>
                    <td style="color: #d9534f;">{{ number_format($closingBalance, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
