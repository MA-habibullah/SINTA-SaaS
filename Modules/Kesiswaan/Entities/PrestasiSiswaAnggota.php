<?php

namespace Modules\Kesiswaan\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Siswa\Entities\Siswa;

class PrestasiSiswaAnggota extends Model
{
    protected $table = 'kesiswaan.prestasi_siswa_anggota';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_prestasi',
        'id_siswa',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function prestasi(): BelongsTo
    {
        return $this->belongsTo(PrestasiSiswa::class, 'id_prestasi');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa');
    }
}
