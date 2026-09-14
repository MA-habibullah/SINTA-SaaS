<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeringananSiswa extends BaseTenantModel
{
    protected $table = 'keuangan.transaksi_spp_keringanan';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'pos_id',
        'tipe_potongan', // 'Nominal', 'Persentase'
        'nilai_potongan',
        'alasan',
        'is_active',
    ];

    protected $casts = [
        'nilai_potongan' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function pos(): BelongsTo
    {
        return $this->belongsTo(PosKeuangan::class, 'pos_id', 'id');
    }
}
