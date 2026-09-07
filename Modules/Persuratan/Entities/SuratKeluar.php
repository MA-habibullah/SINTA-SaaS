<?php

namespace Modules\Persuratan\Entities;

use Modules\Core\Entities\BaseTenantModel;

class SuratKeluar extends BaseTenantModel
{
    protected $table = 'persuratan.surat_keluar';

    protected $fillable = [
        'id',
        'tenant_id',
        'nomor_surat',
        'klasifikasi_kode',
        'tujuan_surat',
        'tanggal_surat',
        'perihal',
        'ringkasan_isi',
        'penandatangan_nama',
        'penandatangan_jabatan',
        'status_surat', // 'Draft', 'Menunggu Persetujuan', 'Disetujui', 'Terkirim'
        'file_surat_url',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
    ];
}
