<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Result {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1, h2 { color: #0b3d60; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cccccc; padding: 6px; }
        th { background: #f2f6fb; text-align: left; }
        .meta { margin-bottom: 12px; }
        .meta span { display: inline-block; min-width: 180px; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} - Laboratory Result</h1>

    <div class="meta">
        <span><strong>Order #:</strong> {{ $order->order_number }}</span>
        <span><strong>Patient:</strong> {{ $user->full_name }}</span>
        <span><strong>DOB:</strong> {{ optional($user->date_of_birth)->format('F j, Y') }}</span>
    </div>

    <div class="meta">
        <span><strong>Collection date:</strong> {{ optional($order->collection_date)->format('F j, Y') ?? 'N/A' }}</span>
        <span><strong>Result date:</strong> {{ optional($result->result_date)->format('F j, Y') ?? 'Pending' }}</span>
        <span><strong>Performing lab:</strong> {{ $result->getPerformingLab() ?? 'Not specified' }}</span>
    </div>

    <h2>Test Results</h2>
    <table>
        <thead>
            <tr>
                <th>Test</th>
                <th>Value</th>
                <th>Unit</th>
                <th>Reference Range</th>
                <th>Flag</th>
            </tr>
        </thead>
        <tbody>
        @foreach($parsedData['tests'] ?? [] as $test)
            <tr>
                <td>{{ $test['test_name'] ?? $test['test_code'] ?? 'Test' }}</td>
                <td>{{ $test['value'] ?? 'N/A' }}</td>
                <td>{{ $test['unit'] ?? '' }}</td>
                <td>{{ $test['reference_range'] ?? '—' }}</td>
                <td>{{ $test['flag'] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if(!empty($parsedData['notes']))
        <h2>Notes</h2>
        <p>{{ $parsedData['notes'] }}</p>
    @endif

    <p style="margin-top: 30px; font-size: 11px; color: #555;">
        Generated on {{ now()->format('F j, Y H:i') }}.
    </p>
</body>
</html>
