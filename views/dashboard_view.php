<?php
/**
 * View: Dashboard (Child View)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */
$user_roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
$isAdminOrSuper = in_array('super_admin', $user_roles, true) || in_array('operator_sekolah', $user_roles, true);
?>
<!-- Area Konten Utama Terbungkus Vue.js App -->
<div id="dashboardApp">

    <!-- Welcome Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-3 p-md-4 rounded-4 shadow-sm border-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff;">
                <div>
                    <h4 class="fw-bold mb-1 fs-5 fs-md-4">
                        <span v-if="isLoadingStats" class="placeholder-glow"><span class="placeholder col-6 bg-secondary"></span></span>
                        <span v-else>Selamat Datang Kembali, {{ stats.user_nama }}! 👋</span>
                    </h4>
                    <p class="text-slate-300 mb-0 opacity-75 fs-7">
                        <span v-if="isLoadingStats" class="placeholder-glow"><span class="placeholder col-4 bg-secondary"></span></span>
                        <span v-else>Mengakses Sekolah: <strong>{{ stats.nama_sekolah }}</strong></span>
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <span class="badge bg-primary px-3 py-2 fs-7 rounded-3" v-if="!isLoadingStats">
                        Domain: {{ stats.subdomain }}.sinta-saas.id
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Tenant Isolation Alert Info -->
    <div class="alert alert-primary border-0 rounded-4 p-3 mb-4 shadow-sm d-flex align-items-center gap-3" role="alert" v-if="userRole !== 'siswa'">
        <i class="bi bi-shield-fill-check text-primary fs-3"></i>
        <div>
            <strong class="text-primary-emphasis">Akses Multi-Tenant Aman:</strong> Sistem mendeteksi ID tenant Anda secara otomatis dari session. Seluruh data sekolah di luar wewenang Anda terisolasi dan tidak dapat diakses (secure by design).
        </div>
    </div>

    <!-- 4 Metrics Cards Row -->
    <div class="row g-3 g-md-4 mb-4" v-if="userRole !== 'siswa'">
        
        <!-- Card 1: Total Sekolah -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 p-md-4 border-start border-primary border-4" style="transition: transform 0.2s ease;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fw-semibold mb-1 fs-8 text-uppercase" style="font-size: 0.75rem;">Total Sekolah</div>
                        <h3 class="fw-bold text-dark mb-0">
                            <span v-if="isLoadingStats" class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            <span v-else>{{ stats.total_sekolah }}</span>
                        </h3>
                    </div>
                    <div class="bg-primary-subtle rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem; color: #084298;">
                        <i class="bi bi-buildings"></i>
                    </div>
                </div>
            </div>
        </div>
 
        <!-- Card 2: Paket Aktif -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 p-md-4 border-start border-info border-4" style="transition: transform 0.2s ease;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fw-semibold mb-1 fs-8 text-uppercase" style="font-size: 0.75rem;">Paket Aktif</div>
                        <h5 class="fw-bold text-info mb-0">
                            <span v-if="isLoadingStats" class="spinner-border spinner-border-sm text-info" role="status"></span>
                            <span v-else>{{ stats.paket_aktif }}</span>
                        </h5>
                    </div>
                    <div class="bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                        <i class="bi bi-gem"></i>
                    </div>
                </div>
            </div>
        </div>
 
        <!-- Card 3: Total Siswa -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 p-md-4 border-start border-success border-4" style="transition: transform 0.2s ease;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fw-semibold mb-1 fs-8 text-uppercase" style="font-size: 0.75rem;">Total Siswa</div>
                        <h3 class="fw-bold text-success mb-0">
                            <span v-if="isLoadingStats" class="spinner-border spinner-border-sm text-success" role="status"></span>
                            <span v-else>{{ stats.total_siswa }}</span>
                        </h3>
                    </div>
                    <div class="bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
 
        <!-- Card 4: Status Sinkronisasi -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 p-md-4 border-start border-warning border-4" style="transition: transform 0.2s ease;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fw-semibold mb-1 fs-8 text-uppercase" style="font-size: 0.75rem;">Sinkronisasi</div>
                        <h5 class="fw-bold text-warning mb-0 d-flex align-items-center gap-1">
                            <span class="spinner-grow spinner-grow-sm text-warning" role="status" v-if="isLoadingStats"></span>
                            <span v-else>{{ stats.status_sinkronisasi }}</span>
                        </h5>
                    </div>
                    <div class="bg-warning-subtle text-warning rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                    </div>
                </div>
            </div>
        </div>
 
    </div>

    <!-- Papan Pengumuman (Timeline UI) -->
    <div class="row mb-5" v-if="stats.pengumuman_list && stats.pengumuman_list.length > 0">
        <div class="col-12">
            <!-- Header section matching reference image -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <h4 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-family: 'Inter', sans-serif;">
                    <i class="bi bi-megaphone-fill text-dark me-2 fs-3"></i> Pengumuman
                </h4>
                <button class="btn btn-info btn-sm text-white fw-bold shadow-sm d-flex align-items-center px-3 rounded-2" @click="refreshStats">
                    <i class="bi bi-arrow-repeat me-1 fs-6"></i> Refresh
                </button>
            </div>
            
            <!-- Timeline Loader Spinner -->
            <div v-if="isLoadingStats" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2 fs-8">Memuat pengumuman terbaru...</p>
            </div>

            <div v-else class="timeline-container position-relative ps-2">
                <div class="d-flex position-relative mb-4" v-for="(pengumuman, idx) in stats.pengumuman_list" :key="pengumuman.id">
                    <!-- Vertical Line (hides on last item if needed, but we keep it simple here) -->
                    <div class="position-absolute h-100" style="left: 15px; top: 32px; width: 2px; background-color: #dee2e6; z-index: 1;"></div>
                    
                    <!-- Icon -->
                    <div class="flex-shrink-0 z-2 position-relative mt-1" style="width: 32px;">
                        <div class="bg-primary bg-gradient rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm border border-2 border-white" style="width: 32px; height: 32px;">
                            <i class="bi bi-envelope-fill fs-7"></i>
                        </div>
                    </div>
                    
                    <!-- Content Card -->
                    <div class="flex-grow-1 ms-2 ms-sm-3">
                        <div class="card border border-light-subtle shadow-sm rounded-3">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start mb-2 gap-1">
                                    <h5 class="fw-bold mb-0 text-uppercase" style="color: #0d6efd; font-family: 'Inter', sans-serif; font-size: 0.85rem; line-height: 1.4;">
                                        {{ pengumuman.judul }}
                                    </h5>
                                    <div class="text-muted text-end align-self-end align-self-sm-start" style="font-size: 0.75rem;">
                                        <i class="bi bi-calendar-event me-1"></i> {{ formatDateTime(pengumuman.created_at) }}
                                    </div>
                                </div>
                                
                                <div class="mb-2 d-flex flex-wrap gap-1 align-items-center text-muted" style="font-size: 0.7rem;" v-if="userRole === 'super_admin'">
                                    <span><i class="bi bi-person-circle me-1"></i>{{ pengumuman.nama_pembuat }}</span>
                                    <span>•</span>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill border px-2 py-1"><i class="bi bi-building me-1"></i>{{ pengumuman.nama_sekolah || 'Global' }}</span>
                                    <span>•</span>
                                    <span class="badge text-white rounded-pill px-2 py-1" :class="pengumuman.visibilitas === 'super_admin' ? 'bg-danger' : 'bg-primary'"><i class="bi bi-globe2 me-1"></i> {{ pengumuman.visibilitas }}</span>
                                </div>
                                
                                <div class="text-dark mt-2 pengumuman-content text-break" style="font-size: 0.8rem; line-height: 1.5;" v-html="pengumuman.isi_pengumuman">
                                </div>
                                
                                <div class="mt-3 pt-2 border-top" v-if="pengumuman.lampiran_file">
                                    <div class="mb-2 rounded-3 overflow-hidden shadow-sm" style="max-height: 150px; cursor: pointer; max-width: 250px;" v-if="isImageFile(pengumuman.lampiran_file)" @click="window.open('/SINTA-SaaS/storage/app/public/' + pengumuman.lampiran_file, '_blank')">
                                        <img :src="'/SINTA-SaaS/storage/app/public/' + pengumuman.lampiran_file" class="w-100 h-100 object-fit-cover hover-zoom" alt="Lampiran Pengumuman">
                                    </div>
                                    <a :href="'/SINTA-SaaS/storage/app/public/' + pengumuman.lampiran_file" target="_blank" class="btn btn-primary fw-semibold d-flex d-md-inline-flex justify-content-center align-items-center rounded-3 px-3 py-1 shadow-sm w-100 w-md-auto" style="font-size: 0.8rem; transition: all 0.2s ease;">
                                        <i class="bi me-2" :class="isImageFile(pengumuman.lampiran_file) ? 'bi-image' : 'bi-cloud-download-fill'"></i> 
                                        {{ isImageFile(pengumuman.lampiran_file) ? 'Lihat Gambar' : 'Unduh Lampiran' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-2 mb-4 text-center" v-if="stats.total_pengumuman > stats.pengumuman_list.length">
            <a href="/SINTA-SaaS/pengumuman/arsip" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold shadow-sm hover-shadow" style="transition: all 0.2s ease;">
                Lihat Semua Pengumuman ({{ stats.total_pengumuman }}) <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    
    <style>
        .hover-zoom:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }
        .hover-shadow:hover {
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
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
    </style>



    <?php if ($isAdminOrSuper): ?>
    <!-- Tabs Navigation Bar (Vue.js Controlled with Mobile Horizontal Scroll) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-nowrap overflow-x-auto gap-2 p-1.5 bg-white rounded-3 border scrollable-tabs" style="-webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none;">
                <button class="btn btn-sm px-3 py-2 rounded-2 fw-semibold text-nowrap" :class="activeTab === 'profil' ? 'btn-primary shadow-sm' : 'btn-light text-muted'" @click="activeTab = 'profil'">
                    <i class="bi bi-info-circle-fill me-1"></i> Profil Sekolah
                </button>
                <button class="btn btn-sm px-3 py-2 rounded-2 fw-semibold text-nowrap" :class="activeTab === 'sarpras' ? 'btn-primary shadow-sm' : 'btn-light text-muted'" @click="activeTab = 'sarpras'">
                    <i class="bi bi-building me-1"></i> Data Sarpras
                </button>
                <button class="btn btn-sm px-3 py-2 rounded-2 fw-semibold text-nowrap" :class="activeTab === 'siswa' ? 'btn-primary shadow-sm' : 'btn-light text-muted'" @click="activeTab = 'siswa'">
                    <i class="bi bi-people me-1"></i> Data Siswa
                </button>
                <button class="btn btn-sm px-3 py-2 rounded-2 fw-semibold text-nowrap" :class="activeTab === 'gtk' ? 'btn-primary shadow-sm' : 'btn-light text-muted'" @click="activeTab = 'gtk'">
                    <i class="bi bi-person-badge me-1"></i> Data GTK
                </button>
                <button class="btn btn-sm px-3 py-2 rounded-2 fw-semibold text-nowrap" :class="activeTab === 'perubahan' ? 'btn-primary shadow-sm' : 'btn-light text-muted'" @click="activeTab = 'perubahan'">
                    <i class="bi bi-clock-history me-1"></i> Log Perubahan Siswa
                </button>
            </div>
        </div>
    </div>
    
    <style>
        .scrollable-tabs::-webkit-scrollbar {
            display: none;
        }
        
        /* Styled, visible horizontal scrollbar for tables on the dashboard */
        #dashboardApp .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        #dashboardApp .table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        #dashboardApp .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
            border: 2px solid #f1f5f9;
        }
        #dashboardApp .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    <?php endif; ?>

    <!-- Tabs Content Panels -->
    <div class="row">
        <?php if ($isAdminOrSuper): ?>
        <div class="col-12">
            
            <!-- TAB: PROFIL SEKOLAH -->
            <div v-if="activeTab === 'profil'" class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                    <h5 class="fw-bold m-0 text-dark"><i class="bi bi-info-circle-fill text-primary me-2"></i>Profil Instansi Sekolah</h5>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button v-if="schoolInfo.npsn !== 'PLATFORM' && (userRole === 'operator_sekolah' || userRole === 'super_admin')" class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm fs-8 fs-sm-7" @click="openEditModal">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profil
                        </button>
                        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill fs-8">Status: {{ schoolInfo.status }}</span>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="info-label text-muted fs-8 fw-semibold mb-1">Nama Sekolah</div>
                            <div class="info-value fw-bold text-dark">{{ schoolInfo.nama_sekolah }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="info-label text-muted fs-8 fw-semibold mb-1">NPSN Nasional</div>
                            <div class="info-value fw-bold text-dark">{{ schoolInfo.npsn }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="info-label text-muted fs-8 fw-semibold mb-1">Subdomain</div>
                            <div class="info-value fw-bold text-dark">{{ schoolInfo.subdomain }}.sinta-saas.id</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="info-label text-muted fs-8 fw-semibold mb-1">Paket Langganan SaaS</div>
                            <div class="info-value fw-bold text-dark">{{ schoolInfo.paket_aktif }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="info-label text-muted fs-8 fw-semibold mb-1">Kondisi Sinkronisasi</div>
                            <div class="info-value fw-bold text-dark">{{ schoolInfo.status_sinkronisasi }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="info-label text-muted fs-8 fw-semibold mb-1">Terdaftar Sejak</div>
                            <div class="info-value fw-bold text-dark">{{ schoolInfo.created_at }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($stats['user_role'] !== 'siswa'): ?>
            <!-- TAB: DATA SARPRAS -->
            <div v-if="activeTab === 'sarpras'" class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
                <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-building text-primary me-2"></i>Data Sarana & Prasarana</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Prasarana</th>
                                <th>Jumlah Unit</th>
                                <th>Status Kelayakan</th>
                                <th>Kondisi Fisik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in sarpras" :key="idx">
                                <td class="fw-semibold text-dark">{{ item.nama }}</td>
                                <td><span class="badge bg-secondary px-3 py-2 fs-7">{{ item.jumlah }} Unit</span></td>
                                <td>
                                    <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill" v-if="item.jumlah > 0">
                                        Layak Pakai
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; min-width: 100px;">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 :aria-label="'Kondisi Fisik ' + item.nama"
                                                 :aria-valuenow="item.kondisiWidth"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"
                                                 :style="{width: item.kondisiWidth + '%'}">
                                            </div>
                                        </div>
                                        <span class="text-muted fs-8">{{ item.kondisiText }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: DATA SISWA -->
            <div v-if="activeTab === 'siswa'" class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                    <h5 class="fw-bold m-0 text-dark"><i class="bi bi-people text-primary me-2"></i>Daftar Siswa Terdaftar (Max 20 Baris)</h5>
                    <?php if ($stats['user_role'] !== 'siswa'): ?>
                        <a href="/SINTA-SaaS/pengguna" class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm fs-8 fs-sm-7">
                            <i class="bi bi-pencil-square me-1"></i>Kelola Siswa Lengkap
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Search Input inside tab -->
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Cari siswa dalam list ini..." v-model="tabSearchQuery" style="max-width: 300px;">
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>NIS / NISN</th>
                                <th class="d-none d-md-table-cell">Gender</th>
                                <th class="d-none d-lg-table-cell">Tempat/Tgl Lahir</th>
                                <th class="d-none d-md-table-cell">Alamat</th>
                                <?php if ($stats['user_role'] === 'super_admin'): ?>
                                    <th>Asal Sekolah</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="siswa in filteredSiswa" :key="siswa.nisn">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                            {{ siswa.nama_lengkap.charAt(0) }}
                                        </div>
                                        <div class="fw-semibold text-dark">{{ siswa.nama_lengkap }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fs-7 text-dark fw-medium">NIS: {{ siswa.nis || '-' }}</div>
                                    <div class="fs-8 text-muted">NISN: {{ siswa.nisn || '-' }}</div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="badge" :class="siswa.jenis_kelamin === 'L' ? 'bg-primary-subtle' : 'bg-danger-subtle text-danger'" :style="siswa.jenis_kelamin === 'L' ? 'color: #084298;' : ''">
                                        {{ siswa.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell fs-7">
                                    {{ siswa.tempat_lahir || '-' }}, {{ siswa.tanggal_lahir || '-' }}
                                </td>
                                <td class="d-none d-md-table-cell fs-7 text-truncate" style="max-width: 200px;">
                                    {{ siswa.alamat || '-' }}
                                </td>
                                <?php if ($stats['user_role'] === 'super_admin'): ?>
                                    <td><span class="badge bg-secondary">{{ siswa.nama_sekolah }}</span></td>
                                <?php endif; ?>
                            </tr>
                            <tr v-if="filteredSiswa.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i> Tidak ada data siswa yang cocok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: DATA GTK -->
            <div v-if="activeTab === 'gtk'" class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
                <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-person-badge text-primary me-2"></i>Daftar Guru & Tenaga Kependidikan</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Peran</th>
                                <th>Status</th>
                                <?php if ($stats['user_role'] === 'super_admin'): ?>
                                    <th>Asal Sekolah</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="gtk in gtkList" :key="gtk.email">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-indigo-subtle text-indigo rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem; background-color: #e0e7ff; color: #4f46e5;">
                                            {{ gtk.nama_lengkap.charAt(0) }}
                                        </div>
                                        <div class="fw-semibold text-dark">{{ gtk.nama_lengkap }}</div>
                                    </div>
                                </td>
                                <td>{{ gtk.email }}</td>
                                <td><span class="badge bg-light text-dark text-capitalize">{{ gtk.nama_role }}</span></td>
                                <td>
                                    <span class="badge" :class="gtk.status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                                        {{ gtk.status }}
                                    </span>
                                </td>
                                <?php if ($stats['user_role'] === 'super_admin'): ?>
                                    <td><span class="badge bg-secondary">{{ gtk.nama_sekolah }}</span></td>
                                <?php endif; ?>
                            </tr>
                            <tr v-if="gtkList.length === 0">
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada data GTK (Guru) terdaftar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: LOG PERUBAHAN SISWA -->
            <div v-if="activeTab === 'perubahan'" class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
                <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Log Perubahan Siswa</h5>
                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="fs-8 text-secondary">
                                <th style="width: 140px;">Waktu</th>
                                <th style="width: 180px;">Sekolah</th>
                                <th style="width: 180px;">Nama Siswa</th>
                                <th style="width: 150px;">Aksi / Tabel</th>
                                <th style="min-width: 250px;">Perubahan Nilai Pokok</th>
                                <th scope="col" class="text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in paginatedChanges" :key="log.id" class="fs-8">
                                <td class="text-muted font-monospace">{{ formatDateTime(log.waktu) }}</td>
                                <td><span class="badge bg-light text-dark border">{{ log.sekolah }}</span></td>
                                <td class="fw-semibold text-dark">{{ log.nama_siswa }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <span :class="{
                                            'badge bg-success bg-opacity-10 text-success border border-success border-opacity-20': log.action === 'INSERT',
                                            'badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-20': log.action === 'UPDATE',
                                            'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20': log.action === 'DELETE'
                                        }" class="px-2 py-0.5 rounded fs-9 fw-semibold tracking-wider font-monospace">
                                            {{ log.action }}
                                        </span>
                                        <code class="text-primary font-monospace fs-8">{{ log.table_name }}</code>
                                    </div>
                                </td>
                                <td>
                                    <div v-html="getChanges(log)"></div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-3 px-3 py-1 fs-8 shadow-xs" @click="showDetail(log)">
                                        <i class="bi bi-eye-fill me-1"></i> Detail
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="recentChanges.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada log perubahan siswa.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Log Perubahan Siswa -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-3 fs-8">
                    <div class="text-muted">
                        Menampilkan {{ recentChanges.length > 0 ? (logCurrentPage - 1) * logPerPage + 1 : 0 }} - {{ Math.min(logCurrentPage * logPerPage, recentChanges.length) }} dari {{ recentChanges.length }} log perubahan siswa
                    </div>
                    <nav v-if="logTotalPages > 1" aria-label="Navigasi log perubahan">
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item" :class="{ disabled: logCurrentPage === 1 }">
                                <button class="page-link" @click="logCurrentPage--" type="button" style="cursor: pointer;">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                            </li>
                            <li class="page-item" v-for="page in logTotalPages" :key="page" :class="{ active: logCurrentPage === page }">
                                <button class="page-link" @click="logCurrentPage = page" type="button" style="cursor: pointer;">{{ page }}</button>
                            </li>
                            <li class="page-item" :class="{ disabled: logCurrentPage === logTotalPages }">
                                <button class="page-link" @click="logCurrentPage++" type="button" style="cursor: pointer;">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($isAdminOrSuper): ?>
        <!-- Modal Edit Profil Sekolah -->
        <div class="modal fade" id="modalEditProfil" tabindex="-1" aria-labelledby="modalEditProfilLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="modalEditProfilLabel">
                            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Profil Sekolah
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="saveProfil">
                        <div class="modal-body py-4">
                            <!-- Alert Peringatan Global -->
                            <div v-if="serverError" class="alert alert-danger border-0 rounded-3 mb-3 d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                                <div>{{ serverError }}</div>
                            </div>

                            <!-- Input Nama Sekolah -->
                            <div class="mb-3">
                                <label for="editNamaSekolah" class="form-label fw-semibold text-muted fs-8 mb-1">Nama Sekolah <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3" id="editNamaSekolah" v-model="editForm.nama_sekolah" required>
                                <div v-if="validationErrors.nama_sekolah" class="text-danger fs-8 mt-1">
                                    {{ validationErrors.nama_sekolah[0] }}
                                </div>
                            </div>

                            <!-- Input NPSN -->
                            <div class="mb-3">
                                <label for="editNpsn" class="form-label fw-semibold text-muted fs-8 mb-1">Nomor NPSN (8 Digit) <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <input 
                                        type="text" 
                                        class="form-control rounded-3 pe-5" 
                                        id="editNpsn" 
                                        v-model="editForm.npsn" 
                                        @input="handleNpsnInput"
                                        required 
                                        maxlength="8" 
                                        pattern="[0-9]*"
                                        inputmode="numeric"
                                        placeholder="Masukkan 8 digit angka"
                                    >
                                    <span class="position-absolute end-0 top-50 translate-middle-y me-3" v-if="isCheckingNpsn">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                    </span>
                                    <span class="position-absolute end-0 top-50 translate-middle-y me-3" v-else-if="npsnAvailable && editForm.npsn.length === 8">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    </span>
                                    <span class="position-absolute end-0 top-50 translate-middle-y me-3" v-else-if="npsnError && editForm.npsn.length === 8">
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    </span>
                                </div>
                                
                                <!-- Reactive messages -->
                                <div class="mt-1">
                                    <div v-if="npsnAvailable && editForm.npsn.length === 8" class="text-success fs-8 fw-semibold">
                                        <i class="bi bi-check2 me-1"></i>NPSN tersedia untuk digunakan.
                                    </div>
                                    <div v-if="npsnError" class="text-danger fs-8">
                                        <i class="bi bi-info-circle me-1"></i>{{ npsnError }}
                                    </div>
                                    <div v-if="validationErrors.npsn" class="text-danger fs-8">
                                        {{ validationErrors.npsn[0] }}
                                    </div>
                                </div>
                            </div>

                            <!-- Input Subdomain -->
                            <div class="mb-3">
                                <label for="editSubdomain" class="form-label fw-semibold text-muted fs-8 mb-1">Subdomain Sekolah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input 
                                        type="text" 
                                        class="form-control rounded-start-3" 
                                        id="editSubdomain" 
                                        v-model="editForm.subdomain" 
                                        @input="handleSubdomainInput"
                                        required
                                        placeholder="misal: sman1jkt"
                                    >
                                    <span class="input-group-text rounded-end-3 bg-light text-muted fs-7">.sinta-saas.id</span>
                                </div>
                                <div v-if="validationErrors.subdomain" class="text-danger fs-8 mt-1">
                                    {{ validationErrors.subdomain[0] }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-light rounded-3 fw-semibold px-3 py-2 text-muted" data-bs-dismiss="modal">Batal</button>
                            <button 
                                type="submit" 
                                class="btn btn-primary rounded-3 fw-semibold px-4 py-2"
                                :disabled="isSaving || (editForm.npsn.length !== 8) || npsnError"
                            >
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" v-if="isSaving"></span>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Vue 3 App Logic Mount -->
<script>
{
    window.VueAppRegistry.register('#dashboardApp', {
        data() {
            return {
                activeTab: 'profil',
                tabSearchQuery: '',
                isLoadingStats: true,
                userRole: <?= json_encode($stats['user_role'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                schoolInfo: {},
                stats: {
                    user_nama: '',
                    nama_sekolah: '',
                    subdomain: '',
                    total_sekolah: 0,
                    paket_aktif: '',
                    total_siswa: 0,
                    status_sinkronisasi: '',
                    pengumuman_list: [],
                    total_pengumuman: 0
                },
                siswaList: [],
                gtkList: [],
                recentChanges: [],
                sarpras: [
                    { nama: 'Ruang Kelas Teori', jumlah: 12, kondisiWidth: 90, kondisiText: '90% Baik' },
                    { nama: 'Laboratorium Komputer', jumlah: 2, kondisiWidth: 95, kondisiText: '95% Sangat Baik' },
                    { nama: 'Laboratorium IPA', jumlah: 1, kondisiWidth: 80, kondisiText: '80% Baik' },
                    { nama: 'Perpustakaan', jumlah: 1, kondisiWidth: 85, kondisiText: '85% Baik' },
                    { nama: 'Lapangan Serbaguna', jumlah: 1, kondisiWidth: 70, kondisiText: '70% Cukup' }
                ],
                // State Edit Profil Sekolah
                editForm: {
                    id: '',
                    nama_sekolah: '',
                    npsn: '',
                    subdomain: ''
                },
                validationErrors: {},
                serverError: '',
                isCheckingNpsn: false,
                npsnAvailable: false,
                npsnError: '',
                isSaving: false,
                editModalInstance: null,
                // Pagination state for logs
                logCurrentPage: 1,
                logPerPage: 10
            };
        },
        computed: {
            filteredSiswa() {
                if (!this.tabSearchQuery) return this.siswaList;
                const query = this.tabSearchQuery.toLowerCase();
                return this.siswaList.filter(s => {
                    return s.nama_lengkap.toLowerCase().includes(query) ||
                           (s.nis && s.nis.toLowerCase().includes(query)) ||
                           (s.nisn && s.nisn.toLowerCase().includes(query));
                });
            },
            logTotalPages() {
                return Math.ceil(this.recentChanges.length / this.logPerPage);
            },
            paginatedChanges() {
                const start = (this.logCurrentPage - 1) * this.logPerPage;
                const end = start + this.logPerPage;
                return this.recentChanges.slice(start, end);
            }
        },
        methods: {
            openEditModal() {
                this.editForm = {
                    id: this.schoolInfo.id || '',
                    nama_sekolah: this.schoolInfo.nama_sekolah || '',
                    npsn: this.schoolInfo.npsn || '',
                    subdomain: this.schoolInfo.subdomain || ''
                };
                this.validationErrors = {};
                this.serverError = '';
                this.npsnError = '';
                this.npsnAvailable = true; // NPSN awal dianggap tersedia
                this.isCheckingNpsn = false;

                if (!this.editModalInstance) {
                    this.editModalInstance = new bootstrap.Modal(document.getElementById('modalEditProfil'));
                }
                this.editModalInstance.show();
            },
            handleNpsnInput() {
                // Bersihkan karakter non-angka secara instan (HTML5 & Regex Sanitization)
                this.editForm.npsn = this.editForm.npsn.replace(/[^0-9]/g, '');
                
                // Reset validasi error kolom npsn
                if (this.validationErrors.npsn) {
                    this.validationErrors.npsn = null;
                }

                // Jika NPSN tepat 8 digit angka, lakukan pengecekan keunikan ke server secara asinkron (Axios)
                if (this.editForm.npsn.length === 8) {
                    // Jika NPSN tidak berubah dari data awal, anggap tersedia
                    if (this.editForm.npsn === this.schoolInfo.npsn) {
                        this.npsnAvailable = true;
                        this.npsnError = '';
                        return;
                    }

                    this.isCheckingNpsn = true;
                    this.npsnError = '';
                    axios.get('/SINTA-SaaS/api/v1/tenant/check-npsn', {
                        params: {
                            npsn: this.editForm.npsn,
                            exclude_id: this.schoolInfo.id
                        }
                    })
                    .then(response => {
                        this.isCheckingNpsn = false;
                        if (response.data.available) {
                            this.npsnAvailable = true;
                            this.npsnError = '';
                        } else {
                            this.npsnAvailable = false;
                            this.npsnError = response.data.message || 'NPSN sudah terdaftar oleh sekolah lain';
                        }
                    })
                    .catch(error => {
                        this.isCheckingNpsn = false;
                        this.npsnAvailable = false;
                        this.npsnError = 'Gagal memeriksa keunikan NPSN';
                    });
                } else {
                    this.npsnAvailable = false;
                    if (this.editForm.npsn.length > 0) {
                        this.npsnError = 'Nomor NPSN harus berupa angka sepanjang tepat 8 digit';
                    } else {
                        this.npsnError = 'Nomor NPSN wajib diisi';
                    }
                }
            },
            handleSubdomainInput() {
                // Hanya izinkan huruf kecil, angka, dan strip
                this.editForm.subdomain = this.editForm.subdomain.toLowerCase().replace(/[^a-z0-9\-]/g, '');
                if (this.validationErrors.subdomain) {
                    this.validationErrors.subdomain = null;
                }
            },
            formatDateTime(rawDateTime) {
                if (!rawDateTime) return '';
                const d = new Date(rawDateTime.replace(/-/g, '/'));
                if (isNaN(d.getTime())) return rawDateTime;
                return d.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }) + ' • ' + d.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            },
            isDifferent(val1, val2) {
                const str1 = val1 === null || val1 === undefined ? '' : String(val1).trim();
                const str2 = val2 === null || val2 === undefined ? '' : String(val2).trim();
                return str1 !== str2;
            },
            escapeHtml(text) {
                if (text === null || text === undefined) return '';
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
            },
            getChanges(log) {
                if (log.action === 'INSERT') {
                    return '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 px-2 py-1"><i class="bi bi-plus-circle me-1"></i> Data Baru</span>';
                }
                if (log.action === 'DELETE') {
                    return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-2 py-1"><i class="bi bi-dash-circle me-1"></i> Data Dihapus</span>';
                }
                if (log.action === 'UPDATE') {
                    try {
                        const oldData = log.old_data ? JSON.parse(log.old_data) : {};
                        const newData = log.new_data ? JSON.parse(log.new_data) : {};
                        const diff = [];
                        
                        const fieldLabels = {
                            nama_lengkap: 'Nama Lengkap',
                            jenis_kelamin: 'Jenis Kelamin',
                            nik: 'NIK',
                            no_kk: 'No. KK',
                            id_angkatan: 'Angkatan',
                            id_tahun_ajaran: 'Tahun Ajaran',
                            id_jenjang: 'Jenjang',
                            id_jurusan: 'Jurusan',
                            id_kelas: 'Kelas',
                            id_pendidikan: 'Pendidikan',
                            nama_wali: 'Nama Wali',
                            current_step: 'Tahap Registrasi',
                            alamat: 'Alamat',
                            tempat_lahir: 'Tempat Lahir',
                            tanggal_lahir: 'Tanggal Lahir',
                            no_telp: 'No. Telepon',
                            agama: 'Agama',
                            nama_ibu: 'Nama Ibu',
                            nama_ayah: 'Nama Ayah',
                            tenant_id: 'Sekolah',
                            user_id: 'Aktor',
                            id_siswa: 'Nama Siswa',
                            siswa_id: 'Nama Siswa',
                            role_id: 'Role/Peran',
                            diverifikasi_oleh: 'Diverifikasi Oleh',
                            id_guru_bk: 'Guru BK',
                            id_kelas_snapshot: 'Kelas (Snapshot)',
                            id_kelas_asal: 'Kelas Asal',
                            id_kelas_tujuan: 'Kelas Tujuan',
                            id_jurusan_lama: 'Jurusan Lama',
                            id_jurusan_baru: 'Jurusan Baru',
                            id_kelurahan: 'Kelurahan',
                            id_tempat_lahir_ayah: 'Tempat Lahir Ayah',
                            id_tempat_lahir_ibu: 'Tempat Lahir Ibu',
                            id_tempat_lahir_wali: 'Tempat Lahir Wali'
                        };

                        const diffKeys = [];
                        for (const key in newData) {
                            if (this.isDifferent(oldData[key], newData[key])) {
                                const oldVal = oldData[key] !== null && oldData[key] !== undefined ? oldData[key] : '';
                                const newVal = newData[key] !== null && newData[key] !== undefined ? newData[key] : '';
                                
                                const label = fieldLabels[key] || key;
                                const displayOld = oldVal === '' ? 'Kosong' : (oldVal.toString().length > 15 ? oldVal.toString().substring(0, 12) + '...' : oldVal);
                                const displayNew = newVal === '' ? 'Kosong' : (newVal.toString().length > 15 ? newVal.toString().substring(0, 12) + '...' : newVal);
                                
                                diffKeys.push({ label, old: displayOld, new: displayNew });
                            }
                        }
                        
                        if (diffKeys.length === 0) {
                            return '<span class="text-muted fs-8">Tidak ada perubahan nilai pokok</span>';
                        }
                        
                        const maxShow = 2;
                        let html = '<div class="d-flex flex-wrap gap-1">';
                        diffKeys.slice(0, maxShow).forEach(d => {
                            html += `<span class="badge bg-light text-dark border border-secondary border-opacity-20 px-2 py-1 fs-9 font-monospace" style="font-weight: 500;">
                                <span class="text-primary">${this.escapeHtml(d.label)}</span>: 
                                <span class="text-danger-emphasis"><s>${this.escapeHtml(d.old)}</s></span> ➔ 
                                <span class="text-success-emphasis fw-semibold">${this.escapeHtml(d.new)}</span>
                            </span>`;
                        });
                        
                        if (diffKeys.length > maxShow) {
                            html += `<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 px-2 py-1 fs-9">
                                +${diffKeys.length - maxShow} perubahan lainnya
                            </span>`;
                        }
                        html += '</div>';
                        return html;
                    } catch (e) {
                        return '<span class="text-muted fs-8">Perubahan data</span>';
                    }
                }
                return '-';
            },
            showDetail(log) {
                const parseJSON = (str) => {
                    if (!str) return null;
                    try { return JSON.parse(str); } catch (e) { return null; }
                };

                const oldObj = parseJSON(log.old_data);
                const newObj = parseJSON(log.new_data);

                // Build modern diff table
                let changesListHtml = '';
                if (log.action === 'UPDATE' && oldObj && newObj) {
                    changesListHtml += '<div class="card border rounded-3 p-3 mb-3 bg-light">';
                    changesListHtml += '<h6 class="fw-bold text-dark mb-2.5"><i class="bi bi-info-circle me-1 text-primary"></i> Perubahan Nilai Pokok (Diff)</h6>';
                    changesListHtml += '<div class="table-responsive"><table class="table table-sm table-striped table-bordered bg-white mb-0 fs-8 align-middle">';
                    changesListHtml += '<thead class="table-light"><tr><th style="width:200px;">Kolom Data</th><th class="text-danger">Sebelum (Old)</th><th class="text-success">Sesudah (New)</th></tr></thead><tbody>';
                    
                    const fieldLabels = {
                        nama_lengkap: 'Nama Lengkap',
                        jenis_kelamin: 'Jenis Kelamin',
                        nik: 'NIK',
                        no_kk: 'No. KK',
                        id_angkatan: 'Angkatan',
                        id_tahun_ajaran: 'Tahun Ajaran',
                        id_jenjang: 'Jenjang',
                        id_jurusan: 'Jurusan',
                        id_kelas: 'Kelas',
                        id_pendidikan: 'Pendidikan',
                        nama_wali: 'Nama Wali',
                        current_step: 'Tahap Registrasi',
                        alamat: 'Alamat',
                        tempat_lahir: 'Tempat Lahir',
                        tanggal_lahir: 'Tanggal Lahir',
                        no_telp: 'No. Telepon',
                        agama: 'Agama',
                        nama_ibu: 'Nama Ibu',
                        nama_ayah: 'Nama Ayah',
                        tenant_id: 'Sekolah',
                        user_id: 'Aktor',
                        id_siswa: 'Nama Siswa',
                        siswa_id: 'Nama Siswa',
                        role_id: 'Role/Peran',
                        diverifikasi_oleh: 'Diverifikasi Oleh',
                        id_guru_bk: 'Guru BK',
                        id_kelas_snapshot: 'Kelas (Snapshot)',
                        id_kelas_asal: 'Kelas Asal',
                        id_kelas_tujuan: 'Kelas Tujuan',
                        id_jurusan_lama: 'Jurusan Lama',
                        id_jurusan_baru: 'Jurusan Baru',
                        id_kelurahan: 'Kelurahan',
                        id_tempat_lahir_ayah: 'Tempat Lahir Ayah',
                        id_tempat_lahir_ibu: 'Tempat Lahir Ibu',
                        id_tempat_lahir_wali: 'Tempat Lahir Wali'
                    };

                    let hasRealChanges = false;
                    for (const key in newObj) {
                        if (this.isDifferent(oldObj[key], newObj[key])) {
                            const oldVal = oldObj[key] !== null && oldObj[key] !== undefined && oldObj[key] !== '' ? oldObj[key] : 'Kosong';
                            const newVal = newObj[key] !== null && newObj[key] !== undefined && newObj[key] !== '' ? newObj[key] : 'Kosong';
                            changesListHtml += `<tr>
                                <td class="fw-semibold text-dark font-monospace">${this.escapeHtml(fieldLabels[key] || key)} <small class="text-muted d-block">(${this.escapeHtml(key)})</small></td>
                                <td class="text-danger-emphasis">${this.escapeHtml(oldVal)}</td>
                                <td class="text-success-emphasis fw-semibold">${this.escapeHtml(newVal)}</td>
                            </tr>`;
                            hasRealChanges = true;
                        }
                    }
                    if (!hasRealChanges) {
                        changesListHtml += '<tr><td colspan="3" class="text-center text-muted">Tidak ada perubahan nilai pokok terdeteksi.</td></tr>';
                    }
                    changesListHtml += '</tbody></table></div></div>';
                }

                const oldFormatted = oldObj ? JSON.stringify(oldObj, null, 2) : 'Tidak ada data (Entri Baru / INSERT)';
                const newFormatted = newObj ? JSON.stringify(newObj, null, 2) : 'Tidak ada data (Hapus / DELETE)';

                Swal.fire({
                    title: `<div class="fs-6 fw-bold border-bottom pb-2 text-start text-dark">Detail Audit Trail Log</div>`,
                    html: `
                        <div class="text-start fs-8 text-muted">
                            <div class="row g-2 mb-3">
                                <div class="col-md-6"><strong>Pengguna:</strong> <span class="text-dark fw-semibold">${this.escapeHtml(log.actor_name || 'System')}</span></div>
                                <div class="col-md-6"><strong>Peran:</strong> <span class="text-dark text-uppercase font-monospace">${log.user_role}</span></div>
                                <div class="col-md-6"><strong>Tindakan:</strong> <span class="badge ${log.action === 'INSERT' ? 'bg-success' : (log.action === 'UPDATE' ? 'bg-warning text-dark' : 'bg-danger')} font-monospace">${log.action}</span></div>
                                <div class="col-md-6"><strong>Tabel/Entitas:</strong> <span class="text-dark font-monospace">${log.table_name}</span></div>
                                <div class="col-md-6"><strong>IP Address:</strong> <span class="text-dark font-monospace">${log.ip_address}</span></div>
                                <div class="col-md-6"><strong>Waktu:</strong> <span class="text-dark">${this.formatDateTime(log.waktu)}</span></div>
                                <div class="col-md-12" v-if="log.sekolah"><strong>Sekolah:</strong> <span class="text-dark">${this.escapeHtml(log.sekolah)}</span></div>
                            </div>
                            
                            <!-- Modern Diff Table -->
                            ${changesListHtml}

                            <hr class="my-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="fw-bold text-danger mb-2"><i class="bi bi-arrow-left-circle me-1"></i> Data Sebelum (Before) - RAW JSON</div>
                                    <pre class="bg-light p-2 border rounded-3 text-dark font-monospace" style="max-height: 180px; overflow-y: auto; font-size: 0.725rem; line-height: 1.4;">${this.escapeHtml(oldFormatted)}</pre>
                                </div>
                                <div class="col-md-6">
                                    <div class="fw-bold text-success mb-2"><i class="bi bi-arrow-right-circle me-1"></i> Data Sesudah (After) - RAW JSON</div>
                                    <pre class="bg-light p-2 border rounded-3 text-dark font-monospace" style="max-height: 180px; overflow-y: auto; font-size: 0.725rem; line-height: 1.4;">${this.escapeHtml(newFormatted)}</pre>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '850px',
                    showCloseButton: true,
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Tutup'
                });
            },
            saveProfil() {
                if (this.editForm.npsn.length !== 8 || this.npsnError) {
                    return;
                }

                this.isSaving = true;
                this.serverError = '';
                this.validationErrors = {};

                axios.post('/SINTA-SaaS/api/v1/tenant/update', this.editForm)
                .then(response => {
                    this.isSaving = false;
                    if (response.data.success) {
                        // Perbarui data reaktif local schoolInfo
                        this.schoolInfo.nama_sekolah = response.data.data.nama_sekolah;
                        this.schoolInfo.npsn = response.data.data.npsn;
                        this.schoolInfo.subdomain = response.data.data.subdomain;

                        // Perbarui nama sekolah di bagian banner/header visual
                        const bannerText = document.querySelector('.text-slate-300 strong');
                        if (bannerText) {
                            bannerText.textContent = response.data.data.nama_sekolah;
                        }

                        // Sembunyikan modal
                        if (this.editModalInstance) {
                            this.editModalInstance.hide();
                        }

                        // Berikan feedback visual yang premium menggunakan SweetAlert2
                        Swal.fire({
                            icon: 'success',
                            title: response.data.message || 'Profil berhasil diperbarui',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                })
                .catch(error => {
                    this.isSaving = false;
                    if (error.response && error.response.status === 422) {
                        this.validationErrors = error.response.data.errors || {};
                    } else {
                        this.serverError = error.response && error.response.data.error 
                            ? error.response.data.error 
                            : 'Terjadi kesalahan sistem saat memperbarui profil sekolah.';
                    }
                });
            },
            async refreshStats() {
                this.isLoadingStats = true;
                try {
                    const response = await axios.get('/SINTA-SaaS/dashboard?ajax=1&action=get_dashboard_stats');
                    if (response.data && response.data.success) {
                        this.siswaList = response.data.siswaList || [];
                        this.gtkList = response.data.gtkList || [];
                        this.recentChanges = response.data.recentChanges || [];
                        if (response.data.stats) {
                            this.stats = response.data.stats;
                            this.schoolInfo = response.data.stats.school_info || {};
                        }
                    }
                } catch (err) {
                    console.error("Gagal memuat data statistik dashboard:", err);
                } finally {
                    this.isLoadingStats = false;
                }
            },
            isImageFile(filename) {
                if (!filename) return false;
                const ext = filename.split('.').pop().toLowerCase();
                return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
            }
        },
        async mounted() {
            await this.refreshStats();
        }
    });
}
</script>
