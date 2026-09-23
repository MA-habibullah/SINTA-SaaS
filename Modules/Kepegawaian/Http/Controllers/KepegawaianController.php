<?php

namespace Modules\Kepegawaian\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Kepegawaian\Entities\Gtk;
use Modules\Kepegawaian\Entities\RiwayatKepangkatan;
use Modules\Kepegawaian\Entities\SertifikasiPtk;
use Modules\Kepegawaian\Entities\LowonganKerja;
use Modules\Kepegawaian\Entities\PelamarKerja;
use Modules\Kepegawaian\Entities\PembinaanSupervisi;
use Modules\Core\Services\SecurityPayloadService;

class KepegawaianController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $tab = $request->input('tab', 'gtk');
        $allowedTabs = \Modules\Core\Services\MenuService::getAllowedTabsForRoute(auth()->user(), '/kepegawaian');

        if ($request->wantsJson() || $request->ajax() || $request->has('async')) {
            $data = match ($tab) {
                'pangkat' => $this->getPangkatData($request),
                'sertifikasi' => $this->getSertifikasiData($request),
                'recruitment' => $this->getRecruitmentData($request),
                'supervisi' => $this->getSupervisiData($request),
                default => $this->getGtkData($request),
            };

            return response()->json([
                'success'      => true,
                'tab'          => $tab,
                'allowed_tabs' => $allowedTabs,
                'data'         => SecurityPayloadService::sanitize($data),
            ]);
        }

        return Inertia::render('Kepegawaian/Index', [
            'initialTab' => $tab,
        ]);
    }

    private function getGtkData(Request $request)
    {
        $query = Gtk::query()
            ->with(['riwayatKepangkatan', 'sertifikasi'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('nip', 'ILIKE', "%{$search}%")
                        ->orWhere('nuptk', 'ILIKE', "%{$search}%")
                        ->orWhere('nik', 'ILIKE', "%{$search}%")
                        ->orWhere('jabatan', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->jenis_ptk, fn($q, $j) => $q->where('jenis_ptk', $j))
            ->when($request->status_kepegawaian, fn($q, $s) => $q->where('status_kepegawaian', $s))
            ->orderBy('nama_lengkap', 'asc');

        $items = $query->paginate(15);

        $stats = [
            'total_gtk' => Gtk::count(),
            'total_guru' => Gtk::where('jenis_ptk', 'ILIKE', '%Guru%')->count(),
            'total_tendik' => Gtk::where('jenis_ptk', 'NOT ILIKE', '%Guru%')->count(),
            'total_pns_pppk' => Gtk::whereIn('status_kepegawaian', ['PNS', 'PPPK'])->count(),
        ];

        return compact('items', 'stats');
    }

    private function getPangkatData(Request $request)
    {
        $items = RiwayatKepangkatan::with('gtk')
            ->when($request->search, function ($q, $search) {
                $q->whereHas('gtk', function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('nip', 'ILIKE', "%{$search}%");
                })->orWhere('golongan_pangkat', 'ILIKE', "%{$search}%")
                  ->orWhere('nomor_sk', 'ILIKE', "%{$search}%");
            })
            ->orderBy('tmt_pangkat', 'desc')
            ->paginate(15);

        $gtks = Gtk::select('id', 'nama_lengkap', 'nip', 'status_kepegawaian')->orderBy('nama_lengkap', 'asc')->get();

        return compact('items', 'gtks');
    }

    private function getSertifikasiData(Request $request)
    {
        $items = SertifikasiPtk::with('gtk')
            ->when($request->search, function ($q, $search) {
                $q->whereHas('gtk', function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%");
                })->orWhere('bidang_studi', 'ILIKE', "%{$search}%")
                  ->orWhere('nomor_sertifikat', 'ILIKE', "%{$search}%");
            })
            ->orderBy('tahun_sertifikasi', 'desc')
            ->paginate(15);

        $gtks = Gtk::select('id', 'nama_lengkap', 'nip')->orderBy('nama_lengkap', 'asc')->get();

        return compact('items', 'gtks');
    }

    private function getRecruitmentData(Request $request)
    {
        $lowongan = LowonganKerja::withCount('pelamar')->orderBy('created_at', 'desc')->get();
        $pelamar = PelamarKerja::with('lowongan')
            ->when($request->lowongan_id, fn($q, $id) => $q->where('lowongan_id', $id))
            ->when($request->status_tahapan, fn($q, $st) => $q->where('status_tahapan', $st))
            ->when($request->search, function ($q, $search) {
                $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('no_hp', 'ILIKE', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return compact('lowongan', 'pelamar');
    }

    private function getSupervisiData(Request $request)
    {
        $items = PembinaanSupervisi::query()
            ->when($request->search, function ($q, $search) {
                $q->where('nama_guru', 'ILIKE', "%{$search}%")
                  ->orWhere('nip_guru', 'ILIKE', "%{$search}%")
                  ->orWhere('mata_pelajaran', 'ILIKE', "%{$search}%");
            })
            ->orderBy('tanggal_supervisi', 'desc')
            ->paginate(15);

        return compact('items');
    }

    public function storeGtk(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nip'                 => 'nullable|string|max:30',
            'nuptk'               => 'nullable|string|max:30',
            'nik'                 => 'nullable|string|max:20',
            'nama_lengkap'        => 'required|string|max:255',
            'gelar_depan'         => 'nullable|string|max:20',
            'gelar_belakang'      => 'nullable|string|max:50',
            'jenis_kelamin'       => 'required|in:L,P',
            'tempat_lahir'        => 'nullable|string|max:100',
            'tanggal_lahir'       => 'nullable|date',
            'jenis_ptk'           => 'required|string|max:50',
            'status_kepegawaian'  => 'required|string|max:50',
            'jabatan'             => 'nullable|string|max:100',
            'pendidikan_terakhir' => 'nullable|string|max:20',
            'jurusan_pendidikan'  => 'nullable|string|max:100',
            'no_hp'               => 'nullable|string|max:30',
            'email'               => 'nullable|email|max:100',
            'alamat_tinggal'      => 'nullable|string',
            'tmt_kerja'           => 'nullable|date',
            'is_active'           => 'boolean',
        ]);

        $gtk = Gtk::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data GTK berhasil disimpan.', 'data' => $gtk], 201);
        }

        return back()->with('success', 'Data pendidik/tenaga kependidikan berhasil ditambahkan.');
    }

    public function updateGtk(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $gtk = Gtk::findOrFail($id);

        $validated = $request->validate([
            'nip'                 => 'nullable|string|max:30',
            'nuptk'               => 'nullable|string|max:30',
            'nik'                 => 'nullable|string|max:20',
            'nama_lengkap'        => 'required|string|max:255',
            'gelar_depan'         => 'nullable|string|max:20',
            'gelar_belakang'      => 'nullable|string|max:50',
            'jenis_kelamin'       => 'required|in:L,P',
            'tempat_lahir'        => 'nullable|string|max:100',
            'tanggal_lahir'       => 'nullable|date',
            'jenis_ptk'           => 'required|string|max:50',
            'status_kepegawaian'  => 'required|string|max:50',
            'jabatan'             => 'nullable|string|max:100',
            'pendidikan_terakhir' => 'nullable|string|max:20',
            'jurusan_pendidikan'  => 'nullable|string|max:100',
            'no_hp'               => 'nullable|string|max:30',
            'email'               => 'nullable|email|max:100',
            'alamat_tinggal'      => 'nullable|string',
            'tmt_kerja'           => 'nullable|date',
            'is_active'           => 'boolean',
        ]);

        $gtk->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data GTK berhasil diperbarui.', 'data' => $gtk]);
        }

        return back()->with('success', 'Data GTK berhasil diperbarui.');
    }

    public function destroyGtk(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $gtk = Gtk::findOrFail($id);
        $gtk->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data GTK berhasil dihapus.']);
        }

        return back()->with('success', 'Data GTK berhasil dihapus.');
    }

    public function storePangkat(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'ptk_id'           => 'required|uuid|exists:kepegawaian.ptk_identitas,id',
            'golongan_pangkat' => 'required|string|max:50',
            'nomor_sk'         => 'nullable|string|max:100',
            'tanggal_sk'       => 'nullable|date',
            'tmt_pangkat'      => 'required|date',
            'pejabat_penetap'  => 'nullable|string|max:150',
            'gaji_pokok'       => 'nullable|numeric|min:0',
            'is_terakhir'      => 'boolean',
        ]);

        $item = RiwayatKepangkatan::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Riwayat kepangkatan berhasil dicatat.', 'data' => $item], 201);
        }

        return back()->with('success', 'Riwayat kepangkatan berhasil dicatat.');
    }

    public function storeSertifikasi(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'ptk_id'            => 'required|uuid|exists:kepegawaian.ptk_identitas,id',
            'jenis_sertifikasi' => 'required|string|max:100',
            'nomor_peserta'     => 'nullable|string|max:100',
            'nomor_sertifikat'  => 'required|string|max:100',
            'tahun_sertifikasi' => 'required|integer|min:1980|max:2099',
            'bidang_studi'      => 'required|string|max:150',
            'lembaga_penerbit'  => 'nullable|string|max:150',
        ]);

        $item = SertifikasiPtk::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Sertifikasi PTK berhasil dicatat.', 'data' => $item], 201);
        }

        return back()->with('success', 'Sertifikasi PTK berhasil dicatat.');
    }

    public function storeLowongan(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'judul_posisi'           => 'required|string|max:200',
            'jenis_pekerjaan'        => 'required|string|max:50',
            'kualifikasi_pendidikan' => 'nullable|string|max:100',
            'persyaratan'            => 'nullable|string',
            'tanggal_buka'           => 'required|date',
            'tanggal_tutup'          => 'required|date',
            'kuota_dibutuhkan'       => 'required|integer|min:1',
            'status'                 => 'required|in:Buka,Tutup,Draft',
        ]);

        $item = LowonganKerja::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Lowongan kerja berhasil dipublikasikan.', 'data' => $item], 201);
        }

        return back()->with('success', 'Lowongan kerja berhasil dipublikasikan.');
    }

    public function storePelamar(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'lowongan_id'         => 'required|uuid|exists:kepegawaian.lowongan_kerja,id',
            'nama_lengkap'        => 'required|string|max:255',
            'nik'                 => 'nullable|string|max:20',
            'email'               => 'required|email|max:100',
            'no_hp'               => 'required|string|max:30',
            'pendidikan_terakhir' => 'nullable|string|max:20',
            'jurusan'             => 'nullable|string|max:100',
            'ipk'                 => 'nullable|numeric|min:0|max:4',
            'pengalaman_kerja'    => 'nullable|string',
            'status_tahapan'      => 'required|string|max:50',
            'catatan_seleksi'     => 'nullable|string',
        ]);

        $item = PelamarKerja::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data pelamar berhasil dicatat.', 'data' => $item], 201);
        }

        return back()->with('success', 'Data pelamar berhasil dicatat.');
    }

    public function updateTahapanPelamar(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pelamar = PelamarKerja::findOrFail($id);

        $validated = $request->validate([
            'status_tahapan'  => 'required|in:Pendaftaran,Seleksi Administrasi,Tes Tertulis/Microteaching,Wawancara,Diterima,Ditolak',
            'catatan_seleksi' => 'nullable|string',
        ]);

        $pelamar->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Status tahapan seleksi berhasil diperbarui.', 'data' => $pelamar]);
        }

        return back()->with('success', 'Status tahapan seleksi berhasil diperbarui.');
    }
}
