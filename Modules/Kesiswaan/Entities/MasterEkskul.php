<?php

namespace Modules\Kesiswaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterEkskul extends BaseTenantModel
{
    use SoftDeletes;

    protected $table = 'kesiswaan.master_ekskul';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_ekskul',
        'nama_master_ekskul',
        'kategori',
        'deskripsi',
        'pembina_id',
        'nama_pembina',
        'hari_latihan',
        'jam_mulai',
        'jam_selesai',
        'tempat_latihan',
        'kuota_maksimal',
        'is_active',
    ];

    protected $casts = [
        'kuota_maksimal' => 'integer',
        'is_active'      => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    public function pembina(): BelongsTo
    {
        return $this->belongsTo(PembinaEkskul::class, 'pembina_id');
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(AnggotaEkskul::class, 'ekskul_id');
    }

    public function jurnal(): HasMany
    {
        return $this->hasMany(JurnalEkskul::class, 'ekskul_id');
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(NilaiEkskul::class, 'ekskul_id');
    }
}
