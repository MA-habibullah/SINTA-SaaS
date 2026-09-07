<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifPembayaran extends BaseTenantModel
{
    protected $table = 'keuangan.transaksi_spp_tarif';

    protected $fillable = [
        'id',
        'tenant_id',
        'pos_keuangan_id',
        'tahun_ajaran_id',
        'tingkat', // 'Semua', 'X', 'XI', 'XII'
        'jurusan_id',
        'nominal',
        'keterangan',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
    ];

    public function pos(): BelongsTo
    {
        return $this->belongsTo(PosKeuangan::class, 'pos_keuangan_id', 'id');
    }
}
