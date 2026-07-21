<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqSettingController extends Controller
{
    public function edit(Request $request): Response
    {
        $setting = TpqSetting::firstOrCreate(
            ['masjid_id' => $request->user()->masjid_id],
            ['name' => 'TPQ '.$request->user()->masjid->name, 'head_name' => '-']
        );

        return Inertia::render('Learning/Tpq/Settings', ['setting' => $setting]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sk_number' => ['nullable', 'string', 'max:255'],
            'head_name' => ['required', 'string', 'max:255'],
            'head_nip' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'min_attendance_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'min_avg_grade' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $setting = TpqSetting::where('masjid_id', $request->user()->masjid_id)->firstOrFail();
        $setting->update($data);

        return back()->with('success', 'Pengaturan TPQ berhasil diperbarui.');
    }
}
