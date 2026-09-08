<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Entities\Tenant;
use Illuminate\Support\Facades\Storage;

class SekolahIdentitasController extends Controller
{
    public function show(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));

        $selectedTenantId = null;
        if ($isSuperAdmin && $request->filled('tenant_id')) {
            $selectedTenantId = $request->input('tenant_id');
        } else {
            $selectedTenantId = session('tenant_id') ?? $user?->tenant_id;
        }

        $identitas = null;
        if ($selectedTenantId) {
            $identitas = Tenant::find($selectedTenantId);
        }

        if (!$identitas) {
            $identitas = Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->first() 
                ?? Tenant::first() 
                ?? new Tenant([
                    'nama_sekolah' => 'SINTA SaaS Partner School',
                    'status' => 'aktif',
                ]);
        }

        $tenantsList = [];
        if ($isSuperAdmin) {
            $tenantsList = Tenant::select('id', 'nama_sekolah', 'npsn', 'status', 'bentuk_pendidikan')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->orderBy('nama_sekolah', 'asc')
                ->get();
        }

        // Return plain JSON ONLY for explicit non-Inertia API/Postman/Axios requests
        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'data' => $identitas,
                'tenantsList' => $tenantsList,
                'userRole' => $userRoleName
            ]);
        }

        return Inertia::render('Core/SekolahIdentitas/Show', [
            'identitas' => $identitas,
            'tenantsList' => $tenantsList,
            'userRole' => $userRoleName,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ]
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));

        $targetTenantId = ($isSuperAdmin && $request->filled('tenant_id'))
            ? $request->input('tenant_id')
            : (session('tenant_id') ?? $user?->tenant_id);

        $tenant = Tenant::find($targetTenantId);
        if (!$tenant) {
            $tenant = Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->first() ?? Tenant::first();
        }

        if (!$tenant) {
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['success' => false, 'message' => 'Tenant sekolah tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Tenant sekolah tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_sekolah'          => 'required|string|max:255',
            'npsn'                  => 'required|string|max:20',
            'bentuk_pendidikan'     => 'nullable|string|max:50',
            'status_sekolah'        => 'nullable|string|max:50',
            'kurikulum_terapan'     => 'nullable|string|max:100',
            'akreditasi'            => 'nullable|string|max:100',
            'alamat'                => 'nullable|string',
            'rt_rw'                 => 'nullable|string|max:20',
            'kode_pos'              => 'nullable|string|max:10',
            'kelurahan'             => 'nullable|string|max:100',
            'kecamatan'             => 'nullable|string|max:100',
            'kabupaten_kota'        => 'nullable|string|max:100',
            'provinsi'              => 'nullable|string|max:100',
            'telepon'               => 'nullable|string|max:50',
            'email'                 => 'nullable|email|max:100',
            'website'               => 'nullable|string|max:150',
            'nama_kepsek'           => 'nullable|string|max:255',
            'pangkat_kepsek'        => 'nullable|string|max:100',
            'nip_kepsek'            => 'nullable|string|max:50',
            'nama_operator'         => 'nullable|string|max:255',
            'email_operator'        => 'nullable|email|max:100',
            'subdomain'             => 'nullable|string|max:100',
            'logo'                  => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'sertifikat_akreditasi' => 'nullable|file|mimes:pdf,jpeg,png,jpg,webp|max:5120',
        ]);

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $validated['logo'] = $request->file('logo')->store('tenant_media/logos', 'public');
        } else {
            unset($validated['logo']);
        }

        // Handle Sertifikat Akreditasi Upload
        if ($request->hasFile('sertifikat_akreditasi')) {
            if ($tenant->sertifikat_akreditasi && Storage::disk('public')->exists($tenant->sertifikat_akreditasi)) {
                Storage::disk('public')->delete($tenant->sertifikat_akreditasi);
            }
            $validated['sertifikat_akreditasi'] = $request->file('sertifikat_akreditasi')->store('tenant_media/certificates', 'public');
        } else {
            unset($validated['sertifikat_akreditasi']);
        }

        // Handle delete flags if user cleared file
        if ($request->input('delete_logo') == '1') {
            if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $validated['logo'] = null;
        }

        if ($request->input('delete_sertifikat') == '1') {
            if ($tenant->sertifikat_akreditasi && Storage::disk('public')->exists($tenant->sertifikat_akreditasi)) {
                Storage::disk('public')->delete($tenant->sertifikat_akreditasi);
            }
            $validated['sertifikat_akreditasi'] = null;
        }

        $tenant->update($validated);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => 'Identitas dan profil sekolah berhasil diperbarui.',
                'data' => $tenant->fresh(),
            ]);
        }

        return back()->with('success', 'Identitas dan profil sekolah berhasil diperbarui.');
    }
}
