<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;

class SiswaRegistrasi extends BaseTenantModel
{
    protected $table = 'siswa.registrasi';

    protected $fillable = [
        'id',
        'siswa_id',
        'tenant_id',
        'jenis_pendaftaran',
        'asal_sekolah',
        'npsn_asal',
        'tahun_daftar',
        'no_pendaftaran',
        'status_ppdb',
        'catatan',
        'jalur_diterima',
        'tanggal_masuk',
        'hobi',
        'paud_formal',
        'paud_non_formal',
        'no_ijazah_sebelumnya',
        'tanggal_ijazah_sebelumnya',
        'lama_belajar_sebelumnya',
        'keluar_karena',
        'tanggal_keluar',
        'alasan_keluar',
        'sekolah_tujuan',
        'nomor_skp',
        'tingkat_ditinggalkan',
        'diterima_di_tingkat',
        'nomor_ijazah_kelulusan',
        'nomor_skl',
        'keterangan_setelah_lulus',
        'sekolah_asal_mutasi',
        'pindah_dari_tingkat',
        'pindah_no_surat',
    ];

    protected $casts = [
        'paud_formal'               => 'boolean',
        'paud_non_formal'           => 'boolean',
        'tanggal_masuk'             => 'date',
        'tanggal_ijazah_sebelumnya' => 'date',
        'tanggal_keluar'            => 'date',
        'tahun_daftar'              => 'integer',
        'lama_belajar_sebelumnya'   => 'integer',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
