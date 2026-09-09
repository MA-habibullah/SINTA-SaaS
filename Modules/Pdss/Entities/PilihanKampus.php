<?php

namespace Modules\Pdss\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilihanKampus extends BaseTenantModel
{
    protected $table = 'pdss.pilihan_kampus';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'tahun_ajaran_id',
        'no_simulasi',
        'no_pilihan',
        'kampus_id',
        'prodi_id',
        'status',
    ];

    protected $casts = [
        'no_simulasi' => 'integer',
        'no_pilihan'  => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function kampus(): BelongsTo
    {
        return $this->belongsTo(MasterKampus::class, 'kampus_id');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(MasterKampusProdi::class, 'prodi_id');
    }
}
