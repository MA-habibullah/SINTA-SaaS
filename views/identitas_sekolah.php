<?php
/**
 * View: Identitas Sekolah (Profile/Tenant Settings)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">Identitas Sekolah</h2>
        <p class="text-muted fs-7">Kelola identitas pokok sekolah dan konfigurasi platform SaaS untuk sekolah Anda.</p>
    </div>
</div>

<div id="schoolIdentityApp" v-cloak class="row g-4 mb-5">
    <!-- Left Column: School Summary Card -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm p-4 text-center h-100" style="background-color: #ffffff;">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3 mx-auto" style="width: 80px; height: 80px;">
                <i class="bi bi-info-circle-fill" style="font-size: 2.5rem;"></i>
            </div>
            
            <h4 class="fw-bold text-dark mb-1">{{ schoolData.nama_sekolah }}</h4>
            <p class="text-muted fs-8 font-monospace mb-3">NPSN: {{ schoolData.npsn }}</p>
            
            <hr class="my-3">
            
            <div class="text-start">
                <div class="mb-3">
                    <label class="text-muted fs-9 text-uppercase fw-bold d-block mb-1">Subdomain Platform</label>
                    <span class="fs-8 fw-semibold text-dark font-monospace">https://{{ schoolData.subdomain }}.dapodikspmb.id</span>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted fs-9 text-uppercase fw-bold d-block mb-1">Paket SaaS</label>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-2.5 py-1.5 rounded-3 fs-9 font-monospace">
                        <i class="bi bi-award me-1"></i> {{ schoolData.paket_aktif }}
                    </span>
                </div>

                <div class="mb-3">
                    <label class="text-muted fs-9 text-uppercase fw-bold d-block mb-1">Status Sinkronisasi</label>
                    <span :class="{
                        'bg-success bg-opacity-10 text-success border-success border-opacity-20': schoolData.status_sinkronisasi === 'Tersinkronisasi',
                        'bg-warning bg-opacity-10 text-warning-emphasis border-warning border-opacity-20': schoolData.status_sinkronisasi !== 'Tersinkronisasi'
                    }" class="badge border px-2.5 py-1.5 rounded-3 fs-9 font-monospace">
                        <i :class="schoolData.status_sinkronisasi === 'Tersinkronisasi' ? 'bi-check-circle' : 'bi-arrow-repeat'" class="bi me-1"></i>
                        {{ schoolData.status_sinkronisasi }}
                    </span>
                </div>

                <div>
                    <label class="text-muted fs-9 text-uppercase fw-bold d-block mb-1">Status Langganan</label>
                    <span :class="{
                        'bg-success bg-opacity-10 text-success border-success border-opacity-20': schoolData.status === 'active',
                        'bg-danger bg-opacity-10 text-danger border-danger border-opacity-20': schoolData.status !== 'active'
                    }" class="badge border px-2.5 py-1.5 rounded-3 fs-9 font-monospace text-uppercase">
                        {{ schoolData.status === 'active' ? 'Aktif' : 'Ditangguhkan' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Edit Profile Form -->
    <div class="col-lg-8">
        <div class="card border-0 rounded-4 shadow-sm p-4" style="background-color: #ffffff;">
            <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom">
                <i class="bi bi-pencil-square me-2 text-primary"></i> Ubah Profil Sekolah
            </h5>
            
            <form @submit.prevent="submitForm">
                <!-- Row 1: Nama Sekolah & NPSN -->
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label for="nama_sekolah" class="form-label fs-8 fw-semibold text-secondary">Nama Sekolah <span class="text-danger">*</span></label>
                        <input type="text" id="nama_sekolah" 
                               class="form-control rounded-3 fs-8" 
                               v-model="form.nama_sekolah" 
                               :class="{'is-invalid': errors.nama_sekolah}"
                               required>
                        <div class="invalid-feedback fs-9" v-if="errors.nama_sekolah">
                            {{ errors.nama_sekolah[0] }}
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="npsn" class="form-label fs-8 fw-semibold text-secondary">Nomor NPSN <span class="text-danger">*</span></label>
                        <input type="text" id="npsn" 
                               class="form-control rounded-3 fs-8 font-monospace" 
                               v-model="form.npsn" 
                               @input="checkNpsnUnique"
                               :class="{'is-invalid': errors.npsn}"
                               maxlength="8"
                               required>
                        <div class="invalid-feedback fs-9" v-if="errors.npsn">
                            {{ errors.npsn[0] }}
                        </div>
                        <small class="text-success fs-9 d-block mt-1" v-if="npsnAvailable && !errors.npsn">
                            <i class="bi bi-check-circle-fill me-1"></i> {{ npsnMessage }}
                        </small>
                    </div>
                </div>

                <!-- Row 2: Subdomain -->
                <div class="mb-4">
                    <label for="subdomain" class="form-label fs-8 fw-semibold text-secondary">Subdomain Platform <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted fs-8 font-monospace rounded-start-3">https://</span>
                        <input type="text" id="subdomain" 
                               class="form-control fs-8 font-monospace" 
                               v-model="form.subdomain" 
                               :class="{'is-invalid': errors.subdomain}"
                               placeholder="nama-sekolah"
                               required>
                        <span class="input-group-text bg-light text-muted fs-8 font-monospace rounded-end-3">.dapodikspmb.id</span>
                        <div class="invalid-feedback fs-9" v-if="errors.subdomain">
                            {{ errors.subdomain[0] }}
                        </div>
                    </div>
                    <div class="form-text fs-9 text-muted mt-1">Hanya huruf kecil, angka, dan tanda hubung (-). Mengubah subdomain akan mempengaruhi URL login sekolah Anda.</div>
                </div>

                <!-- Row 3: Custom Domain & UUID (Read Only) -->
                <div class="row g-3 mb-4 bg-light p-3 rounded-3 border">
                    <div class="col-md-6">
                        <label class="form-label fs-9 fw-bold text-uppercase text-secondary mb-1">Domain Kustom</label>
                        <div class="fs-8 text-dark fw-semibold font-monospace">
                            {{ schoolData.domain || 'Tidak Ada (Menggunakan Subdomain Standard)' }}
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fs-9 fw-bold text-uppercase text-secondary mb-1">UUID Tenant Sekolah</label>
                        <div class="fs-8 text-muted font-monospace">
                            {{ schoolData.id }}
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fs-8 fw-semibold shadow-sm" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i v-else class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Vue App Registration Script -->
<script>
{
    const { ref, reactive } = Vue;

    window.VueAppRegistry.register('#schoolIdentityApp', {
        setup() {
            // Inject initial tenant data from PHP securely
            const schoolData = ref(<?= json_encode($tenant, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>);
            
            const form = reactive({
                nama_sekolah: schoolData.value.nama_sekolah,
                npsn: schoolData.value.npsn,
                subdomain: schoolData.value.subdomain
            });

            const errors = ref({});
            const saving = ref(false);
            
            // NPSN Availability
            const npsnAvailable = ref(false);
            const npsnMessage = ref('');
            let npsnTimeout = null;

            // Debounced unique check for NPSN
            const checkNpsnUnique = () => {
                npsnAvailable.value = false;
                npsnMessage.value = '';
                
                if (form.npsn.length !== 8 || !/^[0-9]+$/.test(form.npsn)) {
                    errors.value.npsn = ['NPSN harus berupa 8 digit angka.'];
                    return;
                }
                
                delete errors.value.npsn;

                clearTimeout(npsnTimeout);
                npsnTimeout = setTimeout(async () => {
                    try {
                        const response = await axios.get('/dapodik-spmb/api/v1/tenant/check-npsn', {
                            params: {
                                npsn: form.npsn,
                                exclude_id: schoolData.value.id
                            }
                        });
                        
                        if (response.data.available) {
                            npsnAvailable.value = true;
                            npsnMessage.value = response.data.message;
                        } else {
                            errors.value.npsn = [response.data.error || 'NPSN sudah terdaftar.'];
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }, 400);
            };

            // Form Submit handler
            const submitForm = async () => {
                saving.value = true;
                errors.value = {};
                
                try {
                    const response = await axios.post('/dapodik-spmb/api/v1/tenant/update', {
                        nama_sekolah: form.nama_sekolah,
                        npsn: form.npsn,
                        subdomain: form.subdomain
                    });

                    if (response.data && response.data.success) {
                        // Update local data view state
                        schoolData.value.nama_sekolah = response.data.data.nama_sekolah;
                        schoolData.value.npsn = response.data.data.npsn;
                        schoolData.value.subdomain = response.data.data.subdomain;
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembaruan Berhasil',
                            text: response.data.message || 'Profil sekolah berhasil diperbarui.',
                            confirmButtonColor: '#2563eb'
                        }).then(() => {
                            // Update sidebar / navbar branding if name changes
                            const brandNameEls = document.querySelectorAll('.sidebar-brand-name, .navbar-brand-name');
                            brandNameEls.forEach(el => {
                                el.textContent = form.nama_sekolah;
                            });
                        });
                    } else {
                        throw new Error(response.data.error || 'Gagal menyimpan perubahan.');
                    }
                } catch (err) {
                    console.error(err);
                    if (err.response && err.response.status === 422) {
                        errors.value = err.response.data.errors || {};
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            text: err.response?.data?.error || err.message || 'Terjadi kesalahan sistem.'
                        });
                    }
                } finally {
                    saving.value = false;
                }
            };

            return {
                schoolData,
                form,
                errors,
                saving,
                npsnAvailable,
                npsnMessage,
                checkNpsnUnique,
                submitForm
            };
        }
    });
}
</script>
