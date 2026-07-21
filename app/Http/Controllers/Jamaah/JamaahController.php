<?php

namespace App\Http\Controllers\Jamaah;

use App\Http\Controllers\Controller;
use App\Imports\JamaahImport;
use App\Models\JamaahProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class JamaahController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = JamaahProfile::where('masjid_id', $request->user()->masjid_id);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('rt')) {
            $query->where('rt', $request->string('rt'));
        }
        if ($request->filled('rw')) {
            $query->where('rw', $request->string('rw'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        }

        return Inertia::render('Jamaah/Index', [
            'jamaah' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'rt', 'rw', 'search']),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Jamaah/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        JamaahProfile::create([...$this->validateJamaah($request), 'masjid_id' => $request->user()->masjid_id]);

        return redirect()->route('admin.jamaah.index')->with('success', 'Data jamaah berhasil ditambahkan.');
    }

    public function edit(JamaahProfile $jamaah): InertiaResponse
    {
        return Inertia::render('Jamaah/Form', ['jamaahProfile' => $jamaah]);
    }

    public function update(Request $request, JamaahProfile $jamaah): RedirectResponse
    {
        $jamaah->update($this->validateJamaah($request));

        return redirect()->route('admin.jamaah.index')->with('success', 'Data jamaah berhasil diperbarui.');
    }

    public function destroy(JamaahProfile $jamaah): RedirectResponse
    {
        $jamaah->delete();

        return back()->with('success', 'Data jamaah berhasil dihapus.');
    }

    public function card(JamaahProfile $jamaah): InertiaResponse
    {
        $qrSvg = base64_encode(QrCode::size(160)->generate($jamaah->id));

        return Inertia::render('Jamaah/Card', [
            'jamaah' => $jamaah->only(['id', 'name', 'photo', 'rt', 'rw']),
            'masjidName' => $jamaah->masjid->name,
            'qrCode' => "data:image/svg+xml;base64,{$qrSvg}",
        ]);
    }

    public function importTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $path = storage_path('app/templates/jamaah-import-template.csv');

        if (! file_exists($path)) {
            @mkdir(dirname($path), 0755, true);
            file_put_contents($path, "name,nik,birth_date,phone,address,rt,rw,status,tags\n");
        }

        return Response::download($path, 'template-import-jamaah.csv');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,xlsx,xls']]);

        $import = new JamaahImport($request->user()->masjid_id);
        Excel::import($import, $request->file('file'));

        return back()->with('success', "{$import->imported} data jamaah berhasil diimpor.");
    }

    private function validateJamaah(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:aktif,luar,musafir'],
            'tags' => ['nullable', 'array'],
            'receive_notification' => ['boolean'],
        ]);
    }
}
