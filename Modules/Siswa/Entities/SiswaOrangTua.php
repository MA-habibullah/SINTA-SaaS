<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;

class SiswaOrangTua extends BaseTenantModel
{
    protected $table = 'siswa.orang_tua';

    protected $fillable = [
        'id',
        'siswa_id',
        'tenant_id',
        'hubungan',
        'nama_lengkap',
        'nik',
        'pekerjaan',
        'pendidikan',
        'penghasilan',
        'no_hp',
        'alamat',
        'is_aktif',
        'tahun_lahir',
        'tanggal_lahir',
        'agama',
        'kewarganegaraan',
        'status_hidup',
        'hubungan_wali',
        'tempat_lahir',
        'id_tempat_lahir',
    ];

    protected $casts = [
        'is_aktif'        => 'boolean',
        'tanggal_lahir'   => 'date',
        'tahun_lahir'     => 'integer',
        'penghasilan'     => 'integer',
        'id_tempat_lahir' => 'integer',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
