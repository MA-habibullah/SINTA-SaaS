
<div id="keuangan-master-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-wallet2 text-blue-600 me-2"></i> Master Data & Konfigurasi Keuangan
            </h2>
            <p class="text-muted mb-0">Kelola komponen biaya, tarif acuan, beasiswa, penerbitan tagihan, dan pengaturan terminologi keuangan.</p>
        </div>
    </div>

    <!-- Tenant Selector Card (Super Admin Only) -->
    <div v-if="isSuperAdmin" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="row align-items-center">
            <div class="col-md-6">
                <label class="form-label fw-bold text-slate-700"><i class="bi bi-building-gear text-blue-600 me-2"></i> Pilih Sekolah (Tenant)</label>
                <select class="form-select border-slate-200" v-model="selectedTenantId" @change="onTenantChange" style="height: 44px;">
                    <option value="">-- Semua Sekolah (Global) --</option>
                    <option v-for="t in tenantsList" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                </select>
            </div>
            <div class="col-md-6 mt-3 mt-md-0 text-md-end text-muted fs-7">
                Sebagai <strong>Super Admin</strong>, Anda dapat melihat dan mengelola seluruh konfigurasi keuangan untuk lembaga sekolah target.
            </div>
        </div>
    </div>

    <!-- Global Alert Feedback -->
    <div v-if="successMsg" class="alert alert-success border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm">
        <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
        <div class="fw-semibold text-success-800">{{ successMsg }}</div>
    </div>
    <div v-if="errorMsg" class="alert alert-danger border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
        <div class="fw-semibold text-danger-800">{{ errorMsg }}</div>
    </div>

    <!-- Sleek Underline Navigation Tabs (Like Buku Induk) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2" id="masterTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition active" id="komponen-tab" data-bs-toggle="tab" data-bs-target="#komponen-pane" type="button" role="tab" aria-controls="komponen-pane" aria-selected="true">
                            <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>1. Komponen Biaya
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="tarif-tab" data-bs-toggle="tab" data-bs-target="#tarif-pane" type="button" role="tab" aria-controls="tarif-pane" aria-selected="false">
                            <i class="bi bi-cash-coin me-2 text-success"></i>2. Tarif Acuan Default
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="keringanan-tab" data-bs-toggle="tab" data-bs-target="#keringanan-pane" type="button" role="tab" aria-controls="keringanan-pane" aria-selected="false" @click="fetchKeringanan">
                            <i class="bi bi-award me-2 text-warning"></i>3. Keringanan & Beasiswa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate-pane" type="button" role="tab" aria-controls="generate-pane" aria-selected="false">
                            <i class="bi bi-magic me-2 text-info"></i>4. Terbit Tagihan (Generate)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="tagihan-tab" data-bs-toggle="tab" data-bs-target="#tagihan-pane" type="button" role="tab" aria-controls="tagihan-pane" aria-selected="false" @click="fetchDaftarTagihan">
                            <i class="bi bi-file-earmark-text me-2 text-secondary"></i>5. Daftar Tagihan Siswa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="pengaturan-tab" data-bs-toggle="tab" data-bs-target="#pengaturan-pane" type="button" role="tab" aria-controls="pengaturan-pane" aria-selected="false" @click="fetchSettings">
                            <i class="bi bi-gear me-2 text-danger"></i>6. Pengaturan Keuangan
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tabs Content -->
    <div class="tab-content" id="masterTabsContent">
        
        <!-- Tab 1: Komponen Biaya -->
        <div class="tab-pane fade show active" id="komponen-pane" role="tabpanel">
            <div class="row">
                <!-- Form Komponen (Left: 4-cols) -->
                <div class="col-12 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">
                            {{ formKomp.id ? 'Edit Komponen' : 'Tambah Komponen Baru' }}
                        </h5>
                        <form @submit.prevent="saveKomponen" class="d-flex flex-column gap-3">
                            <div v-if="isSuperAdmin && !formKomp.id">
                                <label class="form-label fw-semibold text-slate-700">Pilih Sekolah <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formKomp.tenant_id" :required="!formKomp.id" style="height: 42px;">
                                    <option value="" disabled>-- Pilih Sekolah --</option>
                                    <option v-for="t in tenantsList" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Nama Komponen <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-slate-200" v-model="formKomp.nama_komponen" required placeholder="Contoh: SPP Juli" style="height: 42px;">
                            </div>
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Tipe Periode</label>
                                <select class="form-select border-slate-200" v-model="formKomp.tipe_periode" style="height: 42px;">
                                    <option value="Bulanan">Bulanan</option>
                                    <option value="Semester">Semester</option>
                                    <option value="Tahunan">Tahunan</option>
                                    <option value="Bebas">Bebas (Insidental / Non-Periodik)</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" v-if="formKomp.id" @click="resetFormKomp" class="btn btn-outline-secondary fw-semibold py-2.5 flex-fill">Batal</button>
                                <button type="submit" class="btn btn-primary fw-bold py-2.5 flex-fill" :disabled="loadingKomp">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Komponen (Right: 8-cols) -->
                <div class="col-12 col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Daftar Komponen Biaya</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th v-if="isSuperAdmin">Sekolah</th>
                                        <th>Nama Komponen</th>
                                        <th>Tipe Periode</th>
                                        <th class="text-center" style="width: 150px;">Status Aktif</th>
                                        <th class="text-center" style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="komp in paginatedKomponen" :key="komp.id">
                                        <td v-if="isSuperAdmin" class="text-muted fs-8">{{ komp.nama_sekolah || 'Global' }}</td>
                                        <td class="fw-bold text-slate-800">
                                            {{ komp.nama_komponen }}
                                            <span v-if="komp.is_active == 0" class="badge bg-secondary ms-2">Non-Aktif</span>
                                        </td>
                                        <td>
                                            <span class="badge rounded px-3 py-2" :class="getPeriodeBadgeClass(komp.tipe_periode)">
                                                {{ komp.tipe_periode }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" role="switch" :checked="komp.is_active == 1" @change="toggleKompStatus(komp)" style="cursor: pointer; width: 44px; height: 22px;">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button @click="editKomponen(komp)" class="btn btn-link text-primary p-0 me-3 shadow-none" title="Edit">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                            <button @click="deleteKomponen(komp.id)" class="btn btn-link text-danger p-0 shadow-none" title="Hapus">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredKomponen.length === 0">
                                        <td :colspan="isSuperAdmin ? 5 : 4" class="text-center py-4 text-muted">Belum ada komponen biaya terdaftar.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Komponen -->
                        <div class="d-flex justify-content-between align-items-center mt-3" v-if="totalKompPages > 1">
                            <span class="text-muted fs-8">Menampilkan Halaman {{ kompPage }} dari {{ totalKompPages }}</span>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm justify-content-end mb-0">
                                    <li class="page-item" :class="{ disabled: kompPage === 1 }">
                                        <a class="page-link" href="#" @click.prevent="kompPage--">Sebelumnya</a>
                                    </li>
                                    <li class="page-item" v-for="p in visibleKompPages" :key="p" :class="{ active: kompPage === p, disabled: p === '...' }">
                                        <span v-if="p === '...'" class="page-link">...</span>
                                        <a v-else class="page-link" href="#" @click.prevent="kompPage = p">{{ p }}</a>
                                    </li>
                                    <li class="page-item" :class="{ disabled: kompPage === totalKompPages }">
                                        <a class="page-link" href="#" @click.prevent="kompPage++">Berikutnya</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Tarif Acuan Default -->
        <div class="tab-pane fade" id="tarif-pane" role="tabpanel">
            <div class="row">
                <!-- Form Tarif (Left: 4-cols) -->
                <div class="col-12 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Tautkan Tarif Baru</h5>
                        <form @submit.prevent="saveTarif" class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Komponen Biaya <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formTarif.komponen_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Komponen --</option>
                                    <option v-for="k in komponenList" :key="k.id" :value="k.id" :disabled="k.is_active == 0">
                                        {{ k.nama_komponen }} ({{ k.nama_sekolah || 'Global' }}) {{ k.is_active == 0 ? '(Non-Aktif)' : '' }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formTarif.tahun_ajaran_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Tahun Ajaran --</option>
                                    <option v-for="ta in listTa" :key="ta.id" :value="ta.id">
                                        {{ ta.tahun_ajaran }} {{ ta.status === 'Aktif' ? '(Aktif)' : '' }}
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Filter Target -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Target Penerapan Tarif</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="targetRadio" id="radioGeneral" value="general" v-model="tarifTargetType" @change="resetTarifTargets">
                                    <label class="form-check-label text-slate-700 fw-medium" for="radioGeneral">Seluruh Siswa (General)</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="targetRadio" id="radioKelas" value="kelas" v-model="tarifTargetType" @change="resetTarifTargets">
                                    <label class="form-check-label text-slate-700 fw-medium" for="radioKelas">Spesifik per Kelas</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="targetRadio" id="radioJenjang" value="jenjang" v-model="tarifTargetType" @change="resetTarifTargets">
                                    <label class="form-check-label text-slate-700 fw-medium" for="radioJenjang">Spesifik per Jenjang</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="targetRadio" id="radioJalur" value="jalur" v-model="tarifTargetType" @change="resetTarifTargets">
                                    <label class="form-check-label text-slate-700 fw-medium" for="radioJalur">Spesifik per Jalur Masuk</label>
                                </div>
                            </div>

                            <!-- Dropdown dinamis target -->
                            <div v-if="tarifTargetType === 'kelas'">
                                <label class="form-label fw-semibold text-slate-700">Pilih Kelas <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formTarif.kelas_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Kelas --</option>
                                    <option v-for="c in listKelas" :value="c.id">{{ c.nama_kelas }}</option>
                                </select>
                            </div>

                            <div v-if="tarifTargetType === 'jenjang'">
                                <label class="form-label fw-semibold text-slate-700">Pilih Jenjang <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formTarif.jenjang_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Jenjang --</option>
                                    <option v-for="j in listJenjang" :value="j.id">{{ j.nama_jenjang }}</option>
                                </select>
                            </div>

                            <div v-if="tarifTargetType === 'jalur'">
                                <label class="form-label fw-semibold text-slate-700">Jalur Masuk PPDB <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-slate-200" v-model="formTarif.jalur_masuk" placeholder="Contoh: Prestasi, KIP, Reguler" required style="height: 42px;">
                            </div>

                            <div>
                                <label class="form-label fw-semibold text-slate-700">Nominal Tarif (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-slate-200 fw-bold">Rp</span>
                                    <input type="number" class="form-control border-slate-200" v-model="formTarif.nominal" placeholder="0" required style="height: 42px;">
                                </div>
                            </div>

                            <div class="pt-3">
                                <button type="submit" class="btn btn-primary fw-bold w-100 py-2.5" :disabled="loadingTarif">Simpan Tarif</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Tarif (Right: 8-cols) -->
                <div class="col-12 col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Daftar Tarif Default</h5>
                        
                        <!-- Filter Tarif Default -->
                        <div class="row g-2 mb-3 align-items-center">
                            <div v-if="isSuperAdmin" class="col-12 col-md-4">
                                <label class="form-label fw-semibold text-slate-600 fs-8 mb-1">Sekolah</label>
                                <select class="form-select form-select-sm border-slate-200" v-model="filterTarifTenant" style="height: 36px;">
                                    <option value="">-- Semua Sekolah --</option>
                                    <option v-for="t in tenantsList" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>
                            <div class="col-12" :class="isSuperAdmin ? 'col-md-4' : 'col-md-6'">
                                <label class="form-label fw-semibold text-slate-600 fs-8 mb-1">Tahun Ajaran</label>
                                <select class="form-select form-select-sm border-slate-200" v-model="filterTarifTa" style="height: 36px;">
                                    <option value="">-- Semua Tahun Ajaran --</option>
                                    <option v-for="ta in listTa" :key="ta.id" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                                </select>
                            </div>
                            <div class="col-12" :class="isSuperAdmin ? 'col-md-4' : 'col-md-6'">
                                <label class="form-label fw-semibold text-slate-600 fs-8 mb-1">Komponen</label>
                                <select class="form-select form-select-sm border-slate-200" v-model="filterTarifKomp" style="height: 36px;">
                                    <option value="">-- Semua Komponen --</option>
                                    <option v-for="name in uniqueKomponenNames" :key="name" :value="name">{{ name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th v-if="isSuperAdmin">Sekolah</th>
                                        <th>Komponen</th>
                                        <th>Target</th>
                                        <th>Nominal</th>
                                        <th>Tahun Ajaran</th>
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="t in paginatedTarif" :key="t.id">
                                        <td v-if="isSuperAdmin" class="text-muted fs-8">{{ t.nama_sekolah || 'Global' }}</td>
                                        <td class="fw-bold text-slate-800">{{ t.nama_komponen }}</td>
                                        <td>
                                            <span v-if="t.nama_kelas" class="badge bg-blue-100 text-blue-700 px-3 py-2">Kelas {{ t.nama_kelas }}</span>
                                            <span v-else-if="t.nama_jenjang" class="badge bg-purple-100 text-purple-700 px-3 py-2">Jenjang {{ t.nama_jenjang }}</span>
                                            <span v-else-if="t.jalur_masuk" class="badge bg-teal-100 text-teal-700 px-3 py-2">Jalur {{ t.jalur_masuk }}</span>
                                            <span v-else class="badge bg-slate-100 text-slate-700 px-3 py-2">Semua Siswa</span>
                                        </td>
                                        <td class="fw-semibold text-slate-700">Rp {{ formatNumber(t.nominal) }}</td>
                                        <td>{{ t.tahun_ajaran }}</td>
                                        <td class="text-center">
                                            <button @click="deleteTarif(t.id)" class="btn btn-link text-danger p-0 shadow-none" title="Hapus">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredTarif.length === 0">
                                        <td :colspan="isSuperAdmin ? 6 : 5" class="text-center py-4 text-muted">Belum ada tarif default dikonfigurasi.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Tarif -->
                        <div class="d-flex justify-content-between align-items-center mt-3" v-if="totalTarifPages > 1">
                            <span class="text-muted fs-8">Menampilkan Halaman {{ tarifPage }} dari {{ totalTarifPages }}</span>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm justify-content-end mb-0">
                                    <li class="page-item" :class="{ disabled: tarifPage === 1 }">
                                        <a class="page-link" href="#" @click.prevent="tarifPage--">Sebelumnya</a>
                                    </li>
                                    <li class="page-item" v-for="p in visibleTarifPages" :key="p" :class="{ active: tarifPage === p, disabled: p === '...' }">
                                        <span v-if="p === '...'" class="page-link">...</span>
                                        <a v-else class="page-link" href="#" @click.prevent="tarifPage = p">{{ p }}</a>
                                    </li>
                                    <li class="page-item" :class="{ disabled: tarifPage === totalTarifPages }">
                                        <a class="page-link" href="#" @click.prevent="tarifPage++">Berikutnya</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Keringanan dan Beasiswa -->
        <div class="tab-pane fade" id="keringanan-pane" role="tabpanel">
            <div class="row">
                <!-- Form Keringanan -->
                <div class="col-12 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Konfigurasi Keringanan Baru</h5>
                        
                        <form @submit.prevent="saveKeringanan" class="d-flex flex-column gap-3">
                            <!-- Cari Siswa Autocomplete -->
                            <div class="position-relative">
                                <label class="form-label fw-semibold text-slate-700">Cari Siswa <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-slate-200"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" class="form-control border-slate-200" v-model="siswaSearch" @input="searchSiswa" placeholder="Ketik nama/NISN siswa..." style="height: 42px;">
                                </div>
                                <ul class="dropdown-menu show w-100 shadow border-slate-200 p-0 overflow-hidden" v-if="siswaSuggestions.length > 0" style="display: block; max-height: 200px; overflow-y: auto; z-index: 1010;">
                                    <li v-for="s in siswaSuggestions" :key="s.id">
                                        <a href="#" class="dropdown-item py-2.5 px-3 d-flex justify-content-between align-items-center" @click.prevent="selectSiswa(s)">
                                            <div>
                                                <div class="fw-bold text-slate-800 fs-7">{{ s.nama }}</div>
                                                <small class="text-muted">NISN: {{ s.nisn }} | Kelas: {{ s.nama_kelas }}</small>
                                            </div>
                                            <i class="bi bi-plus-circle text-blue-600 fs-5"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Selected Siswa Box -->
                            <div class="p-3 bg-blue-50 border border-blue-100 rounded-3 d-flex align-items-center justify-content-between" v-if="selectedSiswa">
                                <div>
                                    <div class="fw-bold text-slate-800 fs-7">{{ selectedSiswa.nama }}</div>
                                    <small class="text-muted">NISN: {{ selectedSiswa.nisn }} | Kelas: {{ selectedSiswa.nama_kelas }}</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 shadow-none" @click="clearSelectedSiswa"><i class="bi bi-x-circle fs-5"></i></button>
                            </div>

                            <!-- Komponen SPP/Biaya -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Komponen Biaya <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formKeringanan.komponen_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Komponen --</option>
                                    <option v-for="k in komponenList" :value="k.id" :disabled="k.is_active == 0">{{ k.nama_komponen }} {{ k.is_active == 0 ? '(Non-Aktif)' : '' }}</option>
                                </select>
                            </div>

                            <!-- Tipe Keringanan -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Tipe Keringanan</label>
                                <select class="form-select border-slate-200" v-model="formKeringanan.tipe_keringanan" style="height: 42px;">
                                    <option value="Nominal">Nominal (Rp)</option>
                                    <option value="Persentase">Persentase (%)</option>
                                </select>
                            </div>

                            <!-- Nilai Potongan -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Besar Potongan <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-slate-200 fw-bold" v-if="formKeringanan.tipe_keringanan === 'Nominal'">Rp</span>
                                    <input type="number" class="form-control border-slate-200" v-model.number="formKeringanan.nilai" placeholder="0" required style="height: 42px;">
                                    <span class="input-group-text bg-light border-slate-200 fw-bold" v-if="formKeringanan.tipe_keringanan === 'Persentase'">%</span>
                                </div>
                            </div>

                            <!-- Keterangan -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Keterangan / Alasan Beasiswa</label>
                                <textarea class="form-control border-slate-200" v-model="formKeringanan.keterangan" rows="3" placeholder="Contoh: Siswa Berprestasi, Keringanan Yatim Piatu, dll."></textarea>
                            </div>

                            <div class="pt-3">
                                <button type="submit" class="btn btn-primary fw-bold w-100 py-2.5" :disabled="loadingKeringanan || !selectedSiswa">Simpan Keringanan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Keringanan -->
                <div class="col-12 col-md-8 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Daftar Keringanan Aktif</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Nama Siswa</th>
                                        <th>Komponen Tagihan</th>
                                        <th>Tipe</th>
                                        <th>Nilai Potongan</th>
                                        <th>Keterangan</th>
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="k in paginatedKeringanan" :key="k.id">
                                        <td>
                                            <div class="fw-bold text-slate-800">{{ k.nama_siswa }}</div>
                                            <small class="text-muted">NISN: {{ k.nisn }}</small>
                                        </td>
                                        <td class="fw-semibold text-slate-700">{{ k.nama_komponen }}</td>
                                        <td>
                                            <span class="badge rounded px-3 py-2" :class="k.tipe_keringanan === 'Nominal' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                                                {{ k.tipe_keringanan }}
                                            </span>
                                        </td>
                                        <td class="fw-bold text-slate-800">
                                            <span v-if="k.tipe_keringanan === 'Nominal'">Rp {{ formatNumber(k.nilai) }}</span>
                                            <span v-else>{{ formatNumber(k.nilai) }}%</span>
                                        </td>
                                        <td class="text-muted fs-7">{{ k.keterangan || '-' }}</td>
                                        <td class="text-center">
                                            <button @click="deleteKeringanan(k.id)" class="btn btn-link text-danger p-0 shadow-none" title="Hapus">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredKeringanan.length === 0">
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada keringanan/beasiswa terdaftar.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Keringanan -->
                        <div class="d-flex justify-content-between align-items-center mt-3" v-if="totalKeringananPages > 1">
                            <span class="text-muted fs-8">Menampilkan Halaman {{ keringananPage }} dari {{ totalKeringananPages }}</span>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm justify-content-end mb-0">
                                    <li class="page-item" :class="{ disabled: keringananPage === 1 }">
                                        <a class="page-link" href="#" @click.prevent="keringananPage--">Sebelumnya</a>
                                    </li>
                                    <li class="page-item" v-for="p in visibleKeringananPages" :key="p" :class="{ active: keringananPage === p, disabled: p === '...' }">
                                        <span v-if="p === '...'" class="page-link">...</span>
                                        <a v-else class="page-link" href="#" @click.prevent="keringananPage = p">{{ p }}</a>
                                    </li>
                                    <li class="page-item" :class="{ disabled: keringananPage === totalKeringananPages }">
                                        <a class="page-link" href="#" @click.prevent="keringananPage++">Berikutnya</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4: Terbit Tagihan (Generate) -->
        <div class="tab-pane fade" id="generate-pane" role="tabpanel">
            <div class="row">
                <!-- Form Generate (Left: 5-cols) -->
                <div class="col-12 col-lg-5 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Konfigurasi Generate Tagihan</h5>
                        
                        <form @submit.prevent="generateTagihan" class="d-flex flex-column gap-3">
                            <!-- Komponen Biaya -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Komponen Biaya <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formGenerate.komponen_id" @change="onKomponenChange" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Komponen Biaya --</option>
                                    <option v-for="k in komponenList" :value="k.id" :disabled="k.is_active == 0">{{ k.nama_komponen }} ({{ k.tipe_periode }}) {{ k.is_active == 0 ? '(Non-Aktif)' : '' }}</option>
                                </select>
                            </div>

                            <div class="row g-3">
                                <!-- Tahun Ajaran -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-slate-700">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <select class="form-select border-slate-200" v-model="formGenerate.tahun_ajaran_id" required style="height: 42px;">
                                        <option value="" disabled>-- Pilih Tahun Ajaran --</option>
                                        <option v-for="ta in listTa" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                                    </select>
                                </div>

                                <!-- Bulan (jika bulanan) -->
                                <div class="col-md-6" v-if="isBulanan">
                                    <label class="form-label fw-semibold text-slate-700">Bulan Tagihan <span class="text-danger">*</span></label>
                                    <select class="form-select border-slate-200" v-model="formGenerate.bulan" required style="height: 42px;">
                                        <option value="" disabled>-- Pilih Bulan --</option>
                                        <option value="7">Juli</option>
                                        <option value="8">Agustus</option>
                                        <option value="9">September</option>
                                        <option value="10">Oktober</option>
                                        <option value="11">November</option>
                                        <option value="12">Desember</option>
                                        <option value="1">Januari</option>
                                        <option value="2">Februari</option>
                                        <option value="3">Maret</option>
                                        <option value="4">April</option>
                                        <option value="5">Mei</option>
                                        <option value="6">Juni</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Target Filter -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Target Distribusi</label>
                                <div class="d-flex gap-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="targetDist" id="targetAll" value="all" v-model="generateTargetType" @change="resetGenerateTargets">
                                        <label class="form-check-label text-slate-700 fw-medium" for="targetAll">Semua Kelas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="targetDist" id="targetKelas" value="kelas" v-model="generateTargetType" @change="resetGenerateTargets">
                                        <label class="form-check-label text-slate-700 fw-medium" for="targetKelas">Per Kelas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="targetDist" id="targetJenjang" value="jenjang" v-model="generateTargetType" @change="resetGenerateTargets">
                                        <label class="form-check-label text-slate-700 fw-medium" for="targetJenjang">Per Jenjang</label>
                                    </div>
                                </div>

                                <!-- Dropdown kelas/jenjang -->
                                <div class="mb-3" v-if="generateTargetType === 'kelas'">
                                    <label class="form-label fw-semibold text-slate-700">Pilih Kelas Sasaran <span class="text-danger">*</span></label>
                                    <select class="form-select border-slate-200" v-model="formGenerate.kelas_id" required style="height: 42px;">
                                        <option value="" disabled>-- Pilih Kelas --</option>
                                        <option v-for="c in listKelas" :value="c.id">{{ c.nama_kelas }}</option>
                                    </select>
                                </div>

                                <div class="mb-3" v-if="generateTargetType === 'jenjang'">
                                    <label class="form-label fw-semibold text-slate-700">Pilih Jenjang Sasaran <span class="text-danger">*</span></label>
                                    <select class="form-select border-slate-200" v-model="formGenerate.jenjang_id" required style="height: 42px;">
                                        <option value="" disabled>-- Pilih Jenjang --</option>
                                        <option v-for="j in listJenjang" :value="j.id">{{ j.nama_jenjang }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-3">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold w-100 py-3" :disabled="loadingGenerate || selectedSiswaIds.length === 0">
                                    <span v-if="loadingGenerate" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Terbitkan Tagihan ({{ selectedSiswaIds.length }} Siswa)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preview Box (Right: 7-cols) -->
                <div class="col-12 col-lg-7 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">
                            <i class="bi bi-eye text-blue-600 me-2"></i>Pratinjau Siswa Sasaran
                        </h5>

                        <!-- Loading State -->
                        <div v-if="loadingPreview" class="text-center py-5 my-auto text-muted">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <div>Memuat pratinjau daftar siswa...</div>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="previewList.length === 0" class="text-center py-5 my-auto text-muted">
                            <i class="bi bi-file-earmark-person fs-1 text-slate-300 d-block mb-3"></i>
                            <div>Silakan lengkapi pilihan Komponen dan Tahun Ajaran di sebelah kiri untuk melihat daftar siswa calon penerima tagihan.</div>
                        </div>

                        <!-- Table Preview -->
                        <div v-else class="table-responsive flex-fill overflow-auto" style="max-height: 420px;">
                            <table class="table table-hover align-middle fs-8">
                                <thead class="sticky-top bg-white" style="z-index: 1;">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">
                                            <input class="form-check-input" type="checkbox" :checked="isAllSelected" :disabled="eligibleCount === 0" @change="toggleSelectAll">
                                        </th>
                                        <th>Nama Siswa</th>
                                        <th>Kelas</th>
                                        <th class="text-end">Tarif Dasar</th>
                                        <th class="text-end">Keringanan</th>
                                        <th class="text-end">Net Tagihan</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in previewList" :key="p.id">
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox" :value="p.id" v-model="selectedSiswaIds" :disabled="p.sudah_ada">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-slate-800">{{ p.nama }}</div>
                                            <div class="text-muted fs-9">NISN: {{ p.nisn }}</div>
                                        </td>
                                        <td>{{ p.nama_kelas }}</td>
                                        <td class="text-end text-slate-600">Rp {{ formatNumber(p.nominal_asli) }}</td>
                                        <td class="text-end text-success">
                                            <span v-if="p.potongan > 0">-Rp {{ formatNumber(p.potongan) }}</span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td class="fw-bold text-slate-800 text-end">Rp {{ formatNumber(p.nominal_akhir) }}</td>
                                        <td class="text-center">
                                            <span v-if="p.sudah_ada" class="badge bg-secondary">Sudah Terbit</span>
                                            <span v-else class="badge bg-success">Siap Terbit</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 5: Daftar Tagihan Siswa -->
        <div class="tab-pane fade" id="tagihan-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Filter & Cari Tagihan Siswa</h5>
                
                <!-- Filter baris pertama -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-slate-600 fs-8">Cari Nama / NISN</label>
                        <input type="text" class="form-control border-slate-200" v-model="filterList.q" @input="onSearchInput" placeholder="Masukkan kata kunci...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-slate-600 fs-8">Kelas</label>
                        <select class="form-select border-slate-200" v-model="filterList.kelas_id" @change="fetchDaftarTagihan">
                            <option value="">-- Semua Kelas --</option>
                            <option v-for="c in listKelas" :value="c.id">{{ c.nama_kelas }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-slate-600 fs-8">Tahun Ajaran</label>
                        <select class="form-select border-slate-200" v-model="filterList.tahun_ajaran_id" @change="fetchDaftarTagihan">
                            <option value="">-- Semua Tahun Ajaran --</option>
                            <option v-for="ta in listTa" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-slate-600 fs-8">Komponen Tagihan</label>
                        <select class="form-select border-slate-200" v-model="filterList.komponen_id" @change="fetchDaftarTagihan">
                            <option value="">-- Semua Komponen --</option>
                            <option v-for="k in komponenList" :value="k.id">{{ k.nama_komponen }} ({{ k.tipe_periode }})</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-slate-600 fs-8">Status Lunas</label>
                        <select class="form-select border-slate-200" v-model="filterList.status_lunas" @change="fetchDaftarTagihan">
                            <option value="">-- Semua Status --</option>
                            <option value="Belum">Belum Lunas</option>
                            <option value="Cicil">Cicil</option>
                            <option value="Lunas">Lunas</option>
                        </select>
                    </div>
                </div>

                <!-- Export Excel & Reset Button -->
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <div class="text-muted fs-8" v-if="!hasFilterApplied">
                        <i class="bi bi-info-circle me-1"></i> Silakan tentukan minimal 1 filter di atas untuk menampilkan data tagihan.
                    </div>
                    <div class="text-muted fs-8" v-else>
                        Ditemukan <strong>{{ tagihanTotalRows }}</strong> data tagihan.
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" @click="resetFilters" class="btn btn-outline-secondary btn-sm fw-bold px-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                        </button>
                        <button type="button" @click="downloadExcel" class="btn btn-emerald text-white btn-sm fw-bold px-3" :disabled="!hasFilterApplied">
                            <i class="bi bi-file-earmark-excel me-1"></i> Ekspor ke Excel (.xlsx)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Tagihan -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4" v-if="hasFilterApplied">
                <div v-if="loadingList" class="text-center py-5 text-muted">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <div>Memuat data tagihan...</div>
                </div>
                
                <div v-else-if="tagihanList.length === 0" class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-x fs-1 d-block mb-3 text-slate-300"></i>
                    Belum ada tagihan terbit yang cocok dengan kriteria filter Anda.
                </div>

                <div v-else>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th v-if="isSuperAdmin">Sekolah</th>
                                    <th>Siswa</th>
                                    <th>Kelas</th>
                                    <th>Komponen</th>
                                    <th>Tahun Ajaran</th>
                                    <th class="text-end">Nominal Tagihan</th>
                                    <th class="text-end">Telah Dibayar</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in tagihanList" :key="item.id">
                                    <td v-if="isSuperAdmin" class="text-muted fs-8">{{ item.nama_sekolah }}</td>
                                    <td>
                                        <div class="fw-bold text-slate-800">{{ item.nama_siswa }}</div>
                                        <div class="text-muted fs-9">NISN: {{ item.nisn }}</div>
                                    </td>
                                    <td>{{ item.nama_kelas }}</td>
                                    <td>
                                        <span class="fw-bold text-slate-700">{{ item.nama_komponen }}</span>
                                        <small class="text-muted d-block" v-if="item.tipe_periode === 'Bulanan'">Bulan: {{ getBulanName(item.bulan) }}</small>
                                    </td>
                                    <td>{{ item.tahun_ajaran }}</td>
                                    <td class="text-end fw-bold text-slate-800">Rp {{ formatNumber(item.nominal_tagihan) }}</td>
                                    <td class="text-end text-success fw-semibold">Rp {{ formatNumber(item.nominal_bayar) }}</td>
                                    <td class="text-center">
                                        <span class="badge text-white px-3 py-2" :class="getStatusBadgeClass(item.status_lunas)">{{ item.status_lunas }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button @click="openEditModal(item)" class="btn btn-link text-primary p-0 me-3 shadow-none" title="Ubah Nominal">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </button>
                                        <button @click="deleteTagihan(item)" class="btn btn-link text-danger p-0 shadow-none" title="Hapus Tagihan" :disabled="item.nominal_bayar > 0">
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3" v-if="tagihanTotalPages > 1">
                        <span class="text-muted fs-8">Menampilkan Halaman {{ tagihanPage }} dari {{ tagihanTotalPages }}</span>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm justify-content-end mb-0">
                                <li class="page-item" :class="{ disabled: tagihanPage === 1 }">
                                    <a class="page-link" href="#" @click.prevent="setListPage(tagihanPage - 1)">Sebelumnya</a>
                                </li>
                                <li class="page-item" v-for="p in visibleTagihanPages" :key="p" :class="{ active: tagihanPage === p, disabled: p === '...' }">
                                    <span v-if="p === '...'" class="page-link">...</span>
                                    <a v-else class="page-link" href="#" @click.prevent="setListPage(p)">{{ p }}</a>
                                </li>
                                <li class="page-item" :class="{ disabled: tagihanPage === tagihanTotalPages }">
                                    <a class="page-link" href="#" @click.prevent="setListPage(tagihanPage + 1)">Berikutnya</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 6: Pengaturan Keuangan -->
        <div class="tab-pane fade" id="pengaturan-pane" role="tabpanel">
            <div class="row">
                <div class="col-12 col-lg-8 mx-auto">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                        <div class="p-4 bg-gradient-blue text-white d-flex align-items-center" style="background: linear-gradient(135deg, #1e40af, #3b82f6);">
                            <div class="me-3 p-3 bg-white bg-opacity-20 rounded-3">
                                <i class="bi bi-sliders fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1 text-white">Konfigurasi Fleksibel & Regulasi</h5>
                                <p class="mb-0 fs-7 opacity-85 text-white">Sesuaikan penamaan istilah SPP & visibilitas dashboard sesuai kebijakan lembaga sekolah Anda.</p>
                            </div>
                        </div>

                        <form @submit.prevent="saveSettings" class="card-body p-4 bg-white">
                            <!-- Input 1: Nama Modul -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-slate-700">Nama Modul Keuangan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg border-slate-200" v-model="formSettings.nama_modul" placeholder="Contoh: Dana Komite, Sumbangan Sukarela, Iuran Partisipasi" required style="height: 48px;">
                                <div class="form-text text-muted">Nama ini akan menggantikan judul menu utama di sidebar menu sekolah secara global.</div>
                            </div>

                            <div class="row mb-4">
                                <!-- Input 2: Istilah Tagihan -->
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label fw-bold text-slate-700">Istilah untuk "Tagihan" <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control border-slate-200" v-model="formSettings.istilah_tagihan" placeholder="Contoh: Rincian Dana, Kontribusi" required style="height: 42px;">
                                    <div class="form-text text-muted">Akan menggantikan kata "Tagihan" pada kuitansi dan dashboard.</div>
                                </div>

                                <!-- Input 3: Istilah Tunggakan -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-slate-700">Istilah untuk "Tunggakan" <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control border-slate-200" v-model="formSettings.istilah_tunggakan" placeholder="Contoh: Kekurangan Partisipasi" required style="height: 42px;">
                                    <div class="form-text text-muted">Menggantikan kata "Tunggakan" pada rekapitulasi pelaporan.</div>
                                </div>
                            </div>

                            <!-- Input 4: Toggle Visibilitas Siswa -->
                            <div class="mb-4 p-3 bg-light rounded-3 border">
                                <div class="form-check form-switch d-flex align-items-center justify-content-between p-0">
                                    <div>
                                        <label class="form-label fw-bold text-slate-800 mb-1" style="cursor: pointer;" for="switchVisibilitas">
                                            Visibilitas untuk Siswa & Wali Murid
                                        </label>
                                        <p class="text-muted mb-0 fs-7">Jika dinonaktifkan, siswa & wali murid tidak akan dapat melihat modul ini di menu mereka (Hanya Tata Usaha).</p>
                                    </div>
                                    <input class="form-check-input fs-3 ms-2 me-0" type="checkbox" id="switchVisibilitas" v-model="formSettings.visibilitas_siswa" :true-value="1" :false-value="0">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold px-4 py-2" :disabled="loadingSettings">
                                    <span v-if="loadingSettings" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap Modal: Edit Nominal Tagihan -->
    <div class="modal fade" id="editNominalModal" tabindex="-1" aria-labelledby="editNominalModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <form @submit.prevent="saveNominalTagihan" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-bottom-0 py-3">
                    <h5 class="modal-title fw-bold text-slate-800" id="editNominalModalLabel"><i class="bi bi-pencil-square text-primary me-2"></i>Ubah Nominal Tagihan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 d-flex flex-column gap-3">
                    <div>
                        <small class="text-muted d-block">Siswa</small>
                        <div class="fw-bold text-slate-800 fs-6">{{ editNominalForm.nama_siswa }}</div>
                    </div>
                    <div>
                        <small class="text-muted d-block">Komponen Biaya</small>
                        <div class="fw-semibold text-slate-700">{{ editNominalForm.nama_komponen }}</div>
                    </div>
                    <div>
                        <label class="form-label fw-bold text-slate-700">Nominal Tagihan Baru (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">Rp</span>
                            <input type="number" class="form-control" v-model.number="editNominalForm.nominal_tagihan" required min="0" style="height: 44px;">
                        </div>
                        <div class="form-text text-muted">Tagihan saat ini sudah terbayar sebesar <strong>Rp {{ formatNumber(editNominalForm.nominal_bayar) }}</strong>.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 py-3">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4" :disabled="loadingEdit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Data Injections with Anti-XSS Flag -->
<script id="data-kelas" type="application/json">
    <?php echo json_encode($list_kelas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-jenjang" type="application/json">
    <?php echo json_encode($list_jenjang, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-ta" type="application/json">
    <?php echo json_encode($list_ta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-komponen" type="application/json">
    <?php echo json_encode($list_komponen, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="user-session" type="application/json">
    <?php echo json_encode([
        'is_super_admin' => (($_SESSION['role_name'] ?? '') === 'super_admin'),
        'tenant_id' => ($_SESSION['tenant_id'] ?? '')
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
/* Styling Navtabs Minimalis Datar (Like Buku Induk) */
.scrollable-nav-tabs {
    padding-bottom: 5px;
    border-bottom: none !important;
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
    color: #475569 !important;
    background-color: transparent !important;
    border: none !important;
    border-bottom: 2px solid transparent !important;
    border-radius: 0 !important;
    font-weight: 600 !important;
    padding: 10px 16px !important;
    transition: all 0.2s ease-in-out;
}

.nav-tabs-wrapper .nav-link:hover {
    color: #2563eb !important;
}

.nav-tabs-wrapper .nav-link.active {
    color: #2563eb !important;
    background-color: transparent !important;
    border-bottom: 2px solid #2563eb !important;
}

/* Styling Tabel Modern Borderless */
.table {
    border-collapse: collapse !important;
    width: 100%;
}
.table th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 2px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.75rem 1rem !important;
}
.table td {
    border-bottom: 1px solid #f1f5f9 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.85rem 1rem !important;
    font-size: 0.8rem !important;
    color: #334155 !important;
}
.table tbody tr {
    transition: background-color 0.15s ease;
}
.table tbody tr:hover {
    background-color: #f8fafc !important;
}

.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.75rem; }
.fs-9 { font-size: 0.68rem; }
.text-slate-600 { color: #475569; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }
.bg-blue-50 { background-color: #eff6ff; }
.bg-blue-100 { background-color: #dbeafe; }
.text-blue-700 { color: #1d4ed8; }
.bg-purple-100 { background-color: #f3e8ff; }
.text-purple-700 { color: #6b21a8; }
.bg-teal-100 { background-color: #ccfbf1; }
.text-teal-700 { color: #0f766e; }
.bg-slate-100 { background-color: #f1f5f9; }
.bg-amber-100 { background-color: #fef3c7; }
.text-amber-700 { color: #b45309; }
.btn-emerald {
    background-color: #10b981;
    border-color: #10b981;
}
.btn-emerald:hover {
    background-color: #059669;
    border-color: #059669;
}
</style>

<script>
window.VueAppRegistry.register('#keuangan-master-app', {
    setup() {
        const session = JSON.parse(document.getElementById('user-session').textContent || '{}');
        const isSuperAdmin = session.is_super_admin;
        const tenantsList = Vue.ref([]);
        const selectedTenantId = Vue.ref(session.tenant_id || '');

        const successMsg = Vue.ref('');
        const errorMsg = Vue.ref('');

        const listKelas = Vue.ref([]);
        const listJenjang = Vue.ref([]);
        const listTa = Vue.ref([]);
        const komponenList = Vue.ref([]);

        // Cache initial list from DOM injection
        const initialKomponen = JSON.parse(document.getElementById('data-komponen').textContent || '[]');

        // Global Alert helper
        const flashMessage = (msg, isError = false) => {
            if (isError) {
                errorMsg.value = msg;
                successMsg.value = '';
            } else {
                successMsg.value = msg;
                errorMsg.value = '';
            }
            setTimeout(() => {
                successMsg.value = '';
                errorMsg.value = '';
            }, 5000);
        };

        const getQueryParam = () => {
            return isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
        };

        const fetchTenants = async () => {
            if (!isSuperAdmin) return;
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/tenants');
                const res = await response.json();
                if (res.success) {
                    tenantsList.value = res.data;
                    const cached = localStorage.getItem('sinta_spp_selected_tenant_id');
                    if (cached && tenantsList.value.some(t => t.id === cached)) {
                        selectedTenantId.value = cached;
                    } else if (tenantsList.value.length > 0) {
                        selectedTenantId.value = tenantsList.value[0].id;
                        localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
                    }
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchKomponen = async () => {
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/komponen' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    komponenList.value = res.data;
                    kompPage.value = 1;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchTahunAjaran = async () => {
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/tahun-ajaran' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    listTa.value = res.data;
                    // Auto select active TA for generator form if empty
                    const activeTa = listTa.value.find(ta => ta.status === 'Aktif');
                    if (activeTa && !formGenerate.value.tahun_ajaran_id) {
                        formGenerate.value.tahun_ajaran_id = activeTa.id;
                    }
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchKelas = async () => {
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/kelas' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    listKelas.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchJenjang = async () => {
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/jenjang' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    listJenjang.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };


        // ---------------------------------------------------------------------
        // TAB 1: KOMPONEN BIAYA LOGIC
        // ---------------------------------------------------------------------
        const loadingKomp = Vue.ref(false);
        const formKomp = Vue.ref({
            id: 0,
            tenant_id: '',
            nama_komponen: '',
            tipe_periode: 'Bulanan',
            is_active: 1
        });
        const kompPage = Vue.ref(1);
        const kompPageSize = Vue.ref(5);

        const resetFormKomp = () => {
            formKomp.value = { id: 0, tenant_id: selectedTenantId.value || '', nama_komponen: '', tipe_periode: 'Bulanan', is_active: 1 };
        };

        const saveKomponen = async () => {
            loadingKomp.value = true;
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/komponen' + getQueryParam(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formKomp.value)
                });
                const res = await response.json();
                if (res.success) {
                    flashMessage('Komponen biaya berhasil disimpan.');
                    fetchKomponen();
                    resetFormKomp();
                } else {
                    flashMessage(res.error || 'Gagal menyimpan komponen.', true);
                }
            } catch (err) {
                console.error(err);
                flashMessage('Terjadi kesalahan koneksi.', true);
            } finally {
                loadingKomp.value = false;
            }
        };

        const editKomponen = (item) => {
            formKomp.value = { ...item };
        };

        const deleteKomponen = async (id) => {
            if (!confirm('Apakah Anda yakin ingin menghapus komponen biaya ini? Semua tarif terkait akan terhapus.')) return;
            try {
                const response = await fetch(`<?= $this->getBaseUrl() ?>/api/v1/keuangan/komponen?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                });
                const res = await response.json();
                if (res.success) {
                    flashMessage('Komponen biaya berhasil dihapus.');
                    fetchKomponen();
                    fetchTarif();
                } else {
                    flashMessage(res.error || 'Gagal menghapus komponen.', true);
                }
            } catch (err) {
                console.error(err);
            }
        };

        const toggleKompStatus = async (item) => {
            const nextStatus = item.is_active == 1 ? 0 : 1;
            const tenantParam = isSuperAdmin ? `?tenant_id=${item.tenant_id}` : '';
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/komponen/toggle' + tenantParam, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: item.id,
                        is_active: nextStatus
                    })
                });
                const res = await response.json();
                if (res.success) {
                    item.is_active = nextStatus;
                    flashMessage('Status keaktifan komponen diperbarui.');
                } else {
                    alert(res.error || 'Gagal mengubah status komponen.');
                }
            } catch (err) {
                console.error(err);
            }
        };

        const getPeriodeBadgeClass = (periode) => {
            switch(periode) {
                case 'Bulanan': return 'bg-blue-100 text-blue-700';
                case 'Semester': return 'bg-purple-100 text-purple-700';
                case 'Tahunan': return 'bg-teal-100 text-teal-700';
                default: return 'bg-slate-100 text-slate-700';
            }
        };

        const filteredKomponen = Vue.computed(() => komponenList.value);
        const paginatedKomponen = Vue.computed(() => {
            const start = (kompPage.value - 1) * kompPageSize.value;
            return filteredKomponen.value.slice(start, start + kompPageSize.value);
        });
        const totalKompPages = Vue.computed(() => {
            return Math.ceil(filteredKomponen.value.length / kompPageSize.value) || 1;
        });


        // ---------------------------------------------------------------------
        // TAB 2: TARIF ACUAN DEFAULT LOGIC
        // ---------------------------------------------------------------------
        const tarifList = Vue.ref([]);
        const loadingTarif = Vue.ref(false);
        const tarifTargetType = Vue.ref('general');
        const formTarif = Vue.ref({
            komponen_id: '',
            tahun_ajaran_id: '',
            kelas_id: '',
            jenjang_id: '',
            jalur_masuk: '',
            nominal: ''
        });

        const filterTarifTenant = Vue.ref(session.tenant_id || '');
        const filterTarifTa = Vue.ref('');
        const filterTarifKomp = Vue.ref('');
        const tarifPage = Vue.ref(1);
        const tarifPageSize = Vue.ref(8);

        const fetchTarif = async () => {
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/tarif' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    tarifList.value = res.data;
                    tarifPage.value = 1;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const resetTarifTargets = () => {
            formTarif.value.kelas_id = null;
            formTarif.value.jenjang_id = null;
            formTarif.value.jalur_masuk = null;
        };

        const saveTarif = async () => {
            loadingTarif.value = true;
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/tarif' + getQueryParam(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formTarif.value)
                });
                const res = await response.json();
                if (res.success) {
                    flashMessage('Tarif acuan default berhasil disimpan.');
                    fetchTarif();
                    formTarif.value.nominal = '';
                    resetTarifTargets();
                } else {
                    flashMessage(res.error || 'Gagal menyimpan tarif.', true);
                }
            } catch (err) {
                console.error(err);
                flashMessage('Kesalahan jaringan.', true);
            } finally {
                loadingTarif.value = false;
            }
        };

        const deleteTarif = async (id) => {
            if (!confirm('Apakah Anda yakin ingin menghapus tarif default ini?')) return;
            try {
                const response = await fetch(`<?= $this->getBaseUrl() ?>/api/v1/keuangan/tarif?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                });
                const res = await response.json();
                if (res.success) {
                    flashMessage('Tarif acuan default berhasil dihapus.');
                    fetchTarif();
                } else {
                    flashMessage(res.error || 'Gagal menghapus tarif.', true);
                }
            } catch (err) {
                console.error(err);
            }
        };

        const uniqueKomponenNames = Vue.computed(() => {
            const names = new Set();
            tarifList.value.forEach(t => {
                if (t.nama_komponen) names.add(t.nama_komponen);
            });
            return Array.from(names);
        });

        const filteredTarif = Vue.computed(() => {
            return tarifList.value.filter(t => {
                const matchTenant = !filterTarifTenant.value || t.tenant_id === filterTarifTenant.value;
                const matchTa = !filterTarifTa.value || t.tahun_ajaran_id == filterTarifTa.value;
                const matchKomp = !filterTarifKomp.value || t.nama_komponen === filterTarifKomp.value;
                return matchTenant && matchTa && matchKomp;
            });
        });

        const paginatedTarif = Vue.computed(() => {
            const start = (tarifPage.value - 1) * tarifPageSize.value;
            return filteredTarif.value.slice(start, start + tarifPageSize.value);
        });

        const totalTarifPages = Vue.computed(() => {
            return Math.ceil(filteredTarif.value.length / tarifPageSize.value) || 1;
        });

        Vue.watch([filterTarifTenant, filterTarifTa, filterTarifKomp], () => {
            tarifPage.value = 1;
        });


        // ---------------------------------------------------------------------
        // TAB 3: KERINGANAN & BEASISWA LOGIC
        // ---------------------------------------------------------------------
        const keringananList = Vue.ref([]);
        const loadingKeringanan = Vue.ref(false);
        const siswaSearch = Vue.ref('');
        const siswaSuggestions = Vue.ref([]);
        const selectedSiswa = Vue.ref(null);
        const keringananPage = Vue.ref(1);
        const keringananPageSize = Vue.ref(6);

        const formKeringanan = Vue.ref({
            siswa_id: '',
            komponen_id: '',
            tipe_keringanan: 'Nominal',
            nilai: '',
            keterangan: ''
        });

        const fetchKeringanan = async () => {
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/keringanan' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    keringananList.value = res.data;
                    keringananPage.value = 1;
                }
            } catch (err) {
                console.error(err);
            }
        };

        let searchTimeout = null;
        const searchSiswa = () => {
            clearTimeout(searchTimeout);
            if (siswaSearch.value.length < 2) {
                siswaSuggestions.value = [];
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                    const response = await fetch(`<?= $this->getBaseUrl() ?>/api/v1/keuangan/cari-siswa?q=${encodeURIComponent(siswaSearch.value)}${tenantSuffix}`);
                    const res = await response.json();
                    if (res.success) {
                        siswaSuggestions.value = res.data;
                    }
                } catch (err) {
                    console.error(err);
                }
            }, 300);
        };

        const selectSiswa = (siswa) => {
            selectedSiswa.value = siswa;
            formKeringanan.value.siswa_id = siswa.id;
            siswaSearch.value = '';
            siswaSuggestions.value = [];
        };

        const clearSelectedSiswa = () => {
            selectedSiswa.value = null;
            formKeringanan.value.siswa_id = '';
        };

        const saveKeringanan = async () => {
            loadingKeringanan.value = true;
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/keringanan' + getQueryParam(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formKeringanan.value)
                });
                const res = await response.json();
                if (res.success) {
                    flashMessage('Keringanan beasiswa siswa berhasil disimpan.');
                    fetchKeringanan();
                    // Reset
                    formKeringanan.value.komponen_id = '';
                    formKeringanan.value.nilai = '';
                    formKeringanan.value.keterangan = '';
                    clearSelectedSiswa();
                } else {
                    flashMessage(res.error || 'Gagal menyimpan keringanan.', true);
                }
            } catch (err) {
                console.error(err);
            } finally {
                loadingKeringanan.value = false;
            }
        };

        const deleteKeringanan = async (id) => {
            if (!confirm('Hapus konfigurasi beasiswa siswa ini?')) return;
            try {
                const response = await fetch(`<?= $this->getBaseUrl() ?>/api/v1/keuangan/keringanan?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                });
                const res = await response.json();
                if (res.success) {
                    flashMessage('Keringanan beasiswa siswa berhasil dihapus.');
                    fetchKeringanan();
                } else {
                    flashMessage(res.error || 'Gagal menghapus keringanan.', true);
                }
            } catch (err) {
                console.error(err);
            }
        };

        const filteredKeringanan = Vue.computed(() => keringananList.value);
        const paginatedKeringanan = Vue.computed(() => {
            const start = (keringananPage.value - 1) * keringananPageSize.value;
            return filteredKeringanan.value.slice(start, start + keringananPageSize.value);
        });
        const totalKeringananPages = Vue.computed(() => {
            return Math.ceil(filteredKeringanan.value.length / keringananPageSize.value) || 1;
        });


        // ---------------------------------------------------------------------
        // TAB 4: TERBIT TAGIHAN (GENERATE) LOGIC
        // ---------------------------------------------------------------------
        const loadingPreview = Vue.ref(false);
        const loadingGenerate = Vue.ref(false);
        const isBulanan = Vue.ref(false);
        const targetType = Vue.ref('all');
        const previewList = Vue.ref([]);
        const selectedSiswaIds = Vue.ref([]);

        const formGenerate = Vue.ref({
            komponen_id: '',
            tahun_ajaran_id: '',
            bulan: '',
            kelas_id: '',
            jenjang_id: ''
        });

        const resetGenerateTargets = () => {
            formGenerate.value.kelas_id = '';
            formGenerate.value.jenjang_id = '';
        };

        const onKomponenChange = () => {
            const selected = komponenList.value.find(k => k.id == formGenerate.value.komponen_id);
            if (selected && selected.tipe_periode === 'Bulanan') {
                isBulanan.value = true;
                formGenerate.value.bulan = '';
            } else {
                isBulanan.value = false;
                formGenerate.value.bulan = null;
            }
        };

        // Fetch Preview target siswa
        const fetchPreview = async () => {
            if (!formGenerate.value.komponen_id || !formGenerate.value.tahun_ajaran_id) {
                previewList.value = [];
                return;
            }
            loadingPreview.value = true;
            try {
                const tenantParam = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                const query = `komponen_id=${formGenerate.value.komponen_id}&tahun_ajaran_id=${formGenerate.value.tahun_ajaran_id}` +
                              `&kelas_id=${formGenerate.value.kelas_id || ''}&jenjang_id=${formGenerate.value.jenjang_id || ''}` +
                              `&bulan=${formGenerate.value.bulan || ''}${tenantParam}`;
                const response = await fetch(`<?= $this->getBaseUrl() ?>/api/v1/keuangan/preview-generate?${query}`);
                const res = await response.json();
                if (res.success) {
                    previewList.value = res.data;
                } else {
                    previewList.value = [];
                    flashMessage(res.error || 'Gagal memuat pratinjau siswa.', true);
                }
            } catch (err) {
                console.error(err);
            } finally {
                loadingPreview.value = false;
            }
        };

        // Watch generate form fields to trigger preview fetch
        Vue.watch(
            () => [formGenerate.value.komponen_id, formGenerate.value.tahun_ajaran_id, formGenerate.value.kelas_id, formGenerate.value.jenjang_id, formGenerate.value.bulan, targetType.value, selectedTenantId.value],
            () => {
                fetchPreview();
            },
            { deep: true }
        );

        const eligibleCount = Vue.computed(() => {
            return previewList.value.filter(p => !p.sudah_ada).length;
        });

        const isAllSelected = Vue.computed(() => {
            const eligible = previewList.value.filter(p => !p.sudah_ada);
            if (eligible.length === 0) return false;
            return eligible.every(p => selectedSiswaIds.value.includes(p.id));
        });

        const toggleSelectAll = (event) => {
            if (event.target.checked) {
                const eligible = previewList.value.filter(p => !p.sudah_ada);
                selectedSiswaIds.value = eligible.map(p => p.id);
            } else {
                selectedSiswaIds.value = [];
            }
        };

        // Watch previewList changes to auto-select eligible students
        Vue.watch(previewList, (newList) => {
            const eligible = newList.filter(p => !p.sudah_ada);
            selectedSiswaIds.value = eligible.map(p => p.id);
        });

        const generateTagihan = async () => {
            if (selectedSiswaIds.value.length === 0) return;
            loadingGenerate.value = true;
            try {
                const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                const payload = {
                    ...formGenerate.value,
                    siswa_ids: selectedSiswaIds.value
                };
                const response = await fetch(`<?= $this->getBaseUrl() ?>/api/v1/keuangan/generate-tagihan?${tenantSuffix}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                if (res.success) {
                    flashMessage(`Berhasil menerbitkan ${res.count} tagihan untuk target siswa terpilih!`);
                    fetchPreview();
                    resetGenerateTargets();
                } else {
                    flashMessage(res.error || 'Gagal menerbitkan tagihan.', true);
                }
            } catch (err) {
                console.error(err);
                flashMessage('Terjadi kesalahan koneksi jaringan.', true);
            } finally {
                loadingGenerate.value = false;
            }
        };


        // ---------------------------------------------------------------------
        // TAB 5: DAFTAR TAGIHAN SISWA LOGIC
        // ---------------------------------------------------------------------
        const tagihanList = Vue.ref([]);
        const loadingList = Vue.ref(false);
        const tagihanPage = Vue.ref(1);
        const tagihanTotalPages = Vue.ref(1);
        const tagihanTotalRows = Vue.ref(0);

        const filterList = Vue.ref({
            q: '',
            kelas_id: '',
            tahun_ajaran_id: '',
            komponen_id: '',
            status_lunas: '',
            tenant_id: selectedTenantId.value
        });

        const hasFilterApplied = Vue.computed(() => {
            const f = filterList.value;
            return !!(f.q || f.kelas_id || f.tahun_ajaran_id || f.komponen_id || f.status_lunas);
        });

        const fetchDaftarTagihan = async () => {
            if (!hasFilterApplied.value) {
                tagihanList.value = [];
                tagihanTotalPages.value = 1;
                tagihanTotalRows.value = 0;
                return;
            }
            loadingList.value = true;
            try {
                const tenantParam = filterList.value.tenant_id ? `&tenant_id=${filterList.value.tenant_id}` : '';
                const query = `q=${encodeURIComponent(filterList.value.q)}&kelas_id=${filterList.value.kelas_id}` +
                              `&tahun_ajaran_id=${filterList.value.tahun_ajaran_id}&komponen_id=${filterList.value.komponen_id}` +
                              `&status_lunas=${filterList.value.status_lunas}&page=${tagihanPage.value}&page_size=10${tenantParam}`;
                const response = await fetch(`<?= $this->getBaseUrl() ?>/api/v1/keuangan/daftar-tagihan?${query}`);
                const res = await response.json();
                if (res.success) {
                    tagihanList.value = res.data;
                    tagihanTotalPages.value = res.total_pages;
                    tagihanTotalRows.value = res.total_rows;
                }
            } catch (err) {
                console.error(err);
            } finally {
                loadingList.value = false;
            }
        };

        let listSearchTimeout = null;
        const onSearchInput = () => {
            clearTimeout(listSearchTimeout);
            listSearchTimeout = setTimeout(() => {
                tagihanPage.value = 1;
                fetchDaftarTagihan();
            }, 400);
        };

        const setListPage = (page) => {
            tagihanPage.value = page;
            fetchDaftarTagihan();
        };

        const resetFilters = () => {
            filterList.value = {
                q: '',
                kelas_id: '',
                tahun_ajaran_id: '',
                komponen_id: '',
                status_lunas: '',
                tenant_id: selectedTenantId.value
            };
            tagihanPage.value = 1;
            fetchDaftarTagihan();
        };

        const downloadExcel = () => {
            if (!hasFilterApplied.value) return;
            const query = new URLSearchParams();
            if (isSuperAdmin && selectedTenantId.value) {
                query.append('tenant_id', selectedTenantId.value);
            }
            if (filterList.value.q) query.append('q', filterList.value.q);
            if (filterList.value.kelas_id) query.append('kelas_id', filterList.value.kelas_id);
            if (filterList.value.tahun_ajaran_id) query.append('tahun_ajaran_id', filterList.value.tahun_ajaran_id);
            if (filterList.value.komponen_id) query.append('komponen_id', filterList.value.komponen_id);
            if (filterList.value.status_lunas) query.append('status_lunas', filterList.value.status_lunas);

            window.location.href = '<?= $this->getBaseUrl() ?>/api/v1/keuangan/export-tagihan-excel?' + query.toString();
        };

        // Modal Edit Nominal
        const editNominalForm = Vue.ref({
            id: '',
            nama_siswa: '',
            nama_komponen: '',
            nominal_tagihan: 0,
            nominal_bayar: 0
        });
        const loadingEdit = Vue.ref(false);
        let editModalInstance = null;

        const openEditModal = (item) => {
            editNominalForm.value = {
                id: item.id,
                nama_siswa: item.nama_siswa,
                nama_komponen: item.nama_komponen,
                nominal_tagihan: parseFloat(item.nominal_tagihan),
                nominal_bayar: parseFloat(item.nominal_bayar)
            };
            const modalEl = document.getElementById('editNominalModal');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                editModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                editModalInstance.show();
            }
        };

        const saveNominalTagihan = async () => {
            loadingEdit.value = true;
            try {
                const tenantParam = isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/edit-tagihan-nominal' + tenantParam, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: editNominalForm.value.id,
                        nominal_tagihan: editNominalForm.value.nominal_tagihan
                    })
                });
                const res = await response.json();
                if (res.success) {
                    editModalInstance.hide();
                    fetchDaftarTagihan();
                    flashMessage('Nominal tagihan berhasil diperbarui.');
                } else {
                    flashMessage(res.error || 'Gagal mengubah nominal tagihan.', true);
                }
            } catch (err) {
                console.error(err);
            } finally {
                loadingEdit.value = false;
            }
        };

        const deleteTagihan = async (item) => {
            if (item.nominal_bayar > 0) {
                alert('Tagihan sudah dibayar sebagian/lunas dan tidak dapat dihapus.');
                return;
            }
            if (!confirm(`Apakah Anda yakin ingin menghapus tagihan ${item.nama_komponen} untuk siswa ${item.nama_siswa}?`)) return;
            try {
                const tenantParam = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch(`<?= $this->getBaseUrl() ?>/api/v1/keuangan/hapus-tagihan?id=${item.id}${tenantParam}`, {
                    method: 'DELETE'
                });
                const res = await response.json();
                if (res.success) {
                    fetchDaftarTagihan();
                    flashMessage('Tagihan berhasil dihapus.');
                } else {
                    flashMessage(res.error || 'Gagal menghapus tagihan.', true);
                }
            } catch (err) {
                console.error(err);
            }
        };

        const getStatusBadgeClass = (status) => {
            switch(status) {
                case 'Lunas': return 'bg-success';
                case 'Cicil': return 'bg-warning text-dark';
                default: return 'bg-danger';
            }
        };

        const getBulanName = (b) => {
            const list = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return list[b - 1] || '';
        };

        const visibleTagihanPages = Vue.computed(() => {
            return getVisiblePages(tagihanPage.value, tagihanTotalPages.value);
        });


        // ---------------------------------------------------------------------
        // TAB 6: PENGATURAN LOGIC
        // ---------------------------------------------------------------------
        const loadingSettings = Vue.ref(false);
        const formSettings = Vue.ref({
            nama_modul: 'Keuangan & SPP',
            istilah_tagihan: 'Tagihan',
            istilah_tunggakan: 'Tunggakan',
            visibilitas_siswa: 1
        });

        const fetchSettings = async () => {
            try {
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/pengaturan' + getQueryParam());
                const res = await response.json();
                if (res.success && res.data) {
                    formSettings.value = res.data;
                }
            } catch (err) {
                console.error('Failed to load settings', err);
            }
        };

        const saveSettings = async () => {
            loadingSettings.value = true;
            try {
                const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch('<?= $this->getBaseUrl() ?>/api/v1/keuangan/save-pengaturan' + tenantSuffix, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formSettings.value)
                });
                const res = await response.json();
                if (res.success) {
                    flashMessage('Pengaturan modul keuangan berhasil disimpan!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    flashMessage(res.error || 'Gagal menyimpan pengaturan.', true);
                }
            } catch (err) {
                console.error(err);
            } finally {
                loadingSettings.value = false;
            }
        };


        // ---------------------------------------------------------------------
        // SHARED HELPERS & LISTENERS
        // ---------------------------------------------------------------------
        const getVisiblePages = (current, total) => {
            const delta = 2;
            const left = current - delta;
            const right = current + delta + 1;
            const range = [];
            const rangeWithDots = [];
            let l;

            for (let i = 1; i <= total; i++) {
                if (i === 1 || i === total || (i >= left && i < right)) {
                    range.push(i);
                }
            }

            for (const i of range) {
                if (l) {
                    if (i - l === 2) {
                        rangeWithDots.push(l + 1);
                    } else if (i - l > 2) {
                        rangeWithDots.push('...');
                    }
                }
                rangeWithDots.push(i);
                l = i;
            }

            return rangeWithDots;
        };

        const visibleKompPages = Vue.computed(() => {
            return getVisiblePages(kompPage.value, totalKompPages.value);
        });

        const visibleTarifPages = Vue.computed(() => {
            return getVisiblePages(tarifPage.value, totalTarifPages.value);
        });

        const visibleKeringananPages = Vue.computed(() => {
            return getVisiblePages(keringananPage.value, totalKeringananPages.value);
        });

        const formatNumber = (num) => {
            if (num === null || num === undefined) return '0';
            return new Intl.NumberFormat('id-ID').format(num);
        };

        const onTenantChange = () => {
            localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
            filterTarifTenant.value = selectedTenantId.value;
            filterList.value.tenant_id = selectedTenantId.value;

            fetchKomponen();
            fetchTahunAjaran();
            fetchKelas();
            fetchJenjang();
            fetchTarif();
            fetchKeringanan();
            fetchSettings();

            resetFormKomp();
            resetTarifTargets();
            clearSelectedSiswa();
            resetGenerateTargets();
            previewList.value = [];
            selectedSiswaIds.value = [];

            fetchDaftarTagihan();
        };

        Vue.onMounted(async () => {
            if (isSuperAdmin) {
                await fetchTenants();
                const cached = localStorage.getItem('sinta_spp_selected_tenant_id');
                if (cached && tenantsList.value.some(t => t.id === cached)) {
                    selectedTenantId.value = cached;
                } else if (tenantsList.value.length > 0) {
                    selectedTenantId.value = tenantsList.value[0].id;
                }
                filterTarifTenant.value = selectedTenantId.value;
                filterList.value.tenant_id = selectedTenantId.value;
            } else {
                komponenList.value = initialKomponen;
            }
            await fetchKomponen();
            await fetchTahunAjaran();
            await fetchKelas();
            await fetchJenjang();
            await fetchTarif();
            await fetchKeringanan();
            await fetchSettings();
        });

        return {
            isSuperAdmin,
            tenantsList,
            selectedTenantId,
            successMsg,
            errorMsg,
            listKelas,
            listJenjang,
            listTa,
            komponenList,
            onTenantChange,
            formatNumber,

            // Tab 1: Komponen
            loadingKomp,
            formKomp,
            kompPage,
            kompPageSize,
            resetFormKomp,
            saveKomponen,
            editKomponen,
            deleteKomponen,
            toggleKompStatus,
            getPeriodeBadgeClass,
            filteredKomponen,
            paginatedKomponen,
            totalKompPages,
            visibleKompPages,

            // Tab 2: Tarif Acuan
            tarifList,
            loadingTarif,
            tarifTargetType,
            formTarif,
            filterTarifTenant,
            filterTarifTa,
            filterTarifKomp,
            tarifPage,
            tarifPageSize,
            resetTarifTargets,
            saveTarif,
            deleteTarif,
            uniqueKomponenNames,
            filteredTarif,
            paginatedTarif,
            totalTarifPages,
            visibleTarifPages,

            // Tab 3: Keringanan
            keringananList,
            loadingKeringanan,
            siswaSearch,
            siswaSuggestions,
            selectedSiswa,
            keringananPage,
            keringananPageSize,
            formKeringanan,
            searchSiswa,
            selectSiswa,
            clearSelectedSiswa,
            fetchKeringanan,
            saveKeringanan,
            deleteKeringanan,
            filteredKeringanan,
            paginatedKeringanan,
            totalKeringananPages,
            visibleKeringananPages,

            // Tab 4: Generate
            loadingPreview,
            loadingGenerate,
            isBulanan,
            targetType,
            formGenerate,
            resetGenerateTargets,
            onKomponenChange,
            generateTagihan,
            previewList,
            eligibleCount,
            selectedSiswaIds,
            isAllSelected,
            toggleSelectAll,

            // Tab 5: Daftar Tagihan
            tagihanList,
            loadingList,
            tagihanPage,
            tagihanTotalPages,
            tagihanTotalRows,
            filterList,
            hasFilterApplied,
            fetchDaftarTagihan,
            onSearchInput,
            setListPage,
            resetFilters,
            downloadExcel,
            editNominalForm,
            loadingEdit,
            openEditModal,
            saveNominalTagihan,
            deleteTagihan,
            getStatusBadgeClass,
            getBulanName,
            visibleTagihanPages,

            // Tab 6: Pengaturan
            loadingSettings,
            formSettings,
            fetchSettings,
            saveSettings
        };
    }
});
</script>
