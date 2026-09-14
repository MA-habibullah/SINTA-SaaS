<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    use HasUuids;

    protected $table = 'core.ticket_replies';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // created_at only in schema

    protected $fillable = [
        'id',
        'ticket_id',
        'user_id',
        'is_superadmin',
        'pesan',
        'lampiran',
        'created_at',
    ];

    protected $casts = [
        'is_superadmin' => 'boolean',
        'created_at'    => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
