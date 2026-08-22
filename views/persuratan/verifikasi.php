<?php
/**
 * View: Verifikasi Keabsahan Tanda Tangan Elektronik (TTE) & QR Code Naskah Dinas
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'verifikasi';
$pageTitle = 'Verifikasi Keabsahan TTE & QR Code';
$pageSubtitle = 'Layanan validasi integritas digital naskah dinas resmi sekolah berbasis token kriptografi QR Code tersertifikasi.';
$pageIcon = 'bi-patch-check-fill';
?>
<div id="persuratanVerifikasiApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Integritas Naskah Dinas';
    $pageTitle = 'Verifikasi Keabsahan Dokumen & TTE';
    $pageSubtitle = 'Layanan validasi integritas digital naskah dinas resmi sekolah berbasis token kriptografi QR Code tersertifikasi.';
    $pageIcon = 'bi-patch-check-fill';
    include __DIR__ . '/_hero_header.php'; 
    ?>

    <div class="row g-4 mb-5">
        <!-- Input Token Form -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4 h-100">
                <div class="text-center pb-3 border-b border-slate-100 mb-3">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 d-inline-flex align-items-center justify-content-center fs-3 mb-2 shadow-2xs">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <h6 class="font-bold text-slate-800 fs-6 mb-1">Cek Keaslian Naskah Dinas</h6>
                    <p class="text-slate-400 text-xs mb-0">Masukkan token QR Code yang tertera pada lembar fisik surat.</p>
                </div>

                <form @submit.prevent="verifyToken" class="text-xs">
                    <div class="mb-3">
                        <label class="form-label font-bold text-slate-700">Token QR Code / UUID Validasi <span class="text-rose-500">*</span></label>
                        <textarea v-model="tokenInput" rows="3" class="form-control form-control-sm rounded-xl font-mono text-xs" placeholder="Ketik atau tempel (paste) kode token TTE di sini..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-xl font-bold py-2 text-xs shadow-2xs d-flex align-items-center justify-content-center gap-1.5" :disabled="verifying">
                        <span v-if="verifying" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-shield-check"></i>
                        <span>Verifikasi Keabsahan Dokumen</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Verification Result Box -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4 h-100">
                <h6 class="font-bold text-slate-800 fs-6 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-patch-check-fill text-blue-600"></i>
                    Status Hasil Pemeriksaan Integritas Dokumen
                </h6>

                <div v-if="verifying" class="text-center py-5 text-slate-400 text-xs">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    Memeriksa keabsahan naskah dinas di basis data kearsipan...
                </div>

                <div v-else-if="!result" class="text-center py-5">
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2 shadow-2xs">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div class="font-bold text-slate-700 text-sm mb-1">Menunggu Input Token</div>
                    <p class="text-slate-400 text-xs mb-0">Silakan masukkan token QR Code di sebelah kiri untuk melihat hasil verifikasi keaslian naskah.</p>
                </div>

                <div v-else-if="result.valid" class="animate-fade-in text-xs">
                    <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl mb-3 d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white d-flex align-items-center justify-content-center fs-5 flex-shrink-0">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <div>
                            <div class="font-black text-emerald-800 text-sm">DOKUMEN RESMI &amp; TERSERTIFIKASI ASLI</div>
                            <div class="text-emerald-700 text-xs">Tanda tangan elektronik pimpinan valid dan terdaftar di database kearsipan sekolah.</div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 mb-3">
                        <div class="row g-2">
                            <div class="col-4 text-slate-400">Nomor Surat:</div>
                            <div class="col-8 font-mono font-bold text-blue-700">{{ result.data.nomor_surat }}</div>

                            <div class="col-4 text-slate-400">Perihal:</div>
                            <div class="col-8 font-semibold text-slate-800">{{ result.data.perihal }}</div>

                            <div class="col-4 text-slate-400">Tujuan Surat:</div>
                            <div class="col-8 text-slate-700">{{ result.data.tujuan }}</div>

                            <div class="col-4 text-slate-400">Tanggal Terbit:</div>
                            <div class="col-8 text-slate-700">{{ result.data.tgl_surat }}</div>

                            <div class="col-4 text-slate-400">Penandatangan:</div>
                            <div class="col-8 font-bold text-slate-800">{{ result.data.nama_penandatangan }} ({{ result.data.jabatan_penandatangan || 'Kepala Sekolah' }})</div>

                            <div class="col-4 text-slate-400">Token ID:</div>
                            <div class="col-8 font-mono text-[10px] text-slate-500 break-all">{{ result.data.qr_token }}</div>
                        </div>
                    </div>
                </div>

                <div v-else class="animate-fade-in text-xs">
                    <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl mb-3 d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-600 text-white d-flex align-items-center justify-content-center fs-5 flex-shrink-0">
                            <i class="bi bi-x-lg"></i>
                        </div>
                        <div>
                            <div class="font-black text-rose-800 text-sm">DOKUMEN TIDAK DITEMUKAN / TIDAK VALID</div>
                            <div class="text-rose-700 text-xs">Token QR Code tidak terdaftar atau naskah telah kedaluwarsa/ditarik dari peredaran.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, onMounted } = Vue;

    const persuratanVerifikasiAppConfig = {
        setup() {
            const tokenInput = ref('');
            const verifying = ref(false);
            const result = ref(null);

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const initialToken = urlParams.get('token') || '';

            const verifyToken = async () => {
                if (!tokenInput.value.trim()) return;
                verifying.value = true;
                result.value = null;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/verifikasi/cek', {
                        token: tokenInput.value.trim(),
                        tenant_id: currentTenantId
                    });
                    if (res.data && res.data.success && res.data.data) {
                        result.value = { valid: true, data: res.data.data };
                    } else {
                        result.value = { valid: false };
                    }
                } catch (e) {
                    result.value = { valid: false };
                } finally {
                    verifying.value = false;
                }
            };

            onMounted(() => {
                if (initialToken) {
                    tokenInput.value = initialToken;
                    verifyToken();
                }
            });

            return {
                tokenInput,
                verifying,
                result,
                verifyToken
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanVerifikasiApp', persuratanVerifikasiAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanVerifikasiAppConfig).mount('#persuratanVerifikasiApp');
        });
    }
}
</script>
