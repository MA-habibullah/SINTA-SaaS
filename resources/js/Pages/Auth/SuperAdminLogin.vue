<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4 relative overflow-hidden font-sans">
    <!-- Dark Tech Matrix Decorative Background -->
    <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-40"></div>
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full bg-slate-900/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-800 p-8 relative z-10 text-white">
      <!-- Stealth Header -->
      <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white font-black text-2xl shadow-xl shadow-purple-500/20 mb-3 border border-purple-400/30">
          <i class="bi bi-shield-lock-fill text-2xl"></i>
        </div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-950/60 border border-purple-500/30 text-purple-300 text-[11px] font-bold uppercase tracking-widest mb-2">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          Master Stealth Gateway
        </div>
        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">SINTA SAAS ROOT</h2>
        <p class="text-xs text-slate-400 mt-1">Pusat Kendali & Otorisasi Platform Sentral</p>
      </div>

      <!-- Super Admin Security Token Error Alert -->
      <div v-if="form.errors.security_token" class="mb-5 p-3.5 rounded-2xl bg-rose-950/70 border border-rose-800 text-rose-300 text-xs flex items-start gap-2.5">
        <i class="bi bi-exclamation-octagon-fill text-rose-400 text-base shrink-0 mt-0.5"></i>
        <div>
          <span class="font-bold text-rose-200">Token Keamanan Tidak Valid:</span>
          <p class="mt-0.5">{{ form.errors.security_token }}</p>
        </div>
      </div>

      <!-- General Error Alert -->
      <div v-if="form.errors.username" class="mb-5 p-3.5 rounded-2xl bg-rose-950/70 border border-rose-800 text-rose-300 text-xs flex items-start gap-2.5">
        <i class="bi bi-x-circle-fill text-rose-400 text-base shrink-0 mt-0.5"></i>
        <div>
          <span class="font-bold text-rose-200">Akses Ditolak:</span>
          <p class="mt-0.5">{{ form.errors.username }}</p>
        </div>
      </div>

      <!-- Root Login Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <!-- Root Username -->
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5 flex items-center justify-between">
            <span>ID Administrator Sentral</span>
            <span class="text-[10px] text-purple-400 font-normal">Super Admin Account</span>
          </label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
              <i class="bi bi-person-badge"></i>
            </span>
            <input v-model="form.username" type="text" required placeholder="superadmin" 
                   class="w-full pl-10 pr-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs font-medium text-white placeholder-slate-600 focus:ring-2 focus:ring-purple-500 focus:border-transparent focus:outline-none transition" />
          </div>
        </div>

        <!-- Root Password -->
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5 flex items-center justify-between">
            <span>Kata Sandi Master</span>
            <span class="text-[10px] text-slate-500 font-normal">Encrypted</span>
          </label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
              <i class="bi bi-key"></i>
            </span>
            <input v-model="form.password" type="password" required placeholder="••••••••••••" 
                   class="w-full pl-10 pr-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs font-medium text-white placeholder-slate-600 focus:ring-2 focus:ring-purple-500 focus:border-transparent focus:outline-none transition" />
          </div>
          <span v-if="form.errors.password" class="text-xs text-rose-400 font-semibold mt-1 block">{{ form.errors.password }}</span>
        </div>

        <!-- 16-Character Security Token Field -->
        <div class="pt-1">
          <div class="p-3.5 bg-purple-950/40 border border-purple-700/50 rounded-2xl">
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-bold text-purple-300 flex items-center gap-1.5">
                <i class="bi bi-cpu text-purple-400"></i> Token Otorisasi Khusus (16 Karakter)
              </label>
              <span class="text-[10px] font-bold text-purple-400 bg-purple-900/60 px-2 py-0.5 rounded border border-purple-600/40">16-CHAR</span>
            </div>
            <div class="relative">
              <input v-model="form.security_token" type="password" required
                     placeholder="SINTA-2026-SEC1-ROOT" 
                     class="w-full px-3.5 py-2.5 bg-slate-950 border border-purple-500/40 rounded-xl text-xs font-mono tracking-widest text-purple-200 placeholder-purple-800/60 focus:ring-2 focus:ring-purple-500 focus:outline-none uppercase" />
            </div>
            <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1.5">
              <span>Token didefinisikan pada konfigurasi server .env</span>
              <span class="font-mono text-purple-400 font-bold">{{ cleanTokenLength }} / 16 chars</span>
            </div>
          </div>
        </div>

        <!-- Helper Actions -->
        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-slate-300 font-medium">
            <input v-model="form.remember" type="checkbox" class="rounded bg-slate-950 border-slate-700 text-purple-600 focus:ring-purple-500" />
            <span>Simpan Sesi Root</span>
          </label>
          <Link href="/login" class="text-slate-500 hover:text-purple-400 text-xs transition">
            Portal Sekolah Publik &rarr;
          </Link>
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="form.processing"
                class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-extrabold rounded-xl transition-all shadow-lg shadow-purple-600/30 hover:shadow-purple-600/50 disabled:opacity-50 flex items-center justify-center gap-2">
          <i class="bi bi-shield-check" v-if="!form.processing"></i>
          <span v-if="form.processing">Mengautentikasi Kredensial & Token...</span>
          <span v-else>Buka Pusat Kendali SaaS</span>
        </button>
      </form>

      <!-- Footer Security Notice -->
      <div class="mt-6 pt-5 border-t border-slate-800/80 text-center">
        <p class="text-[11px] text-slate-500">
          <i class="bi bi-lock-fill text-slate-400 me-1"></i> Area terproteksi enkripsi bertingkat. Akses tidak sah akan dicatat dalam audit keamanan.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  tenant_id: '00000000-0000-0000-0000-000000000000',
  username: '',
  password: '',
  security_token: '',
  remember: false,
});

const cleanTokenLength = computed(() => {
  return (form.security_token || '').replace(/[- _]/g, '').length;
});

const submit = () => {
  form.post('/super-admin/login');
};
</script>
