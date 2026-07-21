
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

    <!-- Pencarian Siswa Utama -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="row g-3 align-items-center">
            <div class="col-md-6 position-relative">
                <label class="form-label fw-semibold text-slate-700">Cari Nama Siswa atau NISN <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-slate-200"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-slate-200" v-model="siswaSearch" @input="searchSiswa" placeholder="Ketik nama / NISN untuk memulai transaksi..." style="height: 42px;">
                </div>
                <!-- Suggestions -->
                <ul class="dropdown-menu show w-100 shadow border-slate-200 p-0 overflow-hidden" v-if="siswaSuggestions.length > 0" style="display: block; max-height: 200px; overflow-y: auto; z-index: 1010;">
                    <li v-for="s in siswaSuggestions" :key="s.id">
                        <a href="#" class="dropdown-item py-2.5 px-3 d-flex justify-content-between align-items-center" @click.prevent="selectSiswa(s)">
                            <div>
                                <div class="fw-bold text-slate-800">{{ s.nama }}</div>
                                <small class="text-muted">NISN: {{ s.nisn }} | Kelas: {{ s.nama_kelas }}</small>
                            </div>
                            <i class="bi bi-arrow-right-circle text-blue-600 fs-5"></i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-md-6" v-if="selectedSiswa">
                <div class="p-3 bg-blue-50 border border-blue-100 rounded-3 d-flex align-items-center" style="height: 60px;">
                    <div class="me-3 p-2 bg-white rounded-circle text-blue-600 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-slate-800 mb-0 fs-7" style="line-height: 1.2;">{{ selectedSiswa.nama }}</h5>
                        <p class="text-muted mb-0" style="font-size: 0.7rem;">NISN: {{ selectedSiswa.nisn }} | Kelas: <span class="badge bg-blue-100 text-blue-700 px-2 py-0.5" style="font-size: 0.65rem;">{{ selectedSiswa.nama_kelas }}</span></p>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-auto border-0" @click="clearSelectedSiswa"><i class="bi bi-x-circle fs-5"></i></button>
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
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">Pilih</th>
                                <th>Komponen Tagihan</th>
                                <th>Tahun Ajaran</th>
                                <th>Bulan</th>
                                <th>Nominal Awal</th>
                                <th>Sisa Kekurangan</th>
                                <th style="width: 160px;">Bayar (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in tagihanList" :key="t.id" :class="{'table-info-light': t.selected}">
                                <td>
                                    <input class="form-check-input" type="checkbox" v-model="t.selected" @change="toggleSelectTagihan(t)">
                                </td>
                                <td class="fw-bold text-slate-800">{{ t.nama_komponen }}</td>
                                <td>{{ t.tahun_ajaran }}</td>
                                <td>
                                    <span v-if="t.bulan">{{ getBulanName(t.bulan) }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>Rp {{ formatNumber(t.nominal_tagihan) }}</td>
                                <td class="text-danger fw-semibold">Rp {{ formatNumber(t.nominal_tagihan - t.nominal_bayar) }}</td>
                                <td>
                                    <input type="number" class="form-control border-slate-200 text-end py-1 px-2" style="height: 34px;" v-model.number="t.bayar_input" :max="t.nominal_tagihan - t.nominal_bayar" :disabled="!t.selected" @input="updateTotal">
                                </td>
                            </tr>
                            <tr v-if="tagihanList.length === 0">
                                <td colspan="7" class="text-center py-4 text-muted">Seluruh tagihan sudah lunas!</td>
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

                    <!-- Input Jumlah Uang (Tunai) -->
                    <div v-if="checkoutForm.metode_pembayaran === 'Tunai'">
                        <label class="form-label fw-semibold text-slate-700">Jumlah Uang Diterima (Bayar)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200 fw-bold">Rp</span>
                            <input type="number" class="form-control border-slate-200" v-model.number="cashReceived" placeholder="0" @input="calculateKembalian" style="height: 42px;">
                        </div>
                    </div>

                    <!-- Uang Kembalian -->
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
</div>

<!-- Modal Kwitansi Print View -->
<div class="modal fade" id="modalKwitansi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-printer-fill text-blue-600 me-2"></i> Pratinjau Kuitansi Digital</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="printArea" class="border p-4 bg-white rounded-3 shadow-sm" style="font-family: 'Courier New', monospace;">
                    <div class="text-center border-bottom pb-3 mb-3">
                        <h4 class="fw-bold mb-1">SINTA SCHOOL BILLING</h4>
                        <p class="mb-0 fs-7">Bukti Transaksi Resmi Keuangan Sekolah</p>
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
                            <tr v-for="item in printData.items">
                                <td>{{ item.nama }}</td>
                                <td class="text-end">Rp {{ formatNumber(item.bayar) }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td class="text-end">TOTAL DI BAYAR</td>
                                <td class="text-end text-success">Rp {{ formatNumber(printData.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 pt-3 border-top d-flex justify-content-between text-muted fs-8">
                        <div>SINTA SaaS Unified Ledger Platform</div>
                        <div>Tanda Tangan Kasir: ___________________</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-slate-100 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                <button type="button" @click="printKwitansi" class="btn btn-primary fw-bold"><i class="bi bi-printer me-2"></i> Cetak Sekarang</button>
            </div>
        </div>
    </div>
</div>

<style>
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
        const siswaSearch = Vue.ref('');
        const siswaSuggestions = Vue.ref([]);
        const selectedSiswa = Vue.ref(null);

        const tagihanList = Vue.ref([]);
        const totalBelanja = Vue.ref(0);

        const cashReceived = Vue.ref('');
        const changeAmount = Vue.ref(0);

        const hasTunggakanLain = Vue.ref(false);

        const checkoutForm = Vue.ref({
            metode_pembayaran: 'Tunai',
            keterangan: ''
        });

        const loadingCheckout = Vue.ref(false);

        // Kwitansi Print State
        const printData = Vue.ref({
            nomor_kwitansi: '',
            tanggal: '',
            metode: '',
            nama_siswa: '',
            nisn: '',
            kelas: '',
            items: [],
            total: 0
        });

        // Autocomplete Search
        let searchTimeout = null;
        const searchSiswa = () => {
            clearTimeout(searchTimeout);
            if (siswaSearch.value.length < 2) {
                siswaSuggestions.value = [];
                return;
            }
            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/cari-siswa?q=${encodeURIComponent(siswaSearch.value)}`);
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

        const fetchTagihanSiswa = async (siswaId) => {
            try {
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/tagihan-siswa?siswa_id=${siswaId}`);
                const res = await response.json();
                if (res.success) {
                    tagihanList.value = res.data.map(t => ({
                        ...t,
                        selected: false,
                        bayar_input: t.nominal_tagihan - t.nominal_bayar
                    }));

                    // Cek apakah ada tunggakan lebih dari 1 bulan/tahun ajaran (tunggakan lama)
                    if (tagihanList.value.length > 1) {
                        hasTunggakanLain.value = true;
                    }
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
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/bayar', {
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
                    // Set data print
                    printData.value = {
                        nomor_kwitansi: res.nomor_kwitansi,
                        tanggal: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                        metode: checkoutForm.value.metode_pembayaran,
                        nama_siswa: selectedSiswa.value.nama,
                        nisn: selectedSiswa.value.nisn,
                        kelas: selectedSiswa.value.nama_kelas,
                        items: tagihanList.value.filter(t => t.selected).map(t => ({
                            nama: t.nama_komponen + (t.bulan ? ` (${getBulanName(t.bulan)})` : ''),
                            bayar: t.bayar_input
                        })),
                        total: totalBelanja.value
                    };

                    // Tampilkan modal print
                    const modalEl = new bootstrap.Modal(document.getElementById('modalKwitansi'));
                    modalEl.show();

                    // Reload data tagihan
                    fetchTagihanSiswa(selectedSiswa.value.id);
                    totalBelanja.value = 0;
                    cashReceived.value = '';
                    changeAmount.value = 0;
                    checkoutForm.value.keterangan = '';
                } else {
                    alert(res.error || 'Gagal menyimpan transaksi.');
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                loadingCheckout.value = false;
            }
        };

        const printKwitansi = () => {
            const printContents = document.getElementById('printArea').innerHTML;
            const originalContents = document.body.innerHTML;
            
            const win = window.open('', '', 'height=500, width=500');
            win.document.write('<html><head><title>Cetak Kuitansi</title>');
            win.document.write('<link rel="stylesheet" href="/SINTA-SaaS/assets/css/bootstrap.min.css">');
            win.document.write('</head><body onload="window.print(); window.close();">');
            win.document.write(printContents);
            win.document.write('</body></html>');
            win.document.close();
        };

        // Helpers
        const getBulanName = (bln) => {
            const list = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return list[bln] || '';
        };

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num);
        };

        return {
            siswaSearch,
            siswaSuggestions,
            selectedSiswa,
            tagihanList,
            totalBelanja,
            cashReceived,
            changeAmount,
            hasTunggakanLain,
            checkoutForm,
            loadingCheckout,
            printData,
            searchSiswa,
            selectSiswa,
            clearSelectedSiswa,
            toggleSelectTagihan,
            updateTotal,
            calculateKembalian,
            checkoutPembayaran,
            printKwitansi,
            getBulanName,
            formatNumber
        };
    }
});
</script>
