@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">New Subscription</h1>
    </div>

    <div class="bg-base-100 p-6 rounded-lg shadow-lg">
        <form action="{{ route('subscriptions.store') }}" method="POST">
            @csrf
            @include('sales.subscriptions._form', ['subscription' => new \App\Models\Subscription(), 'submitText' => 'Create Subscription'])
        </form>
    </div>
</div>
@endsection
