<?php

namespace Modules\Pdss\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengunduranDiri extends BaseTenantModel
{
    protected $table = 'pdss.pengunduran_diri';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'tahun_ajaran_id',
        'nomor_surat',
        'tanggal_surat',
        'alasan',
        'nama_file',
        'path_file',
        'ukuran_file',
        'mime_type',
        'status_verifikasi',
        'created_by',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'ukuran_file'   => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
