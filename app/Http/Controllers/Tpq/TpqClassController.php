<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqClassController extends Controller
{
    public function index(Request $request): Response
    {
        $classes = TpqClass::where('masjid_id', $request->user()->masjid_id)
            ->withCount('studentClasses')
            ->with('teachers.teacher:id,name')
            ->orderBy('order')
            ->get();

        return Inertia::render('Learning/Tpq/Classes/Index', [
            'classes' => $classes,
            'teachers' => User::where('masjid_id', $request->user()->masjid_id)->role('ustadz')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TpqClass::create([...$this->validateClass($request), 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, TpqClass $kela): RedirectResponse
    {
        $kela->update($this->validateClass($request));

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(TpqClass $kela): RedirectResponse
    {
        $kela->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    private function validateClass(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['integer', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1'],
            'room' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
