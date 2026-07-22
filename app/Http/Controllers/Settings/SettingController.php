<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Masjid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Settings/Index', [
            'masjid' => $request->user()->masjid,
        ]);
    }

    public function updateMasjid(Request $request): RedirectResponse
    {
        $masjid = $request->user()->masjid;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'youtube' => ['nullable', 'string', 'max:255'],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'prayer_method' => ['required', 'in:kemenag,mwl,isna'],
        ]);

        $masjid->update($data);

        return back()->with('success', 'Profil masjid berhasil diperbarui.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $masjid = $request->user()->masjid;

        $data = $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        if ($masjid->logo) {
            Storage::disk('public')->delete($masjid->logo);
        }

        $masjid->update(['logo' => $request->file('logo')->store('masjid-logo', 'public')]);

        return back()->with('success', 'Logo masjid berhasil diperbarui.');
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        $masjid = $request->user()->masjid;

        if ($masjid->logo) {
            Storage::disk('public')->delete($masjid->logo);
            $masjid->update(['logo' => null]);
        }

        return back()->with('success', 'Logo masjid berhasil dihapus.');
    }
}
