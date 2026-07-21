
<div id="keuangan-kasir-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-cash-stack text-blue-600 me-2"></i> Loket Kasir Pembayaran
            </h2>
            <p class="text-muted mb-0">Loket kasir Tata Usaha untuk pencarian siswa, transaksi pembayaran parsial/cicil, dan cetak kuitansi.</p>
        </div>
    </div>

    <!-- Tenant Selector Card (Super Admin Only) -->
    <div v-if="isSuperAdmin" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="row align-items-center">
            <div class="col-md-6">
                <label class="form-label fw-bold text-slate-700"><i class="bi bi-building-gear text-blue-600 me-2"></i> Pilih Sekolah (Tenant)</label>
                <select class="form-select border-slate-200" v-model="selectedTenantId" @change="onTenantChange" style="height: 44px;">
                    <option v-for="t in tenantsList" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                </select>
            </div>
            <div class="col-md-6 mt-3 mt-md-0 text-md-end text-muted fs-7">
                Melakukan transaksi kasir langsung atas nama siswa pada sekolah target terpilih.
            </div>
        </div>
    </div>

    <!-- Pencarian Siswa Utama -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="row g-3 align-items-end">
            <!-- Filter Jenjang -->
            <div class="col-md-2">
                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Filter Jenjang</label>
                <select class="form-select border-slate-200" v-model="selectedJenjangId" @change="onJenjangChange" style="height: 42px;">
                    <option value="">-- Semua Jenjang --</option>
                    <option v-for="j in listJenjang" :key="j.id" :value="j.id">{{ j.nama_jenjang }}</option>
                </select>
            </div>

            <!-- Filter Kelas -->
            <div class="col-md-3">
                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Filter Kelas</label>
                <select class="form-select border-slate-200" v-model="selectedKelasId" @change="onKelasChange" style="height: 42px;">
                    <option value="">-- Semua Kelas --</option>
                    <option v-for="k in listKelas" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                </select>
            </div>

            <!-- Cari Nama/NISN -->
            <div class="col-md-4 position-relative">
                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Cari Nama Siswa atau NISN <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-slate-200"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-slate-200" v-model="siswaSearch" @input="searchSiswa" placeholder="Ketik nama / NISN..." style="height: 42px;">
                </div>
                <!-- Suggestions / Daftar Siswa per Kelas -->
                <ul class="dropdown-menu show w-100 shadow border-slate-200 p-0" v-if="siswaSuggestions.length > 0" style="display: block; max-height: 320px; overflow-y: auto; z-index: 1050;">
                    <li class="bg-light px-3 py-1.5 border-bottom text-muted fw-bold fs-9 d-flex justify-content-between align-items-center" style="position: sticky; top: 0; z-index: 10;">
                        <span>PILIH SISWA ({{ siswaSuggestions.length }} SISWA)</span>
                        <span v-if="selectedKelasId" class="badge bg-blue-100 text-blue-700">Tersaring Kelas</span>
                    </li>
                    <li v-for="s in siswaSuggestions" :key="s.id">
                        <a href="#" class="dropdown-item py-2 px-3 d-flex justify-content-between align-items-center" @click.prevent="selectSiswa(s)">
                            <div>
                                <div class="fw-bold text-slate-800 fs-8">{{ s.nama }}</div>
                                <small class="text-muted fs-9">NISN: {{ s.nisn }} | Kelas: {{ s.nama_kelas }}</small>
                            </div>
                            <i class="bi bi-arrow-right-circle text-blue-600 fs-6"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Info Siswa Terpilih -->
            <div class="col-md-3" v-if="selectedSiswa">
                <div class="p-2.5 bg-blue-50 border border-blue-100 rounded-3 d-flex align-items-center" style="height: 42px;">
                    <div class="me-2.5 p-1 bg-white rounded-circle text-blue-600 shadow-sm d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <i class="bi bi-person-fill fs-6"></i>
                    </div>
                    <div class="overflow-hidden me-2">
                        <h5 class="fw-bold text-slate-800 mb-0 fs-9 text-truncate" style="line-height: 1.2;">{{ selectedSiswa.nama }}</h5>
                        <p class="text-muted mb-0 text-truncate" style="font-size: 0.65rem;">NISN: {{ selectedSiswa.nisn }} | <span class="badge bg-blue-100 text-blue-700 px-1.5 py-0.5" style="font-size: 0.6rem;">{{ selectedSiswa.nama_kelas }}</span></p>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-auto border-0 p-0" @click="clearSelectedSiswa" title="Batalkan Pilihan"><i class="bi bi-x-circle fs-5"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pembayaran Area -->
    <div class="row" v-if="selectedSiswa">
        <!-- List Tagihan Siswa (Left: 8-cols) -->
        <div class="col-12 col-lg-8 mb-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                <!-- Notifikasi Rekomendasi Cross-Selling Tagihan Tertunda -->
                <div v-if="hasTunggakanLain" class="alert alert-warning border-0 rounded-3 d-flex align-items-center mb-4 p-3 shadow-sm">
                    <i class="bi bi-exclamation-circle-fill me-3 fs-3 text-warning"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Rekomendasi Pelunasan!</h6>
                        <p class="mb-0 fs-7">Siswa memiliki tunggakan periode sebelumnya. Silakan centang tunggakan tersebut untuk dilunasi sekalian.</p>
                    </div>
                </div>

                <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-list-task me-2 text-primary"></i> Daftar Kewajiban Pembayaran</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap" style="min-width: 950px;">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">Pilih</th>
                                <th>Komponen Tagihan</th>
                                <th>Tahun Ajaran</th>
                                <th>Kelas</th>
                                <th>Bulan</th>
                                <th class="text-end">Nominal Awal</th>
                                <th class="text-end">Sisa Kekurangan</th>
                                <th class="text-center">Status</th>
                                <th style="width: 150px;" class="text-end">Bayar (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(group, ta) in groupedTagihan" :key="ta">
                                <!-- Group Header: Tahun Ajaran -->
                                <tr class="bg-slate-100 border-bottom border-top border-slate-200">
                                    <td colspan="9" class="fw-bold text-slate-800 py-2 px-3 bg-light fs-8">
                                        <i class="bi bi-calendar-range text-primary me-2"></i> Tahun Ajaran {{ ta }}
                                    </td>
                                </tr>
                                <template v-for="(subGroup, komp) in group" :key="komp">
                                    <!-- Group Header: Komponen Tagihan -->
                                    <tr class="border-bottom border-blue-100">
                                        <td colspan="9" class="fw-semibold text-blue-800 py-1.5 px-4 bg-blue-50 fs-8">
                                            <i class="bi bi-bookmark-fill me-1.5 text-blue-600"></i> Komponen: {{ komp }}
                                        </td>
                                    </tr>
                                    <!-- Rows for this group -->
                                    <tr v-for="t in subGroup" :key="t.id" :class="{'table-info-light': t.selected, 'opacity-75 bg-light': t.status_lunas === 'Lunas'}">
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox" v-model="t.selected" :disabled="t.status_lunas === 'Lunas' || (t.nominal_tagihan - t.nominal_bayar) <= 0" @change="toggleSelectTagihan(t)">
                                        </td>
                                        <td class="fw-bold text-slate-800 ps-4">{{ t.nama_komponen }}</td>
                                        <td>{{ t.tahun_ajaran }}</td>
                                        <td>
                                            <span class="badge bg-light text-slate-700 border px-2 py-0.5 fs-9 fw-semibold">
                                                {{ t.nama_kelas_history }}
                                            </span>
                                        </td>
                                        <td>
                                            <span v-if="t.bulan" class="fw-semibold text-slate-700">{{ getBulanName(t.bulan) }}</span>
                                            <span class="text-muted" v-else>-</span>
                                        </td>
                                        <td class="text-end">Rp {{ formatNumber(t.nominal_tagihan) }}</td>
                                        <td class="text-end fw-semibold" :class="t.status_lunas === 'Lunas' ? 'text-success' : 'text-danger'">
                                            Rp {{ formatNumber(t.nominal_tagihan - t.nominal_bayar) }}
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <span class="badge" :class="getStatusBadgeClass(t.status_lunas)">
                                                    {{ t.status_lunas === 'Belum' ? 'Belum Lunas' : t.status_lunas }}
                                                </span>
                                                <button v-if="t.nominal_bayar > 0" class="btn btn-sm btn-outline-primary py-0 px-1.5 border-slate-200 d-inline-flex align-items-center gap-1" @click="reprintKwitansi(t)" title="Cetak Ulang Bukti Pembayaran" style="font-size: 0.68rem; height: 22px;">
                                                    <i class="bi bi-printer fs-9"></i> Cetak Ulang
                                                </button>
                                                <button v-if="t.nominal_bayar > 0" class="btn btn-sm btn-outline-danger py-0 px-1.5 border-slate-200 d-inline-flex align-items-center gap-1" @click="confirmBatalPembayaran(t)" title="Batalkan Transaksi Pembayaran" style="font-size: 0.68rem; height: 22px;">
                                                    <i class="bi bi-arrow-counterclockwise fs-9"></i> Batal
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control border-slate-200 text-end py-1 px-2 ms-auto" style="height: 34px; min-width: 120px;" v-model.number="t.bayar_input" :max="t.nominal_tagihan - t.nominal_bayar" :disabled="!t.selected || t.status_lunas === 'Lunas' || (t.nominal_tagihan - t.nominal_bayar) <= 0" @input="updateTotal">
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-if="tagihanList.length === 0">
                                <td colspan="9" class="text-center py-4 text-muted">Seluruh tagihan sudah lunas atau belum ada tagihan terbit.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Checkout Card (Right: 4-cols) -->
        <div class="col-12 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2"><i class="bi bi-cart3 me-2 text-success"></i> Ringkasan Pembayaran</h5>
                
                <div class="mb-4 bg-light p-3 rounded-3">
                    <div class="d-flex justify-content-between mb-0">
                        <span class="text-muted fw-semibold" style="font-size: 0.8rem;">Total Tagihan Terpilih</span>
                        <span class="fw-bold text-slate-800 fs-5">Rp {{ formatNumber(totalBelanja) }}</span>
                    </div>
                </div>

                <form @submit.prevent="checkoutPembayaran" class="d-flex flex-column gap-3">
                    <!-- Metode Pembayaran -->
                    <div>
                        <label class="form-label fw-semibold text-slate-700">Metode Pembayaran</label>
                        <select class="form-select border-slate-200" v-model="checkoutForm.metode_pembayaran" style="height: 42px;">
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer">Transfer Manual</option>
                        </select>
                    </div>

                    <!-- Input Uang (Tunai) -->
                    <div v-if="checkoutForm.metode_pembayaran === 'Tunai'">
                        <label class="form-label fw-semibold text-slate-700">Jumlah Uang Diterima (Bayar)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200 fw-bold">Rp</span>
                            <input type="number" class="form-control border-slate-200" v-model.number="cashReceived" placeholder="0" @input="calculateKembalian" style="height: 42px;">
                        </div>
                    </div>

                    <!-- Kembalian -->
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center" v-if="checkoutForm.metode_pembayaran === 'Tunai'">
                        <span class="text-muted fs-7 fw-semibold">Uang Kembalian</span>
                        <span class="fw-bold text-success fs-5">Rp {{ formatNumber(changeAmount) }}</span>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="form-label fw-semibold text-slate-700">Keterangan / Memo Pembayaran</label>
                        <textarea class="form-control border-slate-200" v-model="checkoutForm.keterangan" rows="3" placeholder="Masukkan catatan opsional..."></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-success btn-lg fw-bold w-100 py-3" :disabled="loadingCheckout || totalBelanja <= 0">
                            <span v-if="loadingCheckout" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Bayar & Cetak Kwitansi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Kwitansi Print View -->
    <div v-if="showKwitansiModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;" @click.self="closeKwitansiModal">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-printer-fill text-blue-600 me-2"></i> Pratinjau Kuitansi Digital</h5>
                    <button type="button" class="btn-close" @click="closeKwitansiModal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="printArea" class="border p-4 bg-white rounded-3 shadow-sm" style="font-family: 'Courier New', monospace;">
                        <div class="text-center border-bottom pb-3 mb-3">
                            <h4 class="fw-bold mb-1 text-uppercase">{{ printData.nama_sekolah || 'SINTA SCHOOL' }}</h4>
                            <p class="mb-0 fs-7">Bukti Transaksi Pembayaran</p>
                        </div>
                        <div class="row mb-3 fs-7">
                            <div class="col-md-6">
                                <div>No Kuitansi: <strong>{{ printData.nomor_kwitansi }}</strong></div>
                                <div>Tanggal: {{ printData.tanggal }}</div>
                                <div>Metode: {{ printData.metode }}</div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div>Nama: <strong>{{ printData.nama_siswa }}</strong></div>
                                <div>NISN: {{ printData.nisn }}</div>
                                <div>Kelas: {{ printData.kelas }}</div>
                            </div>
                        </div>
                        <table class="table table-bordered table-sm fs-7">
                            <thead class="table-light">
                                <tr>
                                    <th>Komponen Kewajiban</th>
                                    <th class="text-end">Jumlah Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in printData.items" :key="item.nama">
                                    <td>{{ item.nama }}</td>
                                    <td class="text-end">Rp {{ formatNumber(item.bayar) }}</td>
                                </tr>
                                <tr class="fw-bold">
                                    <td class="text-end">TOTAL DI BAYAR</td>
                                    <td class="text-end text-success">Rp {{ formatNumber(printData.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-end text-muted fs-8">
                            <div>SINTA SaaS Unified Ledger Platform</div>
                            <div class="text-center" style="min-width: 180px;">
                                <div class="fw-bold text-slate-700 mb-4">TTD Petugas,</div>
                                <div class="fw-bold text-slate-800 border-bottom border-dark d-inline-block px-3">
                                    ( {{ printData.nama_petugas || session.nama_petugas || 'Petugas Kasir' }} )
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-slate-100 fw-semibold" @click="closeKwitansiModal">Tutup</button>
                    <button type="button" @click="printKwitansi" class="btn btn-primary fw-bold"><i class="bi bi-printer me-2"></i> Cetak Sekarang</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Batal Pembayaran -->
    <div v-if="showBatalModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;" @click.self="showBatalModal = false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Pembatalan Transaksi Pembayaran</h5>
                    <button type="button" class="btn-close" @click="showBatalModal = false" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" v-if="targetBatalItem">
                    <div class="alert alert-warning border-0 rounded-3 p-3 mb-3 fs-7">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Tindakan ini akan membatalkan transaksi pembayaran terakhir dan mengembalikan sisa kewajiban tagihan siswa.
                    </div>
                    <div class="p-3 bg-light rounded-3 mb-3 fs-8">
                        <div class="row g-2">
                            <div class="col-6"><strong>No Kuitansi:</strong> {{ targetBatalItem.latest_nomor_kwitansi || '-' }}</div>
                            <div class="col-6"><strong>Komponen:</strong> {{ targetBatalItem.nama_komponen }}</div>
                            <div class="col-6"><strong>Nominal Dibayar:</strong> <span class="text-danger fw-bold">Rp {{ formatNumber(targetBatalItem.nominal_bayar) }}</span></div>
                            <div class="col-6"><strong>Siswa:</strong> {{ selectedSiswa ? selectedSiswa.nama : '-' }}</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-slate-700 fs-8">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea class="form-control border-slate-200" v-model="alasanBatalInput" rows="3" placeholder="Masukkan alasan pembatalan (misal: salah input nominal)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-slate-100 fw-semibold" @click="showBatalModal = false">Batal</button>
                    <button type="button" @click="submitBatalPembayaran" class="btn btn-danger fw-bold" :disabled="loadingBatal || !alasanBatalInput.trim()">
                        <span v-if="loadingBatal" class="spinner-border spinner-border-sm me-1"></span>
                        Ya, Batalkan Transaksi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Notifikasi Profesional (Success/Error Popup) -->
    <div v-if="showNotifModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); z-index: 1060;" @click.self="showNotifModal = false">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content shadow-lg border-0 rounded-4 text-center overflow-hidden">
                <div class="p-4" :class="notifData.type === 'success' ? 'bg-blue-50' : 'bg-danger-subtle'">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 mb-3 shadow-sm"
                         :class="notifData.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
                         style="width: 72px; height: 72px;">
                        <i class="bi fs-1" :class="notifData.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
                    </div>
                    <h4 class="fw-bold mb-2" :class="notifData.type === 'success' ? 'text-slate-800' : 'text-danger'">
                        {{ notifData.title }}
                    </h4>
                    <p class="text-muted fs-7 mb-0">{{ notifData.message }}</p>
                </div>
                
                <div class="p-3 bg-white" v-if="notifData.details">
                    <div class="p-3 bg-light rounded-3 text-start fs-8">
                        <div v-for="(val, key) in notifData.details" :key="key" class="d-flex justify-content-between py-1.5 border-bottom border-slate-200" style="&:last-child { border: none; }">
                            <span class="text-muted">{{ key }}:</span>
                            <span class="fw-bold text-slate-800">{{ val }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-white border-top-0">
                    <button type="button" class="btn fw-bold w-100 py-2.5 rounded-3 text-white shadow-sm"
                            :class="notifData.type === 'success' ? 'btn-success fw-bold' : 'btn-danger fw-bold'"
                            @click="showNotifModal = false">
                        <i class="bi me-2" :class="notifData.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
                        Selesai & Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Injection -->
<script id="user-session" type="application/json">
    <?php echo json_encode([
        'is_super_admin' => (($_SESSION['role_name'] ?? '') === 'super_admin'),
        'tenant_id' => ($_SESSION['tenant_id'] ?? ''),
        'nama_petugas' => ($_SESSION['nama_lengkap'] ?? $_SESSION['name'] ?? $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Petugas Kasir'),
        'nama_sekolah' => ($_SESSION['nama_sekolah'] ?? $_SESSION['tenant_name'] ?? 'SINTA SCHOOL')
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
[v-cloak] { display: none !important; }

/* Styling Tabel Modern Borderless (Gambar 1) */
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
.bg-blue-50 { background-color: #eff6ff; }
.bg-blue-100 { background-color: #dbeafe; }
.text-blue-700 { color: #1d4ed8; }
.border-slate-200 { border-color: #e2e8f0; }
.table-info-light { background-color: #f8fafc !important; }
</style>

<script>
window.VueAppRegistry.register('#keuangan-kasir-app', {
    setup() {
        const session = JSON.parse(document.getElementById('user-session').textContent || '{}');
        const isSuperAdmin = session.is_super_admin;
        const tenantsList = Vue.ref([]);
        const selectedTenantId = Vue.ref(session.tenant_id || '');
        const listJenjang = Vue.ref([]);
        const selectedJenjangId = Vue.ref('');
        const listKelas = Vue.ref([]);
        const selectedKelasId = Vue.ref('');

        const siswaSearch = Vue.ref('');
        const siswaSuggestions = Vue.ref([]);
        const selectedSiswa = Vue.ref(null);

        const tagihanList = Vue.ref([]);
        const totalBelanja = Vue.ref(0);

        const cashReceived = Vue.ref('');
        const changeAmount = Vue.ref(0);

        const hasTunggakanLain = Vue.ref(false);
        const showKwitansiModal = Vue.ref(false);
        const showBatalModal = Vue.ref(false);
        const targetBatalItem = Vue.ref(null);
        const alasanBatalInput = Vue.ref('');
        const loadingBatal = Vue.ref(false);

        const showNotifModal = Vue.ref(false);
        const notifData = Vue.ref({
            type: 'success',
            title: '',
            message: '',
            details: null
        });

        const notify = (title, message, type = 'success', details = null) => {
            notifData.value = { type, title, message, details };
            showNotifModal.value = true;
        };

        const checkoutForm = Vue.ref({
            metode_pembayaran: 'Tunai',
            keterangan: ''
        });

        const loadingCheckout = Vue.ref(false);

        // Kwitansi Print State
        const printData = Vue.ref({
            nama_sekolah: '',
            nomor_kwitansi: '',
            tanggal: '',
            metode: '',
            nama_siswa: '',
            nisn: '',
            kelas: '',
            items: [],
            total: 0,
            nama_petugas: ''
        });

        const namaSekolahAktif = Vue.computed(() => {
            if (isSuperAdmin && selectedTenantId.value) {
                const t = tenantsList.value.find(item => item.id === selectedTenantId.value);
                if (t && t.nama_sekolah) return t.nama_sekolah;
            }
            return session.nama_sekolah || 'SINTA SCHOOL';
        });

        // Helper to append tenant query parameter for super admin
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

        const fetchKelas = async () => {
            try {
                const tenantParam = isSuperAdmin && selectedTenantId.value ? `tenant_id=${selectedTenantId.value}` : '';
                const jenjangParam = selectedJenjangId.value ? `jenjang_id=${selectedJenjangId.value}` : '';
                const params = [tenantParam, jenjangParam].filter(Boolean).join('&');
                const query = params ? `?${params}` : '';
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/kelas' + query);
                const res = await response.json();
                if (res.success) {
                    listKelas.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const onJenjangChange = () => {
            selectedKelasId.value = '';
            fetchKelas();
            searchSiswa();
        };

        const onKelasChange = () => {
            searchSiswa();
        };

        // Autocomplete Search
        let searchTimeout = null;
        const searchSiswa = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
                try {
                    const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                    const classParam = selectedKelasId.value ? `&kelas_id=${selectedKelasId.value}` : '';
                    const jenjangParam = selectedJenjangId.value ? `&jenjang_id=${selectedJenjangId.value}` : '';
                    const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/cari-siswa?q=${encodeURIComponent(siswaSearch.value)}${tenantSuffix}${classParam}${jenjangParam}`);
                    const res = await response.json();
                    if (res.success) {
                        siswaSuggestions.value = res.data;
                    }
                } catch (err) {
                    console.error(err);
                }
            }, 200);
        };

        const selectSiswa = (siswa) => {
            selectedSiswa.value = siswa;
            siswaSearch.value = '';
            siswaSuggestions.value = [];
            fetchTagihanSiswa(siswa.id);
        };

        const clearSelectedSiswa = () => {
            selectedSiswa.value = null;
            tagihanList.value = [];
            totalBelanja.value = 0;
            hasTunggakanLain.value = false;
        };

        const onTenantChange = () => {
            localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
            selectedJenjangId.value = '';
            selectedKelasId.value = '';
            clearSelectedSiswa();
            fetchJenjang();
            fetchKelas();
        };

        const fetchTagihanSiswa = async (siswaId) => {
            try {
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/tagihan-siswa?siswa_id=${siswaId}`);
                const res = await response.json();
                if (res.success) {
                    tagihanList.value = res.data.map(t => ({
                        ...t,
                        selected: false,
                        bayar_input: t.status_lunas === 'Lunas' ? 0 : (t.nominal_tagihan - t.nominal_bayar)
                    }));

                    const hasTunggakan = tagihanList.value.some(t => t.status_lunas !== 'Lunas' && (t.nominal_tagihan - t.nominal_bayar) > 0);
                    hasTunggakanLain.value = hasTunggakan;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const toggleSelectTagihan = (t) => {
            updateTotal();
        };

        const updateTotal = () => {
            totalBelanja.value = tagihanList.value
                .filter(t => t.selected)
                .reduce((sum, t) => sum + (parseFloat(t.bayar_input) || 0), 0);
            calculateKembalian();
        };

        const calculateKembalian = () => {
            if (checkoutForm.value.metode_pembayaran === 'Tunai' && cashReceived.value > 0) {
                changeAmount.value = Math.max(0, cashReceived.value - totalBelanja.value);
            } else {
                changeAmount.value = 0;
            }
        };

        const checkoutPembayaran = async () => {
            const selectedItems = tagihanList.value
                .filter(t => t.selected)
                .map(t => ({
                    tagihan_id: t.id,
                    nominal_dibayar: t.bayar_input
                }));

            if (selectedItems.length === 0) return;

            loadingCheckout.value = true;

            try {
                const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/bayar' + tenantSuffix, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        siswa_id: selectedSiswa.value.id,
                        items: selectedItems,
                        metode_pembayaran: checkoutForm.value.metode_pembayaran,
                        keterangan: checkoutForm.value.keterangan
                    })
                });
                const res = await response.json();
                if (res.success) {
                    const paidItems = tagihanList.value.filter(t => t.selected).map(t => ({
                        nama: t.nama_komponen + (t.bulan ? ` (${getBulanName(t.bulan)})` : ''),
                        bayar: t.bayar_input
                    }));
                    const grandTotal = totalBelanja.value;

                    // Set data print
                    printData.value = {
                        nama_sekolah: namaSekolahAktif.value,
                        nomor_kwitansi: res.nomor_kwitansi,
                        tanggal: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                        metode: checkoutForm.value.metode_pembayaran,
                        nama_siswa: selectedSiswa.value ? selectedSiswa.value.nama : '',
                        nisn: selectedSiswa.value ? selectedSiswa.value.nisn : '',
                        kelas: selectedSiswa.value ? selectedSiswa.value.nama_kelas : '',
                        items: paidItems,
                        total: grandTotal,
                        nama_petugas: session.nama_petugas || 'Petugas Kasir'
                    };

                    showKwitansiModal.value = true;

                    fetchTagihanSiswa(selectedSiswa.value.id);
                    totalBelanja.value = 0;
                    cashReceived.value = '';
                    changeAmount.value = 0;
                    checkoutForm.value.keterangan = '';
                } else {
                    notify('Gagal Transaksi', res.error || 'Gagal menyimpan transaksi.', 'error');
                }
            } catch (err) {
                notify('Kesalahan Jaringan', 'Terjadi kesalahan koneksi jaringan.', 'error');
            } finally {
                loadingCheckout.value = false;
            }
        };

        const printKwitansi = () => {
            const printElement = document.getElementById('printArea');
            if (!printElement) return;

            const printWindow = window.open('', '_blank', 'width=850,height=700');
            if (!printWindow) {
                alert('Pop-up terblokir. Harap izinkan pop-up pada browser Anda untuk mencetak kuitansi.');
                return;
            }

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cetak Kuitansi - ${printData.value.nomor_kwitansi || 'Official Receipt'}</title>
                    <link rel="stylesheet" href="/SINTA-SaaS/assets/css/bootstrap.min.css">
                    <style>
                        body {
                            background-color: #ffffff;
                            font-family: 'Courier New', Courier, monospace;
                            padding: 25px;
                            color: #000;
                        }
                        .kwitansi-box {
                            border: 2px dashed #444;
                            padding: 25px;
                            max-width: 750px;
                            margin: 0 auto;
                        }
                        .table-bordered th, .table-bordered td {
                            border: 1px solid #333 !important;
                        }
                        @media print {
                            body { padding: 0; background-color: #fff; }
                            .kwitansi-box { border: none; padding: 0; max-width: 100%; }
                        }
                    </style>
                </head>
                <body>
                    <div class="kwitansi-box">
                        ${printElement.innerHTML}
                    </div>
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.focus();
                                window.print();
                            }, 300);
                        };
                        window.onafterprint = function() {
                            window.close();
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        };

        const reprintKwitansi = (item) => {
            let kwNo = item.latest_nomor_kwitansi || 'KW-OFFICIAL';
            if (kwNo.indexOf('-') > -1) {
                kwNo = kwNo.substring(0, kwNo.lastIndexOf('-'));
            }

            let tglStr = item.latest_tgl_bayar 
                ? new Date(item.latest_tgl_bayar).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                : new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

            printData.value = {
                nama_sekolah: namaSekolahAktif.value,
                nomor_kwitansi: kwNo,
                tanggal: tglStr,
                metode: item.latest_metode || 'Tunai',
                nama_siswa: selectedSiswa.value ? selectedSiswa.value.nama : '',
                nisn: selectedSiswa.value ? selectedSiswa.value.nisn : '',
                kelas: item.nama_kelas_history,
                items: [{
                    nama: item.nama_komponen + (item.bulan ? ` (${getBulanName(item.bulan)})` : ''),
                    bayar: item.nominal_bayar
                }],
                total: item.nominal_bayar,
                nama_petugas: session.nama_petugas || 'Petugas Kasir'
            };

            showKwitansiModal.value = true;
        };

        const closeKwitansiModal = () => {
            showKwitansiModal.value = false;
        };

        const confirmBatalPembayaran = (item) => {
            targetBatalItem.value = item;
            alasanBatalInput.value = '';
            showBatalModal.value = true;
        };

        const submitBatalPembayaran = async () => {
            if (!targetBatalItem.value || !alasanBatalInput.value.trim()) return;

            loadingBatal.value = true;
            try {
                const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/batal-pembayaran' + tenantSuffix, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        tagihan_id: targetBatalItem.value.id,
                        alasan_batal: alasanBatalInput.value.trim()
                    })
                });
                const res = await response.json();
                if (res.success) {
                    showBatalModal.value = false;
                    const kwNo = targetBatalItem.value ? targetBatalItem.value.latest_nomor_kwitansi : '-';
                    const nominalStr = targetBatalItem.value ? 'Rp ' + formatNumber(targetBatalItem.value.nominal_bayar) : '-';
                    const itemNama = targetBatalItem.value ? targetBatalItem.value.nama_komponen : '-';

                    notify(
                        'Pembatalan Pembayaran Berhasil',
                        res.message || 'Transaksi pembayaran berhasil dibatalkan dan sisa tagihan telah dipulihkan.',
                        'success',
                        {
                            'No Kuitansi': kwNo,
                            'Komponen Tagihan': itemNama,
                            'Nominal Dibatalkan': nominalStr,
                            'Siswa Target': selectedSiswa.value ? selectedSiswa.value.nama : '-'
                        }
                    );

                    targetBatalItem.value = null;
                    if (selectedSiswa.value) {
                        fetchTagihanSiswa(selectedSiswa.value.id);
                    }
                } else {
                    notify('Gagal Membatalkan', res.error || 'Gagal membatalkan pembayaran.', 'error');
                }
            } catch (err) {
                notify('Kesalahan Jaringan', 'Terjadi kesalahan koneksi jaringan.', 'error');
            } finally {
                loadingBatal.value = false;
            }
        };

        const getBulanName = (bln) => {
            const list = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return list[bln] || '';
        };

        const getStatusBadgeClass = (status) => {
            switch(status) {
                case 'Lunas': return 'bg-success';
                case 'Cicil': return 'bg-warning text-dark';
                default: return 'bg-danger';
            }
        };

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num);
        };

        const groupedTagihan = Vue.computed(() => {
            const groups = {};
            for (const item of tagihanList.value) {
                const ta = item.tahun_ajaran || 'Lainnya';
                const komp = item.nama_komponen || 'Lainnya';
                if (!groups[ta]) groups[ta] = {};
                if (!groups[ta][komp]) groups[ta][komp] = [];
                groups[ta][komp].push(item);
            }
            for (const ta in groups) {
                for (const komp in groups[ta]) {
                    groups[ta][komp].sort((a, b) => {
                        const bA = parseInt(a.bulan) || 0;
                        const bB = parseInt(b.bulan) || 0;
                        return bA - bB;
                    });
                }
            }
            return groups;
        });

        Vue.onMounted(async () => {
            if (isSuperAdmin) {
                await fetchTenants();
            }
            await fetchJenjang();
            await fetchKelas();
        });

        return {
            isSuperAdmin,
            tenantsList,
            selectedTenantId,
            listJenjang,
            selectedJenjangId,
            listKelas,
            selectedKelasId,
            siswaSearch,
            siswaSuggestions,
            selectedSiswa,
            tagihanList,
            groupedTagihan,
            totalBelanja,
            cashReceived,
            changeAmount,
            hasTunggakanLain,
            checkoutForm,
            loadingCheckout,
            showKwitansiModal,
            printData,
            searchSiswa,
            selectSiswa,
            clearSelectedSiswa,
            onTenantChange,
            onJenjangChange,
            onKelasChange,
            toggleSelectTagihan,
            updateTotal,
            calculateKembalian,
            checkoutPembayaran,
            printKwitansi,
            reprintKwitansi,
            closeKwitansiModal,
            showBatalModal,
            targetBatalItem,
            alasanBatalInput,
            loadingBatal,
            confirmBatalPembayaran,
            submitBatalPembayaran,
            showNotifModal,
            notifData,
            notify,
            getBulanName,
            formatNumber,
            getStatusBadgeClass,
            session,
            namaSekolahAktif
        };
    }
});
</script>
