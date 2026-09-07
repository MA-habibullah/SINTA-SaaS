<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Entities\User;
use Modules\Core\Entities\Tenant;

class UserController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $tenantId = session('tenant_id');

        $query = User::query()
            ->when($tenantId && !auth()->user()?->isSuperAdmin(), function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('username', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->role, function ($q, $role) {
                $q->where('role', $role);
            })
            ->orderBy('created_at', 'desc');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $query->paginate(15)]);
        }

        return Inertia::render('Core/User/Index', [
            'users' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'    => 'nullable|uuid',
            'username'     => 'required|string|max:50|unique:core.users,username',
            'email'        => 'required|email|max:100|unique:core.users,email',
            'nama_lengkap' => 'required|string|max:255',
            'password'     => 'required|string|min:8',
            'role'         => 'required|string',
            'no_hp'        => 'nullable|string|max:20',
            'is_active'    => 'boolean',
        ]);

        if (empty($validated['tenant_id'])) {
            $validated['tenant_id'] = session('tenant_id');
        }

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        if (!empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengguna berhasil ditambahkan.', 'data' => $user], 201);
        }

        return back()->with('success', 'Pengguna berhasil didaftarkan.');
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email'        => 'required|email|max:100|unique:core.users,email,' . $id,
            'role'         => 'required|string',
            'no_hp'        => 'nullable|string|max:20',
            'is_active'    => 'boolean',
            'password'     => 'nullable|string|min:8',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if (!empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengguna berhasil diperbarui.']);
        }

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengguna dinonaktifkan.']);
        }

        return back()->with('success', 'Pengguna berhasil dinonaktifkan.');
    }
}
