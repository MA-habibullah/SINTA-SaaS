<?php

namespace Modules\Core\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HandleInertiaRequests extends Middleware
{
    /**
     * Root view template yang digunakan Inertia.
     */
    protected $rootView = 'app';

    /**
     * Menentukan versi aset untuk cache busting.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Data yang dibagikan secara global ke seluruh komponen Inertia Vue 3.
     */
    public function share(Request $request): array
    {
        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenantInfo = null;

        if ($tenantId) {
            $tenantInfo = DB::table('core.tenants')
                ->where('id', $tenantId)
                ->first(['id', 'nama_sekolah', 'npsn', 'subdomain', 'logo']);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'           => $user->id,
                    'username'     => $user->username,
                    'nama_lengkap' => $user->nama_lengkap,
                    'email'        => $user->email,
                    'role'         => $user->role?->nama_role ?? 'user',
                    'roles'        => $user->role ? [$user->role->nama_role] : [],
                    'avatar'       => $user->foto_url ?? null,
                ] : null,
            ],
            'tenant' => $tenantInfo,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info'    => fn () => $request->session()->get('info'),
            ],
            'csrf_token' => csrf_token(),
        ];
    }
}
