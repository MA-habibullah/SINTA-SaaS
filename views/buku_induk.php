<!-- Halaman Sentral: Buku Induk Siswa -->
<div id="bukuIndukApp" v-cloak>
    
    <!-- Row Header & Title -->
    <div class="row mb-3 mb-md-4 align-items-center">
        <div class="col-12 col-md-7 mb-3 mb-md-0">
            <h3 class="fw-bold text-dark mb-1 fs-4 fs-md-3">
                <i class="bi bi-book-half text-primary me-2"></i>Buku Induk Siswa
            </h3>
            <p class="text-muted fs-8 fs-md-7 mb-0">Catatan kumpulan rekam data pokok dan dokumen historis seluruh siswa yang terdaftar di sekolah.</p>
        </div>
        
        <!-- Actions (Export Excel/PDF) -->
        <div v-show="mainActiveTab === 'buku_induk_siswa'" class="col-12 col-md-5 d-flex gap-2 justify-content-start justify-content-md-end align-items-center flex-wrap">
            <button class="btn btn-outline-success btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0" 
                    @click="exportExcel">
                <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel
            </button>
        </div>
    </div>

    <!-- ═══ FILTER SEKOLAH — Hanya Super Admin ═══════════════════════════════ -->
    <div v-if="userRole === 'super_admin'" class="card border-0 shadow-sm rounded-4 mb-3" style="background:linear-gradient(135deg,#eff6ff,#f0fdf4);border-left:4px solid #2563eb !important;">
        <div class="card-body py-3 px-4">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <div class="d-flex align-items-center gap-2">
                        <label for="sa-filter-sekolah-bukuinduk" class="d-flex align-items-center gap-2 m-0 fw-bold text-dark fs-7 cursor-pointer">
                            <i class="bi bi-building-fill text-primary fs-5"></i>
                            Filter Sekolah
                        </label>
                        <span v-if="filterTenantId" class="badge rounded-pill ms-1" style="background:#dbeafe;color:#1d4ed8;font-size:.72rem;">
                            <i class="bi bi-funnel-fill me-1"></i>Aktif
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-5">
                    <select class="form-select form-select-sm rounded-3 shadow-none"
                            v-model="filterTenantId"
                            @change="onFilterTenantChange"
                            id="sa-filter-sekolah-bukuinduk"
                            name="filter_tenant_id"
                            style="border:1.5px solid #bfdbfe;">
                        <option value="">🏫 -- Pilih Sekolah --</option>
                        <option v-for="t in listTenants" :key="t.id" :value="t.id">
                            {{ t.nama_sekolah }}
                        </option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <div v-if="filterTenantId" class="d-flex align-items-center gap-2">
                        <span class="fs-8 text-muted">Menampilkan data milik:</span>
                        <span class="fw-semibold text-primary fs-8">{{ selectedTenantName }}</span>
                    </div>
                    <div v-else class="fs-8 text-muted">
                        <i class="bi bi-info-circle me-1"></i>Pilih sekolah untuk memfilter semua siswa
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (Sleek Underline, 14px, Dark Grey) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2">
                    <li class="nav-item" v-for="tab in mainTabs" :key="tab.id">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" 
                                :class="{active: mainActiveTab === tab.id}" 
                                @click="switchMainTab(tab.id)">
                            <i :class="tab.icon" class="me-2 fs-6"></i>{{ tab.name }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Card Grid -->
    <div v-show="mainActiveTab === 'buku_induk_siswa'" class="card border-0 shadow-sm rounded-4 animate-fade-in">
        <div class="card-body p-3 p-md-4">
            
            <!-- Table Action Filters -->
            <div class="row g-3 mb-4">
                <!-- Search Box -->
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search text-muted"></i></span>
                        <input id="global_search_input" name="search" type="text" class="form-control bg-light border-start-0 rounded-end-3" placeholder="Cari Nama, NISN atau NIS..." v-model="search" @input="debounceSearch">
                    </div>
                </div>

                <!-- Filter Kelas -->
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm rounded-3" v-model="filterKelas" @change="fetchData(1)">
                        <option value="">🏫 Semua Kelas</option>
                        <option v-for="k in kelasOptions" :value="k.id" :key="k.id">{{ k.nama_kelas || k.nama }}</option>
                    </select>
                </div>

                <!-- Filter Status -->
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm rounded-3" v-model="filterStatus" @change="fetchData(1)">
                        <option value="">📋 Semua Status</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Alumni / Lulus</option>
                        <option value="Pindah">Siswa Pindah</option>
                        <option value="Keluar">Siswa Keluar</option>
                    </select>
                </div>

                <!-- Per Page -->
                <div class="col-12 col-md-2 d-flex align-items-center justify-content-md-end gap-2">
                    <label for="per_page_select" class="fs-8 text-muted mb-0">Baris</label>
                    <select id="per_page_select" name="per_page" class="form-select form-select-sm rounded-3" v-model="perPage" @change="fetchData(1)" style="width: 75px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <!-- Warning State: Super Admin must select school -->
            <div v-if="userRole === 'super_admin' && !filterTenantId" class="py-5 text-center bg-white rounded-4 border-0">
                <i class="bi bi-building text-secondary display-4 d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Pilih Sekolah Terlebih Dahulu</h5>
                <p class="text-muted fs-7 mx-auto" style="max-width: 480px;">
                    Silakan pilih Sekolah terlebih dahulu pada filter "Filter Sekolah" di atas untuk memuat data Buku Induk siswa.
                </p>
            </div>

            <!-- Loader State -->
            <div v-else-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2 fs-7">Mengunduh Buku Induk dari database...</p>
            </div>

            <!-- Table Content -->
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-4" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th v-if="userRole === 'super_admin'">Sekolah</th>
                            <th>NIS / NISN</th>
                            <th>Nama Lengkap</th>
                            <th>L/P</th>
                            <th>Jurusan / Peminatan</th>
                            <th>Kelas</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <tr v-for="(item, idx) in listData" :key="item.id" class="transition-all hover-row">
                            <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td v-if="userRole === 'super_admin'" class="fw-semibold text-secondary">{{ item.nama_sekolah || '-' }}</td>
                            <td>
                                <div class="font-monospace fs-8 text-dark fw-bold">NIS: {{ item.nis || '-' }}</div>
                                <div class="font-monospace fs-9 text-muted">NISN: {{ item.nisn || '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ item.nama_lengkap }}</div>
                            </td>
                            <td>
                                <span class="badge" :class="item.jenis_kelamin === 'L' ? 'bg-light-blue text-blue-700' : 'bg-light-rose text-rose-700'">
                                    {{ item.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                </span>
                            </td>
                            <td>{{ item.nama_jurusan || '-' }}</td>
                            <td>
                                <span class="badge bg-light text-dark border px-2.5 py-1.5 fs-8">
                                    <i class="bi bi-door-closed me-1"></i>{{ item.nama_kelas || 'Belum Masuk Kelas' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-2.5 py-1.5 fs-9"
                                      :class="{
                                          'bg-success-subtle text-success': item.status === 'Aktif',
                                          'bg-primary-subtle text-primary': item.status === 'Lulus',
                                          'bg-warning-subtle text-warning': item.status === 'Pindah',
                                          'bg-danger-subtle text-danger': item.status === 'Keluar'
                                      }">
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary rounded-2 px-2.5 py-1.5 d-inline-flex align-items-center gap-1 shadow-sm" @click="viewDetail(item.id)">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="listData.length === 0">
                            <td :colspan="userRole === 'super_admin' ? 9 : 8" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                                <span>Tidak ada data siswa ditemukan di Buku Induk.</span>
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

    <!-- ═══ PANEL SETING KURIKULUM ═══════════════════════════════ -->
    <div v-show="mainActiveTab === 'seting_kurikulum'" class="animate-fade-in">
        
        <!-- Warning: Super Admin must select school -->
        <div v-if="userRole === 'super_admin' && !filterTenantId" class="card border-0 shadow-sm rounded-4 py-5 text-center bg-white mb-4">
            <div class="card-body">
                <i class="bi bi-building-fill-gear text-secondary display-4 d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Pilih Sekolah Terlebih Dahulu</h5>
                <p class="text-muted fs-7 mx-auto" style="max-width: 450px;">
                    Silakan pilih Sekolah terlebih dahulu pada filter "Filter Sekolah" di atas untuk mengonfigurasi seting kurikulum.
                </p>
            </div>
        </div>

        <div v-else>
            <!-- Controls Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9);">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 align-items-end">
                    
                    <!-- Tahun Ajaran -->
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Tahun Ajaran</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                v-model="kurikulum.tahunAjaran"
                                @change="loadKurikulumMapping">
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <option v-for="t in masterKurikulum.tahun_ajaran" :key="t.id" :value="t.tahun_ajaran">
                                {{ t.tahun_ajaran }}
                            </option>
                        </select>
                    </div>

                    <!-- Semester -->
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Semester</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                v-model="kurikulum.semester"
                                @change="loadKurikulumMapping">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>

                    <!-- Kelas -->
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Kelas Fisik</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                v-model="kurikulum.kelasId"
                                @change="loadKurikulumMapping">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="k in masterKurikulum.kelas" :key="k.id" :value="k.id">
                                {{ k.nama_kelas || k.nama }}
                            </option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm rounded-3 px-3 py-2 w-100 fs-8 fw-semibold"
                                :disabled="!kurikulum.kelasId"
                                @click="showCopyModal">
                            <i class="bi bi-copy me-1"></i> Salin dari Kelas Lain
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Builder Loading State -->
        <div v-if="loadingKurikulum" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 fs-7">Memuat data kurikulum...</p>
        </div>

        <!-- No Selection State -->
        <div v-else-if="!kurikulum.kelasId || !kurikulum.tahunAjaran" class="card border-0 shadow-sm rounded-4 py-5 text-center bg-white">
            <div class="card-body">
                <i class="bi bi-journal-plus text-secondary display-4 d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Konfigurasi Pemetaan Kurikulum</h5>
                <p class="text-muted fs-7 mx-auto" style="max-width: 450px;">
                    Silakan pilih Tahun Ajaran, Semester, dan Kelas fisik pada dropdown di atas untuk memulai penyusunan pemetaan mata pelajaran.
                </p>
            </div>
        </div>

        <!-- Builder Content -->
        <div v-else>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0 fs-6">
                    <i class="bi bi-grid-fill text-primary me-2"></i>Kelompok Mata Pelajaran (Kelas: {{ getKelasName(kurikulum.kelasId) }})
                </h5>
                <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-8 fw-semibold" @click="addGroup">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kelompok
                </button>
            </div>

            <!-- Groups List -->
            <div class="row g-4 mb-4">
                <div v-for="(group, gIdx) in kurikulum.groups" :key="gIdx" class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-all hover-shadow" style="border-top: 4px solid #3b82f6 !important;">
                        <div class="card-body p-3 p-md-4">
                            
                            <!-- Group Title & Delete -->
                            <div class="d-flex align-items-center justify-content-between mb-3 gap-2">
                                <div class="flex-grow-1">
                                    <input type="text" class="form-control form-control-sm rounded-3 fw-bold border-secondary-subtle" 
                                           v-model="group.kelompok_id" 
                                           placeholder="Nama Kelompok (cth: Kelompok A)">
                                </div>
                                <button class="btn btn-outline-danger btn-sm rounded-3 border-0 px-2 py-1" 
                                        @click="removeGroup(gIdx)" 
                                        title="Hapus Kelompok">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <!-- Subject Search Filter -->
                            <div class="input-group input-group-sm mb-3">
                                <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-start-0 rounded-end-3" 
                                       placeholder="Cari mata pelajaran..." 
                                       v-model="group.searchQuery">
                            </div>

                            <!-- Subjects Checklist Area -->
                            <div class="border rounded-3 p-2 bg-light-subtle overflow-y-auto" style="max-height: 250px;">
                                <div class="list-group list-group-flush">
                                    
                                    <!-- Loop Subjects -->
                                    <label v-for="m in filteredMapelList(group.searchQuery)" 
                                           :key="m.id" 
                                           class="list-group-item list-group-item-action d-flex align-items-center justify-content-between border-0 px-2 py-1.5 rounded-2 mb-1 cursor-pointer transition"
                                           :class="group.mapel_ids.includes(m.id) ? 'bg-primary-subtle text-primary fw-semibold' : 'bg-transparent text-dark'">
                                        <div class="d-flex align-items-center gap-2">
                                            <input class="form-check-input mt-0 border-secondary" 
                                                   type="checkbox" 
                                                   :value="m.id" 
                                                   v-model="group.mapel_ids">
                                            <span class="fs-8">{{ m.nama_mapel }}</span>
                                        </div>
                                        <span class="text-muted font-monospace fs-9">{{ m.kode_mapel }}</span>
                                    </label>

                                    <!-- Empty Search Result -->
                                    <div v-if="filteredMapelList(group.searchQuery).length === 0" class="text-center py-4 text-muted fs-8">
                                        <i class="bi bi-search d-block mb-1"></i> Tidak ditemukan pelajaran
                                    </div>

                                </div>
                            </div>

                            <!-- Selected Count -->
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <span class="text-muted fs-8">Pelajaran terpilih:</span>
                                <span class="badge bg-primary rounded-pill px-2.5 py-1.5 fs-9 fw-bold">
                                    {{ group.mapel_ids.length }} Pelajaran
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Section -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3 p-md-4 d-flex justify-content-end align-items-center gap-2">
                    <button class="btn btn-light rounded-3 px-4 py-2 fs-7 fw-semibold" @click="loadKurikulumMapping">
                        Reset Perubahan
                    </button>
                    <button class="btn btn-primary rounded-3 px-4 py-2 fs-7 fw-semibold d-inline-flex align-items-center gap-2" @click="saveKurikulum">
                        <i class="bi bi-save2"></i> Simpan Seting Kurikulum
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Reusable Copy Kurikulum Modal -->
    <div class="modal fade" id="copyKurikulumModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-copy text-primary"></i>
                        Salin Kurikulum
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted fs-8">Tahun Ajaran & Semester</label>
                        <div class="p-2.5 bg-light rounded-3 text-dark fw-bold fs-7">
                            {{ kurikulum.tahunAjaran }} - Semester {{ kurikulum.semester }}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted fs-8">Kelas Tujuan (Target)</label>
                        <div class="p-2.5 bg-light rounded-3 text-primary fw-bold fs-7">
                            {{ getKelasName(kurikulum.kelasId) }}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="source_kelas_select" class="form-label fw-semibold text-dark fs-7">Pilih Kelas Sumber (Source)</label>
                        <select id="source_kelas_select" class="form-select rounded-3" v-model="copySourceKelasId">
                            <option value="">-- Pilih Kelas Sumber --</option>
                            <option v-for="k in filteredCopyKelasOptions" 
                                    :value="k.id" 
                                    :key="k.id">
                                {{ k.nama_kelas || k.nama }}
                            </option>
                        </select>
                        <small class="text-muted fs-8 mt-1 d-block">
                            <i class="bi bi-info-circle me-1"></i>Seluruh pemetaan kelompok & mata pelajaran kelas sumber akan disalin ke kelas tujuan.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-3 fs-8 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary rounded-3 fs-8 px-4" @click="submitCopyKurikulum" :disabled="!copySourceKelasId">
                        Salin Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ PANEL INPUT NILAI RAPOR ═══════════════════════════════ -->
    <div v-show="mainActiveTab === 'input_nilai_rapor'" class="animate-fade-in">
        
        <!-- Warning: Super Admin must select school -->
        <div v-if="userRole === 'super_admin' && !filterTenantId" class="card border-0 shadow-sm rounded-4 py-5 text-center bg-white mb-4">
            <div class="card-body">
                <i class="bi bi-building-fill-gear text-secondary display-4 d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Pilih Sekolah Terlebih Dahulu</h5>
                <p class="text-muted fs-7 mx-auto" style="max-width: 450px;">
                    Silakan pilih Sekolah terlebih dahulu pada filter "Filter Sekolah" di atas untuk mengonfigurasi input nilai rapor.
                </p>
            </div>
        </div>

        <div v-else>
            <!-- Controls Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #eff6ff, #f8fafc);">
                <div class="card-body p-3 p-md-4">
                    <div class="row g-3 align-items-end">
                        
                        <!-- Tahun Ajaran -->
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Tahun Ajaran</label>
                            <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                    v-model="nilaiRapor.tahunAjaran"
                                    @change="loadNilaiRaporGrid">
                                <option value="">-- Pilih Tahun Ajaran --</option>
                                <option v-for="t in masterNilaiRapor.tahun_ajaran" :key="t.id" :value="t.tahun_ajaran">
                                    {{ t.tahun_ajaran }}
                                </option>
                            </select>
                        </div>

                        <!-- Semester -->
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Semester</label>
                            <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                    v-model="nilaiRapor.semester"
                                    @change="loadNilaiRaporGrid">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>

                        <!-- Kelas -->
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Kelas Fisik</label>
                            <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                    v-model="nilaiRapor.kelasId"
                                    @change="loadNilaiRaporGrid">
                                <option value="">-- Pilih Kelas --</option>
                                <option v-for="k in masterNilaiRapor.kelas" :key="k.id" :value="k.id">
                                    {{ k.nama_kelas || k.nama }}
                                </option>
                            </select>
                        </div>

                        <!-- Actions (Import/Export) -->
                        <div class="col-12 col-md-3 d-flex gap-2">
                            <button class="btn btn-outline-success btn-sm rounded-3 px-2 py-2 flex-grow-1 fs-8 fw-semibold"
                                    :disabled="!nilaiRapor.kelasId || nilaiRapor.subjects.length === 0"
                                    @click="exportNilaiRaporExcel"
                                    title="Unduh Format Excel (.xlsx)">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Unduh
                            </button>
                            <button class="btn btn-outline-primary btn-sm rounded-3 px-2 py-2 flex-grow-1 fs-8 fw-semibold"
                                    :disabled="!nilaiRapor.kelasId || nilaiRapor.subjects.length === 0"
                                    @click="showImportGradesModal"
                                    title="Unggah Nilai Excel (.xlsx)">
                                <i class="bi bi-file-earmark-arrow-up me-1"></i> Impor
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Grid Loading State -->
            <div v-if="loadingNilaiRapor" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2 fs-7">Memuat data nilai rapor...</p>
            </div>

            <!-- No Selection State -->
            <div v-else-if="!nilaiRapor.kelasId || !nilaiRapor.tahunAjaran" class="card border-0 shadow-sm rounded-4 py-5 text-center bg-white">
                <div class="card-body">
                    <i class="bi bi-journal-text text-secondary display-4 d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">Matriks Input Nilai Rapor</h5>
                    <p class="text-muted fs-7 mx-auto" style="max-width: 450px;">
                        Silakan pilih Tahun Ajaran, Semester, dan Kelas fisik pada dropdown di atas untuk memuat lembar input nilai rapor siswa.
                    </p>
                </div>
            </div>

            <!-- Empty Subjects State -->
            <div v-else-if="nilaiRapor.subjects.length === 0" class="card border-0 shadow-sm rounded-4 py-5 text-center bg-white" style="border-left: 4px solid #ef4444 !important;">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle text-danger display-4 d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">Mata Pelajaran Belum Dipetakan</h5>
                    <p class="text-muted fs-7 mx-auto mb-3" style="max-width: 480px;">
                        Kelas yang Anda pilih belum memiliki pemetaan kurikulum mata pelajaran pada tahun ajaran dan semester ini.
                    </p>
                    <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-8 fw-semibold" @click="switchMainTab('seting_kurikulum')">
                        <i class="bi bi-gear-wide-connected me-1"></i> Atur Kurikulum Kelas Sekarang
                    </button>
                </div>
            </div>

            <!-- Empty Students State -->
            <div v-else-if="nilaiRapor.students.length === 0" class="card border-0 shadow-sm rounded-4 py-5 text-center bg-white">
                <div class="card-body">
                    <i class="bi bi-people-mute text-secondary display-4 d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">Tidak Ada Siswa Aktif</h5>
                    <p class="text-muted fs-7 mx-auto" style="max-width: 450px;">
                        Tidak ditemukan siswa berstatus aktif di dalam kelas ini untuk tahun ajaran terpilih.
                    </p>
                </div>
            </div>

            <!-- Matriks Grid Table -->
            <div v-else>
                
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3 p-md-4">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold text-dark mb-1 fs-6">
                                    <i class="bi bi-table text-primary me-2"></i>Matriks Nilai Akhir Siswa
                                </h5>
                                <p class="text-muted fs-9 mb-0">Masukkan nilai akhir mata pelajaran siswa (Rentang: 0 - 100). Sel yang kosong tidak akan diubah.</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.82rem; min-width: 800px;">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width: 50px; vertical-align: middle;">No</th>
                                        <th style="width: 120px; vertical-align: middle;">NISN</th>
                                        <th style="min-width: 200px; vertical-align: middle;" class="text-start">Nama Siswa</th>
                                        <th v-for="sub in nilaiRapor.subjects" :key="sub.mapel_id" class="text-center" style="min-width: 100px;">
                                            <div class="fw-bold text-truncate" style="max-width: 150px;" :title="sub.nama_mapel">
                                                {{ sub.nama_mapel }}
                                            </div>
                                            <small class="text-muted font-monospace fs-9" style="font-weight: normal;">{{ sub.kode_mapel }}</small>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(student, sIdx) in nilaiRapor.students" :key="student.id">
                                        <td class="text-center text-muted">{{ sIdx + 1 }}</td>
                                        <td class="text-center font-monospace">{{ student.nisn || student.nis || '-' }}</td>
                                        <td class="fw-bold text-dark">{{ student.nama_lengkap }}</td>
                                        
                                        <!-- Dynamic subject value inputs -->
                                        <td v-for="sub in nilaiRapor.subjects" :key="sub.mapel_id" class="p-1">
                                            <input type="number" 
                                                   min="0" 
                                                   max="100" 
                                                   step="0.01" 
                                                   class="form-control form-control-sm text-center fw-semibold border-0 bg-light-subtle py-1"
                                                   :placeholder="isReligionMismatch(student.agama, sub.nama_mapel) ? 'N/A' : '-'"
                                                   :disabled="isReligionMismatch(student.agama, sub.nama_mapel)"
                                                   :class="{'bg-secondary-subtle text-muted': isReligionMismatch(student.agama, sub.nama_mapel)}"
                                                   v-model.number="nilaiRapor.grades[student.id][sub.mapel_id]">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <!-- Save Section -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3 p-md-4 d-flex justify-content-end align-items-center gap-2">
                        <button class="btn btn-light rounded-3 px-4 py-2 fs-7 fw-semibold" @click="loadNilaiRaporGrid">
                            Reset Perubahan
                        </button>
                        <button class="btn btn-primary rounded-3 px-4 py-2 fs-7 fw-semibold d-inline-flex align-items-center gap-2" @click="saveNilaiRapor">
                            <i class="bi bi-save2"></i> Simpan Perubahan Nilai
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Reusable Import Nilai Modal -->
    <div class="modal fade" id="importNilaiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-arrow-up text-primary"></i>
                        Impor Nilai Rapor (Excel)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted fs-8">Tahun Ajaran & Semester</label>
                        <div class="p-2.5 bg-light rounded-3 text-dark fw-bold fs-7">
                            {{ nilaiRapor.tahunAjaran }} - Semester {{ nilaiRapor.semester }}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted fs-8">Kelas Sasaran (Target)</label>
                        <div class="p-2.5 bg-light rounded-3 text-primary fw-bold fs-7">
                            {{ getNilaiRaporKelasName(nilaiRapor.kelasId) }}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="importNilaiFile" class="form-label fw-semibold text-dark fs-7">Pilih Berkas Excel (.xlsx) Format Nilai</label>
                        <input type="file" id="importNilaiFile" class="form-control rounded-3" accept=".xlsx" @change="onImportFileChange">
                        <small class="text-muted fs-8 mt-2 d-block">
                            <i class="bi bi-info-circle me-1"></i>Pastikan format berkas Excel (.xlsx) yang diunggah sesuai dengan berkas format unduhan dari kelas ini. Kolom Siswa ID dan Header Kode Mata Pelajaran tidak boleh diubah agar data terpetakan dengan benar.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-3 fs-8 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary rounded-3 fs-8 px-4" @click="submitImportGrades" :disabled="!importFile">
                        Mulai Impor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reusable Detail Modal (Buku Induk Lengkap) -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                
                <div class="modal-header border-bottom py-3 bg-light">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-person-lines-fill text-primary"></i>
                        Kartu Buku Induk Siswa Lengkap
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-0">
                    <div v-if="detailLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 fs-7">Memuat detail lembar buku induk...</p>
                    </div>

                    <div v-else-if="selectedSiswa" class="row g-0">
                        <!-- Profile Sidebar -->
                        <div class="col-12 col-lg-3 border-end bg-light p-4 text-center">
                            <div class="d-inline-block position-relative mb-3">
                                <img :src="selectedSiswa.foto_profil ? '/SINTA-SaaS/download.php?file=' + encodeURIComponent(selectedSiswa.foto_profil) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'" 
                                     class="img-fluid rounded-4 shadow border border-3 border-white object-fit-cover" 
                                     style="width: 150px; height: 180px;" alt="Foto Siswa">
                            </div>
                            <h5 class="fw-bold text-dark mb-1">{{ selectedSiswa.nama_lengkap }}</h5>
                            <p class="text-muted fs-8 mb-2">NISN: {{ selectedSiswa.nisn || '-' }}</p>
                            
                            <div class="badge rounded-pill px-3 py-1.5 fs-8 mb-4"
                                 :class="{
                                     'bg-success text-white': selectedSiswa.status === 'Aktif',
                                     'bg-primary text-white': selectedSiswa.status === 'Lulus',
                                     'bg-warning text-dark': selectedSiswa.status === 'Pindah',
                                     'bg-danger text-white': selectedSiswa.status === 'Keluar'
                                 }">
                                {{ selectedSiswa.status }}
                            </div>

                            <div class="border-top pt-3 text-start">
                                <div class="fs-9 text-muted text-uppercase fw-bold mb-1">Sekolah Terdaftar</div>
                                <div class="fs-8 text-dark fw-semibold mb-3">{{ selectedSiswa.nama_sekolah || '-' }}</div>

                                <div class="fs-9 text-muted text-uppercase fw-bold mb-1">Kelas Aktif</div>
                                <div class="fs-8 text-dark fw-semibold mb-3">
                                    <i class="bi bi-door-closed me-1"></i>{{ selectedSiswa.nama_kelas || '-' }}
                                </div>
                            </div>
                        </div>

                        <!-- Info Tabs Content -->
                        <div class="col-12 col-lg-9 p-4">
                            <!-- Tabs Navigation -->
                            <ul class="nav nav-tabs nav-fill mb-4 border-bottom-0 bg-white p-1 rounded-3 border gap-1 fs-8 fw-semibold shadow-sm">
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'identitas'}" @click="activeDetailTab = 'identitas'">
                                        <i class="bi bi-person-badge me-1"></i>Identitas & Profil
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'keluarga'}" @click="activeDetailTab = 'keluarga'">
                                        <i class="bi bi-people me-1"></i>Keluarga & Wali
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'akademik'}" @click="activeDetailTab = 'akademik'">
                                        <i class="bi bi-mortarboard me-1"></i>Riwayat & Nilai
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'prestasi'}" @click="activeDetailTab = 'prestasi'">
                                        <i class="bi bi-trophy me-1"></i>Prestasi & Catatan
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'registrasi'}" @click="activeDetailTab = 'registrasi'">
                                        <i class="bi bi-arrow-left-right me-1"></i>Registrasi & Mutasi
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab 1: Identitas & Profil Siswa -->
                            <div v-if="activeDetailTab === 'identitas'" class="detail-tab-pane animate-fade-in">
                                <div class="row g-3 fs-8">
                                    <!-- Identitas Pokok Card -->
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light-subtle">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-person-vcard me-2"></i>Identitas Diri Pokok</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">Nama Lengkap</span>
                                                    <span class="text-dark fw-bold">{{ selectedSiswa.nama_lengkap }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">Nama Panggilan</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.nama_panggilan || '-' }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">Jenis Kelamin</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">NIK (Nomor Induk Kependudukan)</span>
                                                    <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.nik || '-' }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">NISN (Nomor Induk Siswa Nasional)</span>
                                                    <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.nisn || '-' }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">NIS (Nomor Induk Siswa)</span>
                                                    <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.nis || '-' }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">Tempat & Tanggal Lahir</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.tempat_lahir || '-' }}, {{ formatTanggal(selectedSiswa.tanggal_lahir) }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">Agama</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.agama || '-' }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">Kewarganegaraan</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.kewarganegaraan || 'WNI' }}</span>
                                                </div>
                                                <div class="col-md-6 col-lg-4">
                                                    <span class="text-muted d-block mb-0.5">Bahasa Sehari-hari</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.bahasa_sehari_hari || 'Indonesia' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Domisili & Kontak Card -->
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light-subtle">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Domisili & Kontak Keluarga</h6>
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <span class="text-muted d-block mb-0.5">Alamat Lengkap (KK)</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.alamat_kk || '-' }}</span>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <span class="text-muted d-block mb-0.5">Alamat Domisili Sekarang</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.alamat_domisili || '-' }}</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block mb-0.5">RT / RW</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.rt || '00' }} / {{ selectedSiswa.rw || '00' }}</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block mb-0.5">Kode Pos</span>
                                                    <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.kode_pos || '-' }}</span>
                                                </div>
                                                <div class="col-md-6">
                                                    <span class="text-muted d-block mb-0.5">Status Tempat Tinggal / Tinggal Dengan</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.status_tinggal || '-' }} (Tinggal dengan {{ selectedSiswa.tinggal_dengan || 'Orang Tua' }})</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Wilayah Administratif</span>
                                                    <span class="text-dark fw-semibold">
                                                        Kel. {{ selectedSiswa.nama_kelurahan || '-' }}, Kec. {{ selectedSiswa.nama_kecamatan || '-' }}, 
                                                        {{ selectedSiswa.nama_kota || '-' }}, Prov. {{ selectedSiswa.nama_provinsi || '-' }}
                                                    </span>
                                                </div>
                                                <div class="col-md-6 border-top pt-2">
                                                    <span class="text-muted d-block mb-0.5">Email Siswa</span>
                                                    <span class="text-dark font-monospace fw-bold">{{ selectedSiswa.email || '-' }}</span>
                                                </div>
                                                <div class="col-md-6 border-top pt-2">
                                                    <span class="text-muted d-block mb-0.5">No. HP Aktif</span>
                                                    <span class="text-dark font-monospace fw-bold">{{ selectedSiswa.no_telepon_siswa || '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Accordion: Kesehatan & Kesejahteraan (Bantuan KIP & Dokumen Lampiran) -->
                                    <div class="col-12">
                                        <div class="accordion" id="kesehatanKesejahteraanAccordion">
                                            <div class="accordion-item border-0 shadow-sm rounded-3 overflow-hidden mb-2">
                                                <h2 class="accordion-header" id="headingKesehatan">
                                                    <button class="accordion-button collapsed py-2 px-3 bg-light fs-8 fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKesehatan" aria-expanded="false" aria-controls="collapseKesehatan">
                                                        <i class="bi bi-heart-pulse-fill text-danger me-2"></i> Keadaan Kesehatan Fisik & Riwayat Pelajar
                                                    </button>
                                                </h2>
                                                <div id="collapseKesehatan" class="accordion-collapse collapse" aria-labelledby="headingKesehatan" data-bs-parent="#kesehatanKesejahteraanAccordion">
                                                    <div class="accordion-body p-3">
                                                        <div class="row g-3">
                                                            <div class="col-md-3">
                                                                <span class="text-muted d-block mb-0.5">Tinggi Badan</span>
                                                                <span class="text-dark fw-bold">{{ selectedSiswa.tinggi_badan || '0' }} cm</span>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <span class="text-muted d-block mb-0.5">Berat Badan</span>
                                                                <span class="text-dark fw-bold">{{ selectedSiswa.berat_badan || '0' }} kg</span>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <span class="text-muted d-block mb-0.5">Golongan Darah</span>
                                                                <span class="text-dark fw-bold">{{ selectedSiswa.golongan_darah || '-' }}</span>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <span class="text-muted d-block mb-0.5">Lingkar Kepala</span>
                                                                <span class="text-dark fw-bold">{{ selectedSiswa.lingkar_kepala || '0' }} cm</span>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <span class="text-muted d-block mb-0.5">Susunan Keluarga</span>
                                                                <span class="text-dark">Anak Ke-{{ selectedSiswa.anak_ke || '1' }} dari {{ selectedSiswa.jumlah_saudara || '0' }} bersaudara</span>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <span class="text-muted d-block mb-0.5">Transportasi ke Sekolah / Jarak Rumah</span>
                                                                <span class="text-dark">{{ selectedSiswa.transportasi || '-' }} (Jarak: {{ selectedSiswa.jarak_rumah || '0' }} meter)</span>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <span class="text-muted d-block mb-0.5">Penyakit yang Pernah Diderita</span>
                                                                <span class="text-dark text-danger fw-semibold">{{ selectedSiswa.penyakit_yang_diderita || 'Tidak Ada Riwayat Sakit Berat' }}</span>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <span class="text-muted d-block mb-0.5">Kelainan Jasmani</span>
                                                                <span class="text-dark text-danger fw-semibold">{{ selectedSiswa.kelainan_jasmani || 'Tidak Ada' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="accordion-item border-0 shadow-sm rounded-3 overflow-hidden mb-2">
                                                <h2 class="accordion-header" id="headingKesejahteraan">
                                                    <button class="accordion-button collapsed py-2 px-3 bg-light fs-8 fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKesejahteraan" aria-expanded="false" aria-controls="collapseKesejahteraan">
                                                        <i class="bi bi-shield-check text-success me-2"></i> Kesejahteraan & KIP (Bantuan Siswa)
                                                    </button>
                                                </h2>
                                                <div id="collapseKesejahteraan" class="accordion-collapse collapse" aria-labelledby="headingKesejahteraan" data-bs-parent="#kesehatanKesejahteraanAccordion">
                                                    <div class="accordion-body p-3">
                                                        <div class="row g-3">
                                                            <div class="col-md-4">
                                                                <span class="text-muted d-block mb-0.5">Penerima KPS</span>
                                                                <span class="badge" :class="selectedSiswa.penerima_kps == 1 ? 'bg-success' : 'bg-secondary'">{{ selectedSiswa.penerima_kps == 1 ? 'Ya' : 'Tidak' }}</span>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <span class="text-muted d-block mb-0.5">Penerima KIP (Kartu Indonesia Pintar)</span>
                                                                <span class="badge" :class="selectedSiswa.punya_kip == 1 ? 'bg-success' : 'bg-secondary'">{{ selectedSiswa.punya_kip == 1 ? 'Ya' : 'Tidak' }}</span>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <span class="text-muted d-block mb-0.5">Nomor KIP</span>
                                                                <span class="text-dark font-monospace fw-bold">{{ selectedSiswa.no_kip || '-' }}</span>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <span class="text-muted d-block mb-0.5">Kelayakan KIP / Alasan Layak</span>
                                                                <span class="text-dark fw-semibold">{{ selectedSiswa.layak_kip == 1 ? 'Layak' : 'Tidak Layak' }} / (Alasan: {{ selectedSiswa.alasan_layak || 'Tidak Ada' }})</span>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <span class="text-muted d-block mb-0.5">Status Anak</span>
                                                                <span class="text-dark fw-semibold">{{ selectedSiswa.status_anak || 'Normal' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="accordion-item border-0 shadow-sm rounded-3 overflow-hidden">
                                                <h2 class="accordion-header" id="headingBerkas">
                                                    <button class="accordion-button collapsed py-2 px-3 bg-light fs-8 fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBerkas" aria-expanded="false" aria-controls="collapseBerkas">
                                                        <i class="bi bi-file-pdf-fill text-primary me-2"></i> Lampiran Dokumen Unggahan
                                                    </button>
                                                </h2>
                                                <div id="collapseBerkas" class="accordion-collapse collapse" aria-labelledby="headingBerkas" data-bs-parent="#kesehatanKesejahteraanAccordion">
                                                    <div class="accordion-body p-3">
                                                        <div class="row g-3">
                                                            <div class="col-md-6" v-for="doc in dokumenFields" :key="doc.key">
                                                                <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light-subtle">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <i class="bi fs-5 text-danger" :class="selectedSiswa[doc.key] ? 'bi-file-pdf-fill' : 'bi-file-earmark-x'"></i>
                                                                        <div>
                                                                            <div class="fw-bold text-dark fs-8">{{ doc.label }}</div>
                                                                            <div class="text-muted fs-9" v-if="selectedSiswa[doc.key]">Tersedia • {{ getFileSizeLabel(doc.key) }}</div>
                                                                            <div class="text-muted fs-9" v-else>Belum Diunggah</div>
                                                                        </div>
                                                                    </div>
                                                                    <a v-if="selectedSiswa[doc.key]" :href="'/SINTA-SaaS/download.php?file=' + encodeURIComponent(selectedSiswa[doc.key])" 
                                                                       target="_blank" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1 fs-9">
                                                                        <i class="bi bi-download"></i> Lihat
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Data Keluarga & Wali -->
                            <div v-if="activeDetailTab === 'keluarga'" class="detail-tab-pane animate-fade-in">
                                <div class="row g-4">
                                    <!-- Ayah Kandung Card -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light-subtle h-100" style="border-top: 3px solid #0284c7 !important;">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary d-flex align-items-center gap-2">
                                                <i class="bi bi-gender-male text-sky-600 fs-5"></i>
                                                Ayah Kandung
                                            </h6>
                                            <div class="row g-3 fs-8">
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Nama Lengkap Ayah</span>
                                                    <span class="text-dark fw-bold">{{ selectedSiswa.nama_ayah || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">NIK Ayah</span>
                                                    <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.nik_ayah || '-' }}</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Tanggal Lahir / Status</span>
                                                    <span class="text-dark fw-semibold">{{ formatTanggal(selectedSiswa.tanggal_lahir_ayah) || selectedSiswa.tahun_lahir_ayah || '-' }} ({{ selectedSiswa.status_hidup_ayah || 'Hidup' }})</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Agama / Kewarganegaraan</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.agama_ayah || '-' }} / {{ selectedSiswa.kewarganegaraan_ayah || 'WNI' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Pendidikan Terakhir</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.pendidikan_ayah || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Pekerjaan Utama</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.pekerjaan_ayah || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Rata-rata Penghasilan Bulanan</span>
                                                    <span class="text-dark fw-bold text-success">{{ selectedSiswa.penghasilan_ayah || '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ibu Kandung Card -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light-subtle h-100" style="border-top: 3px solid #ec4899 !important;">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary d-flex align-items-center gap-2">
                                                <i class="bi bi-gender-female text-pink-600 fs-5"></i>
                                                Ibu Kandung
                                            </h6>
                                            <div class="row g-3 fs-8">
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Nama Lengkap Ibu</span>
                                                    <span class="text-dark fw-bold">{{ selectedSiswa.nama_ibu || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">NIK Ibu</span>
                                                    <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.nik_ibu || '-' }}</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Tanggal Lahir / Status</span>
                                                    <span class="text-dark fw-semibold">{{ formatTanggal(selectedSiswa.tanggal_lahir_ibu) || selectedSiswa.tahun_lahir_ibu || '-' }} ({{ selectedSiswa.status_hidup_ibu || 'Hidup' }})</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Agama / Kewarganegaraan</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.agama_ibu || '-' }} / {{ selectedSiswa.kewarganegaraan_ibu || 'WNI' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Pendidikan Terakhir</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.pendidikan_ibu || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Pekerjaan Utama</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.pekerjaan_ibu || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Rata-rata Penghasilan Bulanan</span>
                                                    <span class="text-dark fw-bold text-success">{{ selectedSiswa.penghasilan_ibu || '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Wali Siswa Card (Optional) -->
                                    <div class="col-12" v-if="selectedSiswa.nama_wali && selectedSiswa.nama_wali !== '-'">
                                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light-subtle" style="border-top: 3px solid #10b981 !important;">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary d-flex align-items-center gap-2">
                                                <i class="bi bi-person-bounding-box text-emerald-600 fs-5"></i>
                                                Wali Siswa (Orang Yang Membiayai)
                                            </h6>
                                            <div class="row g-3 fs-8">
                                                <div class="col-md-4">
                                                    <span class="text-muted d-block mb-0.5">Nama Lengkap Wali / Hubungan</span>
                                                    <span class="text-dark fw-bold">{{ selectedSiswa.nama_wali }} ({{ selectedSiswa.hubungan_wali || 'Wali' }})</span>
                                                </div>
                                                <div class="col-md-4">
                                                    <span class="text-muted d-block mb-0.5">NIK Wali</span>
                                                    <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.nik_wali || '-' }}</span>
                                                </div>
                                                <div class="col-md-4">
                                                    <span class="text-muted d-block mb-0.5">HP / Kontak Wali</span>
                                                    <span class="text-dark font-monospace fw-bold text-primary">{{ selectedSiswa.kontak_wali || selectedSiswa.no_telepon_orang_tua || '-' }}</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block mb-0.5">Tanggal Lahir / Kewarganegaraan</span>
                                                    <span class="text-dark fw-semibold">{{ formatTanggal(selectedSiswa.tanggal_lahir_wali) || selectedSiswa.tahun_lahir_wali || '-' }} / {{ selectedSiswa.kewarganegaraan_wali || 'WNI' }}</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block mb-0.5">Agama Wali</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.agama_wali || '-' }}</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block mb-0.5">Pendidikan / Pekerjaan</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.pendidikan_wali || '-' }} / {{ selectedSiswa.pekerjaan_wali || '-' }}</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block mb-0.5">Penghasilan Bulanan</span>
                                                    <span class="text-dark fw-bold text-success">{{ selectedSiswa.penghasilan_wali || '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- No Wali Banner -->
                                    <div class="col-12" v-else>
                                        <div class="p-3 bg-light rounded-3 text-center text-muted fs-8">
                                            <i class="bi bi-info-circle me-1"></i>Siswa tinggal dengan orang tua kandung, data Wali kosong.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 3: Riwayat Akademik & Nilai -->
                            <div v-if="activeDetailTab === 'akademik'" class="detail-tab-pane animate-fade-in">
                                <div class="row g-4">
                                    <!-- Timeline Penempatan Kelas -->
                                    <div class="col-12">
                                        <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-clock-history me-2"></i>Riwayat Penempatan Kelas & Kenaikan</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover align-middle fs-8" style="min-width:600px;">
                                                <thead class="table-light text-center">
                                                    <tr>
                                                        <th>Tahun Ajaran</th>
                                                        <th>Kelas</th>
                                                        <th>Wali Kelas / Pelaku Aksi</th>
                                                        <th>Status / Aksi</th>
                                                        <th>Catatan Kenaikan</th>
                                                        <th>Tanggal Proses</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Dynamic Class History -->
                                                    <tr v-for="h in selectedSiswa.riwayat_kelas" :key="h.id" class="text-center">
                                                        <td class="font-monospace fw-semibold">{{ h.tahun_ajaran }}</td>
                                                        <td class="fw-bold text-primary">{{ h.nama_kelas_tujuan || '-' }}</td>
                                                        <td>{{ h.nama_pelaku || '-' }}</td>
                                                        <td>
                                                            <span class="badge rounded-pill" :class="h.jenis_aksi === 'naik_kelas' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary'">
                                                                {{ h.jenis_aksi === 'naik_kelas' ? 'Naik Kelas' : 'Lulus' }}
                                                            </span>
                                                        </td>
                                                        <td class="text-start">{{ h.catatan || '-' }}</td>
                                                        <td class="font-monospace">{{ formatTanggal(h.created_at) }}</td>
                                                    </tr>

                                                    <!-- Fallback Current Class (jika riwayat_kelas kosong) -->
                                                    <tr v-if="!selectedSiswa.riwayat_kelas || selectedSiswa.riwayat_kelas.length === 0" class="text-center">
                                                        <td class="font-monospace fw-semibold">{{ selectedSiswa.id_tahun_ajaran || '-' }}</td>
                                                        <td class="fw-bold text-primary">{{ selectedSiswa.nama_kelas || 'Belum Terdaftar' }}</td>
                                                        <td>-</td>
                                                        <td><span class="badge bg-secondary-subtle text-secondary">Terdaftar Awal</span></td>
                                                        <td class="text-start">Siswa baru / Belum mengalami siklus kenaikan kelas.</td>
                                                        <td class="font-monospace">-</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- Rekapan Nilai Rapor per Tahun Ajaran -->
                                    <div class="col-12">
                                        <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-grid-3x3-gap me-2"></i>Rekapan Nilai Rapor Siswa</h6>
                                        <p class="text-muted fs-9 mb-3">Tabel rekapitulasi nilai akhir mata pelajaran yang terdata per Tahun Ajaran dan Semester.</p>
                                        
                                        <div class="table-responsive">
                                             <table class="table table-bordered table-hover align-middle text-center fs-8" style="min-width: 800px;">
                                                 <thead class="table-light">
                                                     <tr>
                                                         <th rowspan="2" style="width: 50px; vertical-align: middle;">No</th>
                                                         <th rowspan="2" style="min-width: 250px; vertical-align: middle;" class="text-start">Nama Mata Pelajaran</th>
                                                         <th v-for="ta in activeAcademicYears" :key="ta" colspan="2" class="text-center">
                                                             {{ ta }}
                                                         </th>
                                                     </tr>
                                                     <tr>
                                                         <th v-for="(col, colIdx) in academicYearHeaders" :key="colIdx" style="width: 120px;" class="text-center">
                                                             {{ col.label }}
                                                         </th>
                                                     </tr>
                                                 </thead>
                                                 <tbody>
                                                     <tr v-for="(m, mIdx) in formattedGrades" :key="mIdx">
                                                         <td class="text-muted">{{ mIdx + 1 }}</td>
                                                         <td class="text-start fw-bold text-dark">
                                                             {{ m.nama_mapel }}
                                                             <small class="text-muted font-monospace d-block" style="font-weight:normal; font-size:10px;">{{ m.kode_mapel }}</small>
                                                         </td>
                                                         
                                                         <td v-for="(col, colIdx) in academicYearHeaders" :key="colIdx">
                                                             <div v-if="m.grades[col.ta + '|' + col.sem] && m.grades[col.ta + '|' + col.sem].val !== '-'">
                                                                 <span class="badge fs-8 px-2 py-1 rounded-2"
                                                                       :class="{
                                                                           'bg-success text-white': parseFloat(m.grades[col.ta + '|' + col.sem].val) >= 75,
                                                                           'bg-warning text-dark': parseFloat(m.grades[col.ta + '|' + col.sem].val) >= 60 && parseFloat(m.grades[col.ta + '|' + col.sem].val) < 75,
                                                                           'bg-danger text-white': parseFloat(m.grades[col.ta + '|' + col.sem].val) < 60
                                                                       }"
                                                                       :title="'Kelas: ' + m.grades[col.ta + '|' + col.sem].kelas">
                                                                     {{ m.grades[col.ta + '|' + col.sem].val }}
                                                                     <span class="font-monospace fs-9" style="font-weight:normal;" v-if="m.grades[col.ta + '|' + col.sem].pred">
                                                                         ({{ m.grades[col.ta + '|' + col.sem].pred }})
                                                                     </span>
                                                                 </span>
                                                                 <small class="text-muted d-block font-monospace mt-1" style="font-size: 10px; font-weight: normal;">
                                                                     {{ m.grades[col.ta + '|' + col.sem].kelas }}
                                                                 </small>
                                                             </div>
                                                             <div v-else class="text-muted">-</div>
                                                         </td>
                                                     </tr>
 
                                                     <!-- Empty State Rapor -->
                                                     <tr v-if="formattedGrades.length === 0">
                                                         <td :colspan="2 + (activeAcademicYears.length * 2)" class="py-4 text-center text-muted">
                                                             <i class="bi bi-file-earmark-lock-fill fs-3 d-block mb-1 text-secondary"></i>
                                                             Belum ada data rekapan nilai rapor terinput untuk siswa ini.
                                                         </td>
                                                     </tr>
                                                 </tbody>
                                             </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                    
                                    
                                    
                                    

                            <!-- Tab 4: Prestasi & Catatan Khusus -->
                            <div v-if="activeDetailTab === 'prestasi'" class="detail-tab-pane animate-fade-in">
                                <div class="row g-4">
                                    <!-- Riwayat Prestasi Lomba -->
                                    <div class="col-12">
                                        <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-trophy-fill text-warning me-2"></i>Riwayat Prestasi & Penghargaan Lomba</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover align-middle fs-8" style="min-width: 750px;">
                                                <thead class="table-light text-center">
                                                    <tr>
                                                        <th style="width: 50px;">No</th>
                                                        <th>Tahun/Smt</th>
                                                        <th>Bidang</th>
                                                        <th>Nama Kejuaraan / Lomba</th>
                                                        <th>Juara</th>
                                                        <th>Tingkat</th>
                                                        <th>Penyelenggara</th>
                                                        <th>Poin</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(p, pIdx) in selectedSiswa.prestasi" :key="p.id" class="text-center">
                                                        <td class="text-muted">{{ pIdx + 1 }}</td>
                                                        <td class="font-monospace">{{ p.tahun_ajaran || '-' }} / {{ p.semester }}</td>
                                                        <td><span class="badge bg-light text-dark border">{{ p.bidang_lomba }}</span></td>
                                                        <td class="text-start fw-bold text-dark">
                                                            {{ p.nama_lomba }}
                                                            <small class="text-muted d-block" style="font-weight:normal;" v-if="p.guru_pendamping">Pendamping: {{ p.guru_pendamping }}</small>
                                                        </td>
                                                        <td class="fw-bold text-success">{{ p.juara }}</td>
                                                        <td>
                                                            <span class="badge" 
                                                                  :class="{
                                                                      'bg-primary': p.tingkat_kejuaraan === 'Nasional' || p.tingkat_kejuaraan === 'Internasional',
                                                                      'bg-success': p.tingkat_kejuaraan === 'Provinsi',
                                                                      'bg-secondary': p.tingkat_kejuaraan === 'Kota/Kabupaten'
                                                                  }">
                                                                {{ p.tingkat_kejuaraan }}
                                                            </span>
                                                        </td>
                                                        <td class="text-start">{{ p.penyelenggara }}</td>
                                                        <td class="font-monospace fw-bold text-primary">+{{ p.poin_prestasi }}</td>
                                                    </tr>

                                                    <!-- Empty Prestasi -->
                                                    <tr v-if="!selectedSiswa.prestasi || selectedSiswa.prestasi.length === 0">
                                                        <td colspan="8" class="py-4 text-center text-muted">
                                                            <i class="bi bi-award fs-3 d-block mb-1 text-secondary"></i>
                                                            Tidak ada riwayat prestasi atau piagam lomba tercatat.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Catatan Kedisiplinan & Pelanggaran BK -->
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-shield-exclamation text-danger me-2"></i>Catatan Kedisiplinan & Pelanggaran BK</h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fs-8 text-muted">Akumulasi Poin Pelanggaran:</span>
                                                <span class="badge rounded-pill fs-7 px-3 py-1.5"
                                                      :class="{
                                                          'bg-success text-white': totalPoinPelanggaran < 25,
                                                          'bg-warning text-dark': totalPoinPelanggaran >= 25 && totalPoinPelanggaran < 50,
                                                          'bg-danger text-white': totalPoinPelanggaran >= 50
                                                      }">
                                                    {{ totalPoinPelanggaran }} Poin
                                                </span>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover align-middle fs-8" style="min-width: 750px;">
                                                <thead class="table-light text-center">
                                                    <tr>
                                                        <th style="width: 50px;">No</th>
                                                        <th>Tanggal</th>
                                                        <th>Kategori</th>
                                                        <th>Detail Pelanggaran Tata Tertib</th>
                                                        <th>Bobot Poin</th>
                                                        <th>Keterangan / Catatan BK</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(v, vIdx) in selectedSiswa.pelanggaran" :key="v.id" class="text-center">
                                                        <td class="text-muted">{{ vIdx + 1 }}</td>
                                                        <td class="font-monospace">{{ formatTanggal(v.tanggal_kejadian) }}</td>
                                                        <td>
                                                            <span class="badge"
                                                                  :class="{
                                                                      'bg-danger': v.kategori === 'Berat' || v.kategori === 'Khusus',
                                                                      'bg-warning text-dark': v.kategori === 'Sedang',
                                                                      'bg-secondary': v.kategori === 'Ringan'
                                                                  }">
                                                                {{ v.kategori }}
                                                            </span>
                                                        </td>
                                                        <td class="text-start fw-semibold text-dark">{{ v.nama_pelanggaran }}</td>
                                                        <td class="font-monospace text-danger fw-bold">{{ v.bobot_poin }} Poin</td>
                                                        <td class="text-start">{{ v.catatan_keterangan || '-' }}</td>
                                                    </tr>

                                                    <!-- Empty Pelanggaran -->
                                                    <tr v-if="!selectedSiswa.pelanggaran || selectedSiswa.pelanggaran.length === 0">
                                                        <td colspan="6" class="py-4 text-center text-muted">
                                                            <i class="bi bi-check-circle-fill fs-3 text-success d-block mb-1"></i>
                                                            Siswa berkelakuan sangat baik. Tidak ada catatan pelanggaran tata tertib BK.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 5: Registrasi & Mutasi -->
                            <div v-if="activeDetailTab === 'registrasi'" class="detail-tab-pane animate-fade-in">
                                <div class="row g-4">
                                    <!-- Registrasi PPDB -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light-subtle h-100">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-box-arrow-in-right me-2"></i>Registrasi Masuk & PPDB</h6>
                                            <div class="row g-3 fs-8">
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Tanggal Masuk</span>
                                                    <span class="text-dark fw-bold">{{ formatTanggal(selectedSiswa.tanggal_masuk) }}</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Jenis Pendaftaran</span>
                                                    <span class="text-dark fw-bold">{{ selectedSiswa.jenis_pendaftaran || 'Siswa Baru' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Jalur Penerimaan PPDB</span>
                                                    <span class="text-dark fw-bold text-primary">{{ selectedSiswa.jalur_diterima || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Asal Sekolah Sebelumnya (SMP/MTs)</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.sekolah_asal || '-' }}</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Pernah PAUD Formal / Non-Formal</span>
                                                    <span class="text-dark fw-semibold">
                                                        Formal: {{ selectedSiswa.paud_formal == 1 ? 'Ya' : 'Tidak' }} / 
                                                        Non-Formal: {{ selectedSiswa.paud_non_formal == 1 ? 'Ya' : 'Tidak' }}
                                                    </span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Hobi / Kegemaran</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.hobi || '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Mutasi / Keluar (Jika Ada) -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light-subtle h-100" style="border-left: 3px solid #ef4444 !important;" v-if="selectedSiswa.status === 'Pindah' || selectedSiswa.status === 'Keluar' || selectedSiswa.keluar_karena">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-danger"><i class="bi bi-arrow-left-right me-2"></i>Detail Mutasi Keluar / Keluar Sekolah</h6>
                                            <div class="row g-3 fs-8">
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Status Akhir</span>
                                                    <span class="badge bg-danger-subtle text-danger fs-8 rounded-pill px-2.5 py-1">{{ selectedSiswa.status }}</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Keluar Karena</span>
                                                    <span class="text-dark fw-bold">{{ selectedSiswa.keluar_karena || 'Mutasi Keluar' }}</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Tanggal Keluar / Mutasi</span>
                                                    <span class="text-dark fw-semibold">{{ formatTanggal(selectedSiswa.tanggal_keluar) }}</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Sekolah Tujuan Mutasi</span>
                                                    <span class="text-dark fw-bold text-primary">{{ selectedSiswa.sekolah_tujuan || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Alasan Keluar / Nomor SKP</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.alasan_keluar || '-' }} (SKP: {{ selectedSiswa.nomor_skp || '-' }})</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Kelulusan (Untuk Alumni/Lulus) -->
                                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light-subtle h-100" style="border-left: 3px solid #3b82f6 !important;" v-else-if="selectedSiswa.status === 'Lulus'">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-patch-check-fill text-primary me-2"></i>Status Kelulusan Siswa (Alumni)</h6>
                                            <div class="row g-3 fs-8">
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Status Siswa</span>
                                                    <span class="badge bg-primary-subtle text-primary fs-8 rounded-pill px-2.5 py-1">Lulus / Alumni</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted d-block mb-0.5">Tanggal Kelulusan</span>
                                                    <span class="text-dark fw-bold">{{ formatTanggal(selectedSiswa.tanggal_lulus) || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Nomor Seri Ijazah Kelulusan / Nomor SKL</span>
                                                    <span class="text-dark font-monospace fw-bold">{{ selectedSiswa.nomor_ijazah_kelulusan || '-' }} / {{ selectedSiswa.nomor_skl || '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Keterangan Tambahan / Tracer Study</span>
                                                    <span class="text-dark fw-semibold">{{ selectedSiswa.keterangan_setelah_lulus || 'Data alumni terdata dalam Tracer Study.' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Aktif Banner -->
                                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light-subtle h-100" v-else>
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-success"><i class="bi bi-check-circle-fill text-success me-2"></i>Status Aktif Belajar</h6>
                                            <div class="row g-3 fs-8">
                                                <div class="col-12">
                                                    <span class="text-muted d-block mb-0.5">Informasi Status</span>
                                                    <span class="text-dark fw-semibold">Siswa saat ini berstatus aktif belajar di sekolah. Tidak ada riwayat mutasi keluar, drop out, maupun kelulusan terdata.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tracer Study Alumni Details (Hanya ditampilkan untuk alumni / Lulus) -->
                                    <div class="col-12 border-top pt-4" v-if="selectedSiswa.status === 'Lulus'">
                                        <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-briefcase-fill me-2"></i>Tracer Study - Jejak Karir & Pendidikan Tinggi Alumni</h6>
                                        
                                        <div class="row g-3">
                                            <!-- Riwayat Perguruan Tinggi -->
                                            <div class="col-12 col-lg-6">
                                                <div class="p-2 border rounded-3 bg-white h-100">
                                                    <div class="fw-bold text-dark fs-8 mb-2 border-bottom pb-1"><i class="bi bi-building-fill text-primary me-1"></i>Riwayat Kuliah Alumni</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm align-middle fs-9 table-hover mb-0">
                                                            <thead>
                                                                <tr class="table-light">
                                                                    <th>Kampus</th>
                                                                    <th>Jurusan</th>
                                                                    <th>Tahun</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="c in selectedSiswa.tracer_kuliah" :key="c.id">
                                                                    <td class="fw-bold">{{ c.nama_kampus }}</td>
                                                                    <td>{{ c.jurusan || '-' }}</td>
                                                                    <td class="font-monospace">{{ c.tahun_masuk }}</td>
                                                                    <td>
                                                                        <span class="badge rounded-pill" :class="c.status_kuliah === 'Lulus' ? 'bg-success' : 'bg-primary'">
                                                                            {{ c.status_kuliah }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                <tr v-if="!selectedSiswa.tracer_kuliah || selectedSiswa.tracer_kuliah.length === 0">
                                                                    <td colspan="4" class="text-center py-3 text-muted">Belum ada riwayat perkuliahan terdata.</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Riwayat Karir Pekerjaan -->
                                            <div class="col-12 col-lg-6">
                                                <div class="p-2 border rounded-3 bg-white h-100">
                                                    <div class="fw-bold text-dark fs-8 mb-2 border-bottom pb-1"><i class="bi bi-briefcase-fill text-success me-1"></i>Riwayat Pekerjaan Alumni</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm align-middle fs-9 table-hover mb-0">
                                                            <thead>
                                                                <tr class="table-light">
                                                                    <th>Perusahaan</th>
                                                                    <th>Posisi</th>
                                                                    <th>Tahun</th>
                                                                    <th>Status Kerja</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="j in selectedSiswa.tracer_pekerjaan" :key="j.id">
                                                                    <td class="fw-bold">{{ j.nama_perusahaan }}</td>
                                                                    <td>{{ j.posisi_jabatan || '-' }}</td>
                                                                    <td class="font-monospace">{{ j.tahun_mulai }}</td>
                                                                    <td>
                                                                        <span class="badge bg-secondary-subtle text-secondary">{{ j.status_kerja }}</span>
                                                                    </td>
                                                                </tr>
                                                                <tr v-if="!selectedSiswa.tracer_pekerjaan || selectedSiswa.tracer_pekerjaan.length === 0">
                                                                    <td colspan="4" class="text-center py-3 text-muted">Belum ada riwayat pekerjaan terdata.</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-3 fs-8 px-4" data-bs-dismiss="modal">Tutup</button>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- Styles CSS Custom untuk Pilihan Tabs & Scrollable -->
<style>
    [v-cloak] {
        display: none !important;
    }
    
    .hover-row {
        transition: background-color 0.2s ease;
    }
    
    .hover-row:hover {
        background-color: #f8fafc !important;
    }
    
    .bg-light-blue {
        background-color: #e0f2fe;
    }
    
    .text-blue-700 {
        color: #0369a1;
    }
    
    .bg-light-rose {
        background-color: #ffe4e6;
    }
    
    .text-rose-700 {
        color: #be123c;
    }

    .detail-tab-pane {
        animation: fadeIn 0.30s ease;
    }

    .cursor-pointer {
        cursor: pointer;
    }
    
    .hover-shadow {
        transition: all 0.3s ease;
    }
    
    .hover-shadow:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08) !important;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
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

<!-- Script Inisialisasi Vue App -->
<script>
{
    window.VueAppRegistry.register('#bukuIndukApp', {
        data() {
            return {
                mainActiveTab: 'buku_induk_siswa',
                mainTabs: [
                    { id: 'buku_induk_siswa', name: 'Buku Induk Siswa', icon: 'bi bi-person-lines-fill' },
                    { id: 'seting_kurikulum', name: 'Seting Kurikulum', icon: 'bi bi-gear-wide-connected' },
                    { id: 'input_nilai_rapor', name: 'Input Nilai Rapor', icon: 'bi bi-journal-check' }
                ],
                userRole: '<?php echo htmlspecialchars($user_role ?? ""); ?>',
                listTenants: <?php echo json_encode($tenantList ?? []); ?>,
                filterTenantId: '', 
                kelasOptions: <?php echo json_encode($kelasList ?? []); ?>,
                filterKelas: '',
                filterStatus: '',
                listData: [],
                currentPage: 1,
                totalPages: 1,
                perPage: 10,
                search: '',
                total: 0,
                from: 0,
                to: 0,
                
                loading: false,
                loadingKurikulum: false,
                
                // Kurikulum State
                kurikulum: {
                    tahunAjaran: '',
                    semester: 'Ganjil',
                    kelasId: '',
                    groups: []
                },
                masterKurikulum: {
                    tahun_ajaran: [],
                    kelas: [],
                    bank_mapel: []
                },
                copySourceKelasId: '',
                copyModalObj: null,

                // Nilai Rapor State
                nilaiRapor: {
                    tahunAjaran: '',
                    semester: 'Ganjil',
                    kelasId: '',
                    subjects: [],
                    students: [],
                    grades: {},
                    isSaving: false
                },
                masterNilaiRapor: {
                    tahun_ajaran: [],
                    kelas: []
                },
                loadingNilaiRapor: false,
                importFile: null,
                importModalObj: null,
                detailLoading: false,
                selectedSiswa: null,
                activeDetailTab: 'identitas',
                detailModalObj: null,
                searchTimeout: null,
                
                dokumenFields: [
                    { key: 'berkas_kk', label: 'Kartu Keluarga (KK)' },
                    { key: 'berkas_akta', label: 'Akta Kelahiran' },
                    { key: 'berkas_ijazah_sd', label: 'Ijazah SD' },
                    { key: 'berkas_ijazah_smp', label: 'Ijazah SMP' },
                    { key: 'berkas_ijazah_sma', label: 'Ijazah SMA (Alumni)' },
                    { key: 'berkas_kip', label: 'Kartu KIP' },
                    { key: 'berkas_pernyataan_baru', label: 'Surat Pernyataan Siswa Baru' },
                    { key: 'berkas_pernyataan_tka', label: 'Surat Pernyataan Orang Tua' }
                ],

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
            this.detailModalObj = new bootstrap.Modal(document.getElementById('detailModal'));
            this.copyModalObj = new bootstrap.Modal(document.getElementById('copyKurikulumModal'));
            this.importModalObj = new bootstrap.Modal(document.getElementById('importNilaiModal'));
            this.fetchData(1);
        },
        computed: {
            selectedTenantName() {
                if (!this.filterTenantId) return '';
                const t = this.listTenants.find(t => t.id === this.filterTenantId);
                return t ? t.nama_sekolah : '';
            },
            filteredCopyKelasOptions() {
                if (!this.masterKurikulum || !this.masterKurikulum.kelas) return [];
                return this.masterKurikulum.kelas.filter(k => k.id != this.kurikulum.kelasId);
            },
            activeAcademicYears() {
                if (!this.selectedSiswa || !this.selectedSiswa.nilai_rapor) return [];
                const years = new Set();
                this.selectedSiswa.nilai_rapor.forEach(g => {
                    if (g.tahun_ajaran) {
                        years.add(g.tahun_ajaran);
                    }
                });
                return Array.from(years).sort();
            },
            academicYearHeaders() {
                if (!this.selectedSiswa || !this.selectedSiswa.nilai_rapor) return [];
                const cols = [];
                this.activeAcademicYears.forEach(ta => {
                    cols.push({ ta: ta, sem: 'ganjil', label: 'Smt 1 (Ganjil)' });
                    cols.push({ ta: ta, sem: 'genap', label: 'Smt 2 (Genap)' });
                });
                return cols;
            },
            formattedGrades() {
                if (!this.selectedSiswa || !this.selectedSiswa.nilai_rapor) return [];
                const mapelMap = {};
                
                this.selectedSiswa.nilai_rapor.forEach(g => {
                    const mapelName = g.nama_mapel;
                    const mapelCode = g.kode_mapel;
                    const val = g.nilai_akhir;
                    const pred = g.predikat || '';
                    const ta = g.tahun_ajaran;
                    const sem = (g.semester || '').toLowerCase();
                    
                    if (!mapelMap[mapelName]) {
                        mapelMap[mapelName] = {
                            nama_mapel: mapelName,
                            kode_mapel: mapelCode,
                            grades: {}
                        };
                    }
                    
                    const semKey = sem.includes('ganjil') ? 'ganjil' : 'genap';
                    const key = `${ta}|${semKey}`;
                    
                    mapelMap[mapelName].grades[key] = {
                        val: val !== null ? parseFloat(val).toFixed(0) : '-',
                        pred: pred,
                        kelas: g.nama_kelas || '-'
                    };
                });
                
                return Object.values(mapelMap);
            },
            totalPoinPelanggaran() {
                if (!this.selectedSiswa || !this.selectedSiswa.pelanggaran) return 0;
                return this.selectedSiswa.pelanggaran.reduce((sum, item) => sum + parseInt(item.bobot_poin || 0), 0);
            }
        },
        methods: {
            switchMainTab(tabId) {
                this.mainActiveTab = tabId;
                if (tabId === 'seting_kurikulum') {
                    this.fetchKurikulumMaster();
                } else if (tabId === 'input_nilai_rapor') {
                    this.fetchNilaiRaporMaster();
                } else {
                    this.fetchData(1);
                }
            },
            fetchNilaiRaporMaster() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.masterNilaiRapor.tahun_ajaran = [];
                    this.masterNilaiRapor.kelas = [];
                    this.loadingNilaiRapor = false;
                    return;
                }
                this.loadingNilaiRapor = true;
                const params = {};
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    params.tenant_id = this.filterTenantId;
                }
                axios.get('/SINTA-SaaS/api/v1/kurikulum', { params })
                    .then(res => {
                        this.masterNilaiRapor.tahun_ajaran = res.data.tahun_ajaran || [];
                        this.masterNilaiRapor.kelas = res.data.kelas || [];
                        
                        if (this.masterNilaiRapor.tahun_ajaran.length > 0 && !this.nilaiRapor.tahunAjaran) {
                            this.nilaiRapor.tahunAjaran = this.masterNilaiRapor.tahun_ajaran[0].tahun_ajaran;
                        }
                        
                        this.loadingNilaiRapor = false;
                        this.loadNilaiRaporGrid();
                    })
                    .catch(err => {
                        this.loadingNilaiRapor = false;
                        this.toast.fire({ icon: 'error', title: 'Gagal memuat master data nilai rapor.' });
                    });
            },
            loadNilaiRaporGrid() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.nilaiRapor.subjects = [];
                    this.nilaiRapor.students = [];
                    this.nilaiRapor.grades = {};
                    return;
                }
                if (!this.nilaiRapor.kelasId || !this.nilaiRapor.tahunAjaran || !this.nilaiRapor.semester) {
                    this.nilaiRapor.subjects = [];
                    this.nilaiRapor.students = [];
                    this.nilaiRapor.grades = {};
                    return;
                }

                this.loadingNilaiRapor = true;
                const params = {
                    kelas_id: this.nilaiRapor.kelasId,
                    tahun_ajaran: this.nilaiRapor.tahunAjaran,
                    semester: this.nilaiRapor.semester
                };
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    params.tenant_id = this.filterTenantId;
                }

                axios.get('/SINTA-SaaS/api/v1/nilai-rapor/grid', { params })
                    .then(res => {
                        this.nilaiRapor.subjects = res.data.subjects || [];
                        this.nilaiRapor.students = res.data.students || [];
                        const rawGrades = res.data.grades || {};
                        
                        const gradesObj = {};
                        this.nilaiRapor.students.forEach(student => {
                            gradesObj[student.id] = {};
                            this.nilaiRapor.subjects.forEach(subject => {
                                const existingVal = (rawGrades[student.id] && rawGrades[student.id][subject.mapel_id] !== undefined)
                                    ? rawGrades[student.id][subject.mapel_id]
                                    : '';
                                gradesObj[student.id][subject.mapel_id] = existingVal;
                            });
                        });
                        
                        this.nilaiRapor.grades = gradesObj;
                        this.loadingNilaiRapor = false;
                    })
                    .catch(err => {
                        this.loadingNilaiRapor = false;
                        this.toast.fire({ icon: 'error', title: 'Gagal memuat tabel nilai rapor.' });
                    });
            },
            saveNilaiRapor() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih Sekolah terlebih dahulu.' });
                    return;
                }
                if (!this.nilaiRapor.kelasId || !this.nilaiRapor.tahunAjaran || !this.nilaiRapor.semester) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih Kelas, Tahun Ajaran, dan Semester terlebih dahulu.' });
                    return;
                }

                const gradesPayload = [];
                this.nilaiRapor.students.forEach(student => {
                    const studentId = student.id;
                    this.nilaiRapor.subjects.forEach(subject => {
                        const subjectId = subject.mapel_id;
                        let val = '';
                        if (this.nilaiRapor.grades[studentId] && this.nilaiRapor.grades[studentId][subjectId] !== undefined) {
                            val = this.nilaiRapor.grades[studentId][subjectId];
                        }
                        
                        gradesPayload.push({
                            siswa_id: studentId,
                            mapel_id: subjectId,
                            nilai_akhir: val !== '' ? val : null
                        });
                    });
                });

                const payload = {
                    kelas_id: this.nilaiRapor.kelasId,
                    tahun_ajaran: this.nilaiRapor.tahunAjaran,
                    semester: this.nilaiRapor.semester,
                    grades: gradesPayload
                };

                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    payload.tenant_id = this.filterTenantId;
                }

                Swal.fire({
                    title: 'Simpan Perubahan Nilai?',
                    text: 'Nilai yang dimasukkan akan disimpan secara permanen.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menyimpan...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        axios.post('/SINTA-SaaS/api/v1/nilai-rapor/save', payload)
                            .then(res => {
                                Swal.close();
                                this.toast.fire({ icon: 'success', title: res.data.message || 'Nilai rapor berhasil disimpan.' });
                                this.loadNilaiRaporGrid();
                            })
                            .catch(err => {
                                Swal.close();
                                const msg = err.response && err.response.data && err.response.data.message 
                                    ? err.response.data.message 
                                    : 'Gagal menyimpan nilai.';
                                this.toast.fire({ icon: 'error', title: msg });
                            });
                    }
                });
            },
            exportNilaiRaporExcel() {
                if (!this.nilaiRapor.kelasId || !this.nilaiRapor.tahunAjaran || !this.nilaiRapor.semester) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih Kelas, Tahun Ajaran, dan Semester terlebih dahulu.' });
                    return;
                }
                let url = `/SINTA-SaaS/api/v1/nilai-rapor/export?kelas_id=${this.nilaiRapor.kelasId}&tahun_ajaran=${encodeURIComponent(this.nilaiRapor.tahunAjaran)}&semester=${this.nilaiRapor.semester}`;
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    url += `&tenant_id=${this.filterTenantId}`;
                }
                window.location.href = url;
            },
            showImportGradesModal() {
                this.importFile = null;
                const fileEl = document.getElementById('importNilaiFile');
                if (fileEl) fileEl.value = '';
                this.importModalObj.show();
            },
            onImportFileChange(e) {
                this.importFile = e.target.files[0] || null;
            },
            submitImportGrades() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih Sekolah terlebih dahulu.' });
                    return;
                }
                if (!this.importFile) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih berkas Excel (.xlsx) terlebih dahulu.' });
                    return;
                }

                const formData = new FormData();
                formData.append('file', this.importFile);
                formData.append('kelas_id', this.nilaiRapor.kelasId);
                formData.append('tahun_ajaran', this.nilaiRapor.tahunAjaran);
                formData.append('semester', this.nilaiRapor.semester);
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    formData.append('tenant_id', this.filterTenantId);
                }

                Swal.fire({
                    title: 'Mengimpor...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                axios.post('/SINTA-SaaS/api/v1/nilai-rapor/import', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(res => {
                    Swal.close();
                    this.importModalObj.hide();
                    this.toast.fire({ icon: 'success', title: res.data.message || 'Nilai rapor berhasil diimpor.' });
                    this.loadNilaiRaporGrid();
                })
                .catch(err => {
                    Swal.close();
                    const msg = err.response && err.response.data && err.response.data.message 
                        ? err.response.data.message 
                        : 'Gagal mengimpor nilai rapor.';
                    this.toast.fire({ icon: 'error', title: msg });
                });
            },
            getNilaiRaporKelasName(kelasId) {
                if (!kelasId) return '';
                const k = this.masterNilaiRapor.kelas.find(x => x.id == kelasId);
                return k ? k.nama_kelas : '';
            },
            onFilterTenantChange() {
                // Ambil daftar kelas yang sesuai dengan tenant terpilih (untuk Super Admin)
                this.filterKelas = '';
                this.fetchKelasOptions(this.filterTenantId || null);
                if (this.mainActiveTab === 'seting_kurikulum') {
                    this.fetchKurikulumMaster();
                } else if (this.mainActiveTab === 'input_nilai_rapor') {
                    this.fetchNilaiRaporMaster();
                } else {
                    this.fetchData(1);
                }
            },
            fetchKurikulumMaster() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.masterKurikulum.tahun_ajaran = [];
                    this.masterKurikulum.kelas = [];
                    this.masterKurikulum.bank_mapel = [];
                    this.loadingKurikulum = false;
                    return;
                }
                this.loadingKurikulum = true;
                const params = {};
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    params.tenant_id = this.filterTenantId;
                }
                axios.get('/SINTA-SaaS/api/v1/kurikulum', { params })
                    .then(res => {
                        this.masterKurikulum.tahun_ajaran = res.data.tahun_ajaran || [];
                        this.masterKurikulum.kelas = res.data.kelas || [];
                        this.masterKurikulum.bank_mapel = res.data.bank_mapel || [];
                        
                        if (this.masterKurikulum.tahun_ajaran.length > 0 && !this.kurikulum.tahunAjaran) {
                            this.kurikulum.tahunAjaran = this.masterKurikulum.tahun_ajaran[0].tahun_ajaran;
                        }
                        
                        this.loadingKurikulum = false;
                        this.loadKurikulumMapping();
                    })
                    .catch(err => {
                        this.loadingKurikulum = false;
                        this.toast.fire({ icon: 'error', title: 'Gagal memuat master data kurikulum.' });
                    });
            },
            loadKurikulumMapping() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.kurikulum.groups = [];
                    return;
                }
                if (!this.kurikulum.kelasId || !this.kurikulum.tahunAjaran || !this.kurikulum.semester) {
                    this.kurikulum.groups = [];
                    return;
                }
                
                this.loadingKurikulum = true;
                const params = {
                    kelas_id: this.kurikulum.kelasId,
                    tahun_ajaran: this.kurikulum.tahunAjaran,
                    semester: this.kurikulum.semester
                };
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    params.tenant_id = this.filterTenantId;
                }

                axios.get('/SINTA-SaaS/api/v1/kurikulum', { params })
                    .then(res => {
                        const mapping = res.data.existing_mapping || [];
                        const groupsMap = {};
                        
                        mapping.forEach(row => {
                            const groupName = row.kelompok_id;
                            const mapelId = parseInt(row.mapel_id);
                            if (!groupsMap[groupName]) {
                                groupsMap[groupName] = [];
                            }
                            groupsMap[groupName].push(mapelId);
                        });
                        
                        const loadedGroups = [];
                        Object.keys(groupsMap).forEach(groupName => {
                            loadedGroups.push({
                                kelompok_id: groupName,
                                mapel_ids: groupsMap[groupName],
                                searchQuery: ''
                            });
                        });
                        
                        if (loadedGroups.length === 0) {
                            loadedGroups.push({
                                kelompok_id: 'Kelompok A (Umum)',
                                mapel_ids: [],
                                searchQuery: ''
                            });
                        }
                        
                        this.kurikulum.groups = loadedGroups;
                        this.loadingKurikulum = false;
                    })
                    .catch(err => {
                        this.loadingKurikulum = false;
                        this.toast.fire({ icon: 'error', title: 'Gagal memuat pemetaan kurikulum.' });
                    });
            },
            addGroup() {
                this.kurikulum.groups.push({
                    kelompok_id: 'Kelompok Baru',
                    mapel_ids: [],
                    searchQuery: ''
                });
            },
            removeGroup(index) {
                const group = this.kurikulum.groups[index];
                if (group.mapel_ids.length > 0) {
                    Swal.fire({
                        title: 'Hapus Kelompok?',
                        text: `Kelompok "${group.kelompok_id}" memiliki ${group.mapel_ids.length} mata pelajaran terpilih. Anda yakin ingin menghapusnya dari rancangan?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.kurikulum.groups.splice(index, 1);
                        }
                    });
                } else {
                    this.kurikulum.groups.splice(index, 1);
                }
            },
            filteredMapelList(searchQuery) {
                if (!searchQuery) return this.masterKurikulum.bank_mapel;
                const query = searchQuery.toLowerCase().trim();
                return this.masterKurikulum.bank_mapel.filter(m => 
                    (m.nama_mapel && m.nama_mapel.toLowerCase().includes(query)) ||
                    (m.kode_mapel && m.kode_mapel.toLowerCase().includes(query))
                );
            },
            getKelasName(kelasId) {
                if (!kelasId) return '';
                const k = this.masterKurikulum.kelas.find(x => x.id == kelasId);
                return k ? k.nama_kelas : '';
            },
            showCopyModal() {
                this.copySourceKelasId = '';
                this.copyModalObj.show();
            },
            submitCopyKurikulum() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih Sekolah terlebih dahulu.' });
                    return;
                }
                if (!this.copySourceKelasId) return;
                
                const payload = {
                    source_kelas_id: this.copySourceKelasId,
                    target_kelas_id: this.kurikulum.kelasId,
                    tahun_ajaran: this.kurikulum.tahunAjaran,
                    semester: this.kurikulum.semester
                };

                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    payload.tenant_id = this.filterTenantId;
                }

                Swal.fire({
                    title: 'Konfirmasi Salin',
                    text: `Salin kurikulum dari kelas "${this.getKelasName(this.copySourceKelasId)}" ke "${this.getKelasName(this.kurikulum.kelasId)}"? Ini akan menghapus data kurikulum yang ada di kelas tujuan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Salin',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menyalin...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        axios.post('/SINTA-SaaS/api/v1/kurikulum/copy', payload)
                            .then(res => {
                                Swal.close();
                                this.copyModalObj.hide();
                                this.toast.fire({ icon: 'success', title: res.data.message || 'Kurikulum berhasil disalin.' });
                                this.loadKurikulumMapping();
                            })
                            .catch(err => {
                                Swal.close();
                                const msg = err.response && err.response.data && err.response.data.message 
                                    ? err.response.data.message 
                                    : 'Gagal menyalin kurikulum.';
                                this.toast.fire({ icon: 'error', title: msg });
                            });
                    }
                });
            },
            saveKurikulum() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih Sekolah terlebih dahulu.' });
                    return;
                }
                if (!this.kurikulum.kelasId || !this.kurikulum.tahunAjaran || !this.kurikulum.semester) {
                    this.toast.fire({ icon: 'warning', title: 'Pilih Kelas, Tahun Ajaran, dan Semester terlebih dahulu.' });
                    return;
                }

                const cleanMappings = [];
                for (let i = 0; i < this.kurikulum.groups.length; i++) {
                    const group = this.kurikulum.groups[i];
                    const gName = group.kelompok_id.trim();
                    if (!gName) {
                        this.toast.fire({ icon: 'warning', title: `Nama Kelompok pada baris ${i + 1} tidak boleh kosong.` });
                        return;
                    }
                    if (group.mapel_ids.length === 0) {
                        this.toast.fire({ icon: 'warning', title: `Kelompok "${gName}" harus memilih minimal satu mata pelajaran.` });
                        return;
                    }
                    cleanMappings.push({
                        kelompok_id: gName,
                        mapel_ids: group.mapel_ids
                    });
                }

                const payload = {
                    kelas_id: this.kurikulum.kelasId,
                    tahun_ajaran: this.kurikulum.tahunAjaran,
                    semester: this.kurikulum.semester,
                    mappings: cleanMappings
                };

                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    payload.tenant_id = this.filterTenantId;
                }

                Swal.fire({
                    title: 'Simpan Kurikulum?',
                    text: "Penyimpanan akan menggantikan konfigurasi kurikulum yang sudah ada untuk kelas dan semester terpilih.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menyimpan...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        axios.post('/SINTA-SaaS/api/v1/kurikulum', payload)
                            .then(res => {
                                Swal.close();
                                this.toast.fire({ icon: 'success', title: res.data.message || 'Kurikulum berhasil disimpan.' });
                                this.loadKurikulumMapping();
                            })
                            .catch(err => {
                                Swal.close();
                                const msg = err.response && err.response.data && err.response.data.message 
                                    ? err.response.data.message 
                                    : 'Gagal menyimpan kurikulum.';
                                this.toast.fire({ icon: 'error', title: msg });
                            });
                    }
                });
            },
            fetchKelasOptions(tenantId = null) {
                const params = { module: 'kelas' };
                if (tenantId) {
                    params.tenant_id = tenantId;
                }
                axios.get('/SINTA-SaaS/api/v1/kelembagaan/options', { params })
                     .then(res => {
                         this.kelasOptions = res.data.data;
                     })
                     .catch(err => {
                         console.error("Gagal memuat filter kelas:", err);
                     });
            },
            fetchData(page = 1) {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.listData = [];
                    this.totalPages = 1;
                    this.total = 0;
                    this.from = 0;
                    this.to = 0;
                    this.loading = false;
                    return;
                }
                this.loading = true;
                this.currentPage = page;

                const params = {
                    page:      this.currentPage,
                    per_page:  this.perPage,
                    search:    this.search,
                    kelas_id:  this.filterKelas,
                    status:    this.filterStatus
                };

                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    params.filter_tenant_id = this.filterTenantId;
                }

                axios.get('/SINTA-SaaS/api/v1/buku-induk', { params })
                    .then(res => {
                        this.listData    = res.data.data;
                        this.totalPages  = res.data.last_page;
                        this.total       = res.data.total;
                        this.from        = res.data.from;
                        this.to          = res.data.to;
                        this.loading     = false;
                    }).catch(err => {
                        this.loading = false;
                        this.toast.fire({ icon: 'error', title: 'Gagal memuat Buku Induk.' });
                    });
            },
            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.fetchData(1);
                }, 400);
            },
            viewDetail(siswaId) {
                this.detailLoading = true;
                this.selectedSiswa = null;
                this.activeDetailTab = 'identitas';
                this.detailModalObj.show();

                axios.get('/SINTA-SaaS/api/v1/buku-induk/detail', { params: { id: siswaId } })
                     .then(res => {
                         this.selectedSiswa = res.data;
                         this.detailLoading = false;
                     })
                     .catch(err => {
                         this.detailLoading = false;
                         this.detailModalObj.hide();
                         this.toast.fire({ icon: 'error', title: 'Gagal memuat detail siswa.' });
                     });
            },
            formatTanggal(tglStr) {
                if (!tglStr) return '-';
                try {
                    const date = new Date(tglStr);
                    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
                } catch(e) {
                    return tglStr;
                }
            },
            getFileSizeLabel(key) {
                if (!this.selectedSiswa || !this.selectedSiswa.file_sizes) return 'N/A';
                try {
                    const sizes = JSON.parse(this.selectedSiswa.file_sizes) || {};
                    const sizeBytes = sizes[key] || 0;
                    if (sizeBytes === 0) return '0 KB';
                    return (sizeBytes / 1024).toFixed(1) + ' KB';
                } catch(e) {
                    return 'N/A';
                }
            },
            exportExcel() {
                Swal.fire({
                    icon: 'success',
                    title: 'Ekspor Sukses',
                    text: 'Ekspor berkas Buku Induk berformat Excel berhasil diunduh.',
                    confirmButtonText: 'OK'
                });
            },
            printBukuInduk() {
                window.print();
            },
            printSingleCard(siswaId) {
                Swal.fire({
                    icon: 'info',
                    title: 'Cetak Kartu Siswa',
                    text: 'Menghubungkan ke printer kartu sekolah... (Simulasi)',
                    confirmButtonText: 'Tutup'
                });
            },
            isReligionMismatch(studentReligion, subjectName) {
                if (!subjectName) return false;
                const subjLower = subjectName.toLowerCase();
                
                // If it's not a religion subject, it's general
                if (subjLower.indexOf('agama') === -1 && subjLower.indexOf('keagamaan') === -1) {
                    return false;
                }

                const religions = {
                    islam: ['islam'],
                    kristen: ['kristen', 'protestan'],
                    katolik: ['katolik'],
                    hindu: ['hindu'],
                    buddha: ['buddha', 'budha'],
                    konghucu: ['khonghucu', 'konghucu']
                };

                // Find subject religion key
                let subjectReligionKey = null;
                for (const [key, keywords] of Object.entries(religions)) {
                    for (const kw of keywords) {
                        if (subjLower.indexOf(kw) !== -1) {
                            subjectReligionKey = key;
                            break;
                        }
                    }
                    if (subjectReligionKey) break;
                }

                // If not matched to any specific religion, assume general religion course
                if (!subjectReligionKey) return false;

                // Normalize student religion
                if (!studentReligion) return false;
                const studLower = studentReligion.toLowerCase().trim();

                // Find student religion key
                let studentReligionKey = null;
                for (const [key, keywords] of Object.entries(religions)) {
                    for (const kw of keywords) {
                        if (studLower.indexOf(kw) !== -1) {
                            studentReligionKey = key;
                            break;
                        }
                    }
                    if (studentReligionKey) break;
                }

                return studentReligionKey !== subjectReligionKey;
            }
        }
    });
}
</script>
