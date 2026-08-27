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
        $this->authorizeSameMasjid($request, $majelis);

        $data = $this->validateMember($request);

        $majelis->members()->create($data);

        return back()->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function update(Request $request, Majelis $majelis, MajelisMember $anggota): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $majelis);
        $this->authorizeMemberOfMajelis($majelis, $anggota);

        $anggota->update($this->validateMember($request));

        return back()->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Request $request, Majelis $majelis, MajelisMember $anggota): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $majelis);
        $this->authorizeMemberOfMajelis($majelis, $anggota);

        $anggota->delete();

        return back()->with('success', 'Anggota berhasil dihapus.');
    }

    /**
     * Majelis tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak UUID
     * majelis tenant B bisa menambah/ubah/hapus anggotanya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, Majelis $majelis): void
    {
        abort_unless($majelis->masjid_id === $request->user()->masjid_id, 404);
    }

    /**
     * Binding bersarang di sini tidak di-scope Laravel: {anggota} dicari cuma
     * berdasarkan id, tanpa memastikan dia benar-benar anggota {majelis}. Jadi
     * cek tenant di atas saja belum cukup — tanpa ini, UUID anggota majelis lain
     * (termasuk milik tenant lain) tetap bisa diubah/dihapus asalkan {majelis}
     * yang disebut di URL milik tenant sendiri. MajelisMember sendiri tidak punya
     * kolom masjid_id, jadi kepemilikannya memang harus lewat majelis induk.
     */
    private function authorizeMemberOfMajelis(Majelis $majelis, MajelisMember $member): void
    {
        abort_unless($member->majelis_id === $majelis->id, 404);
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
