
<!-- Halaman Sentral: Manajemen Pengguna -->
<div id="penggunaApp" v-cloak>
    
    <!-- Row Header & Actions -->
    <div class="row mb-3 mb-md-4 align-items-center">
        <div class="col-12 col-md-7 mb-3 mb-md-0">
            <template v-if="userRole === 'siswa'">
                <h3 class="fw-bold text-dark mb-1 fs-4 fs-md-3">
                    <i class="bi bi-person-bounding-box text-primary me-2"></i>Profil Data Diri
                </h3>
            </template>
            <template v-else>
                <h3 class="fw-bold text-dark mb-1 fs-4 fs-md-3">
                    <i class="bi bi-people-fill text-primary me-2"></i>Manajemen Pengguna
                </h3>
                <p class="text-muted fs-8 fs-md-7 mb-0">Kelola data akademik dan non-akademik sekolah (Siswa, Guru, Karyawan, dan Operator) secara terintegrasi.</p>
            </template>
        </div>
        
        <!-- Toggle Trash & Add Action -->
        <div class="col-12 col-md-5 d-flex gap-2 justify-content-start justify-content-md-end align-items-center flex-wrap" v-if="userRole !== 'siswa' && activeTab !== 'profile_rapot'">
            <button class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0" 
                    @click="toggleTrashMode" 
                    :class="{'btn-danger text-white': trashMode}"
                    :style="!trashMode ? 'color: #334155; border-color: #94a3b8;' : ''">
                <i class="bi" :class="trashMode ? 'bi-table' : 'bi-trash3'"></i>
                {{ trashMode ? 'Kembali ke Data Aktif' : 'Lihat Tong Sampah' }}
            </button>
            <label v-if="userRole === 'super_admin' && activeTab === 'siswa' && !trashMode" for="sa-export-filter-sekolah" class="visually-hidden">Pilih Sekolah untuk Ekspor</label>
            <select id="sa-export-filter-sekolah" name="sa_export_filter_sekolah" class="form-select form-select-sm rounded-3 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0" v-model="selectedExportTenantId" style="max-width: 200px;" v-if="userRole === 'super_admin' && activeTab === 'siswa' && !trashMode">
                <option value="">-- Semua Sekolah --</option>
                <option v-for="t in listTenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
            </select>
            <button class="btn btn-outline-primary btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0" @click="downloadExcel" v-if="activeTab === 'siswa' && !trashMode">
                <i class="bi bi-download me-1"></i> Download Excel
            </button>
            <button class="btn btn-outline-success btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0" @click="openImportModal" v-if="activeTab === 'siswa' && !trashMode">
                <i class="bi bi-file-earmark-excel me-1"></i> Import Siswa
            </button>
            <button class="btn btn-success btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 shadow-sm flex-grow-1 flex-md-grow-0" @click="openQuickAddModal" v-if="activeTab === 'siswa' && !trashMode && userRole !== 'siswa' && userRole !== 'guru'">
                <i class="bi bi-lightning-fill me-1"></i> Registrasi Cepat
            </button>
            <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 shadow-sm flex-grow-1 flex-md-grow-0" @click="openCreateModal" v-if="!trashMode && activeTab !== 'mutasi'">
                <i class="bi bi-plus-lg me-1"></i> Tambah {{ getActiveTabName() }}
            </button>
        </div>
    </div>

    <!-- Navigation Tabs (Sleek Underline, 14px, Dark Grey) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.id">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === tab.id}" 
                                @click="switchTab(tab.id)">
                            <i :class="tab.icon" class="me-2 fs-6"></i>{{ tab.name }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Datatable Grid (disembunyikan saat tab aksi aktif) -->
    <div class="card border-0 shadow-sm rounded-4" v-if="activeTab !== 'naikkan_kelas' && activeTab !== 'profile_rapot'">
        <div class="card-body p-3 p-md-4">
            
            <!-- Horizontal Filter Form (Tailwind CSS) -->
            <div class="mb-4 bg-slate-50 p-4 rounded-xl border border-slate-100" v-if="activeTab === 'siswa' || activeTab === 'mutasi'">
                <form @submit.prevent="fetchData(1)" class="flex flex-col md:flex-row md:items-end gap-3">
                    <!-- Filter 1: Nama Sekolah (Super Admin Only) -->
                    <div class="flex-1 min-w-[200px]" v-if="userRole === 'super_admin'">
                        <label for="filter_tenant_id" class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Nama Sekolah / Tenant</label>
                        <select id="filter_tenant_id" name="filter_tenant_id" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" v-model="filterTenantId" @change="onFilterTenantChange">
                            <option value="">-- Semua Sekolah --</option>
                            <option v-for="t in listTenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                        </select>
                    </div>

                    <!-- Filter 2: Kelas / Rombel -->
                    <div class="flex-1 min-w-[150px]">
                        <label for="filter_kelas" class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Kelas / Rombel</label>
                        <select id="filter_kelas" name="filter_kelas" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" v-model="filterKelas" @change="fetchData(1)">
                            <option value="">-- Semua Kelas --</option>
                            <option v-for="k in listKelas" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                        </select>
                    </div>

                    <!-- Filter 3: Status Siswa -->
                    <div class="flex-1 min-w-[150px]" v-if="activeTab === 'siswa'">
                        <label for="filter_status" class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Status Siswa</label>
                        <select id="filter_status" name="filter_status" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" v-model="filterStatus" @change="fetchData(1)">
                            <option value="Aktif">Aktif</option>
                            <option value="Lulus">Lulus</option>
                            <option value="Pindah">Pindah</option>
                        </select>
                    </div>

                    <!-- Button Cari & Reset -->
                    <div class="flex gap-2">
                        <button type="submit" class="h-10 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition-colors shadow-sm flex items-center gap-1.5 border-0">
                            <i class="bi bi-search"></i> Cari
                        </button>
                        <button type="button" @click="resetFilters" class="h-10 px-4 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-sm transition-colors cursor-pointer">
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table Action Filters -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div class="d-flex align-items-center gap-2 order-2 order-md-1">
                    <label for="per_page_select" class="fs-8 text-muted mb-0">Tampilkan</label>
                    <select id="per_page_select" name="per_page" aria-label="Tampilkan baris data" class="form-select form-select-sm rounded-3" v-model="perPage" @change="fetchData(1)" style="width: 80px;">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="fs-8 text-muted">Baris</span>
                </div>
                
                <div class="search-box-wrapper order-1 order-md-2 w-100" style="max-width: 350px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search text-muted"></i></span>
                        <label for="search_input" class="visually-hidden">Cari data pengguna</label>
                        <input id="search_input" name="search" aria-label="Cari data pengguna" type="text" class="form-control bg-light border-start-0 rounded-end-3" placeholder="Cari nama, email, NISN, NIS..." v-model="search" @input="debounceSearch">
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
                        <!-- Head Table Siswa -->
                        <tr v-if="activeTab === 'siswa'">
                            <th style="width: 50px;">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>Nama Lengkap</th>
                            <th>Jenjang</th>
                            <th>Kelas</th>
                            <th>NISN & NIS</th>
                            <th class="text-center">L/P</th>
                            <th>TTL</th>
                            <th>Alamat</th>
                            <th>Akun User & Email</th>
                            <th>Kelengkapan Data</th>
                            <th class="text-center" style="width: 120px;">Status Siswa</th>
                            <th class="text-center" style="width: 160px;">Aksi</th>
                        </tr>
                        <!-- Head Table Mutasi -->
                        <tr v-else-if="activeTab === 'mutasi'">
                            <th style="width: 50px;">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>Nama Lengkap</th>
                            <th>Kelas</th>
                            <th>NISN & NIS</th>
                            <th>Keluar Karena</th>
                            <th>Tanggal Keluar</th>
                            <th>Alasan / Keterangan</th>
                            <th class="text-center" style="width: 160px;">Aksi</th>
                        </tr>
                        <!-- Head Table Staff (Guru, Karyawan, Operator) -->
                        <tr v-else>
                            <th style="width: 50px;">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Peran / Role</th>
                            <th class="text-center" style="width: 120px;">Status Akun</th>
                            <th class="text-center" style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <!-- Loop Data Siswa -->
                        <template v-if="activeTab === 'siswa'">
                            <tr v-for="(item, idx) in listData" :key="item.id" :class="{'table-light-danger text-muted': trashMode}">
                                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                <td v-if="userRole === 'super_admin'" class="fw-semibold text-secondary fs-8">{{ item.nama_sekolah || '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2 bg-light-primary fw-bold">
                                            {{ getInitials(item.nama_lengkap) }}
                                        </div>
                                        <span class="fw-semibold text-dark">{{ item.nama_lengkap }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ item.nama_jenjang || '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light-primary border">{{ item.nama_kelas || '-' }}</span>
                                </td>
                                <td>
                                    <div><small class="text-muted d-block">NISN: <span class="font-monospace text-dark">{{ item.nisn || '-' }}</span></small></div>
                                    <div><small class="text-muted">NIS: <span class="font-monospace text-dark">{{ item.nis || '-' }}</span></small></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge" :class="item.jenis_kelamin === 'L' ? 'bg-info text-dark' : 'bg-warning text-dark'">
                                        {{ item.jenis_kelamin }}
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
                                    <div v-if="item.kontak_email || item.email">
                                        <a :href="'mailto:' + (item.kontak_email || item.email)" class="text-decoration-none font-monospace">{{ item.kontak_email || item.email }}</a>
                                    </div>
                                    <span v-else class="badge bg-light text-muted border">Tanpa Akun</span>
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
                                    <span v-if="item.status === 'Aktif'" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                                    <span v-else-if="item.status === 'Lulus'" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Lulus</span>
                                    <span v-else-if="item.status === 'Pindah'" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Pindah</span>
                                    <span v-else class="badge bg-secondary">-</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2" v-if="!trashMode">
                                        <template v-if="userRole === 'siswa'">
                                            <button class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1 fs-8" @click="openEditModal(item)">
                                                <i class="bi bi-pencil-square me-1"></i>Lihat/Perbarui Data
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
                                        <span class="fw-semibold text-dark">{{ item.nama_lengkap }}</span>
                                    </div>
                                </td>
                                <td class="font-monospace"><a :href="'mailto:'+item.email" class="text-decoration-none">{{ item.email }}</a></td>
                                <td>
                                    <span class="badge bg-light text-secondary border text-capitalize px-2.5 py-1.5 fs-8">
                                        <span v-if="activeTab === 'guru'">
                                            {{ item.nama_role === 'guru' ? ('Guru' + (item.is_bk == 1 || item.is_bk === true ? ', Guru BK' : '') + (item.is_kesiswaan == 1 || item.is_kesiswaan === true ? ', Kesiswaan' : '') + (item.is_humas == 1 || item.is_humas === true ? ', Waka HUMAS' : '') + (item.is_kurikulum == 1 || item.is_kurikulum === true ? ', Waka Kurikulum' : '') + (item.is_sarpras == 1 || item.is_sarpras === true ? ', Waka Sarpras' : '')) : (item.nama_role === 'operator_sekolah' ? 'Operator Sekolah' : (item.nama_role === 'guru_bk' ? 'Guru BK' : (item.nama_role === 'kesiswaan' ? 'Kesiswaan' : item.nama_role))) }}
                                        </span>
                                        <span v-else>
                                            {{ item.nama_role }}
                                        </span>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block" v-if="!trashMode">
                                        <input :id="'status_switch_' + item.id" :name="'status_switch_' + item.id" aria-label="Ubah status aktif pengguna" class="form-check-input" type="checkbox" role="switch" 
                                               :checked="item.status === 'active'" @change="toggleStatus(item.id)">
                                    </div>
                                    <span v-else class="badge bg-danger rounded-pill px-2 py-1 fs-9">Terhapus</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2" v-if="!trashMode">
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
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                <span class="fs-8 text-muted">Menampilkan {{ from }} s.d. {{ to }} dari {{ total }} baris</span>
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
                                <a href="/SINTA-SaaS/api/v1/siswa/import/template" class="text-decoration-none fs-9 fw-bold text-success" download>
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
                                <select id="form_tenant_id" name="tenant_id" class="form-select rounded-3" :class="{'is-invalid': errors.tenant_id}" v-model="form.tenant_id" :disabled="isEditMode" required>
                                    <option value="" disabled>-- Pilih Sekolah --</option>
                                    <option v-for="t in listTenants" :value="t.id" :key="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                                <div class="invalid-feedback">{{ getError('tenant_id') }}</div>
                            </div>
                            
                            <!-- Form inputs khusus kategori SISWA -->
                            <template v-if="activeTab === 'siswa'">
                                <div class="col-12 col-md-6">
                                    <label for="form_nama_lengkap" class="form-label fw-semibold fs-8 text-muted mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input id="form_nama_lengkap" name="nama_lengkap" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.nama_lengkap}" v-model="form.nama_lengkap" placeholder="Nama lengkap siswa" required>
                                    <div class="invalid-feedback">{{ getError('nama_lengkap') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_jenis_kelamin" class="form-label fw-semibold fs-8 text-muted mb-1">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select id="form_jenis_kelamin" name="jenis_kelamin" class="form-select rounded-3" :class="{'is-invalid': errors.jenis_kelamin}" v-model="form.jenis_kelamin" required>
                                        <option value="" disabled>-- Pilih Jenis Kelamin --</option>
                                        <option value="L">Laki-laki (L)</option>
                                        <option value="P">Perempuan (P)</option>
                                    </select>
                                    <div class="invalid-feedback">{{ getError('jenis_kelamin') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_nisn" class="form-label fw-semibold fs-8 text-muted mb-1">NISN <small class="text-muted">(10 Digit - Opsional)</small></label>
                                    <input id="form_nisn" name="nisn" type="text" class="form-control rounded-3 font-monospace" :class="{'is-invalid': errors.nisn}" v-model="form.nisn" placeholder="Contoh: 0054231901" maxlength="10">
                                    <div class="invalid-feedback">{{ getError('nisn') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_nis" class="form-label fw-semibold fs-8 text-muted mb-1">NIS <small class="text-muted">(Nomor Induk Siswa - Opsional)</small></label>
                                    <input id="form_nis" name="nis" type="text" class="form-control rounded-3 font-monospace" :class="{'is-invalid': errors.nis}" v-model="form.nis" placeholder="Contoh: 2026102">
                                    <div class="invalid-feedback">{{ getError('nis') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_tempat_lahir" class="form-label fw-semibold fs-8 text-muted mb-1">Tempat Lahir</label>
                                    <input id="form_tempat_lahir" name="tempat_lahir" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.tempat_lahir}" v-model="form.tempat_lahir" placeholder="Tempat lahir">
                                    <div class="invalid-feedback">{{ getError('tempat_lahir') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_tanggal_lahir" class="form-label fw-semibold fs-8 text-muted mb-1">Tanggal Lahir</label>
                                    <input id="form_tanggal_lahir" name="tanggal_lahir" type="date" class="form-control rounded-3" :class="{'is-invalid': errors.tanggal_lahir}" v-model="form.tanggal_lahir">
                                    <div class="invalid-feedback">{{ getError('tanggal_lahir') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_nama_wali" class="form-label fw-semibold fs-8 text-muted mb-1">Nama Wali Siswa</label>
                                    <input id="form_nama_wali" name="nama_wali" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.nama_wali}" v-model="form.nama_wali" placeholder="Nama ayah/ibu/wali">
                                    <div class="invalid-feedback">{{ getError('nama_wali') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_kontak_wali" class="form-label fw-semibold fs-8 text-muted mb-1">Kontak Wali Siswa</label>
                                    <input id="form_kontak_wali" name="kontak_wali" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kontak_wali}" v-model="form.kontak_wali" placeholder="No. HP / Kontak wali">
                                    <div class="invalid-feedback">{{ getError('kontak_wali') }}</div>
                                </div>

                                <div class="col-12">
                                    <label for="form_alamat" class="form-label fw-semibold fs-8 text-muted mb-1">Alamat Lengkap</label>
                                    <textarea id="form_alamat" name="alamat" class="form-control rounded-3" :class="{'is-invalid': errors.alamat}" v-model="form.alamat" rows="2" placeholder="Alamat tinggal siswa"></textarea>
                                    <div class="invalid-feedback">{{ getError('alamat') }}</div>
                                </div>

                                <div class="col-12 border-top my-3 pt-3">
                                    <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-shield-lock me-1"></i>Akses Akun Siswa (Opsional)</h6>
                                    <p class="text-muted fs-8 mb-3">Isi email & password di bawah jika ingin siswa memiliki akun login tersendiri.</p>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_email" class="form-label fw-semibold fs-8 text-muted mb-1">Email Akun</label>
                                    <input id="form_email" name="email" type="email" class="form-control rounded-3 font-monospace" :class="{'is-invalid': errors.email}" v-model="form.email" placeholder="siswa@sekolah.sch.id" autocomplete="email">
                                    <div class="invalid-feedback">{{ getError('email') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_password" class="form-label fw-semibold fs-8 text-muted mb-1">Password</label>
                                    <input id="form_password" name="password" type="password" class="form-control rounded-3" :class="{'is-invalid': errors.password}" v-model="form.password" :placeholder="isEditMode ? 'Kosongkan jika tidak ingin diubah' : 'Min. 6 karakter (default: siswa123)'" autocomplete="new-password">
                                    <div class="invalid-feedback">{{ getError('password') }}</div>
                                </div>
                            </template>

                            <!-- Form inputs khusus kategori STAFF (Guru, Karyawan, Operator) -->
                            <template v-else>
                                <div class="col-12">
                                    <label for="form_staff_nama_lengkap" class="form-label fw-semibold fs-8 text-muted mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input id="form_staff_nama_lengkap" name="nama_lengkap" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.nama_lengkap}" v-model="form.nama_lengkap" placeholder="Nama lengkap beserta gelar" required>
                                    <div class="invalid-feedback">{{ getError('nama_lengkap') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_staff_email" class="form-label fw-semibold fs-8 text-muted mb-1">Email <span class="text-danger">*</span></label>
                                    <input id="form_staff_email" name="email" type="email" class="form-control rounded-3 font-monospace" :class="{'is-invalid': errors.email}" v-model="form.email" placeholder="nama@sekolah.sch.id" required autocomplete="email">
                                    <div class="invalid-feedback">{{ getError('email') }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form_staff_password" class="form-label fw-semibold fs-8 text-muted mb-1">Password <span class="text-danger" v-if="!isEditMode">*</span></label>
                                    <input id="form_staff_password" name="password" type="password" class="form-control rounded-3" :class="{'is-invalid': errors.password}" v-model="form.password" :placeholder="isEditMode ? 'Kosongkan jika tidak ingin diubah' : 'Min. 6 karakter'" :required="!isEditMode" autocomplete="new-password">
                                    <div class="invalid-feedback">{{ getError('password') }}</div>
                                </div>

                                <!-- Checkbox Role Tambahan (hanya muncul saat activeTab === 'guru') -->
                                <div class="col-12 mt-3" v-if="activeTab === 'guru'">
                                    <div class="border rounded-3 p-3 bg-light-secondary">
                                        <h6 class="fw-bold fs-7 text-dark mb-3"><i class="bi bi-person-badge me-2"></i>Peran Tambahan (Opsional)</h6>
                                        <p class="text-muted fs-8 mb-3">Centang peran di bawah ini jika guru tersebut merangkap jabatan lain.</p>
                                        
                                        <div class="form-check mb-3">
                                            <input id="isBkCheckbox" name="is_bk" class="form-check-input border-slate-300" type="checkbox" v-model="form.is_bk">
                                            <label class="form-check-label fw-semibold fs-8 text-slate-700" for="isBkCheckbox">
                                                Guru BK (Bimbingan Konseling)
                                            </label>
                                            <p class="text-muted fs-9 mb-0 mt-1">Dapat mengakses modul Bimbingan Konseling.</p>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input id="isKesiswaanCheckbox" name="is_kesiswaan" class="form-check-input border-slate-300" type="checkbox" v-model="form.is_kesiswaan">
                                            <label class="form-check-label fw-semibold fs-8 text-slate-700" for="isKesiswaanCheckbox">
                                                Staf/Guru Kesiswaan
                                            </label>
                                            <p class="text-muted fs-9 mb-0 mt-1">Dapat mengakses dan mengunci modul Ekstrakurikuler.</p>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input id="isHumasCheckbox" name="is_humas" class="form-check-input border-slate-300" type="checkbox" v-model="form.is_humas">
                                            <label class="form-check-label fw-semibold fs-8 text-slate-700" for="isHumasCheckbox">
                                                Waka HUMAS
                                            </label>
                                            <p class="text-muted fs-9 mb-0 mt-1">Dapat mengelola informasi publik dan relasi masyarakat.</p>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input id="isKurikulumCheckbox" name="is_kurikulum" class="form-check-input border-slate-300" type="checkbox" v-model="form.is_kurikulum">
                                            <label class="form-check-label fw-semibold fs-8 text-slate-700" for="isKurikulumCheckbox">
                                                Waka Kurikulum
                                            </label>
                                            <p class="text-muted fs-9 mb-0 mt-1">Dapat mengelola jadwal dan kurikulum sekolah.</p>
                                        </div>
                                        
                                        <div class="form-check mb-0">
                                            <input id="isSarprasCheckbox" name="is_sarpras" class="form-check-input border-slate-300" type="checkbox" v-model="form.is_sarpras">
                                            <label class="form-check-label fw-semibold fs-8 text-slate-700" for="isSarprasCheckbox">
                                                Waka Sarpras
                                            </label>
                                            <p class="text-muted fs-9 mb-0 mt-1">Dapat mengelola pendataan sarana dan prasarana sekolah.</p>
                                        </div>
                                    </div>
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
                        <span class="text-muted fs-8">Total: <strong>{{ total }}</strong> siswa aktif ditemukan.</span>
                    </div>
                    <button class="btn btn-success btn-sm rounded-3 px-4 d-flex align-items-center gap-1.5" @click="printBulk">
                        <i class="bi bi-printer-fill"></i>
                        Cetak Rapot Kelas (Bulk)
                    </button>
                </div>

                <div v-if="listData.length === 0" class="aksi-empty-state text-center py-5">
                    <i class="bi bi-person-slash fs-1 text-muted opacity-50"></i>
                    <p class="text-muted mt-2 mb-0">Tidak ada siswa aktif di kelas ini.</p>
                </div>

                <div class="table-responsive" v-if="listData.length > 0">
                    <table class="table table-hover align-middle mb-4" style="font-size:0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Lengkap</th>
                                <th>NISN</th>
                                <th>NIS</th>
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
                                    <button class="btn btn-sm btn-outline-primary rounded-2 px-3 py-1 fs-8 d-inline-flex align-items-center gap-1" @click="printSingle(item.id)">
                                        <i class="bi bi-printer"></i> Cetak
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-3" v-if="totalPages > 1">
                    <span class="fs-8 text-muted">Menampilkan {{ from }} s.d. {{ to }} dari {{ total }} baris</span>
                    <nav>
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
                    { id: 'naikkan_kelas', name: 'Naikkan Kelas', icon: 'bi bi-arrow-up-circle' },
                    { id: 'mutasi', name: 'Log Mutasi & Putus Sekolah', icon: 'bi bi-person-x' },
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
                
                // New Filters state
                filterTenantId: '',
                filterKelas: '',
                filterStatus: 'Aktif',
                listKelas: [],

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
                    this.aksiKelasAsalId = '';
                    this.aksiKelasTujuanId = '';
                    this.aksiListSiswa = [];
                    this.aksiSelectedIds = [];
                    this.aksiSelectAll = false;
                    // Load kelas langsung untuk admin sekolah
                    if (this.userRole !== 'super_admin') {
                        this.fetchAksiKelas();
                    } else if (this.aksiTenantId) {
                        this.fetchAksiKelas();
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

                axios.post('/SINTA-SaaS/api/v1/siswa/import', formData, {
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
            getActiveTabName() {
                const tab = this.tabs.find(t => t.id === this.activeTab);
                return tab ? tab.name : '';
            },
            downloadExcel() {
                let url = '/SINTA-SaaS/pengguna/download-excel';
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

                if (this.activeTab === 'siswa' || this.activeTab === 'mutasi' || this.activeTab === 'profile_rapot') {
                    params.status = this.activeTab === 'profile_rapot' ? 'Aktif' : this.filterStatus;
                    params.id_kelas = this.filterKelas;
                    if (this.userRole === 'super_admin') {
                        params.tenant_id = this.filterTenantId;
                    }
                }

                axios.get('/SINTA-SaaS/api/v1/pengguna', {
                    params: params
                }).then(res => {
                    this.listData = res.data.data;
                    this.totalPages = res.data.last_page;
                    this.total = res.data.total;
                    this.from = res.data.from;
                    this.to = res.data.to;
                    this.loading = false;
                }).catch(err => {
                    this.loading = false;
                    this.toast.fire({ icon: 'error', title: (err && err.response && err.response.data && err.response.data.error) || 'Gagal memuat data dari server.' });
                });
            },
            fetchTenants() {
                axios.get('/SINTA-SaaS/api/v1/pengguna/tenants')
                     .then(res => {
                          this.listTenants = res.data.data;
                     })
                     .catch(err => {
                          console.error("Gagal mengambil data sekolah:", err);
                     });
            },
            fetchKelas() {
                let tenantId = '';
                if (this.userRole === 'super_admin') {
                    tenantId = this.filterTenantId;
                }
                axios.get('/SINTA-SaaS/api/v1/pengguna/kelas', {
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
                    tenantId = this.aksiTenantId;
                }
                if (!tenantId && this.userRole === 'super_admin') {
                    this.tahunAjaranList = [];
                    this.aksiTahunAjaran = '';
                    return;
                }

                axios.get('/SINTA-SaaS/api/v1/pengguna/tahun-ajaran', {
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
                this.filterKelas = '';
                this.fetchKelas();
                this.fetchTahunAjaran();
                this.fetchData(1);
            },
            resetFilters() {
                this.filterStatus = 'Aktif';
                this.filterKelas = '';
                if (this.userRole === 'super_admin') {
                    this.filterTenantId = '';
                }
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
                if (this.activeTab === 'siswa') {
                    this.form = { 
                        nama_lengkap: '', 
                        jenis_kelamin: '', 
                        nisn: '', 
                        nis: '',
                        tempat_lahir: '',
                        tanggal_lahir: '',
                        alamat: '',
                        nama_wali: '',
                        kontak_wali: '',
                        email: '',
                        password: ''
                    };
                } else {
                    this.form = { 
                        nama_lengkap: '', 
                        email: '', 
                        password: '',
                        is_bk: false,
                        is_kesiswaan: false,
                        is_humas: false,
                        is_kurikulum: false,
                        is_sarpras: false
                    };
                }
                if (this.userRole === 'super_admin') {
                    this.form.tenant_id = '';
                }
            },
            openCreateModal() {
                if (this.activeTab === 'siswa') {
                    window.location.href = '/SINTA-SaaS/siswa/tambah';
                    return;
                }
                this.isEditMode = false;
                this.resetForm();
                this.modalObj.show();
            },
            openEditModal(item) {
                if (this.activeTab === 'siswa' || this.activeTab === 'mutasi') {
                    window.location.href = '/SINTA-SaaS/siswa/edit?id=' + item.id;
                    return;
                }
                this.isEditMode = true;
                this.errors = {};
                this.editId = item.id;
                
                this.form = {
                    nama_lengkap: item.nama_lengkap,
                    email: item.email,
                    password: '',
                    is_bk: item.is_bk == 1 || item.is_bk === true,
                    is_kesiswaan: item.is_kesiswaan == 1 || item.is_kesiswaan === true,
                    is_humas: item.is_humas == 1 || item.is_humas === true,
                    is_kurikulum: item.is_kurikulum == 1 || item.is_kurikulum === true,
                    is_sarpras: item.is_sarpras == 1 || item.is_sarpras === true
                };
                
                if (this.userRole === 'super_admin') {
                    this.form.tenant_id = item.tenant_id;
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

                axios.post('/SINTA-SaaS/api/v1/pengguna/simpan', payload)
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
                        axios.post('/SINTA-SaaS/api/v1/pengguna/hapus', { tab: this.activeTab, id: id })
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
                axios.post('/SINTA-SaaS/api/v1/pengguna/restore', { tab: this.activeTab, id: id })
                     .then(res => {
                          this.toast.fire({ icon: 'success', title: res.data.message });
                          this.fetchData(this.currentPage);
                      })
                     .catch(err => {
                          this.toast.fire({ icon: 'error', title: 'Gagal memulihkan data.' });
                      });
            },
            toggleStatus(id) {
                axios.post('/SINTA-SaaS/api/v1/pengguna/toggle-status', { tab: this.activeTab, id: id })
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
            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },
            getError(field) {
                return this.errors[field] ? this.errors[field][0] : '';
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
                axios.get('/SINTA-SaaS/api/v1/pengguna/aksi/kelas', { params })
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
                axios.get('/SINTA-SaaS/api/v1/pengguna/aksi/siswa', { params })
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

                    axios.post('/SINTA-SaaS/api/v1/pengguna/aksi/naikkan-kelas', payload)
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

                    axios.post('/SINTA-SaaS/api/v1/pengguna/aksi/luluskan', payload)
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

                axios.post('/SINTA-SaaS/api/v1/pengguna/quick-add-siswa', this.quickAddForm)
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
                
                const url = `/SINTA-SaaS/cetak-rapot?id=${encodeURIComponent(studentId)}&tempat=${encodeURIComponent(this.printTempat.trim())}&tanggal=${encodeURIComponent(this.printTanggal.trim())}`;
                window.open(url, '_blank');
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
                
                const url = `/SINTA-SaaS/cetak-rapot-kelas?kelas_id=${encodeURIComponent(this.filterKelas)}&tempat=${encodeURIComponent(this.printTempat.trim())}&tanggal=${encodeURIComponent(this.printTanggal.trim())}`;
                window.open(url, '_blank');
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

                axios.post('/SINTA-SaaS/api/v1/siswa/bulk-photo', formData, {
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
            }
        }
    });
}
</script>
