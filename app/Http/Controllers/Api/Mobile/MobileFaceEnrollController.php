<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\StaffFaceProfile;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MobileFaceEnrollController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $profile = StaffFaceProfile::where('user_id', $request->user()->id)->first();

        return response()->json([
            'enrolled' => (bool) $profile?->mobile_descriptor,
            'enrolled_at' => $profile?->enrolled_at,
        ]);
    }

    // Self-enrollment: ustadz sudah lolos otentikasi login, jadi mendaftarkan
    // wajahnya sendiri di sini.
    public function store(Request $request, FaceRecognitionService $faceService): JsonResponse
    {
        $data = $request->validate([
            'descriptor' => ['required', 'array'],
            'photo' => ['nullable', 'string'],
        ]);

        if (! $faceService->isValidDescriptor($data['descriptor'])) {
            return response()->json(['message' => 'Data wajah tidak valid. Coba lagi dengan pencahayaan yang lebih baik.'], 422);
        }

        $user = $request->user();

        $photoPath = null;
        if (! empty($data['photo'])) {
            $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['photo']));
            $photoPath = "face-enrollments/{$user->masjid_id}/{$user->id}.jpg";
            Storage::disk('local')->put($photoPath, $binary);
        }

        StaffFaceProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'masjid_id' => $user->masjid_id,
                'mobile_descriptor' => $data['descriptor'],
                'enrolled_at' => now(),
                ...($photoPath ? ['photo_path' => $photoPath] : []),
            ]
        );

        return response()->json(['message' => 'Wajah berhasil didaftarkan.']);
    }
}
