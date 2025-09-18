@extends('layouts.app')

@section('title', 'Campaign Report: ' . $report->campaign->name)

@section('content')
<div class="container mx-auto px-6 py-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">{{ $report->campaign->name }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Campaign Performance Report</p>
        </div>
        <a href="{{ route('campaigns.index') }}" class="btn btn-ghost mt-4 sm:mt-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali ke Daftar Kampanye
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-sm text-gray-500">Return on Investment (ROI)</div>
            <div class="text-3xl font-bold @if($report->roi() > 0) text-success @else text-error @endif">{{ number_format($report->roi(), 2) }}%</div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-sm text-gray-500">Total Revenue</div>
            <div class="text-3xl font-bold">Rp {{ number_format($report->campaign->revenue, 0, ',', '.') }}</div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-sm text-gray-500">Total Budget</div>
            <div class="text-3xl font-bold">Rp {{ number_format($report->campaign->budget, 0, ',', '.') }}</div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-sm text-gray-500">Status</div>
            <div class="text-3xl font-bold">{{ ucfirst($report->campaign->status) }}</div>
        </div>
    </div>

    <!-- Campaign Details -->
    <div class="mt-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
        <h4 class="text-xl font-semibold mb-4">Detail Kampanye</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700 dark:text-gray-300">
            <p><strong>Channel:</strong> {{ $report->campaign->channel }}</p>
            <p><strong>Owner:</strong> {{ $report->campaign->owner->name }}</p>
            <p><strong>Tanggal Mulai:</strong> {{ $report->campaign->start_date->format('d M Y') }}</p>
            <p><strong>Tanggal Selesai:</strong> {{ $report->campaign->end_date->format('d M Y') }}</p>
        </div>
        <div class="mt-4 text-gray-600 dark:text-gray-400">
            <p><strong>Deskripsi:</strong></p>
            <p>{{ $report->campaign->description ?? 'Tidak ada deskripsi.' }}</p>
        </div>
    </div>
</div>
@endsection
