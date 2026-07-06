<?php
/**
 * View: Tambah/Edit Siswa (Child View)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */

use App\Config\Database;

// Tentukan mode: Edit atau Tambah Baru
$isEdit = isset($data['siswa']);
$actionUrl = $isEdit ? '/SINTA-SaaS/siswa/update' : '/SINTA-SaaS/siswa/simpan';
$formTitle = $isEdit ? 'Edit Data Siswa' : 'Tambah Siswa Baru';
$idSiswa = $isEdit ? ($data['siswa']['id'] ?? '') : '';
$siswaFullData = $isEdit ? $data['siswa'] : [];
?>

<style>
    /* Premium Color Palette & Design Utilities */
    :root {
        --saas-blue: #2563eb;
        --saas-blue-light: #eff6ff;
        --saas-success: #10b981;
        --saas-success-light: #ecfdf5;
        --saas-gray: #f8fafc;
        --saas-border: #e2e8f0;
        --saas-text-dark: #0f172a;
        --saas-text-gray: #64748b;
    }

    .wizard-card {
        border: none;
        border-radius: 1.25rem;
        background-color: #ffffff;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
    }

    .form-label {
        font-weight: 600;
        color: var(--saas-text-dark);
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
    }

    .form-control, .form-select {
        border-color: var(--saas-border);
        border-radius: 0.5rem;
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
        transition: all 0.2s ease-in-out;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--saas-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* Searchable Select styles */
    .cursor-pointer {
        cursor: pointer;
    }
    .hover-bg:hover {
        background-color: var(--saas-blue-light) !important;
        color: var(--saas-blue) !important;
    }
    .overflow-y-auto {
        overflow-y: auto;
    }

    .transition-all {
        transition: all 0.3s ease-in-out;
    }

    .fs-7 {
        font-size: 0.875rem;
    }

    .fs-8 {
        font-size: 0.775rem;
    }

    .fs-9 {
        font-size: 0.7rem;
    }

    /* Step Indicator Styling */
    .step-dot {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border: 2px solid #e2e8f0;
        background-color: #f8fafc;
        color: #94a3b8;
    }

    .step-active .step-dot {
        border-color: var(--saas-blue);
        background-color: var(--saas-blue);
        color: #ffffff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
    }

    .step-completed .step-dot {
        border-color: var(--saas-success);
        background-color: var(--saas-success);
        color: #ffffff;
    }

    .step-line {
        height: 4px;
        background-color: var(--saas-border);
        flex-grow: 1;
        margin: 0 10px;
        position: relative;
        top: -20px;
        border-radius: 2px;
    }

    .step-line-fill {
        height: 100%;
        background-color: var(--saas-success);
        width: 0%;
        transition: width 0.3s ease;
        border-radius: 2px;
    }

    /* Sub-tabs for Parents */
    .nav-pills-custom .nav-link {
        color: var(--saas-text-gray);
        font-weight: 600;
        border-radius: 0.5rem;
        padding: 0.5rem 1.25rem;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }

    .nav-pills-custom .nav-link.active {
        background-color: var(--saas-blue-light);
        color: var(--saas-blue);
    }

    /* Upload Area Interface */
    .upload-box {
        border: 2px dashed var(--saas-border);
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
        background-color: var(--saas-gray);
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
    }

    .upload-box:hover {
        border-color: var(--saas-blue);
        background-color: var(--saas-blue-light);
    }

    .upload-box input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .upload-icon {
        font-size: 1.75rem;
        color: var(--saas-text-gray);
        margin-bottom: 0.5rem;
        transition: color 0.2s ease;
    }

    .upload-box:hover .upload-icon {
        color: var(--saas-blue);
    }

    [v-cloak] {
        display: none !important;
    }

    /* Vue transition classes for KIP fade in/out */
    .fade-enter-active, .fade-leave-active {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .fade-enter-from, .fade-leave-to {
        opacity: 0;
        transform: translateY(-10px);
    }

    /* Modern Upload Status Bar */
    .upload-status-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 0.75rem;
        padding: 0.5rem 0.85rem;
        border-radius: 0.5rem;
        background-color: rgba(25, 135, 84, 0.08);
        border: 1px solid rgba(25, 135, 84, 0.15);
        transition: all 0.2s ease-in-out;
    }
    .upload-status-bar:hover {
        background-color: rgba(25, 135, 84, 0.12);
        border-color: rgba(25, 135, 84, 0.25);
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(25, 135, 84, 0.05);
    }
    .upload-status-bar .status-text {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem; /* 14px - proportional & modern */
        font-weight: 600;
        color: #146c43;
    }
    .upload-status-bar .status-text i {
        font-size: 1.05rem;
    }
    .upload-status-bar .status-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .upload-status-bar .btn-view-file {
        background-color: #198754;
        color: #ffffff;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease-in-out;
        box-shadow: 0 2px 4px rgba(25, 135, 84, 0.15);
    }
    .upload-status-bar .btn-view-file:hover {
        background-color: #146c43;
        color: #ffffff;
        box-shadow: 0 4px 8px rgba(25, 135, 84, 0.25);
    }
    .upload-status-bar .btn-external-link {
        color: rgba(25, 135, 84, 0.7);
        transition: color 0.15s ease-in-out, transform 0.15s ease-in-out;
        display: inline-flex;
        align-items: center;
        text-decoration: none;
    }
    .upload-status-bar .btn-external-link:hover {
        color: #146c43;
        transform: scale(1.1);
    }
</style>

<!-- App Header -->
<?php
$userRole = $_SESSION['role_name'] ?? '';
$userNama = $_SESSION['nama_lengkap'] ?? '';
if ($userRole === 'siswa'):
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">Pembaruan Data Mandiri</h2>
        <p class="text-muted fs-7">Halo, <strong><?= htmlspecialchars($userNama) ?></strong> - Silakan perbarui data diri Anda secara berkala.</p>
    </div>
</div>
<?php else: ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars($formTitle) ?></h2>
        <p class="text-muted fs-7">Lengkapi formulir registrasi multi-step di bawah ini sesuai database SINTA-SaaS.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="/SINTA-SaaS/pengguna" class="btn btn-outline-secondary d-flex align-items-center rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-2"></i> Kembali ke Daftar
        </a>
    </div>
</div>
<?php endif; ?>
<?php
// Status lock variables — $siswaStatus & $userRole passed from Controller
$siswaStatus = $siswaStatus ?? ($data['siswa_status'] ?? 'Aktif');
$isLocked    = ($userRole === 'siswa' && ($siswaStatus === 'Lulus' || $siswaStatus === 'Pindah'));
?>

<?php if ($isLocked): ?>
<!-- ============================================================
     STATE LOCK BANNER: Tampil jika siswa berstatus Lulus
     Server-side rendered untuk keamanan (tidak bisa dimanipulasi JS)
============================================================ -->
<div class="alert border-0 rounded-4 p-4 mb-4 shadow-sm d-flex align-items-start gap-3"
     style="background:linear-gradient(135deg,#fff7ed,#fef3c7);border-left:4px solid #f59e0b !important;"
     id="state-lock-banner">
    <div class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center"
         style="width:48px;height:48px;background:#f59e0b;">
        <i class="bi bi-lock-fill text-white fs-5"></i>
    </div>
    <div class="flex-grow-1">
        <h6 class="fw-bold mb-1" style="color:#92400e;">🔒 Data Diri Telah Dikunci (Status: <?= htmlspecialchars($siswaStatus) ?>)</h6>
        <p class="mb-2 text-muted fs-7">
            Karena status Anda adalah <strong><?= htmlspecialchars($siswaStatus) ?></strong>, data pokok Anda dikunci secara otomatis
            oleh sistem untuk menjaga integritas arsip akademik. Hubungi Admin Sekolah jika ada kesalahan data.
        </p>
        <a href="/SINTA-SaaS/tracer-study" class="btn btn-sm btn-warning fw-semibold rounded-3">
            <i class="bi bi-mortarboard-fill me-1"></i> Isi Tracer Study Alumni →
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Main Wizard Component Container (Vue Mounted) -->
<div id="studentWizardApp" v-cloak class="wizard-card p-4 p-md-5 mb-5"
     data-is-locked="<?= $isLocked ? 'true' : 'false' ?>">
    
    <!-- Header Card Khusus Siswa -->
    <div v-if="userRole === 'siswa'" class="alert alert-primary border-0 rounded-4 p-4 mb-5 shadow-sm d-flex align-items-center gap-3">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-person-fill-check fs-4"></i>
        </div>
        <div>
            <h5 class="fw-bold text-primary-emphasis mb-1">Pembaruan Data Diri</h5>
            <p class="text-muted fs-7 mb-0">Halo, <strong>{{ form.nama_lengkap || 'Siswa' }}</strong> - Silakan perbarui data diri Anda.</p>
        </div>
    </div>

    <!-- Step Indicator Progress Bar -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center position-relative flex-nowrap">
            <!-- Step Items Loop -->
            <div v-for="step in 5" :key="step" @click="goToStep(step)" class="d-flex flex-column align-items-center text-center flex-fill position-relative" style="z-index: 2; cursor: pointer; user-select: none;">
                <div :class="{
                         'step-active': currentStep === step,
                         'step-completed': currentStep > step
                     }" class="mb-2">
                    <div class="step-dot shadow-sm transition-all">
                        <i v-if="currentStep > step" class="bi bi-check-lg text-white font-bold"></i>
                        <span v-else>{{ step }}</span>
                    </div>
                </div>
                <span class="fs-8 fw-semibold d-none d-md-inline-block mt-2" :class="currentStep === step ? 'text-primary fw-bold' : (currentStep > step ? 'text-dark' : 'text-muted')">
                    {{ stepNames[step - 1] }}
                </span>
            </div>
        </div>
        <!-- Progress Line Connector (Desktop only position hack) -->
        <div class="d-none d-md-block" style="margin-top: -38px; padding: 0 8%;">
            <div class="step-line">
                <div class="step-line-fill" :style="{ width: ((currentStep - 1) / 4 * 100) + '%' }"></div>
            </div>
        </div>
        <!-- Mobile only active step description badge -->
        <div class="d-block d-md-none text-center mt-3">
            <span class="badge bg-primary px-3 py-2 fs-8 rounded-pill shadow-sm">
                Langkah {{ currentStep }} dari 5: {{ stepNames[currentStep - 1] }}
            </span>
        </div>
    </div>

    <!-- Alert Form Error Notification -->
    <div v-if="errorsList.length > 0" class="alert alert-danger border-0 rounded-3 alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Mohon koreksi kesalahan input berikut:</div>
        <ul class="mb-0 fs-8 ps-3">
            <li v-for="(err, idx) in errorsList" :key="idx">{{ err }}</li>
        </ul>
        <button type="button" class="btn-close" @click="errorsList = []"></button>
    </div>

    <!-- Standard HTML Form -->
    <form id="wizardForm" action="<?= htmlspecialchars($actionUrl) ?>" method="POST" enctype="multipart/form-data" @submit.prevent="submitFullForm" novalidate>
        
        <!-- ID Siswa (Wajib untuk Mode Edit) -->
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($idSiswa) ?>">
        <?php endif; ?>

        <!-- ==================== LANGKAH 1: DATA POKOK & AKADEMIK ==================== -->
        <div v-show="currentStep === 1" data-step="1">
            <h5 class="fw-bold text-primary mb-4 pb-2 border-bottom"><i class="bi bi-person-badge-fill me-2"></i> Langkah 1: Data Pokok & Akademik</h5>
            <div class="row g-3 g-md-4">
                
                <!-- Input Sekolah khusus Super Admin -->
                <div class="col-12" v-if="userRole === 'super_admin'">
                    <label for="tenant_select" class="form-label">Sekolah / Tenant <span class="text-danger">*</span></label>
                    <select class="form-select" id="tenant_select" name="tenant_id" v-model="form.tenant_id" @change="onTenantChange" :disabled="isEdit" required>
                        <option value="" disabled>-- Pilih Sekolah --</option>
                        <option v-for="t in listTenants" :value="t.id" :key="t.id">{{ t.nama_sekolah }}</option>
                    </select>
                    <input v-if="isEdit" type="hidden" name="tenant_id" :value="form.tenant_id">
                </div>

                <!-- NIK -->
                <div class="col-md-6">
                    <label for="nik" class="form-label">NIK (Nomor Induk Kependudukan) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nik" name="nik" v-model="form.nik" placeholder="Masukkan 16 digit NIK" maxlength="16" required>
                </div>
                
                <!-- No. KK -->
                <div class="col-md-6">
                    <label for="no_kk" class="form-label">No. KK (Kartu Keluarga)</label>
                    <input type="text" class="form-control" id="no_kk" name="no_kk" v-model="form.no_kk" placeholder="Masukkan 16 digit No. KK" maxlength="16">
                </div>

                <!-- NISN -->
                <div class="col-md-6">
                    <label for="nisn" class="form-label">NISN (Nomor Induk Siswa Nasional) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nisn" name="nisn" v-model="form.nisn" placeholder="Masukkan 10 digit NISN" maxlength="10" :readonly="userRole === 'siswa'" required>
                </div>

                <!-- NIS -->
                <div class="col-md-6">
                    <label for="nis" class="form-label">NIS (Nomor Induk Siswa)</label>
                    <input type="text" class="form-control" id="nis" name="nis" v-model="form.nis" placeholder="Masukkan NIS sekolah" maxlength="20" :readonly="userRole === 'siswa'" autocomplete="off">
                </div>

                <!-- Ubah Password (Hanya muncul saat Mode Edit) -->
                <div class="col-md-6" v-if="isEdit">
                    <label for="password" class="form-label">Ubah Password</label>
                    <input type="password" class="form-control" id="password" name="password" v-model="form.password" placeholder="Kosongkan jika tidak ingin mengubah password" autocomplete="new-password">
                    <div class="form-text text-muted" style="font-size: 0.75rem;">
                        Kosongkan jika tidak ingin mengubah password.
                    </div>
                </div>

                <!-- Nama Lengkap -->
                <div class="col-md-8">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="nama_lengkap" name="nama_lengkap" v-model="form.nama_lengkap" placeholder="Masukkan nama lengkap sesuai ijazah" :readonly="userRole === 'siswa'" required>
                </div>

                <!-- Nama Panggilan -->
                <div class="col-md-4">
                    <label for="nama_panggilan" class="form-label">Nama Panggilan</label>
                    <input type="text" class="form-control" id="nama_panggilan" name="nama_panggilan" v-model="form.nama_panggilan" placeholder="Nama panggilan">
                </div>

                <!-- Jenis Kelamin -->
                <div class="col-md-6">
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" v-model="form.jenis_kelamin" required>
                        <option value="" disabled>-- Pilih Jenis Kelamin --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <!-- Agama -->
                <div class="col-md-6">
                    <label for="agama" class="form-label">Agama <span class="text-danger">*</span></label>
                    <select class="form-select" id="agama" name="agama" v-model="form.agama" required>
                        <option value="" disabled>-- Pilih Agama --</option>
                        <option value="Islam">Islam</option>
                        <option value="Kristen">Kristen</option>
                        <option value="Katolik">Katolik</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                        <option value="Khonghucu">Khonghucu</option>
                    </select>
                </div>

                <!-- Kewarganegaraan -->
                <div class="col-md-6">
                    <label for="kewarganegaraan" class="form-label">Kewarganegaraan <span class="text-danger">*</span></label>
                    <select class="form-select" id="kewarganegaraan" name="kewarganegaraan" v-model="form.kewarganegaraan" required>
                        <option value="WNI">Warga Negara Indonesia (WNI)</option>
                        <option value="WNA">Warga Negara Asing (WNA)</option>
                    </select>
                </div>

                <!-- Bahasa Sehari-hari -->
                <div class="col-md-6">
                    <label for="bahasa_sehari_hari" class="form-label">Bahasa Sehari-hari <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="bahasa_sehari_hari" name="bahasa_sehari_hari" v-model="form.bahasa_sehari_hari" placeholder="Contoh: Indonesia, Jawa" required>
                </div>

                <!-- Tempat Lahir -->
                <div class="col-md-6">
                    <label for="tempat_lahir" class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           id="tempat_lahir" 
                           name="tempat_lahir" 
                           v-model="form.tempat_lahir" 
                           placeholder="Masukkan tempat lahir" 
                           required>
                    <div class="form-text text-muted" style="font-size: 0.75rem;">
                        Sesuai dengan ijazah.
                    </div>
                </div>

                <!-- Tanggal Lahir -->
                <div class="col-md-6">
                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" v-model="form.tanggal_lahir" required>
                </div>

                <!-- Asal Sekolah -->
                <div class="col-md-6">
                    <label for="sekolah_asal" class="form-label">Asal Sekolah Sebelumnya</label>
                    <input type="text" class="form-control" id="sekolah_asal" name="sekolah_asal" v-model="form.sekolah_asal" placeholder="Contoh: SMP Negeri 1 Jakarta">
                </div>

                <!-- Nomor Ijazah Sebelumnya -->
                <div class="col-md-4">
                    <label for="no_ijazah_sebelumnya" class="form-label">No. Ijazah Sebelumnya</label>
                    <input type="text" class="form-control" id="no_ijazah_sebelumnya" name="no_ijazah_sebelumnya" v-model="form.no_ijazah_sebelumnya" placeholder="Masukkan nomor ijazah">
                </div>

                <!-- Tanggal Ijazah Sebelumnya -->
                <div class="col-md-4">
                    <label for="tanggal_ijazah_sebelumnya" class="form-label">Tanggal Ijazah Sebelumnya</label>
                    <input type="date" class="form-control" id="tanggal_ijazah_sebelumnya" name="tanggal_ijazah_sebelumnya" v-model="form.tanggal_ijazah_sebelumnya">
                </div>

                <!-- Lama Belajar Sebelumnya -->
                <div class="col-md-4">
                    <label for="lama_belajar_sebelumnya" class="form-label">Lama Belajar Sebelumnya (Tahun)</label>
                    <input type="number" class="form-control" id="lama_belajar_sebelumnya" name="lama_belajar_sebelumnya" v-model.number="form.lama_belajar_sebelumnya" min="1" max="10" placeholder="Contoh: 3">
                </div>

                <!-- Status Siswa -->
                <div class="col-md-6">
                    <label for="status" class="form-label">Status Siswa <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" v-model="form.status" required :disabled="!['super_admin', 'operator_sekolah'].includes(userRole)">
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Pindah">Pindah</option>
                    </select>
                </div>

                <!-- Row divider: Data Akademik Relasional -->
                <div class="col-12 mt-4">
                    <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-mortarboard me-2"></i>Data Penempatan Akademik</h6>
                </div>

                <!-- Angkatan -->
                <div class="col-md-4">
                    <label for="id_angkatan" class="form-label">Tahun Angkatan <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_angkatan" name="id_angkatan" v-model="form.id_angkatan" :disabled="loadingAcademic" required>
                        <option value="" disabled>{{ loadingAcademic ? 'Memuat data...' : '-- Pilih Angkatan --' }}</option>
                        <option v-for="opt in acOptions.angkatan" :key="opt.id" :value="opt.id">{{ opt.tahun_angkatan }}</option>
                    </select>
                </div>

                <!-- Tahun Ajaran -->
                <div class="col-md-4">
                    <label for="id_tahun_ajaran" class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_tahun_ajaran" name="id_tahun_ajaran" v-model="form.id_tahun_ajaran" :disabled="loadingAcademic" required>
                        <option value="" disabled>{{ loadingAcademic ? 'Memuat data...' : '-- Pilih Tahun Ajaran --' }}</option>
                        <option v-for="opt in acOptions.tahun_ajaran" :key="opt.id" :value="opt.id">{{ opt.tahun_ajaran }}</option>
                    </select>
                </div>

                <!-- Jenjang -->
                <div class="col-md-4">
                    <label for="id_jenjang" class="form-label">Jenjang Pendidikan <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_jenjang" name="id_jenjang" v-model="form.id_jenjang" @change="onJenjangChange" :disabled="loadingAcademic" required>
                        <option value="" disabled>{{ loadingAcademic ? 'Memuat data...' : '-- Pilih Jenjang --' }}</option>
                        <option v-for="opt in acOptions.jenjang" :key="opt.id" :value="opt.id">{{ opt.nama_jenjang }}</option>
                    </select>
                </div>

                <!-- Jurusan -->
                <div class="col-md-4">
                    <label for="id_jurusan" class="form-label">Jurusan <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_jurusan" name="id_jurusan" v-model="form.id_jurusan" @change="onJurusanChange" :disabled="loadingAcademic || !form.id_jenjang" required>
                        <option value="" disabled>{{ loadingAcademic ? 'Memuat data...' : '-- Pilih Jurusan --' }}</option>
                        <option v-for="opt in filteredJurusan" :key="opt.id" :value="opt.id">{{ opt.nama_jurusan }}</option>
                    </select>
                </div>

                <!-- Kelas (Rombel) - Di-filter reaktif berdasarkan Jenjang & Jurusan -->
                <div class="col-md-4">
                    <label for="id_kelas" class="form-label">Rombel / Kelas <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_kelas" name="id_kelas" v-model="form.id_kelas" :disabled="loadingAcademic || !form.id_jenjang || !form.id_jurusan" required>
                        <option value="" disabled>{{ loadingAcademic ? 'Memuat data...' : '-- Pilih Rombel --' }}</option>
                        <option v-for="opt in filteredKelas" :key="opt.id" :value="opt.id">{{ opt.nama_kelas }}</option>
                    </select>
                </div>

                <!-- Pendidikan Terakhir -->
                <div class="col-md-4">
                    <label for="id_pendidikan" class="form-label">Pendidikan Ditempuh <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_pendidikan" name="id_pendidikan" v-model="form.id_pendidikan" :disabled="loadingAcademic" required>
                        <option value="" disabled>{{ loadingAcademic ? 'Memuat data...' : '-- Pilih Pendidikan --' }}</option>
                        <option v-for="opt in acOptions.pendidikan" :key="opt.id" :value="opt.id">{{ opt.nama_pendidikan }}</option>
                    </select>
                </div>

                <!-- Ukuran Seragam Sekolah -->
                <div class="col-md-6">
                    <label for="ukuran_seragam_sekolah" class="form-label">Ukuran Seragam Sekolah (S/M/L/XL/dst)</label>
                    <input type="text" class="form-control text-uppercase" id="ukuran_seragam_sekolah" name="ukuran_seragam_sekolah" v-model="form.ukuran_seragam_sekolah" placeholder="Contoh: M" maxlength="3" style="text-transform: uppercase;">
                </div>

                <!-- Ukuran Seragam Olahraga -->
                <div class="col-md-6">
                    <label for="ukuran_seragam_olahraga" class="form-label">Ukuran Seragam Olahraga (S/M/L/XL/dst)</label>
                    <input type="text" class="form-control text-uppercase" id="ukuran_seragam_olahraga" name="ukuran_seragam_olahraga" v-model="form.ukuran_seragam_olahraga" placeholder="Contoh: L" maxlength="3" style="text-transform: uppercase;">
                </div>

            </div>
        </div>

        <!-- ==================== LANGKAH 2: ALAMAT & KONTAK ==================== -->
        <div v-show="currentStep === 2" data-step="2">
            <h5 class="fw-bold text-primary mb-4 pb-2 border-bottom"><i class="bi bi-geo-alt-fill me-2"></i> Langkah 2: Detail Alamat & Kontak</h5>
            <div class="row g-3 g-md-4">
                
                <!-- Alamat KK -->
                <div class="col-md-6">
                    <label for="alamat_kk" class="form-label">Alamat Sesuai KK <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="alamat_kk" name="alamat_kk" rows="3" v-model="form.alamat_kk" placeholder="Masukkan alamat lengkap sesuai Kartu Keluarga" required></textarea>
                </div>

                <!-- Alamat Domisili -->
                <div class="col-md-6">
                    <label for="alamat_domisili" class="form-label">Alamat Domisili Sekarang <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="alamat_domisili" name="alamat_domisili" rows="3" v-model="form.alamat_domisili" placeholder="Masukkan alamat domisili saat ini" required></textarea>
                </div>

                <!-- RT -->
                <div class="col-md-4">
                    <label for="rt" class="form-label">RT <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="rt" name="rt" v-model="form.rt" placeholder="Contoh: 001" maxlength="3" required>
                </div>

                <!-- RW -->
                <div class="col-md-4">
                    <label for="rw" class="form-label">RW <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="rw" name="rw" v-model="form.rw" placeholder="Contoh: 010" maxlength="3" required>
                </div>

                <!-- Kode Pos -->
                <div class="col-md-4">
                    <label for="kode_pos" class="form-label">Kode Pos <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="kode_pos" name="kode_pos" v-model="form.kode_pos" placeholder="5 digit" maxlength="5" required>
                </div>

                <!-- Chain Dropdown Wilayah: Provinsi -->
                <div class="col-md-6">
                    <label for="province_select" class="form-label">Provinsi <span class="text-danger">*</span></label>
                    <select class="form-select" id="province_select" v-model="form.id_provinsi" @change="onProvinceChange" :disabled="loadingProvinces" required>
                        <option value="" disabled>{{ loadingProvinces ? 'Memuat data...' : '-- Pilih Provinsi --' }}</option>
                        <option v-for="p in provinces" :key="p.id_provinsi" :value="p.id_provinsi">{{ p.nama_provinsi }}</option>
                    </select>
                </div>

                <!-- Chain Dropdown Wilayah: Kota -->
                <div class="col-md-6">
                    <label for="city_select" class="form-label">Kabupaten / Kota <span class="text-danger">*</span></label>
                    <select class="form-select" id="city_select" v-model="form.id_kota" @change="onCityChange" :disabled="loadingCities || !form.id_provinsi" required>
                        <option value="" disabled>{{ loadingCities ? 'Memuat data...' : '-- Pilih Kota --' }}</option>
                        <option v-for="c in cityFiltered" :key="c.id_kota" :value="c.id_kota">{{ c.nama_kota }}</option>
                    </select>
                </div>

                <!-- Chain Dropdown Wilayah: Kecamatan -->
                <div class="col-md-6">
                    <label for="district_select" class="form-label">Kecamatan <span class="text-danger">*</span></label>
                    <select class="form-select" id="district_select" v-model="form.id_kecamatan" @change="onDistrictChange" :disabled="loadingDistricts || !form.id_kota" required>
                        <option value="" disabled>{{ loadingDistricts ? 'Memuat data...' : '-- Pilih Kecamatan --' }}</option>
                        <option v-for="d in districts" :key="d.id_kecamatan" :value="d.id_kecamatan">{{ d.nama_kecamatan }}</option>
                    </select>
                </div>

                <!-- Chain Dropdown Wilayah: Kelurahan (Final Table ID) -->
                <div class="col-md-6">
                    <label for="id_kelurahan" class="form-label">Kelurahan <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_kelurahan" name="id_kelurahan" v-model="form.id_kelurahan" :disabled="loadingSubdistricts || !form.id_kecamatan" required>
                        <option value="" disabled>{{ loadingSubdistricts ? 'Memuat data...' : '-- Pilih Kelurahan --' }}</option>
                        <option v-for="k in subdistricts" :key="k.id_kelurahan" :value="k.id_kelurahan">{{ k.nama_kelurahan }}</option>
                    </select>
                </div>

                <!-- Status Tinggal -->
                <div class="col-md-6">
                    <label for="status_tinggal" class="form-label">Status Tinggal <span class="text-danger">*</span></label>
                    <select class="form-select" id="status_tinggal" name="status_tinggal" v-model="form.status_tinggal" required>
                        <option value="" disabled>-- Pilih Status Tinggal --</option>
                        <option value="Milik Sendiri">Milik Sendiri</option>
                        <option value="Menumpang">Menumpang</option>
                        <option value="Kos">Kos</option>
                        <option value="Kontrak / Sewa">Kontrak / Sewa</option>
                        <option value="Asrama Sekolah">Asrama Sekolah</option>
                        <option value="Rumah Dinas">Rumah Dinas</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <!-- Tinggal Dengan -->
                <div class="col-md-6">
                    <label for="tinggal_dengan" class="form-label">Tinggal Dengan <span class="text-danger">*</span></label>
                    <select class="form-select" id="tinggal_dengan" name="tinggal_dengan" v-model="form.tinggal_dengan" required>
                        <option value="Orang Tua">Orang Tua</option>
                        <option value="Wali">Wali</option>
                        <option value="Kos">Kos</option>
                        <option value="Asrama">Asrama</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <!-- Email Siswa -->
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Siswa <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" v-model="form.email" placeholder="Contoh: siswa@gmail.com" required autocomplete="email">
                </div>

                <!-- No Telepon Rumah -->
                <div class="col-md-4">
                    <label for="no_telepon_rumah" class="form-label">No. Telepon Rumah</label>
                    <input type="text" class="form-control" id="no_telepon_rumah" name="no_telepon_rumah" v-model="form.no_telepon_rumah" placeholder="Maks 10 digit" maxlength="10">
                </div>

                <!-- No HP Siswa -->
                <div class="col-md-4">
                    <label for="no_telepon_siswa" class="form-label">No. HP Siswa <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="no_telepon_siswa" name="no_telepon_siswa" v-model="form.no_telepon_siswa" placeholder="Maks 13 digit" maxlength="13" required>
                </div>

                <!-- No HP Orang Tua -->
                <div class="col-md-4">
                    <label for="no_telepon_orang_tua" class="form-label">No. HP Orang Tua / Wali</label>
                    <input type="text" class="form-control" id="no_telepon_orang_tua" name="no_telepon_orang_tua" v-model="form.no_telepon_orang_tua" placeholder="Maks 13 digit" maxlength="13">
                </div>

            </div>
        </div>

        <!-- ==================== LANGKAH 3: FISIK, RIWAYAT & KESEJAHTERAAN ==================== -->
        <div v-show="currentStep === 3" data-step="3">
            <h5 class="fw-bold text-primary mb-4 pb-2 border-bottom"><i class="bi bi-heart-pulse-fill me-2"></i> Langkah 3: Kondisi Fisik, Riwayat & Kesejahteraan</h5>
            <div class="row g-3 g-md-4">
                
                <!-- Tinggi Badan -->
                <div class="col-md-4">
                    <label for="tinggi_badan" class="form-label">Tinggi Badan (cm) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="tinggi_badan" name="tinggi_badan" v-model.number="form.tinggi_badan" min="30" max="250" required>
                </div>

                <!-- Berat Badan -->
                <div class="col-md-4">
                    <label for="berat_badan" class="form-label">Berat Badan (kg) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="berat_badan" name="berat_badan" v-model.number="form.berat_badan" min="5" max="200" required>
                </div>

                <!-- Lingkar Kepala -->
                <div class="col-md-4">
                    <label for="lingkar_kepala" class="form-label">Lingkar Kepala (cm) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="lingkar_kepala" name="lingkar_kepala" v-model.number="form.lingkar_kepala" min="20" max="100" required>
                </div>

                <!-- Golongan Darah -->
                <div class="col-md-4">
                    <label for="golongan_darah" class="form-label">Golongan Darah <span class="text-danger">*</span></label>
                    <select class="form-select" id="golongan_darah" name="golongan_darah" v-model="form.golongan_darah" required>
                        <option value="" disabled>-- Pilih --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="AB">AB</option>
                        <option value="O">O</option>
                    </select>
                </div>

                <!-- Anak Ke- -->
                <div class="col-md-4">
                    <label for="anak_ke" class="form-label">Anak Ke- (Dalam Silsilah) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="anak_ke" name="anak_ke" v-model.number="form.anak_ke" min="1" max="20" required>
                </div>

                <!-- Jumlah Saudara Kandung -->
                <div class="col-md-4">
                    <label for="jumlah_saudara" class="form-label">Jumlah Saudara Kandung <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="jumlah_saudara" name="jumlah_saudara" v-model.number="form.jumlah_saudara" min="0" max="25" required>
                </div>

                <!-- Penyakit yang Pernah Diderita -->
                <div class="col-md-12">
                    <label for="penyakit_yang_diderita" class="form-label">Riwayat Penyakit yang Diderita (Opsional)</label>
                    <input type="text" class="form-control" id="penyakit_yang_diderita" name="penyakit_yang_diderita" v-model="form.penyakit_yang_diderita" placeholder="Tulis nama penyakit jika ada (asma, jantung, alergi, dsb)">
                </div>

                <!-- Kelainan Jasmani -->
                <div class="col-md-12">
                    <label for="kelainan_jasmani" class="form-label">Kelainan Jasmani / Disabilitas <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="kelainan_jasmani" name="kelainan_jasmani" v-model="form.kelainan_jasmani" placeholder="Contoh: Tidak Ada, Tuli, Low Vision, dll" required>
                </div>

                <!-- Jarak Rumah ke Sekolah -->
                <div class="col-md-6">
                    <label for="jarak_rumah" class="form-label">Jarak Rumah ke Sekolah (Meter) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="jarak_rumah" name="jarak_rumah" v-model.number="form.jarak_rumah" placeholder="Contoh: 1500" min="1" required>
                </div>

                <!-- Transportasi -->
                <div class="col-md-6">
                    <label for="transportasi" class="form-label">Alat Transportasi Utama <span class="text-danger">*</span></label>
                    <select class="form-select" id="transportasi" name="transportasi" v-model="form.transportasi" required>
                        <option value="" disabled>-- Pilih Transportasi --</option>
                        <option value="Jalan Kaki">Jalan Kaki</option>
                        <option value="Sepeda">Sepeda</option>
                        <option value="Motor">Motor</option>
                        <option value="Mobil">Mobil</option>
                        <option value="Antar Jemput">Antar Jemput</option>
                        <option value="Angkutan Umum">Angkutan Umum</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <!-- Status Yatim/Piatu -->
                <div class="col-md-6">
                    <label for="status_anak" class="form-label">Status Anak (Yatim/Piatu)</label>
                    <select class="form-select" id="status_anak" name="status_anak" v-model="form.status_anak">
                        <option value="">-- Pilih Status (Opsional) --</option>
                        <option value="Bukan Yatim/Piatu">Lengkap (Bukan Yatim/Piatu)</option>
                        <option value="Yatim">Yatim (Tidak Ada Ayah)</option>
                        <option value="Piatu">Piatu (Tidak Ada Ibu)</option>
                        <option value="Yatim Piatu">Yatim Piatu (Tidak Ada Orang Tua)</option>
                    </select>
                </div>

                <!-- Penerima KPS / KKS -->
                <div class="col-md-6">
                    <div class="form-label d-block fw-bold mb-2">Penerima KPS / KKS (Keluarga Penerima Sejahtera)</div>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="penerima_kps" id="kps_ya" :value="1" v-model.number="form.penerima_kps">
                            <label class="form-check-label" for="kps_ya">Ya</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="penerima_kps" id="kps_tidak" :value="0" v-model.number="form.penerima_kps">
                            <label class="form-check-label" for="kps_tidak">Tidak</label>
                        </div>
                    </div>
                </div>

                <!-- Punya KIP -->
                <div class="col-md-6">
                    <div class="form-label d-block fw-bold mb-2">Memiliki Kartu Indonesia Pintar (KIP)?</div>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="punya_kip" id="kip_ya" :value="1" v-model.number="form.punya_kip">
                            <label class="form-check-label" for="kip_ya">Ya</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="punya_kip" id="kip_tidak" :value="0" v-model.number="form.punya_kip">
                            <label class="form-check-label" for="kip_tidak">Tidak</label>
                        </div>
                    </div>
                </div>

                <!-- Layak KIP -->
                <div class="col-md-6">
                    <div class="form-label d-block fw-bold mb-2">Layak Menerima PIP / KIP?</div>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="layak_kip" id="layak_ya" :value="1" v-model.number="form.layak_kip">
                            <label class="form-check-label" for="layak_ya">Ya</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="layak_kip" id="layak_tidak" :value="0" v-model.number="form.layak_kip">
                            <label class="form-check-label" for="layak_tidak">Tidak</label>
                        </div>
                    </div>
                </div>

                <!-- Nomor KIP (Muncul jika punya_kip == 1) -->
                <div class="col-md-6" v-if="form.punya_kip == 1">
                    <label for="no_kip" class="form-label">Nomor KIP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="no_kip" name="no_kip" v-model="form.no_kip" placeholder="Masukkan nomor KIP" maxlength="100" :required="form.punya_kip == 1">
                </div>

                <!-- Alasan Layak KIP (Muncul jika layak_kip == 1) -->
                <div class="col-md-6" v-show="form.layak_kip == 1">
                    <label for="alasan_layak" class="form-label">Alasan Layak KIP <span class="text-danger">*</span></label>
                    <select class="form-select" id="alasan_layak" name="alasan_layak" v-model="form.alasan_layak" :required="form.layak_kip == 1">
                        <option value="" disabled>-- Pilih Alasan --</option>
                        <option value="Siswa Miskin">Siswa Miskin</option>
                        <option value="Daerah Konflik">Daerah Konflik</option>
                        <option value="Dampak Bencana Alam">Dampak Bencana Alam</option>
                        <option value="Kelainan Fisik">Kelainan Fisik</option>
                        <option value="Keluarga Terpidana / Berada di LAPAS">Keluarga Terpidana / Berada di LAPAS</option>
                        <option value="Pemegang PKH / KPS / KKS">Pemegang PKH / KPS / KKS</option>
                        <option value="Pernah Drop Out">Pernah Drop Out</option>
                        <option value="Tidak Ada">Tidak Ada</option>
                    </select>
                </div>

            </div>
        </div>

        <!-- ==================== LANGKAH 4: DATA ORANG TUA / WALI ==================== -->
        <div v-show="currentStep === 4" data-step="4">
            <h5 class="fw-bold text-primary mb-4 pb-2 border-bottom"><i class="bi bi-people-fill me-2"></i> Langkah 4: Data Orang Tua & Wali</h5>
            
            <!-- Tab Navigation for Father, Mother, Guardian -->
            <ul class="nav nav-pills nav-pills-custom mb-4 bg-light p-1 rounded-3 gap-2" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link" :class="{active: activeParentTab === 'father'}" type="button" @click="activeParentTab = 'father'">
                        <i class="bi bi-gender-male me-2"></i>Data Ayah Kandung
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" :class="{active: activeParentTab === 'mother'}" type="button" @click="activeParentTab = 'mother'">
                        <i class="bi bi-gender-female me-2"></i>Data Ibu Kandung <span class="text-danger">*</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" :class="{active: activeParentTab === 'guardian'}" type="button" @click="activeParentTab = 'guardian'">
                        <i class="bi bi-person-bounding-box me-2"></i>Data Wali (Opsional)
                    </button>
                </li>
            </ul>

            <div class="tab-content border p-4 rounded-4 bg-white shadow-xs">
                <!-- SUB-TAB 1: AYAH KANDUNG -->
                <div v-show="activeParentTab === 'father'">
                    <h6 class="fw-bold text-dark mb-4 pb-2 border-bottom text-muted">Informasi Ayah Kandung</h6>
                    <div class="row g-3 g-md-4">
                        <div class="col-md-6">
                            <label for="nik_ayah" class="form-label">NIK Ayah</label>
                            <input type="text" class="form-control" id="nik_ayah" name="nik_ayah" v-model="form.nik_ayah" placeholder="Masukkan 16 digit NIK" maxlength="16">
                        </div>
                        <div class="col-md-6">
                            <label for="nama_ayah" class="form-label">Nama Lengkap Ayah</label>
                            <input type="text" class="form-control text-uppercase" id="nama_ayah" name="nama_ayah" v-model="form.nama_ayah" placeholder="Nama lengkap tanpa gelar">
                        </div>
                        <div class="col-md-6">
                            <div class="form-label mb-2 fw-bold">Tempat Lahir Ayah</div>
                            <searchable-select 
                                id="id_tempat_lahir_ayah" 
                                name="id_tempat_lahir_ayah" 
                                v-model="form.id_tempat_lahir_ayah" 
                                :options="citiesOptions" 
                                placeholder="-- Pilih Kota Tempat Lahir Ayah --">
                            </searchable-select>
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_lahir_ayah" class="form-label">Tanggal Lahir Ayah</label>
                            <input type="date" class="form-control" id="tanggal_lahir_ayah" name="tanggal_lahir_ayah" v-model="form.tanggal_lahir_ayah">
                        </div>
                        <div class="col-md-6">
                            <label for="kewarganegaraan_ayah" class="form-label">Kewarganegaraan Ayah</label>
                            <select class="form-select" id="kewarganegaraan_ayah" name="kewarganegaraan_ayah" v-model="form.kewarganegaraan_ayah">
                                <option value="WNI">Warga Negara Indonesia (WNI)</option>
                                <option value="WNA">Warga Negara Asing (WNA)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status_hidup_ayah" class="form-label">Status Kelangsungan Hidup Ayah</label>
                            <select class="form-select" id="status_hidup_ayah" name="status_hidup_ayah" v-model="form.status_hidup_ayah">
                                <option value="Hidup">Masih Hidup</option>
                                <option value="Meninggal">Wafat / Meninggal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pendidikan_ayah" class="form-label">Pendidikan Terakhir Ayah</label>
                            <select class="form-select" id="pendidikan_ayah" name="pendidikan_ayah" v-model="form.pendidikan_ayah">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Tamat Sekolah">Tidak Tamat Sekolah</option>
                                <option value="SD">SD</option>
                                <option value="SMP">SMP</option>
                                <option value="SMA">SMA</option>
                                <option value="D3">D3</option>
                                <option value="D4">D4</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pekerjaan_ayah" class="form-label">Pekerjaan Ayah</label>
                            <select class="form-select" id="pekerjaan_ayah" name="pekerjaan_ayah" v-model="form.pekerjaan_ayah">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Bekerja">Tidak Bekerja</option>
                                <option value="Buruh">Buruh</option>
                                <option value="Petani">Petani</option>
                                <option value="Nelayan">Nelayan</option>
                                <option value="Pedagang">Pedagang</option>
                                <option value="Wiraswasta">Wiraswasta</option>
                                <option value="Pegawai Swasta">Pegawai Swasta</option>
                                <option value="PNS / TNI / Polri">PNS / TNI / Polri</option>
                                <option value="Guru / Dosen">Guru / Dosen</option>
                                <option value="Dokter / Perawat">Dokter / Perawat</option>
                                <option value="Meninggal">Meninggal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="penghasilan_ayah" class="form-label">Penghasilan Ayah</label>
                            <select class="form-select" id="penghasilan_ayah" name="penghasilan_ayah" v-model="form.penghasilan_ayah">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Berpenghasilan">Tidak Berpenghasilan</option>
                                <option value="Kurang dari Rp500.000">Kurang dari Rp500.000</option>
                                <option value="Rp500.000 sampai Rp999.999">Rp500.000 sampai Rp999.999</option>
                                <option value="Rp1.000.000 sampai Rp1.999.999">Rp1.000.000 sampai Rp1.999.999</option>
                                <option value="Rp2.000.000 sampai Rp4.999.999">Rp2.000.000 sampai Rp4.999.999</option>
                                <option value="Rp5.000.000 sampai Rp20.000.000">Rp5.000.000 sampai Rp20.000.000</option>
                                <option value="Lebih dari Rp20.000.000">Lebih dari Rp20.000.000</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="agama_ayah" class="form-label">Agama Ayah</label>
                            <select class="form-select" id="agama_ayah" name="agama_ayah" v-model="form.agama_ayah">
                                <option value="" disabled>-- Pilih Agama --</option>
                                <option value="Islam">Islam</option>
                                <option value="Kristen">Kristen</option>
                                <option value="Katolik">Katolik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Khonghucu">Khonghucu</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 2: IBU KANDUNG -->
                <div v-show="activeParentTab === 'mother'">
                    <h6 class="fw-bold text-dark mb-4 pb-2 border-bottom text-muted">Informasi Ibu Kandung <span class="text-danger">*</span></h6>
                    <div class="row g-3 g-md-4">
                        <div class="col-md-6">
                            <label for="nik_ibu" class="form-label">NIK Ibu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nik_ibu" name="nik_ibu" v-model="form.nik_ibu" placeholder="Masukkan 16 digit NIK" maxlength="16" :required="activeParentTab === 'mother'">
                        </div>
                        <div class="col-md-6">
                            <label for="nama_ibu" class="form-label">Nama Lengkap Ibu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="nama_ibu" name="nama_ibu" v-model="form.nama_ibu" placeholder="Nama lengkap tanpa gelar" :required="activeParentTab === 'mother'">
                        </div>
                        <div class="col-md-6">
                            <div class="form-label mb-2 fw-bold">Tempat Lahir Ibu <span class="text-danger">*</span></div>
                            <searchable-select 
                                id="id_tempat_lahir_ibu" 
                                name="id_tempat_lahir_ibu" 
                                v-model="form.id_tempat_lahir_ibu" 
                                :options="citiesOptions" 
                                placeholder="-- Pilih Kota Tempat Lahir Ibu --"
                                :required="activeParentTab === 'mother'">
                            </searchable-select>
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_lahir_ibu" class="form-label">Tanggal Lahir Ibu <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_lahir_ibu" name="tanggal_lahir_ibu" v-model="form.tanggal_lahir_ibu" :required="activeParentTab === 'mother'">
                        </div>
                        <div class="col-md-6">
                            <label for="kewarganegaraan_ibu" class="form-label">Kewarganegaraan Ibu</label>
                            <select class="form-select" id="kewarganegaraan_ibu" name="kewarganegaraan_ibu" v-model="form.kewarganegaraan_ibu">
                                <option value="WNI">Warga Negara Indonesia (WNI)</option>
                                <option value="WNA">Warga Negara Asing (WNA)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status_hidup_ibu" class="form-label">Status Kelangsungan Hidup Ibu</label>
                            <select class="form-select" id="status_hidup_ibu" name="status_hidup_ibu" v-model="form.status_hidup_ibu">
                                <option value="Hidup">Masih Hidup</option>
                                <option value="Meninggal">Wafat / Meninggal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pendidikan_ibu" class="form-label">Pendidikan Terakhir Ibu <span class="text-danger">*</span></label>
                            <select class="form-select" id="pendidikan_ibu" name="pendidikan_ibu" v-model="form.pendidikan_ibu" :required="activeParentTab === 'mother'">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Tamat Sekolah">Tidak Tamat Sekolah</option>
                                <option value="SD">SD</option>
                                <option value="SMP">SMP</option>
                                <option value="SMA">SMA</option>
                                <option value="D3">D3</option>
                                <option value="D4">D4</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pekerjaan_ibu" class="form-label">Pekerjaan Ibu <span class="text-danger">*</span></label>
                            <select class="form-select" id="pekerjaan_ibu" name="pekerjaan_ibu" v-model="form.pekerjaan_ibu" :required="activeParentTab === 'mother'">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Bekerja">Tidak Bekerja / Ibu Rumah Tangga</option>
                                <option value="Buruh">Buruh</option>
                                <option value="Petani">Petani</option>
                                <option value="Nelayan">Nelayan</option>
                                <option value="Pedagang">Pedagang</option>
                                <option value="Wiraswasta">Wiraswasta</option>
                                <option value="Pegawai Swasta">Pegawai Swasta</option>
                                <option value="PNS / TNI / Polri">PNS / TNI / Polri</option>
                                <option value="Guru / Dosen">Guru / Dosen</option>
                                <option value="Dokter / Perawat">Dokter / Perawat</option>
                                <option value="Meninggal">Meninggal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="penghasilan_ibu" class="form-label">Penghasilan Ibu <span class="text-danger">*</span></label>
                            <select class="form-select" id="penghasilan_ibu" name="penghasilan_ibu" v-model="form.penghasilan_ibu" :required="activeParentTab === 'mother'">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Berpenghasilan">Tidak Berpenghasilan</option>
                                <option value="Kurang dari Rp500.000">Kurang dari Rp500.000</option>
                                <option value="Rp500.000 sampai Rp999.999">Rp500.000 sampai Rp999.999</option>
                                <option value="Rp1.000.000 sampai Rp1.999.999">Rp1.000.000 sampai Rp1.999.999</option>
                                <option value="Rp2.000.000 sampai Rp4.999.999">Rp2.000.000 sampai Rp4.999.999</option>
                                <option value="Rp5.000.000 sampai Rp20.000.000">Rp5.000.000 sampai Rp20.000.000</option>
                                <option value="Lebih dari Rp20.000.000">Lebih dari Rp20.000.000</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="agama_ibu" class="form-label">Agama Ibu <span class="text-danger">*</span></label>
                            <select class="form-select" id="agama_ibu" name="agama_ibu" v-model="form.agama_ibu" :required="activeParentTab === 'mother'">
                                <option value="" disabled>-- Pilih Agama --</option>
                                <option value="Islam">Islam</option>
                                <option value="Kristen">Kristen</option>
                                <option value="Katolik">Katolik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Khonghucu">Khonghucu</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 3: WALI KANDUNG (OPSIONAL) -->
                <div v-show="activeParentTab === 'guardian'">
                    <h6 class="fw-bold text-dark mb-4 pb-2 border-bottom text-muted">Informasi Wali Murid (Opsional)</h6>
                    <div class="row g-3 g-md-4">
                        <div class="col-md-6">
                            <label for="nik_wali" class="form-label">NIK Wali</label>
                            <input type="text" class="form-control" id="nik_wali" name="nik_wali" v-model="form.nik_wali" placeholder="Masukkan 16 digit NIK" maxlength="16">
                        </div>
                        <div class="col-md-6">
                            <label for="nama_wali" class="form-label">Nama Lengkap Wali</label>
                            <input type="text" class="form-control text-uppercase" id="nama_wali" name="nama_wali" v-model="form.nama_wali" placeholder="Nama lengkap tanpa gelar">
                        </div>
                        <div class="col-md-6">
                            <div class="form-label mb-2 fw-bold">Tempat Lahir Wali</div>
                            <searchable-select 
                                id="id_tempat_lahir_wali" 
                                name="id_tempat_lahir_wali" 
                                v-model="form.id_tempat_lahir_wali" 
                                :options="citiesOptions" 
                                placeholder="-- Pilih Kota Tempat Lahir Wali --">
                            </searchable-select>
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_lahir_wali" class="form-label">Tanggal Lahir Wali</label>
                            <input type="date" class="form-control" id="tanggal_lahir_wali" name="tanggal_lahir_wali" v-model="form.tanggal_lahir_wali">
                        </div>
                        <div class="col-md-6">
                            <label for="kewarganegaraan_wali" class="form-label">Kewarganegaraan Wali</label>
                            <select class="form-select" id="kewarganegaraan_wali" name="kewarganegaraan_wali" v-model="form.kewarganegaraan_wali">
                                <option value="">-- Pilih --</option>
                                <option value="WNI">Warga Negara Indonesia (WNI)</option>
                                <option value="WNA">Warga Negara Asing (WNA)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="hubungan_wali" class="form-label">Hubungan Keluarga Wali</label>
                            <input type="text" class="form-control" id="hubungan_wali" name="hubungan_wali" v-model="form.hubungan_wali" placeholder="Contoh: Paman, Tante, Kakak Kandung">
                        </div>
                        <div class="col-md-4">
                            <label for="pendidikan_wali" class="form-label">Pendidikan Terakhir Wali</label>
                            <select class="form-select" id="pendidikan_wali" name="pendidikan_wali" v-model="form.pendidikan_wali">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Tamat Sekolah">Tidak Tamat Sekolah</option>
                                <option value="SD">SD</option>
                                <option value="SMP">SMP</option>
                                <option value="SMA">SMA</option>
                                <option value="D3">D3</option>
                                <option value="D4">D4</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pekerjaan_wali" class="form-label">Pekerjaan Wali</label>
                            <select class="form-select" id="pekerjaan_wali" name="pekerjaan_wali" v-model="form.pekerjaan_wali">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Bekerja">Tidak Bekerja</option>
                                <option value="Buruh">Buruh</option>
                                <option value="Petani">Petani</option>
                                <option value="Nelayan">Nelayan</option>
                                <option value="Pedagang">Pedagang</option>
                                <option value="Wiraswasta">Wiraswasta</option>
                                <option value="Pegawai Swasta">Pegawai Swasta</option>
                                <option value="PNS / TNI / Polri">PNS / TNI / Polri</option>
                                <option value="Guru / Dosen">Guru / Dosen</option>
                                <option value="Dokter / Perawat">Dokter / Perawat</option>
                                <option value="Meninggal">Meninggal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="penghasilan_wali" class="form-label">Penghasilan Wali</label>
                            <select class="form-select" id="penghasilan_wali" name="penghasilan_wali" v-model="form.penghasilan_wali">
                                <option value="" disabled>-- Pilih --</option>
                                <option value="Tidak Berpenghasilan">Tidak Berpenghasilan</option>
                                <option value="Kurang dari Rp500.000">Kurang dari Rp500.000</option>
                                <option value="Rp500.000 sampai Rp999.999">Rp500.000 sampai Rp999.999</option>
                                <option value="Rp1.000.000 sampai Rp1.999.999">Rp1.000.000 sampai Rp1.999.999</option>
                                <option value="Rp2.000.000 sampai Rp4.999.999">Rp2.000.000 sampai Rp4.999.999</option>
                                <option value="Rp5.000.000 sampai Rp20.000.000">Rp5.000.000 sampai Rp20.000.000</option>
                                <option value="Lebih dari Rp20.000.000">Lebih dari Rp20.000.000</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="agama_wali" class="form-label">Agama Wali</label>
                            <select class="form-select" id="agama_wali" name="agama_wali" v-model="form.agama_wali">
                                <option value="" disabled>-- Pilih Agama --</option>
                                <option value="Islam">Islam</option>
                                <option value="Kristen">Kristen</option>
                                <option value="Katolik">Katolik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Khonghucu">Khonghucu</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== LANGKAH 5: REGISTRASI & DOKUMEN UPLOAD ==================== -->
        <div v-show="currentStep === 5" data-step="5">
            <h5 class="fw-bold text-primary mb-4 pb-2 border-bottom"><i class="bi bi-file-earmark-check-fill me-2"></i> Langkah 5: Registrasi, Keluar & Dokumen Berkas</h5>
            
            <div class="row g-3 g-md-4">
                
                <!-- Jenis Pendaftaran -->
                <div class="col-md-6">
                    <label for="jenis_pendaftaran" class="form-label">Jenis Pendaftaran <span class="text-danger">*</span></label>
                    <select class="form-select" id="jenis_pendaftaran" name="jenis_pendaftaran" v-model="form.jenis_pendaftaran" required>
                        <option value="" disabled>-- Pilih Jenis Pendaftaran --</option>
                        <option value="Siswa Baru">Siswa Baru</option>
                        <option value="Pindahan">Pindahan</option>
                        <option value="Kembali Sekolah">Kembali Sekolah</option>
                    </select>
                </div>

                <!-- Jalur Diterima -->
                <div class="col-md-6" v-if="userRole !== 'siswa'">
                    <label for="jalur_diterima" class="form-label">Jalur Pendaftaran / Diterima</label>
                    <select class="form-select" id="jalur_diterima" name="jalur_diterima" v-model="form.jalur_diterima">
                        <option value="" disabled>-- Pilih Jalur --</option>
                        <option value="Zonasi">Zonasi</option>
                        <option value="Afirmasi">Afirmasi</option>
                        <option value="Prestasi Akademik">Prestasi Akademik</option>
                        <option value="Prestasi Non-akademik">Prestasi Non-akademik</option>
                        <option value="Perpindahan Tugas">Perpindahan Tugas Orang Tua / Wali</option>
                        <option value="Anak Guru / Tenaga Kependidikan">Anak Guru / Tenaga Kependidikan</option>
                        <option value="Khusus">Jalur Khusus / Kemitraan</option>
                    </select>
                </div>

                <!-- Tanggal Masuk -->
                <div class="col-md-6">
                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk / Terdaftar <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" v-model="form.tanggal_masuk" required>
                </div>

                <!-- Hobi -->
                <div class="col-md-6">
                    <label for="hobi" class="form-label">Hobi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="hobi" name="hobi" v-model="form.hobi" placeholder="Contoh: Membaca, Olahraga, Kesenian" required>
                </div>

                <!-- PAUD Formal -->
                <div class="col-md-6">
                    <div class="form-label d-block fw-bold mb-2">Pernah Mengikuti PAUD Formal?</div>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="paud_formal" id="paud_f_ya" value="1" v-model.number="form.paud_formal">
                            <label class="form-check-label" for="paud_f_ya">Ya</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="paud_formal" id="paud_f_tidak" value="0" v-model.number="form.paud_formal">
                            <label class="form-check-label" for="paud_f_tidak">Tidak</label>
                        </div>
                    </div>
                </div>

                <!-- PAUD Non-Formal -->
                <div class="col-md-6">
                    <div class="form-label d-block fw-bold mb-2">Pernah Mengikuti PAUD Non-Formal?</div>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="paud_non_formal" id="paud_nf_ya" value="1" v-model.number="form.paud_non_formal">
                            <label class="form-check-label" for="paud_nf_ya">Ya</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="paud_non_formal" id="paud_nf_tidak" value="0" v-model.number="form.paud_non_formal">
                            <label class="form-check-label" for="paud_nf_tidak">Tidak</label>
                        </div>
                    </div>
                </div>

                <!-- TOGGLE FORM KELUAR:
                     - Hanya ditampilkan jika status siswa bukan 'Aktif'
                     - TIDAK ditampilkan untuk role siswa (hanya Super Admin & Admin Sekolah)
                     - Data keluar diisi oleh Admin, bukan siswa sendiri -->
                <div class="col-12 mt-4"
                     v-show="form.status !== 'Aktif' && userRole !== 'siswa'">
                    <div class="bg-warning-subtle border border-warning rounded-4 p-4">
                        <h6 class="fw-bold text-warning-emphasis mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span>
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Form Registrasi Keluar / Mutasi Siswa
                                <span class="badge bg-warning text-dark ms-2 fw-normal" style="font-size:0.7rem;">Admin Only</span>
                            </span>
                            <button type="button" class="btn btn-xs btn-outline-danger border-0 py-1 px-2 rounded-3 fs-9 fw-semibold bg-white shadow-sm" @click="cancelMutasi">
                                <i class="bi bi-x-circle me-1"></i> Batalkan & Aktifkan Kembali
                            </button>
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="keluar_karena" class="form-label">Keluar Karena</label>
                                <select class="form-select" id="keluar_karena" name="keluar_karena" v-model="form.keluar_karena">
                                    <option value="">-- Pilih Alasan Keluar (Opsional) --</option>
                                    <option value="Lulus">Lulus</option>
                                    <option value="Mutasi">Mutasi / Pindah Sekolah</option>
                                    <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                                    <option value="Putus Sekolah">Putus Sekolah</option>
                                    <option value="Dikeluarkan">Dikeluarkan</option>
                                    <option value="Wafat">Wafat / Meninggal Dunia</option>
                                    <option value="Hilang">Hilang</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_keluar" class="form-label">Tanggal Keluar</label>
                                <input type="date" class="form-control" id="tanggal_keluar" name="tanggal_keluar" v-model="form.tanggal_keluar">
                            </div>

                            <!-- Fields for Mutasi -->
                            <div class="col-md-6" v-if="form.keluar_karena === 'Mutasi'">
                                <label for="sekolah_tujuan" class="form-label">Sekolah Tujuan Pindahan</label>
                                <input type="text" class="form-control" id="sekolah_tujuan" name="sekolah_tujuan" v-model="form.sekolah_tujuan" placeholder="Masukkan nama sekolah tujuan">
                            </div>
                            <div class="col-md-6" v-if="form.keluar_karena === 'Mutasi'">
                                <label for="nomor_skp" class="form-label">Nomor Surat Keterangan Pindah (SKP)</label>
                                <input type="text" class="form-control" id="nomor_skp" name="nomor_skp" v-model="form.nomor_skp" placeholder="Masukkan nomor SKP">
                            </div>

                            <!-- Fields for Lulus -->
                            <div class="col-md-4" v-if="form.keluar_karena === 'Lulus'">
                                <label for="nomor_ijazah_kelulusan" class="form-label">Nomor Blangko Ijazah Kelulusan</label>
                                <input type="text" class="form-control" id="nomor_ijazah_kelulusan" name="nomor_ijazah_kelulusan" v-model="form.nomor_ijazah_kelulusan" placeholder="Masukkan nomor ijazah kelulusan">
                            </div>
                            <div class="col-md-4" v-if="form.keluar_karena === 'Lulus'">
                                <label for="nomor_skl" class="form-label">Nomor Surat Keterangan Lulus (SKL)</label>
                                <input type="text" class="form-control" id="nomor_skl" name="nomor_skl" v-model="form.nomor_skl" placeholder="Masukkan nomor SKL">
                            </div>
                            <div class="col-md-4" v-if="form.keluar_karena === 'Lulus'">
                                <label for="keterangan_setelah_lulus" class="form-label">Rencana Setelah Lulus</label>
                                <select class="form-select" id="keterangan_setelah_lulus" name="keterangan_setelah_lulus" v-model="form.keterangan_setelah_lulus">
                                    <option value="">-- Pilih Rencana --</option>
                                    <option value="Kuliah">Kuliah / Melanjutkan Studi</option>
                                    <option value="Bekerja">Bekerja</option>
                                    <option value="Wirausaha">Wirausaha</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="alasan_keluar" class="form-label">Uraian / Alasan Keluar Lengkap</label>
                                <textarea class="form-control" id="alasan_keluar" name="alasan_keluar" rows="2" v-model="form.alasan_keluar" placeholder="Jelaskan alasan resmi keluar atau nama sekolah tujuan mutasi"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- UPLOAD AREA DOKUMEN DAN FOTO PROFIL -->
                <div class="col-12 mt-4">
                    <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-cloud-upload-fill me-2"></i>Upload Berkas & Dokumen Pendukung (PDF/JPG, Max 500 KB)</h6>
                    <div class="row g-3">
                        
                        <!-- 1. Foto Profil -->
                        <div class="col-md-4">
                            <div class="form-label fw-bold mb-2">Foto Profil Murid</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.foto_profil || (form.foto_profil && !filesSelected.foto_profil)" class="mb-2">
                                    <img :src="filePreviews.foto_profil ? filePreviews.foto_profil : getFileUrl(form.foto_profil, 'foto_profil')" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-image upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.foto_profil ? filesSelected.foto_profil : 'Pilih Foto Profil' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .jpg / .png</div>
                                <input type="file" name="foto_profil" accept="image/*" @change="onFileSelected($event, 'foto_profil')">
                            </div>
                            <div v-if="form.foto_profil && !filesSelected.foto_profil" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.foto_profil, 'Foto Profil Murid')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.foto_profil, 'foto_profil')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 2. Berkas KK -->
                        <div class="col-md-4">
                            <div class="form-label fw-bold mb-2">Kartu Keluarga (KK)</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.berkas_kk" class="mb-2">
                                    <img :src="filePreviews.berkas_kk" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-pdf upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_kk ? filesSelected.berkas_kk : 'Pilih Berkas KK' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg</div>
                                <input type="file" name="berkas_kk" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_kk')">
                            </div>
                            <div v-if="form.berkas_kk && !filesSelected.berkas_kk" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_kk, 'Kartu Keluarga (KK)')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_kk, 'berkas_kk')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 3. Berkas Akta Lahir -->
                        <div class="col-md-4">
                            <div class="form-label fw-bold mb-2">Akta Kelahiran</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.berkas_akta" class="mb-2">
                                    <img :src="filePreviews.berkas_akta" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-pdf upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_akta ? filesSelected.berkas_akta : 'Pilih Berkas Akta' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg</div>
                                <input type="file" name="berkas_akta" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_akta')">
                            </div>
                            <div v-if="form.berkas_akta && !filesSelected.berkas_akta" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_akta, 'Akta Kelahiran')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_akta, 'berkas_akta')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 4. Ijazah SD -->
                        <div class="col-md-4">
                            <div class="form-label fw-bold mb-2">Ijazah SD / MI</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.berkas_ijazah_sd" class="mb-2">
                                    <img :src="filePreviews.berkas_ijazah_sd" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-text upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_ijazah_sd ? filesSelected.berkas_ijazah_sd : 'Pilih Ijazah SD' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg</div>
                                <input type="file" name="berkas_ijazah_sd" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_ijazah_sd')">
                            </div>
                            <div v-if="form.berkas_ijazah_sd && !filesSelected.berkas_ijazah_sd" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_ijazah_sd, 'Ijazah SD / MI')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_ijazah_sd, 'berkas_ijazah_sd')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 5. Ijazah SMP -->
                        <div class="col-md-4">
                            <div class="form-label fw-bold mb-2">Ijazah SMP / MTs</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.berkas_ijazah_smp" class="mb-2">
                                    <img :src="filePreviews.berkas_ijazah_smp" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-text upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_ijazah_smp ? filesSelected.berkas_ijazah_smp : 'Pilih Ijazah SMP' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg</div>
                                <input type="file" name="berkas_ijazah_smp" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_ijazah_smp')">
                            </div>
                            <div v-if="form.berkas_ijazah_smp && !filesSelected.berkas_ijazah_smp" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_ijazah_smp, 'Ijazah SMP / MTs')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_ijazah_smp, 'berkas_ijazah_smp')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 6. Ijazah SMA -->
                        <div class="col-md-4">
                            <div class="form-label fw-bold mb-2">Ijazah SMA / MA (Jika Ada)</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.berkas_ijazah_sma" class="mb-2">
                                    <img :src="filePreviews.berkas_ijazah_sma" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-text upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_ijazah_sma ? filesSelected.berkas_ijazah_sma : 'Pilih Ijazah SMA' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg</div>
                                <input type="file" name="berkas_ijazah_sma" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_ijazah_sma')">
                            </div>
                            <div v-if="form.berkas_ijazah_sma && !filesSelected.berkas_ijazah_sma" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_ijazah_sma, 'Ijazah SMA / MA')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_ijazah_sma, 'berkas_ijazah_sma')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 7. Berkas Mutasi Masuk -->
                        <div class="col-md-4" v-show="form.jenis_pendaftaran === 'Pindahan'">
                            <div class="form-label fw-bold mb-2">Surat Mutasi Masuk</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.berkas_mutasi_masuk" class="mb-2">
                                    <img :src="filePreviews.berkas_mutasi_masuk" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-arrow-up upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_mutasi_masuk ? filesSelected.berkas_mutasi_masuk : 'Pilih Berkas Mutasi' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg</div>
                                <input type="file" name="berkas_mutasi_masuk" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_mutasi_masuk')">
                            </div>
                            <div v-if="form.berkas_mutasi_masuk && !filesSelected.berkas_mutasi_masuk" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_mutasi_masuk, 'Surat Mutasi Masuk')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_mutasi_masuk, 'berkas_mutasi_masuk')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 8. Berkas Mutasi Keluar -->
                        <div class="col-md-4" v-show="form.status === 'Pindah'">
                            <div class="form-label fw-bold mb-2">Surat Mutasi Keluar</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.berkas_mutasi_keluar" class="mb-2">
                                    <img :src="filePreviews.berkas_mutasi_keluar" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-arrow-down upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_mutasi_keluar ? filesSelected.berkas_mutasi_keluar : 'Pilih Berkas Mutasi' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg</div>
                                <input type="file" name="berkas_mutasi_keluar" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_mutasi_keluar')">
                            </div>
                            <div v-if="form.berkas_mutasi_keluar && !filesSelected.berkas_mutasi_keluar" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_mutasi_keluar, 'Surat Mutasi Keluar')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_mutasi_keluar, 'berkas_mutasi_keluar')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 9. Berkas KIP -->
                        <div class="col-md-4" v-show="form.punya_kip == 1">
                            <div class="form-label fw-bold mb-2">Kartu KIP / PKH (PDF/JPG)</div>
                            <div class="upload-box shadow-xs">
                                <div v-if="filePreviews.berkas_kip" class="mb-2">
                                    <img :src="filePreviews.berkas_kip" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-credit-card-2-front upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_kip ? filesSelected.berkas_kip : 'Pilih Kartu KIP' }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg</div>
                                <input type="file" name="berkas_kip" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_kip')">
                            </div>
                            <div v-if="form.berkas_kip && !filesSelected.berkas_kip" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_kip, 'Kartu KIP / PKH')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_kip, 'berkas_kip')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 10. Surat Pernyataan Siswa Baru & Orang Tua -->
                        <div class="col-md-4">
                            <div class="form-label fw-bold mb-2">Surat Pernyataan Baru & Orang Tua</div>
                            <div class="upload-box shadow-xs" :style="userRole === 'siswa' ? 'cursor: not-allowed; opacity: 0.65;' : ''">
                                <div v-if="filePreviews.berkas_pernyataan_baru || (form.berkas_pernyataan_baru && !filesSelected.berkas_pernyataan_baru)" class="mb-2">
                                    <img :src="filePreviews.berkas_pernyataan_baru ? filePreviews.berkas_pernyataan_baru : getFileUrl(form.berkas_pernyataan_baru, 'berkas_pernyataan_baru')" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-pdf upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_pernyataan_baru ? filesSelected.berkas_pernyataan_baru : (userRole === 'siswa' ? 'Tidak ada berkas' : 'Pilih Surat Pernyataan') }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg (Admin Only)</div>
                                <input v-if="userRole !== 'siswa'" type="file" name="berkas_pernyataan_baru" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_pernyataan_baru')">
                            </div>
                            <div v-if="form.berkas_pernyataan_baru && !filesSelected.berkas_pernyataan_baru" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_pernyataan_baru, 'Surat Pernyataan Baru & Orang Tua')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_pernyataan_baru, 'berkas_pernyataan_baru')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                        <!-- 11. Surat Pernyataan TKA -->
                        <div class="col-md-4">
                            <div class="form-label fw-bold mb-2">Surat Pernyataan TKA</div>
                            <div class="upload-box shadow-xs" :style="userRole === 'siswa' ? 'cursor: not-allowed; opacity: 0.65;' : ''">
                                <div v-if="filePreviews.berkas_pernyataan_tka || (form.berkas_pernyataan_tka && !filesSelected.berkas_pernyataan_tka)" class="mb-2">
                                    <img :src="filePreviews.berkas_pernyataan_tka ? filePreviews.berkas_pernyataan_tka : getFileUrl(form.berkas_pernyataan_tka, 'berkas_pernyataan_tka')" class="img-thumbnail rounded-3" style="max-height: 80px;">
                                </div>
                                <i v-else class="bi bi-file-earmark-pdf upload-icon"></i>
                                <div class="fs-8 fw-semibold text-secondary">
                                    {{ filesSelected.berkas_pernyataan_tka ? filesSelected.berkas_pernyataan_tka : (userRole === 'siswa' ? 'Tidak ada berkas' : 'Pilih Surat Pernyataan TKA') }}
                                </div>
                                <div class="fs-9 text-muted mt-1">Ekstensi .pdf / .jpg (Admin Only)</div>
                                <input v-if="userRole !== 'siswa'" type="file" name="berkas_pernyataan_tka" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_pernyataan_tka')">
                            </div>
                            <div class="form-text text-warning fw-semibold mt-1" style="font-size: 0.75rem;">
                                <i class="bi bi-info-circle-fill"></i> File TKA Hanya diisi ketika sudah kelas 12.
                            </div>
                            <div v-if="form.berkas_pernyataan_tka && !filesSelected.berkas_pernyataan_tka" class="upload-status-bar">
                                <span class="status-text">
                                    <i class="bi bi-check-circle-fill text-success"></i> Berkas sudah terunggah
                                </span>
                                <span class="status-actions">
                                    <a href="#" @click.prevent="openDocumentViewer(form.berkas_pernyataan_tka, 'Surat Pernyataan TKA')" class="btn-view-file">Lihat Berkas</a>
                                    <a :href="getFileUrl(form.berkas_pernyataan_tka, 'berkas_pernyataan_tka')" target="_blank" class="btn-external-link" title="Buka di tab baru"><i class="bi bi-box-arrow-up-right"></i></a>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <hr class="my-4">

        <!-- Wizard Navigation Controls (Kembali, Lanjut, Simpan) -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center mt-4 pt-4 border-top gap-3">
            <button type="button" class="btn btn-light rounded-3 px-4 py-2 fs-7 shadow-sm border w-100 w-sm-auto order-last order-sm-first" @click="prevStep" v-show="currentStep > 1">
                <i class="bi bi-chevron-left me-2"></i> Sebelumnya
            </button>
            
            <!-- Batal if step 1 -->
            <a href="/SINTA-SaaS/pengguna" class="btn btn-light rounded-3 px-4 py-2 fs-7 shadow-sm border w-100 w-sm-auto order-last order-sm-first text-center" v-show="currentStep === 1">
                Batal
            </a>

            <div class="d-flex flex-column flex-sm-row gap-3 ms-sm-auto w-100 w-sm-auto">
                <button v-if="isEdit && currentStep < 5" type="button" class="btn btn-success rounded-3 px-4 py-2 fs-7 shadow-sm w-100 w-sm-auto" @click="saveCurrentStep(false)" :disabled="loadingSaveStep">
                    <span v-if="loadingSaveStep" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <i v-else class="bi bi-check-circle me-1"></i> Simpan Step {{ currentStep }}
                </button>

                <button type="button" class="btn btn-primary rounded-3 px-4 py-2 fs-7 shadow-sm w-100 w-sm-auto" @click="nextStep" v-show="currentStep < 5">
                    Lanjut <i class="bi bi-chevron-right ms-2"></i>
                </button>
                
                <!-- Save Button (Step 5 only, or if not Edit Mode, standard save) -->
                <button v-show="currentStep === 5" type="submit" class="btn btn-success rounded-3 px-4 py-2 fs-7 shadow-sm w-100 w-sm-auto" :disabled="loadingSaveStep">
                    <span v-if="loadingSaveStep" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <i v-else class="bi bi-save me-2"></i> {{ isEdit ? 'Simpan / Update' : 'Simpan Data Siswa' }}
                </button>
            </div>
        </div>

    </form>

    <!-- Inline Document Viewer Modal -->
    <div class="modal fade" id="documentViewerModal" tabindex="-1" aria-labelledby="documentViewerModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom px-4">
                    <h6 class="modal-title fw-bold text-dark" id="documentViewerModalLabel">
                        <i class="bi bi-file-earmark-text-fill text-primary me-2"></i> {{ viewerModalTitle }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-light rounded-bottom-4 overflow-hidden d-flex align-items-center justify-content-center" style="min-height: 400px;">
                    <!-- PDF Viewer -->
                    <iframe v-if="isViewerFilePdf" :src="viewerModalUrl" class="w-100" style="height: 600px; border: none;" allow="autoplay"></iframe>
                    <!-- Image Viewer -->
                    <div v-else class="p-3 text-center w-100">
                        <img :src="viewerModalUrl" class="img-fluid rounded shadow-sm mx-auto d-block" style="max-height: 550px; object-fit: contain;">
                    </div>
                </div>
                <div class="modal-footer border-top bg-white px-4">
                    <a :href="viewerModalUrl" target="_blank" class="btn btn-outline-secondary rounded-3 fs-8">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka di Tab Baru
                    </a>
                    <button type="button" class="btn btn-primary rounded-3 fs-8 px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
{
    // Inisialisasi awal Vue 3 App
    const { ref, computed, onMounted, onUnmounted } = Vue;

    window.VueAppRegistry.register('#studentWizardApp', {
        setup() {
            // Urutan Langkah Wizard
            const currentStep = ref(1);
            const stepNames = [
                'Data Pokok',
                'Alamat & Kontak',
                'Fisik & Riwayat',
                'Data Orang Tua',
                'Registrasi & Berkas'
            ];

            // Sub-tab aktif pada data Orang Tua (Step 4)
            const activeParentTab = ref('father');

            // User Role dan Edit Status dari PHP
            const userRole = ref('<?= htmlspecialchars($data['user_role'] ?? "") ?>');
            const isEdit = ref(<?= $isEdit ? 'true' : 'false' ?>);

            // List options untuk Super Admin
            const listTenants = ref([]);

            // List options dari DB
            const provinces = ref([]);
            const cities = ref([]);
            const cityFiltered = ref([]); // Filter kota berdasarkan provinsi terpilih
            const districts = ref([]);
            const subdistricts = ref([]);
            const acOptions = ref({
                angkatan: [],
                tahun_ajaran: [],
                jenjang: [],
                jurusan: [],
                kelas: [],
                pendidikan: []
            });

            // Loading Indicators
            const loadingAcademic = ref(false);
            const loadingProvinces = ref(false);
            const loadingCities = ref(false);
            const loadingDistricts = ref(false);
            const loadingSubdistricts = ref(false);
            const loadingSaveStep = ref(false);

            // Menyimpan nama berkas terpilih klien
            const filesSelected = ref({
                foto_profil: '',
                berkas_kk: '',
                berkas_akta: '',
                berkas_ijazah_sd: '',
                berkas_ijazah_smp: '',
                berkas_ijazah_sma: '',
                berkas_mutasi_masuk: '',
                berkas_mutasi_keluar: '',
                berkas_kip: '',
                berkas_pernyataan_baru: '',
                berkas_pernyataan_tka: ''
            });

            // Penampung Error Client-side
            const errorsList = ref([]);

            // File previews base64 state (TUGAS 4)
            const filePreviews = ref({
                foto_profil: '',
                berkas_kk: '',
                berkas_akta: '',
                berkas_ijazah_sd: '',
                berkas_ijazah_smp: '',
                berkas_ijazah_sma: '',
                berkas_mutasi_masuk: '',
                berkas_mutasi_keluar: '',
                berkas_kip: '',
                berkas_pernyataan_baru: '',
                berkas_pernyataan_tka: ''
            });

            // Form Model State 1:1 matching database columns
            const form = ref({
                // Siswa
                tenant_id: '',
                nik: '',
                no_kk: '',
                nisn: '',
                nis: '',
                password: '',
                nama_lengkap: '',
                nama_panggilan: '',
                jenis_kelamin: '',
                agama: '',
                tempat_lahir: '',
                tanggal_lahir: '',
                sekolah_asal: '',
                status: 'Aktif',
                id_angkatan: '',
                id_tahun_ajaran: '',
                id_jenjang: '',
                id_jurusan: '',
                id_kelas: '',
                id_pendidikan: '',
                ukuran_seragam_sekolah: '',
                ukuran_seragam_olahraga: '',
                kewarganegaraan: 'WNI',
                bahasa_sehari_hari: 'Indonesia',
                no_ijazah_sebelumnya: '',
                tanggal_ijazah_sebelumnya: '',
                lama_belajar_sebelumnya: '',

                // Rincian Alamat
                alamat_kk: '',
                alamat_domisili: '',
                rt: '',
                rw: '',
                kode_pos: '',
                status_tinggal: '',
                id_kelurahan: '',
                id_provinsi: '',
                id_kota: '',
                id_kecamatan: '',
                tinggal_dengan: 'Orang Tua',

                // Kontak
                email: '',
                no_telepon_rumah: '',
                no_telepon_siswa: '',
                no_telepon_orang_tua: '',

                // Rincian Pelajar
                tinggi_badan: '',
                berat_badan: '',
                lingkar_kepala: '',
                golongan_darah: '',
                anak_ke: '',
                jumlah_saudara: '',
                penyakit_yang_diderita: '',
                jarak_rumah: '',
                transportasi: '',
                foto_profil: '', // database filename
                kelainan_jasmani: 'Tidak Ada',

                // KIP
                penerima_kps: 0,
                punya_kip: 0,
                layak_kip: 0,
                no_kip: '',
                status_anak: '',
                alasan_layak: '',

                // Orang Tua
                nik_ayah: '',
                nama_ayah: '',
                id_tempat_lahir_ayah: '',
                tahun_lahir_ayah: '',
                pendidikan_ayah: '',
                pekerjaan_ayah: '',
                penghasilan_ayah: '',
                agama_ayah: '',
                tanggal_lahir_ayah: '',
                kewarganegaraan_ayah: 'WNI',
                status_hidup_ayah: 'Hidup',

                nik_ibu: '',
                nama_ibu: '',
                id_tempat_lahir_ibu: '',
                tahun_lahir_ibu: '',
                pendidikan_ibu: '',
                pekerjaan_ibu: '',
                penghasilan_ibu: '',
                agama_ibu: '',
                tanggal_lahir_ibu: '',
                kewarganegaraan_ibu: 'WNI',
                status_hidup_ibu: 'Hidup',

                nik_wali: '',
                nama_wali: '',
                id_tempat_lahir_wali: '',
                tahun_lahir_wali: '',
                pendidikan_wali: '',
                pekerjaan_wali: '',
                penghasilan_wali: '',
                agama_wali: '',
                tanggal_lahir_wali: '',
                kewarganegaraan_wali: '',
                hubungan_wali: '',

                // Registrasi
                jenis_pendaftaran: '',
                jalur_diterima: '',
                tanggal_masuk: '',
                paud_formal: 1,
                paud_non_formal: 0,
                hobi: '',
                keluar_karena: '',
                tanggal_keluar: '',
                alasan_keluar: '',
                sekolah_tujuan: '',
                nomor_skp: '',
                nomor_ijazah_kelulusan: '',
                nomor_skl: '',
                keterangan_setelah_lulus: '',

                // Dokumen DB filenames
                berkas_kk: '',
                berkas_akta: '',
                berkas_ijazah_sd: '',
                berkas_ijazah_smp: '',
                berkas_ijazah_sma: '',
                berkas_mutasi_masuk: '',
                berkas_mutasi_keluar: '',
                berkas_kip: '',
                berkas_pernyataan_baru: '',
                berkas_pernyataan_tka: ''
            });

            // Map kota ke options untuk searchable dropdown
            const citiesOptions = computed(() => {
                if (!Array.isArray(cities.value)) return [];
                return cities.value.map(c => ({
                    id: c.id_kota,
                    label: c.nama_kota
                }));
            });

            // Filter Jurusan secara reaktif berdasarkan jenjang & tenant terpilih
            const filteredJurusan = computed(() => {
                if (!form.value.id_jenjang || !Array.isArray(acOptions.value.kelas) || !Array.isArray(acOptions.value.jurusan)) return [];
                const allowedJurusanIds = acOptions.value.kelas
                    .filter(k => parseInt(k.id_jenjang) === parseInt(form.value.id_jenjang))
                    .map(k => parseInt(k.id_jurusan));
                
                if (allowedJurusanIds.length === 0) {
                    return acOptions.value.jurusan; // Fallback jika rombel belum ter-set
                }
                return acOptions.value.jurusan.filter(j => 
                    allowedJurusanIds.includes(parseInt(j.id))
                );
            });

            // Filter Rombel Kelas secara reaktif berdasarkan jenjang & jurusan yang dipilih
            const filteredKelas = computed(() => {
                if (!form.value.id_jenjang || !form.value.id_jurusan || !Array.isArray(acOptions.value.kelas)) return [];
                return acOptions.value.kelas.filter(k => 
                    parseInt(k.id_jenjang) === parseInt(form.value.id_jenjang) && 
                    parseInt(k.id_jurusan) === parseInt(form.value.id_jurusan)
                );
            });

            // Validasi client-side global untuk mengunci tombol submit Step 5 (Edit Mode)
            const isFormValid = computed(() => {
                const f = form.value;
                // Step 1 required
                if (!f.nisn || f.nisn.length !== 10) return false;
                if (!f.nama_lengkap) return false;
                if (!f.jenis_kelamin) return false;
                if (!f.tanggal_lahir) return false;
                if (!f.tempat_lahir) return false;
                if (!f.id_angkatan || !f.id_tahun_ajaran || !f.id_jenjang || !f.id_jurusan || !f.id_kelas || !f.id_pendidikan) return false;
                // Step 2 required
                if (!f.alamat_kk) return false;
                if (!f.alamat_domisili) return false;
                if (!f.rt || !/^\d{1,3}$/.test(f.rt)) return false;
                if (!f.rw || !/^\d{1,3}$/.test(f.rw)) return false;
                if (!f.kode_pos || f.kode_pos.length !== 5) return false;
                if (!f.id_kelurahan) return false;
                if (!f.status_tinggal) return false;
                if (!f.email) return false;
                if (!f.no_telepon_siswa) return false;
                // Step 3 required
                if (f.tinggi_badan === '' || f.tinggi_badan < 30) return false;
                if (f.berat_badan === '' || f.berat_badan < 5) return false;
                if (f.lingkar_kepala === '' || f.lingkar_kepala < 20) return false;
                if (!f.golongan_darah) return false;
                if (f.anak_ke === '' || f.anak_ke < 1) return false;
                if (f.jumlah_saudara === '' || f.jumlah_saudara < 0) return false;
                if (f.jarak_rumah === '' || f.jarak_rumah < 1) return false;
                if (!f.transportasi) return false;
                if (f.punya_kip == 1 && !f.no_kip) return false;
                if (f.layak_kip == 1 && !f.alasan_layak) return false;
                // Step 4 required (Ibu kandung)
                if (!f.nik_ibu || f.nik_ibu.length !== 16) return false;
                if (!f.nama_ibu) return false;
                if (!f.id_tempat_lahir_ibu) return false;
                if (!f.tanggal_lahir_ibu) return false;
                if (!f.pendidikan_ibu) return false;
                if (!f.pekerjaan_ibu) return false;
                if (!f.penghasilan_ibu) return false;
                if (!f.agama_ibu) return false;
                // Step 5 required
                if (!f.jenis_pendaftaran) return false;
                if (!f.tanggal_masuk) return false;
                if (!f.hobi) return false;
                // Form keluar tidak wajib dan tidak ditampilkan ke siswa
                // Hanya Admin/Super Admin yang perlu mengisi, dan bersifat opsional
                return true;
            });

            // Ambil data query Edit jika di-inject dari PHP
            const loadEditData = () => {
                const phpData = <?= json_encode($siswaFullData) ?>;
                if (phpData && Object.keys(phpData).length > 0) {
                    Object.keys(phpData).forEach(key => {
                        if (key in form.value && key !== 'password') {
                            let val = phpData[key] !== null ? phpData[key] : '';
                            if (val === '0000-00-00') val = '';
                            if (typeof form.value[key] === 'number' && val !== '') {
                                val = Number(val);
                            }
                            form.value[key] = val;
                        }
                    });
                    
                    // Trigger pemuatan opsi akademik sesuai tenant_id siswa
                    if (form.value.tenant_id) {
                        fetchAcademicOptions(form.value.tenant_id);
                    }
                    
                    // Trigger pemuatan chained dropdown alamat secara bertahap
                    if (form.value.id_provinsi) {
                        fetchKota(form.value.id_provinsi, false);
                    }
                    if (form.value.id_kota) {
                        fetchKecamatan(form.value.id_kota, false);
                    }
                    if (form.value.id_kecamatan) {
                        fetchKelurahan(form.value.id_kecamatan, false);
                    }
                }
            };

            // Toast Error Alert
            const showErrorToast = (msg) => {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: msg,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });
                } else {
                    alert(msg);
                }
            };

            // Reset Kelas jika Jenjang/Jurusan diganti
            const onJenjangChange = () => {
                form.value.id_jurusan = '';
                form.value.id_kelas = '';
            };

            const onJurusanChange = () => {
                form.value.id_kelas = '';
            };

            // --- AJAX METHODS ---
            const fetchProvinces = async () => {
                loadingProvinces.value = true;
                try {
                    const res = await axios.get('?ajax=1&action=get_provinsi');
                    provinces.value = res.data;
                } catch (err) {
                    console.error("Gagal load provinsi", err);
                    showErrorToast("Gagal memuat data provinsi.");
                } finally {
                    loadingProvinces.value = false;
                }
            };

            const fetchAllCities = async () => {
                try {
                    const res = await axios.get('?ajax=1&action=get_all_kota');
                    cities.value = res.data;
                } catch (err) {
                    console.error("Gagal load kota lengkap", err);
                    showErrorToast("Gagal memuat data kota tempat lahir.");
                }
            };

            const fetchAcademicOptions = async (tenantId = '') => {
                loadingAcademic.value = true;
                try {
                    let url = '?ajax=1&action=get_academic_options';
                    if (tenantId) {
                        url += `&tenant_id=${tenantId}`;
                    }
                    const res = await axios.get(url);
                    acOptions.value = res.data;
                } catch (err) {
                    console.error("Gagal load opsi akademik", err);
                    showErrorToast("Gagal memuat opsi penempatan akademik.");
                } finally {
                    loadingAcademic.value = false;
                }
            };

            const fetchTenants = async () => {
                try {
                    const res = await axios.get('/SINTA-SaaS/api/v1/pengguna/tenants');
                    listTenants.value = res.data.data;
                } catch (err) {
                    console.error("Gagal load list tenants", err);
                    showErrorToast("Gagal memuat daftar sekolah.");
                }
            };

            const onTenantChange = () => {
                form.value.id_angkatan = '';
                form.value.id_tahun_ajaran = '';
                form.value.id_jenjang = '';
                form.value.id_jurusan = '';
                form.value.id_kelas = '';
                form.value.id_pendidikan = '';
                
                if (form.value.tenant_id) {
                    fetchAcademicOptions(form.value.tenant_id);
                } else {
                    acOptions.value = {
                        angkatan: [],
                        tahun_ajaran: [],
                        jenjang: [],
                        jurusan: [],
                        kelas: [],
                        pendidikan: []
                    };
                }
            };

            const fetchKota = async (provId, resetChildren = true) => {
                if (resetChildren) {
                    form.value.id_kota = '';
                    form.value.id_kecamatan = '';
                    form.value.id_kelurahan = '';
                    cityFiltered.value = [];
                    districts.value = [];
                    subdistricts.value = [];
                }
                loadingCities.value = true;
                try {
                    const res = await axios.get(`?ajax=1&action=get_kota&id_provinsi=${provId}`);
                    cityFiltered.value = res.data;
                } catch (err) {
                    console.error(err);
                    showErrorToast("Gagal memuat kabupaten/kota.");
                } finally {
                    loadingCities.value = false;
                }
            };

            const fetchKecamatan = async (kotaId, resetChildren = true) => {
                if (resetChildren) {
                    form.value.id_kecamatan = '';
                    form.value.id_kelurahan = '';
                    districts.value = [];
                    subdistricts.value = [];
                }
                loadingDistricts.value = true;
                try {
                    const res = await axios.get(`?ajax=1&action=get_kecamatan&id_kota=${kotaId}`);
                    districts.value = res.data;
                } catch (err) {
                    console.error(err);
                    showErrorToast("Gagal memuat kecamatan.");
                } finally {
                    loadingDistricts.value = false;
                }
            };

            const fetchKelurahan = async (kecId, resetChildren = true) => {
                if (resetChildren) {
                    form.value.id_kelurahan = '';
                    subdistricts.value = [];
                }
                loadingSubdistricts.value = true;
                try {
                    const res = await axios.get(`?ajax=1&action=get_kelurahan&id_kecamatan=${kecId}`);
                    subdistricts.value = res.data;
                } catch (err) {
                    console.error(err);
                    showErrorToast("Gagal memuat kelurahan.");
                } finally {
                    loadingSubdistricts.value = false;
                }
            };

            // --- EVENT HANDLERS REGION SELECT ---
            const onProvinceChange = () => {
                if (form.value.id_provinsi) {
                    fetchKota(form.value.id_provinsi, true);
                }
            };

            const onCityChange = () => {
                if (form.value.id_kota) {
                    fetchKecamatan(form.value.id_kota, true);
                }
            };

            const onDistrictChange = () => {
                if (form.value.id_kecamatan) {
                    fetchKelurahan(form.value.id_kecamatan, true);
                }
            };

            // File selection preview label and size/format validation (TUGAS 4)
            const onFileSelected = (event, type) => {
                const file = event.target.files[0];
                if (file) {
                    if (file.size > 500 * 1024) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Berkas Terlalu Besar',
                                text: 'Ukuran berkas melebihi batas maksimal 500 KB!',
                                confirmButtonColor: '#2563eb'
                            });
                        } else {
                            alert("Ukuran berkas melebihi batas maksimal 500 KB!");
                        }
                        event.target.value = ""; // Reset file input
                        filesSelected.value[type] = "";
                        filePreviews.value[type] = "";
                        return;
                    }
                    filesSelected.value[type] = file.name;
                    
                    // Generate image preview (TUGAS 4)
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            filePreviews.value[type] = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        filePreviews.value[type] = "";
                    }
                }
            };

            // Helper to get file URL (handles both legacy and new folder format)
            const getFileUrl = (path, fieldName) => {
                if (!path) return '#';
                
                let baseUrl = window.location.pathname.startsWith('/SINTA-SaaS') ? '/SINTA-SaaS' : '';
                
                if (path.indexOf('/') !== -1) {
                    return baseUrl + '/download.php?file=' + encodeURIComponent(path);
                }
                return baseUrl + '/download.php?file=' + encodeURIComponent(path) + 
                       '&tenant=' + encodeURIComponent(form.value.tenant_id || '') + 
                       '&field=' + encodeURIComponent(fieldName);
            };

            // Save draft payload (TUGAS 1) - local only to avoid server limit costs
            const saveDraft = async () => {
                // Jangan save draft jika dalam mode edit
                if (isEdit.value) return;
                // Draft is already saved to localStorage reactively via watcher
            };

            const getFieldsForStep = (step) => {
                const stepFields = {
                    1: [
                        'tenant_id', 'nik', 'no_kk', 'nisn', 'nis', 'password',
                        'nama_lengkap', 'nama_panggilan', 'jenis_kelamin', 'agama',
                        'tempat_lahir', 'tanggal_lahir', 'sekolah_asal', 'status',
                        'id_angkatan', 'id_tahun_ajaran', 'id_jenjang', 'id_jurusan',
                        'id_kelas', 'id_pendidikan', 'ukuran_seragam_sekolah', 'ukuran_seragam_olahraga',
                        'nama_wali', 'kontak_wali', 'kewarganegaraan', 'bahasa_sehari_hari',
                        'no_ijazah_sebelumnya', 'tanggal_ijazah_sebelumnya', 'lama_belajar_sebelumnya'
                    ],
                    2: [
                        'alamat_kk', 'alamat_domisili', 'rt', 'rw', 'kode_pos',
                        'status_tinggal', 'id_kelurahan', 'id_provinsi', 'id_kota', 'id_kecamatan',
                        'email', 'no_telepon_rumah', 'no_telepon_siswa', 'no_telepon_orang_tua', 'tinggal_dengan'
                    ],
                    3: [
                        'tinggi_badan', 'berat_badan', 'lingkar_kepala', 'golongan_darah',
                        'anak_ke', 'jumlah_saudara', 'penyakit_yang_diderita', 'jarak_rumah',
                        'transportasi', 'status_anak', 'penerima_kps', 'punya_kip',
                        'layak_kip', 'no_kip', 'alasan_layak', 'kelainan_jasmani'
                    ],
                    4: [
                        'nik_ayah', 'nama_ayah', 'id_tempat_lahir_ayah', 'tahun_lahir_ayah',
                        'pendidikan_ayah', 'pekerjaan_ayah', 'penghasilan_ayah', 'agama_ayah',
                        'tanggal_lahir_ayah', 'kewarganegaraan_ayah', 'status_hidup_ayah',
                        'nik_ibu', 'nama_ibu', 'id_tempat_lahir_ibu', 'tahun_lahir_ibu',
                        'pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu', 'agama_ibu',
                        'tanggal_lahir_ibu', 'kewarganegaraan_ibu', 'status_hidup_ibu',
                        'nik_wali', 'nama_wali', 'id_tempat_lahir_wali', 'tahun_lahir_wali',
                        'pendidikan_wali', 'pekerjaan_wali', 'penghasilan_wali', 'agama_wali',
                        'tanggal_lahir_wali', 'kewarganegaraan_wali', 'hubungan_wali'
                    ],
                    5: [
                        'jenis_pendaftaran', 'jalur_diterima', 'tanggal_masuk', 'paud_formal',
                        'paud_non_formal', 'hobi', 'keluar_karena', 'tanggal_keluar', 'alasan_keluar', 'status',
                        'sekolah_tujuan', 'nomor_skp', 'nomor_ijazah_kelulusan', 'nomor_skl', 'keterangan_setelah_lulus'
                    ]
                };
                return stepFields[step] || [];
            };

            const saveCurrentStep = async (isFullSubmit = false) => {
                // If it is a full submit, validate all steps. Otherwise, validate only the current step.
                if (isFullSubmit) {
                    let allStepsValid = true;
                    for (let s = 1; s <= 5; s++) {
                        if (!validateStep(s)) {
                            allStepsValid = false;
                            currentStep.value = s;
                            break;
                        }
                    }
                    if (!allStepsValid) {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }
                } else {
                    if (!validateStep(currentStep.value)) {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }
                }

                loadingSaveStep.value = true;
                errorsList.value = [];

                try {
                    const formData = new FormData();
                    const studentId = '<?= htmlspecialchars($idSiswa) ?>';
                    formData.append('id', studentId);
                    
                    if (!isFullSubmit) {
                        formData.append('current_step', currentStep.value);
                    }

                    // Collect fields to send
                    if (isFullSubmit) {
                        // Send all steps fields
                        for (let s = 1; s <= 5; s++) {
                            const fields = getFieldsForStep(s);
                            fields.forEach(field => {
                                if (form.value[field] !== undefined && form.value[field] !== null) {
                                    formData.append(field, form.value[field]);
                                }
                            });
                        }
                    } else {
                        // Send only current step fields
                        const fields = getFieldsForStep(currentStep.value);
                        fields.forEach(field => {
                            if (form.value[field] !== undefined && form.value[field] !== null) {
                                formData.append(field, form.value[field]);
                            }
                        });
                    }

                    // Append files if on Step 5 or isFullSubmit
                    if (currentStep.value === 5 || isFullSubmit) {
                        let totalSize = 0;
                        const fileInputs = [
                            'foto_profil', 'berkas_kk', 'berkas_akta', 'berkas_ijazah_sd', 
                            'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk', 
                            'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 'berkas_pernyataan_tka'
                        ];
                        
                        fileInputs.forEach(key => {
                            const inputElement = document.querySelector(`input[name="${key}"]`);
                            if (inputElement && inputElement.files && inputElement.files[0]) {
                                formData.append(key, inputElement.files[0]);
                                totalSize += inputElement.files[0].size;
                            }
                        });

                        // Cek total payload file di sisi client (mencegah error Nginx 413 / Connection Drop yang dibaca Axios sbg Network Error)
                        if (totalSize > 7.5 * 1024 * 1024) { // Batas aman 7.5 MB (asumsi server default post_max_size = 8MB)
                            throw new Error("Total ukuran dokumen yang Anda pilih (" + (totalSize/(1024*1024)).toFixed(1) + " MB) terlalu besar. Batas maksimal server adalah 8MB. Silakan kompres ukuran file Anda atau unggah satu per satu.");
                        }
                    }

                    // Send to backend via Axios
                    const response = await axios.post('/SINTA-SaaS/siswa/update', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.data && response.data.success) {
                        if (isFullSubmit) {
                            localStorage.removeItem('siswa_form_draft');
                            Swal.fire({
                                icon: 'success',
                                title: 'Pembaruan Berhasil',
                                text: response.data.message || 'Data siswa berhasil diperbarui secara penuh!',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload halaman saja agar tetap di halaman edit/tambah siswa sesuai instruksi
                                window.location.reload();
                            });
                        } else {
                            // Update file names in form state if returned
                            if (response.data.files) {
                                Object.keys(response.data.files).forEach(key => {
                                    if (response.data.files[key]) {
                                        form.value[key] = response.data.files[key];
                                    }
                                });
                            }

                            // Clear selected files state since they are now saved on the server
                            if (currentStep.value === 5) {
                                Object.keys(filesSelected.value).forEach(key => {
                                    filesSelected.value[key] = '';
                                });
                            }

                            // Show premium SweetAlert2 Toast for partial save success
                            Swal.fire({
                                icon: 'success',
                                title: 'Simpan Step Berhasil',
                                text: response.data.message || `Data Step ${currentStep.value} berhasil disimpan ke database!`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                        }
                    } else if (response.data && response.data.errors) {
                        errorsList.value = Object.values(response.data.errors);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validasi Gagal',
                            text: 'Silakan periksa kembali isian form Anda (scroll ke atas untuk detail).',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        throw new Error(response.data.error || 'Terjadi kesalahan sistem.');
                    }
                } catch (err) {
                    // Full validation check removed to allow partial updates in edit mode.
                    let errMsg = (err.response && err.response.data && err.response.data.error) || err.message || 'Gagal menyimpan perubahan.';
                    if (err.response && err.response.status === 413) {
                        errMsg = "Total ukuran semua dokumen yang Anda unggah secara bersamaan terlalu besar dan ditolak oleh server (Batas maksimal server terlampaui). Silakan unggah dokumen satu per satu secara bertahap, lalu klik 'Simpan / Update'.";
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Penyimpanan Gagal',
                        text: errMsg,
                        confirmButtonText: 'OK'
                    });
                } finally {
                    loadingSaveStep.value = false;
                }
            };

            const submitFullForm = () => {
                if (isEdit.value) {
                    saveCurrentStep(true);
                } else {
                    document.getElementById('wizardForm').submit();
                }
            };

            const cancelMutasi = () => {
                Swal.fire({
                    title: 'Batalkan Status Mutasi?',
                    text: "Tindakan ini akan mengembalikan status siswa menjadi 'Aktif' dan mengosongkan semua data keluar/mutasi.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#dc2626',
                    confirmButtonText: 'Ya, Aktifkan Kembali',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.value.status = 'Aktif';
                        form.value.keluar_karena = '';
                        form.value.tanggal_keluar = '';
                        form.value.alasan_keluar = '';
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Dibatalkan',
                            text: "Status siswa telah kembali menjadi 'Aktif' dan form keluar dikosongkan.",
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            };

            // --- WIZARD NAVIGATION & VALIDATION ---
            const prevStep = async () => {
                if (currentStep.value > 1) {
                    currentStep.value--;
                    errorsList.value = [];
                    await saveDraft();
                }
            };

            const validateStepHtml5 = (step) => {
                // Bypass HTML5 validation agar bisa lanjut/simpan meskipun data kosong
                return true;
            };

            const validateStep = (step) => {
                // Bypass validasi frontend sesuai permintaan (siswa tidak wajib isi semua data untuk lanjut/simpan)
                errorsList.value = [];
                return true;
            };


            const nextStep = async () => {
                if (validateStep(currentStep.value)) {
                    if (currentStep.value < 5) {
                        currentStep.value++;
                        errorsList.value = [];
                        await saveDraft();
                    }
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            };

            const goToStep = async (step) => {
                if (step < currentStep.value) {
                    currentStep.value = step;
                    errorsList.value = [];
                    await saveDraft();
                } else if (step > currentStep.value) {
                    let valid = true;
                    for (let s = currentStep.value; s < step; s++) {
                        if (!validateStep(s)) {
                            valid = false;
                            break;
                        }
                    }
                    if (valid) {
                        currentStep.value = step;
                        errorsList.value = [];
                        await saveDraft();
                    } else {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            };

            // Load draft data (TUGAS 1)
            const loadDraftData = () => {
                const phpDraft = <?= json_encode($data['draft'] ?? null) ?> || <?= json_encode($data['old'] ?? null) ?>;
                if (phpDraft && Object.keys(phpDraft).length > 0) {
                    Object.keys(phpDraft).forEach(key => {
                        if (key in form.value) {
                            let val = phpDraft[key] !== null ? phpDraft[key] : '';
                            if (typeof form.value[key] === 'number' && val !== '') {
                                val = Number(val);
                            }
                            form.value[key] = val;
                        }
                    });
                    
                    // Re-trigger chained dropdown loads for loaded draft values
                    if (form.value.id_provinsi) {
                        fetchKota(form.value.id_provinsi, false);
                    }
                    if (form.value.id_kota) {
                        fetchKecamatan(form.value.id_kota, false);
                    }
                    if (form.value.id_kecamatan) {
                        fetchKelurahan(form.value.id_kecamatan, false);
                    }
                    if (form.value.tenant_id) {
                        fetchAcademicOptions(form.value.tenant_id);
                    }
                    return;
                }
                
                // Fallback to localStorage draft
                const localDraftStr = localStorage.getItem('siswa_form_draft');
                if (localDraftStr) {
                    try {
                        const localDraft = JSON.parse(localDraftStr);
                        if (localDraft && typeof localDraft === 'object') {
                            Object.keys(localDraft).forEach(key => {
                                if (key in form.value) {
                                    let val = localDraft[key] !== null ? localDraft[key] : '';
                                    if (val === '0000-00-00') val = '';
                                    if (typeof form.value[key] === 'number' && val !== '') {
                                        val = Number(val);
                                    }
                                    form.value[key] = val;
                                }
                            });
                            
                            // Re-trigger chained dropdown loads
                            if (form.value.id_provinsi) {
                                fetchKota(form.value.id_provinsi, false);
                            }
                            if (form.value.id_kota) {
                                fetchKecamatan(form.value.id_kota, false);
                            }
                            if (form.value.id_kecamatan) {
                                fetchKelurahan(form.value.id_kecamatan, false);
                            }
                            if (form.value.tenant_id) {
                                fetchAcademicOptions(form.value.tenant_id);
                            }
                        }
                    } catch (e) {
                        console.error("Gagal parse local draft", e);
                    }
                }
            };

            // Clear localStorage on submit (TUGAS 1)
            const onSubmit = () => {
                localStorage.removeItem('siswa_form_draft');
            };

            // Watch form values to auto-save to localStorage reactively (TUGAS 1)
            Vue.watch(form, (newVal) => {
                if (!isEdit.value) {
                    localStorage.setItem('siswa_form_draft', JSON.stringify(newVal));
                }
            }, { deep: true });

            // Reset no_kip if punya_kip changes from 'Ya' (1) to 'Tidak' (0)
            Vue.watch(() => form.value.punya_kip, (newVal) => {
                if (newVal != 1) {
                    form.value.no_kip = '';
                }
            });

            // Reset alasan_layak if layak_kip changes from 'Ya' (1) to 'Tidak' (0)
            Vue.watch(() => form.value.layak_kip, (newVal) => {
                if (newVal != 1) {
                    form.value.alasan_layak = '';
                }
            });

            // Auto-uppercase ukuran_seragam_sekolah
            Vue.watch(() => form.value.ukuran_seragam_sekolah, (newVal) => {
                if (newVal) {
                    form.value.ukuran_seragam_sekolah = newVal.toUpperCase();
                }
            });

            // Auto-uppercase ukuran_seragam_olahraga
            Vue.watch(() => form.value.ukuran_seragam_olahraga, (newVal) => {
                if (newVal) {
                    form.value.ukuran_seragam_olahraga = newVal.toUpperCase();
                }
            });

            // Watch tanggal_lahir_ayah to auto-populate tahun_lahir_ayah
            Vue.watch(() => form.value.tanggal_lahir_ayah, (newVal) => {
                if (newVal) {
                    form.value.tahun_lahir_ayah = new Date(newVal).getFullYear();
                } else {
                    form.value.tahun_lahir_ayah = '';
                }
            });

            // Watch tanggal_lahir_ibu to auto-populate tahun_lahir_ibu
            Vue.watch(() => form.value.tanggal_lahir_ibu, (newVal) => {
                if (newVal) {
                    form.value.tahun_lahir_ibu = new Date(newVal).getFullYear();
                } else {
                    form.value.tahun_lahir_ibu = '';
                }
            });

            // Watch tanggal_lahir_wali to auto-populate tahun_lahir_wali
            Vue.watch(() => form.value.tanggal_lahir_wali, (newVal) => {
                if (newVal) {
                    form.value.tahun_lahir_wali = new Date(newVal).getFullYear();
                } else {
                    form.value.tahun_lahir_wali = '';
                }
            });

            // Watch status to automatically set keluar_karena when status is Lulus or Pindah
            Vue.watch(() => form.value.status, (newVal) => {
                if (newVal === 'Lulus') {
                    form.value.keluar_karena = 'Lulus';
                } else if (newVal === 'Pindah') {
                    form.value.keluar_karena = 'Mutasi';
                }
            });

            // --- INITIALIZATION ---
            onMounted(async () => {
                await fetchProvinces();
                await fetchAllCities();
                if (userRole.value === 'super_admin') {
                    await fetchTenants();
                } else {
                    await fetchAcademicOptions();
                }
                
                if (isEdit.value) {
                    loadEditData();
                } else {
                    loadDraftData();
                }

                // Inject PHP errors if any
                const phpErrors = <?= json_encode($data['errors'] ?? []) ?>;
                if (phpErrors && typeof phpErrors === 'object') {
                    Object.values(phpErrors).forEach(err => {
                        errorsList.value.push(err);
                    });
                }
            });

            // Document Viewer State
            const viewerModalTitle = ref('');
            const viewerModalUrl = ref('');
            const isViewerFilePdf = computed(() => {
                const url = viewerModalUrl.value || '';
                return url.toLowerCase().endsWith('.pdf') || url.toLowerCase().includes('application/pdf');
            });

            const openDocumentViewer = (path, title) => {
                if (!path) return;
                viewerModalTitle.value = title;
                viewerModalUrl.value = getFileUrl(path, '');
                
                // Open Bootstrap Modal
                const modalEl = document.getElementById('documentViewerModal');
                if (modalEl && window.bootstrap) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                } else {
                    // Fallback to external window if modal/bootstrap is not available
                    window.open(viewerModalUrl.value, '_blank');
                }
            };

            return {
                viewerModalTitle,
                viewerModalUrl,
                isViewerFilePdf,
                openDocumentViewer,
                userRole,
                isEdit,
                listTenants,
                onTenantChange,
                currentStep,
                stepNames,
                activeParentTab,
                provinces,
                cities,
                citiesOptions,
                cityFiltered,
                districts,
                subdistricts,
                acOptions,
                filesSelected,
                filePreviews,
                errorsList,
                form,
                filteredJurusan,
                filteredKelas,
                onJenjangChange,
                onJurusanChange,
                onProvinceChange,
                onCityChange,
                onDistrictChange,
                onFileSelected,
                getFileUrl,
                prevStep,
                nextStep,
                goToStep,
                onSubmit,
                loadingAcademic,
                loadingProvinces,
                loadingCities,
                loadingDistricts,
                loadingSubdistricts,
                loadingSaveStep,
                saveCurrentStep,
                submitFullForm,
                isFormValid,
                cancelMutasi
            };
        }
    }, function(app) {
        // Custom Component for Searchable Select
        app.component('searchable-select', {
            props: {
                modelValue: [String, Number],
                options: {
                    type: Array,
                    required: true
                },
                placeholder: {
                    type: String,
                    default: '-- Pilih --'
                },
                name: {
                    type: String,
                    required: true
                },
                id: {
                    type: String,
                    required: true
                },
                required: {
                    type: Boolean,
                    default: false
                }
            },
            emits: ['update:modelValue'],
            setup(props, { emit }) {
                const isOpen = ref(false);
                const searchQuery = ref('');
                const containerRef = ref(null);
    
                const selectedLabel = computed(() => {
                    const found = props.options.find(opt => String(opt.id) === String(props.modelValue));
                    return found ? found.label : '';
                });
    
                const filteredOptions = computed(() => {
                    const query = searchQuery.value.trim().toLowerCase();
                    if (!query) return props.options;
                    return props.options.filter(opt => 
                        opt.label.toLowerCase().includes(query)
                    );
                });
    
                const toggleDropdown = () => {
                    isOpen.value = !isOpen.value;
                    if (isOpen.value) {
                        searchQuery.value = '';
                        setTimeout(() => {
                            const input = containerRef.value && containerRef.value.querySelector('.search-input');
                            if (input) input.focus();
                        }, 50);
                    }
                };
    
                const selectOption = (opt) => {
                    emit('update:modelValue', opt.id);
                    isOpen.value = false;
                    searchQuery.value = '';
                };
    
                const handleClickOutside = (e) => {
                    if (containerRef.value && !containerRef.value.contains(e.target)) {
                        isOpen.value = false;
                    }
                };
    
                onMounted(() => {
                    document.addEventListener('click', handleClickOutside);
                });
    
                onUnmounted(() => {
                    document.removeEventListener('click', handleClickOutside);
                });
    
                return {
                    isOpen,
                    searchQuery,
                    containerRef,
                    selectedLabel,
                    filteredOptions,
                    toggleDropdown,
                    selectOption
                };
            },
            template: `
                <div class="position-relative w-100" ref="containerRef">
                    <input type="hidden" :name="name" :value="modelValue" :required="required">
                    
                    <div class="form-select d-flex align-items-center justify-content-between cursor-pointer"
                         :class="{ 'border-primary shadow-sm': isOpen }"
                         @click="toggleDropdown"
                         style="cursor: pointer; min-height: 38px; user-select: none;">
                        <span :class="{ 'text-muted': !selectedLabel }">
                            {{ selectedLabel || placeholder }}
                        </span>
                        <i class="bi" :class="isOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </div>
                    
                    <div v-show="isOpen" class="position-absolute w-100 mt-1 shadow bg-white border rounded-3 overflow-hidden" 
                         style="z-index: 1050; max-height: 280px; display: flex; flex-direction: column;">
                        <div class="p-2 border-bottom bg-light">
                            <input type="text" 
                                   :id="'searchQuery_' + $.uid"
                                   v-model="searchQuery" 
                                   class="form-control form-control-sm search-input" 
                                   placeholder="Ketik untuk mencari..." 
                                   @click.stop>
                        </div>
                        <div class="overflow-y-auto" style="flex: 1; max-height: 220px;">
                            <div v-if="filteredOptions.length === 0" class="p-3 text-muted text-center fs-8">
                                Data tidak ditemukan
                            </div>
                            <div v-else
                                 v-for="opt in filteredOptions" 
                                 :key="opt.id" 
                                 @click="selectOption(opt)"
                                 class="p-2 cursor-pointer border-bottom text-start dropdown-item fs-8 hover-bg"
                                 :class="{ 'bg-primary text-white': String(opt.id) === String(modelValue) }"
                                 style="cursor: pointer;">
                                {{ opt.label }}
                            </div>
                        </div>
                    </div>
                </div>
            `
        });
    });
}
</script>
