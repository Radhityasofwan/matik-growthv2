{{--
    File view ini khusus untuk Maatwebsite/Excel.
    Library ini akan membaca tag <table> dan mengonversinya menjadi sheet Excel.
    Tidak perlu ada styling HTML, CSS, atau tag lainnya.
--}}
<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; text-align: center;">Sales Funnel Report</th>
        </tr>
        <tr>
            <th colspan="6" style="text-align: center;">Generated on: {{ $date }}</th>
        </tr>
        <tr></tr> {{-- Baris kosong sebagai spasi --}}
        <tr>
            <th style="font-weight: bold;">Summary</th>
        </tr>
        <tr>
            <td>Total Leads</td>
            <td>{{ $stats->totalLeads }}</td>
        </tr>
        <tr>
            <td>Trial</td>
            <td>{{ $stats->trialCount }}</td>
        </tr>
        <tr>
            <td>Active</td>
            <td>{{ $stats->activeCount }}</td>
        </tr>
        <tr>
            <td>Converted</td>
            <td>{{ $stats->convertedCount }}</td>
        </tr>
        <tr>
            <td>Churn</td>
            <td>{{ $stats->churnCount }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Conversion Rate</td>
            <td style="font-weight: bold;">{{ $stats->conversionRate() }}%</td>
        </tr>
        <tr></tr> {{-- Baris kosong sebagai spasi --}}
        <tr>
            <th style="font-weight: bold;">ID</th>
            <th style="font-weight: bold;">Name</th>
            <th style="font-weight: bold;">Email</th>
            <th style="font-weight: bold;">Status</th>
            <th style="font-weight: bold;">Owner</th>
            <th style="font-weight: bold;">Created At</th>
        </tr>
    </thead>
    <tbody>
        @foreach($leads as $lead)
        <tr>
            <td>{{ $lead->id }}</td>
            <td>{{ $lead->name }}</td>
            <td>{{ $lead->email }}</td>
            <td>{{ ucfirst($lead->status) }}</td>
            <td>{{ $lead->owner->name ?? 'N/A' }}</td>
            <td>{{ $lead->created_at->format('Y-m-d') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
