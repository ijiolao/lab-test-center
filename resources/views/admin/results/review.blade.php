{{-- resources/views/admin/results/review.blade.php --}}
@extends('layouts.admin')

@section('title', 'Review Result')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <a href="{{ route('admin.results.show', $result) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Result
        </a>

        <h1 class="text-3xl font-bold text-gray-900 mb-2">Review Result</h1>
        <p class="text-gray-600 mb-8">Order {{ $result->order->order_number }} - {{ $result->order->user->full_name }}</p>

        @if($result->has_critical_values)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-red-600 mt-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-4">
                        <h3 class="font-semibold text-red-900">Critical Values Detected</h3>
                        <p class="text-sm text-red-800 mt-1">This result contains {{ count($criticalTests) }} critical value(s). Please review carefully before releasing to patient.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Critical Tests Summary --}}
        @if(count($criticalTests) > 0)
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h2 class="text-xl font-semibold text-red-900">Critical Results</h2>
            </div>
            <div class="p-6">
                @foreach($criticalTests as $test)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-3 last:mb-0">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $test['test_name'] }}</div>
                            <div class="text-sm text-gray-600">{{ $test['test_code'] }}</div>
                        </div>
                        <span class="px-3 py-1 bg-red-200 text-red-900 text-sm font-bold rounded-full">
                            {{ $test['flag'] }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-4">
                        <div>
                            <div class="text-xs text-gray-600">Result</div>
                            <div class="font-bold text-red-900">{{ $test['value'] }} {{ $test['unit'] ?? '' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600">Reference Range</div>
                            <div class="font-medium text-gray-900">{{ $test['reference_range'] ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600">Status</div>
                            <div class="font-medium text-gray-900">{{ $test['status'] ?? 'Final' }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- All Results Summary --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">All Test Results</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flag</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($result->parsed_data['tests'] as $test)
                        <tr class="{{ isset($test['flag']) ? 'bg-yellow-50' : '' }}">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $test['test_name'] }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold">
                                {{ $test['value'] }} {{ $test['unit'] ?? '' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $test['reference_range'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @if(isset($test['flag']))
                                    @if(in_array($test['flag'], ['HH', 'LL']))
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                            {{ $test['flag'] }}
                                        </span>
                                    @elseif(in_array($test['flag'], ['H', 'L']))
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                            {{ $test['flag'] }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
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

        {{-- Review Form --}}
        <form action="{{ route('admin.results.review', $result) }}" method="POST">
            @csrf
            
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Review Notes</h2>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Clinical Notes (Optional)
                    </label>
                    <textarea name="notes" 
                              rows="6"
                              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                              placeholder="Enter any clinical notes, observations, or recommendations for the patient or ordering physician...">{{ old('notes') }}</textarea>
                    <p class="text-xs text-gray-500 mt-2">
                        These notes will be included in the result report and visible to the patient.
                    </p>
                </div>
            </div>

            {{-- Review Checklist --}}
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Review Checklist</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <label class="flex items-start">
                            <input type="checkbox" required class="mt-1 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-3 text-gray-700">
                                I have reviewed all test results and verified they are clinically appropriate
                            </span>
                        </label>
                        
                        @if($result->has_critical_values)
                        <label class="flex items-start">
                            <input type="checkbox" required class="mt-1 h-5 w-5 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                            <span class="ml-3 text-gray-700">
                                I acknowledge that critical values are present and have been reviewed
                            </span>
                        </label>
                        @endif
                        
                        <label class="flex items-start">
                            <input type="checkbox" required class="mt-1 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-3 text-gray-700">
                                Patient demographics have been verified to match the order
                            </span>
                        </label>
                        
                        <label class="flex items-start">
                            <input type="checkbox" required class="mt-1 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-3 text-gray-700">
                                I authorize the release of these results to the patient
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Patient Information Confirmation --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-blue-900 mb-3">Patient Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-blue-700">Name:</span>
                        <span class="font-medium text-blue-900 ml-2">{{ $result->order->user->full_name }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">DOB:</span>
                        <span class="font-medium text-blue-900 ml-2">{{ $result->order->user->date_of_birth->format('d/m/Y') }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">Order:</span>
                        <span class="font-medium text-blue-900 ml-2">{{ $result->order->order_number }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">Collection:</span>
                        <span class="font-medium text-blue-900 ml-2">{{ $result->order->collection_date->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-between items-center">
                <a href="{{ route('admin.results.show', $result) }}" class="text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 font-semibold">
                    Approve & Release Result
                </button>
            </div>
        </form>

        {{-- Warning Notice --}}
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <p class="ml-3 text-sm text-yellow-800">
                    By approving this result, you confirm that it has been properly reviewed and is ready to be released to the patient. 
                    The patient will be automatically notified once approved.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection