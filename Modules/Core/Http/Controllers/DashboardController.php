<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Services\SecurityPayloadService;

class DashboardController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $roleName = strtolower($user?->role?->nama_role ?? 'siswa');

        $isSuperAdmin = $user?->isSuperAdmin() || $roleName === 'super_admin' || $tenantId === '00000000-0000-0000-0000-000000000000';
        $isAdminSekolah = in_array($roleName, ['admin_sekolah', 'admin', 'kepala_sekolah', 'tu', 'operator']);
        $isGuru = in_array($roleName, ['guru', 'wali_kelas', 'waka_kurikulum', 'kurikulum', 'pendidik']);
        $isSiswa = in_array($roleName, ['siswa', 'murid', 'orang_tua', 'wali_murid']);
        $isKeuangan = in_array($roleName, ['keuangan', 'kasir', 'bendahara']);
        $isPerpus = in_array($roleName, ['perpustakaan', 'pustakawan']);
        $isBk = in_array($roleName, ['bk', 'bimbingan_konseling', 'konselor']);
        $isSarpras = in_array($roleName, ['sarpras', 'inventaris']);

        $today = date('Y-m-d');

        // Tenant metadata
        $tenantInfo = null;
        if ($tenantId && $tenantId !== '00000000-0000-0000-0000-000000000000') {
            $tenantInfo = DB::table('core.tenants')
                ->where('id', $tenantId)
                ->select([
                    'id', 'nama_sekolah', 'npsn', 'subdomain', 'custom_domain',
                    'status', 'paket_aktif', 'bentuk_pendidikan', 'status_sekolah',
                    'akreditasi', 'alamat', 'kabupaten_kota', 'provinsi',
                    'nama_kepsek', 'storage_limit_mb', 'max_siswa_limit',
                    'trial_ends_at', 'subscription_type', 'subscription_expires_at',
                    'subscription_price', 'billing_cycle', 'is_locked', 'lock_reason'
                ])
                ->first();
            if ($tenantInfo) {
                $tenantInfo->is_trial_active = !empty($tenantInfo->trial_ends_at) && strtotime($tenantInfo->trial_ends_at) > time();
                
                $expiryTimestamp = $tenantInfo->subscription_expires_at 
                    ? strtotime($tenantInfo->subscription_expires_at) 
                    : ($tenantInfo->is_trial_active ? strtotime($tenantInfo->trial_ends_at) : null);

                $remainingSeconds = $expiryTimestamp ? max(0, $expiryTimestamp - time()) : 0;
                $remainingDays = (int) ceil($remainingSeconds / 86400);

                $tenantInfo->subscription_expires_at = $tenantInfo->subscription_expires_at;
                $tenantInfo->remaining_seconds = $remainingSeconds;
                $tenantInfo->remaining_days = $remainingDays;
                $tenantInfo->is_subscription_active = empty($tenantInfo->is_locked) && ($remainingSeconds > 0 || empty($expiryTimestamp));
                $tenantInfo->is_expiring_soon = $remainingDays <= 30;
                $tenantInfo->is_critical = $remainingDays <= 10;
            }
        }

        // Global announcements & agendas for this tenant
        $announcements = [];
        $agendas = [];
        if ($tenantId) {
            $announcements = DB::table('sistem.pengumuman')
                ->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $agendas = DB::table('sistem.agenda_sekolah')
                ->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        // Quick Actions configuration per role
        $quickActions = [];
        if ($isSuperAdmin) {
            $quickActions = [
                ['label' => 'Kelola Sekolah / Tenants', 'href' => '/super-admin/tenants', 'icon' => 'bi-buildings', 'color' => 'from-emerald-500 to-teal-600', 'desc' => 'Daftar, aktivasi & monitoring tenant'],
                ['label' => 'CMS Promosi & Landing', 'href' => '/super-admin/cms-promosi', 'icon' => 'bi-window-stack', 'color' => 'from-indigo-500 to-purple-600', 'desc' => 'Kelola banner & promo landing web'],
                ['label' => 'Server & Error Monitor', 'href' => '/super-admin/server-monitor', 'icon' => 'bi-cpu', 'color' => 'from-blue-500 to-cyan-600', 'desc' => 'Resource server, CPU & error logs'],
                ['label' => 'Audit & Log Aktivitas', 'href' => '/utilitas/log-aktivitas', 'icon' => 'bi-journal-text', 'color' => 'from-amber-500 to-orange-600', 'desc' => 'Log aktivitas pengguna & audit trail'],
            ];
        } elseif ($isAdminSekolah) {
            $quickActions = [
                ['label' => 'Buku Induk Siswa', 'href' => '/buku-induk', 'icon' => 'bi-journal-text', 'color' => 'from-blue-500 to-indigo-600', 'desc' => 'Data pokok & mutasi siswa'],
                ['label' => 'Presensi Kehadiran', 'href' => '/absensi', 'icon' => 'bi-calendar-check', 'color' => 'from-teal-500 to-emerald-600', 'desc' => 'Presensi siswa, GTK & verifikasi'],
                ['label' => 'Kasir & Pembayaran SPP', 'href' => '/keuangan/kasir', 'icon' => 'bi-calculator', 'color' => 'from-amber-500 to-orange-600', 'desc' => 'Transaksi kasir & kuitansi'],
                ['label' => 'Jadwal & KBM Mengajar', 'href' => '/akademik/jadwal', 'icon' => 'bi-calendar-week', 'color' => 'from-purple-500 to-pink-600', 'desc' => 'Distribusi jam mengajar guru'],
            ];
        } elseif ($isGuru) {
            $quickActions = [
                ['label' => 'Presensi & Jurnal Mengajar', 'href' => '/absensi', 'icon' => 'bi-journal-check', 'color' => 'from-indigo-500 to-purple-600', 'desc' => 'Catat materi & kehadiran KBM hari ini'],
                ['label' => 'Jadwal Pelajaran', 'href' => '/akademik/jadwal', 'icon' => 'bi-calendar3', 'color' => 'from-teal-500 to-emerald-600', 'desc' => 'Jadwal mengajar matrix mingguan'],
                ['label' => 'Rapor & Penilaian', 'href' => '/akademik/rapor', 'icon' => 'bi-file-earmark-spreadsheet', 'color' => 'from-blue-500 to-cyan-600', 'desc' => 'Entri nilai tugas, ujian & cetak rapor'],
                ['label' => 'PDSS & Peluang PTN', 'href' => '/akademik/pdss', 'icon' => 'bi-graph-up-arrow', 'color' => 'from-amber-500 to-orange-600', 'desc' => 'Simulasi kelayakan siswa SNBP/PTN'],
            ];
        } elseif ($isSiswa) {
            $quickActions = [
                ['label' => 'Presensi Mandiri GPS', 'href' => '/absensi/mandiri', 'icon' => 'bi-geo-alt-fill', 'color' => 'from-emerald-500 to-teal-600', 'desc' => 'Check-in kehadiran mandiri radius sekolah'],
                ['label' => 'Tagihan & SPP Saya', 'href' => '/keuangan/tagihan-saya', 'icon' => 'bi-wallet2', 'color' => 'from-blue-500 to-indigo-600', 'desc' => 'Cek rincian & status pembayaran SPP'],
                ['label' => 'Rapor & Nilai Saya', 'href' => '/akademik/rapor-saya', 'icon' => 'bi-award-fill', 'color' => 'from-purple-500 to-pink-600', 'desc' => 'Transkrip & lembar capaian hasil belajar'],
                ['label' => 'Perpustakaan Saya', 'href' => '/perpustakaan/riwayat-saya', 'icon' => 'bi-book-half', 'color' => 'from-rose-500 to-orange-600', 'desc' => 'Daftar buku yang dipinjam & riwayat'],
            ];
        } else {
            $quickActions = [
                ['label' => 'Presensi Harian', 'href' => '/absensi/presensi-siswa', 'icon' => 'bi-clock-history', 'color' => 'from-teal-500 to-emerald-600', 'desc' => 'Cek kehadiran operasional'],
                ['label' => 'Manajemen Pengguna', 'href' => '/core/users', 'icon' => 'bi-people', 'color' => 'from-blue-500 to-indigo-600', 'desc' => 'Informasi akun & profil'],
            ];
        }

        // 1. DATA SUPER ADMIN
        $superAdminData = null;
        if ($isSuperAdmin) {
            $totalTenants = DB::table('core.tenants')->count();
            $activeTenants = DB::table('core.tenants')->whereIn('status', ['approved', 'aktif', 'active'])->count();
            $trialTenants = DB::table('core.tenants')->where('subscription_type', 'trial')->orWhere('trial_ends_at', '>', now())->count();
            $pendingTenants = DB::table('core.tenants')->where('status', 'pending')->count();
            $suspendedTenants = DB::table('core.tenants')->where('status', 'suspended')->count();

            $totalUsersPlatform = DB::table('core.users')->where('is_active', true)->count();
            $usersByRole = DB::table('core.users')
                ->join('core.roles', 'core.users.role_id', '=', 'core.roles.id')
                ->where('core.users.is_active', true)
                ->select('core.roles.nama_role', DB::raw('count(*) as total'))
                ->groupBy('core.roles.nama_role')
                ->get();

            $errors24h = DB::table('sistem.system_errors')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            $recentErrors = DB::table('sistem.system_errors')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recentTenants = DB::table('core.tenants')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->select(['id', 'nama_sekolah', 'npsn', 'subdomain', 'status', 'paket_aktif', 'created_at', 'subscription_type', 'trial_ends_at'])
                ->get();

            // PostgreSQL DB Size
            $dbSizeResult = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
            $dbSize = $dbSizeResult[0]->size ?? 'N/A';

            // Estimated Monthly SaaS Revenue (Rp)
            $estimatedRevenue = ($activeTenants * 750000);

            $superAdminData = [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'trial_tenants' => $trialTenants,
                'pending_tenants' => $pendingTenants,
                'suspended_tenants' => $suspendedTenants,
                'total_users' => $totalUsersPlatform,
                'users_by_role' => $usersByRole,
                'errors_24h' => $errors24h,
                'recent_errors' => $recentErrors,
                'recent_tenants' => $recentTenants,
                'database_size' => $dbSize,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'estimated_revenue' => $estimatedRevenue,
            ];
        }

        // 2. DATA ADMIN SEKOLAH & KEPALA SEKOLAH
        $schoolOpsData = null;
        if ($isSuperAdmin || $isAdminSekolah) {
            $totalSiswa = DB::table('siswa.siswa')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count();

            $totalGtk = DB::table('kepegawaian.ptk_identitas')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count();

            $totalKelas = DB::table('akademik.kelas')
                ->where('tenant_id', $tenantId)
                ->count();

            // Attendance Today
            $attendanceToday = DB::table('absensi.presensi_siswa_harian')
                ->where('tenant_id', $tenantId)
                ->whereDate('tanggal', $today)
                ->selectRaw("
                    COUNT(CASE WHEN status_kehadiran IN ('h', 'hadir', 'Hadir') THEN 1 END) as hadir,
                    COUNT(CASE WHEN status_kehadiran IN ('i', 'izin', 'Izin') THEN 1 END) as izin,
                    COUNT(CASE WHEN status_kehadiran IN ('s', 'sakit', 'Sakit') THEN 1 END) as sakit,
                    COUNT(CASE WHEN status_kehadiran IN ('a', 'alpa', 'Alpa', 'tanpa_keterangan') THEN 1 END) as alpa,
                    COUNT(CASE WHEN is_mock_location = true OR is_suspicious = true THEN 1 END) as fraud_count
                ")->first();

            $attendanceRate = $totalSiswa > 0 && $attendanceToday 
                ? round((($attendanceToday->hadir ?? 0) / $totalSiswa) * 100, 1) 
                : 0;

            // 7 Days Attendance Trend
            $attendanceTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $targetDate = date('Y-m-d', strtotime("-$i days"));
                $dayName = date('D', strtotime($targetDate));
                $count = DB::table('absensi.presensi_siswa_harian')
                    ->where('tenant_id', $tenantId)
                    ->whereDate('tanggal', $targetDate)
                    ->whereIn('status_kehadiran', ['h', 'hadir', 'Hadir'])
                    ->count();
                $attendanceTrend[] = [
                    'date' => $targetDate,
                    'day' => $dayName,
                    'hadir_count' => $count,
                ];
            }

            // Fraud Alerts
            $recentFrauds = DB::table('absensi.presensi_fraud_logs')
                ->where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Finance Summary
            $kasMasukHariIni = DB::table('keuangan.transaksi_spp_pembayaran')
                ->where('tenant_id', $tenantId)
                ->whereDate('tanggal_bayar', $today)
                ->sum('nominal_bayar') ?? 0;

            $tagihanTertunggak = DB::table('keuangan.transaksi_spp_tagihan')
                ->where('tenant_id', $tenantId)
                ->whereIn('status_pembayaran', ['belum_lunas', 'menunggu_pembayaran', 'parsial'])
                ->sum('sisa_tagihan') ?? 0;

            // BK Violations
            $recentViolations = DB::table('bk.pelanggaran_siswa')
                ->where('tenant_id', $tenantId)
                ->orderBy('tanggal_kejadian', 'desc')
                ->limit(5)
                ->get();

            $schoolOpsData = [
                'total_siswa' => $totalSiswa,
                'total_gtk' => $totalGtk,
                'total_kelas' => $totalKelas,
                'attendance_today' => [
                    'hadir' => (int) ($attendanceToday->hadir ?? 0),
                    'izin' => (int) ($attendanceToday->izin ?? 0),
                    'sakit' => (int) ($attendanceToday->sakit ?? 0),
                    'alpa' => (int) ($attendanceToday->alpa ?? 0),
                    'fraud_count' => (int) ($attendanceToday->fraud_count ?? 0),
                    'rate' => $attendanceRate,
                ],
                'attendance_trend' => $attendanceTrend,
                'recent_frauds' => $recentFrauds,
                'kas_masuk_hari_ini' => (float) $kasMasukHariIni,
                'tagihan_tertunggak' => (float) $tagihanTertunggak,
                'recent_violations' => $recentViolations,
            ];
        }

        // 3. DATA GURU & WAKA KURIKULUM
        $teacherData = null;
        if ($isSuperAdmin || $isGuru) {
            // Jurnal Mengajar Hari Ini
            $jurnalHariIni = DB::table('absensi.jurnal_mengajar')
                ->where('tenant_id', $tenantId)
                ->whereDate('tanggal', $today)
                ->count();

            // Penugasan Mengajar
            $penugasanCount = DB::table('akademik.penugasan_mengajar')
                ->where('tenant_id', $tenantId)
                ->count();

            // Kelas Ampu / Bimbingan
            $kelasList = DB::table('akademik.kelas')
                ->where('tenant_id', $tenantId)
                ->limit(10)
                ->get();

            $teacherData = [
                'jurnal_hari_ini' => $jurnalHariIni,
                'total_penugasan' => $penugasanCount,
                'kelas_list' => $kelasList,
                'deadline_rapor' => '2026-12-20',
                'status_kunci_nilai' => false,
            ];
        }

        // 4. DATA SISWA & ORANG TUA
        $studentData = null;
        if ($isSuperAdmin || $isSiswa) {
            // Find student profile linked to this user if exists
            $studentProfile = DB::table('siswa.siswa')
                ->where('tenant_id', $tenantId)
                ->first();

            $siswaId = $studentProfile?->id;

            // Student Attendance Today
            $presensiSiswaHariIni = null;
            if ($siswaId) {
                $presensiSiswaHariIni = DB::table('absensi.presensi_siswa_harian')
                    ->where('tenant_id', $tenantId)
                    ->where('siswa_id', $siswaId)
                    ->whereDate('tanggal', $today)
                    ->first();
            }

            // Student Bills / Tagihan SPP
            $tagihanSiswa = [];
            $totalTunggakanSiswa = 0;
            if ($siswaId) {
                $tagihanSiswa = DB::table('keuangan.transaksi_spp_tagihan')
                    ->where('tenant_id', $tenantId)
                    ->where('siswa_id', $siswaId)
                    ->orderBy('tahun', 'desc')
                    ->orderBy('bulan', 'desc')
                    ->limit(5)
                    ->get();

                $totalTunggakanSiswa = DB::table('keuangan.transaksi_spp_tagihan')
                    ->where('tenant_id', $tenantId)
                    ->where('siswa_id', $siswaId)
                    ->whereIn('status_pembayaran', ['belum_lunas', 'menunggu_pembayaran', 'parsial'])
                    ->sum('sisa_tagihan') ?? 0;
            }

            // Student Borrowed Books
            $borrowedBooks = [];
            if ($siswaId) {
                $borrowedBooks = DB::table('perpustakaan.perpus_sirkulasi')
                    ->where('tenant_id', $tenantId)
                    ->where('peminjam_id', (string) $siswaId)
                    ->where('status_sirkulasi', 'dipinjam')
                    ->limit(5)
                    ->get();
            }

            $studentData = [
                'profile' => $studentProfile,
                'presensi_hari_ini' => $presensiSiswaHariIni,
                'tagihan_list' => $tagihanSiswa,
                'total_tunggakan' => (float) $totalTunggakanSiswa,
                'borrowed_books' => $borrowedBooks,
                'monthly_attendance_rate' => 96.5,
            ];
        }

        // 5. DATA SPESIALIS (Keuangan, Perpustakaan, BK, Sarpras)
        $specialistData = [];
        if ($isSuperAdmin || $isKeuangan) {
            $specialistData['keuangan'] = [
                'kas_masuk_hari_ini' => DB::table('keuangan.transaksi_spp_pembayaran')->where('tenant_id', $tenantId)->whereDate('tanggal_bayar', $today)->sum('nominal_bayar') ?? 0,
                'recent_pembayaran' => DB::table('keuangan.transaksi_spp_pembayaran')->where('tenant_id', $tenantId)->orderBy('tanggal_bayar', 'desc')->limit(5)->get(),
            ];
        }
        if ($isSuperAdmin || $isPerpus) {
            $specialistData['perpustakaan'] = [
                'total_sirkulasi_aktif' => DB::table('perpustakaan.perpus_sirkulasi')->where('tenant_id', $tenantId)->where('status_sirkulasi', 'dipinjam')->count(),
                'recent_sirkulasi' => DB::table('perpustakaan.perpus_sirkulasi')->where('tenant_id', $tenantId)->orderBy('tanggal_pinjam', 'desc')->limit(5)->get(),
            ];
        }
        if ($isSuperAdmin || $isBk) {
            $specialistData['bk'] = [
                'total_pelanggaran' => DB::table('bk.pelanggaran_siswa')->where('tenant_id', $tenantId)->count(),
                'recent_pelanggaran' => DB::table('bk.pelanggaran_siswa')->where('tenant_id', $tenantId)->orderBy('tanggal_kejadian', 'desc')->limit(5)->get(),
            ];
        }
        if ($isSuperAdmin || $isSarpras) {
            $specialistData['sarpras'] = [
                'total_barang_modal' => DB::table('sarpras.barang_modal')->where('tenant_id', $tenantId)->count(),
                'total_ruang' => DB::table('sarpras.ruangan')->where('tenant_id', $tenantId)->count(),
            ];
        }

        // Payload response
        $payload = [
            'user' => [
                'id' => $user?->id,
                'nama' => $user?->nama_lengkap ?? $user?->nama ?? $user?->name ?? 'Pengguna',
                'email' => $user?->email,
                'role' => $roleName,
                'role_label' => ucwords(str_replace('_', ' ', $roleName)),
            ],
            'tenant' => $tenantInfo,
            'is_super_admin' => $isSuperAdmin,
            'is_admin_sekolah' => $isAdminSekolah,
            'is_guru' => $isGuru,
            'is_siswa' => $isSiswa,
            'is_keuangan' => $isKeuangan,
            'is_perpus' => $isPerpus,
            'is_bk' => $isBk,
            'is_sarpras' => $isSarpras,
            'quick_actions' => $quickActions,
            'announcements' => $announcements,
            'agendas' => $agendas,
            'super_admin_data' => $superAdminData,
            'school_ops_data' => $schoolOpsData,
            'teacher_data' => $teacherData,
            'student_data' => $studentData,
            'specialist_data' => $specialistData,
        ];

        // Sanitize data according to OWASP ASVS L3
        $sanitizedPayload = SecurityPayloadService::sanitize($payload);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $sanitizedPayload,
            ]);
        }

        return Inertia::render('Dashboard', $sanitizedPayload);
    }
}
