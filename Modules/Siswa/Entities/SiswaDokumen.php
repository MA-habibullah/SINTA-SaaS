<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;

class SiswaDokumen extends BaseTenantModel
{
    protected $table = 'siswa.dokumen';

    protected $fillable = [
        'id',
        'siswa_id',
        'tenant_id',
        'jenis_dokumen',
        'nama_file',
        'url_file',
        'keterangan',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
