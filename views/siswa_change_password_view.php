<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wajib Ubah Password - Dapodik & SPMB SaaS</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="/dapodik-spmb/assets/css/bootstrap-icons.css" rel="stylesheet">
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
    <script src="/dapodik-spmb/assets/js/tailwindcss.js"></script>
    <!-- Vue 3 -->
    <script src="/dapodik-spmb/assets/js/vue.global.prod.js"></script>
    <style>
        [v-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gradient-to-tr from-slate-50 via-slate-100 to-indigo-50/50 min-h-screen flex items-center justify-center p-4">

    <div id="siswaChangePasswordApp" v-cloak class="w-full max-w-md">
        
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white shadow-lg shadow-orange-500/20 mb-4 transform hover:rotate-6 transition-transform">
                <i class="bi bi-shield-exclamation text-3xl"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Keamanan Akun Wajib</h2>
            <p class="text-slate-500 text-sm mt-1">Ubah password awal Anda demi melindungi keamanan data.</p>
        </div>

        <!-- Card Container -->
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8">
            
            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 flex gap-3 align-start">
                <i class="bi bi-info-circle-fill text-xl shrink-0 text-amber-500"></i>
                <div class="text-xs">
                    <h6 class="font-bold mb-0.5">Sandi Default Terdeteksi</h6>
                    <p class="leading-relaxed">Ini adalah login pertama Anda menggunakan sandi tanggal lahir. Anda wajib mengubah sandi bawaan menjadi sandi baru yang aman sebelum dapat masuk ke Dashboard Siswa.</p>
                </div>
            </div>

            <form @submit.prevent="handleChangePassword" class="space-y-5">
                
                <!-- Password Baru -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Password Baru <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input 
                            :type="showPass1 ? 'text' : 'password'" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-10 text-sm text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all font-semibold" 
                            placeholder="Minimal 8 karakter" 
                            v-model="form.password_baru"
                            autocomplete="new-password"
                            required
                        >
                        <button 
                            type="button" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                            @click="showPass1 = !showPass1"
                        >
                            <i class="bi" :class="showPass1 ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Konfirmasi Password Baru -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Ulangi Password Baru <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="bi bi-lock-check"></i>
                        </span>
                        <input 
                            :type="showPass2 ? 'text' : 'password'" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-10 text-sm text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all font-semibold" 
                            placeholder="Ulangi sandi baru secara tepat" 
                            v-model="form.konfirmasi_password"
                            autocomplete="new-password"
                            required
                        >
                        <button 
                            type="button" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                            @click="showPass2 = !showPass2"
                        >
                            <i class="bi" :class="showPass2 ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Keamanan sandi checklist (Client-side helper) -->
                <div class="text-[11px] space-y-1 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div class="flex items-center gap-1.5" :class="passwordLengthValid ? 'text-emerald-600' : 'text-slate-400'">
                        <i class="bi" :class="passwordLengthValid ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                        <span>Minimal 8 karakter</span>
                    </div>
                    <div class="flex items-center gap-1.5" :class="passwordsMatch ? 'text-emerald-600' : 'text-slate-400'">
                        <i class="bi" :class="passwordsMatch ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                        <span>Kedua password cocok</span>
                    </div>
                </div>

                <!-- Alert error backend -->
                <div v-if="errorMsg" class="bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl p-3 flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-sm shrink-0"></i>
                    <span>{{ errorMsg }}</span>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-xl hover:shadow-blue-500/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="loading || !isFormValid"
                >
                    <span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full" v-if="loading"></span>
                    <i class="bi bi-shield-check" v-else></i>
                    Simpan Sandi & Masuk Dashboard
                </button>

            </form>

            <div class="mt-6 text-center border-t border-slate-100 pt-5">
                <a href="/dapodik-spmb/siswa/logout" class="text-xs font-semibold text-slate-500 hover:text-rose-600 transition-colors flex items-center justify-center gap-1.5">
                    <i class="bi bi-box-arrow-left"></i> Batal & Keluar Sesi
                </a>
            </div>

        </div>
    </div>

    <!-- Axios & SweetAlert2 -->
    <script src="/dapodik-spmb/assets/js/axios.min.js"></script>
    <script src="/dapodik-spmb/assets/js/sweetalert2.all.min.js"></script>

    <script>
{
        document.addEventListener('DOMContentLoaded', function() {
            Vue.createApp({
                data() {
                    return {
                        form: {
                            password_baru: '',
                            konfirmasi_password: ''
                        },
                        showPass1: false,
                        showPass2: false,
                        loading: false,
                        errorMsg: ''
                    };
                },
                computed: {
                    passwordLengthValid() {
                        return this.form.password_baru.length >= 8;
                    },
                    passwordsMatch() {
                        return this.form.password_baru && this.form.password_baru === this.form.konfirmasi_password;
                    },
                    isFormValid() {
                        return this.passwordLengthValid && this.passwordsMatch;
                    }
                },
                methods: {
                    handleChangePassword() {
                        if (!this.isFormValid) return;
                        
                        this.loading = true;
                        this.errorMsg = '';

                        axios.post('/dapodik-spmb/api/v1/siswa/ubah-password', this.form)
                        .then(response => {
                            this.loading = false;
                            if (response.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sandi Berhasil Diperbarui',
                                    text: 'Sandi baru Anda telah disimpan. Membuka Dashboard...',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    willClose: () => {
                                        window.location.href = '/dapodik-spmb/dashboard';
                                    }
                                });
                            }
                        })
                        .catch(error => {
                            this.loading = false;
                            this.errorMsg = error.response && error.response.data.error 
                                ? error.response.data.error 
                                : 'Terjadi kegagalan saat memperbarui password.';
                        });
                    }
                }
            }).mount('#siswaChangePasswordApp');
        });
}
</script>
</body>
</html>
