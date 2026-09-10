<?php

namespace Modules\Cms\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Core\Entities\User;

class Pengumuman extends BaseTenantModel
{
    protected $table = 'sistem.pengumuman';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'judul',
        'deskripsi',
        'kategori_id',
        'visibilitas',   // 'public', 'guru', 'siswa', 'orang_tua'
        'target_roles',  // JSON array of roles
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'target_roles' => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriPengumuman::class, 'kategori_id');
    }

    public function penulis()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
