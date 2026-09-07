<?php

namespace Modules\Core\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasUuids, HasRoles, Notifiable, SoftDeletes;

    protected $table = 'core.users';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guard_name = 'web';

    protected $fillable = [
        'id',
        'tenant_id',
        'username',
        'email',
        'password',
        'nama_lengkap',
        'role',
        'no_hp',
        'foto_url',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'last_login_at'           => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'email_verified_at'       => 'datetime',
        'password'                => 'hashed',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' || $this->hasRole('super_admin');
    }
}
