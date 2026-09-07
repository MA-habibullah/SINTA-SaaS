<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;

class Gtk extends BaseTenantModel
{
    protected $table = 'kepegawaian.gtk';

    protected $fillable = [
        'id',
        'tenant_id',
        'nip',
        'nuptk',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_ptk', // 'Guru Mapel', 'Guru BK', 'Guru Kelas', 'Tenaga Administrasi', 'Kepala Sekolah', 'Laboran'
        'status_kepegawaian', // 'PNS', 'PPPK', 'GTY', 'GTT', 'PTY', 'PTT'
        'pendidikan_terakhir',
        'jurusan_pendidikan',
        'no_hp',
        'email',
        'alamat_tinggal',
        'is_active',
        'foto_url',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'is_active'     => 'boolean',
    ];
}
