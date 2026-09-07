<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;

class KasBank extends BaseTenantModel
{
    protected $table = 'keuangan.kas_bank';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_akun',
        'nama_kas_bank', // e.g. 'Kas Utama Sekolah', 'Bank BNI Operasional'
        'nomor_rekening',
        'atas_nama',
        'saldo_saat_ini',
        'is_active',
    ];

    protected $casts = [
        'saldo_saat_ini' => 'decimal:2',
        'is_active'      => 'boolean',
    ];
}
