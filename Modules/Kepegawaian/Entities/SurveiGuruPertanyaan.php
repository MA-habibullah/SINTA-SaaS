<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveiGuruPertanyaan extends BaseTenantModel
{
    protected $table = 'kepegawaian.survei_guru_pertanyaan';

    protected $fillable = [
        'id',
        'tenant_id',
        'survei_id',
        'dimensi', // 'Pedagogik', 'Kepribadian & Sosial'
        'nomor_urut',
        'pertanyaan',
        'tipe_skala', // 'likert_4', 'teks_terbuka'
        'is_active',
    ];

    protected $casts = [
        'nomor_urut' => 'integer',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function survei(): BelongsTo
    {
        return $this->belongsTo(SurveiGuru::class, 'survei_id');
    }
}
