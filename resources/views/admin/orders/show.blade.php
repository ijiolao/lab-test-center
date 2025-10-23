{{-- resources/views/admin/orders/show.blade.php --}}
@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="text-blue-600 hover:underline text-sm mb-2 inline-block">
                ← Back to Orders
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Order {{ $order->order_number }}</h1>
        </div>
        <div class="flex items-center space-x-3">
            @if($order->can_be_printed)
                <form action="{{ route('admin.orders.print', $order) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print Label
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Patient Information --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Patient Information</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm text-gray-600">Name</label>
                            <div class="font-medium text-gray-900 mt-1">
                                {{ $order->user->full_name }}
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Date of Birth</label>
                            <div class="font-medium text-gray-900 mt-1">
                                {{ $order->user->date_of_birth->format('d/m/Y') }}
                                <span class="text-sm text-gray-500">({{ $order->user->age }} years old)</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Email</label>
                            <div class="font-medium text-gray-900 mt-1">
                                <a href="mailto:{{ $order->user->email }}" class="text-blue-600 hover:underline">
                                    {{ $order->user->email }}
                                </a>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Phone</label>
                            <div class="font-medium text-gray-900 mt-1">
                                @if($order->user->phone)
                                    <a href="tel:{{ $order->user->phone }}" class="text-blue-600 hover:underline">
                                        {{ $order->user->phone }}
                                    </a>
                                @else
                                    <span class="text-gray-400">Not provided</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Gender</label>
                            <div class="font-medium text-gray-900 mt-1">
                                {{ $order->user->gender ? ucfirst($order->user->gender) : 'Not specified' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tests Ordered --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Tests Ordered</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specimen Barcode</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($order->items as $item)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $item->test_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $item->test->specimen_type }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $item->test_code }}
                                </td>
                                <td class="px-6 py-4">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded font-mono">
                                        {{ $item->specimen_barcode }}
                                    </code>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900">
                                    {{ $item->formatted_price }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-900">Subtotal:</td>
                                <td class="px-6 py-3 text-right font-medium text-gray-900">{{ $order->formatted_subtotal }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-900">Tax (20%):</td>
                                <td class="px-6 py-3 text-right font-medium text-gray-900">{{ $order->formatted_tax }}</td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total:</td>
                                <td class="px-6 py-3 text-right text-lg font-bold text-gray-900">{{ $order->formatted_total }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Lab Submissions --}}
            @if($order->labSubmissions->count() > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Lab Submissions</h2>
                </div>
                <div class="p-6 space-y-4">
                    @foreach($order->labSubmissions as $submission)
                    <div class="border-l-4 border-{{ $submission->status_color }}-500 pl-4 py-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $submission->labPartner->name }}</div>
                                @if($submission->lab_order_id)
                                    <div class="text-sm text-gray-600 mt-1">
                                        Lab Order ID: <code class="bg-gray-100 px-2 py-0.5 rounded">{{ $submission->lab_order_id }}</code>
                                    </div>
                                @endif
                                @if($submission->submitted_at)
                                    <div class="text-sm text-gray-500 mt-1">
                                        Submitted: {{ $submission->submitted_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                                @if($submission->error_message)
                                    <div class="text-sm text-red-600 mt-2">
                                        Error: {{ $submission->error_message }}
                                    </div>
                                @endif
                            </div>
                            <span class="px-3 py-1 text-sm rounded-full bg-{{ $submission->status_color }}-100 text-{{ $submission->status_color }}-800">
                                {{ $submission->status_display_name }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Result --}}
            @if($order->result)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Result</h2>
                </div>
                <div class="p-6">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3 flex-1">
                                <div class="font-medium text-green-900">Result Available</div>
                                <div class="text-sm text-green-700 mt-1">
                                    Received: {{ $order->result->result_date->format('d/m/Y H:i') }}
                                </div>
                                @if($order->result->patient_notified_at)
                                    <div class="text-sm text-green-700">
                                        Patient notified: {{ $order->result->patient_notified_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                                @if($order->result->patient_viewed_at)
                                    <div class="text-sm text-green-700">
                                        Viewed by patient: {{ $order->result->patient_viewed_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                                @if($order->result->has_critical_values)
                                    <div class="mt-2 px-3 py-1 bg-red-100 text-red-800 rounded inline-block text-sm font-medium">
                                        ⚠️ Contains Critical Values
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4">
                                <a href="{{ route('admin.results.show', $order->result) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                                    View Result
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Activity Log --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Activity Log</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($activityLog as $activity)
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm text-gray-700">{{ $activity->description }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    @if($activity->causer)
                                        {{ $activity->causer->full_name }} •
                                    @endif
                                    {{ $activity->created_at->format('d/m/Y H:i:s') }}
                                </p>
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-500 text-sm">No activity recorded</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Order Status</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-sm text-gray-600">Current Status</label>
                        <div class="mt-1">
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800">
                                {{ $order->getStatusDisplayName() }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Payment Status</label>
                        <div class="mt-1">
                            <span class="px-3 py-1 text-sm font-medium rounded-full 
                                {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Collection Date</label>
                        <div class="font-medium text-gray-900 mt-1">
                            {{ $order->collection_date->format('d/m/Y') }}
                            @if($order->collection_time)
                                at {{ $order->collection_time->format('H:i') }}
                            @endif
                        </div>
                    </div>
                    @if($order->collection_location)
                    <div>
                        <label class="text-sm text-gray-600">Collection Location</label>
                        <div class="font-medium text-gray-900 mt-1">
                            {{ $order->collection_location }}
                        </div>
                    </div>
                    @endif
                    @if($order->collected_at)
                    <div>
                        <label class="text-sm text-gray-600">Collected At</label>
                        <div class="font-medium text-gray-900 mt-1">
                            {{ $order->collected_at->format('d/m/Y H:i') }}
                        </div>
                        @if($order->collectedBy)
                            <div class="text-sm text-gray-500">
                                By: {{ $order->collectedBy->full_name }}
                            </div>
                        @endif
                    </div>
                    @endif
                    @if($order->special_instructions)
                    <div>
                        <label class="text-sm text-gray-600">Special Instructions</label>
                        <div class="text-sm text-gray-900 mt-1 bg-yellow-50 border border-yellow-200 rounded p-3">
                            {{ $order->special_instructions }}
                        </div>
                    </div>
                    @endif
                    <div>
                        <label class="text-sm text-gray-600">Created</label>
                        <div class="text-sm text-gray-900 mt-1">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    @if($order->can_be_printed)
                    <form action="{{ route('admin.orders.print', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            Print Specimen Label
                        </button>
                    </form>
                    @endif

                    @if(in_array($order->status, ['paid', 'scheduled']) && !$order->collected_at)
                    <form action="{{ route('admin.orders.collect', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Mark as Collected
                        </button>
                    </form>
                    @endif

                    @if($order->canBeSubmittedToLab())
                    <form action="{{ route('admin.orders.submit', $order) }}" method="POST">
                        @csrf
                        <label class="text-sm text-gray-700 mb-2 block">Select Lab Partner</label>
                        <select name="lab_partner_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-3 focus:ring-2 focus:ring-purple-500">
                            <option value="">Choose lab...</option>
                            @foreach($labPartners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Submit to Lab
                        </button>
                    </form>
                    @endif

                    @if($order->canBeCancelled())
                    <button onclick="if(confirm('Are you sure you want to cancel this order?')) document.getElementById('cancel-form').submit()" class="w-full bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200">
                        Cancel Order
                    </button>
                    <form id="cancel-form" action="{{ route('admin.orders.cancel', $order) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="reason" value="Cancelled by admin">
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection