<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SekolahIdentitas extends BaseTenantModel
{
    protected $table = 'core.tenants';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_sekolah',
        'npsn',
        'nss',
        'jenjang',
        'status_sekolah',
        'akreditasi',
        'kurikulum',
        'alamat',
        'kode_pos',
        'kelurahan',
        'kecamatan',
        'kabupaten_kota',
        'provinsi',
        'nomor_telepon',
        'email',
        'website',
        'nama_kepala_sekolah',
        'nip_kepala_sekolah',
        'ttd_kepala_sekolah_url',
        'stempel_sekolah_url',
        'logo_url',
        'header_surat_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
