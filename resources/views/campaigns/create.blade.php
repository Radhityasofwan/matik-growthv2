@extends('layouts.app')

@section('title', 'Create Campaign - Matik Growth Hub')

@section('content')
<div class="container mx-auto py-6" data-aos="fade-up">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-base-content">Create New Campaign</h1>
        <a href="{{ route('campaigns.index') }}" class="btn btn-ghost">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-error mt-4">
            <span>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </span>
        </div>
    @endif

    <div class="card bg-base-100 shadow-md border border-base-300/50 mt-6">
        <div class="card-body">
            <form action="{{ route('campaigns.store') }}" method="POST">
                @csrf
                @include('campaigns._form', ['campaign' => new \App\Models\Campaign()])
                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('campaigns.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">Create Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
