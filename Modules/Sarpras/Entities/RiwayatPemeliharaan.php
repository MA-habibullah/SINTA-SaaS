<?php

namespace Modules\Sarpras\Entities;

use Modules\Core\Entities\BaseTenantModel;

class RiwayatPemeliharaan extends BaseTenantModel
{
    protected $table = 'sarpras.riwayat_pemeliharaan';

    protected $fillable = [
        'id',
        'tenant_id',
        'aset_id',           // FK ke barang_modal
        'nomor_pemeliharaan',
        'jenis_pemeliharaan', // 'Rutin', 'Perbaikan', 'Penggantian Komponen', 'Renovasi'
        'tanggal_laporan',
        'tanggal_selesai',
        'pelapor',
        'teknisi_vendor',
        'deskripsi_kerusakan',
        'tindakan',
        'biaya_pemeliharaan',
        'status',             // 'Dilaporkan', 'Dalam Proses', 'Selesai', 'Ditunda'
        'hasil_pemeliharaan', // 'Berfungsi Normal', 'Perlu Penggantian', 'Tidak Bisa Diperbaiki'
        'foto_sebelum_url',
        'foto_sesudah_url',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_laporan'   => 'date',
        'tanggal_selesai'   => 'date',
        'biaya_pemeliharaan' => 'decimal:2',
    ];

    public function aset()
    {
        return $this->belongsTo(InventarisAset::class, 'aset_id', 'id');
    }
}
