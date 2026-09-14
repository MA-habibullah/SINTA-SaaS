<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\User;

class SurveiGuruRespon extends BaseTenantModel
{
    protected $table = 'kepegawaian.survei_guru_respon';

    protected $fillable = [
        'id',
        'tenant_id',
        'survei_id',
        'guru_id',
        'nama_guru',
        'kelas_id',
        'nama_kelas',
        'mapel_id',
        'nama_mapel',
        'is_anonim',
        'penilai_id',
        'nama_penilai',
        'peran_penilai',
        'skor_detail_json',
        'skor_pedagogik',
        'skor_kedisiplinan',
        'skor_komunikasi',
        'skor_penguasaan_materi',
        'skor_sosial_kepribadian',
        'rata_rata_skor',
        'predikat_kategori',
        'umpan_balik_positif',
        'area_pengembangan',
    ];

    protected $casts = [
        'is_anonim'               => 'boolean',
        'skor_detail_json'        => 'array',
        'skor_pedagogik'          => 'decimal:2',
        'skor_kedisiplinan'       => 'decimal:2',
        'skor_komunikasi'         => 'decimal:2',
        'skor_penguasaan_materi'  => 'decimal:2',
        'skor_sosial_kepribadian' => 'decimal:2',
        'rata_rata_skor'          => 'decimal:2',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
    ];

    public function survei(): BelongsTo
    {
        return $this->belongsTo(SurveiGuru::class, 'survei_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }
}
