{{-- resources/views/errors/404.blade.php --}}
@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="max-w-3xl mx-auto text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-50 text-red-600 mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.29 3.86L1.82 18a1 1 0 00.86 1.5h18.64a1 1 0 00.86-1.5L13.71 3.86a1 1 0 00-1.72 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01"></path>
            </svg>
        </div>
        <p class="text-sm font-semibold text-red-600 tracking-widest uppercase">404 Error</p>
        <h1 class="mt-4 text-4xl font-extrabold text-gray-900">We couldn't find that page</h1>
        <p class="mt-4 text-lg text-gray-600">
            The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ url()->previous() }}" class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                Go Back
            </a>
            <a href="{{ route('home') }}" class="px-6 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Return Home
            </a>
        </div>
    </div>
</div>
@endsection
