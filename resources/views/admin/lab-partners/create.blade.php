{{-- resources/views/admin/lab-partners/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Add Lab Partner')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
        <a href="{{ route('admin.lab-partners.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Lab Partners
        </a>

        <div class="bg-white rounded-2xl shadow">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-3xl font-bold text-gray-900">Register a New Partner</h1>
                <p class="text-gray-600 text-sm mt-1">Configure connectivity details so we can exchange orders and results.</p>
            </div>

            <form method="POST" action="{{ route('admin.lab-partners.store') }}" class="p-6 space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Partner Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" placeholder="acme-lab" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Connection Type</label>
                        <select name="connection_type" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                            <option value="">Select</option>
                            @foreach($connectionTypes as $type)
                                <option value="{{ $type }}" {{ old('connection_type') === $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Auth Type</label>
                        <select name="auth_type" class="w-full border border-gray-300 rounded-lg px-4 py-3">
                            <option value="">None</option>
                            @foreach($authTypes as $type)
                                <option value="{{ $type }}" {{ old('auth_type') === $type ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <input type="number" min="0" max="100" name="priority" value="{{ old('priority', 50) }}" class="w-full border border-gray-300 rounded-lg px-4 py-3">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">API Endpoint</label>
                        <input type="url" name="api_endpoint" value="{{ old('api_endpoint') }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" placeholder="https://api.partner.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Adapter</label>
                        <select name="adapter" class="w-full border border-gray-300 rounded-lg px-4 py-3">
                            <option value="">Select adapter</option>
                            @foreach($registeredAdapters as $adapter => $class)
                                <option value="{{ $adapter }}" {{ old('adapter') === $adapter ? 'selected' : '' }}>{{ $adapter }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                        <input type="text" name="api_key" value="{{ old('api_key') }}" class="w-full border border-gray-300 rounded-lg px-4 py-3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">API Secret</label>
                        <input type="text" name="api_secret" value="{{ old('api_secret') }}" class="w-full border border-gray-300 rounded-lg px-4 py-3">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Credentials (JSON)</label>
                    <textarea name="credentials" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-3" placeholder='{"username":"..."}'>{{ old('credentials') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Supported Tests (JSON)</label>
                        <textarea name="supported_tests" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-3" placeholder='[{"code":"CMP","turnaround":48}]'>{{ old('supported_tests') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Field Mapping (JSON)</label>
                        <textarea name="field_mapping" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-3" placeholder='{"patient.first_name":"firstName"}'>{{ old('field_mapping') }}</textarea>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="is_active" value="1" class="h-4 w-4 text-blue-600 border-gray-300 rounded" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Active immediately</span>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">
                        Save Partner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
