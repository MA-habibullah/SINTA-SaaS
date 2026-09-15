<?php

namespace Modules\Sarpras\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ruangan extends BaseTenantModel
{
    protected $table = 'sarpras.ruangan';

    protected $fillable = [
        'id',
        'tenant_id',
        'bangunan_id',
        'kode_ruangan',
        'nama_ruangan',
        'fungsi_ruangan', // 'Kelas', 'Lab Komputer', 'Lab IPA', 'Perpustakaan', 'Kantor', 'Gudang'
        'kapasitas',
        'lantai',
        'luas_m2',
        'kondisi',        // 'Baik', 'Rusak Ringan', 'Rusak Berat'
        'penanggung_jawab',
        'foto_url',
        'keterangan',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'lantai'    => 'integer',
        'luas_m2'   => 'decimal:2',
    ];

    public function bangunan(): BelongsTo
    {
        return $this->belongsTo(Bangunan::class, 'bangunan_id', 'id');
    }

    public function inventarisBarang(): HasMany
    {
        return $this->hasMany(InventarisAset::class, 'ruangan_id', 'id');
    }
}
