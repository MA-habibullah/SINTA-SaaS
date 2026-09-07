<?php

namespace Modules\Smk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MitraDudi extends BaseTenantModel
{
    protected $table = 'smk.mitra_dudi';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perusahaan',
        'bidang_usaha',
        'alamat_perusahaan',
        'kota',
        'contact_person_nama',
        'contact_person_jabatan',
        'contact_person_hp',
        'email_perusahaan',
        'nomor_mou_kerjasama',
        'tanggal_mulai_mou',
        'tanggal_akhir_mou',
        'kuota_penerimaan_pkl',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai_mou'    => 'date',
        'tanggal_akhir_mou'    => 'date',
        'kuota_penerimaan_pkl' => 'integer',
        'is_active'            => 'boolean',
    ];

    public function pkl(): HasMany
    {
        return $this->hasMany(PrakerinPkl::class, 'mitra_dudi_id', 'id');
    }
}
