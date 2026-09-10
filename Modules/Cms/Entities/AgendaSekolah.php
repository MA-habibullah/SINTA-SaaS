<?php

namespace Modules\Cms\Entities;

use Modules\Core\Entities\BaseTenantModel;

class AgendaSekolah extends BaseTenantModel
{
    protected $table = 'sistem.agenda_sekolah';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_agenda_sekolah',
        'kategori',
        'deskripsi',  // JSON string: {isi, tanggal_mulai, tanggal_selesai, waktu_mulai, waktu_selesai, lokasi, penanggung_jawab, visibilitas, target_roles}
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get parsed deskripsi JSON as array
     */
    public function getDetailAttribute(): array
    {
        if (empty($this->deskripsi)) return [];
        $decoded = json_decode($this->deskripsi, true);
        return is_array($decoded) ? $decoded : [];
    }
}
