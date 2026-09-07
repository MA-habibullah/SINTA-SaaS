<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Entities\User;
use Modules\Core\Entities\Tenant;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login Inertia
     */
    public function showLoginForm(): InertiaResponse
    {
        return Inertia::render('Auth/Login', [
            'tenants' => Tenant::where('is_active', true)->select('id', 'nama_sekolah', 'npsn', 'logo_url')->get(),
        ]);
    }

    /**
     * Proses autentikasi pengguna
     */
    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'tenant_id' => ['nullable', 'string'],
        ]);

        $query = User::where(function ($q) use ($credentials) {
            $q->where('username', $credentials['username'])
              ->orWhere('email', $credentials['username']);
        });

        if (!empty($credentials['tenant_id'])) {
            $query->where('tenant_id', $credentials['tenant_id']);
        }

        $user = $query->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'Kombinasi nama pengguna atau kata sandi tidak cocok.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => 'Akun Anda sedang dinonaktifkan oleh administrator.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $user->tenant_id);

        // Update tracking login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil.',
                'user'    => $user,
            ]);
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Ganti sekolah aktif (Super Admin Switch Tenant)
     */
    public function switchTenant(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['tenant_id' => 'required|uuid|exists:core.tenants,id']);

        if (!Auth::user()?->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang diizinkan berpindah ruang lingkup tenant.');
        }

        $request->session()->put('tenant_id', $request->input('tenant_id'));

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berhasil beralih sekolah.']);
        }

        return back()->with('success', 'Sekolah aktif berhasil diubah.');
    }

    /**
     * Logout pengguna
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berhasil keluar.']);
        }

        return redirect('/login');
    }
}
