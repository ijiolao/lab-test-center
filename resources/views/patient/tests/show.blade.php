{{-- resources/views/patient/tests/show.blade.php --}}
@extends('layouts.app')

@section('title', $test->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <a href="{{ route('patient.tests.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to All Tests
        </a>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-8">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">{{ $test->name }}</h1>
                        <p class="text-blue-100">Test Code: {{ $test->code }}</p>
                        @if($test->loinc_code)
                            <p class="text-blue-100 text-sm">LOINC: {{ $test->loinc_code }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-bold">{{ $test->formatted_price }}</div>
                        <div class="text-blue-100 text-sm">inc. VAT</div>
                    </div>
                </div>
            </div>

            {{-- Test Information --}}
            <div class="p-8">
                {{-- Key Information Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center text-blue-700 mb-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-medium">Turnaround Time</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">{{ $test->expected_completion }}</div>
                    </div>

                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="flex items-center text-purple-700 mb-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                            <span class="text-sm font-medium">Specimen Type</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">{{ ucfirst($test->specimen_type) }}</div>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center text-green-700 mb-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <span class="text-sm font-medium">Category</span>
                        </div>
                        <div class="text-xl font-bold text-gray-900">{{ $test->category }}</div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">About This Test</h2>
                    <p class="text-gray-700 leading-relaxed">{{ $test->description }}</p>
                </div>

                {{-- Preparation --}}
                @if($test->fasting_required || $test->preparation_instructions)
                <div class="mb-8 bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-6 h-6 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Test Preparation Required
                    </h3>
                    
                    @if($test->fasting_required)
                        <div class="mb-3">
                            <div class="font-medium text-gray-900 mb-1">Fasting Required</div>
                            <p class="text-gray-700 text-sm">Please fast for 8-12 hours before this test. Water is allowed.</p>
                        </div>
                    @endif

                    @if($test->preparation_instructions)
                        <div>
                            <div class="font-medium text-gray-900 mb-1">Additional Instructions</div>
                            <p class="text-gray-700 text-sm">{{ $test->preparation_instructions }}</p>
                        </div>
                    @endif
                </div>
                @endif

                {{-- Related Tests --}}
                @if($relatedTests->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Related Tests</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($relatedTests as $relatedTest)
                        <a href="{{ route('patient.tests.show', $relatedTest) }}" class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $relatedTest->name }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ Str::limit($relatedTest->description, 80) }}</p>
                                </div>
                                <div class="text-lg font-bold text-blue-600 ml-4">
                                    {{ $relatedTest->formatted_price }}
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('patient.orders.create') }}?test={{ $test->id }}" class="flex-1 bg-blue-600 text-white px-8 py-4 rounded-lg hover:bg-blue-700 text-center font-semibold">
                        Order This Test
                    </a>
                    <a href="{{ route('patient.tests.index') }}" class="flex-1 bg-gray-100 text-gray-700 px-8 py-4 rounded-lg hover:bg-gray-200 text-center font-semibold">
                        Browse All Tests
                    </a>
                </div>
            </div>
        </div>

        {{-- Additional Information --}}
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Important Information</h3>
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>All tests are performed by certified laboratories</span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>Results are reviewed by qualified medical professionals before release</span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>You'll receive an email notification when your results are ready</span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>Results can be downloaded as PDF for your records</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection