<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class Buku extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_bibliografi';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_bibliografi',
        'kode_buku',
        'isbn',
        'judul_buku',
        'pengarang',
        'penerbit',
        'kota_terbit',
        'tahun_terbit',
        'halaman',
        'dimensi',
        'bahasa',
        'nomor_klasifikasi_ddc',
        'nomor_panggil',
        'subjek',
        'sinopsis',
        'kategori',
        'lokasi_rak',
        'jumlah_eksemplar',
        'jumlah_tersedia',
        'cover_url',
        'ebook_url',
        'is_ebook',
        'status_opac',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tahun_terbit'     => 'integer',
        'halaman'          => 'integer',
        'jumlah_eksemplar' => 'integer',
        'jumlah_tersedia'  => 'integer',
        'is_ebook'         => 'boolean',
        'status_opac'      => 'boolean',
        'is_active'        => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function eksemplar(): HasMany
    {
        return $this->hasMany(Eksemplar::class, 'bibliografi_id');
    }

    public function sirkulasi(): HasMany
    {
        return $this->hasMany(Sirkulasi::class, 'buku_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
