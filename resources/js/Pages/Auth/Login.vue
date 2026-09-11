<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Decorative background blobs -->
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 relative z-10">
      <!-- Brand Logo & Header -->
      <div class="text-center mb-6">
        <Link href="/" class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 text-white font-black text-2xl shadow-lg shadow-blue-500/30 mb-3 hover:scale-105 transition-transform">
          S
        </Link>
        <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">SINTA SAAS</h2>
        <p class="text-xs text-slate-500 mt-1">Platform Tata Kelola Akademik & Multi-Tenant Sekolah</p>
      </div>

      <!-- Registration Flash Alert -->
      <div v-if="$page.props.flash?.registration_success" class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
        <div class="flex items-start gap-2.5">
          <i class="bi bi-check-circle-fill text-emerald-600 text-base shrink-0 mt-0.5"></i>
          <div>
            <h4 class="font-bold text-emerald-900">Pendaftaran Berhasil Dikirim!</h4>
            <p class="text-emerald-700 mt-0.5">{{ $page.props.flash.registration_success.message }}</p>
            <p class="text-emerald-600 text-[11px] mt-1.5 font-medium">Username: <span class="font-bold">{{ $page.props.flash.registration_success.username }}</span></p>
          </div>
        </div>
      </div>

      <!-- General Form Error Alert -->
      <div v-if="form.errors.username && form.errors.username.includes('menunggu persetujuan')" class="mb-5 p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2.5">
        <i class="bi bi-clock-history text-amber-600 text-base shrink-0 mt-0.5"></i>
        <div>
          <span class="font-bold text-amber-900">Menunggu Approval:</span>
          <p class="mt-0.5">{{ form.errors.username }}</p>
        </div>
      </div>

      <!-- Super Admin Security Token Error Alert -->
      <div v-if="form.errors.security_token" class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5 animate-shake">
        <i class="bi bi-shield-lock-fill text-rose-600 text-base shrink-0 mt-0.5"></i>
        <div>
          <span class="font-bold text-rose-900">Validasi Token Gagal:</span>
          <p class="mt-0.5">{{ form.errors.security_token }}</p>
        </div>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <!-- Pilihan Sekolah / Tenant (Global Platform disembunyikan untuk privasi) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Sekolah / Instansi</label>
          <select v-model="form.tenant_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition">
            <option value="">-- Pilih Sekolah / Deteksi Otomatis --</option>
            <option v-for="t in tenants" :key="t.id" :value="t.id">
              {{ t.nama_sekolah }} (NPSN: {{ t.npsn }})
            </option>
          </select>
        </div>

        <!-- Username / Email -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-bold text-slate-700">Nama Pengguna / Email</label>
            <span v-if="isSuperAdminInput" class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-100 text-purple-700">
              <i class="bi bi-shield-shaded"></i> Root Access
            </span>
          </div>
          <input v-model="form.username" type="text" required placeholder="Masukkan username..." 
                 class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
          <span v-if="form.errors.username && !form.errors.username.includes('menunggu persetujuan')" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.username }}</span>
        </div>

        <!-- Password -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi</label>
          <input v-model="form.password" type="password" required placeholder="••••••••" 
                 class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
          <span v-if="form.errors.password" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.password }}</span>
        </div>

        <!-- Token Keamanan Khusus Super Admin (Otomatis muncul jika akun Super Admin atau toggle aktif) -->
        <div v-if="isSuperAdminInput || showManualTokenField" class="pt-1 transition-all duration-300">
          <div class="p-3 bg-purple-50/80 border border-purple-200/80 rounded-2xl">
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-bold text-purple-900 flex items-center gap-1.5">
                <i class="bi bi-key-fill text-purple-600"></i> Token Keamanan Super Admin (16 Karakter)
              </label>
              <span class="text-[10px] font-semibold text-purple-600 uppercase tracking-wider">Wajib</span>
            </div>
            <input v-model="form.security_token" type="password" 
                   placeholder="Contoh: SINTA-2026-SEC1-ROOT" 
                   class="w-full px-3.5 py-2.5 bg-white border border-purple-200 rounded-xl text-xs font-mono tracking-widest text-purple-950 focus:ring-2 focus:ring-purple-600 focus:outline-none uppercase" />
            <p class="text-[10px] text-purple-600 mt-1">Masukkan 16 digit authorization token rahasia untuk mengakses pusat kendali.</p>
          </div>
        </div>

        <!-- Remember Me Checkbox & Helper -->
        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-medium">
            <input v-model="form.remember" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500" />
            <span>Ingat Saya</span>
          </label>
          <div class="flex items-center gap-3">
            <button type="button" @click="showManualTokenField = !showManualTokenField" 
                    class="text-slate-400 hover:text-purple-600 text-[11px] font-medium transition" 
                    :title="showManualTokenField ? 'Sembunyikan Kolom Token' : 'Masukkan Token Super Admin Manual'">
              <i class="bi" :class="showManualTokenField ? 'bi-shield-slash' : 'bi-shield-lock'"></i>
            </button>
            <Link href="/" class="text-slate-500 hover:text-blue-600 font-semibold">Ke Beranda</Link>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="form.processing"
                class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-blue-500/20 hover:shadow-lg disabled:opacity-50 flex items-center justify-center gap-2">
          <span v-if="form.processing">Memvalidasi Otorisasi...</span>
          <span v-else>Masuk ke Portal</span>
          <i class="bi bi-arrow-right font-bold"></i>
        </button>
      </form>

      <!-- Self-Registration Banner / Free Trial CTA -->
      <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">Sekolah Anda belum terdaftar di SINTA?</p>
        <Link href="/daftar-sekolah" 
              class="mt-2.5 inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition">
          <i class="bi bi-gift-fill text-emerald-600"></i>
          <span>Daftarkan Sekolah (Coba Gratis 3 Bulan)</span>
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps({
  tenants: Array,
});

const showManualTokenField = ref(false);

const form = useForm({
  tenant_id: '',
  username: '',
  password: '',
  security_token: '',
  remember: false,
});

const isSuperAdminInput = computed(() => {
  const u = (form.username || '').toLowerCase().trim();
  return u === 'superadmin' || u === 'root' || u === 'admin@sinta.id';
});

const submit = () => {
  form.post('/login');
};
</script>
