<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Core\Entities\User;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\MataPelajaran;
use Modules\Akademik\Entities\PemetaanMapel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalMengajar extends BaseTenantModel
{
    protected $table = 'absensi.jurnal_mengajar';

    protected $fillable = [
        'id',
        'tenant_id',
        'jadwal_id',
        'guru_id',
        'nama_guru',
        'kelas_id',
        'nama_kelas',
        'mapel_id',
        'nama_mapel',
        'tahun_ajaran',
        'semester',
        'tanggal',
        'jam_ke',
        'jam_mulai',
        'jam_selesai',
        'capaian_pembelajaran',
        'aktivitas_pembelajaran',
        'kendala_pembelajaran',
        'foto_kegiatan_url',
        'foto_ukuran_kb',
        'jumlah_hadir',
        'jumlah_sakit',
        'jumlah_izin',
        'jumlah_alpa',
        'jumlah_terlambat',
        'status_kbm',
        'catatan_supervisor',
        'is_verified',
    ];

    protected $casts = [
        'tanggal'          => 'date',
        'foto_ukuran_kb'   => 'float',
        'jumlah_hadir'     => 'integer',
        'jumlah_sakit'     => 'integer',
        'jumlah_izin'      => 'integer',
        'jumlah_alpa'      => 'integer',
        'jumlah_terlambat' => 'integer',
        'is_verified'      => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id', 'id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id', 'id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(PemetaanMapel::class, 'jadwal_id', 'id');
    }

    public function presensiKbm(): HasMany
    {
        return $this->hasMany(PresensiSiswaKbm::class, 'jurnal_id', 'id');
    }
}
