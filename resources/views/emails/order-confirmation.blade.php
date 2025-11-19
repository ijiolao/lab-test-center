@component('mail::message')
# Order Confirmation

Hi {{ $user->first_name }},

Thank you for scheduling your laboratory tests. Here are the details for order **{{ $order->order_number }}** placed on {{ $order->created_at->format('F j, Y') }}.

@component('mail::panel')
**Collection date:** {{ optional($order->collection_date)->format('F j, Y') ?? 'TBD' }}

**Collection time:** {{ optional($order->collection_time)->format('H:i') ?? 'TBD' }}

**Location:** {{ $order->collection_location ?? 'Clinic' }}
@endcomponent

@component('mail::table')
| Test | Code | Price |
| :---- | :---- | ---: |
@foreach($items as $item)
| {{ $item->test_name }} | {{ $item->test_code }} | £{{ number_format($item->price, 2) }} |
@endforeach
@endcomponent

**Subtotal:** £{{ number_format($order->subtotal, 2) }}  \\
**Tax:** £{{ number_format($order->tax, 2) }}  \\
**Total:** £{{ number_format($order->total, 2) }}

We'll send another message once your results are ready. You can review this order any time from the patient portal.

Thanks,
{{ config('app.name') }}
@endcomponent
