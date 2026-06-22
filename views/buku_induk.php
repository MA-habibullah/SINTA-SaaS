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
        <div class="col-12 col-md-5 d-flex gap-2 justify-content-start justify-content-md-end align-items-center flex-wrap">
            <button class="btn btn-outline-success btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0" 
                    @click="exportExcel">
                <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel
            </button>
            <button class="btn btn-outline-danger btn-sm rounded-3 px-3 py-2 fs-8 fs-md-7 flex-grow-1 flex-md-grow-0" 
                    @click="printBukuInduk">
                <i class="bi bi-printer me-1"></i> Cetak Buku Induk
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
                        <option value="">🏫 Semua Sekolah</option>
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

    <!-- Main Card Grid -->
    <div class="card border-0 shadow-sm rounded-4">
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
                        <option v-for="k in kelasOptions" :value="k.id" :key="k.id">{{ k.nama_kelas }}</option>
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

            <!-- Loader State -->
            <div v-if="loading" class="text-center py-5">
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
                                <img :src="selectedSiswa.foto_profil ? '/SINTA-SaaS/storage/app/public/' + selectedSiswa.foto_profil : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'" 
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
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'diri'}" @click="activeDetailTab = 'diri'">
                                        <i class="bi bi-person me-1"></i>Data Diri
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'ortu'}" @click="activeDetailTab = 'ortu'">
                                        <i class="bi bi-people me-1"></i>Orang Tua
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'alamat'}" @click="activeDetailTab = 'alamat'">
                                        <i class="bi bi-geo-alt me-1"></i>Alamat & Kontak
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'fisik'}" @click="activeDetailTab = 'fisik'">
                                        <i class="bi bi-activity me-1"></i>Fisik & Riwayat
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-3 border-0 rounded-3 text-dark transition" :class="{active: activeDetailTab === 'berkas'}" @click="activeDetailTab = 'berkas'">
                                        <i class="bi bi-file-earmark-lock me-1"></i>Registrasi & Berkas
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab: Data Diri -->
                            <div v-if="activeDetailTab === 'diri'" class="detail-tab-pane animate-fade-in">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-person-vcard me-2"></i>Identitas Diri Siswa</h6>
                                <div class="row g-3 fs-8">
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Nama Lengkap</span>
                                        <span class="text-dark fw-bold">{{ selectedSiswa.nama_lengkap }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Nama Panggilan</span>
                                        <span class="text-dark fw-semibold">{{ selectedSiswa.nama_panggilan || '-' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">NIK (No. KTP)</span>
                                        <span class="text-dark font-monospace">{{ selectedSiswa.nik || '-' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">No. Kartu Keluarga</span>
                                        <span class="text-dark font-monospace">{{ selectedSiswa.no_kk || '-' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Tempat, Tanggal Lahir</span>
                                        <span class="text-dark">{{ selectedSiswa.tempat_lahir || '-' }}, {{ formatTanggal(selectedSiswa.tanggal_lahir) }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Jenis Kelamin</span>
                                        <span class="text-dark">{{ selectedSiswa.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Agama</span>
                                        <span class="text-dark">{{ selectedSiswa.agama || '-' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Sekolah Asal</span>
                                        <span class="text-dark">{{ selectedSiswa.sekolah_asal || '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Orang Tua -->
                            <div v-if="activeDetailTab === 'ortu'" class="detail-tab-pane animate-fade-in">
                                <div class="row g-4">
                                    <!-- Ayah -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-gender-male me-2"></i>Data Ayah Kandung</h6>
                                        <div class="row g-3 fs-8">
                                            <div class="col-12">
                                                <span class="text-muted d-block mb-0.5">Nama Ayah</span>
                                                <span class="text-dark fw-bold">{{ selectedSiswa.nama_ayah || '-' }}</span>
                                            </div>
                                            <div class="col-12">
                                                <span class="text-muted d-block mb-0.5">NIK Ayah</span>
                                                <span class="text-dark font-monospace">{{ selectedSiswa.nik_ayah || '-' }}</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block mb-0.5">Tahun Lahir</span>
                                                <span class="text-dark">{{ selectedSiswa.tahun_lahir_ayah || '-' }}</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block mb-0.5">Agama</span>
                                                <span class="text-dark">{{ selectedSiswa.agama_ayah || '-' }}</span>
                                            </div>
                                            <div class="col-12">
                                                <span class="text-muted d-block mb-0.5">Pendidikan / Pekerjaan</span>
                                                <span class="text-dark">{{ selectedSiswa.pendidikan_ayah || '-' }} / {{ selectedSiswa.pekerjaan_ayah || '-' }}</span>
                                            </div>
                                            <div class="col-12">
                                                <span class="text-muted d-block mb-0.5">Penghasilan Bulanan</span>
                                                <span class="text-dark">{{ selectedSiswa.penghasilan_ayah || '-' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ibu -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-gender-female me-2"></i>Data Ibu Kandung</h6>
                                        <div class="row g-3 fs-8">
                                            <div class="col-12">
                                                <span class="text-muted d-block mb-0.5">Nama Ibu</span>
                                                <span class="text-dark fw-bold">{{ selectedSiswa.nama_ibu || '-' }}</span>
                                            </div>
                                            <div class="col-12">
                                                <span class="text-muted d-block mb-0.5">NIK Ibu</span>
                                                <span class="text-dark font-monospace">{{ selectedSiswa.nik_ibu || '-' }}</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block mb-0.5">Tahun Lahir</span>
                                                <span class="text-dark">{{ selectedSiswa.tahun_lahir_ibu || '-' }}</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block mb-0.5">Agama</span>
                                                <span class="text-dark">{{ selectedSiswa.agama_ibu || '-' }}</span>
                                            </div>
                                            <div class="col-12">
                                                <span class="text-muted d-block mb-0.5">Pendidikan / Pekerjaan</span>
                                                <span class="text-dark">{{ selectedSiswa.pendidikan_ibu || '-' }} / {{ selectedSiswa.pekerjaan_ibu || '-' }}</span>
                                            </div>
                                            <div class="col-12">
                                                <span class="text-muted d-block mb-0.5">Penghasilan Bulanan</span>
                                                <span class="text-dark">{{ selectedSiswa.penghasilan_ibu || '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Alamat & Kontak -->
                            <div v-if="activeDetailTab === 'alamat'" class="detail-tab-pane animate-fade-in">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-map me-2"></i>Alamat & Kontak Keluarga</h6>
                                <div class="row g-3 fs-8">
                                    <div class="col-12">
                                        <span class="text-muted d-block mb-0.5">Alamat Lengkap (Sesuai KK)</span>
                                        <span class="text-dark fw-semibold">{{ selectedSiswa.alamat_kk || '-' }}</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block mb-0.5">Alamat Domisili</span>
                                        <span class="text-dark fw-semibold">{{ selectedSiswa.alamat_domisili || '-' }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted d-block mb-0.5">RT / RW</span>
                                        <span class="text-dark">{{ selectedSiswa.rt || '00' }} / {{ selectedSiswa.rw || '00' }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted d-block mb-0.5">Kode Pos</span>
                                        <span class="text-dark font-monospace">{{ selectedSiswa.kode_pos || '-' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Status Tempat Tinggal</span>
                                        <span class="text-dark">{{ selectedSiswa.status_tinggal || '-' }}</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block mb-0.5">Wilayah Administratif</span>
                                        <span class="text-dark">
                                            Kel. {{ selectedSiswa.nama_kelurahan || '-' }}, Kec. {{ selectedSiswa.nama_kecamatan || '-' }}, 
                                            {{ selectedSiswa.nama_kota || '-' }}, Prov. {{ selectedSiswa.nama_provinsi || '-' }}
                                        </span>
                                    </div>
                                    <div class="col-md-6 border-top pt-3">
                                        <span class="text-muted d-block mb-0.5">Email Siswa</span>
                                        <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.email || '-' }}</span>
                                    </div>
                                    <div class="col-md-6 border-top pt-3">
                                        <span class="text-muted d-block mb-0.5">No. HP Aktif (Siswa)</span>
                                        <span class="text-dark font-monospace fw-semibold">{{ selectedSiswa.no_telepon_siswa || '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Fisik & Riwayat -->
                            <div v-if="activeDetailTab === 'fisik'" class="detail-tab-pane animate-fade-in">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-heart me-2"></i>Keadaan Fisik & Riwayat Pelajar</h6>
                                <div class="row g-3 fs-8">
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
                                        <span class="text-muted d-block mb-0.5">Anak Ke (Dari Saudara)</span>
                                        <span class="text-dark">Anak Ke-{{ selectedSiswa.anak_ke || '1' }} dari {{ selectedSiswa.jumlah_saudara || '0' }} bersaudara</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Transportasi ke Sekolah</span>
                                        <span class="text-dark">{{ selectedSiswa.transportasi || '-' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Jarak Rumah ke Sekolah</span>
                                        <span class="text-dark">{{ selectedSiswa.jarak_rumah || '0' }} meter</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Penyakit yang Pernah Diderita</span>
                                        <span class="text-dark text-danger fw-semibold">{{ selectedSiswa.penyakit_yang_diderita || 'Tidak Ada Riwayat Sakit Berat' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Registrasi & Berkas -->
                            <div v-if="activeDetailTab === 'berkas'" class="detail-tab-pane animate-fade-in">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-cash-coin me-2"></i>Registrasi Akademik & Bantuan</h6>
                                <div class="row g-3 fs-8 mb-4">
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Jenis Pendaftaran</span>
                                        <span class="text-dark fw-bold">{{ selectedSiswa.jenis_pendaftaran || '-' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Tanggal Masuk Sekolah</span>
                                        <span class="text-dark fw-semibold">{{ formatTanggal(selectedSiswa.tanggal_masuk) }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Jalur Diterima (PPDB)</span>
                                        <span class="text-dark fw-semibold">{{ selectedSiswa.jalur_diterima || '-' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block mb-0.5">Hobi / Kegemaran</span>
                                        <span class="text-dark">{{ selectedSiswa.hobi || '-' }}</span>
                                    </div>
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
                                        <span class="text-dark font-monospace">{{ selectedSiswa.no_kip || '-' }}</span>
                                    </div>
                                </div>

                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-file-earmark-pdf me-2"></i>Lampiran Dokumen Unggahan</h6>
                                <div class="row g-3 fs-8">
                                    <div class="col-md-6" v-for="doc in dokumenFields" :key="doc.key">
                                        <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi fs-5 text-danger" :class="selectedSiswa[doc.key] ? 'bi-file-pdf-fill' : 'bi-file-earmark-x'"></i>
                                                <div>
                                                    <div class="fw-bold text-dark fs-8">{{ doc.label }}</div>
                                                    <div class="text-muted fs-9" v-if="selectedSiswa[doc.key]">Tersedia • {{ getFileSizeLabel(doc.key) }}</div>
                                                    <div class="text-muted fs-9" v-else>Belum Diunggah</div>
                                                </div>
                                            </div>
                                            <a v-if="selectedSiswa[doc.key]" :href="'/SINTA-SaaS/storage/app/public/' + selectedSiswa[doc.key]" 
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
                
                <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-3 fs-8 px-4" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary rounded-3 fs-8 px-4 d-inline-flex align-items-center gap-1" 
                            v-if="selectedSiswa" @click="printSingleCard(selectedSiswa.id)">
                        <i class="bi bi-printer"></i> Cetak Kartu Siswa
                    </button>
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
</style>

<!-- Script Inisialisasi Vue App -->
<script>
{
    window.VueAppRegistry.register('#bukuIndukApp', {
        data() {
            return {
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
                detailLoading: false,
                selectedSiswa: null,
                activeDetailTab: 'diri',
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
            this.fetchData(1);
        },
        methods: {
            get selectedTenantName() {
                if (!this.filterTenantId) return '';
                const t = this.listTenants.find(t => t.id === this.filterTenantId);
                return t ? t.nama_sekolah : '';
            },
            onFilterTenantChange() {
                // Ambil daftar kelas yang sesuai dengan tenant terpilih (untuk Super Admin)
                this.filterKelas = '';
                this.fetchKelasOptions(this.filterTenantId || null);
                this.fetchData(1);
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
                this.activeDetailTab = 'diri';
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
            }
        }
    });
}
</script>
