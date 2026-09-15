<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatKepangkatan extends BaseTenantModel
{
    protected $table = 'kepegawaian.riwayat_kepangkatan';

    protected $fillable = [
        'id',
        'tenant_id',
        'ptk_id',
        'golongan_pangkat',
        'nomor_sk',
        'tanggal_sk',
        'tmt_pangkat',
        'pejabat_penetap',
        'gaji_pokok',
        'is_terakhir',
        'is_active',
    ];

    protected $casts = [
        'tanggal_sk'  => 'date',
        'tmt_pangkat' => 'date',
        'gaji_pokok'  => 'decimal:2',
        'is_terakhir' => 'boolean',
        'is_active'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function gtk(): BelongsTo
    {
        return $this->belongsTo(Gtk::class, 'ptk_id', 'id');
    }
}
