<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\User;

class PembinaanSupervisi extends BaseTenantModel
{
    protected $table = 'kepegawaian.supervisi_pembinaan';

    protected $fillable = [
        'id',
        'tenant_id',
        'guru_id',
        'nama_guru',
        'nip_guru',
        'mata_pelajaran',
        'kelas_rombel',
        'tanggal_supervisi',
        'tahun_ajaran',
        'semester',
        'jenis_supervisi',
        'skor_pedagogik',
        'skor_profesional',
        'skor_kepribadian',
        'skor_sosial',
        'skor_total',
        'predikat',
        'catatan_observasi',
        'rekomendasi_pembinaan',
        'tindak_lanjut',
        'status_pembinaan',
        'dokumen_lampiran',
        'is_active',
    ];

    protected $casts = [
        'tanggal_supervisi' => 'date',
        'skor_pedagogik'    => 'integer',
        'skor_profesional'  => 'integer',
        'skor_kepribadian'  => 'integer',
        'skor_sosial'       => 'integer',
        'skor_total'        => 'decimal:2',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
