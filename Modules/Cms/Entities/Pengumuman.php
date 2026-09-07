<?php

namespace Modules\Cms\Entities;

use Modules\Core\Entities\BaseTenantModel;

class Pengumuman extends BaseTenantModel
{
    protected $table = 'cms.pengumuman';

    protected $fillable = [
        'id',
        'tenant_id',
        'judul',
        'slug',
        'kategori', // 'Akademik', 'Kesiswaan', 'Umum', 'Libur'
        'konten',
        'target_audiens', // 'Semua', 'Siswa', 'Guru', 'Orang Tua'
        'tanggal_publikasi',
        'file_lampiran_url',
        'is_published',
    ];

    protected $casts = [
        'tanggal_publikasi' => 'datetime',
        'is_published'      => 'boolean',
    ];
}
