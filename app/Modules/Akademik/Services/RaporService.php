<?php

namespace App\Modules\Akademik\Services;

use App\Modules\Akademik\Models\RombelModel;
use App\Modules\Akademik\Models\NilaiRaporModel;

class RaporService {
    public function getRombelAktif(string $tenantId, string $tahunAjaranId): array {
        return RombelModel::getActiveRombel($tenantId, $tahunAjaranId);
    }

    public function getRaporSiswa(string $tenantId, string $siswaId, string $semester): array {
        return NilaiRaporModel::getNilaiBySiswaSemester($tenantId, $siswaId, $semester);
    }
}
