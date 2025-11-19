@component('mail::message')
# Laboratory result shared with you

Hello {{ $recipientName }},

{{ $patient->full_name }} asked us to share their laboratory result (order **{{ $order->order_number }}**) with you. The full PDF report is attached for your review.

@if($messageBody)
@component('mail::panel')
**Message from the patient:**

{{ $messageBody }}
@endcomponent
@endif

@component('mail::panel')
- **Collection date:** {{ optional($order->collection_date)->format('F j, Y') ?? 'N/A' }}
- **Result date:** {{ optional($result->result_date)->format('F j, Y') ?? 'Pending' }}
- **Performing lab:** {{ $result->getPerformingLab() ?? 'Not specified' }}
@endcomponent

If you have questions about the attachment, contact {{ config('app.name') }} support at {{ config('mail.from.address') }}.

Regards,
{{ config('app.name') }}
@endcomponent
