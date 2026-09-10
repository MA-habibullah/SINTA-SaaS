<?php

namespace Modules\Pdss\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class KampusProdiRiwayat extends Model
{
    use HasUuids;

    protected $table = 'pdss.kampus_prodi_riwayat';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'prodi_id',
        'id_prodi',
        'kode_prodi',
        'tahun',
        'daya_tampung',
        'jumlah_pendaftar',
        'diterima',
        'keketatan',
    ];

    protected $casts = [
        'tahun'            => 'integer',
        'daya_tampung'     => 'integer',
        'jumlah_pendaftar' => 'integer',
        'diterima'         => 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public static function withoutTenant(): Builder
    {
        return static::query();
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(MasterKampusProdi::class, 'prodi_id');
    }
}
