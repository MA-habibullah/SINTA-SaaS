<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;

class SiswaFisikKesehatan extends BaseTenantModel
{
    protected $table = 'siswa.fisik_kesehatan_siswa';

    protected $fillable = [
        'id',
        'siswa_id',
        'tenant_id',
        'tinggi_badan',
        'berat_badan',
        'lingkar_kepala',
        'golongan_darah',
        'riwayat_penyakit',
        'alergi',
        'disabilitas',
        'detail_semester',
    ];

    protected $casts = [
        'tinggi_badan'    => 'integer',
        'berat_badan'     => 'integer',
        'lingkar_kepala'  => 'integer',
        'detail_semester' => 'array',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
