@component('mail::message')
# Your test results are ready

Hi {{ $user->first_name }},

We have finished processing your laboratory tests for order **{{ $order->order_number }}** dated {{ optional($result->result_date)->format('F j, Y') ?? 'recently' }}.

@component('mail::panel')
@if($hasCriticalValues)
**Important:** One or more values were flagged as critical. A clinician will review them before they are released.
@else
You can now sign in to the patient portal to review your full report and download a PDF copy for your records.
@endif
@endcomponent

@component('mail::button', ['url' => route('patient.results.show', $result)])
View Results
@endcomponent

If you have any questions, reply to this email or contact our care team.

Thanks,
{{ config('app.name') }}
@endcomponent
