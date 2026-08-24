<?php
/**
 * View: Tambah/Edit Siswa (Child View)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */

use App\Config\Database;

// Tentukan mode: Edit atau Tambah Baru
$isEdit = isset($data['siswa']);
$actionUrl = $isEdit ? $this->getBaseUrl() . '/siswa/update' : $this->getBaseUrl() . '/siswa/simpan';
$formTitle = $isEdit ? 'Edit Data Siswa' : 'Tambah Siswa Baru';
$idSiswa = $isEdit ? ($data['siswa']['id'] ?? '') : '';
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

    /* =========================================================
       Ultra-Modern Document Upload Cards Interface
       ========================================================= */
    .doc-upload-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
    }
    .doc-upload-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.08);
        transform: translateY(-2px);
    }
    .doc-upload-card.is-uploaded {
        border-color: rgba(34, 197, 94, 0.35);
        background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
    }
    .doc-upload-card.is-selected {
        border-color: rgba(59, 130, 246, 0.45);
        background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
    }
    .doc-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.85rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed #e2e8f0;
    }
    .doc-card-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0;
    }
    .doc-dropzone {
        border: 1.5px dashed #cbd5e1;
        border-radius: 0.75rem;
        padding: 1.15rem 0.75rem;
        text-align: center;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 135px;
        margin-bottom: 0.75rem;
    }
    .doc-dropzone:hover {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }
    .doc-dropzone.has-file {
        border-style: solid;
        border-color: rgba(34, 197, 94, 0.3);
        background-color: rgba(240, 253, 244, 0.7);
    }
    .doc-dropzone input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 5;
    }
    .doc-preview-img-box {
        text-align: center;
    }
    .doc-preview-img {
        max-height: 75px;
        max-width: 100%;
        object-fit: cover;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
    }
    .doc-card-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-top: auto;
        padding-top: 0.65rem;
        border-top: 1px solid #f1f5f9;
        min-height: 40px;
    }
    .doc-card-actions .btn-view-doc {
        background-color: #10b981;
        color: #ffffff;
        border: none;
        border-radius: 0.5rem;
        padding: 0.35rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px 0 rgba(16, 185, 129, 0.2);
        text-decoration: none;
    }
    .doc-card-actions .btn-view-doc:hover {
        background-color: #059669;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
    }
    .doc-card-actions .btn-ext-doc {
        color: #64748b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.35rem 0.6rem;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .doc-card-actions .btn-ext-doc:hover {
        color: #0f172a;
        background: #e2e8f0;
        border-color: #cbd5e1;
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
        <a href="<?= $this->getBaseUrl() ?>/pengguna" class="btn btn-outline-secondary d-flex align-items-center rounded-3 px-3 py-2 fs-7">
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
        <a href="<?= $this->getBaseUrl() ?>/tracer-study" class="btn btn-sm btn-warning fw-semibold rounded-3">
            <i class="bi bi-mortarboard-fill me-1"></i> Isi Tracer Study Alumni →
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Main Wizard Component Container (Vue Mounted) -->
<div id="studentWizardApp" v-cloak class="wizard-card p-4 p-md-5 mb-5"
     data-is-locked="<?= htmlspecialchars($isLocked ? 'true' : 'false', ENT_QUOTES, 'UTF-8') ?>">
    
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
                    <select class="form-select" id="id_jurusan" name="id_jurusan" v-model="form.id_jurusan" @change="onJurusanChange" :disabled="loadingAcademic" required>
                        <option value="" disabled>{{ loadingAcademic ? 'Memuat data...' : '-- Pilih Jurusan --' }}</option>
                        <option v-for="opt in filteredJurusan" :key="opt.id" :value="opt.id">{{ opt.nama_jurusan }}</option>
                    </select>
                </div>

                <!-- Kelas (Rombel) - Di-filter reaktif berdasarkan Jenjang & Jurusan -->
                <div class="col-md-4">
                    <label for="id_kelas" class="form-label">Rombel / Kelas <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_kelas" name="id_kelas" v-model="form.id_kelas" :disabled="loadingAcademic" required>
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
                        <option v-for="p in (Array.isArray(provinces) ? provinces.filter(x => x && x.id_provinsi) : [])" :key="p.id_provinsi" :value="p.id_provinsi">{{ p.nama_provinsi }}</option>
                    </select>
                </div>

                <!-- Chain Dropdown Wilayah: Kota -->
                <div class="col-md-6">
                    <label for="city_select" class="form-label">Kabupaten / Kota <span class="text-danger">*</span></label>
                    <select class="form-select" id="city_select" v-model="form.id_kota" @change="onCityChange" :disabled="loadingCities || !form.id_provinsi" required>
                        <option value="" disabled>{{ loadingCities ? 'Memuat data...' : '-- Pilih Kota --' }}</option>
                        <option v-for="c in (Array.isArray(cityFiltered) ? cityFiltered.filter(x => x && x.id_kota) : [])" :key="c.id_kota" :value="c.id_kota">{{ c.nama_kota }}</option>
                    </select>
                </div>

                <!-- Chain Dropdown Wilayah: Kecamatan -->
                <div class="col-md-6">
                    <label for="district_select" class="form-label">Kecamatan <span class="text-danger">*</span></label>
                    <select class="form-select" id="district_select" v-model="form.id_kecamatan" @change="onDistrictChange" :disabled="loadingDistricts || !form.id_kota" required>
                        <option value="" disabled>{{ loadingDistricts ? 'Memuat data...' : '-- Pilih Kecamatan --' }}</option>
                        <option v-for="d in (Array.isArray(districts) ? districts.filter(x => x && x.id_kecamatan) : [])" :key="d.id_kecamatan" :value="d.id_kecamatan">{{ d.nama_kecamatan }}</option>
                    </select>
                </div>

                <!-- Chain Dropdown Wilayah: Kelurahan (Final Table ID) -->
                <div class="col-md-6">
                    <label for="id_kelurahan" class="form-label">Kelurahan <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_kelurahan" name="id_kelurahan" v-model="form.id_kelurahan" :disabled="loadingSubdistricts || !form.id_kecamatan" required>
                        <option value="" disabled>{{ loadingSubdistricts ? 'Memuat data...' : '-- Pilih Kelurahan --' }}</option>
                        <option v-for="k in (Array.isArray(subdistricts) ? subdistricts.filter(x => x && x.id_kelurahan) : [])" :key="k.id_kelurahan" :value="k.id_kelurahan">{{ k.nama_kelurahan }}</option>
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
                    <input type="number" class="form-control" id="tinggi_badan" name="tinggi_badan" v-model.number="form.tinggi_badan" min="30" max="255" required>
                </div>

                <!-- Berat Badan -->
                <div class="col-md-4">
                    <label for="berat_badan" class="form-label">Berat Badan (kg) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="berat_badan" name="berat_badan" v-model.number="form.berat_badan" min="5" max="255" required>
                </div>

                <!-- Lingkar Kepala -->
                <div class="col-md-4">
                    <label for="lingkar_kepala" class="form-label">Lingkar Kepala (cm) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="lingkar_kepala" name="lingkar_kepala" v-model.number="form.lingkar_kepala" min="20" max="255" required>
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
                    <input type="number" class="form-control" id="anak_ke" name="anak_ke" v-model.number="form.anak_ke" min="1" max="255" required>
                </div>

                <!-- Jumlah Saudara Kandung -->
                <div class="col-md-4">
                    <label for="jumlah_saudara" class="form-label">Jumlah Saudara Kandung <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="jumlah_saudara" name="jumlah_saudara" v-model.number="form.jumlah_saudara" min="0" max="255" required>
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
                    <input type="number" class="form-control" id="jarak_rumah" name="jarak_rumah" v-model.number="form.jarak_rumah" placeholder="Contoh: 1500" min="1" max="65535" required>
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

            <!-- ==================== KESEHATAN PER SEMESTER ==================== -->
            <div class="mt-4 border-top pt-4">
                <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-heart-pulse-fill me-2 text-danger"></i>Riwayat Kesehatan (Per Semester)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle fs-9">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Semester</th>
                                <th style="width: 15%">Tinggi (cm)</th>
                                <th style="width: 15%">Berat (kg)</th>
                                <th>Pendengaran</th>
                                <th>Pengelihatan</th>
                                <th>Kondisi Gigi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sem in 6" :key="sem">
                                <td class="text-center fw-bold">{{ sem }}</td>
                                <td><input type="number" class="form-control form-control-sm" :name="`kesehatan[${sem}][tinggi_badan]`" v-model="form.kesehatan[sem].tinggi_badan" min="0" v-if="form.kesehatan && form.kesehatan[sem]"></td>
                                <td><input type="number" class="form-control form-control-sm" :name="`kesehatan[${sem}][berat_badan]`" v-model="form.kesehatan[sem].berat_badan" min="0" v-if="form.kesehatan && form.kesehatan[sem]"></td>
                                <td><input type="text" class="form-control form-control-sm" :name="`kesehatan[${sem}][pendengaran]`" v-model="form.kesehatan[sem].pendengaran" placeholder="Normal/Kurang" v-if="form.kesehatan && form.kesehatan[sem]"></td>
                                <td><input type="text" class="form-control form-control-sm" :name="`kesehatan[${sem}][pengelihatan]`" v-model="form.kesehatan[sem].pengelihatan" placeholder="Normal/Minus" v-if="form.kesehatan && form.kesehatan[sem]"></td>
                                <td><input type="text" class="form-control form-control-sm" :name="`kesehatan[${sem}][gigi]`" v-model="form.kesehatan[sem].gigi" placeholder="Bersih/Berlubang" v-if="form.kesehatan && form.kesehatan[sem]"></td>
                            </tr>
                        </tbody>
                    </table>
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
                
                <!-- Mutasi Masuk Fields -->
                <div class="col-12 border p-3 bg-light rounded" v-if="form.jenis_pendaftaran === 'Pindahan'">
                    <h6 class="fw-bold mb-3"><i class="bi bi-box-arrow-in-right me-2"></i>Data Asal Pindahan</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="sekolah_asal_mutasi" class="form-label">Nama Sekolah Asal Mutasi</label>
                            <input type="text" class="form-control" id="sekolah_asal_mutasi" name="sekolah_asal_mutasi" v-model="form.sekolah_asal_mutasi" placeholder="Contoh: SMPN 1 Jakarta">
                        </div>
                        <div class="col-md-4">
                            <label for="pindah_dari_tingkat" class="form-label">Pindah dari Tingkat/Kelas</label>
                            <input type="text" class="form-control" id="pindah_dari_tingkat" name="pindah_dari_tingkat" v-model="form.pindah_dari_tingkat" placeholder="Contoh: VII">
                        </div>
                        <div class="col-md-4">
                            <label for="pindah_no_surat" class="form-label">Nomor Surat Keterangan Pindah</label>
                            <input type="text" class="form-control" id="pindah_no_surat" name="pindah_no_surat" v-model="form.pindah_no_surat" placeholder="Masukkan nomor surat">
                        </div>
                    </div>
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
                            <div class="col-md-6" v-if="form.keluar_karena === 'Mutasi'">
                                <label for="tingkat_ditinggalkan" class="form-label">Tingkat/Kelas yang ditinggalkan</label>
                                <input type="text" class="form-control" id="tingkat_ditinggalkan" name="tingkat_ditinggalkan" v-model="form.tingkat_ditinggalkan" placeholder="Contoh: VII">
                            </div>
                            <div class="col-md-6" v-if="form.keluar_karena === 'Mutasi'">
                                <label for="diterima_di_tingkat" class="form-label">Diterima di Tingkat (Sekolah Tujuan)</label>
                                <input type="text" class="form-control" id="diterima_di_tingkat" name="diterima_di_tingkat" v-model="form.diterima_di_tingkat" placeholder="Contoh: VII">
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
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-cloud-arrow-up-fill fs-6"></i>
                            </span>
                            Upload Berkas & Dokumen Pendukung
                        </h6>
                        <span class="badge bg-light text-muted border px-2.5 py-1.5 fs-9 fw-medium">
                            <i class="bi bi-shield-check text-success me-1"></i>Maksimal 500 KB / Berkas (Auto-Compress)
                        </span>
                    </div>

                    <div class="alert alert-light border border-info-subtle rounded-4 p-3 mb-4 shadow-2xs d-flex align-items-center gap-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                        <div class="rounded-circle bg-info bg-opacity-15 p-2 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                            <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark fs-8">Petunjuk Pengunggahan & Kompresi Berkas Otomatis</div>
                            <div class="text-muted fs-9">Format yang didukung: PDF, JPG, PNG, atau WebP (Maks 500 KB). <strong>Tips Perangkat HP:</strong> Jika mengalami kendala saat mengunggah banyak berkas sekaligus, silakan upload 1 file lalu klik <strong>Simpan</strong>, dan ulangi secara bertahap satu per satu hingga semua dokumen terunggah.</div>
                        </div>
                    </div>

                    <div class="row g-3 g-lg-4">
                        
                        <!-- 1. Foto Profil -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.foto_profil && !filesSelected.foto_profil, 'is-selected': filesSelected.foto_profil}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-person-bounding-box text-primary fs-5"></i>
                                        <span>Foto Profil Murid</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.foto_profil && !filesSelected.foto_profil" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.foto_profil" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.foto_profil || filesSelected.foto_profil}">
                                    <input type="file" name="foto_profil" accept="image/*" @change="onFileSelected($event, 'foto_profil')">
                                    
                                    <!-- Preview Image -->
                                    <div v-if="filePreviews.foto_profil || (form.foto_profil && !filesSelected.foto_profil && isImageFile(form.foto_profil))" class="doc-preview-img-box">
                                        <img :src="filePreviews.foto_profil ? filePreviews.foto_profil : getFileUrl(form.foto_profil, 'foto_profil')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti foto</div>
                                    </div>
                                    
                                    <!-- File newly selected -->
                                    <div v-else-if="filesSelected.foto_profil" class="text-center">
                                        <i class="bi bi-file-earmark-check-fill text-primary fs-1"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 200px;">{{ filesSelected.foto_profil }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <!-- Empty state -->
                                    <div v-else class="text-center">
                                        <i class="bi bi-image fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Foto Profil</div>
                                        <div class="fs-9 text-muted mt-0.5">Ekstensi .jpg, .png, .webp</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.foto_profil && !filesSelected.foto_profil">
                                        <button type="button" @click.prevent="openDocumentViewer(form.foto_profil, 'Foto Profil Murid')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Foto
                                        </button>
                                        <a :href="getFileUrl(form.foto_profil, 'foto_profil')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.foto_profil">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format JPG, PNG, WebP</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Berkas KK -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_kk && !filesSelected.berkas_kk, 'is-selected': filesSelected.berkas_kk}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-people-fill text-primary fs-5"></i>
                                        <span>Kartu Keluarga (KK)</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.berkas_kk && !filesSelected.berkas_kk" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_kk" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_kk || filesSelected.berkas_kk}">
                                    <input type="file" name="berkas_kk" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_kk')">
                                    
                                    <div v-if="filePreviews.berkas_kk || (form.berkas_kk && !filesSelected.berkas_kk && isImageFile(form.berkas_kk))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_kk ? filePreviews.berkas_kk : getFileUrl(form.berkas_kk, 'berkas_kk')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_kk && !filesSelected.berkas_kk && isPdfFile(form.berkas_kk)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_kk) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_kk" class="text-center">
                                        <i :class="filesSelected.berkas_kk.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_kk }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Berkas KK</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_kk && !filesSelected.berkas_kk">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_kk, 'Kartu Keluarga (KK)')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_kk, 'berkas_kk')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_kk">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format PDF, JPG, PNG</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Berkas Akta Lahir -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_akta && !filesSelected.berkas_akta, 'is-selected': filesSelected.berkas_akta}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-file-earmark-person-fill text-primary fs-5"></i>
                                        <span>Akta Kelahiran</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.berkas_akta && !filesSelected.berkas_akta" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_akta" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_akta || filesSelected.berkas_akta}">
                                    <input type="file" name="berkas_akta" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_akta')">
                                    
                                    <div v-if="filePreviews.berkas_akta || (form.berkas_akta && !filesSelected.berkas_akta && isImageFile(form.berkas_akta))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_akta ? filePreviews.berkas_akta : getFileUrl(form.berkas_akta, 'berkas_akta')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_akta && !filesSelected.berkas_akta && isPdfFile(form.berkas_akta)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_akta) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_akta" class="text-center">
                                        <i :class="filesSelected.berkas_akta.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_akta }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Berkas Akta</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_akta && !filesSelected.berkas_akta">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_akta, 'Akta Kelahiran')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_akta, 'berkas_akta')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_akta">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format PDF, JPG, PNG</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Ijazah SD -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_ijazah_sd && !filesSelected.berkas_ijazah_sd, 'is-selected': filesSelected.berkas_ijazah_sd}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
                                        <span>Ijazah SD / MI</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.berkas_ijazah_sd && !filesSelected.berkas_ijazah_sd" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_ijazah_sd" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_ijazah_sd || filesSelected.berkas_ijazah_sd}">
                                    <input type="file" name="berkas_ijazah_sd" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_ijazah_sd')">
                                    
                                    <div v-if="filePreviews.berkas_ijazah_sd || (form.berkas_ijazah_sd && !filesSelected.berkas_ijazah_sd && isImageFile(form.berkas_ijazah_sd))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_ijazah_sd ? filePreviews.berkas_ijazah_sd : getFileUrl(form.berkas_ijazah_sd, 'berkas_ijazah_sd')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_ijazah_sd && !filesSelected.berkas_ijazah_sd && isPdfFile(form.berkas_ijazah_sd)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_ijazah_sd) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_ijazah_sd" class="text-center">
                                        <i :class="filesSelected.berkas_ijazah_sd.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_ijazah_sd }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-file-earmark-text fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Ijazah SD</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_ijazah_sd && !filesSelected.berkas_ijazah_sd">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_ijazah_sd, 'Ijazah SD / MI')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_ijazah_sd, 'berkas_ijazah_sd')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_ijazah_sd">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format PDF, JPG, PNG</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Ijazah SMP -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_ijazah_smp && !filesSelected.berkas_ijazah_smp, 'is-selected': filesSelected.berkas_ijazah_smp}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
                                        <span>Ijazah SMP / MTs</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.berkas_ijazah_smp && !filesSelected.berkas_ijazah_smp" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_ijazah_smp" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_ijazah_smp || filesSelected.berkas_ijazah_smp}">
                                    <input type="file" name="berkas_ijazah_smp" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_ijazah_smp')">
                                    
                                    <div v-if="filePreviews.berkas_ijazah_smp || (form.berkas_ijazah_smp && !filesSelected.berkas_ijazah_smp && isImageFile(form.berkas_ijazah_smp))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_ijazah_smp ? filePreviews.berkas_ijazah_smp : getFileUrl(form.berkas_ijazah_smp, 'berkas_ijazah_smp')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_ijazah_smp && !filesSelected.berkas_ijazah_smp && isPdfFile(form.berkas_ijazah_smp)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_ijazah_smp) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_ijazah_smp" class="text-center">
                                        <i :class="filesSelected.berkas_ijazah_smp.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_ijazah_smp }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-file-earmark-text fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Ijazah SMP</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_ijazah_smp && !filesSelected.berkas_ijazah_smp">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_ijazah_smp, 'Ijazah SMP / MTs')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_ijazah_smp, 'berkas_ijazah_smp')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_ijazah_smp">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format PDF, JPG, PNG</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 6. Ijazah SMA -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_ijazah_sma && !filesSelected.berkas_ijazah_sma, 'is-selected': filesSelected.berkas_ijazah_sma}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
                                        <span>Ijazah SMA / MA (Jika Ada)</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.berkas_ijazah_sma && !filesSelected.berkas_ijazah_sma" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_ijazah_sma" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_ijazah_sma || filesSelected.berkas_ijazah_sma}">
                                    <input type="file" name="berkas_ijazah_sma" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_ijazah_sma')">
                                    
                                    <div v-if="filePreviews.berkas_ijazah_sma || (form.berkas_ijazah_sma && !filesSelected.berkas_ijazah_sma && isImageFile(form.berkas_ijazah_sma))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_ijazah_sma ? filePreviews.berkas_ijazah_sma : getFileUrl(form.berkas_ijazah_sma, 'berkas_ijazah_sma')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_ijazah_sma && !filesSelected.berkas_ijazah_sma && isPdfFile(form.berkas_ijazah_sma)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_ijazah_sma) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_ijazah_sma" class="text-center">
                                        <i :class="filesSelected.berkas_ijazah_sma.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_ijazah_sma }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-file-earmark-text fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Ijazah SMA</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_ijazah_sma && !filesSelected.berkas_ijazah_sma">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_ijazah_sma, 'Ijazah SMA / MA')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_ijazah_sma, 'berkas_ijazah_sma')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_ijazah_sma">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format PDF, JPG, PNG</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 7. Berkas Mutasi Masuk -->
                        <div class="col-12 col-md-6 col-xl-4" v-show="form.jenis_pendaftaran === 'Pindahan'">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_mutasi_masuk && !filesSelected.berkas_mutasi_masuk, 'is-selected': filesSelected.berkas_mutasi_masuk}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-box-arrow-in-right text-info fs-5"></i>
                                        <span>Surat Mutasi Masuk</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.berkas_mutasi_masuk && !filesSelected.berkas_mutasi_masuk" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_mutasi_masuk" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_mutasi_masuk || filesSelected.berkas_mutasi_masuk}">
                                    <input type="file" name="berkas_mutasi_masuk" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_mutasi_masuk')">
                                    
                                    <div v-if="filePreviews.berkas_mutasi_masuk || (form.berkas_mutasi_masuk && !filesSelected.berkas_mutasi_masuk && isImageFile(form.berkas_mutasi_masuk))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_mutasi_masuk ? filePreviews.berkas_mutasi_masuk : getFileUrl(form.berkas_mutasi_masuk, 'berkas_mutasi_masuk')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_mutasi_masuk && !filesSelected.berkas_mutasi_masuk && isPdfFile(form.berkas_mutasi_masuk)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_mutasi_masuk) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_mutasi_masuk" class="text-center">
                                        <i :class="filesSelected.berkas_mutasi_masuk.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_mutasi_masuk }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-file-earmark-arrow-up fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Berkas Mutasi</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_mutasi_masuk && !filesSelected.berkas_mutasi_masuk">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_mutasi_masuk, 'Surat Mutasi Masuk')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_mutasi_masuk, 'berkas_mutasi_masuk')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_mutasi_masuk">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format PDF, JPG, PNG</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 8. Berkas Mutasi Keluar -->
                        <div class="col-12 col-md-6 col-xl-4" v-show="form.status === 'Pindah'">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_mutasi_keluar && !filesSelected.berkas_mutasi_keluar, 'is-selected': filesSelected.berkas_mutasi_keluar}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-box-arrow-right text-warning fs-5"></i>
                                        <span>Surat Mutasi Keluar</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.berkas_mutasi_keluar && !filesSelected.berkas_mutasi_keluar" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_mutasi_keluar" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_mutasi_keluar || filesSelected.berkas_mutasi_keluar}">
                                    <input type="file" name="berkas_mutasi_keluar" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_mutasi_keluar')">
                                    
                                    <div v-if="filePreviews.berkas_mutasi_keluar || (form.berkas_mutasi_keluar && !filesSelected.berkas_mutasi_keluar && isImageFile(form.berkas_mutasi_keluar))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_mutasi_keluar ? filePreviews.berkas_mutasi_keluar : getFileUrl(form.berkas_mutasi_keluar, 'berkas_mutasi_keluar')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_mutasi_keluar && !filesSelected.berkas_mutasi_keluar && isPdfFile(form.berkas_mutasi_keluar)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_mutasi_keluar) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_mutasi_keluar" class="text-center">
                                        <i :class="filesSelected.berkas_mutasi_keluar.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_mutasi_keluar }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-file-earmark-arrow-down fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Berkas Mutasi</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_mutasi_keluar && !filesSelected.berkas_mutasi_keluar">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_mutasi_keluar, 'Surat Mutasi Keluar')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_mutasi_keluar, 'berkas_mutasi_keluar')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_mutasi_keluar">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format PDF, JPG, PNG</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 9. Berkas KIP -->
                        <div class="col-12 col-md-6 col-xl-4" v-show="form.punya_kip == 1">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_kip && !filesSelected.berkas_kip, 'is-selected': filesSelected.berkas_kip}">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-credit-card-2-front-fill text-success fs-5"></i>
                                        <span>Kartu KIP / PKH</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="form.berkas_kip && !filesSelected.berkas_kip" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_kip" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_kip || filesSelected.berkas_kip}">
                                    <input type="file" name="berkas_kip" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_kip')">
                                    
                                    <div v-if="filePreviews.berkas_kip || (form.berkas_kip && !filesSelected.berkas_kip && isImageFile(form.berkas_kip))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_kip ? filePreviews.berkas_kip : getFileUrl(form.berkas_kip, 'berkas_kip')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_kip && !filesSelected.berkas_kip && isPdfFile(form.berkas_kip)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_kip) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_kip" class="text-center">
                                        <i :class="filesSelected.berkas_kip.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_kip }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-credit-card-2-front fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">Pilih Kartu KIP</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png / .webp</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_kip && !filesSelected.berkas_kip">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_kip, 'Kartu KIP / PKH')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_kip, 'berkas_kip')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_kip">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-shield-check me-1"></i>Format PDF, JPG, PNG, WebP</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 10. Surat Pernyataan Siswa Baru & Orang Tua -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_pernyataan_baru && !filesSelected.berkas_pernyataan_baru, 'is-selected': filesSelected.berkas_pernyataan_baru}" :style="userRole === 'siswa' ? 'cursor: not-allowed; opacity: 0.7;' : ''">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-file-earmark-text-fill text-secondary fs-5"></i>
                                        <span>Surat Pernyataan Baru & Ortu</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="userRole === 'siswa'" class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Admin Only
                                        </span>
                                        <span v-else-if="form.berkas_pernyataan_baru && !filesSelected.berkas_pernyataan_baru" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_pernyataan_baru" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_pernyataan_baru || filesSelected.berkas_pernyataan_baru}">
                                    <input v-if="userRole !== 'siswa'" type="file" name="berkas_pernyataan_baru" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_pernyataan_baru')">
                                    
                                    <div v-if="filePreviews.berkas_pernyataan_baru || (form.berkas_pernyataan_baru && !filesSelected.berkas_pernyataan_baru && isImageFile(form.berkas_pernyataan_baru))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_pernyataan_baru ? filePreviews.berkas_pernyataan_baru : getFileUrl(form.berkas_pernyataan_baru, 'berkas_pernyataan_baru')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_pernyataan_baru && !filesSelected.berkas_pernyataan_baru && isPdfFile(form.berkas_pernyataan_baru)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_pernyataan_baru) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_pernyataan_baru" class="text-center">
                                        <i :class="filesSelected.berkas_pernyataan_baru.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_pernyataan_baru }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-file-earmark-text fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">{{ userRole === 'siswa' ? 'Hanya Diisi Admin' : 'Pilih Surat Pernyataan' }}</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_pernyataan_baru && !filesSelected.berkas_pernyataan_baru">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_pernyataan_baru, 'Surat Pernyataan Baru & Orang Tua')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_pernyataan_baru, 'berkas_pernyataan_baru')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_pernyataan_baru">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-lock-fill me-1"></i>Dikelola oleh Administrator</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- 11. Surat Pernyataan TKA -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="doc-upload-card" :class="{'is-uploaded': form.berkas_pernyataan_tka && !filesSelected.berkas_pernyataan_tka, 'is-selected': filesSelected.berkas_pernyataan_tka}" :style="userRole === 'siswa' ? 'cursor: not-allowed; opacity: 0.7;' : ''">
                                <div class="doc-card-header">
                                    <div class="doc-card-title">
                                        <i class="bi bi-award-fill text-warning fs-5"></i>
                                        <span>Surat Pernyataan TKA</span>
                                    </div>
                                    <div class="doc-card-badge">
                                        <span v-if="userRole === 'siswa'" class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Admin Only
                                        </span>
                                        <span v-else-if="form.berkas_pernyataan_tka && !filesSelected.berkas_pernyataan_tka" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Terunggah
                                        </span>
                                        <span v-else-if="filesSelected.berkas_pernyataan_tka" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                            <i class="bi bi-arrow-repeat me-1"></i>Siap Simpan
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-9">
                                            Belum Ada
                                        </span>
                                    </div>
                                </div>

                                <div class="doc-dropzone" :class="{'has-file': form.berkas_pernyataan_tka || filesSelected.berkas_pernyataan_tka}">
                                    <input v-if="userRole !== 'siswa'" type="file" name="berkas_pernyataan_tka" accept="image/*,application/pdf" @change="onFileSelected($event, 'berkas_pernyataan_tka')">
                                    
                                    <div v-if="filePreviews.berkas_pernyataan_tka || (form.berkas_pernyataan_tka && !filesSelected.berkas_pernyataan_tka && isImageFile(form.berkas_pernyataan_tka))" class="doc-preview-img-box">
                                        <img :src="filePreviews.berkas_pernyataan_tka ? filePreviews.berkas_pernyataan_tka : getFileUrl(form.berkas_pernyataan_tka, 'berkas_pernyataan_tka')" class="doc-preview-img">
                                        <div class="fs-9 text-muted mt-1.5"><i class="bi bi-pencil-fill me-1"></i>Klik untuk ganti berkas</div>
                                    </div>
                                    
                                    <div v-else-if="form.berkas_pernyataan_tka && !filesSelected.berkas_pernyataan_tka && isPdfFile(form.berkas_pernyataan_tka)" class="text-center">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                        <div class="fs-8 fw-semibold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ getDisplayFileName(form.berkas_pernyataan_tka) }}</div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 mt-1">Dokumen PDF</span>
                                    </div>

                                    <div v-else-if="filesSelected.berkas_pernyataan_tka" class="text-center">
                                        <i :class="filesSelected.berkas_pernyataan_tka.toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill text-danger fs-1' : 'bi bi-file-earmark-image-fill text-primary fs-1'"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1 text-truncate" style="max-width: 220px;">{{ filesSelected.berkas_pernyataan_tka }}</div>
                                        <div class="fs-9 text-primary mt-0.5">Berkas siap disimpan</div>
                                    </div>

                                    <div v-else class="text-center">
                                        <i class="bi bi-award fs-1 text-secondary opacity-75"></i>
                                        <div class="fs-8 fw-bold text-dark mt-1">{{ userRole === 'siswa' ? 'Hanya Diisi Admin' : 'Pilih Surat Pernyataan TKA' }}</div>
                                        <div class="fs-9 text-muted mt-0.5">Format .pdf / .jpg / .png</div>
                                    </div>
                                </div>

                                <div class="px-1 mb-2" v-if="userRole !== 'siswa'">
                                    <div class="text-warning fw-semibold d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-info-circle-fill"></i> Diisi khusus ketika siswa sudah kelas 12
                                    </div>
                                </div>

                                <div class="doc-card-actions">
                                    <template v-if="form.berkas_pernyataan_tka && !filesSelected.berkas_pernyataan_tka">
                                        <button type="button" @click.prevent="openDocumentViewer(form.berkas_pernyataan_tka, 'Surat Pernyataan TKA')" class="btn-view-doc">
                                            <i class="bi bi-eye-fill"></i> Lihat Berkas
                                        </button>
                                        <a :href="getFileUrl(form.berkas_pernyataan_tka, 'berkas_pernyataan_tka')" target="_blank" class="btn-ext-doc" title="Buka berkas di tab baru">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </template>
                                    <template v-else-if="filesSelected.berkas_pernyataan_tka">
                                        <span class="fs-9 text-primary fw-medium"><i class="bi bi-arrow-repeat me-1"></i>Klik Simpan di bawah</span>
                                    </template>
                                    <template v-else>
                                        <span class="fs-9 text-muted"><i class="bi bi-lock-fill me-1"></i>Dikelola oleh Administrator</span>
                                    </template>
                                </div>
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
            <a href="<?= $this->getBaseUrl() ?>/pengguna" class="btn btn-light rounded-3 px-4 py-2 fs-7 shadow-sm border w-100 w-sm-auto order-last order-sm-first text-center" v-show="currentStep === 1">
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
            const idSiswa = ref('<?= htmlspecialchars($idSiswa, ENT_QUOTES, 'UTF-8') ?>');

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
                saudara_tiri: '',
                saudara_angkat: '',
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
                sekolah_asal_mutasi: '',
                pindah_dari_tingkat: '',
                pindah_no_surat: '',
                tingkat_ditinggalkan: '',
                diterima_di_tingkat: '',
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
                berkas_pernyataan_tka: '',

                // Kesehatan Per Semester
                kesehatan: {
                    1: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                    2: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                    3: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                    4: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                    5: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                    6: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' }
                }
            });

            // Helper aman untuk re-hydration data riwayat kesehatan tanpa merusak struktur semester {1..6}
            const hydrateKesehatan = (kesehatanSource) => {
                if (!kesehatanSource) return;
                if (!form.value.kesehatan || typeof form.value.kesehatan !== 'object') {
                    form.value.kesehatan = {
                        1: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                        2: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                        3: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                        4: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                        5: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
                        6: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' }
                    };
                }
                for (let sem = 1; sem <= 6; sem++) {
                    if (!form.value.kesehatan[sem]) {
                        form.value.kesehatan[sem] = { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' };
                    }
                }
                if (Array.isArray(kesehatanSource)) {
                    kesehatanSource.forEach(item => {
                        const sem = item.semester || 1;
                        if (form.value.kesehatan[sem]) {
                            Object.assign(form.value.kesehatan[sem], item);
                        }
                    });
                } else if (typeof kesehatanSource === 'object') {
                    if (kesehatanSource[1] || kesehatanSource['1']) {
                        for (let sem = 1; sem <= 6; sem++) {
                            if (kesehatanSource[sem]) {
                                Object.assign(form.value.kesehatan[sem], kesehatanSource[sem]);
                            }
                        }
                    } else {
                        const sem = kesehatanSource.semester || 1;
                        if (form.value.kesehatan[sem]) {
                            Object.assign(form.value.kesehatan[sem], kesehatanSource);
                        }
                    }
                }
            };



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
                if (!form.value.id_jenjang || !Array.isArray(acOptions.value.kelas) || !Array.isArray(acOptions.value.jurusan)) return acOptions.value.jurusan || [];
                const allowedJurusanIds = acOptions.value.kelas
                    .filter(k => k && String(k.id_jenjang) === String(form.value.id_jenjang))
                    .map(k => String(k.id_jurusan));
                
                if (allowedJurusanIds.length === 0) {
                    return acOptions.value.jurusan; // Fallback jika rombel belum ter-set
                }
                return acOptions.value.jurusan.filter(j => 
                    allowedJurusanIds.includes(String(j.id))
                );
            });

            // Filter Rombel Kelas secara reaktif berdasarkan jenjang & jurusan yang dipilih
            const filteredKelas = computed(() => {
                if (!form.value.id_jenjang || !Array.isArray(acOptions.value.kelas)) return acOptions.value.kelas || [];
                return acOptions.value.kelas.filter(k => {
                    if (!k) return false;
                    const matchJenjang = String(k.id_jenjang) === String(form.value.id_jenjang);
                    const matchJurusan = !form.value.id_jurusan || String(k.id_jurusan) === String(form.value.id_jurusan);
                    return matchJenjang && matchJurusan;
                });
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
                if (f.tinggi_badan === '' || f.tinggi_badan < 30 || f.tinggi_badan > 255) return false;
                if (f.berat_badan === '' || f.berat_badan < 5 || f.berat_badan > 255) return false;
                if (f.lingkar_kepala === '' || f.lingkar_kepala < 20 || f.lingkar_kepala > 255) return false;
                if (!f.golongan_darah) return false;
                if (f.anak_ke === '' || f.anak_ke < 1 || f.anak_ke > 255) return false;
                if (f.jumlah_saudara === '' || f.jumlah_saudara < 0 || f.jumlah_saudara > 255) return false;
                if (f.jarak_rumah === '' || f.jarak_rumah < 1 || f.jarak_rumah > 65535) return false;
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
            const loadEditData = async () => {
                try {
                    const targetId = idSiswa.value || (new URLSearchParams(window.location.search)).get('id') || '';
                    if (!targetId) return;
                    const response = await axios.get(`<?= $this->getBaseUrl() ?>/siswa/edit?ajax=1&action=get_siswa_detail&id=${encodeURIComponent(targetId)}`);
                    if (response.data && response.data.success) {
                        const phpData = response.data.data;
                        const kesehatanPhp = response.data.kesehatan;
                        
                        if (phpData && Object.keys(phpData).length > 0) {
                            // 1. Panggil opsi akademik terlebih dahulu secara asinkron agar <option> ter-render di DOM
                            if (phpData.tenant_id) {
                                await fetchAcademicOptions(phpData.tenant_id);
                            }
                            
                            // 2. Petakan data siswa ke model form Vue
                            Object.keys(phpData).forEach(key => {
                                if (key in form.value && key !== 'password' && key !== 'kesehatan') {
                                    let val = phpData[key] !== null ? phpData[key] : '';
                                    if (val === '0000-00-00') val = '';
                                    if (typeof form.value[key] === 'number' && val !== '') {
                                        val = Number(val);
                                    }
                                    form.value[key] = val;
                                }
                            });

                            hydrateKesehatan(kesehatanPhp || phpData.kesehatan);

                            // 3. Mapping spesifik untuk alias input Vue Step 1 - Step 5
                            if (phpData.alamat && !form.value.alamat_kk) {
                                form.value.alamat_kk = phpData.alamat;
                            }
                            if (phpData.alamat_kk) {
                                form.value.alamat_kk = phpData.alamat_kk;
                            }
                            if (phpData.alamat_domisili) {
                                form.value.alamat_domisili = phpData.alamat_domisili;
                            }
                            if (phpData.no_hp && !form.value.no_telepon_siswa) {
                                form.value.no_telepon_siswa = phpData.no_hp;
                            }
                            if (phpData.no_telepon_siswa) {
                                form.value.no_telepon_siswa = phpData.no_telepon_siswa;
                            }
                            if (phpData.no_telepon_orang_tua) {
                                form.value.no_telepon_orang_tua = phpData.no_telepon_orang_tua;
                            }
                            if (phpData.status_siswa) {
                                form.value.status = phpData.status_siswa;
                            }
                            if (phpData.asal_sekolah && !form.value.sekolah_asal) {
                                form.value.sekolah_asal = phpData.asal_sekolah;
                            }
                            if (phpData.sekolah_asal) {
                                form.value.sekolah_asal = phpData.sekolah_asal;
                            }

                            // Normalisasi Boolean Radio/Toggle
                            if (phpData.penerima_kps !== undefined && phpData.penerima_kps !== null) {
                                form.value.penerima_kps = (phpData.penerima_kps === true || phpData.penerima_kps === 'true' || phpData.penerima_kps === 1 || phpData.penerima_kps === '1') ? 1 : 0;
                            }
                            if (phpData.punya_kip !== undefined && phpData.punya_kip !== null) {
                                form.value.punya_kip = (phpData.punya_kip === true || phpData.punya_kip === 'true' || phpData.punya_kip === 1 || phpData.punya_kip === '1') ? 1 : 0;
                            }
                            if (phpData.layak_kip !== undefined && phpData.layak_kip !== null) {
                                form.value.layak_kip = (phpData.layak_kip === true || phpData.layak_kip === 'true' || phpData.layak_kip === 1 || phpData.layak_kip === '1') ? 1 : 0;
                            }
                            if (phpData.paud_formal !== undefined && phpData.paud_formal !== null) {
                                form.value.paud_formal = (phpData.paud_formal === true || phpData.paud_formal === 'true' || phpData.paud_formal === 1 || phpData.paud_formal === '1') ? 1 : 0;
                            }
                            if (phpData.paud_non_formal !== undefined && phpData.paud_non_formal !== null) {
                                form.value.paud_non_formal = (phpData.paud_non_formal === true || phpData.paud_non_formal === 'true' || phpData.paud_non_formal === 1 || phpData.paud_non_formal === '1') ? 1 : 0;
                            }

                            // Parent Tempat Lahir, Pendidikan & Penghasilan
                            if (phpData.id_tempat_lahir_ayah) {
                                form.value.id_tempat_lahir_ayah = Number(phpData.id_tempat_lahir_ayah);
                            }
                            if (phpData.id_tempat_lahir_ibu) {
                                form.value.id_tempat_lahir_ibu = Number(phpData.id_tempat_lahir_ibu);
                            }
                            if (phpData.id_tempat_lahir_wali) {
                                form.value.id_tempat_lahir_wali = Number(phpData.id_tempat_lahir_wali);
                            }
                            if (phpData.pendidikan_ayah) form.value.pendidikan_ayah = phpData.pendidikan_ayah;
                            if (phpData.penghasilan_ayah) form.value.penghasilan_ayah = phpData.penghasilan_ayah;
                            if (phpData.pendidikan_ibu) form.value.pendidikan_ibu = phpData.pendidikan_ibu;
                            if (phpData.penghasilan_ibu) form.value.penghasilan_ibu = phpData.penghasilan_ibu;
                            if (phpData.pendidikan_wali) form.value.pendidikan_wali = phpData.pendidikan_wali;
                            if (phpData.penghasilan_wali) form.value.penghasilan_wali = phpData.penghasilan_wali;

                            // Dokumen & Foto
                            if (phpData.foto_profil || phpData.foto_url) {
                                form.value.foto_profil = phpData.foto_profil || phpData.foto_url;
                            }
                            const docKeysList = ['berkas_kk', 'berkas_akta', 'berkas_ijazah_sd', 'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk', 'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 'berkas_pernyataan_tka'];
                            docKeysList.forEach(dKey => {
                                if (phpData[dKey]) form.value[dKey] = phpData[dKey];
                            });

                            // Mapping ID Akademik Relasional
                            if (phpData.id_kelas) form.value.id_kelas = phpData.id_kelas;
                            if (phpData.id_jurusan) form.value.id_jurusan = phpData.id_jurusan;
                            if (phpData.id_angkatan) form.value.id_angkatan = phpData.id_angkatan;
                            if (phpData.id_tahun_ajaran) form.value.id_tahun_ajaran = phpData.id_tahun_ajaran;
                            if (phpData.id_jenjang) form.value.id_jenjang = phpData.id_jenjang;
                            if (phpData.id_pendidikan) form.value.id_pendidikan = phpData.id_pendidikan;

                            // 4. Resolusi fallback ID akademik dari Rombel/Kelas
                            if (form.value.id_kelas && Array.isArray(acOptions.value.kelas)) {
                                const matchedKelas = acOptions.value.kelas.find(k => String(k.id) === String(form.value.id_kelas) || k.nama_kelas === form.value.id_kelas || k.nama_kelas === phpData.kelas_saat_ini);
                                if (matchedKelas) {
                                    form.value.id_kelas = matchedKelas.id;
                                    if (!form.value.id_jenjang && matchedKelas.id_jenjang) {
                                        form.value.id_jenjang = matchedKelas.id_jenjang;
                                    }
                                    if (!form.value.id_jurusan && matchedKelas.id_jurusan) {
                                        form.value.id_jurusan = matchedKelas.id_jurusan;
                                    }
                                }
                            }

                            if (form.value.id_jurusan && Array.isArray(acOptions.value.jurusan)) {
                                const matchedJur = acOptions.value.jurusan.find(j => String(j.id) === String(form.value.id_jurusan) || j.nama_jurusan === form.value.id_jurusan || j.nama_jurusan === phpData.jurusan);
                                if (matchedJur) form.value.id_jurusan = matchedJur.id;
                            }

                            if (form.value.id_angkatan && Array.isArray(acOptions.value.angkatan)) {
                                const matchedAng = acOptions.value.angkatan.find(a => String(a.id) === String(form.value.id_angkatan) || String(a.tahun_angkatan).includes(String(form.value.id_angkatan)) || String(a.tahun_angkatan) === String(phpData.angkatan));
                                if (matchedAng) form.value.id_angkatan = matchedAng.id;
                            }
                            
                            // Trigger pemuatan chained dropdown alamat secara bertahap
                            if (form.value.id_provinsi) {
                                await fetchKota(form.value.id_provinsi, false);
                            }
                            if (form.value.id_kota) {
                                await fetchKecamatan(form.value.id_kota, false);
                            }
                            if (form.value.id_kecamatan) {
                                await fetchKelurahan(form.value.id_kecamatan, false);
                            }
                            
                            if (kesehatanPhp && Object.keys(kesehatanPhp).length > 0) {
                                if (kesehatanPhp.tinggi_badan) form.value.tinggi_badan = kesehatanPhp.tinggi_badan;
                                if (kesehatanPhp.berat_badan) form.value.berat_badan = kesehatanPhp.berat_badan;
                                if (kesehatanPhp.golongan_darah) form.value.golongan_darah = kesehatanPhp.golongan_darah;
                                if (kesehatanPhp.riwayat_penyakit) form.value.penyakit_yang_diderita = kesehatanPhp.riwayat_penyakit;
                                if (kesehatanPhp.disabilitas) form.value.kelainan_jasmani = kesehatanPhp.disabilitas;
                            }
                        }
                    }
                } catch (err) {
                    console.error("Gagal memuat data edit siswa:", err);
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
                    provinces.value = (res.data && Array.isArray(res.data.data)) ? res.data.data : (Array.isArray(res.data) ? res.data : []);
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
                    cities.value = (res.data && Array.isArray(res.data.data)) ? res.data.data : (Array.isArray(res.data) ? res.data : []);
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
                    acOptions.value = (res.data && res.data.data) ? res.data.data : (res.data || {});
                } catch (err) {
                    console.error("Gagal load opsi akademik", err);
                    showErrorToast("Gagal memuat opsi penempatan akademik.");
                } finally {
                    loadingAcademic.value = false;
                }
            };

            const fetchTenants = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/pengguna/tenants');
                    listTenants.value = (res.data && Array.isArray(res.data.data)) ? res.data.data : (Array.isArray(res.data) ? res.data : []);
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
                    cityFiltered.value = (res.data && Array.isArray(res.data.data)) ? res.data.data : (Array.isArray(res.data) ? res.data : []);
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
                    districts.value = (res.data && Array.isArray(res.data.data)) ? res.data.data : (Array.isArray(res.data) ? res.data : []);
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
                    subdistricts.value = (res.data && Array.isArray(res.data.data)) ? res.data.data : (Array.isArray(res.data) ? res.data : []);
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

            // Client-side smart image compressor (Canvas to WebP/JPEG under 500 KB)
            const compressImageClient = (file, maxSizeBytes = 500 * 1024, maxWidth = 1600) => {
                return new Promise((resolve) => {
                    if (!file || !file.type.startsWith('image/')) {
                        resolve({ file, originalSize: file ? file.size : 0, compressedSize: file ? file.size : 0, wasCompressed: false });
                        return;
                    }

                    const reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = (event) => {
                        const img = new Image();
                        img.src = event.target.result;
                        img.onload = () => {
                            let width = img.width;
                            let height = img.height;

                            if (width > maxWidth) {
                                height = Math.round((height * maxWidth) / width);
                                width = maxWidth;
                            }

                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');

                            let quality = 0.85;
                            const tryCompress = (q, w, h) => {
                                canvas.width = w;
                                canvas.height = h;
                                ctx.fillStyle = '#FFFFFF';
                                ctx.fillRect(0, 0, w, h);
                                ctx.drawImage(img, 0, 0, w, h);

                                canvas.toBlob((blob) => {
                                    if (!blob) {
                                        resolve({ file, originalSize: file.size, compressedSize: file.size, wasCompressed: false });
                                        return;
                                    }
                                    if (blob.size <= maxSizeBytes || (q <= 0.35 && w <= 400)) {
                                        const cleanName = file.name.replace(/\.[^/.]+$/, "") + ".webp";
                                        const convertedFile = new File([blob], cleanName, {
                                            type: 'image/webp',
                                            lastModified: Date.now()
                                        });
                                        resolve({
                                            file: convertedFile,
                                            originalSize: file.size,
                                            compressedSize: blob.size,
                                            wasCompressed: (file.size > maxSizeBytes || blob.size < file.size)
                                        });
                                    } else {
                                        const nextQ = Math.max(0.35, q - 0.15);
                                        const nextW = Math.round(w * 0.85);
                                        const nextH = Math.round(h * 0.85);
                                        tryCompress(nextQ, nextW, nextH);
                                    }
                                }, 'image/webp', q);
                            };

                            tryCompress(quality, width, height);
                        };
                        img.onerror = () => resolve({ file, originalSize: file.size, compressedSize: file.size, wasCompressed: false });
                    };
                    reader.onerror = () => resolve({ file, originalSize: file.size, compressedSize: file.size, wasCompressed: false });
                });
            };

            // File selection preview label and automatic compression handler
            const onFileSelected = async (event, type) => {
                const originalFile = event.target.files[0];
                if (!originalFile) return;

                // Batas maksimal mutlak file mentah sebelum diproses (10 MB)
                if (originalFile.size > 10 * 1024 * 1024) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Berkas Terlalu Besar',
                            text: 'Ukuran file asli melebihi batas 10 MB. Harap pilih berkas yang lebih kecil.',
                            confirmButtonColor: '#2563eb'
                        });
                    } else {
                        alert("Ukuran file asli melebihi batas 10 MB!");
                    }
                    event.target.value = "";
                    filesSelected.value[type] = "";
                    filePreviews.value[type] = "";
                    return;
                }

                // 1. Jika Gambar (JPG/PNG/WebP), lakukan kompresi otomatis langsung di sisi klien
                if (originalFile.type.startsWith('image/')) {
                    const result = await compressImageClient(originalFile, 500 * 1024);
                    const finalFile = result.file;

                    // Pasang kembali file hasil kompresi ke input element menggunakan DataTransfer
                    if (typeof DataTransfer !== 'undefined') {
                        try {
                            const dt = new DataTransfer();
                            dt.items.add(finalFile);
                            event.target.files = dt.files;
                        } catch (e) {
                            console.warn("DataTransfer assignment fallback", e);
                        }
                    }

                    filesSelected.value[type] = finalFile.name;

                    // Generate image preview
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        filePreviews.value[type] = e.target.result;
                    };
                    reader.readAsDataURL(finalFile);

                    // Beri notifikasi toast informatif jika file berhasil dikompresi
                    if (result.originalSize > 500 * 1024) {
                        const origKb = Math.round(result.originalSize / 1024);
                        const compKb = Math.round(result.compressedSize / 1024);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berkas Terkompresi Otomatis',
                                text: `Ukuran berkas berhasil dikompresi dari ${origKb} KB menjadi ${compKb} KB (Maks 500 KB).`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3500,
                                timerProgressBar: true
                            });
                        }
                    }
                } 
                // 2. Jika PDF, validasi dan siapkan untuk optimasi server otomatis
                else if (originalFile.type === 'application/pdf') {
                    filesSelected.value[type] = originalFile.name;
                    filePreviews.value[type] = "";

                    if (originalFile.size > 500 * 1024) {
                        const kb = Math.round(originalFile.size / 1024);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'info',
                                title: 'Optimasi PDF Otomatis',
                                text: `Dokumen PDF (${kb} KB) siap diunggah dan akan dioptimasi secara otomatis oleh sistem saat disimpan.`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3500,
                                timerProgressBar: true
                            });
                        }
                    }
                } else {
                    filesSelected.value[type] = originalFile.name;
                    filePreviews.value[type] = "";
                }
            };

            // Helper to get file URL (handles both legacy and new folder format)
            const getFileUrl = (path, fieldName) => {
                if (!path) return '#';
                
                let baseUrl = window.location.pathname.startsWith('<?= $this->getBaseUrl() ?>') ? '<?= $this->getBaseUrl() ?>' : '';
                
                if (path.indexOf('/') !== -1) {
                    return baseUrl + '/download.php?file=' + encodeURIComponent(path);
                }
                return baseUrl + '/download.php?file=' + encodeURIComponent(path) + 
                       '&tenant=' + encodeURIComponent(form.value.tenant_id || '') + 
                       '&field=' + encodeURIComponent(fieldName);
            };

            const isImageFile = (path) => {
                if (!path) return false;
                const clean = String(path).split('?')[0].toLowerCase();
                return clean.endsWith('.jpg') || clean.endsWith('.jpeg') || clean.endsWith('.png') || clean.endsWith('.webp') || clean.endsWith('.gif') || clean.startsWith('data:image/');
            };

            const isPdfFile = (path) => {
                if (!path) return false;
                const clean = String(path).split('?')[0].toLowerCase();
                return clean.endsWith('.pdf') || clean.includes('.pdf');
            };

            const getDisplayFileName = (path) => {
                if (!path) return '';
                const parts = String(path).split('/');
                return parts[parts.length - 1];
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
                        'anak_ke', 'jumlah_saudara', 'saudara_tiri', 'saudara_angkat', 'penyakit_yang_diderita', 'jarak_rumah',
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
                        'sekolah_tujuan', 'nomor_skp', 'nomor_ijazah_kelulusan', 'nomor_skl', 'keterangan_setelah_lulus',
                        'sekolah_asal_mutasi', 'pindah_dari_tingkat', 'pindah_no_surat', 'tingkat_ditinggalkan', 'diterima_di_tingkat'
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
                    let studentId = '<?= htmlspecialchars($idSiswa) ?>';
                    
                    // --- GATHER FIELDS ---
                    const baseFormData = new FormData();
                    baseFormData.append('id', studentId);
                    if (!isFullSubmit) {
                        baseFormData.append('current_step', currentStep.value);
                    }
                    
                    const maxStepToGather = isFullSubmit ? 5 : currentStep.value;
                    for (let s = 1; s <= maxStepToGather; s++) {
                        const fields = getFieldsForStep(s);
                        fields.forEach(field => {
                            if (form.value[field] !== undefined && form.value[field] !== null) {
                                baseFormData.append(field, form.value[field]);
                            }
                        });
                    }

                    // --- GATHER KESEHATAN DATA (STEP 3) ---
                    if (isFullSubmit || currentStep.value === 3) {
                        if (form.value.kesehatan) {
                            for (let sem in form.value.kesehatan) {
                                for (let key in form.value.kesehatan[sem]) {
                                    if (form.value.kesehatan[sem][key] !== undefined && form.value.kesehatan[sem][key] !== null) {
                                        baseFormData.append(`kesehatan[${sem}][${key}]`, form.value.kesehatan[sem][key]);
                                    }
                                }
                            }
                        }
                    }

                    // --- GATHER FILES ---
                    let filesToUpload = [];
                    let totalSize = 0;
                    if (currentStep.value === 5 || isFullSubmit) {
                        const fileInputs = [
                            'foto_profil', 'berkas_kk', 'berkas_akta', 'berkas_ijazah_sd', 
                            'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk', 
                            'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 'berkas_pernyataan_tka'
                        ];
                        
                        fileInputs.forEach(key => {
                            const inputElement = document.querySelector(`input[name="${key}"]`);
                            if (inputElement && inputElement.files && inputElement.files[0]) {
                                filesToUpload.push({ key: key, file: inputElement.files[0] });
                                totalSize += inputElement.files[0].size;
                            }
                        });
                    }

                    // Validation if sending multiple large files (though sequential prevents 522, individual file still can't exceed PHP limit)
                    if (filesToUpload.length > 0) {
                        let singleOversize = filesToUpload.find(f => f.file.size > 8 * 1024 * 1024);
                        if (singleOversize) {
                            throw new Error("Ukuran satu dokumen melebihi 8MB. Silakan kompres file tersebut.");
                        }
                    }

                    let finalResponse = null;
                    let fileUpdates = {};

                    // OPTION 1: No files, or just 1 file -> Send all at once (Classic)
                    if (filesToUpload.length <= 1) {
                        if (filesToUpload.length === 1) {
                            baseFormData.append(filesToUpload[0].key, filesToUpload[0].file);
                        }
                        
                        finalResponse = await axios.post(isEdit.value ? '<?= $this->getBaseUrl() ?>/siswa/update' : '<?= $this->getBaseUrl() ?>/siswa/simpan', baseFormData, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        
                        if (finalResponse.data && finalResponse.data.files) {
                            Object.assign(fileUpdates, finalResponse.data.files);
                        }
                    } 
                    // OPTION 2: Multiple files -> Send sequentially to prevent 522 Connection Timed Out
                    else {
                        // 1. Send Base Data first
                        finalResponse = await axios.post(isEdit.value ? '<?= $this->getBaseUrl() ?>/siswa/update' : '<?= $this->getBaseUrl() ?>/siswa/simpan', baseFormData, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });

                        if (finalResponse.data && finalResponse.data.success) {
                            if (!isEdit.value && finalResponse.data.id) {
                                studentId = finalResponse.data.id;
                            }
                            // 2. Loop and send each file one by one
                            for (let i = 0; i < filesToUpload.length; i++) {
                                let fData = new FormData();
                                
                                // Copy base fields so backend validation passes
                                for (let [k, v] of baseFormData.entries()) {
                                    if (k !== 'id' && k !== 'current_step') {
                                        fData.append(k, v);
                                    }
                                }
                                
                                fData.append('id', studentId);
                                if (!isFullSubmit) {
                                    fData.append('current_step', currentStep.value);
                                }
                                fData.append(filesToUpload[i].key, filesToUpload[i].file);

                                try {
                                    let fResponse = await axios.post('<?= $this->getBaseUrl() ?>/siswa/update', fData, {
                                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                    });
                                    
                                    if (fResponse.data && fResponse.data.success) {
                                        if (fResponse.data.files) {
                                            Object.assign(fileUpdates, fResponse.data.files);
                                        }
                                    } else {
                                        console.error("Gagal mengunggah file " + filesToUpload[i].key, fResponse.data);
                                    }
                                } catch (fErr) {
                                    console.error("Gagal upload file " + filesToUpload[i].key, fErr);
                                    const fStatus = fErr.response ? fErr.response.status : 0;
                                    if (fStatus === 404 || fStatus === 413 || fStatus === 500 || fStatus === 502 || fStatus === 522) {
                                        throw fErr; // rethrow to be caught by main catch block
                                    }
                                }
                                
                                // Delay 500ms between uploads to prevent Cloudflare rate limits / Network Error
                                await new Promise(r => setTimeout(r, 500));
                            }
                        }
                    }

                    // --- PROCESS RESPONSE ---
                    if (finalResponse.data && finalResponse.data.success) {
                        if (isFullSubmit) {
                            localStorage.removeItem('siswa_form_draft');
                            Swal.fire({
                                icon: 'success',
                                title: isEdit.value ? 'Pembaruan Berhasil' : 'Pendaftaran Berhasil',
                                text: finalResponse.data.message || 'Data siswa berhasil disimpan secara penuh!',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                if (!isEdit.value) {
                                    window.location.href = '<?= $this->getBaseUrl() ?>/pengguna';
                                } else {
                                    // Tetap di halaman dan step yang sama (jangan reload ke step 1)
                                    Object.keys(fileUpdates).forEach(key => {
                                        if (fileUpdates[key]) form.value[key] = fileUpdates[key];
                                    });
                                    if (currentStep.value === 5) {
                                        Object.keys(filesSelected.value).forEach(key => {
                                            filesSelected.value[key] = '';
                                        });
                                    }
                                }
                            });
                        } else {
                            if (isEdit.value) {
                                localStorage.removeItem('siswa_form_draft');
                            }
                            // Update file names in form state
                            Object.keys(fileUpdates).forEach(key => {
                                if (fileUpdates[key]) form.value[key] = fileUpdates[key];
                            });

                            // Clear selected files state
                            if (currentStep.value === 5) {
                                Object.keys(filesSelected.value).forEach(key => {
                                    filesSelected.value[key] = '';
                                });
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Simpan Step Berhasil',
                                text: finalResponse.data.message || `Data Step ${currentStep.value} berhasil disimpan ke database!`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                        }
                    } else if (finalResponse.data && finalResponse.data.errors) {
                        errorsList.value = Object.values(finalResponse.data.errors);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validasi Gagal',
                            text: 'Silakan periksa kembali isian form Anda (scroll ke atas untuk detail).',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        throw new Error(finalResponse.data?.error || 'Terjadi kesalahan sistem.');
                    }
                } catch (err) {
                    const status = err.response ? err.response.status : 0;
                    let errMsg = (err.response && err.response.data && (err.response.data.error || err.response.data.message)) || err.message || 'Gagal menyimpan perubahan.';
                    
                    // Khusus jika terjadi kendala 404, 413, 500, atau koneksi timeout saat unggah banyak berkas di HP / jaringan lambat
                    if (status === 404) {
                        errMsg = "Terjadi kendala endpoint atau koneksi (Error 404). Silakan upload 1 file terlebih dahulu lalu klik Simpan, dan lakukan berulang satu per satu hingga semua file terunggah.";
                    } else if (status === 413) {
                        errMsg = "Total ukuran berkas terlalu besar untuk dikirim sekaligus (Error 413). Silakan upload 1 file terlebih dahulu lalu klik Simpan, dan ulangi satu per satu.";
                    } else if (status === 522 || status === 524 || status === 504) {
                        errMsg = "Koneksi terputus (Timeout) saat memproses dokumen. Silakan upload 1 file terlebih dahulu lalu klik Simpan, dan ulangi proses tersebut satu per satu.";
                    } else if (status === 500 || status === 502) {
                        errMsg = "Server sedang sibuk memproses dokumen (Error " + status + "). Silakan upload 1 file terlebih dahulu lalu klik Simpan, dan ulangi satu per satu.";
                    } else if (err.message && err.message.toLowerCase().includes('network error')) {
                        errMsg = "Koneksi internet tidak stabil atau terputus. Silakan upload 1 file terlebih dahulu lalu klik Simpan, dan lakukan berulang hingga semua berkas terunggah.";
                    }

                    Swal.fire({
                        icon: (status === 404 || status === 413 || status === 522 || status === 524 || status === 500 || (err.message && err.message.toLowerCase().includes('network error'))) ? 'warning' : 'error',
                        title: (status === 404 || status === 413 || status === 522 || status === 524 || status === 500) ? 'Tips Pengunggahan Berkas' : 'Penyimpanan Gagal',
                        text: errMsg,
                        confirmButtonText: 'Mengerti, Saya Coba 1 File',
                        confirmButtonColor: '#2563eb'
                    });
                } finally {
                    loadingSaveStep.value = false;
                }
            };

            const submitFullForm = () => {
                saveCurrentStep(true);
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
            const loadDraftData = async () => {
                try {
                    const response = await axios.get(`<?= $this->getBaseUrl() ?>/siswa/tambah?ajax=1&action=get_siswa_draft`);
                    if (response.data && response.data.success) {
                        const phpDraft = response.data.draft || response.data.old;
                        const phpErrors = response.data.errors;
                        
                        if (phpErrors && typeof phpErrors === 'object') {
                            Object.values(phpErrors).forEach(err => {
                                errorsList.value.push(err);
                            });
                        }
                        
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
                    }
                } catch (err) {
                    console.error("Gagal memuat draft:", err);
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

            // loadDraftData sudah dideklarasikan di atas (tidak perlu duplikat)

            // --- INITIALIZATION ---
            onMounted(async () => {
                const storedStep = sessionStorage.getItem('siswa_current_step');
                if (storedStep) {
                    currentStep.value = parseInt(storedStep);
                    sessionStorage.removeItem('siswa_current_step');
                }
                
                await fetchProvinces();
                await fetchAllCities();
                if (userRole.value === 'super_admin') {
                    await fetchTenants();
                } else {
                    await fetchAcademicOptions();
                }
                
                if (isEdit.value) {
                    await loadEditData();
                } else {
                    await loadDraftData();
                }
            });

            // Document Viewer State
            const viewerModalTitle = ref('');
            const viewerModalUrl = ref('');
            const isViewerFilePdf = computed(() => {
                const url = viewerModalUrl.value || '';
                return isPdfFile(url) || url.toLowerCase().includes('.pdf') || url.toLowerCase().includes('application/pdf');
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
                isImageFile,
                isPdfFile,
                getDisplayFileName,
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
