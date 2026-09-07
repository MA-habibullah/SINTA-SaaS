<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buku extends BaseTenantModel
{
    protected $table = 'perpustakaan.buku';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_buku',
        'isbn',
        'judul_buku',
        'pengarang',
        'penerbit',
        'tahun_terbit',
        'nomor_klasifikasi_ddc', // Decimal Dewey Classification (e.g., 004, 510, 621)
        'lokasi_rak',
        'jumlah_eksemplar',
        'jumlah_tersedia',
        'sinopsis',
        'cover_url',
        'is_active',
    ];

    protected $casts = [
        'tahun_terbit'     => 'integer',
        'jumlah_eksemplar' => 'integer',
        'jumlah_tersedia'  => 'integer',
        'is_active'        => 'boolean',
    ];

    public function sirkulasi(): HasMany
    {
        return $this->hasMany(Sirkulasi::class, 'buku_id', 'id');
    }
}
