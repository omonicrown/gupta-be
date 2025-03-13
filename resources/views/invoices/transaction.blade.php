<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $transaction->reference }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-title {
            font-size: 28px;
            color: #0071BC;
        }
        .company-details {
            margin-bottom: 20px;
        }
        .invoice-details, .user-details {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background-color: #f8f8f8;
        }
        .amount {
            font-weight: bold;
            font-size: 18px;
            color: #0071BC;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="invoice-title">INVOICE</div>
        <div>{{ date('Y-m-d') }}</div>
    </div>
    
    <div class="company-details">
        <strong>Your Company Name</strong><br>
        123 Business Street<br>
        City, Country<br>
        Email: support@yourcompany.com
    </div>
    
    <div class="user-details">
        <strong>Billed To:</strong><br>
        {{ $user->name }}<br>
        {{ $user->email }}<br>
        {{ $user->phone_number ?? '' }}
    </div>
    
    <div class="invoice-details">
        <strong>Invoice #:</strong> {{ $transaction->reference }}<br>
        <strong>Date:</strong> {{ date('Y-m-d', strtotime($transaction->created_at)) }}<br>
        <strong>Payment Method:</strong> {{ ucfirst($transaction->payment_method) }}
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Type</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $transaction->description ?? 'SMS Wallet Funding' }}</td>
                <td>{{ ucfirst($transaction->type) }}</td>
                <td>₦{{ number_format($transaction->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>
    
    <div class="amount">
        Total: ₦{{ number_format($transaction->amount, 2) }}
    </div>
    
    <div class="footer">
        Thank you for your business!<br>
        For questions about this invoice, please contact support@yourcompany.com
    </div>
</body>
</html>