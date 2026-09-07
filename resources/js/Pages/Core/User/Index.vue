<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Manajemen Pengguna & RBAC</h1>
        <p class="text-xs text-slate-500">Pengelolaan akun pengguna dan penugasan 9 peran akses.</p>
      </div>
      <button class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
        <i class="bi bi-person-plus-fill"></i> Tambah Pengguna
      </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">Nama Lengkap</th>
              <th class="py-3.5 px-4">Nama Pengguna</th>
              <th class="py-3.5 px-4">Email</th>
              <th class="py-3.5 px-4">Peran (Role)</th>
              <th class="py-3.5 px-4">Status</th>
              <th class="py-3.5 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="u in users?.data || []" :key="u.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-bold text-slate-800">{{ u.nama_lengkap }}</td>
              <td class="py-3 px-4 font-mono">{{ u.username }}</td>
              <td class="py-3 px-4">{{ u.email }}</td>
              <td class="py-3 px-4">
                <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 capitalize">
                  {{ u.role }}
                </span>
              </td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-md text-2xs font-bold" :class="u.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                  {{ u.is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <button class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition text-xs">
                  Edit
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  users: Object,
  filters: Object,
});
</script>
