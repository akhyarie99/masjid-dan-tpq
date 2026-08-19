<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\StaffFaceProfile;
use App\Services\FaceRecognitionService;
use App\Services\LocationMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileStaffAttendanceController extends Controller
{
    public function __construct(
        private LocationMatchingService $locationMatcher,
    ) {}

    public function today(Request $request): JsonResponse
    {
        $attendance = StaffAttendance::where('user_id', $request->user()->id)->where('date', today())->first();

        return response()->json(['attendance' => $attendance ? $this->format($attendance) : null]);
    }

    public function history(Request $request): JsonResponse
    {
        $attendances = StaffAttendance::where('user_id', $request->user()->id)
            ->orderByDesc('date')
            ->limit(31)
            ->get()
            ->map(fn (StaffAttendance $a) => $this->format($a));

        return response()->json(['attendances' => $attendances]);
    }

    // Presensi Masuk — GPS DAN wajah divalidasi bersamaan dalam satu aksi. Sinyal
    // anti-fake-GPS dicek paling awal karena murah dan harus ditolak keras sebelum
    // kerja lain (pencocokan wajah, dll) dilakukan.
    public function clockIn(Request $request, FaceRecognitionService $faceService): JsonResponse
    {
        $data = $this->validateClock($request);

        if ($data['is_mock_location']) {
            return response()->json(['message' => 'Lokasi GPS terdeteksi palsu (mock location). Matikan aplikasi fake GPS / mode developer lalu coba lagi.'], 422);
        }

        if (! $data['liveness_verified']) {
            return response()->json(['message' => 'Kedipan mata alami tidak terdeteksi. Pastikan wajah Anda terlihat jelas dan coba lagi.'], 422);
        }

        $user = $request->user();

        $todayAttendance = StaffAttendance::where('user_id', $user->id)->where('date', today())->first();
        if ($todayAttendance && $todayAttendance->clock_in && $todayAttendance->clock_out) {
            return response()->json(['message' => 'Anda sudah menyelesaikan presensi hari ini (masuk & keluar). Presensi berikutnya baru bisa besok.'], 422);
        }

        $faceResult = $this->verifyFace($user->id, $data['descriptor'], $faceService);
        if (! $faceResult['success']) {
            return response()->json(['message' => $faceResult['message']], 422);
        }

        $result = $this->locationMatcher->match($user->masjid_id, (float) $data['lat'], (float) $data['lng']);
        $matchedLocation = $result['matched'];

        if (! $matchedLocation && $result['nearest']) {
            $nearestDistance = round($result['nearestDistance']);
            $nearest = $result['nearest'];

            return response()->json([
                'message' => "Anda berada {$nearestDistance}m dari lokasi terdekat ({$nearest->name}). Maksimal {$nearest->radius_meters}m.",
            ], 422);
        }

        $attendance = StaffAttendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => today(), 'masjid_id' => $user->masjid_id],
            [
                'clock_in' => now(),
                'clock_in_lat' => $data['lat'],
                'clock_in_lng' => $data['lng'],
                'clock_in_photo' => ! empty($data['photo']) ? $this->savePhoto($data['photo']) : null,
                'clock_in_location_id' => $matchedLocation?->id,
                'clock_in_is_mock_location' => false,
                'clock_in_gps_accuracy' => $data['gps_accuracy'] ?? null,
                'clock_in_device_info' => $data['device_info'] ?? null,
                'clock_in_liveness_verified' => true,
            ]
        );

        return response()->json([
            'message' => $matchedLocation ? "Presensi berhasil di {$matchedLocation->name}!" : 'Presensi berhasil!',
            'attendance' => $this->format($attendance),
        ]);
    }

    public function clockOut(Request $request, FaceRecognitionService $faceService): JsonResponse
    {
        $data = $this->validateClock($request);

        if ($data['is_mock_location']) {
            return response()->json(['message' => 'Lokasi GPS terdeteksi palsu (mock location). Matikan aplikasi fake GPS / mode developer lalu coba lagi.'], 422);
        }

        if (! $data['liveness_verified']) {
            return response()->json(['message' => 'Kedipan mata alami tidak terdeteksi. Pastikan wajah Anda terlihat jelas dan coba lagi.'], 422);
        }

        $user = $request->user();

        $faceResult = $this->verifyFace($user->id, $data['descriptor'], $faceService);
        if (! $faceResult['success']) {
            return response()->json(['message' => $faceResult['message']], 422);
        }

        $attendance = StaffAttendance::where('user_id', $user->id)->where('date', today())->first();

        if (! $attendance || ! $attendance->clock_in) {
            return response()->json(['message' => 'Anda belum melakukan presensi masuk hari ini.'], 422);
        }

        if ($attendance->clock_out) {
            return response()->json(['message' => 'Anda sudah melakukan presensi keluar hari ini.'], 422);
        }

        $nearestLocation = $this->locationMatcher->nearestWithinRadius($user->masjid_id, (float) $data['lat'], (float) $data['lng']);

        $attendance->update([
            'clock_out' => now(),
            'clock_out_lat' => $data['lat'],
            'clock_out_lng' => $data['lng'],
            'clock_out_photo' => ! empty($data['photo']) ? $this->savePhoto($data['photo']) : null,
            'clock_out_location_id' => $nearestLocation?->id,
            'clock_out_is_mock_location' => false,
            'clock_out_gps_accuracy' => $data['gps_accuracy'] ?? null,
            'clock_out_device_info' => $data['device_info'] ?? null,
            'clock_out_liveness_verified' => true,
        ]);

        return response()->json([
            'message' => 'Presensi keluar berhasil!',
            'attendance' => $this->format($attendance->fresh()),
        ]);
    }

    private function validateClock(Request $request): array
    {
        return $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'is_mock_location' => ['required', 'boolean'],
            'gps_accuracy' => ['nullable', 'numeric'],
            'device_info' => ['nullable', 'array'],
            'descriptor' => ['required', 'array'],
            'photo' => ['nullable', 'string'],
            'liveness_verified' => ['required', 'boolean'],
        ]);
    }

    /**
     * @return array{success:bool, message?:string, distance?:float}
     */
    private function verifyFace(string $userId, array $capturedDescriptor, FaceRecognitionService $faceService): array
    {
        if (! $faceService->isValidDescriptor($capturedDescriptor)) {
            return ['success' => false, 'message' => 'Data wajah tidak valid. Coba lagi dengan pencahayaan yang lebih baik.'];
        }

        $profile = StaffFaceProfile::where('user_id', $userId)->first();

        if (! $profile || ! $profile->mobile_descriptor) {
            return ['success' => false, 'message' => 'Wajah Anda belum terdaftar. Daftarkan dulu di menu Daftarkan Wajah.'];
        }

        if (count($capturedDescriptor) !== count($profile->mobile_descriptor)) {
            return ['success' => false, 'message' => 'Data wajah tidak sebanding dengan data terdaftar. Coba enroll ulang.'];
        }

        $threshold = (float) config('face.mobile_match_threshold');
        $distance = round($faceService->distance($capturedDescriptor, $profile->mobile_descriptor), 4);

        if (! $faceService->isMatch($capturedDescriptor, $profile->mobile_descriptor, $threshold)) {
            return ['success' => false, 'message' => "Wajah tidak cocok (jarak: {$distance}). Silakan coba lagi.", 'distance' => $distance];
        }

        return ['success' => true, 'distance' => $distance];
    }

    private function format(StaffAttendance $attendance): array
    {
        return [
            'date' => $attendance->date->format('Y-m-d'),
            'clock_in' => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,
            'clock_in_location' => $attendance->clockInLocation?->name,
            'clock_out_location' => $attendance->clockOutLocation?->name,
        ];
    }

    private function savePhoto(string $base64): string
    {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $path = 'staff-attendance-photos/'.date('Y/m').'/'.Str::uuid().'.jpg';
        Storage::disk('local')->put($path, base64_decode($data));

        return $path;
    }
}
