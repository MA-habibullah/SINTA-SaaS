<?php
/**
 * View: Kelola Sekolah (SaaS Tenant Management)
 * Terintegrasi dengan Tailwind CSS, Vue.js, Axios, dan SweetAlert2
 */
?>

<!-- Load Tailwind CSS CDN and disable preflight to prevent conflict with Bootstrap core styles -->
<script>
    // Suppress tailwind CDN production warning in console
    (function() {
        const origWarn = console.warn;
        console.warn = function(...args) {
            if (typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
            origWarn.apply(console, args);
        };
    })();

    // Configure Tailwind config BEFORE loading tailwindcss.js to avoid race conditions under Turbo
    window.tailwind = {
        config: {
            corePlugins: {
                preflight: false, // Disable preflight to keep global layout intact
            },
            theme: {
                extend: {
                    colors: {
                        'saas-blue': '#2563eb',
                        'saas-hover': '#1d4ed8',
                        'saas-light': '#eff6ff',
                    }
                }
            }
        }
    };
</script>
<script src="/SINTA-SaaS/assets/js/tailwindcss.js"></script>

<style>
    /* Fix Bootstrap collapse conflict with Tailwind CSS Play CDN */
    .collapse:not(.show) {
        display: none !important;
    }
    .collapsing {
        height: 0;
        overflow: hidden;
        transition: height 0.35s ease;
    }
    .collapse {
        visibility: visible !important;
    }
</style>

<!-- Area Konten Utama Terbungkus Vue.js App -->
<div id="tenantManagementApp" v-cloak>

    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ title }}</h2>
            <p class="text-slate-600 fs-7">Kelola data instansi sekolah (tenant), paket berlangganan, subdomain, status sinkronisasi, dan kontrol akses secara terpusat.</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button 
                type="button" 
                class="btn btn-primary btn-sm d-flex align-items-center rounded-3 px-3 py-2 fs-7 fw-semibold shadow-sm"
                @click="openAddModal"
            >
                <i class="bi bi-plus-lg me-2"></i> Tambah Sekolah Baru
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Tenants -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fs-8 fw-semibold uppercase tracking-wider block mb-1">Total Sekolah</span>
                        <h3 class="fw-extrabold text-dark m-0">{{ totalTenants }}</h3>
                    </div>
                    <div class="bg-blue-50 text-blue-600 rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-buildings fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Active Tenants -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fs-8 fw-semibold uppercase tracking-wider block mb-1">Tenant Aktif</span>
                        <h3 class="fw-extrabold text-emerald-600 m-0">{{ activeTenants }}</h3>
                    </div>
                    <div class="bg-emerald-50 text-emerald-600 rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Inactive / Suspended Tenants -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fs-8 fw-semibold uppercase tracking-wider block mb-1">Ditangguhkan / Nonaktif</span>
                        <h3 class="fw-extrabold text-rose-600 m-0">{{ suspendedTenants }}</h3>
                    </div>
                    <div class="bg-rose-50 text-rose-600 rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Synchronized Tenants -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fs-8 fw-semibold uppercase tracking-wider block mb-1">Tersinkronisasi</span>
                        <h3 class="fw-extrabold text-indigo-600 m-0">{{ syncedTenants }}</h3>
                    </div>
                    <div class="bg-indigo-50 text-indigo-600 rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-cloud-check-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        
        <!-- Search and Quick Actions Bar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="position-relative" style="max-width: 380px; width: 100%;">
                <span class="position-absolute translate-middle-y text-slate-500" style="top: 50%; left: 14px;">
                    <i class="bi bi-search"></i>
                </span>
                <label for="searchQuery" class="visually-hidden">Cari Sekolah</label>
                <input 
                    id="searchQuery"
                    name="search_query"
                    type="text" 
                    class="form-control rounded-3 py-2 ps-5 fs-7 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                    placeholder="Cari nama sekolah, NPSN, atau subdomain..."
                    v-model="searchQuery"
                >
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <span class="text-slate-600 fs-8">Menampilkan <strong>{{ filteredTenants.length }}</strong> sekolah</span>
            </div>
        </div>

        <!-- Datatable -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border-slate-100">
                <thead class="bg-slate-50 border-bottom">
                    <tr>
                        <th class="py-3 text-slate-500 fw-bold fs-8 text-center" style="width: 60px;">No</th>
                        <th class="py-3 text-slate-500 fw-bold fs-8">Nama Sekolah</th>
                        <th class="py-3 text-slate-500 fw-bold fs-8 text-center" style="width: 110px;">NPSN</th>
                        <th class="py-3 text-slate-500 fw-bold fs-8">Subdomain / Domain</th>
                        <th class="py-3 text-slate-500 fw-bold fs-8">Paket Langganan</th>
                        <th class="py-3 text-slate-500 fw-bold fs-8" style="width: 170px;">Status Sinkronisasi</th>
                        <th class="py-3 text-slate-500 fw-bold fs-8" style="width: 140px;">Status Akses</th>
                        <th class="py-3 text-slate-500 fw-bold fs-8 text-center" style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <tr v-for="(tenant, idx) in filteredTenants" :key="tenant.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="text-center text-slate-500 fs-7 py-3">{{ idx + 1 }}</td>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-blue-100 text-blue-700 rounded-3 d-flex align-items-center justify-content-center me-2.5 font-bold" style="width: 38px; height: 38px;">
                                    {{ getInitials(tenant.nama_sekolah) }}
                                </div>
                                <div>
                                    <h6 class="fw-bold text-slate-900 m-0 fs-7">{{ tenant.nama_sekolah }}</h6>
                                    <span class="text-muted fs-9 font-monospace">{{ tenant.id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center py-3">
                            <span class="badge bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-1.5 rounded-3 fs-8 font-monospace">
                                {{ tenant.npsn }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="badge bg-blue-50 text-blue-700 px-2 py-1 rounded-3 fs-8 font-semibold">
                                        {{ tenant.subdomain }}.dapodikspmb.id
                                    </span>
                                </div>
                                <div v-if="tenant.domain" class="d-flex align-items-center gap-1.5 text-muted fs-8">
                                    <i class="bi bi-globe text-slate-400"></i>
                                    <span>{{ tenant.domain }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="badge px-2.5 py-1.5 rounded-3 fs-8 fw-semibold" :class="getPaketBadgeClass(tenant.paket_aktif)">
                                    <i class="bi bi-gem me-1" v-if="tenant.paket_aktif === 'Premium SaaS'"></i>
                                    <i class="bi bi-award me-1" v-else-if="tenant.paket_aktif === 'Enterprise SaaS'"></i>
                                    <i class="bi bi-shield-shaded me-1" v-else-if="tenant.paket_aktif === 'Pro'"></i>
                                    <i class="bi bi-patch-check me-1" v-else></i>
                                    {{ tenant.paket_aktif }}
                                </span>
                            </div>
                        </td>
                        <td class="py-3">
                            <span class="badge px-2.5 py-1.5 rounded-3 fs-8 fw-semibold d-inline-flex align-items-center gap-1" :class="getSinkronisasiBadgeClass(tenant.status_sinkronisasi)">
                                <span class="rounded-circle" style="width: 6px; height: 6px;" :class="getSinkronisasiDotClass(tenant.status_sinkronisasi)"></span>
                                {{ tenant.status_sinkronisasi }}
                            </span>
                        </td>
                        <td class="py-3">
                            <span class="badge px-2.5 py-1.5 rounded-3 fs-8 fw-semibold d-inline-flex align-items-center gap-1" :class="getStatusBadgeClass(tenant.status)">
                                <span class="rounded-circle animate-pulse" style="width: 6px; height: 6px;" :class="getStatusDotClass(tenant.status)"></span>
                                <span class="capitalize">{{ tenant.status === 'active' ? 'Active' : (tenant.status === 'suspended' ? 'Suspended' : 'Inactive') }}</span>
                            </span>
                        </td>
                        <td class="text-center py-3">
                            <div class="d-flex justify-content-center gap-1.5">
                                <!-- Edit Button -->
                                <button 
                                    type="button" 
                                    class="btn btn-outline-primary btn-sm rounded-3 px-2 py-1.5 fs-8 d-flex align-items-center gap-1 border-slate-200 text-blue-600 hover:text-white hover:bg-blue-600"
                                    title="Edit Sekolah"
                                    @click="openEditModal(tenant)"
                                >
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                
                                <!-- Toggle Active Status Button (Nonaktifkan / Aktifkan) -->
                                <button 
                                    v-if="tenant.status === 'active'"
                                    type="button" 
                                    class="btn btn-outline-warning btn-sm rounded-3 px-2 py-1.5 fs-8 d-flex align-items-center gap-1 border-slate-200 text-amber-700 hover:text-white hover:bg-amber-600"
                                    title="Nonaktifkan Sekolah"
                                    @click="toggleActiveStatus(tenant, 'inactive')"
                                >
                                    <i class="bi bi-shield-slash"></i> Nonaktifkan
                                </button>
                                <button 
                                    v-else
                                    type="button" 
                                    class="btn btn-outline-success btn-sm rounded-3 px-2 py-1.5 fs-8 d-flex align-items-center gap-1 border-slate-200 text-emerald-700 hover:text-white hover:bg-emerald-600"
                                    title="Aktifkan Sekolah"
                                    @click="toggleActiveStatus(tenant, 'active')"
                                >
                                    <i class="bi bi-shield-check"></i> Aktifkan
                                </button>

                                <!-- Delete Button -->
                                <button 
                                    type="button" 
                                    class="btn btn-outline-danger btn-sm rounded-3 px-2 py-1.5 fs-8 d-flex align-items-center gap-1 border-slate-200 text-rose-700 hover:text-white hover:bg-rose-600"
                                    title="Hapus Sekolah (Soft Delete)"
                                    @click="deleteTenant(tenant)"
                                >
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filteredTenants.length === 0">
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <div class="bg-slate-100 text-slate-400 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; font-size: 1.75rem;">
                                    <i class="bi bi-building"></i>
                                </div>
                                <h5 class="fw-bold text-slate-800 mb-1">Sekolah Tidak Ditemukan</h5>
                                <p class="text-muted fs-7 mx-auto" style="max-width: 400px;">Tidak ada data sekolah/tenant yang sesuai dengan pencarian Anda atau database masih kosong.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Form Modal (Tambah / Edit Sekolah) -->
    <div class="modal fade" id="tenantModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-2xl rounded-2xl overflow-hidden bg-white">
                
                <!-- Modal Header (Biru SaaS Gradasi) -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 py-3 px-4 d-flex justify-content-between align-items-center border-0">
                    <div>
                        <h5 class="modal-title fw-bold text-white fs-6 m-0">
                            <i class="bi bi-building me-2"></i>
                            {{ isEditMode ? 'Perbarui Profil Sekolah (Tenant)' : 'Pendaftaran Sekolah Baru' }}
                        </h5>
                        <p class="text-blue-100 fs-9 m-0 mt-0.5">Semua data akan divalidasi oleh sistem SaaS secara real-time.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white focus:outline-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form @submit.prevent="submitForm">
                    <div class="modal-body p-4 bg-slate-50/50">
                        <!-- Navigation Tabs inside Modal -->
                        <ul class="nav nav-tabs mb-4 fs-8" id="tenantModalTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold text-slate-700 border-slate-200" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="true">
                                    <i class="bi bi-info-circle me-1"></i> Profil & Routing
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold text-slate-700 border-slate-200" id="subscription-tab" data-bs-toggle="tab" data-bs-target="#subscription-tab-pane" type="button" role="tab" aria-controls="subscription-tab-pane" aria-selected="false">
                                    <i class="bi bi-gem me-1"></i> Paket Langganan & Kapasitas
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="tenantModalTabContent">
                            
                            <!-- TAB 1: PROFIL & ROUTING -->
                            <div class="tab-pane fade show active" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                                <div class="row g-3">
                                    <!-- Section 1: Data Identitas Sekolah -->
                                    <div class="col-12 mt-1">
                                        <div class="border-bottom pb-1 mb-2">
                                            <span class="fs-8 fw-bold text-blue-600 uppercase tracking-wider">
                                                <i class="bi bi-info-circle me-1.5"></i>Informasi Identitas Pokok
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Input Nama Sekolah -->
                                    <div class="col-12 col-md-8">
                                        <label for="modal_nama_sekolah" class="form-label fw-bold fs-8 text-slate-700 mb-1">Nama Instansi Sekolah <span class="text-rose-500">*</span></label>
                                        <input 
                                            id="modal_nama_sekolah"
                                            name="nama_sekolah"
                                            type="text" 
                                            class="form-control rounded-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                            :class="{'is-invalid': errors.nama_sekolah}" 
                                            v-model="form.nama_sekolah" 
                                            placeholder="Contoh: SMA Negeri 1 Jakarta" 
                                            required
                                        >
                                        <div class="invalid-feedback fs-9">{{ getError('nama_sekolah') }}</div>
                                    </div>

                                    <!-- Input NPSN -->
                                    <div class="col-12 col-md-4">
                                        <label for="modal_npsn" class="form-label fw-bold fs-8 text-slate-700 mb-1">NPSN (8 Digit) <span class="text-rose-500">*</span></label>
                                        <input 
                                            id="modal_npsn"
                                            name="npsn"
                                            type="text" 
                                            class="form-control rounded-3 py-2 font-monospace border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                            :class="{'is-invalid': errors.npsn}" 
                                            v-model="form.npsn" 
                                            placeholder="Contoh: 10203040" 
                                            maxlength="8"
                                            required
                                        >
                                        <div class="invalid-feedback fs-9">{{ getError('npsn') }}</div>
                                    </div>

                                    <!-- Section 2: Konfigurasi Subdomain & Routing -->
                                    <div class="col-12 mt-4">
                                        <div class="border-bottom pb-1 mb-2">
                                            <span class="fs-8 fw-bold text-blue-600 uppercase tracking-wider">
                                                <i class="bi bi-globe me-1.5"></i>Konfigurasi Routing Domain & Subdomain
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Input Subdomain -->
                                    <div class="col-12 col-md-6">
                                        <label for="modal_subdomain" class="form-label fw-bold fs-8 text-slate-700 mb-1">Subdomain Aplikasi <span class="text-rose-500">*</span></label>
                                        <div class="input-group">
                                            <input 
                                                id="modal_subdomain"
                                                name="subdomain"
                                                type="text" 
                                                class="form-control rounded-start-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-end font-semibold text-blue-700" 
                                                :class="{'is-invalid': errors.subdomain}" 
                                                v-model="form.subdomain" 
                                                placeholder="sman1jkt" 
                                                required
                                            >
                                            <span class="input-group-text bg-slate-100 border-slate-200 text-slate-500 rounded-end-3 fs-8 fw-medium">.dapodikspmb.id</span>
                                            <div class="invalid-feedback fs-9">{{ getError('subdomain') }}</div>
                                        </div>
                                        <span class="text-slate-500 fs-9 block mt-1"><i class="bi bi-info-circle me-1"></i>Hanya huruf kecil, angka, dan tanda hubung (-). Tanpa spasi.</span>
                                    </div>

                                    <!-- Input Custom Domain -->
                                    <div class="col-12 col-md-6">
                                        <label for="modal_domain" class="form-label fw-bold fs-8 text-slate-700 mb-1">Domain Kustom <small class="text-slate-500">(Opsional)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-slate-100 border-slate-200 text-slate-500 rounded-start-3 fs-8"><i class="bi bi-link-45deg"></i></span>
                                            <input 
                                                id="modal_domain"
                                                name="domain"
                                                type="text" 
                                                class="form-control rounded-end-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-700 font-monospace" 
                                                :class="{'is-invalid': errors.domain}" 
                                                v-model="form.domain" 
                                                placeholder="Contoh: sman1jakarta.sch.id"
                                            >
                                            <div class="invalid-feedback fs-9">{{ getError('domain') }}</div>
                                        </div>
                                        <span class="text-slate-500 fs-9 block mt-1"><i class="bi bi-info-circle me-1"></i>Domain kustom penuh milik instansi sekolah.</span>
                                    </div>

                                    <!-- Section 3: Status Akses & Sinkronisasi -->
                                    <div class="col-12 mt-4">
                                        <div class="border-bottom pb-1 mb-2">
                                            <span class="fs-8 fw-bold text-blue-600 uppercase tracking-wider">
                                                <i class="bi bi-sliders me-1.5"></i>Status Kontrol & Akses
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Select Status Akses -->
                                    <div class="col-12 col-md-6">
                                        <label for="modal_status" class="form-label fw-bold fs-8 text-slate-700 mb-1">Status Akses Sekolah <span class="text-rose-500">*</span></label>
                                        <select 
                                            id="modal_status"
                                            name="status"
                                            class="form-select rounded-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                            :class="{'is-invalid': errors.status}" 
                                            v-model="form.status" 
                                            required
                                        >
                                            <option value="active">Active (Aktif)</option>
                                            <option value="inactive">Inactive (Tidak Aktif)</option>
                                            <option value="suspended">Suspended (Ditangguhkan)</option>
                                        </select>
                                        <div class="invalid-feedback fs-9">{{ getError('status') }}</div>
                                    </div>

                                    <!-- Select Status Sinkronisasi -->
                                    <div class="col-12 col-md-6">
                                        <label for="modal_status_sinkronisasi" class="form-label fw-bold fs-8 text-slate-700 mb-1">Status Sinkronisasi <span class="text-rose-500">*</span></label>
                                        <select 
                                            id="modal_status_sinkronisasi"
                                            name="status_sinkronisasi"
                                            class="form-select rounded-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                            :class="{'is-invalid': errors.status_sinkronisasi}" 
                                            v-model="form.status_sinkronisasi" 
                                            required
                                        >
                                            <option value="Tersinkronisasi">Tersinkronisasi</option>
                                            <option value="Menunggu">Menunggu</option>
                                            <option value="Gagal">Gagal</option>
                                        </select>
                                        <div class="invalid-feedback fs-9">{{ getError('status_sinkronisasi') }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: PAKET LANGGANAN & KAPASITAS -->
                            <div class="tab-pane fade" id="subscription-tab-pane" role="tabpanel" aria-labelledby="subscription-tab" tabindex="0">
                                <div class="row g-3">
                                    <!-- Section Header -->
                                    <div class="col-12 mt-1">
                                        <div class="border-bottom pb-1 mb-2">
                                            <span class="fs-8 fw-bold text-blue-600 uppercase tracking-wider">
                                                <i class="bi bi-gem me-1.5"></i>Alokasi Kapasitas & Paket Fitur
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Select Paket Langganan -->
                                    <div class="col-12">
                                        <label for="modal_paket_aktif" class="form-label fw-bold fs-8 text-slate-700 mb-1">Paket Langganan Aktif <span class="text-rose-500">*</span></label>
                                        <select 
                                            id="modal_paket_aktif"
                                            name="paket_aktif"
                                            class="form-select rounded-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-semibold text-blue-800" 
                                            :class="{'is-invalid': errors.paket_aktif}" 
                                            v-model="form.paket_aktif" 
                                            @change="applyPackageDefaults"
                                            required
                                        >
                                            <option value="Basic">Basic Edition</option>
                                            <option value="Pro">Pro Edition</option>
                                            <option value="Premium SaaS">Premium SaaS</option>
                                            <option value="Enterprise SaaS">Enterprise SaaS</option>
                                        </select>
                                        <div class="invalid-feedback fs-9">{{ getError('paket_aktif') }}</div>
                                        <span class="text-slate-500 fs-9 block mt-1"><i class="bi bi-info-circle"></i> Memilih paket akan otomatis mengisi nilai default di bawah, namun Anda tetap bisa mengubahnya secara manual.</span>
                                    </div>

                                    <!-- Quota Storage -->
                                    <div class="col-12 col-md-4">
                                        <label for="modal_storage_limit_mb" class="form-label fw-bold fs-8 text-slate-700 mb-1">Kuota Penyimpanan <span class="text-rose-500">*</span></label>
                                        <div class="input-group">
                                            <input 
                                                id="modal_storage_limit_mb"
                                                name="storage_limit_mb"
                                                type="number" 
                                                class="form-control rounded-start-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-semibold text-slate-700" 
                                                :class="{'is-invalid': errors.storage_limit_mb}"
                                                v-model="form.storage_limit_mb" 
                                                min="1" 
                                                required
                                            >
                                            <span class="input-group-text bg-slate-100 border-slate-200 text-slate-500 rounded-end-3 fs-8">MB</span>
                                            <div class="invalid-feedback fs-9">{{ getError('storage_limit_mb') }}</div>
                                        </div>
                                    </div>

                                    <!-- Maks. Siswa -->
                                    <div class="col-12 col-md-4">
                                        <label for="modal_max_siswa_limit" class="form-label fw-bold fs-8 text-slate-700 mb-1">Batas Maks. Siswa <span class="text-rose-500">*</span></label>
                                        <input 
                                            id="modal_max_siswa_limit"
                                            name="max_siswa_limit"
                                            type="number" 
                                            class="form-control rounded-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-semibold text-slate-700" 
                                            :class="{'is-invalid': errors.max_siswa_limit}"
                                            v-model="form.max_siswa_limit" 
                                            min="0" 
                                            required
                                        >
                                        <div class="invalid-feedback fs-9">{{ getError('max_siswa_limit') }}</div>
                                    </div>

                                    <!-- Maks. Guru/Staf -->
                                    <div class="col-12 col-md-4">
                                        <label for="modal_max_staff_limit" class="form-label fw-bold fs-8 text-slate-700 mb-1">Batas Maks. Guru & Staf <span class="text-rose-500">*</span></label>
                                        <input 
                                            id="modal_max_staff_limit"
                                            name="max_staff_limit"
                                            type="number" 
                                            class="form-control rounded-3 py-2 border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-semibold text-slate-700" 
                                            :class="{'is-invalid': errors.max_staff_limit}"
                                            v-model="form.max_staff_limit" 
                                            min="0" 
                                            required
                                        >
                                        <div class="invalid-feedback fs-9">{{ getError('max_staff_limit') }}</div>
                                    </div>

                                    <!-- Aktivasi Fitur Switch -->
                                    <div class="col-12 mt-2">
                                        <span class="block fw-bold fs-8 text-slate-700 mb-1">Aktivasi Modul Fitur Sekolah</span>
                                        <div class="d-flex flex-wrap gap-4 p-3 rounded-3 border bg-white shadow-inner">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="switchBk" name="enable_bk" v-model="form.enable_bk" :true-value="1" :false-value="0">
                                                <label class="form-check-label fs-8 fw-semibold text-slate-700" for="switchBk">Modul Bimbingan Konseling (BK)</label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="switchTracer" name="enable_tracer" v-model="form.enable_tracer" :true-value="1" :false-value="0">
                                                <label class="form-check-label fs-8 fw-semibold text-slate-700" for="switchTracer">Modul Tracer Study (Alumni)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recommendations Block -->
                                    <div class="col-12 mt-3">
                                        <div class="alert alert-info border-0 rounded-3 p-3 mb-0 shadow-sm" style="font-size: 0.76rem;">
                                            <div class="fw-bold mb-2 text-indigo-700 d-flex align-items-center gap-1">
                                                <i class="bi bi-lightbulb-fill text-amber-500"></i>
                                                Rekomendasi Kapasitas & Paket Langganan Platform
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless mb-0 align-middle text-indigo-900" style="font-size: 0.72rem;">
                                                    <thead>
                                                        <tr class="border-bottom border-indigo-100 text-indigo-950 font-bold">
                                                            <th>Nama Paket</th>
                                                            <th class="text-center">Storage</th>
                                                            <th class="text-center">Maks. Siswa</th>
                                                            <th class="text-center">Maks. Staf</th>
                                                            <th class="text-center">Modul BK</th>
                                                            <th class="text-center">Tracer Study</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="hover:bg-indigo-50/50">
                                                            <td class="fw-bold text-indigo-950">Basic Edition</td>
                                                            <td class="text-center">50 MB</td>
                                                            <td class="text-center">100 siswa</td>
                                                            <td class="text-center">10 staf</td>
                                                            <td class="text-center text-rose-500 fw-bold">Nonaktif (❌)</td>
                                                            <td class="text-center text-rose-500 fw-bold">Nonaktif (❌)</td>
                                                        </tr>
                                                        <tr class="hover:bg-indigo-50/50">
                                                            <td class="fw-bold text-indigo-950">Pro Edition</td>
                                                            <td class="text-center">250 MB</td>
                                                            <td class="text-center">500 siswa</td>
                                                            <td class="text-center">50 staf</td>
                                                            <td class="text-center text-emerald-600 fw-bold">Aktif (✅)</td>
                                                            <td class="text-center text-rose-500 fw-bold">Nonaktif (❌)</td>
                                                        </tr>
                                                        <tr class="hover:bg-indigo-50/50">
                                                            <td class="fw-bold text-indigo-950">Premium SaaS</td>
                                                            <td class="text-center">1024 MB (1 GB)</td>
                                                            <td class="text-center">1000 siswa</td>
                                                            <td class="text-center">100 staf</td>
                                                            <td class="text-center text-emerald-600 fw-bold">Aktif (✅)</td>
                                                            <td class="text-center text-emerald-600 fw-bold">Aktif (✅)</td>
                                                        </tr>
                                                        <tr class="hover:bg-indigo-50/50">
                                                            <td class="fw-bold text-indigo-950">Enterprise SaaS</td>
                                                            <td class="text-center">5120 MB (5 GB)</td>
                                                            <td class="text-center">99999 (Unlimited)</td>
                                                            <td class="text-center">999 (Unlimited)</td>
                                                            <td class="text-center text-emerald-600 fw-bold">Aktif (✅)</td>
                                                            <td class="text-center text-emerald-600 fw-bold">Aktif (✅)</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="modal-footer border-top bg-light py-2.5 rounded-bottom-2xl">
                        <button type="button" class="btn btn-light rounded-3 fs-8 px-4 py-2 fw-semibold text-slate-500" data-bs-dismiss="modal">Batal</button>
                        <button 
                            type="submit" 
                            class="btn btn-primary rounded-3 fs-8 px-4 py-2 fw-semibold d-flex align-items-center gap-1.5 shadow-sm"
                            :disabled="isSaving"
                        >
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="isSaving"></span>
                            <i class="bi bi-check-circle" v-else></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

<!-- Vue 3 App Implementation -->
<script>
{
    window.VueAppRegistry.register('#tenantManagementApp', {
        data() {
            return {
                title: 'Kelola Sekolah (SaaS Tenant Management)',
                tenants: [],
                searchQuery: '',
                isLoading: false,
                isSaving: false,
                isEditMode: false,
                errors: {},
                modalObj: null,
                form: {
                    id: '',
                    nama_sekolah: '',
                    npsn: '',
                    subdomain: '',
                    domain: '',
                    paket_aktif: 'Premium SaaS',
                    status_sinkronisasi: 'Tersinkronisasi',
                    status: 'active',
                    storage_limit_mb: 100,
                    max_siswa_limit: 500,
                    max_staff_limit: 50,
                    enable_bk: 1,
                    enable_tracer: 1
                }
            };
        },
        computed: {
            filteredTenants() {
                if (!this.searchQuery) return this.tenants;
                const q = this.searchQuery.toLowerCase();
                return this.tenants.filter(t => 
                    t.nama_sekolah.toLowerCase().includes(q) || 
                    t.npsn.includes(q) || 
                    t.subdomain.toLowerCase().includes(q) || 
                    (t.domain && t.domain.toLowerCase().includes(q))
                );
            },
            totalTenants() { 
                return this.tenants.length; 
            },
            activeTenants() { 
                return this.tenants.filter(t => t.status === 'active').length; 
            },
            suspendedTenants() { 
                return this.tenants.filter(t => t.status === 'suspended' || t.status === 'inactive').length; 
            },
            syncedTenants() { 
                return this.tenants.filter(t => t.status_sinkronisasi === 'Tersinkronisasi').length; 
            }
        },
        methods: {
            // Fetch all tenants from API
            fetchTenants() {
                this.isLoading = true;
                axios.get('/SINTA-SaaS/api/v1/super-admin/tenants')
                .then(response => {
                    this.isLoading = false;
                    if (response.data.success) {
                        this.tenants = response.data.data || [];
                    }
                })
                .catch(error => {
                    this.isLoading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Sistem',
                        text: 'Terjadi kegagalan mengambil daftar data sekolah dari server.'
                    });
                });
            },

            // Reset form fields
            resetForm() {
                this.form = {
                    id: '',
                    nama_sekolah: '',
                    npsn: '',
                    subdomain: '',
                    domain: '',
                    paket_aktif: 'Premium SaaS',
                    status_sinkronisasi: 'Tersinkronisasi',
                    status: 'active',
                    storage_limit_mb: 100,
                    max_siswa_limit: 500,
                    max_staff_limit: 50,
                    enable_bk: 1,
                    enable_tracer: 1
                };
                this.errors = {};
            },

            // Apply default capacity settings based on selected package
            applyPackageDefaults() {
                const paket = this.form.paket_aktif;
                if (paket === 'Basic') {
                    this.form.storage_limit_mb = 50;
                    this.form.max_siswa_limit = 100;
                    this.form.max_staff_limit = 10;
                    this.form.enable_bk = 0;
                    this.form.enable_tracer = 0;
                } else if (paket === 'Pro') {
                    this.form.storage_limit_mb = 250;
                    this.form.max_siswa_limit = 500;
                    this.form.max_staff_limit = 50;
                    this.form.enable_bk = 1;
                    this.form.enable_tracer = 0;
                } else if (paket === 'Premium SaaS') {
                    this.form.storage_limit_mb = 1024;
                    this.form.max_siswa_limit = 1000;
                    this.form.max_staff_limit = 100;
                    this.form.enable_bk = 1;
                    this.form.enable_tracer = 1;
                } else if (paket === 'Enterprise SaaS') {
                    this.form.storage_limit_mb = 5120;
                    this.form.max_siswa_limit = 99999;
                    this.form.max_staff_limit = 999;
                    this.form.enable_bk = 1;
                    this.form.enable_tracer = 1;
                }
            },

            // Open Modal for Addition
            openAddModal() {
                this.isEditMode = false;
                this.resetForm();
                this.modalObj.show();
                this.$nextTick(() => {
                    const firstTabEl = document.querySelector('#profile-tab');
                    if (firstTabEl) {
                        const tab = bootstrap.Tab.getInstance(firstTabEl) || new bootstrap.Tab(firstTabEl);
                        tab.show();
                    }
                });
            },

            // Open Modal for Edit
            openEditModal(tenant) {
                this.isEditMode = true;
                this.errors = {};
                this.form = {
                    id: tenant.id,
                    nama_sekolah: tenant.nama_sekolah,
                    npsn: tenant.npsn,
                    subdomain: tenant.subdomain,
                    domain: tenant.domain || '',
                    paket_aktif: tenant.paket_aktif,
                    status_sinkronisasi: tenant.status_sinkronisasi,
                    status: tenant.status,
                    storage_limit_mb: tenant.storage_limit_mb !== undefined ? parseInt(tenant.storage_limit_mb) : 100,
                    max_siswa_limit: tenant.max_siswa_limit !== undefined ? parseInt(tenant.max_siswa_limit) : 500,
                    max_staff_limit: tenant.max_staff_limit !== undefined ? parseInt(tenant.max_staff_limit) : 50,
                    enable_bk: tenant.enable_bk !== undefined ? parseInt(tenant.enable_bk) : 1,
                    enable_tracer: tenant.enable_tracer !== undefined ? parseInt(tenant.enable_tracer) : 1
                };
                this.modalObj.show();
                this.$nextTick(() => {
                    const firstTabEl = document.querySelector('#profile-tab');
                    if (firstTabEl) {
                        const tab = bootstrap.Tab.getInstance(firstTabEl) || new bootstrap.Tab(firstTabEl);
                        tab.show();
                    }
                });
            },

            // Validation Helper
            getError(field) {
                return this.errors[field] ? this.errors[field][0] : '';
            },

            // Submit Form Data (Add or Edit)
            submitForm() {
                this.isSaving = true;
                this.errors = {};

                axios.post('/SINTA-SaaS/api/v1/super-admin/tenants/simpan', this.form)
                .then(response => {
                    this.isSaving = false;
                    if (response.data.success) {
                        this.modalObj.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Disimpan',
                            text: response.data.message || 'Data sekolah berhasil disimpan.',
                            confirmButtonColor: '#2563eb'
                        });
                        this.fetchTenants();
                    }
                })
                .catch(error => {
                    this.isSaving = false;
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data.errors || {};
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validasi Gagal',
                            text: 'Harap periksa kembali isian form Anda.',
                            confirmButtonColor: '#2563eb'
                        });
                    } else {
                        const errorMsg = error.response && error.response.data.error 
                            ? error.response.data.error 
                            : 'Terjadi kesalahan sistem saat menyimpan data.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Penyimpanan Gagal',
                            text: errorMsg
                        });
                    }
                });
            },

            // Quick Change Status Access (SweetAlert)
            changeStatus(tenant) {
                Swal.fire({
                    title: 'Ubah Status Akses Sekolah',
                    text: `Pilih status akses keamanan baru untuk ${tenant.nama_sekolah}:`,
                    input: 'select',
                    inputOptions: {
                        'active': 'Active (Aktif)',
                        'inactive': 'Inactive (Tidak Aktif)',
                        'suspended': 'Suspended (Ditangguhkan)'
                    },
                    inputValue: tenant.status,
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="bi bi-check-circle-fill me-1"></i> Simpan Status',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        this.saveStatus(tenant.id, result.value, tenant.nama_sekolah);
                    }
                });
            },

            // Direct toggle status active/inactive
            toggleActiveStatus(tenant, targetStatus) {
                const actionText = targetStatus === 'active' ? 'mengaktifkan kembali' : 'menonaktifkan';
                const confirmButtonColor = targetStatus === 'active' ? '#10b981' : '#f59e0b';
                
                Swal.fire({
                    title: `Apakah Anda yakin?`,
                    html: `Anda akan <strong>${actionText}</strong> akses sekolah <strong>${tenant.nama_sekolah}</strong>.<br><br>` + 
                          (targetStatus === 'inactive' ? `<span class="text-danger font-semibold"><i class="bi bi-exclamation-triangle"></i> PENTING: Seluruh user (Admin, Guru, Siswa) dari sekolah ini tidak akan bisa login & otomatis dikeluarkan!</span>` : ''),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: confirmButtonColor,
                    cancelButtonColor: '#64748b',
                    confirmButtonText: `Ya, ${targetStatus === 'active' ? 'Aktifkan' : 'Nonaktifkan'}!`,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.saveStatus(tenant.id, targetStatus, tenant.nama_sekolah);
                    }
                });
            },

            // Save status update to database
            saveStatus(id, newStatus, namaSekolah) {
                axios.post('/SINTA-SaaS/api/v1/super-admin/tenants/toggle-status', {
                    id: id,
                    status: newStatus
                })
                .then(response => {
                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Diperbarui',
                            text: `Status akses ${namaSekolah} berhasil diubah menjadi ${newStatus}.`,
                            confirmButtonColor: '#2563eb'
                        });
                        this.fetchTenants();
                    }
                })
                .catch(error => {
                    const errorMsg = error.response && error.response.data.error 
                        ? error.response.data.error 
                        : 'Terjadi kesalahan saat memproses pembaruan status.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memperbarui',
                        text: errorMsg
                    });
                });
            },

            // Soft Delete Tenant
            deleteTenant(tenant) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    html: `Anda akan menghapus sekolah <strong>${tenant.nama_sekolah}</strong> secara Soft Delete.<br><br><span class="text-danger font-semibold"><i class="bi bi-exclamation-triangle"></i> PENTING: Seluruh user (Admin, Guru, Siswa) yang berelasi dengan sekolah ini akan diblokir & otomatis keluar sesi!</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus Sekolah!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('/SINTA-SaaS/api/v1/super-admin/tenants/hapus', {
                            id: tenant.id
                        })
                        .then(response => {
                            if (response.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus',
                                    text: response.data.message || 'Sekolah berhasil dihapus (Soft Delete).',
                                    confirmButtonColor: '#2563eb'
                                });
                                this.fetchTenants();
                            }
                        })
                        .catch(error => {
                            const errorMsg = error.response && error.response.data.error 
                                ? error.response.data.error 
                                : 'Terjadi kesalahan saat menghapus data.';
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: errorMsg
                            });
                        });
                    }
                });
            },

            // UI helper: Get initials for icon circle
            getInitials(name) {
                if (!name) return 'S';
                const words = name.split(' ');
                if (words.length >= 2) {
                    return (words[0][0] + words[1][0]).toUpperCase();
                }
                return name.substring(0, 2).toUpperCase();
            },

            // UI helper: Badge classes for subscription package
            getPaketBadgeClass(paket) {
                switch (paket) {
                    case 'Enterprise SaaS':
                        return 'bg-indigo-50 text-indigo-700 border border-indigo-200';
                    case 'Premium SaaS':
                        return 'bg-purple-50 text-purple-700 border border-purple-200';
                    case 'Pro':
                        return 'bg-blue-50 text-blue-700 border border-blue-200';
                    default: // Basic
                        return 'bg-slate-100 text-slate-600 border border-slate-200';
                }
            },

            // UI helper: Badge classes for sync status
            getSinkronisasiBadgeClass(sync) {
                switch (sync) {
                    case 'Tersinkronisasi':
                        return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                    case 'Menunggu':
                        return 'bg-amber-50 text-amber-700 border border-amber-200';
                    default: // Gagal
                        return 'bg-rose-50 text-rose-700 border border-rose-200';
                }
            },

            // UI helper: Dot classes for sync status
            getSinkronisasiDotClass(sync) {
                switch (sync) {
                    case 'Tersinkronisasi':
                        return 'bg-emerald-500';
                    case 'Menunggu':
                        return 'bg-amber-500';
                    default: // Gagal
                        return 'bg-rose-500';
                }
            },

            // UI helper: Badge classes for status access
            getStatusBadgeClass(status) {
                switch (status) {
                    case 'active':
                        return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                    case 'suspended':
                        return 'bg-amber-50 text-amber-700 border border-amber-200';
                    default: // inactive
                        return 'bg-slate-100 text-slate-600 border border-slate-200';
                }
            },

            // UI helper: Dot classes for status access
            getStatusDotClass(status) {
                switch (status) {
                    case 'active':
                        return 'bg-emerald-500';
                    case 'suspended':
                        return 'bg-amber-500';
                    default: // inactive
                        return 'bg-slate-400';
                }
            }
        },
        mounted() {
            // Instantiate Bootstrap Modal object
            this.modalObj = new bootstrap.Modal(document.getElementById('tenantModal'));
            
            // Load initial tenant list
            this.fetchTenants();
        }
    });
}
</script>
