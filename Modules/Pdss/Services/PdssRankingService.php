<?php

namespace Modules\Pdss\Services;

use Modules\Siswa\Entities\Siswa;
use Modules\Akademik\Entities\NilaiRapor;
use Modules\Core\Entities\SekolahIdentitas;

class PdssRankingService
{
    /**
     * Hitung pemeringkatan siswa eligible SNBP (PDSS) berdasarkan nilai rapor semester 1-5
     */
    public function hitungRankingSnbp(string $tenantId, string $jurusan, string $angkatan): array
    {
        $identitas = SekolahIdentitas::where('tenant_id', $tenantId)->first();
        $akreditasi = strtoupper(trim($identitas->akreditasi ?? 'A'));

        // Kuota SNBP berdasarkan Akreditasi Kemendikbud:
        // A: 40%, B: 25%, C/Lainnya: 5%
        $kuotaPersen = match ($akreditasi) {
            'A' => 40,
            'B' => 25,
            default => 5,
        };

        // Ambil seluruh siswa tingkat akhir / angkatan tersebut pada jurusan terkait
        $siswaList = Siswa::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->where('jurusan', $jurusan)
            ->where('angkatan', $angkatan)
            ->where('status_siswa', 'Aktif')
            ->get();

        $totalSiswa = $siswaList->count();
        $kuotaEligible = (int)ceil(($totalSiswa * $kuotaPersen) / 100);

        $scoredSiswa = [];

        foreach ($siswaList as $siswa) {
            // Rata-rata nilai akhir siswa pada semester 1 sampai 5
            $avgScore = NilaiRapor::withoutTenant()
                ->where('tenant_id', $tenantId)
                ->where('siswa_id', $siswa->id)
                ->avg('nilai_akhir') ?? 0;

            $totalPrestasi = $siswa->prestasi()->count();

            $scoredSiswa[] = [
                'siswa_id'       => $siswa->id,
                'nisn'           => $siswa->nisn,
                'nama_lengkap'   => $siswa->nama_lengkap,
                'jurusan'        => $siswa->jurusan,
                'rata_rata_nilai'=> round((float)$avgScore, 2),
                'total_prestasi' => $totalPrestasi,
            ];
        }

        // Sort descending berdasarkan rata-rata nilai, lalu prestasi
        usort($scoredSiswa, function ($a, $b) {
            if ($b['rata_rata_nilai'] == $a['rata_rata_nilai']) {
                return $b['total_prestasi'] <=> $a['total_prestasi'];
            }
            return $b['rata_rata_nilai'] <=> $a['rata_rata_nilai'];
        });

        // Tandai status eligible
        foreach ($scoredSiswa as $rank => &$item) {
            $item['ranking'] = $rank + 1;
            $item['is_eligible'] = ($item['ranking'] <= $kuotaEligible);
            $item['status_snbp'] = $item['is_eligible'] ? 'Eligible (' . $kuotaPersen . '%)' : 'Tidak Eligible';
        }

        return [
            'akreditasi_sekolah' => $akreditasi,
            'kuota_persen'       => $kuotaPersen,
            'total_siswa'        => $totalSiswa,
            'kuota_eligible'     => $kuotaEligible,
            'ranking_list'       => $scoredSiswa,
        ];
    }
}
