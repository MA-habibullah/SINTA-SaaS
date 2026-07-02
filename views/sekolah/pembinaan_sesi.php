<div class="container-fluid px-4 py-4">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="h3 mb-0 text-slate-800 font-bold">Pelaksanaan Sesi Mentoring</h1>
            <p class="text-slate-500 text-sm mt-1">Pembinaan: <?= htmlspecialchars($sesi['nama_guru']) ?></p>
        </div>
        <a href="/SINTA-SaaS/pembinaan<?= $tenantParam ?>" class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-4">
        <!-- Informasi Kasus -->
        <div class="col-12 col-lg-4">
            <div class="glass-card rounded-3xl p-6 h-100">
                <h6 class="font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3">Informasi Kasus</h6>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Nama Guru</label>
                    <div class="font-bold text-slate-800"><?= htmlspecialchars($sesi['nama_guru']) ?></div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Kategori Masalah</label>
                    <span class="badge bg-red-100 text-red-600 border border-red-200 px-3 py-1.5"><?= htmlspecialchars($sesi['kategori_masalah']) ?></span>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Jadwal Sesi</label>
                    <div class="text-sm font-medium text-slate-700">
                        <i class="bi bi-calendar-check text-blue-500 mr-1"></i> <?= date('d F Y, H:i', strtotime($sesi['tanggal_sesi'])) ?>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-2">Deskripsi Awal (Laporan)</label>
                    <div class="bg-slate-50 p-4 rounded-xl text-sm text-slate-600 border border-slate-100 leading-relaxed">
                        "<?= htmlspecialchars($sesi['deskripsi_kasus']) ?>"
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Sesi -->
        <div class="col-12 col-lg-8">
            <div class="glass-card rounded-3xl p-6">
                <form action="/SINTA-SaaS/pembinaan/sesi/simpan" method="POST" id="formSesi">
                    <input type="hidden" name="tenant_id" value="<?= isset($_GET['tenant_id']) ? htmlspecialchars($_GET['tenant_id']) : '' ?>">
                    <input type="hidden" name="sesi_id" value="<?= $sesi['id'] ?>">
                    <input type="hidden" name="monitoring_id" value="<?= $sesi['monitoring_id'] ?>">
                    
                    <h6 class="font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="bi bi-journal-text text-blue-500"></i> Catatan Mentoring
                    </h6>
                    
                    <div class="mb-4">
                        <label class="form-label font-semibold text-slate-700">Fakta / Akar Masalah (Hasil Klarifikasi)</label>
                        <p class="text-xs text-slate-500 mb-2">Tuliskan hasil diskusi dan klarifikasi dari sudut pandang guru.</p>
                        <textarea name="catatan_fakta" rows="4" class="form-control border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500" required placeholder="Contoh: Guru mengakui sering terlambat karena ada urusan mengantar anak..."></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label class="form-label font-semibold text-slate-700">Rencana Tindak Lanjut (RTL) / Komitmen</label>
                        <p class="text-xs text-slate-500 mb-2">Apa kesepakatan langkah perbaikan yang akan dilakukan guru ke depannya?</p>
                        <textarea name="rencana_tindak_lanjut" rows="4" class="form-control border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500" required placeholder="Contoh: 1. Akan berangkat 15 menit lebih awal. 2. Mengisi presensi tepat waktu."></textarea>
                    </div>
                    
                    <!-- Tanda Tangan Digital -->
                    <h6 class="font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3 flex items-center gap-2 mt-8">
                        <i class="bi bi-pen text-purple-500"></i> Pengesahan Tanda Tangan Digital
                    </h6>
                    
                    <div class="row g-4 mb-6">
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-slate-700 block text-center mb-2">Tanda Tangan Kepala Sekolah</label>
                            <div class="border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 relative overflow-hidden" style="height: 200px;">
                                <canvas id="pad-kepsek" class="w-100 h-100 touch-action-none"></canvas>
                                <button type="button" class="btn btn-sm btn-light absolute top-2 right-2 border shadow-sm z-10" onclick="clearPadKepsek()">Ulangi</button>
                            </div>
                            <input type="hidden" name="ttd_kepsek" id="ttd_kepsek_input" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-slate-700 block text-center mb-2">Tanda Tangan Guru</label>
                            <div class="border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 relative overflow-hidden" style="height: 200px;">
                                <canvas id="pad-guru" class="w-100 h-100 touch-action-none"></canvas>
                                <button type="button" class="btn btn-sm btn-light absolute top-2 right-2 border shadow-sm z-10" onclick="clearPadGuru()">Ulangi</button>
                            </div>
                            <input type="hidden" name="ttd_guru" id="ttd_guru_input" required>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 text-blue-800 p-4 rounded-xl border border-blue-100 mb-6 text-sm">
                        <i class="bi bi-info-circle-fill mr-1"></i> Dengan menyimpan formulir ini, maka Sesi Mentoring dianggap Selesai, dan status kasus guru akan otomatis berubah menjadi <strong>Kuning (Masa Pemantauan)</strong>.
                    </div>
                    
                    <button type="button" onclick="submitSesiForm()" class="btn bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl py-3 px-4 transition-colors w-100 text-lg shadow-md shadow-blue-500/30">
                        <i class="bi bi-check2-circle mr-1"></i> Simpan Sesi & Tetapkan Komitmen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.padKepsek = null;
window.padGuru = null;

function initSignaturePads() {
    // Pastikan SignaturePad sudah ada
    if (typeof SignaturePad === 'undefined') {
        setTimeout(initSignaturePads, 50);
        return;
    }
    
    // Inisialisasi Canvas Kepsek
    const canvasKepsek = document.getElementById('pad-kepsek');
    if (canvasKepsek) {
        resizeCanvas(canvasKepsek);
        window.padKepsek = new SignaturePad(canvasKepsek, {
            penColor: "rgb(15, 23, 42)" // slate-900
        });
    }
    
    // Inisialisasi Canvas Guru
    const canvasGuru = document.getElementById('pad-guru');
    if (canvasGuru) {
        resizeCanvas(canvasGuru);
        window.padGuru = new SignaturePad(canvasGuru, {
            penColor: "rgb(15, 23, 42)"
        });
    }
    
    // Resize handler to avoid blurry lines
    window.addEventListener("resize", () => {
        if(canvasKepsek) resizeCanvas(canvasKepsek);
        if(canvasGuru) resizeCanvas(canvasGuru);
    });
}

function startInit() {
    if (typeof SignaturePad === 'undefined') {
        const script = document.createElement('script');
        script.src = "https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js";
        script.onload = initSignaturePads;
        document.head.appendChild(script);
    } else {
        initSignaturePads();
    }
}

// Kompatibilitas dengan Turbo (Hotwire) dan pemuatan normal
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", startInit);
} else {
    startInit();
}
document.addEventListener("turbo:load", startInit);

function clearPadKepsek() {
    if (window.padKepsek) window.padKepsek.clear();
}

function clearPadGuru() {
    if (window.padGuru) window.padGuru.clear();
}

function resizeCanvas(canvas) {
    const ratio =  Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext("2d").scale(ratio, ratio);
}

function submitSesiForm() {
    if (!window.padKepsek || window.padKepsek.isEmpty()) {
        alert("Kepala Sekolah belum menandatangani!");
        return;
    }
    if (!window.padGuru || window.padGuru.isEmpty()) {
        alert("Guru belum menandatangani!");
        return;
    }
    
    // Convert to Base64
    document.getElementById('ttd_kepsek_input').value = window.padKepsek.toDataURL();
    document.getElementById('ttd_guru_input').value = window.padGuru.toDataURL();
    
    // Validasi input
    const form = document.getElementById('formSesi');
    if (!form.catatan_fakta.value || !form.rencana_tindak_lanjut.value) {
        alert("Harap lengkapi Catatan Fakta dan Rencana Tindak Lanjut!");
        return;
    }
    
    form.submit();
}
</script>
