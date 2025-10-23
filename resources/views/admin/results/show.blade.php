{{-- resources/views/admin/results/show.blade.php --}}
@extends('layouts.admin')

@section('title', 'Result Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <a href="{{ route('admin.results.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Results
        </a>

        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Result for Order {{ $result->order->order_number }}</h1>
                <p class="text-gray-600 mt-1">Result received: {{ $result->result_date->format('F j, Y \a\t g:i A') }}</p>
            </div>
            <div class="flex space-x-3">
                @if($result->pdf_path)
                    <a href="{{ route('admin.results.download', $result) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Download PDF
                    </a>
                @endif
                @if(!$result->is_reviewed)
                    <a href="{{ route('admin.results.review-form', $result) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        Review Result
                    </a>
                @endif
            </div>
        </div>

        {{-- Critical Alert --}}
        @if($result->has_critical_values && !$result->is_reviewed)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-red-600 mt-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-4">
                        <h3 class="font-semibold text-red-900">Critical Values Detected - Review Required</h3>
                        <p class="text-sm text-red-800 mt-1">This result contains critical values that require immediate review before being released to the patient.</p>
                    </div>
                </div>
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
                                <div class="text-sm text-gray-600">Name</div>
                                <div class="font-medium text-gray-900 mt-1">{{ $result->order->user->full_name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Date of Birth</div>
                                <div class="font-medium text-gray-900 mt-1">
                                    {{ $result->order->user->date_of_birth->format('d/m/Y') }}
                                    <span class="text-sm text-gray-500">({{ $result->order->user->age }} years)</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Gender</div>
                                <div class="font-medium text-gray-900 mt-1">{{ ucfirst($result->order->user->gender ?? 'Not specified') }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Order Number</div>
                                <div class="font-medium text-gray-900 mt-1">
                                    <a href="{{ route('admin.orders.show', $result->order) }}" class="text-blue-600 hover:underline">
                                        {{ $result->order->order_number }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Test Results Summary --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Test Results Summary</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Total Tests</div>
                                <div class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total_tests'] }}</div>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Abnormal</div>
                                <div class="text-2xl font-bold text-yellow-600 mt-1">{{ $summary['abnormal_count'] }}</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Critical</div>
                                <div class="text-2xl font-bold text-red-600 mt-1">{{ $summary['critical_count'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Critical Tests --}}
                @if(count($criticalTests) > 0)
                <div class="bg-red-50 border-2 border-red-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-red-200 bg-red-100">
                        <h2 class="text-xl font-semibold text-red-900 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Critical Results
                        </h2>
                    </div>
                    <div class="p-6">
                        @foreach($criticalTests as $test)
                        <div class="bg-white border border-red-200 rounded-lg p-4 mb-3 last:mb-0">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">{{ $test['test_name'] }}</div>
                                    <div class="text-sm text-gray-600 mt-1">{{ $test['test_code'] }}</div>
                                </div>
                                <span class="px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full">
                                    {{ $test['flag'] }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <div class="text-gray-600">Result</div>
                                    <div class="font-bold text-red-900">{{ $test['value'] }} {{ $test['unit'] ?? '' }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-600">Reference Range</div>
                                    <div class="font-medium text-gray-900">{{ $test['reference_range'] ?? 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-600">Status</div>
                                    <div class="font-medium text-gray-900">{{ $test['status'] ?? 'Final' }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- All Test Results --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">All Test Results</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference Range</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flag</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($result->parsed_data['tests'] as $test)
                                <tr class="{{ isset($test['flag']) && in_array($test['flag'], ['H', 'L', 'HH', 'LL']) ? 'bg-yellow-50' : '' }}">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $test['test_name'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $test['test_code'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-900">
                                        {{ $test['value'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $test['unit'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $test['reference_range'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if(isset($test['flag']))
                                            @if(in_array($test['flag'], ['HH', 'LL']))
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                    {{ $test['flag'] === 'HH' ? '↑↑ Critical High' : '↓↓ Critical Low' }}
                                                </span>
                                            @elseif($test['flag'] === 'H')
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">
                                                    ↑ High
                                                </span>
                                            @elseif($test['flag'] === 'L')
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                    ↓ Low
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                    {{ $test['flag'] }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                Normal
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Review Notes --}}
                @if($result->is_reviewed && $result->reviewer_notes)
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Reviewer Notes</h2>
                    </div>
                    <div class="p-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-gray-700">{{ $result->reviewer_notes }}</p>
                            <div class="mt-3 text-sm text-gray-600">
                                Reviewed by {{ $result->reviewer->full_name }} on {{ $result->reviewed_at->format('F j, Y \a\t g:i A') }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Status Card --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Status</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <div class="text-sm text-gray-600">Review Status</div>
                            <div class="mt-1">
                                @if($result->is_reviewed)
                                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800">
                                        ✓ Reviewed
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800">
                                        Pending Review
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($result->is_reviewed)
                        <div>
                            <div class="text-sm text-gray-600">Reviewed By</div>
                            <div class="font-medium text-gray-900 mt-1">{{ $result->reviewer->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $result->reviewed_at->format('d/m/Y H:i') }}</div>
                        </div>
                        @endif

                        <div>
                            <div class="text-sm text-gray-600">Patient Notification</div>
                            <div class="mt-1">
                                @if($result->patient_notified_at)
                                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                                        ✓ Notified
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">{{ $result->patient_notified_at->diffForHumans() }}</div>
                                @else
                                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-gray-100 text-gray-800">
                                        Not notified
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($result->patient_viewed_at)
                        <div>
                            <div class="text-sm text-gray-600">Patient Viewed</div>
                            <div class="text-sm text-gray-900 mt-1">{{ $result->patient_viewed_at->format('d/m/Y H:i') }}</div>
                        </div>
                        @endif

                        <div>
                            <div class="text-sm text-gray-600">Result Date</div>
                            <div class="text-sm text-gray-900 mt-1">{{ $result->result_date->format('d/m/Y H:i') }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-600">Performing Lab</div>
                            <div class="text-sm text-gray-900 mt-1">{{ $result->getPerformingLab() }}</div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Actions</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        @if(!$result->is_reviewed)
                            <a href="{{ route('admin.results.review-form', $result) }}" class="block w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-center">
                                Review Result
                            </a>
                        @endif

                        @if($result->pdf_path)
                            <a href="{{ route('admin.results.download', $result) }}" class="block w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">
                                Download PDF
                            </a>
                        @else
                            <form action="{{ route('admin.results.regenerate-pdf', $result) }}" method="POST">
                                @csrf
                                <button type="submit" class="block w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">
                                    Generate PDF
                                </button>
                            </form>
                        @endif

                        @if($result->is_reviewed && !$result->patient_notified_at)
                            <form action="{{ route('admin.results.notify-patient', $result) }}" method="POST">
                                @csrf
                                <button type="submit" class="block w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-center">
                                    Notify Patient
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.orders.show', $result->order) }}" class="block w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-center">
                            View Order
                        </a>

                        <a href="{{ route('admin.results.raw', $result) }}" class="block w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-center text-sm">
                            View Raw Data
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection