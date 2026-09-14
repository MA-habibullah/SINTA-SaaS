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
        'pos_id',
        'tahun_ajaran_id',
        'tingkat', // 'X', 'XI', 'XII', 'VII', dll atau null untuk semua
        'jurusan_id',
        'kelas_id',
        'nominal_tarif',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'nominal_tarif' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function pos(): BelongsTo
    {
        return $this->belongsTo(PosKeuangan::class, 'pos_id', 'id');
    }
}
