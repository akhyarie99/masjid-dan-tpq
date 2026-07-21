<?php

namespace App\Http\Controllers\Ramadhan;

use App\Http\Controllers\Controller;
use App\Models\ItikafRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItikafController extends Controller
{
    public function index(Request $request): Response
    {
        $registrations = ItikafRegistration::where('masjid_id', $request->user()->masjid_id)
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Ramadhan/Itikaf/Index', ['registrations' => $registrations]);
    }

    public function store(Request $request): RedirectResponse
    {
        ItikafRegistration::create([...$this->validateRegistration($request), 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Pendaftaran itikaf berhasil dicatat.');
    }

    public function update(Request $request, ItikafRegistration $itikaf): RedirectResponse
    {
        $itikaf->update($this->validateRegistration($request));

        return back()->with('success', 'Data itikaf berhasil diperbarui.');
    }

    public function destroy(ItikafRegistration $itikaf): RedirectResponse
    {
        $itikaf->delete();

        return back()->with('success', 'Pendaftaran itikaf berhasil dihapus.');
    }

    private function validateRegistration(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,confirmed,cancelled'],
        ]);
    }
}
