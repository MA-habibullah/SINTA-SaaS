<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class PengaturanPerpus extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_pengaturan';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_pengaturan',
        'nama_perpustakaan',
        'kepala_perpustakaan',
        'nip_kepala',
        'tarif_denda_per_hari',
        'max_hari_pinjam_siswa',
        'max_hari_pinjam_guru',
        'max_buku_pinjam_siswa',
        'max_buku_pinjam_guru',
        'opac_aktif',
        'syarat_bebas_pustaka',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tarif_denda_per_hari'  => 'decimal:2',
        'max_hari_pinjam_siswa' => 'integer',
        'max_hari_pinjam_guru'  => 'integer',
        'max_buku_pinjam_siswa' => 'integer',
        'max_buku_pinjam_guru'  => 'integer',
        'opac_aktif'            => 'boolean',
        'is_active'             => 'boolean',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
