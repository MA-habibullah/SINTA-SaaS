<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Menu extends Model
{
    use HasUuids;

    protected $table = 'core.menus';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'parent_id',
        'nama_menu',
        'url',
        'icon',
        'urutan',
        'is_active',
        'badge',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan'    => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('urutan', 'asc');
    }
}
