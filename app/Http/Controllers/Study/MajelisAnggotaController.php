<?php

namespace App\Http\Controllers\Study;

use App\Http\Controllers\Controller;
use App\Models\Majelis;
use App\Models\MajelisMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MajelisAnggotaController extends Controller
{
    public function store(Request $request, Majelis $majelis): RedirectResponse
    {
        $data = $this->validateMember($request);

        $majelis->members()->create($data);

        return back()->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function update(Request $request, Majelis $majelis, MajelisMember $anggota): RedirectResponse
    {
        $anggota->update($this->validateMember($request));

        return back()->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Majelis $majelis, MajelisMember $anggota): RedirectResponse
    {
        $anggota->delete();

        return back()->with('success', 'Anggota berhasil dihapus.');
    }

    private function validateMember(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'joined_date' => ['required', 'date'],
        ]);
    }
}
