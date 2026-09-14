<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends BaseTenantModel
{
    protected $table = 'core.tickets';

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'category_id',
        'nomor_tiket',
        'judul',
        'deskripsi',
        'urgensi',
        'status',
        'lampiran',
        'user_agent',
        'last_url',
        'sla_deadline',
        'user_unread',
        'admin_unread',
        'is_active',
    ];

    protected $casts = [
        'category_id'  => 'integer',
        'sla_deadline' => 'datetime',
        'user_unread'  => 'boolean',
        'admin_unread' => 'boolean',
        'is_active'    => 'boolean',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'ticket_id', 'id')->orderBy('created_at', 'asc');
    }

    /**
     * Hitung apakah tiket melebihi batas SLA
     */
    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['Selesai', 'Batal'])) {
            return false;
        }

        if (!$this->sla_deadline) {
            return false;
        }

        return now()->isAfter($this->sla_deadline);
    }
}
