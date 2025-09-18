@extends('layouts.app')

@section('title', 'Waha Senders - Matik Growth Hub')

@section('content')
<div class="container mx-auto py-6">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 0 0118 0z" /></svg>
                <span><strong>Terjadi kesalahan:</strong>
                    <ul class="list-disc ml-5">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </span>
            </div>
        </div>
    @endif

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">Waha Senders</h1>
            <p class="mt-1 text-base-content/70">Kelola nomor pengirim & verifikasi sesi lewat Scan QR.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="create_sender_modal.showModal()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Tambah Sender
        </button>
    </div>

    @if($senders->isEmpty())
        {{-- Tampilan state kosong --}}
        <div class="text-center py-20 card bg-base-100 mt-8 border border-base-300/50 shadow-lg" data-aos="fade-up">
            <div class="card-body items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <h3 class="mt-4 text-lg font-medium text-base-content">Belum ada sender</h3>
                <p class="mt-1 text-sm text-base-content/60">Tambahkan minimal satu nomor pengirim untuk mulai mengirim pesan.</p>
            </div>
        </div>
    @else
        {{-- Tabel Senders --}}
        <div class="card bg-base-100 shadow-lg border border-base-300/50 mt-8" data-aos="fade-up">
            {{-- FIX: izinkan overflow vertikal agar dropdown tidak terpotong --}}
            <div class="overflow-x-auto overflow-y-visible">
                {{-- FIX: min-w-max menjaga lebar tabel saat scroll horizontal --}}
                <table class="table w-full min-w-max">
                    <thead>
                    <tr>
                        <th class="w-12 text-center" title="Default">⭐</th>
                        <th>Nama</th>
                        <th>Nomor & Sesi</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($senders as $s)
                        <tr class="hover">
                            <td class="w-14 text-center">@if($s->is_default) <span class="text-lg">⭐</span> @endif</td>
                            <td>
                                <div class="font-semibold text-base-content">{{ $s->name }}</div>
                                @if($s->description)
                                    <div class="text-xs text-base-content/60">{{ $s->description }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $s->number ?? '—' }}</div>
                                <div class="font-mono text-xs text-base-content/60">{{ $s->session ?? $s->session_name ?? '—' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $s->is_active ? 'badge-success' : 'badge-ghost' }}">
                                    {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex gap-2 justify-end">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openAndStartScan({{ $s->id }}, @js($s->name))">
                                        Scan / Connect
                                    </button>
                                    {{-- FIX: arah aman di kanan, tetap bisa kiri di desktop --}}
                                    <div class="dropdown dropdown-end md:dropdown-left">
                                        <label tabindex="0" class="btn btn-ghost btn-sm">Opsi</label>
                                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-50 border border-base-300/50">
                                            <li><button type="button" onclick="document.getElementById('edit_sender_{{ $s->id }}').showModal()">Edit</button></li>
                                            <li>
                                                <a href="#" onclick="event.preventDefault(); document.getElementById('form-default-{{ $s->id }}').submit();" class="{{ $s->is_default ? 'disabled' : '' }}">Jadikan Default</a>
                                            </li>
                                            <div class="divider my-1"></div>
                                            <li>
                                                <a href="#" onclick="event.preventDefault(); if(confirm('Hapus sender ini?')) document.getElementById('form-delete-{{ $s->id }}').submit();" class="text-error">Hapus</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-10 text-base-content/60">Tidak ada sender.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
             @if ($senders->hasPages())
                <div class="p-4 border-t border-base-300/50">
                    {{ $senders->links() }}
                </div>
            @endif
        </div>

        {{-- Form tersembunyi untuk aksi dropdown --}}
        @foreach($senders as $s)
            <form id="form-default-{{ $s->id }}" action="{{ route('waha-senders.set-default', $s) }}" method="POST" class="hidden">@csrf</form>
            <form id="form-delete-{{ $s->id }}" action="{{ route('waha-senders.destroy', $s) }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
        @endforeach
    @endif
</div>

{{-- Modals --}}
<dialog id="create_sender_modal" class="modal">
    <div class="modal-box max-w-xl">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
        <h3 class="font-bold text-lg text-base-content">Tambah Sender Baru</h3>
        <form action="{{ route('waha-senders.store') }}" method="POST" class="mt-4">
            @csrf
            <div class="py-4 space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" class="input input-bordered w-full" required placeholder="Contoh: Akun Admin 1">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Deskripsi (opsional)</span></label>
                    <input type="text" name="description" class="input input-bordered w-full" placeholder="Untuk keperluan apa sender ini">
                </div>
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="is_default" value="1" class="checkbox checkbox-primary">
                        <span class="label-text">Jadikan sender default</span>
                    </label>
                </div>
                <p class="text-xs text-base-content/60">Nomor & session akan terisi otomatis setelah Anda berhasil Scan QR.</p>
            </div>
            <div class="modal-action">
                <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@foreach ($senders as $s)
    <dialog id="edit_sender_{{ $s->id }}" class="modal">
        <div class="modal-box max-w-xl">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
            <h3 class="font-bold text-lg text-base-content">Edit Sender</h3>
            <form action="{{ route('waha-senders.update', $s) }}" method="POST" class="mt-4">
                @csrf @method('PATCH')
                <div class="py-4 space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Nama</span></label>
                        <input type="text" name="name" class="input input-bordered w-full" value="{{ old('name', $s->name) }}" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Deskripsi (opsional)</span></label>
                        <input type="text" name="description" class="input input-bordered w-full" value="{{ old('description', $s->description) }}">
                    </div>
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" name="is_default" value="1" @checked($s->is_default) class="checkbox checkbox-primary">
                            <span class="label-text">Jadikan sender default</span>
                        </label>
                    </div>
                </div>
                <div class="modal-action">
                    <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </dialog>
    <dialog id="scan_sender_{{ $s->id }}" class="modal">
        <div class="modal-box max-w-2xl relative overflow-hidden">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
            <h3 class="font-bold text-lg text-base-content">Scan / Connect — <span id="scan-name-{{ $s->id }}">{{ $s->name }}</span></h3>
            <div id="scan-loading-{{ $s->id }}" class="py-10 text-center"><span class="loading loading-spinner loading-lg"></span><div class="mt-3 text-sm">Memeriksa status sesi...</div></div>
            <div id="scan-error-{{ $s->id }}" class="alert alert-error my-4 hidden"></div>
            <div id="scan-qr-wrap-{{ $s->id }}" class="mt-3 hidden text-center">
                <p class="text-sm mb-2">Scan QR berikut menggunakan WhatsApp.</p>
                <img id="scan-qr-{{ $s->id }}" alt="QR Code" class="w-72 h-72 mx-auto rounded-lg shadow border" />
            </div>
            <div class="mt-4"><button type="button" class="btn btn-outline btn-sm" onclick="requestCode({{ $s->id }})">Minta Kode Pairing</button></div>
            <div id="scan-code-wrap-{{ $s->id }}" class="mt-3 hidden">
                <p class="text-sm mb-2">Atau tautkan dengan kode berikut di WhatsApp (Linked Devices → Link with phone number).</p>
                <div class="flex items-center gap-3">
                    <div id="scan-code-{{ $s->id }}" class="text-3xl font-mono tracking-widest p-3 bg-base-200 rounded-lg">———</div>
                    <button type="button" class="btn btn-xs" onclick="copyText('scan-code-{{ $s->id }}')">Salin</button>
                </div>
            </div>
            <div class="mt-4">Status: <div class="badge" id="scan-state-{{ $s->id }}">—</div></div>
            <div id="scan-success-{{ $s->id }}" class="hidden text-center py-8">
                <div class="mx-auto w-20 h-20 rounded-full bg-success/10 flex items-center justify-center"><svg class="w-12 h-12 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <h4 class="mt-4 text-xl font-semibold">Berhasil Tersambung</h4>
                <p class="text-sm text-base-content/70">Nomor Anda sudah aktif & siap dipakai 🎉</p>
            </div>
            <div id="scan-confetti-{{ $s->id }}" class="pointer-events-none absolute inset-0 z-50 hidden"></div>
            <div class="modal-action"><form method="dialog"><button class="btn">Tutup</button></form></div>
        </div>
    </dialog>
@endforeach

<style>
@keyframes confetti-fall { 0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; } 100% { transform: translateY(100vh)  rotate(720deg); opacity: 1; } }
.confetti { position: absolute; width: 8px; height: 14px; borde
