<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosKeuangan extends BaseTenantModel
{
    protected $table = 'keuangan.transaksi_spp_komponen';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_pos',
        'nama_pos',
        'tipe_periode', // 'Bulanan', 'Bebas', 'Semester', 'Tahunan'
        'urutan',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'urutan'    => 'integer',
        'is_active' => 'boolean',
    ];

    public function tarif(): HasMany
    {
        return $this->hasMany(TarifPembayaran::class, 'pos_id', 'id');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(TagihanSiswa::class, 'pos_id', 'id');
    }
}
