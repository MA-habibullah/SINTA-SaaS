<?php

namespace Modules\Kesiswaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Entities\User;

class PembinaEkskul extends BaseTenantModel
{
    protected $table = 'kesiswaan.data_pembina';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_pembina',
        'nama_data_pembina',
        'guru_id',
        'nip',
        'jenis_kelamin',
        'no_hp',
        'email',
        'kategori_pembina',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function ekskul(): HasMany
    {
        return $this->hasMany(MasterEkskul::class, 'pembina_id');
    }
}
