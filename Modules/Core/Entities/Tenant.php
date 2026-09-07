<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $table = 'core.tenants';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'nama_sekolah',
        'npsn',
        'subdomain',
        'custom_domain',
        'cname_alias',
        'cms_landing_enabled',
        'cms_hero_title',
        'cms_hero_subtitle',
        'cms_theme_color',
        'sub_portal_login_siswa',
        'sub_portal_login_admin',
        'sub_portal_perpus',
        'sub_portal_pdss',
        'sub_portal_ppdb',
        'sub_portal_tracer',
        'status',
        'paket_aktif',
        'status_sinkronisasi',
        'logo',
        'sertifikat_akreditasi',
        'bentuk_pendidikan',
        'status_sekolah',
        'kurikulum_terapan',
        'akreditasi',
        'alamat',
        'rt_rw',
        'kode_pos',
        'kelurahan',
        'kecamatan',
        'kabupaten_kota',
        'provinsi',
        'telepon',
        'email',
        'website',
        'nama_kepsek',
        'pangkat_kepsek',
        'nip_kepsek',
        'nama_operator',
        'email_operator',
        'storage_limit_mb',
        'max_siswa_limit',
        'max_staff_limit',
        'enable_bk',
        'enable_tracer',
    ];

    protected $casts = [
        'cms_landing_enabled' => 'boolean',
        'storage_limit_mb'    => 'integer',
        'max_siswa_limit'     => 'integer',
        'max_staff_limit'     => 'integer',
        'enable_bk'           => 'integer',
        'enable_tracer'       => 'integer',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id', 'id');
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif' || $this->status === 'active';
    }
}
