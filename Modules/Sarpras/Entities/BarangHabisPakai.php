<?php

namespace Modules\Sarpras\Entities;

use Modules\Core\Entities\BaseTenantModel;

class BarangHabisPakai extends BaseTenantModel
{
    protected $table = 'sarpras.barang_habis_pakai';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_bhp',
        'nama_barang',
        'kategori',       // 'ATK', 'Alat Kebersihan', 'Bahan Lab', 'Tinta/Toner', 'Lainnya'
        'satuan',         // 'Rim', 'Pcs', 'Box', 'Liter', 'Kg'
        'stok_saat_ini',
        'stok_minimum',   // Alert jika stok di bawah nilai ini
        'harga_satuan',
        'lokasi_penyimpanan',
        'keterangan',
    ];

    protected $casts = [
        'stok_saat_ini' => 'integer',
        'stok_minimum'  => 'integer',
        'harga_satuan'  => 'decimal:2',
    ];
}
