@extends('layouts.app')

@section('title', 'Subscriptions')

@section('content')
<div class="p-4 sm:p-6 lg:p-8" data-aos="fade-up" data-aos-delay="150">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-base-content">Subscriptions</h1>
            <p class="mt-2 text-sm text-base-content/70">A list of all subscriptions from converted leads.</p>
        </div>
    </div>

    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-base-300">
                        <thead class="bg-base-200">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-base-content sm:pl-6">Lead Name</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-base-content">Plan</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-base-content">Status</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-base-content">Amount</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-base-content">Cycle</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-base-content">End Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200 bg-base-100">
                            @forelse ($subscriptions as $subscription)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-base-content sm:pl-6">{{ $subscription->lead->name ?? 'N/A' }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-base-content/70"><span class="badge badge-info">{{ ucfirst($subscription->plan) }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-base-content/70">
                                        <span @class([
                                            'badge',
                                            'badge-success' => $subscription->status === 'active',
                                            'badge-warning' => $subscription->status === 'paused',
                                            'badge-error' => $subscription->status === 'cancelled',
                                        ])>{{ ucfirst($subscription->status) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-base-content/70">${{ number_format($subscription->amount, 2) }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-base-content/70">{{ ucfirst($subscription->cycle) }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-base-content/70">{{ optional($subscription->end_date)->format('d M Y') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-10">
                                        <div class="text-center">
                                            <svg class="mx-auto h-12 w-12 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                            </svg>
                                            <h3 class="mt-2 text-sm font-semibold text-base-content">No subscriptions</h3>
                                            <p class="mt-1 text-sm text-base-content/70">Get started by creating subscriptions or converting a lead.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 @if($subscriptions->hasPages())
                    <div class="mt-4 px-4">
                        {{ $subscriptions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
