{{-- resources/views/patient/results/share.blade.php --}}
@extends('layouts.app')

@section('title', 'Share Result')

@section('content')
@php
    $shareRoute = Route::has('patient.results.share')
        ? route('patient.results.share', $result)
        : request()->url();
@endphp
<div class="container mx-auto px-4 py-10">
    <div class="max-w-3xl mx-auto">
        <a href="{{ route('patient.results.show', $result) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Result
        </a>

        <div class="bg-white rounded-2xl shadow">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-3xl font-bold text-gray-900">Share with your clinician</h1>
                <p class="text-gray-600 mt-1">We'll email a secure link to download this result.</p>
            </div>
            <div class="p-6 space-y-6">
                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ $shareRoute }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recipient Name</label>
                        <input type="text" name="recipient_name" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="Dr Jane Smith" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recipient Email</label>
                        <input type="email" name="recipient_email" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="clinic@example.com" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message (optional)</label>
                        <textarea name="message" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="Include any additional context you would like to share."></textarea>
                        <p class="text-xs text-gray-500 mt-2">We'll include the selected result as a PDF attachment if available.</p>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700">
                        Send Secure Link
                    </button>
                </form>

                <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-600">
                    <p class="font-semibold text-gray-900">Privacy notice</p>
                    <p class="mt-1">Recipients will have 7 days to download the report. Links are single-use and require verification before access is granted.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
