<?php

namespace Modules\Absensi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Absensi\Entities\PresensiSiswaHarian;
use Modules\Absensi\Entities\PresensiPtkHarian;
use Modules\Absensi\Entities\LokasiAbsensiSetting;
use Modules\Absensi\Entities\PengajuanIzinCuti;
use Modules\Absensi\Entities\PresensiFraudLog;
use Modules\Siswa\Entities\Siswa;
use Modules\Kepegawaian\Entities\Gtk;
use Modules\Core\Services\SecurityPayloadService;
use Illuminate\Support\Str;

class PresensiController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $tab = $request->input('tab', 'siswa');

        if ($request->wantsJson() || $request->ajax() || $request->has('async')) {
            $data = match ($tab) {
                'gtk' => $this->getGtkPresensiData($request),
                'izin' => $this->getIzinData($request),
                'setting' => $this->getSettingData($request),
                'rekap' => $this->getRekapData($request),
                'fraud' => $this->getFraudData($request),
                default => $this->getSiswaPresensiData($request),
            };

            return response()->json([
                'success' => true,
                'tab'     => $tab,
                'data'    => SecurityPayloadService::sanitize($data),
            ]);
        }

        return Inertia::render('Absensi/Index', [
            'initialTab' => $tab,
        ]);
    }

    private function getSiswaPresensiData(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());

        $items = PresensiSiswaHarian::with('siswa')
            ->where('tanggal', $tanggal)
            ->when($request->search, function ($q, $search) {
                $q->where('nama_siswa', 'ILIKE', "%{$search}%")
                  ->orWhere('nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('nama_kelas', 'ILIKE', "%{$search}%");
            })
            ->when($request->status_kehadiran, fn($q, $st) => $q->where('status_kehadiran', $st))
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $stats = [
            'total_siswa' => Siswa::where('status_siswa', 'Aktif')->count(),
            'hadir' => PresensiSiswaHarian::where('tanggal', $tanggal)->where('status_kehadiran', 'Hadir')->count(),
            'sakit_izin' => PresensiSiswaHarian::where('tanggal', $tanggal)->whereIn('status_kehadiran', ['Sakit', 'Izin', 'Dispensasi'])->count(),
            'alpa' => PresensiSiswaHarian::where('tanggal', $tanggal)->where('status_kehadiran', 'Alpa')->count(),
            'terlambat' => PresensiSiswaHarian::where('tanggal', $tanggal)->where('status_kehadiran', 'Terlambat')->count(),
            'fraud_count' => PresensiFraudLog::whereDate('created_at', $tanggal)->count(),
        ];

        $siswaList = Siswa::select('id', 'nama_lengkap', 'nisn', 'kelas_saat_ini')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->limit(100)
            ->get();

        $setting = LokasiAbsensiSetting::first();

        return compact('items', 'stats', 'tanggal', 'siswaList', 'setting');
    }

    private function getGtkPresensiData(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());

        $items = PresensiPtkHarian::with('gtk')
            ->where('tanggal', $tanggal)
            ->when($request->search, function ($q, $search) {
                $q->where('nama_ptk', 'ILIKE', "%{$search}%")
                  ->orWhere('nip', 'ILIKE', "%{$search}%");
            })
            ->when($request->status_kehadiran, fn($q, $st) => $q->where('status_kehadiran', $st))
            ->orderBy('jam_masuk', 'desc')
            ->paginate(25);

        $stats = [
            'total_gtk' => Gtk::count(),
            'hadir' => PresensiPtkHarian::where('tanggal', $tanggal)->where('status_kehadiran', 'Hadir')->count(),
            'luar_radius' => PresensiPtkHarian::where('tanggal', $tanggal)->where('status_geofence', 'Luar Radius')->count(),
            'cuti_dinas' => PresensiPtkHarian::where('tanggal', $tanggal)->whereIn('status_kehadiran', ['Cuti', 'Dinas Luar', 'Izin'])->count(),
        ];

        $gtkList = Gtk::select('id', 'nama_lengkap', 'nip', 'jabatan')
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $setting = LokasiAbsensiSetting::first();

        return compact('items', 'stats', 'tanggal', 'gtkList', 'setting');
    }

    private function getIzinData(Request $request)
    {
        $items = PengajuanIzinCuti::query()
            ->when($request->status_persetujuan, fn($q, $st) => $q->where('status_persetujuan', $st))
            ->when($request->search, function ($q, $search) {
                $q->where('nama_pemohon', 'ILIKE', "%{$search}%")
                  ->orWhere('alasan', 'ILIKE', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return compact('items');
    }

    private function getSettingData(Request $request)
    {
        $setting = LokasiAbsensiSetting::first() ?? LokasiAbsensiSetting::create([
            'nama_lokasi'               => 'Gedung Utama Sekolah',
            'latitude_pusat'            => -6.20880000,
            'longitude_pusat'           => 106.84560000,
            'radius_meter'              => 150,
            'jam_masuk_normal'          => '07:00',
            'jam_pulang_normal'         => '15:30',
            'toleransi_terlambat_menit' => 15,
        ]);

        return compact('setting');
    }

    private function getRekapData(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));

        $rekapSiswa = PresensiSiswaHarian::selectRaw("
            siswa_id, nama_siswa, nama_kelas,
            COUNT(CASE WHEN status_kehadiran = 'Hadir' THEN 1 END) as hadir,
            COUNT(CASE WHEN status_kehadiran = 'Sakit' THEN 1 END) as sakit,
            COUNT(CASE WHEN status_kehadiran = 'Izin' THEN 1 END) as izin,
            COUNT(CASE WHEN status_kehadiran = 'Alpa' THEN 1 END) as alpa,
            COUNT(CASE WHEN status_kehadiran = 'Terlambat' THEN 1 END) as terlambat
        ")
        ->whereRaw("TO_CHAR(tanggal, 'YYYY-MM') = ?", [$bulan])
        ->groupBy('siswa_id', 'nama_siswa', 'nama_kelas')
        ->orderBy('nama_siswa', 'asc')
        ->paginate(20);

        return compact('rekapSiswa', 'bulan');
    }

    private function getFraudData(Request $request)
    {
        $items = PresensiFraudLog::query()
            ->when($request->fraud_type, fn($q, $ft) => $q->where('fraud_type', $ft))
            ->when($request->search, function ($q, $search) {
                $q->where('nama_pelaku', 'ILIKE', "%{$search}%")
                  ->orWhere('identifier', 'ILIKE', "%{$search}%")
                  ->orWhere('fraud_reason', 'ILIKE', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_fraud'     => PresensiFraudLog::count(),
            'mock_gps'        => PresensiFraudLog::where('fraud_type', 'MOCK_PROVIDER')->orWhere('fraud_type', 'FAKE_GPS')->count(),
            'outside_radius'  => PresensiFraudLog::where('fraud_type', 'OUTSIDE_GEOFENCE')->count(),
            'accuracy_anomaly'=> PresensiFraudLog::where('fraud_type', 'ACCURACY_ANOMALY')->count(),
            'device_emulation'=> PresensiFraudLog::where('fraud_type', 'DEVICE_EMULATION')->count(),
        ];

        return compact('items', 'stats');
    }

    /**
     * Hitung Jarak Haversine (meter)
     */
    private function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos(max(-1.0, min(1.0, $dist)));
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return (int) round($miles * 1609.344);
    }

    /**
     * Engine Deteksi Anti-Fraud & Validasi GPS
     */
    private function inspectFraud(Request $request, ?Siswa $siswa, float $lat, float $lng, ?float $accuracy, int $distMeters, int $maxRadius): ?array
    {
        $userAgent = $request->userAgent() ?? '';

        // 1. Check Plausibility Coordinate Range
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) {
            return [
                'type'   => 'FAKE_GPS',
                'reason' => 'Koordinat tidak valid atau berada pada titik nol (Null Island).',
                'details'=> ['latitude' => $lat, 'longitude' => $lng]
            ];
        }

        // 2. Check Mock Provider / Fake GPS Flags from Frontend
        if ($request->boolean('is_mock') || $request->boolean('is_mock_location') || $request->input('is_mock') === 'true') {
            return [
                'type'   => 'MOCK_PROVIDER',
                'reason' => 'Terdeteksi penggunaan aplikasi Fake GPS / Mock Location Provider pada perangkat.',
                'details'=> ['is_mock' => true, 'mock_engine' => $request->input('mock_engine', 'Android Mock Provider')]
            ];
        }

        // 3. Check Headless / Webdriver / Emulation
        if ($request->boolean('is_webdriver') || preg_match('/(HeadlessChrome|PhantomJS|Selenium|Puppeteer|Nightmare)/i', $userAgent)) {
            return [
                'type'   => 'DEVICE_EMULATION',
                'reason' => 'Terdeteksi lingkungan virtual / emulator / automated webdriver.',
                'details'=> ['user_agent' => $userAgent]
            ];
        }

        // 4. Check Accuracy Radius Anomaly
        if ($accuracy !== null) {
            if ($accuracy > 250) {
                return [
                    'type'   => 'ACCURACY_ANOMALY',
                    'reason' => "Akurasi GPS terlalu lemah ({$accuracy}m > batas aman 250m). Mohon aktifkan mode High Accuracy GPS.",
                    'details'=> ['accuracy' => $accuracy]
                ];
            }
            if ($accuracy === 0.0) {
                return [
                    'type'   => 'FAKE_GPS',
                    'reason' => 'Akurasi GPS bernilai tepat 0.0m (indikasi simulasi software GPS).',
                    'details'=> ['accuracy' => $accuracy]
                ];
            }
        }

        // 5. Check Geofence Radius
        if ($distMeters > $maxRadius) {
            return [
                'type'   => 'OUTSIDE_GEOFENCE',
                'reason' => "Posisi Anda berada di luar radius sekolah ({$distMeters} meter dari titik pusat, batas maksimal {$maxRadius} meter).",
                'details'=> ['jarak_meter' => $distMeters, 'max_radius' => $maxRadius]
            ];
        }

        return null;
    }

    /**
     * Presensi GPS Siswa (Mandiri / Web / Mobile) dengan Anti-Fraud
     */
    public function presensiSiswaGps(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id'        => 'required|uuid',
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',
            'akurasi_meter'   => 'nullable|numeric',
            'is_mock'         => 'nullable|boolean',
            'device_info'     => 'nullable|string',
        ]);

        $siswa = Siswa::find($validated['siswa_id']);
        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $tenantId = $siswa->tenant_id ?? auth()->user()?->tenant_id ?? '00000000-0000-0000-0000-000000000000';
        $setting = LokasiAbsensiSetting::first();
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $lat1 = (float) $validated['latitude'];
        $lon1 = (float) $validated['longitude'];
        $lat2 = $setting ? (float)$setting->latitude_pusat : -6.2088;
        $lon2 = $setting ? (float)$setting->longitude_pusat : 106.8456;
        $maxRadius = $setting ? (int)$setting->radius_meter : 150;
        $accuracy = isset($validated['akurasi_meter']) ? (float)$validated['akurasi_meter'] : null;

        $distanceMeters = $this->calculateDistanceMeters($lat1, $lon1, $lat2, $lon2);

        // Evaluasi Anti-Fraud
        $fraud = $this->inspectFraud($request, $siswa, $lat1, $lon1, $accuracy, $distanceMeters, $maxRadius);

        if ($fraud) {
            // Log Fraud Attempt ke database
            PresensiFraudLog::create([
                'tenant_id'      => $tenantId,
                'siswa_id'       => $siswa->id,
                'tipe_pengguna'  => 'siswa',
                'nama_pelaku'    => $siswa->nama_lengkap,
                'identifier'     => $siswa->nisn,
                'latitude'       => $lat1,
                'longitude'      => $lon1,
                'jarak_meter'    => $distanceMeters,
                'akurasi_meter'  => $accuracy,
                'fraud_type'     => $fraud['type'],
                'fraud_reason'   => $fraud['reason'],
                'fraud_details'  => $fraud['details'],
                'device_info'    => $validated['device_info'] ?? $request->userAgent(),
                'ip_address'     => $request->ip(),
                'is_blocked'     => true,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success'     => false,
                    'is_fraud'    => true,
                    'fraud_type'  => $fraud['type'],
                    'message'     => $fraud['reason'],
                    'jarak_meter' => $distanceMeters,
                    'max_radius'  => $maxRadius,
                ], 422);
            }

            return back()->withErrors(['lokasi' => $fraud['reason']]);
        }

        // Tentukan Status Kehadiran (Tepat Waktu vs Terlambat)
        $jamMasukNormal = $setting ? $setting->jam_masuk_normal : '07:00';
        $toleransi = $setting ? (int)$setting->toleransi_terlambat_menit : 15;
        
        $batasWaktu = date('H:i', strtotime("+{$toleransi} minutes", strtotime($jamMasukNormal)));
        $statusKehadiran = ($nowTime <= $batasWaktu) ? 'Hadir' : 'Terlambat';

        $presensi = PresensiSiswaHarian::updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'tanggal'  => $today,
            ],
            [
                'tenant_id'        => $tenantId,
                'nama_siswa'       => $siswa->nama_lengkap,
                'nisn'             => $siswa->nisn,
                'kelas_id'         => $siswa->kelas_saat_ini_id ?? null,
                'nama_kelas'       => $siswa->kelas_saat_ini ?? '',
                'jam_masuk'        => $nowTime,
                'status_kehadiran' => $statusKehadiran,
                'metode_presensi'  => 'Geolokasi_GPS',
                'latitude'         => $lat1,
                'longitude'        => $lon1,
                'jarak_meter'      => $distanceMeters,
                'status_geofence'  => 'Valid',
                'akurasi_meter'    => $accuracy,
                'is_mock_location' => false,
                'is_suspicious'    => false,
                'device_info'      => $validated['device_info'] ?? $request->userAgent(),
                'ip_address'       => $request->ip(),
                'keterangan'       => "Presensi Mandiri GPS: {$distanceMeters}m dari titik pusat sekolah (Akurasi {$accuracy}m, Status: {$statusKehadiran})",
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success'     => true,
                'message'     => "Presensi GPS berhasil dicatat! Status: {$statusKehadiran} ({$distanceMeters}m dari sekolah).",
                'data'        => $presensi,
                'jarak_meter' => $distanceMeters,
            ], 201);
        }

        return back()->with('success', "Presensi GPS berhasil dicatat! Status: {$statusKehadiran}.");
    }

    public function scanQrSiswa(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id'         => 'required|uuid',
            'status_kehadiran' => 'required|in:Hadir,Terlambat,Dispensasi',
            'keterangan'       => 'nullable|string',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'akurasi_meter'    => 'nullable|numeric',
            'is_mock'          => 'nullable|boolean',
        ]);

        $siswa = Siswa::find($validated['siswa_id']);
        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $tenantId = $siswa->tenant_id ?? auth()->user()?->tenant_id ?? '00000000-0000-0000-0000-000000000000';
        $setting = LokasiAbsensiSetting::first();
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $lat1 = isset($validated['latitude']) ? (float)$validated['latitude'] : null;
        $lon1 = isset($validated['longitude']) ? (float)$validated['longitude'] : null;
        $accuracy = isset($validated['akurasi_meter']) ? (float)$validated['akurasi_meter'] : null;
        $distanceMeters = null;
        $statusGeofence = 'Valid';

        if ($lat1 !== null && $lon1 !== null) {
            $lat2 = $setting ? (float)$setting->latitude_pusat : -6.2088;
            $lon2 = $setting ? (float)$setting->longitude_pusat : 106.8456;
            $maxRadius = $setting ? (int)$setting->radius_meter : 150;
            $distanceMeters = $this->calculateDistanceMeters($lat1, $lon1, $lat2, $lon2);

            $fraud = $this->inspectFraud($request, $siswa, $lat1, $lon1, $accuracy, $distanceMeters, $maxRadius);
            if ($fraud) {
                PresensiFraudLog::create([
                    'tenant_id'      => $tenantId,
                    'siswa_id'       => $siswa->id,
                    'tipe_pengguna'  => 'siswa',
                    'nama_pelaku'    => $siswa->nama_lengkap ?? 'Unknown Siswa',
                    'identifier'     => $siswa->nisn,
                    'latitude'       => $lat1,
                    'longitude'      => $lon1,
                    'jarak_meter'    => $distanceMeters,
                    'akurasi_meter'  => $accuracy,
                    'fraud_type'     => $fraud['type'],
                    'fraud_reason'   => $fraud['reason'],
                    'fraud_details'  => $fraud['details'],
                    'device_info'    => $request->userAgent(),
                    'ip_address'     => $request->ip(),
                    'is_blocked'     => true,
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success'     => false,
                        'is_fraud'    => true,
                        'fraud_type'  => $fraud['type'],
                        'message'     => $fraud['reason'],
                        'jarak_meter' => $distanceMeters,
                    ], 422);
                }

                return back()->withErrors(['lokasi' => $fraud['reason']]);
            }
        }

        $presensi = PresensiSiswaHarian::updateOrCreate(
            [
                'siswa_id' => $validated['siswa_id'],
                'tanggal'  => $today,
            ],
            [
                'tenant_id'        => $tenantId,
                'nama_siswa'       => $siswa->nama_lengkap ?? '',
                'nisn'             => $siswa->nisn ?? '',
                'kelas_id'         => $siswa->kelas_saat_ini_id ?? null,
                'nama_kelas'       => $siswa->kelas_saat_ini ?? '',
                'jam_masuk'        => $nowTime,
                'status_kehadiran' => $validated['status_kehadiran'],
                'metode_presensi'  => ($lat1 !== null) ? 'QR_Code_GPS' : 'QR_Code',
                'latitude'         => $lat1,
                'longitude'        => $lon1,
                'jarak_meter'      => $distanceMeters,
                'status_geofence'  => $statusGeofence,
                'akurasi_meter'    => $accuracy,
                'ip_address'       => $request->ip(),
                'keterangan'       => $validated['keterangan'] ?? 'Presensi Scanner QR Kartu Pelajar',
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Presensi {$siswa->nama_lengkap} berhasil dicatat.", 'data' => $presensi], 201);
        }

        return back()->with('success', "Presensi {$siswa->nama_lengkap} berhasil dicatat.");
    }

    public function presensiGtkGps(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ptk_id'        => 'required|uuid',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'akurasi_meter' => 'nullable|numeric',
            'is_mock'       => 'nullable|boolean',
        ]);

        $gtk = Gtk::find($validated['ptk_id']);
        if (!$gtk) {
            return response()->json(['success' => false, 'message' => 'Data GTK tidak ditemukan.'], 404);
        }

        $tenantId = $gtk->tenant_id ?? auth()->user()?->tenant_id ?? '00000000-0000-0000-0000-000000000000';
        $setting = LokasiAbsensiSetting::first();
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $lat1 = (float)$validated['latitude'];
        $lon1 = (float)$validated['longitude'];
        $lat2 = $setting ? (float)$setting->latitude_pusat : -6.2088;
        $lon2 = $setting ? (float)$setting->longitude_pusat : 106.8456;
        $maxRadius = $setting ? (int)$setting->radius_meter : 150;
        $accuracy = isset($validated['akurasi_meter']) ? (float)$validated['akurasi_meter'] : null;

        $distanceMeters = $this->calculateDistanceMeters($lat1, $lon1, $lat2, $lon2);

        // Anti-Fraud inspection
        $fraud = $this->inspectFraud($request, null, $lat1, $lon1, $accuracy, $distanceMeters, $maxRadius);
        if ($fraud) {
            PresensiFraudLog::create([
                'tenant_id'      => $tenantId,
                'ptk_id'         => $gtk->id,
                'tipe_pengguna'  => 'gtk',
                'nama_pelaku'    => $gtk->nama_lengkap ?? 'Unknown GTK',
                'identifier'     => $gtk->nip,
                'latitude'       => $lat1,
                'longitude'      => $lon1,
                'jarak_meter'    => $distanceMeters,
                'akurasi_meter'  => $accuracy,
                'fraud_type'     => $fraud['type'],
                'fraud_reason'   => $fraud['reason'],
                'fraud_details'  => $fraud['details'],
                'device_info'    => $request->userAgent(),
                'ip_address'     => $request->ip(),
                'is_blocked'     => true,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success'     => false,
                    'is_fraud'    => true,
                    'fraud_type'  => $fraud['type'],
                    'message'     => $fraud['reason'],
                    'jarak_meter' => $distanceMeters,
                ], 422);
            }

            return back()->withErrors(['lokasi' => $fraud['reason']]);
        }

        $jamMasukNormal = $setting ? $setting->jam_masuk_normal : '07:00';
        $toleransi = $setting ? (int)$setting->toleransi_terlambat_menit : 15;
        $batasWaktu = date('H:i', strtotime("+{$toleransi} minutes", strtotime($jamMasukNormal)));
        $statusKehadiran = ($nowTime <= $batasWaktu) ? 'Hadir' : 'Terlambat';

        $presensi = PresensiPtkHarian::updateOrCreate(
            [
                'ptk_id'  => $validated['ptk_id'],
                'tanggal' => $today,
            ],
            [
                'tenant_id'        => $tenantId,
                'nama_ptk'         => $gtk->nama_lengkap ?? '',
                'nip'              => $gtk->nip ?? '',
                'jam_masuk'        => $nowTime,
                'status_kehadiran' => $statusKehadiran,
                'metode_presensi'  => 'Geolokasi_GPS',
                'latitude'         => $lat1,
                'longitude'        => $lon1,
                'jarak_meter'      => $distanceMeters,
                'status_geofence'  => 'Valid',
                'keterangan'       => "Presensi GPS GTK: {$distanceMeters}m dari titik sekolah (Status: {$statusKehadiran})",
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Presensi GTK {$gtk->nama_lengkap} tercatat (Valid, {$distanceMeters}m).", 'data' => $presensi], 201);
        }

        return back()->with('success', "Presensi GTK {$gtk->nama_lengkap} tercatat (Valid, {$distanceMeters}m).");
    }

    public function storeIzin(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'pemohon_type'    => 'required|in:siswa,gtk',
            'pemohon_id'      => 'required|uuid',
            'jenis_izin'      => 'required|string|max:50',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date',
            'alasan'          => 'required|string',
        ]);

        if ($validated['pemohon_type'] === 'siswa') {
            $pemohon = Siswa::find($validated['pemohon_id']);
            $validated['nama_pemohon'] = $pemohon?->nama_lengkap ?? '';
            $validated['tenant_id'] = $pemohon?->tenant_id ?? auth()->user()?->tenant_id;
        } else {
            $pemohon = Gtk::find($validated['pemohon_id']);
            $validated['nama_pemohon'] = $pemohon?->nama_lengkap ?? '';
            $validated['tenant_id'] = $pemohon?->tenant_id ?? auth()->user()?->tenant_id;
        }

        $item = PengajuanIzinCuti::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengajuan izin/cuti berhasil dikirimkan.', 'data' => $item], 201);
        }

        return back()->with('success', 'Pengajuan izin/cuti berhasil dikirimkan.');
    }

    public function updateStatusIzin(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $izin = PengajuanIzinCuti::findOrFail($id);

        $validated = $request->validate([
            'status_persetujuan' => 'required|in:Disetujui,Ditolak,Menunggu',
            'catatan_approver'   => 'nullable|string',
        ]);

        $izin->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Status pengajuan izin berhasil diperbarui.', 'data' => $izin]);
        }

        return back()->with('success', 'Status pengajuan izin berhasil diperbarui.');
    }

    public function updateSetting(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_lokasi'               => 'required|string|max:150',
            'latitude_pusat'            => 'required|numeric',
            'longitude_pusat'           => 'required|numeric',
            'radius_meter'              => 'required|integer|min:10|max:5000',
            'jam_masuk_normal'          => 'required|string|max:10',
            'jam_pulang_normal'         => 'required|string|max:10',
            'toleransi_terlambat_menit' => 'required|integer|min:0',
        ]);

        $setting = LokasiAbsensiSetting::first();
        if ($setting) {
            $setting->update($validated);
        } else {
            $setting = LokasiAbsensiSetting::create($validated);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengaturan geofencing absensi berhasil disimpan.', 'data' => $setting]);
        }

        return back()->with('success', 'Pengaturan geofencing absensi berhasil disimpan.');
    }
}
