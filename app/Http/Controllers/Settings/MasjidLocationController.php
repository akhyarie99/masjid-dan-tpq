<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MasjidLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasjidLocationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Settings/Locations/Index', [
            'locations' => MasjidLocation::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MasjidLocation::create([...$this->validateLocation($request), 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Lokasi presensi berhasil ditambahkan.');
    }

    public function update(Request $request, MasjidLocation $location): RedirectResponse
    {
        abort_unless($location->masjid_id === $request->user()->masjid_id, 404);

        $location->update($this->validateLocation($request));

        return back()->with('success', 'Lokasi presensi berhasil diperbarui.');
    }

    public function destroy(Request $request, MasjidLocation $location): RedirectResponse
    {
        abort_unless($location->masjid_id === $request->user()->masjid_id, 404);

        $location->delete();

        return back()->with('success', 'Lokasi presensi berhasil dihapus.');
    }

    private function validateLocation(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
            'is_active' => ['boolean'],
        ]);
    }
}
