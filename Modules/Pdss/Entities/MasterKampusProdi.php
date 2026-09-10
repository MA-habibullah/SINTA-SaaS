<?php

namespace Modules\Pdss\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class MasterKampusProdi extends Model
{
    use HasUuids;

    protected $table = 'pdss.master_kampus_prodi';

    protected $keyType = 'string';
    public $incrementing = false;

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

    public static function withoutTenant(): Builder
    {
        return static::query();
    }

    public function kampus(): BelongsTo
    {
        return $this->belongsTo(MasterKampus::class, 'kampus_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(KampusProdiRiwayat::class, 'prodi_id')->orderBy('tahun', 'desc');
    }
}


