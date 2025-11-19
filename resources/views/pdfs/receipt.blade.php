<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { color: #0b3d60; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { text-align: left; background: #f5f5f5; }
        .totals td { border: none; }
    </style>
</head>
<body>
    <h1>Payment Receipt</h1>
    <p><strong>Order #:</strong> {{ $order->order_number }}<br>
       <strong>Date:</strong> {{ $order->created_at->format('F j, Y') }}<br>
       <strong>Patient:</strong> {{ $order->user->full_name }}<br>
       <strong>Email:</strong> {{ $order->user->email }}</p>

    <table>
        <thead>
            <tr>
                <th>Test</th>
                <th>Code</th>
                <th style="text-align:right;">Price</th>
            </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->test_name }}</td>
                <td>{{ $item->test_code }}</td>
                <td style="text-align:right;">£{{ number_format($item->price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top: 10px;">
        <tr>
            <td style="text-align:right;">Subtotal:</td>
            <td style="text-align:right;">£{{ number_format($order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td style="text-align:right;">Tax:</td>
            <td style="text-align:right;">£{{ number_format($order->tax, 2) }}</td>
        </tr>
        <tr>
            <td style="text-align:right;"><strong>Total Paid:</strong></td>
            <td style="text-align:right;"><strong>£{{ number_format($order->total, 2) }}</strong></td>
        </tr>
    </table>

    <p style="margin-top: 20px;">Payment method: {{ ucfirst($order->payment_method ?? 'card') }}<br>
       Transaction ID: {{ $order->payment_intent_id }}</p>

    <p style="font-size: 11px; color:#555; margin-top: 30px;">Thank you for choosing {{ config('app.name') }}.</p>
</body>
</html>
