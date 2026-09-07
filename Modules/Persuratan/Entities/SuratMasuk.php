<?php

namespace Modules\Persuratan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratMasuk extends BaseTenantModel
{
    protected $table = 'persuratan.surat_masuk';

    protected $fillable = [
        'id',
        'tenant_id',
        'nomor_agenda',
        'nomor_surat_asal',
        'tanggal_surat',
        'tanggal_diterima',
        'pengirim',
        'perihal',
        'kategori_surat', // 'Dinas', 'Undangan', 'Pemberitahuan', 'Lainnya'
        'sifat_surat', // 'Biasa', 'Penting', 'Rahasia', 'Segera'
        'file_surat_url',
        'status_disposisi', // 'Belum Disposisi', 'Sudah Disposisi', 'Selesai'
    ];

    protected $casts = [
        'tanggal_surat'    => 'date',
        'tanggal_diterima' => 'date',
    ];

    public function disposisi(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'surat_masuk_id', 'id');
    }
}
