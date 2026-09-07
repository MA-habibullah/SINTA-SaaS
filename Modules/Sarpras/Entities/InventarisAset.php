<?php

namespace Modules\Sarpras\Entities;

use Modules\Core\Entities\BaseTenantModel;

class InventarisAset extends BaseTenantModel
{
    protected $table = 'sarpras.barang_modal';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_aset',
        'nama_barang',
        'kategori', // 'Elektronik', 'Mebel', 'Alat Lab', 'Kendaraan', 'Bangunan'
        'lokasi_ruangan', // 'Lab Komputer 1', 'Ruang Guru', 'Kelas X RPL'
        'jumlah',
        'satuan', // 'Unit', 'Set', 'Pcs'
        'kondisi', // 'Baik', 'Rusak Ringan', 'Rusak Berat'
        'sumber_dana', // 'BOS', 'Yayasan', 'Komite', 'Hibah'
        'tanggal_pengadaan',
        'harga_perolehan',
        'qr_code_token',
        'foto_barang_url',
        'keterangan',
    ];

    protected $casts = [
        'jumlah'            => 'integer',
        'harga_perolehan'   => 'decimal:2',
        'tanggal_pengadaan' => 'date',
    ];
}
