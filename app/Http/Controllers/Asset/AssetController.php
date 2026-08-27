<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        $query = Asset::with('category:id,name,icon')->where('masjid_id', $masjidId);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->string('category_id'));
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->string('condition'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('asset_code', 'like', "%{$search}%"));
        }

        return Inertia::render('Asset/Index', [
            'assets' => $query->orderBy('name')->paginate(12)->withQueryString(),
            'filters' => $request->only(['category_id', 'condition', 'status', 'search']),
            'categories' => AssetCategory::where('masjid_id', $masjidId)->orderBy('name')->get(['id', 'name', 'icon']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Asset/Form', [
            'categories' => AssetCategory::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAsset($request);
        $masjidId = $request->user()->masjid_id;

        $category = AssetCategory::findOrFail($data['category_id']);

        Asset::create([
            ...$data,
            'masjid_id' => $masjidId,
            'asset_code' => $this->generateAssetCode($masjidId, $category->name),
        ]);

        return redirect()->route('admin.asset.inventaris.index')->with('success', 'Aset berhasil ditambahkan.');
    }

    public function edit(Request $request, Asset $inventaris): Response
    {
        $this->authorizeSameMasjid($request, $inventaris);

        return Inertia::render('Asset/Form', [
            'asset' => $inventaris,
            'categories' => AssetCategory::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Asset $inventaris): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $inventaris);

        $inventaris->update($this->validateAsset($request));

        return redirect()->route('admin.asset.inventaris.index')->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Request $request, Asset $inventaris): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $inventaris);

        $inventaris->delete();

        return back()->with('success', 'Aset berhasil dihapus.');
    }

    public function generateQr(Request $request, Asset $inventaris): Response
    {
        $this->authorizeSameMasjid($request, $inventaris);

        $qrSvg = base64_encode(QrCode::size(220)->generate(route('public.asset', $inventaris->asset_code)));

        return Inertia::render('Asset/QrLabel', [
            'asset' => $inventaris->only(['id', 'name', 'asset_code', 'location']),
            'masjidName' => $inventaris->masjid->name,
            'qrCode' => "data:image/svg+xml;base64,{$qrSvg}",
        ]);
    }

    /**
     * Asset tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak UUID
     * aset tenant B bisa lihat/ubah/hapus/cetak QR-nya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, Asset $asset): void
    {
        abort_unless($asset->masjid_id === $request->user()->masjid_id, 404);
    }

    private function generateAssetCode(string $masjidId, string $categoryName): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $categoryName), 0, 3)) ?: 'AST';
        $year = now()->year;

        $count = Asset::where('masjid_id', $masjidId)
            ->where('asset_code', 'like', "{$prefix}/{$year}/%")
            ->count();

        $sequence = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}/{$year}/{$sequence}";
    }

    private function validateAsset(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'uuid', 'exists:asset_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'condition' => ['required', 'in:baik,cukup,rusak_ringan,rusak_berat'],
            'status' => ['required', 'in:aktif,dipinjam,perbaikan,dihapus'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'maintenance_interval_days' => ['nullable', 'integer', 'min:1'],
            'next_maintenance_date' => ['nullable', 'date'],
        ]);
    }
}
