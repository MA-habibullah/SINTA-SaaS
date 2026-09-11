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
        'enable_ppdb',
        'enable_perpustakaan',
        'enable_keuangan',
        'enable_pdss',
        'enable_smk',
        'enable_sarpras',
        'enable_persuratan',
        'trial_ends_at',
        'trial_duration_months',
        'subscription_type',
        'pic_nama',
        'pic_jabatan',
        'pic_telepon',
        'pic_email',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'cms_landing_enabled'   => 'boolean',
        'storage_limit_mb'      => 'integer',
        'max_siswa_limit'       => 'integer',
        'max_staff_limit'       => 'integer',
        'enable_bk'             => 'integer',
        'enable_tracer'         => 'integer',
        'enable_ppdb'           => 'integer',
        'enable_perpustakaan'   => 'integer',
        'enable_keuangan'       => 'integer',
        'enable_pdss'           => 'integer',
        'enable_smk'            => 'integer',
        'enable_sarpras'        => 'integer',
        'enable_persuratan'     => 'integer',
        'trial_duration_months' => 'integer',
        'trial_ends_at'         => 'datetime',
        'approved_at'           => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id', 'id');
    }

    public function isActive(): bool
    {
        return in_array(strtolower((string)$this->status), ['aktif', 'active'], true);
    }

    public function isPendingApproval(): bool
    {
        return in_array(strtolower((string)$this->status), ['pending', 'pending_approval', 'menunggu'], true);
    }

    public function isRejected(): bool
    {
        return in_array(strtolower((string)$this->status), ['rejected', 'ditolak'], true);
    }

    public function isTrialActive(): bool
    {
        if (!$this->trial_ends_at) return false;
        return $this->trial_ends_at->isFuture();
    }

    public function remainingTrialDays(): int
    {
        if (!$this->trial_ends_at) return 0;
        return (int) max(0, ceil(now()->floatDiffInDays($this->trial_ends_at, false)));
    }
}
