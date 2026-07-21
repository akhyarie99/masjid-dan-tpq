<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function index(Request $request): Response
    {
        $maintenances = AssetMaintenance::with(['asset:id,name,asset_code', 'reporter:id,name', 'handler:id,name'])
            ->whereHas('asset', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
            ->latest('scheduled_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Asset/Maintenance/Index', ['maintenances' => $maintenances]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Asset/Maintenance/Form', [
            'assets' => Asset::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(['id', 'name', 'asset_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateMaintenance($request);

        AssetMaintenance::create([
            ...$data,
            'reported_by' => $request->user()->id,
            'status' => 'scheduled',
        ]);

        return redirect()->route('admin.asset.maintenance.index')->with('success', 'Jadwal maintenance berhasil dibuat.');
    }

    public function edit(Request $request, AssetMaintenance $maintenance): Response
    {
        return Inertia::render('Asset/Maintenance/Form', [
            'maintenance' => $maintenance,
            'assets' => Asset::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(['id', 'name', 'asset_code']),
        ]);
    }

    public function update(Request $request, AssetMaintenance $maintenance): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:scheduled,repair,inspection'],
            'description' => ['required', 'string'],
            'action_taken' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:scheduled,in_progress,done'],
            'scheduled_date' => ['required', 'date'],
            'completed_date' => ['nullable', 'date'],
        ]);

        $maintenance->update([
            ...$data,
            'handled_by' => $data['status'] !== 'scheduled' ? $request->user()->id : $maintenance->handled_by,
        ]);

        if ($data['status'] === 'done' && $maintenance->asset->maintenance_interval_days) {
            $maintenance->asset->update([
                'next_maintenance_date' => now()->addDays($maintenance->asset->maintenance_interval_days),
            ]);
        }

        return redirect()->route('admin.asset.maintenance.index')->with('success', 'Maintenance berhasil diperbarui.');
    }

    public function destroy(AssetMaintenance $maintenance): RedirectResponse
    {
        $maintenance->delete();

        return back()->with('success', 'Jadwal maintenance berhasil dihapus.');
    }

    private function validateMaintenance(Request $request): array
    {
        return $request->validate([
            'asset_id' => ['required', 'uuid', 'exists:assets,id'],
            'type' => ['required', 'in:scheduled,repair,inspection'],
            'description' => ['required', 'string'],
            'scheduled_date' => ['required', 'date'],
        ]);
    }
}
