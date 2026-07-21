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
        $penerima->update($this->validateRecipient($request));

        return back()->with('success', 'Data penerima zakat berhasil diperbarui.');
    }

    public function destroy(ZakatRecipient $penerima): RedirectResponse
    {
        $penerima->delete();

        return back()->with('success', 'Penerima zakat berhasil dihapus.');
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
