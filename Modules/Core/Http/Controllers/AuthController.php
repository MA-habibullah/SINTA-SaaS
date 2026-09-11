<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Entities\User;
use Modules\Core\Entities\Tenant;
use Modules\Cms\Entities\CmsPromotion;

class AuthController extends Controller
{
    /**
     * Tampilkan Halaman Promosi & Landing Page Utama Publik
     * GET / atau GET /landing
     */
    public function showLandingPage(Request $request): InertiaResponse|RedirectResponse
    {
        // Jika sudah login, langsung ke dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $promotions = CmsPromotion::where('is_active', true)
            ->orderBy('order_num', 'asc')
            ->get()
            ->groupBy('section_key');

        $tenantsCount = Tenant::whereIn('status', ['active', 'aktif'])->count();

        return Inertia::render('Landing/Index', [
            'promotions'   => $promotions,
            'tenantsCount' => max(1, $tenantsCount),
        ]);
    }

    /**
     * Tampilkan formulir pendaftaran sekolah baru (Free Trial Self-Registration)
     * GET /daftar-sekolah
     */
    public function showRegisterForm(Request $request): InertiaResponse
    {
        $defaultTrial = (int) $request->input('trial', 3);
        if (!in_array($defaultTrial, [1, 3, 6], true)) {
            $defaultTrial = 3;
        }

        return Inertia::render('Auth/RegisterSekolah', [
            'defaultTrial' => $defaultTrial,
            'features'     => [
                ['title' => 'Buku Induk & Akademik', 'desc' => 'Kelola profil siswa, NISN, mutasi, dan cetak lembar buku induk.', 'icon' => 'bi-journal-text'],
                ['title' => 'Cetak Rapor Kurikulum Merdeka', 'desc' => 'Hitung nilai otomatis dan cetak raport PDF rapi instan.', 'icon' => 'bi-award'],
                ['title' => 'Keuangan & Pembayaran SPP', 'desc' => 'Pos tagihan, pembayaran kasir, kuitansi dan notifikasi WA.', 'icon' => 'bi-wallet2'],
                ['title' => 'PPDB & Seleksi Masuk Online', 'desc' => 'Penerimaan calon siswa baru lengkap dengan alur verifikasi berkas.', 'icon' => 'bi-person-plus'],
                ['title' => 'Bimbingan Konseling & Disiplin', 'desc' => 'Catatan bimbingan siswa, poin pelanggaran & pembinaan siswa.', 'icon' => 'bi-shield-check'],
                ['title' => 'Perpustakaan & Katalog Buku', 'desc' => 'Sirkulasi peminjaman buku, katalog OPAC dan barcode anggota.', 'icon' => 'bi-book'],
            ],
        ]);
    }

    /**
     * Proses pendaftaran sekolah baru (Free Trial)
     * POST /daftar-sekolah
     */
    public function registerSchool(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            // Identitas Sekolah
            'nama_sekolah'          => ['required', 'string', 'max:255'],
            'npsn'                  => ['required', 'string', 'max:20', 'unique:core.tenants,npsn'],
            'bentuk_pendidikan'     => ['required', 'string', 'in:SD,SMP,SMA,SMK,Madrasah,Lainnya'],
            'status_sekolah'        => ['required', 'string', 'in:Negeri,Swasta'],
            'kabupaten_kota'        => ['nullable', 'string', 'max:100'],
            'provinsi'              => ['nullable', 'string', 'max:100'],
            'subdomain'             => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9-]+$/', 'unique:core.tenants,subdomain'],
            
            // Kontak Penanggung Jawab (PIC)
            'pic_nama'              => ['required', 'string', 'max:255'],
            'pic_jabatan'           => ['required', 'string', 'max:100'],
            'pic_telepon'           => ['required', 'string', 'max:30'],
            'pic_email'             => ['required', 'email', 'max:150'],

            // Akun Administrator Awal
            'admin_nama'            => ['required', 'string', 'max:255'],
            'admin_username'        => ['required', 'string', 'min:4', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:core.users,username'],
            'admin_password'        => ['required', 'string', 'min:6'],

            // Pilihan Uji Coba Gratis
            'trial_duration_months' => ['nullable', 'integer', 'in:1,3,6'],
        ], [
            'npsn.unique'            => 'NPSN ini sudah terdaftar pada sistem SINTA SaaS.',
            'subdomain.unique'       => 'Subdomain ini sudah digunakan sekolah lain. Silakan pilih subdomain lain.',
            'subdomain.regex'        => 'Subdomain hanya boleh berisi huruf kecil, angka, dan tanda hubung (-).',
            'admin_username.unique'  => 'Username administrator ini sudah digunakan.',
            'admin_username.regex'   => 'Username administrator hanya boleh huruf, angka, garis bawah (_), dan titik (.).',
            'admin_password.min'     => 'Kata sandi minimal 6 karakter.',
        ]);

        $trialMonths = (int) ($validated['trial_duration_months'] ?? 3);
        $tenantId = Str::uuid()->toString();
        $subdomain = Str::lower(trim($validated['subdomain']));

        // Ambil role admin_sekolah
        $adminRole = DB::table('core.roles')->where('nama_role', 'admin_sekolah')->first();
        $roleId = $adminRole ? $adminRole->id : '22222222-2222-2222-2222-222222222221';

        try {
            DB::transaction(function () use ($tenantId, $validated, $trialMonths, $subdomain, $roleId) {
                // 1. Simpan Data Sekolah (core.tenants) dengan status pending_approval
                $tenant = Tenant::create([
                    'id'                    => $tenantId,
                    'nama_sekolah'          => trim($validated['nama_sekolah']),
                    'npsn'                  => trim($validated['npsn']),
                    'subdomain'             => $subdomain,
                    'bentuk_pendidikan'     => $validated['bentuk_pendidikan'],
                    'status_sekolah'        => $validated['status_sekolah'],
                    'kabupaten_kota'        => $validated['kabupaten_kota'] ?? null,
                    'provinsi'              => $validated['provinsi'] ?? null,
                    'telepon'               => trim($validated['pic_telepon']),
                    'email'                 => trim($validated['pic_email']),
                    'status'                => 'pending_approval',
                    'paket_aktif'           => 'Free Trial ' . $trialMonths . ' Bulan',
                    'subscription_type'     => 'Free Trial (' . $trialMonths . ' Bulan)',
                    'trial_duration_months' => $trialMonths,
                    'status_sinkronisasi'   => 'Menunggu',
                    'storage_limit_mb'      => 1024,
                    'max_siswa_limit'       => 1000,
                    'max_staff_limit'       => 100,
                    'pic_nama'              => trim($validated['pic_nama']),
                    'pic_jabatan'           => trim($validated['pic_jabatan']),
                    'pic_telepon'           => trim($validated['pic_telepon']),
                    'pic_email'             => trim($validated['pic_email']),
                    'enable_bk'             => 1,
                    'enable_tracer'         => 1,
                    'enable_ppdb'           => 1,
                    'enable_perpustakaan'   => 1,
                    'enable_keuangan'       => 1,
                    'enable_pdss'           => 1,
                    'enable_smk'            => $validated['bentuk_pendidikan'] === 'SMK' ? 1 : 0,
                    'enable_sarpras'        => 1,
                    'enable_persuratan'     => 1,
                    'cms_landing_enabled'   => true,
                ]);

                // 2. Simpan Akun Administrator Sekolah (core.users) dalam kondisi belum aktif
                User::create([
                    'id'            => Str::uuid()->toString(),
                    'tenant_id'     => $tenantId,
                    'role_id'       => $roleId,
                    'nama_lengkap'  => trim($validated['admin_nama']),
                    'username'      => trim($validated['admin_username']),
                    'email'         => trim($validated['pic_email']),
                    'password_hash' => Hash::make($validated['admin_password']),
                    'is_active'     => false, // Akan diaktifkan otomatis saat Super Admin menyetujui
                    'no_hp'         => trim($validated['pic_telepon']),
                ]);
            });

            $successMessage = "Pendaftaran sekolah '{$validated['nama_sekolah']}' berhasil dikirimkan! Permohonan Anda sedang dalam proses verifikasi oleh Super Admin. Anda dapat login setelah status disetujui.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data'    => [
                        'nama_sekolah' => $validated['nama_sekolah'],
                        'subdomain'    => $subdomain,
                        'npsn'         => $validated['npsn'],
                        'trial_months' => $trialMonths,
                    ]
                ], 201);
            }

            return redirect()->route('login')->with('registration_success', [
                'nama_sekolah' => $validated['nama_sekolah'],
                'username'     => $validated['admin_username'],
                'trial_months' => $trialMonths,
                'message'      => $successMessage,
            ]);

        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Terjadi kesalahan sistem saat mendaftarkan sekolah: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['general' => 'Gagal mendaftar: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Tampilkan halaman login Inertia Publik (Sekolah)
     */
    public function showLoginForm(): InertiaResponse
    {
        return Inertia::render('Auth/Login', [
            'tenants' => Tenant::whereIn('status', ['aktif', 'active'])
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->where('npsn', '!=', 'PLATFORM')
                ->select('id', 'nama_sekolah', 'npsn', 'logo')
                ->orderBy('nama_sekolah', 'asc')
                ->get(),
        ]);
    }

    /**
     * Tampilkan portal login khusus Super Admin (Stealth Gateway)
     * GET /super-admin/login
     */
    public function showSuperAdminLoginForm(): InertiaResponse|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/SuperAdminLogin');
    }

    /**
     * Proses autentikasi pengguna (Unified Multi-Tenant & Super Admin Token Guard)
     */
    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'username'       => ['required', 'string'],
            'password'       => ['required', 'string'],
            'tenant_id'      => ['nullable', 'string'],
            'security_token' => ['nullable', 'string'],
        ]);

        $query = User::with(['role'])->where(function ($q) use ($credentials) {
            $q->where('username', $credentials['username'])
              ->orWhere('email', $credentials['username']);
        });

        if (!empty($credentials['tenant_id'])) {
            $query->where('tenant_id', $credentials['tenant_id']);
        }

        $user = $query->first();

        if (!$user || !password_verify($credentials['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'username' => 'Kombinasi nama pengguna atau kata sandi tidak cocok.',
            ]);
        }

        // =========================================================================
        // Super Admin 16-Character Security Token Guard (Proteksi Token Khusus)
        // =========================================================================
        $isSuperAdmin = ($user->tenant_id === '00000000-0000-0000-0000-000000000000' 
            || $user->isSuperAdmin() 
            || ($user->role && in_array(strtolower((string)$user->role->nama_role), ['super_admin', 'superadmin'], true)) 
            || strtolower(trim((string)$user->username)) === 'superadmin');

        if ($isSuperAdmin) {
            $inputToken = trim((string)$request->input('security_token', ''));
            $validToken = config('auth.superadmin_token', env('SUPERADMIN_SECURITY_TOKEN', 'SINTA-2026-SEC1-ROOT'));

            $cleanInput = strtoupper(str_replace(['-', ' ', '_'], '', $inputToken));
            $cleanValid = strtoupper(str_replace(['-', ' ', '_'], '', (string)$validToken));

            if (empty($cleanInput) || $cleanInput !== $cleanValid) {
                throw ValidationException::withMessages([
                    'security_token' => 'Otorisasi Ditolak: Token Keamanan Khusus Super Admin (16 Karakter) tidak valid atau belum diisi.',
                ]);
            }
        }

        // Cek Status Tenant Sekolah
        if ($user->tenant_id && $user->tenant_id !== '00000000-0000-0000-0000-000000000000') {
            $tenant = Tenant::find($user->tenant_id);
            if ($tenant) {
                // 1. Pending Approval
                if ($tenant->isPendingApproval() || (!$user->is_active && $tenant->isPendingApproval())) {
                    throw ValidationException::withMessages([
                        'username' => "Pendaftaran sekolah '{$tenant->nama_sekolah}' saat ini sedang menunggu persetujuan (approval) oleh Super Admin. Akun Anda akan aktif otomatis setelah disetujui.",
                    ]);
                }

                // 2. Rejected
                if ($tenant->isRejected()) {
                    $reason = $tenant->rejection_reason ? ": \"{$tenant->rejection_reason}\"" : '.';
                    throw ValidationException::withMessages([
                        'username' => "Pendaftaran sekolah '{$tenant->nama_sekolah}' telah ditolak oleh Super Admin{$reason} Silakan hubungi tim kami untuk informasi lebih lanjut.",
                    ]);
                }

                // 3. Suspended / Inactive
                if (in_array(strtolower((string)$tenant->status), ['suspended', 'inactive', 'nonaktif'], true)) {
                    throw ValidationException::withMessages([
                        'username' => "Akses sekolah '{$tenant->nama_sekolah}' sedang dinonaktifkan sementara oleh Super Admin. Silakan hubungi administrator pusat.",
                    ]);
                }
            }
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => 'Akun Anda sedang dinonaktifkan oleh administrator.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $user->tenant_id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil.',
                'user'    => $user,
            ]);
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Ganti sekolah aktif (Super Admin Switch Tenant)
     */
    public function switchTenant(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['tenant_id' => 'required|uuid|exists:core.tenants,id']);

        if (!Auth::user()?->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang diizinkan berpindah ruang lingkup tenant.');
        }

        $request->session()->put('tenant_id', $request->input('tenant_id'));

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berhasil beralih sekolah.']);
        }

        return back()->with('success', 'Sekolah aktif berhasil diubah.');
    }

    /**
     * Logout pengguna
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berhasil keluar.']);
        }

        return redirect('/login');
    }
}
