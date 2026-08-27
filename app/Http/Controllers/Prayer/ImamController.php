<?php

namespace App\Http\Controllers\Prayer;

use App\Http\Controllers\Controller;
use App\Models\Imam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImamController extends Controller
{
    public function index(Request $request): Response
    {
        $imams = Imam::where('masjid_id', $request->user()->masjid_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Prayer/Imam/Index', ['imams' => $imams]);
    }

    public function create(): Response
    {
        return Inertia::render('Prayer/Imam/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        Imam::create([...$this->validateImam($request), 'masjid_id' => $request->user()->masjid_id]);

        return redirect()->route('admin.prayer.imam.index')->with('success', 'Imam berhasil ditambahkan.');
    }

    public function edit(Request $request, Imam $imam): Response
    {
        $this->authorizeSameMasjid($request, $imam);

        return Inertia::render('Prayer/Imam/Form', ['imam' => $imam]);
    }

    public function update(Request $request, Imam $imam): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $imam);

        $imam->update($this->validateImam($request));

        return redirect()->route('admin.prayer.imam.index')->with('success', 'Data imam berhasil diperbarui.');
    }

    public function destroy(Request $request, Imam $imam): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $imam);

        $imam->delete();

        return back()->with('success', 'Imam berhasil dihapus.');
    }

    /**
     * Imam tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak UUID
     * imam tenant B bisa lihat/ubah/hapus datanya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, Imam $imam): void
    {
        abort_unless($imam->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateImam(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', 'in:tetap,cadangan,tamu'],
            'bio' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}
