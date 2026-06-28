<?php
/**
 * View: Tracer Study / Portofolio Alumni
 * Hanya dapat diakses oleh siswa berstatus 'Lulus' atau Admin Sekolah / Super Admin.
 */
$userRole  = $data['user_role']         ?? ($_SESSION['role_name']    ?? '');
$userNama  = $data['user_nama']         ?? ($_SESSION['nama_lengkap'] ?? 'Alumni');
$kuliah    = $data['riwayat_kuliah']    ?? [];
$pekerjaan = $data['riwayat_pekerjaan'] ?? [];
$baseUrl   = '/SINTA-SaaS';
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

    .tracer-tab-btn {
        padding: 0.65rem 1.4rem;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        border: 2px solid var(--tracer-border);
        background: transparent;
        color: #64748b;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .tracer-tab-btn.active-tab {
        background: var(--tracer-blue);
        border-color: var(--tracer-blue);
        color: #fff;
        box-shadow: 0 4px 12px rgba(37,99,235,0.2);
    }
    .tracer-tab-btn:hover:not(.active-tab) {
        border-color: var(--tracer-blue);
        color: var(--tracer-blue);
        background: #eff6ff;
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

<!-- Vue App Mount Point -->
<div id="tracerApp" v-cloak>

    <!-- ================================================================
         BANNER INFO STATUS ALUMNI
    ================================================================ -->
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

    <!-- ================================================================
         TAB NAVIGATION
    ================================================================ -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <button class="tracer-tab-btn" :class="{'active-tab': activeTab === 'kuliah'}"
                @click="activeTab = 'kuliah'" id="tab-kuliah">
            <i class="bi bi-mortarboard me-1"></i> Riwayat Kuliah
            <span class="badge bg-primary ms-1 rounded-pill">{{ riwayatKuliah.length }}</span>
        </button>
        <button class="tracer-tab-btn" :class="{'active-tab': activeTab === 'pekerjaan'}"
                @click="activeTab = 'pekerjaan'" id="tab-pekerjaan">
            <i class="bi bi-briefcase me-1"></i> Riwayat Pekerjaan
            <span class="badge bg-success ms-1 rounded-pill">{{ riwayatPekerjaan.length }}</span>
        </button>
    </div>

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
            <table class="table table-hover align-middle" id="tbl-kuliah">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kampus</th>
                        <th>Fakultas / Jurusan</th>
                        <th>Tahun Masuk</th>
                        <th>Tahun Lulus</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in riwayatKuliah" :key="item.id">
                        <td class="text-muted">{{ idx + 1 }}</td>
                        <td class="fw-semibold">{{ item.nama_kampus }}</td>
                        <td class="text-muted">{{ item.fakultas || '—' }} / {{ item.jurusan || '—' }}</td>
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
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="empty-state">
            <i class="bi bi-mortarboard fs-1 d-block mb-2"></i>
            Belum ada riwayat kuliah. Tambahkan di bawah ini.
        </div>

        <!-- Form Tambah Riwayat Kuliah (Reaktif) -->
        <div class="form-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Riwayat Kuliah</h6>

            <div class="row g-3">
                <div class="col-md-12" v-if="userRole !== 'siswa'">
                    <label class="form-label fw-semibold fs-7">Nama Alumni (Siswa) <span class="text-danger">*</span></label>
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

                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-7">Nama Kampus <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="formKuliah.nama_kampus"
                           placeholder="Universitas Indonesia" id="input-nama-kampus" maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-7">Fakultas</label>
                    <input type="text" class="form-control" v-model="formKuliah.fakultas"
                           placeholder="Fakultas Teknik" id="input-fakultas" maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-7">Jurusan / Program Studi</label>
                    <input type="text" class="form-control" v-model="formKuliah.jurusan"
                           placeholder="Teknik Informatika" id="input-jurusan" maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold fs-7">Tahun Masuk <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" v-model.number="formKuliah.tahun_masuk"
                           :min="2000" :max="currentYear + 1" placeholder="2022" id="input-tahun-masuk">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold fs-7">Tahun Lulus</label>
                    <input type="number" class="form-control" v-model.number="formKuliah.tahun_lulus"
                           :min="formKuliah.tahun_masuk" :max="currentYear + 5"
                           placeholder="Kosongkan jika masih aktif" id="input-tahun-lulus">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-7">Status Kuliah <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="formKuliah.status_kuliah" id="select-status-kuliah">
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Drop">Drop Out</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary px-4 rounded-3" :disabled="loadingKuliah"
                            @click="submitKuliah" id="btn-simpan-kuliah">
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
            <table class="table table-hover align-middle" id="tbl-pekerjaan">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Perusahaan</th>
                        <th>Posisi / Jabatan</th>
                        <th>Tahun Mulai</th>
                        <th>Tahun Selesai</th>
                        <th>Pendapatan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in riwayatPekerjaan" :key="item.id">
                        <td class="text-muted">{{ idx + 1 }}</td>
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
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="empty-state">
            <i class="bi bi-briefcase fs-1 d-block mb-2"></i>
            Belum ada riwayat pekerjaan. Tambahkan di bawah ini.
        </div>

        <!-- Form Tambah Riwayat Pekerjaan (Reaktif) -->
        <div class="form-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Riwayat Pekerjaan</h6>

            <div class="row g-3">
                <div class="col-md-12" v-if="userRole !== 'siswa'">
                    <label class="form-label fw-semibold fs-7">Nama Alumni (Siswa) <span class="text-danger">*</span></label>
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

                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-7">Nama Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="formPekerjaan.nama_perusahaan"
                           placeholder="PT. Contoh Indonesia" id="input-nama-perusahaan" maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-7">Posisi / Jabatan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="formPekerjaan.posisi_jabatan"
                           placeholder="Software Engineer" id="input-posisi" maxlength="255">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-7">Pendapatan Bulanan</label>
                    <select class="form-select" v-model="formPekerjaan.pendapatan_bulanan" id="select-pendapatan">
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
                           :min="2000" :max="currentYear + 1" placeholder="2023" id="input-tahun-mulai-kerja">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold fs-7">Tahun Selesai</label>
                    <input type="number" class="form-control" v-model.number="formPekerjaan.tahun_selesai"
                           :min="formPekerjaan.tahun_mulai" :max="currentYear + 5"
                           placeholder="Kosongkan jika aktif" id="input-tahun-selesai-kerja">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-7">Status Kerja <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="formPekerjaan.status_kerja" id="select-status-kerja">
                        <option value="Kontrak">Kontrak</option>
                        <option value="Tetap">Tetap / Permanent</option>
                        <option value="Magang">Magang</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-success px-4 rounded-3" :disabled="loadingPekerjaan"
                            @click="submitPekerjaan" id="btn-simpan-pekerjaan">
                        <span v-if="loadingPekerjaan" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-floppy me-2"></i>
                        {{ loadingPekerjaan ? 'Menyimpan...' : 'Simpan Pekerjaan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div><!-- End #tracerApp -->

<script>
{
    const { ref, computed } = Vue;

    window.VueAppRegistry.register('#tracerApp', {
        setup() {
            const activeTab      = ref('kuliah');
            const currentYear    = ref(new Date().getFullYear());
            const loadingKuliah  = ref(false);
            const loadingPekerjaan = ref(false);

            const alertKuliah    = ref({ msg: '', type: 'success' });
            const alertPekerjaan = ref({ msg: '', type: 'success' });

            // Initial data dari PHP
            const riwayatKuliah    = ref(<?= json_encode($kuliah, JSON_UNESCAPED_UNICODE) ?>);
            const riwayatPekerjaan = ref(<?= json_encode($pekerjaan, JSON_UNESCAPED_UNICODE) ?>);

            const formKuliah = ref({
                siswa_id: '', nama_alumni: '',
                nama_kampus: '', fakultas: '', jurusan: '',
                tahun_masuk: new Date().getFullYear(),
                tahun_lulus: null,
                status_kuliah: 'Aktif'
            });

            const formPekerjaan = ref({
                siswa_id: '', nama_alumni: '',
                nama_perusahaan: '', posisi_jabatan: '', pendapatan_bulanan: '',
                tahun_mulai: new Date().getFullYear(),
                tahun_selesai: null,
                status_kerja: 'Kontrak'
            });

            const userRole = ref('<?= $userRole ?>');
            const showSearchDropdown = ref(false);
            const searchResults = ref([]);
            const searchingStudents = ref(false);
            const selectedStudent = ref(null);
            const activeForm = ref('');

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
                    const res = await fetch(`/SINTA-SaaS/api/v1/pdss/students/search?q=${encodeURIComponent(query)}`);
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
                    siswa_id: '', nama_alumni: '',
                    nama_kampus: '', fakultas: '', jurusan: '',
                    tahun_masuk: new Date().getFullYear(),
                    tahun_lulus: null, status_kuliah: 'Aktif'
                };
                if (activeForm.value === 'kuliah') selectedStudent.value = null;
            }

            function resetPekerjaan() {
                formPekerjaan.value = {
                    siswa_id: '', nama_alumni: '',
                    nama_perusahaan: '', posisi_jabatan: '', pendapatan_bulanan: '',
                    tahun_mulai: new Date().getFullYear(),
                    tahun_selesai: null, status_kerja: 'Kontrak'
                };
                if (activeForm.value === 'pekerjaan') selectedStudent.value = null;
            }

            async function submitKuliah() {
                if (userRole.value !== 'siswa' && !formKuliah.value.siswa_id) {
                    alertKuliah.value = { msg: 'Silakan cari dan pilih alumni (siswa) terlebih dahulu.', type: 'danger' };
                    return;
                }
                if (!formKuliah.value.nama_kampus.trim()) {
                    alertKuliah.value = { msg: 'Nama kampus wajib diisi.', type: 'danger' };
                    return;
                }
                loadingKuliah.value = true;
                alertKuliah.value   = { msg: '', type: 'success' };
                try {
                    const res = await fetch('/SINTA-SaaS/api/v1/tracer/kuliah', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(formKuliah.value)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        alertKuliah.value = { msg: '✅ ' + data.message, type: 'success' };
                        riwayatKuliah.value.push({
                            id: data.id,
                            ...formKuliah.value
                        });
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
                if (userRole.value !== 'siswa' && !formPekerjaan.value.siswa_id) {
                    alertPekerjaan.value = { msg: 'Silakan cari dan pilih alumni (siswa) terlebih dahulu.', type: 'danger' };
                    return;
                }
                if (!formPekerjaan.value.nama_perusahaan.trim() || !formPekerjaan.value.posisi_jabatan.trim()) {
                    alertPekerjaan.value = { msg: 'Nama perusahaan dan posisi wajib diisi.', type: 'danger' };
                    return;
                }
                loadingPekerjaan.value = true;
                alertPekerjaan.value   = { msg: '', type: 'success' };
                try {
                    const res = await fetch('/SINTA-SaaS/api/v1/tracer/pekerjaan', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(formPekerjaan.value)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        alertPekerjaan.value = { msg: '✅ ' + data.message, type: 'success' };
                        riwayatPekerjaan.value.push({
                            id: data.id,
                            ...formPekerjaan.value
                        });
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

            return {
                activeTab, currentYear,
                loadingKuliah, loadingPekerjaan,
                alertKuliah, alertPekerjaan,
                riwayatKuliah, riwayatPekerjaan,
                formKuliah, formPekerjaan,
                submitKuliah, submitPekerjaan
            };
        }
    });
}
</script>
