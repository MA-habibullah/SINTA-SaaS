<?php

namespace App\Modules\Siswa\Services;

use App\Modules\Siswa\Models\SiswaModel;

class SiswaService {
    public function getSiswaDetail(string $tenantId, string $siswaId): ?array {
        return SiswaModel::findById($tenantId, $siswaId);
    }

    public function getDaftarSiswaAktif(string $tenantId, int $limit = 50, int $offset = 0): array {
        return SiswaModel::getActiveSiswa($tenantId, $limit, $offset);
    }
}
