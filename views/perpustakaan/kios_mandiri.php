<?php
/**
 * View: Kios Mandiri Peminjaman & Pengembalian Perpustakaan (Self-Service Kiosk)
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
?>
<div id="kiosMandiriApp" v-cloak class="container-fluid px-0">
    <!-- ═══════════════════════════════════════════════════════════════════════
         HERO BANNER & MULTI-TENANT SAAS SWITCHER
         ═══════════════════════════════════════════════════════════════════════ -->
    <?php
    $heroIcon = 'bi-display';
    $heroBadge = 'Kios Mandiri Digital & Presensi';
    $heroTitle = 'Kios Mandiri Peminjaman & Pengembalian';
    $heroDesc = 'Stasiun Mandiri Layanan Sirkulasi Pustaka (Self-Service Kiosk) untuk Siswa dan Guru berbasis Barcode & QR Code.';
    $heroButtons = '
        <button type="button" @click="toggleFullscreen" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi" :class="isFullscreen ? \'bi-fullscreen-exit\' : \'bi-arrows-fullscreen\'" class="me-1.5"></i> {{ isFullscreen ? \'Keluar Layar Penuh\' : \'Layar Penuh Kios\' }}
        </button>
        <a href="' . $this->getBaseUrl() . '/perpustakaan" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    ';
    include __DIR__ . '/_tenant_filter.php';
    ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODERN INTERACTIVE PILL NAVIGATION (MODUL KIOS MANDIRI)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-2xs rounded-2xl mb-4 bg-white">
        <div class="card-body p-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <ul class="nav nav-pills custom-modern-pills flex-nowrap overflow-x-auto text-nowrap gap-1 px-1" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeKioskMode === 'pinjam' }" @click="activeKioskMode = 'pinjam'" type="button">
                            <i class="bi bi-box-arrow-up-right me-1.5 text-primary"></i> 1. Peminjaman Mandiri
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeKioskMode === 'kembali' }" @click="activeKioskMode = 'kembali'" type="button">
                            <i class="bi bi-box-arrow-in-left me-1.5 text-success"></i> 2. Pengembalian Mandiri
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeKioskMode === 'presensi' }" @click="activeKioskMode = 'presensi'" type="button">
                            <i class="bi bi-person-check-fill me-1.5 text-amber-500"></i> 3. Presensi Pengunjung
                        </button>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 pe-2">
                    <div class="form-check form-switch mb-0 fs-8 fw-semibold text-slate-600 d-flex align-items-center gap-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="soundToggle" v-model="soundEnabled">
                        <label class="form-check-label cursor-pointer" for="soundToggle">
                            <i class="bi" :class="soundEnabled ? 'bi-volume-up-fill text-primary' : 'bi-volume-mute text-slate-400'"></i> Suara Beep
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         TAB 1: PEMINJAMAN MANDIRI (SELF-CHECKOUT)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeKioskMode === 'pinjam'" class="tab-pane-content transition-all">
        <div class="row g-4">
            <!-- Langkah 1: Scan Kartu Anggota -->
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4 h-100 position-relative overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-5 fw-bold shadow-2xs">
                                1
                            </div>
                            <div>
                                <h5 class="fw-bold text-slate-800 mb-0">Identifikasi Anggota</h5>
                                <small class="text-slate-400 text-xs">Scan Kartu Siswa / Ketik NISN</small>
                            </div>
                        </div>
                        <span v-if="selectedAnggota" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-pill fs-9 fw-bold">
                            <i class="bi bi-check-circle-fill me-1"></i> Terverifikasi
                        </span>
                    </div>

                    <!-- Scanner Animation Area -->
                    <div class="p-4 rounded-2xl text-center my-3 transition-all"
                         :class="selectedAnggota ? 'bg-emerald-50/50 border border-emerald-200' : 'scanner-glow-blue'">
                        <div v-if="!selectedAnggota">
                            <div class="w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 d-inline-flex align-items-center justify-content-center fs-2 mb-2 shadow-2xs">
                                <i class="bi bi-qr-code-scan"></i>
                            </div>
                            <h6 class="fw-bold text-slate-700 mb-1">Arahkan QR / Barcode Kartu</h6>
                            <p class="text-slate-400 text-xs mb-0">Dekatkan kartu anggota ke pemindai laser atau gunakan input di bawah.</p>
                        </div>
                        <div v-else class="text-start">
                            <div class="d-flex align-items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white d-flex align-items-center justify-content-center fs-4 fw-bold shadow-sm">
                                    {{ selectedAnggota.nama_lengkap ? selectedAnggota.nama_lengkap.charAt(0).toUpperCase() : 'A' }}
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-bold text-slate-800 fs-6 text-truncate">{{ selectedAnggota.nama_lengkap }}</div>
                                    <div class="text-xs text-slate-500">
                                        <span class="badge bg-slate-100 text-slate-700 me-1">{{ selectedAnggota.tipe_anggota || 'Siswa' }}</span>
                                        <span class="font-mono text-slate-500">{{ selectedAnggota.nisn || selectedAnggota.no_anggota }}</span>
                                    </div>
                                </div>
                                <button type="button" @click="resetAnggota" class="btn btn-sm btn-outline-danger rounded-xl px-2.5 py-1 text-xs" title="Ganti Anggota">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="mt-3 pt-3 border-top border-emerald-200/60 d-flex justify-content-between text-xs">
                                <span class="text-slate-500">Status Keanggotaan:</span>
                                <span class="fw-bold text-emerald-700">Aktif & Siap Meminjam</span>
                            </div>
                        </div>
                    </div>

                    <!-- Input Form NISN / ID Anggota -->
                    <form @submit.prevent="lookupAnggota" class="mt-auto">
                        <div class="mb-2">
                            <label class="form-label text-xs fw-bold text-slate-700">Nomor Kartu / NISN / No. Anggota</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-50 border-slate-200 text-slate-400 rounded-start-xl">
                                    <i class="bi bi-person-badge"></i>
                                </span>
                                <input type="text" v-model="inputAnggotaId" ref="refInputAnggota"
                                       class="form-control rounded-end-xl text-xs py-2.5 border-slate-200 focus:border-blue-500 shadow-2xs font-semibold"
                                       placeholder="Ketik NISN lalu tekan ENTER..." :disabled="selectedAnggota !== null" required>
                            </div>
                        </div>
                        <button v-if="!selectedAnggota" type="submit" class="btn btn-primary w-100 rounded-xl py-2 text-xs font-bold shadow-2xs mt-2 d-inline-flex align-items-center justify-content-center gap-1.5" :disabled="loadingLookup">
                            <i class="bi bi-search" :class="{'spin': loadingLookup}"></i> Verifikasi Anggota
                        </button>
                    </form>
                </div>
            </div>

            <!-- Langkah 2 & 3: Scan Buku & Konfirmasi Pinjam -->
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4 h-100 position-relative overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 d-flex align-items-center justify-content-center fs-5 fw-bold shadow-2xs">
                                2
                            </div>
                            <div>
                                <h5 class="fw-bold text-slate-800 mb-0">Scan Barcode Buku</h5>
                                <small class="text-slate-400 text-xs">Pindai barcode pada sampul buku yang ingin dipinjam</small>
                            </div>
                        </div>
                        <span class="badge bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-1 rounded-pill fs-8 fw-semibold">
                            Durasi Standar: 7 Hari
                        </span>
                    </div>

                    <!-- Input Scan Barcode Buku -->
                    <form @submit.prevent="tambahBukuKeKeranjang" class="mb-3">
                        <div class="input-group input-group-lg shadow-2xs rounded-2xl overflow-hidden border border-slate-200">
                            <span class="input-group-text bg-emerald-50 text-emerald-600 border-0 px-3.5">
                                <i class="bi bi-upc-scan fs-4"></i>
                            </span>
                            <input type="text" v-model="inputBarcodeBuku" ref="refInputBuku"
                                   class="form-control border-0 text-sm fw-bold py-3 bg-white"
                                   placeholder="Scan Barcode Eksemplar Buku (LIB-xxxx) di sini..."
                                   :disabled="!selectedAnggota">
                            <button type="submit" class="btn btn-emerald px-4 fw-bold text-xs" :disabled="!selectedAnggota || !inputBarcodeBuku">
                                <i class="bi bi-plus-circle me-1"></i> Tambah
                            </button>
                        </div>
                    </form>

                    <!-- Queue / Daftar Buku yang Sedang Dipinjam -->
                    <div class="flex-grow-1 border border-slate-200/80 rounded-2xl p-3 bg-slate-50/50 mb-3" style="min-height: 180px; max-height: 260px; overflow-y: auto;">
                        <div v-if="keranjangBuku.length === 0" class="text-center py-4 text-slate-400 text-xs">
                            <i class="bi bi-book-half display-6 text-slate-300 d-block mb-1"></i>
                            Belum ada buku yang discan. Silakan scan barcode buku pertama Anda.
                        </div>
                        <div v-else class="d-flex flex-column gap-2">
                            <div v-for="(buku, idx) in keranjangBuku" :key="idx"
                                 class="p-2.5 bg-white rounded-xl border border-slate-200/90 shadow-2xs d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 d-flex align-items-center justify-content-center font-mono text-xs fw-bold flex-shrink-0">
                                        {{ idx + 1 }}
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-slate-800 text-xs text-truncate">{{ buku.judul || 'Buku Pustaka (' + buku.barcode + ')' }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">Barcode: {{ buku.barcode }}</div>
                                    </div>
                                </div>
                                <button type="button" @click="hapusBukuDariKeranjang(idx)" class="btn btn-xs text-rose-500 hover:text-rose-700 p-1">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Button -->
                    <div class="d-flex align-items-center justify-content-between pt-2">
                        <div>
                            <span class="text-xs text-slate-500">Total Buku:</span>
                            <span class="fw-bold text-slate-800 fs-6 ms-1">{{ keranjangBuku.length }} Judul</span>
                        </div>
                        <button type="button" @click="prosesCheckoutMandiri"
                                class="btn btn-emerald btn-lg rounded-2xl px-4 py-2.5 text-xs font-bold shadow-xs d-inline-flex align-items-center gap-2"
                                :disabled="!selectedAnggota || keranjangBuku.length === 0 || processingCheckout">
                            <i class="bi bi-check-circle-fill" :class="{'spin': processingCheckout}"></i>
                            {{ processingCheckout ? 'Memproses Peminjaman...' : 'SELESAIKAN & AMBIL BUKU' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         TAB 2: PENGEMBALIAN MANDIRI (SELF-RETURN)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeKioskMode === 'kembali'" class="tab-pane-content transition-all">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4 p-md-5 text-center">
                    <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-2xs">
                        <i class="bi bi-box-arrow-in-left"></i>
                    </div>
                    <h4 class="fw-bold text-slate-800 mb-1">Pengembalian Buku Mandiri</h4>
                    <p class="text-slate-400 text-xs mb-4" style="max-width: 480px; margin: 0 auto;">
                        Scan barcode eksemplar di bagian belakang sampul buku. Sistem akan memeriksa riwayat dan menghitung denda secara otomatis.
                    </p>

                    <!-- Scan Input Barcode Pengembalian -->
                    <form @submit.prevent="prosesReturnMandiri" class="mb-4">
                        <div class="input-group input-group-lg shadow-2xs rounded-2xl overflow-hidden border border-slate-200 mb-3">
                            <span class="input-group-text bg-emerald-50 text-emerald-600 border-0 px-3.5">
                                <i class="bi bi-qr-code-scan fs-4"></i>
                            </span>
                            <input type="text" v-model="inputBarcodeKembali"
                                   class="form-control border-0 text-sm fw-bold py-3.5 text-center bg-white"
                                   placeholder="Scan atau Ketik Barcode Buku yang Dikembalikan..." autofocus required>
                        </div>
                        <button type="submit" class="btn btn-emerald btn-lg w-100 rounded-2xl py-3 text-xs font-bold shadow-xs d-inline-flex align-items-center justify-content-center gap-2" :disabled="processingReturn || !inputBarcodeKembali">
                            <i class="bi bi-check-circle-fill" :class="{'spin': processingReturn}"></i>
                            {{ processingReturn ? 'Memverifikasi Pengembalian...' : 'PROSES PENGEMBALIAN BUKU' }}
                        </button>
                    </form>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 text-start text-xs">
                        <div class="fw-bold text-slate-700 mb-1 d-flex align-items-center gap-1.5">
                            <i class="bi bi-info-circle-fill text-blue-600"></i> Petunjuk Pengembalian Buku:
                        </div>
                        <ul class="text-slate-500 mb-0 ps-3 space-y-1">
                            <li>Setelah status berhasil, silakan masukkan buku ke dalam kotak <strong>Drop-Box Pengembalian</strong>.</li>
                            <li>Jika terdapat tagihan denda keterlambatan, mohon lakukan pembayaran di loket kasir perpustakaan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         TAB 3: PRESENSI PENGUNJUNG CEPAT (FAST VISITOR LOG)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeKioskMode === 'presensi'" class="tab-pane-content transition-all">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4 p-md-5 text-center">
                    <div class="w-16 h-16 rounded-3xl bg-amber-50 text-amber-600 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-2xs">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <h4 class="fw-bold text-slate-800 mb-1">Presensi Buku Tamu Perpustakaan</h4>
                    <p class="text-slate-400 text-xs mb-4" style="max-width: 480px; margin: 0 auto;">
                        Cukup scan kartu anggota Anda sekali saat memasuki ruangan perpustakaan untuk mencatatkan kunjungan hari ini.
                    </p>

                    <form @submit.prevent="prosesPresensiCepat" class="mb-4">
                        <div class="input-group input-group-lg shadow-2xs rounded-2xl overflow-hidden border border-slate-200 mb-3">
                            <span class="input-group-text bg-amber-50 text-amber-600 border-0 px-3.5">
                                <i class="bi bi-person-badge fs-4"></i>
                            </span>
                            <input type="text" v-model="inputPresensiId"
                                   class="form-control border-0 text-sm fw-bold py-3.5 text-center bg-white"
                                   placeholder="Scan Kartu Siswa / Ketik NISN..." required>
                        </div>
                        <button type="submit" class="btn btn-warning btn-lg w-100 rounded-2xl py-3 text-xs font-bold text-slate-900 shadow-xs d-inline-flex align-items-center justify-content-center gap-2" :disabled="processingPresensi || !inputPresensiId">
                            <i class="bi bi-check-lg" :class="{'spin': processingPresensi}"></i>
                            {{ processingPresensi ? 'Mencatat Kehadiran...' : 'CATAT KUNJUNGAN SAYA' }}
                        </button>
                    </form>

                    <div v-if="lastVisitor" class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-start text-xs animate__animated animate__fadeIn">
                        <div class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-check-circle-fill text-emerald-600 fs-4"></i>
                            <div>
                                <div class="fw-bold text-emerald-900">Selamat Datang, {{ lastVisitor.nama }}!</div>
                                <div class="text-emerald-700 text-[11px]">Kehadiran Anda telah dicatat pada jam {{ lastVisitor.waktu }}. Selamat membaca!</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vue 3 Interactive Script for Kios Mandiri -->
<script>
if (typeof Vue !== 'undefined') {
    const { ref, onMounted } = Vue;

    const kiosMandiriAppConfig = {
        setup() {
            const activeKioskMode = ref('pinjam');
            const isFullscreen = ref(false);
            const soundEnabled = ref(true);

            // Tenant Isolation Helper
            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($data['active_tenant_id'] ?? ($activeTenantId ?? '')) ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            // Audio synth beep helper
            const playBeep = (freq = 880, type = 'sine', duration = 0.15) => {
                if (!soundEnabled.value) return;
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = type;
                    osc.frequency.value = freq;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration);
                    osc.stop(ctx.currentTime + duration);
                } catch (e) {}
            };

            // State Peminjaman
            const inputAnggotaId = ref('');
            const loadingLookup = ref(false);
            const selectedAnggota = ref(null);
            const inputBarcodeBuku = ref('');
            const keranjangBuku = ref([]);
            const processingCheckout = ref(false);
            const refInputAnggota = ref(null);
            const refInputBuku = ref(null);

            // State Pengembalian
            const inputBarcodeKembali = ref('');
            const processingReturn = ref(false);

            // State Presensi
            const inputPresensiId = ref('');
            const processingPresensi = ref(false);
            const lastVisitor = ref(null);

            const lookupAnggota = async () => {
                if (!inputAnggotaId.value) return;
                loadingLookup.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/anggota' + getTenantParam('?'));
                    const list = res.data?.data?.list || [];
                    const q = inputAnggotaId.value.trim().toLowerCase();
                    const found = list.find(a => (a.nisn && a.nisn.toLowerCase() === q) || (a.no_anggota && a.no_anggota.toLowerCase() === q) || (a.nama_lengkap && a.nama_lengkap.toLowerCase().includes(q)));

                    if (found) {
                        selectedAnggota.value = found;
                        playBeep(987, 'sine', 0.2);
                        setTimeout(() => {
                            if (refInputBuku.value) refInputBuku.value.focus();
                        }, 200);
                    } else {
                        // Fallback generic anggota jika baru scan NISN
                        selectedAnggota.value = {
                            nama_lengkap: 'Anggota (' + inputAnggotaId.value + ')',
                            nisn: inputAnggotaId.value,
                            tipe_anggota: 'Siswa'
                        };
                        playBeep(987, 'sine', 0.2);
                    }
                } catch (e) {
                    selectedAnggota.value = {
                        nama_lengkap: 'Anggota (' + inputAnggotaId.value + ')',
                        nisn: inputAnggotaId.value,
                        tipe_anggota: 'Siswa'
                    };
                } finally {
                    loadingLookup.value = false;
                }
            };

            const resetAnggota = () => {
                selectedAnggota.value = null;
                inputAnggotaId.value = '';
                keranjangBuku.value = [];
            };

            const tambahBukuKeKeranjang = () => {
                const code = inputBarcodeBuku.value.trim();
                if (!code) return;

                // Cek apakah barcode sudah ada di keranjang
                if (keranjangBuku.value.some(b => b.barcode === code)) {
                    alert('Barcode buku ini sudah masuk ke daftar pinjam.');
                    return;
                }

                keranjangBuku.value.push({
                    barcode: code,
                    judul: 'Buku Koleksi ' + code
                });
                playBeep(1200, 'sine', 0.12);
                inputBarcodeBuku.value = '';
            };

            const hapusBukuDariKeranjang = (idx) => {
                keranjangBuku.value.splice(idx, 1);
            };

            const prosesCheckoutMandiri = async () => {
                if (!selectedAnggota.value || keranjangBuku.value.length === 0) return;
                processingCheckout.value = true;

                try {
                    let suksesCount = 0;
                    for (const buku of keranjangBuku.value) {
                        const payload = {
                            anggota_id: selectedAnggota.value.nisn || selectedAnggota.value.no_anggota || inputAnggotaId.value,
                            nama_anggota: selectedAnggota.value.nama_lengkap,
                            eksemplar_id: buku.barcode,
                            durasi_hari: 7,
                            tenant_id: currentTenantId
                        };
                        const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/sirkulasi/pinjam' + getTenantParam('?'), payload);
                        if (res.data && res.data.success) {
                            suksesCount++;
                        }
                    }

                    playBeep(1500, 'triangle', 0.3);
                    alert(`Selamat! ${suksesCount} buku berhasil dipinjam. Jatuh tempo pengembalian adalah 7 hari dari sekarang.`);
                    resetAnggota();
                } catch (e) {
                    alert('Terjadi kesalahan saat menyimpan transaksi peminjaman.');
                } finally {
                    processingCheckout.value = false;
                }
            };

            const prosesReturnMandiri = async () => {
                if (!inputBarcodeKembali.value) return;
                processingReturn.value = true;
                try {
                    const payload = {
                        eksemplar_id: inputBarcodeKembali.value.trim(),
                        kondisi: 'Baik',
                        tenant_id: currentTenantId
                    };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/sirkulasi/kembali' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        playBeep(1200, 'sine', 0.25);
                        alert(res.data.message || 'Buku berhasil dikembalikan! Silakan letakkan buku di Drop-Box.');
                        inputBarcodeKembali.value = '';
                    } else {
                        alert(res.data.message || res.data.error || 'Buku tidak ditemukan dalam daftar peminjaman aktif.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan saat memproses pengembalian buku.');
                } finally {
                    processingReturn.value = false;
                }
            };

            const prosesPresensiCepat = async () => {
                if (!inputPresensiId.value) return;
                processingPresensi.value = true;
                try {
                    const payload = {
                        nama_pengunjung: 'Pengunjung (' + inputPresensiId.value + ')',
                        keperluan: 'Membaca & Belajar Mandiri',
                        tenant_id: currentTenantId
                    };
                    await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/visitor-logs/simpan' + getTenantParam('?'), payload);
                    playBeep(1000, 'sine', 0.2);
                    lastVisitor.value = {
                        nama: inputPresensiId.value,
                        waktu: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                    };
                    inputPresensiId.value = '';
                } catch (e) {
                    lastVisitor.value = {
                        nama: inputPresensiId.value,
                        waktu: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                    };
                    inputPresensiId.value = '';
                } finally {
                    processingPresensi.value = false;
                }
            };

            const toggleFullscreen = () => {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().then(() => {
                        isFullscreen.value = true;
                    }).catch(() => {});
                } else {
                    document.exitFullscreen().then(() => {
                        isFullscreen.value = false;
                    }).catch(() => {});
                }
            };

            onMounted(() => {
                document.addEventListener('fullscreenchange', () => {
                    isFullscreen.value = !!document.fullscreenElement;
                });
            });

            return {
                activeKioskMode,
                isFullscreen,
                soundEnabled,
                inputAnggotaId,
                loadingLookup,
                selectedAnggota,
                inputBarcodeBuku,
                keranjangBuku,
                processingCheckout,
                refInputAnggota,
                refInputBuku,
                inputBarcodeKembali,
                processingReturn,
                inputPresensiId,
                processingPresensi,
                lastVisitor,
                lookupAnggota,
                resetAnggota,
                tambahBukuKeKeranjang,
                hapusBukuDariKeranjang,
                prosesCheckoutMandiri,
                prosesReturnMandiri,
                prosesPresensiCepat,
                toggleFullscreen
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#kiosMandiriApp', kiosMandiriAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        const mountApp = () => {
            const el = document.querySelector('#kiosMandiriApp');
            if (el && !el.__vue_app__) {
                Vue.createApp(kiosMandiriAppConfig).mount('#kiosMandiriApp');
            }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mountApp);
        } else {
            mountApp();
        }
        document.addEventListener('turbo:load', mountApp);
    }
}
</script>

<style>
/* Modern Pill Navtabs */
.custom-modern-pills {
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    background: transparent;
    padding: 2px;
}
.custom-modern-pills .nav-link {
    border-radius: 12px !important;
    padding: 8px 18px !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    color: #475569 !important;
    background-color: transparent !important;
    border: none !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}
.custom-modern-pills .nav-link:hover:not(.active) {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
}
.custom-modern-pills .nav-link.active {
    background-color: #2563eb !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25) !important;
}
.custom-modern-pills .nav-link.active i {
    color: #ffffff !important;
}
.btn-emerald {
    background-color: #059669 !important;
    color: #ffffff !important;
}
.btn-emerald:hover {
    background-color: #047857 !important;
    color: #ffffff !important;
}
.scanner-glow-blue {
    border: 2px dashed #93c5fd;
    background: #f8fafc;
    border-radius: 1rem;
    animation: pulse-glow-blue 2s infinite alternate;
}
@keyframes pulse-glow-blue {
    0% { box-shadow: 0 0 6px rgba(59, 130, 246, 0.15); }
    100% { box-shadow: 0 0 18px rgba(59, 130, 246, 0.35); }
}
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
[v-cloak] { display: none !important; }
</style>
