<?php
/**
 * View: Tracer Study / Portofolio Alumni
 * Mendukung data siswa terdaftar & alumni luar database (manual input).
 * Form Tambah & Edit menggunakan Modal Pop-up modern dan bersih.
 */
$userRole  = $data['user_role']         ?? ($_SESSION['role_name']    ?? '');
$userNama  = $data['user_nama']         ?? ($_SESSION['nama_lengkap'] ?? 'Alumni');
$tenantId  = $data['tenant_id']         ?? ($_SESSION['tenant_id']    ?? '');
$baseUrl   = (isset($this) && method_exists($this, 'getBaseUrl')) ? $this->getBaseUrl() : (rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));

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

    /* Modern Modal Dedicated Styles */
    .tracer-modal-content {
        background: #ffffff !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25) !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        overflow: hidden !important;
    }
    .tracer-modal-header {
        padding: 1.25rem 1.5rem !important;
        background: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .tracer-icon-pekerjaan {
        width: 44px;
        height: 44px;
        border-radius: 0.875rem;
        background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        flex-shrink: 0;
    }
    .tracer-icon-kuliah {
        width: 44px;
        height: 44px;
        border-radius: 0.875rem;
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        flex-shrink: 0;
    }
    .tracer-step-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }
    .tracer-step-title {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .tracer-step-title.pekerjaan {
        color: #0d9488;
    }
    .tracer-step-title.kuliah {
        color: #4f46e5;
    }
    .tracer-step-num {
        width: 22px;
        height: 22px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        color: #ffffff;
    }
    .tracer-step-num.pekerjaan {
        background: #0d9488;
    }
    .tracer-step-num.kuliah {
        background: #4f46e5;
    }
    .tracer-form-control {
        border-radius: 0.75rem !important;
        font-size: 0.8125rem !important;
        padding: 0.625rem 0.875rem !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #f8fafc !important;
        color: #1e293b !important;
        transition: all 0.2s ease !important;
    }
    .tracer-form-control:focus {
        background-color: #ffffff !important;
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15) !important;
        outline: none !important;
    }
    .tracer-form-control.kuliah:focus {
        border-color: #4f46e5 !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15) !important;
    }
    .tracer-btn-submit-pekerjaan {
        background: linear-gradient(135deg, #059669 0%, #0d9488 100%) !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.8125rem !important;
        padding: 0.65rem 1.4rem !important;
        border-radius: 0.75rem !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3) !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        transition: all 0.2s ease !important;
        cursor: pointer !important;
    }
    .tracer-btn-submit-pekerjaan:hover {
        background: linear-gradient(135deg, #047857 0%, #0f766e 100%) !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(5, 150, 105, 0.4) !important;
    }
    .tracer-btn-submit-kuliah {
        background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%) !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.8125rem !important;
        padding: 0.65rem 1.4rem !important;
        border-radius: 0.75rem !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3) !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        transition: all 0.2s ease !important;
        cursor: pointer !important;
    }
    .tracer-btn-submit-kuliah:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #4338ca 100%) !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4) !important;
    }
    .tracer-btn-batal {
        background: #f1f5f9 !important;
        color: #475569 !important;
        font-weight: 600 !important;
        font-size: 0.8125rem !important;
        padding: 0.65rem 1.25rem !important;
        border-radius: 0.75rem !important;
        border: 1px solid #e2e8f0 !important;
        transition: all 0.2s ease !important;
    }
    .tracer-btn-batal:hover {
        background: #e2e8f0 !important;
        color: #1e293b !important;
    }
    .tracer-toggle-btn {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        padding: 0.35rem 0.75rem !important;
        border-radius: 0.625rem !important;
        border: 1px solid #a7f3d0 !important;
        background: #ecfdf5 !important;
        color: #065f46 !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
        text-decoration: none !important;
    }
    .tracer-toggle-btn:hover {
        background: #d1fae5 !important;
        border-color: #6ee7b7 !important;
        color: #047857 !important;
    }
    .tracer-toggle-btn-kuliah {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        padding: 0.35rem 0.75rem !important;
        border-radius: 0.625rem !important;
        border: 1px solid #c7d2fe !important;
        background: #eef2ff !important;
        color: #3730a3 !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
        text-decoration: none !important;
    }
    .tracer-toggle-btn-kuliah:hover {
        background: #e0e7ff !important;
        border-color: #a5b4fc !important;
        color: #312e81 !important;
    }
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
<div id="<?= htmlspecialchars($tracer_instance_id, ENT_QUOTES, 'UTF-8') ?>" 
     data-initial-tab="<?= htmlspecialchars($tracer_initial_tab, ENT_QUOTES, 'UTF-8') ?>"
     data-user-role="<?= htmlspecialchars((string)$userRole, ENT_QUOTES, 'UTF-8') ?>"
     data-is-admin="<?= htmlspecialchars($isAdmin ? 'true' : 'false', ENT_QUOTES, 'UTF-8') ?>"
     data-tenant-id="<?= htmlspecialchars((string)$tenantId, ENT_QUOTES, 'UTF-8') ?>"
     data-instance-id="<?= htmlspecialchars($tracer_instance_id, ENT_QUOTES, 'UTF-8') ?>"
     v-cloak>

    <!-- BANNER: Status tergantung role (Hanya jika bukan sub-module) -->
    <?php if ($isAdmin && empty($is_sub_module)): ?>
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
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('tracerStudyNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="tracerStudyNavTabs" role="tablist">
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

            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('tracerStudyNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>
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
                <div class="position-relative">
                    <i class="bi bi-search position-absolute text-slate-400 text-xs" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                    <input type="text" class="rounded-xl border border-slate-300 pl-8 pr-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 w-52 shadow-2xs" v-model="searchKuliah" placeholder="Cari alumni, kampus, prodi...">
                </div>
                <select class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs" v-model="filterStatusKuliah">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Drop">Drop Out</option>
                </select>
                <button v-if="isAdmin" class="btn btn-primary rounded-xl text-xs font-bold px-3.5 py-2 flex items-center gap-1.5 shadow-sm" @click="openTambahKuliah()">
                    <i class="bi bi-plus-lg"></i> Tambah Riwayat Kuliah
                </button>
            </div>
        </div>

        <!-- Data Table Kuliah -->
        <div v-if="filteredKuliah.length > 0" class="table-responsive rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs">
            <table class="table table-hover align-middle mb-0 text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200/80">
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
                <div class="position-relative">
                    <i class="bi bi-search position-absolute text-slate-400 text-xs" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                    <input type="text" class="rounded-xl border border-slate-300 pl-8 pr-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 w-52 shadow-2xs" v-model="searchPekerjaan" placeholder="Cari alumni, perusahaan, posisi...">
                </div>
                <select class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs" v-model="filterStatusKerja">
                    <option value="">Semua Status</option>
                    <option value="Tetap">Tetap</option>
                    <option value="Kontrak">Kontrak</option>
                    <option value="Magang">Magang</option>
                    <option value="Wirausaha">Wirausaha</option>
                </select>
                <button v-if="isAdmin" class="btn btn-success rounded-xl text-xs font-bold px-3.5 py-2 flex items-center gap-1.5 shadow-sm" @click="openTambahPekerjaan()">
                    <i class="bi bi-plus-lg"></i> Tambah Riwayat Pekerjaan
                </button>
            </div>
        </div>

        <!-- Data Table Pekerjaan -->
        <div v-if="filteredPekerjaan.length > 0" class="table-responsive rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs">
            <table class="table table-hover align-middle mb-0 text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200/80">
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
            <p class="text-xs text-slate-400 mb-3">Tambahkan data alumni bekerja atau berwirausaha melalui tombol di atas.</p>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL POP-UP: FORM RIWAYAT KULIAH (TAMBAH & EDIT)
    ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="modalFormKuliah.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); z-index: 1060; backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 660px;">
            <div class="modal-content tracer-modal-content">
                <!-- Header Modal -->
                <div class="modal-header tracer-modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="tracer-icon-kuliah">
                            <i class="bi" :class="modalFormKuliah.isEdit ? 'bi-pencil-square' : 'bi-mortarboard-fill'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-slate-800 text-base mb-0 tracking-tight">
                                {{ modalFormKuliah.isEdit ? 'Edit Riwayat Kuliah Alumni' : 'Tambah Riwayat Kuliah Baru' }}
                            </h5>
                            <p class="text-xs text-slate-500 font-normal mb-0 mt-0.5">
                                Lacak penerimaan perguruan tinggi, jenjang, dan program studi alumni Anda di sini.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none" @click="modalFormKuliah.show = false"></button>
                </div>

                <!-- Body Modal Form: Unified Single Card Container with Dividers -->
                <div class="modal-body px-6 py-4 space-y-4 bg-white text-xs">
                    
                    <!-- 1. IDENTITAS ALUMNI SECTION -->
                    <div>
                        <div class="tracer-step-header">
                            <span class="tracer-step-title kuliah">
                                <span class="tracer-step-num kuliah">1</span> IDENTITAS ALUMNI
                            </span>
                            
                            <!-- Toggle Button / Mode Badge on the Right -->
                            <div v-if="!modalFormKuliah.isEdit && isAdmin">
                                <button v-if="!modalFormKuliah.form.is_manual" type="button" 
                                        class="tracer-toggle-btn-kuliah"
                                        @click="modalFormKuliah.form.is_manual = true; resetModalKuliahStudentSelection()">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Alumni Luar Sistem (Manual)</span>
                                </button>
                                <button v-else type="button" 
                                        class="tracer-toggle-btn-kuliah"
                                        @click="modalFormKuliah.form.is_manual = false; resetModalKuliahStudentSelection()">
                                    <i class="bi bi-database-check"></i>
                                    <span>Pilih dari Database Siswa</span>
                                </button>
                            </div>
                            <span v-else-if="modalFormKuliah.isEdit" class="badge font-semibold px-3 py-1 rounded-full text-[11px]" style="background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;">
                                {{ modalFormKuliah.form.is_manual ? 'Alumni Luar Sistem' : 'Siswa Terdaftar' }}
                            </span>
                        </div>

                        <!-- Display Alumni Name Card (Mode Edit) -->
                        <div v-if="modalFormKuliah.isEdit" class="p-3 rounded-xl d-flex align-items-center gap-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="w-10 h-10 rounded-xl text-white font-bold d-flex align-items-center justify-content-center text-sm shadow-xs flex-shrink-0" style="background: linear-gradient(135deg, #3b82f6, #6366f1);">
                                {{ (modalFormKuliah.form.nama_lengkap || modalFormKuliah.form.nama_alumni || 'A').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-sm leading-tight">{{ modalFormKuliah.form.nama_lengkap || modalFormKuliah.form.nama_alumni }}</div>
                                <div class="text-[11px] text-slate-400 font-mono mt-0.5 flex items-center gap-1">
                                    <i class="bi bi-card-text"></i> NISN: {{ modalFormKuliah.form.nisn || '—' }}
                                </div>
                            </div>
                        </div>

                        <!-- Pemilihan Siswa Terdaftar (Mode Tambah) -->
                        <div v-if="!modalFormKuliah.isEdit && !modalFormKuliah.form.is_manual && isAdmin">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Cari Alumni di Database Siswa <span class="text-rose-500">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3.5 text-sm" style="color: #4f46e5; z-index: 5;"></i>
                                <input type="text" class="form-control tracer-form-control kuliah w-100" 
                                       v-model="searchStudentQuery"
                                       @input="searchStudents('kuliah')" @focus="showSearchDropdown = true; activeForm = 'kuliah'"
                                       placeholder="Ketik nama lengkap atau NISN siswa lulus..." autocomplete="off" style="padding-left: 2.5rem !important;">
                                <div v-if="showSearchDropdown && activeForm === 'kuliah' && searchResults.length > 0" 
                                     class="dropdown-menu show w-100 position-absolute overflow-auto shadow-2xl rounded-2xl border-slate-200 mt-1.5 p-1.5 bg-white" style="max-height: 220px; z-index: 9999;">
                                    <button type="button" class="dropdown-item py-2 px-3 rounded-xl border-0 text-xs hover:bg-indigo-50 text-left transition d-flex items-center justify-between" 
                                             v-for="s in searchResults" :key="s.id" @mousedown.prevent="selectStudentForModal(s, 'kuliah')">
                                        <div>
                                            <div class="fw-semibold text-slate-800">{{ s.nama_lengkap }}</div>
                                            <div class="text-[10px] text-slate-400">NISN: {{ s.nisn || '-' }} | Kelas: {{ s.kelas_saat_ini || '-' }}</div>
                                        </div>
                                        <i class="bi bi-chevron-right text-slate-300 fs-7"></i>
                                    </button>
                                </div>
                            </div>
                            <div v-if="selectedStudent && activeForm === 'kuliah'" class="mt-2.5 p-3 rounded-xl d-flex align-items-center justify-content-between" style="background: #eef2ff; border: 1px solid #c7d2fe;">
                                <div class="d-flex align-items-center gap-2.5">
                                    <i class="bi bi-check-circle-fill text-indigo-600 fs-5"></i>
                                    <div>
                                        <span class="d-block font-bold text-indigo-950 text-xs">{{ selectedStudent.nama_lengkap }}</span>
                                        <span class="text-indigo-700 text-[10px]">NISN: {{ selectedStudent.nisn || '-' }} | Terpilih dari database siswa</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-xs rounded-lg font-bold px-2.5 py-1" style="background: #ffffff; color: #4338ca; border: 1px solid #c7d2fe;" @click="selectedStudent = null; modalFormKuliah.form.siswa_id = ''">Ganti</button>
                            </div>
                        </div>

                        <!-- Input Manual Alumni Luar Sistem (Mode Tambah) -->
                        <div v-if="!modalFormKuliah.isEdit && modalFormKuliah.form.is_manual && isAdmin" class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap Alumni <span class="text-rose-500">*</span></label>
                                <input type="text" class="form-control tracer-form-control kuliah" v-model="modalFormKuliah.form.nama_alumni" placeholder="Contoh: Budi Santoso" autocomplete="off">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">NISN / NIS (Opsional)</label>
                                <input type="text" class="form-control tracer-form-control kuliah" v-model="modalFormKuliah.form.nisn" placeholder="Nomor Induk Siswa..." autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- DIVIDER -->
                    <hr class="border-slate-100 my-3">

                    <!-- 2. PERGURUAN TINGGI & PROGRAM STUDI SECTION -->
                    <div>
                        <div class="tracer-step-header">
                            <span class="tracer-step-title kuliah">
                                <span class="tracer-step-num kuliah">2</span> PERGURUAN TINGGI & PROGRAM STUDI
                            </span>
                            
                            <!-- Toggle Kampus Master vs Manual -->
                            <div>
                                <button v-if="!modalFormKuliah.form.is_kampus_swasta" type="button" 
                                        class="tracer-toggle-btn-kuliah"
                                        @click="modalFormKuliah.form.is_kampus_swasta = true; resetModalKampusSelection()">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Ketik Manual (Swasta / LN)</span>
                                </button>
                                <button v-else type="button" 
                                        class="tracer-toggle-btn-kuliah"
                                        @click="modalFormKuliah.form.is_kampus_swasta = false; resetModalKampusSelection()">
                                    <i class="bi bi-building-check"></i>
                                    <span>Pilih dari Master PDSS</span>
                                </button>
                            </div>
                        </div>

                        <!-- Dropdown PDSS Master Kampus -->
                        <div v-if="!modalFormKuliah.form.is_kampus_swasta" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Perguruan Tinggi <span class="text-rose-500">*</span></label>
                                <select class="form-select tracer-form-control kuliah" v-model="modalFormKuliah.form.kampus_id" @change="onModalKampusChange()">
                                    <option value="">-- Pilih Perguruan Tinggi (PDSS) --</option>
                                    <option v-for="k in listKampusFlat" :key="k.id" :value="k.id">
                                        {{ k.nama_kampus }} ({{ k.jenis_kampus || 'PTN' }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Program Studi <span class="text-rose-500">*</span></label>
                                <select class="form-select tracer-form-control kuliah" v-model="modalFormKuliah.form.prodi_id" @change="onModalProdiChange()">
                                    <option value="">-- Pilih Program Studi --</option>
                                    <option v-for="p in listProdiByModalKampus" :key="p.id" :value="p.id">
                                        {{ p.program_studi || p.nama_prodi }} ({{ p.jenjang || 'S1' }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Input Manual Kampus Swasta -->
                        <div v-else class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Nama Kampus Swasta / LN <span class="text-rose-500">*</span></label>
                                <input type="text" class="form-control tracer-form-control kuliah" v-model="modalFormKuliah.form.nama_kampus" placeholder="Contoh: Universitas Telkom / Monash Univ">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Program Studi <span class="text-rose-500">*</span></label>
                                <input type="text" class="form-control tracer-form-control kuliah" v-model="modalFormKuliah.form.nama_prodi" placeholder="Contoh: Sistem Informasi / Informatika">
                            </div>
                        </div>

                        <div class="row g-3 pt-2">
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Jalur Masuk / Seleksi</label>
                                <select class="form-select tracer-form-control kuliah" v-model="modalFormKuliah.form.jalur_masuk_id">
                                    <option value="">-- Pilih Jalur Masuk --</option>
                                    <option v-for="j in listJalur" :key="j.id" :value="j.id">{{ j.nama_jalur }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Fakultas (Opsional)</label>
                                <input type="text" class="form-control tracer-form-control kuliah" v-model="modalFormKuliah.form.fakultas" placeholder="Contoh: Teknik / MIPA">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Jenjang Pendidikan</label>
                                <select class="form-select tracer-form-control kuliah" v-model="modalFormKuliah.form.jenjang">
                                    <option value="S1">S1 (Sarjana)</option>
                                    <option value="D4">D4 (Sarjana Terapan)</option>
                                    <option value="D3">D3 (Diploma Tiga)</option>
                                    <option value="S2">S2 (Magister)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- DIVIDER -->
                    <hr class="border-slate-100 my-3">

                    <!-- 3. PERIODE & STATUS KULIAH SECTION -->
                    <div>
                        <div class="tracer-step-title kuliah mb-3">
                            <span class="tracer-step-num kuliah">3</span> PERIODE & STATUS KELULUSAN
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Tahun Masuk <span class="text-rose-500">*</span></label>
                                <input type="number" class="form-control tracer-form-control kuliah" v-model.number="modalFormKuliah.form.tahun_masuk" :min="1990" :max="currentYear + 1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Tahun Lulus</label>
                                <input type="number" class="form-control tracer-form-control kuliah" v-model.number="modalFormKuliah.form.tahun_lulus" :min="modalFormKuliah.form.tahun_masuk" :max="currentYear + 7" placeholder="Kosongkan jika aktif">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Status Kuliah <span class="text-rose-500">*</span></label>
                                <select class="form-select tracer-form-control kuliah" v-model="modalFormKuliah.form.status_kuliah">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Lulus">Lulus</option>
                                    <option value="Drop">Drop Out</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Modal (Navigation: Batal on Left, Action on Right) -->
                <div class="modal-footer border-t border-slate-100 px-6 py-3.5 d-flex align-items-center justify-content-between bg-white">
                    <button type="button" class="btn tracer-btn-batal" @click="modalFormKuliah.show = false">Batal</button>
                    <button type="button" class="btn tracer-btn-submit-kuliah" :disabled="modalFormKuliah.saving" @click="submitModalKuliah()">
                        <span v-if="modalFormKuliah.saving" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-check2-circle fs-6"></i> {{ modalFormKuliah.isEdit ? 'Simpan Perubahan' : 'Simpan Riwayat Kuliah' }}
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL POP-UP: FORM RIWAYAT PEKERJAAN (TAMBAH & EDIT)
    ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="modalFormPekerjaan.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); z-index: 1060; backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 660px;">
            <div class="modal-content tracer-modal-content">
                <!-- Header Modal -->
                <div class="modal-header tracer-modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="tracer-icon-pekerjaan">
                            <i class="bi" :class="modalFormPekerjaan.isEdit ? 'bi-pencil-square' : 'bi-briefcase-fill'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-slate-800 text-base mb-0 tracking-tight">
                                {{ modalFormPekerjaan.isEdit ? 'Edit Riwayat Pekerjaan Alumni' : 'Tambah Riwayat Pekerjaan Baru' }}
                            </h5>
                            <p class="text-xs text-slate-500 font-normal mb-0 mt-0.5">
                                Lacak perjalanan karir, perusahaan, dan wirausaha alumni Anda di sini.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none" @click="modalFormPekerjaan.show = false"></button>
                </div>

                <!-- Body Modal Form: Unified Single Card Container with Dividers -->
                <div class="modal-body px-6 py-4 space-y-4 bg-white text-xs">
                    
                    <!-- 1. IDENTITAS ALUMNI SECTION -->
                    <div>
                        <div class="tracer-step-header">
                            <span class="tracer-step-title pekerjaan">
                                <span class="tracer-step-num pekerjaan">1</span> IDENTITAS ALUMNI
                            </span>

                            <!-- Toggle Button / Mode Badge on the Right -->
                            <div v-if="!modalFormPekerjaan.isEdit && isAdmin">
                                <button v-if="!modalFormPekerjaan.form.is_manual" type="button" 
                                        class="tracer-toggle-btn"
                                        @click="modalFormPekerjaan.form.is_manual = true; resetModalPekerjaanStudentSelection()">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Alumni Luar Sistem (Manual)</span>
                                </button>
                                <button v-else type="button" 
                                        class="tracer-toggle-btn"
                                        @click="modalFormPekerjaan.form.is_manual = false; resetModalPekerjaanStudentSelection()">
                                    <i class="bi bi-database-check"></i>
                                    <span>Pilih dari Database Siswa</span>
                                </button>
                            </div>
                            <span v-else-if="modalFormPekerjaan.isEdit" class="badge font-semibold px-3 py-1 rounded-full text-[11px]" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                {{ modalFormPekerjaan.form.is_manual ? 'Alumni Luar Sistem' : 'Siswa Terdaftar' }}
                            </span>
                        </div>

                        <!-- Display Alumni Name Card (Mode Edit) -->
                        <div v-if="modalFormPekerjaan.isEdit" class="p-3 rounded-xl d-flex align-items-center gap-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="w-10 h-10 rounded-xl text-white font-bold d-flex align-items-center justify-content-center text-sm shadow-xs flex-shrink-0" style="background: linear-gradient(135deg, #059669, #0d9488);">
                                {{ (modalFormPekerjaan.form.nama_lengkap || modalFormPekerjaan.form.nama_alumni || 'A').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-sm leading-tight">{{ modalFormPekerjaan.form.nama_lengkap || modalFormPekerjaan.form.nama_alumni }}</div>
                                <div class="text-[11px] text-slate-400 font-mono mt-0.5 flex items-center gap-1">
                                    <i class="bi bi-card-text"></i> NISN: {{ modalFormPekerjaan.form.nisn || '—' }}
                                </div>
                            </div>
                        </div>

                        <!-- Pemilihan Siswa Terdaftar (Mode Tambah) -->
                        <div v-if="!modalFormPekerjaan.isEdit && !modalFormPekerjaan.form.is_manual && isAdmin">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Cari Alumni di Database Siswa <span class="text-rose-500">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3.5 text-sm" style="color: #0d9488; z-index: 5;"></i>
                                <input type="text" class="form-control tracer-form-control w-100" 
                                       v-model="searchStudentQueryPekerjaan"
                                       @input="searchStudents('pekerjaan')" @focus="showSearchDropdown = true; activeForm = 'pekerjaan'"
                                       placeholder="Ketik nama lengkap atau NISN siswa lulus..." autocomplete="off" style="padding-left: 2.5rem !important;">
                                <div v-if="showSearchDropdown && activeForm === 'pekerjaan' && searchResults.length > 0" 
                                     class="dropdown-menu show w-100 position-absolute overflow-auto shadow-2xl rounded-2xl border-slate-200 mt-1.5 p-1.5 bg-white" style="max-height: 220px; z-index: 9999;">
                                    <button type="button" class="dropdown-item py-2 px-3 rounded-xl border-0 text-xs hover:bg-teal-50 text-left transition d-flex items-center justify-between" 
                                             v-for="s in searchResults" :key="s.id" @mousedown.prevent="selectStudentForModal(s, 'pekerjaan')">
                                        <div>
                                            <div class="fw-semibold text-slate-800">{{ s.nama_lengkap }}</div>
                                            <div class="text-[10px] text-slate-400">NISN: {{ s.nisn || '-' }} | Kelas: {{ s.kelas_saat_ini || '-' }}</div>
                                        </div>
                                        <i class="bi bi-chevron-right text-slate-300 fs-7"></i>
                                    </button>
                                </div>
                            </div>
                            <div v-if="selectedStudentPekerjaan && activeForm === 'pekerjaan'" class="mt-2.5 p-3 rounded-xl d-flex align-items-center justify-content-between" style="background: #ecfdf5; border: 1px solid #a7f3d0;">
                                <div class="d-flex align-items-center gap-2.5">
                                    <i class="bi bi-check-circle-fill fs-5" style="color: #059669;"></i>
                                    <div>
                                        <span class="d-block font-bold text-teal-950 text-xs">{{ selectedStudentPekerjaan.nama_lengkap }}</span>
                                        <span class="text-[10px]" style="color: #047857;">NISN: {{ selectedStudentPekerjaan.nisn || '-' }} | Terpilih dari database siswa</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-xs rounded-lg font-bold px-2.5 py-1" style="background: #ffffff; color: #047857; border: 1px solid #a7f3d0;" @click="selectedStudentPekerjaan = null; modalFormPekerjaan.form.siswa_id = ''">Ganti</button>
                            </div>
                        </div>

                        <!-- Input Manual Alumni Luar Sistem (Mode Tambah) -->
                        <div v-if="!modalFormPekerjaan.isEdit && modalFormPekerjaan.form.is_manual && isAdmin" class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap Alumni <span class="text-rose-500">*</span></label>
                                <input type="text" class="form-control tracer-form-control" v-model="modalFormPekerjaan.form.nama_alumni" placeholder="Contoh: Siti Rahmawati" autocomplete="off">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">NISN / NIS (Opsional)</label>
                                <input type="text" class="form-control tracer-form-control" v-model="modalFormPekerjaan.form.nisn" placeholder="Nomor Induk Siswa..." autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- DIVIDER -->
                    <hr class="border-slate-100 my-3">

                    <!-- 2. INFORMASI PERUSAHAAN & KARIR SECTION -->
                    <div>
                        <div class="tracer-step-title pekerjaan mb-3">
                            <span class="tracer-step-num pekerjaan">2</span> INFORMASI PERUSAHAAN & KARIR
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Nama Perusahaan / Tempat Kerja <span class="text-rose-500">*</span></label>
                                <input type="text" class="form-control tracer-form-control" v-model="modalFormPekerjaan.form.nama_perusahaan" placeholder="Contoh: PT. Telkom Indonesia">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Posisi / Jabatan Pekerjaan <span class="text-rose-500">*</span></label>
                                <input type="text" class="form-control tracer-form-control" v-model="modalFormPekerjaan.form.posisi_jabatan" placeholder="Contoh: Junior Software Developer">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Jenis Instansi</label>
                                <select class="form-select tracer-form-control" v-model="modalFormPekerjaan.form.jenis_instansi">
                                    <option value="Swasta">Perusahaan Swasta / Multinasional</option>
                                    <option value="BUMN">BUMN / BUMD</option>
                                    <option value="Pemerintah">Pemerintah / Instansi Negeri</option>
                                    <option value="Wirausaha">Wirausaha / Bisnis Mandiri</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Rentang Pendapatan Bulanan</label>
                                <select class="form-select tracer-form-control" v-model="modalFormPekerjaan.form.pendapatan_bulanan">
                                    <option value="">-- Pilih Rentang Pendapatan --</option>
                                    <option value="< 3 Juta">< Rp 3.000.000</option>
                                    <option value="3 - 5 Juta">Rp 3.000.000 - Rp 5.000.000</option>
                                    <option value="5 - 10 Juta">Rp 5.000.000 - Rp 10.000.000</option>
                                    <option value="> 10 Juta">> Rp 10.000.000</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- DIVIDER -->
                    <hr class="border-slate-100 my-3">

                    <!-- 3. PERIODE & STATUS KERJA SECTION -->
                    <div>
                        <div class="tracer-step-title pekerjaan mb-3">
                            <span class="tracer-step-num pekerjaan">3</span> PERIODE & STATUS KARIR
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Tahun Mulai Bekerja <span class="text-rose-500">*</span></label>
                                <input type="number" class="form-control tracer-form-control" v-model.number="modalFormPekerjaan.form.tahun_mulai" :min="1990" :max="currentYear + 1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Tahun Selesai</label>
                                <input type="number" class="form-control tracer-form-control" v-model.number="modalFormPekerjaan.form.tahun_selesai" :min="modalFormPekerjaan.form.tahun_mulai" :max="currentYear + 10" placeholder="Kosongkan jika aktif">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Status Kerja <span class="text-rose-500">*</span></label>
                                <select class="form-select tracer-form-control" v-model="modalFormPekerjaan.form.status_kerja">
                                    <option value="Tetap">Karyawan Tetap</option>
                                    <option value="Kontrak">Karyawan Kontrak</option>
                                    <option value="Magang">Magang / Internship</option>
                                    <option value="Wirausaha">Wirausaha / Freelance</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Modal (Navigation: Batal on Left, Action on Right) -->
                <div class="modal-footer border-t border-slate-100 px-6 py-3.5 d-flex align-items-center justify-content-between bg-white">
                    <button type="button" class="btn tracer-btn-batal" @click="modalFormPekerjaan.show = false">Batal</button>
                    <button type="button" class="btn tracer-btn-submit-pekerjaan" :disabled="modalFormPekerjaan.saving" @click="submitModalPekerjaan()">
                        <span v-if="modalFormPekerjaan.saving" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-check2-circle fs-6"></i> {{ modalFormPekerjaan.isEdit ? 'Simpan Perubahan' : 'Simpan Riwayat Pekerjaan' }}
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

    const rootEl = document.querySelector('[data-instance-id^="tracerApp_"]') || document.querySelector('[id^="tracerApp_"]');
    const tracerSelector = rootEl ? '#' + rootEl.id : '#tracerApp_kuliah';
    const initTabVal = rootEl?.dataset?.initialTab || 'kuliah';
    const userRoleVal = rootEl?.dataset?.userRole || '';
    const isAdminVal = rootEl?.dataset?.isAdmin === 'true';
    const tenantIdVal = rootEl?.dataset?.tenantId || '';

    window.VueAppRegistry.register(tracerSelector, {
        setup() {
            const activeTab        = ref(initTabVal);
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

            const userRole = ref(userRoleVal);
            const isAdmin  = ref(isAdminVal);

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
            const tenantId  = urlParams.get('tenant_id') || tenantIdVal || '';

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

            // Master Data API (Lazy Loaded On Demand)
            let masterDataLoaded = false;
            async function fetchMasterData() {
                if (masterDataLoaded && listKampusFlat.value.length > 0) return;
                try {
                    const [resJalur, resKampus] = await Promise.all([
                        axios.get(`<?= $baseUrl ?>/api/v1/kampus/jalur?tenant_id=${tenantId}`),
                        axios.get(`<?= $baseUrl ?>/api/v1/kampus/master?tenant_id=${tenantId}`)
                    ]);
                    if (resJalur.data?.success) listJalur.value = resJalur.data.data;
                    if (resKampus.data?.success) listKampusFlat.value = resKampus.data.data;
                    masterDataLoaded = true;
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
                const initTab = 'kuliah';
                const isSubModule = false;
                
                if (isSubModule) {
                    const selector = '#tracer-study-app';
                    const containerEl = document.querySelector(selector);
                    const tabPane = containerEl ? containerEl.closest('.tab-pane') : null;
                    const isVisible = tabPane ? tabPane.classList.contains('active') : true;

                    if (isVisible) {
                        fetchRiwayat(initTab || 'kuliah');
                    } else if (tabPane && tabPane.id) {
                        const tabTrigger = document.querySelector(`[data-bs-target="#${tabPane.id}"]`);
                        if (tabTrigger) {
                            tabTrigger.addEventListener('shown.bs.tab', () => {
                                if (initTab === 'pekerjaan') {
                                    if (riwayatPekerjaan.value.length === 0) fetchRiwayat('pekerjaan');
                                } else {
                                    if (riwayatKuliah.value.length === 0) fetchRiwayat('kuliah');
                                }
                            }, { once: true });
                        }
                    }
                } else {
                    fetchAllData();
                }
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

            // Open Modal Tambah Kuliah (Lazy fetch master data)
            function openTambahKuliah() {
                fetchMasterData();
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

            // Open Modal Edit Kuliah (Lazy fetch master data)
            function openEditKuliah(item) {
                fetchMasterData();
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
