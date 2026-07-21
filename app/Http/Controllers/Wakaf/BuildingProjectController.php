<?php

namespace App\Http\Controllers\Wakaf;

use App\Http\Controllers\Controller;
use App\Models\BuildingProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuildingProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = BuildingProject::where('masjid_id', $request->user()->masjid_id)
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (BuildingProject $project) => [...$project->toArray(), 'funding_percent' => $project->fundingPercent()]);

        return Inertia::render('Wakaf/Projects/Index', ['projects' => $projects]);
    }

    public function create(): Response
    {
        return Inertia::render('Wakaf/Projects/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        BuildingProject::create([...$this->validateProject($request), 'masjid_id' => $request->user()->masjid_id]);

        return redirect()->route('admin.wakaf.proyek.index')->with('success', 'Proyek pembangunan berhasil dibuat.');
    }

    public function show(BuildingProject $proyek): Response
    {
        $proyek->load(['updates.poster:id,name']);

        return Inertia::render('Wakaf/Projects/Show', [
            'project' => [...$proyek->toArray(), 'funding_percent' => $proyek->fundingPercent()],
        ]);
    }

    public function edit(BuildingProject $proyek): Response
    {
        return Inertia::render('Wakaf/Projects/Form', ['project' => $proyek]);
    }

    public function update(Request $request, BuildingProject $proyek): RedirectResponse
    {
        $proyek->update($this->validateProject($request));

        return back()->with('success', 'Proyek pembangunan berhasil diperbarui.');
    }

    public function destroy(BuildingProject $proyek): RedirectResponse
    {
        $proyek->delete();

        return redirect()->route('admin.wakaf.proyek.index')->with('success', 'Proyek pembangunan berhasil dihapus.');
    }

    public function storeUpdate(Request $request, BuildingProject $proyek): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'progress_percent_at_time' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('wakaf-updates', 'public') : null;

        $proyek->updates()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'photo_path' => $photoPath,
            'progress_percent_at_time' => $data['progress_percent_at_time'] ?? null,
            'posted_by' => $request->user()->id,
        ]);

        if (! empty($data['progress_percent_at_time'])) {
            $proyek->update(['physical_progress_percent' => $data['progress_percent_at_time']]);
        }

        return back()->with('success', 'Update progress proyek berhasil ditambahkan.');
    }

    private function validateProject(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_amount' => ['required', 'numeric', 'min:0'],
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'physical_progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'start_date' => ['required', 'date'],
            'target_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:planning,ongoing,completed'],
        ]);
    }
}
