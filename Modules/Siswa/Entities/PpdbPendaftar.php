<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;

class PpdbPendaftar extends BaseTenantModel
{
    protected $table = 'kesiswaan.pendaftaran_spmb';

    protected $fillable = [
        'id',
        'tenant_id',
        'no_pendaftaran',
        'nisn',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'asal_sekolah',
        'pilihan_jurusan_1',
        'pilihan_jurusan_2',
        'nama_orang_tua',
        'no_hp_orang_tua',
        'alamat',
        'jalur_pendaftaran',
        'nilai_rata_rata_rapor',
        'status_pendaftaran',
        'status_verifikasi_berkas',
        'catatan_verifikasi',
        'is_diterima',
        'tahun_ajaran_daftar',
        'dokumen_lampiran_json',
    ];

    protected $casts = [
        'tanggal_lahir'          => 'date',
        'nilai_rata_rata_rapor'  => 'decimal:2',
        'is_diterima'            => 'boolean',
        'dokumen_lampiran_json'  => 'array',
    ];
}
