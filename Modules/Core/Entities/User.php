<?php

namespace Modules\Core\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasUuids, Notifiable;

    protected $table = 'core.users';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'tenant_id',
        'role_id',
        'nama_lengkap',
        'username',
        'email',
        'password_hash',
        'is_active',
        'nip',
        'nuptk',
        'jenis_gtk',
        'jabatan_struktural',
        'status_kepegawaian',
        'jam_mengajar',
        'status_sertifikasi',
        'no_hp',
        'alamat',
        'jenis_kelamin',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'status_sertifikasi' => 'boolean',
        'jam_mengajar'       => 'integer',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    /**
     * Get password attribute for Laravel Auth
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'core.user_roles', 'user_id', 'role_id');
    }

    public function isSuperAdmin(): bool
    {
        if ($this->tenant_id === '00000000-0000-0000-0000-000000000000') {
            return true;
        }
        if (is_object($this->role) && isset($this->role->nama_role) && $this->role->nama_role === 'super_admin') {
            return true;
        }
        if (is_string($this->role) && $this->role === 'super_admin') {
            return true;
        }
        return $this->roles()->where('nama_role', 'super_admin')->exists();
    }

    public function hasRole(string $roleName): bool
    {
        if ($roleName === 'super_admin' && $this->tenant_id === '00000000-0000-0000-0000-000000000000') {
            return true;
        }
        if (is_object($this->role) && isset($this->role->nama_role) && $this->role->nama_role === $roleName) {
            return true;
        }
        if (is_string($this->role) && $this->role === $roleName) {
            return true;
        }
        return $this->roles()->where('nama_role', $roleName)->exists();
    }

    public function getRoleNames(): \Illuminate\Support\Collection
    {
        $names = collect();
        if (is_object($this->role) && isset($this->role->nama_role)) {
            $names->push($this->role->nama_role);
        } elseif (is_string($this->role)) {
            $names->push($this->role);
        }
        if ($this->tenant_id === '00000000-0000-0000-0000-000000000000') {
            $names->push('super_admin');
        }
        $relNames = $this->roles()->pluck('nama_role');
        return $names->merge($relNames)->unique()->values();
    }
}

