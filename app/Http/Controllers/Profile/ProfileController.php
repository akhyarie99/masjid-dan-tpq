<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil akun sendiri — tanpa middleware permission apa pun, semua role
 * (termasuk ustadz) harus bisa lihat/ubah profilnya sendiri.
 */
class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Profile/Index', [
            'user' => $request->user()->only([
                'id', 'name', 'email', 'phone', 'birth_date', 'address', 'gender', 'avatar_url',
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:L,P'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => $request->file('avatar')->store('user-avatar', 'public')]);

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Kata sandi saat ini salah.',
            ]);
        }

        $user->update(['password' => $data['password']]);

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }
}
