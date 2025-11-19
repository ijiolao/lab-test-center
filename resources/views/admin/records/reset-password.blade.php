{{-- resources/views/admin/records/reset-password.blade.php --}}
@extends('layouts.admin')

@section('title', 'Reset Patient Password')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-xl mx-auto">
        <a href="{{ route('admin.records.show', $patient) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Profile
        </a>

        <div class="bg-white rounded-2xl shadow">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-3xl font-bold text-gray-900">Reset Password</h1>
                <p class="text-sm text-gray-600 mt-1">Send the new credentials securely to the patient.</p>
            </div>
            <form method="POST" action="{{ route('admin.records.reset-password', $patient) }}" class="p-6 space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-4 py-3" required minlength="8">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full border border-gray-300 rounded-lg px-4 py-3" required minlength="8">
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-900">
                    <p class="font-semibold">Security reminder</p>
                    <p class="mt-1">Encourage the patient to change this password once they log in.</p>
                </div>
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.records.show', $patient) }}" class="px-6 py-3 rounded-lg border border-gray-300">Cancel</a>
                    <button type="submit" class="px-6 py-3 rounded-lg bg-blue-600 text-white font-semibold">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
