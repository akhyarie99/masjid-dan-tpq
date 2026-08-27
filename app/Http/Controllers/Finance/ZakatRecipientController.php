<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ZakatRecipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ZakatRecipientController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Finance/Zakat/Recipients', [
            'recipients' => ZakatRecipient::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ZakatRecipient::create([...$this->validateRecipient($request), 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Penerima zakat berhasil ditambahkan.');
    }

    public function update(Request $request, ZakatRecipient $penerima): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $penerima);

        $penerima->update($this->validateRecipient($request));

        return back()->with('success', 'Data penerima zakat berhasil diperbarui.');
    }

    public function destroy(Request $request, ZakatRecipient $penerima): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $penerima);

        $penerima->delete();

        return back()->with('success', 'Penerima zakat berhasil dihapus.');
    }

    /**
     * ZakatRecipient tidak punya global scope tenant — route-model binding cuma
     * cari berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak
     * UUID mustahik tenant B bisa ubah/hapus datanya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, ZakatRecipient $recipient): void
    {
        abort_unless($recipient->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateRecipient(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string'],
            'category' => ['required', 'in:fakir,miskin,amil,muallaf,riqab,gharimin,fisabilillah,ibnus_sabil'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}
