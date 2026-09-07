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
        'nama_pos', // e.g. 'SPP Bulanan', 'Uang Gedung / DSP', 'Seragam & Buku'
        'tipe_pembayaran', // 'Bulanan', 'Bebas' (Sekali bayar/Cicilan)
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tarif(): HasMany
    {
        return $this->hasMany(TarifPembayaran::class, 'pos_keuangan_id', 'id');
    }
}
