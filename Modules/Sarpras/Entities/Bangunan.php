<?php

namespace Modules\Sarpras\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bangunan extends BaseTenantModel
{
    protected $table = 'sarpras.bangunan';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_bangunan',
        'nama_bangunan',
        'tahun_dibangun',
        'luas_m2',
        'jumlah_lantai',
        'kondisi',        // 'Baik', 'Rusak Ringan', 'Rusak Berat'
        'sumber_dana',    // 'Pemerintah', 'Yayasan', 'BOS', 'Komite', 'Hibah'
        'keterangan',
    ];

    protected $casts = [
        'luas_m2'       => 'decimal:2',
        'jumlah_lantai' => 'integer',
        'tahun_dibangun' => 'integer',
    ];

    public function ruangans(): HasMany
    {
        return $this->hasMany(Ruangan::class, 'bangunan_id', 'id');
    }
}
