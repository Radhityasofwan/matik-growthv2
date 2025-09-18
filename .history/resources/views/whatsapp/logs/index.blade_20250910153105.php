@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-8 max-w-5xl">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight">WhatsApp Message Logs</h2>
            <span class="text-base-content/70">A history of all automated messages sent.</span>
        </div>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg mt-6">
            <table class="w-full text-sm text-left text-base-content">
                <thead class="text-xs text-base-content uppercase bg-base-200">
                    <tr>
                        <th scope="col" class="py-3 px-6">
                            Recipient (Lead)
                        </th>
                        <th scope="col" class="py-3 px-6">
                            Message
                        </th>
                        <th scope="col" class="py-3 px-6">
                            Status
                        </th>
                        <th scope="col" class="py-3 px-6">
                            Date
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                    <tr class="bg-base-100 border-b border-base-300 hover:bg-base-200">
                        <td class="py-4 px-6 font-medium whitespace-nowrap">
                            @if ($log->lead)
                                <a href="{{ route('leads.show', $log->lead) }}" class="link link-primary">{{ $log->lead->name }}</a>
                                <div class="text-xs text-base-content/60">{{ $log->phone_number }}</div>
                            @else
                                {{ $log->phone_number }}
                            @endif
                        </td>
                        <td class="py-4 px-6 text-xs max-w-sm">
                            <p class="truncate">{{ $log->message }}</p>
                        </td>
                        <td class="py-4 px-6">
                            <span class="badge {{ $log->status === 'sent' ? 'badge-success' : 'badge-error' }} badge-sm">{{ ucfirst($log->status) }}</span>
                        </td>
                        <td class="py-4 px-6 text-xs text-base-content/70">
                            {{ $log->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-10">
                            <p class="font-semibold">No message logs found.</p>
                            <p class="text-sm text-base-content/60">Messages will appear here after they are sent.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
