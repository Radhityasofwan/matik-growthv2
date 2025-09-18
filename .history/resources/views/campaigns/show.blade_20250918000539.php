@extends('layouts.app')

@section('title', 'Campaign Report: ' . $report->campaign->name)

@section('content')
<div class="container mx-auto px-6 py-8">

    <div class="sm:flex sm:items-center sm:justify-between" data-aos="fade-down">
        <div>
            <h3 class="text-3xl font-bold text-base-content">{{ $report->campaign->name }}</h3>
            <p class="mt-1 text-base-content/70">Laporan Performa Kampanye</p>
        </div>
        <a href="{{ route('campaigns.index') }}" class="btn btn-ghost mt-4 sm:mt-0">Kembali ke Daftar</a>
    </div>

    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" data-aos="fade-up">
        <div class="p-6 bg-base-100 rounded-lg shadow border border-base-300/50">
            <div class="text-sm text-base-content/70">Return on Investment (ROI)</div>
            <div class="text-3xl font-bold {{ $report->roi() >= 0 ? 'text-success' : 'text-error' }}">{{ number_format($report->roi(), 2) }}%</div>
        </div>
        <div class="p-6 bg-base-100 rounded-lg shadow border border-base-300/50">
            <div class="text-sm text-base-content/70">Return on Ad Spend (ROAS)</div>
            <div class="text-3xl font-bold">{{ number_format($report->roas(), 2) }}x</div>
        </div>
        <div class="p-6 bg-base-100 rounded-lg shadow border border-base-300/50">
            <div class="text-sm text-base-content/70">Total Revenue</div>
            <div class="text-3xl font-bold">Rp {{ number_format($report->campaign->revenue, 0, ',', '.') }}</div>
        </div>
        <div class="p-6 bg-base-100 rounded-lg shadow border border-base-300/50">
            <div class="text-sm text-base-content/70">Total Spent</div>
            <div class="text-3xl font-bold">Rp {{ number_format($report->campaign->total_spent, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="mt-8 p-6 bg-base-100 rounded-lg shadow border border-base-300/50" data-aos="fade-up">
        <h4 class="text-xl font-semibold mb-4 text-base-content">Detail Metrik Performa</h4>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <tbody>
                    <tr class="hover"><th class="w-1/3">Nama Kampanye</th><td>{{ $report->campaign->name }}</td></tr>
                    <tr class="hover">
                        <th>Periode</th>
                        <td>
                            {{ optional($report->campaign->start_date)->format('d M Y') }} -
                            {{ optional($report->campaign->end_date)->format('d M Y') }}
                            ({{ $report->periodInDays() }} hari)
                        </td>
                    </tr>
                    <tr class="hover"><th>Budget Harian (Rata-rata)</th><td>Rp {{ number_format($report->dailyBudget(), 0, ',', '.') }}</td></tr>

                    <tr class="hover"><th class="bg-base-200" colspan="2">Performa Iklan</th></tr>
                    <tr class="hover"><th>Impressions</th><td>{{ number_format((int) $report->campaign->impressions) }}</td></tr>
                    <tr class="hover"><th>Link Clicks</th><td>{{ number_format((int) $report->campaign->link_clicks) }}</td></tr>
                    <tr class="hover"><th>CTR (Click-Through Rate)</th><td>{{ number_format($report->ctr(), 2) }}%</td></tr>
                    <tr class="hover"><th>CPC (Cost Per Click)</th><td>Rp {{ number_format($report->cpc(), 0, ',', '.') }}</td></tr>

                    <tr class="hover"><th class="bg-base-200" colspan="2">Performa Konversi</th></tr>
                    <tr class="hover"><th>Hasil (Konversi)</th><td>{{ number_format((int) $report->campaign->results) }}</td></tr>
                    <tr class="hover"><th>CPR/CPL (Cost Per Result)</th><td>Rp {{ number_format($report->cpr(), 0, ',', '.') }}</td></tr>

                    <tr class="hover"><th class="bg-base-200" colspan="2">Performa Landing Page</th></tr>
                    <tr class="hover"><th>LP Impressions</th><td>{{ number_format((int) $report->campaign->lp_impressions) }}</td></tr>
                    <tr class="hover"><th>LP Link Clicks</th><td>{{ number_format((int) $report->campaign->lp_link_clicks) }}</td></tr>
                    <tr class="hover"><th>LP CTR</th><td>{{ number_format($report->lpCtr(), 2) }}%</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
