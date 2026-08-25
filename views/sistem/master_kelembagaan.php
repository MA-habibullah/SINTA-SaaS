<!-- Halaman Sentral: Master Data Kelembagaan & Data Pokok -->
<div id="masterKelembagaanApp" data-user-role="<?= htmlspecialchars($userRole ?? '', ENT_QUOTES, 'UTF-8') ?>" data-tenant-id="<?= htmlspecialchars((string)($tenantId ?? ''), ENT_QUOTES, 'UTF-8') ?>" v-cloak>

    <!-- 1. Row Header & Action Toolbar -->
    <div class="row mb-3 mb-md-4 align-items-center justify-content-between g-3">
        <div class="col-12 col-lg-7">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-blue-600 text-white rounded-2xl d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px;">
                    <i class="bi bi-diagram-3-fill fs-4"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h3 class="fw-bold text-slate-900 mb-0 fs-4">Master Data Kelembagaan</h3>
                        <span v-if="userRole === 'super_admin'" class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-1 fs-9 font-bold">
                            <i class="bi bi-shield-check text-blue-600 me-1"></i>Super Admin
                        </span>
                    </div>
                    <p class="text-slate-500 fs-8 mb-0 mt-0.5">Kelola data pokok, kelas, jurusan, jenjang, mapel, kurikulum, dan tahun ajaran terpusat.</p>
                </div>
            </div>
        </div>
        
        <!-- Grouped Action Buttons -->
        <div class="col-12 col-lg-5 d-flex gap-2 justify-content-start justify-content-lg-end align-items-center flex-wrap">
            <!-- Tombol Tong Sampah Toggle -->
            <button type="button"
                    class="btn btn-sm rounded-xl px-3 py-2 fs-8 font-semibold transition d-inline-flex align-items-center gap-1.5 shadow-2xs" 
                    @click="toggleTrashMode" 
                    :class="trashMode ? 'btn-danger text-white' : 'btn-light border border-slate-200 text-slate-600 hover:bg-slate-100'"
                    :title="trashMode ? 'Kembali ke data aktif' : 'Lihat data di tong sampah'">
                <i class="bi" :class="trashMode ? 'bi-arrow-left-circle' : 'bi-trash3'"></i>
                <span>{{ trashMode ? 'Kembali ke Data Aktif' : 'Sampah' }}</span>
            </button>

            <!-- Tombol Tambah Utama -->
            <button class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs d-inline-flex align-items-center gap-1.5 hover-lift" 
                    @click="openCreateModal" 
                    v-if="!trashMode">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah {{ getActiveTabName() }}</span>
            </button>
        </div>
    </div>

    <!-- 2. Compact School Selector Banner (Khusus Super Admin) -->
    <div class="mb-4 p-3 px-md-4 rounded-2xl shadow-2xs border border-blue-100 bg-white" 
         v-if="userRole === 'super_admin' && listTenants.length > 0">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center flex-wrap gap-2.5">
                <div class="bg-blue-50 text-blue-600 p-2 rounded-xl d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-building fs-6"></i>
                </div>
                <div>
                    <span class="fs-8 fw-bold text-slate-800 me-2">Pilih Sekolah:</span>
                </div>
                
                <div class="my-1 my-md-0">
                    <select id="sa-filter-sekolah-kelembagaan" name="filter_tenant_id" 
                            class="form-select form-select-sm bg-slate-50 border-slate-200 rounded-xl text-slate-800 fs-8 font-semibold shadow-2xs cursor-pointer focus:bg-white" 
                            style="min-width: 260px; max-width: 360px; height: 38px;" 
                            v-model="filterTenantId" 
                            @change="onFilterTenantChange">
                        <option value="">-- Semua Sekolah (Global) --</option>
                        <option v-for="t in listTenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                    </select>
                </div>
            </div>

            <div class="text-slate-500 fs-8 d-flex align-items-center gap-1.5">
                <i class="bi bi-info-circle text-blue-500"></i>
                <span>Data Aktif: <strong class="text-blue-600 fw-bold">{{ getSelectedTenantName() }}</strong></span>
            </div>
        </div>
    </div>

    <!-- 3. Horizontal NavTabs (Single-Row Modern Pill NavTab with 3-Way Scroller) -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <!-- 1 Tombol Panah Kiri -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('masterDataNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="masterDataNavTabs" role="tablist">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.id">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" 
                                :class="{'active': activeTab === tab.id}" 
                                @click="switchTab(tab.id)">
                            <i :class="tab.icon" class="me-2 fs-6"></i>{{ tab.name }}
                        </button>
                    </li>
                </ul>
            </div>

            <!-- 1 Tombol Panah Kanan -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('masterDataNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Tombol Segarkan Data -->
            <div class="d-none d-md-flex align-items-center ps-2 pe-1 border-s border-slate-200/80 ms-2">
                <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl px-3 py-2 text-xs font-bold shadow-2xs d-flex align-items-center gap-1.5" @click="fetchData(1)" title="Segarkan Data">
                    <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
                    <span>Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 4. KPI Summary Metric Cards (Ringkasan Real-time Data Pokok) -->
    <div class="row g-3 mb-4" v-if="!trashMode">
        <!-- Card 1: Total Data Entitas -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card shadow-2xs d-flex align-items-center gap-3">
                <div class="kpi-icon-box bg-blue-50 text-blue-600">
                    <i class="bi bi-database-check"></i>
                </div>
                <div>
                    <div class="kpi-label">Total {{ getActiveTabName() }}</div>
                    <div class="kpi-value">{{ total }}</div>
                </div>
            </div>
        </div>
        <!-- Card 2: Status Aktif -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card shadow-2xs d-flex align-items-center gap-3">
                <div class="kpi-icon-box bg-emerald-50 text-emerald-600">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="kpi-label">Data Aktif</div>
                    <div class="kpi-value text-emerald-600">{{ activeCount }}</div>
                </div>
            </div>
        </div>
        <!-- Card 3: Status Terkunci / Sistem -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card shadow-2xs d-flex align-items-center gap-3">
                <div class="kpi-icon-box bg-amber-50 text-amber-600">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <div class="kpi-label">Standar Sistem</div>
                    <div class="kpi-value text-amber-600">{{ systemCount }}</div>
                </div>
            </div>
        </div>
        <!-- Card 4: Kategori Modul -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card shadow-2xs d-flex align-items-center gap-3">
                <div class="kpi-icon-box bg-indigo-50 text-indigo-600">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </div>
                <div>
                    <div class="kpi-label">Total Modul Pokok</div>
                    <div class="kpi-value text-indigo-600">{{ tabs.length }} Kategori</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Main Datatable Card & Filter Toolbar -->
    <div class="card bg-white border-0 shadow-xs rounded-2xl p-4 mb-4">
        
        <!-- Table Action Toolbar (Search & Per Page) -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2 order-2 order-md-1">
                <label for="per_page_select" class="fs-8 text-slate-600 font-medium mb-0">Tampilkan</label>
                <select id="per_page_select" 
                        name="per_page" 
                        aria-label="Tampilkan baris data" 
                        class="form-select form-select-sm rounded-xl border border-slate-200 bg-white text-slate-800 font-medium shadow-2xs" 
                        v-model="perPage" 
                        @change="fetchData(1)" 
                        style="width: 84px; height: 38px;">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="fs-8 text-slate-600 font-medium">Baris</span>
            </div>
            
            <div class="search-box-wrapper order-1 order-md-2 w-100" style="max-width: 360px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-slate-50 border-slate-200 border-end-0 rounded-start-xl text-slate-400 ps-3">
                        <i class="bi bi-search"></i>
                    </span>
                    <label for="global_search_input" class="visually-hidden">Pencarian data master</label>
                    <input id="global_search_input" 
                           name="search" 
                           aria-label="Cari data master" 
                           type="text" 
                           class="form-control bg-slate-50 border-slate-200 border-start-0 border-end-0 text-slate-800 fs-8 font-medium py-2 shadow-none focus:bg-white" 
                           placeholder="Cari kode, nama, atau deskripsi..." 
                           v-model="search" 
                           @input="debounceSearch">
                    <span class="input-group-text bg-slate-50 border-slate-200 border-start-0 rounded-end-xl pe-2" v-if="search">
                        <button type="button" class="btn btn-sm btn-link text-slate-400 p-0 text-decoration-none" @click="search=''; fetchData(1)">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </span>
                    <span class="input-group-text bg-slate-50 border-slate-200 border-start-0 rounded-end-xl pe-2" v-else></span>
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
            <table class="table table-hover align-middle mb-0 pengguna-table" style="font-size: 0.85rem;">
                <thead>
                    <!-- Head Table Kelas -->
                    <tr v-if="activeTab === 'kelas'">
                        <th style="width: 50px;" class="text-center">No</th>
                        <th v-if="userRole === 'super_admin'">Sekolah</th>
                        <th>Kode Kelas</th>
                        <th>Nama Rombel / Kelas</th>
                        <th>Jenjang</th>
                        <th>Jurusan</th>
                        <th class="text-center" style="width: 110px;">Status</th>
                        <th class="text-center pe-3" style="width: 150px;">Aksi</th>
                    </tr>
                    <!-- Head Table Tahun Ajaran / Angkatan -->
                    <tr v-else-if="activeTab === 'tahun_ajaran' || activeTab === 'angkatan'">
                        <th style="width: 50px;" class="text-center">No</th>
                        <th v-if="userRole === 'super_admin'">Sekolah</th>
                        <th>{{ activeTab === 'tahun_ajaran' ? 'Tahun Ajaran' : 'Tahun Angkatan' }}</th>
                        <th class="text-center" style="width: 110px;">Status</th>
                        <th class="text-center pe-3" style="width: 150px;">Aksi</th>
                    </tr>
                    <!-- Head Table Kurikulum -->
                    <tr v-else-if="activeTab === 'kurikulum'">
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Sekolah / Asal</th>
                        <th>Nama Kurikulum</th>
                        <th>Tipe Penilaian</th>
                        <th class="text-center" style="width: 120px;">Status</th>
                        <th class="text-center pe-3" style="width: 150px;">Aksi</th>
                    </tr>
                    <!-- Head Table Generik Lainnya (Pendidikan, Jenjang, Jurusan, Mapel, Program Pengajaran) -->
                    <tr v-else>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th v-if="userRole === 'super_admin'">Sekolah</th>
                        <th>Kode</th>
                        <th>Nama {{ getActiveTabName() }}</th>
                        <th class="text-center" style="width: 110px;">Status</th>
                        <th class="text-center pe-3" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                
                <tbody>
                    <!-- Loop Data Kelas -->
                    <template v-if="activeTab === 'kelas'">
                        <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                            <td class="text-center text-muted font-medium fs-8">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td v-if="userRole === 'super_admin'" class="fw-semibold text-slate-700 fs-8">{{ item.nama_sekolah || '-' }}</td>
                            <td>
                                <span class="badge bg-slate-100 text-blue-700 border border-slate-200 font-monospace px-2.5 py-1 fs-8 rounded-lg">
                                    {{ item.kode_kelas }}
                                </span>
                            </td>
                            <td class="fw-bold text-slate-900 fs-8">{{ item.nama_kelas }}</td>
                            <td class="text-slate-600 fs-8">{{ item.nama_jenjang || '-' }}</td>
                            <td class="text-slate-600 fs-8">{{ item.nama_jurusan || '-' }}</td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block" v-if="!trashMode">
                                    <input :id="'status_switch_kelas_' + item.id" :name="'status_switch_kelas_' + item.id" :aria-label="'Ubah status aktif kelas ' + item.nama_kelas" class="form-check-input" type="checkbox" role="switch" 
                                           :checked="item.is_active == 1" @change="toggleStatus(item.id)">
                                </div>
                                <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1.5" v-if="!trashMode">
                                    <button class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-lg px-2 py-1 fs-8 hover:bg-slate-100" @click="openEditModal(item)" title="Edit">
                                        <i class="bi bi-pencil-square text-blue-600 me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-light border border-slate-200 text-danger rounded-lg px-2 py-1 fs-8 hover:bg-danger-subtle" @click="deleteItem(item.id)" title="Hapus">
                                        <i class="bi bi-trash3 text-danger me-1"></i>Hapus
                                    </button>
                                </div>
                                <div class="d-inline-flex gap-1.5" v-else>
                                    <button class="btn btn-sm btn-success text-white rounded-lg px-2.5 py-1 fs-8" @click="restoreItem(item.id)">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Loop Data Tahun Ajaran & Angkatan -->
                    <template v-else-if="activeTab === 'tahun_ajaran' || activeTab === 'angkatan'">
                        <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                            <td class="text-center text-muted font-medium fs-8">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td v-if="userRole === 'super_admin'" class="fw-semibold text-slate-700 fs-8">{{ item.nama_sekolah || '-' }}</td>
                            <td class="fw-bold text-slate-900 font-monospace fs-8">
                                {{ activeTab === 'tahun_ajaran' ? (item.tahun_ajaran || item.nama_tahun_ajaran || item.nama) : (item.tahun_angkatan || item.nama_angkatan || item.nama) }}
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block" v-if="!trashMode">
                                    <input :id="'status_switch_ta_' + item.id" :name="'status_switch_ta_' + item.id" :aria-label="'Ubah status aktif ' + (activeTab === 'tahun_ajaran' ? item.tahun_ajaran : item.tahun_angkatan)" class="form-check-input" type="checkbox" role="switch" 
                                           :checked="item.is_active == 1" @change="toggleStatus(item.id)">
                                </div>
                                <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1.5" v-if="!trashMode">
                                    <button class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-lg px-2 py-1 fs-8 hover:bg-slate-100" @click="openEditModal(item)" title="Edit">
                                        <i class="bi bi-pencil-square text-blue-600 me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-light border border-slate-200 text-danger rounded-lg px-2 py-1 fs-8 hover:bg-danger-subtle" @click="deleteItem(item.id)" title="Hapus">
                                        <i class="bi bi-trash3 text-danger me-1"></i>Hapus
                                    </button>
                                </div>
                                <div class="d-inline-flex gap-1.5" v-else>
                                    <button class="btn btn-sm btn-success text-white rounded-lg px-2.5 py-1 fs-8" @click="restoreItem(item.id)">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Loop Data Kurikulum -->
                    <template v-else-if="activeTab === 'kurikulum'">
                        <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                            <td class="text-center text-muted font-medium fs-8">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td class="text-slate-600 fs-8 font-medium">
                                {{ isSystemItem(item) ? 'Sistem (Pemerintah)' : (item.nama_sekolah || 'Sistem (Pemerintah)') }}
                            </td>
                            <td class="fw-bold text-slate-900 fs-8">
                                {{ getKurikulumTitle(item) }}
                                <span v-if="isSystemItem(item)" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-9 ms-1.5 px-2 py-0.5">
                                    <i class="bi bi-shield-fill-check me-0.5"></i>Nasional
                                </span>
                                <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill fs-9 ms-1.5 px-2 py-0.5">
                                    <i class="bi bi-building me-0.5"></i>Kustom
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-2.5 py-1 fs-8 font-semibold" :class="getKurikulumCategoryClass(item)">
                                    {{ getKurikulumCategoryLabel(item) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block" v-if="!trashMode && !isSystemItem(item)">
                                    <input :id="'status_switch_kur_' + item.id" :name="'status_switch_kur_' + item.id" :aria-label="'Ubah status aktif kurikulum ' + getKurikulumTitle(item)" class="form-check-input" type="checkbox" role="switch" 
                                           :checked="item.is_active == 1" @change="toggleStatus(item.id)">
                                </div>
                                <span v-else-if="isSystemItem(item)" class="badge text-white rounded-pill px-2.5 py-1 fs-8 font-bold" style="background-color: #0f766e !important;">Aktif Bawaan</span>
                                <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1.5 align-items-center" v-if="!trashMode">
                                    <template v-if="isSystemItem(item)">
                                        <span class="text-muted fs-8 font-medium d-inline-flex align-items-center gap-1"><i class="bi bi-lock-fill"></i> Terkunci</span>
                                    </template>
                                    <template v-else>
                                        <button class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-lg px-2 py-1 fs-8 hover:bg-slate-100" @click="openEditModal(item)" title="Edit">
                                            <i class="bi bi-pencil-square text-blue-600 me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-light border border-slate-200 text-danger rounded-lg px-2 py-1 fs-8 hover:bg-danger-subtle" @click="deleteItem(item.id)" title="Hapus">
                                            <i class="bi bi-trash3 text-danger me-1"></i>Hapus
                                        </button>
                                    </template>
                                </div>
                                <div class="d-inline-flex gap-1.5" v-else>
                                    <button class="btn btn-sm btn-success text-white rounded-lg px-2.5 py-1 fs-8" @click="restoreItem(item.id)" v-if="!isSystemItem(item)">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                    </button>
                                    <span v-else class="text-muted fs-8">-</span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Loop Data Generik Lainnya (Pendidikan, Jenjang, Jurusan, Mapel, Program Pengajaran) -->
                    <template v-else>
                        <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                            <td class="text-center text-muted font-medium fs-8">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td v-if="userRole === 'super_admin'" class="fw-semibold text-slate-700 fs-8">{{ item.nama_sekolah || '-' }}</td>
                            <td>
                                <span class="badge bg-slate-100 text-blue-700 border border-slate-200 font-monospace px-2.5 py-1 fs-8 rounded-lg">
                                    {{ getField(item, 'kode') }}
                                </span>
                            </td>
                            <td class="fw-bold text-slate-900 fs-8">{{ getField(item, 'nama') }}</td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block" v-if="!trashMode">
                                    <input :id="'status_switch_gen_' + item.id" :name="'status_switch_gen_' + item.id" :aria-label="'Ubah status aktif ' + (getField(item, 'nama') || getField(item, 'kode'))" class="form-check-input" type="checkbox" role="switch" 
                                           :checked="item.is_active == 1" @change="toggleStatus(item.id)">
                                </div>
                                <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1.5" v-if="!trashMode">
                                    <button class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-lg px-2 py-1 fs-8 hover:bg-slate-100" @click="openEditModal(item)" title="Edit">
                                        <i class="bi bi-pencil-square text-blue-600 me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-light border border-slate-200 text-danger rounded-lg px-2 py-1 fs-8 hover:bg-danger-subtle" @click="deleteItem(item.id)" title="Hapus">
                                        <i class="bi bi-trash3 text-danger me-1"></i>Hapus
                                    </button>
                                </div>
                                <div class="d-inline-flex gap-1.5" v-else>
                                    <button class="btn btn-sm btn-success text-white rounded-lg px-2.5 py-1 fs-8" @click="restoreItem(item.id)">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr v-if="listData.length === 0">
                        <td :colspan="userRole === 'super_admin' ? 8 : 7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-slate-400 opacity-60"></i>
                            <h6 class="fw-bold text-slate-800 mb-1">Belum ada data {{ getActiveTabName() }}</h6>
                            <div class="fs-8 text-slate-500 mb-2 mx-auto" style="max-width: 560px;">
                                <template v-if="activeTab === 'jenjang'">
                                    <strong>Pendidikan (Bentuk Pendidikan):</strong> SMA, SMK, SMP, SD, MA, MTs, MI.<br>
                                    <strong>Jenjang (Tingkat Kelas):</strong> 7, 8, 9, 10, 11, 12.
                                </template>
                                <template v-else-if="activeTab === 'jurusan'">
                                    <strong>SMA / MA:</strong> Umum, IPA (MIPA), IPS, Bahasa &amp; Budaya.<br>
                                    <strong>SMK / MAK:</strong> TKJ, RPL, AKL, DKV, TKR, TBSM.
                                </template>
                                <template v-else-if="activeTab === 'kelas'">
                                    <strong>Contoh Rombel Kelas:</strong> VII A, VIII B, IX C, X IPA 1, XI TKJ 1, XII DKV 1.
                                </template>
                                <template v-else-if="activeTab === 'program_pengajaran'">
                                    <p class="mb-2">Silakan klik tombol <strong>"Tambah Program Pengajaran"</strong> di atas untuk menambahkan data baru.</p>
                                    <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl text-start font-monospace fs-8 text-slate-700">
                                        <div class="fw-bold text-primary mb-1">Contoh data Program Pengajaran:</div>
                                        <div>• <code>PROG-REG-01</code> : Program Pengajaran Reguler 5 Hari Kerja</div>
                                        <div>• <code>PROG-REG-02</code> : Program Pengajaran Reguler 6 Hari Kerja</div>
                                        <div>• <code>PROG-VOK-01</code> : Program Pengajaran Vokasi &amp; Kelas Industri</div>
                                        <div>• <code>PROG-BIL-01</code> : Program Pengajaran Kelas Bilingual (Bahasa Inggris)</div>
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

        <!-- 6. Bottom Pagination Toolbar -->
        <div class="pt-4 mt-2 border-top border-slate-100 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3" v-if="total > 0">
            <div class="d-flex align-items-center gap-2 flex-wrap text-slate-500 fs-8 font-medium">
                <span>Menampilkan <strong class="text-slate-800">{{ from }}</strong> s.d. <strong class="text-slate-800">{{ to }}</strong> dari <strong class="text-slate-800">{{ total }}</strong> baris</span>
                <div class="d-flex align-items-center gap-1.5 ms-md-3">
                    <label for="mk-perpage" class="fs-8 text-slate-500 mb-0">Tampilkan:</label>
                    <select id="mk-perpage" name="mk_perpage" class="form-select perpage-select shadow-2xs" v-model="perPage" @change="fetchData(1)">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="80">80</option>
                        <option :value="120">120</option>
                    </select>
                    <span class="fs-8 text-slate-500">per hal</span>
                </div>
            </div>
            <nav v-if="totalPages > 1" aria-label="Navigasi Halaman Data Master">
                <ul class="pagination pagination-sm pagination-modern m-0">
                    <li class="page-item" :class="{disabled: currentPage === 1}">
                        <a class="page-link" href="#" @click.prevent="fetchData(currentPage - 1)" title="Sebelumnya">&laquo;</a>
                    </li>
                    <li class="page-item" v-for="page in totalPages" :key="page" :class="{active: page === currentPage}">
                        <a class="page-link" href="#" @click.prevent="fetchData(page)">{{ page }}</a>
                    </li>
                    <li class="page-item" :class="{disabled: currentPage === totalPages}">
                        <a class="page-link" href="#" @click.prevent="fetchData(currentPage + 1)" title="Berikutnya">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>

    </div>

    <!-- 7. Reusable Form Modal (Tambah / Edit dengan Sticky Header/Footer & Clean Section) -->
    <div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                
                <div class="modal-header border-bottom py-3 bg-white sticky-top">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-blue-50 text-blue-600 rounded-xl p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi" :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-slate-900 fs-6 mb-0">
                                {{ isEditMode ? 'Edit ' + getActiveTabName() : 'Tambah ' + getActiveTabName() }}
                            </h5>
                            <span class="text-slate-500 fs-9">Lengkapi parameter form di bawah dengan valid.</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form @submit.prevent="submitForm">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            
                            <!-- Input Sekolah khusus Super Admin -->
                            <div class="col-12" v-if="userRole === 'super_admin'">
                                <div class="form-section-card">
                                    <div class="form-section-title text-blue-700">
                                        <i class="bi bi-building"></i> Instansi Sekolah / Tenant
                                    </div>
                                    <label for="form_tenant_id" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Pilih Sekolah <span class="text-danger">*</span></label>
                                    <select id="form_tenant_id" name="tenant_id" class="form-select rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.tenant_id}" v-model="form.tenant_id" :disabled="isEditMode" @change="onTenantChange" required>
                                        <option value="" disabled>-- Pilih Sekolah --</option>
                                        <option v-for="t in listTenants" :value="t.id" :key="t.id">{{ t.nama_sekolah }}</option>
                                    </select>
                                    <div class="invalid-feedback">{{ getError('tenant_id') }}</div>
                                </div>
                            </div>
                            
                            <!-- Form inputs khusus modul KELAS -->
                            <template v-if="activeTab === 'kelas'">
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title text-blue-700">
                                            <i class="bi bi-mortarboard"></i> Identitas Rombel Kelas
                                        </div>
                                        <div class="mb-3">
                                            <label for="form_id_jenjang" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Bentuk Pendidikan / Jenjang <span class="text-danger">*</span></label>
                                            <select id="form_id_jenjang" name="id_jenjang" class="form-select rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.id_jenjang}" v-model="form.id_jenjang" required>
                                                <option value="" disabled>-- Pilih Bentuk Pendidikan (SMA/SMK/SMP/SD) --</option>
                                                <option v-for="j in listJenjang" :value="j.id" :key="j.id">{{ j.nama }}</option>
                                            </select>
                                            <div class="invalid-feedback">{{ getError('id_jenjang') }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="form_id_jurusan" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jurusan / Program Keahlian <span class="text-danger">*</span></label>
                                            <select id="form_id_jurusan" name="id_jurusan" class="form-select rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.id_jurusan}" v-model="form.id_jurusan" required>
                                                <option value="" disabled>-- Pilih Jurusan (IPA/IPS/TKJ/RPL/AKL/Umum) --</option>
                                                <option v-for="j in listJurusan" :value="j.id" :key="j.id">{{ j.nama }}</option>
                                            </select>
                                            <div class="invalid-feedback">{{ getError('id_jurusan') }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="form_kode_kelas" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Kode Kelas <span class="text-danger">*</span></label>
                                            <input id="form_kode_kelas" name="kode_kelas" type="text" class="form-control rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.kode_kelas}" v-model="form.kode_kelas" placeholder="Contoh: KLS-XIPA1, KLS-XRPL1, KLS-7A" required>
                                            <div class="invalid-feedback">{{ getError('kode_kelas') }}</div>
                                        </div>
                                        <div>
                                            <label for="form_nama_kelas" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nama Kelas / Rombel <span class="text-danger">*</span></label>
                                            <input id="form_nama_kelas" name="nama_kelas" type="text" class="form-control rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.nama_kelas}" v-model="form.nama_kelas" placeholder="Contoh: VII A, X IPA 1, XI TKJ 1, XII DKV 1" required>
                                            <div class="invalid-feedback">{{ getError('nama_kelas') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Form inputs khusus Tahun Ajaran -->
                            <template v-else-if="activeTab === 'tahun_ajaran'">
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title text-blue-700">
                                            <i class="bi bi-calendar-check"></i> Parameter Tahun Ajaran
                                        </div>
                                        <label for="form_tahun_ajaran" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Tahun Ajaran <span class="text-danger">*</span></label>
                                        <input id="form_tahun_ajaran" name="kode" type="text" class="form-control rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.kode}" v-model="form.kode" placeholder="Contoh: 2025/2026" required>
                                        <div class="invalid-feedback">{{ getError('kode') }}</div>
                                        <small class="text-slate-500 fs-9 mt-1 d-block">Format standar: <strong>YYYY/YYYY</strong> (misal: 2025/2026).</small>
                                    </div>
                                </div>
                            </template>

                            <!-- Form inputs khusus Angkatan -->
                            <template v-else-if="activeTab === 'angkatan'">
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title text-blue-700">
                                            <i class="bi bi-calendar2-range"></i> Parameter Tahun Angkatan
                                        </div>
                                        <label for="form_tahun_angkatan" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Tahun Angkatan Masuk <span class="text-danger">*</span></label>
                                        <input id="form_tahun_angkatan" name="kode" type="text" class="form-control rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.kode}" v-model="form.kode" placeholder="Contoh: 2026" required>
                                        <div class="invalid-feedback">{{ getError('kode') }}</div>
                                        <small class="text-slate-500 fs-9 mt-1 d-block">Masukkan 4 digit tahun masuk peserta didik.</small>
                                    </div>
                                </div>
                            </template>

                            <!-- Form inputs khusus Kurikulum -->
                            <template v-else-if="activeTab === 'kurikulum'">
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title text-blue-700">
                                            <i class="bi bi-gear-wide-connected"></i> Pengaturan Kurikulum &amp; Penilaian
                                        </div>
                                        <div class="mb-3">
                                            <label for="form_kurikulum_nama" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nama Kurikulum <span class="text-danger">*</span></label>
                                            <input id="form_kurikulum_nama" name="nama_kurikulum" type="text" class="form-control rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.nama}" v-model="form.nama_kurikulum" placeholder="Contoh: Kurikulum Merdeka, K-13 Revisi" required>
                                            <div class="invalid-feedback">{{ getError('nama') }}</div>
                                        </div>
                                        <div>
                                            <label for="form_kurikulum_tipe" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Tipe Penilaian / Rapor <span class="text-danger">*</span></label>
                                            <select id="form_kurikulum_tipe" name="tipe_penilaian" class="form-select rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.tipe_penilaian}" v-model="form.tipe_penilaian" required>
                                                <option value="" disabled>-- Pilih Tipe Penilaian --</option>
                                                <option value="sederhana">Sederhana (Merdeka - Nilai Akhir &amp; Deskripsi Capaian)</option>
                                                <option value="klasik">Klasik (KTSP - Kognitif, Psikomotorik, Afektif)</option>
                                                <option value="kompleks">Kompleks (K-13 - Pengetahuan KI-3 &amp; Keterampilan KI-4)</option>
                                            </select>
                                            <div class="invalid-feedback">{{ getError('tipe_penilaian') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Form inputs generik (Pendidikan, Jenjang, Jurusan, Mapel, Program Pengajaran) -->
                            <template v-else>
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title text-blue-700">
                                            <i class="bi bi-pencil"></i> Parameter Data {{ getActiveTabName() }}
                                        </div>
                                        <div class="mb-3">
                                            <label for="form_generik_kode" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Kode {{ getActiveTabName() }} <span class="text-danger">*</span></label>
                                            <input id="form_generik_kode" name="kode" type="text" class="form-control rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.kode}" v-model="form.kode"
                                                   :placeholder="activeTab === 'jenjang' ? 'Contoh Kode: SMA, SMK, SMP, SD, MA' : activeTab === 'jurusan' ? 'Contoh Kode: IPA, IPS, RPL, TKJ, AKL' : 'Masukkan kode unik...'" required>
                                            <div class="invalid-feedback">{{ getError('kode') }}</div>
                                            <small class="text-slate-500 fs-9 mt-1 d-block" v-if="activeTab === 'jenjang'">Singkatan Bentuk Pendidikan (misal: SMA, SMK, SMP, SD, MA, MTS).</small>
                                            <small class="text-slate-500 fs-9 mt-1 d-block" v-else-if="activeTab === 'jurusan'">Singkatan Jurusan (misal: IPA, IPS, TKJ, RPL, AKL, DKV).</small>
                                        </div>
                                        <div>
                                            <label for="form_generik_nama" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nama Lengkap {{ getActiveTabName() }} <span class="text-danger">*</span></label>
                                            <input id="form_generik_nama" name="nama" type="text" class="form-control rounded-xl fs-8 font-medium" :class="{'is-invalid': errors.nama}" v-model="form.nama"
                                                   :placeholder="activeTab === 'jenjang' ? 'Contoh: Sekolah Menengah Atas, Sekolah Menengah Kejuruan' : activeTab === 'jurusan' ? 'Contoh: Ilmu Pengetahuan Alam, Teknik Komputer dan Jaringan' : 'Masukkan nama lengkap...'" required>
                                            <div class="invalid-feedback">{{ getError('nama') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                        </div>
                    </div>
                    
                    <div class="modal-footer border-top bg-slate-50 py-2.5 px-4 sticky-bottom">
                        <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl fs-8 px-3 font-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-xl fs-8 px-4 font-semibold shadow-2xs d-inline-flex align-items-center gap-1.5" :disabled="submitLoading">
                            <span v-if="submitLoading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <i v-else class="bi bi-check2-circle"></i>
                            <span>{{ isEditMode ? 'Perbarui Data' : 'Simpan Data' }}</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

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
                filterTenantId: '',
                listData: [],
                listJenjang: [],
                listJurusan: [],
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
        computed: {
            activeCount() {
                return (this.listData || []).filter(item => item.is_active == 1 || item.is_active === true).length;
            },
            systemCount() {
                return (this.listData || []).filter(item => this.isSystemItem(item)).length;
            }
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
                if (!this.filterTenantId) return 'Semua Sekolah (Global)';
                const t = (this.listTenants || []).find(t => t.id === this.filterTenantId);
                return t ? t.nama_sekolah : 'Semua Sekolah';
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
                         this.fetchData(this.currentPage);
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
