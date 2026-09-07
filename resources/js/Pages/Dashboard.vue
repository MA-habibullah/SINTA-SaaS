<template>
  <AppLayout>
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-3xl p-6 sm:p-8 text-white shadow-lg mb-8 relative overflow-hidden">
      <div class="relative z-10">
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight mb-2">Selamat Datang di Portal SINTA-SaaS</h1>
        <p class="text-blue-100 text-sm sm:text-base max-w-2xl">
          Sistem Tata Kelola Akademik, Kesiswaan, Keuangan, dan Operasional Sekolah Terintegrasi Multi-Tenant PostgreSQL.
        </p>
      </div>
      <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Siswa Aktif</span>
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
            <i class="bi bi-people-fill"></i>
          </div>
        </div>
        <div class="text-2xl font-black text-slate-800">1.240</div>
        <div class="text-xs text-emerald-600 font-semibold mt-1 flex items-center gap-1">
          <i class="bi bi-arrow-up-right"></i> 100% Terverifikasi
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pendidik & GTK</span>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
            <i class="bi bi-person-badge-fill"></i>
          </div>
        </div>
        <div class="text-2xl font-black text-slate-800">84</div>
        <div class="text-xs text-slate-500 font-medium mt-1">Guru & Tendik Aktif</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Penerimaan SPP Bulan Ini</span>
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
            <i class="bi bi-wallet2"></i>
          </div>
        </div>
        <div class="text-2xl font-black text-slate-800">Rp 148.5 jt</div>
        <div class="text-xs text-emerald-600 font-semibold mt-1 flex items-center gap-1">
          <i class="bi bi-check-circle-fill"></i> Realtime Kas Terupdate
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tingkat Presensi Hari Ini</span>
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
            <i class="bi bi-qr-code-scan"></i>
          </div>
        </div>
        <div class="text-2xl font-black text-slate-800">97.8%</div>
        <div class="text-xs text-emerald-600 font-semibold mt-1">Status Kehadiran Normal</div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>
