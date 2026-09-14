<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyRespon extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_survey_respon';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'survey_id',
        'pertanyaan_id',
        'anggota_id',
        'nama_responden',
        'skor_nilai',
        'jawaban_teks',
        'created_at',
    ];

    protected $casts = [
        'skor_nilai' => 'integer',
        'created_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(SurveyPertanyaan::class, 'pertanyaan_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }
}
