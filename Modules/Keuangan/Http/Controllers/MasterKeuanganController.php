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
use Modules\Keuangan\Entities\PosKeuangan;
use Modules\Keuangan\Entities\TarifPembayaran;
use Modules\Keuangan\Entities\KeringananSiswa;
use Modules\Keuangan\Entities\KasBank;
use Modules\Keuangan\Entities\PengaturanKeuangan;
use Modules\Keuangan\Entities\KeuanganAuditLog;
use Modules\Siswa\Entities\Siswa;

class MasterKeuanganController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $selectedTenantId = $request->query('tenant_id');
        $tenantId = ($isSuperAdmin && !empty($selectedTenantId)) ? $selectedTenantId : (session('tenant_id') ?? $user?->tenant_id);

        $posList = PosKeuangan::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->orderBy('urutan', 'asc')->orderBy('created_at', 'asc')->get();
        $tarifList = TarifPembayaran::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->with('pos:id,nama_pos,tipe_periode')->orderBy('created_at', 'desc')->get();
        $keringananList = KeringananSiswa::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->with(['siswa:id,nama_lengkap,nisn,kelas_saat_ini', 'pos:id,nama_pos'])->orderBy('created_at', 'desc')->get();
        $kasList = KasBank::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->orderBy('created_at', 'asc')->get();
        $pengaturan = PengaturanKeuangan::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'id' => (string)Str::uuid(),
                'nama_modul' => 'Keuangan & SPP',
                'istilah_tagihan' => 'Tagihan',
                'istilah_tunggakan' => 'Tunggakan',
                'format_nomor_kuitansi' => 'KW/{Y}{m}/{NUM}',
                'nama_bendahara' => 'Bendahara Sekolah',
                'catatan_kuitansi' => 'Bukti pembayaran sah yang diterbitkan oleh sistem SINTA.',
                'is_active' => true,
            ]
        );

        // Master data referensi kelas, jurusan & tahun ajaran
        $kelasList = DB::table('akademik.kelas')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->orderBy('nama_kelas', 'asc')->get(['id', 'nama_kelas', 'kode_kelas']);
        $jurusanList = DB::table('akademik.jurusan')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->orderBy('nama_jurusan', 'asc')->get(['id', 'nama_jurusan']);
        $tahunAjaranList = DB::table('akademik.tahun_ajaran')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->orderBy('is_active', 'desc')->orderBy('created_at', 'desc')->get(['id', 'nama_tahun_ajaran as tahun_ajaran', 'is_active']);

        $tenantsList = $isSuperAdmin ? Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : [];

        $data = [
            'posList'          => $posList,
            'tarifList'        => $tarifList,
            'keringananList'   => $keringananList,
            'kasList'          => $kasList,
            'pengaturan'       => $pengaturan,
            'kelasList'        => $kelasList,
            'jurusanList'      => $jurusanList,
            'tahunAjaranList'  => $tahunAjaranList,
            'isSuperAdmin'     => $isSuperAdmin,
            'tenantsList'      => $tenantsList,
            'selectedTenantId' => $selectedTenantId,
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return Inertia::render('Keuangan/Master/Index', $data);
    }

    // 1. CRUD Pos Keuangan
    public function storePos(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kode_pos'     => 'required|string|max:50',
            'nama_pos'     => 'required|string|max:150',
            'tipe_periode' => 'required|string|in:Bulanan,Bebas,Semester,Tahunan',
            'urutan'       => 'nullable|integer',
            'keterangan'   => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        $pos = PosKeuangan::create($validated);

        KeuanganAuditLog::create([
            'tenant_id'  => session('tenant_id'),
            'user_id'    => Auth::id(),
            'user_role'  => Auth::user()?->role?->nama_role,
            'event_type' => 'CREATE_POS',
            'new_data'   => $pos->toArray(),
            'ip_address' => $request->ip(),
            'keterangan' => "Menambahkan Pos Biaya baru: {$pos->nama_pos}",
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pos biaya berhasil ditambahkan.', 'data' => $pos], 201);
        }

        return back()->with('success', 'Pos biaya berhasil ditambahkan.');
    }

    public function updatePos(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $pos = PosKeuangan::findOrFail($id);
        $oldData = $pos->toArray();

        $validated = $request->validate([
            'kode_pos'     => 'required|string|max:50',
            'nama_pos'     => 'required|string|max:150',
            'tipe_periode' => 'required|string|in:Bulanan,Bebas,Semester,Tahunan',
            'urutan'       => 'nullable|integer',
            'keterangan'   => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        $pos->update($validated);

        KeuanganAuditLog::create([
            'tenant_id'  => session('tenant_id'),
            'user_id'    => Auth::id(),
            'user_role'  => Auth::user()?->role?->nama_role,
            'event_type' => 'UPDATE_POS',
            'old_data'   => $oldData,
            'new_data'   => $pos->toArray(),
            'ip_address' => $request->ip(),
            'keterangan' => "Memperbarui Pos Biaya: {$pos->nama_pos}",
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pos biaya berhasil diperbarui.', 'data' => $pos]);
        }

        return back()->with('success', 'Pos biaya berhasil diperbarui.');
    }

    public function deletePos(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $pos = PosKeuangan::findOrFail($id);
        $posName = $pos->nama_pos;
        $pos->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Pos biaya '{$posName}' berhasil dihapus."]);
        }

        return back()->with('success', "Pos biaya '{$posName}' berhasil dihapus.");
    }

    // 2. CRUD Tarif Pembayaran
    public function storeTarif(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'pos_id'          => 'required|uuid|exists:keuangan.transaksi_spp_komponen,id',
            'tahun_ajaran_id' => 'nullable|uuid',
            'tingkat'         => 'nullable|string|max:20',
            'jurusan_id'      => 'nullable|uuid',
            'kelas_id'        => 'nullable|uuid',
            'nominal_tarif'   => 'required|numeric|min:0',
            'keterangan'      => 'nullable|string',
        ]);

        $tarif = TarifPembayaran::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tarif pembayaran berhasil diset.', 'data' => $tarif->load('pos')], 201);
        }

        return back()->with('success', 'Tarif pembayaran berhasil disimpan.');
    }

    public function deleteTarif(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $tarif = TarifPembayaran::findOrFail($id);
        $tarif->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tarif pembayaran berhasil dihapus.']);
        }

        return back()->with('success', 'Tarif pembayaran berhasil dihapus.');
    }

    // 3. CRUD Keringanan & Beasiswa
    public function storeKeringanan(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id'       => 'required|uuid|exists:siswa.siswa,id',
            'pos_id'         => 'required|uuid|exists:keuangan.transaksi_spp_komponen,id',
            'tipe_potongan'  => 'required|string|in:Nominal,Persentase',
            'nilai_potongan' => 'required|numeric|min:0',
            'alasan'         => 'nullable|string',
        ]);

        $keringanan = KeringananSiswa::updateOrCreate(
            ['tenant_id' => session('tenant_id'), 'siswa_id' => $validated['siswa_id'], 'pos_id' => $validated['pos_id']],
            $validated
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data keringanan/beasiswa siswa berhasil disimpan.', 'data' => $keringanan->load(['siswa', 'pos'])], 201);
        }

        return back()->with('success', 'Data keringanan/beasiswa berhasil disimpan.');
    }

    public function deleteKeringanan(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $keringanan = KeringananSiswa::findOrFail($id);
        $keringanan->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data keringanan berhasil dihapus.']);
        }

        return back()->with('success', 'Data keringanan berhasil dihapus.');
    }

    // 4. CRUD Kas & Bank
    public function storeKasBank(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kode_kas'       => 'nullable|string|max:50',
            'nama_kas'       => 'required|string|max:100',
            'nomor_rekening' => 'nullable|string|max:100',
            'atas_nama'      => 'nullable|string|max:100',
            'saldo_awal'     => 'nullable|numeric|min:0',
            'is_active'      => 'boolean',
        ]);

        $validated['saldo_awal'] = $validated['saldo_awal'] ?? 0;
        $validated['saldo_saat_ini'] = $validated['saldo_awal'];

        $kas = KasBank::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Akun kas/bank berhasil ditambahkan.', 'data' => $kas], 201);
        }

        return back()->with('success', 'Akun kas/bank berhasil ditambahkan.');
    }

    public function updateKasBank(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $kas = KasBank::findOrFail($id);
        $validated = $request->validate([
            'kode_kas'       => 'nullable|string|max:50',
            'nama_kas'       => 'required|string|max:100',
            'nomor_rekening' => 'nullable|string|max:100',
            'atas_nama'      => 'nullable|string|max:100',
            'is_active'      => 'boolean',
        ]);

        $kas->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Akun kas/bank berhasil diperbarui.', 'data' => $kas]);
        }

        return back()->with('success', 'Akun kas/bank berhasil diperbarui.');
    }

    public function deleteKasBank(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $kas = KasBank::findOrFail($id);
        $kas->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Akun kas berhasil dihapus.']);
        }

        return back()->with('success', 'Akun kas berhasil dihapus.');
    }

    // 5. Update Pengaturan
    public function updatePengaturan(Request $request): JsonResponse|RedirectResponse
    {
        $tenantId = session('tenant_id') ?? Auth::user()?->tenant_id;
        $pengaturan = PengaturanKeuangan::where('tenant_id', $tenantId)->firstOrFail();

        $validated = $request->validate([
            'nama_modul'            => 'required|string|max:100',
            'istilah_tagihan'       => 'required|string|max:100',
            'istilah_tunggakan'     => 'required|string|max:100',
            'format_nomor_kuitansi' => 'required|string|max:100',
            'nama_bendahara'        => 'nullable|string|max:150',
            'nip_bendahara'         => 'nullable|string|max:50',
            'catatan_kuitansi'      => 'nullable|string',
            'midtrans_client_key'   => 'nullable|string|max:255',
            'midtrans_server_key'   => 'nullable|string|max:255',
            'midtrans_is_production'=> 'boolean',
        ]);

        $pengaturan->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengaturan keuangan berhasil disimpan.', 'data' => $pengaturan]);
        }

        return back()->with('success', 'Pengaturan keuangan berhasil disimpan.');
    }

    // Helper Search Siswa
    public function searchSiswa(Request $request): JsonResponse
    {
        $q = trim((string)$request->query('q', ''));
        $kelasId = $request->query('kelas_id');

        $query = Siswa::select('id', 'nama_lengkap', 'nisn', 'nis', 'kelas_saat_ini')
            ->where('status_siswa', 'Aktif');

        if (!empty($kelasId)) {
            $kelasObj = DB::table('akademik.kelas')->where('id', $kelasId)->first();
            $namaKelas = $kelasObj ? $kelasObj->nama_kelas : $kelasId;
            $query->where(function ($sub) use ($namaKelas, $kelasId) {
                $sub->where('kelas_saat_ini', $namaKelas)
                    ->orWhere('kelas_saat_ini', $kelasId);
            });
        }

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_lengkap', 'ILIKE', "%{$q}%")
                    ->orWhere('nisn', 'ILIKE', "%{$q}%")
                    ->orWhere('nis', 'ILIKE', "%{$q}%");
            });
        }

        $siswas = $query->limit(20)->get();

        return response()->json(['success' => true, 'data' => $siswas]);
    }
}
