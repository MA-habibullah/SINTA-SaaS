<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveiGuru extends BaseTenantModel
{
    protected $table = 'kepegawaian.survei_guru';

    protected $fillable = [
        'id',
        'tenant_id',
        'judul_survei',
        'deskripsi',
        'sasaran_survei',
        'tahun_ajaran',
        'semester',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'is_active'       => 'boolean',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function respons(): HasMany
    {
        return $this->hasMany(SurveiGuruRespon::class, 'survei_id');
    }

    public function pertanyaans(): HasMany
    {
        return $this->hasMany(SurveiGuruPertanyaan::class, 'survei_id')->orderBy('nomor_urut', 'asc');
    }
}
