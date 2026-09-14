<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    protected $table = 'core.ticket_categories';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'sla_hours',
        'is_active',
    ];

    protected $casts = [
        'sla_hours' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id', 'id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(TicketFaq::class, 'category_id', 'id');
    }
}
