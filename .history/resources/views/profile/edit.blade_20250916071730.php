@extends('layouts.app')

@section('title', 'Profile - Matik Growth Hub')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile Settings</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your account information and preferences.</p>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 sm:mt-0">
            @csrf
            <button type="submit" class="btn btn-error text-white">Log Out</button>
        </form>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success shadow mb-6">
            <div>Profile updated successfully.</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error shadow mb-6">
            <div><strong>Whoops!</strong> Something went wrong. Check the fields below.</div>
        </div>
    @endif

    {{-- Update Profile Information --}}
    <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <form method="post" action="{{ route('profile.update') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('put')

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                <input name="name" type="text" class="input input-bordered w-full mt-1"
                       value="{{ old('name', $user->name) }}" required>
                @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
                <input name="email" type="email" class="input input-bordered w-full mt-1"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Avatar</label>
                @if ($user->avatar)
                    <img src="{{ asset('storage/'.$user->avatar) }}" alt="Current Avatar"
                         class="h-16 w-16 rounded-full object-cover my-2">
                @endif
                <input name="avatar" type="file" class="file-input file-input-bordered w-full max-w-md">
                @error('avatar')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>

    {{-- Update Password --}}
    <div class="mt-8 p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('put')

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Current Password</label>
                <input name="current_password" type="password" class="input input-bordered w-full mt-1" autocomplete="current-password">
                @error('current_password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">New Password</label>
                <input name="new_password" type="password" class="input input-bordered w-full mt-1" autocomplete="new-password">
                @error('new_password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Confirm New Password</label>
                <input name="new_password_confirmation" type="password" class="input input-bordered w-full mt-1" autocomplete="new-password">
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
