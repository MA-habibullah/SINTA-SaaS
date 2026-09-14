<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LokerLog extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_loker_log';

    protected $fillable = [
        'id',
        'tenant_id',
        'loker_id',
        'anggota_id',
        'nama_peminjam',
        'identitas_jaminan', // KTA, Kartu Pelajar, KTP
        'waktu_pinjam',
        'waktu_kembali',
        'status_pinjam', // dipinjam, kembali, pelanggaran
        'denda',
        'petugas_id',
        'catatan',
    ];

    protected $casts = [
        'denda'         => 'decimal:2',
        'waktu_pinjam'  => 'datetime',
        'waktu_kembali' => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function loker(): BelongsTo
    {
        return $this->belongsTo(Loker::class, 'loker_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }
}
