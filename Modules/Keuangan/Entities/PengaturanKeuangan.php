<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;

class PengaturanKeuangan extends BaseTenantModel
{
    protected $table = 'keuangan.transaksi_spp_pengaturan';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_modul',
        'istilah_tagihan',
        'istilah_tunggakan',
        'format_nomor_kuitansi',
        'nama_bendahara',
        'nip_bendahara',
        'catatan_kuitansi',
        'midtrans_client_key',
        'midtrans_server_key',
        'midtrans_is_production',
        'is_active',
    ];

    protected $casts = [
        'midtrans_is_production' => 'boolean',
        'is_active'               => 'boolean',
    ];
}
