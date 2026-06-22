<!-- Halaman Sentral: Master Data Kelembagaan -->
<div id="masterKelembagaanApp" v-cloak>
    
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

    <!-- ═══ FILTER SEKOLAH — Hanya Super Admin ═══════════════════════════════ -->
    <div v-if="userRole === 'super_admin'" class="card border-0 shadow-sm rounded-4 mb-3" style="background:linear-gradient(135deg,#eff6ff,#f0fdf4);border-left:4px solid #2563eb !important;">
        <div class="card-body py-3 px-4">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <div class="d-flex align-items-center gap-2">
                        <label for="sa-filter-sekolah-kelembagaan" class="d-flex align-items-center gap-2 m-0 fw-bold text-dark fs-7 cursor-pointer">
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
                            id="sa-filter-sekolah-kelembagaan"
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
                        <i class="bi bi-info-circle me-1"></i>Pilih sekolah untuk memfilter semua tabel
                    </div>
                </div>
                <div class="col-auto ms-auto" v-if="filterTenantId">
                    <button class="btn btn-outline-secondary btn-sm rounded-3" style="color:#334155; border-color:#94a3b8;"
                            @click="clearFilterTenant"
                            id="btn-reset-filter-sekolah">
                        <i class="bi bi-x-circle me-1"></i>Reset Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (Scrollable on Mobile) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-pills nav-fill flex-nowrap overflow-x-auto text-nowrap scrollable-nav-pills gap-1">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.id">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-8 fs-md-7 rounded-3 transition" 
                                :class="{active: activeTab === tab.id}" 
                                @click="switchTab(tab.id)">
                            <i :class="tab.icon" class="me-2 fs-6"></i>{{ tab.name }}
                        </button>
                    </li>
                </ul>
            </div>
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
                                    {{ activeTab === 'tahun_ajaran' ? item.tahun_ajaran : item.tahun_angkatan }}
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
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                <span>Tidak ada data ditemukan dalam modul ini.</span>
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

    <!-- Reusable Form Modal (Tambah / Edit) -->
    <div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
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
                                    <label for="form_id_jenjang" class="form-label fw-semibold fs-8 text-muted mb-1">Jenjang Pendidikan <span class="text-danger">*</span></label>
                                    <select id="form_id_jenjang" name="id_jenjang" class="form-select rounded-3" :class="{'is-invalid': errors.id_jenjang}" v-model="form.id_jenjang" required>
                                        <option value="" disabled>-- Pilih Jenjang --</option>
                                        <option v-for="j in listJenjang" :value="j.id" :key="j.id">{{ j.nama }}</option>
                                    </select>
                                    <div class="invalid-feedback">{{ getError('id_jenjang') }}</div>
                                </div>
                                <div class="col-12">
                                    <label for="form_id_jurusan" class="form-label fw-semibold fs-8 text-muted mb-1">Jurusan / Peminatan <span class="text-danger">*</span></label>
                                    <select id="form_id_jurusan" name="id_jurusan" class="form-select rounded-3" :class="{'is-invalid': errors.id_jurusan}" v-model="form.id_jurusan" required>
                                        <option value="" disabled>-- Pilih Jurusan --</option>
                                        <option v-for="j in listJurusan" :value="j.id" :key="j.id">{{ j.nama }}</option>
                                    </select>
                                    <div class="invalid-feedback">{{ getError('id_jurusan') }}</div>
                                </div>
                                <div class="col-12">
                                    <label for="form_kode_kelas" class="form-label fw-semibold fs-8 text-muted mb-1">Kode Kelas <span class="text-danger">*</span></label>
                                    <input id="form_kode_kelas" name="kode_kelas" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kode_kelas}" v-model="form.kode_kelas" placeholder="Misal: KLS-XA" required>
                                    <div class="invalid-feedback">{{ getError('kode_kelas') }}</div>
                                </div>
                                <div class="col-12">
                                    <label for="form_nama_kelas" class="form-label fw-semibold fs-8 text-muted mb-1">Nama Kelas / Rombel <span class="text-danger">*</span></label>
                                    <input id="form_nama_kelas" name="nama_kelas" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.nama_kelas}" v-model="form.nama_kelas" placeholder="Misal: Kelas X-A" required>
                                    <div class="invalid-feedback">{{ getError('nama_kelas') }}</div>
                                </div>
                            </template>

                            <!-- Form inputs khusus Tahun Ajaran -->
                            <template v-else-if="activeTab === 'tahun_ajaran'">
                                <div class="col-12">
                                    <label for="form_tahun_ajaran" class="form-label fw-semibold fs-8 text-muted mb-1">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <input id="form_tahun_ajaran" name="kode" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kode}" v-model="form.kode" placeholder="Contoh: 2025/2026" required>
                                    <div class="invalid-feedback">{{ getError('kode') }}</div>
                                    <small class="text-muted fs-9">Gunakan format YYYY/YYYY.</small>
                                </div>
                            </template>

                            <!-- Form inputs khusus Angkatan -->
                            <template v-else-if="activeTab === 'angkatan'">
                                <div class="col-12">
                                    <label for="form_tahun_angkatan" class="form-label fw-semibold fs-8 text-muted mb-1">Tahun Angkatan <span class="text-danger">*</span></label>
                                    <input id="form_tahun_angkatan" name="kode" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kode}" v-model="form.kode" placeholder="Contoh: 2026" required>
                                    <div class="invalid-feedback">{{ getError('kode') }}</div>
                                    <small class="text-muted fs-9">Masukkan 4 digit angka tahun masuk siswa.</small>
                                </div>
                            </template>

                            <!-- Form inputs generik (Jenjang, Jurusan, Mapel, dll) -->
                            <template v-else>
                                <div class="col-12">
                                    <label for="form_generik_kode" class="form-label fw-semibold fs-8 text-muted mb-1">Kode <span class="text-danger">*</span></label>
                                    <input id="form_generik_kode" name="kode" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.kode}" v-model="form.kode" placeholder="Masukkan kode..." required>
                                    <div class="invalid-feedback">{{ getError('kode') }}</div>
                                </div>
                                <div class="col-12">
                                    <label for="form_generik_nama" class="form-label fw-semibold fs-8 text-muted mb-1">Nama Data <span class="text-danger">*</span></label>
                                    <input id="form_generik_nama" name="nama" type="text" class="form-control rounded-3" :class="{'is-invalid': errors.nama}" v-model="form.nama" placeholder="Masukkan nama..." required>
                                    <div class="invalid-feedback">{{ getError('nama') }}</div>
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
                    { id: 'jenjang', name: 'Jenjang', icon: 'bi bi-award' },
                    { id: 'jurusan', name: 'Jurusan', icon: 'bi bi-diagram-3' },
                    { id: 'kelas', name: 'Kelas', icon: 'bi bi-mortarboard' },
                    { id: 'mata_pelajaran', name: 'Mata Pelajaran', icon: 'bi bi-book' },
                    { id: 'pendidikan', name: 'Pendidikan', icon: 'bi bi-award-fill' },
                    { id: 'program_pengajaran', name: 'Program Pengajaran', icon: 'bi bi-journal-text' },
                    { id: 'tahun_ajaran', name: 'Tahun Ajaran', icon: 'bi bi-calendar-check' },
                    { id: 'angkatan', name: 'Angkatan', icon: 'bi bi-calendar2-range' }
                ],
                activeTab: 'jenjang',
                userRole: '<?php echo htmlspecialchars($user_role ?? ""); ?>',
                listTenants: <?php echo json_encode($tenant_list ?? []); ?>,
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
            // Bootstrap terjamin sudah termuat pada event DOMContentLoaded
            this.modalObj = new bootstrap.Modal(document.getElementById('formModal'));
            this.fetchData(1);
            this.fetchAuxiliaryData();
            // listTenants sudah di-inject via PHP (json_encode), tidak perlu fetch API
        },
        methods: {
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
            // Nama sekolah yang sedang difilter (computed via find)
            get selectedTenantName() {
                if (!this.filterTenantId) return '';
                const t = this.listTenants.find(t => t.id === this.filterTenantId);
                return t ? t.nama_sekolah : '';
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

                axios.get('/dapodik-spmb/api/v1/kelembagaan', { params })
                    .then(res => {
                        this.listData    = res.data.data;
                        this.totalPages  = res.data.last_page;
                        this.total       = res.data.total;
                        this.from        = res.data.from;
                        this.to          = res.data.to;
                        this.loading     = false;
                    }).catch(err => {
                        this.loading = false;
                        this.toast.fire({ icon: 'error', title: err.response?.data?.error || 'Gagal memuat data dari server.' });
                    });
            },
            // Ambil opsi relasi untuk modal form Kelas
            fetchAuxiliaryData(tenantId = null) {
                const params = { module: 'jenjang' };
                const params2 = { module: 'jurusan' };
                if (tenantId) {
                    params.tenant_id = tenantId;
                    params2.tenant_id = tenantId;
                }
                
                axios.get('/dapodik-spmb/api/v1/kelembagaan/options', { params })
                     .then(res => this.listJenjang = res.data.data);
                axios.get('/dapodik-spmb/api/v1/kelembagaan/options', { params: params2 })
                     .then(res => this.listJurusan = res.data.data);
            },
            fetchTenants() {
                axios.get('/dapodik-spmb/api/v1/kelembagaan/tenants')
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
                } else {
                    this.form = { kode: '', nama: '' };
                }
                if (this.userRole === 'super_admin') {
                    this.form.tenant_id = '';
                }
            },
            openCreateModal() {
                this.isEditMode = false;
                this.resetForm();
                this.modalObj.show();
            },
            openEditModal(item) {
                this.isEditMode = true;
                this.errors = {};
                this.editId = item.id;
                
                if (this.activeTab === 'kelas') {
                    this.form = {
                        id_jenjang: item.id_jenjang,
                        id_jurusan: item.id_jurusan,
                        kode_kelas: item.kode_kelas,
                        nama_kelas: item.nama_kelas
                    };
                } else if (this.activeTab === 'tahun_ajaran' || this.activeTab === 'angkatan') {
                    this.form = {
                        kode: this.activeTab === 'tahun_ajaran' ? item.tahun_ajaran : item.tahun_angkatan
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
                
                this.modalObj.show();
            },
            submitForm() {
                this.submitLoading = true;
                this.errors = {};
                
                // Siapkan data payload
                const payload = { ...this.form, module: this.activeTab };
                if (this.isEditMode) {
                    payload.id = this.editId;
                }

                axios.post('/dapodik-spmb/api/v1/kelembagaan/simpan', payload)
                     .then(res => {
                         this.submitLoading = false;
                         this.modalObj.hide();
                         this.toast.fire({ icon: 'success', title: res.data.message });
                         this.fetchData(this.isEditMode ? this.currentPage : 1);
                         if (this.activeTab === 'jenjang' || this.activeTab === 'jurusan') {
                             this.fetchAuxiliaryData(this.userRole === 'super_admin' ? this.form.tenant_id : null); // Refresh dropdown options
                         }
                     })
                     .catch(err => {
                         this.submitLoading = false;
                         if (err.response && err.response.status === 422) {
                             this.errors = err.response.data.errors;
                             this.toast.fire({ icon: 'error', title: 'Silakan periksa input form Anda.' });
                         } else {
                             this.toast.fire({ icon: 'error', title: err.response?.data?.error || 'Gagal menyimpan data.' });
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
                        axios.post('/dapodik-spmb/api/v1/kelembagaan/hapus', { module: this.activeTab, id: id })
                             .then(res => {
                                 this.toast.fire({ icon: 'success', title: res.data.message });
                                 this.fetchData(this.currentPage);
                             })
                             .catch(err => {
                                 this.toast.fire({ icon: 'error', title: err.response?.data?.error || 'Gagal menghapus.' });
                             });
                    }
                });
            },
            restoreItem(id) {
                axios.post('/dapodik-spmb/api/v1/kelembagaan/restore', { module: this.activeTab, id: id })
                     .then(res => {
                         this.toast.fire({ icon: 'success', title: res.data.message });
                         this.fetchData(this.currentPage);
                     })
                     .catch(err => {
                         this.toast.fire({ icon: 'error', title: 'Gagal memulihkan data.' });
                     });
            },
            toggleStatus(id) {
                axios.post('/dapodik-spmb/api/v1/kelembagaan/toggle-status', { module: this.activeTab, id: id })
                     .then(res => {
                         this.toast.fire({ icon: 'success', title: res.data.message });
                     })
                     .catch(err => {
                         this.toast.fire({ icon: 'error', title: 'Gagal merubah status keaktifan.' });
                         this.fetchData(this.currentPage); // Reset switch
                     });
            },
            getField(item, type) {
                if (type === 'kode') {
                    return item.kode_jenjang || item.kode_jurusan || item.kode_mapel || item.kode_pendidikan || item.kode_program;
                } else if (type === 'nama') {
                    return item.nama_jenjang || item.nama_jurusan || item.nama_mapel || item.nama_pendidikan || item.nama_program;
                }
                return '';
            },
            getError(field) {
                return this.errors[field] ? this.errors[field][0] : '';
            }
        }
    });
}
</script>
