<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Entities\SekolahIdentitas;

class SekolahIdentitasController extends Controller
{
    public function show(): InertiaResponse|JsonResponse
    {
        $tenantId = session('tenant_id');
        $identitas = SekolahIdentitas::firstOrCreate(
            ['tenant_id' => $tenantId],
            ['nama_sekolah' => 'SINTA SaaS Partner School']
        );

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'data' => $identitas]);
        }

        return Inertia::render('Core/SekolahIdentitas/Show', [
            'identitas' => $identitas,
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = session('tenant_id');
        $identitas = SekolahIdentitas::where('tenant_id', $tenantId)->firstOrFail();

        $validated = $request->validate([
            'nama_sekolah'        => 'required|string|max:255',
            'npsn'                => 'required|string|max:20',
            'nss'                 => 'nullable|string|max:30',
            'jenjang'             => 'required|string|in:SD,SMP,SMA,SMK',
            'status_sekolah'      => 'required|string|in:Negeri,Swasta',
            'akreditasi'          => 'nullable|string|max:10',
            'kurikulum'           => 'nullable|string|max:100',
            'alamat'              => 'nullable|string',
            'kode_pos'            => 'nullable|string|max:10',
            'kelurahan'           => 'nullable|string|max:100',
            'kecamatan'           => 'nullable|string|max:100',
            'kabupaten_kota'      => 'nullable|string|max:100',
            'provinsi'            => 'nullable|string|max:100',
            'nomor_telepon'       => 'nullable|string|max:30',
            'email'               => 'nullable|email|max:100',
            'website'             => 'nullable|string|max:150',
            'nama_kepala_sekolah' => 'nullable|string|max:255',
            'nip_kepala_sekolah'  => 'nullable|string|max:30',
        ]);

        $identitas->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Identitas sekolah berhasil disimpan.', 'data' => $identitas]);
        }

        return back()->with('success', 'Profil dan identitas sekolah berhasil diperbarui.');
    }
}
