{{-- resources/views/patient/results/compare.blade.php --}}
@extends('layouts.app')

@section('title', 'Compare Results')

@section('content')
<div class="container mx-auto px-4 py-10">
    <div class="max-w-6xl mx-auto">
        <a href="{{ route('patient.results.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Results
        </a>

        <div class="bg-white rounded-2xl shadow">
            <div class="px-6 py-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Trend Comparison</h1>
                    <p class="text-gray-600 mt-1">Comparing {{ $results->count() }} selected results</p>
                </div>
                <div class="mt-3 md:mt-0">
                    <p class="text-sm text-gray-500">Tip: download a PDF from each result for your records.</p>
                </div>
            </div>

            <div class="p-6 space-y-8">
                {{-- Selected Results --}}
                <div class="grid grid-cols-1 md:grid-cols-{{ max(2, min(3, $results->count())) }} gap-4">
                    @foreach($results as $result)
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="text-xs uppercase text-gray-500 tracking-wide">Result Date</div>
                            <div class="text-lg font-semibold text-gray-900">
                                {{ $result->result_date->format('F j, Y') }}
                            </div>
                            <div class="text-sm text-gray-500 mt-1">Order {{ $result->order->order_number }}</div>
                            <div class="mt-3 flex items-center space-x-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $result->has_critical_values ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $result->has_critical_values ? 'Critical Values' : 'Normal Range' }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $result->order->items->count() }} tests
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Comparison Table --}}
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Test Value Comparison</h2>
                    <div class="overflow-x-auto">
                        @php
                            $resultHeaders = $results->map(function ($result) {
                                return [
                                    'label' => $result->result_date->format('d M Y'),
                                    'id' => $result->id,
                                ];
                            });
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test</th>
                                    @foreach($resultHeaders as $header)
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $header['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @forelse($comparisonData as $code => $data)
                                    <tr>
                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-gray-900">{{ $data['name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $code }}</div>
                                        </td>
                                        @foreach($data['values'] as $value)
                                            <td class="px-4 py-4 align-top">
                                                <div class="text-sm font-semibold text-gray-900">{{ $value['value'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $value['unit'] }}</div>
                                                @if($value['flag'])
                                                    <span class="inline-flex mt-2 px-2 py-0.5 rounded-full text-xs font-medium {{ in_array($value['flag'], ['H','HH']) ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                                        {{ $value['flag'] === 'H' ? 'High' : ($value['flag'] === 'L' ? 'Low' : $value['flag']) }}
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $results->count() + 1 }}" class="px-4 py-8 text-center text-gray-500">
                                            No overlapping tests were found between the selected results.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 text-sm text-blue-900">
                    <p class="font-semibold">Important:</p>
                    <p class="mt-1">Trends are for informational purposes only. If you notice persistent abnormal values, please consult a healthcare professional.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
