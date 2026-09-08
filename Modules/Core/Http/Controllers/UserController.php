<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Entities\User;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\Role;
use Modules\Siswa\Entities\Siswa;
use Modules\Siswa\Entities\RiwayatKenaikanKelas;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\Jenjang;
use Modules\Akademik\Entities\TahunAjaran;

class UserController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $activeTab = $request->input('tab', 'siswa');
        $search = $request->input('search', '');
        $filterJenjang = $request->input('jenjang', '');
        $filterKelas = $request->input('kelas', '');
        $filterStatus = $request->input('status', 'Aktif');
        $trashMode = $request->boolean('trash', false);

        $isSuperAdmin = auth()->user()?->isSuperAdmin() || session('tenant_id') === '00000000-0000-0000-0000-000000000000';
        $filterTenantId = $request->input('tenant_id', '');
        $effectiveTenant = $isSuperAdmin && $filterTenantId ? $filterTenantId : $tenantId;

        // Role groupings
        $guruRoles = ['guru', 'wali_kelas', 'guru_bk', 'bk', 'kurikulum', 'kesiswaan', 'pembina_ekskul', 'kepala_sekolah'];
        $karyawanRoles = ['karyawan', 'keuangan', 'perpustakaan', 'sarpras', 'humas', 'tata_usaha', 'staff', 'laboran'];
        $operatorRoles = ['super_admin', 'admin', 'admin_sekolah', 'operator_sekolah', 'operator'];

        $userBaseQuery = function () use ($isSuperAdmin, $tenantId, $filterTenantId) {
            return User::query()
                ->when(!$isSuperAdmin && $tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->when($isSuperAdmin && $filterTenantId, fn($q) => $q->where('tenant_id', $filterTenantId));
        };

        // 1. Statistics Summary Cards
        $stats = [
            'total_siswa' => ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
                ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
                ->where('is_active', true)
                ->count(),
            'total_guru' => $userBaseQuery()->where(function ($q) use ($guruRoles) {
                $q->whereHas('role', fn($r) => $r->whereIn('nama_role', $guruRoles))
                  ->orWhereHas('roles', fn($r) => $r->whereIn('nama_role', $guruRoles));
            })->count(),
            'total_karyawan' => $userBaseQuery()->where(function ($q) use ($karyawanRoles) {
                $q->whereHas('role', fn($r) => $r->whereIn('nama_role', $karyawanRoles))
                  ->orWhereHas('roles', fn($r) => $r->whereIn('nama_role', $karyawanRoles));
            })->count(),
            'total_operator' => $userBaseQuery()->where(function ($q) use ($operatorRoles) {
                $q->whereHas('role', fn($r) => $r->whereIn('nama_role', $operatorRoles))
                  ->orWhereHas('roles', fn($r) => $r->whereIn('nama_role', $operatorRoles));
            })->count(),
            'total_mutasi' => ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
                ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
                ->where('is_active', false)
                ->count(),
        ];

        // 2. Dropdown Options Sesuai Database Riil
        $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')->orderBy('nama_sekolah', 'asc')->get();
        
        $kelasList = ($isSuperAdmin ? Kelas::withoutTenant() : Kelas::query())
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->where('is_active', true)
            ->orderBy('nama_kelas', 'asc')
            ->get();

        $tahunAjaranList = ($isSuperAdmin ? TahunAjaran::withoutTenant() : TahunAjaran::query())
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->whereNotNull('nama_tahun_ajaran')
            ->orderBy('nama_tahun_ajaran', 'desc')
            ->get(['id', 'nama_tahun_ajaran', 'nama_tahun_ajaran as tahun_ajaran']);

        $jenjangList = ($isSuperAdmin ? Jenjang::withoutTenant() : Jenjang::query())
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->where('is_active', true)
            ->orderBy('nama_jenjang', 'asc')
            ->get(['id', 'nama_jenjang as nama']);

        if ($jenjangList->isEmpty()) {
            $jenjangList = [
                ['id' => '1', 'nama' => 'Kelas X / Tingkat 10'],
                ['id' => '2', 'nama' => 'Kelas XI / Tingkat 11'],
                ['id' => '3', 'nama' => 'Kelas XII / Tingkat 12'],
            ];
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5 || $perPage > 200) {
            $perPage = 15;
        }

        // 3. Tab-Specific Data Processing
        $items = null;

        // Helper untuk mencari nama kelas dari id atau string
        $namaKelasFilter = null;
        if ($filterKelas) {
            if (\Illuminate\Support\Str::isUuid($filterKelas)) {
                $kObj = ($isSuperAdmin ? Kelas::withoutTenant() : Kelas::query())->find($filterKelas);
                $namaKelasFilter = $kObj ? $kObj->nama_kelas : $filterKelas;
            } else {
                $namaKelasFilter = $filterKelas;
            }
        }

        if ($activeTab === 'siswa') {
            $query = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
                ->with(['tenant', 'orangTua', 'fisikKesehatan', 'registrasi'])
                ->when($isSuperAdmin && $filterTenantId, function ($q) use ($filterTenantId) {
                    $q->where('tenant_id', $filterTenantId);
                })
                ->when($filterKelas, function ($q) use ($filterKelas, $namaKelasFilter) {
                    $q->where(function ($sub) use ($filterKelas, $namaKelasFilter) {
                        $sub->where('kelas_saat_ini', $namaKelasFilter)
                            ->orWhere('kelas_saat_ini', $filterKelas);
                    });
                })
                ->when($filterJenjang, function ($q) use ($filterJenjang) {
                    $jenjangObj = Jenjang::withoutTenant()->find($filterJenjang);
                    $namaJenjang = $jenjangObj ? $jenjangObj->nama_jenjang : $filterJenjang;
                    $classNames = Kelas::withoutTenant()
                        ->where(function ($sub) use ($filterJenjang, $namaJenjang) {
                            $sub->where('id_jenjang', $filterJenjang)
                                ->orWhere('nama_kelas', 'ILIKE', "%{$namaJenjang}%")
                                ->orWhere('kategori', 'ILIKE', "%{$namaJenjang}%");
                        })
                        ->pluck('nama_kelas')
                        ->toArray();
                    if (!empty($classNames)) {
                        $q->whereIn('kelas_saat_ini', $classNames);
                    }
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                            ->orWhere('nisn', 'ILIKE', "%{$search}%")
                            ->orWhere('nis', 'ILIKE', "%{$search}%")
                            ->orWhere('nik', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($trashMode, fn($q) => $q->where('is_active', false))
                ->when(!$trashMode && $filterStatus, function ($q) use ($filterStatus) {
                    if (strcasecmp($filterStatus, 'Aktif') === 0) {
                        $q->where('is_active', true)->where(function ($sq) {
                            $sq->where('status_siswa', 'ILIKE', 'aktif')->orWhereNull('status_siswa');
                        });
                    } elseif (strcasecmp($filterStatus, 'Lulus') === 0) {
                        $q->where('status_siswa', 'ILIKE', 'lulus');
                    } elseif (strcasecmp($filterStatus, 'Pindah') === 0) {
                        $q->where('status_siswa', 'ILIKE', 'pindah');
                    } elseif (strcasecmp($filterStatus, 'Keluar') === 0) {
                        $q->where(function ($sq) {
                            $sq->where('status_siswa', 'ILIKE', 'keluar')->orWhere('status_siswa', 'ILIKE', 'mutasi_keluar');
                        });
                    } elseif (strcasecmp($filterStatus, 'Non-Aktif') === 0) {
                        $q->where('is_active', false);
                    }
                })
                ->orderBy('nama_lengkap', 'asc');

            $items = $query->paginate($perPage)->withQueryString();
        } elseif ($activeTab === 'profile_rapot') {
            $query = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
                ->with(['tenant', 'orangTua'])
                ->when($isSuperAdmin && $filterTenantId, function ($q) use ($filterTenantId) {
                    $q->where('tenant_id', $filterTenantId);
                })
                ->when($filterKelas, function ($q) use ($filterKelas, $namaKelasFilter) {
                    $q->where(function ($sub) use ($filterKelas, $namaKelasFilter) {
                        $sub->where('kelas_saat_ini', $namaKelasFilter)
                            ->orWhere('kelas_saat_ini', $filterKelas);
                    });
                })
                ->when($filterJenjang, function ($q) use ($filterJenjang) {
                    $classNames = Kelas::where('id_jenjang', $filterJenjang)->orWhere('kategori', 'ILIKE', "%{$filterJenjang}%")->pluck('nama_kelas')->toArray();
                    if (!empty($classNames)) {
                        $q->whereIn('kelas_saat_ini', $classNames);
                    }
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                            ->orWhere('nisn', 'ILIKE', "%{$search}%")
                            ->orWhere('nis', 'ILIKE', "%{$search}%")
                            ->orWhere('nik', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($filterStatus, function ($q) use ($filterStatus) {
                    if (strcasecmp($filterStatus, 'Aktif') === 0) {
                        $q->where('is_active', true)->where(function ($sq) {
                            $sq->where('status_siswa', 'ILIKE', 'aktif')->orWhereNull('status_siswa');
                        });
                    } elseif (strcasecmp($filterStatus, 'Lulus') === 0) {
                        $q->where('status_siswa', 'ILIKE', 'lulus');
                    } elseif (strcasecmp($filterStatus, 'Pindah') === 0) {
                        $q->where('status_siswa', 'ILIKE', 'pindah');
                    } elseif (strcasecmp($filterStatus, 'Non-Aktif') === 0) {
                        $q->where('is_active', false);
                    }
                })
                ->orderBy('nama_lengkap', 'asc');

            // Hitung statistik seluruh siswa yang sesuai filter (bukan hanya 15 per halaman)
            $allFilteredStudents = (clone $query)->select([
                'id', 'tenant_id', 'nama_lengkap', 'nisn', 'nis', 'nik', 
                'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'alamat_domisili'
            ])->withCount('orangTua')->get();

            $siapCetakCount = 0;
            $perluDilengkapiCount = 0;
            foreach ($allFilteredStudents as $s) {
                $score = 0;
                if (!empty($s->nama_lengkap)) $score += 15;
                if (!empty($s->nisn)) $score += 15;
                if (!empty($s->nis)) $score += 10;
                if (!empty($s->nik)) $score += 15;
                if (!empty($s->tempat_lahir) && !empty($s->tanggal_lahir)) $score += 15;
                if (!empty($s->jenis_kelamin)) $score += 10;
                if (!empty($s->alamat) || !empty($s->alamat_domisili)) $score += 10;
                if ($s->orang_tua_count > 0) $score += 10;

                if ($score >= 80) {
                    $siapCetakCount++;
                } else {
                    $perluDilengkapiCount++;
                }
            }

            $stats['rapot_siap_cetak'] = $siapCetakCount;
            $stats['rapot_perlu_dilengkapi'] = $perluDilengkapiCount;
            $stats['rapot_total'] = $allFilteredStudents->count();

            $items = $query->paginate($perPage)->withQueryString();

            // Hitung persentase kelengkapan profil rapot untuk setiap siswa di halaman aktif
            $items->getCollection()->transform(function ($s) {
                $score = 0;
                if (!empty($s->nama_lengkap)) $score += 15;
                if (!empty($s->nisn)) $score += 15;
                if (!empty($s->nis)) $score += 10;
                if (!empty($s->nik)) $score += 15;
                if (!empty($s->tempat_lahir) && !empty($s->tanggal_lahir)) $score += 15;
                if (!empty($s->jenis_kelamin)) $score += 10;
                if (!empty($s->alamat) || !empty($s->alamat_domisili)) $score += 10;
                if ($s->orangTua && $s->orangTua->count() > 0) $score += 10;
                
                $s->kelengkapan_persen = min(100, $score);
                $s->is_ready_rapot = $score >= 80;
                return $s;
            });
        } elseif ($activeTab === 'guru') {
            $query = $userBaseQuery()
                ->with(['role', 'roles', 'tenant'])
                ->where(function ($q) use ($guruRoles) {
                    $q->whereHas('role', fn($r) => $r->whereIn('nama_role', $guruRoles))
                      ->orWhereHas('roles', fn($r) => $r->whereIn('nama_role', $guruRoles));
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%")
                            ->orWhere('username', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($trashMode, fn($q) => $q->where('is_active', false))
                ->when(!$trashMode && $filterStatus === 'Aktif', fn($q) => $q->where('is_active', true))
                ->when(!$trashMode && $filterStatus === 'Non-Aktif', fn($q) => $q->where('is_active', false))
                ->orderBy('nama_lengkap', 'asc');

            $items = $query->paginate($perPage)->withQueryString();
        } elseif ($activeTab === 'karyawan') {
            $query = $userBaseQuery()
                ->with(['role', 'roles', 'tenant'])
                ->where(function ($q) use ($karyawanRoles) {
                    $q->whereHas('role', fn($r) => $r->whereIn('nama_role', $karyawanRoles))
                      ->orWhereHas('roles', fn($r) => $r->whereIn('nama_role', $karyawanRoles));
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%")
                            ->orWhere('username', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($trashMode, fn($q) => $q->where('is_active', false))
                ->when(!$trashMode && $filterStatus === 'Aktif', fn($q) => $q->where('is_active', true))
                ->when(!$trashMode && $filterStatus === 'Non-Aktif', fn($q) => $q->where('is_active', false))
                ->orderBy('nama_lengkap', 'asc');

            $items = $query->paginate($perPage)->withQueryString();
        } elseif ($activeTab === 'operator') {
            $query = $userBaseQuery()
                ->with(['role', 'roles', 'tenant'])
                ->where(function ($q) use ($operatorRoles) {
                    $q->whereHas('role', fn($r) => $r->whereIn('nama_role', $operatorRoles))
                      ->orWhereHas('roles', fn($r) => $r->whereIn('nama_role', $operatorRoles));
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%")
                            ->orWhere('username', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($trashMode, fn($q) => $q->where('is_active', false))
                ->when(!$trashMode && $filterStatus === 'Aktif', fn($q) => $q->where('is_active', true))
                ->when(!$trashMode && $filterStatus === 'Non-Aktif', fn($q) => $q->where('is_active', false))
                ->orderBy('created_at', 'desc');

            $items = $query->paginate($perPage)->withQueryString();
        } elseif ($activeTab === 'mutasi') {
            $query = ($isSuperAdmin ? RiwayatKenaikanKelas::withoutTenant() : RiwayatKenaikanKelas::query())
                ->with(['siswa', 'tenant'])
                ->when($isSuperAdmin && $filterTenantId, function ($q) use ($filterTenantId) {
                    $q->where('tenant_id', $filterTenantId);
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('dari_kelas', 'ILIKE', "%{$search}%")
                            ->orWhere('ke_kelas', 'ILIKE', "%{$search}%")
                            ->orWhere('status', 'ILIKE', "%{$search}%")
                            ->orWhere('catatan', 'ILIKE', "%{$search}%")
                            ->orWhereHas('siswa', function ($sq) use ($search) {
                                $sq->where('nama_lengkap', 'ILIKE', "%{$search}%")
                                   ->orWhere('nisn', 'ILIKE', "%{$search}%")
                                   ->orWhere('nis', 'ILIKE', "%{$search}%");
                            });
                    });
                })
                ->orderBy('created_at', 'desc');

            $items = $query->paginate($perPage)->withQueryString();
        } elseif ($activeTab === 'naikkan_kelas') {
            $kelasAsalId = $request->input('kelas_asal_id') ?: $filterKelas;
            $initialSiswaList = [];

            if ($kelasAsalId) {
                $kelasObj = ($isSuperAdmin ? Kelas::withoutTenant() : Kelas::query())->find($kelasAsalId);
                $namaKelas = $kelasObj ? $kelasObj->nama_kelas : $kelasAsalId;

                $initialSiswaList = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
                    ->when($filterTenantId, fn($q) => $q->where('tenant_id', $filterTenantId))
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->where('status_siswa', 'ILIKE', 'aktif')
                          ->orWhereNull('status_siswa');
                    })
                    ->where(function ($q) use ($kelasAsalId, $namaKelas) {
                        $q->where('kelas_saat_ini', $namaKelas)
                          ->orWhere('kelas_saat_ini', $kelasAsalId);
                    })
                    ->orderBy('nama_lengkap', 'asc')
                    ->get(['id', 'tenant_id', 'nisn', 'nis', 'nama_lengkap', 'jenis_kelamin', 'kelas_saat_ini', 'status_siswa', 'foto_url']);
            }

            $items = [
                'kelasList'        => $kelasList,
                'tahunAjaranList'  => $tahunAjaranList,
                'initialSiswaList' => $initialSiswaList,
            ];
        }

        // Transform User items with multi-role flags
        if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator && in_array($activeTab, ['guru', 'karyawan', 'operator'])) {
            $items->getCollection()->transform(function ($u) {
                $roleNames = $u->roles ? $u->roles->pluck('nama_role')->toArray() : [];
                if ($u->role && !in_array($u->role->nama_role, $roleNames)) {
                    $roleNames[] = $u->role->nama_role;
                }
                $u->role_names = $roleNames;
                $u->is_wali_kelas = in_array('wali_kelas', $roleNames);
                $u->is_bk = in_array('guru_bk', $roleNames) || in_array('bk', $roleNames);
                $u->is_kurikulum = in_array('kurikulum', $roleNames);
                $u->is_kesiswaan = in_array('kesiswaan', $roleNames);
                $u->is_sarpras = in_array('sarpras', $roleNames);
                $u->is_humas = in_array('humas', $roleNames);
                $u->is_perpustakaan = in_array('perpustakaan', $roleNames);
                $u->is_keuangan = in_array('keuangan', $roleNames);
                $u->is_pembina_ekskul = in_array('pembina_ekskul', $roleNames);
                $u->is_operator = in_array('operator_sekolah', $roleNames) || in_array('admin_sekolah', $roleNames);
                $u->is_kepala_sekolah = in_array('kepala_sekolah', $roleNames) || in_array('admin', $roleNames);
                return $u;
            });
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $items,
                'stats'   => $stats,
            ]);
        }

        return Inertia::render('Core/User/Index', [
            'activeTab'       => $activeTab,
            'items'           => $items,
            'stats'           => $stats,
            'tenants'         => $tenants,
            'kelasList'       => $kelasList,
            'jenjangList'     => $jenjangList,
            'tahunAjaranList' => $tahunAjaranList,
            'isSuperAdmin'    => $isSuperAdmin,
            'filters'         => [
                'search'    => $search,
                'tenant_id' => $filterTenantId,
                'jenjang'   => $filterJenjang,
                'kelas'     => $filterKelas,
                'status'    => $filterStatus,
                'trash'     => $trashMode,
                'per_page'  => $perPage,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'user');

        if ($tab === 'siswa') {
            $validated = $request->validate([
                'nama_lengkap'  => 'required|string|max:255',
                'nisn'          => 'required|string|max:20',
                'nis'           => 'nullable|string|max:20',
                'jenis_kelamin' => 'required|in:L,P',
                'tempat_lahir'  => 'nullable|string|max:100',
                'tanggal_lahir' => 'nullable|date',
                'alamat_tinggal'=> 'nullable|string',
                'nama_ayah'     => 'nullable|string|max:255',
                'no_hp'         => 'nullable|string|max:20',
                'tenant_id'     => 'nullable|uuid',
            ]);

            $validated['id'] = (string) Str::uuid();
            $validated['tenant_id'] = $validated['tenant_id'] ?? session('tenant_id') ?? auth()->user()?->tenant_id ?? Tenant::first()?->id;
            $validated['is_active'] = true;

            $item = Siswa::create($validated);
            $msg = 'Data siswa ' . $item->nama_lengkap . ' berhasil ditambahkan.';
        } else {
            $validated = $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'username'     => 'required|string|max:100|unique:core.users,username',
                'email'        => 'required|email|max:255|unique:core.users,email',
                'password'     => 'required|string|min:6',
                'role_id'      => 'nullable|string',
                'no_hp'        => 'nullable|string|max:20',
                'nip'          => 'nullable|string|max:50',
                'jenis_gtk'    => 'nullable|string|max:100',
                'tenant_id'    => 'nullable|uuid',
            ]);

            $validated['id'] = (string) Str::uuid();
            $validated['tenant_id'] = $validated['tenant_id'] ?? session('tenant_id') ?? auth()->user()?->tenant_id ?? Tenant::first()?->id;
            $validated['password_hash'] = Hash::make($validated['password']);
            $validated['is_active'] = true;

            $item = User::create($validated);
            $msg = 'Pengguna ' . $item->nama_lengkap . ' berhasil ditambahkan.';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'data' => $item], 201);
        }

        return back()->with('success', $msg);
    }

    public function quickAddSiswa(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_lengkap'  => 'required|string|max:255',
            'nisn'          => 'required|string|max:20',
            'nis'           => 'required|string|max:20',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas_saat_ini'=> 'required|string',
            'tenant_id'     => 'nullable|uuid',
        ]);

        $validated['id'] = (string) Str::uuid();
        $validated['tenant_id'] = $validated['tenant_id'] ?? session('tenant_id') ?? auth()->user()?->tenant_id ?? Tenant::first()?->id;
        $validated['status_siswa'] = 'Aktif';
        $validated['is_active'] = true;

        $siswa = Siswa::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Registrasi cepat siswa berhasil!', 'data' => $siswa], 201);
        }

        return back()->with('success', 'Registrasi cepat siswa berhasil!');
    }

    public function getSiswaByKelas(Request $request): JsonResponse
    {
        $kelasId = $request->input('kelas_id');
        $tenantId = $request->input('tenant_id') ?? session('tenant_id') ?? auth()->user()?->tenant_id;
        $isSuperAdmin = auth()->user()?->isSuperAdmin() || session('tenant_id') === '00000000-0000-0000-0000-000000000000';

        if (!$kelasId) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Cari nama kelas jika yang dikirim adalah UUID
        $kelas = null;
        if (\Illuminate\Support\Str::isUuid($kelasId)) {
            $kelas = ($isSuperAdmin ? Kelas::withoutTenant() : Kelas::query())->find($kelasId);
        }
        $namaKelas = $kelas ? $kelas->nama_kelas : $kelasId;
        if ($kelas && (!$tenantId || $tenantId === '00000000-0000-0000-0000-000000000000')) {
            $tenantId = $kelas->tenant_id;
        }

        $query = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
            ->when($tenantId && $tenantId !== '00000000-0000-0000-0000-000000000000', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('status_siswa', 'ILIKE', 'aktif')
                  ->orWhereNull('status_siswa');
            })
            ->where(function ($q) use ($kelasId, $namaKelas) {
                $q->where('kelas_saat_ini', $namaKelas)
                  ->orWhere('kelas_saat_ini', $kelasId)
                  ->orWhere('kelas_saat_ini', 'ILIKE', "%{$namaKelas}%");
            })
            ->orderBy('nama_lengkap', 'asc');

        $siswaList = $query->get([
            'id',
            'tenant_id',
            'nisn',
            'nis',
            'nama_lengkap',
            'jenis_kelamin',
            'kelas_saat_ini',
            'status_siswa',
            'foto_url',
        ]);

        return response()->json([
            'success' => true,
            'kelas'   => $namaKelas,
            'count'   => $siswaList->count(),
            'data'    => $siswaList,
        ]);
    }

    public function getRiwayatSiswa(string $id): JsonResponse
    {
        $riwayat = RiwayatKenaikanKelas::withoutTenant()
            ->where('siswa_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $riwayat,
        ]);
    }

    public function promoteKelas(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'mode'             => 'required|in:promote,pindah,retain,graduate,mutasi_keluar',
            'kelas_asal_id'    => 'required|string',
            'kelas_tujuan_id'  => 'nullable|string',
            'tahun_ajaran'     => 'required|string',
            'siswa_ids'        => 'required|array|min:1',
            'catatan'          => 'nullable|string|max:500',
            'tenant_id'        => 'nullable|string',
        ]);

        $mode = $validated['mode'];
        $kelasAsalId = $validated['kelas_asal_id'];
        $kelasTujuanId = $validated['kelas_tujuan_id'] ?? null;
        $tahunAjaran = $validated['tahun_ajaran'];
        $siswaIds = $validated['siswa_ids'];
        $catatan = $validated['catatan'] ?? null;

        $isSuperAdmin = auth()->user()?->isSuperAdmin() || session('tenant_id') === '00000000-0000-0000-0000-000000000000';
        $tenantId = $validated['tenant_id'] ?? session('tenant_id') ?? auth()->user()?->tenant_id;

        // Validasi kebutuhan kelas tujuan
        if (in_array($mode, ['promote', 'pindah', 'retain']) && empty($kelasTujuanId)) {
            $err = 'Kelas tujuan wajib dipilih untuk tipe aksi ini.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 422);
            }
            return back()->with('error', $err);
        }

        // Cari nama kelas asal
        $kelasAsal = ($isSuperAdmin ? Kelas::withoutTenant() : Kelas::query())->find($kelasAsalId);
        $namaKelasAsal = $kelasAsal ? $kelasAsal->nama_kelas : $kelasAsalId;

        // Cari nama kelas tujuan jika ada
        $namaKelasTujuan = null;
        if ($kelasTujuanId) {
            $kelasTujuan = ($isSuperAdmin ? Kelas::withoutTenant() : Kelas::query())->find($kelasTujuanId);
            $namaKelasTujuan = $kelasTujuan ? $kelasTujuan->nama_kelas : $kelasTujuanId;
        }

        // Ambil siswa yang valid (hanya yang aktif & cocok tenant)
        $siswaQuery = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
            ->whereIn('id', $siswaIds)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('status_siswa', 'ILIKE', 'aktif')
                  ->orWhereNull('status_siswa');
            });

        if ($tenantId && $tenantId !== '00000000-0000-0000-0000-000000000000') {
            $siswaQuery->where('tenant_id', $tenantId);
        }

        $validStudents = $siswaQuery->get();

        if ($validStudents->isEmpty()) {
            $msg = 'Tidak ada data siswa aktif yang valid untuk diproses.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return back()->with('error', $msg);
        }

        $parts = explode('/', $tahunAjaran);
        $tahunLulus = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : (int)date('Y');
        $processedCount = 0;

        DB::transaction(function () use ($validStudents, $mode, $namaKelasAsal, $namaKelasTujuan, $tahunAjaran, $tahunLulus, $catatan, &$processedCount) {
            foreach ($validStudents as $student) {
                $studentTenantId = $student->tenant_id;

                if ($mode === 'promote') {
                    $student->update([
                        'kelas_saat_ini' => $namaKelasTujuan,
                        'status_siswa'   => 'Aktif',
                        'is_active'      => true,
                    ]);

                    RiwayatKenaikanKelas::create([
                        'id'           => (string) Str::uuid(),
                        'tenant_id'    => $studentTenantId,
                        'siswa_id'     => $student->id,
                        'tahun_ajaran' => $tahunAjaran,
                        'dari_kelas'   => $namaKelasAsal,
                        'ke_kelas'     => $namaKelasTujuan,
                        'status'       => 'naik',
                        'catatan'      => $catatan ?: "Naik kelas dari {$namaKelasAsal} ke {$namaKelasTujuan} (TA {$tahunAjaran})",
                    ]);
                } elseif ($mode === 'pindah') {
                    $student->update([
                        'kelas_saat_ini' => $namaKelasTujuan,
                        'status_siswa'   => 'Aktif',
                        'is_active'      => true,
                    ]);

                    RiwayatKenaikanKelas::create([
                        'id'           => (string) Str::uuid(),
                        'tenant_id'    => $studentTenantId,
                        'siswa_id'     => $student->id,
                        'tahun_ajaran' => $tahunAjaran,
                        'dari_kelas'   => $namaKelasAsal,
                        'ke_kelas'     => $namaKelasTujuan,
                        'status'       => 'pindah',
                        'catatan'      => $catatan ?: "Pindah rombel dari {$namaKelasAsal} ke {$namaKelasTujuan} (TA {$tahunAjaran})",
                    ]);
                } elseif ($mode === 'retain') {
                    $student->update([
                        'kelas_saat_ini' => $namaKelasTujuan,
                        'status_siswa'   => 'Aktif',
                        'is_active'      => true,
                    ]);

                    RiwayatKenaikanKelas::create([
                        'id'           => (string) Str::uuid(),
                        'tenant_id'    => $studentTenantId,
                        'siswa_id'     => $student->id,
                        'tahun_ajaran' => $tahunAjaran,
                        'dari_kelas'   => $namaKelasAsal,
                        'ke_kelas'     => $namaKelasTujuan,
                        'status'       => 'tinggal',
                        'catatan'      => $catatan ?: "Tinggal kelas di {$namaKelasTujuan} (TA {$tahunAjaran})",
                    ]);
                } elseif ($mode === 'graduate') {
                    $student->update([
                        'status_siswa' => 'Lulus',
                        'tahun_lulus'  => $tahunLulus,
                        'is_active'    => false,
                    ]);

                    RiwayatKenaikanKelas::create([
                        'id'           => (string) Str::uuid(),
                        'tenant_id'    => $studentTenantId,
                        'siswa_id'     => $student->id,
                        'tahun_ajaran' => $tahunAjaran,
                        'dari_kelas'   => $namaKelasAsal,
                        'ke_kelas'     => null,
                        'status'       => 'lulus',
                        'catatan'      => $catatan ?: "Kelulusan siswa tingkat akhir kelas {$namaKelasAsal} (TA {$tahunAjaran})",
                    ]);
                } elseif ($mode === 'mutasi_keluar') {
                    $student->update([
                        'status_siswa' => 'Keluar',
                        'is_active'    => false,
                    ]);

                    RiwayatKenaikanKelas::create([
                        'id'           => (string) Str::uuid(),
                        'tenant_id'    => $studentTenantId,
                        'siswa_id'     => $student->id,
                        'tahun_ajaran' => $tahunAjaran,
                        'dari_kelas'   => $namaKelasAsal,
                        'ke_kelas'     => null,
                        'status'       => 'mutasi_keluar',
                        'catatan'      => $catatan ?: "Siswa mutasi keluar / pindah sekolah dari {$namaKelasAsal}",
                    ]);
                }

                $processedCount++;
            }
        });

        $actionNames = [
            'promote'       => 'Kenaikan Kelas',
            'pindah'        => 'Pindah Kelas / Rombel',
            'retain'        => 'Penetapan Tinggal Kelas',
            'graduate'      => 'Kelulusan Siswa',
            'mutasi_keluar' => 'Mutasi Keluar',
        ];
        $actText = $actionNames[$mode] ?? 'Aksi Siswa';
        $msg = "Berhasil memproses {$actText} untuk {$processedCount} siswa secara atomik.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'count'   => $processedCount,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'user');

        if ($tab === 'siswa') {
            $siswa = Siswa::withoutTenant()->findOrFail($id);
            $validated = $request->validate([
                'nama_lengkap'  => 'required|string|max:255',
                'nisn'          => 'required|string|max:20',
                'nis'           => 'nullable|string|max:20',
                'jenis_kelamin' => 'required|in:L,P',
                'tempat_lahir'  => 'nullable|string|max:100',
                'tanggal_lahir' => 'nullable|date',
                'alamat_tinggal'=> 'nullable|string',
                'nama_ayah'     => 'nullable|string|max:255',
                'no_hp'         => 'nullable|string|max:20',
                'is_active'     => 'nullable|boolean',
            ]);
            $siswa->update($validated);
            $msg = 'Data siswa ' . $siswa->nama_lengkap . ' berhasil diperbarui.';
        } else {
            $user = User::findOrFail($id);
            $validated = $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'email'        => ['required', 'email', 'max:100', \Illuminate\Validation\Rule::unique('pgsql.core.users', 'email')->ignore($id)],
                'role'         => 'nullable|string',
                'no_hp'        => 'nullable|string|max:20',
                'nip'          => 'nullable|string|max:50',
                'is_active'    => 'nullable|boolean',
            ]);

            $primaryRoleName = $request->input('role', $user->role?->nama_role ?? 'guru');

            if ($request->filled('role')) {
                $roleRecord = Role::where('nama_role', $request->role)->first();
                if ($roleRecord) {
                    $validated['role_id'] = $roleRecord->id;
                }
                unset($validated['role']);
            }

            if ($request->filled('password')) {
                $validated['password_hash'] = Hash::make($request->password);
            }

            $user->update($validated);

            // Sync multi-roles
            $multiRoleMap = [
                'is_wali_kelas'     => 'wali_kelas',
                'is_bk'             => 'guru_bk',
                'is_kurikulum'      => 'kurikulum',
                'is_kesiswaan'      => 'kesiswaan',
                'is_sarpras'        => 'sarpras',
                'is_humas'          => 'humas',
                'is_perpustakaan'   => 'perpustakaan',
                'is_keuangan'       => 'keuangan',
                'is_pembina_ekskul' => 'pembina_ekskul',
                'is_operator'       => 'operator_sekolah',
                'is_kepala_sekolah' => 'kepala_sekolah',
            ];
            $assignedRoles = [$primaryRoleName];
            foreach ($multiRoleMap as $key => $roleName) {
                if ($request->boolean($key)) {
                    $assignedRoles[] = $roleName;
                }
            }
            $roleIds = Role::whereIn('nama_role', array_unique($assignedRoles))->pluck('id')->toArray();
            if (!empty($roleIds)) {
                DB::table('core.user_roles')->where('user_id', $user->id)->delete();
                foreach ($roleIds as $rId) {
                    DB::table('core.user_roles')->insert([
                        'user_id' => $user->id,
                        'role_id' => $rId,
                    ]);
                }
            }

            $msg = 'Data pengguna ' . $user->nama_lengkap . ' berhasil diperbarui.';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'user');

        if ($tab === 'siswa') {
            $siswa = Siswa::withoutTenant()->find($id);
            if ($siswa) {
                $siswa->update(['is_active' => false]);
            }
            $msg = 'Data siswa berhasil dipindahkan ke tong sampah.';
        } else {
            $user = User::find($id);
            if ($user) {
                $user->update(['is_active' => false]);
            }
            $msg = 'Pengguna dinonaktifkan.';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function restore(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'user');

        if ($tab === 'siswa') {
            Siswa::withoutTenant()->where('id', $id)->update(['is_active' => true]);
            $msg = 'Data siswa berhasil dipulihkan ke data aktif.';
        } else {
            User::where('id', $id)->update(['is_active' => true]);
            $msg = 'Pengguna berhasil diaktifkan kembali.';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function exportExcel(Request $request)
    {
        $tab = $request->input('tab', 'siswa');
        $fileName = 'export_' . $tab . '_' . date('Ymd_His') . '.xlsx';

        if ($tab === 'siswa') {
            $rows = [
                ['ID', 'NISN', 'NIS', 'Nama Lengkap', 'Jenis Kelamin', 'Status']
            ];
            $students = Siswa::withoutTenant()->limit(500)->get();
            foreach ($students as $r) {
                $rows[] = [
                    $r->id,
                    $r->nisn ?? '-',
                    $r->nis ?? '-',
                    $r->nama_lengkap ?? '-',
                    $r->jenis_kelamin ?? '-',
                    $r->is_active ? 'Aktif' : 'Non-Aktif',
                ];
            }
        } else {
            $rows = [
                ['ID', 'Nama Lengkap', 'Username', 'Email', 'Role', 'Status']
            ];
            $roleName = $tab === 'guru' ? 'guru' : ($tab === 'karyawan' ? 'keuangan' : 'admin_sekolah');
            $users = User::with('role')->whereHas('role', fn($q) => $q->where('nama_role', $roleName))->get();
            foreach ($users as $r) {
                $rows[] = [
                    $r->id,
                    $r->nama_lengkap ?? '-',
                    $r->username ?? '-',
                    $r->email ?? '-',
                    $r->role?->nama_role ?? '-',
                    $r->is_active ? 'Aktif' : 'Non-Aktif',
                ];
            }
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($rows);

        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    public function uploadBulkPhotos(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'foto_zip' => 'required|file|mimes:zip|max:51200',
        ]);

        $file = $request->file('foto_zip');
        $zip = new \ZipArchive();
        $res = $zip->open($file->getRealPath());

        if ($res !== true) {
            $err = 'Gagal membuka file ZIP.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 400);
            }
            return back()->with('error', $err);
        }

        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $updatedCount = 0;
        $destinationDir = storage_path('app/public/siswa_foto');
        if (!file_exists($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                continue;
            }

            $basename = pathinfo($filename, PATHINFO_FILENAME);
            $parts = explode('_', $basename);
            $nisn = end($parts);

            if (empty($nisn)) continue;

            $siswa = Siswa::withoutTenant()->where('nisn', $nisn)
                ->when($tenantId && $tenantId !== '00000000-0000-0000-0000-000000000000', fn($q) => $q->where('tenant_id', $tenantId))
                ->first();

            if ($siswa) {
                $newFilename = 'siswa_' . $siswa->id . '.' . $ext;
                $targetPath = $destinationDir . '/' . $newFilename;
                copy("zip://" . $file->getRealPath() . "#" . $filename, $targetPath);
                
                $siswa->update([
                    'foto_url' => '/storage/siswa_foto/' . $newFilename,
                ]);
                $updatedCount++;
            }
        }

        $zip->close();
        $msg = "Berhasil mengunggah dan menyinkronkan foto untuk {$updatedCount} siswa.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'count' => $updatedCount]);
        }

        return back()->with('success', $msg);
    }
}
