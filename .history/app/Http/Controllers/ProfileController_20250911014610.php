<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information (data dasar, avatar, dan/atau password).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // 1) Update data dasar (name, email)
        $user->fill($request->validated());

        // Jika email berubah, batalkan verifikasi
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // 2) Upload avatar (opsional)
        if ($request->hasFile('avatar')) {
            $request->validate([
                'avatar' => ['image', 'max:2048'], // 2MB
            ]);

            // Hapus avatar lama jika ada
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;            // kolom 'avatar' pada tabel users
            // Jika kamu juga punya kolom 'avatar_url', aktifkan 1 baris ini:
            // $user->avatar_url = Storage::url($path);
        }

        // 3) Ganti password (opsional)
        if ($request->filled('current_password') || $request->filled('new_password')) {
            $request->validate([
                'current_password'          => ['required', 'current_password'],
                'new_password'              => ['required', 'string', 'min:8', 'confirmed'],
                // field konfirmasi: new_password_confirmation
            ]);

            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // Hapus avatar file jika ada
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
