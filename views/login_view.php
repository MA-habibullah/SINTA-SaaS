<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dapodik & SPMB SaaS</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="/SINTA-SaaS/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="/SINTA-SaaS/assets/css/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
        }
        .login-card {
            background-color: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
        }
        .form-control, .form-select {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
            color: #f8fafc;
        }
        .form-control::placeholder {
            color: #94a3b8;
        }
        .btn-primary {
            background-color: #3b82f6;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .text-muted-custom {
            color: #94a3b8;
        }
        .alert-custom {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 0.5rem;
        }
        .hover-bg-custom:hover {
            background-color: rgba(59, 130, 246, 0.2) !important;
        }
        [v-cloak] {
            display: none !important;
        }
    </style>
</head>
<body>

    <div id="adminLoginApp" v-cloak class="login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3 p-3 mb-3">
                <i class="bi bi-shield-lock-fill fs-2 text-primary"></i>
            </div>
            <h3 class="fw-bold">Dapodik & SPMB</h3>
            <p class="text-muted-custom">Platform Sistem Akademik & PPDB Multi-tenant</p>
        </div>

        <!-- Alert Error -->
        <div v-if="errorMsg" class="alert alert-custom d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <span>{{ errorMsg }}</span>
        </div>

        <form @submit.prevent="handleLogin">
            <!-- Pilihan Sekolah / Tenant (Dengan Kolom Search & Lazy Load) -->
            <div class="mb-3">
                <label class="form-label fw-medium text-muted-custom">Pilih Sekolah (Tenant)</label>
                <div class="position-relative" id="tenantSearchDropdown">
                    <!-- Dropdown Trigger Button -->
                    <button 
                        type="button"
                        @click="toggleDropdown"
                        class="w-100 text-start form-select d-flex align-items-center justify-content-between relative"
                        style="padding: 0.75rem 1rem;"
                    >
                        <span class="d-flex align-items-center">
                            <i class="bi bi-buildings text-muted-custom me-2"></i>
                            <span class="text-truncate" style="max-width: 250px;">
                                {{ selectedTenantName || 'Super Admin Platform (Global)' }}
                            </span>
                        </span>
                        <i class="bi" :class="isOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>

                    <!-- Dropdown Panel -->
                    <div 
                        v-if="isOpen" 
                        class="position-absolute z-3 w-100 mt-2 rounded-3 border overflow-hidden shadow-lg"
                        style="background-color: #1e293b; border-color: rgba(255, 255, 255, 0.1); max-height: 320px; display: flex; flex-direction: column;"
                    >
                        <!-- Search Input inside dropdown -->
                        <div class="p-2 border-bottom" style="background-color: rgba(15, 23, 42, 0.6); border-color: rgba(255, 255, 255, 0.1);">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-transparent text-muted-custom border-end-0" style="border-color: rgba(255, 255, 255, 0.15);">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input 
                                    type="text" 
                                    ref="searchInput"
                                    v-model="searchQuery"
                                    @input="onSearchInput"
                                    class="form-control border-start-0 text-white" 
                                    style="background-color: transparent; border-color: rgba(255, 255, 255, 0.15);"
                                    placeholder="Ketik nama sekolah atau NPSN..."
                                >
                            </div>
                        </div>

                        <!-- List of options -->
                        <div class="overflow-y-auto flex-grow-1" style="max-height: 200px;">
                            <!-- Global Option -->
                            <button 
                                type="button"
                                @click="selectTenant('', 'Super Admin Platform (Global)')"
                                class="w-100 text-start px-3 py-2.5 text-sm text-white border-0 bg-transparent hover-bg-custom border-bottom"
                                style="transition: background-color 0.2s; border-color: rgba(255, 255, 255, 0.05) !important;"
                            >
                                Super Admin Platform (Global)
                            </button>
                            
                            <!-- Loading State -->
                            <div v-if="loadingTenants" class="py-3 text-center text-muted-custom text-xs d-flex align-items-center justify-content-center gap-2">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <span>Memuat sekolah...</span>
                            </div>

                            <!-- Empty State -->
                            <div v-else-if="tenants.length === 0" class="py-3 text-center text-muted-custom text-xs">
                                Sekolah tidak ditemukan.
                            </div>

                            <!-- Options List -->
                            <button 
                                v-else
                                type="button"
                                v-for="tenant in tenants" 
                                :key="tenant.id"
                                @click="selectTenant(tenant.subdomain, tenant.nama_sekolah)"
                                class="w-100 text-start px-3 py-2 text-white border-0 bg-transparent hover-bg-custom d-flex flex-column gap-1"
                                style="transition: background-color 0.2s; border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;"
                            >
                                <span class="fw-bold fs-7">{{ tenant.nama_sekolah }}</span>
                                <span class="text-muted-custom" style="font-size: 0.75rem;">NPSN: {{ tenant.npsn }} | Subdomain: {{ tenant.subdomain }}</span>
                            </button>

                            <!-- Lazy Load: Load More Button -->
                            <div v-if="hasMore" class="p-2 border-top bg-transparent" style="border-color: rgba(255, 255, 255, 0.1) !important;">
                                <button 
                                    type="button" 
                                    @click="loadMore"
                                    class="w-100 btn btn-sm btn-outline-primary py-1"
                                >
                                    Tampilkan Lebih Banyak
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-text text-muted-custom" style="font-size: 0.75rem;">
                    *Pada server produksi, sekolah dideteksi otomatis via subdomain URL.
                </div>
            </div>

            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label fw-medium text-muted-custom">Alamat Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted-custom" style="border-color: rgba(255, 255, 255, 0.15);">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control border-start-0" id="email" v-model="form.email" placeholder="nama@sekolah.sch.id" autocomplete="username" required>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="form-label fw-medium text-muted-custom">Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted-custom" style="border-color: rgba(255, 255, 255, 0.15);">
                        <i class="bi bi-key"></i>
                    </span>
                    <input type="password" class="form-control border-start-0" id="password" v-model="form.password" placeholder="••••••••" autocomplete="current-password" required>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center mb-3" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                <span>{{ loading ? 'Memverifikasi...' : 'Masuk Ke Portal' }}</span>
                <i v-if="!loading" class="bi bi-arrow-right-short fs-4 ms-1"></i>
            </button>

            <!-- Link to Student Portal -->
            <div class="text-center mt-3 pt-3 border-top" style="border-color: rgba(255, 255, 255, 0.1) !important;">
                <a href="/SINTA-SaaS/login" class="text-decoration-none text-muted-custom hover:text-white" style="font-size: 0.85rem; transition: color 0.2s;">
                    <i class="bi bi-mortarboard me-1.5"></i>Login Khusus Siswa
                </a>
            </div>
        </form>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="/SINTA-SaaS/assets/js/bootstrap.bundle.min.js"></script>
    <!-- Vue 3 Global Build -->
    <script src="/SINTA-SaaS/assets/js/vue.global.prod.js"></script>

    <script>
{
        const INITIAL_TENANTS = <?= json_encode($tenants ?? []) ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            Vue.createApp({
                data() {
                    return {
                        form: {
                            email: '',
                            password: '',
                            tenant_id: ''
                        },
                        // Dropdown state
                        isOpen: false,
                        searchQuery: '',
                        selectedTenantName: '',
                        tenants: INITIAL_TENANTS,
                        loadingTenants: false,
                        page: 1,
                        hasMore: false,
                        debounceTimer: null,
                        // Login action state
                        loading: false,
                        errorMsg: ''
                    };
                },
                methods: {
                    toggleDropdown() {
                        this.isOpen = !this.isOpen;
                        if (this.isOpen) {
                            this.searchQuery = '';
                            this.page = 1;
                            this.tenants = INITIAL_TENANTS;
                            this.hasMore = false;
                            this.$nextTick(() => {
                                if (this.$refs.searchInput) {
                                    this.$refs.searchInput.focus();
                                }
                            });
                        }
                    },
                    fetchTenants(append = false) {
                        this.loadingTenants = true;
                        fetch(`/SINTA-SaaS/api/v1/tenant/search?q=${encodeURIComponent(this.searchQuery)}&page=${this.page}&limit=10`)
                        .then(res => res.json())
                        .then(resData => {
                            this.loadingTenants = false;
                            if (resData.success) {
                                if (append) {
                                    this.tenants = [...this.tenants, ...resData.data];
                                } else {
                                    this.tenants = resData.data;
                                }
                                this.hasMore = resData.pagination.has_more;
                            }
                        })
                        .catch(err => {
                            this.loadingTenants = false;
                            console.error('Gagal mengambil data sekolah:', err);
                        });
                    },
                    onSearchInput() {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = setTimeout(() => {
                            this.page = 1;
                            this.fetchTenants();
                        }, 300);
                    },
                    loadMore() {
                        if (this.hasMore) {
                            this.page++;
                            this.fetchTenants(true);
                        }
                    },
                    selectTenant(id, name) {
                        this.form.tenant_id = id;
                        this.selectedTenantName = id ? name : '';
                        this.isOpen = false;
                    },
                    closeDropdownOnClickOutside(e) {
                        const container = document.getElementById('tenantSearchDropdown');
                        if (container && this.isOpen && !container.contains(e.target)) {
                            this.isOpen = false;
                        }
                    },
                    async handleLogin() {
                        this.loading = true;
                        this.errorMsg = '';

                        try {
                            const headers = {
                                'Content-Type': 'application/json'
                            };
                            if (this.form.tenant_id) {
                                headers['X-Tenant-ID'] = this.form.tenant_id;
                            }

                            const response = await fetch('/SINTA-SaaS/api/v1/auth/login', {
                                method: 'POST',
                                headers: headers,
                                body: JSON.stringify({
                                    email: this.form.email,
                                    password: this.form.password
                                })
                            });

                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.error || 'Terjadi kesalahan sistem.');
                            }

                            // Login sukses, arahkan ke dashboard
                            window.location.href = '/SINTA-SaaS/dashboard';

                        } catch (err) {
                            this.errorMsg = err.message;
                            this.loading = false;
                        }
                    }
                },
                mounted() {
                    document.addEventListener('click', this.closeDropdownOnClickOutside);
                },
                unmounted() {
                    document.removeEventListener('click', this.closeDropdownOnClickOutside);
                }
            }).mount('#adminLoginApp');
        });
}
</script>
</body>
</html>
