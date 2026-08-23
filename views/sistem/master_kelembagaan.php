<!-- Halaman Sentral: Master Data Kelembagaan -->
<div id="masterKelembagaanApp" data-user-role="<?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>" data-tenant-id="<?= htmlspecialchars((string)($tenantId ?? ''), ENT_QUOTES, 'UTF-8') ?>" v-cloak>

    
    <!-- Row Header & Tabs -->
    <div class="row mb-3 mb-md-4 align-items-start">
        <div class="col-12 col-md-7 mb-3 mb-md-0">
            <h3 class="fw-bold text-dark mb-1 fs-4 fs-md-3">
                <i class="bi bi-diagram-3-fill text-primary me-2"></i>Master Data Kelembagaan
            </h3>
            <p class="text-muted fs-8 fs-md-7 mb-0">Kelola konfigurasi data pokok, kelas, jurusan, jenjang, mapel, dan tahun ajaran dalam satu atap.</p>
        </div>
        
        <!-- Toggle Trash Mode & Tambah -->
        <div class="col-12 col-md-5 d-flex gap-2 justify-content-start justify-content-md-end align-items-center flex-wrap">
            <button class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0" 
                    @click="toggleTrashMode" 
                    :class="{'btn-danger text-white': trashMode}"
                    :style="!trashMode ? 'color: #334155; border-color: #94a3b8;' : ''">
                <i class="bi" :class="trashMode ? 'bi-table' : 'bi-trash3'"></i>
                {{ trashMode ? 'Kembali ke Data Aktif' : 'Lihat Tong Sampah' }}
            </button>
            <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 shadow-sm flex-grow-1 flex-md-grow-0" @click="openCreateModal" v-if="!trashMode">
                <i class="bi bi-plus-lg me-1"></i> Tambah Data
            </button>
        </div>
    </div>

    <!-- Filter Sekolah Banner (Di bawah tulisan Master Data Kelembagaan & Sebelum Navtab) -->
    <div class="mb-4 p-3 px-md-4 rounded-4 shadow-sm border border-blue-100" 
         style="background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border-left: 4px solid #2563eb !important;"
         v-if="userRole === 'super_admin'">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <i class="bi bi-building text-primary fs-5"></i>
                <span class="fw-bold text-dark me-1" style="font-size: 0.95rem;">Filter Sekolah</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1.5 fs-8">
                    <i class="bi bi-funnel-fill me-1"></i>Aktif
                </span>

                <!-- Dropdown Filter Sekolah (Khusus Super Admin) -->
                <div class="ms-md-2 my-1 my-md-0">
                    <select id="sa-filter-sekolah-kelembagaan" name="filter_tenant_id" 
                            class="form-select form-select-sm bg-white border-blue-200 rounded-3 text-dark fw-medium shadow-sm" 
                            style="min-width: 250px; max-width: 340px; height: 38px; font-size: 0.875rem;" 
                            v-model="filterTenantId" 
                            @change="onFilterTenantChange">
                        <option value="">-- Semua Sekolah --</option>
                        <option v-for="t in listTenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                    </select>
                </div>

                <!-- Tombol Terapkan Filter -->
                <button type="button" 
                        @click="onFilterTenantChange" 
                        class="btn btn-primary btn-sm rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm"
                        style="height: 38px;">
                    <i class="bi bi-funnel-fill"></i> Terapkan Filter
                </button>

                <!-- Reset Filter jika ada -->
                <button v-if="filterTenantId" 
                        type="button" 
                        @click="clearFilterTenant" 
                        class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1"
                        style="height: 38px; color: #334155; border-color: #94a3b8;">
                    <i class="bi bi-x-circle"></i> Reset Filter
                </button>
            </div>

            <!-- Informational Text -->
            <div class="text-muted fs-8 fs-md-7">
                Menampilkan data milik: 
                <strong class="text-primary fw-bold ms-1">
                    {{ getSelectedTenantName() }}
                </strong>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Modern SINTA SaaS -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('masterDataNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="masterDataNavTabs" role="tablist">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.id">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === tab.id}" 
                                @click="switchTab(tab.id)">
                            <i :class="tab.icon" class="me-2 fs-6"></i>{{ tab.name }}
                        </button>
                    </li>
                </ul>
            </div>

            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('masterDataNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Main Datatable Grid -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3 p-md-4">
            
            <!-- Table Action Filters -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div class="d-flex align-items-center gap-2 order-2 order-md-1">
                    <label for="per_page_select" class="fs-8 text-muted mb-0">Tampilkan</label>
                    <select id="per_page_select" name="per_page" class="form-select form-select-sm rounded-3" v-model="perPage" @change="fetchData(1)" style="width: 80px;">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="fs-8 text-muted">Baris</span>
                </div>
                
                <div class="search-box-wrapper order-1 order-md-2 w-100" style="max-width: 300px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search text-muted"></i></span>
                        <label for="global_search_input" class="visually-hidden">Pencarian global</label>
                        <input id="global_search_input" name="search" type="text" class="form-control bg-light border-start-0 rounded-end-3" placeholder="Pencarian global..." v-model="search" @input="debounceSearch">
                    </div>
                </div>
            </div>

            <!-- Loader State -->
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2 fs-7">Mengunduh data dari server...</p>
            </div>

            <!-- Table Content (Dinamis berdasarkan tab aktif) -->
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-4" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <!-- Head Table Kelas -->
                        <tr v-if="activeTab === 'kelas'">
                            <th style="width: 60px;">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>Kode Kelas</th>
                            <th>Nama Rombel / Kelas</th>
                            <th>Jenjang</th>
                            <th>Jurusan</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                            <th class="text-center" style="width: 180px;">Aksi</th>
                        </tr>
                        <!-- Head Table Tahun Ajaran / Angkatan (Satu parameter input) -->
                        <tr v-else-if="activeTab === 'tahun_ajaran' || activeTab === 'angkatan'">
                            <th style="width: 60px;">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>{{ activeTab === 'tahun_ajaran' ? 'Tahun Ajaran' : 'Tahun Angkatan' }}</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                            <th class="text-center" style="width: 180px;">Aksi</th>
                        </tr>
                        <!-- Head Table Kurikulum -->
                        <tr v-else-if="activeTab === 'kurikulum'">
                            <th style="width: 60px;">No</th>
                            <th>Sekolah</th>
                            <th>Kode</th>
                            <th>Nama Data</th>
                            <th class="text-center" style="width: 130px;">Status</th>
                            <th class="text-center" style="width: 140px;">Aksi</th>
                        </tr>
                        <!-- Head Table Generik Lainnya (Jenjang, Jurusan, Mapel, dll.) -->
                        <tr v-else>
                            <th style="width: 60px;">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>Kode</th>
                            <th>Nama Data</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                            <th class="text-center" style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <!-- Loop Data Kelas -->
                        <template v-if="activeTab === 'kelas'">
                            <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td v-if="userRole === 'super_admin'" class="fw-semibold text-secondary">{{ item.nama_sekolah || '-' }}</td>
                                <td><span class="badge bg-light border font-monospace px-2.5 py-1.5 fs-8" style="color: #1d4ed8;">{{ item.kode_kelas }}</span></td>
                                <td class="fw-semibold text-dark">{{ item.nama_kelas }}</td>
                                <td>{{ item.nama_jenjang || '-' }}</td>
                                <td>{{ item.nama_jurusan || '-' }}</td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block" v-if="!trashMode">
                                        <input :id="'status_switch_kelas_' + item.id" :name="'status_switch_kelas_' + item.id" :aria-label="'Ubah status aktif kelas ' + item.nama_kelas" class="form-check-input" type="checkbox" role="switch" 
                                               :checked="item.is_active == 1" @change="toggleStatus(item.id)">
                                    </div>
                                    <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2" v-if="!trashMode">
                                        <button class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" style="color:#334155; border-color:#94a3b8;" @click="openEditModal(item)">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" @click="deleteItem(item.id)">
                                            <i class="bi bi-trash3 me-1"></i>Hapus
                                        </button>
                                    </div>
                                    <div class="d-inline-flex gap-2" v-else>
                                        <button class="btn btn-sm btn-success text-white rounded-2 px-2 py-1" @click="restoreItem(item.id)">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Loop Data Tahun Ajaran & Angkatan -->
                        <template v-else-if="activeTab === 'tahun_ajaran' || activeTab === 'angkatan'">
                            <tr v-for="(item, idx) in listData" :key="item.id">
                                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td v-if="userRole === 'super_admin'" class="fw-semibold text-secondary">{{ item.nama_sekolah || '-' }}</td>
                                <td class="fw-semibold text-dark font-monospace fs-7">
                                    {{ activeTab === 'tahun_ajaran' ? (item.tahun_ajaran || item.nama_tahun_ajaran || item.nama) : (item.tahun_angkatan || item.nama_angkatan || item.nama) }}
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block" v-if="!trashMode">
                                        <input :id="'status_switch_ta_' + item.id" :name="'status_switch_ta_' + item.id" :aria-label="'Ubah status aktif ' + (activeTab === 'tahun_ajaran' ? item.tahun_ajaran : item.tahun_angkatan)" class="form-check-input" type="checkbox" role="switch" 
                                               :checked="item.is_active == 1" @change="toggleStatus(item.id)">
                                    </div>
                                    <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2" v-if="!trashMode">
                                        <button class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" style="color:#334155; border-color:#94a3b8;" @click="openEditModal(item)">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" @click="deleteItem(item.id)">
                                            <i class="bi bi-trash3 me-1"></i>Hapus
                                        </button>
                                    </div>
                                    <div class="d-inline-flex gap-2" v-else>
                                        <button class="btn btn-sm btn-success text-white rounded-2 px-2 py-1" @click="restoreItem(item.id)">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Loop Data Kurikulum -->
                        <template v-else-if="activeTab === 'kurikulum'">
                            <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td class="text-secondary fw-normal">
                                    {{ isSystemItem(item) ? 'Sistem (Pemerintah)' : (item.nama_sekolah || 'Sistem (Pemerintah)') }}
                                </td>
                                <td class="fw-bold text-dark">
                                    {{ getKurikulumTitle(item) }}
                                    <span v-if="isSystemItem(item)" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-9 ms-1.5 px-2 py-0.5">
                                        <i class="bi bi-shield-fill-check me-0.5"></i>Nasional
                                    </span>
                                    <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill fs-9 ms-1.5 px-2 py-0.5">
                                        <i class="bi bi-building me-0.5"></i>Kustom
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-2.5 py-1 fs-8" :class="getKurikulumCategoryClass(item)">
                                        {{ getKurikulumCategoryLabel(item) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block" v-if="!trashMode && !isSystemItem(item)">
                                        <input :id="'status_switch_kur_' + item.id" :name="'status_switch_kur_' + item.id" :aria-label="'Ubah status aktif kurikulum ' + getKurikulumTitle(item)" class="form-check-input" type="checkbox" role="switch" 
                                               :checked="item.is_active == 1" @change="toggleStatus(item.id)">
                                    </div>
                                    <span v-else-if="isSystemItem(item)" class="badge text-white rounded-pill px-2.5 py-1 fs-8 fw-semibold" style="background-color: #0f766e !important;">Aktif Bawaan</span>
                                    <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2 align-items-center" v-if="!trashMode">
                                        <template v-if="isSystemItem(item)">
                                            <span class="text-muted fs-8 fw-medium d-inline-flex align-items-center gap-1"><i class="bi bi-lock-fill"></i> Terkunci</span>
                                        </template>
                                        <template v-else>
                                            <button class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" style="color:#334155; border-color:#94a3b8;" @click="openEditModal(item)">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" @click="deleteItem(item.id)">
                                                <i class="bi bi-trash3 me-1"></i>Hapus
                                            </button>
                                        </template>
                                    </div>
                                    <div class="d-inline-flex gap-2" v-else>
                                        <button class="btn btn-sm btn-success text-white rounded-2 px-2 py-1" @click="restoreItem(item.id)" v-if="!isSystemItem(item)">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                        <span v-else class="text-muted fs-8">-</span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Loop Data Generik Lainnya -->
                        <template v-else>
                            <tr v-for="(item, idx) in listData" :key="item.id">
                                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td v-if="userRole === 'super_admin'" class="fw-semibold text-secondary">{{ item.nama_sekolah || '-' }}</td>
                                <td>
                                    <span class="badge bg-light border font-monospace px-2.5 py-1.5 fs-8" style="color: #1d4ed8;">
                                        {{ getField(item, 'kode') }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark">{{ getField(item, 'nama') }}</td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block" v-if="!trashMode">
                                        <input :id="'status_switch_gen_' + item.id" :name="'status_switch_gen_' + item.id" :aria-label="'Ubah status aktif ' + (getField(item, 'nama') || getField(item, 'kode'))" class="form-check-input" type="checkbox" role="switch" 
                                               :checked="item.is_active == 1" @change="toggleStatus(item.id)">
                                    </div>
                                    <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2" v-if="!trashMode">
                                        <button class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" style="color:#334155; border-color:#94a3b8;" @click="openEditModal(item)">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" @click="deleteItem(item.id)">
                                            <i class="bi bi-trash3 me-1"></i>Hapus
                                        </button>
                                    </div>
                                    <div class="d-inline-flex gap-2" v-else>
                                        <button class="btn btn-sm btn-success text-white rounded-2 px-2 py-1" @click="restoreItem(item.id)">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr v-if="listData.length === 0">
                            <td :colspan="userRole === 'super_admin' ? 8 : 7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h6 class="fw-bold text-dark mb-1">Belum ada data {{ getActiveTabName() }}</h6>
                                <div class="fs-8 text-muted mb-2 mx-auto" style="max-width: 560px;">
                                    <template v-if="activeTab === 'jenjang'">
                                        <strong>Pendidikan (Bentuk Pendidikan):</strong> Sekolah Menengah Atas (SMA), Sekolah Menengah Kejuruan (SMK), Sekolah Menengah Pertama (SMP), Sekolah Dasar (SD), Madrasah Aliyah (MA), Madrasah Tsanawiyah (MTs), Madrasah Ibtidaiyah (MI).<br>
                                        <strong>Jenjang (Tingkat Kelas):</strong> 7, 8, 9, 10, 11, 12.
                                    </template>
                                    <template v-else-if="activeTab === 'jurusan'">
                                        <strong>SMA / MA:</strong> Umum, IPA (MIPA), IPS, Bahasa dan Budaya, Keagamaan.<br>
                                        <strong>SMK / MAK:</strong> Teknik Komputer dan Jaringan (TKJ), Rekayasa Perangkat Lunak (RPL), Akuntansi dan Keuangan Lembaga (AKL), Desain Komunikasi Visual (DKV), Teknik Kendaraan Ringan (TKR), Teknik &amp; Bisnis Sepeda Motor (TBSM).
                                    </template>
                                    <template v-else-if="activeTab === 'kelas'">
                                        <strong>Jenjang (Tingkat Kelas):</strong> 7, 8, 9, 10, 11, 12.<br>
                                        <strong>Contoh Nama Rombel Kelas:</strong> VII A, VIII B, IX C, X IPA 1, XI TKJ 1, XII DKV 1.
                                    </template>
                                    <template v-else-if="activeTab === 'program_pengajaran'">
                                        <p class="mb-2">Silakan klik tombol <strong>"Tambah Program Pengajaran"</strong> di atas untuk menambahkan data baru.</p>
                                        <div class="bg-light border p-3 rounded-3 text-start font-monospace fs-8 text-dark">
                                            <div class="fw-bold text-primary mb-1">Contoh data Program Pengajaran:</div>
                                            <div>• <code>PROG-REG-01</code> : Program Pengajaran Reguler 5 Hari Kerja</div>
                                            <div>• <code>PROG-REG-02</code> : Program Pengajaran Reguler 6 Hari Kerja</div>
                                            <div>• <code>PROG-VOK-01</code> : Program Pengajaran Vokasi &amp; Kelas Industri</div>
                                            <div>• <code>PROG-BIL-01</code> : Program Pengajaran Kelas Bilingual (Bahasa Inggris)</div>
                                            <div>• <code>PROG-AKS-01</code> : Program Akselerasi Akademik Cepat</div>
                                        </div>
                                    </template>
                                    <template v-else>
                                        Silakan klik tombol "Tambah {{ getActiveTabName() }}" di atas untuk menambahkan data baru.
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Pagination Footer -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3" v-if="total > 0">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fs-8 text-muted">Menampilkan {{ from }} s.d. {{ to }} dari {{ total }} baris</span>
                    <div class="d-flex align-items-center gap-1 ms-2">
                        <label for="mk-perpage" class="fs-8 text-muted mb-0">Tampilkan:</label>
                        <select id="mk-perpage" name="mk_perpage" class="form-select form-select-sm py-0 px-2 rounded-2 fs-8" style="width: auto; height: 28px;" v-model="perPage" @change="fetchData(1)">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="80">80</option>
                            <option :value="120">120</option>
                        </select>
                        <span class="fs-8 text-muted">per hal</span>
                    </div>
                </div>
                <nav v-if="totalPages > 1">
                    <ul class="pagination pagination-sm m-0">
                        <li class="page-item" :class="{disabled: currentPage === 1}">
                            <a class="page-link" href="#" @click.prevent="fetchData(currentPage - 1)">&laquo;</a>
                        </li>
                        <li class="page-item" v-for="page in totalPages" :key="page" :class="{active: page === currentPage}">
                            <a class="page-link" href="#" @click.prevent="fetchData(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === totalPages}">
                            <a class="page-link" href="#" @click.prevent="fetchData(currentPage + 1)">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>

    <!-- Reusable Form Modal (Tambah / Edit) -->
    <div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        {{ isEditMode ? 'Edit ' + getActiveTabName() : 'Tambah ' + getActiveTabName() }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form @submit.prevent="submitForm">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            
                            <!-- Input Sekolah khusus Super Admin -->
                            <div class="col-12" v-if="userRole === 'super_admin'">
                                <label for="form_tenant_id" class="form-label fw-semibold fs-8 text-muted mb-1">Sekolah / Tenant <span class="text-danger">*</span></label>
                                <select id="form_tenant_id" name="tenant_id" class="form-select rounded-3" :class="{'is-invalid': errors.tenant_id}" v-model="form.tenant_id" :disabled="isEditMode" @change="onTenantChange" required>
                                    <option value="" disabled>-- Pilih Sekolah --</option>
                                    <option v-for="t in listTenants" :value="t.id" :key="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                                <div class="invalid-feedback">{{ getError('tenant_id') }}</div>
                            </div>
                            
                            <!-- Form inputs khusus modul KELAS -->
                            <template v-if="activeTab === 'kelas'">
                                <div class="col-12">
                                    <label for="form_id_jenjang" class="form-label fw-semibold fs-8 text-muted mb-1">Bentuk Pendidikan <span class="text-danger">*</span></label>
                                    <select id="form_id_jenjang" name="id_jenjang" class="form-select rounded-3" :class="{'is-invalid': errors.id_jenjang}" v-model="form.id_jenjang" required>
                                        <option value="" disabled>-- Pilih Bentuk Pendidikan (SMA/SMK/SMP/SD) --</option>
                                        <option v-for="j in listJenjang" :value="j.id" :key="j.id">{{ j.nama }}</option>
                                    </select>
                                    <div class="invalid-feedback">{{ getError('id_jenjang') }}</div>
                                    <small class="text-muted fs-9">Contoh Bentuk Pendidikan: Sekolah Menengah Atas (SMA), Sekolah Menengah Kejuruan (SMK), Sekolah Menengah Pertama (SMP).</small>
                                </div>
                                <div class="col-12">
                                    <label for="form_id_jurusan" class="form-label fw-semibold fs-8 text-muted mb-1">Jurusan / Program Keahlian <span class="text-danger">*</span></label>
                                    <select id="form_id_jurusan" name="id_jurusan" class="form-select rounded-3" :class="{'is-invalid': errors.id_jurusan}" v-model="form.id_jurusan" required>
                                        <option value="" disabled>-- Pilih Jurusan (IPA/IPS/TKJ/RPL/AKL/Umum) --</option>
                                        <option v-for="j in listJurusan" :value="j.id" :key="j.id">{{ j.nama }}</option>
                                    </select>
                                    <div class="invalid-feedback">{{ getError('id_jurusan') }}</div>
                                    <small class="text-muted fs-9">Contoh SMA: Umum, IPA (MIPA), IPS, Bahasa. Contoh SMK: TKJ, RPL, AKL, DKV, TKR.</small>
                                </div>
                                <div class="col-12">
                                    <label for="form_kode_kelas" class="form-label fw-semibold fs-8 text-muted mb-1">Kode Kelas <span class="text-danger">*</span></label>
                                    <input id="form_kode_kelas" name="kode_kelas" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kode_kelas}" v-model="form.kode_kelas" placeholder="Contoh: KLS-XIPA1, KLS-XRPL1, KLS-7A" required>
                                    <div class="invalid-feedback">{{ getError('kode_kelas') }}</div>
                                </div>
                                <div class="col-12">
                                    <label for="form_nama_kelas" class="form-label fw-semibold fs-8 text-muted mb-1">Nama Kelas / Rombel <span class="text-danger">*</span></label>
                                    <input id="form_nama_kelas" name="nama_kelas" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.nama_kelas}" v-model="form.nama_kelas" placeholder="Contoh Rombel Kelas: VII A, VIII B, IX C, X IPA 1, XI TKJ 1, XII DKV 1" required>
                                    <div class="invalid-feedback">{{ getError('nama_kelas') }}</div>
                                    <small class="text-muted fs-9">Contoh Kelas: VII A, VIII B, IX C, X IPA 1, XI TKJ 1, XII DKV 1.</small>
                                </div>
                            </template>

                            <!-- Form inputs khusus Tahun Ajaran -->
                            <template v-else-if="activeTab === 'tahun_ajaran'">
                                <div class="col-12">
                                    <label for="form_tahun_ajaran" class="form-label fw-semibold fs-8 text-muted mb-1">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <input id="form_tahun_ajaran" name="kode" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kode}" v-model="form.kode" placeholder="Contoh: 2025/2026" required>
                                    <div class="invalid-feedback">{{ getError('kode') }}</div>
                                    <small class="text-muted fs-9">Gunakan format YYYY/YYYY (misal: 2025/2026).</small>
                                </div>
                            </template>

                            <!-- Form inputs khusus Angkatan -->
                            <template v-else-if="activeTab === 'angkatan'">
                                <div class="col-12">
                                    <label for="form_tahun_angkatan" class="form-label fw-semibold fs-8 text-muted mb-1">Tahun Angkatan <span class="text-danger">*</span></label>
                                    <input id="form_tahun_angkatan" name="kode" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kode}" v-model="form.kode" placeholder="Contoh: 2026" required>
                                    <div class="invalid-feedback">{{ getError('kode') }}</div>
                                    <small class="text-muted fs-9">Masukkan 4 digit angka tahun masuk siswa (misal: 2026).</small>
                                </div>
                            </template>

                            <!-- Form inputs khusus Kurikulum -->
                            <template v-else-if="activeTab === 'kurikulum'">
                                <div class="col-12">
                                    <label for="form_kurikulum_nama" class="form-label fw-semibold fs-8 text-muted mb-1">Nama Kurikulum <span class="text-danger">*</span></label>
                                    <input id="form_kurikulum_nama" name="nama_kurikulum" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.nama}" v-model="form.nama_kurikulum" placeholder="Contoh: Kurikulum Merdeka, K-13 Revisi" required>
                                    <div class="invalid-feedback">{{ getError('nama') }}</div>
                                </div>
                                <div class="col-12">
                                    <label for="form_kurikulum_tipe" class="form-label fw-semibold fs-8 text-muted mb-1">Tipe Penilaian / Rapor <span class="text-danger">*</span></label>
                                    <select id="form_kurikulum_tipe" name="tipe_penilaian" class="form-select rounded-3" :class="{'is-invalid': errors.tipe_penilaian}" v-model="form.tipe_penilaian" required>
                                        <option value="" disabled>-- Pilih Tipe Penilaian --</option>
                                        <option value="sederhana">Sederhana (Merdeka - Nilai Akhir & Deskripsi Capaian)</option>
                                        <option value="klasik">Klasik (KTSP - Kognitif, Psikomotorik, Afektif)</option>
                                        <option value="kompleks">Kompleks (K-13 - Pengetahuan KI-3 & Keterampilan KI-4)</option>
                                    </select>
                                    <div class="invalid-feedback">{{ getError('tipe_penilaian') }}</div>
                                </div>
                            </template>

                            <!-- Form inputs generik (Jenjang, Jurusan, Mapel, dll) -->
                            <template v-else>
                                <div class="col-12">
                                    <label for="form_generik_kode" class="form-label fw-semibold fs-8 text-muted mb-1">Kode {{ getActiveTabName() }} <span class="text-danger">*</span></label>
                                    <input id="form_generik_kode" name="kode" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kode}" v-model="form.kode"
                                           :placeholder="activeTab === 'jenjang' ? 'Contoh Kode: SMA, SMK, SMP, SD, MA' : activeTab === 'jurusan' ? 'Contoh Kode: IPA, IPS, RPL, TKJ, AKL' : 'Masukkan kode...'" required>
                                    <div class="invalid-feedback">{{ getError('kode') }}</div>
                                    <small class="text-muted fs-9" v-if="activeTab === 'jenjang'">Singkatan Bentuk Pendidikan (misal: SMA, SMK, SMP, SD, MA, MTS).</small>
                                    <small class="text-muted fs-9" v-else-if="activeTab === 'jurusan'">Singkatan Jurusan (misal: IPA, IPS, TKJ, RPL, AKL, DKV).</small>
                                </div>
                                <div class="col-12">
                                    <label for="form_generik_nama" class="form-label fw-semibold fs-8 text-muted mb-1">Nama {{ getActiveTabName() }} <span class="text-danger">*</span></label>
                                    <input id="form_generik_nama" name="nama" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.nama}" v-model="form.nama"
                                           :placeholder="activeTab === 'jenjang' ? 'Contoh Nama: Sekolah Menengah Atas, Sekolah Menengah Kejuruan' : activeTab === 'jurusan' ? 'Contoh Nama: Ilmu Pengetahuan Alam, Teknik Komputer dan Jaringan' : 'Masukkan nama...'" required>
                                    <div class="invalid-feedback">{{ getError('nama') }}</div>
                                    <small class="text-muted fs-9" v-if="activeTab === 'jenjang'">Contoh Bentuk Pendidikan: Sekolah Menengah Atas (SMA), Sekolah Menengah Kejuruan (SMK), Sekolah Menengah Pertama (SMP).</small>
                                    <small class="text-muted fs-9" v-else-if="activeTab === 'jurusan'">Contoh SMA: Umum, IPA, IPS, Bahasa. Contoh SMK: Teknik Komputer dan Jaringan, Rekayasa Perangkat Lunak, Akuntansi.</small>
                                </div>
                            </template>

                        </div>
                    </div>
                    
                    <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-3 fs-8 px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-3 fs-8 px-4" :disabled="submitLoading">
                            <span v-if="submitLoading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Simpan Data
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

<!-- Styles CSS Custom untuk Pilihan Tabs & Scrollable -->
<style>
    [v-cloak] {
        display: none !important;
    }
    
    .scrollable-nav-pills {
        padding-bottom: 5px;
    }

    .scrollable-nav-pills::-webkit-scrollbar {
        height: 5px;
    }

    .scrollable-nav-pills::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }

    .nav-tabs-wrapper .nav-link {
        color: #475569;
        background-color: transparent;
        transition: all 0.25s ease;
    }

    .nav-tabs-wrapper .nav-link:hover {
        color: #2563eb;
        background-color: #f1f5f9;
    }

    .nav-tabs-wrapper .nav-link.active {
        color: #ffffff !important;
        background-color: #2563eb !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    
    .table-light-danger {
        background-color: #fef2f2 !important;
    }
    
    .fs-9 {
        font-size: 0.75rem !important;
    }
    
    .fs-8 {
        font-size: 0.8rem !important;
    }
    
    .fs-7 {
        font-size: 0.875rem !important;
    }
</style>

<!-- Script Inisialisasi Vue App -->
<script>
{
    window.VueAppRegistry.register('#masterKelembagaanApp', {
        data() {
            return {
                tabs: [
                    { id: 'pendidikan', name: 'Pendidikan', icon: 'bi bi-award-fill' },
                    { id: 'jenjang', name: 'Jenjang', icon: 'bi bi-award' },
                    { id: 'jurusan', name: 'Jurusan', icon: 'bi bi-diagram-3' },
                    { id: 'kelas', name: 'Kelas', icon: 'bi bi-mortarboard' },
                    { id: 'mata_pelajaran', name: 'Mata Pelajaran', icon: 'bi bi-book' },
                    { id: 'program_pengajaran', name: 'Program Pengajaran', icon: 'bi bi-journal-text' },
                    { id: 'tahun_ajaran', name: 'Tahun Ajaran', icon: 'bi bi-calendar-check' },
                    { id: 'angkatan', name: 'Angkatan', icon: 'bi bi-calendar2-range' },
                    { id: 'kurikulum', name: 'Kurikulum', icon: 'bi bi-gear-wide-connected' }
                ],
                activeTab: 'pendidikan',
                userRole: document.getElementById('masterKelembagaanApp')?.dataset?.userRole || '<?= htmlspecialchars($userRole ?? '', ENT_QUOTES, 'UTF-8') ?>',
                listTenants: [],
                filterTenantId: '', // Filter sekolah aktif (Super Admin only)
                listData: [],
                listJenjang: [], // Opsi khusus dropdown Kelas
                listJurusan: [], // Opsi khusus dropdown Kelas
                currentPage: 1,
                totalPages: 1,
                perPage: 10,
                search: '',
                total: 0,
                from: 0,
                to: 0,
                
                loading: false,
                submitLoading: false,
                trashMode: false,
                isEditMode: false,
                editId: null,
                form: {},
                errors: {},
                modalObj: null,
                searchTimeout: null,

                toast: Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                })
            };
        },
        mounted() {
            const rootEl = document.getElementById('masterKelembagaanApp');
            if (rootEl?.dataset?.userRole) {
                this.userRole = rootEl.dataset.userRole;
            }
            if (this.userRole === 'super_admin') {
                this.fetchTenantsList();
            }
            this.fetchData(1);
            this.fetchAuxiliaryData();
        },
        methods: {
            async fetchTenantsList() {
                try {
                    const res = await axios.get('<?= htmlspecialchars($this->getBaseUrl(), ENT_QUOTES, 'UTF-8') ?>/api/v1/kelembagaan/tenants');
                    if (res.data && res.data.success) {
                        this.listTenants = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Gagal memuat daftar sekolah:', e);
                }
            },
            getModal() {
                const el = document.getElementById('formModal');
                return (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) ? bootstrap.Modal.getOrCreateInstance(el) : null;
            },
            switchTab(tabId) {
                this.activeTab = tabId;
                this.trashMode = false;
                this.search = '';
                this.resetForm();
                this.fetchData(1);
            },
            getActiveTabName() {
                const tab = this.tabs.find(t => t.id === this.activeTab);
                return tab ? tab.name : '';
            },
            getSelectedTenantName() {
                if (!this.filterTenantId) return 'Semua Sekolah';
                const t = (this.listTenants || []).find(t => t.id === this.filterTenantId);
                return t ? t.nama_sekolah : 'Semua Sekolah';
            },
            // Nama sekolah yang sedang difilter (computed via find)
            get selectedTenantName() {
                if (!this.filterTenantId) return '';
                const t = (this.listTenants || []).find(t => t.id === this.filterTenantId);
                return t ? t.nama_sekolah : '';
            },
            isSystemItem(item) {
                if (!item) return false;
                return !item.tenant_id || 
                       item.tenant_id === '11111111-1111-1111-1111-111111111111' || 
                       item.tenant_id === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' || 
                       item.is_system === true || 
                       item.is_system === 1;
            },
            getKurikulumTitle(item) {
                if (!item) return '';
                return item.nama_ref_kurikulum || item.nama_kurikulum || item.nama || '';
            },
            getKurikulumCategoryLabel(item) {
                if (!item) return 'Sederhana (Merdeka)';
                const kat = (item.kategori || item.tipe_penilaian || '').toLowerCase();
                const name = (item.nama_ref_kurikulum || item.nama_kurikulum || item.nama || '').toLowerCase();

                if (kat === 'kompleks' || name.includes('2013') || name.includes('k-13') || name.includes('vokasi')) {
                    return 'Kompleks (K-13)';
                } else if (kat === 'klasik' || name.includes('ktsp') || name.includes('kbk')) {
                    return 'Klasik (KTSP)';
                } else {
                    return 'Sederhana (Merdeka)';
                }
            },
            getKurikulumCategoryClass(item) {
                const label = this.getKurikulumCategoryLabel(item);
                if (label.includes('Kompleks')) {
                    return 'bg-danger-subtle text-danger border border-danger-subtle';
                } else if (label.includes('Klasik')) {
                    return 'bg-info-subtle text-info border border-info-subtle';
                } else {
                    return 'bg-success-subtle text-success border border-success-subtle';
                }
            },
            onFilterTenantChange() {
                this.fetchData(1);
                // Refresh auxiliary data (jenjang/jurusan dropdown) sesuai sekolah yang dipilih
                if (this.activeTab === 'kelas') {
                    this.fetchAuxiliaryData(this.filterTenantId || null);
                }
            },
            clearFilterTenant() {
                this.filterTenantId = '';
                this.fetchData(1);
                if (this.activeTab === 'kelas') {
                    this.fetchAuxiliaryData(null);
                }
            },
            fetchData(page = 1) {
                this.loading = true;
                this.currentPage = page;

                const params = {
                    module:    this.activeTab,
                    page:      this.currentPage,
                    per_page:  this.perPage,
                    search:    this.search,
                    trash:     this.trashMode ? 'true' : 'false'
                };

                // Kirim filter_tenant_id hanya jika Super Admin memilih sekolah
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    params.filter_tenant_id = this.filterTenantId;
                }

                axios.get('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan', { params })
                    .then(res => {
                        const payload = (res.data && res.data.data) ? res.data.data : (res.data || {});
                        this.listData    = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload) ? payload : []);
                        this.totalPages  = payload.last_page || 1;
                        this.total       = payload.total || 0;
                        this.from        = payload.from || 0;
                        this.to          = payload.to || 0;
                        this.loading     = false;
                    }).catch(err => {
                        this.loading = false;
                        this.toast.fire({ icon: 'error', title: (err.response && err.response.data && err.response.data.error) || 'Gagal memuat data dari server.' });
                    });
            },
            // Ambil opsi relasi untuk modal form Kelas
            fetchAuxiliaryData(tenantId = null) {
                const activeTid = tenantId || this.filterTenantId || null;
                const params = { module: 'jenjang' };
                const params2 = { module: 'jurusan' };
                if (activeTid) {
                    params.tenant_id = activeTid;
                    params2.tenant_id = activeTid;
                }
                
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan/options', { params })
                     .then(res => this.listJenjang = (res.data && res.data.data) ? res.data.data : (res.data || []))
                     .catch(err => console.error("Gagal mengambil opsi jenjang:", err));
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan/options', { params: params2 })
                     .then(res => this.listJurusan = (res.data && res.data.data) ? res.data.data : (res.data || []))
                     .catch(err => console.error("Gagal mengambil opsi jurusan:", err));
            },
            fetchTenants() {
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan/tenants')
                     .then(res => {
                         this.listTenants = res.data.data;
                     })
                     .catch(err => {
                         console.error("Gagal mengambil data sekolah:", err);
                     });
            },
            onTenantChange() {
                if (this.activeTab === 'kelas') {
                    this.fetchAuxiliaryData(this.form.tenant_id);
                }
            },
            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.fetchData(1);
                }, 400);
            },
            toggleTrashMode() {
                this.trashMode = !this.trashMode;
                this.fetchData(1);
            },
            resetForm() {
                this.errors = {};
                if (this.activeTab === 'kelas') {
                    this.form = { id_jenjang: '', id_jurusan: '', kode_kelas: '', nama_kelas: '' };
                } else if (this.activeTab === 'tahun_ajaran' || this.activeTab === 'angkatan') {
                    this.form = { kode: '' };
                } else if (this.activeTab === 'kurikulum') {
                    this.form = { nama_kurikulum: '', tipe_penilaian: 'sederhana', is_active: 1 };
                } else {
                    this.form = { kode: '', nama: '' };
                }
                if (this.userRole === 'super_admin') {
                    this.form.tenant_id = this.filterTenantId || '';
                    if (this.activeTab === 'kelas') {
                        this.fetchAuxiliaryData(this.form.tenant_id);
                    }
                }
            },
            openCreateModal() {
                this.isEditMode = false;
                this.resetForm();
                if (this.userRole === 'super_admin') {
                    if (!this.form.tenant_id && this.listTenants && this.listTenants.length > 0) {
                        this.form.tenant_id = this.listTenants[0].id;
                    }
                    if (this.activeTab === 'kelas') {
                        this.fetchAuxiliaryData(this.form.tenant_id);
                    }
                }
                const m = this.getModal();
                if (m) m.show();
            },
            openEditModal(item) {
                this.isEditMode = true;
                this.errors = {};
                this.editId = item.id;
                
                if (this.activeTab === 'kelas') {
                    this.form = {
                        id_jenjang: item.id_jenjang || '',
                        id_jurusan: item.id_jurusan || '',
                        kode_kelas: item.kode_kelas || item.kode || '',
                        nama_kelas: item.nama_kelas || item.nama || ''
                    };
                } else if (this.activeTab === 'tahun_ajaran' || this.activeTab === 'angkatan') {
                    this.form = {
                        kode: this.activeTab === 'tahun_ajaran' ? (item.tahun_ajaran || item.nama_tahun_ajaran || item.nama || '') : (item.tahun_angkatan || item.nama_angkatan || item.nama || '')
                    };
                } else if (this.activeTab === 'kurikulum') {
                    this.form = {
                        nama_kurikulum: item.nama_kurikulum || item.nama_ref_kurikulum || item.nama || '',
                        tipe_penilaian: item.tipe_penilaian || item.kategori || 'sederhana',
                        is_active: item.is_active
                    };
                } else {
                    this.form = {
                        kode: this.getField(item, 'kode'),
                        nama: this.getField(item, 'nama')
                    };
                }
                
                if (this.userRole === 'super_admin') {
                    this.form.tenant_id = item.tenant_id;
                    if (this.activeTab === 'kelas') {
                        this.fetchAuxiliaryData(item.tenant_id);
                    }
                }
                
                const m = this.getModal();
                if (m) m.show();
            },
            submitForm() {
                this.submitLoading = true;
                this.errors = {};
                
                // Siapkan data payload
                const payload = { ...this.form, module: this.activeTab };
                if (this.userRole === 'super_admin') {
                    payload.tenant_id = this.form.tenant_id || this.filterTenantId || '';
                } else {
                    payload.tenant_id = this.tenantId || '';
                }
                if (this.isEditMode) {
                    payload.id = this.editId;
                }

                axios.post('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan/simpan', payload)
                     .then(res => {
                         this.submitLoading = false;
                         if (res.data.success === false && res.data.errors) {
                             this.errors = res.data.errors;
                             this.toast.fire({ icon: 'error', title: 'Silakan periksa input form Anda.' });
                             return;
                         }
                         const m = this.getModal();
                         if (m) m.hide();
                         this.toast.fire({ icon: 'success', title: res.data.message });
                         this.fetchData(this.isEditMode ? this.currentPage : 1);
                         if (this.activeTab === 'jenjang' || this.activeTab === 'jurusan') {
                             this.fetchAuxiliaryData(this.userRole === 'super_admin' ? (this.form.tenant_id || this.filterTenantId) : null);
                         }
                     })
                     .catch(err => {
                         this.submitLoading = false;
                         if (err.response && err.response.status === 422) {
                             this.errors = err.response.data.errors;
                             this.toast.fire({ icon: 'error', title: 'Silakan periksa input form Anda.' });
                         } else {
                             this.toast.fire({ icon: 'error', title: (err.response && err.response.data && err.response.data.error) || 'Gagal menyimpan data.' });
                         }
                     });
            },
            deleteItem(id) {
                Swal.fire({
                    title: 'Pindahkan ke Tong Sampah?',
                    text: `Data ini akan disembunyikan sementara dari sistem.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan/hapus', { module: this.activeTab, id: id })
                             .then(res => {
                                 if (res.data && res.data.success) {
                                     this.toast.fire({ icon: 'success', title: res.data.message });
                                     this.fetchData(this.currentPage);
                                 } else {
                                     const msg = (res.data && res.data.error) || 'Gagal menghapus data.';
                                     Swal.fire({
                                         icon: 'warning',
                                         title: 'Tidak Dapat Dihapus',
                                         text: msg,
                                         confirmButtonColor: '#3085d6',
                                         confirmButtonText: 'Saya Mengerti'
                                     });
                                 }
                             })
                             .catch(err => {
                                 const msg = (err.response && err.response.data && err.response.data.error) || 'Gagal menghapus data.';
                                 Swal.fire({
                                     icon: 'warning',
                                     title: 'Tidak Dapat Dihapus',
                                     text: msg,
                                     confirmButtonColor: '#3085d6',
                                     confirmButtonText: 'Saya Mengerti'
                                 });
                             });
                    }
                });
            },
            restoreItem(id) {
                axios.post('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan/restore', { module: this.activeTab, id: id })
                     .then(res => {
                         this.toast.fire({ icon: 'success', title: res.data.message });
                         this.fetchData(this.currentPage);
                     })
                     .catch(err => {
                         this.toast.fire({ icon: 'error', title: 'Gagal memulihkan data.' });
                     });
            },
            toggleStatus(id) {
                axios.post('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan/toggle-status', { module: this.activeTab, id: id })
                     .then(res => {
                         this.toast.fire({ icon: 'success', title: res.data.message });
                     })
                     .catch(err => {
                         this.toast.fire({ icon: 'error', title: 'Gagal merubah status keaktifan.' });
                         this.fetchData(this.currentPage); // Reset switch
                     });
            },
            getField(item, type) {
                if (!item) return '-';
                if (type === 'kode') {
                    return item.kode || item.kode_kelas || item.kode_jenjang || item.kode_jurusan || item.kode_mapel || item.kode_mata_pelajaran || item.kode_pendidikan || item.kode_program || item.tahun_ajaran || item.nama_tahun_ajaran || item.tahun_angkatan || item.nama_angkatan || (item.id ? item.id.substring(0, 8) : '-');
                } else if (type === 'nama') {
                    return item.nama || item.nama_kelas || item.nama_jenjang || item.nama_jurusan || item.nama_mapel || item.nama_mata_pelajaran || item.nama_pendidikan || item.nama_program || item.nama_ref_kurikulum || item.nama_kurikulum || item.tahun_ajaran || item.nama_tahun_ajaran || item.tahun_angkatan || item.nama_angkatan || '-';
                }
                return '-';
            },
            getError(field) {
                return this.errors[field] ? this.errors[field][0] : '';
            }
        }
    });
}
</script>
