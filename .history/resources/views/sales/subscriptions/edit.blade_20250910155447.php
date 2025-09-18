@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Subscription</h1>
    </div>

    <div class="bg-base-100 p-6 rounded-lg shadow-lg">
        <form action="{{ route('subscriptions.update', $subscription) }}" method="POST">
            @csrf
            @method('PUT')
            @include('sales.subscriptions._form', ['submitText' => 'Update Subscription'])
        </form>
    </div>
</div>
@endsection
