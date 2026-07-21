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

    public function edit(Imam $imam): Response
    {
        return Inertia::render('Prayer/Imam/Form', ['imam' => $imam]);
    }

    public function update(Request $request, Imam $imam): RedirectResponse
    {
        $imam->update($this->validateImam($request));

        return redirect()->route('admin.prayer.imam.index')->with('success', 'Data imam berhasil diperbarui.');
    }

    public function destroy(Imam $imam): RedirectResponse
    {
        $imam->delete();

        return back()->with('success', 'Imam berhasil dihapus.');
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
