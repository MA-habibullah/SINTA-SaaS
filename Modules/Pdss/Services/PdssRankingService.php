<?php

namespace Modules\Pdss\Services;

use Modules\Siswa\Entities\Siswa;
use Modules\Core\Entities\Tenant;
use Modules\Pdss\Entities\KesiapanSiswa;
use Modules\Pdss\Entities\PilihanKampus;
use Modules\Pdss\Entities\PdssLock;
use Modules\Pdss\Entities\PengunduranDiri;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PdssRankingService
{
    /**
     * Hitung pemeringkatan siswa eligible SNBP (PDSS) berdasarkan nilai rapor & akreditasi
     * Sesuai Tahun Ajaran & Riwayat Kelas Siswa (siswa.anggota_kelas)
     * Dengan alur: Pengunduran Diri (Langkah 3) & Promosi Otomatis Siswa Cadangan sebelum Simulasi PTN (Langkah 4)
     */
    public function hitungRankingSnbp(?string $tenantId, ?string $jurusan = null, ?string $angkatan = null, bool $isSuperAdmin = false, ?string $tahunAjaran = null, int $noSimulasi = 1, ?int $kuotaPersenOverride = null, bool $useERapor = false): array
    {
        if ($isSuperAdmin && !$tenantId) {
            $targetTenantId = null;
            $tenant = Tenant::first();
        } else {
            $tenant = $tenantId ? Tenant::where('id', $tenantId)->first() : Tenant::first();
            $targetTenantId = $tenant?->id ?? $tenantId;
        }

        $akreditasiRaw = strtoupper(trim($tenant?->akreditasi ?? 'A'));
        if (str_starts_with($akreditasiRaw, 'A') || str_contains($akreditasiRaw, 'UNGGUL')) {
            $defaultKuota = 40;
            $akreditasi = 'A (Unggul)';
        } elseif (str_starts_with($akreditasiRaw, 'B') || str_contains($akreditasiRaw, 'BAIK SEKALI')) {
            $defaultKuota = 25;
            $akreditasi = 'B (Baik Sekali)';
        } else {
            $defaultKuota = 5;
            $akreditasi = 'C (Cukup / Lainnya)';
        }

        if ($kuotaPersenOverride !== null && $kuotaPersenOverride > 0) {
            $kuotaPersen = $kuotaPersenOverride;
        } else {
            $kuotaPersen = $defaultKuota + ($useERapor ? 5 : 0);
        }

        // Resolusi Tahun Ajaran
        $taModel = \Modules\Akademik\Entities\TahunAjaran::withoutTenant()->where('nama_tahun_ajaran', $tahunAjaran)->first()
            ?? \Modules\Akademik\Entities\TahunAjaran::withoutTenant()->where('id', $tahunAjaran)->first()
            ?? \Modules\Akademik\Entities\TahunAjaran::withoutTenant()->where('is_active', true)->first();
        $tahunAjaranId = $taModel?->id;
        $namaTahunAjaran = $taModel?->nama_tahun_ajaran ?? ($tahunAjaran ?: '2026/2027');

        // Query Siswa - STRICTLY Grade 12 (Kelas XII) Berdasarkan Riwayat Kelas (siswa.anggota_kelas)
        $akQuery = DB::table('siswa.anggota_kelas as ak')
            ->join('akademik.kelas as k', 'ak.kelas_id', '=', 'k.id')
            ->join('siswa.siswa as s', 'ak.siswa_id', '=', 's.id')
            ->where('ak.tahun_ajaran', $namaTahunAjaran)
            ->where('ak.is_aktif', true)
            ->where('s.is_active', true)
            ->where(function ($kq) {
                $kq->where('k.nama_kelas', 'ILIKE', '%XII%')
                   ->orWhere('k.nama_kelas', 'ILIKE', '%12%');
            })
            ->select(
                's.id',
                's.tenant_id',
                's.nama_lengkap',
                's.nisn',
                's.nis',
                's.jurusan',
                's.agama',
                'k.nama_kelas as kelas_riwayat',
                's.kelas_saat_ini'
            );

        if ($targetTenantId) {
            $akQuery->where('ak.tenant_id', $targetTenantId);
        }

        if ($jurusan && $jurusan !== 'all' && $jurusan !== '') {
            $akQuery->where(function ($jq) use ($jurusan) {
                $jq->where('s.jurusan', 'ILIKE', "%{$jurusan}%")
                   ->orWhere('k.nama_kelas', 'ILIKE', "%{$jurusan}%");
            });
        }

        $anggotaList = $akQuery->get();

        if ($anggotaList->count() > 0) {
            $siswaList = $anggotaList;
        } else {
            // Jika tidak ada data anggota kelas 12 aktif di tahun ajaran ini, kembalikan daftar kosong (0 siswa)
            $siswaList = collect([]);
        }

        $totalSiswa = $siswaList->count();
        $kuotaEligible = (int)ceil(($totalSiswa * $kuotaPersen) / 100);
        if ($kuotaEligible === 0 && $totalSiswa > 0) $kuotaEligible = 1;

        if ($totalSiswa === 0) {
            return [
                'tenant_id'          => $targetTenantId,
                'tahun_ajaran'       => $namaTahunAjaran,
                'tahun_ajaran_id'    => $tahunAjaranId,
                'akreditasi_sekolah' => $akreditasi,
                'kuota_persen'       => $kuotaPersen,
                'total_siswa'        => 0,
                'kuota_eligible'     => 0,
                'active_eligible'    => 0,
                'total_pengunduran'  => 0,
                'promoted_count'     => 0,
                'no_simulasi'        => $noSimulasi,
                'ranking_list'       => [],
            ];
        }

        $siswaIds = $siswaList->pluck('id')->toArray();

        // Ambil data nilai rapor dari buku induk (akademik.detail_nilai_rapor)
        $stringSiswaIds = array_map('strval', $siswaIds);
        $nilaiDb = DB::table('akademik.detail_nilai_rapor as dnr')
            ->leftJoin('akademik.mata_pelajaran as mp', DB::raw('mp.id::text'), '=', DB::raw('dnr.mapel_id::text'))
            ->whereIn('dnr.siswa_id', $stringSiswaIds)
            ->where('dnr.nilai_akhir', '>', 0)
            ->select('dnr.siswa_id', 'dnr.semester', 'dnr.mapel_id', 'mp.nama_mata_pelajaran', 'dnr.nilai_akhir')
            ->get()
            ->groupBy('siswa_id');

        // Ambil existing overrides / kesiapan data per tahun ajaran
        $existingKesiapanQuery = KesiapanSiswa::withoutTenant();
        if ($targetTenantId) {
            $existingKesiapanQuery->where('tenant_id', $targetTenantId);
        }
        if ($tahunAjaranId) {
            $existingKesiapanQuery->where(function($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('tahun_ajaran_id');
            });
        }
        $existingKesiapan = $existingKesiapanQuery->get()->keyBy('siswa_id');

        // Ambil pilihan kampus siswa untuk nomor simulasi aktif per tahun ajaran
        $pilihanQuery = PilihanKampus::withoutTenant();
        if ($targetTenantId) {
            $pilihanQuery->where('tenant_id', $targetTenantId);
        }
        $pilihanQuery->where('no_simulasi', $noSimulasi);
        if ($tahunAjaranId) {
            $pilihanQuery->where(function($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('tahun_ajaran_id');
            });
        }
        $pilihanList = $pilihanQuery->with(['kampus', 'prodi'])->get()->groupBy('siswa_id');

        $scoredSiswa = [];

        foreach ($siswaList as $siswa) {
            $kesiapan = $existingKesiapan->get($siswa->id);

            // Hitung rata-rata nilai dari detail_nilai_rapor dengan normalisasi 1 Agama
            $studentGrades = $nilaiDb->get($siswa->id) ?? collect([]);
            $avgScore = 0.00;

            if ($studentGrades->isNotEmpty()) {
                // Kelompokkan per semester
                $bySemester = $studentGrades->groupBy('semester');
                $semesterAverages = [];

                foreach ($bySemester as $sem => $grades) {
                    $mapelValues = [];
                    $religionValues = [];

                    foreach ($grades as $g) {
                        $mapelName = strtolower(trim($g->nama_mata_pelajaran ?? ''));
                        $isAgama = str_contains($mapelName, 'agama') || str_contains($mapelName, 'pendidikan agama');

                        if ($isAgama) {
                            $religionValues[] = (float)$g->nilai_akhir;
                        } else {
                            $mapelKey = $g->mapel_id ?: $mapelName;
                            $mapelValues[$mapelKey] = (float)$g->nilai_akhir;
                        }
                    }

                    // Jika ada nilai agama, ambil 1 nilai (maksimum / yang diambil siswa)
                    if (!empty($religionValues)) {
                        $mapelValues['_agama_'] = max($religionValues);
                    }

                    if (!empty($mapelValues)) {
                        $semesterAverages[] = array_sum($mapelValues) / count($mapelValues);
                    }
                }

                if (!empty($semesterAverages)) {
                    $avgScore = round(array_sum($semesterAverages) / count($semesterAverages), 2);
                }
            } elseif ($kesiapan && $kesiapan->nilai_rata_rata > 0) {
                $avgScore = round((float)$kesiapan->nilai_rata_rata, 2);
            }

            // Sertifikat Prestasi
            $prestasiCount = 0;
            try {
                $prestasiCount = DB::table('kesiswaan.prestasi_siswa_anggota')
                    ->where('id_siswa', (string)$siswa->id)
                    ->count();
            } catch (\Throwable $e) {
                $prestasiCount = 0;
            }

            // Pilihan Kampus pada nomor simulasi ini
            $choices = $pilihanList->get($siswa->id);
            $pilihan1 = $choices?->firstWhere('no_pilihan', 1);
            $pilihan2 = $choices?->firstWhere('no_pilihan', 2);

            $kelasLabel = $siswa->kelas_riwayat ?: ($siswa->kelas_saat_ini ?: 'XII');

            $scoredSiswa[] = [
                'siswa_id'                => $siswa->id,
                'tenant_id'               => $siswa->tenant_id,
                'nisn'                    => trim($siswa->nisn) ?: '-',
                'nis'                     => trim($siswa->nis) ?: '-',
                'nama_lengkap'            => $siswa->nama_lengkap,
                'jurusan'                 => $siswa->jurusan ?: ($kelasLabel ? 'Kelas ' . $kelasLabel : 'MIPA'),
                'kelas_saat_ini'          => $kelasLabel,
                'kelas_riwayat'           => $kelasLabel,
                'tahun_ajaran'            => $namaTahunAjaran,
                'rata_rata_nilai'         => $avgScore,
                'total_prestasi'          => $prestasiCount,
                'status_pengunduran_diri' => (bool)($kesiapan?->status_pengunduran_diri ?? false),
                'catatan_override'        => $kesiapan?->catatan_override ?? null,
                'is_override'             => !is_null($kesiapan?->is_eligible_final),
                'override_value'          => $kesiapan?->is_eligible_final,
                'no_simulasi'             => $noSimulasi,
                'pilihan_1'               => $pilihan1 ? [
                    'kampus_id'   => $pilihan1->kampus_id,
                    'nama_kampus' => $pilihan1->kampus?->nama_kampus ?? 'PTN Pilihan 1',
                    'prodi_id'    => $pilihan1->prodi_id,
                    'nama_prodi'  => $pilihan1->prodi?->nama_prodi ?? $pilihan1->prodi?->program_studi ?? 'Program Studi 1',
                    'jenjang'     => $pilihan1->prodi?->jenjang ?? 'S1',
                    'status'      => $pilihan1->status ?? 'Draft',
                ] : null,
                'pilihan_2'               => $pilihan2 ? [
                    'kampus_id'   => $pilihan2->kampus_id,
                    'nama_kampus' => $pilihan2->kampus?->nama_kampus ?? 'PTN Pilihan 2',
                    'prodi_id'    => $pilihan2->prodi_id,
                    'nama_prodi'  => $pilihan2->prodi?->nama_prodi ?? $pilihan2->prodi?->program_studi ?? 'Program Studi 2',
                    'jenjang'     => $pilihan2->prodi?->jenjang ?? 'S1',
                    'status'      => $pilihan2->status ?? 'Draft',
                ] : null,
            ];
        }

        // Sort descending: Rata-rata nilai, lalu Total prestasi
        usort($scoredSiswa, function ($a, $b) {
            if ($b['rata_rata_nilai'] == $a['rata_rata_nilai']) {
                return $b['total_prestasi'] <=> $a['total_prestasi'];
            }
            return $b['rata_rata_nilai'] <=> $a['rata_rata_nilai'];
        });

        // Hitung Ranking & Status Eligible dengan Promosi Otomatis Kuota Cadangan
        $eligibleFilled = 0;
        $totalPengunduran = 0;
        $promotedCount = 0;

        foreach ($scoredSiswa as $rank => &$item) {
            $item['ranking_sekolah'] = $rank + 1;

            if ($item['status_pengunduran_diri']) {
                $totalPengunduran++;
                $item['is_eligible'] = false;
                $item['is_cadangan_promosi'] = false;
                $item['status_snbp'] = 'Mengundurkan Diri (Kuota Dialihkan)';
                $item['status_badge'] = 'resigned';
            } elseif ($item['is_override']) {
                $item['is_eligible'] = (bool)$item['override_value'];
                $item['is_cadangan_promosi'] = false;
                $item['status_snbp'] = $item['is_eligible'] ? 'Eligible (Override)' : 'Tidak Eligible (Override)';
                $item['status_badge'] = $item['is_eligible'] ? 'eligible_override' : 'not_eligible';
                if ($item['is_eligible']) {
                    $eligibleFilled++;
                }
            } else {
                if ($eligibleFilled < $kuotaEligible) {
                    $isPromoted = ($item['ranking_sekolah'] > $kuotaEligible);
                    $item['is_eligible'] = true;
                    $item['is_cadangan_promosi'] = $isPromoted;
                    if ($isPromoted) {
                        $promotedCount++;
                        $item['status_snbp'] = 'Eligible (Pengganti / Promosi Cadangan)';
                        $item['status_badge'] = 'eligible_cadangan';
                    } else {
                        $item['status_snbp'] = 'Eligible (' . $kuotaPersen . '%)';
                        $item['status_badge'] = 'eligible';
                    }
                    $eligibleFilled++;
                } else {
                    $item['is_eligible'] = false;
                    $item['is_cadangan_promosi'] = false;
                    $item['status_snbp'] = 'Cadangan (Peringkat ' . $item['ranking_sekolah'] . ')';
                    $item['status_badge'] = 'not_eligible';
                }
            }
        }
        unset($item);

        // 3. Deteksi Tabrakan Pilihan Prodi per Jurusan / Sekolah untuk Pilihan 1 & Pilihan 2
        $prodiMapPil1 = [];
        $prodiMapPil2 = [];

        foreach ($scoredSiswa as $item) {
            if (!empty($item['pilihan_1']['prodi_id'])) {
                $pId1 = $item['pilihan_1']['prodi_id'];
                $prodiMapPil1[$pId1][] = [
                    'siswa_id'        => $item['siswa_id'],
                    'nama_lengkap'    => $item['nama_lengkap'],
                    'nisn'            => $item['nisn'],
                    'jurusan'         => $item['jurusan'],
                    'kelas'           => $item['kelas_saat_ini'] ?? $item['kelas_riwayat'] ?? 'XII',
                    'ranking_sekolah' => $item['ranking_sekolah'],
                    'rata_rata_nilai' => $item['rata_rata_nilai'],
                    'nilai_rata_rata' => $item['rata_rata_nilai'],
                    'total_prestasi'  => $item['total_prestasi'],
                    'no_pilihan'      => 1,
                    'status'          => $item['pilihan_1']['status'] ?? 'Draft',
                    'nama_kampus'     => $item['pilihan_1']['nama_kampus'] ?? 'PTN Pilihan 1',
                    'nama_prodi'      => $item['pilihan_1']['nama_prodi'] ?? 'Program Studi 1',
                    'jenjang'         => $item['pilihan_1']['jenjang'] ?? 'S1',
                    'daya_tampung'    => $item['pilihan_1']['daya_tampung'] ?? 20,
                ];
            }

            if (!empty($item['pilihan_2']['prodi_id'])) {
                $pId2 = $item['pilihan_2']['prodi_id'];
                $prodiMapPil2[$pId2][] = [
                    'siswa_id'        => $item['siswa_id'],
                    'nama_lengkap'    => $item['nama_lengkap'],
                    'nisn'            => $item['nisn'],
                    'jurusan'         => $item['jurusan'],
                    'kelas'           => $item['kelas_saat_ini'] ?? $item['kelas_riwayat'] ?? 'XII',
                    'ranking_sekolah' => $item['ranking_sekolah'],
                    'rata_rata_nilai' => $item['rata_rata_nilai'],
                    'nilai_rata_rata' => $item['rata_rata_nilai'],
                    'total_prestasi'  => $item['total_prestasi'],
                    'no_pilihan'      => 2,
                    'status'          => $item['pilihan_2']['status'] ?? 'Draft',
                    'nama_kampus'     => $item['pilihan_2']['nama_kampus'] ?? 'PTN Pilihan 2',
                    'nama_prodi'      => $item['pilihan_2']['nama_prodi'] ?? 'Program Studi 2',
                    'jenjang'         => $item['pilihan_2']['jenjang'] ?? 'S1',
                    'daya_tampung'    => $item['pilihan_2']['daya_tampung'] ?? 20,
                ];
            }
        }

        // Tautkan status konflik dan detail pesaing ke tiap siswa
        foreach ($scoredSiswa as &$item) {
            if (!empty($item['pilihan_1']['prodi_id'])) {
                $pId1 = $item['pilihan_1']['prodi_id'];
                $competitors1 = $prodiMapPil1[$pId1] ?? [];
                if (count($competitors1) > 1) {
                    // Sort descending: ranking terbaik di atas
                    usort($competitors1, fn($a, $b) => $a['ranking_sekolah'] <=> $b['ranking_sekolah']);
                    $item['pilihan_1']['is_bentrok'] = true;
                    $item['pilihan_1']['total_bentrok'] = count($competitors1);
                    $item['pilihan_1']['konflik_detail'] = $competitors1;
                } else {
                    $item['pilihan_1']['is_bentrok'] = false;
                    $item['pilihan_1']['total_bentrok'] = 0;
                    $item['pilihan_1']['konflik_detail'] = [];
                }
            }

            if (!empty($item['pilihan_2']['prodi_id'])) {
                $pId2 = $item['pilihan_2']['prodi_id'];
                $competitors2 = $prodiMapPil2[$pId2] ?? [];
                if (count($competitors2) > 1) {
                    usort($competitors2, fn($a, $b) => $a['ranking_sekolah'] <=> $b['ranking_sekolah']);
                    $item['pilihan_2']['is_bentrok'] = true;
                    $item['pilihan_2']['total_bentrok'] = count($competitors2);
                    $item['pilihan_2']['konflik_detail'] = $competitors2;
                } else {
                    $item['pilihan_2']['is_bentrok'] = false;
                    $item['pilihan_2']['total_bentrok'] = 0;
                    $item['pilihan_2']['konflik_detail'] = [];
                }
            }
        }

        return [
            'tenant_id'          => $targetTenantId,
            'tahun_ajaran'       => $namaTahunAjaran,
            'tahun_ajaran_id'    => $tahunAjaranId,
            'akreditasi_sekolah' => $akreditasi,
            'kuota_persen'       => $kuotaPersen,
            'total_siswa'        => $totalSiswa,
            'kuota_eligible'     => $kuotaEligible,
            'active_eligible'    => $eligibleFilled,
            'total_pengunduran'  => $totalPengunduran,
            'promoted_count'     => $promotedCount,
            'no_simulasi'        => $noSimulasi,
            'ranking_list'       => $scoredSiswa,
        ];
    }

    /**
     * Deteksi Tabrakan Pilihan Jurusan Antar Siswa Satu Sekolah (Conflict Collision Matrix)
     * Dapat difilter per nomor simulasi dan per tahun ajaran
     */
    public function deteksiTabrakanPilihan(?string $tenantId, bool $isSuperAdmin = false, int $noSimulasi = 1, ?string $tahunAjaranId = null): array
    {
        $query = PilihanKampus::withoutTenant();
        if ($tenantId) {
            $query->where('pdss.pilihan_kampus.tenant_id', $tenantId);
        }
        $query->where('pdss.pilihan_kampus.no_simulasi', $noSimulasi);
        if ($tahunAjaranId) {
            $query->where(function($q) use ($tahunAjaranId) {
                $q->where('pdss.pilihan_kampus.tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('pdss.pilihan_kampus.tahun_ajaran_id');
            });
        }

        $pilihanData = $query->with(['siswa', 'kampus', 'prodi'])->get();

        // Group by prodi_id
        $grouped = $pilihanData->groupBy(function ($item) {
            return ($item->prodi_id ?: $item->kampus_id) . '_pil_' . $item->no_pilihan;
        });

        $collisions = [];

        foreach ($grouped as $key => $items) {
            if ($items->count() > 1) {
                $first = $items->first();
                $students = $items->map(function ($p) {
                    return [
                        'siswa_id'     => $p->siswa_id,
                        'nama_lengkap' => $p->siswa?->nama_lengkap ?? 'Siswa',
                        'nisn'         => $p->siswa?->nisn ?? '-',
                        'jurusan'      => $p->siswa?->jurusan ?? ($p->siswa?->kelas_saat_ini ? 'Kelas ' . $p->siswa?->kelas_saat_ini : 'MIPA'),
                        'status'       => $p->status ?? 'Draft',
                    ];
                })->values()->toArray();

                $collisions[] = [
                    'key'           => $key,
                    'no_simulasi'   => $noSimulasi,
                    'nama_kampus'   => $first->kampus?->nama_kampus ?? 'Universitas Negeri',
                    'nama_prodi'    => $first->prodi?->nama_prodi ?? $first->prodi?->program_studi ?? 'Program Studi',
                    'jenjang'       => $first->prodi?->jenjang ?? 'S1',
                    'daya_tampung'  => $first->prodi?->daya_tampung_sekarang ?? 50,
                    'no_pilihan'    => $first->no_pilihan,
                    'total_bentrok' => count($students),
                    'students'      => $students,
                ];
            }
        }

        return $collisions;
    }

    /**
     * Ambil status tahapan 5-Step Workflow Sesuai Aturan Alur Baku PDSS SINTA SaaS
     * Mendukung isolasi per tahun ajaran
     */
    public function getWorkflowStatus(?string $tenantId, bool $isSuperAdmin = false, ?string $tahunAjaranId = null): array
    {
        $query = PdssLock::withoutTenant();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($tahunAjaranId) {
            $query->where(function($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('tahun_ajaran_id');
            });
        }

        $locks = $query->get()->keyBy('step');

        $steps = [
            1 => ['nama' => 'Pilihan Mapel & Nilai Rapor', 'deskripsi' => 'Pilih mapel & verifikasi nilai rapor 5 semester', 'tab' => 'mapel', 'is_locked' => false, 'locked_by' => null, 'locked_at' => null],
            2 => ['nama' => 'Penetapan Kuota & Pemeringkatan', 'deskripsi' => 'Penetapan kuota akreditasi & ranking paralel', 'tab' => 'pemeringkatan', 'is_locked' => false, 'locked_by' => null, 'locked_at' => null],
            3 => ['nama' => 'Pengunduran Diri & Kuota Pengganti', 'deskripsi' => 'Pencatatan siswa mundur & promosi kuota cadangan', 'tab' => 'pengunduran', 'is_locked' => false, 'locked_by' => null, 'locked_at' => null],
            4 => ['nama' => 'Simulasi Pilihan PTN (Sim 1, 2, 3)', 'deskripsi' => 'Simulasi 1 (Draf), Simulasi 2 (Rasionalisasi), Simulasi 3 (Permanen)', 'tab' => 'simulasi', 'is_locked' => false, 'locked_by' => null, 'locked_at' => null],
            5 => ['nama' => 'Katalog PTN & Finalisasi PDSS', 'deskripsi' => 'Master prodi PTN SNPMB dan finalisasi data siap ekspor PDSS', 'tab' => 'katalog', 'is_locked' => false, 'locked_by' => null, 'locked_at' => null],
        ];

        foreach ($steps as $st => &$info) {
            if ($locks->has($st)) {
                $l = $locks->get($st);
                $info['is_locked'] = (bool)$l->is_locked;
                $info['locked_by'] = $l->locked_by;
                $info['locked_at'] = $l->locked_at?->format('d M Y H:i');
            }
        }

        return $steps;
    }
}
