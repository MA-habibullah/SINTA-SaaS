<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureRequest extends BaseTenantModel
{
    protected $table = 'core.feature_requests';

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'judul_fitur',
        'modul_terkait',
        'urgensi_bisnis',
        'deskripsi_kebutuhan',
        'ekspektasi_solusi',
        'status',
        'estimasi_rilis',
        'catatan_pengembang',
        'votes_count',
        'lampiran',
        'is_active',
    ];

    protected $casts = [
        'votes_count' => 'integer',
        'is_active'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeatureRequestVote::class, 'feature_request_id', 'id');
    }

    public function isVotedBy(?string $userId): bool
    {
        if (!$userId) return false;
        return $this->votes()->where('user_id', $userId)->exists();
    }
}
