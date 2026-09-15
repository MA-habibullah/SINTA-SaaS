<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gtk extends BaseTenantModel
{
    protected $table = 'kepegawaian.ptk_identitas';

    protected $fillable = [
        'id',
        'tenant_id',
        'nip',
        'nuptk',
        'nik',
        'nama_lengkap',
        'gelar_depan',
        'gelar_belakang',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_ptk', // 'Guru Mapel', 'Guru BK', 'Guru Kelas', 'Tenaga Administrasi', 'Kepala Sekolah', 'Laboran', 'Tenaga Kebersihan/Keamanan'
        'status_kepegawaian', // 'PNS', 'PPPK', 'GTY', 'GTT', 'PTY', 'PTT'
        'jabatan',
        'pendidikan_terakhir',
        'jurusan_pendidikan',
        'no_hp',
        'email',
        'alamat_tinggal',
        'tmt_kerja',
        'foto_url',
        'is_active',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tmt_kerja'     => 'date',
        'is_active'     => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function riwayatKepangkatan(): HasMany
    {
        return $this->hasMany(RiwayatKepangkatan::class, 'ptk_id', 'id');
    }

    public function sertifikasi(): HasMany
    {
        return $this->hasMany(SertifikasiPtk::class, 'ptk_id', 'id');
    }
}
