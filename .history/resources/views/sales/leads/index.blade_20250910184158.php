@extends('layouts.app')

@section('title', 'Leads - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">

    <!-- ADDED: Success and Error Alerts -->
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>
                    <strong>Terdapat kesalahan!</strong> Mohon periksa kembali data yang Anda masukkan.
                    <ul class="list-disc pl-5 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </span>
            </div>
        </div>
    @endif
    <!-- END ADDED SECTION -->


    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Leads</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track and manage your potential customers.</p>
        </div>
        <a href="#create_lead_modal" class="btn btn-primary mt-4 sm:mt-0">
            Add Lead
        </a>
    </div>

    <!-- Filters -->
    <div class="mt-6">
        <form action="{{ route('leads.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="search" placeholder="Search by name or email..." value="{{ request('search') }}" class="input input-bordered w-full">
            <select name="status" class="select select-bordered w-full">
                <option value="">All Statuses</option>
                <option value="trial" @selected(request('status') == 'trial')>Trial</option>
                <option value="active" @selected(request('status') == 'active')>Active</option>
                <option value="converted" @selected(request('status') == 'converted')>Converted</option>
                <option value="churn" @selected(request('status') == 'churn')>Churn</option>
            </select>
            <button type="submit" class="btn btn-secondary w-full md:w-auto">Filter</button>
        </form>
    </div>

    @if($leads->isEmpty() && !request()->query())
        <!-- Empty State -->
        <div class="text-center py-20" data-aos="fade-up">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" width="24" height="24" style="stroke-width: 1;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21v-1a6 6 0 00-5.176-5.97m5.176 5.97h3.328a2 2 0 002-1.996V11A2 2 0 0018 9h-2.28a2 2 0 00-1.996 1.854z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No leads yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first lead.</p>
            <div class="mt-6">
                <a href="#create_lead_modal" class="btn btn-primary">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    New Lead
                </a>
            </div>
        </div>
    @else
        <!-- Leads Table -->
        <div class="mt-8 overflow-x-auto">
            <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Owner</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Created At</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        @forelse ($leads as $lead)
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <a href="{{ route('leads.show', $lead) }}" class="text-blue-600 hover:text-blue-900 font-semibold">{{ $lead->name }}</a>
                                <p class="text-gray-600 dark:text-gray-400 whitespace-no-wrap">{{ $lead->email }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <span class="badge
                                    @switch($lead->status)
                                        @case('trial') badge-info @break
                                        @case('active') badge-success @break
                                        @case('converted') badge-accent @break
                                        @case('churn') badge-error @break
                                    @endswitch">
                                    {{ ucfirst($lead->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <p class="text-gray-900 dark:text-white whitespace-no-wrap">{{ $lead->owner?->name ?? 'Unassigned' }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <p class="text-gray-900 dark:text-white whitespace-no-wrap">{{ $lead->created_at->format('M d, Y') }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm text-right">
                                <a href="#edit_lead_modal_{{ $lead->id }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">
                                <p class="font-semibold">No leads found for your search.</p>
                                <p class="text-sm mt-1">Try adjusting your filters.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col xs:flex-row items-center xs:justify-between">
                    {{ $leads->withQueryString()->links() }}
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Create Lead Modal -->
<div id="create_lead_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl dark:bg-gray-800">
        <form action="{{ route('leads.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg text-gray-800 dark:text-gray-200">Add New Lead</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="label" for="name_create"><span class="label-text dark:text-gray-300">Name</span></label>
                    <input type="text" id="name_create" name="name" placeholder="Enter full name" value="{{ old('name') }}" class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-200" required />
                </div>
                <div>
                    <label class="label" for="email_create"><span class="label-text dark:text-gray-300">Email Address</span></label>
                    <input type="email" id="email_create" name="email" placeholder="Enter email address" value="{{ old('email') }}" class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-200" required />
                </div>
                <div>
                    <label class="label" for="status_create"><span class="label-text dark:text-gray-300">Status</span></label>
                    <select id="status_create" name="status" class="select select-bordered w-full dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="trial" @selected(old('status') == 'trial')>Trial</option>
                        <option value="active" @selected(old('status') == 'active')>Active</option>
                        <option value="converted" @selected(old('status') == 'converted')>Converted</option>
                        <option value="churn" @selected(old('status') == 'churn')>Churn</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="owner_id_create"><span class="label-text dark:text-gray-300">Owner</span></label>
                    <select id="owner_id_create" name="owner_id" class="select select-bordered w-full dark:bg-gray-700 dark:text-gray-200" required>
                        @isset($users)
                             @forelse($users as $user)
                                <option value="{{ $user->id }}" @selected(old('owner_id') == $user->id)>{{ $user->name }}</option>
                             @empty
                                 <option value="" disabled>No users available</option>
                             @endforelse
                        @else
                            <option value="" disabled>Please add users first</option>
                        @endisset
                    </select>
                </div>
            </div>

            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Lead</button>
            </div>
        </form>
    </div>
     <a href="#" class="modal-backdrop">Close</a>
</div>

<!-- Edit Lead Modals -->
@foreach ($leads as $lead)
<div id="edit_lead_modal_{{ $lead->id }}" class="modal">
    <div class="modal-box w-11/12 max-w-2xl dark:bg-gray-800">
        <form action="{{ route('leads.update', $lead) }}" method="POST">
            @csrf
            @method('PATCH')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg text-gray-800 dark:text-gray-200">Edit Lead: {{ $lead->name }}</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="label" for="name_edit_{{ $lead->id }}"><span class="label-text dark:text-gray-300">Name</span></label>
                    <input type="text" id="name_edit_{{ $lead->id }}" name="name" value="{{ old('name', $lead->name) }}" class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-200" required />
                </div>
                <div>
                    <label class="label" for="email_edit_{{ $lead->id }}"><span class="label-text dark:text-gray-300">Email Address</span></label>
                    <input type="email" id="email_edit_{{ $lead->id }}" name="email" value="{{ old('email', $lead->email) }}" class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-200" required />
                </div>
                <div>
                    <label class="label" for="status_edit_{{ $lead->id }}"><span class="label-text dark:text-gray-300">Status</span></label>
                    <select id="status_edit_{{ $lead->id }}" name="status" class="select select-bordered w-full dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="trial" @selected(old('status', $lead->status) == 'trial')>Trial</option>
                        <option value="active" @selected(old('status', $lead->status) == 'active')>Active</option>
                        <option value="converted" @selected(old('status', $lead->status) == 'converted')>Converted</option>
                        <option value="churn" @selected(old('status', $lead->status) == 'churn')>Churn</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="owner_id_edit_{{ $lead->id }}"><span class="label-text dark:text-gray-300">Owner</span></label>
                    <select id="owner_id_edit_{{ $lead->id }}" name="owner_id" class="select select-bordered w-full dark:bg-gray-700 dark:text-gray-200" required>
                         @isset($users)
                             @forelse($users as $user)
                                <option value="{{ $user->id }}" @selected(old('owner_id', $lead->owner_id) == $user->id)>{{ $user->name }}</option>
                             @empty
                                 <option value="" disabled>No users available</option>
                             @endforelse
                        @else
                            <option value="" disabled>Please add users first</option>
                        @endisset
                    </select>
                </div>
            </div>

            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Lead</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endforeach

@endsection

