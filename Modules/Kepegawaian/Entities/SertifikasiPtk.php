<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SertifikasiPtk extends BaseTenantModel
{
    protected $table = 'kepegawaian.sertifikasi_ptk';

    protected $fillable = [
        'id',
        'tenant_id',
        'ptk_id',
        'jenis_sertifikasi', // 'Pendidik', 'Profesi', 'Keahlian'
        'nomor_peserta',
        'nomor_sertifikat',
        'tahun_sertifikasi',
        'bidang_studi',
        'lembaga_penerbit',
        'is_active',
    ];

    protected $casts = [
        'tahun_sertifikasi' => 'integer',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function gtk(): BelongsTo
    {
        return $this->belongsTo(Gtk::class, 'ptk_id', 'id');
    }
}
