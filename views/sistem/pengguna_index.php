
<style>
    [v-cloak] { display: none !important; }
    .fs-9 { font-size: 0.725rem !important; }
    .fs-8 { font-size: 0.815rem !important; }
    .fs-7\.5 { font-size: 0.875rem !important; }

    /* Custom Table Styles */
    .pengguna-table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1050px;
    }
    .pengguna-table thead th {
        background: #f8fafc !important;
        border-bottom: 2px solid #e2e8f0 !important;
        font-weight: 700;
        font-size: 0.72rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #475569;
        padding: 0.85rem 0.75rem;
        white-space: nowrap;
    }
    .pengguna-table tbody td {
        padding: 0.85rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .pengguna-table tbody tr:hover td {
        background-color: #f8fafc !important;
    }

    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        flex-shrink: 0;
    }
    .bg-light-primary {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #dbeafe;
    }
    .bg-light-danger {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fee2e2;
    }

    .gender-badge-l {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-weight: 700;
        font-size: 0.72rem;
        padding: 2px 7px;
        border-radius: 6px;
    }
    .gender-badge-p {
        background: #fdf2f8;
        color: #be185d;
        border: 1px solid #fbcfe8;
        font-weight: 700;
        font-size: 0.72rem;
        padding: 2px 7px;
        border-radius: 6px;
    }

    .filter-card-modern {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
    }

    /* KPI Summary Cards */
    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        padding: 1.15rem 1.25rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        border-color: #cbd5e1;
    }
    .kpi-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
</style>

<!-- Halaman Sentral: Manajemen Pengguna -->
<div id="penggunaApp" v-cloak>
    
    <!-- Row Header & Modern Action Toolbar -->
    <div class="row mb-3 mb-md-4 align-items-center justify-content-between g-3">
        <div class="col-12 col-lg-6">
            <template v-if="userRole === 'siswa'">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-blue-600 text-white rounded-2xl d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px;">
                        <i class="bi bi-person-bounding-box fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-slate-900 mb-0.5 fs-4">Profil Data Diri</h3>
                        <p class="text-slate-500 fs-8 mb-0">Kelola dan lengkapi data induk kependidikan Anda secara mandiri.</p>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-blue-600 text-white rounded-2xl d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h3 class="fw-bold text-slate-900 mb-0 fs-4">Manajemen Pengguna</h3>
                            <span v-if="userRole === 'super_admin'" class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-1 fs-9 font-bold">
                                <i class="bi bi-shield-check text-blue-600 me-1"></i>Super Admin
                            </span>
                        </div>
                        <p class="text-slate-500 fs-8 mb-0 mt-0.5">Kelola data akademik dan non-akademik (Siswa, Guru, Staf, & Operator) secara terpusat.</p>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Grouped Action Buttons (Sleek & Space-Efficient) -->
        <div class="col-12 col-lg-6 d-flex gap-2 justify-content-start justify-content-lg-end align-items-center flex-wrap" v-if="userRole !== 'siswa' && activeTab !== 'profile_rapot' && activeTab !== 'naikkan_kelas'">
            <!-- Tombol Tong Sampah Toggle -->
            <button type="button"
                    class="btn btn-sm rounded-xl px-3 py-2 fs-8 font-semibold transition d-inline-flex align-items-center gap-1.5 shadow-2xs" 
                    @click="toggleTrashMode" 
                    v-if="activeTab !== 'mutasi'"
                    :class="trashMode ? 'btn-danger text-white' : 'btn-light border border-slate-200 text-slate-600 hover:bg-slate-100'"
                    :title="trashMode ? 'Kembali ke data aktif' : 'Lihat data di tong sampah'">
                <i class="bi" :class="trashMode ? 'bi-arrow-left-circle' : 'bi-trash3'"></i>
                <span>{{ trashMode ? 'Kembali' : 'Sampah' }}</span>
            </button>

            <!-- Dropdown Menu Opsi Data (Export / Import) -->
            <div class="dropdown" v-if="activeTab === 'siswa' && !trashMode">
                <button class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-xl px-3 py-2 fs-8 font-semibold dropdown-toggle shadow-2xs d-inline-flex align-items-center gap-1.5" 
                        type="button" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                    <i class="bi bi-box-arrow-in-down text-slate-500"></i>
                    <span>Opsi Data</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-md border border-slate-100 rounded-2xl py-2 fs-8">
                    <li>
                        <button class="dropdown-item py-2 px-3 d-flex align-items-center gap-2 text-slate-700" @click="downloadExcel">
                            <i class="bi bi-file-earmark-spreadsheet text-emerald-600 fs-6"></i> Download Excel (.xlsx)
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item py-2 px-3 d-flex align-items-center gap-2 text-slate-700" @click="openImportModal">
                            <i class="bi bi-cloud-arrow-up text-blue-600 fs-6"></i> Import Siswa dari Excel
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tombol Registrasi Cepat -->
            <button class="btn btn-sm btn-emerald-soft rounded-xl px-3 py-2 fs-8 font-semibold shadow-2xs text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition d-inline-flex align-items-center gap-1.5" 
                    @click="openQuickAddModal" 
                    v-if="activeTab === 'siswa' && !trashMode && userRole !== 'siswa' && userRole !== 'guru'">
                <i class="bi bi-lightning-charge-fill text-emerald-600"></i>
                <span>Registrasi Cepat</span>
            </button>

            <!-- Tombol Tambah Utama -->
            <button class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs d-inline-flex align-items-center gap-1.5 hover-lift" 
                    @click="openCreateModal" 
                    v-if="!trashMode && activeTab !== 'mutasi' && activeTab !== 'naikkan_kelas'">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah {{ getActiveTabName() }}</span>
            </button>
        </div>
    </div>

    <!-- Compact School Selector Banner (Khusus Super Admin) -->
    <div class="mb-4 p-3 px-md-4 rounded-2xl shadow-2xs border border-blue-100 bg-white" 
         v-if="userRole === 'super_admin' && listTenants.length > 0">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center flex-wrap gap-2.5">
                <span class="d-inline-flex align-items-center justify-content-center bg-blue-50 text-blue-600 rounded-xl" style="width: 34px; height: 34px;">
                    <i class="bi bi-building fs-6"></i>
                </span>
                <div>
                    <span class="fs-8 fw-bold text-slate-800 d-block leading-tight">Sekolah Terpilih:</span>
                    <span class="fs-9 text-slate-500">Filter data berdasarkan tenant sekolah</span>
                </div>
                
                <div class="ms-md-2 my-1 my-md-0">
                    <select id="top_filter_tenant_id" 
                            name="top_filter_tenant_id" 
                            class="form-select form-select-sm bg-slate-50 border-slate-200 rounded-xl text-slate-800 font-semibold shadow-2xs py-1.5" 
                            style="min-width: 260px; max-width: 360px; font-size: 0.85rem;" 
                            v-model="filterTenantId" 
                            @change="onFilterTenantChange">
                        <option value="">-- Semua Sekolah (Global SaaS) --</option>
                        <option v-for="t in listTenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded-pill px-3 py-1.5 fs-8 font-medium">
                    <i class="bi bi-check2-circle me-1"></i>{{ getSelectedTenantName() }}
                </span>
            </div>
        </div>
    </div>

    <!-- 4 KPI Metric Summary Cards (Live Data Ringkasan Cepat) -->
    <div class="row g-3 mb-4" v-if="activeTab !== 'naikkan_kelas' && activeTab !== 'profile_rapot'">
        <!-- Card 1: Total Data -->
        <div class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-9 font-bold text-slate-400 text-uppercase tracking-wider d-block mb-1">
                            Total {{ activeTab === 'siswa' ? 'Siswa' : getActiveTabName() }}
                        </span>
                        <h4 class="fw-bolder text-slate-900 mb-0 font-monospace">
                            {{ summaryStats.total ?? total ?? 0 }}
                        </h4>
                    </div>
                    <div class="kpi-icon-box bg-blue-50 text-blue-600">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="mt-2.5 pt-2 border-top border-slate-100 fs-9 text-slate-500 d-flex align-items-center gap-1">
                    <i class="bi bi-layers text-blue-500"></i>
                    <span>{{ filterKelas ? 'Kelas Terfilter' : 'Seluruh Rombel' }}</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Laki-laki / Perempuan (Tab Siswa) atau Aktif -->
        <div class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-9 font-bold text-slate-400 text-uppercase tracking-wider d-block mb-1">
                            Rasio Gender (L/P)
                        </span>
                        <h4 class="fw-bolder text-slate-900 mb-0 font-monospace">
                            <span class="text-blue-600">{{ summaryStats.male ?? 0 }}</span> / <span class="text-pink-600">{{ summaryStats.female ?? 0 }}</span>
                        </h4>
                    </div>
                    <div class="kpi-icon-box bg-indigo-50 text-indigo-600">
                        <i class="bi bi-gender-ambiguous"></i>
                    </div>
                </div>
                <div class="mt-2.5 pt-2 border-top border-slate-100 fs-9 text-slate-500 d-flex align-items-center gap-1">
                    <span class="badge gender-badge-l" style="font-size:0.65rem;">L: {{ summaryStats.male ?? 0 }}</span>
                    <span class="badge gender-badge-p" style="font-size:0.65rem;">P: {{ summaryStats.female ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Status Aktif -->
        <div class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-9 font-bold text-slate-400 text-uppercase tracking-wider d-block mb-1">
                            Status Aktif
                        </span>
                        <h4 class="fw-bolder text-emerald-600 mb-0 font-monospace">
                            {{ summaryStats.active ?? total ?? 0 }}
                        </h4>
                    </div>
                    <div class="kpi-icon-box bg-emerald-50 text-emerald-600">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div class="mt-2.5 pt-2 border-top border-slate-100 fs-9 text-slate-500 d-flex align-items-center gap-1">
                    <i class="bi bi-patch-check-fill text-emerald-500"></i>
                    <span>Tervalidasi aktif di sistem</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Jenjang & Rombel Terdata -->
        <div class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-9 font-bold text-slate-400 text-uppercase tracking-wider d-block mb-1">
                            Tingkat Jenjang
                        </span>
                        <h4 class="fw-bolder text-slate-900 mb-0 font-monospace">
                            {{ listJenjang.length }} Jenjang
                        </h4>
                    </div>
                    <div class="kpi-icon-box bg-amber-50 text-amber-600">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                </div>
                <div class="mt-2.5 pt-2 border-top border-slate-100 fs-9 text-slate-500 d-flex align-items-center gap-1">
                    <i class="bi bi-grid-3x3 text-amber-500"></i>
                    <span>{{ listKelas.length }} Rombel terdaftar</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Modern SINTA SaaS -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('penggunaNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="penggunaNavTabs" role="tablist">
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
                    onclick="document.getElementById('penggunaNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Main Datatable Grid (disembunyikan saat tab aksi aktif) -->
    <div class="card border border-slate-200/80 shadow-xs rounded-3xl overflow-hidden mb-5 bg-white" v-if="activeTab !== 'naikkan_kelas' && activeTab !== 'profile_rapot'">
        <div class="card-body p-3 p-md-4">
            
            <!-- Horizontal Filter Form (Dinamis & Responsive Modern Card) -->
            <div class="mb-4 filter-card-modern p-3.5 p-md-4 shadow-2xs" v-if="activeTab === 'siswa' || activeTab === 'mutasi'">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center bg-blue-100 text-blue-700 rounded-lg shadow-2xs" style="width: 28px; height: 28px;">
                            <i class="bi bi-funnel-fill fs-8"></i>
                        </span>
                        <span class="fs-8 fw-bold text-slate-800 text-uppercase tracking-wider">
                            Filter Data {{ activeTab === 'siswa' ? 'Siswa' : 'Mutasi' }}
                        </span>
                        <span v-if="filterJenjang || filterKelas || filterStatus" class="badge bg-blue-50 text-blue-700 border border-blue-200 text-xs px-2.5 py-1 rounded-pill font-medium d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check-circle-fill text-blue-600 fs-9"></i> Filter Aktif
                        </span>
                    </div>
                    <button v-if="filterJenjang || filterKelas || filterStatus" 
                            type="button" 
                            @click="resetFilters" 
                            class="btn btn-sm btn-link text-slate-500 hover:text-rose-600 p-0 fs-8 text-decoration-none d-flex align-items-center gap-1">
                        <i class="bi bi-x-circle"></i> Reset Semua Filter
                    </button>
                </div>

                <form @submit.prevent="fetchData(1)">
                    <div class="row g-2.5 align-items-end">
                        <!-- Filter 1: Jenjang / Tingkat -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="filter_jenjang" class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Tingkat Jenjang</label>
                            <select id="filter_jenjang" 
                                    name="filter_jenjang" 
                                    class="form-select rounded-xl border border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs" 
                                    v-model="filterJenjang" 
                                    @change="onJenjangFilterChange">
                                <option value="">-- Semua Jenjang --</option>
                                <option v-for="j in listJenjang" :key="j.id" :value="j.id">{{ j.nama || j.nama_jenjang || j.kode_jenjang }}</option>
                            </select>
                        </div>

                        <!-- Filter 2: Kelas / Rombel -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="filter_kelas" class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Kelas / Rombel</label>
                            <select id="filter_kelas" 
                                    name="filter_kelas" 
                                    class="form-select rounded-xl border border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs" 
                                    v-model="filterKelas" 
                                    @change="fetchData(1)">
                                <option value="">-- Semua Kelas --</option>
                                <option v-for="k in filteredKelasList" :key="k.id" :value="k.id">{{ k.nama_kelas || k.nama }}</option>
                            </select>
                        </div>

                        <!-- Filter 3: Status Siswa (Hanya untuk tab siswa) -->
                        <div class="col-12 col-sm-6" :class="activeTab === 'siswa' ? 'col-md-3' : 'd-none'">
                            <label for="filter_status" class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Status Siswa</label>
                            <select id="filter_status" 
                                    name="filter_status" 
                                    class="form-select rounded-xl border border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs" 
                                    v-model="filterStatus" 
                                    @change="fetchData(1)">
                                <option value="">-- Semua Status --</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Lulus">Lulus</option>
                                <option value="Pindah">Pindah</option>
                                <option value="Keluar">Keluar</option>
                            </select>
                        </div>

                        <!-- Button Cari & Reset (Responsive Column) -->
                        <div class="col-12 col-sm-6" :class="activeTab === 'siswa' ? 'col-md-3' : 'col-md-6'">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary rounded-xl py-2 px-3.5 fs-8 font-semibold flex-grow-1 shadow-2xs d-flex align-items-center justify-content-center gap-1.5 hover-lift">
                                    <i class="bi bi-search"></i>
                                    <span>Cari</span>
                                </button>
                                <button type="button" 
                                        @click="resetFilters" 
                                        class="btn btn-light border border-slate-200 text-slate-600 rounded-xl py-2 px-3 fs-8 font-semibold shadow-2xs hover-lift d-flex align-items-center gap-1">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

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
                        <label for="search_input" class="visually-hidden">Cari data pengguna</label>
                        <input id="search_input" 
                               name="search" 
                               aria-label="Cari data pengguna" 
                               type="text" 
                               class="form-control bg-slate-50 border-slate-200 border-start-0 border-end-0 text-slate-800 fs-8 font-medium py-2 shadow-none focus:bg-white" 
                               placeholder="Cari nama, email, NISN, NIS..." 
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
                <table class="table table-hover align-middle mb-4 pengguna-table" style="font-size: 0.85rem;">
                    <thead>
                        <!-- Head Table Siswa -->
                        <tr v-if="activeTab === 'siswa'">
                            <th style="width: 50px;" class="text-center">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>Nama Lengkap</th>
                            <th>Jenjang</th>
                            <th>Kelas</th>
                            <th>NISN & NIS</th>
                            <th class="text-center" style="width: 70px;">L/P</th>
                            <th>TTL</th>
                            <th>Alamat</th>

                            <th>Kelengkapan Data</th>
                            <th class="text-center" style="width: 120px;">Status Siswa</th>
                            <th class="text-center pe-3" style="width: 160px;">Aksi</th>
                        </tr>
                        <!-- Head Table Mutasi -->
                        <tr v-else-if="activeTab === 'mutasi'">
                            <th style="width: 50px;" class="text-center">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>Nama Lengkap</th>
                            <th>Kelas</th>
                            <th>NISN & NIS</th>
                            <th>Keluar Karena</th>
                            <th>Tanggal Keluar</th>
                            <th>Alasan / Keterangan</th>
                            <th class="text-center pe-3" style="width: 160px;">Aksi</th>
                        </tr>
                        <!-- Head Table Staff (Guru, Karyawan, Operator) -->
                        <tr v-else>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>Nama Lengkap & NIP</th>
                            <th>Email & Kontak</th>
                            <th>Jenis GTK & Jabatan</th>
                            <th>Peran & Tugas Tambahan</th>
                            <th class="text-center" style="width: 110px;">Status</th>
                            <th class="text-center pe-3" style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <!-- Loop Data Siswa -->
                        <template v-if="activeTab === 'siswa'">
                            <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                                <td class="text-center text-muted font-monospace fs-9">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td v-if="userRole === 'super_admin'" class="fw-semibold text-secondary fs-8">{{ item.nama_sekolah || '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2.5 bg-light-primary fw-bold shadow-2xs">
                                            {{ getInitials(item.nama_lengkap) }}
                                        </div>
                                        <span class="fw-bold text-slate-800">{{ item.nama_lengkap }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ item.nama_jenjang || '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light-primary border">{{ item.nama_kelas || '-' }}</span>
                                </td>
                                <td>
                                    <div><small class="text-muted d-block">NISN: <span class="font-monospace text-dark fw-semibold">{{ item.nisn || '-' }}</span></small></div>
                                    <div><small class="text-muted">NIS: <span class="font-monospace text-dark">{{ item.nis || '-' }}</span></small></div>
                                </td>
                                <td class="text-center">
                                    <span :class="item.jenis_kelamin === 'L' ? 'gender-badge-l' : 'gender-badge-p'">
                                        {{ item.jenis_kelamin || '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.tempat_lahir || item.tanggal_lahir">
                                        {{ item.tempat_lahir || '-' }}, {{ formatDate(item.tanggal_lahir) }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 150px;" :title="item.alamat">
                                        {{ item.alamat || '-' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="d-flex flex-column" style="min-width: 120px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge" :class="item.persentase_kelengkapan < 50 ? 'bg-danger-subtle text-danger' : (item.persentase_kelengkapan < 100 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success')">
                                                {{ item.persentase_kelengkapan }}%
                                                <i v-if="item.persentase_kelengkapan == 100" class="bi bi-check-lg ms-0.5"></i>
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar rounded" 
                                                 role="progressbar" 
                                                 :aria-label="'Kelengkapan Profil ' + item.nama_lengkap"
                                                 :aria-valuenow="item.persentase_kelengkapan"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"
                                                 :style="{ width: item.persentase_kelengkapan + '%' }"
                                                 :class="item.persentase_kelengkapan < 50 ? 'bg-danger' : (item.persentase_kelengkapan < 100 ? 'bg-warning' : 'bg-success')">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span v-if="item.status === 'Aktif' || item.status_siswa === 'aktif' || item.status_siswa === 'Aktif'" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                                    <span v-else-if="item.status === 'Lulus' || item.status_siswa === 'lulus' || item.status_siswa === 'Lulus'" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Lulus</span>
                                    <span v-else-if="item.status === 'Pindah' || item.status_siswa === 'pindah' || item.status_siswa === 'Pindah'" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Pindah</span>
                                    <span v-else-if="item.status === 'Putus Sekolah' || item.status_siswa === 'putus_sekolah'" class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Putus Sekolah</span>
                                    <span v-else class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">{{ item.status || 'Aktif' }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2" v-if="!trashMode">
                                        <template v-if="userRole === 'siswa'">
                                            <a :href="'<?= $this->getBaseUrl() ?>/siswa/edit?id=' + item.id" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1 fs-8">
                                                <i class="bi bi-pencil-square me-1"></i>Lihat/Perbarui Data
                                            </a>
                                        </template>
                                        <template v-else-if="activeTab === 'siswa'">
                                            <a :href="'<?= $this->getBaseUrl() ?>/siswa/edit?id=' + item.id" class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1 fs-8">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1 fs-8" @click="deleteItem(item.id)">
                                                <i class="bi bi-trash3 me-1"></i>Hapus
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1 fs-8" @click="openEditModal(item)">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1 fs-8" @click="deleteItem(item.id)">
                                                <i class="bi bi-trash3 me-1"></i>Hapus
                                            </button>
                                        </template>
                                    </div>
                                    <div class="d-inline-flex gap-2" v-else>
                                        <button class="btn btn-sm btn-success text-white rounded-2 px-2 py-1 fs-8" @click="restoreItem(item.id)">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Loop Data Mutasi -->
                        <template v-else-if="activeTab === 'mutasi'">
                            <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td v-if="userRole === 'super_admin'" class="fw-semibold text-secondary fs-8">{{ item.nama_sekolah || '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2 bg-light-danger text-danger fw-bold">
                                            {{ getInitials(item.nama_lengkap) }}
                                        </div>
                                        <span class="fw-semibold text-dark">{{ item.nama_lengkap }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light-primary text-primary border">{{ item.nama_kelas || '-' }}</span>
                                </td>
                                <td>
                                    <div><small class="text-muted d-block">NISN: <span class="font-monospace text-dark">{{ item.nisn || '-' }}</span></small></div>
                                    <div><small class="text-muted">NIS: <span class="font-monospace text-dark">{{ item.nis || '-' }}</span></small></div>
                                </td>
                                <td>
                                    <span class="badge bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">{{ item.keluar_karena || 'Mutasi' }}</span>
                                </td>
                                <td>
                                    <span v-if="item.tanggal_keluar" class="fw-medium text-dark">{{ formatDate(item.tanggal_keluar) }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block text-secondary" style="max-width: 250px;" :title="item.alasan_keluar">
                                        {{ item.alasan_keluar || '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2" v-if="!trashMode">
                                        <a :href="'<?= $this->getBaseUrl() ?>/siswa/edit?id=' + item.id" class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1 fs-8">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1 fs-8" @click="deleteItem(item.id)">
                                            <i class="bi bi-trash3 me-1"></i>Hapus
                                        </button>
                                    </div>
                                    <div class="d-inline-flex gap-2" v-else>
                                        <button class="btn btn-sm btn-success text-white rounded-2 px-2 py-1 fs-8" @click="restoreItem(item.id)">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Loop Data Staff (Guru, Karyawan, Operator) -->
                        <template v-else>
                            <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td v-if="userRole === 'super_admin'" class="fw-semibold text-secondary fs-8">{{ item.nama_sekolah || '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2 bg-light-primary fw-bold">
                                            {{ getInitials(item.nama_lengkap) }}
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark d-block">{{ item.nama_lengkap }}</span>
                                            <small v-if="item.nip" class="text-muted font-monospace fs-9">NIP: {{ item.nip }}</small>
                                            <small v-else-if="item.nuptk" class="text-muted font-monospace fs-9">NUPTK: {{ item.nuptk }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><a :href="'mailto:'+item.email" class="text-decoration-none font-monospace fs-8">{{ item.email }}</a></div>
                                    <small v-if="item.no_hp" class="text-muted fs-9"><i class="bi bi-telephone me-1"></i>{{ item.no_hp }}</small>
                                </td>
                                <td>
                                    <div><span class="badge bg-light text-dark border">{{ item.jenis_gtk || (activeTab === 'guru' ? 'Guru' : 'Tenaga Kependidikan') }}</span></div>
                                    <small v-if="item.jabatan_struktural" class="text-primary fw-medium d-block mt-0.5 fs-9">{{ item.jabatan_struktural }}</small>
                                    <small v-if="item.status_kepegawaian" class="badge bg-secondary-subtle text-secondary border mt-0.5 fs-9">{{ item.status_kepegawaian }}</small>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-capitalize px-2 py-1 fs-9">
                                            {{ item.nama_role === 'operator_sekolah' ? 'Operator' : item.nama_role }}
                                        </span>
                                        <span v-for="sec in (item.secondary_roles || [])" :key="sec.id" class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-capitalize px-2 py-1 fs-9">
                                            {{ sec.deskripsi || sec.nama_role }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block" v-if="!trashMode">
                                        <input :id="'status_switch_' + item.id" :name="'status_switch_' + item.id" aria-label="Ubah status aktif pengguna" class="form-check-input" type="checkbox" role="switch" 
                                               :checked="item.is_active" @change="toggleStatus(item.id)">
                                    </div>
                                    <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2" v-if="!trashMode">
                                        <button class="btn btn-sm btn-warning text-dark fw-semibold rounded-2 px-2 py-1 fs-8" @click="openUserAccessModal(item)" title="Hak Akses Khusus">
                                            <i class="bi bi-key-fill me-1"></i>Akses
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1 fs-8" @click="openEditModal(item)">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1 fs-8" @click="deleteItem(item.id)">
                                            <i class="bi bi-trash3 me-1"></i>Hapus
                                        </button>
                                    </div>
                                    <div class="d-inline-flex gap-2" v-else>
                                        <button class="btn btn-sm btn-success text-white rounded-2 px-2 py-1 fs-8" @click="restoreItem(item.id)">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr v-if="listData.length === 0">
                            <td :colspan="userRole === 'super_admin' ? (activeTab === 'siswa' ? 14 : 7) : (userRole === 'siswa' ? 12 : (activeTab === 'siswa' ? 13 : 6))" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                <span>Tidak ada data ditemukan dalam kategori {{ getActiveTabName() }}.</span>
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
                        <label for="main-perpage" class="fs-8 text-muted mb-0">Tampilkan:</label>
                        <select id="main-perpage" name="main_perpage" class="form-select form-select-sm py-0 px-2 rounded-2 fs-8" style="width: auto; height: 28px;" v-model="perPage" @change="fetchData(1)">
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
                        <li class="page-item" v-for="(page, index) in paginationPages" :key="index" :class="{active: page === currentPage, disabled: page === '...'}">
                            <a class="page-link" href="#" @click.prevent="page !== '...' && fetchData(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === totalPages}">
                            <a class="page-link" href="#" @click.prevent="fetchData(currentPage + 1)">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>

    <!-- Modal Import Siswa -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-file-earmark-excel text-success me-2"></i>Import Siswa via Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitImport">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="import_csv_file" class="form-label fw-semibold fs-8 text-muted mb-0">Pilih File Excel (.xlsx) <span class="text-danger">*</span></label>
                                <a href="<?= $this->getBaseUrl() ?>/api/v1/siswa/import/template" class="text-decoration-none fs-9 fw-bold text-success" download>
                                    <i class="bi bi-download me-1"></i>Download Template Excel
                                </a>
                            </div>
                            <input id="import_csv_file" name="import_csv_file" type="file" class="form-control rounded-3" ref="importFile" accept=".xlsx" @change="handleImportFileChange" required>
                        </div>
                        <div class="alert alert-info rounded-3 py-2.5 px-3 fs-9 mb-0">
                            <h6 class="fw-bold fs-9 mb-1"><i class="bi bi-info-circle me-1"></i>Petunjuk Impor:</h6>
                            <ul class="ps-3 mb-0" style="list-style-type: decimal;">
                                <li>Pastikan file berformat <strong>.xlsx</strong> (Excel).</li>
                                <li>Baris pertama harus berisi kolom:
                                    <code class="d-block bg-light p-1 my-1 border rounded text-dark font-monospace">NPSN Sekolah, Nama Lengkap Siswa, NISN, Tanggal Lahir, Email</code>
                                </li>
                                <li>Format Tanggal Lahir wajib <strong>YYYY-MM-DD</strong>.</li>
                                <li>Kolom <strong>Email</strong> wajib berisi alamat email aktif dan valid untuk akses login siswa.</li>
                                <li v-if="userRole === 'super_admin'">Kolom <strong>NPSN Sekolah</strong> wajib diisi valid sesuai data sekolah.</li>
                                <li v-else>Kolom <strong>NPSN Sekolah</strong> diabaikan dan otomatis dikaitkan ke sekolah login Admin Anda.</li>
                                <li>Password default login siswa baru adalah <strong>Tanggal Lahir</strong> masing-masing.</li>
                            </ul>
                        </div>
                        
                        <!-- List error detail -->
                        <div v-if="importErrors.length > 0" class="mt-3 bg-danger-subtle text-danger border border-danger-subtle p-3 rounded-3 fs-9" style="max-height: 150px; overflow-y: auto;">
                            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle"></i> Gagal Validasi Baris Data:</h6>
                            <ul class="ps-3 mb-0">
                                <li v-for="err in importErrors">{{ err }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-3 fs-8 px-3" data-bs-dismiss="modal" :disabled="importLoading">Batal</button>
                        <button type="submit" class="btn btn-success rounded-3 fs-8 px-3 d-flex align-items-center gap-1.5" :disabled="importLoading">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="importLoading"></span>
                            <i class="bi bi-cloud-arrow-up" v-else></i>
                            Mulai Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reusable Form Modal (Siswa & Staff Dynamic Modal) -->
    <div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                
                <div class="modal-header border-bottom py-3 bg-white sticky-top">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon-box bg-blue-50 text-blue-600" style="width:36px; height:36px; font-size:1.1rem;">
                            <i class="bi" :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-slate-900 mb-0 fs-6">
                                {{ isEditMode ? 'Edit ' + getActiveTabName() : 'Tambah ' + getActiveTabName() }}
                            </h5>
                            <span class="fs-9 text-slate-500">Lengkapi data formulir di bawah ini dengan valid.</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form @submit.prevent="submitForm">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            
                            <!-- Input Sekolah khusus Super Admin -->
                            <div class="col-12" v-if="userRole === 'super_admin'">
                                <div class="p-3 bg-blue-50/60 border border-blue-200 rounded-3">
                                    <label for="form_tenant_id" class="form-label fw-bold fs-8 text-blue-900 mb-1">
                                        <i class="bi bi-buildings-fill me-1 text-blue-600"></i>Sekolah / Tenant <span class="text-danger">*</span>
                                    </label>
                                    <select id="form_tenant_id" name="tenant_id" class="form-select form-select-sm bg-white rounded-3 shadow-2xs font-semibold" :class="{'is-invalid': errors.tenant_id}" v-model="form.tenant_id" :disabled="isEditMode" required>
                                        <option value="" disabled>-- Pilih Sekolah Tujuan --</option>
                                        <option v-for="t in listTenants" :value="t.id" :key="t.id">{{ t.nama_sekolah }}</option>
                                    </select>
                                    <div class="invalid-feedback">{{ getError('tenant_id') }}</div>
                                </div>
                            </div>
                            
                            <!-- ======================================================= -->
                            <!-- 1. FORM KHUSUS GURU (activeTab === 'guru')               -->
                            <!-- ======================================================= -->
                            <template v-if="activeTab === 'guru'">
                                <!-- Section A: Identitas & Akun Login -->
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-person-circle text-blue-600"></i> Identitas & Akun Login Guru
                                        </div>
                                        <div class="row g-2.5">
                                            <div class="col-12">
                                                <label for="form_guru_nama" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                                <input id="form_guru_nama" name="nama_lengkap" type="text" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.nama_lengkap}" v-model="form.nama_lengkap" placeholder="Contoh: Drs. H. Ahmad Dahlan, M.Pd" required>
                                                <div class="invalid-feedback">{{ getError('nama_lengkap') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_guru_email" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Email Login <span class="text-danger">*</span></label>
                                                <input id="form_guru_email" name="email" type="email" class="form-control form-control-sm rounded-3 font-monospace" :class="{'is-invalid': errors.email}" v-model="form.email" placeholder="guru@sekolah.sch.id" required autocomplete="email">
                                                <div class="invalid-feedback">{{ getError('email') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_guru_password" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Password <span class="text-danger" v-if="!isEditMode">*</span></label>
                                                <input id="form_guru_password" name="password" type="password" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.password}" v-model="form.password" :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : 'Min. 6 karakter'" :required="!isEditMode" autocomplete="new-password">
                                                <div class="invalid-feedback">{{ getError('password') }}</div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <label for="form_guru_nip" class="form-label fw-semibold fs-8 text-slate-700 mb-1">NIP <small class="text-muted">(18 Digit)</small></label>
                                                <input id="form_guru_nip" name="nip" type="text" class="form-control form-control-sm rounded-3 font-monospace" :class="{'is-invalid': errors.nip}" v-model="form.nip" placeholder="198503152010011005">
                                                <div class="invalid-feedback">{{ getError('nip') }}</div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <label for="form_guru_nuptk" class="form-label fw-semibold fs-8 text-slate-700 mb-1">NUPTK <small class="text-muted">(16 Digit)</small></label>
                                                <input id="form_guru_nuptk" name="nuptk" type="text" class="form-control form-control-sm rounded-3 font-monospace" :class="{'is-invalid': errors.nuptk}" v-model="form.nuptk" placeholder="1234567890123456" maxlength="16">
                                                <div class="invalid-feedback">{{ getError('nuptk') }}</div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <label for="form_guru_jk" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jenis Kelamin</label>
                                                <select id="form_guru_jk" name="jenis_kelamin" class="form-select form-select-sm rounded-3" v-model="form.jenis_kelamin">
                                                    <option value="L">Laki-laki (L)</option>
                                                    <option value="P">Perempuan (P)</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label for="form_guru_nohp" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                                                <input id="form_guru_nohp" name="no_hp" type="text" class="form-control form-control-sm rounded-3 font-monospace" v-model="form.no_hp" placeholder="Contoh: 081234567890">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section B: Status Kepegawaian & Tugas Akademik -->
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-briefcase-fill text-blue-600"></i> Status Kepegawaian & Tugas Guru
                                        </div>
                                        <div class="row g-2.5">
                                            <div class="col-12 col-md-6">
                                                <label for="form_guru_jenis_gtk" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jenis GTK / Kategori Guru</label>
                                                <select id="form_guru_jenis_gtk" name="jenis_gtk" class="form-select form-select-sm rounded-3" v-model="form.jenis_gtk">
                                                    <option value="Guru Mata Pelajaran">Guru Mata Pelajaran</option>
                                                    <option value="Guru Bimbingan Konseling (BK)">Guru Bimbingan Konseling (BK)</option>
                                                    <option value="Guru Kelas">Guru Kelas</option>
                                                    <option value="Guru Pendamping Khusus">Guru Pendamping Khusus</option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_guru_kepegawaian" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Status Kepegawaian</label>
                                                <select id="form_guru_kepegawaian" name="status_kepegawaian" class="form-select form-select-sm rounded-3" v-model="form.status_kepegawaian">
                                                    <option value="PNS">PNS (Pegawai Negeri Sipil)</option>
                                                    <option value="PPPK">PPPK (P3K)</option>
                                                    <option value="GTY/PTY">GTY / PTY (Tetap Yayasan)</option>
                                                    <option value="GTT">GTT (Guru Tidak Tetap)</option>
                                                    <option value="Honorer Sekolah">Honorer Sekolah</option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_guru_jabatan" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jabatan Struktural Utama</label>
                                                <input id="form_guru_jabatan" name="jabatan_struktural" type="text" class="form-control form-control-sm rounded-3" v-model="form.jabatan_struktural" placeholder="Contoh: Kepala Sekolah / Waka Kurikulum">
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <label for="form_guru_jam" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jam Ajar / Minggu</label>
                                                <input id="form_guru_jam" name="jam_mengajar" type="number" min="0" max="60" class="form-control form-control-sm rounded-3 text-center" v-model="form.jam_mengajar" placeholder="0">
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <label class="form-label fw-semibold fs-8 text-slate-700 mb-1">Sertifikasi GTK</label>
                                                <div class="form-check form-switch pt-1">
                                                    <input id="form_guru_sertifikasi" name="status_sertifikasi" class="form-check-input" type="checkbox" role="switch" v-model="form.status_sertifikasi">
                                                    <label class="form-check-label fs-8 text-slate-700" for="form_guru_sertifikasi">{{ form.status_sertifikasi ? 'Sudah Sertifikasi' : 'Belum' }}</label>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label for="form_guru_alamat" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Alamat Tinggal</label>
                                                <textarea id="form_guru_alamat" name="alamat" class="form-control form-control-sm rounded-3" v-model="form.alamat" rows="2" placeholder="Alamat lengkap tempat tinggal guru"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section C: Penugasan Peran & Multi-Role Tambahan -->
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-shield-check text-blue-600"></i> Penugasan Peran & Tugas Tambahan (Multi-Role)
                                        </div>
                                        <p class="text-slate-500 fs-9 mb-2.5">Centang peran di bawah untuk mengaktifkan akses modul terkait pada akun Guru ini:</p>
                                        
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isWaliKelasCheckbox" name="is_wali_kelas" class="form-check-input" type="checkbox" v-model="form.is_wali_kelas">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isWaliKelasCheckbox">Wali Kelas</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Rekap absensi, rapor kelas, & pembinaan siswa.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isPembinaEkskulCheckbox" name="is_pembina_ekskul" class="form-check-input" type="checkbox" v-model="form.is_pembina_ekskul">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isPembinaEkskulCheckbox">Pembina Ekstrakurikuler</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Input nilai, absensi, dan jurnal kegiatan ekskul.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isBkCheckbox" name="is_bk" class="form-check-input" type="checkbox" v-model="form.is_bk">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isBkCheckbox">Guru BK (Bimbingan Konseling)</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Layanan BK, Kedisiplinan, & PDSS SNBP.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isKesiswaanCheckbox" name="is_kesiswaan" class="form-check-input" type="checkbox" v-model="form.is_kesiswaan">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isKesiswaanCheckbox">Staf / Waka Kesiswaan</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Kelola master ekskul, mutasi, dan kedisiplinan.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isKurikulumCheckbox" name="is_kurikulum" class="form-check-input" type="checkbox" v-model="form.is_kurikulum">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isKurikulumCheckbox">Staf / Waka Kurikulum</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Jadwal pelajaran, kurikulum, dan leger rapor.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isSarprasCheckbox" name="is_sarpras" class="form-check-input" type="checkbox" v-model="form.is_sarpras">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isSarprasCheckbox">Staf / Waka Sarpras</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Pendataan sarana, ruang kelas, dan inventaris.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isHumasCheckbox" name="is_humas" class="form-check-input" type="checkbox" v-model="form.is_humas">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isHumasCheckbox">Staf / Waka HUMAS</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Warta pengumuman publik, agenda, & berita.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isKeuanganCheckbox" name="is_keuangan" class="form-check-input" type="checkbox" v-model="form.is_keuangan">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isKeuanganCheckbox">Bendahara / Staf Keuangan</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Loket pembayaran, pos tarif, & laporan SPP.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="isPerpustakaanCheckbox" name="is_perpustakaan" class="form-check-input" type="checkbox" v-model="form.is_perpustakaan">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="isPerpustakaanCheckbox">Pengelola Perpustakaan</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Sirkulasi buku, katalog barcode, dan peminjaman.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- ======================================================= -->
                            <!-- 2. FORM KHUSUS KARYAWAN (activeTab === 'karyawan')       -->
                            <!-- ======================================================= -->
                            <template v-else-if="activeTab === 'karyawan'">
                                <!-- Section A: Identitas & Akun Login -->
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-person-lines-fill text-indigo-600"></i> Identitas & Akun Login Karyawan
                                        </div>
                                        <div class="row g-2.5">
                                            <div class="col-12">
                                                <label for="form_karyawan_nama" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                                <input id="form_karyawan_nama" name="nama_lengkap" type="text" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.nama_lengkap}" v-model="form.nama_lengkap" placeholder="Contoh: Siti Rahmawati, S.Kom" required>
                                                <div class="invalid-feedback">{{ getError('nama_lengkap') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_karyawan_email" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Email Login <span class="text-danger">*</span></label>
                                                <input id="form_karyawan_email" name="email" type="email" class="form-control form-control-sm rounded-3 font-monospace" :class="{'is-invalid': errors.email}" v-model="form.email" placeholder="staf@sekolah.sch.id" required autocomplete="email">
                                                <div class="invalid-feedback">{{ getError('email') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_karyawan_password" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Password <span class="text-danger" v-if="!isEditMode">*</span></label>
                                                <input id="form_karyawan_password" name="password" type="password" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.password}" v-model="form.password" :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : 'Min. 6 karakter'" :required="!isEditMode" autocomplete="new-password">
                                                <div class="invalid-feedback">{{ getError('password') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_karyawan_nip" class="form-label fw-semibold fs-8 text-slate-700 mb-1">NIP / NIY / No. Induk Pegawai</label>
                                                <input id="form_karyawan_nip" name="nip" type="text" class="form-control form-control-sm rounded-3 font-monospace" v-model="form.nip" placeholder="Contoh: 199008202022012001">
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_karyawan_jk" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jenis Kelamin</label>
                                                <select id="form_karyawan_jk" name="jenis_kelamin" class="form-select form-select-sm rounded-3" v-model="form.jenis_kelamin">
                                                    <option value="L">Laki-laki (L)</option>
                                                    <option value="P">Perempuan (P)</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label for="form_karyawan_nohp" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                                                <input id="form_karyawan_nohp" name="no_hp" type="text" class="form-control form-control-sm rounded-3 font-monospace" v-model="form.no_hp" placeholder="Contoh: 085712345678">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section B: Bidang Tugas & Kepegawaian -->
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-building-gear text-indigo-600"></i> Bidang Tugas & Kepegawaian Karyawan
                                        </div>
                                        <div class="row g-2.5">
                                            <div class="col-12 col-md-6">
                                                <label for="form_karyawan_bidang" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Kategori Bidang Staf</label>
                                                <select id="form_karyawan_bidang" name="jenis_gtk" class="form-select form-select-sm rounded-3" v-model="form.jenis_gtk">
                                                    <option value="Tata Usaha / Administrasi">Tata Usaha / Administrasi</option>
                                                    <option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
                                                    <option value="Laboran">Laboran</option>
                                                    <option value="Pustakawan">Pustakawan</option>
                                                    <option value="Keuangan & Kasir">Keuangan & Kasir</option>
                                                    <option value="Staf IT & Multimedia">Staf IT & Multimedia</option>
                                                    <option value="Keamanan / Security">Keamanan / Security</option>
                                                    <option value="Kebersihan / Janitor">Kebersihan / Janitor</option>
                                                    <option value="Bagian Umum">Bagian Umum</option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_karyawan_kepegawaian" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Status Kepegawaian</label>
                                                <select id="form_karyawan_kepegawaian" name="status_kepegawaian" class="form-select form-select-sm rounded-3" v-model="form.status_kepegawaian">
                                                    <option value="PNS">PNS (Pegawai Negeri Sipil)</option>
                                                    <option value="PPPK">PPPK (P3K)</option>
                                                    <option value="GTY/PTY">GTY / PTY (Tetap Yayasan)</option>
                                                    <option value="PTT">PTT (Pegawai Tidak Tetap)</option>
                                                    <option value="Honorer Sekolah">Honorer Sekolah</option>
                                                    <option value="Outsourcing">Outsourcing</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label for="form_karyawan_jabatan" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jabatan / Posisi Penugasan</label>
                                                <input id="form_karyawan_jabatan" name="jabatan_struktural" type="text" class="form-control form-control-sm rounded-3" v-model="form.jabatan_struktural" placeholder="Contoh: Kepala Tata Usaha / Petugas Sirkulasi Perpus">
                                            </div>

                                            <div class="col-12">
                                                <label for="form_karyawan_alamat" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Alamat Tinggal</label>
                                                <textarea id="form_karyawan_alamat" name="alamat" class="form-control form-control-sm rounded-3" v-model="form.alamat" rows="2" placeholder="Alamat tempat tinggal pegawai"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section C: Hak Akses Tambahan Staf -->
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-key-fill text-indigo-600"></i> Hak Akses Modul Operasional Tambahan
                                        </div>
                                        <p class="text-slate-500 fs-9 mb-2.5">Aktifkan hak akses ke modul khusus berikut jika staf ini ditugaskan mengelola sistem:</p>
                                        
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="karyawanPerpusCheckbox" name="is_perpustakaan" class="form-check-input" type="checkbox" v-model="form.is_perpustakaan">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="karyawanPerpusCheckbox">Pengelola Perpustakaan Digital</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Katalog buku, sirkulasi peminjaman, & denda.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="karyawanKeuanganCheckbox" name="is_keuangan" class="form-check-input" type="checkbox" v-model="form.is_keuangan">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="karyawanKeuanganCheckbox">Loket Keuangan / Kasir SPP</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Transaksi bayar, cetak kuitansi, & rekap tagihan.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="karyawanSarprasCheckbox" name="is_sarpras" class="form-check-input" type="checkbox" v-model="form.is_sarpras">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="karyawanSarprasCheckbox">Staf Pengelola Sarana & Prasarana</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Inventaris barang, ruang kelas, & aset gedung.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="p-2 border border-slate-200 rounded-3 bg-white">
                                                    <div class="form-check">
                                                        <input id="karyawanHumasCheckbox" name="is_humas" class="form-check-input" type="checkbox" v-model="form.is_humas">
                                                        <label class="form-check-label fw-semibold fs-8 text-dark" for="karyawanHumasCheckbox">Staf Publikasi & HUMAS</label>
                                                        <p class="text-slate-400 fs-9 mb-0">Pengumuman portal publik dan agenda kegiatan.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- ======================================================= -->
                            <!-- 3. FORM KHUSUS OPERATOR (activeTab === 'operator')       -->
                            <!-- ======================================================= -->
                            <template v-else-if="activeTab === 'operator'">
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-person-gear text-emerald-600"></i> Akun Administrator & Operator Sekolah
                                        </div>
                                        <div class="row g-2.5">
                                            <div class="col-12">
                                                <label for="form_operator_nama" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nama Lengkap Operator <span class="text-danger">*</span></label>
                                                <input id="form_operator_nama" name="nama_lengkap" type="text" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.nama_lengkap}" v-model="form.nama_lengkap" placeholder="Contoh: Budi Prasetyo, S.Kom" required>
                                                <div class="invalid-feedback">{{ getError('nama_lengkap') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_operator_email" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Email Login Utama <span class="text-danger">*</span></label>
                                                <input id="form_operator_email" name="email" type="email" class="form-control form-control-sm rounded-3 font-monospace" :class="{'is-invalid': errors.email}" v-model="form.email" placeholder="admin.operator@sekolah.sch.id" required autocomplete="email">
                                                <div class="invalid-feedback">{{ getError('email') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_operator_password" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Password <span class="text-danger" v-if="!isEditMode">*</span></label>
                                                <input id="form_operator_password" name="password" type="password" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.password}" v-model="form.password" :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : 'Min. 6 karakter'" :required="!isEditMode" autocomplete="new-password">
                                                <div class="invalid-feedback">{{ getError('password') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_operator_jk" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jenis Kelamin</label>
                                                <select id="form_operator_jk" name="jenis_kelamin" class="form-select form-select-sm rounded-3" v-model="form.jenis_kelamin">
                                                    <option value="L">Laki-laki (L)</option>
                                                    <option value="P">Perempuan (P)</option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_operator_nohp" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                                                <input id="form_operator_nohp" name="no_hp" type="text" class="form-control form-control-sm rounded-3 font-monospace" v-model="form.no_hp" placeholder="Contoh: 081298765432">
                                            </div>

                                            <div class="col-12">
                                                <label for="form_operator_jabatan" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Tanggung Jawab / Posisi Admin</label>
                                                <input id="form_operator_jabatan" name="jabatan_struktural" type="text" class="form-control form-control-sm rounded-3" v-model="form.jabatan_struktural" placeholder="Contoh: Operator Dapodik & SINTA SaaS / Tim IT Sekolah">
                                            </div>

                                            <div class="col-12">
                                                <label for="form_operator_alamat" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Alamat Lengkap</label>
                                                <textarea id="form_operator_alamat" name="alamat" class="form-control form-control-sm rounded-3" v-model="form.alamat" rows="2" placeholder="Alamat tinggal operator"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-3 d-flex align-items-start gap-2.5">
                                        <i class="bi bi-info-circle-fill text-emerald-600 fs-6 mt-0.5"></i>
                                        <div class="fs-9 text-emerald-900">
                                            <strong>Hak Akses Operator:</strong> Akun ini memiliki izin penuh untuk mengelola konfigurasi sekolah, manajemen pengguna (siswa, guru, staf), sinkronisasi data akademik, serta operasional sistem sekolah.
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- ======================================================= -->
                            <!-- 4. FORM KHUSUS SISWA & MUTASI                           -->
                            <!-- ======================================================= -->
                            <template v-else>
                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-mortarboard-fill text-blue-600"></i> Biodata Siswa
                                        </div>
                                        <div class="row g-2.5">
                                            <div class="col-12 col-md-6">
                                                <label for="form_nama_lengkap" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                                                <input id="form_nama_lengkap" name="nama_lengkap" type="text" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.nama_lengkap}" v-model="form.nama_lengkap" placeholder="Nama lengkap siswa" required>
                                                <div class="invalid-feedback">{{ getError('nama_lengkap') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_jenis_kelamin" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Jenis Kelamin <span class="text-danger">*</span></label>
                                                <select id="form_jenis_kelamin" name="jenis_kelamin" class="form-select form-select-sm rounded-3" :class="{'is-invalid': errors.jenis_kelamin}" v-model="form.jenis_kelamin" required>
                                                    <option value="L">Laki-laki (L)</option>
                                                    <option value="P">Perempuan (P)</option>
                                                </select>
                                                <div class="invalid-feedback">{{ getError('jenis_kelamin') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_nisn" class="form-label fw-semibold fs-8 text-slate-700 mb-1">NISN <small class="text-muted">(10 Digit)</small></label>
                                                <input id="form_nisn" name="nisn" type="text" class="form-control form-control-sm rounded-3 font-monospace" :class="{'is-invalid': errors.nisn}" v-model="form.nisn" placeholder="0054231901" maxlength="10">
                                                <div class="invalid-feedback">{{ getError('nisn') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_nis" class="form-label fw-semibold fs-8 text-slate-700 mb-1">NIS <small class="text-muted">(Nomor Induk Siswa)</small></label>
                                                <input id="form_nis" name="nis" type="text" class="form-control form-control-sm rounded-3 font-monospace" :class="{'is-invalid': errors.nis}" v-model="form.nis" placeholder="2026102">
                                                <div class="invalid-feedback">{{ getError('nis') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_tempat_lahir" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Tempat Lahir</label>
                                                <input id="form_tempat_lahir" name="tempat_lahir" type="text" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.tempat_lahir}" v-model="form.tempat_lahir" placeholder="Tempat lahir">
                                                <div class="invalid-feedback">{{ getError('tempat_lahir') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_tanggal_lahir" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Tanggal Lahir</label>
                                                <input id="form_tanggal_lahir" name="tanggal_lahir" type="date" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.tanggal_lahir}" v-model="form.tanggal_lahir">
                                                <div class="invalid-feedback">{{ getError('tanggal_lahir') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_nama_wali" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Nama Wali Siswa</label>
                                                <input id="form_nama_wali" name="nama_wali" type="text" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.nama_wali}" v-model="form.nama_wali" placeholder="Nama ayah/ibu/wali">
                                                <div class="invalid-feedback">{{ getError('nama_wali') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_kontak_wali" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Kontak Wali Siswa</label>
                                                <input id="form_kontak_wali" name="kontak_wali" type="text" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.kontak_wali}" v-model="form.kontak_wali" placeholder="No. HP / Kontak wali">
                                                <div class="invalid-feedback">{{ getError('kontak_wali') }}</div>
                                            </div>

                                            <div class="col-12">
                                                <label for="form_alamat" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Alamat Lengkap</label>
                                                <textarea id="form_alamat" name="alamat" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.alamat}" v-model="form.alamat" rows="2" placeholder="Alamat tinggal siswa"></textarea>
                                                <div class="invalid-feedback">{{ getError('alamat') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-section-card">
                                        <div class="form-section-title">
                                            <i class="bi bi-shield-lock-fill text-blue-600"></i> Akun Login Siswa (Opsional)
                                        </div>
                                        <p class="text-slate-500 fs-9 mb-2.5">Isi email & password di bawah jika ingin siswa memiliki akun login tersendiri:</p>
                                        
                                        <div class="row g-2.5">
                                            <div class="col-12 col-md-6">
                                                <label for="form_email" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Email Akun</label>
                                                <input id="form_email" name="email" type="email" class="form-control form-control-sm rounded-3 font-monospace" :class="{'is-invalid': errors.email}" v-model="form.email" placeholder="siswa@sekolah.sch.id" autocomplete="email">
                                                <div class="invalid-feedback">{{ getError('email') }}</div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label for="form_password" class="form-label fw-semibold fs-8 text-slate-700 mb-1">Password</label>
                                                <input id="form_password" name="password" type="password" class="form-control form-control-sm rounded-3" :class="{'is-invalid': errors.password}" v-model="form.password" :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : 'Min. 6 karakter (default: siswa123)'" autocomplete="new-password">
                                                <div class="invalid-feedback">{{ getError('password') }}</div>
                                            </div>
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
        </div>
    </div>

    <!-- Modal Registrasi Cepat (Quick Add) Siswa -->
    <div class="modal fade" id="quickAddModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-lightning-fill text-success me-2"></i>Registrasi Cepat Siswa Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitQuickAdd">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- Super Admin: Dropdown Filter NPSN -->
                            <div class="col-12" v-if="userRole === 'super_admin'">
                                <label for="quick_add_npsn_select" class="form-label fw-semibold fs-8 text-muted mb-1">Pilih Instansi Sekolah / Masukkan NPSN <span class="text-danger">*</span></label>
                                <select id="quick_add_npsn_select" name="npsn" class="form-select rounded-3 font-medium text-dark" v-model="quickAddForm.npsn" :class="{'is-invalid': quickAddErrors.npsn}" required>
                                    <option value="" disabled>-- Pilih Sekolah --</option>
                                    <option v-for="t in listTenants" :value="t.npsn" :key="t.id">
                                        {{ t.nama_sekolah }} (NPSN: {{ t.npsn }})
                                    </option>
                                </select>
                                <div class="invalid-feedback">{{ getQuickAddError('npsn') }}</div>
                            </div>
                            
                            <!-- Admin Sekolah / Operator: Hidden/Readonly NPSN -->
                            <div class="col-12" v-else>
                                <label for="quick_add_npsn_input" class="form-label fw-semibold fs-8 text-muted mb-1">NPSN Sekolah <small class="text-muted">(Terkunci)</small></label>
                                <input id="quick_add_npsn_input" name="npsn" type="text" class="form-control rounded-3 font-monospace bg-light" v-model="quickAddForm.npsn" readonly required>
                            </div>

                            <!-- Nama Lengkap -->
                            <div class="col-12">
                                <label for="quick_add_nama_lengkap" class="form-label fw-semibold fs-8 text-muted mb-1">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                                <input id="quick_add_nama_lengkap" name="nama_lengkap" type="text" class="form-control rounded-3" :class="{'is-invalid': quickAddErrors.nama_lengkap}" v-model="quickAddForm.nama_lengkap" placeholder="Nama lengkap siswa" required>
                                <div class="invalid-feedback">{{ getQuickAddError('nama_lengkap') }}</div>
                            </div>

                            <!-- NISN -->
                            <div class="col-12">
                                <label for="quick_add_nisn" class="form-label fw-semibold fs-8 text-muted mb-1">NISN <small class="text-muted">(Maks. 10 Digit)</small> <span class="text-danger">*</span></label>
                                <input id="quick_add_nisn" name="nisn" type="text" class="form-control rounded-3 font-monospace" :class="{'is-invalid': quickAddErrors.nisn}" v-model="quickAddForm.nisn" placeholder="Contoh: 0054231901" maxlength="10" required>
                                <div class="invalid-feedback">{{ getQuickAddError('nisn') }}</div>
                            </div>

                            <!-- Tanggal Lahir -->
                            <div class="col-12">
                                <label for="quick_add_tanggal_lahir" class="form-label fw-semibold fs-8 text-muted mb-1">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input id="quick_add_tanggal_lahir" name="tanggal_lahir" type="date" class="form-control rounded-3" :class="{'is-invalid': quickAddErrors.tanggal_lahir}" v-model="quickAddForm.tanggal_lahir" required>
                                <div class="invalid-feedback">{{ getQuickAddError('tanggal_lahir') }}</div>
                            </div>

                            <!-- Email Aktif Siswa -->
                            <div class="col-12">
                                <label for="quick_add_email" class="form-label fw-semibold fs-8 text-muted mb-1">Email Aktif Siswa <span class="text-danger">*</span></label>
                                <input id="quick_add_email" name="email" type="email" class="form-control rounded-3 font-monospace" :class="{'is-invalid': quickAddErrors.email}" v-model="quickAddForm.email" placeholder="siswa@domain.com" required>
                                <div class="invalid-feedback">{{ getQuickAddError('email') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-3 fs-8 px-3" data-bs-dismiss="modal" :disabled="quickAddLoading">Batal</button>
                        <button type="submit" class="btn btn-success rounded-3 fs-8 px-4 d-flex align-items-center gap-1.5" :disabled="quickAddLoading">
                            <span v-if="quickAddLoading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <i class="bi bi-lightning-fill" v-else></i>
                            Proses Registrasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Hak Akses Khusus Pengguna -->
    <div class="modal fade" id="userAccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-key-fill text-warning me-2"></i>Hak Akses Menu: {{ selectedStaffName }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted fs-8 mb-3">Tandai menu di bawah untuk memberikan akses khusus langsung ke staf ini di luar hak akses perannya.</p>
                    
                    <div class="list-group list-group-flush border-bottom-0">
                        <label v-for="menu in overrideMenus" :key="menu.id" class="list-group-item d-flex align-items-center gap-2 border-0 px-0 py-2 fs-7 cursor-pointer">
                            <input type="checkbox" :value="menu.id" v-model="overrideCheckedIds" class="form-check-input">
                            <span :class="{'fw-bold text-dark': !menu.parent_id, 'ps-3 text-secondary': menu.parent_id}">
                                <span v-if="menu.parent_id" class="text-muted opacity-50 me-1">└──</span>{{ menu.nama_menu }}
                            </span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-3 fs-8 px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary rounded-3 fs-8 px-4 d-flex align-items-center gap-1.5" @click="saveUserAccessOverrides" :disabled="saveAccessLoading">
                        <span v-if="saveAccessLoading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                        <i class="bi bi-check-lg" v-else></i>
                        Simpan Akses
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Photo Upload -->
    <div class="modal fade" id="bulkPhotoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-images text-success me-2"></i>Unggah Foto Profil Siswa Masal (.ZIP)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" :disabled="bulkPhotoLoading" @click="resetBulkPhotoModal"></button>
                </div>
                <form @submit.prevent="submitBulkPhoto">
                    <div class="modal-body p-4">
                        <!-- Instructions -->
                        <div class="alert alert-info border-0 rounded-3 mb-4 fs-8">
                            <h6 class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i>Petunjuk Upload Masal:</h6>
                            <ul class="mb-0 ps-3">
                                <li>Pastikan semua foto dimasukkan ke dalam satu file arsip berformat <strong>.ZIP</strong>.</li>
                                <li>Setiap file foto harus dinamai dengan format: <strong>NPSN_NISN.ekstensi</strong> (Contoh: <code>20524512_0051234567.jpg</code> atau <code>20524512_0051234567.png</code>).</li>
                                <li>Ekstensi foto yang didukung: <strong>.jpg, .jpeg, .png</strong>.</li>
                                <li>Batas ukuran masing-masing file foto maksimal <strong>500 KB</strong>.</li>
                                <li v-if="userRole !== 'super_admin'">Sebagai Operator Sekolah, Anda hanya dapat mengunggah foto untuk siswa dengan NPSN sekolah Anda (<strong>{{ userNpsn }}</strong>).</li>
                            </ul>
                        </div>

                        <!-- Upload File Input -->
                        <div class="mb-4" v-if="!bulkPhotoReport && !bulkPhotoLoading">
                            <label for="bulk_photo_file" class="form-label fw-semibold fs-8 text-muted mb-1">Pilih File ZIP <span class="text-danger">*</span></label>
                            <input id="bulk_photo_file" name="bulk_photo_file" type="file" ref="bulkPhotoFile" class="form-control rounded-3" accept=".zip" required @change="handleBulkPhotoFileChange">
                        </div>

                        <!-- Progress Loading -->
                        <div class="text-center py-4" v-if="bulkPhotoLoading">
                            <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;"></div>
                            <h6 class="fw-bold mt-3 text-dark">Mengekstrak dan Memproses Foto...</h6>
                            <p class="text-muted fs-8 mb-0">Mohon tunggu, jangan menutup modal atau me-refresh halaman.</p>
                        </div>

                        <!-- Report Results -->
                        <div v-if="bulkPhotoReport && !bulkPhotoLoading" class="report-section">
                            <div class="row g-3 text-center mb-4">
                                <div class="col-4">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <h5 class="fw-bold text-dark mb-1">{{ bulkPhotoReport.total_files }}</h5>
                                        <span class="text-muted fs-8">Total File</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-3 rounded-3 border" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                                        <h5 class="fw-bold text-success mb-1">{{ bulkPhotoReport.success_count }}</h5>
                                        <span class="text-success fs-8">Berhasil</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-3 rounded-3 border" style="background-color: #fef2f2; border-color: #fecaca !important;">
                                        <h5 class="fw-bold text-danger mb-1">{{ bulkPhotoReport.failed_count }}</h5>
                                        <span class="text-danger fs-8">Gagal</span>
                                    </div>
                                </div>
                            </div>

                            <!-- List of files report -->
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-list-task me-1"></i>Rincian Hasil Pemrosesan:</h6>
                            <div class="border rounded-3 overflow-y-auto bg-light p-2" style="max-height: 250px;">
                                <div v-for="(rep, rIdx) in bulkPhotoReport.report" :key="rIdx" class="d-flex justify-content-between align-items-start py-2 px-2 border-bottom last-border-0 fs-8 gap-2">
                                    <div class="d-flex align-items-start gap-2">
                                        <i :class="rep.status === 'success' ? 'bi bi-check-circle-fill text-success' : 'bi bi-x-circle-fill text-danger'" class="mt-0.5"></i>
                                        <span class="font-monospace text-dark fw-medium break-all">{{ rep.file }}</span>
                                    </div>
                                    <span :class="rep.status === 'success' ? 'text-success' : 'text-danger'" class="text-end fw-semibold flex-shrink-0" style="max-width: 60%;">
                                        {{ rep.message }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-3 fs-8 px-3" data-bs-dismiss="modal" :disabled="bulkPhotoLoading" @click="resetBulkPhotoModal">
                            {{ bulkPhotoReport ? 'Tutup' : 'Batal' }}
                        </button>
                        <button type="submit" class="btn btn-success rounded-3 fs-8 px-4" :disabled="bulkPhotoLoading" v-if="!bulkPhotoReport">
                            Mulai Upload
                        </button>
                        <button type="button" class="btn btn-primary rounded-3 fs-8 px-4" v-else @click="resetBulkPhotoModal">
                            Upload Lagi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- PANEL: NAIKKAN KELAS                                                 -->
    <!-- ================================================================== -->
    <div v-if="activeTab === 'naikkan_kelas' && (userRole === 'super_admin' || userRole === 'operator_sekolah')" class="aksi-panel">
        <!-- Header -->
        <div class="aksi-panel-header">
            <div class="d-flex align-items-center gap-3">
                <div class="aksi-icon-wrap" :style="aksiMode === 'promote' ? 'background:linear-gradient(135deg,#2563eb,#3b82f6);' : 'background:linear-gradient(135deg,#059669,#10b981);'">
                    <i class="bi fs-4 text-white" :class="aksiMode === 'promote' ? 'bi-arrow-up-circle-fill' : 'bi-mortarboard-fill'"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">{{ aksiMode === 'promote' ? 'Naikkan Kelas Siswa' : 'Luluskan Siswa & Alumni' }}</h5>
                    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ aksiMode === 'promote' ? 'Pindahkan siswa dari kelas asal ke kelas tujuan secara massal. Setiap aksi tercatat dalam riwayat.' : 'Ubah status siswa menjadi Lulus secara massal. Setiap aksi kelulusan akan tercatat dalam riwayat.' }}</p>
                </div>
            </div>
        </div>

        <!-- Mode Switcher -->
        <div class="px-4 py-3 bg-white border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-gear-wide-connected text-primary fs-5"></i>
                <span class="fw-bold text-dark fs-7">Pilih Mode Aksi Kolektif:</span>
            </div>
            <div class="btn-group border rounded-3 p-1 bg-light" role="group" aria-label="Tipe Aksi Kolektif">
                <input type="radio" class="btn-check" name="promotemode" id="mode-promote" value="promote" v-model="aksiMode" @change="aksiSelectedIds = []; aksiSelectAll = false; aksiKelasTujuanId = '';">
                <label class="btn btn-outline-primary btn-sm rounded-2 border-0 px-3 fw-semibold py-1.5 fs-8" for="mode-promote">
                    <i class="bi bi-arrow-up-circle-fill me-1"></i>Kenaikan Kelas
                </label>

                <input type="radio" class="btn-check" name="promotemode" id="mode-graduate" value="graduate" v-model="aksiMode" @change="aksiSelectedIds = []; aksiSelectAll = false; aksiKelasTujuanId = '';">
                <label class="btn btn-outline-success btn-sm rounded-2 border-0 px-3 fw-semibold py-1.5 fs-8" for="mode-graduate">
                    <i class="bi bi-mortarboard-fill me-1"></i>Kelulusan Siswa
                </label>

                <input type="radio" class="btn-check" name="promotemode" id="mode-retain" value="retain" v-model="aksiMode" @change="aksiSelectedIds = []; aksiSelectAll = false; aksiKelasTujuanId = '';">
                <label class="btn btn-outline-danger btn-sm rounded-2 border-0 px-3 fw-semibold py-1.5 fs-8" for="mode-retain">
                    <i class="bi bi-arrow-repeat me-1"></i>Tinggal Kelas
                </label>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="aksi-filter-section">
            <div class="row g-3 align-items-end">
                <!-- Filter Sekolah (Super Admin Only) -->
                <div class="col-12 col-md-4" v-if="userRole === 'super_admin'">
                    <label for="nk-tenant" class="aksi-label"><i class="bi bi-building me-1"></i> Instansi Sekolah <span class="text-danger">*</span></label>
                    <select id="nk-tenant" name="nk_tenant" class="form-select form-select-sm rounded-3" v-model="aksiTenantId" @change="onAksiTenantChange">
                        <option value="">-- Pilih Sekolah --</option>
                        <option v-for="t in listTenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                    </select>
                </div>

                <!-- Filter Kelas Asal -->
                <div class="col-12" :class="userRole === 'super_admin' ? 'col-md-3' : 'col-md-5'">
                    <label for="nk-kelas-asal" class="aksi-label"><i class="bi bi-door-open me-1"></i> Kelas Asal <span class="text-danger">*</span></label>
                    <select id="nk-kelas-asal" name="nk_kelas_asal" class="form-select form-select-sm rounded-3" v-model="aksiKelasAsalId" @change="onAksiKelasAsalChange" :disabled="userRole === 'super_admin' && !aksiTenantId">
                        <option value="">-- Pilih Kelas Asal --</option>
                        <option v-for="k in aksiListKelas" :key="k.id" :value="k.id">{{ k.nama_jenjang }} &ndash; {{ k.nama_kelas }}</option>
                    </select>
                </div>

                <!-- Filter Kelas Tujuan (promote & retain) -->
                <div class="col-12 col-md-3" v-if="['promote', 'retain'].includes(aksiMode)">
                    <label for="nk-kelas-tujuan" class="aksi-label"><i class="bi bi-door-closed me-1"></i> Kelas Tujuan <span class="text-danger">*</span></label>
                    <select id="nk-kelas-tujuan" name="nk_kelas_tujuan" class="form-select form-select-sm rounded-3" v-model="aksiKelasTujuanId" :disabled="!aksiKelasAsalId">
                        <option value="">-- Pilih Kelas Tujuan --</option>
                        <option v-if="aksiMode === 'promote'" v-for="k in aksiListKelas.filter(k => k.id != aksiKelasAsalId)" :key="k.id" :value="k.id">{{ k.nama_jenjang }} &ndash; {{ k.nama_kelas }}</option>
                        <option v-if="aksiMode === 'retain'" v-for="k in aksiListKelas.filter(k => k.nama_jenjang === (aksiListKelas.find(x => x.id == aksiKelasAsalId) || {}).nama_jenjang)" :key="k.id" :value="k.id">{{ k.nama_jenjang }} &ndash; {{ k.nama_kelas }}</option>
                    </select>
                </div>

                <!-- Tahun Ajaran -->
                <div class="col-12" :class="['promote', 'retain'].includes(aksiMode) ? 'col-md-2' : (userRole === 'super_admin' ? 'col-md-5' : 'col-md-7')">
                    <label for="nk-tahun" class="aksi-label"><i class="bi bi-calendar3 me-1"></i> Tahun Ajaran <span class="text-danger">*</span></label>
                    <select id="nk-tahun" name="nk_tahun" class="form-select form-select-sm rounded-3 fw-semibold text-dark" v-model="aksiTahunAjaran">
                        <option value="" disabled>-- Pilih --</option>
                        <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.tahun_ajaran">{{ ta.tahun_ajaran }}</option>
                    </select>
                </div>
            </div>
            <!-- Catatan -->
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <label for="nk-catatan" class="aksi-label"><i class="bi bi-chat-left-text me-1"></i> Catatan (opsional)</label>
                    <input id="nk-catatan" name="nk_catatan" type="text" class="form-control form-control-sm rounded-3" v-model="aksiCatatan" placeholder="Misal: Kenaikan kelas reguler semester genap">
                </div>
            </div>
        </div>

        <!-- Tabel Siswa -->
        <div class="aksi-table-section">
            <div v-if="userRole === 'super_admin' && !aksiTenantId" class="aksi-empty-state">
                <i class="bi bi-building fs-1 text-muted opacity-50"></i>
                <p class="text-muted mt-2 mb-0">Pilih instansi sekolah terlebih dahulu.</p>
            </div>
            <div v-else-if="!aksiKelasAsalId" class="aksi-empty-state">
                <i class="bi bi-funnel fs-1 text-muted opacity-50"></i>
                <p class="text-muted mt-2 mb-0">Pilih kelas asal untuk menampilkan daftar siswa.</p>
            </div>
            <div v-else-if="aksiLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2">Memuat daftar siswa...</p>
            </div>
            <div v-else>
                <!-- Toolbar checklist -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2" v-if="aksiListSiswa.length > 0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check mb-0">
                            <input id="nk-select-all" name="nk_select_all" class="form-check-input" type="checkbox" v-model="aksiSelectAll" @change="toggleAksiSelectAll">
                            <label class="form-check-label fw-semibold" for="nk-select-all">Pilih Semua ({{ aksiListSiswa.length }} siswa)</label>
                        </div>
                        <span class="badge rounded-pill" :class="aksiMode === 'promote' ? 'bg-primary' : (aksiMode === 'graduate' ? 'bg-success' : 'bg-danger')" v-if="aksiSelectedIds.length > 0">{{ aksiSelectedIds.length }} dipilih</span>
                    </div>
                    <!-- Submit Promotion Button -->
                    <button v-if="aksiMode === 'promote'" class="btn btn-primary btn-sm rounded-3 px-4 fw-semibold" @click="submitNaikkanKelas" :disabled="aksiSubmitLoading || aksiSelectedIds.length === 0 || !aksiKelasTujuanId" id="btn-naikkan">
                        <span v-if="aksiSubmitLoading" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-arrow-up-circle me-1" v-else></i>
                        Naikkan Kelas Terpilih
                    </button>
                    <!-- Submit Graduation Button -->
                    <button v-if="aksiMode === 'graduate'" class="btn btn-success btn-sm rounded-3 px-4 fw-semibold border-0" @click="submitLuluskan" :disabled="aksiSubmitLoading || aksiSelectedIds.length === 0" id="btn-luluskan">
                        <span v-if="aksiSubmitLoading" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-mortarboard me-1" v-else></i>
                        Luluskan Siswa Terpilih
                    </button>
                    <!-- Submit Retain Button -->
                    <button v-if="aksiMode === 'retain'" class="btn btn-danger btn-sm rounded-3 px-4 fw-semibold border-0" @click="submitTinggalKelas" :disabled="aksiSubmitLoading || aksiSelectedIds.length === 0 || !aksiKelasTujuanId" id="btn-tinggal">
                        <span v-if="aksiSubmitLoading" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-arrow-repeat me-1" v-else></i>
                        Tetapkan Tinggal Kelas
                    </button>
                </div>

                <div v-if="aksiListSiswa.length === 0" class="aksi-empty-state">
                    <i class="bi bi-person-slash fs-1 text-muted opacity-50"></i>
                    <p class="text-muted mt-2 mb-0">Tidak ada siswa aktif di kelas ini.</p>
                </div>

                <div class="table-responsive" v-if="aksiListSiswa.length > 0">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;"><input id="nk-table-select-all" name="nk_table_select_all" aria-label="Pilih semua baris siswa" class="form-check-input" type="checkbox" v-model="aksiSelectAll" @change="toggleAksiSelectAll"></th>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Tahun Ajaran</th>
                                <th>NISN</th>
                                <th>NIS</th>
                                <th>Kelas Saat Ini</th>
                                <th>Jenjang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(s, i) in aksiListSiswa" :key="s.id" :class="{'table-primary bg-opacity-10': aksiSelectedIds.includes(s.id) && aksiMode === 'promote', 'table-success bg-opacity-10': aksiSelectedIds.includes(s.id) && aksiMode === 'graduate', 'table-danger bg-opacity-10': aksiSelectedIds.includes(s.id) && aksiMode === 'retain'}">
                                <td><input :id="'nk_select_siswa_' + s.id" :name="'nk_select_siswa_' + s.id" aria-label="Pilih baris siswa" class="form-check-input" type="checkbox" :value="s.id" v-model="aksiSelectedIds" @change="onAksiCheckboxChange"></td>
                                <td class="text-muted">{{ i + 1 }}</td>
                                <td class="fw-semibold">{{ s.nama_lengkap }}</td>
                                <td><span class="badge bg-light text-dark border">{{ s.tahun_ajaran || '-' }}</span></td>
                                <td><span class="badge bg-light text-dark border">{{ s.nisn || '-' }}</span></td>
                                <td><span class="badge bg-light text-dark border">{{ s.nis || '-' }}</span></td>
                                <td><span class="badge" :style="aksiMode === 'promote' ? 'background:#dbeafe;color:#1e40af;' : (aksiMode === 'graduate' ? 'background:#d1fae5;color:#065f46;' : 'background:#fee2e2;color:#991b1b;')">{{ s.nama_kelas }}</span></td>
                                <td class="text-muted">{{ s.nama_jenjang }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- PANEL: PROFILE RAPOT                                               -->
    <!-- ================================================================== -->
    <div v-if="activeTab === 'profile_rapot'" class="aksi-panel">
        <!-- Header -->
        <div class="aksi-panel-header">
            <div class="d-flex align-items-center gap-3">
                <div class="aksi-icon-wrap" style="background:linear-gradient(135deg,#059669,#10b981);">
                    <i class="bi bi-file-earmark-person-fill fs-4 text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Profile Rapot (Identitas Peserta Didik)</h5>
                    <p class="text-muted mb-0" style="font-size:0.82rem;">Unduh lembar Identitas Peserta Didik per siswa atau per kelas dengan format A4 standar.</p>
                </div>
            </div>
        </div>

        <!-- Bulk Photo Upload Card -->
        <div class="px-4 py-3 bg-emerald-50 border-bottom border-emerald-100 d-flex align-items-center justify-content-between flex-wrap gap-3" style="background-color: #f0fdf4;">
            <div class="d-flex align-items-start gap-3">
                <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 40px; height: 40px; flex-shrink: 0;">
                    <i class="bi bi-images fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-success-800 mb-1" style="color: #166534;">Unggah Foto Profil Siswa Masal (ZIP)</h6>
                    <p class="text-muted mb-0 fs-8">Upload file ZIP berisi foto siswa dengan format nama file <code>NPSN_NISN.jpg/png</code> (Contoh: <code>20524512_0051234567.jpg</code>). Ukuran maksimal 500 KB per foto.</p>
                </div>
            </div>
            <button class="btn btn-success btn-sm rounded-3 px-3 py-2 fs-8 fw-semibold" @click="openBulkPhotoModal">
                <i class="bi bi-cloud-upload me-1"></i> Unggah Foto Masal
            </button>
        </div>

        <!-- Filter & Metadata Section -->
        <div class="aksi-filter-section">
            <div class="row g-3 align-items-end">
                <!-- Filter Sekolah (Super Admin Only) -->
                <div class="col-12 col-md-3" v-if="userRole === 'super_admin'">
                    <label for="pr-tenant" class="aksi-label"><i class="bi bi-building me-1"></i> Instansi Sekolah <span class="text-danger">*</span></label>
                    <select id="pr-tenant" name="pr_tenant" class="form-select form-select-sm rounded-3" v-model="filterTenantId" @change="onFilterTenantChange">
                        <option value="">-- Pilih Sekolah --</option>
                        <option v-for="t in listTenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div class="col-12 col-md-3">
                    <label for="pr-kelas" class="aksi-label"><i class="bi bi-door-open me-1"></i> Kelas / Rombel <span class="text-danger">*</span></label>
                    <select id="pr-kelas" name="pr_kelas" class="form-select form-select-sm rounded-3" v-model="filterKelas" @change="fetchData(1)">
                        <option value="">-- Pilih Kelas --</option>
                        <option v-for="k in listKelas" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                    </select>
                </div>

                <!-- Filter Status Siswa -->
                <div class="col-12 col-md-3">
                    <label for="pr-status" class="aksi-label"><i class="bi bi-funnel me-1"></i> Status Siswa</label>
                    <select id="pr-status" name="pr_status" class="form-select form-select-sm rounded-3" v-model="filterStatus" @change="fetchData(1)">
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Lulus (Alumni)</option>
                        <option value="Pindah">Pindah</option>
                        <option value="Putus Sekolah">Putus Sekolah</option>
                        <option value="">Semua Status</option>
                    </select>
                </div>

                <!-- Input Tempat -->
                <div class="col-12 col-md-3">
                    <label for="pr-tempat" class="aksi-label"><i class="bi bi-geo-alt me-1"></i> Tempat Tanda Tangan <span class="text-danger">*</span></label>
                    <input id="pr-tempat" name="pr_tempat" type="text" class="form-control form-control-sm rounded-3" v-model="printTempat" placeholder="Contoh: Jombang" required>
                </div>

                <!-- Input Tanggal -->
                <div class="col-12 col-md-3">
                    <label for="pr-tanggal" class="aksi-label"><i class="bi bi-calendar3 me-1"></i> Tanggal Tanda Tangan <span class="text-danger">*</span></label>
                    <input id="pr-tanggal" name="pr_tanggal" type="text" class="form-control form-control-sm rounded-3" v-model="printTanggal" placeholder="Contoh: 10 November 2022" required>
                </div>
            </div>
        </div>

        <!-- Tabel & Actions Section -->
        <div class="aksi-table-section">
            <div v-if="userRole === 'super_admin' && !filterTenantId" class="aksi-empty-state text-center py-5">
                <i class="bi bi-building fs-1 text-muted opacity-50"></i>
                <p class="text-muted mt-2 mb-0">Pilih instansi sekolah terlebih dahulu.</p>
            </div>
            <div v-else-if="!filterKelas" class="aksi-empty-state text-center py-5">
                <i class="bi bi-funnel fs-1 text-muted opacity-50"></i>
                <p class="text-muted mt-2 mb-0">Pilih kelas terlebih dahulu untuk melihat daftar siswa.</p>
            </div>
            <div v-else-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2">Memuat daftar siswa...</p>
            </div>
            <div v-else>
                <!-- Bulk Actions -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2" v-if="listData.length > 0">
                    <div>
                        <span class="text-muted fs-8">Total: <strong>{{ total }}</strong> siswa (Status: {{ filterStatus || 'Semua Status' }}) ditemukan.</span>
                    </div>
                    <button class="btn btn-success btn-sm rounded-3 px-4 d-flex align-items-center gap-1.5" @click="printBulk">
                        <i class="bi bi-printer-fill"></i>
                        Cetak Rapot Kelas (Bulk)
                    </button>
                </div>

                <div v-if="listData.length === 0" class="aksi-empty-state text-center py-5">
                    <i class="bi bi-person-slash fs-1 text-muted opacity-50"></i>
                    <p class="text-muted mt-2 mb-0">Tidak ada siswa (Status: {{ filterStatus || 'Semua Status' }}) di kelas ini.</p>
                </div>

                <div class="table-responsive" v-if="listData.length > 0">
                    <table class="table table-hover align-middle mb-4" style="font-size:0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Lengkap</th>
                                <th>NISN</th>
                                <th>NIS</th>
                                <th class="text-center" style="width: 120px;">Status</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in listData" :key="item.id">
                                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2 bg-light-primary fw-bold">
                                            {{ getInitials(item.nama_lengkap) }}
                                        </div>
                                        <span class="fw-semibold text-dark">{{ item.nama_lengkap }}</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border font-monospace">{{ item.nisn || '-' }}</span></td>
                                <td><span class="badge bg-light text-dark border font-monospace">{{ item.nis || '-' }}</span></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-2.5 py-1" :class="getStatusBadgeClass(item.status)">
                                        {{ item.status || 'Aktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-2 px-3 py-1 fs-8 d-inline-flex align-items-center gap-1" @click="printSingle(item.id)">
                                        <i class="bi bi-printer"></i> Cetak
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-3" v-if="total > 0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fs-8 text-muted">Menampilkan {{ from }} s.d. {{ to }} dari {{ total }} baris</span>
                        <div class="d-flex align-items-center gap-1 ms-2">
                            <label for="pr-perpage" class="fs-8 text-muted mb-0">Tampilkan:</label>
                            <select id="pr-perpage" name="pr_perpage" class="form-select form-select-sm py-0 px-2 rounded-2 fs-8" style="width: auto; height: 28px;" v-model="perPage" @change="fetchData(1)">
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
                            <li class="page-item" v-for="(page, index) in paginationPages" :key="index" :class="{active: page === currentPage, disabled: page === '...'}">
                                <a class="page-link" href="#" @click.prevent="page !== '...' && fetchData(page)">{{ page }}</a>
                            </li>
                            <li class="page-item" :class="{disabled: currentPage === totalPages}">
                                <a class="page-link" href="#" @click.prevent="fetchData(currentPage + 1)">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- Styles CSS Custom untuk Pilihan Tabs & Responsive Design -->
<style>
    [v-cloak] {
        display: none !important;
    }
    
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

    .table-light-danger {
        background-color: #fef2f2 !important;
    }

    /* Tailwind utility equivalents for progress bar */
    .bg-red-500 {
        background-color: #ef4444 !important;
    }
    .bg-amber-500 {
        background-color: #f59e0b !important;
    }
    .bg-green-500 {
        background-color: #22c55e !important;
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

    .avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .bg-light-primary {
        background-color: #eff6ff;
        color: #084298 !important;
    }

    /* Modal Form Scroll & Clean Section Layout */
    .modal-dialog-scrollable .modal-body {
        max-height: min(74vh, 620px) !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        padding: 1.5rem !important;
    }
    
    .modal-dialog-scrollable .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .modal-dialog-scrollable .modal-body::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 6px;
    }
    
    .modal-dialog-scrollable .modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
    }
    
    .modal-dialog-scrollable .modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .form-section-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 8px;
    }

    .form-section-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* =============================================
       AKSI PANEL: Naikkan Kelas & Luluskan Siswa
       ============================================= */
    .aksi-panel {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .aksi-panel-header {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
    }

    .aksi-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .aksi-filter-section {
        padding: 1.25rem 1.5rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .aksi-table-section {
        padding: 1.25rem 1.5rem;
    }

    .aksi-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.4rem;
    }

    .aksi-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #94a3b8;
    }
</style>

<!-- Script Inisialisasi Vue App -->
<script>
{
    window.VueAppRegistry.register('#penggunaApp', {
        data() {
            return {
                tabs: [
                    { id: 'siswa', name: 'Siswa', icon: 'bi bi-mortarboard' },
                    { id: 'guru', name: 'Guru', icon: 'bi bi-person-badge' },
                    { id: 'karyawan', name: 'Karyawan', icon: 'bi bi-briefcase' },
                    { id: 'operator', name: 'Operator', icon: 'bi bi-person-gear' },
                    { id: 'mutasi', name: 'Log Mutasi & Putus Sekolah', icon: 'bi bi-person-x' },
                    { id: 'naikkan_kelas', name: 'Naikkan Kelas', icon: 'bi bi-arrow-up-circle' },
                    { id: 'profile_rapot', name: 'Profile Rapot', icon: 'bi bi-file-earmark-person' }
                ],
                activeTab: 'siswa', // Default tab aktif
                userRole: '<?php echo htmlspecialchars($user_role ?? ""); ?>',
                listTenants: [],
                selectedExportTenantId: '',
                listData: [],
                currentPage: 1,
                totalPages: 1,
                perPage: 10,
                search: '',
                total: 0,
                from: 0,
                to: 0,
                summaryStats: { total: 0, male: 0, female: 0, active: 0 },
                
                // New Filters state
                filterTenantId: '',
                filterJenjang: '',
                filterKelas: '',
                filterStatus: 'Aktif',
                listKelas: [],
                listJenjang: [],

                loading: false,
                submitLoading: false,
                trashMode: false,
                isEditMode: false,
                editId: null,
                form: {},
                errors: {},
                modalObj: null,
                searchTimeout: null,
                importModalObj: null,
                importLoading: false,
                importFile: null,
                importErrors: [],

                // Override user access states (Opsi B)
                overrideMenus: [],
                overrideCheckedIds: [],
                selectedStaffId: '',
                selectedStaffName: '',
                saveAccessLoading: false,

                // ---- State untuk panel Profile Rapot ----
                printTempat: '',
                printTanggal: '',

                // ---- State untuk panel Naikkan Kelas & Luluskan Siswa ----
                aksiMode: 'promote',
                aksiTenantId: '',
                aksiKelasAsalId: '',
                aksiKelasTujuanId: '',
                aksiTahunAjaran: '',
                tahunAjaranList: [],
                aksiCatatan: '',
                aksiListKelas: [],
                aksiListSiswa: [],
                aksiSelectedIds: [],
                aksiLoading: false,
                aksiSubmitLoading: false,
                aksiSelectAll: false,

                toast: Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                }),

                // ---- State untuk Registrasi Cepat Siswa ----
                userNpsn: '<?php echo htmlspecialchars($user_npsn ?? ""); ?>',
                userNamaSekolah: '<?php echo htmlspecialchars($user_nama_sekolah ?? ""); ?>',
                quickAddForm: { npsn: '', nama_lengkap: '', nisn: '', tanggal_lahir: '', email: '' },
                quickAddErrors: {},
                quickAddLoading: false,
                quickAddModalObj: null,

                // ---- State untuk Bulk Photo Upload ----
                bulkPhotoModalObj: null,
                bulkPhotoLoading: false,
                bulkPhotoReport: null,
                bulkPhotoFile: null
            };
        },
        mounted() {
            const fmEl = document.getElementById('formModal');
            if (fmEl) {
                this.modalObj = new bootstrap.Modal(fmEl);
            }
            const imEl = document.getElementById('importModal');
            if (imEl) {
                this.importModalObj = new bootstrap.Modal(imEl);
            }
            const qEl = document.getElementById('quickAddModal');
            if (qEl) {
                this.quickAddModalObj = new bootstrap.Modal(qEl);
            }
            const bpEl = document.getElementById('bulkPhotoModal');
            if (bpEl) {
                this.bulkPhotoModalObj = new bootstrap.Modal(bpEl);
            }
            
            if (this.userRole === 'siswa') {
                this.tabs = [
                    { id: 'siswa', name: 'Siswa', icon: 'bi bi-mortarboard' }
                ];
                this.activeTab = 'siswa';
            }
            
            this.fetchJenjang();
            this.fetchKelas();
            this.fetchTahunAjaran();
            this.fetchData(1);
            if (this.userRole === 'super_admin') {
                this.fetchTenants();
            }

            // Init print metadata
            this.printTempat = 'Jombang';
            const now2 = new Date();
            const day2 = now2.getDate();
            const monthNames2 = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const month2 = monthNames2[now2.getMonth()];
            const year2 = now2.getFullYear();
            this.printTanggal = `${day2} ${month2} ${year2}`;
        },
        computed: {
            filteredKelasList() {
                if (!this.listKelas || !Array.isArray(this.listKelas)) return [];
                if (!this.filterJenjang) {
                    return this.listKelas;
                }
                const selectedJ = (this.listJenjang || []).find(j => String(j.id) === String(this.filterJenjang));
                const jCode = selectedJ ? String(selectedJ.kode_jenjang || selectedJ.kode || selectedJ.nama || '').toUpperCase() : '';
                const romanMap = {
                    '7': 'VII', '8': 'VIII', '9': 'IX',
                    '10': 'X', '11': 'XI', '12': 'XII',
                    '1': 'I', '2': 'II', '3': 'III', '4': 'IV', '5': 'V', '6': 'VI'
                };
                const roman = romanMap[jCode] || '';

                const matched = this.listKelas.filter(k => {
                    const kJenjang = String(k.id_jenjang || k.jenjang_id || '');
                    if (kJenjang === String(this.filterJenjang)) return true;
                    if (roman && k.nama_kelas) {
                        return k.nama_kelas.startsWith(roman + ' ') || k.nama_kelas.startsWith('Kelas ' + jCode);
                    }
                    return false;
                });

                return matched.length > 0 ? matched : this.listKelas;
            },
            paginationPages() {
                const current = this.currentPage;
                const total = this.totalPages;
                if (total <= 7) {
                    const pages = [];
                    for (let i = 1; i <= total; i++) pages.push(i);
                    return pages;
                }
                const pages = [];
                if (current <= 4) {
                    pages.push(1, 2, 3, 4, 5, '...', total);
                } else if (current >= total - 3) {
                    pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total);
                } else {
                    pages.push(1, '...', current - 1, current, current + 1, '...', total);
                }
                return pages;
            }
        },
        methods: {
            switchTab(tabId) {
                this.activeTab = tabId;
                this.trashMode = false;
                this.search = '';
                this.resetForm();
                // Reset panel aksi saat pindah tab
                if (tabId === 'naikkan_kelas') {
                    this.aksiTenantId = this.filterTenantId;
                    this.aksiKelasAsalId = '';
                    this.aksiKelasTujuanId = '';
                    this.aksiListSiswa = [];
                    this.aksiSelectedIds = [];
                    this.aksiSelectAll = false;
                    // Load kelas langsung untuk tenant aktif
                    if (this.userRole !== 'super_admin' || this.aksiTenantId) {
                        this.fetchAksiKelas();
                        this.fetchTahunAjaran();
                    } else {
                        this.aksiListKelas = [];
                        this.tahunAjaranList = [];
                        this.aksiTahunAjaran = '';
                    }
                } else {
                    this.fetchData(1);
                }
            },
            openImportModal() {
                this.importErrors = [];
                this.importFile = null;
                if (this.$refs.importFile) {
                    this.$refs.importFile.value = '';
                }
                this.importModalObj.show();
            },
            handleImportFileChange(e) {
                const files = e.target.files;
                if (files.length > 0) {
                    this.importFile = files[0];
                }
            },
            submitImport() {
                if (!this.importFile) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Silakan pilih berkas Excel (.xlsx) terlebih dahulu.' });
                    return;
                }

                this.importLoading = true;
                this.importErrors = [];

                const formData = new FormData();
                formData.append('file', this.importFile);

                axios.post('<?= $this->getBaseUrl() ?>/api/v1/siswa/import', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    this.importLoading = false;
                    if (response.data.success) {
                        this.importModalObj.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Import Berhasil',
                            text: response.data.message || 'Data siswa berhasil diimport.',
                            confirmButtonColor: '#10b981'
                        });
                        this.fetchData(1);
                    }
                })
                .catch(error => {
                    this.importLoading = false;
                    if (error.response && error.response.status === 422) {
                        this.importErrors = error.response.data.errors || [];
                        let errorHtml = 'Beberapa baris data tidak valid:<br><ul style="text-align: left; margin-top: 10px; font-size: 0.85em; max-height: 150px; overflow-y: auto;">';
                        this.importErrors.forEach(err => {
                            errorHtml += `<li>${err}</li>`;
                        });
                        errorHtml += '</ul>';

                        Swal.fire({
                            icon: 'warning',
                            title: 'Import Gagal',
                            html: errorHtml,
                            confirmButtonColor: '#f59e0b'
                        });
                    } else {
                        const errorMsg = error.response && error.response.data.error 
                            ? error.response.data.error 
                            : 'Terjadi kesalahan sistem saat memproses impor data.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Sistem Error',
                            text: errorMsg,
                            confirmButtonColor: '#ef4444'
                        });
                    }
                });
            },
            async openUserAccessModal(user) {
                if (!user || !user.id) {
                    Swal.fire('Error', 'ID User tidak valid atau belum termuat.', 'error');
                    return;
                }
                this.selectedStaffId = user.id;
                this.selectedStaffName = user.nama_lengkap;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/akses/user-override?user_id=' + encodeURIComponent(user.id));
                    if (res.data.success) {
                        this.overrideMenus = res.data.menus || [];
                        this.overrideCheckedIds = res.data.checked_ids || [];
                        const modal = new bootstrap.Modal(document.getElementById('userAccessModal'));
                        modal.show();
                    } else {
                        Swal.fire('Gagal', res.data.error || 'Gagal memuat data akses.', 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Gagal memuat akses user.', 'error');
                }
            },
            async saveUserAccessOverrides() {
                this.saveAccessLoading = true;
                const payload = new FormData();
                payload.append('user_id', this.selectedStaffId);
                this.overrideCheckedIds.forEach(id => payload.append('menu_ids[]', id));

                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/akses/user-override/simpan', payload);
                    if (res.data.success) {
                        Swal.fire({
                            title: 'Berhasil',
                            text: res.data.message,
                            icon: 'success',
                            confirmButtonColor: '#2563eb'
                        }).then(() => {
                            const modalEl = document.getElementById('userAccessModal');
                            const modalInst = bootstrap.Modal.getInstance(modalEl);
                            if (modalInst) modalInst.hide();
                        });
                    } else {
                        Swal.fire('Gagal', res.data.error || 'Gagal menyimpan akses.', 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Gagal menyimpan akses.', 'error');
                } finally {
                    this.saveAccessLoading = false;
                }
            },
            getActiveTabName() {
                const tab = this.tabs.find(t => t.id === this.activeTab);
                return tab ? tab.name : '';
            },
            downloadExcel() {
                let url = '<?= $this->getBaseUrl() ?>/pengguna/download-excel';
                if (this.userRole === 'super_admin' && this.selectedExportTenantId) {
                    url += '?tenant_id=' + encodeURIComponent(this.selectedExportTenantId);
                }
                window.location.href = url;
            },
            // Pemuatan data utama terpaginasi
            fetchData(page = 1) {
                if (this.activeTab === 'profile_rapot' && !this.filterKelas) {
                    this.listData = [];
                    this.total = 0;
                    this.totalPages = 1;
                    this.currentPage = 1;
                    this.from = 0;
                    this.to = 0;
                    return;
                }

                this.loading = true;
                this.currentPage = page;
                
                let targetTab = this.activeTab;
                if (targetTab === 'profile_rapot') {
                    targetTab = 'siswa';
                }
                
                let params = {
                    tab: targetTab,
                    page: this.currentPage,
                    per_page: this.perPage,
                    search: this.search,
                    trash: this.trashMode ? 'true' : 'false'
                };

                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    params.tenant_id = this.filterTenantId;
                }

                if (this.activeTab === 'siswa' || this.activeTab === 'mutasi' || this.activeTab === 'profile_rapot') {
                    params.status = this.filterStatus;
                    params.id_jenjang = this.filterJenjang;
                    params.id_kelas = this.filterKelas;
                }

                axios.get('<?= $this->getBaseUrl() ?>/api/v1/pengguna', {
                    params: params
                }).then(res => {
                    this.listData = res.data.data;
                    this.totalPages = res.data.last_page;
                    this.total = res.data.total;
                    this.summaryStats = res.data.summary_stats || { 
                        total: res.data.total, 
                        male: 0, 
                        female: 0, 
                        active: res.data.total 
                    };
                    this.from = res.data.from;
                    this.to = res.data.to;
                    this.loading = false;
                }).catch(err => {
                    this.loading = false;
                    this.toast.fire({ icon: 'error', title: (err && err.response && err.response.data && err.response.data.error) || 'Gagal memuat data dari server.' });
                });
            },
            fetchTenants() {
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/pengguna/tenants')
                     .then(res => {
                          this.listTenants = res.data.data;
                     })
                     .catch(err => {
                          console.error("Gagal mengambil data sekolah:", err);
                     });
            },
            fetchJenjang() {
                let tenantId = '';
                if (this.userRole === 'super_admin') {
                    tenantId = this.filterTenantId;
                }
                const params = { module: 'jenjang' };
                if (tenantId) {
                    params.tenant_id = tenantId;
                    params.filter_tenant_id = tenantId;
                }
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/kelembagaan/options', { params })
                     .then(res => {
                          const fetched = (res.data && res.data.data) ? res.data.data : (res.data || []);
                          if (Array.isArray(fetched) && fetched.length > 0) {
                              this.listJenjang = fetched;
                          }
                      })
                     .catch(err => {
                         console.error("Gagal mengambil data jenjang:", err);
                     });
            },
            onJenjangFilterChange() {
                if (this.filterKelas) {
                    const exists = this.filteredKelasList.some(k => String(k.id) === String(this.filterKelas));
                    if (!exists) {
                        this.filterKelas = '';
                    }
                }
                this.fetchData(1);
            },
            fetchKelas() {
                let tenantId = '';
                if (this.userRole === 'super_admin') {
                    tenantId = this.filterTenantId;
                }
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/pengguna/kelas', {
                    params: { tenant_id: tenantId }
                }).then(res => {
                    this.listKelas = res.data.data || [];
                }).catch(err => {
                    console.error("Gagal mengambil data kelas:", err);
                });
            },
            fetchTahunAjaran() {
                let tenantId = '';
                if (this.userRole === 'super_admin') {
                    tenantId = this.filterTenantId || this.aksiTenantId;
                }
                if (!tenantId && this.userRole === 'super_admin') {
                    this.tahunAjaranList = [];
                    this.aksiTahunAjaran = '';
                    return;
                }

                axios.get('<?= $this->getBaseUrl() ?>/api/v1/pengguna/tahun-ajaran', {
                    params: { tenant_id: tenantId }
                }).then(res => {
                    this.tahunAjaranList = res.data.data || [];
                    if (this.tahunAjaranList.length > 0) {
                        this.aksiTahunAjaran = this.tahunAjaranList[0].tahun_ajaran;
                    } else {
                        this.aksiTahunAjaran = '';
                    }
                }).catch(err => {
                    console.error("Gagal mengambil data tahun ajaran:", err);
                });
            },
            onFilterTenantChange() {
                this.filterJenjang = '';
                this.filterKelas = '';
                this.aksiTenantId = this.filterTenantId;
                this.fetchJenjang();
                this.fetchKelas();
                this.fetchTahunAjaran();
                if (this.activeTab === 'naikkan_kelas') {
                    this.onAksiTenantChange();
                } else {
                    this.fetchData(1);
                }
            },
            getSelectedTenantName() {
                if (this.userRole === 'super_admin') {
                    if (!this.filterTenantId) {
                        return 'Semua Sekolah';
                    }
                    const found = this.listTenants.find(t => t.id == this.filterTenantId);
                    return found ? found.nama_sekolah : 'Semua Sekolah';
                }
                return this.userNamaSekolah || 'Sekolah Anda';
            },
            downloadExcel() {
                let url = '<?= $this->getBaseUrl() ?>/api/v1/pengguna/export-excel?tab=' + this.activeTab;
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    url += '&tenant_id=' + encodeURIComponent(this.filterTenantId);
                }
                if (this.filterJenjang) {
                    url += '&id_jenjang=' + encodeURIComponent(this.filterJenjang);
                }
                if (this.filterKelas) {
                    url += '&id_kelas=' + encodeURIComponent(this.filterKelas);
                }
                if (this.filterStatus) {
                    url += '&status=' + encodeURIComponent(this.filterStatus);
                }
                if (this.search) {
                    url += '&search=' + encodeURIComponent(this.search);
                }
                if (this.trashMode) {
                    url += '&trash=true';
                }
                window.open(url, '_blank');
            },
            resetFilters() {
                this.filterStatus = 'Aktif';
                this.filterJenjang = '';
                this.filterKelas = '';
                if (this.userRole === 'super_admin') {
                    this.filterTenantId = '';
                }
                this.fetchJenjang();
                this.fetchKelas();
                this.fetchTahunAjaran();
                this.fetchData(1);
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
                if (this.activeTab === 'siswa' || this.activeTab === 'mutasi') {
                    this.form = { 
                        nama_lengkap: '', 
                        nisn: '',
                        nis: '',
                        tanggal_lahir: '',
                        tempat_lahir: '',
                        jenis_kelamin: 'L',
                        alamat: '',
                        nama_wali: '',
                        kontak_wali: '',
                        email: '',
                        password: ''
                    };
                } else if (this.activeTab === 'guru') {
                    this.form = { 
                        nama_lengkap: '', 
                        email: '', 
                        password: '',
                        nip: '',
                        nuptk: '',
                        jenis_gtk: 'Guru Mata Pelajaran',
                        jabatan_struktural: '',
                        status_kepegawaian: 'GTY/PTY',
                        jam_mengajar: 0,
                        status_sertifikasi: false,
                        no_hp: '',
                        alamat: '',
                        jenis_kelamin: 'L',
                        is_wali_kelas: false,
                        is_pembina_ekskul: false,
                        is_bk: false,
                        is_kesiswaan: false,
                        is_humas: false,
                        is_kurikulum: false,
                        is_sarpras: false,
                        is_keuangan: false,
                        is_perpustakaan: false
                    };
                } else if (this.activeTab === 'karyawan') {
                    this.form = { 
                        nama_lengkap: '', 
                        email: '', 
                        password: '',
                        nip: '',
                        nuptk: '',
                        jenis_gtk: 'Tata Usaha / Administrasi',
                        jabatan_struktural: '',
                        status_kepegawaian: 'PTT',
                        jam_mengajar: 0,
                        status_sertifikasi: false,
                        no_hp: '',
                        alamat: '',
                        jenis_kelamin: 'L',
                        is_wali_kelas: false,
                        is_pembina_ekskul: false,
                        is_bk: false,
                        is_kesiswaan: false,
                        is_humas: false,
                        is_kurikulum: false,
                        is_sarpras: false,
                        is_keuangan: false,
                        is_perpustakaan: false
                    };
                } else {
                    this.form = { 
                        nama_lengkap: '', 
                        email: '', 
                        password: '',
                        nip: '',
                        nuptk: '',
                        jenis_gtk: 'Tenaga IT & Operator Sistem',
                        jabatan_struktural: 'Operator Sekolah',
                        status_kepegawaian: 'Honorer Sekolah',
                        jam_mengajar: 0,
                        status_sertifikasi: false,
                        no_hp: '',
                        alamat: '',
                        jenis_kelamin: 'L',
                        is_wali_kelas: false,
                        is_pembina_ekskul: false,
                        is_bk: false,
                        is_kesiswaan: false,
                        is_humas: false,
                        is_kurikulum: false,
                        is_sarpras: false,
                        is_keuangan: false,
                        is_perpustakaan: false
                    };
                }
                if (this.userRole === 'super_admin') {
                    this.form.tenant_id = this.filterTenantId || '';
                }
            },
            openCreateModal() {
                this.isEditMode = false;
                this.resetForm();
                this.modalObj.show();
            },
            openEditModal(item) {
                if (this.activeTab === 'siswa' || this.activeTab === 'mutasi') {
                    window.location.href = '<?= $this->getBaseUrl() ?>/siswa/edit?id=' + item.id;
                    return;
                }
                this.isEditMode = true;
                this.errors = {};
                this.editId = item.id;
                
                if (this.activeTab === 'siswa' || this.activeTab === 'mutasi') {
                    this.form = {
                        nama_lengkap: item.nama_lengkap || '',
                        nisn: item.nisn || '',
                        nis: item.nis || '',
                        tanggal_lahir: item.tanggal_lahir || '',
                        tempat_lahir: item.tempat_lahir || '',
                        jenis_kelamin: item.jenis_kelamin || 'L',
                        alamat: item.alamat || '',
                        nama_wali: item.nama_wali || '',
                        kontak_wali: item.kontak_wali || ''
                    };
                } else {
                    const assigned = Array.isArray(item.assigned_roles) ? item.assigned_roles : [];
                    this.form = {
                        nama_lengkap: item.nama_lengkap || '',
                        email: item.email || '',
                        password: '',
                        nip: item.nip || '',
                        nuptk: item.nuptk || '',
                        jenis_gtk: item.jenis_gtk || (this.activeTab === 'guru' ? 'Guru Mata Pelajaran' : 'Tenaga Kependidikan'),
                        jabatan_struktural: item.jabatan_struktural || '',
                        status_kepegawaian: item.status_kepegawaian || 'GTY/PTY',
                        jam_mengajar: item.jam_mengajar || 0,
                        status_sertifikasi: item.status_sertifikasi === true || item.status_sertifikasi === 'true' || item.status_sertifikasi == 1,
                        no_hp: item.no_hp || '',
                        alamat: item.alamat || '',
                        jenis_kelamin: item.jenis_kelamin || 'L',
                        is_wali_kelas: item.is_wali_kelas == 1 || item.is_wali_kelas === true || assigned.includes('wali_kelas'),
                        is_pembina_ekskul: item.is_pembina_ekskul == 1 || item.is_pembina_ekskul === true || assigned.includes('pembina_ekskul'),
                        is_bk: item.is_bk == 1 || item.is_bk === true || assigned.includes('bk') || assigned.includes('guru_bk'),
                        is_kesiswaan: item.is_kesiswaan == 1 || item.is_kesiswaan === true || assigned.includes('kesiswaan'),
                        is_humas: item.is_humas == 1 || item.is_humas === true || assigned.includes('humas'),
                        is_kurikulum: item.is_kurikulum == 1 || item.is_kurikulum === true || assigned.includes('kurikulum'),
                        is_sarpras: item.is_sarpras == 1 || item.is_sarpras === true || assigned.includes('sarpras'),
                        is_keuangan: item.is_keuangan == 1 || item.is_keuangan === true || assigned.includes('keuangan') || assigned.includes('bendahara'),
                        is_perpustakaan: item.is_perpustakaan == 1 || item.is_perpustakaan === true || assigned.includes('perpustakaan')
                    };
                }
                
                if (this.userRole === 'super_admin') {
                    this.form.tenant_id = item.tenant_id || '';
                }
                
                this.modalObj.show();
            },
            submitForm() {
                this.submitLoading = true;
                this.errors = {};
                
                const payload = { ...this.form, tab: this.activeTab };
                if (this.isEditMode) {
                    payload.id = this.editId;
                }

                axios.post('<?= $this->getBaseUrl() ?>/api/v1/pengguna/simpan', payload)
                     .then(res => {
                          this.submitLoading = false;
                          this.modalObj.hide();
                          this.toast.fire({ icon: 'success', title: res.data.message });
                          this.fetchData(this.isEditMode ? this.currentPage : 1);
                     })
                     .catch(err => {
                          this.submitLoading = false;
                          if (err.response && err.response.status === 422) {
                              this.errors = err.response.data.errors;
                              this.toast.fire({ icon: 'error', title: 'Silakan periksa input form Anda.' });
                          } else {
                              this.toast.fire({ icon: 'error', title: (err && err.response && err.response.data && err.response.data.error) || 'Gagal menyimpan data.' });
                          }
                      });
            },
            deleteItem(id) {
                if (!id) {
                    Swal.fire('Error', 'ID Pengguna tidak valid atau belum termuat.', 'error');
                    return;
                }
                Swal.fire({
                    title: 'Pindahkan ke Tong Sampah?',
                    text: `Data ${this.getActiveTabName()} ini akan disembunyikan sementara dari sistem.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('<?= $this->getBaseUrl() ?>/api/v1/pengguna/hapus', { tab: this.activeTab, id: id })
                             .then(res => {
                                  this.toast.fire({ icon: 'success', title: res.data.message });
                                  this.fetchData(this.currentPage);
                              })
                             .catch(err => {
                                  this.toast.fire({ icon: 'error', title: (err && err.response && err.response.data && err.response.data.error) || 'Gagal menghapus.' });
                              });
                    }
                });
            },
            restoreItem(id) {
                axios.post('<?= $this->getBaseUrl() ?>/api/v1/pengguna/restore', { tab: this.activeTab, id: id })
                     .then(res => {
                          this.toast.fire({ icon: 'success', title: res.data.message });
                          this.fetchData(this.currentPage);
                      })
                     .catch(err => {
                          this.toast.fire({ icon: 'error', title: 'Gagal memulihkan data.' });
                      });
            },
            toggleStatus(id) {
                axios.post('<?= $this->getBaseUrl() ?>/api/v1/pengguna/toggle-status', { tab: this.activeTab, id: id })
                     .then(res => {
                          this.toast.fire({ icon: 'success', title: res.data.message });
                      })
                     .catch(err => {
                          this.toast.fire({ icon: 'error', title: (err && err.response && err.response.data && err.response.data.error) || 'Gagal mengubah status.' });
                          this.fetchData(this.currentPage); // Reset switch state
                      });
            },
            getInitials(name) {
                if (!name) return '';
                const parts = name.split(' ');
                let initials = parts[0].charAt(0);
                if (parts.length > 1) {
                    initials += parts[1].charAt(0);
                }
                return initials.toUpperCase();
            },
            getStatsMaleCount() {
                return (this.listData || []).filter(item => item.jenis_kelamin === 'L').length;
            },
            getStatsFemaleCount() {
                return (this.listData || []).filter(item => item.jenis_kelamin === 'P').length;
            },
            getStatsActiveCount() {
                if (this.activeTab === 'siswa') {
                    return (this.listData || []).filter(item => item.status_siswa === 'Aktif' || !item.status_siswa).length;
                }
                return (this.listData || []).filter(item => item.status === '1' || item.status === 1 || item.status === true || item.status === 'Aktif').length;
            },
            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },
            getError(field) {
                return this.errors[field] ? this.errors[field][0] : '';
            },
            downloadExcel() {
                const params = new URLSearchParams({
                    tab: this.activeTab,
                    search: this.searchQuery || '',
                    trash: this.trashMode ? 'true' : 'false',
                    id_kelas: this.filterKelas || '',
                    status: this.filterStatus || '',
                    tenant_id: this.filterTenantId || ''
                });
                window.open('<?= $this->getBaseUrl() ?>/api/v1/pengguna/export-excel?' + params.toString(), '_blank');
            },

            // ================================================================
            // METHODS: PANEL NAIKKAN KELAS & LULUSKAN SISWA
            // ================================================================
            onAksiTenantChange() {
                this.aksiKelasAsalId = '';
                this.aksiKelasTujuanId = '';
                this.aksiListSiswa = [];
                this.aksiSelectedIds = [];
                this.aksiSelectAll = false;
                if (this.aksiTenantId) {
                    this.fetchAksiKelas();
                    this.fetchTahunAjaran();
                } else {
                    this.aksiListKelas = [];
                    this.tahunAjaranList = [];
                    this.aksiTahunAjaran = '';
                }
            },
            fetchAksiKelas() {
                let params = {};
                if (this.userRole === 'super_admin') {
                    if (!this.aksiTenantId) return;
                    params.tenant_id = this.aksiTenantId;
                }
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/pengguna/aksi/kelas', { params })
                    .then(res => {
                        this.aksiListKelas = res.data.data || [];
                    })
                    .catch(err => {
                        this.toast.fire({ icon: 'error', title: (err && err.response && err.response.data && err.response.data.error) || 'Gagal memuat daftar kelas.' });
                    });
            },
            onAksiKelasAsalChange() {
                this.aksiListSiswa = [];
                this.aksiSelectedIds = [];
                this.aksiSelectAll = false;
                if (!this.aksiKelasAsalId) return;
                this.fetchAksiSiswa();
            },
            fetchAksiSiswa() {
                if (!this.aksiKelasAsalId) return;
                const params = { kelas_id: this.aksiKelasAsalId };
                if (this.userRole === 'super_admin') {
                    if (!this.aksiTenantId) return;
                    params.tenant_id = this.aksiTenantId;
                }
                this.aksiLoading = true;
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/pengguna/aksi/siswa', { params })
                    .then(res => {
                        this.aksiListSiswa = res.data.data || [];
                        this.aksiSelectedIds = [];
                        this.aksiSelectAll = false;
                        this.aksiLoading = false;
                    })
                    .catch(err => {
                        this.aksiLoading = false;
                        this.toast.fire({ icon: 'error', title: (err && err.response && err.response.data && err.response.data.error) || 'Gagal memuat daftar siswa.' });
                    });
            },
            toggleAksiSelectAll() {
                if (this.aksiSelectAll) {
                    this.aksiSelectedIds = this.aksiListSiswa.map(s => s.id);
                } else {
                    this.aksiSelectedIds = [];
                }
            },
            onAksiCheckboxChange() {
                this.aksiSelectAll = this.aksiSelectedIds.length === this.aksiListSiswa.length && this.aksiListSiswa.length > 0;
            },
            submitNaikkanKelas() {
                if (this.aksiSelectedIds.length === 0) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih minimal satu siswa.' }); return;
                }
                if (!this.aksiKelasTujuanId) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih kelas tujuan.' }); return;
                }
                if (!this.aksiTahunAjaran) {
                    this.toast.fire({ icon: 'warning', title: 'Isi tahun ajaran.' }); return;
                }

                const kelasAsal = this.aksiListKelas.find(k => k.id == this.aksiKelasAsalId);
                const kelasTujuan = this.aksiListKelas.find(k => k.id == this.aksiKelasTujuanId);

                Swal.fire({
                    title: 'Konfirmasi Naikkan Kelas',
                    html: `Anda akan menaikkan <b>${this.aksiSelectedIds.length} siswa</b><br>dari <b>${(kelasAsal && kelasAsal.nama_kelas) || '-'}</b> → <b>${(kelasTujuan && kelasTujuan.nama_kelas) || '-'}</b><br>Tahun Ajaran: <b>${this.aksiTahunAjaran}</b>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Naikkan!',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    this.aksiSubmitLoading = true;
                    const payload = {
                        siswa_ids: this.aksiSelectedIds,
                        id_kelas_tujuan: this.aksiKelasTujuanId,
                        tahun_ajaran: this.aksiTahunAjaran,
                        catatan: this.aksiCatatan
                    };
                    if (this.userRole === 'super_admin') payload.tenant_id = this.aksiTenantId;

                    axios.post('<?= $this->getBaseUrl() ?>/api/v1/pengguna/aksi/naikkan-kelas', payload)
                        .then(res => {
                            this.aksiSubmitLoading = false;
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.data.message, confirmButtonColor: '#10b981' });
                            this.aksiKelasAsalId = '';
                            this.aksiKelasTujuanId = '';
                            this.aksiListSiswa = [];
                            this.aksiSelectedIds = [];
                            this.aksiSelectAll = false;
                            this.aksiCatatan = '';
                        })
                        .catch(err => {
                            this.aksiSubmitLoading = false;
                            Swal.fire({ icon: 'error', title: 'Gagal', text: (err && err.response && err.response.data && err.response.data.error) || 'Terjadi kesalahan.', confirmButtonColor: '#ef4444' });
                        });
                });
            },
            submitTinggalKelas() {
                if (this.aksiSelectedIds.length === 0) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih minimal satu siswa.' }); return;
                }
                if (!this.aksiKelasTujuanId) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih kelas tujuan.' }); return;
                }
                if (!this.aksiTahunAjaran) {
                    this.toast.fire({ icon: 'warning', title: 'Isi tahun ajaran.' }); return;
                }

                const kelasAsal = this.aksiListKelas.find(k => k.id == this.aksiKelasAsalId);
                const kelasTujuan = this.aksiListKelas.find(k => k.id == this.aksiKelasTujuanId);

                Swal.fire({
                    title: 'Konfirmasi Tinggal Kelas',
                    html: `Anda akan menetapkan tinggal kelas untuk <b>${this.aksiSelectedIds.length} siswa</b><br>dari <b>${(kelasAsal && kelasAsal.nama_kelas) || '-'}</b> &#10145; <b>${(kelasTujuan && kelasTujuan.nama_kelas) || '-'}</b><br>Tahun Ajaran: <b>${this.aksiTahunAjaran}</b>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Tetapkan!',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    this.aksiSubmitLoading = true;
                    const payload = {
                        siswa_ids: this.aksiSelectedIds,
                        kelas_tujuan: this.aksiKelasTujuanId,
                        tahun_ajaran: this.aksiTahunAjaran,
                        catatan: this.aksiCatatan
                    };
                    if (this.userRole === 'super_admin') payload.tenant_id = this.aksiTenantId;

                    axios.post('<?= $this->getBaseUrl() ?>/api/v1/pengguna/aksi/tinggal-kelas', payload)
                        .then(res => {
                            this.aksiSubmitLoading = false;
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.data.message, confirmButtonColor: '#10b981' });
                            this.aksiKelasAsalId = '';
                            this.aksiKelasTujuanId = '';
                            this.aksiListSiswa = [];
                            this.aksiSelectedIds = [];
                            this.aksiSelectAll = false;
                            this.aksiCatatan = '';
                        })
                        .catch(err => {
                            this.aksiSubmitLoading = false;
                            const msg = err.response && err.response.data.error ? err.response.data.error : err.message;
                            Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#ef4444' });
                        });
                });
            },
            submitLuluskan() {
                if (this.aksiSelectedIds.length === 0) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih minimal satu siswa.' }); return;
                }
                if (!this.aksiTahunAjaran) {
                    this.toast.fire({ icon: 'warning', title: 'Isi tahun ajaran.' }); return;
                }

                Swal.fire({
                    title: 'Konfirmasi Luluskan Siswa',
                    html: `Anda akan meluluskan <b>${this.aksiSelectedIds.length} siswa</b>.<br>Tahun Ajaran: <b>${this.aksiTahunAjaran}</b><br><span class='text-danger'>Status siswa akan berubah menjadi <b>Lulus</b> secara permanen.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Luluskan!',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    this.aksiSubmitLoading = true;
                    const payload = {
                        siswa_ids: this.aksiSelectedIds,
                        tahun_ajaran: this.aksiTahunAjaran,
                        catatan: this.aksiCatatan
                    };
                    if (this.userRole === 'super_admin') payload.tenant_id = this.aksiTenantId;

                    axios.post('<?= $this->getBaseUrl() ?>/api/v1/pengguna/aksi/luluskan', payload)
                        .then(res => {
                            this.aksiSubmitLoading = false;
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.data.message, confirmButtonColor: '#10b981' });
                            this.aksiKelasAsalId = '';
                            this.aksiListSiswa = [];
                            this.aksiSelectedIds = [];
                            this.aksiSelectAll = false;
                            this.aksiCatatan = '';
                        })
                        .catch(err => {
                            this.aksiSubmitLoading = false;
                            Swal.fire({ icon: 'error', title: 'Gagal', text: (err && err.response && err.response.data && err.response.data.error) || 'Terjadi kesalahan.', confirmButtonColor: '#ef4444' });
                        });
                });
            },

            // ---- Methods untuk Registrasi Cepat Siswa ----
            openQuickAddModal() {
                this.quickAddErrors = {};
                this.quickAddForm = {
                    npsn: this.userRole === 'super_admin' ? '' : this.userNpsn,
                    nama_lengkap: '',
                    nisn: '',
                    tanggal_lahir: '',
                    email: ''
                };
                if (this.quickAddModalObj) {
                    this.quickAddModalObj.show();
                }
            },
            submitQuickAdd() {
                this.quickAddLoading = true;
                this.quickAddErrors = {};

                axios.post('<?= $this->getBaseUrl() ?>/api/v1/pengguna/quick-add-siswa', this.quickAddForm)
                    .then(res => {
                        this.quickAddLoading = false;
                        if (this.quickAddModalObj) {
                            this.quickAddModalObj.hide();
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Registrasi Berhasil',
                            text: res.data.message || 'Siswa baru berhasil diregistrasikan.',
                            confirmButtonColor: '#10b981'
                        });
                        this.fetchData(1);
                    })
                    .catch(err => {
                        this.quickAddLoading = false;
                        if (err.response && err.response.status === 422) {
                            this.quickAddErrors = err.response.data.errors || {};
                            this.toast.fire({ icon: 'error', title: 'Silakan periksa input form registrasi cepat Anda.' });
                        } else {
                            this.toast.fire({ icon: 'error', title: (err && err.response && err.response.data && err.response.data.error) || 'Gagal meregistrasikan siswa.' });
                        }
                    });
            },
            getQuickAddError(field) {
                return this.quickAddErrors[field] ? this.quickAddErrors[field][0] : '';
            },
            printSingle(studentId) {
                if (!this.printTempat || !this.printTempat.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tempat penandatanganan wajib diisi sebelum mencetak.' });
                    return;
                }
                if (!this.printTanggal || !this.printTanggal.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tanggal penandatanganan wajib diisi sebelum mencetak.' });
                    return;
                }
                
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/cetak/request-token', { params: { id: studentId } })
                    .then(response => {
                        if (response.data && response.data.success) {
                            const token = response.data.token;
                            const url = `<?= $this->getBaseUrl() ?>/cetak-rapot?id=${encodeURIComponent(studentId)}&tempat=${encodeURIComponent(this.printTempat.trim())}&tanggal=${encodeURIComponent(this.printTanggal.trim())}&token=${token}`;
                            window.open(url, '_blank');
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: response.data.error || 'Gagal menyiapkan otentikasi cetak.' });
                        }
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memproses token keamanan cetak.' });
                    });
            },
            printBulk() {
                if (!this.filterKelas) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Silakan pilih kelas terlebih dahulu.' });
                    return;
                }
                if (!this.printTempat || !this.printTempat.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tempat penandatanganan wajib diisi sebelum mencetak.' });
                    return;
                }
                if (!this.printTanggal || !this.printTanggal.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tanggal penandatanganan wajib diisi sebelum mencetak.' });
                    return;
                }
                
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/cetak/request-token', { params: { kelas_id: this.filterKelas } })
                    .then(response => {
                        if (response.data && response.data.success) {
                            const token = response.data.token;
                            const url = `<?= $this->getBaseUrl() ?>/cetak-rapot-kelas?kelas_id=${encodeURIComponent(this.filterKelas)}&tempat=${encodeURIComponent(this.printTempat.trim())}&tanggal=${encodeURIComponent(this.printTanggal.trim())}&token=${token}`;
                            window.open(url, '_blank');
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: response.data.error || 'Gagal menyiapkan otentikasi cetak.' });
                        }
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memproses token keamanan cetak.' });
                    });
            },

            // ---- Methods untuk Bulk Photo Upload ----
            openBulkPhotoModal() {
                this.resetBulkPhotoModal();
                if (this.bulkPhotoModalObj) {
                    this.bulkPhotoModalObj.show();
                }
            },
            handleBulkPhotoFileChange(e) {
                const files = e.target.files;
                if (files.length > 0) {
                    this.bulkPhotoFile = files[0];
                }
            },
            submitBulkPhoto() {
                if (!this.bulkPhotoFile) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Silakan pilih berkas ZIP terlebih dahulu.' });
                    return;
                }

                this.bulkPhotoLoading = true;
                this.bulkPhotoReport = null;

                const formData = new FormData();
                formData.append('file', this.bulkPhotoFile);

                axios.post('<?= $this->getBaseUrl() ?>/api/v1/siswa/bulk-photo', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    this.bulkPhotoLoading = false;
                    this.bulkPhotoReport = response.data;
                    Swal.fire({
                        icon: response.data.success_count > 0 ? 'success' : 'warning',
                        title: 'Proses Selesai',
                        text: `${response.data.success_count} foto berhasil dipasang, ${response.data.failed_count} gagal.`,
                        confirmButtonColor: '#10b981'
                    });
                    this.fetchData(this.currentPage);
                })
                .catch(error => {
                    this.bulkPhotoLoading = false;
                    const errorMsg = error.response && error.response.data.error 
                        ? error.response.data.error 
                        : 'Terjadi kesalahan sistem saat memproses upload foto masal.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Sistem Error',
                        text: errorMsg,
                        confirmButtonColor: '#ef4444'
                    });
                });
            },
            resetBulkPhotoModal() {
                this.bulkPhotoLoading = false;
                this.bulkPhotoReport = null;
                this.bulkPhotoFile = null;
                if (this.$refs.bulkPhotoFile) {
                    this.$refs.bulkPhotoFile.value = '';
                }
            },
            getStatusBadgeClass(status) {
                if (!status) return 'bg-success-subtle text-success border border-success-subtle';
                const s = String(status).toLowerCase();
                if (s.includes('lulus')) return 'bg-primary-subtle text-primary border border-primary-subtle';
                if (s.includes('pindah')) return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                if (s.includes('putus')) return 'bg-danger-subtle text-danger border border-danger-subtle';
                return 'bg-success-subtle text-success border border-success-subtle';
            }
        }
    });
}
</script>
