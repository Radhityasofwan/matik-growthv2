@extends('layouts.app')

@section('title', 'Profile - Matik Growth Hub')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div class="flex items-center gap-4">
            <img src="{{ $user->avatar_url }}" alt="Avatar" class="h-16 w-16 rounded-full object-cover">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kelola informasi akun & preferensi Anda.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 sm:mt-0">
            @csrf
            <button type="submit" class="btn btn-error text-white">Log Out</button>
        </form>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success shadow mb-6">
            <div>Profile berhasil diperbarui.</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error shadow mb-6">
            <div><strong>Whoops!</strong> Ada kesalahan pada input Anda.</div>
        </div>
    @endif

    {{-- Update Profile Information --}}
    <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <form method="post" action="{{ route('profile.update') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('put')

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nama</label>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nomor WhatsApp</label>
                <input name="wa_number" type="text" class="input input-bordered w-full mt-1"
                       value="{{ old('wa_number', $user->wa_number) }}">
                <p class="text-xs text-gray-500 mt-1">Gunakan format internasional, contoh: 6281234567890</p>
                @error('wa_number')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Avatar</label>
                <img src="{{ $user->avatar_url }}" alt="Current Avatar"
                     class="h-16 w-16 rounded-full object-cover my-2">
                <input name="avatar" type="file" class="file-input file-input-bordered w-full max-w-md">
                @error('avatar')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>

    {{-- Update Password --}}
    <div class="mt-8 p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('put')

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Password Saat Ini</label>
                <input name="current_password" type="password" class="input input-bordered w-full mt-1" autocomplete="current-password">
                @error('current_password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Password Baru</label>
                <input name="new_password" type="password" class="input input-bordered w-full mt-1" autocomplete="new-password">
                @error('new_password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Konfirmasi Password Baru</label>
                <input name="new_password_confirmation" type="password" class="input input-bordered w-full mt-1" autocomplete="new-password">
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>

    {{-- Delete Account --}}
    <div class="mt-8 p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <form method="post" action="{{ route('profile.destroy') }}" class="space-y-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Hapus Akun</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Setelah akun Anda dihapus, semua data akan hilang permanen. Masukkan password Anda untuk konfirmasi.
            </p>

            <div>
                <input name="password" type="password" class="input input-bordered w-full mt-1" placeholder="Password">
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-error">Hapus Akun</button>
            </div>
        </form>
    </div>
</div>
@endsection
