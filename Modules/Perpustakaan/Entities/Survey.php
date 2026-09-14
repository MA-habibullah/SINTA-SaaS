<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_survey';

    protected $fillable = [
        'id',
        'tenant_id',
        'judul_survey',
        'deskripsi',
        'tanggal_buka',
        'tanggal_tutup',
        'is_active',
    ];

    protected $casts = [
        'tanggal_buka'  => 'date',
        'tanggal_tutup' => 'date',
        'is_active'     => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function pertanyaans(): HasMany
    {
        return $this->hasMany(SurveyPertanyaan::class, 'survey_id')->orderBy('urutan', 'asc');
    }

    public function respons(): HasMany
    {
        return $this->hasMany(SurveyRespon::class, 'survey_id');
    }
}
