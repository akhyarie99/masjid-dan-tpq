<?php

namespace App\Http\Controllers\Wakaf;

use App\Http\Controllers\Controller;
use App\Models\WakafRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WakafController extends Controller
{
    public function index(Request $request): Response
    {
        $records = WakafRecord::where('masjid_id', $request->user()->masjid_id)
            ->orderByDesc('donated_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Wakaf/Index', ['records' => $records]);
    }

    public function create(): Response
    {
        return Inertia::render('Wakaf/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        WakafRecord::create([...$this->validateRecord($request), 'masjid_id' => $request->user()->masjid_id]);

        return redirect()->route('admin.wakaf.index')->with('success', 'Data wakaf berhasil dicatat.');
    }

    public function edit(WakafRecord $wakaf): Response
    {
        return Inertia::render('Wakaf/Form', ['record' => $wakaf]);
    }

    public function update(Request $request, WakafRecord $wakaf): RedirectResponse
    {
        $wakaf->update($this->validateRecord($request));

        return redirect()->route('admin.wakaf.index')->with('success', 'Data wakaf berhasil diperbarui.');
    }

    public function destroy(WakafRecord $wakaf): RedirectResponse
    {
        $wakaf->delete();

        return back()->with('success', 'Data wakaf berhasil dihapus.');
    }

    private function validateRecord(Request $request): array
    {
        return $request->validate([
            'wakif_name' => ['required', 'string', 'max:255'],
            'wakif_phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', 'in:tanah,bangunan,uang,lainnya'],
            'description' => ['nullable', 'string'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:proses,selesai'],
            'donated_date' => ['required', 'date'],
        ]);
    }
}
