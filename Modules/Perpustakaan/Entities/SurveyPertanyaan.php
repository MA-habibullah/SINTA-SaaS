<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyPertanyaan extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_survey_pertanyaan';

    protected $fillable = [
        'id',
        'tenant_id',
        'survey_id',
        'urutan',
        'pertanyaan',
        'tipe_pertanyaan', // skala_likert, esai, pilihan_ganda
        'is_active',
    ];

    protected $casts = [
        'urutan'     => 'integer',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function respons(): HasMany
    {
        return $this->hasMany(SurveyRespon::class, 'pertanyaan_id');
    }
}
