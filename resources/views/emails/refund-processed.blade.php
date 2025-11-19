@component('mail::message')
# Your refund is complete

Hi {{ $user->first_name }},

We have issued a refund for order **{{ $order->order_number }}**. The funds should return to your original payment method within 5–10 business days depending on your bank.

@component('mail::panel')
**Amount refunded:** £{{ number_format($order->total, 2) }}  \\
**Payment method:** {{ ucfirst($order->payment_method ?? 'card') }}  \\
**Refund date:** {{ now()->format('F j, Y') }}
@endcomponent

If you do not see the credit after 10 days, please reply to this email and we will investigate immediately.

Thanks,
{{ config('app.name') }}
@endcomponent
