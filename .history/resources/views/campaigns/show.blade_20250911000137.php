@extends('layouts.app')

@section('title', 'Campaign Report: ' . $report->campaign->name)

@section('content')
<div class="container mx-auto px-6 py-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">{{ $report->campaign->name }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Laporan Performa Kampanye</p>
        </div>
        <a href="{{ route('campaigns.index') }}" class="btn btn-ghost mt-4 sm:mt-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali ke Daftar
        </a>
    </div>

    <!-- Main KPIs -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-sm text-gray-500">Return on Investment (ROI)</div>
            <div class="text-3xl font-bold @if($report->roi() >= 0) text-success @else text-error @endif">{{ number_format($report->roi(), 2) }}%</div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-sm text-gray-500">Return on Ad Spend (ROAS)</div>
            <div class="text-3xl font-bold">{{ number_format($report->roas(), 2) }}x</div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-sm text-gray-500">Total Revenue</div>
            <div class="text-3xl font-bold">Rp {{ number_format($report->campaign->revenue, 0, ',', '.') }}</div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-sm text-gray-500">Total Spent</div>
            <div class="text-3xl font-bold">Rp {{ number_format($report->campaign->total_spent, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Detailed Report Table -->
    <div class="mt-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
        <h4 class="text-xl font-semibold mb-4">Detail Metrik Performa</h4>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <tbody>
                    <!-- General Info -->
                    <tr class="hover"><th class="w-1/3">Nama Kampanye</th><td>{{ $report->campaign->name }}</td></tr>
                    <tr class="hover"><th>Periode</th><td>{{ $report->campaign->start_date->format('d M Y') }} - {{ $report->campaign->end_date->format('d M Y') }} ({{ $report->periodInDays() }} hari)</td></tr>
                    <tr class="hover"><th>Budget Harian (Rata-rata)</th><td>Rp {{ number_format($report->dailyBudget(), 0, ',', '.') }}</td></tr>

                    <!-- Ad Performance -->
                    <tr class="hover"><th class="bg-gray-50 dark:bg-gray-700" colspan="2">Performa Iklan</th></tr>
                    <tr class="hover"><th>Impressions</th><td>{{ number_format($report->campaign->impressions) }}</td></tr>
                    <tr class="hover"><th>Link Clicks</th><td>{{ number_format($report->campaign->link_clicks) }}</td></tr>
                    <tr class="hover"><th>CTR (Click-Through Rate)</th><td>{{ number_format($report->ctr(), 2) }}%</td></tr>
                    <tr class="hover"><th>CPC (Cost Per Click)</th><td>Rp {{ number_format($report->cpc(), 0, ',', '.') }}</td></tr>

                    <!-- Conversion Performance -->
                    <tr class="hover"><th class="bg-gray-50 dark:bg-gray-700" colspan="2">Performa Konversi</th></tr>
                    <tr class="hover"><th>Hasil (Konversi)</th><td>{{ number_format($report->campaign->results) }}</td></tr>
                    <tr class="hover"><th>CPR/CPL (Cost Per Result)</th><td>Rp {{ number_format($report->cpr(), 0, ',', '.') }}</td></tr>

                    <!-- Landing Page Performance -->
                    <tr class="hover"><th class="bg-gray-50 dark:bg-gray-700" colspan="2">Performa Landing Page</th></tr>
                    <tr class="hover"><th>LP Impressions</th><td>{{ number_format($report->campaign->lp_impressions) }}</td></tr>
                    <tr class="hover"><th>LP Link Clicks</th><td>{{ number_format($report->campaign->lp_link_clicks) }}</td></tr>
                    <tr class="hover"><th>LP CTR</th><td>{{ number_format($report->lpCtr(), 2) }}%</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

