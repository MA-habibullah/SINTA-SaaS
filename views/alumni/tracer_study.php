<?php
/**
 * View: Tracer Study / Portofolio Alumni
 * Mendukung data siswa terdaftar & alumni luar database (manual input).
 * Form Tambah & Edit menggunakan Modal Pop-up modern dan bersih.
 */
$userRole  = $data['user_role']         ?? ($_SESSION['role_name']    ?? '');
$userNama  = $data['user_nama']         ?? ($_SESSION['nama_lengkap'] ?? 'Alumni');
$tenantId  = $data['tenant_id']         ?? ($_SESSION['tenant_id']    ?? '');
$baseUrl   = $this->getBaseUrl();

// Role Admin / BK
$isAdmin = in_array($userRole, ['super_admin', 'operator_sekolah', 'admin', 'operator', 'guru_bk']);

// ID unik untuk Vue mount point (mendukung embed sub-module)
$tracer_initial_tab  = $active_tracer_tab ?? 'kuliah';
$tracer_instance_id  = 'tracerApp_' . $tracer_initial_tab;
$tracer_vue_selector = '#' . $tracer_instance_id;
?>

<style>
    .tracer-card {
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 4px 20px rgba(15,23,42,0.05);
        border: 1px solid #f1f5f9;
    }
    .tracer-badge {
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .status-aktif    { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .status-lulus    { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .status-drop     { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .status-kontrak  { background: #fff7ed; color: #d97706; border: 1px solid #fed7aa; }
    .status-tetap    { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .status-magang   { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
    .status-usaha    { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }

    .empty-state {
        text-align: center;
        padding: 3.5rem 1rem;
        color: #94a3b8;
    }
    [v-cloak] { display: none !important; }
</style>

<?php if (empty($is_sub_module)): ?>
<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-mortarboard-fill me-2 text-primary"></i>
            Tracer Study / Portofolio Alumni
        </h2>
        <p class="text-muted fs-7 mb-0">
            Rekam jejak pendidikan tinggi dan karir alumni terpusat.
            <?php if ($userRole === 'siswa'): ?>
                Halo, <strong><?= htmlspecialchars($userNama) ?></strong>!
            <?php endif; ?>
        </p>
    </div>
    <?php if ($userRole !== 'siswa'): ?>
    <div>
        <a href="<?= $baseUrl ?>/bk/alumni" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Hub Alumni
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Vue App Mount Point -->
<div id="<?= htmlspecialchars($tracer_instance_id, ENT_QUOTES, 'UTF-8') ?>" v-cloak>

    <!-- BANNER: Status tergantung role -->
    <?php if ($isAdmin): ?>
    <div class="alert border-0 rounded-4 p-3.5 mb-4 shadow-xs d-flex align-items-center justify-content-between flex-wrap gap-3"
         style="background: linear-gradient(135deg, #eff6ff 0%, #f5f3ff 100%); border: 1px solid #e0e7ff;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-2xl d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs"
                 style="width:46px; height:46px; background: linear-gradient(135deg, #3b82f6, #6366f1); color: white;">
                <i class="bi bi-mortarboard-fill fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-slate-800 flex items-center gap-2">
                    Penelusuran Alumni & Tracer Study
                    <span class="badge bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded-lg border border-indigo-200">
                        Mode <?= htmlspecialchars(ucwords(str_replace('_', ' ', $userRole))) ?>
                    </span>
                </h6>
                <p class="mb-0 text-slate-500 text-xs mt-0.5">
                    Dapat menginput siswa terdaftar di pangkalan data maupun <strong>alumni lawas di luar sistem</strong> beserta kampus swasta/luar negeri.
                </p>
            </div>
        </div>
        <div class="d-flex items-center gap-2">
            <button class="btn btn-sm btn-outline-primary rounded-xl font-bold px-3 py-1.5 flex items-center gap-1.5 bg-white shadow-2xs" @click="fetchAllData()">
                <i class="bi bi-arrow-clockwise" :class="{'spin-anim': loadingKuliah || loadingPekerjaan}"></i> Refresh Data
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB NAVIGATION (Hanya jika bukan sub-module) -->
    <?php if (empty($is_sub_module)): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2">
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-bold px-3.5 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'kuliah'}"
                                @click="activeTab = 'kuliah'">
                            <i class="bi bi-mortarboard me-2 fs-6"></i> Riwayat Kuliah
                            <span class="badge bg-primary ms-1.5 rounded-pill">{{ riwayatKuliah.length }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-bold px-3.5 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'pekerjaan'}"
                                @click="activeTab = 'pekerjaan'">
                            <i class="bi bi-briefcase me-2 fs-6"></i> Riwayat Pekerjaan
                            <span class="badge bg-success ms-1.5 rounded-pill">{{ riwayatPekerjaan.length }}</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         TAB PANEL: RIWAYAT KULIAH
    ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'kuliah'" class="tracer-card p-4 space-y-4">
        
        <!-- Toolbar & Filter Kuliah -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 pb-2 border-b border-slate-100">
            <div class="d-flex items-center gap-2">
                <h5 class="fw-black text-slate-800 mb-0 fs-6 flex items-center gap-2">
                    <i class="bi bi-buildings text-primary"></i> Data Riwayat Pendidikan Tinggi Alumni
                </h5>
                <span class="badge bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded-lg border border-slate-200 text-xs">
                    {{ filteredKuliah.length }} Data
                </span>
            </div>
            <div class="d-flex items-center gap-2 flex-wrap">
                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text bg-white border-slate-200 text-slate-400"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-slate-200 text-xs" v-model="searchKuliah" placeholder="Cari alumni, kampus, prodi...">
                </div>
                <select class="form-select form-select-sm border-slate-200 text-xs w-auto" v-model="filterStatusKuliah">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Drop">Drop Out</option>
                </select>
                <button v-if="isAdmin" class="btn btn-sm btn-primary rounded-xl font-bold px-3 py-1.5 flex items-center gap-1.5 shadow-2xs" @click="openTambahKuliah()">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Riwayat Kuliah
                </button>
            </div>
        </div>

        <!-- Data Table Kuliah -->
        <div v-if="filteredKuliah.length > 0" class="table-responsive rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
            <table class="table table-hover align-middle mb-0 text-slate-700">
                <thead class="bg-slate-50 text-slate-500 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-3 text-center" style="width: 45px;">#</th>
                        <th v-if="isAdmin" class="py-3">Nama Alumni</th>
                        <th class="py-3">Perguruan Tinggi / Kampus</th>
                        <th class="py-3">Program Studi & Fakultas</th>
                        <th class="py-3 text-center">Jalur</th>
                        <th class="py-3 text-center">Tahun</th>
                        <th class="py-3 text-center">Status</th>
                        <th v-if="isAdmin" class="py-3 text-center" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    <tr v-for="(item, idx) in filteredKuliah" :key="item.id">
                        <td class="text-center text-slate-400 font-bold px-3">{{ idx + 1 }}</td>
                        <td v-if="isAdmin" class="py-2.5">
                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                {{ item.nama_lengkap }}
                                <span v-if="item.is_manual" class="badge bg-amber-50 text-amber-700 border border-amber-200 text-[9px] px-1.5 py-0.2 rounded font-bold" title="Diinput manual di luar database siswa">
                                    Luar Sistem
                                </span>
                                <span v-else class="badge bg-indigo-50 text-indigo-700 border border-indigo-100 text-[9px] px-1.5 py-0.2 rounded font-bold">
                                    Siswa Sistem
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono">NISN: {{ item.nisn_display || item.nisn || '—' }}</div>
                        </td>
                        <td class="py-2.5">
                            <div class="font-bold text-indigo-700 text-xs flex items-center gap-1">
                                <i class="bi bi-building text-indigo-500"></i> {{ item.nama_kampus_display || item.nama_kampus }}
                            </div>
                            <div v-if="item.is_kampus_swasta" class="text-[10px] text-amber-600 font-semibold flex items-center gap-1">
                                <i class="bi bi-patch-check"></i> Kampus Swasta / Mandiri
                            </div>
                        </td>
                        <td class="py-2.5">
                            <div class="font-semibold text-slate-800">{{ item.nama_prodi_display || item.nama_prodi || item.jurusan || '—' }}</div>
                            <div class="text-[10px] text-slate-500">{{ item.fakultas ? 'Fakultas ' + item.fakultas : (item.jenjang || 'S1') }}</div>
                        </td>
                        <td class="py-2.5 text-center">
                            <span class="badge bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-lg border border-slate-200">
                                {{ item.nama_jalur || '—' }}
                            </span>
                        </td>
                        <td class="py-2.5 text-center font-mono text-[11px]">
                            {{ item.tahun_masuk }} <span class="text-slate-400">s/d</span> {{ item.tahun_lulus || 'Aktif' }}
                        </td>
                        <td class="py-2.5 text-center">
                            <span class="tracer-badge"
                                  :class="{
                                      'status-aktif': item.status_kuliah === 'Aktif',
                                      'status-lulus': item.status_kuliah === 'Lulus',
                                      'status-drop':  item.status_kuliah === 'Drop'
                                  }">
                                <i class="bi" :class="item.status_kuliah === 'Lulus' ? 'bi-check-circle-fill' : (item.status_kuliah === 'Drop' ? 'bi-x-circle-fill' : 'bi-clock-fill')"></i>
                                {{ item.status_kuliah === 'Drop' ? 'Drop Out' : item.status_kuliah }}
                            </span>
                        </td>
                        <td v-if="isAdmin" class="py-2.5 text-center">
                            <div class="d-inline-flex items-center gap-1">
                                <button class="btn btn-sm btn-light text-primary hover:bg-primary-50 rounded-lg p-1.5"
                                        @click="openEditKuliah(item)"
                                        title="Edit data riwayat kuliah">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger hover:bg-rose-50 rounded-lg p-1.5"
                                        @click="hapusKuliah(item.id, item.nama_lengkap)"
                                        title="Hapus riwayat kuliah">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="empty-state bg-slate-50/60 rounded-2xl border border-dashed border-slate-200">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-2.5">
                <i class="bi bi-mortarboard fs-4"></i>
            </div>
            <div class="font-bold text-slate-700 text-sm">Belum Ada Riwayat Kuliah</div>
            <p class="text-xs text-slate-400 mb-3">Tambahkan data mahasiswa baru / alumni diterima perguruan tinggi melalui tombol di bawah.</p>
            <button v-if="isAdmin" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2" @click="openTambahKuliah()">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Riwayat Kuliah Baru
            </button>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════════════════════════════════
         TAB PANEL: RIWAYAT PEKERJAAN
    ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'pekerjaan'" class="tracer-card p-4 space-y-4">
        
        <!-- Toolbar & Filter Pekerjaan -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 pb-2 border-b border-slate-100">
            <div class="d-flex items-center gap-2">
                <h5 class="fw-black text-slate-800 mb-0 fs-6 flex items-center gap-2">
                    <i class="bi bi-briefcase text-emerald-600"></i> Data Riwayat Karir & Pekerjaan Alumni
                </h5>
                <span class="badge bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded-lg border border-slate-200 text-xs">
                    {{ filteredPekerjaan.length }} Data
                </span>
            </div>
            <div class="d-flex items-center gap-2 flex-wrap">
                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text bg-white border-slate-200 text-slate-400"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-slate-200 text-xs" v-model="searchPekerjaan" placeholder="Cari alumni, perusahaan, posisi...">
                </div>
                <select class="form-select form-select-sm border-slate-200 text-xs w-auto" v-model="filterStatusKerja">
                    <option value="">Semua Status</option>
                    <option value="Tetap">Tetap</option>
                    <option value="Kontrak">Kontrak</option>
                    <option value="Magang">Magang</option>
                    <option value="Wirausaha">Wirausaha</option>
                </select>
                <button v-if="isAdmin" class="btn btn-sm btn-success rounded-xl font-bold px-3 py-1.5 flex items-center gap-1.5 shadow-2xs" @click="openTambahPekerjaan()">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Riwayat Pekerjaan
                </button>
            </div>
        </div>

        <!-- Data Table Pekerjaan -->
        <div v-if="filteredPekerjaan.length > 0" class="table-responsive rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
            <table class="table table-hover align-middle mb-0 text-slate-700">
                <thead class="bg-slate-50 text-slate-500 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-3 text-center" style="width: 45px;">#</th>
                        <th v-if="isAdmin" class="py-3">Nama Alumni</th>
                        <th class="py-3">Perusahaan / Instansi</th>
                        <th class="py-3">Posisi / Jabatan</th>
                        <th class="py-3 text-center">Tahun Bekerja</th>
                        <th class="py-3 text-center">Pendapatan</th>
                        <th class="py-3 text-center">Status</th>
                        <th v-if="isAdmin" class="py-3 text-center" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    <tr v-for="(item, idx) in filteredPekerjaan" :key="item.id">
                        <td class="text-center text-slate-400 font-bold px-3">{{ idx + 1 }}</td>
                        <td v-if="isAdmin" class="py-2.5">
                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                {{ item.nama_lengkap }}
                                <span v-if="item.is_manual" class="badge bg-amber-50 text-amber-700 border border-amber-200 text-[9px] px-1.5 py-0.2 rounded font-bold" title="Diinput manual di luar database siswa">
                                    Luar Sistem
                                </span>
                                <span v-else class="badge bg-indigo-50 text-indigo-700 border border-indigo-100 text-[9px] px-1.5 py-0.2 rounded font-bold">
                                    Siswa Sistem
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono">NISN: {{ item.nisn_display || item.nisn || '—' }}</div>
                        </td>
                        <td class="py-2.5">
                            <div class="font-bold text-slate-800 text-xs flex items-center gap-1">
                                <i class="bi bi-buildings text-slate-400"></i> {{ item.nama_perusahaan }}
                            </div>
                            <div class="text-[10px] text-slate-400">{{ item.jenis_instansi || 'Perusahaan Swasta' }}</div>
                        </td>
                        <td class="py-2.5 font-semibold text-slate-800">
                            {{ item.posisi_jabatan }}
                        </td>
                        <td class="py-2.5 text-center font-mono text-[11px]">
                            {{ item.tahun_mulai }} <span class="text-slate-400">s/d</span> {{ item.tahun_selesai || 'Sekarang' }}
                        </td>
                        <td class="py-2.5 text-center text-slate-600 font-medium">
                            {{ item.pendapatan_bulanan || '—' }}
                        </td>
                        <td class="py-2.5 text-center">
                            <span class="tracer-badge"
                                  :class="{
                                      'status-tetap':   item.status_kerja === 'Tetap',
                                      'status-kontrak': item.status_kerja === 'Kontrak',
                                      'status-magang':  item.status_kerja === 'Magang',
                                      'status-usaha':   item.status_kerja === 'Wirausaha'
                                  }">
                                <i class="bi bi-briefcase-fill"></i> {{ item.status_kerja }}
                            </span>
                        </td>
                        <td v-if="isAdmin" class="py-2.5 text-center">
                            <div class="d-inline-flex items-center gap-1">
                                <button class="btn btn-sm btn-light text-primary hover:bg-primary-50 rounded-lg p-1.5"
                                        @click="openEditPekerjaan(item)"
                                        title="Edit data riwayat pekerjaan">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger hover:bg-rose-50 rounded-lg p-1.5"
                                        @click="hapusPekerjaan(item.id, item.nama_lengkap)"
                                        title="Hapus riwayat pekerjaan">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="empty-state bg-slate-50/60 rounded-2xl border border-dashed border-slate-200">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2.5">
                <i class="bi bi-briefcase fs-4"></i>
            </div>
            <div class="font-bold text-slate-700 text-sm">Belum Ada Riwayat Pekerjaan</div>
            <p class="text-xs text-slate-400 mb-3">Tambahkan data alumni bekerja atau berwirausaha melalui tombol di bawah.</p>
            <button v-if="isAdmin" class="btn btn-sm btn-success rounded-xl font-bold px-4 py-2" @click="openTambahPekerjaan()">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Riwayat Pekerjaan Baru
            </button>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL POP-UP: FORM RIWAYAT KULIAH (TAMBAH & EDIT)
    ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="modalFormKuliah.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); z-index: 1060; backdrop-filter: blur(6px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-3xl shadow-2xl bg-white overflow-hidden">
                <!-- Header Modal -->
                <div class="modal-header border-b border-slate-100 px-6 py-4 flex items-center justify-between bg-gradient-to-r from-slate-50 to-indigo-50/40">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-primary text-white flex items-center justify-center shadow-md flex-shrink-0">
                            <i class="bi" :class="modalFormKuliah.isEdit ? 'bi-pencil-square fs-5' : 'bi-mortarboard-fill fs-5'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-black text-slate-800 text-sm md:text-base mb-0">
                                {{ modalFormKuliah.isEdit ? 'Edit Riwayat Kuliah Alumni' : 'Tambah Riwayat Kuliah Baru' }}
                            </h5>
                            <p class="text-[11px] text-slate-500 mb-0">
                                {{ modalFormKuliah.isEdit ? 'Perbarui data perguruan tinggi atau program studi alumni' : 'Rekam data penerimaan perguruan tinggi atau alumni kuliah' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" @click="modalFormKuliah.show = false"></button>
                </div>

                <!-- Body Modal Form -->
                <div class="modal-body px-6 py-4 space-y-4 text-xs">
                    
                    <!-- 1. IDENTITAS ALUMNI SECTION -->
                    <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-2.5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-black uppercase text-slate-500 tracking-wider flex items-center gap-1.5">
                                <i class="bi bi-person-badge-fill text-primary"></i> 1. Identitas Alumni
                            </span>
                            <span v-if="modalFormKuliah.isEdit" class="badge bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded-md text-[10px]">
                                Mode Edit
                            </span>
                        </div>

                        <!-- Segmented Pill Selector for Alumni Source (Hanya saat Tambah Baru) -->
                        <div v-if="isAdmin && !modalFormKuliah.isEdit">
                            <div class="d-flex gap-2 p-1 bg-white rounded-xl border border-slate-200 shadow-2xs mb-2.5">
                                <button type="button" 
                                        class="btn btn-sm py-1.5 px-3 rounded-lg font-bold text-xs flex-1 flex items-center justify-center gap-2 transition-all border-0"
                                        :class="!modalFormKuliah.form.is_manual ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-800 bg-transparent'"
                                        @click="modalFormKuliah.form.is_manual = false; resetModalKuliahStudentSelection()">
                                    <i class="bi bi-database-check"></i>
                                    <span>Alumni Terdaftar di Sistem</span>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm py-1.5 px-3 rounded-lg font-bold text-xs flex-1 flex items-center justify-center gap-2 transition-all border-0"
                                        :class="modalFormKuliah.form.is_manual ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-800 bg-transparent'"
                                        @click="modalFormKuliah.form.is_manual = true; resetModalKuliahStudentSelection()">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Alumni Luar Sistem (Manual)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Display Alumni Name (Mode Edit) -->
                        <div v-if="modalFormKuliah.isEdit" class="bg-white p-3 rounded-xl border border-slate-200 d-flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">{{ modalFormKuliah.form.nama_lengkap || modalFormKuliah.form.nama_alumni }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">NISN: {{ modalFormKuliah.form.nisn || '—' }}</div>
                            </div>
                            <span v-if="modalFormKuliah.form.is_manual" class="badge bg-amber-50 text-amber-700 border border-amber-200 text-[10px] px-2 py-1 rounded-lg font-bold">
                                Alumni Luar Sistem
                            </span>
                            <span v-else class="badge bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] px-2 py-1 rounded-lg font-bold">
                                Siswa Sistem
                            </span>
                        </div>

                        <!-- Pemilihan Siswa Terdaftar (Mode Tambah) -->
                        <div v-if="!modalFormKuliah.isEdit && !modalFormKuliah.form.is_manual && isAdmin">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Cari Alumni di Database Siswa <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-slate-200 text-slate-400"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control rounded-r-xl text-xs border-slate-200" v-model="searchStudentQuery"
                                           @input="searchStudents('kuliah')" @focus="showSearchDropdown = true; activeForm = 'kuliah'"
                                           placeholder="Ketik nama lengkap atau NISN siswa..." autocomplete="off">
                                </div>
                                <div v-if="showSearchDropdown && activeForm === 'kuliah' && searchResults.length > 0" 
                                     class="dropdown-menu show w-100 position-absolute overflow-auto shadow-xl rounded-2xl border-slate-200 mt-1.5 p-1 bg-white" style="max-height: 200px; z-index: 9999;">
                                    <button type="button" class="dropdown-item py-2 px-3 rounded-xl border-0 text-xs hover:bg-indigo-50 text-left transition" 
                                            v-for="s in searchResults" :key="s.id" @mousedown.prevent="selectStudentForModal(s, 'kuliah')">
                                        <div class="fw-bold text-slate-800">{{ s.nama_lengkap }}</div>
                                        <div class="text-[10px] text-slate-400">NISN: {{ s.nisn || '-' }} | Kelas: {{ s.kelas_saat_ini || '-' }} ({{ s.jurusan || '-' }})</div>
                                    </button>
                                </div>
                            </div>
                            <div v-if="selectedStudent && activeForm === 'kuliah'" class="mt-2 p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-emerald-600 fs-5"></i>
                                    <div>
                                        <span class="d-block fw-bold text-emerald-900 text-xs">{{ selectedStudent.nama_lengkap }}</span>
                                        <span class="text-emerald-700 text-[10px]">NISN: {{ selectedStudent.nisn || '-' }} | Terpilih dari database siswa</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-emerald font-bold px-2 py-1 rounded-lg" @click="selectedStudent = null; modalFormKuliah.form.siswa_id = ''">Ganti</button>
                            </div>
                        </div>

                        <!-- Input Manual Alumni Luar Sistem (Mode Tambah) -->
                        <div v-if="!modalFormKuliah.isEdit && modalFormKuliah.form.is_manual && isAdmin" class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Nama Lengkap Alumni <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.nama_alumni" placeholder="Contoh: Budi Santoso" autocomplete="off">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">NISN / NIS (Opsional)</label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.nisn" placeholder="Nomor Induk Siswa..." autocomplete="off">
                            </div>
                        </div>
                    </div>


                    <!-- 2. PERGURUAN TINGGI & PROGRAM STUDI SECTION -->
                    <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-2.5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-black uppercase text-slate-500 tracking-wider flex items-center gap-1.5">
                                <i class="bi bi-buildings text-primary"></i> 2. Perguruan Tinggi & Program Studi
                            </span>
                        </div>

                        <!-- Segmented Pill Selector for Kampus Type -->
                        <div class="d-flex gap-2 p-1 bg-white rounded-xl border border-slate-200 shadow-2xs mb-2.5">
                            <button type="button" 
                                    class="btn btn-sm py-1.5 px-3 rounded-lg font-bold text-xs flex-1 flex items-center justify-center gap-2 transition-all border-0"
                                    :class="!modalFormKuliah.form.is_kampus_swasta ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-800 bg-transparent'"
                                    @click="modalFormKuliah.form.is_kampus_swasta = false; resetModalKampusSelection()">
                                <i class="bi bi-building-check"></i>
                                <span>Pilih dari Master PDSS / PTN</span>
                            </button>
                            <button type="button" 
                                    class="btn btn-sm py-1.5 px-3 rounded-lg font-bold text-xs flex-1 flex items-center justify-center gap-2 transition-all border-0"
                                    :class="modalFormKuliah.form.is_kampus_swasta ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-800 bg-transparent'"
                                    @click="modalFormKuliah.form.is_kampus_swasta = true; resetModalKampusSelection()">
                                <i class="bi bi-input-cursor-text"></i>
                                <span>Ketik Manual (Swasta / Luar Negeri)</span>
                            </button>
                        </div>

                        <!-- Dropdown PDSS Master Kampus -->
                        <div v-if="!modalFormKuliah.form.is_kampus_swasta" class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Perguruan Tinggi <span class="text-danger">*</span></label>
                                <select class="form-select rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.kampus_id" @change="onModalKampusChange()">
                                    <option value="">-- Pilih Perguruan Tinggi (PDSS) --</option>
                                    <option v-for="k in listKampusFlat" :key="k.id" :value="k.id">
                                        {{ k.nama_kampus }} ({{ k.jenis_kampus || 'PTN' }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Program Studi <span class="text-danger">*</span></label>
                                <select class="form-select rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.prodi_id" @change="onModalProdiChange()">
                                    <option value="">-- Pilih Program Studi --</option>
                                    <option v-for="p in listProdiByModalKampus" :key="p.id" :value="p.id">
                                        {{ p.program_studi || p.nama_prodi }} ({{ p.jenjang || 'S1' }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Input Manual Kampus Swasta -->
                        <div v-else class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Nama Perguruan Tinggi Swasta / Luar Negeri <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.nama_kampus" placeholder="Contoh: Universitas Telkom / Monash University">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Program Studi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.nama_prodi" placeholder="Contoh: Sistem Informasi / Teknik Informatika">
                            </div>
                        </div>

                        <div class="row g-2 pt-1">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Jalur Masuk / Seleksi</label>
                                <select class="form-select rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.jalur_masuk_id">
                                    <option value="">-- Pilih Jalur Masuk --</option>
                                    <option v-for="j in listJalur" :key="j.id" :value="j.id">{{ j.nama_jalur }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Fakultas (Opsional)</label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.fakultas" placeholder="Contoh: Teknik / Ekonomi">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Jenjang Pendidikan</label>
                                <select class="form-select rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.jenjang">
                                    <option value="S1">S1 (Sarjana)</option>
                                    <option value="D4">D4 (Sarjana Terapan)</option>
                                    <option value="D3">D3 (Diploma Tiga)</option>
                                    <option value="S2">S2 (Magister)</option>
                                </select>
                            </div>
                        </div>
                    </div>


                    <!-- 3. PERIODE & STATUS KULIAH SECTION -->
                    <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-2.5">
                        <span class="text-[11px] font-black uppercase text-slate-500 tracking-wider flex items-center gap-1.5 mb-1">
                            <i class="bi bi-clock-history text-primary"></i> 3. Periode & Status Kelulusan
                        </span>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Tahun Masuk <span class="text-danger">*</span></label>
                                <input type="number" class="form-control rounded-xl text-xs border-slate-200" v-model.number="modalFormKuliah.form.tahun_masuk" :min="1990" :max="currentYear + 1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Tahun Lulus</label>
                                <input type="number" class="form-control rounded-xl text-xs border-slate-200" v-model.number="modalFormKuliah.form.tahun_lulus" :min="modalFormKuliah.form.tahun_masuk" :max="currentYear + 7" placeholder="Kosongkan jika aktif">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Status Kuliah <span class="text-danger">*</span></label>
                                <select class="form-select rounded-xl text-xs border-slate-200" v-model="modalFormKuliah.form.status_kuliah">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Lulus">Lulus</option>
                                    <option value="Drop">Drop Out</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Modal -->
                <div class="modal-footer border-t border-slate-100 px-6 py-4 flex items-center justify-end gap-2 bg-slate-50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-bold px-4 text-slate-600" @click="modalFormKuliah.show = false">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 flex items-center gap-1.5 shadow-sm" :disabled="modalFormKuliah.saving" @click="submitModalKuliah()">
                        <span v-if="modalFormKuliah.saving" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-save"></i> {{ modalFormKuliah.isEdit ? 'Simpan Perubahan' : 'Simpan Riwayat Kuliah' }}
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL POP-UP: FORM RIWAYAT PEKERJAAN (TAMBAH & EDIT)
    ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="modalFormPekerjaan.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); z-index: 1060; backdrop-filter: blur(6px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-3xl shadow-2xl bg-white overflow-hidden">
                <!-- Header Modal -->
                <div class="modal-header border-b border-slate-100 px-6 py-4 flex items-center justify-between bg-gradient-to-r from-slate-50 to-emerald-50/40">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-md flex-shrink-0">
                            <i class="bi" :class="modalFormPekerjaan.isEdit ? 'bi-pencil-square fs-5' : 'bi-briefcase-fill fs-5'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-black text-slate-800 text-sm md:text-base mb-0">
                                {{ modalFormPekerjaan.isEdit ? 'Edit Riwayat Pekerjaan Alumni' : 'Tambah Riwayat Pekerjaan Baru' }}
                            </h5>
                            <p class="text-[11px] text-slate-500 mb-0">
                                {{ modalFormPekerjaan.isEdit ? 'Perbarui data instansi atau jabatan karir alumni' : 'Rekam jejak karir, perusahaan, atau wirausaha alumni' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" @click="modalFormPekerjaan.show = false"></button>
                </div>

                <!-- Body Modal Form -->
                <div class="modal-body px-6 py-4 space-y-4 text-xs">
                    
                    <!-- 1. IDENTITAS ALUMNI SECTION -->
                    <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-2.5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-black uppercase text-slate-500 tracking-wider flex items-center gap-1.5">
                                <i class="bi bi-person-badge-fill text-emerald-600"></i> 1. Identitas Alumni
                            </span>
                            <span v-if="modalFormPekerjaan.isEdit" class="badge bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-md text-[10px]">
                                Mode Edit
                            </span>
                        </div>

                        <!-- Segmented Pill Selector for Alumni Source (Hanya saat Tambah Baru) -->
                        <div v-if="isAdmin && !modalFormPekerjaan.isEdit">
                            <div class="d-flex gap-2 p-1 bg-white rounded-xl border border-slate-200 shadow-2xs mb-2.5">
                                <button type="button" 
                                        class="btn btn-sm py-1.5 px-3 rounded-lg font-bold text-xs flex-1 flex items-center justify-center gap-2 transition-all border-0"
                                        :class="!modalFormPekerjaan.form.is_manual ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-800 bg-transparent'"
                                        @click="modalFormPekerjaan.form.is_manual = false; resetModalPekerjaanStudentSelection()">
                                    <i class="bi bi-database-check"></i>
                                    <span>Alumni Terdaftar di Sistem</span>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm py-1.5 px-3 rounded-lg font-bold text-xs flex-1 flex items-center justify-center gap-2 transition-all border-0"
                                        :class="modalFormPekerjaan.form.is_manual ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-800 bg-transparent'"
                                        @click="modalFormPekerjaan.form.is_manual = true; resetModalPekerjaanStudentSelection()">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Alumni Luar Sistem (Manual)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Display Alumni Name (Mode Edit) -->
                        <div v-if="modalFormPekerjaan.isEdit" class="bg-white p-3 rounded-xl border border-slate-200 d-flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">{{ modalFormPekerjaan.form.nama_lengkap || modalFormPekerjaan.form.nama_alumni }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">NISN: {{ modalFormPekerjaan.form.nisn || '—' }}</div>
                            </div>
                            <span v-if="modalFormPekerjaan.form.is_manual" class="badge bg-amber-50 text-amber-700 border border-amber-200 text-[10px] px-2 py-1 rounded-lg font-bold">
                                Alumni Luar Sistem
                            </span>
                            <span v-else class="badge bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] px-2 py-1 rounded-lg font-bold">
                                Siswa Sistem
                            </span>
                        </div>

                        <!-- Pemilihan Siswa Terdaftar (Mode Tambah) -->
                        <div v-if="!modalFormPekerjaan.isEdit && !modalFormPekerjaan.form.is_manual && isAdmin">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Cari Alumni di Database Siswa <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-slate-200 text-slate-400"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control rounded-r-xl text-xs border-slate-200" v-model="searchStudentQueryPekerjaan"
                                           @input="searchStudents('pekerjaan')" @focus="showSearchDropdown = true; activeForm = 'pekerjaan'"
                                           placeholder="Ketik nama lengkap atau NISN siswa lulus..." autocomplete="off">
                                </div>
                                <div v-if="showSearchDropdown && activeForm === 'pekerjaan' && searchResults.length > 0" 
                                     class="dropdown-menu show w-100 position-absolute overflow-auto shadow-xl rounded-2xl border-slate-200 mt-1.5 p-1 bg-white" style="max-height: 200px; z-index: 9999;">
                                    <button type="button" class="dropdown-item py-2 px-3 rounded-xl border-0 text-xs hover:bg-emerald-50 text-left transition" 
                                            v-for="s in searchResults" :key="s.id" @mousedown.prevent="selectStudentForModal(s, 'pekerjaan')">
                                        <div class="fw-bold text-slate-800">{{ s.nama_lengkap }}</div>
                                        <div class="text-[10px] text-slate-400">NISN: {{ s.nisn || '-' }} | Kelas: {{ s.kelas_saat_ini || '-' }}</div>
                                    </button>
                                </div>
                            </div>
                            <div v-if="selectedStudentPekerjaan && activeForm === 'pekerjaan'" class="mt-2 p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-emerald-600 fs-5"></i>
                                    <div>
                                        <span class="d-block fw-bold text-emerald-900 text-xs">{{ selectedStudentPekerjaan.nama_lengkap }}</span>
                                        <span class="text-emerald-700 text-[10px]">NISN: {{ selectedStudentPekerjaan.nisn || '-' }} | Terpilih dari database siswa</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-emerald font-bold px-2 py-1 rounded-lg" @click="selectedStudentPekerjaan = null; modalFormPekerjaan.form.siswa_id = ''">Ganti</button>
                            </div>
                        </div>

                        <!-- Input Manual Alumni Luar Sistem (Mode Tambah) -->
                        <div v-if="!modalFormPekerjaan.isEdit && modalFormPekerjaan.form.is_manual && isAdmin" class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Nama Lengkap Alumni <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormPekerjaan.form.nama_alumni" placeholder="Contoh: Siti Rahmawati" autocomplete="off">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">NISN / NIS (Opsional)</label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormPekerjaan.form.nisn" placeholder="Nomor Induk Siswa..." autocomplete="off">
                            </div>
                        </div>
                    </div>


                    <!-- 2. INFORMASI PERUSAHAAN & KARIR SECTION -->
                    <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-2.5">
                        <span class="text-[11px] font-black uppercase text-slate-500 tracking-wider flex items-center gap-1.5 mb-1">
                            <i class="bi bi-buildings text-emerald-600"></i> 2. Informasi Perusahaan & Karir
                        </span>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Nama Perusahaan / Tempat Kerja <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormPekerjaan.form.nama_perusahaan" placeholder="Contoh: PT. Telkom Indonesia">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Posisi / Jabatan Pekerjaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-xl text-xs border-slate-200" v-model="modalFormPekerjaan.form.posisi_jabatan" placeholder="Contoh: Junior Software Developer">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Jenis Instansi</label>
                                <select class="form-select rounded-xl text-xs border-slate-200" v-model="modalFormPekerjaan.form.jenis_instansi">
                                    <option value="Swasta">Perusahaan Swasta / Multinasional</option>
                                    <option value="BUMN">BUMN / BUMD</option>
                                    <option value="Pemerintah">Pemerintah / Instansi Negeri</option>
                                    <option value="Wirausaha">Wirausaha / Bisnis Mandiri</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Rentang Pendapatan Bulanan</label>
                                <select class="form-select rounded-xl text-xs border-slate-200" v-model="modalFormPekerjaan.form.pendapatan_bulanan">
                                    <option value="">-- Pilih Rentang Pendapatan --</option>
                                    <option value="< 3 Juta">< Rp 3.000.000</option>
                                    <option value="3 - 5 Juta">Rp 3.000.000 - Rp 5.000.000</option>
                                    <option value="5 - 10 Juta">Rp 5.000.000 - Rp 10.000.000</option>
                                    <option value="> 10 Juta">> Rp 10.000.000</option>
                                </select>
                            </div>
                        </div>
                    </div>


                    <!-- 3. PERIODE & STATUS KERJA SECTION -->
                    <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-2.5">
                        <span class="text-[11px] font-black uppercase text-slate-500 tracking-wider flex items-center gap-1.5 mb-1">
                            <i class="bi bi-clock-history text-emerald-600"></i> 3. Periode & Status Karir
                        </span>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Tahun Mulai Bekerja <span class="text-danger">*</span></label>
                                <input type="number" class="form-control rounded-xl text-xs border-slate-200" v-model.number="modalFormPekerjaan.form.tahun_mulai" :min="1990" :max="currentYear + 1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Tahun Selesai</label>
                                <input type="number" class="form-control rounded-xl text-xs border-slate-200" v-model.number="modalFormPekerjaan.form.tahun_selesai" :min="modalFormPekerjaan.form.tahun_mulai" :max="currentYear + 10" placeholder="Kosongkan jika aktif">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Status Kerja <span class="text-danger">*</span></label>
                                <select class="form-select rounded-xl text-xs border-slate-200" v-model="modalFormPekerjaan.form.status_kerja">
                                    <option value="Tetap">Karyawan Tetap</option>
                                    <option value="Kontrak">Karyawan Kontrak</option>
                                    <option value="Magang">Magang / Internship</option>
                                    <option value="Wirausaha">Wirausaha / Freelance</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Modal -->
                <div class="modal-footer border-t border-slate-100 px-6 py-4 flex items-center justify-end gap-2 bg-slate-50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-bold px-4 text-slate-600" @click="modalFormPekerjaan.show = false">Batal</button>
                    <button type="button" class="btn btn-sm btn-success rounded-xl font-bold px-4 flex items-center gap-1.5 shadow-sm" :disabled="modalFormPekerjaan.saving" @click="submitModalPekerjaan()">
                        <span v-if="modalFormPekerjaan.saving" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-save"></i> {{ modalFormPekerjaan.isEdit ? 'Simpan Perubahan' : 'Simpan Riwayat Pekerjaan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

                    <!-- Display Alumni Name (Mode Edit) -->
                    <div v-if="modalFormPekerjaan.isEdit" class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Nama Alumni:</span>
                        <span class="font-bold text-slate-800 text-sm">{{ modalFormPekerjaan.form.nama_lengkap || modalFormPekerjaan.form.nama_alumni }}</span>
                    </div>

                    <!-- Pemilihan Siswa (Mode Tambah Baru) -->
                    <div class="row g-3" v-if="!modalFormPekerjaan.isEdit && isAdmin">
                        <div class="col-md-12">
                            <div v-if="!modalFormPekerjaan.form.is_manual">
                                <label class="form-label fw-bold text-xs text-slate-700 mb-1">Cari Alumni Terdaftar di Sistem <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <input type="text" class="form-control rounded-xl text-xs" v-model="searchStudentQueryPekerjaan"
                                           @input="searchStudents('pekerjaan')" @focus="showSearchDropdown = true; activeForm = 'pekerjaan'"
                                           placeholder="Ketik nama lengkap atau NISN siswa lulus..." autocomplete="off">
                                    <div v-if="showSearchDropdown && activeForm === 'pekerjaan' && searchResults.length > 0" 
                                         class="dropdown-menu show w-100 position-absolute overflow-auto shadow-lg rounded-xl border-slate-200 mt-1" style="max-height: 200px; z-index: 9999;">
                                        <button type="button" class="dropdown-item py-2 px-3 border-bottom text-xs" v-for="s in searchResults" :key="s.id" @mousedown.prevent="selectStudentForModal(s, 'pekerjaan')">
                                            <div class="fw-bold text-slate-800">{{ s.nama_lengkap }}</div>
                                            <div class="text-[10px] text-slate-400">NISN: {{ s.nisn || '-' }} | Kelas: {{ s.kelas_saat_ini || '-' }}</div>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="selectedStudentPekerjaan && activeForm === 'pekerjaan'" class="mt-2 p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-check-circle-fill text-emerald-600 fs-6"></i>
                                        <div>
                                            <span class="d-block fw-bold text-emerald-900 text-xs">{{ selectedStudentPekerjaan.nama_lengkap }}</span>
                                            <span class="text-emerald-700 text-[10px]">NISN: {{ selectedStudentPekerjaan.nisn || '-' }} | Siswa Sistem</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-link text-emerald-800 font-bold p-0 text-decoration-none" @click="selectedStudentPekerjaan = null; modalFormPekerjaan.form.siswa_id = ''">Ganti</button>
                                </div>
                            </div>
                            <div v-else class="row g-2">
                                <div class="col-md-7">
                                    <label class="form-label fw-bold text-xs text-slate-700 mb-1">Nama Lengkap Alumni Luar Sistem <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control rounded-xl text-xs" v-model="modalFormPekerjaan.form.nama_alumni" placeholder="Contoh: Siti Rahmawati" autocomplete="off">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold text-xs text-slate-700 mb-1">NISN / NIS (Opsional)</label>
                                    <input type="text" class="form-control rounded-xl text-xs" v-model="modalFormPekerjaan.form.nisn" placeholder="Nomor Induk Siswa..." autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perusahaan & Posisi -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Nama Perusahaan / Tempat Kerja <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-xl text-xs" v-model="modalFormPekerjaan.form.nama_perusahaan" placeholder="Contoh: PT. Telkom Indonesia">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Posisi / Jabatan Pekerjaan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-xl text-xs" v-model="modalFormPekerjaan.form.posisi_jabatan" placeholder="Contoh: Junior Software Developer">
                        </div>

                        <!-- Jenis Instansi & Pendapatan -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Jenis Instansi</label>
                            <select class="form-select rounded-xl text-xs" v-model="modalFormPekerjaan.form.jenis_instansi">
                                <option value="Swasta">Perusahaan Swasta / Multinasional</option>
                                <option value="BUMN">BUMN / BUMD</option>
                                <option value="Pemerintah">Pemerintah / Instansi Negeri</option>
                                <option value="Wirausaha">Wirausaha / Bisnis Mandiri</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Rentang Pendapatan Bulanan</label>
                            <select class="form-select rounded-xl text-xs" v-model="modalFormPekerjaan.form.pendapatan_bulanan">
                                <option value="">-- Pilih Rentang Pendapatan --</option>
                                <option value="< 3 Juta">< Rp 3.000.000</option>
                                <option value="3 - 5 Juta">Rp 3.000.000 - Rp 5.000.000</option>
                                <option value="5 - 10 Juta">Rp 5.000.000 - Rp 10.000.000</option>
                                <option value="> 10 Juta">> Rp 10.000.000</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Status Kerja <span class="text-danger">*</span></label>
                            <select class="form-select rounded-xl text-xs" v-model="modalFormPekerjaan.form.status_kerja">
                                <option value="Tetap">Karyawan Tetap</option>
                                <option value="Kontrak">Karyawan Kontrak</option>
                                <option value="Magang">Magang / Internship</option>
                                <option value="Wirausaha">Wirausaha / Freelance</option>
                            </select>
                        </div>

                        <!-- Tahun Mulai & Selesai -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Tahun Mulai Bekerja <span class="text-danger">*</span></label>
                            <input type="number" class="form-control rounded-xl text-xs" v-model.number="modalFormPekerjaan.form.tahun_mulai" :min="1990" :max="currentYear + 1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-xs text-slate-700 mb-1">Tahun Selesai</label>
                            <input type="number" class="form-control rounded-xl text-xs" v-model.number="modalFormPekerjaan.form.tahun_selesai" :min="modalFormPekerjaan.form.tahun_mulai" :max="currentYear + 10" placeholder="Kosongkan jika masih aktif bekerja">
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="modal-footer border-t border-slate-100 px-6 py-4 flex items-center justify-end gap-2 bg-slate-50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-bold px-4" @click="modalFormPekerjaan.show = false">Batal</button>
                    <button type="button" class="btn btn-sm btn-success rounded-xl font-bold px-4 flex items-center gap-1.5 shadow-xs" :disabled="modalFormPekerjaan.saving" @click="submitModalPekerjaan()">
                        <span v-if="modalFormPekerjaan.saving" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-save"></i> {{ modalFormPekerjaan.isEdit ? 'Simpan Perubahan' : 'Simpan Riwayat Pekerjaan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Vue.js 3 Script Setup -->
<script>
{
    const { ref, computed, onMounted } = Vue;

    window.VueAppRegistry.register(<?= json_encode($tracer_vue_selector, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, {
        setup() {
            const activeTab        = ref(<?= json_encode($tracer_initial_tab, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const currentYear      = ref(new Date().getFullYear());
            const loadingKuliah    = ref(false);
            const loadingPekerjaan = ref(false);

            const riwayatKuliah    = ref([]);
            const riwayatPekerjaan = ref([]);
            const listJalur        = ref([]);
            const listKampusFlat   = ref([]);
            const listProdiByKampus = ref({});

            const searchKuliah       = ref('');
            const filterStatusKuliah = ref('');
            const searchPekerjaan    = ref('');
            const filterStatusKerja  = ref('');

            const userRole = ref(<?= json_encode($userRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const isAdmin  = ref(<?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);

            // Modal Form Kuliah State (Unified for Tambah & Edit)
            const modalFormKuliah = ref({
                show: false,
                isEdit: false,
                saving: false,
                form: {
                    id: '',
                    is_manual: false,
                    is_kampus_swasta: false,
                    siswa_id: '',
                    nama_lengkap: '',
                    nama_alumni: '',
                    nisn: '',
                    kampus_id: '',
                    prodi_id: '',
                    nama_kampus: '',
                    nama_prodi: '',
                    fakultas: '',
                    jenjang: 'S1',
                    jalur_masuk_id: '',
                    tahun_masuk: new Date().getFullYear(),
                    tahun_lulus: null,
                    status_kuliah: 'Aktif'
                }
            });

            // Modal Form Pekerjaan State (Unified for Tambah & Edit)
            const modalFormPekerjaan = ref({
                show: false,
                isEdit: false,
                saving: false,
                form: {
                    id: '',
                    is_manual: false,
                    siswa_id: '',
                    nama_lengkap: '',
                    nama_alumni: '',
                    nisn: '',
                    nama_perusahaan: '',
                    posisi_jabatan: '',
                    jenis_instansi: 'Swasta',
                    pendapatan_bulanan: '',
                    tahun_mulai: new Date().getFullYear(),
                    tahun_selesai: null,
                    status_kerja: 'Kontrak'
                }
            });

            // Autocomplete Siswa
            const showSearchDropdown = ref(false);
            const searchResults = ref([]);
            const searchStudentQuery = ref('');
            const searchStudentQueryPekerjaan = ref('');
            const selectedStudent = ref(null);
            const selectedStudentPekerjaan = ref(null);
            const activeForm = ref('');

            const urlParams = new URLSearchParams(window.location.search);
            const tenantId  = urlParams.get('tenant_id') || <?= json_encode($tenantId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> || '';

            // Computed Filters
            const filteredKuliah = computed(() => {
                let res = riwayatKuliah.value || [];
                if (filterStatusKuliah.value) {
                    res = res.filter(r => r.status_kuliah === filterStatusKuliah.value);
                }
                if (searchKuliah.value.trim()) {
                    const q = searchKuliah.value.toLowerCase().trim();
                    res = res.filter(r => 
                        (r.nama_lengkap || '').toLowerCase().includes(q) ||
                        (r.nama_kampus || '').toLowerCase().includes(q) ||
                        (r.nama_prodi || '').toLowerCase().includes(q) ||
                        (r.nisn_display || '').includes(q)
                    );
                }
                return res;
            });

            const filteredPekerjaan = computed(() => {
                let res = riwayatPekerjaan.value || [];
                if (filterStatusKerja.value) {
                    res = res.filter(r => r.status_kerja === filterStatusKerja.value);
                }
                if (searchPekerjaan.value.trim()) {
                    const q = searchPekerjaan.value.toLowerCase().trim();
                    res = res.filter(r => 
                        (r.nama_lengkap || '').toLowerCase().includes(q) ||
                        (r.nama_perusahaan || '').toLowerCase().includes(q) ||
                        (r.posisi_jabatan || '').toLowerCase().includes(q) ||
                        (r.nisn_display || '').includes(q)
                    );
                }
                return res;
            });

            const listProdiByModalKampus = computed(() => {
                if (!modalFormKuliah.value.form.kampus_id) return [];
                return listProdiByKampus.value[modalFormKuliah.value.form.kampus_id] || [];
            });

            // Master Data API
            async function fetchMasterData() {
                try {
                    const resJalur = await axios.get(`<?= $baseUrl ?>/api/v1/kampus/jalur?tenant_id=${tenantId}`);
                    if (resJalur.data?.success) listJalur.value = resJalur.data.data;

                    const resKampus = await axios.get(`<?= $baseUrl ?>/api/v1/kampus/master?tenant_id=${tenantId}`);
                    if (resKampus.data?.success) listKampusFlat.value = resKampus.data.data;
                } catch (e) {
                    console.error("Gagal load master data kampus/jalur:", e);
                }
            }

            async function onModalKampusChange() {
                const kid = modalFormKuliah.value.form.kampus_id;
                modalFormKuliah.value.form.prodi_id = '';
                if (!kid) return;

                const k = listKampusFlat.value.find(x => x.id === kid);
                if (k) modalFormKuliah.value.form.nama_kampus = k.nama_kampus;

                if (!listProdiByKampus.value[kid]) {
                    try {
                        const res = await axios.get(`<?= $baseUrl ?>/api/v1/kampus/prodi?kampus_id=${kid}&tenant_id=${tenantId}`);
                        if (res.data?.success) {
                            listProdiByKampus.value[kid] = res.data.data;
                        }
                    } catch (e) {
                        console.error("Gagal load prodi:", e);
                    }
                }
            }

            function onModalProdiChange() {
                const pid = modalFormKuliah.value.form.prodi_id;
                const prodis = listProdiByModalKampus.value;
                const p = prodis.find(x => x.id === pid);
                if (p) {
                    modalFormKuliah.value.form.nama_prodi = p.program_studi || p.nama_prodi;
                    modalFormKuliah.value.form.jenjang = p.jenjang || 'S1';
                    if (p.fakultas) modalFormKuliah.value.form.fakultas = p.fakultas;
                }
            }

            function resetModalKampusSelection() {
                modalFormKuliah.value.form.kampus_id = '';
                modalFormKuliah.value.form.prodi_id = '';
                modalFormKuliah.value.form.nama_kampus = '';
                modalFormKuliah.value.form.nama_prodi = '';
                modalFormKuliah.value.form.fakultas = '';
            }

            function resetModalKuliahStudentSelection() {
                modalFormKuliah.value.form.siswa_id = '';
                modalFormKuliah.value.form.nama_alumni = '';
                modalFormKuliah.value.form.nisn = '';
                selectedStudent.value = null;
                searchStudentQuery.value = '';
            }

            function resetModalPekerjaanStudentSelection() {
                modalFormPekerjaan.value.form.siswa_id = '';
                modalFormPekerjaan.value.form.nama_alumni = '';
                modalFormPekerjaan.value.form.nisn = '';
                selectedStudentPekerjaan.value = null;
                searchStudentQueryPekerjaan.value = '';
            }

            // Fetch Riwayat API
            async function fetchRiwayat(type) {
                if (type === 'kuliah') loadingKuliah.value = true;
                else loadingPekerjaan.value = true;

                try {
                    const res = await axios.get(`<?= $baseUrl ?>/api/v1/tracer/${type}?tenant_id=${tenantId}`);
                    if (res.data?.success) {
                        if (type === 'kuliah') riwayatKuliah.value = res.data.data;
                        else riwayatPekerjaan.value = res.data.data;
                    }
                } catch (e) {
                    console.error("Gagal load data " + type, e);
                } finally {
                    if (type === 'kuliah') loadingKuliah.value = false;
                    else loadingPekerjaan.value = false;
                }
            }

            function fetchAllData() {
                fetchRiwayat('kuliah');
                fetchRiwayat('pekerjaan');
            }

            onMounted(() => {
                fetchMasterData();
                fetchAllData();
            });

            // Autocomplete Siswa
            async function searchStudents(formType) {
                activeForm.value = formType;
                const query = formType === 'kuliah' ? searchStudentQuery.value : searchStudentQueryPekerjaan.value;
                if (query.trim().length < 2) {
                    searchResults.value = [];
                    showSearchDropdown.value = false;
                    return;
                }
                try {
                    const res = await axios.get(`<?= $baseUrl ?>/api/v1/pdss/students/search?q=${encodeURIComponent(query)}&tenant_id=${tenantId}`);
                    if (res.data?.success) {
                        searchResults.value = res.data.data || [];
                        showSearchDropdown.value = true;
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            function selectStudentForModal(student, formType) {
                if (formType === 'kuliah') {
                    selectedStudent.value = student;
                    modalFormKuliah.value.form.siswa_id = student.id;
                    modalFormKuliah.value.form.nama_alumni = student.nama_lengkap;
                    modalFormKuliah.value.form.nisn = student.nisn || '';
                    searchStudentQuery.value = student.nama_lengkap;
                } else {
                    selectedStudentPekerjaan.value = student;
                    modalFormPekerjaan.value.form.siswa_id = student.id;
                    modalFormPekerjaan.value.form.nama_alumni = student.nama_lengkap;
                    modalFormPekerjaan.value.form.nisn = student.nisn || '';
                    searchStudentQueryPekerjaan.value = student.nama_lengkap;
                }
                showSearchDropdown.value = false;
                searchResults.value = [];
            }

            // Open Modal Tambah Kuliah
            function openTambahKuliah() {
                resetModalKuliahStudentSelection();
                modalFormKuliah.value = {
                    show: true,
                    isEdit: false,
                    saving: false,
                    form: {
                        id: '',
                        is_manual: false,
                        is_kampus_swasta: false,
                        siswa_id: '',
                        nama_lengkap: '',
                        nama_alumni: '',
                        nisn: '',
                        kampus_id: '',
                        prodi_id: '',
                        nama_kampus: '',
                        nama_prodi: '',
                        fakultas: '',
                        jenjang: 'S1',
                        jalur_masuk_id: '',
                        tahun_masuk: new Date().getFullYear(),
                        tahun_lulus: null,
                        status_kuliah: 'Aktif'
                    }
                };
            }

            // Open Modal Edit Kuliah
            function openEditKuliah(item) {
                modalFormKuliah.value = {
                    show: true,
                    isEdit: true,
                    saving: false,
                    form: {
                        id: item.id,
                        is_manual: Boolean(item.is_manual),
                        is_kampus_swasta: Boolean(item.is_kampus_swasta) || !item.kampus_id,
                        siswa_id: item.siswa_id || '',
                        nama_lengkap: item.nama_lengkap,
                        nama_alumni: item.nama_alumni,
                        nisn: item.nisn,
                        kampus_id: item.kampus_id || '',
                        prodi_id: item.prodi_id || '',
                        nama_kampus: item.nama_kampus_display || item.nama_kampus || '',
                        nama_prodi: item.nama_prodi_display || item.nama_prodi || item.jurusan || '',
                        fakultas: item.fakultas || '',
                        jenjang: item.jenjang || 'S1',
                        jalur_masuk_id: item.jalur_masuk_id || '',
                        tahun_masuk: item.tahun_masuk,
                        tahun_lulus: item.tahun_lulus,
                        status_kuliah: item.status_kuliah || 'Aktif'
                    }
                };
                if (item.kampus_id) {
                    onModalKampusChange();
                }
            }

            // Submit Modal Kuliah (Create or Update)
            async function submitModalKuliah() {
                const isEdit = modalFormKuliah.value.isEdit;
                const form = modalFormKuliah.value.form;

                if (!isEdit && isAdmin.value && !form.is_manual && !form.siswa_id) {
                    Swal.fire({ icon: 'warning', title: 'Pilih Siswa', text: 'Silakan cari dan pilih siswa terdaftar, atau aktifkan "Input Alumni Luar Sistem".' });
                    return;
                }
                if (!isEdit && form.is_manual && !form.nama_alumni.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Nama Alumni Wajib', text: 'Silakan isi nama lengkap alumni luar sistem.' });
                    return;
                }
                if (!form.nama_kampus.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Kampus Wajib', text: 'Silakan pilih kampus atau ketik nama kampus.' });
                    return;
                }

                modalFormKuliah.value.saving = true;
                const endpoint = isEdit 
                    ? `<?= $baseUrl ?>/api/v1/tracer/kuliah/update?tenant_id=${tenantId}`
                    : `<?= $baseUrl ?>/api/v1/tracer/kuliah?tenant_id=${tenantId}`;

                try {
                    const res = await axios.post(endpoint, form);
                    if (res.data?.success) {
                        modalFormKuliah.value.show = false;
                        Swal.fire({ 
                            icon: 'success', 
                            title: isEdit ? 'Berhasil Diperbarui' : 'Berhasil Disimpan', 
                            text: res.data.message, 
                            timer: 2000, 
                            showConfirmButton: false 
                        });
                        await fetchRiwayat('kuliah');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Terjadi kesalahan sistem.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Error Koneksi', text: e.response?.data?.error || e.message });
                } finally {
                    modalFormKuliah.value.saving = false;
                }
            }

            // Hapus Kuliah
            async function hapusKuliah(id, nama) {
                const confirm = await Swal.fire({
                    title: 'Hapus Riwayat Kuliah?',
                    text: `Apakah Anda yakin ingin menghapus data kuliah untuk ${nama || 'alumni ini'}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus Data',
                    cancelButtonText: 'Batal'
                });

                if (!confirm.isConfirmed) return;

                try {
                    const res = await axios.post(`<?= $baseUrl ?>/api/v1/tracer/kuliah/delete?tenant_id=${tenantId}`, { id: id });
                    if (res.data?.success) {
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: res.data.message, timer: 1500, showConfirmButton: false });
                        await fetchRiwayat('kuliah');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.error || e.message });
                }
            }

            // Open Modal Tambah Pekerjaan
            function openTambahPekerjaan() {
                resetModalPekerjaanStudentSelection();
                modalFormPekerjaan.value = {
                    show: true,
                    isEdit: false,
                    saving: false,
                    form: {
                        id: '',
                        is_manual: false,
                        siswa_id: '',
                        nama_lengkap: '',
                        nama_alumni: '',
                        nisn: '',
                        nama_perusahaan: '',
                        posisi_jabatan: '',
                        jenis_instansi: 'Swasta',
                        pendapatan_bulanan: '',
                        tahun_mulai: new Date().getFullYear(),
                        tahun_selesai: null,
                        status_kerja: 'Kontrak'
                    }
                };
            }

            // Open Modal Edit Pekerjaan
            function openEditPekerjaan(item) {
                modalFormPekerjaan.value = {
                    show: true,
                    isEdit: true,
                    saving: false,
                    form: {
                        id: item.id,
                        is_manual: Boolean(item.is_manual),
                        siswa_id: item.siswa_id || '',
                        nama_lengkap: item.nama_lengkap,
                        nama_alumni: item.nama_alumni,
                        nisn: item.nisn,
                        nama_perusahaan: item.nama_perusahaan,
                        posisi_jabatan: item.posisi_jabatan,
                        jenis_instansi: item.jenis_instansi || 'Swasta',
                        pendapatan_bulanan: item.pendapatan_bulanan || '',
                        tahun_mulai: item.tahun_mulai,
                        tahun_selesai: item.tahun_selesai,
                        status_kerja: item.status_kerja || 'Kontrak'
                    }
                };
            }

            // Submit Modal Pekerjaan (Create or Update)
            async function submitModalPekerjaan() {
                const isEdit = modalFormPekerjaan.value.isEdit;
                const form = modalFormPekerjaan.value.form;

                if (!isEdit && isAdmin.value && !form.is_manual && !form.siswa_id) {
                    Swal.fire({ icon: 'warning', title: 'Pilih Siswa', text: 'Silakan cari dan pilih siswa terdaftar, atau aktifkan "Input Alumni Luar Sistem".' });
                    return;
                }
                if (!isEdit && form.is_manual && !form.nama_alumni.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Nama Alumni Wajib', text: 'Silakan isi nama lengkap alumni luar sistem.' });
                    return;
                }
                if (!form.nama_perusahaan.trim() || !form.posisi_jabatan.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Data Wajib', text: 'Nama perusahaan dan posisi/jabatan tidak boleh kosong.' });
                    return;
                }

                modalFormPekerjaan.value.saving = true;
                const endpoint = isEdit 
                    ? `<?= $baseUrl ?>/api/v1/tracer/pekerjaan/update?tenant_id=${tenantId}`
                    : `<?= $baseUrl ?>/api/v1/tracer/pekerjaan?tenant_id=${tenantId}`;

                try {
                    const res = await axios.post(endpoint, form);
                    if (res.data?.success) {
                        modalFormPekerjaan.value.show = false;
                        Swal.fire({ 
                            icon: 'success', 
                            title: isEdit ? 'Berhasil Diperbarui' : 'Berhasil Disimpan', 
                            text: res.data.message, 
                            timer: 2000, 
                            showConfirmButton: false 
                        });
                        await fetchRiwayat('pekerjaan');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Terjadi kesalahan sistem.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Error Koneksi', text: e.response?.data?.error || e.message });
                } finally {
                    modalFormPekerjaan.value.saving = false;
                }
            }

            // Hapus Pekerjaan
            async function hapusPekerjaan(id, nama) {
                const confirm = await Swal.fire({
                    title: 'Hapus Riwayat Pekerjaan?',
                    text: `Apakah Anda yakin ingin menghapus data pekerjaan untuk ${nama || 'alumni ini'}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus Data',
                    cancelButtonText: 'Batal'
                });

                if (!confirm.isConfirmed) return;

                try {
                    const res = await axios.post(`<?= $baseUrl ?>/api/v1/tracer/pekerjaan/delete?tenant_id=${tenantId}`, { id: id });
                    if (res.data?.success) {
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: res.data.message, timer: 1500, showConfirmButton: false });
                        await fetchRiwayat('pekerjaan');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.error || e.message });
                }
            }

            return {
                activeTab,
                currentYear,
                loadingKuliah,
                loadingPekerjaan,
                riwayatKuliah,
                riwayatPekerjaan,
                listJalur,
                listKampusFlat,
                listProdiByModalKampus,
                searchKuliah,
                filterStatusKuliah,
                searchPekerjaan,
                filterStatusKerja,
                userRole,
                isAdmin,
                modalFormKuliah,
                modalFormPekerjaan,
                showSearchDropdown,
                searchResults,
                searchStudentQuery,
                searchStudentQueryPekerjaan,
                selectedStudent,
                selectedStudentPekerjaan,
                activeForm,
                filteredKuliah,
                filteredPekerjaan,
                onModalKampusChange,
                onModalProdiChange,
                resetModalKampusSelection,
                resetModalKuliahStudentSelection,
                resetModalPekerjaanStudentSelection,
                fetchAllData,
                searchStudents,
                selectStudentForModal,
                openTambahKuliah,
                openEditKuliah,
                submitModalKuliah,
                hapusKuliah,
                openTambahPekerjaan,
                openEditPekerjaan,
                submitModalPekerjaan,
                hapusPekerjaan
            };
        }
    });
}
</script>
