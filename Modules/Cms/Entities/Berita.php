<?php

namespace Modules\Cms\Entities;

use Modules\Core\Entities\BaseTenantModel;

class Berita extends BaseTenantModel
{
    protected $table = 'cms.cms_posts';

    protected $fillable = [
        'id',
        'tenant_id',
        'judul_berita',
        'slug',
        'ringkasan',
        'konten_lengkap',
        'gambar_utama_url',
        'penulis_nama',
        'kategori',
        'jumlah_pembaca',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'jumlah_pembaca' => 'integer',
        'is_published'   => 'boolean',
        'published_at'   => 'datetime',
    ];
}
