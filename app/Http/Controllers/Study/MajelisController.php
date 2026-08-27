<?php

namespace App\Http\Controllers\Study;

use App\Http\Controllers\Controller;
use App\Models\Majelis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MajelisController extends Controller
{
    public function index(Request $request): Response
    {
        $majelis = Majelis::where('masjid_id', $request->user()->masjid_id)
            ->withCount('members')
            ->orderBy('name')
            ->get();

        return Inertia::render('Study/Majelis/Index', ['majelisList' => $majelis]);
    }

    public function store(Request $request): RedirectResponse
    {
        Majelis::create([...$this->validateMajelis($request), 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Majelis taklim berhasil ditambahkan.');
    }

    public function show(Request $request, Majelis $majelis): Response
    {
        $this->authorizeSameMasjid($request, $majelis);

        return Inertia::render('Study/Majelis/Show', [
            'majelis' => $majelis,
            'members' => $majelis->members()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Majelis $majelis): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $majelis);

        $majelis->update($this->validateMajelis($request));

        return back()->with('success', 'Majelis taklim berhasil diperbarui.');
    }

    public function destroy(Request $request, Majelis $majelis): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $majelis);

        $majelis->delete();

        return back()->with('success', 'Majelis taklim berhasil dihapus.');
    }

    /**
     * Majelis tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak UUID
     * majelis tenant B bisa lihat/ubah/hapus beserta daftar anggotanya lewat
     * URL langsung.
     */
    private function authorizeSameMasjid(Request $request, Majelis $majelis): void
    {
        abort_unless($majelis->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateMajelis(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'leader_name' => ['nullable', 'string', 'max:255'],
            'leader_phone' => ['nullable', 'string', 'max:30'],
            'meeting_schedule' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
