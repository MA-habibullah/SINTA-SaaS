<?php

namespace Modules\Bk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pelanggaran extends BaseTenantModel
{
    protected $table = 'bk.pelanggaran_siswa';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'tanggal_kejadian',
        'kategori_pelanggaran', // 'Ringan', 'Sedang', 'Berat'
        'nama_pelanggaran',
        'poin_pelanggaran',
        'tindakan_hukuman',
        'petugas_pencatat',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
        'poin_pelanggaran' => 'integer',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
