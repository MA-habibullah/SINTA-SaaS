<?php
/**
 * View: Tracer Study / Portofolio Alumni
 * Hanya dapat diakses oleh siswa berstatus 'Lulus' atau Admin Sekolah / Super Admin.
 *
 * Hak Akses Per Role:
 * - siswa      : Hanya lihat & input data milik sendiri. Tidak bisa melihat data siswa lain.
 * - guru_bk    : Bisa input & lihat data alumni milik sekolahnya. Aksi hapus tersedia.
 * - operator_sekolah / admin : Sama seperti guru_bk.
 * - super_admin: Akses seluruh data lintas sekolah. Perlu memilih tenant dulu untuk input.
 *
 * Variabel opsional dari layout pemanggil:
 * - $active_tracer_tab : 'kuliah' | 'pekerjaan'  — tab aktif awal (default: 'kuliah')
 * - $is_sub_module     : true — sembunyikan header & sub-nav internal
 */
$userRole  = $data['user_role']         ?? ($_SESSION['role_name']    ?? '');
$userNama  = $data['user_nama']         ?? ($_SESSION['nama_lengkap'] ?? 'Alumni');
$kuliah    = $data['riwayat_kuliah']    ?? [];
$pekerjaan = $data['riwayat_pekerjaan'] ?? [];
$baseUrl   = '/SINTA-SaaS';

// Tentukan apakah user ini adalah role "admin" (bukan siswa)
$isAdmin = in_array($userRole, ['super_admin', 'operator_sekolah', 'admin', 'operator', 'guru_bk']);

// --- Reusability Support ---
// ID unik untuk Vue mount point (mendukung include 2x dalam halaman yang sama)
$tracer_initial_tab  = $active_tracer_tab ?? 'kuliah'; // 'kuliah' | 'pekerjaan'
$tracer_instance_id  = 'tracerApp_' . $tracer_initial_tab;
$tracer_vue_selector = '#' . $tracer_instance_id;
?>

<style>
    :root {
        --tracer-blue:   #2563eb;
        --tracer-green:  #10b981;
        --tracer-amber:  #f59e0b;
        --tracer-indigo: #6366f1;
        --tracer-border: #e2e8f0;
        --tracer-bg:     #f8fafc;
    }

    .tracer-card {
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 4px 20px rgba(15,23,42,0.06);
        border: none;
    }

    /* Navigation Tabs Styling */
    .scrollable-nav-tabs {
        padding-bottom: 5px;
        border-bottom: none;
    }
    .scrollable-nav-tabs::-webkit-scrollbar {
        height: 4px;
    }
    .scrollable-nav-tabs::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    .nav-tabs-wrapper .nav-link {
        font-size: 14px;
        color: #475569;
        background-color: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        font-weight: 600;
        padding: 10px 16px;
        transition: all 0.2s ease-in-out;
    }
    .nav-tabs-wrapper .nav-link:hover {
        color: #2563eb;
    }
    .nav-tabs-wrapper .nav-link.active {
        color: #2563eb !important;
        background-color: transparent !important;
        border-bottom: 2px solid #2563eb !important;
    }

    .tracer-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-aktif    { background: #ecfdf5; color: #059669; }
    .status-lulus    { background: #eff6ff; color: #2563eb; }
    .status-drop     { background: #fef2f2; color: #dc2626; }
    .status-kontrak  { background: #fff7ed; color: #d97706; }
    .status-tetap    { background: #ecfdf5; color: #059669; }
    .status-magang   { background: #f0f9ff; color: #0284c7; }

    .form-card {
        background: var(--tracer-bg);
        border-radius: 1rem;
        border: 1px dashed var(--tracer-border);
        transition: border-color 0.2s;
    }
    .form-card:hover { border-color: var(--tracer-blue); }

    .add-row-btn {
        background: none;
        border: 2px dashed var(--tracer-blue);
        color: var(--tracer-blue);
        border-radius: 0.75rem;
        padding: 0.5rem 1.25rem;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
    }
    .add-row-btn:hover { background: #eff6ff; }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #94a3b8;
    }

    [v-cloak] { display: none !important; }
</style>

<?php if (empty($is_sub_module)): ?>
<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-mortarboard-fill me-2 text-primary"></i>
            Tracer Study / Portofolio Alumni
        </h2>
        <p class="text-muted fs-7 mb-0">
            Rekam jejak pendidikan dan karir setelah kelulusan.
            <?php if ($userRole === 'siswa'): ?>
                Halo, <strong><?= htmlspecialchars($userNama) ?></strong>!
            <?php endif; ?>
        </p>
    </div>
    <?php if ($userRole !== 'siswa'): ?>
    <div>
        <a href="<?= $baseUrl ?>/pengguna" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Vue App Mount Point -->
<div id="<?= htmlspecialchars($tracer_instance_id, ENT_QUOTES, 'UTF-8') ?>" v-cloak>

    <!-- ================================================================
         BANNER: Status tergantung role
    ================================================================ -->
    <!-- Banner untuk SISWA ALUMNI -->
    <?php if ($userRole === 'siswa'): ?>
    <div class="alert border-0 rounded-4 p-4 mb-4 shadow-sm d-flex align-items-center gap-3"
         style="background: linear-gradient(135deg,#eff6ff,#ecfdf5);">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:52px;height:52px;background:linear-gradient(135deg,#2563eb,#10b981);">
            <i class="bi bi-award-fill fs-4 text-white"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-1" style="color:#1e293b;">✅ Status: Alumni Lulus</h5>
            <p class="mb-0 text-muted fs-7">
                Anda dapat menambah riwayat kuliah dan pekerjaan di bawah ini.
                Data ini bersifat informatif dan dikelola sendiri oleh alumni.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Banner untuk ADMIN / GURU BK / OPERATOR -->
    <?php if ($isAdmin): ?>
    <div class="alert border-0 rounded-4 p-3 mb-4 shadow-sm d-flex align-items-center gap-3"
         style="background: linear-gradient(135deg,#f5f3ff,#ede9fe);">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:44px;height:44px;background:linear-gradient(135deg,#6366f1,#8b5cf6);">
            <i class="bi bi-person-badge-fill fs-5 text-white"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0" style="color:#3730a3;">
                Mode Admin — <?= htmlspecialchars(ucwords(str_replace('_', ' ', $userRole))) ?>
            </h6>
            <p class="mb-0 text-muted" style="font-size:0.78rem;">
                Anda dapat menambah dan mengelola data tracer study alumni di sekolah ini.
                <strong>Siswa hanya dapat melihat dan mengedit data milik dirinya sendiri.</strong>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================================================================
         TAB NAVIGATION (disembunyikan jika dipanggil sebagai sub-module)
    ================================================================ -->
    <?php if (empty($is_sub_module)): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2">
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'kuliah'}"
                                @click="activeTab = 'kuliah'" id="tab-kuliah-<?= $tracer_instance_id ?>">
                            <i class="bi bi-mortarboard me-2 fs-6"></i> Riwayat Kuliah
                            <span class="badge bg-primary ms-1 rounded-pill">{{ riwayatKuliah.length }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'pekerjaan'}"
                                @click="activeTab = 'pekerjaan'" id="tab-pekerjaan-<?= $tracer_instance_id ?>">
                            <i class="bi bi-briefcase me-2 fs-6"></i> Riwayat Pekerjaan
                            <span class="badge bg-success ms-1 rounded-pill">{{ riwayatPekerjaan.length }}</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================================================================
         TAB PANEL: RIWAYAT KULIAH
    ================================================================ -->
    <div v-show="activeTab === 'kuliah'" class="tracer-card p-4">

        <!-- Alert -->
        <div v-if="alertKuliah.msg" :class="'alert alert-' + alertKuliah.type + ' border-0 rounded-3'" role="alert">
            {{ alertKuliah.msg }}
            <button type="button" class="btn-close float-end" @click="alertKuliah.msg = ''"></button>
        </div>

        <!-- Data Table -->
        <div v-if="riwayatKuliah.length > 0" class="table-responsive mb-4">
            <table class="table table-hover align-middle" id="tbl-kuliah-<?= $tracer_instance_id ?>">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th v-if="isAdmin">Nama Alumni</th>
                        <th>Kampus</th>
                        <th>Fakultas / Jurusan</th>
                        <th>Jalur</th>
                        <th>Tahun Masuk</th>
                        <th>Tahun Lulus</th>
                        <th>Status</th>
                        <th v-if="isAdmin" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in riwayatKuliah" :key="item.id">
                        <td class="text-muted">{{ idx + 1 }}</td>
                        <td v-if="isAdmin" class="fw-semibold text-truncate" style="max-width:140px;">
                            {{ item.nama_lengkap || item.nama_alumni || '—' }}
                        </td>
                        <td class="fw-semibold">{{ item.nama_kampus }}</td>
                        <td class="text-muted">{{ item.fakultas || '—' }} / {{ item.jurusan || '—' }}</td>
                        <td class="text-muted">{{ item.nama_jalur || '—' }}</td>
                        <td>{{ item.tahun_masuk }}</td>
                        <td>{{ item.tahun_lulus || 'Masih Kuliah' }}</td>
                        <td>
                            <span class="tracer-badge"
                                  :class="{
                                      'status-aktif': item.status_kuliah === 'Aktif',
                                      'status-lulus': item.status_kuliah === 'Lulus',
                                      'status-drop':  item.status_kuliah === 'Drop'
                                  }">
                                {{ item.status_kuliah }}
                            </span>
                        </td>
                        <td v-if="isAdmin" class="text-center">
                            <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1"
                                    @click="hapusKuliah(item.id)"
                                    title="Hapus riwayat kuliah ini">
                                <i class="bi bi-trash3 fs-7"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="empty-state">
            <i class="bi bi-mortarboard fs-1 d-block mb-2"></i>
            Belum ada riwayat kuliah. Tambahkan di bawah ini.
        </div>

        <!-- Form Tambah Riwayat Kuliah (Reaktif) -->
        <div class="form-card p-4 mt-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Riwayat Kuliah</h6>

            <div class="row g-3">
                <div class="col-md-12" v-if="isAdmin">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold fs-7 mb-0">Nama Alumni (Siswa) <span class="text-danger">*</span></label>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" id="manualInputKuliah-<?= $tracer_instance_id ?>" v-model="formKuliah.is_manual" @change="resetKuliah()">
                            <label class="form-check-label fs-7" for="manualInputKuliah-<?= $tracer_instance_id ?>">Input Alumni Luar Sistem</label>
                        </div>
                    </div>
                    
                    <div v-if="!formKuliah.is_manual">
                        <div class="position-relative">
                            <input type="text" class="form-control" v-model="formKuliah.nama_alumni"
                                   @input="searchStudents('kuliah')" @focus="showSearchDropdown = true; activeForm = 'kuliah'"
                                   placeholder="Ketik nama atau NISN siswa lulus..." autocomplete="off">
                            <div v-if="showSearchDropdown && activeForm === 'kuliah' && searchResults.length > 0" 
                                 class="dropdown-menu show w-100 position-absolute overflow-auto shadow-sm" style="max-height: 200px; z-index: 999;">
                                <button type="button" class="dropdown-item py-2 border-bottom" v-for="s in searchResults" :key="s.id" @mousedown.prevent="selectStudent(s, 'kuliah')">
                                    <div class="fw-bold">{{ s.nama_lengkap }}</div>
                                    <small class="text-muted">NISN: {{ s.nisn || '-' }} | NIS: {{ s.nis || '-' }}</small>
                                </button>
                            </div>
                            <div v-else-if="showSearchDropdown && activeForm === 'kuliah' && searchResults.length === 0 && formKuliah.nama_alumni.trim().length >= 2"
                                 class="dropdown-menu show w-100 position-absolute p-3 text-center text-muted shadow-sm" style="z-index: 999;">
                                <i class="bi bi-info-circle me-1"></i> Tidak ada siswa cocok.
                            </div>
                        </div>
                        <div v-if="selectedStudent && activeForm === 'kuliah'" class="mt-2 p-2 bg-success bg-opacity-10 border border-success border-opacity-25 rounded d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <div style="font-size: 0.8rem;">
                                <span class="d-block fw-bold text-success">{{ selectedStudent.nama_lengkap }}</span>
                                <span class="text-success text-opacity-75">Siswa Terpilih</span>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <input type="text" class="form-control" v-model="formKuliah.nama_alumni" placeholder="Ketik nama alumni secara manual..." autocomplete="off">
                        <small class="text-muted">Menambahkan data alumni lawas yang tidak terdaftar di sistem.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-7">Jalur Masuk</label>
                    <select class="form-select" v-model="formKuliah.jalur_masuk_id">
                        <option value="">-- Pilih Jalur --</option>
                        <option v-for="j in listJalur" :key="j.id" :value="j.id">{{ j.nama_jalur }}</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold fs-7">Kampus & Program Studi <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="formKuliah.kampus_prodi_id" @change="syncKampusData()">
                        <option value="">-- Pilih dari Pangkalan Data (PDSS) --</option>
                        <option v-for="p in listKampusProdi" :key="p.prodi_id" :value="p.prodi_id">
                            {{ p.nama_kampus }} - {{ p.program_studi }} ({{ p.jenjang }})
                        </option>
                    </select>
                </div>

                <div class="col-md-6" v-show="!formKuliah.kampus_prodi_id">
                    <label class="form-label fw-semibold fs-7">Nama Kampus (Manual) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="formKuliah.nama_kampus" placeholder="Ketik jika kampus tidak ada di daftar" maxlength="255">
                </div>
                <div class="col-md-6" v-show="!formKuliah.kampus_prodi_id">
                    <label class="form-label fw-semibold fs-7">Program Studi (Manual)</label>
                    <input type="text" class="form-control" v-model="formKuliah.jurusan" placeholder="Ketik manual" maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold fs-7">Tahun Masuk <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" v-model.number="formKuliah.tahun_masuk"
                           :min="2000" :max="currentYear + 1" placeholder="2022" id="input-tahun-masuk-<?= $tracer_instance_id ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold fs-7">Tahun Lulus</label>
                    <input type="number" class="form-control" v-model.number="formKuliah.tahun_lulus"
                           :min="formKuliah.tahun_masuk" :max="currentYear + 5"
                           placeholder="Kosongkan jika masih aktif" id="input-tahun-lulus-<?= $tracer_instance_id ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-7">Status Kuliah <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="formKuliah.status_kuliah" id="select-status-kuliah-<?= $tracer_instance_id ?>">
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Drop">Drop Out</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary px-4 rounded-3" :disabled="loadingKuliah"
                            @click="submitKuliah" id="btn-simpan-kuliah-<?= $tracer_instance_id ?>">
                        <span v-if="loadingKuliah" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-floppy me-2"></i>
                        {{ loadingKuliah ? 'Menyimpan...' : 'Simpan Kuliah' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================
         TAB PANEL: RIWAYAT PEKERJAAN
    ================================================================ -->
    <div v-show="activeTab === 'pekerjaan'" class="tracer-card p-4">

        <div v-if="alertPekerjaan.msg" :class="'alert alert-' + alertPekerjaan.type + ' border-0 rounded-3'" role="alert">
            {{ alertPekerjaan.msg }}
            <button type="button" class="btn-close float-end" @click="alertPekerjaan.msg = ''"></button>
        </div>

        <!-- Data Table -->
        <div v-if="riwayatPekerjaan.length > 0" class="table-responsive mb-4">
            <table class="table table-hover align-middle" id="tbl-pekerjaan-<?= $tracer_instance_id ?>">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th v-if="isAdmin">Nama Alumni</th>
                        <th>Perusahaan</th>
                        <th>Posisi / Jabatan</th>
                        <th>Tahun Mulai</th>
                        <th>Tahun Selesai</th>
                        <th>Pendapatan</th>
                        <th>Status</th>
                        <th v-if="isAdmin" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in riwayatPekerjaan" :key="item.id">
                        <td class="text-muted">{{ idx + 1 }}</td>
                        <td v-if="isAdmin" class="fw-semibold text-truncate" style="max-width:140px;">
                            {{ item.nama_lengkap || item.nama_alumni || '—' }}
                        </td>
                        <td class="fw-semibold">{{ item.nama_perusahaan }}</td>
                        <td>{{ item.posisi_jabatan }}</td>
                        <td>{{ item.tahun_mulai }}</td>
                        <td>{{ item.tahun_selesai || 'Masih Bekerja' }}</td>
                        <td class="text-muted">{{ item.pendapatan_bulanan || '—' }}</td>
                        <td>
                            <span class="tracer-badge"
                                  :class="{
                                      'status-kontrak': item.status_kerja === 'Kontrak',
                                      'status-tetap':   item.status_kerja === 'Tetap',
                                      'status-magang':  item.status_kerja === 'Magang'
                                  }">
                                {{ item.status_kerja }}
                            </span>
                        </td>
                        <td v-if="isAdmin" class="text-center">
                            <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1"
                                    @click="hapusPekerjaan(item.id)"
                                    title="Hapus riwayat pekerjaan ini">
                                <i class="bi bi-trash3 fs-7"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="empty-state">
            <i class="bi bi-briefcase fs-1 d-block mb-2"></i>
            Belum ada riwayat pekerjaan. Tambahkan di bawah ini.
        </div>

        <!-- Form Tambah Riwayat Pekerjaan (Reaktif) -->
        <div class="form-card p-4 mt-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Riwayat Pekerjaan</h6>

            <div class="row g-3">
                <div class="col-md-12" v-if="isAdmin">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold fs-7 mb-0">Nama Alumni (Siswa) <span class="text-danger">*</span></label>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" id="manualInputPekerjaan-<?= $tracer_instance_id ?>" v-model="formPekerjaan.is_manual" @change="resetPekerjaan()">
                            <label class="form-check-label fs-7" for="manualInputPekerjaan-<?= $tracer_instance_id ?>">Input Alumni Luar Sistem</label>
                        </div>
                    </div>

                    <div v-if="!formPekerjaan.is_manual">
                        <div class="position-relative">
                            <input type="text" class="form-control" v-model="formPekerjaan.nama_alumni"
                                   @input="searchStudents('pekerjaan')" @focus="showSearchDropdown = true; activeForm = 'pekerjaan'"
                                   placeholder="Ketik nama atau NISN siswa lulus..." autocomplete="off">
                            <div v-if="showSearchDropdown && activeForm === 'pekerjaan' && searchResults.length > 0" 
                                 class="dropdown-menu show w-100 position-absolute overflow-auto shadow-sm" style="max-height: 200px; z-index: 999;">
                                <button type="button" class="dropdown-item py-2 border-bottom" v-for="s in searchResults" :key="s.id" @mousedown.prevent="selectStudent(s, 'pekerjaan')">
                                    <div class="fw-bold">{{ s.nama_lengkap }}</div>
                                    <small class="text-muted">NISN: {{ s.nisn || '-' }} | NIS: {{ s.nis || '-' }}</small>
                                </button>
                            </div>
                            <div v-else-if="showSearchDropdown && activeForm === 'pekerjaan' && searchResults.length === 0 && formPekerjaan.nama_alumni.trim().length >= 2"
                                 class="dropdown-menu show w-100 position-absolute p-3 text-center text-muted shadow-sm" style="z-index: 999;">
                                <i class="bi bi-info-circle me-1"></i> Tidak ada siswa cocok.
                            </div>
                        </div>
                        <div v-if="selectedStudent && activeForm === 'pekerjaan'" class="mt-2 p-2 bg-success bg-opacity-10 border border-success border-opacity-25 rounded d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <div style="font-size: 0.8rem;">
                                <span class="d-block fw-bold text-success">{{ selectedStudent.nama_lengkap }}</span>
                                <span class="text-success text-opacity-75">Siswa Terpilih</span>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <input type="text" class="form-control" v-model="formPekerjaan.nama_alumni" placeholder="Ketik nama alumni secara manual..." autocomplete="off">
                        <small class="text-muted">Menambahkan data alumni lawas yang tidak terdaftar di sistem.</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-7">Nama Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="formPekerjaan.nama_perusahaan"
                           placeholder="PT. Contoh Indonesia" id="input-nama-perusahaan-<?= $tracer_instance_id ?>" maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-7">Posisi / Jabatan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="formPekerjaan.posisi_jabatan"
                           placeholder="Software Engineer" id="input-posisi-<?= $tracer_instance_id ?>" maxlength="255">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-7">Pendapatan Bulanan</label>
                    <select class="form-select" v-model="formPekerjaan.pendapatan_bulanan" id="select-pendapatan-<?= $tracer_instance_id ?>">
                        <option value="">Pilih rentang...</option>
                        <option value="< 1 Juta">Kurang dari 1 Juta</option>
                        <option value="1-3 Juta">1 – 3 Juta</option>
                        <option value="3-5 Juta">3 – 5 Juta</option>
                        <option value="5-10 Juta">5 – 10 Juta</option>
                        <option value="> 10 Juta">Lebih dari 10 Juta</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold fs-7">Tahun Mulai <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" v-model.number="formPekerjaan.tahun_mulai"
                           :min="2000" :max="currentYear + 1" placeholder="2023" id="input-tahun-mulai-kerja-<?= $tracer_instance_id ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold fs-7">Tahun Selesai</label>
                    <input type="number" class="form-control" v-model.number="formPekerjaan.tahun_selesai"
                           :min="formPekerjaan.tahun_mulai" :max="currentYear + 5"
                           placeholder="Kosongkan jika aktif" id="input-tahun-selesai-kerja-<?= $tracer_instance_id ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-7">Status Kerja <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="formPekerjaan.status_kerja" id="select-status-kerja-<?= $tracer_instance_id ?>">
                        <option value="Kontrak">Kontrak</option>
                        <option value="Tetap">Tetap / Permanent</option>
                        <option value="Magang">Magang</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-success px-4 rounded-3" :disabled="loadingPekerjaan"
                            @click="submitPekerjaan" id="btn-simpan-pekerjaan-<?= $tracer_instance_id ?>">
                        <span v-if="loadingPekerjaan" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-floppy me-2"></i>
                        {{ loadingPekerjaan ? 'Menyimpan...' : 'Simpan Pekerjaan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div><!-- End Vue mount -->

<script>
{
    const { ref, computed, onMounted } = Vue;

    window.VueAppRegistry.register(<?= json_encode($tracer_vue_selector, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, {
        setup() {
            const activeTab      = ref(<?= json_encode($tracer_initial_tab, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const currentYear    = ref(new Date().getFullYear());
            const loadingKuliah  = ref(false);
            const loadingPekerjaan = ref(false);

            const alertKuliah    = ref({ msg: '', type: 'success' });
            const alertPekerjaan = ref({ msg: '', type: 'success' });

            const riwayatKuliah    = ref([]);
            const riwayatPekerjaan = ref([]);
            const listJalur        = ref([]);
            const listKampusProdi  = ref([]);

            const userRole = ref(<?= json_encode($userRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const isAdmin  = ref(<?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);

            const formKuliah = ref({
                is_manual: false,
                siswa_id: '',
                nama_alumni: '',
                kampus_prodi_id: '',
                jalur_masuk_id: '',
                nama_kampus: '',
                fakultas: '',
                jurusan: '',
                tahun_masuk: new Date().getFullYear(),
                tahun_lulus: null,
                status_kuliah: 'Aktif'
            });

            const formPekerjaan = ref({
                is_manual: false,
                siswa_id: '', nama_alumni: '',
                nama_perusahaan: '', posisi_jabatan: '', pendapatan_bulanan: '',
                tahun_mulai: new Date().getFullYear(),
                tahun_selesai: null,
                status_kerja: 'Kontrak'
            });

            const showSearchDropdown = ref(false);
            const searchResults = ref([]);
            const searchingStudents = ref(false);
            const selectedStudent = ref(null);
            const activeForm = ref('');

            const urlParams = new URLSearchParams(window.location.search);
            const tenantId  = urlParams.get('tenant_id') || '';

            async function fetchMasterData() {
                if (userRole.value === 'super_admin' && !tenantId) {
                    listJalur.value = [];
                    listKampusProdi.value = [];
                    return;
                }
                try {
                    const resJalur = await fetch(`/SINTA-SaaS/api/v1/kampus/jalur?tenant_id=${tenantId}`);
                    const dataJalur = await resJalur.json();
                    if(dataJalur.success) listJalur.value = dataJalur.data;

                    const resProdi = await fetch(`/SINTA-SaaS/api/v1/kampus/all-prodi?tenant_id=${tenantId}`);
                    const dataProdi = await resProdi.json();
                    if(dataProdi.success) listKampusProdi.value = dataProdi.data;
                } catch (e) {
                    console.error("Gagal load master kampus:", e);
                }
            }

            async function fetchRiwayat(type) {
                if (userRole.value === 'super_admin' && !tenantId) {
                    if (type === 'kuliah') riwayatKuliah.value = [];
                    else riwayatPekerjaan.value = [];
                    return;
                }
                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/tracer/${type}?tenant_id=${tenantId}`);
                    const data = await res.json();
                    if (data.success) {
                        if (type === 'kuliah') riwayatKuliah.value = data.data;
                        else riwayatPekerjaan.value = data.data;
                    }
                } catch (e) { console.error(e); }
            }

            onMounted(() => {
                fetchMasterData();
                fetchRiwayat('kuliah');
                fetchRiwayat('pekerjaan');
            });

            function syncKampusData() {
                const p = listKampusProdi.value.find(x => x.prodi_id === formKuliah.value.kampus_prodi_id);
                if(p) {
                    formKuliah.value.nama_kampus = p.nama_kampus;
                    formKuliah.value.jurusan = p.program_studi;
                    formKuliah.value.fakultas = p.fakultas;
                } else {
                    formKuliah.value.nama_kampus = '';
                    formKuliah.value.jurusan = '';
                    formKuliah.value.fakultas = '';
                }
            }

            async function searchStudents(formType) {
                activeForm.value = formType;
                selectedStudent.value = null;
                const query = formType === 'kuliah' ? formKuliah.value.nama_alumni : formPekerjaan.value.nama_alumni;
                if (query.trim().length < 2) {
                    searchResults.value = [];
                    showSearchDropdown.value = false;
                    return;
                }
                searchingStudents.value = true;
                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/pdss/students/search?q=${encodeURIComponent(query)}&tenant_id=${tenantId}`);
                    const data = await res.json();
                    if (data.success) {
                        searchResults.value = data.data || [];
                        showSearchDropdown.value = true;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    searchingStudents.value = false;
                }
            }

            function selectStudent(student, formType) {
                selectedStudent.value = student;
                if (formType === 'kuliah') {
                    formKuliah.value.nama_alumni = student.nama_lengkap;
                    formKuliah.value.siswa_id = student.id;
                } else {
                    formPekerjaan.value.nama_alumni = student.nama_lengkap;
                    formPekerjaan.value.siswa_id = student.id;
                }
                showSearchDropdown.value = false;
                searchResults.value = [];
            }

            function resetKuliah() {
                formKuliah.value = {
                    is_manual: formKuliah.value.is_manual,
                    siswa_id: '', nama_alumni: '',
                    kampus_prodi_id: '', jalur_masuk_id: '',
                    nama_kampus: '', fakultas: '', jurusan: '',
                    tahun_masuk: new Date().getFullYear(),
                    tahun_lulus: null, status_kuliah: 'Aktif'
                };
                if (activeForm.value === 'kuliah') selectedStudent.value = null;
            }

            function resetPekerjaan() {
                formPekerjaan.value = {
                    is_manual: formPekerjaan.value.is_manual,
                    siswa_id: '', nama_alumni: '',
                    nama_perusahaan: '', posisi_jabatan: '', pendapatan_bulanan: '',
                    tahun_mulai: new Date().getFullYear(),
                    tahun_selesai: null, status_kerja: 'Kontrak'
                };
                if (activeForm.value === 'pekerjaan') selectedStudent.value = null;
            }

            async function submitKuliah() {
                if (isAdmin.value && !formKuliah.value.is_manual && !formKuliah.value.siswa_id) {
                    alertKuliah.value = { msg: 'Silakan cari dan pilih alumni (siswa) terlebih dahulu. Atau centang "Input Alumni Luar Sistem".', type: 'danger' };
                    return;
                }
                if (!formKuliah.value.nama_kampus.trim()) {
                    alertKuliah.value = { msg: 'Nama kampus wajib diisi.', type: 'danger' };
                    return;
                }
                loadingKuliah.value = true;
                alertKuliah.value   = { msg: '', type: 'success' };
                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/tracer/kuliah?tenant_id=${tenantId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(formKuliah.value)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        alertKuliah.value = { msg: '✅ ' + data.message, type: 'success' };
                        await fetchRiwayat('kuliah');
                        resetKuliah();
                    } else {
                        alertKuliah.value = { msg: '❌ ' + (data.error || 'Gagal menyimpan.'), type: 'danger' };
                    }
                } catch (err) {
                    alertKuliah.value = { msg: '❌ Koneksi gagal. Coba lagi.', type: 'danger' };
                } finally {
                    loadingKuliah.value = false;
                }
            }

            async function submitPekerjaan() {
                if (isAdmin.value && !formPekerjaan.value.is_manual && !formPekerjaan.value.siswa_id) {
                    alertPekerjaan.value = { msg: 'Silakan cari dan pilih alumni (siswa) terlebih dahulu. Atau centang "Input Alumni Luar Sistem".', type: 'danger' };
                    return;
                }
                if (isAdmin.value && formPekerjaan.value.is_manual && !formPekerjaan.value.nama_alumni.trim()) {
                    alertPekerjaan.value = { msg: 'Nama alumni wajib diisi untuk input luar sistem.', type: 'danger' };
                    return;
                }
                if (!formPekerjaan.value.nama_perusahaan.trim() || !formPekerjaan.value.posisi_jabatan.trim()) {
                    alertPekerjaan.value = { msg: 'Nama perusahaan dan posisi wajib diisi.', type: 'danger' };
                    return;
                }
                loadingPekerjaan.value = true;
                alertPekerjaan.value   = { msg: '', type: 'success' };
                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/tracer/pekerjaan?tenant_id=${tenantId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(formPekerjaan.value)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        alertPekerjaan.value = { msg: '✅ ' + data.message, type: 'success' };
                        await fetchRiwayat('pekerjaan');
                        resetPekerjaan();
                    } else {
                        alertPekerjaan.value = { msg: '❌ ' + (data.error || 'Gagal menyimpan.'), type: 'danger' };
                    }
                } catch (err) {
                    alertPekerjaan.value = { msg: '❌ Koneksi gagal. Coba lagi.', type: 'danger' };
                } finally {
                    loadingPekerjaan.value = false;
                }
            }

            async function hapusKuliah(id) {
                if (!confirm('Hapus riwayat kuliah ini? Tindakan tidak dapat dibatalkan.')) return;
                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/tracer/kuliah/delete?id=${id}&tenant_id=${tenantId}`, {
                        method: 'DELETE',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        riwayatKuliah.value = riwayatKuliah.value.filter(k => k.id !== id);
                        alertKuliah.value = { msg: '✅ Data berhasil dihapus.', type: 'success' };
                    } else {
                        alert(data.error || 'Gagal menghapus data.');
                    }
                } catch (e) {
                    alert('Koneksi gagal. Coba lagi.');
                }
            }

            async function hapusPekerjaan(id) {
                if (!confirm('Hapus riwayat pekerjaan ini? Tindakan tidak dapat dibatalkan.')) return;
                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/tracer/pekerjaan/delete?id=${id}&tenant_id=${tenantId}`, {
                        method: 'DELETE',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        riwayatPekerjaan.value = riwayatPekerjaan.value.filter(p => p.id !== id);
                        alertPekerjaan.value = { msg: '✅ Data berhasil dihapus.', type: 'success' };
                    } else {
                        alert(data.error || 'Gagal menghapus data.');
                    }
                } catch (e) {
                    alert('Koneksi gagal. Coba lagi.');
                }
            }

            return {
                activeTab, currentYear, isAdmin,
                loadingKuliah, loadingPekerjaan,
                alertKuliah, alertPekerjaan,
                riwayatKuliah, riwayatPekerjaan,
                formKuliah, formPekerjaan,
                submitKuliah, submitPekerjaan,
                hapusKuliah, hapusPekerjaan,
                userRole, showSearchDropdown, searchResults, searchingStudents, selectedStudent, activeForm,
                searchStudents, selectStudent,
                listJalur, listKampusProdi, syncKampusData, resetKuliah, resetPekerjaan
            };
        }
    });
}
</script>
