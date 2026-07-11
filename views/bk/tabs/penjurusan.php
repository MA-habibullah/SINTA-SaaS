<!-- ═══════════════════════════════════════════════════════════
         TAB 2: PENJURUSAN MANDIRI
    ════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'penjurusan'">

        <!-- Alert -->
        <div v-if="alertPenjurusan.msg" :class="'alert alert-' + alertPenjurusan.type + ' border-0 rounded-4 mb-3'" role="alert">
            <i class="bi bi-info-circle me-2"></i>{{ alertPenjurusan.msg }}
            <button type="button" class="btn-close float-end" @click="alertPenjurusan.msg=''"></button>
        </div>

        <!-- Loading -->
        <div v-if="loadingPenjurusan" class="text-center py-5">
            <div class="spinner-border" style="color:var(--bk-primary);"></div>
            <p class="text-muted mt-2 fs-7">Memuat data penjurusan...</p>
        </div>

        <div v-else>
            <!-- Summary Cards Per Jurusan -->
            <div v-if="penjurusanSummary.length > 0" class="row g-3 mb-4">
                <div v-for="s in penjurusanSummary" :key="s.kode_jurusan" class="col-md-4 col-lg-3">
                    <div class="kpi-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge rounded-pill fw-bold px-3" style="background:var(--bk-p-light);color:var(--bk-primary);">
                                {{ s.kode_jurusan }}
                            </span>
                            <span class="fw-bold text-dark fs-5">{{ s.total }}</span>
                        </div>
                        <p class="fw-semibold text-dark fs-7 mb-2" style="line-height:1.3;">{{ s.nama_jurusan }}</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;font-size:.68rem;">
                                {{ s.pending }} Pending
                            </span>
                            <span class="badge rounded-pill" style="background:#d1fae5;color:#065f46;font-size:.68rem;">
                                {{ s.terverifikasi }} Verified
                            </span>
                            <span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:.68rem;">
                                {{ s.ditolak }} Ditolak
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bk-card p-3 mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="input-search-penjurusan" class="form-label fw-semibold fs-8 mb-1">Cari Siswa</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0 bg-white">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 rounded-end-3"
                                   v-model="filterPenjurusan.search"
                                   placeholder="Nama / NISN..."
                                   id="input-search-penjurusan"
                                   name="search_penjurusan">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="select-filter-status" class="form-label fw-semibold fs-8 mb-1">Filter Status</label>
                        <select class="form-select form-select-sm rounded-3" v-model="filterPenjurusan.status"
                                id="select-filter-status" name="status">
                            <option value="">Semua Status</option>
                            <option value="Diajukan">Diajukan</option>
                            <option value="Diverifikasi">Diverifikasi</option>
                            <option value="Ditolak">Ditolak</option>
                            <option value="Override_BK">Override BK</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="select-filter-jurusan" class="form-label fw-semibold fs-8 mb-1">Filter Jurusan</label>
                        <select class="form-select form-select-sm rounded-3" v-model="filterPenjurusan.jurusan_id"
                                id="select-filter-jurusan" name="jurusan_id">
                            <option value="">Semua Jurusan</option>
                            <option v-for="j in jurusanList" :key="j.id" :value="j.id">
                                {{ j.kode_jurusan }} — {{ j.nama_jurusan }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm w-100 rounded-3 fw-semibold"
                                style="background:var(--bk-primary);color:#fff;"
                                @click="loadPenjurusan" id="btn-filter-penjurusan">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bk-card p-0 overflow-hidden">
                <div v-if="penjurusanData.length > 0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" id="tbl-penjurusan">
                            <thead style="background:var(--bk-bg);border-bottom:2px solid var(--bk-border);">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold fs-7 text-muted">Siswa</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Kelas</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Pilihan Jurusan</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Status</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Dikunci</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Diajukan Oleh</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in filteredPenjurusan" :key="p.id">
                                    <td class="ps-4">
                                        <div class="fw-semibold">{{ p.nama_siswa }}</div>
                                        <div class="text-muted fs-8 font-monospace">{{ p.nisn }}</div>
                                    </td>
                                    <td class="fs-7 text-muted">{{ p.nama_kelas || '—' }}</td>
                                    <td>
                                        <span class="fw-semibold" style="color:var(--bk-primary);">{{ p.kode_jurusan }}</span>
                                        <div class="text-muted fs-8">{{ p.nama_jurusan }}</div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill fw-semibold fs-8 px-3 py-1"
                                              :style="statusStyle(p.status)">
                                            {{ p.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <i v-if="p.dikunci == 1" class="bi bi-lock-fill text-warning fs-5" title="Terkunci"></i>
                                        <i v-else class="bi bi-unlock text-muted fs-5" title="Tidak Terkunci"></i>
                                    </td>
                                    <td class="fs-7 text-muted">{{ p.diajukan_oleh }}</td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                                            <!-- Verifikasi -->
                                            <button v-if="p.status === 'Diajukan' && !p.dikunci"
                                                    class="btn btn-xs btn-success rounded-2 fw-semibold"
                                                    style="font-size:.72rem;padding:3px 8px;"
                                                    @click="doVerifikasi(p, 'Verifikasi')"
                                                    :id="'btn-verif-' + p.id">
                                                <i class="bi bi-check-lg"></i> Verifikasi
                                            </button>
                                            <!-- Tolak -->
                                            <button v-if="p.status === 'Diajukan' && !p.dikunci"
                                                    class="btn btn-xs btn-danger rounded-2 fw-semibold"
                                                    style="font-size:.72rem;padding:3px 8px;"
                                                    @click="doVerifikasi(p, 'Tolak')"
                                                    :id="'btn-tolak-' + p.id">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                            <!-- Override -->
                                            <button class="btn btn-xs rounded-2 fw-semibold"
                                                    style="font-size:.72rem;padding:3px 8px;background:var(--bk-primary);color:#fff;"
                                                    @click="openOverride(p)"
                                                    :id="'btn-override-' + p.id">
                                                <i class="bi bi-arrow-repeat"></i> Override
                                            </button>
                                            <!-- Buka/Kunci -->
                                            <button class="btn btn-xs rounded-2 fw-semibold"
                                                    :style="p.dikunci == 1 ? 'background:#fef3c7;color:#92400e;' : 'background:#f0fdf4;color:#166534;'"
                                                    style="font-size:.72rem;padding:3px 8px;"
                                                    @click="doToggleKunci(p)"
                                                    :id="'btn-kunci-' + p.id">
                                                <i :class="p.dikunci == 1 ? 'bi bi-unlock-fill' : 'bi bi-lock-fill'"></i>
                                                {{ p.dikunci == 1 ? 'Buka' : 'Kunci' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="text-center py-5 text-muted">
                    <i class="bi bi-diagram-3 fs-1 d-block mb-2"></i>
                    <p class="mb-0">Belum ada data pilihan penjurusan untuk filter ini.</p>
                    <p class="fs-7">Pastikan siswa sudah mengajukan pilihan jurusan mandiri.</p>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL OVERRIDE ══════════════════════════════════════ -->
        <div v-if="overrideModal.show"
             class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
             style="background:rgba(0,0,0,0.55);z-index:9999;"
             id="modal-override-backdrop">
            <div class="bg-white rounded-4 shadow-lg p-4" style="max-width:520px;width:92%;">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="kpi-icon flex-shrink-0" style="background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Override Pilihan Jurusan</h5>
                        <p class="text-muted fs-7 mb-0">Siswa: <strong>{{ overrideModal.siswa.nama_siswa }}</strong></p>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded-3" style="background:#fef3c7;border:1px solid #fde68a;">
                    <p class="fs-7 fw-semibold mb-1" style="color:#92400e;">⚠️ Peringatan ACID Lock</p>
                    <p class="fs-8 mb-0 text-muted">Override akan mengubah jurusan siswa secara permanen, mengunci pilihan,
                    dan mencatat tindakan di log audit. Tindakan ini tidak bisa dibatalkan tanpa membuka kunci manual.</p>
                </div>

                <div class="mb-3">
                    <label for="input-jurusan-sekarang" class="form-label fw-semibold fs-7">Jurusan Saat Ini</label>
                    <input type="text" class="form-control rounded-3 bg-light" readonly
                           id="input-jurusan-sekarang" name="jurusan_sekarang"
                           :value="overrideModal.siswa.kode_jurusan + ' — ' + overrideModal.siswa.nama_jurusan">
                </div>

                <div class="mb-3">
                    <label for="select-override-jurusan" class="form-label fw-semibold fs-7">Jurusan Tujuan Override <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" v-model="overrideModal.id_jurusan_baru"
                            id="select-override-jurusan" name="id_jurusan_baru">
                        <option value="">-- Pilih Jurusan Tujuan --</option>
                        <option v-for="j in jurusanList" :key="j.id" :value="j.id"
                                :disabled="j.id == overrideModal.siswa.id_jurusan">
                            {{ j.kode_jurusan }} — {{ j.nama_jurusan }}
                        </option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="input-override-catatan" class="form-label fw-semibold fs-7">Alasan Override <span class="text-danger">*</span> <small class="text-muted fw-normal">(wajib untuk audit)</small></label>
                    <textarea class="form-control rounded-3" rows="3"
                              v-model="overrideModal.catatan_bk"
                              placeholder="Tuliskan alasan resmi penggantian jurusan (contoh: hasil tes psikologi, kapasitas penuh, rekomendasi BK)..."
                              id="input-override-catatan" name="catatan_bk"></textarea>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary rounded-3" style="color:#334155; border-color:#94a3b8;"
                            @click="overrideModal.show = false" id="btn-batal-override">
                        Batal
                    </button>
                    <button class="btn rounded-3 fw-semibold"
                            style="background:var(--bk-primary);color:#fff;"
                            :disabled="loadingOverride"
                            @click="submitOverride" id="btn-konfirmasi-override">
                        <span v-if="loadingOverride" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-arrow-repeat me-2"></i>
                        {{ loadingOverride ? 'Memproses...' : 'Konfirmasi Override' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
