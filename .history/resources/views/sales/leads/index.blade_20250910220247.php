@extends('layouts.app')

@section('title', 'Leads - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">

    <!-- Alerts -->
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
                <span><strong>Terdapat kesalahan!</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></span>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Leads</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track and manage your potential customers.</p>
        </div>
        <a href="#create_lead_modal" class="btn btn-primary mt-4 sm:mt-0">Add Lead</a>
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
        <div class="text-center py-20">
             <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No leads yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first lead.</p>
            <div class="mt-6">
                <a href="#create_lead_modal" class="btn btn-primary">New Lead</a>
            </div>
        </div>
    @else
        <!-- Quick Actions Bar -->
        <div id="quick-actions-bar" class="hidden bg-gray-100 dark:bg-gray-700 border dark:border-gray-600 px-4 py-2 rounded-lg my-4 flex items-center justify-between transition-all duration-300">
            <div><span id="selected-count" class="font-bold">0</span> leads selected.</div>
            <div>
                <a href="#bulk_whatsapp_modal" id="bulk-whatsapp-trigger" class="btn btn-sm btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp mr-2" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.

