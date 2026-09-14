<?php

namespace Modules\Kepegawaian\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Kepegawaian\Entities\PembinaanSupervisi;
use Modules\Core\Entities\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PembinaanController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : (strtolower($user?->role?->nama_role ?? '') === 'super_admin');
        $tenants = $isSuperAdmin ? DB::table('core.tenants')->orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah', 'npsn']) : [];
        
        $activeTenantId = ($isSuperAdmin && $request->filled('tenant_id'))
            ? $request->input('tenant_id')
            : (session('tenant_id') ?? $user?->tenant_id);

        $query = PembinaanSupervisi::query()
            ->when($activeTenantId, function ($q, $tId) {
                $q->where('tenant_id', $tId);
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_guru', 'ILIKE', "%{$search}%")
                        ->orWhere('nip_guru', 'ILIKE', "%{$search}%")
                        ->orWhere('mata_pelajaran', 'ILIKE', "%{$search}%")
                        ->orWhere('catatan_observasi', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->status_pembinaan, function ($q, $status) {
                $q->where('status_pembinaan', $status);
            })
            ->when($request->jenis_supervisi, function ($q, $jenis) {
                $q->where('jenis_supervisi', $jenis);
            })
            ->when($request->predikat, function ($q, $predikat) {
                $q->where('predikat', $predikat);
            })
            ->when($request->tahun_ajaran, function ($q, $ta) {
                $q->where('tahun_ajaran', $ta);
            })
            ->orderBy('tanggal_supervisi', 'desc')
            ->orderBy('created_at', 'desc');

        $supervisiList = $query->paginate(15)->withQueryString();

        // KPI Statistik
        $allRecords = PembinaanSupervisi::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('is_active', true)
            ->get();
        $totalSupervisi = $allRecords->count();
        $rataRataSkor = $totalSupervisi > 0 ? round($allRecords->avg('skor_total'), 1) : 0;
        $selesaiDibina = $allRecords->where('status_pembinaan', 'Selesai Dibina')->count();
        $butuhPendampingan = $allRecords->where('status_pembinaan', 'Butuh Pendampingan Khusus')->count();

        // Distribusi Predikat
        $predikatStats = [
            'sangat_baik' => $allRecords->where('predikat', 'Sangat Baik')->count(),
            'baik'        => $allRecords->where('predikat', 'Baik')->count(),
            'cukup'       => $allRecords->where('predikat', 'Cukup')->count(),
            'perlu_bina'  => $allRecords->where('predikat', 'Perlu Pembinaan')->count(),
        ];

        // Daftar Guru untuk Selector Autocomplete
        $guruSelector = User::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('is_active', true)
            ->whereHas('role', function ($r) {
                $r->whereIn('nama_role', ['guru', 'admin_sekolah', 'kepala_sekolah']);
            })
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'nip', 'nuptk', 'email']);

        // Jika tidak ada user dengan role guru, fallback ke semua user non-siswa
        if ($guruSelector->isEmpty()) {
            $guruSelector = User::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
                ->where('is_active', true)
                ->orderBy('nama_lengkap', 'asc')
                ->limit(50)
                ->get(['id', 'nama_lengkap', 'nip', 'nuptk', 'email']);
        }

        $data = [
            'isSuperAdmin'      => $isSuperAdmin,
            'tenants'           => $tenants,
            'activeTenantId'    => $activeTenantId,
            'supervisiList'     => $supervisiList,
            'kpi'               => [
                'total_supervisi'    => $totalSupervisi,
                'rata_rata_skor'     => $rataRataSkor,
                'selesai_dibina'     => $selesaiDibina,
                'butuh_pendampingan' => $butuhPendampingan,
                'predikat_stats'     => $predikatStats,
            ],
            'guruSelector'      => $guruSelector,
            'filters'           => $request->only(['search', 'status_pembinaan', 'jenis_supervisi', 'predikat', 'tahun_ajaran', 'tenant_id']),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return Inertia::render('KepalaSekolah/Pembinaan/Index', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'guru_id'               => 'nullable|uuid',
            'nama_guru'             => 'required|string|max:255',
            'nip_guru'              => 'nullable|string|max:50',
            'mata_pelajaran'        => 'nullable|string|max:100',
            'kelas_rombel'          => 'nullable|string|max:50',
            'tanggal_supervisi'     => 'required|date',
            'tahun_ajaran'          => 'required|string|max:20',
            'semester'              => 'required|string|max:10',
            'jenis_supervisi'       => 'required|string|max:50',
            'skor_pedagogik'        => 'required|integer|min:0|max:100',
            'skor_profesional'      => 'required|integer|min:0|max:100',
            'skor_kepribadian'      => 'required|integer|min:0|max:100',
            'skor_sosial'           => 'required|integer|min:0|max:100',
            'catatan_observasi'     => 'nullable|string',
            'rekomendasi_pembinaan' => 'nullable|string',
            'tindak_lanjut'         => 'nullable|string',
            'status_pembinaan'      => 'required|string|in:Terjadwal,Dalam Proses,Selesai Dibina,Butuh Pendampingan Khusus',
        ]);

        // Hitung Skor Total & Predikat
        $skorTotal = round(($validated['skor_pedagogik'] + $validated['skor_profesional'] + $validated['skor_kepribadian'] + $validated['skor_sosial']) / 4, 2);
        $predikat = 'Baik';
        if ($skorTotal >= 91) {
            $predikat = 'Sangat Baik';
        } elseif ($skorTotal >= 76) {
            $predikat = 'Baik';
        } elseif ($skorTotal >= 61) {
            $predikat = 'Cukup';
        } else {
            $predikat = 'Perlu Pembinaan';
        }

        $validated['skor_total'] = $skorTotal;
        $validated['predikat'] = $predikat;
        $validated['tenant_id'] = session('tenant_id') ?? auth()->user()?->tenant_id;
        $validated['id'] = Str::uuid()->toString();

        $item = PembinaanSupervisi::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data supervisi & pembinaan guru berhasil disimpan.',
                'data'    => $item,
            ], 201);
        }

        return back()->with('success', 'Data supervisi & pembinaan guru berhasil disimpan.');
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $item = PembinaanSupervisi::findOrFail($id);

        $validated = $request->validate([
            'guru_id'               => 'nullable|uuid',
            'nama_guru'             => 'required|string|max:255',
            'nip_guru'              => 'nullable|string|max:50',
            'mata_pelajaran'        => 'nullable|string|max:100',
            'kelas_rombel'          => 'nullable|string|max:50',
            'tanggal_supervisi'     => 'required|date',
            'tahun_ajaran'          => 'required|string|max:20',
            'semester'              => 'required|string|max:10',
            'jenis_supervisi'       => 'required|string|max:50',
            'skor_pedagogik'        => 'required|integer|min:0|max:100',
            'skor_profesional'      => 'required|integer|min:0|max:100',
            'skor_kepribadian'      => 'required|integer|min:0|max:100',
            'skor_sosial'           => 'required|integer|min:0|max:100',
            'catatan_observasi'     => 'nullable|string',
            'rekomendasi_pembinaan' => 'nullable|string',
            'tindak_lanjut'         => 'nullable|string',
            'status_pembinaan'      => 'required|string|in:Terjadwal,Dalam Proses,Selesai Dibina,Butuh Pendampingan Khusus',
        ]);

        $skorTotal = round(($validated['skor_pedagogik'] + $validated['skor_profesional'] + $validated['skor_kepribadian'] + $validated['skor_sosial']) / 4, 2);
        $predikat = 'Baik';
        if ($skorTotal >= 91) {
            $predikat = 'Sangat Baik';
        } elseif ($skorTotal >= 76) {
            $predikat = 'Baik';
        } elseif ($skorTotal >= 61) {
            $predikat = 'Cukup';
        } else {
            $predikat = 'Perlu Pembinaan';
        }

        $validated['skor_total'] = $skorTotal;
        $validated['predikat'] = $predikat;

        $item->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data supervisi & pembinaan guru berhasil diperbarui.',
                'data'    => $item,
            ]);
        }

        return back()->with('success', 'Data supervisi & pembinaan guru berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $item = PembinaanSupervisi::findOrFail($id);
        $item->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data supervisi & pembinaan guru berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Data supervisi & pembinaan guru berhasil dihapus.');
    }
}
