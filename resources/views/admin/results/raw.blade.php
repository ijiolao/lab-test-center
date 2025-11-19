{{-- resources/views/admin/results/raw.blade.php --}}
@extends('layouts.admin')

@section('title', 'Raw Result Data')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
        <a href="{{ route('admin.results.show', $result) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Result
        </a>

        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Raw Laboratory Payload</h1>
                    <p class="text-sm text-gray-600">Order {{ $result->order->order_number }} • Received {{ $result->result_date->format('d M Y H:i') }}</p>
                </div>
                <a href="{{ route('admin.results.download', $result) }}" class="text-blue-600 text-sm hover:underline">Download PDF</a>
            </div>

            <div class="p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Parsed Data</h2>
                    <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 overflow-auto text-xs">{{ json_encode($result->parsed_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Raw Payload</h2>
                    <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 overflow-auto text-xs">{{ json_encode($result->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-900">
                    <p class="font-semibold">Audit tip</p>
                    <p class="mt-1">Ensure any exported data excludes identifiable patient information unless a data sharing agreement is in place.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
