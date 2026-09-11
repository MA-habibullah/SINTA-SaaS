<?php

namespace Modules\Kesiswaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalEkskul extends BaseTenantModel
{
    protected $table = 'kesiswaan.jurnal_ekskul';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'ekskul_id',
        'pembina_id',
        'tahun_ajaran_id',
        'semester',
        'tanggal_kegiatan',
        'jam_mulai',
        'jam_selesai',
        'materi_kegiatan',
        'lokasi',
        'jumlah_hadir',
        'jumlah_absen',
        'foto_kegiatan',
        'catatan_evaluasi',
        'is_active',
    ];

    protected $casts = [
        'tanggal_kegiatan' => 'date',
        'jumlah_hadir'     => 'integer',
        'jumlah_absen'     => 'integer',
        'is_active'        => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function ekskul(): BelongsTo
    {
        return $this->belongsTo(MasterEkskul::class, 'ekskul_id');
    }

    public function pembina(): BelongsTo
    {
        return $this->belongsTo(PembinaEkskul::class, 'pembina_id');
    }
}
