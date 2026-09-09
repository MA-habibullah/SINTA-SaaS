<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
      <!-- Brand Logo & Header -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 flex items-center justify-center text-white font-black text-2xl shadow-lg mb-4">
          S
        </div>
        <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight uppercase">SISTEM INTI AKADEMIK</h2>
        <p class="text-xs text-slate-500 mt-1">Platform Tata Kelola Akademik & Multi-Tenant Sekolah</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <!-- Pilihan Sekolah / Tenant -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Sekolah / Lembaga</label>
          <select v-model="form.tenant_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
            <option value="">-- Pilih Sekolah --</option>
            <option v-for="t in tenants" :key="t.id" :value="t.id">
              {{ t.nama_sekolah }} (NPSN: {{ t.npsn }})
            </option>
          </select>
        </div>

        <!-- Username / Email -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pengguna / Email</label>
          <input v-model="form.username" type="text" required placeholder="Masukkan username..." 
                 class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
          <span v-if="form.errors.username" class="text-xs text-red-500 font-semibold mt-1">{{ form.errors.username }}</span>
        </div>

        <!-- Password -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi</label>
          <input v-model="form.password" type="password" required placeholder="••••••••" 
                 class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
          <span v-if="form.errors.password" class="text-xs text-red-500 font-semibold mt-1">{{ form.errors.password }}</span>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center justify-between text-xs">
          <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-medium">
            <input v-model="form.remember" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500" />
            <span>Ingat Saya</span>
          </label>
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="form.processing"
                class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md hover:shadow-lg disabled:opacity-50 flex items-center justify-center gap-2">
          <span v-if="form.processing">Memproses...</span>
          <span v-else>Masuk ke Portal</span>
          <i class="bi bi-arrow-right font-bold"></i>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
  tenants: Array,
});

const form = useForm({
  tenant_id: '',
  username: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login');
};
</script>
