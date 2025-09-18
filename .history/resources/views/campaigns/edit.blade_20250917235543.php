@extends('layouts.app')

@section('title', 'Edit Campaign - ' . $campaign->name)

@section('content')
<div class="container mx-auto py-6" data-aos="fade-up">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-base-content">Edit Campaign: {{ $campaign->name }}</h1>
        <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-ghost">Lihat Laporan</a>
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
            <form action="{{ route('campaigns.update', $campaign) }}" method="POST">
                @csrf
                @method('PUT')
                @include('campaigns._form', ['campaign' => $campaign])
                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('campaigns.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">Update Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
