<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login Siswa - Dapodik & SPMB SaaS</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="/SINTA-SaaS/assets/css/bootstrap-icons.css" rel="stylesheet">
    <!-- Tailwind CSS -->
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
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        };
    </script>
    <script src="/SINTA-SaaS/assets/js/tailwindcss.js"></script>
    <!-- Vue 3 -->
    <script src="/SINTA-SaaS/assets/js/vue.global.prod.js"></script>
    <style>
        [v-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gradient-to-tr from-slate-50 via-slate-100 to-blue-50/50 min-h-screen flex items-center justify-center p-4">

    <div id="siswaLoginApp" v-cloak class="w-full max-w-md">
        
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/20 mb-4 transform hover:rotate-6 transition-transform">
                <i class="bi bi-mortarboard-fill text-3xl"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Portal Siswa</h2>
            <p class="text-slate-500 text-sm mt-1">Dapodik & Penerimaan Siswa Baru Multi-Tenant</p>
        </div>

        <!-- Card Container -->
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8">
            <h4 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i class="bi bi-shield-lock text-blue-600"></i> Masuk Ke Akun Anda
            </h4>

            <form @submit.prevent="handleLogin" class="space-y-5">
                
                <!-- Pilihan Sekolah / Tenant (Dengan Kolom Search & Lazy Load) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Pilih Sekolah (Tenant)</label>
                    <div class="relative" id="tenantSearchDropdown">
                        <!-- Dropdown Trigger Button -->
                        <button 
                            type="button"
                            @click="toggleDropdown"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-10 text-sm font-semibold tracking-wide text-left text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all relative"
                        >
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <i class="bi bi-buildings"></i>
                            </span>
                            
                            <span class="block truncate">
                                {{ selectedTenantName || 'Cari Semua Sekolah (Global)' }}
                            </span>
                            
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                <i class="bi" :class="isOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                            </span>
                        </button>

                        <!-- Dropdown Panel -->
                        <div 
                            v-if="isOpen" 
                            class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden focus:outline-none"
                            style="max-height: 320px; display: flex; flex-direction: column;"
                        >
                            <!-- Search Input inside dropdown -->
                            <div class="p-3 border-b border-slate-100 bg-slate-50/50 sticky top-0 z-10">
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-400">
                                        <i class="bi bi-search text-xs"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        ref="searchInput"
                                        v-model="searchQuery"
                                        @input="onSearchInput"
                                        class="w-full bg-white border border-slate-200 rounded-lg py-2 pl-8 pr-4 text-xs font-medium text-slate-800 focus:border-blue-500 focus:outline-none transition-all"
                                        placeholder="Ketik nama sekolah atau NPSN..."
                                    >
                                </div>
                            </div>

                            <!-- List of options -->
                            <div class="overflow-y-auto flex-grow" style="max-height: 200px;">
                                <!-- Global Option -->
                                <button 
                                    type="button"
                                    @click="selectTenant('', 'Cari Semua Sekolah (Global)')"
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors border-b border-slate-50"
                                >
                                    Cari Semua Sekolah (Global)
                                </button>
                                
                                <!-- Loading State -->
                                <div v-if="loadingTenants" class="py-4 text-center text-slate-400 text-xs flex items-center justify-center gap-2">
                                    <span class="animate-spin inline-block w-4 h-4 border-2 border-slate-400 border-t-transparent rounded-full"></span>
                                    <span>Memuat data sekolah...</span>
                                </div>

                                <!-- Empty State -->
                                <div v-else-if="tenants.length === 0" class="py-4 text-center text-slate-400 text-xs">
                                    Sekolah tidak ditemukan.
                                </div>

                                <!-- Options List -->
                                <button 
                                    v-else
                                    type="button"
                                    v-for="tenant in tenants" 
                                    :key="tenant.id"
                                    @click="selectTenant(tenant.subdomain, tenant.nama_sekolah)"
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex flex-col gap-0.5 border-b border-slate-50"
                                >
                                    <span class="text-slate-900 font-bold">{{ tenant.nama_sekolah }}</span>
                                    <span class="text-[10px] text-slate-400">NPSN: {{ tenant.npsn }} | Subdomain: {{ tenant.subdomain }}</span>
                                </button>

                                <!-- Lazy Load: Load More Button -->
                                <div v-if="hasMore" class="p-2 border-t border-slate-50 bg-slate-50/20">
                                    <button 
                                        type="button" 
                                        @click="loadMore"
                                        class="w-full py-1.5 text-center text-[10px] font-bold text-blue-600 hover:text-blue-700 hover:bg-blue-50/50 rounded-lg transition-all"
                                    >
                                        Tampilkan Lebih Banyak
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-1">
                        *Pada server produksi, sekolah dideteksi otomatis via subdomain URL.
                    </div>
                </div>

                <!-- Input NISN -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Nomor NISN Siswa</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="bi bi-person-badge"></i>
                        </span>
                        <input 
                            type="text" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 text-sm font-semibold tracking-wide text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all" 
                            placeholder="Contoh: 0054231901" 
                            maxlength="10" 
                            v-model="form.nisn"
                            required
                        >
                    </div>
                </div>

                <!-- Input Password -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Password / Sandi</label>
                        <span class="text-[10px] font-medium text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full"><i class="bi bi-info-circle me-0.5"></i>Default: Tanggal Lahir</span>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="bi bi-key"></i>
                        </span>
                        <input 
                            :type="showPassword ? 'text' : 'password'" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-10 text-sm text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all font-semibold" 
                            placeholder="Format Default: YYYY-MM-DD" 
                            v-model="form.password"
                            autocomplete="current-password"
                            required
                        >
                        <button 
                            type="button" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                            @click="showPassword = !showPassword"
                        >
                            <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Alert error -->
                <div v-if="errorMsg" class="bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl p-3 flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-sm shrink-0"></i>
                    <span>{{ errorMsg }}</span>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-xl hover:shadow-blue-500/30 transition-all flex items-center justify-center gap-2"
                    :disabled="loading"
                >
                    <span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full" v-if="loading"></span>
                    <i class="bi bi-box-arrow-in-right" v-else></i>
                    Masuk Portal Siswa
                </button>

            </form>
            
            <div class="mt-6 text-center border-t border-slate-100 pt-5">
                <a href="/SINTA-SaaS/admin" class="text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors flex items-center justify-center gap-1.5">
                    <i class="bi bi-arrow-left"></i> Login sebagai Operator / Guru / Super Admin
                </a>
            </div>
        </div>
    </div>

    <!-- Axios & SweetAlert2 -->
    <script src="/SINTA-SaaS/assets/js/axios.min.js"></script>
    <script src="/SINTA-SaaS/assets/js/sweetalert2.all.min.js"></script>

    <script>
{
        const INITIAL_TENANTS = <?= json_encode($tenants ?? []) ?>;

        document.addEventListener('DOMContentLoaded', function() {
            Vue.createApp({
                data() {
                    return {
                        form: {
                            tenant_id: '',
                            nisn: '',
                            password: ''
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
                        // Form action state
                        showPassword: false,
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
                        axios.get('/SINTA-SaaS/api/v1/tenant/search', {
                            params: {
                                q: this.searchQuery,
                                page: this.page,
                                limit: 10
                            }
                        })
                        .then(response => {
                            this.loadingTenants = false;
                            if (response.data.success) {
                                if (append) {
                                    this.tenants = [...this.tenants, ...response.data.data];
                                } else {
                                    this.tenants = response.data.data;
                                }
                                this.hasMore = response.data.pagination.has_more;
                            }
                        })
                        .catch(error => {
                            this.loadingTenants = false;
                            console.error('Failed to fetch tenants:', error);
                        });
                    },
                    onSearchInput() {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = setTimeout(() => {
                            this.page = 1;
                            this.fetchTenants();
                        }, 300); // 300ms debounce
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
                        const dropdownContainer = document.getElementById('tenantSearchDropdown');
                        if (dropdownContainer && this.isOpen && !dropdownContainer.contains(e.target)) {
                            this.isOpen = false;
                        }
                    },
                    handleLogin() {
                        this.loading = true;
                        this.errorMsg = '';

                        const headers = {
                            'Content-Type': 'application/json'
                        };
                        if (this.form.tenant_id) {
                            headers['X-Tenant-ID'] = this.form.tenant_id;
                        }

                        axios.post('/SINTA-SaaS/api/v1/siswa/login', this.form, { headers: headers })
                        .then(response => {
                            this.loading = false;
                            if (response.data.success) {
                                if (response.data.is_first_login) {
                                    // First login wajib ubah password
                                    window.location.href = '/SINTA-SaaS/siswa/ubah-password';
                                } else {
                                    // Siswa yang sudah update password dialihkan langsung ke dashboard
                                    window.location.href = '/SINTA-SaaS/dashboard';
                                }
                            }
                        })
                        .catch(error => {
                            this.loading = false;
                            this.errorMsg = error.response && error.response.data.error 
                                ? error.response.data.error 
                                : 'Terjadi kegagalan koneksi ke server auth.';
                        });
                    }
                },
                mounted() {
                    document.addEventListener('click', this.closeDropdownOnClickOutside);
                },
                unmounted() {
                    document.removeEventListener('click', this.closeDropdownOnClickOutside);
                }
            }).mount('#siswaLoginApp');
        });
}
</script>
</body>
</html>
