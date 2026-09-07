<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Entities\Tenant;

class TenantManagementController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $query = Tenant::query()
            ->when($request->search, function ($q, $search) {
                $q->where('nama_sekolah', 'ILIKE', "%{$search}%")
                  ->orWhere('npsn', 'ILIKE', "%{$search}%");
            })
            ->when($request->paket, function ($q, $paket) {
                $q->where('paket_aktif', $paket);
            })
            ->orderBy('created_at', 'desc');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $query->paginate($request->input('per_page', 15)),
            ]);
        }

        return Inertia::render('Core/Tenant/Index', [
            'tenants' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'paket']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'npsn'             => 'required|string|max:20|unique:core.tenants,npsn',
            'nama_sekolah'     => 'required|string|max:255',
            'jenjang'          => 'required|string|in:SD,SMP,SMA,SMK',
            'status_sekolah'   => 'required|string|in:Negeri,Swasta',
            'alamat_jalan'     => 'nullable|string',
            'kabupaten_kota'   => 'nullable|string',
            'provinsi'         => 'nullable|string',
            'email'            => 'nullable|email',
            'nomor_telepon'    => 'nullable|string',
            'paket_aktif'      => 'required|string|in:Free,Starter,Pro,Enterprise',
            'storage_limit_mb' => 'required|integer|min:100',
            'is_active'        => 'boolean',
        ]);

        $tenant = Tenant::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tenant sekolah berhasil didaftarkan.', 'data' => $tenant], 201);
        }

        return redirect()->route('core.tenants.index')->with('success', 'Sekolah berhasil ditambahkan ke jaringan SINTA-SaaS.');
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'npsn'             => 'required|string|max:20|unique:core.tenants,npsn,' . $id,
            'nama_sekolah'     => 'required|string|max:255',
            'jenjang'          => 'required|string|in:SD,SMP,SMA,SMK',
            'status_sekolah'   => 'required|string|in:Negeri,Swasta',
            'alamat_jalan'     => 'nullable|string',
            'kabupaten_kota'   => 'nullable|string',
            'provinsi'         => 'nullable|string',
            'email'            => 'nullable|email',
            'nomor_telepon'    => 'nullable|string',
            'paket_aktif'      => 'required|string|in:Free,Starter,Pro,Enterprise',
            'storage_limit_mb' => 'required|integer|min:100',
            'is_active'        => 'boolean',
        ]);

        $tenant->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data sekolah berhasil diperbarui.', 'data' => $tenant]);
        }

        return back()->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse|JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tenant sekolah dinonaktifkan.']);
        }

        return back()->with('success', 'Tenant sekolah berhasil dinonaktifkan.');
    }
}
