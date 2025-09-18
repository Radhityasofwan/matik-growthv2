@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-8 max-w-3xl">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight">Create New WhatsApp Broadcast</h2>
            <span class="text-base-content/70">Send a message to a segment of your leads.</span>
        </div>

        @if (session('success'))
            <div role="alert" class="alert alert-success mt-6">
                 <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
             <div role="alert" class="alert alert-error mt-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif


        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <form action="{{ route('broadcasts.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="template_id" class="label"><span class="label-text">Select Message Template</span></label>
                            <select name="template_id" id="template_id" class="select select-bordered w-full" required>
                                <option disabled selected>Choose a template</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label"><span class="label-text">Select Lead Segments</span></label>
                            <div class="grid grid-cols-2 gap-4">
                                @foreach($leadStatuses as $status)
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="segments[]" value="{{ $status }}" class="checkbox checkbox-primary" />
                                    <span class="label-text capitalize">{{ $status }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="card-actions justify-end mt-6">
                            <a href="{{ route('dashboard') }}" class="btn">Cancel</a>
                            <button type="submit" class="btn btn-primary">Queue Broadcast</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
