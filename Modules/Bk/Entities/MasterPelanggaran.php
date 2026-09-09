<?php

namespace Modules\Bk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterPelanggaran extends BaseTenantModel
{
    use SoftDeletes;

    protected $table = 'bk.master_pelanggaran';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_master_pelanggaran',
        'nama_pelanggaran',
        'kategori', // 'Ringan', 'Sedang', 'Berat', 'Khusus'
        'bobot_poin',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'bobot_poin' => 'integer',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function pelanggaranList(): HasMany
    {
        return $this->hasMany(Pelanggaran::class, 'pelanggaran_id', 'id');
    }
}
