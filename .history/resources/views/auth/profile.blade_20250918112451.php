@extends('layouts.app')

@section('title', 'Profile Settings')

@section('content')
<div x-data="{
    avatarPreview: '{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=random&color=fff' }}',
    handleAvatarChange(event) {
        const file = event.target.files[0];
        if (file) {
            this.avatarPreview = URL.createObjectURL(file);
        }
    }
}" class="container mx-auto py-8 space-y-8">

    {{-- Header --}}
    <header class="text-center md:text-left" data-aos="fade-down">
        <h1 class="text-4xl font-bold text-base-content">Pengaturan Profil</h1>
        <p class="text-base-content/70 mt-2">Kelola informasi akun dan preferensi Anda.</p>
    </header>

    {{-- Session Status --}}
    @if (session('status') === 'profile-updated')
        <div role="alert" class="alert alert-success" data-aos="fade-up">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>Profil berhasil diperbarui.</span>
        </div>
    @endif
     @if (session('status') === 'password-updated')
        <div role="alert" class="alert alert-success" data-aos="fade-up">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>Password berhasil diperbarui.</span>
        </div>
    @endif

    {{-- Update Profile Information --}}
    <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('patch')

            <div class="card-body">
                <h2 class="card-title">Informasi Profil</h2>
                <p class="text-sm text-base-content/60 -mt-2">Perbarui informasi dasar dan alamat email akun Anda.</p>
                <div class="space-y-4 mt-4">
                    <!-- Avatar -->
                    <div class="form-control">
                        <label for="avatar" class="label"><span class="label-text">Avatar</span></label>
                        <div class="flex items-center gap-4">
                            <div class="avatar">
                                <div class="w-20 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                                    <img :src="avatarPreview" alt="Avatar Preview" />
                                </div>
                            </div>
                            <input id="avatar" name="avatar" type="file" @change="handleAvatarChange" class="file-input file-input-bordered w-full max-w-xs" />
                        </div>
                        @error('avatar')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Name -->
                    <div class="form-control">
                        <label for="name" class="label"><span class="label-text">Nama</span></label>
                        <input id="name" name="name" type="text" class="input input-bordered w-full" value="{{ old('name', $user->name) }}" required autofocus />
                        @error('name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Email -->
                    <div class="form-control">
                        <label for="email" class="label"><span class="label-text">Email</span></label>
                        <input id="email" name="email" type="email" class="input input-bordered w-full" value="{{ old('email', $user->email) }}" required />
                        @error('email')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- WA Number -->
                    <div class="form-control">
                        <label for="wa_number" class="label"><span class="label-text">Nomor WhatsApp</span></label>
                        <input id="wa_number" name="wa_number" type="text" class="input input-bordered w-full" value="{{ old('wa_number', $user->wa_number) }}" placeholder="Contoh: 6281234567890" />
                        @error('wa_number')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div class="card-actions justify-end p-4 bg-base-200/50 rounded-b-2xl">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    {{-- Update Password --}}
    <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('put')
            <div class="card-body">
                <h2 class="card-title">Perbarui Password</h2>
                <p class="text-sm text-base-content/60 -mt-2">Pastikan akun Anda menggunakan password yang panjang dan acak agar tetap aman.</p>
                <div class="space-y-4 mt-4">
                    <!-- Current Password -->
                    <div class="form-control">
                        <label for="current_password" class="label"><span class="label-text">Password Saat Ini</span></label>
                        <input id="current_password" name="current_password" type="password" class="input input-bordered w-full" required />
                        @error('current_password', 'updatePassword')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <!-- New Password -->
                    <div class="form-control">
                        <label for="password" class="label"><span class="label-text">Password Baru</span></label>
                        <input id="password" name="password" type="password" class="input input-bordered w-full" required />
                        @error('password', 'updatePassword')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <!-- Confirm New Password -->
                    <div class="form-control">
                        <label for="password_confirmation" class="label"><span class="label-text">Konfirmasi Password Baru</span></label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="input input-bordered w-full" required />
                    </div>
                </div>
            </div>
             <div class="card-actions justify-end p-4 bg-base-200/50 rounded-b-2xl">
                <button type="submit" class="btn btn-primary">Ganti Password</button>
            </div>
        </form>
    </div>

    {{-- Delete Account --}}
    <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title text-error">Hapus Akun</h2>
            <p class="text-sm text-base-content/60 -mt-2">Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Sebelum menghapus akun Anda, harap unduh data atau informasi apa pun yang ingin Anda simpan.</p>
            <div class="card-actions justify-start mt-4">
                <button class="btn btn-error" onclick="delete_modal.showModal()">Hapus Akun</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<dialog id="delete_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Apakah Anda yakin ingin menghapus akun Anda?</h3>
    <p class="py-4">Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Silakan masukkan password Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun Anda secara permanen.</p>
    <form method="post" action="{{ route('profile.destroy') }}" class="space-y-4">
        @csrf
        @method('delete')
        <div class="form-control">
            <label for="delete_password" class="label"><span class="label-text">Password</span></label>
            <input id="delete_password" name="password" type="password" class="input input-bordered w-full" required placeholder="Password" />
            @error('password', 'userDeletion')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="modal-action">
          <button type="button" class="btn" onclick="delete_modal.close()">Batal</button>
          <button type="submit" class="btn btn-error">Hapus Akun</button>
        </div>
    </form>
  </div>
  <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

