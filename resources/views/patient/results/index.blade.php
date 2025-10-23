{{-- resources/views/patient/results/index.blade.php --}}
@extends('layouts.app')

@section('title', 'My Results')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">My Test Results</h1>

        <div class="grid grid-cols-1 gap-6">
            @forelse($results as $result)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-xl font-semibold text-gray-900">
                                    Order {{ $result->order->order_number }}
                                </h3>
                                @if(!$result->patient_viewed_at)
                                    <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                        NEW
                                    </span>
                                @endif
                                @if($result->has_critical_values)
                                    <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                                        Important
                                    </span>
                                @endif
                            </div>
                            
                            <div class="text-sm text-gray-600 space-y-1">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Result Date: {{ $result->result_date->format('F j, Y') }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    {{ $result->order->items->count() }} test(s)
                                </div>
                                @if($result->patient_notified_at)
                                    <div class="flex items-center text-blue-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Notified {{ $result->patient_notified_at->diffForHumans() }}
                                    </div>
                                @endif
                            </div>

                            {{-- Test Summary --}}
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($result->order->items->take(3) as $item)
                                    <span class="inline-block bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">
                                        {{ $item->test_name }}
                                    </span>
                                @endforeach
                                @if($result->order->items->count() > 3)
                                    <span class="inline-block text-gray-500 text-xs px-2 py-1">
                                        +{{ $result->order->items->count() - 3 }} more
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="ml-6 flex flex-col items-end space-y-3">
                            <a href="{{ route('patient.results.show', $result) }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 inline-flex items-center">
                                View Results
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                            @if($result->pdf_path)
                                <a href="{{ route('patient.results.download', $result) }}" class="text-gray-600 hover:text-gray-900 text-sm inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download PDF
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Critical Notice --}}
                    @if($result->has_critical_values)
                        <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-red-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="ml-3 text-sm text-red-800">
                                    This result contains values that may require attention. Please review carefully and consult with your healthcare provider if you have questions.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No Results Available</h3>
                <p class="mt-2 text-gray-600">You don't have any test results yet.</p>
                <a href="{{ route('patient.orders.create') }}" class="mt-6 inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    Order Tests
                </a>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($results->hasPages())
            <div class="mt-8">
                {{ $results->links() }}
            </div>
        @endif

        {{-- Help Section --}}
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="ml-4">
                    <h3 class="font-semibold text-blue-900">Understanding Your Results</h3>
                    <p class="text-sm text-blue-800 mt-1">
                        Your test results are reviewed by our medical professionals before being released. 
                        If you have questions about your results, please consult with your healthcare provider or contact our support team.
                    </p>
                    <div class="mt-3 space-x-4">
                        <a href="mailto:support@labtestcenter.com" class="text-blue-600 hover:underline text-sm">
                            Email Support
                        </a>
                        <a href="tel:+441234567890" class="text-blue-600 hover:underline text-sm">
                            Call Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection