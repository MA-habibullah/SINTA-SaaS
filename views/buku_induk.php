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
            <button v-if="filterKelas && isKelas12(getKelasName(filterKelas))" class="btn btn-outline-warning text-dark btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0 fw-semibold" 
                    @click="exportPdssExcel">
                <i class="bi bi-award-fill me-1"></i> Ekspor PDSS SNBP
            </button>
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
                <div class="col-12 col-md-4">
                    <select class="form-select form-select-sm rounded-3 shadow-none"
                            v-model="tempFilterTenantId"
                            id="sa-filter-sekolah-bukuinduk"
                            name="filter_tenant_id"
                            style="border:1.5px solid #bfdbfe;">
                        <option value="">🏫 -- Pilih Sekolah --</option>
                        <option v-for="t in listTenants" :key="t.id" :value="t.id">
                            {{ t.nama_sekolah }}
                        </option>
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm w-100" @click="applyTenantFilter">
                        <i class="bi bi-funnel-fill me-1"></i> Terapkan Filter
                    </button>
                </div>
                <div class="col-12 col-md-3">
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

                <!-- Filter Jenjang -->
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm rounded-3" v-model="filterJenjang">
                        <option value="">🎓 Semua Jenjang</option>
                        <option v-for="j in jenjangOptions" :value="j.id" :key="j.id">{{ j.nama_jenjang || j.nama }}</option>
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm rounded-3" v-model="filterKelas" @change="fetchData(1)">
                        <option value="">🏫 Semua Kelas</option>
                        <option v-for="k in filteredKelasOptions" :value="k.id" :key="k.id">{{ k.nama_kelas || k.nama }}</option>
                    </select>
                </div>

                <!-- Filter Status -->
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm rounded-3" v-model="filterStatus" @change="fetchData(1)">
                        <option value="">📋 Semua Status</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Pindah">Pindah</option>
                        <option value="Keluar">Keluar</option>
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
                        <li class="page-item" v-for="(page, idx) in paginationPages" :key="idx" 
                            :class="{active: page === currentPage, disabled: page === '...'}">
                            <a class="page-link" href="#" @click.prevent="page !== '...' ? fetchData(page) : null">{{ page }}</a>
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
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Tahun Ajaran</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                v-model="kurikulum.tahunAjaran"
                                @change="loadKurikulumMapping">
                            <option value="">-- Pilih TA --</option>
                            <option v-for="t in masterKurikulum.tahun_ajaran" :key="t.id" :value="t.tahun_ajaran">
                                {{ t.tahun_ajaran }}
                            </option>
                        </select>
                    </div>

                    <!-- Jenjang -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Jenjang</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                v-model="kurikulum.jenjangId">
                            <option value="">-- Pilih Jenjang --</option>
                            <option v-for="j in jenjangOptions" :key="j.id" :value="j.id">
                                {{ j.nama_jenjang || j.nama }}
                            </option>
                        </select>
                    </div>

                    <!-- Kelas -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Kelas Fisik</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                v-model="kurikulum.kelasId"
                                @change="loadKurikulumMapping">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="k in filteredKurikulumKelas" :key="k.id" :value="k.id">
                                {{ k.nama_kelas || k.nama }}
                            </option>
                        </select>
                    </div>

                    <!-- Semester -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Semester</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                v-model="kurikulum.semester"
                                @change="loadKurikulumMapping">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                            <option v-if="isKelas12(getKelasName(kurikulum.kelasId))" value="Ujian Sekolah">Ujian Sekolah</option>
                        </select>
                    </div>

                    <!-- Kurikulum -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Kurikulum Kelas</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                v-model="kurikulum.kurikulumId">
                            <option value="">-- Pilih Kurikulum --</option>
                            <option v-for="c in kurikulumList" :key="c.id" :value="c.id">
                                {{ c.nama_kurikulum }}
                            </option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm rounded-3 px-3 py-2 w-50 fs-8 fw-semibold"
                                :disabled="!kurikulum.kelasId || isLockedKurikulum == 1"
                                @click="showCopyModal"
                                title="Salin dari Kelas Lain">
                            <i class="bi bi-files"></i>
                        </button>
                        <button v-if="userRole === 'super_admin' || userRole === 'operator_sekolah'"
                                :class="['btn btn-sm rounded-3 px-2 py-2 w-50 fs-8 fw-semibold', isLockedKurikulum == 1 ? 'btn-danger' : 'btn-outline-secondary']"
                                :disabled="!kurikulum.tahunAjaran || !kurikulum.semester"
                                @click="toggleLock('kurikulum')">
                            <i :class="isLockedKurikulum == 1 ? 'bi bi-lock-fill' : 'bi bi-unlock-fill'"></i>
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
                    <button class="btn btn-primary rounded-3 px-4 py-2 fs-7 fw-semibold d-inline-flex align-items-center gap-2" 
                            @click="saveKurikulum"
                            :disabled="isLockedKurikulum == 1">
                        <i class="bi bi-save2"></i> Simpan Seting Kurikulum
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Reusable Copy Kurikulum Modal -->
    <div class="modal fade" id="copyKurikulumModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-files text-primary"></i>
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
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Tahun Ajaran</label>
                            <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                    v-model="nilaiRapor.tahunAjaran"
                                    @change="loadNilaiRaporGrid">
                                <option value="">-- Pilih TA --</option>
                                <option v-for="t in masterNilaiRapor.tahun_ajaran" :key="t.id" :value="t.tahun_ajaran">
                                    {{ t.tahun_ajaran }}
                                </option>
                            </select>
                        </div>

                        <!-- Semester -->
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Semester</label>
                            <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                    v-model="nilaiRapor.semester"
                                    @change="loadNilaiRaporGrid">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                                <option v-if="isKelas12(getKelasName(nilaiRapor.kelasId))" value="Ujian Sekolah">Ujian Sekolah</option>
                            </select>
                        </div>

                        <!-- Jenjang -->
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Jenjang</label>
                            <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                    v-model="nilaiRapor.jenjangId">
                                <option value="">-- Pilih Jenjang --</option>
                                <option v-for="j in jenjangOptions" :key="j.id" :value="j.id">
                                    {{ j.nama_jenjang || j.nama }}
                                </option>
                            </select>
                        </div>

                        <!-- Kelas -->
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Kelas Fisik</label>
                            <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle"
                                    v-model="nilaiRapor.kelasId"
                                    @change="loadNilaiRaporGrid">
                                <option value="">-- Pilih Kelas --</option>
                                <option v-for="k in filteredNilaiRaporKelas" :key="k.id" :value="k.id">
                                    {{ k.nama_kelas || k.nama }}
                                </option>
                            </select>
                        </div>

                        <!-- Actions (Import/Export & Lock) -->
                        <div class="col-12 col-md-4 d-flex gap-2">
                            <button class="btn btn-outline-success btn-sm rounded-3 px-2 py-2 flex-grow-1 fs-8 fw-semibold"
                                    :disabled="!nilaiRapor.kelasId || nilaiRapor.subjects.length === 0"
                                    @click="exportNilaiRaporExcel"
                                    title="Unduh Format Excel (.xlsx)">
                                <i class="bi bi-file-earmark-arrow-down"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm rounded-3 px-2 py-2 flex-grow-1 fs-8 fw-semibold"
                                    :disabled="!nilaiRapor.kelasId || nilaiRapor.subjects.length === 0 || isLockedNilai == 1 || isRombelLocked"
                                    @click="showImportGradesModal"
                                    title="Unggah Nilai Excel (.xlsx)">
                                <i class="bi bi-file-earmark-arrow-up"></i>
                            </button>
                            <button v-if="userRole === 'super_admin' || userRole === 'operator_sekolah' || userRole === 'admin'"
                                    :class="['btn btn-sm rounded-3 px-2 py-2 flex-grow-1 fs-8 fw-semibold', isLockedNilai == 1 ? 'btn-danger' : 'btn-outline-secondary']"
                                    :disabled="!nilaiRapor.tahunAjaran || !nilaiRapor.semester"
                                    @click="toggleLock('nilai')"
                                    :title="isLockedNilai == 1 ? 'Terkunci' : 'Kunci Global'">
                                <i :class="isLockedNilai == 1 ? 'bi bi-lock-fill' : 'bi bi-unlock-fill'"></i>
                            </button>
                            <!-- Kunci Rombel Kelas -->
                            <button v-if="userRole === 'super_admin' || userRole === 'operator_sekolah' || userRole === 'admin'"
                                    :class="['btn btn-sm rounded-3 px-2 py-2 flex-grow-1 fs-8 fw-semibold', isRombelLocked ? 'btn-danger' : 'btn-outline-danger']"
                                    :disabled="!nilaiRapor.kelasId || !nilaiRapor.tahunAjaran"
                                    @click="toggleRombelLock"
                                    :title="isRombelLocked ? 'Buka Kunci Rombel' : 'Kunci Rombel Kelas'">
                                <i :class="isRombelLocked ? 'bi bi-shield-lock-fill' : 'bi bi-shield-slash'"></i>
                                <span class="ms-1 fs-9 d-md-none d-lg-inline">{{ isRombelLocked ? 'Locked' : 'Lock Rombel' }}</span>
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
                                        <th style="width: 130px; vertical-align: middle;">Tahun Ajaran</th>
                                        <th style="width: 100px; vertical-align: middle;">Semester</th>
                                        <th style="width: 150px; vertical-align: middle;">Kurikulum Aktif</th>
                                        <th style="width: 150px; vertical-align: middle;">Rata-rata Nilai</th>
                                        <th style="width: 120px; vertical-align: middle;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(student, sIdx) in nilaiRapor.students" :key="student.id">
                                        <td class="text-center text-muted">{{ sIdx + 1 }}</td>
                                        <td class="text-center font-monospace">{{ student.nisn || student.nis || '-' }}</td>
                                        <td class="fw-bold text-dark">{{ student.nama_lengkap }}</td>
                                        <td class="text-center text-muted fs-8">{{ nilaiRapor.tahunAjaran }}</td>
                                        <td class="text-center text-muted fs-8">{{ nilaiRapor.semester }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill fs-9">
                                                {{ nilaiRapor.kurikulum.nama_kurikulum || 'Kurikulum Merdeka' }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold fs-7" :class="getAverageGrade(student.id) != '-' && parseFloat(getAverageGrade(student.id)) >= 75 ? 'text-success' : 'text-danger'">
                                            {{ getAverageGrade(student.id) }}
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-primary border-0 rounded-circle" 
                                                        title="Input / Edit Detail Nilai Rapor" 
                                                        @click="openInputDetailNilaiModal(student)">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" 
                                                        title="Hapus Nilai Siswa Ini" 
                                                        :disabled="isLockedNilai == 1"
                                                        @click="confirmDeleteGrades(student)">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>
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
                        <button class="btn btn-primary rounded-3 px-4 py-2 fs-7 fw-semibold d-inline-flex align-items-center gap-2" 
                                @click="saveNilaiRapor"
                                :disabled="isLockedNilai == 1 || nilaiRapor.isSaving">
                            <span v-if="nilaiRapor.isSaving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <i v-else class="bi bi-save2"></i> 
                            {{ nilaiRapor.isSaving ? 'Menyimpan...' : 'Simpan Perubahan Nilai' }}
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Tab Cetak Buku Induk -->
    <div v-show="mainActiveTab === 'cetak_buku_induk'" class="animate-fade-in">
        <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 align-items-end">
                    <!-- Tahun Ajaran -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Tahun Ajaran</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle" v-model="filterTahunAjaranCetak" @change="loadMatrixData">
                            <option value="">-- Semua TA --</option>
                            <option v-for="t in masterNilaiRapor?.tahun_ajaran || []" :key="t.id || t.tahun_ajaran" :value="t.tahun_ajaran">{{ t.tahun_ajaran }}</option>
                        </select>
                    </div>
                    <!-- Jenjang -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Filter Jenjang</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle" v-model="filterJenjang">
                            <option value="">-- Semua Jenjang --</option>
                            <option v-for="j in jenjangOptions" :key="j.id" :value="j.id">{{ j.nama_jenjang || j.nama }}</option>
                        </select>
                    </div>
                    <!-- Kelas -->
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Filter Kelas</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle" v-model="filterKelas" @change="loadMatrixData">
                            <option value="">-- Semua Kelas --</option>
                            <option v-for="k in filteredKelasOptions" :key="k.id" :value="k.id">{{ k.nama_kelas || k.nama }}</option>
                        </select>
                    </div>
                    <!-- Status -->
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Status Siswa</label>
                        <select class="form-select form-select-sm rounded-3 shadow-none border-secondary-subtle" v-model="filterStatus" @change="loadMatrixData">
                            <option value="">-- Semua Status --</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Lulus">Lulus</option>
                            <option value="Pindah">Pindah</option>
                            <option value="Drop Out">Drop Out</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button class="btn btn-primary btn-sm rounded-3 w-100 fs-8" @click="loadMatrixData">Tampilkan</button>
                    </div>
                </div>
                <!-- Bulk Actions -->
                <div class="row g-3 mt-2 border-top pt-3" v-if="filterKelas">
                    <div class="col-12 col-md-5 d-flex gap-2 align-items-center">
                        <label class="form-label fw-bold text-dark fs-8 mb-0 text-nowrap">Cetak Massal Rombel:</label>
                        <select class="form-select form-select-sm rounded-3" v-model="bulkPrintSemester" style="max-width: 180px;">
                            <option value="">-- Pilih Semester --</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                            <option v-if="isKelas12(getKelasName(filterKelas))" value="Ujian Sekolah">Ujian Sekolah</option>
                        </select>
                        <button class="btn btn-outline-primary btn-sm rounded-3 px-3 fs-8 text-nowrap" @click="printBulkRapor" :disabled="!bulkPrintSemester">
                            <i class="bi bi-printer-fill me-1"></i> Rapor Massal
                        </button>
                        <button class="btn btn-outline-success btn-sm rounded-3 px-3 fs-8 text-nowrap" @click="printBulkIdentitas">
                            <i class="bi bi-people-fill me-1"></i> Identitas Massal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="loadingMatrix" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 fs-7">Memuat matriks cetak buku induk...</p>
        </div>
        <div v-else class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 fs-8 text-nowrap">
                        <thead class="bg-light text-center">
                            <tr>
                                <th rowspan="2" class="align-middle" style="width: 40px;">NO</th>
                                <th rowspan="2" class="align-middle">NISN</th>
                                <th rowspan="2" class="align-middle">Nama Siswa</th>
                                <th rowspan="2" class="align-middle">Tahun Masuk</th>
                                <th rowspan="2" class="align-middle">Identitas Siswa</th>
                                <th rowspan="2" class="align-middle">Transkrip Nilai</th>
                                <th v-for="n in matrixMaxYears" :key="'year-'+n" colspan="3" class="align-middle bg-primary text-white">
                                    Tahun Ke-{{ n }}
                                </th>
                            </tr>
                            <tr>
                                <template v-for="n in matrixMaxYears" :key="'sub-'+n">
                                    <th class="bg-light border-start">Kelas</th>
                                    <th class="bg-light">Semester 1</th>
                                    <th class="bg-light">Semester 2</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="matrixData.length === 0">
                                <td :colspan="6 + (matrixMaxYears * 3)" class="text-center text-muted py-4">Data tidak ditemukan.</td>
                            </tr>
                            <tr v-for="(siswa, idx) in matrixData" :key="siswa.id">
                                <td class="text-center text-muted">{{ idx + 1 }}</td>
                                <td class="text-center">{{ siswa.nisn || '-' }}</td>
                                <td class="fw-bold text-dark">{{ siswa.nama_lengkap }}</td>
                                <td class="text-center">{{ siswa.tahun_masuk || '-' }}</td>
                                <td class="text-center">
                                    <button @click="openPrintModal('/SINTA-SaaS/cetak-buku-induk?id=' + siswa.id, 'Cetak Buku Induk')" class="btn btn-outline-dark btn-sm rounded-3 py-1 px-2 fs-8 fw-semibold" title="Cetak Identitas Siswa">
                                        <i class="bi bi-printer me-1"></i> Cetak
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button @click="openPrintModal('/SINTA-SaaS/cetak-transkrip-nilai?id=' + siswa.id, 'Transkrip Kelulusan')" class="btn btn-success btn-sm rounded-3 py-1 px-2 fs-8 fw-semibold" title="Cetak Transkrip Kelulusan">
                                        <i class="bi bi-award me-1"></i> Transkrip
                                    </button>
                                </td>
                                
                                <template v-for="n in matrixMaxYears" :key="'td-'+n">
                                    <!-- Jika n <= jumlah history tahun -->
                                    <template v-if="n <= siswa.years.length">
                                        <td class="text-center fw-semibold text-primary bg-light border-start">{{ siswa.years[n-1].nama_kelas || '-' }}</td>
                                        <td class="text-center">
                                            <button v-if="siswa.years[n-1].has_ganjil || siswa.years[n-1].tahun_ajaran !== '-'" @click="openPrintModal('/SINTA-SaaS/cetak-rapot-semester?id=' + siswa.id + '&semester=Ganjil&ta=' + encodeURIComponent(siswa.years[n-1].tahun_ajaran), 'Rapor Semester Ganjil (' + siswa.years[n-1].tahun_ajaran + ')')" class="btn btn-primary btn-sm rounded-3 py-1 px-2 fs-8">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1 justify-content-center">
                                                <button v-if="siswa.years[n-1].has_genap || siswa.years[n-1].tahun_ajaran !== '-'" @click="openPrintModal('/SINTA-SaaS/cetak-rapot-semester?id=' + siswa.id + '&semester=Genap&ta=' + encodeURIComponent(siswa.years[n-1].tahun_ajaran), 'Rapor Semester Genap (' + siswa.years[n-1].tahun_ajaran + ')')" class="btn btn-primary btn-sm rounded-3 py-1 px-2 fs-8">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                                <button v-if="isKelas12(siswa.years[n-1].nama_kelas)" @click="openPrintModal('/SINTA-SaaS/cetak-rapot-semester?id=' + siswa.id + '&semester=' + encodeURIComponent('Ujian Sekolah') + '&ta=' + encodeURIComponent(siswa.years[n-1].tahun_ajaran), 'Rapor Ujian Sekolah (' + siswa.years[n-1].tahun_ajaran + ')')" class="btn btn-warning btn-sm rounded-3 py-1 px-2 fs-8 text-dark fw-bold" title="Cetak Rapor Ujian Sekolah">
                                                    US
                                                </button>
                                            </div>
                                            <span v-if="!siswa.years[n-1].has_genap && siswa.years[n-1].tahun_ajaran === '-' && !isKelas12(siswa.years[n-1].nama_kelas)" class="text-muted">-</span>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="text-center bg-light text-muted border-start">-</td>
                                        <td class="text-center text-muted">-</td>
                                        <td class="text-center text-muted">-</td>
                                    </template>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Kepsek Tab -->
    <div v-show="mainActiveTab === 'riwayat_kepsek'" class="card border-0 shadow-sm rounded-4 animate-fade-in">
        <div class="card-body p-3 p-md-4">
            
            <div v-if="userRole === 'super_admin' && !filterTenantId" class="py-5 text-center bg-white rounded-4 border-0">
                <i class="bi bi-building text-secondary display-4 d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Pilih Sekolah Terlebih Dahulu</h5>
                <p class="text-muted fs-7 mx-auto" style="max-width: 480px;">
                    Silakan pilih Sekolah terlebih dahulu pada filter "Pilih Sekolah" di atas untuk mengelola Riwayat Kepala Sekolah.
                </p>
            </div>
            
            <div v-else>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Kepala Sekolah</h5>
                    <button class="btn btn-primary btn-sm rounded-3 px-3" @click="showTambahKepsek">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Riwayat
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sekolah</th>
                            <th>Nama Kepala Sekolah</th>
                            <th>NIP</th>
                            <th>Status</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="k in riwayatKepsek" :key="k.id">
                            <td class="text-muted"><small><i class="bi bi-building me-1"></i>{{ selectedTenantName }}</small></td>
                            <td class="fw-bold">{{ k.nama_kepsek }}</td>
                            <td>{{ k.nip_kepsek || '-' }}</td>
                            <td>
                                <span class="badge" :class="k.status_plt == 1 ? 'bg-warning text-dark' : 'bg-success'">
                                    {{ k.status_plt == 1 ? 'Plt / Pjs' : 'Definitif' }}
                                </span>
                            </td>
                            <td>{{ k.tanggal_mulai }}</td>
                            <td>
                                <span v-if="k.tanggal_selesai">{{ k.tanggal_selesai }}</span>
                                <span v-else class="badge bg-primary">Masih Menjabat</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary me-1" @click="editKepsek(k)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="hapusKepsek(k.id)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="!riwayatKepsek.length">
                            <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat kepala sekolah tercatat.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div> <!-- End of v-else -->
        </div>
    </div>

    <!-- Arsip Alumni Tab -->
    <div v-show="mainActiveTab === 'arsip_alumni'" class="card border-0 shadow-sm rounded-4 animate-fade-in">
        <?php require __DIR__ . '/arsip_alumni.php'; ?>
    </div>

    <!-- Modal View Alumni Doc (PDF Viewer) -->
    <div class="modal fade" id="modalViewAlumniDoc" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 90%; height: 90vh;">
            <div class="modal-content border-0 shadow rounded-4" style="height: 100%;">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-pdf-fill text-danger me-2"></i>{{ activeAlumniDocTitle }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-secondary-subtle" style="height: calc(100% - 60px);">
                    <iframe v-if="activeAlumniDocUrl" :src="activeAlumniDocUrl" class="w-100 h-100 border-0"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Riwayat Kepsek -->
    <div class="modal fade" id="modalRiwayatKepsek" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">{{ modalKepsekTitle }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kepala Sekolah</label>
                        <input type="text" class="form-control" v-model="formKepsek.nama_kepsek" placeholder="Contoh: Drs. H. Ahmad, M.Pd">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NIP</label>
                        <input type="text" class="form-control" v-model="formKepsek.nip_kepsek" placeholder="Boleh kosong jika non-PNS">
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="statusPlt" v-model="formKepsek.status_plt" :true-value="1" :false-value="0">
                            <label class="form-check-label fw-semibold" for="statusPlt">Status Plt. (Pelaksana Tugas)</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" class="form-control" v-model="formKepsek.tanggal_mulai">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Tanggal Selesai</label>
                            <input type="date" class="form-control" v-model="formKepsek.tanggal_selesai">
                            <small class="text-muted fs-8">Kosongkan jika masih menjabat</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary px-4" @click="saveKepsek">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Cetak Dokumen -->
    <div class="modal fade" id="modalCetakDokumen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-printer text-primary"></i> Cetak Dokumen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted fs-7 mb-4">Silakan tentukan tanggal yang akan tertera pada dokumen. Sistem akan otomatis menyesuaikan nama Kepala Sekolah yang menjabat pada tanggal tersebut.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Dokumen</label>
                        <input type="text" class="form-control bg-light" readonly :value="printModal.title">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Cetak Dokumen</label>
                        <input type="date" class="form-control" v-model="printModal.tanggalCetak">
                    </div>

                    <!-- Pilihan Menampilkan QR Code -->
                    <div class="form-check form-switch mb-2" v-if="printModal.url && !printModal.url.includes('print-rapot-kelas')">
                        <input class="form-check-input" type="checkbox" id="flexSwitchShowQrCode" v-model="printModal.showQrCode">
                        <label class="form-check-label fw-semibold text-dark fs-8" for="flexSwitchShowQrCode">Tampilkan QR Code Verifikasi</label>
                    </div>

                    <!-- Pilihan Hapus/Perbarui Arsip Rapor (Re-Generate) -->
                    <div class="form-check form-switch mb-3" v-if="printModal.url && (printModal.url.includes('cetak-rapot') || printModal.url.includes('cetak-transkrip'))">
                        <input class="form-check-input" type="checkbox" id="flexSwitchReGenerate" v-model="printModal.reGenerate">
                        <label class="form-check-label fw-semibold text-danger fs-8" for="flexSwitchReGenerate">Perbarui Arsip Rapor (Ambil Nilai Terbaru)</label>
                    </div>
                </div>
                <div class="modal-footer border-top p-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary px-4" @click="executePrint">Cetak Sekarang</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reusable Import Nilai Modal -->
    <div class="modal fade" id="importNilaiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" :class="importState === 'preview' ? 'modal-lg' : ''">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-arrow-up text-primary"></i>
                        Impor Nilai Rapor (Excel)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <!-- State: Select File -->
                    <div v-if="importState === 'select'">
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

                    <!-- State: Validating -->
                    <div v-else-if="importState === 'validating'" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted mt-2 fs-7 fw-semibold">Memvalidasi data Excel dengan database...</p>
                    </div>

                    <!-- State: Preview (Validator Results) -->
                    <div v-else-if="importState === 'preview'">
                        <!-- Summary Cards -->
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-3">
                                <div class="p-2 bg-light border rounded-3">
                                    <span class="fs-9 text-muted d-block fw-semibold text-uppercase">Total Baris</span>
                                    <span class="fs-6 fw-bold text-dark">{{ validationSummary.total_rows }}</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 bg-success-subtle border border-success-subtle rounded-3">
                                    <span class="fs-9 text-success d-block fw-semibold text-uppercase">Valid</span>
                                    <span class="fs-6 fw-bold text-success">{{ validationSummary.valid }}</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 bg-warning-subtle border border-warning-subtle rounded-3">
                                    <span class="fs-9 text-warning d-block fw-semibold text-uppercase">Peringatan</span>
                                    <span class="fs-6 fw-bold text-warning">{{ validationSummary.warning }}</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 bg-danger-subtle border border-danger-subtle rounded-3">
                                    <span class="fs-9 text-danger d-block fw-semibold text-uppercase">Eror</span>
                                    <span class="fs-6 fw-bold text-danger">{{ validationSummary.error }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Rows Preview List -->
                        <div class="border rounded-3 p-2 bg-white mb-3" style="max-height: 250px; overflow-y: auto;">
                            <div v-for="(row, rIdx) in validationData" :key="row.siswa_id" class="border-bottom py-2 px-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark fs-8">{{ rIdx + 1 }}. {{ row.nama_lengkap }}</span>
                                    <span :class="['badge rounded-pill fs-9 px-2 py-0.5', 
                                                  row.status === 'valid' ? 'bg-success-subtle text-success' : 
                                                  row.status === 'warning' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger']">
                                        {{ row.status.toUpperCase() }}
                                    </span>
                                </div>
                                <!-- Errors/Warnings Details -->
                                <ul class="mb-0 ps-3 text-danger fs-8" v-if="row.errors.length > 0">
                                    <li v-for="err in row.errors" :key="err">{{ err }}</li>
                                </ul>
                                <ul class="mb-0 ps-3 text-warning-emphasis fs-8" v-if="row.warnings.length > 0">
                                    <li v-for="wrn in row.warnings" :key="wrn">{{ wrn }}</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Warning Message -->
                        <div class="alert alert-danger border-0 d-flex align-items-center gap-2 mb-0 py-2.5 rounded-3 fs-8" v-if="validationSummary.error > 0">
                            <i class="bi bi-exclamation-octagon-fill fs-6 text-danger"></i>
                            <span>Ditemukan kesalahan data (eror). Mohon perbaiki berkas Excel Anda sebelum mengunggah kembali.</span>
                        </div>
                        <div class="alert alert-warning border-0 d-flex align-items-center gap-2 mb-0 py-2.5 rounded-3 fs-8" v-else-if="validationSummary.warning > 0">
                            <i class="bi bi-exclamation-triangle-fill fs-6 text-warning"></i>
                            <span>Data valid dengan peringatan. Peringatan perbedaan agama akan diabaikan otomatis saat impor.</span>
                        </div>
                        <div class="alert alert-success border-0 d-flex align-items-center gap-2 mb-0 py-2.5 rounded-3 fs-8" v-else>
                            <i class="bi bi-check-circle-fill fs-6 text-success"></i>
                            <span>Semua data berhasil divalidasi dan siap untuk diimpor.</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                    <!-- Footer: Select File -->
                    <template v-if="importState === 'select'">
                        <button type="button" class="btn btn-light rounded-3 fs-8 px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary rounded-3 fs-8 px-4" @click="validateImportGrades" :disabled="!importFile">
                            Mulai Impor
                        </button>
                    </template>
                    <!-- Footer: Validating -->
                    <template v-else-if="importState === 'validating'">
                        <button type="button" class="btn btn-light rounded-3 fs-8 px-4" :disabled="true">Batal</button>
                        <button type="button" class="btn btn-primary rounded-3 fs-8 px-4" :disabled="true">Memproses...</button>
                    </template>
                    <!-- Footer: Preview -->
                    <template v-else-if="importState === 'preview'">
                        <button type="button" class="btn btn-light rounded-3 fs-8 px-4" @click="resetImportState">Kembali</button>
                        <button type="button" class="btn btn-primary rounded-3 fs-8 px-4" @click="submitImportGrades" :disabled="validationSummary.error > 0">
                            Lanjutkan Simpan Nilai
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Input Detail Nilai Rapor (Dinamis Multi-Kurikulum) -->
    <div class="modal fade" id="modalInputDetailNilai" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3 bg-light">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-primary"></i>
                        Detail Nilai Rapor: <span class="text-primary">{{ activeEditSiswa ? activeEditSiswa.nama_lengkap : '' }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" v-if="activeEditSiswa">
                    
                    <!-- Info Bar -->
                    <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fs-8 text-secondary d-block fw-semibold text-uppercase">Kurikulum Aktif</span>
                            <span class="fs-6 text-primary fw-bold">{{ nilaiRapor.kurikulum.nama_kurikulum || 'Kurikulum Merdeka' }}</span>
                        </div>
                        <div>
                            <span class="fs-8 text-secondary d-block fw-semibold text-uppercase">Tahun Ajaran / Semester</span>
                            <span class="fs-7 text-dark fw-bold">{{ nilaiRapor.tahunAjaran }} - {{ nilaiRapor.semester }}</span>
                        </div>
                    </div>

                    <!-- 1. FORM PENILAIAN KLASIK (KTSP / KBK) -->
                    <div v-if="nilaiRapor.kurikulum.tipe_penilaian === 'klasik'">
                        <div v-for="sub in nilaiRapor.subjects" :key="sub.mapel_id" class="card border border-secondary-subtle rounded-3 mb-3 shadow-none">
                            <div class="card-header bg-light py-2 fw-semibold text-dark fs-7 d-flex justify-content-between align-items-center">
                                <span>{{ sub.nama_mapel }} <small class="text-muted font-monospace fs-9">({{ sub.kode_mapel }})</small></span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9" v-if="isReligionMismatch(activeEditSiswa.agama, sub.nama_mapel)">Beda Agama</span>
                            </div>
                            <div class="card-body p-3 bg-warning-subtle text-warning-emphasis" v-if="isReligionMismatch(activeEditSiswa.agama, sub.nama_mapel)">
                                <div class="d-flex align-items-center gap-2 fs-8 fw-semibold">
                                    <i class="bi bi-exclamation-triangle-fill text-warning fs-6"></i>
                                    <span>Peringatan: Mata pelajaran ini tidak sesuai dengan keyakinan siswa ({{ activeEditSiswa.agama || 'Belum Diisi' }}). Pengisian dinonaktifkan.</span>
                                </div>
                            </div>
                            <div class="card-body p-3" v-else>
                                <div class="row g-3">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label fs-8 fw-semibold text-muted mb-1">KKM Mapel</label>
                                        <input type="number" min="0" max="100" class="form-control form-control-sm" v-model.number="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].kkm" :disabled="isLockedNilai == 1">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label fs-8 fw-semibold text-muted mb-1">Nilai Kognitif</label>
                                        <input type="number" min="0" max="100" class="form-control form-control-sm text-center fw-bold" v-model.number="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.kognitif" :disabled="isLockedNilai == 1">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label fs-8 fw-semibold text-muted mb-1">Nilai Psikomotorik</label>
                                        <input type="number" min="0" max="100" class="form-control form-control-sm text-center fw-bold" v-model.number="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.psikomotorik" :disabled="isLockedNilai == 1">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label fs-8 fw-semibold text-muted mb-1">Sikap (Afektif)</label>
                                        <select class="form-select form-select-sm text-center fw-bold" v-model="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.afektif" :disabled="isLockedNilai == 1">
                                            <option value="">-</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. FORM PENILAIAN KOMPLEKS (KURIKULUM 2013) -->
                    <div v-else-if="nilaiRapor.kurikulum.tipe_penilaian === 'kompleks'">
                        <!-- Loop Mapel -->
                        <div v-for="sub in nilaiRapor.subjects" :key="sub.mapel_id" class="card border border-secondary-subtle rounded-3 mb-3 shadow-none">
                            <div class="card-header bg-light py-2 fw-semibold text-dark fs-7 d-flex justify-content-between align-items-center">
                                <span>{{ sub.nama_mapel }} <small class="text-muted font-monospace fs-9">({{ sub.kode_mapel }})</small></span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9" v-if="isReligionMismatch(activeEditSiswa.agama, sub.nama_mapel)">Beda Agama</span>
                            </div>
                            <div class="card-body p-3 bg-warning-subtle text-warning-emphasis" v-if="isReligionMismatch(activeEditSiswa.agama, sub.nama_mapel)">
                                <div class="d-flex align-items-center gap-2 fs-8 fw-semibold">
                                    <i class="bi bi-exclamation-triangle-fill text-warning fs-6"></i>
                                    <span>Peringatan: Mata pelajaran ini tidak sesuai dengan keyakinan siswa ({{ activeEditSiswa.agama || 'Belum Diisi' }}). Pengisian dinonaktifkan.</span>
                                </div>
                            </div>
                            <div class="card-body p-3" v-else>
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label fs-8 fw-semibold text-muted mb-1">KKM Mapel</label>
                                        <input type="number" min="0" max="100" class="form-control form-control-sm" v-model.number="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].kkm" :disabled="isLockedNilai == 1">
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <!-- KI-3 Pengetahuan -->
                                    <div class="col-12 col-md-6 border-end border-secondary-subtle pe-md-3">
                                        <h6 class="fw-bold text-dark fs-8 border-bottom pb-1 mb-2"><i class="bi bi-book text-primary me-1"></i>KI-3: Pengetahuan</h6>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label fs-9 fw-semibold text-muted mb-0">Nilai (0-100)</label>
                                                <input type="number" min="0" max="100" class="form-control form-control-sm text-center fw-bold text-primary" v-model.number="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.pengetahuan_nilai" :disabled="isLockedNilai == 1">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fs-9 fw-semibold text-muted mb-0">Predikat</label>
                                                <input type="text" maxlength="2" class="form-control form-control-sm text-center fw-bold" placeholder="A/B/C/D" v-model="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.pengetahuan_predikat" :disabled="isLockedNilai == 1">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label fs-9 fw-semibold text-muted mb-0">Deskripsi Capaian</label>
                                            <textarea class="form-control form-control-sm fs-8" rows="2" placeholder="Masukkan deskripsi pengetahuan..." v-model="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.pengetahuan_deskripsi" :disabled="isLockedNilai == 1"></textarea>
                                        </div>
                                    </div>
                                    <!-- KI-4 Keterampilan -->
                                    <div class="col-12 col-md-6 ps-md-3">
                                        <h6 class="fw-bold text-dark fs-8 border-bottom pb-1 mb-2"><i class="bi bi-tools text-primary me-1"></i>KI-4: Keterampilan</h6>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label fs-9 fw-semibold text-muted mb-0">Nilai (0-100)</label>
                                                <input type="number" min="0" max="100" class="form-control form-control-sm text-center fw-bold text-success" v-model.number="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.keterampilan_nilai" :disabled="isLockedNilai == 1">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fs-9 fw-semibold text-muted mb-0">Predikat</label>
                                                <input type="text" maxlength="2" class="form-control form-control-sm text-center fw-bold" placeholder="A/B/C/D" v-model="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.keterampilan_predikat" :disabled="isLockedNilai == 1">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label fs-9 fw-semibold text-muted mb-0">Deskripsi Capaian</label>
                                            <textarea class="form-control form-control-sm fs-8" rows="2" placeholder="Masukkan deskripsi keterampilan..." v-model="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.keterampilan_deskripsi" :disabled="isLockedNilai == 1"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sikap KI-1 dan KI-2 (Global) -->
                        <div class="card border border-primary rounded-3 mb-3 shadow-none" style="border-width: 1.5px !important;">
                            <div class="card-header bg-primary text-white py-2 fw-bold fs-7">
                                <i class="bi bi-heart-pulse-fill me-1"></i> ASPEK SIKAP SPIRITUAL (KI-1) & SOSIAL (KI-2) GLOBAL
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 border-end border-secondary-subtle pe-md-3">
                                        <h6 class="fw-bold text-dark fs-8 border-bottom pb-1 mb-2">1. Sikap Spiritual (KI-1)</h6>
                                        <div class="mb-2">
                                            <label class="form-label fs-9 fw-semibold text-muted mb-0">Predikat</label>
                                            <select class="form-select form-select-sm fw-bold" v-model="nilaiRapor.sikapK13[activeEditSiswa.id].predikat_spiritual" :disabled="isLockedNilai == 1">
                                                <option value="">-</option>
                                                <option value="Sangat Baik">Sangat Baik</option>
                                                <option value="Baik">Baik</option>
                                                <option value="Cukup">Cukup</option>
                                                <option value="Kurang">Kurang</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label fs-9 fw-semibold text-muted mb-0">Deskripsi Deskriptif</label>
                                            <textarea class="form-control form-control-sm fs-8" rows="3" placeholder="Deskripsikan sikap spiritual siswa..." v-model="nilaiRapor.sikapK13[activeEditSiswa.id].deskripsi_spiritual" :disabled="isLockedNilai == 1"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 ps-md-3">
                                        <h6 class="fw-bold text-dark fs-8 border-bottom pb-1 mb-2">2. Sikap Sosial (KI-2)</h6>
                                        <div class="mb-2">
                                            <label class="form-label fs-9 fw-semibold text-muted mb-0">Predikat</label>
                                            <select class="form-select form-select-sm fw-bold" v-model="nilaiRapor.sikapK13[activeEditSiswa.id].predikat_sosial" :disabled="isLockedNilai == 1">
                                                <option value="">-</option>
                                                <option value="Sangat Baik">Sangat Baik</option>
                                                <option value="Baik">Baik</option>
                                                <option value="Cukup">Cukup</option>
                                                <option value="Kurang">Kurang</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label fs-9 fw-semibold text-muted mb-0">Deskripsi Deskriptif</label>
                                            <textarea class="form-control form-control-sm fs-8" rows="3" placeholder="Deskripsikan sikap sosial siswa..." v-model="nilaiRapor.sikapK13[activeEditSiswa.id].deskripsi_sosial" :disabled="isLockedNilai == 1"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. FORM PENILAIAN SEDERHANA (KURIKULUM MERDEKA) -->
                    <div v-else>
                        <div v-for="sub in nilaiRapor.subjects" :key="sub.mapel_id" class="card border border-secondary-subtle rounded-3 mb-3 shadow-none">
                            <div class="card-header bg-light py-2 fw-semibold text-dark fs-7 d-flex justify-content-between align-items-center">
                                <span>{{ sub.nama_mapel }} <small class="text-muted font-monospace fs-9">({{ sub.kode_mapel }})</small></span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9" v-if="isReligionMismatch(activeEditSiswa.agama, sub.nama_mapel)">Beda Agama</span>
                            </div>
                            <div class="card-body p-3 bg-warning-subtle text-warning-emphasis" v-if="isReligionMismatch(activeEditSiswa.agama, sub.nama_mapel)">
                                <div class="d-flex align-items-center gap-2 fs-8 fw-semibold">
                                    <i class="bi bi-exclamation-triangle-fill text-warning fs-6"></i>
                                    <span>Peringatan: Mata pelajaran ini tidak sesuai dengan keyakinan siswa ({{ activeEditSiswa.agama || 'Belum Diisi' }}). Pengisian dinonaktifkan.</span>
                                </div>
                            </div>
                            <div class="card-body p-3" v-else>
                                <div class="row g-3 mb-2">
                                    <div class="col-6">
                                        <label class="form-label fs-8 fw-semibold text-muted mb-1">KKTP (Standar Kelulusan)</label>
                                        <input type="number" min="0" max="100" class="form-control form-control-sm text-center font-monospace" v-model.number="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].kkm" :disabled="isLockedNilai == 1">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-8 fw-semibold text-muted mb-1">Nilai Akhir (Angka)</label>
                                        <input type="number" min="0" max="100" class="form-control form-control-sm text-center fw-bold text-primary fs-7" v-model.number="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].nilai_akhir" :disabled="isLockedNilai == 1">
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fs-9 fw-semibold text-muted mb-0">Capaian Kompetensi Tertinggi</label>
                                        <textarea class="form-control form-control-sm fs-8" rows="2" placeholder="Kompetensi yang sangat dikuasai..." v-model="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.deskripsi_tertinggi" :disabled="isLockedNilai == 1"></textarea>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fs-9 fw-semibold text-muted mb-0">Capaian Kompetensi Terendah</label>
                                        <textarea class="form-control form-control-sm fs-8" rows="2" placeholder="Kompetensi yang perlu bimbingan..." v-model="nilaiRapor.grades[activeEditSiswa.id][sub.mapel_id].detail.deskripsi_terendah" :disabled="isLockedNilai == 1"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                    <button type="button" class="btn btn-primary rounded-3 fs-8 px-5 fw-bold" data-bs-dismiss="modal">
                        Selesai Mengisi
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
                                                                <span class="text-dark">Anak Ke-{{ selectedSiswa.anak_ke || '1' }} dari {{ selectedSiswa.jumlah_saudara || '0' }} bersaudara ({{ selectedSiswa.saudara_tiri || '0' }} tiri, {{ selectedSiswa.saudara_angkat || '0' }} angkat)</span>
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
                                                            <div class="col-12 mt-3" v-if="selectedSiswa.kesehatan && Object.keys(selectedSiswa.kesehatan).length > 0">
                                                                <span class="text-muted d-block fw-bold mb-2"><i class="bi bi-activity text-danger me-1"></i>Riwayat Kesehatan (Per Semester)</span>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered table-sm fs-9 text-center">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th>Sem</th>
                                                                                <th>Tinggi (cm)</th>
                                                                                <th>Berat (kg)</th>
                                                                                <th>Pendengaran</th>
                                                                                <th>Pengelihatan</th>
                                                                                <th>Gigi</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr v-for="(kes, sem) in selectedSiswa.kesehatan" :key="sem">
                                                                                <td class="fw-bold">{{ sem }}</td>
                                                                                <td>{{ kes.tinggi_badan || '-' }}</td>
                                                                                <td>{{ kes.berat_badan || '-' }}</td>
                                                                                <td>{{ kes.pendengaran || '-' }}</td>
                                                                                <td>{{ kes.pengelihatan || '-' }}</td>
                                                                                <td>{{ kes.gigi || '-' }}</td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
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
                                                            <span class="badge rounded-pill" :class="h.jenis_aksi === 'naik_kelas' ? 'bg-success-subtle text-success' : (h.jenis_aksi === 'penempatan_awal' ? 'bg-info-subtle text-info' : (h.jenis_aksi === 'tinggal_kelas' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'))">
                                                                {{ h.jenis_aksi === 'naik_kelas' ? 'Naik Kelas' : (h.jenis_aksi === 'penempatan_awal' ? 'Penempatan Awal' : (h.jenis_aksi === 'tinggal_kelas' ? 'Tinggal Kelas' : 'Lulus')) }}
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
                                                <div class="col-12" v-if="selectedSiswa.jenis_pendaftaran === 'Pindahan'">
                                                    <div class="p-3 border rounded bg-white">
                                                        <span class="text-muted d-block mb-2 fw-bold text-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Data Mutasi Masuk (Siswa Pindahan)</span>
                                                        <div class="row g-2 fs-9">
                                                            <div class="col-6">
                                                                <span class="text-muted d-block mb-0.5">Asal Sekolah Mutasi</span>
                                                                <span class="text-dark fw-bold">{{ selectedSiswa.sekolah_asal_mutasi || '-' }}</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <span class="text-muted d-block mb-0.5">Pindah dari Tingkat</span>
                                                                <span class="text-dark fw-bold">{{ selectedSiswa.pindah_dari_tingkat || '-' }}</span>
                                                            </div>
                                                            <div class="col-12 mt-2">
                                                                <span class="text-muted d-block mb-0.5">Surat Keterangan Pindah</span>
                                                                <span class="text-dark fw-bold">{{ selectedSiswa.pindah_no_surat || '-' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
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
                                                <div class="col-6" v-if="selectedSiswa.keluar_karena === 'Mutasi'">
                                                    <span class="text-muted d-block mb-0.5">Tingkat Ditinggalkan</span>
                                                    <span class="text-dark fw-bold">{{ selectedSiswa.tingkat_ditinggalkan || '-' }}</span>
                                                </div>
                                                <div class="col-6" v-if="selectedSiswa.keluar_karena === 'Mutasi'">
                                                    <span class="text-muted d-block mb-0.5">Diterima di Tingkat</span>
                                                    <span class="text-dark fw-bold">{{ selectedSiswa.diterima_di_tingkat || '-' }}</span>
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
                    <button type="button" class="btn btn-light rounded-3 fs-8 px-4 border" data-bs-dismiss="modal">Tutup</button>
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
                tanggalCetak: new Date().toISOString().split('T')[0],
                printModal: {
                    title: '',
                    url: '',
                    tanggalCetak: new Date().toISOString().split('T')[0],
                    showQrCode: true,
                    reGenerate: false
                },
                importState: 'select',
                validationSummary: { total_rows: 0, valid: 0, warning: 0, error: 0 },
                validationData: [],
                riwayatKepsek: [],
                formKepsek: { id: '', nama_kepsek: '', nip_kepsek: '', tanggal_mulai: '', tanggal_selesai: '', status_plt: 0 },
                modalKepsekTitle: 'Tambah Riwayat Kepsek',
                mainActiveTab: 'buku_induk_siswa',
                mainTabs: [
                    { id: 'buku_induk_siswa', name: 'Buku Induk Siswa', icon: 'bi bi-person-lines-fill' },
                    { id: 'seting_kurikulum', name: 'Seting Kurikulum', icon: 'bi bi-gear-wide-connected' },
                    { id: 'input_nilai_rapor', name: 'Input Nilai Rapor', icon: 'bi bi-journal-check' },
                    { id: 'cetak_buku_induk', name: 'Cetak Buku Induk', icon: 'bi bi-printer-fill' },
                    { id: 'riwayat_kepsek', name: 'Riwayat Kepsek', icon: 'bi bi-clock-history' },
                    { id: 'arsip_alumni', name: 'Arsip Alumni', icon: 'bi bi-safe2-fill' }
                ],
                // Alumni State
                alumniList: [],
                alumniSearch: '',
                alumniFilterAngkatan: '',
                alumniCurrentPage: 1,
                alumniTotalPages: 1,
                alumniTotal: 0,
                alumniLoading: false,
                selectedAlumniId: '',
                selectedAlumniName: '',
                alumniDocs: [],
                alumniDocsLoading: false,
                activeAlumniDocUrl: '',
                activeAlumniDocTitle: '',
                uploadingAlumniDoc: false,
                arsipJenisDokumen: 'Buku Induk',
                arsipKeterangan: '',
                capturedAlumniImages: [],
                capturedAlumniImageUrls: [],
                userRole: '<?php echo htmlspecialchars($user_role ?? ""); ?>',
                listTenants: <?php echo json_encode($tenantList ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                tempFilterTenantId: '',
                filterTenantId: '', 
                jenjangOptions: <?php echo json_encode($jenjangList ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                filterJenjang: '',
                kelasOptions: <?php echo json_encode($kelasList ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                filterKelas: '',
                filterStatus: '',
                filterTahunAjaranCetak: '',
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
                    jenjangId: '',
                    kelasId: '',
                    kurikulumId: '',
                    groups: []
                },
                kurikulumList: [],
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
                    jenjangId: '',
                    kelasId: '',
                    subjects: [],
                    students: [],
                    grades: {},
                    kurikulum: { tipe_penilaian: 'sederhana', nama_kurikulum: '' },
                    sikapK13: {},
                    isSaving: false
                },
                masterNilaiRapor: {
                    tahun_ajaran: [],
                    kelas: []
                },
                loadingNilaiRapor: false,
                importFile: null,
                importModalObj: null,
                activeEditSiswa: null,
                detailNilaiModalObj: null,
                detailLoading: false,
                selectedSiswa: null,
                activeDetailTab: 'identitas',
                detailModalObj: null,
                searchTimeout: null,
                
                // Kunci Akademik State
                isLockedKurikulum: 0,
                isLockedNilai: 0,
                isRombelLocked: false,
                bulkPrintSemester: '',

                // Cetak Matrix State
                matrixData: [],
                matrixMaxYears: 0,
                loadingMatrix: false,
                
                
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
            this.detailNilaiModalObj = new bootstrap.Modal(document.getElementById('modalInputDetailNilai'));
            
            // Bersihkan data sampah dari tenant lain jika Super Admin belum memilih sekolah
            if (this.userRole === 'super_admin' && !this.filterTenantId) {
                this.jenjangOptions = [];
                this.kelasOptions = [];
            }
            
            this.fetchData(1);
            this.fetchRiwayatKepsek();
        },
        computed: {
            paginationPages() {
                const current = this.currentPage;
                const total = this.totalPages;
                if (total <= 7) {
                    return Array.from({ length: total }, (_, i) => i + 1);
                }
                if (current <= 3) {
                    return [1, 2, 3, 4, '...', total - 1, total];
                }
                if (current >= total - 2) {
                    return [1, 2, '...', total - 3, total - 2, total - 1, total];
                }
                return [1, '...', current - 1, current, current + 1, '...', total];
            },
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
            },
            filteredKelasOptions() {
                if (!this.filterJenjang) {
                    return this.kelasOptions;
                }
                return this.kelasOptions.filter(k => k.id_jenjang == this.filterJenjang);
            },
            filteredKurikulumKelas() {
                if (!this.kurikulum.jenjangId) {
                    return this.masterKurikulum.kelas;
                }
                return this.masterKurikulum.kelas.filter(k => k.id_jenjang == this.kurikulum.jenjangId);
            },
            filteredNilaiRaporKelas() {
                if (!this.nilaiRapor.jenjangId) {
                    return this.masterNilaiRapor.kelas;
                }
                return this.masterNilaiRapor.kelas.filter(k => k.id_jenjang == this.nilaiRapor.jenjangId);
            }
        },
        watch: {
            filterJenjang(newVal) {
                this.filterKelas = '';
                this.fetchData(1);
                this.loadMatrixData();
            },
            'kurikulum.jenjangId'(newVal) {
                this.kurikulum.kelasId = '';
            },
            'nilaiRapor.jenjangId'(newVal) {
                this.nilaiRapor.kelasId = '';
            }
        },
        methods: {
            // --- ALUMNI ARCHIVE METHODS ---
            fetchAlumni(page = 1) {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.alumniList = [];
                    return;
                }
                this.alumniLoading = true;
                this.alumniCurrentPage = page;
                
                let params = {
                    page: page,
                    per_page: 10,
                    search: this.alumniSearch,
                    status: 'Lulus' // Only alumni
                };
                if (this.userRole === 'super_admin' && this.filterTenantId) {
                    params.filter_tenant_id = this.filterTenantId;
                }

                axios.get('/SINTA-SaaS/api/v1/buku-induk', { params })
                    .then(res => {
                        this.alumniList = res.data.data;
                        this.alumniTotalPages = res.data.last_page;
                        this.alumniTotal = res.data.total;
                        this.alumniLoading = false;
                    }).catch(err => {
                        this.alumniLoading = false;
                        this.toast.fire({ icon: 'error', title: 'Gagal memuat data alumni.' });
                    });
            },
            debounceAlumniSearch() {
                clearTimeout(this.alumniSearchTimeout);
                this.alumniSearchTimeout = setTimeout(() => {
                    this.fetchAlumni(1);
                }, 400);
            },
            selectAlumni(siswa) {
                this.selectedAlumniId = siswa.id;
                this.selectedAlumniName = siswa.nama_lengkap;
                this.clearCapture();
                this.fetchAlumniDocs();
            },
            fetchAlumniDocs() {
                if (!this.selectedAlumniId) return;
                this.alumniDocsLoading = true;
                axios.get('/SINTA-SaaS/api/v1/buku-induk/archive/list', { params: { siswa_id: this.selectedAlumniId } })
                    .then(res => {
                        this.alumniDocs = res.data;
                        this.alumniDocsLoading = false;
                    }).catch(err => {
                        this.alumniDocsLoading = false;
                        this.toast.fire({ icon: 'error', title: 'Gagal memuat berkas arsip.' });
                    });
            },
            deleteAlumniDoc(docId) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Dokumen arsip ini akan dihapus permanen dari server!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let fd = new FormData();
                        fd.append('id', docId);
                        axios.post('/SINTA-SaaS/api/v1/buku-induk/archive/delete', fd)
                            .then(res => {
                                if (res.data.success) {
                                    this.toast.fire({ icon: 'success', title: 'Dokumen berhasil dihapus.' });
                                    this.fetchAlumniDocs();
                                } else {
                                    this.toast.fire({ icon: 'error', title: res.data.error || 'Gagal menghapus dokumen.' });
                                }
                            }).catch(err => {
                                this.toast.fire({ icon: 'error', title: 'Gagal menghubungi server.' });
                            });
                    }
                });
            },
            viewAlumniDoc(docId, title) {
                this.activeAlumniDocTitle = title;
                this.activeAlumniDocUrl = '/SINTA-SaaS/api/v1/buku-induk/archive/view?id=' + docId;
                new bootstrap.Modal(document.getElementById('modalViewAlumniDoc')).show();
            },
            addCapturePage(event) {
                const files = event.target.files;
                if (!files || files.length === 0) return;
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    // Client-side image resizing and compression via Canvas
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => {
                            const canvas = document.createElement('canvas');
                            let width = img.width;
                            let height = img.height;
                            
                            // Max dimension limit (1600px)
                            const max_size = 1600;
                            if (width > max_size || height > max_size) {
                                if (width > height) {
                                    height = Math.round((height * max_size) / width);
                                    width = max_size;
                                } else {
                                    width = Math.round((width * max_size) / height);
                                    height = max_size;
                                }
                            }
                            
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);
                            
                            // Convert to light JPEG blob (quality 0.7)
                            canvas.toBlob((blob) => {
                                this.capturedAlumniImages.push(blob);
                                this.capturedAlumniImageUrls.push(URL.createObjectURL(blob));
                            }, 'image/jpeg', 0.7);
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
                // Reset input file value
                event.target.value = '';
            },
            removeCapturePage(idx) {
                URL.revokeObjectURL(this.capturedAlumniImageUrls[idx]);
                this.capturedAlumniImages.splice(idx, 1);
                this.capturedAlumniImageUrls.splice(idx, 1);
            },
            clearCapture() {
                this.capturedAlumniImageUrls.forEach(url => URL.revokeObjectURL(url));
                this.capturedAlumniImages = [];
                this.capturedAlumniImageUrls = [];
                this.arsipKeterangan = '';
                this.arsipJenisDokumen = 'Buku Induk';
            },
            handleAlumniDocUpload() {
                if (!this.selectedAlumniId) {
                    this.toast.fire({ icon: 'error', title: 'Pilih siswa alumni terlebih dahulu.' });
                    return;
                }
                
                const hasPdfFile = this.$refs.pdfFileInput && this.$refs.pdfFileInput.files && this.$refs.pdfFileInput.files.length > 0;
                const hasImages = this.capturedAlumniImages.length > 0;
                
                if (!hasPdfFile && !hasImages) {
                    this.toast.fire({ icon: 'error', title: 'Silakan ambil foto HP atau pilih file PDF terlebih dahulu.' });
                    return;
                }
                
                this.uploadingAlumniDoc = true;
                let fd = new FormData();
                fd.append('siswa_id', this.selectedAlumniId);
                fd.append('jenis_dokumen', this.arsipJenisDokumen);
                fd.append('keterangan', this.arsipKeterangan);
                
                if (hasPdfFile) {
                    fd.append('pdf_file', this.$refs.pdfFileInput.files[0]);
                } else {
                    this.capturedAlumniImages.forEach((blob, idx) => {
                        fd.append('images[]', blob, `page_${idx + 1}.jpg`);
                    });
                }
                
                axios.post('/SINTA-SaaS/api/v1/buku-induk/archive/upload', fd, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                }).then(res => {
                    this.uploadingAlumniDoc = false;
                    if (res.data.success) {
                        this.toast.fire({ icon: 'success', title: 'Berkas berhasil diarsipkan.' });
                        this.clearCapture();
                        if (this.$refs.pdfFileInput) this.$refs.pdfFileInput.value = '';
                        this.fetchAlumniDocs();
                    } else {
                        this.toast.fire({ icon: 'error', title: res.data.error || 'Gagal mengunggah berkas.' });
                    }
                }).catch(err => {
                    this.uploadingAlumniDoc = false;
                    this.toast.fire({ icon: 'error', title: 'Gagal menghubungi server.' });
                });
            },
            openPrintModal(url, title) {
                this.printModal.url = url;
                this.printModal.title = title;
                this.printModal.showQrCode = true;
                this.printModal.reGenerate = false;
                new bootstrap.Modal(document.getElementById('modalCetakDokumen')).show();
            },
            executePrint() {
                if (document.activeElement) document.activeElement.blur();
                let finalUrl = this.printModal.url 
                    + '&tanggal_cetak=' + this.printModal.tanggalCetak 
                    + '&show_qrcode=' + (this.printModal.showQrCode ? '1' : '0');
                if (this.printModal.reGenerate) {
                    finalUrl += '&re_generate=1';
                }
                window.open(finalUrl, '_blank');
                bootstrap.Modal.getInstance(document.getElementById('modalCetakDokumen')).hide();
            },
            async fetchRiwayatKepsek() {
                try {
                    let url = '/SINTA-SaaS/api/v1/riwayat-kepsek';
                    if (this.filterTenantId) {
                        url += '?filter_tenant_id=' + encodeURIComponent(this.filterTenantId);
                    }
                    const res = await fetch(url);
                    const json = await res.json();
                    if (json.success) {
                        this.riwayatKepsek = json.data;
                    } else {
                        this.riwayatKepsek = [];
                    }
                } catch (e) {
                    console.error(e);
                    this.riwayatKepsek = [];
                }
            },
            showTambahKepsek() {
                this.formKepsek = { id: '', nama_kepsek: '', nip_kepsek: '', tanggal_mulai: '', tanggal_selesai: '', status_plt: 0 };
                this.modalKepsekTitle = 'Tambah Riwayat Kepsek';
                new bootstrap.Modal(document.getElementById('modalRiwayatKepsek')).show();
            },
            editKepsek(item) {
                this.formKepsek = { ...item };
                this.modalKepsekTitle = 'Edit Riwayat Kepsek';
                new bootstrap.Modal(document.getElementById('modalRiwayatKepsek')).show();
            },
            async saveKepsek() {
                try {
                    const fd = new FormData();
                    for (let k in this.formKepsek) {
                        fd.append(k, this.formKepsek[k]);
                    }
                    if (this.filterTenantId) {
                        fd.append('filter_tenant_id', this.filterTenantId);
                    }
                    const res = await fetch('/SINTA-SaaS/api/v1/riwayat-kepsek', { method: 'POST', body: fd });
                    const json = await res.json();
                    if (json.success) {
                        this.toast.fire({ icon: 'success', title: 'Berhasil menyimpan riwayat' });
                        bootstrap.Modal.getInstance(document.getElementById('modalRiwayatKepsek')).hide();
                        this.fetchRiwayatKepsek();
                    } else {
                        Swal.fire('Gagal', json.error, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                }
            },
            async hapusKepsek(id) {
                const conf = await Swal.fire({
                    title: 'Hapus Riwayat?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus'
                });
                if (!conf.isConfirmed) return;
                try {
                    const bodyData = { id: id };
                    if (this.filterTenantId) {
                        bodyData.filter_tenant_id = this.filterTenantId;
                    }
                    const res = await fetch('/SINTA-SaaS/api/v1/riwayat-kepsek', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(bodyData)
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.toast.fire({ icon: 'success', title: 'Berhasil dihapus' });
                        this.fetchRiwayatKepsek();
                    } else {
                        Swal.fire('Gagal', json.error, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                }
            },
            async confirmDeleteGrades(student) {
                if (!student) return;
                const conf = await Swal.fire({
                    title: 'Hapus Nilai Siswa?',
                    html: `Apakah Anda yakin ingin menghapus seluruh rekaman nilai rapor <b>${student.nama_lengkap}</b> pada Kelas, Tahun Ajaran, dan Semester ini?<br><br>
                           <span class="text-danger fs-8">
                           <i class="bi bi-info-circle-fill me-1"></i>
                           Siswa hanya akan dihapus dari tabel ini jika ia tidak terdaftar secara resmi di kelas ini.
                           </span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus Nilainya',
                    cancelButtonText: 'Batal'
                });
                if (!conf.isConfirmed) return;

                Swal.fire({
                    title: 'Menghapus Nilai...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                try {
                    const fd = new FormData();
                    fd.append('siswa_id', student.id);
                    fd.append('kelas_id', this.nilaiRapor.kelasId);
                    fd.append('tahun_ajaran', this.nilaiRapor.tahunAjaran);
                    fd.append('semester', this.nilaiRapor.semester);
                    
                    if (this.filterTenantId) {
                        fd.append('tenant_id', this.filterTenantId);
                    }
                    const res = await fetch('/SINTA-SaaS/api/v1/nilai-rapor/delete-siswa', { method: 'POST', body: fd });
                    const json = await res.json();
                    
                    if (res.ok && json.success) {
                        Swal.fire('Berhasil', json.message, 'success');
                        this.loadNilaiRaporGrid();
                    } else {
                        Swal.fire('Gagal', json.error || 'Terjadi kesalahan saat menghapus.', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                }
            },
            async fetchLockStatus(type) {
                let tahun = '', semester = '';
                if (type === 'kurikulum') {
                    tahun = this.kurikulum.tahunAjaran;
                    semester = this.kurikulum.semester;
                } else if (type === 'nilai') {
                    tahun = this.nilaiRapor.tahunAjaran;
                    semester = this.nilaiRapor.semester;
                }
                
                if (!tahun || !semester) {
                    if (type === 'kurikulum') this.isLockedKurikulum = 0;
                    if (type === 'nilai') this.isLockedNilai = 0;
                    return;
                }
                
                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/kunci_akademik?tahun_ajaran=${encodeURIComponent(tahun)}&semester=${encodeURIComponent(semester)}&filter_tenant_id=${encodeURIComponent(this.filterTenantId)}`);
                    const data = await res.json();
                    if (type === 'kurikulum') {
                        this.isLockedKurikulum = data.is_locked_kurikulum || 0;
                    } else if (type === 'nilai') {
                        this.isLockedNilai = data.is_locked_nilai || 0;
                    }
                } catch (e) {
                    console.error('Failed to fetch lock status', e);
                }
            },
            async toggleLock(type) {
                let currentStatus = type === 'kurikulum' ? this.isLockedKurikulum : this.isLockedNilai;
                let newStatus = currentStatus ? 0 : 1;
                
                if (newStatus === 0) {
                    const confirm = await Swal.fire({
                        title: 'Buka Kunci?',
                        text: 'Pastikan Anda sudah melakukan koordinasi dengan kurikulum sebelum membuka kunci ini!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Buka Kunci',
                        cancelButtonText: 'Batal'
                    });
                    if (!confirm.isConfirmed) return;
                }

                let tahun = type === 'kurikulum' ? this.kurikulum.tahunAjaran : this.nilaiRapor.tahunAjaran;
                let semester = type === 'kurikulum' ? this.kurikulum.semester : this.nilaiRapor.semester;

                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/kunci_akademik/toggle`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            tahun_ajaran: tahun,
                            semester: semester,
                            type: type,
                            status: newStatus,
                            filter_tenant_id: this.filterTenantId
                        })
                    });
                    const data = await res.json();
                    if (res.ok) {
                        this.toast.fire({ icon: 'success', title: data.message });
                        if (type === 'kurikulum') this.isLockedKurikulum = newStatus;
                        else this.isLockedNilai = newStatus;
                    } else {
                        Swal.fire('Gagal', data.error || 'Terjadi kesalahan', 'error');
                    }
                } catch (e) {
                    Swal.fire('Gagal', 'Koneksi ke server terputus.', 'error');
                }
            },
            async loadMatrixData() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.matrixData = [];
                    return;
                }
                
                this.loadingMatrix = true;
                
                // Pastikan masterNilaiRapor.tahun_ajaran sudah dimuat agar dropdown Tahun Ajaran terisi
                if (!this.masterNilaiRapor || !this.masterNilaiRapor.tahun_ajaran || this.masterNilaiRapor.tahun_ajaran.length === 0) {
                    this.fetchNilaiRaporMaster(false); // fetch without replacing active tab state
                }

                try {
                    const res = await fetch(`/SINTA-SaaS/api/v1/buku_induk/matrix_cetak?kelas_id=${encodeURIComponent(this.filterKelas)}&status=${encodeURIComponent(this.filterStatus)}&filter_tenant_id=${encodeURIComponent(this.filterTenantId)}&tahun_ajaran=${encodeURIComponent(this.filterTahunAjaranCetak)}`);
                    const data = await res.json();
                    this.matrixMaxYears = data.max_years || 0;
                    this.matrixData = data.data || [];
                } catch (e) {
                    console.error('Failed to load matrix data', e);
                } finally {
                    this.loadingMatrix = false;
                }
            },
            switchMainTab(tabId) {
                this.mainActiveTab = tabId;
                if (tabId === 'seting_kurikulum') {
                    this.fetchKurikulumMaster();
                } else if (tabId === 'input_nilai_rapor') {
                    this.fetchNilaiRaporMaster();
                } else if (tabId === 'cetak_buku_induk') {
                    if (this.matrixData.length === 0) {
                        this.loadMatrixData();
                    }
                } else if (tabId === 'arsip_alumni') {
                    this.fetchAlumni(1);
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
                        this.jenjangOptions = res.data.jenjang || [];
                        
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
                
                this.fetchLockStatus('nilai');

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
                        this.nilaiRapor.kurikulum = res.data.kurikulum || { tipe_penilaian: 'sederhana', nama_kurikulum: 'Kurikulum Merdeka' };
                        this.nilaiRapor.sikapK13 = res.data.sikap_k13 || {};
                        this.isRombelLocked = res.data.is_rombel_locked || false;
                        const rawGrades = res.data.grades || {};
                        
                        const gradesObj = {};
                        this.nilaiRapor.students.forEach(student => {
                            gradesObj[student.id] = {};
                            
                            // Initialize K-13 attitude list for each student if not present
                            if (!this.nilaiRapor.sikapK13[student.id]) {
                                this.nilaiRapor.sikapK13[student.id] = {
                                    predikat_spiritual: '',
                                    deskripsi_spiritual: '',
                                    predikat_sosial: '',
                                    deskripsi_sosial: ''
                                };
                            }

                            this.nilaiRapor.subjects.forEach(subject => {
                                const rawEntry = (rawGrades[student.id] && rawGrades[student.id][subject.mapel_id])
                                    ? rawGrades[student.id][subject.mapel_id]
                                    : null;
                                
                                gradesObj[student.id][subject.mapel_id] = {
                                    nilai_akhir: rawEntry && rawEntry.nilai_akhir !== null ? rawEntry.nilai_akhir : '',
                                    kkm: rawEntry && rawEntry.kkm !== null ? rawEntry.kkm : '',
                                    detail: {
                                        kognitif: rawEntry && rawEntry.detail && rawEntry.detail.kognitif !== undefined ? rawEntry.detail.kognitif : '',
                                        psikomotorik: rawEntry && rawEntry.detail && rawEntry.detail.psikomotorik !== undefined ? rawEntry.detail.psikomotorik : '',
                                        afektif: rawEntry && rawEntry.detail && rawEntry.detail.afektif !== undefined ? rawEntry.detail.afektif : '',
                                        pengetahuan_nilai: rawEntry && rawEntry.detail && rawEntry.detail.pengetahuan_nilai !== undefined ? rawEntry.detail.pengetahuan_nilai : '',
                                        pengetahuan_predikat: rawEntry && rawEntry.detail && rawEntry.detail.pengetahuan_predikat !== undefined ? rawEntry.detail.pengetahuan_predikat : '',
                                        pengetahuan_deskripsi: rawEntry && rawEntry.detail && rawEntry.detail.pengetahuan_deskripsi !== undefined ? rawEntry.detail.pengetahuan_deskripsi : '',
                                        keterampilan_nilai: rawEntry && rawEntry.detail && rawEntry.detail.keterampilan_nilai !== undefined ? rawEntry.detail.keterampilan_nilai : '',
                                        keterampilan_predikat: rawEntry && rawEntry.detail && rawEntry.detail.keterampilan_predikat !== undefined ? rawEntry.detail.keterampilan_predikat : '',
                                        keterampilan_deskripsi: rawEntry && rawEntry.detail && rawEntry.detail.keterampilan_deskripsi !== undefined ? rawEntry.detail.keterampilan_deskripsi : '',
                                        deskripsi_tertinggi: rawEntry && rawEntry.detail && rawEntry.detail.deskripsi_tertinggi !== undefined ? rawEntry.detail.deskripsi_tertinggi : '',
                                        deskripsi_terendah: rawEntry && rawEntry.detail && rawEntry.detail.deskripsi_terendah !== undefined ? rawEntry.detail.deskripsi_terendah : ''
                                    }
                                };
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
                        const entry = this.nilaiRapor.grades[studentId][subjectId];
                        
                        let nilaiAkhir = entry.nilai_akhir;
                        let kkm = entry.kkm;
                        let detail = {};
                        
                        const tipe = this.nilaiRapor.kurikulum.tipe_penilaian;
                        if (tipe === 'klasik') {
                            detail = {
                                kognitif: entry.detail.kognitif,
                                psikomotorik: entry.detail.psikomotorik,
                                afektif: entry.detail.afektif
                            };
                            // Auto calculate average final grade if not manually set
                            if (nilaiAkhir === '' && entry.detail.kognitif !== '' && entry.detail.psikomotorik !== '') {
                                nilaiAkhir = (parseFloat(entry.detail.kognitif) + parseFloat(entry.detail.psikomotorik)) / 2;
                            }
                        } else if (tipe === 'kompleks') {
                            detail = {
                                pengetahuan_nilai: entry.detail.pengetahuan_nilai,
                                pengetahuan_predikat: entry.detail.pengetahuan_predikat,
                                pengetahuan_deskripsi: entry.detail.pengetahuan_deskripsi,
                                keterampilan_nilai: entry.detail.keterampilan_nilai,
                                keterampilan_predikat: entry.detail.keterampilan_predikat,
                                keterampilan_deskripsi: entry.detail.keterampilan_deskripsi
                            };
                            // Auto calculate average final grade if not manually set
                            if (nilaiAkhir === '' && entry.detail.pengetahuan_nilai !== '' && entry.detail.keterampilan_nilai !== '') {
                                nilaiAkhir = (parseFloat(entry.detail.pengetahuan_nilai) + parseFloat(entry.detail.keterampilan_nilai)) / 2;
                            }
                        } else {
                            // Sederhana (Merdeka)
                            detail = {
                                deskripsi_tertinggi: entry.detail.deskripsi_tertinggi,
                                deskripsi_terendah: entry.detail.deskripsi_terendah
                            };
                        }

                        gradesPayload.push({
                            siswa_id: studentId,
                            mapel_id: subjectId,
                            nilai_akhir: nilaiAkhir !== '' ? nilaiAkhir : null,
                            kkm: kkm !== '' ? kkm : null,
                            detail: detail
                        });
                    });
                });

                const payload = {
                    kelas_id: this.nilaiRapor.kelasId,
                    tahun_ajaran: this.nilaiRapor.tahunAjaran,
                    semester: this.nilaiRapor.semester,
                    grades: gradesPayload,
                    sikap_k13: this.nilaiRapor.kurikulum.tipe_penilaian === 'kompleks' ? this.nilaiRapor.sikapK13 : null
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
            openInputDetailNilaiModal(student) {
                this.activeEditSiswa = student;
                this.detailNilaiModalObj.show();
            },
            getAverageGrade(studentId) {
                if (!this.nilaiRapor.grades[studentId] || this.nilaiRapor.subjects.length === 0) return '-';
                let total = 0;
                let count = 0;
                this.nilaiRapor.subjects.forEach(subject => {
                    const entry = this.nilaiRapor.grades[studentId][subject.mapel_id];
                    let val = entry.nilai_akhir;
                    
                    // Auto calculate if empty
                    const tipe = this.nilaiRapor.kurikulum.tipe_penilaian;
                    if (tipe === 'klasik') {
                        if (val === '' && entry.detail.kognitif !== '' && entry.detail.psikomotorik !== '') {
                            val = (parseFloat(entry.detail.kognitif) + parseFloat(entry.detail.psikomotorik)) / 2;
                        }
                    } else if (tipe === 'kompleks') {
                        if (val === '' && entry.detail.pengetahuan_nilai !== '' && entry.detail.keterampilan_nilai !== '') {
                            val = (parseFloat(entry.detail.pengetahuan_nilai) + parseFloat(entry.detail.keterampilan_nilai)) / 2;
                        }
                    }
                    
                    if (val !== '' && val !== null) {
                        total += parseFloat(val);
                        count++;
                    }
                });
                return count > 0 ? (total / count).toFixed(2) : '-';
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
                this.importState = 'select';
                this.validationSummary = { total_rows: 0, valid: 0, warning: 0, error: 0 };
                this.validationData = [];
                this.importModalObj.show();
            },
            onImportFileChange(e) {
                this.importFile = e.target.files[0] || null;
            },
            validateImportGrades() {
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

                this.importState = 'validating';

                axios.post('/SINTA-SaaS/api/v1/nilai-rapor/import-validate', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(res => {
                    if (res.data.success) {
                        this.validationSummary = res.data.summary;
                        this.validationData = res.data.data;
                        this.importState = 'preview';
                    } else {
                        this.toast.fire({ icon: 'error', title: res.data.error || 'Gagal memvalidasi file Excel.' });
                        this.importState = 'select';
                    }
                })
                .catch(err => {
                    const msg = err.response && err.response.data && err.response.data.error 
                        ? err.response.data.error 
                        : 'Terjadi kesalahan saat memvalidasi.';
                    this.toast.fire({ icon: 'error', title: msg });
                    this.importState = 'select';
                });
            },
            resetImportState() {
                this.importState = 'select';
                this.validationSummary = { total_rows: 0, valid: 0, warning: 0, error: 0 };
                this.validationData = [];
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
            applyTenantFilter() {
                this.filterTenantId = this.tempFilterTenantId;
                this.onFilterTenantChange();
            },
            onFilterTenantChange() {
                // Ambil daftar kelas dan jenjang yang sesuai dengan tenant terpilih (untuk Super Admin)
                this.filterKelas = '';
                this.filterJenjang = '';
                this.fetchKelasOptions(this.filterTenantId || null);
                this.fetchJenjangOptions(this.filterTenantId || null);
                if (this.mainActiveTab === 'seting_kurikulum') {
                    this.fetchKurikulumMaster();
                } else if (this.mainActiveTab === 'input_nilai_rapor') {
                    this.fetchNilaiRaporMaster();
                } else if (this.mainActiveTab === 'riwayat_kepsek') {
                    this.fetchRiwayatKepsek();
                } else if (this.mainActiveTab === 'cetak_buku_induk') {
                    this.loadMatrixData();
                } else {
                    this.fetchData(1);
                }
            },
            fetchKurikulumMaster() {
                if (this.userRole === 'super_admin' && !this.filterTenantId) {
                    this.masterKurikulum.tahun_ajaran = [];
                    this.masterKurikulum.kelas = [];
                    this.masterKurikulum.bank_mapel = [];
                    this.kurikulumList = [];
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
                        this.jenjangOptions = res.data.jenjang || [];
                        this.kurikulumList = res.data.kurikulum_list || [];
                        
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
                    this.kurikulum.kurikulumId = '';
                    return;
                }
                if (!this.kurikulum.kelasId || !this.kurikulum.tahunAjaran || !this.kurikulum.semester) {
                    this.kurikulum.groups = [];
                    this.kurikulum.kurikulumId = '';
                    return;
                }
                
                this.fetchLockStatus('kurikulum');
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
                        this.kurikulum.kurikulumId = res.data.active_kurikulum_id || '';
                        this.kurikulumList = res.data.kurikulum_list || [];
                        
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
            isKelas12(className) {
                if (!className) return false;
                const normalized = className.toString().trim().toUpperCase();
                return normalized.startsWith('12') || normalized.startsWith('XII');
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
                    kurikulum_id: this.kurikulum.kurikulumId,
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
            fetchJenjangOptions(tenantId = null) {
                const params = { module: 'jenjang' };
                if (tenantId) {
                    params.tenant_id = tenantId;
                }
                axios.get('/SINTA-SaaS/api/v1/kelembagaan/options', { params })
                     .then(res => {
                         this.jenjangOptions = res.data.data;
                     })
                     .catch(err => {
                         console.error("Gagal memuat filter jenjang:", err);
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
            exportPdssExcel() {
                if (!this.filterKelas) return;
                window.open(`/SINTA-SaaS/buku-induk/export-pdss-snbp?kelas_id=${this.filterKelas}`, '_blank');
            },
            toggleRombelLock() {
                const targetLock = this.isRombelLocked ? 0 : 1;
                const statusText = targetLock ? 'mengunci' : 'membuka kunci';
                
                Swal.fire({
                    title: `Apakah Anda yakin?`,
                    text: `Anda akan ${statusText} penginputan nilai untuk rombel ini pada tahun ajaran terkait.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Lanjutkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('/SINTA-SaaS/buku-induk/toggle-lock-kelas', {
                            kelas_id: this.nilaiRapor.kelasId,
                            tahun_ajaran: this.nilaiRapor.tahunAjaran,
                            lock: targetLock
                        }).then(res => {
                            if (res.data.success) {
                                this.isRombelLocked = targetLock === 1;
                                Swal.fire('Berhasil!', res.data.message, 'success');
                            } else {
                                Swal.fire('Gagal!', res.data.error || 'Terjadi kesalahan.', 'error');
                            }
                        }).catch(err => {
                            Swal.fire('Gagal!', 'Terjadi kesalahan koneksi server.', 'error');
                        });
                    }
                });
            },
            printBulkRapor() {
                if (!this.filterKelas || !this.bulkPrintSemester) return;
                const ta = this.filterTahunAjaranCetak || '2025/2026';
                window.open(`/SINTA-SaaS/buku-induk/print-rapot-semester-bulk?kelas_id=${this.filterKelas}&semester=${this.bulkPrintSemester}&ta=${encodeURIComponent(ta)}`, '_blank');
            },
            printBulkIdentitas() {
                if (!this.filterKelas) return;
                window.open(`/SINTA-SaaS/buku-induk/print-rapot-kelas?kelas_id=${this.filterKelas}`, '_blank');
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

    // Fix for WAI-ARIA accessibility error: Blocked aria-hidden on an element because its descendant retained focus
    document.addEventListener('hide.bs.modal', function() {
        if (document.activeElement) {
            document.activeElement.blur();
        }
    });
}
</script>
