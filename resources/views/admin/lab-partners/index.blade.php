{{-- resources/views/admin/lab-partners/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Lab Partners')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Lab Partners</h1>
        <a href="{{ route('admin.lab-partners.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Lab Partner
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6">
        @forelse($labPartners as $data)
            @php $partner = $data['partner']; @endphp
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <h3 class="text-xl font-semibold text-gray-900">{{ $partner->name }}</h3>
                                <span class="ml-3 px-3 py-1 text-xs font-medium rounded-full {{ $partner->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $partner->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <span class="inline-flex items-center mr-4">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                    </svg>
                                    Code: <span class="font-mono ml-1">{{ $partner->code }}</span>
                                </span>
                                <span class="inline-flex items-center mr-4">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    {{ $partner->connection_type_name }}
                                </span>
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                    Priority: {{ $partner->priority }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-4 flex space-x-2">
                            <a href="{{ route('admin.lab-partners.show', $partner) }}" class="text-blue-600 hover:text-blue-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="{{ route('admin.lab-partners.edit', $partner) }}" class="text-gray-600 hover:text-gray-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Total Submissions</div>
                            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $data['partner']->labSubmissions->count() }}</div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Success Rate</div>
                            <div class="text-2xl font-bold text-blue-600 mt-1">{{ $data['success_rate'] }}%</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Avg Turnaround</div>
                            <div class="text-2xl font-bold text-green-600 mt-1">
                                @if($data['avg_turnaround'])
                                    {{ $data['avg_turnaround'] }}h
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Pending</div>
                            <div class="text-2xl font-bold text-yellow-600 mt-1">{{ $data['pending'] }}</div>
                        </div>
                    </div>

                    @if($data['failed'] > 0)
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3">
                        <div class="flex items-center text-red-800 text-sm">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $data['failed'] }} failed submission(s) require attention
                        </div>
                    </div>
                    @endif

                    <div class="mt-4 flex items-center space-x-3">
                        <form action="{{ route('admin.lab-partners.toggle-active', $partner) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm {{ $partner->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $partner->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <span class="text-gray-300">|</span>
                        <button onclick="testConnection({{ $partner->id }})" class="text-sm text-blue-600 hover:text-blue-800">
                            Test Connection
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <p class="mt-4 text-gray-600">No lab partners configured yet</p>
                <a href="{{ route('admin.lab-partners.create') }}" class="mt-4 inline-block text-blue-600 hover:underline">
                    Add your first lab partner →
                </a>
            </div>
        @endforelse
    </div>
</div>

<script>
function testConnection(partnerId) {
    if (confirm('Test connection to this lab partner?')) {
        fetch(`/admin/lab-partners/${partnerId}/test-connection`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Connection successful!');
            } else {
                alert('✗ Connection failed: ' + data.message);
            }
        })
        .catch(error => {
            alert('✗ Connection test failed: ' + error);
        });
    }
}
</script>
@endsection