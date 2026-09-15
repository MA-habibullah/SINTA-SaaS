<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Entities\Tenant;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\PosKeuangan;
use Modules\Keuangan\Entities\TarifPembayaran;
use Modules\Keuangan\Entities\KeringananSiswa;
use Modules\Keuangan\Entities\KeuanganAuditLog;
use Modules\Siswa\Entities\Siswa;
use App\Services\SecurityPayloadService;

class TagihanSiswaController extends Controller
{
    /**
     * Daftar Tagihan Siswa & Generator Tagihan Massal
     * Zero-SSR Pattern: initial GET render shell kosong, data via ?async=1
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        // === ZERO-SSR: Initial page load hanya render shell kosong ===
        $isInitialSsr = !$request->header('X-Inertia') && !$request->has('async');

        if ($isInitialSsr) {
            return Inertia::render('Keuangan/Tagihan/Index', [
                'tagihanList'     => null,
                'posList'         => null,
                'kelasList'       => null,
                'tahunAjaranList' => null,
                'isSuperAdmin'    => $isSuperAdmin,
                'tenantsList'     => null,
                'filters'         => null,
            ]);
        }

        // === ASYNC JSON: Data aktual dimuat on-demand via Axios ===
        // Tenant dari sesi server, bukan URL query parameter
        $selectedTenantId = $request->header('X-Tenant-Id') ?? $request->query('async_tenant_id');
        $tenantId = ($isSuperAdmin && !empty($selectedTenantId))
            ? $selectedTenantId
            : (session('tenant_id') ?? $user?->tenant_id);

        // Filter dari request body/query (boleh karena ini panggilan API, bukan URL navigasi)
        $search  = trim((string)$request->query('search', ''));
        $status  = $request->query('status');
        $posId   = $request->query('pos_id');
        $kelasId = $request->query('kelas_id');
        $bulan   = $request->query('bulan');
        $tahun   = $request->query('tahun');

        $query = TagihanSiswa::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->with([
            'siswa:id,nama_lengkap,nisn,nis,kelas_saat_ini,jurusan',
            'pos:id,nama_pos,tipe_periode'
        ]);

        if (!empty($search)) {
            $query->whereHas('siswa', function ($sub) use ($search) {
                $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                    ->orWhere('nisn', 'ILIKE', "%{$search}%")
                    ->orWhere('nis', 'ILIKE', "%{$search}%");
            });
        }
        if (!empty($status))  $query->where('status_pembayaran', $status);
        if (!empty($posId))   $query->where('pos_id', $posId);
        if (!empty($kelasId)) {
            $kelasObj = DB::table('akademik.kelas')->where('id', $kelasId)->first();
            $namaKelas = $kelasObj ? $kelasObj->nama_kelas : $kelasId;
            $query->whereHas('siswa', fn($sub) => $sub->where('kelas_saat_ini', $namaKelas));
        }
        if (!empty($bulan)) $query->where('bulan', (int)$bulan);
        if (!empty($tahun)) $query->where('tahun', (int)$tahun);

        $tagihanList     = $query->orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->orderBy('created_at', 'desc')->paginate(20);
        $posList         = PosKeuangan::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->orderBy('urutan', 'asc')->get(['id', 'nama_pos', 'tipe_periode']);
        $kelasList       = DB::table('akademik.kelas')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->orderBy('nama_kelas', 'asc')->get(['id', 'nama_kelas', 'kode_kelas']);
        $tahunAjaranList = DB::table('akademik.tahun_ajaran')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->orderBy('is_active', 'desc')->get(['id', 'nama_tahun_ajaran as tahun_ajaran']);
        $tenantsList     = $isSuperAdmin ? Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : [];

        $payload = [
            'tagihanList'     => $tagihanList,
            'posList'         => $posList,
            'kelasList'       => $kelasList,
            'tahunAjaranList' => $tahunAjaranList,
            'isSuperAdmin'    => $isSuperAdmin,
            'tenantsList'     => $tenantsList,
            'filters'         => compact('search', 'status', 'posId', 'kelasId', 'bulan', 'tahun'),
        ];

        $payload = SecurityPayloadService::sanitize($payload);

        if ($request->wantsJson() || $request->has('async')) {
            return response()->json(['success' => true, 'data' => $payload]);
        }

        return Inertia::render('Keuangan/Tagihan/Index', $payload);
    }

    /**
     * Penerbitan Tagihan Massal (Batch Generate Invoices)
     */
    public function generateTagihan(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'pos_id'          => 'required|uuid|exists:keuangan.transaksi_spp_komponen,id',
            'tahun_ajaran_id' => 'nullable|uuid',
            'target_tipe'     => 'required|string|in:all,tingkat,kelas',
            'tingkat'         => 'nullable|string',
            'kelas_id'        => 'nullable|uuid',
            'bulan'           => 'nullable|integer|min:1|max:12',
            'tahun'           => 'required|integer|min:2020|max:2099',
            'tanggal_jatuh_tempo' => 'nullable|date',
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        $pos = PosKeuangan::findOrFail($validated['pos_id']);
        $isBulanan = in_array($pos->tipe_periode, ['Bulanan']);

        if ($isBulanan && empty($validated['bulan'])) {
            return response()->json(['success' => false, 'message' => 'Bulan tagihan wajib diisi untuk pos bertipe Bulanan.'], 422);
        }

        // Ambil daftar siswa sasaran
        $siswaQuery = Siswa::where('tenant_id', $tenantId)->where('status_siswa', 'Aktif');

        if ($validated['target_tipe'] === 'kelas' && !empty($validated['kelas_id'])) {
            $kelasObj = DB::table('akademik.kelas')->where('id', $validated['kelas_id'])->first();
            $namaKelas = $kelasObj ? $kelasObj->nama_kelas : $validated['kelas_id'];
            $siswaQuery->where('kelas_saat_ini', $namaKelas);
        } elseif ($validated['target_tipe'] === 'tingkat' && !empty($validated['tingkat'])) {
            $siswaQuery->where('kelas_saat_ini', 'LIKE', $validated['tingkat'] . '%');
        }

        $targetSiswa = $siswaQuery->get();

        if ($targetSiswa->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan siswa aktif untuk kriteria target yang dipilih.'], 422);
        }

        // Ambil tarif acuan aktif
        $tarifList   = TarifPembayaran::where('tenant_id', $tenantId)->where('pos_id', $pos->id)->get();
        $defaultTarif = $tarifList->whereNull('kelas_id')->whereNull('tingkat')->first()?->nominal_tarif ?? 0;

        // Ambil keringanan beasiswa aktif
        $keringananList = KeringananSiswa::where('tenant_id', $tenantId)->where('pos_id', $pos->id)->where('is_active', true)->get()->keyBy('siswa_id');

        $generatedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($targetSiswa, $pos, $validated, $tarifList, $defaultTarif, $keringananList, $tenantId, &$generatedCount, &$skippedCount) {
            foreach ($targetSiswa as $siswa) {
                // Cek duplikasi tagihan
                $exists = TagihanSiswa::where('tenant_id', $tenantId)
                    ->where('siswa_id', $siswa->id)
                    ->where('pos_id', $pos->id)
                    ->when($pos->tipe_periode === 'Bulanan', function ($q) use ($validated) {
                        $q->where('bulan', (int)$validated['bulan'])->where('tahun', (int)$validated['tahun']);
                    })
                    ->when($pos->tipe_periode !== 'Bulanan' && !empty($validated['tahun_ajaran_id']), function ($q) use ($validated) {
                        $q->where('tahun_ajaran_id', $validated['tahun_ajaran_id']);
                    })
                    ->exists();

                if ($exists) { $skippedCount++; continue; }

                // Hitung Tarif Dasar
                $tarifDasar = $defaultTarif;
                $tarifKelas = $tarifList->where('kelas_id', $siswa->kelas_id)->first();
                if ($tarifKelas) {
                    $tarifDasar = $tarifKelas->nominal_tarif;
                } else {
                    $tarifTingkat = $tarifList->where('tingkat', $siswa->kelas?->tingkat)->first();
                    if ($tarifTingkat) $tarifDasar = $tarifTingkat->nominal_tarif;
                }

                // Hitung Potongan Keringanan
                $potongan = 0;
                if (isset($keringananList[$siswa->id])) {
                    $k = $keringananList[$siswa->id];
                    $potongan = ($k->tipe_potongan === 'Persentase')
                        ? ($tarifDasar * (float)$k->nilai_potongan) / 100
                        : (float)$k->nilai_potongan;
                }

                $totalTagihan = max(0, $tarifDasar - $potongan);
                $status = ($totalTagihan <= 0) ? 'Lunas' : 'Belum Bayar';
                $nomorTagihan = 'INV/' . ($validated['tahun'] ?? date('Y')) . '/' . ($pos->kode_pos ?? 'SPP') . '/' . strtoupper(Str::random(6));

                TagihanSiswa::create([
                    'tenant_id'           => $tenantId,
                    'nomor_tagihan'       => $nomorTagihan,
                    'siswa_id'            => $siswa->id,
                    'pos_id'              => $pos->id,
                    'tahun_ajaran_id'     => $validated['tahun_ajaran_id'] ?? null,
                    'bulan'               => $validated['bulan'] ?? null,
                    'tahun'               => $validated['tahun'],
                    'nominal_tarif_dasar' => $tarifDasar,
                    'nominal_potongan'    => $potongan,
                    'total_tagihan'       => $totalTagihan,
                    'total_terbayar'      => ($totalTagihan <= 0) ? $totalTagihan : 0,
                    'sisa_tagihan'        => $totalTagihan,
                    'status_pembayaran'   => $status,
                    'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'] ?? null,
                ]);

                $generatedCount++;
            }
        });

        KeuanganAuditLog::create([
            'tenant_id'  => $tenantId,
            'user_id'    => $user?->id,
            'user_role'  => $user?->role?->nama_role ?? 'admin',
            'event_type' => 'GENERATE_TAGIHAN',
            'nominal'    => 0,
            'new_data'   => [
                'pos_id'          => $pos->id,
                'pos_nama'        => $pos->nama_pos,
                'target_tipe'     => $validated['target_tipe'],
                'generated_count' => $generatedCount,
                'skipped_count'   => $skippedCount,
            ],
            'ip_address' => $request->ip(),
            'keterangan' => "Penerbitan Tagihan Massal: {$pos->nama_pos} berhasil diterbitkan untuk {$generatedCount} siswa ({$skippedCount} dilewati karena sudah ada).",
        ]);

        $msg = "Berhasil menerbitkan {$generatedCount} tagihan siswa." . ($skippedCount > 0 ? " ({$skippedCount} siswa dilewati karena tagihan sudah terbit sebelumnya)" : "");

        if ($request->wantsJson()) {
            return response()->json([
                'success'         => true,
                'message'         => $msg,
                'generated_count' => $generatedCount,
                'skipped_count'   => $skippedCount,
            ], 201);
        }

        return back()->with('success', $msg);
    }

    /**
     * Hapus Tagihan Siswa (Hanya jika belum ada pembayaran)
     */
    public function deleteTagihan(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $tagihan = TagihanSiswa::findOrFail($id);

        if ($tagihan->total_terbayar > 0) {
            return response()->json(['success' => false, 'message' => 'Tagihan tidak dapat dihapus karena sudah memiliki riwayat pembayaran.'], 422);
        }

        $tagihan->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tagihan berhasil dihapus.']);
        }

        return back()->with('success', 'Tagihan berhasil dihapus.');
    }
}
