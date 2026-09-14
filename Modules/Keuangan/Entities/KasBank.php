<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KasBank extends BaseTenantModel
{
    protected $table = 'keuangan.kas_bank';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_kas',
        'nama_kas', // e.g. 'Kas Tunai Bendahara', 'Bank BSI Penampung'
        'nomor_rekening',
        'atas_nama',
        'saldo_awal',
        'saldo_saat_ini',
        'is_active',
    ];

    protected $casts = [
        'saldo_awal'     => 'decimal:2',
        'saldo_saat_ini' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function transaksi(): HasMany
    {
        return $this->hasMany(TransaksiPembayaran::class, 'kas_id', 'id');
    }
}
