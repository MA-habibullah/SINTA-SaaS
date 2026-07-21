
<div id="keuangan-generate-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-magic text-blue-600 me-2"></i> Penerbitan & Daftar Tagihan SPP
            </h2>
            <p class="text-muted mb-0">Kelola penerbitan tagihan secara massal dan pantau daftar tagihan terbit siswa secara terpadu.</p>
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
                Mengelola data penagihan terpusat di seluruh tenant sekolah SINTA-SaaS.
            </div>
        </div>
    </div>

    <!-- Alert Feedback -->
    <div v-if="successMsg" class="alert alert-success border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm">
        <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
        <div class="fw-semibold text-success-800">{{ successMsg }}</div>
    </div>
    <div v-if="errorMsg" class="alert alert-danger border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
        <div class="fw-semibold text-danger-800">{{ errorMsg }}</div>
    </div>

    <!-- Nav Tabs Minimalis -->
    <ul class="nav nav-tabs border-bottom mb-4" id="generateTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate-pane" type="button" role="tab">
                <i class="bi bi-magic me-2"></i>Terbitkan Tagihan (Generate)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tagihan-tab" data-bs-toggle="tab" data-bs-target="#tagihan-pane" type="button" role="tab" @click="fetchDaftarTagihan">
                <i class="bi bi-file-earmark-text me-2"></i>Daftar Tagihan Siswa
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="generateTabsContent">
        <!-- Tab 1: Generate Pane -->
        <div class="tab-pane fade show active" id="generate-pane" role="tabpanel">
            <div class="row">
                <!-- Form Generate (Left: 5-cols) -->
                <div class="col-12 col-lg-5 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Konfigurasi Generate Tagihan</h5>
                        
                        <form @submit.prevent="generateTagihan" class="d-flex flex-column gap-3">
                            <!-- Komponen Biaya -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Komponen Biaya <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="form.komponen_id" @change="onKomponenChange" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Komponen Biaya --</option>
                                    <option v-for="k in komponenList" :value="k.id" :disabled="k.is_active == 0">{{ k.nama_komponen }} ({{ k.tipe_periode }}) {{ k.is_active == 0 ? '(Non-Aktif)' : '' }}</option>
                                </select>
                            </div>

                            <div class="row g-3">
                                <!-- Tahun Ajaran -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-slate-700">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <select class="form-select border-slate-200" v-model="form.tahun_ajaran_id" required style="height: 42px;">
                                        <option value="" disabled>-- Pilih Tahun Ajaran --</option>
                                        <option v-for="ta in listTa" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                                    </select>
                                </div>

                                <!-- Bulan (jika bulanan) -->
                                <div class="col-md-6" v-if="isBulanan">
                                    <label class="form-label fw-semibold text-slate-700">Bulan Tagihan <span class="text-danger">*</span></label>
                                    <select class="form-select border-slate-200" v-model="form.bulan" required style="height: 42px;">
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
                                        <input class="form-check-input" type="radio" name="targetDist" id="targetAll" value="all" v-model="targetType" @change="resetTargets">
                                        <label class="form-check-label text-slate-700 fw-medium" for="targetAll">Semua Kelas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="targetDist" id="targetKelas" value="kelas" v-model="targetType" @change="resetTargets">
                                        <label class="form-check-label text-slate-700 fw-medium" for="targetKelas">Per Kelas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="targetDist" id="targetJenjang" value="jenjang" v-model="targetType" @change="resetTargets">
                                        <label class="form-check-label text-slate-700 fw-medium" for="targetJenjang">Per Jenjang</label>
                                    </div>
                                </div>

                                <!-- Dropdown kelas/jenjang -->
                                <div class="mb-3" v-if="targetType === 'kelas'">
                                    <label class="form-label fw-semibold text-slate-700">Pilih Kelas Sasaran <span class="text-danger">*</span></label>
                                    <select class="form-select border-slate-200" v-model="form.kelas_id" required style="height: 42px;">
                                        <option value="" disabled>-- Pilih Kelas --</option>
                                        <option v-for="c in listKelas" :value="c.id">{{ c.nama_kelas }}</option>
                                    </select>
                                </div>

                                <div class="mb-3" v-if="targetType === 'jenjang'">
                                    <label class="form-label fw-semibold text-slate-700">Pilih Jenjang Sasaran <span class="text-danger">*</span></label>
                                    <select class="form-select border-slate-200" v-model="form.jenjang_id" required style="height: 42px;">
                                        <option value="" disabled>-- Pilih Jenjang --</option>
                                        <option v-for="j in listJenjang" :value="j.id">{{ j.nama_jenjang }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-3">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold w-100 py-3" :disabled="loading || selectedSiswaIds.length === 0">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
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
                                        <td class="text-end fw-bold text-slate-800">Rp {{ formatNumber(p.nominal_akhir) }}</td>
                                        <td class="text-center">
                                            <span v-if="p.sudah_ada" class="badge bg-warning text-dark">Sudah Terbit</span>
                                            <span v-else class="badge bg-success">Siap Terbit</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div v-if="previewList.length > 0 && !loadingPreview" class="pt-3 border-top mt-3 text-muted fs-8 d-flex justify-content-between align-items-center">
                            <span>Total Siswa: <strong>{{ previewList.length }}</strong></span>
                            <span>Siap Diterbitkan: <strong class="text-success">{{ eligibleCount }}</strong></span>
                            <span>Terpilih: <strong class="text-primary">{{ selectedSiswaIds.length }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Daftar Tagihan Pane -->
        <div class="tab-pane fade" id="tagihan-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Daftar Tagihan Siswa Terbit</h5>

                <!-- Filter Controls -->
                <div class="row g-2 mb-4 align-items-end">
                    <!-- Search Input -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold text-slate-600 fs-8 mb-1">Cari Nama / NISN</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-slate-200" v-model="filterList.q" @input="onSearchInput" placeholder="Ketik nama..." style="height: 38px;">
                        </div>
                    </div>

                    <!-- Filter Kelas -->
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold text-slate-600 fs-8 mb-1">Kelas</label>
                        <select class="form-select border-slate-200" v-model="filterList.kelas_id" @change="fetchDaftarTagihan" style="height: 38px;">
                            <option value="">-- Semua Kelas --</option>
                            <option v-for="c in listKelas" :key="c.id" :value="c.id">{{ c.nama_kelas }}</option>
                        </select>
                    </div>

                    <!-- Filter Tahun Ajaran -->
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold text-slate-600 fs-8 mb-1">Tahun Ajaran</label>
                        <select class="form-select border-slate-200" v-model="filterList.tahun_ajaran_id" @change="fetchDaftarTagihan" style="height: 38px;">
                            <option value="">-- Semua TA --</option>
                            <option v-for="ta in listTa" :key="ta.id" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                        </select>
                    </div>

                    <!-- Filter Komponen -->
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold text-slate-600 fs-8 mb-1">Komponen Biaya</label>
                        <select class="form-select border-slate-200" v-model="filterList.komponen_id" @change="fetchDaftarTagihan" style="height: 38px;">
                            <option value="">-- Semua Komponen --</option>
                            <option v-for="k in komponenList" :key="k.id" :value="k.id">{{ k.nama_komponen }}</option>
                        </select>
                    </div>

                    <!-- Filter Status -->
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold text-slate-600 fs-8 mb-1">Status Lunas</label>
                        <select class="form-select border-slate-200" v-model="filterList.status_lunas" @change="fetchDaftarTagihan" style="height: 38px;">
                            <option value="">-- Semua Status --</option>
                            <option value="Belum">Belum Lunas</option>
                            <option value="Cicil">Cicil</option>
                            <option value="Lunas">Lunas</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 col-md-2 d-flex align-items-end gap-1">
                        <button class="btn btn-outline-secondary fs-8 border-slate-200 d-flex align-items-center justify-content-center" @click="resetFilters" style="height: 38px; width: 45px;" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise fs-6"></i>
                        </button>
                        <button class="btn btn-success flex-fill fs-8 fw-semibold d-flex align-items-center justify-content-center" :disabled="!hasFilterApplied" @click="downloadExcel" style="height: 38px;" title="Unduh Laporan Excel Pivot">
                            <i class="bi bi-file-earmark-excel fs-6 me-1"></i> Excel
                        </button>
                    </div>
                </div>

                <!-- Case: No filter applied yet -->
                <div v-if="!hasFilterApplied" class="text-center py-5 text-muted bg-light rounded-3 my-3">
                    <div class="fs-1 mb-2 text-secondary opacity-50">
                        <i class="bi bi-funnel"></i>
                    </div>
                    <h6 class="fw-bold text-slate-700 mb-1">Pencarian Laporan Tagihan</h6>
                    <p class="fs-9 mb-0 px-3">Silakan pilih atau ketik minimal satu kriteria filter di atas terlebih dahulu untuk menampilkan data tagihan.</p>
                </div>

                <template v-else>
                    <!-- Table Loading -->
                    <div v-if="loadingList" class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2">Memuat daftar tagihan...</div>
                    </div>

                    <!-- Table Empty State -->
                    <div v-else-if="tagihanList.length === 0" class="text-center py-5 text-muted">
                        <i class="bi bi-file-earmark-lock fs-1 text-slate-300 d-block mb-3"></i>
                        <div>Tidak ada tagihan siswa terbit yang cocok dengan kriteria pencarian Anda.</div>
                    </div>

                <!-- Tagihan Table -->
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th v-if="isSuperAdmin">Sekolah</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Komponen</th>
                                <th>Periode / Bulan</th>
                                <th class="text-end">Nominal Tagihan</th>
                                <th class="text-end">Telah Dibayar</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in tagihanList" :key="t.id">
                                <td v-if="isSuperAdmin" class="text-muted fs-8">{{ t.nama_sekolah }}</td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ t.nama_siswa }}</div>
                                    <div class="text-muted fs-9">NISN: {{ t.nisn }}</div>
                                </td>
                                <td>{{ t.nama_kelas }}</td>
                                <td><span class="fw-semibold text-slate-700">{{ t.nama_komponen }}</span></td>
                                <td>
                                    <span v-if="t.bulan">{{ getBulanName(t.bulan) }} ({{ t.tahun_ajaran }})</span>
                                    <span v-else class="text-muted">{{ t.tahun_ajaran }}</span>
                                </td>
                                <td class="text-end fw-bold text-slate-800">Rp {{ formatNumber(t.nominal_tagihan) }}</td>
                                <td class="text-end text-slate-600">Rp {{ formatNumber(t.nominal_bayar) }}</td>
                                <td class="text-center">
                                    <span class="badge" :class="getStatusBadgeClass(t.status_lunas)">{{ t.status_lunas }}</span>
                                </td>
                                <td class="text-center">
                                    <!-- Edit Nominal Button -->
                                    <button @click="openEditModal(t)" class="btn btn-link text-primary p-0 me-3" title="Edit Nominal Tagihan" :disabled="t.status_lunas === 'Lunas'">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </button>
                                    <!-- Delete Button -->
                                    <button @click="deleteTagihan(t)" class="btn btn-link text-danger p-0" title="Hapus Tagihan" :disabled="t.nominal_bayar > 0">
                                        <i class="bi bi-trash fs-5"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tagihanTotalRows > 0" class="d-flex flex-wrap align-items-center justify-content-between pt-4 gap-2">
                    <span class="fs-8 text-slate-500">
                        Menampilkan Halaman {{ tagihanPage }} dari {{ tagihanTotalPages }} (Total {{ tagihanTotalRows }} Tagihan)
                    </span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: tagihanPage === 1 }">
                                <a class="page-link" href="#" @click.prevent="setListPage(tagihanPage - 1)">Sebelumnya</a>
                            </li>
                            <li v-for="p in visibleTagihanPages" :key="p" class="page-item" :class="{ active: p === tagihanPage, disabled: p === '...' }">
                                <a class="page-link" href="#" @click.prevent="p !== '...' && setListPage(p)">{{ p }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: tagihanPage === tagihanTotalPages }">
                                <a class="page-link" href="#" @click.prevent="setListPage(tagihanPage + 1)">Berikutnya</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Edit Nominal Modal -->
    <div class="modal fade" id="editNominalModal" tabindex="-1" aria-labelledby="editNominalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom pb-2">
                    <h5 class="modal-title fw-bold text-slate-800" id="editNominalModalLabel"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Nominal Tagihan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="saveNominalTagihan">
                    <div class="modal-body py-3 d-flex flex-column gap-3">
                        <div>
                            <label class="form-label text-slate-600 fs-8 mb-1">Nama Siswa</label>
                            <input type="text" class="form-control bg-light border-0" :value="editNominalForm.nama_siswa" readonly>
                        </div>
                        <div>
                            <label class="form-label text-slate-600 fs-8 mb-1">Komponen Biaya</label>
                            <input type="text" class="form-control bg-light border-0" :value="editNominalForm.nama_komponen" readonly>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label text-slate-600 fs-8 mb-1">Sudah Dibayar</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">Rp</span>
                                    <input type="text" class="form-control bg-light border-0 text-end" :value="formatNumber(editNominalForm.nominal_bayar)" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Nominal Tagihan Baru <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text border-slate-200">Rp</span>
                                    <input type="number" class="form-control border-slate-200 text-end" v-model="editNominalForm.nominal_tagihan" required :min="editNominalForm.nominal_bayar" placeholder="0">
                                </div>
                                <div class="text-muted fs-9 mt-1">Minimal sama dengan total bayar (Rp {{ formatNumber(editNominalForm.nominal_bayar) }})</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top pt-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary fw-bold" :disabled="loadingEdit">
                            <span v-if="loadingEdit" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Injections -->
<script id="data-kelas" type="application/json">
    <?php echo json_encode($list_kelas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-jenjang" type="application/json">
    <?php echo json_encode($list_jenjang, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-ta" type="application/json">
    <?php echo json_encode($list_ta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="user-session" type="application/json">
    <?php echo json_encode([
        'is_super_admin' => (($_SESSION['role_name'] ?? '') === 'super_admin'),
        'tenant_id' => ($_SESSION['tenant_id'] ?? '')
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.775rem; }
.fs-9 { font-size: 0.7rem; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }

.nav-tabs {
    border-bottom: 1px solid #e2e8f0 !important;
}
.nav-tabs .nav-item {
    margin-bottom: -1px;
}
.nav-tabs .nav-link {
    border: none !important;
    border-bottom: 2px solid transparent !important;
    color: #64748b !important;
    font-weight: 600 !important;
    background: transparent !important;
    padding: 0.8rem 1.2rem !important;
    font-size: 0.85rem !important;
    transition: all 0.15s ease-in-out;
}
.nav-tabs .nav-link:hover {
    color: #1e293b !important;
    border-bottom-color: #cbd5e1 !important;
}
.nav-tabs .nav-link.active {
    color: #2563eb !important;
    border-bottom-color: #2563eb !important;
    background: transparent !important;
}

.table th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 2px solid #e2e8f0 !important;
}
</style>

<script>
window.VueAppRegistry.register('#keuangan-generate-app', {
    setup() {
        const session = JSON.parse(document.getElementById('user-session').textContent || '{}');
        const isSuperAdmin = session.is_super_admin;
        const tenantsList = Vue.ref([]);
        const selectedTenantId = Vue.ref(session.tenant_id || '');

        const listKelas = Vue.ref([]);
        const listJenjang = Vue.ref([]);
        const listTa = Vue.ref([]);
        const komponenList = Vue.ref([]);
        const selectedSiswaIds = Vue.ref([]);

        const loading = Vue.ref(false);
        const successMsg = Vue.ref('');
        const errorMsg = Vue.ref('');

        const isBulanan = Vue.ref(false);
        const targetType = Vue.ref('all');

        const form = Vue.ref({
            komponen_id: '',
            tahun_ajaran_id: '',
            bulan: '',
            kelas_id: '',
            jenjang_id: ''
        });

        // Tab 1: Preview States
        const previewList = Vue.ref([]);
        const loadingPreview = Vue.ref(false);

        // Tab 2: Tagihan List & Filters
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
            tenant_id: session.tenant_id || ''
        });

        // Tab 2: Edit nominal state
        const editNominalForm = Vue.ref({
            id: '',
            nama_siswa: '',
            nama_komponen: '',
            nominal_tagihan: 0,
            nominal_bayar: 0
        });
        const loadingEdit = Vue.ref(false);
        let editModal = null;

        // Search debounce
        let searchTimeout = null;

        const getQueryParam = () => {
            return isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
        };

        const fetchTenants = async () => {
            if (!isSuperAdmin) return;
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/tenants');
                const res = await response.json();
                if (res.success) {
                    tenantsList.value = res.data;
                    const cached = localStorage.getItem('sinta_spp_selected_tenant_id');
                    if (cached === '') {
                        selectedTenantId.value = '';
                    } else if (cached && tenantsList.value.some(t => t.id === cached)) {
                        selectedTenantId.value = cached;
                    } else {
                        selectedTenantId.value = '';
                        localStorage.setItem('sinta_spp_selected_tenant_id', '');
                    }
                    filterList.value.tenant_id = selectedTenantId.value;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchKomponen = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/komponen' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    komponenList.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchTahunAjaran = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/tahun-ajaran' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    listTa.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchKelas = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/kelas' + getQueryParam());
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
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/jenjang' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    listJenjang.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const onTenantChange = () => {
            localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
            filterList.value.tenant_id = selectedTenantId.value;
            fetchKomponen();
            fetchTahunAjaran();
            fetchKelas();
            fetchJenjang();
            form.value.komponen_id = '';
            resetTargets();
            fetchDaftarTagihan();
        };

        const onKomponenChange = () => {
            const selected = komponenList.value.find(k => k.id == form.value.komponen_id);
            if (selected && selected.tipe_periode === 'Bulanan') {
                isBulanan.value = true;
                form.value.bulan = '';
            } else {
                isBulanan.value = false;
                form.value.bulan = null;
            }
        };

        const resetTargets = () => {
            form.value.kelas_id = '';
            form.value.jenjang_id = '';
        };

        // Fetch Preview target siswa
        const fetchPreview = async () => {
            if (!form.value.komponen_id || !form.value.tahun_ajaran_id) {
                previewList.value = [];
                return;
            }
            loadingPreview.value = true;
            errorMsg.value = '';
            try {
                const tenantParam = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                const query = `komponen_id=${form.value.komponen_id}&tahun_ajaran_id=${form.value.tahun_ajaran_id}` +
                              `&kelas_id=${form.value.kelas_id || ''}&jenjang_id=${form.value.jenjang_id || ''}` +
                              `&bulan=${form.value.bulan || ''}${tenantParam}`;
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/preview-generate?${query}`);
                const res = await response.json();
                if (res.success) {
                    previewList.value = res.data;
                } else {
                    previewList.value = [];
                    errorMsg.value = res.error || 'Gagal memuat pratinjau siswa.';
                }
            } catch (err) {
                console.error(err);
                errorMsg.value = 'Terjadi kesalahan jaringan saat memuat pratinjau.';
            } finally {
                loadingPreview.value = false;
            }
        };

        // Watch form fields to trigger preview fetch
        Vue.watch(
            () => [form.value.komponen_id, form.value.tahun_ajaran_id, form.value.kelas_id, form.value.jenjang_id, form.value.bulan, targetType.value, selectedTenantId.value],
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
            loading.value = true;
            successMsg.value = '';
            errorMsg.value = '';
            try {
                const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                const payload = {
                    ...form.value,
                    siswa_ids: selectedSiswaIds.value
                };
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/generate-tagihan?${tenantSuffix}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                if (res.success) {
                    successMsg.value = `Berhasil menerbitkan ${res.count} tagihan untuk target siswa terpilih!`;
                    fetchPreview();
                    resetTargets();
                } else {
                    errorMsg.value = res.error || 'Gagal menerbitkan tagihan.';
                }
            } catch (err) {
                errorMsg.value = 'Terjadi kesalahan jaringan.';
            } finally {
                loading.value = false;
            }
        };

        const hasFilterApplied = Vue.computed(() => {
            const f = filterList.value;
            return !!(f.q || f.kelas_id || f.tahun_ajaran_id || f.komponen_id || f.status_lunas);
        });

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

            window.location.href = '/SINTA-SaaS/api/v1/keuangan/export-tagihan-excel?' + query.toString();
        };

        // Tab 2: Fetch paginated & filtered invoices
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
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/daftar-tagihan?${query}`);
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

        const onSearchInput = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
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

        // Edit Nominal Modal Actions
        const openEditModal = (item) => {
            editNominalForm.value = {
                id: item.id,
                nama_siswa: item.nama_siswa,
                nama_komponen: item.nama_komponen,
                nominal_tagihan: item.nominal_tagihan,
                nominal_bayar: item.nominal_bayar
            };
            if (!editModal) {
                editModal = new bootstrap.Modal(document.getElementById('editNominalModal'));
            }
            editModal.show();
        };

        const saveNominalTagihan = async () => {
            loadingEdit.value = true;
            try {
                const tenantParam = isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/edit-tagihan-nominal' + tenantParam, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: editNominalForm.value.id,
                        nominal_tagihan: editNominalForm.value.nominal_tagihan
                    })
                });
                const res = await response.json();
                if (res.success) {
                    editModal.hide();
                    fetchDaftarTagihan();
                    successMsg.value = 'Nominal tagihan berhasil diperbarui.';
                } else {
                    alert(res.error || 'Gagal mengubah nominal tagihan.');
                }
            } catch (err) {
                console.error(err);
            } finally {
                loadingEdit.value = false;
            }
        };

        const deleteTagihan = async (item) => {
            if (item.nominal_bayar > 0) {
                alert('Tagihan sudah dibayar sebagian/lunas and tidak dapat dihapus.');
                return;
            }
            if (!confirm(`Apakah Anda yakin ingin menghapus tagihan ${item.nama_komponen} untuk siswa ${item.nama_siswa}?`)) return;
            try {
                const tenantParam = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/hapus-tagihan?id=${item.id}${tenantParam}`, {
                    method: 'DELETE'
                });
                const res = await response.json();
                if (res.success) {
                    fetchDaftarTagihan();
                    successMsg.value = 'Tagihan berhasil dihapus.';
                } else {
                    alert(res.error || 'Gagal menghapus tagihan.');
                }
            } catch (err) {
                console.error(err);
            }
        };

        // Sliding Window pagination helper
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

        const visibleTagihanPages = Vue.computed(() => {
            return getVisiblePages(tagihanPage.value, tagihanTotalPages.value);
        });

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num);
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

        Vue.onMounted(async () => {
            if (isSuperAdmin) {
                await fetchTenants();
                const cached = localStorage.getItem('sinta_spp_selected_tenant_id');
                if (cached === '') {
                    selectedTenantId.value = '';
                } else if (cached && tenantsList.value.some(t => t.id === cached)) {
                    selectedTenantId.value = cached;
                } else {
                    selectedTenantId.value = '';
                    localStorage.setItem('sinta_spp_selected_tenant_id', '');
                }
                filterList.value.tenant_id = selectedTenantId.value;
            }
            await fetchKomponen();
            await fetchTahunAjaran();
            await fetchKelas();
            await fetchJenjang();
            // Select active TA by default
            const activeTa = listTa.value.find(ta => ta.status === 'Aktif');
            if (activeTa) {
                form.value.tahun_ajaran_id = activeTa.id;
            }
        });

        return {
            isSuperAdmin,
            tenantsList,
            selectedTenantId,
            listKelas,
            listJenjang,
            listTa,
            komponenList,
            loading,
            successMsg,
            errorMsg,
            isBulanan,
            targetType,
            form,
            onTenantChange,
            onKomponenChange,
            resetTargets,
            generateTagihan,
            previewList,
            loadingPreview,
            eligibleCount,
            selectedSiswaIds,
            isAllSelected,
            toggleSelectAll,
            tagihanList,
            loadingList,
            tagihanPage,
            tagihanTotalPages,
            tagihanTotalRows,
            filterList,
            hasFilterApplied,
            downloadExcel,
            fetchDaftarTagihan,
            onSearchInput,
            setListPage,
            resetFilters,
            openEditModal,
            saveNominalTagihan,
            deleteTagihan,
            editNominalForm,
            loadingEdit,
            visibleTagihanPages,
            formatNumber,
            getStatusBadgeClass,
            getBulanName
        };
    }
});
</script>
