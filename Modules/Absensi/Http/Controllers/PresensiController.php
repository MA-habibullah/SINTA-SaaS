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
use Modules\Absensi\Entities\JurnalMengajar;
use Modules\Absensi\Entities\PresensiSiswaKbm;
use Modules\Absensi\Services\FileUploadCompressionService;
use Modules\Siswa\Entities\Siswa;
use Modules\Kepegawaian\Entities\Gtk;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\MataPelajaran;
use Modules\Akademik\Entities\PemetaanMapel;
use Modules\Core\Entities\User;
use Modules\Core\Services\SecurityPayloadService;
use Illuminate\Support\Str;

class PresensiController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $tab = $request->input('tab', 'siswa');

        if ($request->wantsJson() || $request->ajax() || $request->has('async')) {
            $data = match ($tab) {
                'wali_kelas' => $this->getWaliKelasData($request),
                'jurnal'     => $this->getJurnalData($request),
                'gtk'        => $this->getGtkPresensiData($request),
                'izin'       => $this->getIzinData($request),
                'setting'    => $this->getSettingData($request),
                'rekap'      => $this->getRekapData($request),
                'fraud'      => $this->getFraudData($request),
                default      => $this->getSiswaPresensiData($request),
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

        $items = PresensiSiswaHarian::with(['siswa', 'verifikator:id,nama_lengkap'])
            ->where('tanggal', $tanggal)
            ->when($request->search, function ($q, $search) {
                $q->where('nama_siswa', 'ILIKE', "%{$search}%")
                  ->orWhere('nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('nama_kelas', 'ILIKE', "%{$search}%");
            })
            ->when($request->status_kehadiran, fn($q, $st) => $q->where('status_kehadiran', $st))
            ->when($request->status_verifikasi, fn($q, $sv) => $q->where('status_verifikasi', $sv))
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $stats = [
            'total_siswa'     => Siswa::where('status_siswa', 'Aktif')->count(),
            'hadir'           => PresensiSiswaHarian::where('tanggal', $tanggal)->where('status_kehadiran', 'Hadir')->count(),
            'sakit_izin'      => PresensiSiswaHarian::where('tanggal', $tanggal)->whereIn('status_kehadiran', ['Sakit', 'Izin', 'Dispensasi'])->count(),
            'alpa'            => PresensiSiswaHarian::where('tanggal', $tanggal)->where('status_kehadiran', 'Alpa')->count(),
            'terlambat'       => PresensiSiswaHarian::where('tanggal', $tanggal)->where('status_kehadiran', 'Terlambat')->count(),
            'menunggu_verif'  => PresensiSiswaHarian::where('tanggal', $tanggal)->where('status_verifikasi', 'Menunggu')->count(),
            'fraud_count'     => PresensiFraudLog::whereDate('created_at', $tanggal)->count(),
        ];

        $siswaList = Siswa::select('id', 'nama_lengkap', 'nisn', 'kelas_saat_ini')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->limit(100)
            ->get();

        $kelasList = Kelas::select('id', 'nama_kelas', 'kode_kelas')
            ->orderBy('nama_kelas', 'asc')
            ->get();

        $setting = LokasiAbsensiSetting::first();

        return compact('items', 'stats', 'tanggal', 'siswaList', 'kelasList', 'setting');
    }

    private function getWaliKelasData(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());
        $kelasId = $request->input('kelas_id', '');

        $kelasList = Kelas::select('id', 'nama_kelas', 'kode_kelas')
            ->orderBy('nama_kelas', 'asc')
            ->get();

        if (empty($kelasId) && $kelasList->isNotEmpty()) {
            $kelasId = $kelasList->first()->id;
        }

        $selectedKelas = Kelas::find($kelasId);
        $namaKelas = $selectedKelas?->nama_kelas ?? '';

        // Ambil seluruh siswa di kelas ini
        $siswaList = Siswa::select('id', 'nama_lengkap', 'nisn', 'kelas_saat_ini', 'jenis_kelamin')
            ->where('status_siswa', 'Aktif')
            ->when($namaKelas, function($q) use ($namaKelas, $kelasId) {
                $q->where('kelas_saat_ini', $namaKelas)
                  ->orWhere('kelas_saat_ini_id', $kelasId);
            })
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        // Presensi yang sudah tersimpan untuk kelas & tanggal ini
        $presensiSaved = PresensiSiswaHarian::where('tanggal', $tanggal)
            ->when($namaKelas, function($q) use ($namaKelas, $kelasId) {
                $q->where('nama_kelas', $namaKelas)
                  ->orWhere('kelas_id', $kelasId);
            })
            ->get()
            ->keyBy('siswa_id');

        $rows = $siswaList->map(function ($s) use ($presensiSaved) {
            $p = $presensiSaved->get($s->id);
            return [
                'siswa_id'          => $s->id,
                'nama_lengkap'      => $s->nama_lengkap,
                'nisn'              => $s->nisn,
                'jenis_kelamin'     => $s->jenis_kelamin,
                'presensi_id'       => $p?->id,
                'status_kehadiran'  => $p?->status_kehadiran ?? 'Hadir',
                'jam_masuk'         => $p?->jam_masuk ?? null,
                'metode_presensi'   => $p?->metode_presensi ?? 'Manual_WaliKelas',
                'bukti_izin_url'    => $p?->bukti_izin_url ?? null,
                'status_verifikasi' => $p?->status_verifikasi ?? 'Terverifikasi',
                'catatan'           => $p?->keterangan ?? '',
            ];
        });

        return compact('rows', 'kelasList', 'kelasId', 'tanggal', 'selectedKelas');
    }

    private function getJurnalData(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $tanggal = $request->input('tanggal', now()->toDateString());
        $kelasId = $request->input('kelas_id', '');
        $mapelId = $request->input('mapel_id', '');
        $guruId  = $request->input('guru_id', '');

        $items = JurnalMengajar::with(['guru:id,nama_lengkap', 'kelas:id,nama_kelas', 'mapel:id,nama_mata_pelajaran', 'presensiKbm'])
            ->when($tanggal && !$request->has('bulan_full'), fn($q) => $q->where('tanggal', $tanggal))
            ->when($request->has('bulan_full'), fn($q) => $q->whereRaw("TO_CHAR(tanggal, 'YYYY-MM') = ?", [$bulan]))
            ->when($kelasId, fn($q) => $q->where('kelas_id', $kelasId))
            ->when($mapelId, fn($q) => $q->where('mapel_id', $mapelId))
            ->when($guruId, fn($q) => $q->where('guru_id', $guruId))
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_mulai', 'asc')
            ->paginate(20);

        // Day name in Indonesian
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $namaHari = $hariMap[date('l', strtotime($tanggal))] ?? 'Senin';

        // Jadwal Mengajar Guru pada hari ini
        $user = auth()->user();
        $isGuru = $user && in_array($user->role?->nama_role ?? '', ['guru', 'wali_kelas', 'guru_bk']);

        $jadwalHariIni = PemetaanMapel::with(['kelas:id,nama_kelas', 'mapel:id,nama_mata_pelajaran', 'guru:id,nama_lengkap'])
            ->where('is_active', true)
            ->where('hari', $namaHari)
            ->when($isGuru, fn($q) => $q->where('guru_id', $user->id))
            ->orderBy('jam_mulai', 'asc')
            ->get();

        $kelasList = Kelas::select('id', 'nama_kelas', 'kode_kelas')->orderBy('nama_kelas')->get();
        $mapelList = MataPelajaran::select('id', 'nama_mata_pelajaran', 'kategori')->orderBy('nama_mata_pelajaran')->get();
        $guruList  = User::select('id', 'nama_lengkap', 'nip')->where('is_active', true)->orderBy('nama_lengkap')->get();

        $stats = [
            'total_jurnal'       => JurnalMengajar::whereRaw("TO_CHAR(tanggal, 'YYYY-MM') = ?", [$bulan])->count(),
            'total_kbm_selesai'  => JurnalMengajar::whereRaw("TO_CHAR(tanggal, 'YYYY-MM') = ?", [$bulan])->where('status_kbm', 'Selesai')->count(),
            'total_siswa_hadir'  => (int) JurnalMengajar::whereRaw("TO_CHAR(tanggal, 'YYYY-MM') = ?", [$bulan])->sum('jumlah_hadir'),
            'total_siswa_absen'  => (int) JurnalMengajar::whereRaw("TO_CHAR(tanggal, 'YYYY-MM') = ?", [$bulan])->selectRaw('SUM(jumlah_sakit + jumlah_izin + jumlah_alpa) as total')->value('total'),
        ];

        return compact('items', 'jadwalHariIni', 'kelasList', 'mapelList', 'guruList', 'stats', 'tanggal', 'bulan');
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

    public function getSiswaByKelas(string $kelasId): JsonResponse
    {
        $kelas = Kelas::findOrFail($kelasId);
        $siswa = Siswa::select('id', 'nama_lengkap', 'nisn', 'kelas_saat_ini', 'jenis_kelamin')
            ->where('status_siswa', 'Aktif')
            ->where(function($q) use ($kelas) {
                $q->where('kelas_saat_ini', $kelas->nama_kelas)
                  ->orWhere('kelas_saat_ini_id', $kelas->id);
            })
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'kelas'   => $kelas,
            'data'    => $siswa,
        ]);
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
     * Presensi GPS Mandiri Siswa (dengan Aturan Wajib Bukti Izin/Sakit & Kompresi File < 500KB)
     */
    public function presensiSiswaGps(Request $request): JsonResponse|RedirectResponse
    {
        $status = $request->input('status_kehadiran', 'Hadir') ?: 'Hadir';
        $isIzinOrSakit = in_array($status, ['Sakit', 'Izin']);

        $rules = [
            'siswa_id'         => 'required|uuid',
            'status_kehadiran' => 'nullable|in:Hadir,Terlambat,Sakit,Izin,Dispensasi',
            'keterangan'       => 'nullable|string',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'akurasi_meter'    => 'nullable|numeric',
            'is_mock'          => 'nullable|boolean',
            'device_info'      => 'nullable|string',
        ];

        // Jika siswa presensi mandiri memilih Izin / Sakit, WAJIB upload berkas bukti
        if ($isIzinOrSakit) {
            $rules['bukti_file'] = 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:10240';
        }

        $validated = $request->validate($rules);

        $siswa = Siswa::find($validated['siswa_id']);
        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $tenantId = $siswa->tenant_id ?? auth()->user()?->tenant_id ?? '00000000-0000-0000-0000-000000000000';
        $setting = LokasiAbsensiSetting::first();
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $lat1 = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $lon1 = isset($validated['longitude']) ? (float) $validated['longitude'] : null;
        $accuracy = isset($validated['akurasi_meter']) ? (float)$validated['akurasi_meter'] : null;
        $distanceMeters = null;
        $statusGeofence = 'Tanpa GPS';

        // Validasi GPS & Radius hanya jika status Hadir / Terlambat
        if (!$isIzinOrSakit && $lat1 !== null && $lon1 !== null) {
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

            $statusGeofence = 'Valid';
        }

        // Handle Upload Berkas Surat Bukti (Auto-Compressed < 500 KB)
        $buktiUrl = null;
        $originalFileName = null;
        $fileSizeKb = null;

        if ($request->hasFile('bukti_file')) {
            $uploadRes = FileUploadCompressionService::processAndCompress($request->file('bukti_file'), 'absensi_bukti', 500);
            $buktiUrl = $uploadRes['url'];
            $originalFileName = $uploadRes['original_name'];
            $fileSizeKb = $uploadRes['size_kb'];
        }

        // Tentukan Status Kehadiran (Hadir vs Terlambat vs Izin/Sakit)
        $statusKehadiran = $status;
        if ($status === 'Hadir') {
            $jamMasukNormal = $setting ? $setting->jam_masuk_normal : '07:00';
            $toleransi = $setting ? (int)$setting->toleransi_terlambat_menit : 15;
            $batasWaktu = date('H:i', strtotime("+{$toleransi} minutes", strtotime($jamMasukNormal)));
            $statusKehadiran = ($nowTime <= $batasWaktu) ? 'Hadir' : 'Terlambat';
        }

        $statusVerifikasi = $isIzinOrSakit ? 'Menunggu' : 'Terverifikasi';

        $presensi = PresensiSiswaHarian::updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'tanggal'  => $today,
            ],
            [
                'tenant_id'         => $tenantId,
                'nama_siswa'        => $siswa->nama_lengkap,
                'nisn'              => $siswa->nisn,
                'kelas_id'          => $siswa->kelas_saat_ini_id ?? null,
                'nama_kelas'        => $siswa->kelas_saat_ini ?? '',
                'jam_masuk'         => $nowTime,
                'status_kehadiran'  => $statusKehadiran,
                'metode_presensi'   => ($lat1 !== null) ? 'Geolokasi_GPS' : 'Presensi_Mandiri',
                'latitude'          => $lat1,
                'longitude'         => $lon1,
                'jarak_meter'       => $distanceMeters,
                'status_geofence'   => $statusGeofence,
                'akurasi_meter'     => $accuracy,
                'is_mock_location'  => false,
                'is_suspicious'     => false,
                'bukti_izin_url'    => $buktiUrl,
                'nama_berkas_asli'  => $originalFileName,
                'ukuran_berkas_kb'  => $fileSizeKb,
                'status_verifikasi' => $statusVerifikasi,
                'diinput_oleh'      => 'siswa',
                'device_info'       => $validated['device_info'] ?? $request->userAgent(),
                'ip_address'        => $request->ip(),
                'keterangan'        => $validated['keterangan'] ?? "Presensi mandiri siswa ({$statusKehadiran})",
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'message'           => "Presensi {$statusKehadiran} berhasil dicatat!" . ($isIzinOrSakit ? " (Menunggu verifikasi wali kelas)." : ""),
                'data'              => $presensi,
                'jarak_meter'       => $distanceMeters,
                'bukti_url'         => $buktiUrl,
                'ukuran_berkas_kb'  => $fileSizeKb,
            ], 201);
        }

        return back()->with('success', "Presensi {$statusKehadiran} berhasil dicatat.");
    }

    /**
     * Presensi Siswa Fleksibel oleh Wali Kelas / Guru Piket (Upload Bukti OPSIONAL)
     */
    public function storePresensiWaliKelas(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id'  => 'required|uuid',
            'tanggal'   => 'required|date',
            'records'   => 'required|array|min:1',
            'records.*.siswa_id'         => 'required|uuid',
            'records.*.status_kehadiran' => 'required|in:Hadir,Terlambat,Sakit,Izin,Alpa,Dispensasi',
            'records.*.catatan'          => 'nullable|string',
        ]);

        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $tanggal = $validated['tanggal'];
        $user = auth()->user();
        $diinputOleh = in_array($user?->role?->nama_role ?? '', ['wali_kelas']) ? 'wali_kelas' : 'guru_piket';

        $savedCount = 0;
        foreach ($validated['records'] as $r) {
            $siswa = Siswa::find($r['siswa_id']);
            if (!$siswa) continue;

            PresensiSiswaHarian::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tanggal'  => $tanggal,
                ],
                [
                    'tenant_id'         => $siswa->tenant_id ?? $kelas->tenant_id,
                    'nama_siswa'        => $siswa->nama_lengkap,
                    'nisn'              => $siswa->nisn,
                    'kelas_id'          => $kelas->id,
                    'nama_kelas'        => $kelas->nama_kelas,
                    'jam_masuk'         => now()->format('H:i'),
                    'status_kehadiran'  => $r['status_kehadiran'],
                    'metode_presensi'   => 'Manual_WaliKelas',
                    'status_verifikasi' => 'Terverifikasi',
                    'diverifikasi_oleh' => $user?->id,
                    'waktu_verifikasi'  => now(),
                    'diinput_oleh'      => $diinputOleh,
                    'keterangan'        => $r['catatan'] ?? "Diinput langsung oleh {$user?->nama_lengkap} ({$diinputOleh})",
                ]
            );
            $savedCount++;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Presensi {$savedCount} siswa kelas {$kelas->nama_kelas} berhasil disimpan oleh Wali Kelas.",
            ], 200);
        }

        return back()->with('success', "Presensi {$savedCount} siswa kelas {$kelas->nama_kelas} berhasil disimpan.");
    }

    /**
     * Verifikasi Absensi Mandiri Siswa oleh Wali Kelas
     */
    public function verifikasiPresensiSiswa(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $presensi = PresensiSiswaHarian::findOrFail($id);

        $validated = $request->validate([
            'status_verifikasi'  => 'required|in:Terverifikasi,Ditolak',
            'status_kehadiran'   => 'nullable|in:Hadir,Terlambat,Sakit,Izin,Alpa,Dispensasi',
            'catatan_wali_kelas' => 'nullable|string',
        ]);

        $updateData = [
            'status_verifikasi'  => $validated['status_verifikasi'],
            'diverifikasi_oleh'  => auth()->id(),
            'waktu_verifikasi'   => now(),
            'catatan_wali_kelas' => $validated['catatan_wali_kelas'] ?? $presensi->catatan_wali_kelas,
        ];

        if (!empty($validated['status_kehadiran'])) {
            $updateData['status_kehadiran'] = $validated['status_kehadiran'];
        }

        $presensi->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Presensi siswa {$presensi->nama_siswa} berhasil diverifikasi ({$validated['status_verifikasi']}).",
                'data'    => $presensi,
            ]);
        }

        return back()->with('success', "Presensi siswa {$presensi->nama_siswa} berhasil diverifikasi.");
    }

    /**
     * Simpan Jurnal Mengajar Guru (dengan Foto KBM Auto-Compressed < 500 KB & Presensi Siswa KBM)
     */
    public function storeJurnal(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'jadwal_id'              => 'nullable|uuid',
            'kelas_id'               => 'required|uuid',
            'mapel_id'               => 'required|uuid',
            'tanggal'                => 'required|date',
            'jam_ke'                 => 'required|string|max:50',
            'jam_mulai'              => 'required|string|max:10',
            'jam_selesai'            => 'required|string|max:10',
            'capaian_pembelajaran'   => 'required|string',
            'aktivitas_pembelajaran' => 'required|string',
            'kendala_pembelajaran'   => 'nullable|string',
            'status_kbm'             => 'required|in:Selesai,Terganti,Daring,Tugas Mandiri',
            'foto_kegiatan'          => 'nullable|file|mimes:jpeg,jpg,png,webp|max:10240',
            'presensi_kbm'           => 'nullable|array',
            'presensi_kbm.*.siswa_id'         => 'required|uuid',
            'presensi_kbm.*.status_kehadiran' => 'required|in:Hadir,Terlambat,Sakit,Izin,Alpa',
            'presensi_kbm.*.catatan'          => 'nullable|string',
        ]);

        $user = auth()->user();
        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $mapel = MataPelajaran::findOrFail($validated['mapel_id']);

        // Handle Foto KBM Auto-Compressed (< 500 KB)
        $fotoUrl = null;
        $fotoSizeKb = null;

        if ($request->hasFile('foto_kegiatan')) {
            $uploadRes = FileUploadCompressionService::processAndCompress($request->file('foto_kegiatan'), 'jurnal_kegiatan', 500);
            $fotoUrl = $uploadRes['url'];
            $fotoSizeKb = $uploadRes['size_kb'];
        }

        // Hitung Kehadiran Siswa
        $presensiList = $validated['presensi_kbm'] ?? [];
        $hadir = 0; $sakit = 0; $izin = 0; $alpa = 0; $terlambat = 0;

        foreach ($presensiList as $p) {
            match ($p['status_kehadiran']) {
                'Hadir'     => $hadir++,
                'Terlambat' => $terlambat++,
                'Sakit'     => $sakit++,
                'Izin'      => $izin++,
                'Alpa'      => $alpa++,
                default     => $hadir++,
            };
        }

        $jurnal = JurnalMengajar::create([
            'tenant_id'              => $kelas->tenant_id ?? $user->tenant_id,
            'jadwal_id'              => $validated['jadwal_id'] ?? null,
            'guru_id'                => $user->id,
            'nama_guru'              => $user->nama_lengkap,
            'kelas_id'               => $kelas->id,
            'nama_kelas'             => $kelas->nama_kelas,
            'mapel_id'               => $mapel->id,
            'nama_mapel'             => $mapel->nama_mata_pelajaran,
            'tahun_ajaran'           => '2026/2027',
            'semester'               => 'Ganjil',
            'tanggal'                => $validated['tanggal'],
            'jam_ke'                 => $validated['jam_ke'],
            'jam_mulai'              => $validated['jam_mulai'],
            'jam_selesai'            => $validated['jam_selesai'],
            'capaian_pembelajaran'   => $validated['capaian_pembelajaran'],
            'aktivitas_pembelajaran' => $validated['aktivitas_pembelajaran'],
            'kendala_pembelajaran'   => $validated['kendala_pembelajaran'] ?? '',
            'foto_kegiatan_url'      => $fotoUrl,
            'foto_ukuran_kb'         => $fotoSizeKb,
            'jumlah_hadir'           => $hadir,
            'jumlah_sakit'           => $sakit,
            'jumlah_izin'            => $izin,
            'jumlah_alpa'            => $alpa,
            'jumlah_terlambat'       => $terlambat,
            'status_kbm'             => $validated['status_kbm'],
            'is_verified'            => false,
        ]);

        // Simpan Presensi KBM per Siswa
        foreach ($presensiList as $p) {
            $siswa = Siswa::find($p['siswa_id']);
            if (!$siswa) continue;

            PresensiSiswaKbm::create([
                'tenant_id'        => $jurnal->tenant_id,
                'jurnal_id'        => $jurnal->id,
                'siswa_id'         => $siswa->id,
                'nama_siswa'       => $siswa->nama_lengkap,
                'nisn'             => $siswa->nisn,
                'status_kehadiran' => $p['status_kehadiran'],
                'catatan'          => $p['catatan'] ?? null,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Jurnal Mengajar {$mapel->nama_mata_pelajaran} kelas {$kelas->nama_kelas} berhasil disimpan.",
                'data'    => $jurnal,
            ], 201);
        }

        return back()->with('success', "Jurnal Mengajar {$mapel->nama_mata_pelajaran} kelas {$kelas->nama_kelas} berhasil disimpan.");
    }

    public function updateJurnal(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $jurnal = JurnalMengajar::findOrFail($id);

        $validated = $request->validate([
            'jam_ke'                 => 'required|string|max:50',
            'jam_mulai'              => 'required|string|max:10',
            'jam_selesai'            => 'required|string|max:10',
            'capaian_pembelajaran'   => 'required|string',
            'aktivitas_pembelajaran' => 'required|string',
            'kendala_pembelajaran'   => 'nullable|string',
            'status_kbm'             => 'required|in:Selesai,Terganti,Daring,Tugas Mandiri',
            'foto_kegiatan'          => 'nullable|file|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        if ($request->hasFile('foto_kegiatan')) {
            $uploadRes = FileUploadCompressionService::processAndCompress($request->file('foto_kegiatan'), 'jurnal_kegiatan', 500);
            $validated['foto_kegiatan_url'] = $uploadRes['url'];
            $validated['foto_ukuran_kb'] = $uploadRes['size_kb'];
        }

        $jurnal->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jurnal Mengajar berhasil diperbarui.',
                'data'    => $jurnal,
            ]);
        }

        return back()->with('success', 'Jurnal Mengajar berhasil diperbarui.');
    }

    public function destroyJurnal(string $id): JsonResponse|RedirectResponse
    {
        $jurnal = JurnalMengajar::findOrFail($id);
        PresensiSiswaKbm::where('jurnal_id', $jurnal->id)->delete();
        $jurnal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jurnal Mengajar berhasil dihapus.',
        ]);
    }

    public function supervisiJurnal(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $jurnal = JurnalMengajar::findOrFail($id);

        $validated = $request->validate([
            'catatan_supervisor' => 'nullable|string',
            'is_verified'        => 'required|boolean',
        ]);

        $jurnal->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status supervisi jurnal mengajar berhasil diperbarui.',
            'data'    => $jurnal,
        ]);
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
            'surat_bukti'     => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
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

        if ($request->hasFile('surat_bukti')) {
            $uploadRes = FileUploadCompressionService::processAndCompress($request->file('surat_bukti'), 'izin_lampiran', 500);
            $validated['surat_lampiran_url'] = $uploadRes['url'];
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
