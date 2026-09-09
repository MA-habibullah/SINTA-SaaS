<?php

namespace Modules\Pdss\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterKampusProdi extends BaseTenantModel
{
    protected $table = 'pdss.master_kampus_prodi';

    protected $fillable = [
        'id',
        'tenant_id',
        'kampus_id',
        'nama_master_kampus_prodi',
        'nama_prodi',
        'program_studi',
        'id_prodi',
        'kode_prodi',
        'id_ptn',
        'fakultas',
        'jenjang',
        'daya_tampung_sekarang',
        'jenis_portofolio',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'daya_tampung_sekarang' => 'integer',
        'is_active'              => 'boolean',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function kampus(): BelongsTo
    {
        return $this->belongsTo(MasterKampus::class, 'kampus_id');
    }
}
