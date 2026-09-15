<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;

class PengajuanIzinCuti extends BaseTenantModel
{
    protected $table = 'absensi.pengajuan_izin_cuti';

    protected $fillable = [
        'id',
        'tenant_id',
        'pemohon_type', // 'siswa', 'gtk'
        'pemohon_id',
        'nama_pemohon',
        'jenis_izin', // 'Sakit', 'Izin Penting', 'Cuti Tahunan', 'Cuti Melahirkan', 'Dinas Luar'
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan',
        'surat_lampiran_url',
        'status_persetujuan', // 'Menunggu', 'Disetujui', 'Ditolak'
        'catatan_approver',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'is_active'       => 'boolean',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];
}
