<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loker extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_loker';

    protected $fillable = [
        'id',
        'tenant_id',
        'nomor_loker',
        'lokasi_ruangan',
        'status', // tersedia, terisi, rusak, kunci_hilang
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(LokerLog::class, 'loker_id');
    }

    public function activeLog()
    {
        return $this->hasOne(LokerLog::class, 'loker_id')->where('status_pinjam', 'dipinjam')->latest('waktu_pinjam');
    }
}
