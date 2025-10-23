{{-- resources/views/patient/results/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Test Results')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
        <a href="{{ route('patient.results.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to All Results
        </a>

        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Your Test Results</h1>
                <p class="text-gray-600 mt-1">Order {{ $result->order->order_number }}</p>
            </div>
            @if($result->pdf_path)
                <a href="{{ route('patient.results.download', $result) }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download PDF
                </a>
            @endif
        </div>

        {{-- Important Notice --}}
        @if($result->has_critical_values)
            <div class="bg-red-50 border-2 border-red-300 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <svg class="w-8 h-8 text-red-600 mt-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-red-900">Important: Please Review Carefully</h3>
                        <p class="text-red-800 mt-2">
                            Some of your test results are outside the normal range and may require attention. 
                            Please review these results with your healthcare provider as soon as possible.
                        </p>
                        <div class="mt-4">
                            <a href="tel:+441234567890" class="inline-block bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm">
                                Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Result Information --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Result Information</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="text-sm text-gray-600">Result Date</div>
                        <div class="font-medium text-gray-900 mt-1">{{ $result->result_date->format('F j, Y') }}</div>
                        <div class="text-xs text-gray-500">{{ $result->result_date->format('g:i A') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Collection Date</div>
                        <div class="font-medium text-gray-900 mt-1">{{ $result->order->collection_date->format('F j, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Number of Tests</div>
                        <div class="font-medium text-gray-900 mt-1">{{ $result->order->items->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Test Results --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Your Test Results</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($result->parsed_data['tests'] as $test)
                <div class="p-6 {{ isset($test['flag']) && in_array($test['flag'], ['H', 'L', 'HH', 'LL']) ? 'bg-yellow-50' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $test['test_name'] }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $test['test_code'] }}</p>
                        </div>
                        @if(isset($test['flag']))
                            @if(in_array($test['flag'], ['HH', 'LL']))
                                <span class="px-4 py-2 bg-red-100 text-red-800 text-sm font-bold rounded-full">
                                    {{ $test['flag'] === 'HH' ? '↑↑ Critical High' : '↓↓ Critical Low' }}
                                </span>
                            @elseif($test['flag'] === 'H')
                                <span class="px-4 py-2 bg-orange-100 text-orange-800 text-sm font-semibold rounded-full">
                                    ↑ High
                                </span>
                            @elseif($test['flag'] === 'L')
                                <span class="px-4 py-2 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                                    ↓ Low
                                </span>
                            @else
                                <span class="px-4 py-2 bg-yellow-100 text-yellow-800 text-sm font-semibold rounded-full">
                                    {{ $test['flag'] }}
                                </span>
                            @endif
                        @else
                            <span class="px-4 py-2 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                                ✓ Normal
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-600 uppercase">Your Result</div>
                            <div class="text-2xl font-bold text-gray-900 mt-1">
                                {{ $test['value'] }}
                                @if($test['unit'] ?? null)
                                    <span class="text-base font-normal text-gray-600">{{ $test['unit'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-600 uppercase">Reference Range</div>
                            <div class="text-lg font-medium text-gray-900 mt-1">
                                {{ $test['reference_range'] ?? 'N/A' }}
                                @if($test['unit'] ?? null)
                                    <span class="text-sm font-normal text-gray-600">{{ $test['unit'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-600 uppercase">Status</div>
                            <div class="text-lg font-medium text-gray-900 mt-1">
                                {{ $test['status'] ?? 'Final' }}
                            </div>
                        </div>
                    </div>

                    @if(isset($test['flag']) && in_array($test['flag'], ['H', 'L', 'HH', 'LL']))
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="ml-3 text-sm text-yellow-800">
                                    This result is outside the normal range. Please discuss this with your healthcare provider to understand what this means for your health.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Reviewer Notes --}}
        @if($result->is_reviewed && $result->reviewer_notes)
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Clinical Notes</h2>
            </div>
            <div class="p-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-gray-800">{{ $result->reviewer_notes }}</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Important Information --}}
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
            <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Understanding Your Results
            </h3>
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>Reference ranges are guidelines for typical healthy individuals and may vary based on age, gender, and other factors.</span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>Results outside the reference range don't always indicate a health problem and should be interpreted by your healthcare provider.</span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>These results have been reviewed by our qualified medical professionals before being released to you.</span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>If you have questions or concerns, please contact your doctor or our support team.</span>
                </li>
            </ul>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('patient.results.index') }}" class="text-gray-600 hover:text-gray-900">
                ← Back to All Results
            </a>
            <div class="flex space-x-3">
                @if($result->pdf_path)
                    <a href="{{ route('patient.results.download', $result) }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">
                        Download PDF
                    </a>
                @endif
                <a href="{{ route('patient.orders.show', $result->order) }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    View Order Details
                </a>
            </div>
        </div>

        {{-- Disclaimer --}}
        <div class="mt-8 text-center text-xs text-gray-500 border-t border-gray-200 pt-6">
            <p>This is a confidential medical report. Please keep it secure and only share with authorized healthcare providers.</p>
            <p class="mt-1">Results received on {{ $result->result_date->format('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</div>
@endsection