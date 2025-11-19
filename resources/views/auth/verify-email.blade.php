@extends('layouts.app')

@section('title', 'Verify your email')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-3xl">
    <div class="bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Verify your email address</h1>
        <p class="text-gray-600 mb-6">Please check your inbox for a verification link. Once verified, you can continue to your dashboard.</p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded">
                A fresh verification link has been sent to your email address.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
            @csrf
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Resend verification email</button>
        </form>
    </div>
</div>
@endsection
