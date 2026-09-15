<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SubscriptionCountdownBanner from '@/Components/SubscriptionCountdownBanner.vue';
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js';
import axios from 'axios';

const props = defineProps({
  user: Object,
  tenant: Object,
  is_super_admin: Boolean,
  is_admin_sekolah: Boolean,
  is_guru: Boolean,
  is_siswa: Boolean,
  is_keuangan: Boolean,
  is_perpus: Boolean,
  is_bk: Boolean,
  is_sarpras: Boolean,
  quick_actions: Array,
  announcements: Array,
  agendas: Array,
  super_admin_data: Object,
  school_ops_data: Object,
  teacher_data: Object,
  student_data: Object,
  specialist_data: Object,
});

// Memory security cleanup
const dashboardState = ref({
  refreshing: false,
  currentTime: '',
  currentDateStr: '',
  surveyModalOpen: false,
  selectedTeacherSurvey: null,
});

useMemorySecurity([dashboardState]);

// Format Currency
const formatRupiah = (val) => {
  if (!val && val !== 0) return 'Rp 0';
  return 'Rp ' + Number(val).toLocaleString('id-ID');
};

// Format Date
const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Greeting by Time of Day
const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 11) return 'Selamat Pagi';
  if (hour < 15) return 'Selamat Siang';
  if (hour < 18) return 'Selamat Sore';
  return 'Selamat Malam';
});

// Update live clock
const updateClock = () => {
  const now = new Date();
  dashboardState.value.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  dashboardState.value.currentDateStr = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

onMounted(() => {
  updateClock();
  setInterval(updateClock, 1000);
});

// Refresh data
const refreshData = () => {
  dashboardState.value.refreshing = true;
  router.reload({
    only: ['super_admin_data', 'school_ops_data', 'teacher_data', 'student_data', 'specialist_data', 'announcements', 'agendas'],
    onFinish: () => {
      dashboardState.value.refreshing = false;
    }
  });
};

const handleQuickAction = (action) => {
  if (action.is_modal && action.href === '#student-survey') {
    dashboardState.value.surveyModalOpen = true;
  }
};
</script>

<template>
  <AppLayout title="Dashboard">
    <Head title="Dashboard Multi-Role" />

    <div class="space-y-6 pb-12">
      <!-- 0. SAAS SUBSCRIPTION COUNTDOWN TIMER BANNER -->
      <SubscriptionCountdownBanner :tenant="tenant" :is-super-admin="is_super_admin" />

      <!-- 1. TOP HEADER & GREETING HERO -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 p-6 sm:p-8 text-white shadow-2xl">
        <!-- Glow Effects -->
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
          <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                <i class="bi bi-shield-lock-fill text-indigo-400"></i>
                {{ user?.role_label || 'Pengguna' }}
              </span>
              <span v-if="tenant" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                <i class="bi bi-building text-slate-400"></i>
                {{ tenant.nama_sekolah }} (NPSN: {{ tenant.npsn || '-' }})
              </span>
              <span v-else-if="is_super_admin" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                <i class="bi bi-cpu-fill text-amber-400"></i>
                Platform Super Administrator (Multi-Tenant)
              </span>
            </div>

            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white">
              {{ greeting }}, <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-sky-300 to-teal-200">{{ user?.nama }}</span>
            </h1>
            <p class="text-slate-300 text-xs sm:text-sm max-w-2xl leading-relaxed">
              Selamat datang di Pusat Kendali SINTA. Pantau performa, data operasional, dan tindak lanjuti tugas harian sekolah Anda dengan cepat dan terintegrasi.
            </p>
          </div>

          <!-- Date & Live Time Widget -->
          <div class="flex items-center gap-3 self-start lg:self-center shrink-0">
            <div class="bg-slate-800/80 backdrop-blur-md border border-slate-700/80 rounded-2xl p-3.5 text-right shadow-inner">
              <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider flex items-center justify-end gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>{{ dashboardState.currentDateStr || 'Hari Ini' }}</span>
              </div>
              <div class="text-xl font-black text-slate-100 font-mono tracking-wider mt-0.5">
                {{ dashboardState.currentTime || '00:00:00' }} <span class="text-xs text-indigo-400 font-sans">WIB</span>
              </div>
            </div>

            <button
              @click="refreshData"
              :disabled="dashboardState.refreshing"
              class="w-12 h-12 rounded-2xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white flex items-center justify-center transition shadow-lg shadow-indigo-600/30 disabled:opacity-50"
              title="Perbarui Data"
            >
              <i class="bi bi-arrow-clockwise text-xl" :class="{ 'animate-spin': dashboardState.refreshing }"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- 2. NOTIFIKASI TRIAL / SURVEI GURU -->
      <!-- Free Trial Banner -->
      <div
        v-if="tenant?.is_trial_active || tenant?.trial_ends_at"
        class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl shrink-0 text-yellow-300 shadow-inner">
            <i class="bi bi-gift-fill"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded-md bg-white/20 text-[10px] font-black uppercase tracking-wider">Masa Percobaan Aktif</span>
              <span class="text-xs text-emerald-100">{{ tenant?.subscription_type || 'Free Trial 14 Hari' }}</span>
            </div>
            <p class="text-xs sm:text-sm font-semibold mt-0.5">
              Seluruh modul SINTA aktif penuh. Berakhir pada:
              <span class="font-bold underline">{{ formatDate(tenant?.trial_ends_at) }}</span>
            </p>
          </div>
        </div>
        <a
          href="https://wa.me/6281388884043?text=Halo%20Admin%20SINTA,%20saya%20ingin%20konsultasi%20perpanjangan%20layanan"
          target="_blank"
          class="px-4 py-2.5 rounded-xl bg-white text-teal-800 hover:bg-teal-50 font-bold text-xs shadow-md transition inline-flex items-center justify-center gap-1.5 shrink-0"
        >
          <i class="bi bi-whatsapp text-emerald-600"></i>
          <span>Konsultasi / Upgrade Lisensi</span>
        </a>
      </div>

      <!-- Student Survey Callout (Khusus Siswa) -->
      <div
        v-if="is_siswa"
        class="bg-gradient-to-r from-indigo-700 via-purple-700 to-pink-700 rounded-2xl p-4 sm:p-5 text-white shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-amber-400 text-slate-900 flex items-center justify-center text-2xl shrink-0 shadow-md">
            <i class="bi bi-star-fill"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded-md bg-amber-400 text-slate-900 text-[10px] font-black uppercase tracking-wider">Evaluasi Guru Semester</span>
              <span class="text-xs text-purple-200">Survei Anonim</span>
            </div>
            <p class="text-xs sm:text-sm font-semibold mt-0.5">
              Beri penilaian dan masukan untuk bapak/ibu guru pengampu demi peningkatan mutu belajar mengajar.
            </p>
          </div>
        </div>
        <button
          type="button"
          @click="dashboardState.surveyModalOpen = true"
          class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-900 font-bold text-xs shadow-md transition inline-flex items-center justify-center gap-1.5 shrink-0 cursor-pointer"
        >
          <i class="bi bi-pencil-square"></i>
          <span>Buka Form Survei</span>
        </button>
      </div>

      <!-- 3. QUICK ACTIONS GRID -->
      <div v-if="quick_actions?.length" class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
            <i class="bi bi-lightning-charge-fill text-amber-500"></i>
            Tindakan Cepat (Quick Actions)
          </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <component
            :is="action.is_modal ? 'button' : Link"
            v-for="(action, idx) in quick_actions"
            :key="idx"
            :href="!action.is_modal ? action.href : undefined"
            @click="action.is_modal ? handleQuickAction(action) : null"
            class="group text-left p-4 rounded-2xl bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-lg transition duration-200 flex items-start gap-3.5 relative overflow-hidden"
          >
            <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${action.color} text-white flex items-center justify-center text-xl shrink-0 shadow-md group-hover:scale-105 transition`">
              <i :class="action.icon"></i>
            </div>
            <div class="min-w-0 flex-1">
              <div class="font-bold text-sm text-slate-800 group-hover:text-indigo-600 transition truncate">
                {{ action.label }}
              </div>
              <p class="text-xs text-slate-500 line-clamp-2 mt-0.5">
                {{ action.desc }}
              </p>
            </div>
            <div class="self-center text-slate-300 group-hover:text-indigo-500 group-hover:translate-x-0.5 transition">
              <i class="bi bi-chevron-right text-xs"></i>
            </div>
          </component>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- 4A. ROLE VIEW: SUPERADMIN (PLATFORM HEALTH & MULTI-TENANCY) -->
      <!-- ========================================================================= -->
      <div v-if="is_super_admin && super_admin_data" class="space-y-6">
        <!-- SuperAdmin KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Total Tenants -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Tenants (Sekolah)</span>
              <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="bi bi-buildings-fill"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ super_admin_data.total_tenants }}</div>
            <div class="text-xs text-slate-500 mt-2 flex items-center gap-2">
              <span class="text-emerald-600 font-bold">{{ super_admin_data.active_tenants }} Aktif</span>
              <span>•</span>
              <span class="text-amber-600 font-bold">{{ super_admin_data.trial_tenants }} Trial</span>
              <span v-if="super_admin_data.pending_tenants > 0" class="text-rose-600 font-bold">• {{ super_admin_data.pending_tenants }} Pending</span>
            </div>
          </div>

          <!-- Total Platform Users -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Akun Pengguna</span>
              <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="bi bi-people-fill"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ super_admin_data.total_users }}</div>
            <div class="text-xs text-blue-600 font-medium mt-2 flex items-center gap-1">
              <i class="bi bi-person-check-fill"></i> Lintas Seluruh Tenant
            </div>
          </div>

          <!-- Error Logs 24h -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Log Error (24 Jam)</span>
              <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                <i class="bi bi-bug-fill"></i>
              </div>
            </div>
            <div class="text-3xl font-black" :class="super_admin_data.errors_24h > 0 ? 'text-rose-600' : 'text-slate-800'">
              {{ super_admin_data.errors_24h }}
            </div>
            <div class="text-xs mt-2 flex items-center gap-1" :class="super_admin_data.errors_24h === 0 ? 'text-emerald-600 font-bold' : 'text-rose-500 font-medium'">
              <i :class="super_admin_data.errors_24h === 0 ? 'bi-shield-check' : 'bi-exclamation-triangle-fill'"></i>
              {{ super_admin_data.errors_24h === 0 ? 'Kondisi Server Prima' : 'Perlu Investigasi Log' }}
            </div>
          </div>

          <!-- Monthly SaaS MRR -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Estimasi MRR Lisensi</span>
              <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                <i class="bi bi-cash-stack"></i>
              </div>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ formatRupiah(super_admin_data.estimated_revenue) }}</div>
            <div class="text-xs text-purple-600 font-medium mt-2 flex items-center gap-1">
              <i class="bi bi-graph-up-arrow"></i> Berdasarkan Tenant Aktif
            </div>
          </div>
        </div>

        <!-- SuperAdmin Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Recent Tenants Table -->
          <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h3 class="font-bold text-slate-800 text-base">Pendaftaran & Status Sekolah Terbaru</h3>
                <p class="text-xs text-slate-500">Monitoring multi-tenant realtime</p>
              </div>
              <Link href="/core/tenants" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                Lihat Semua <i class="bi bi-arrow-right"></i>
              </Link>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                  <tr>
                    <th class="px-5 py-3">Nama Sekolah</th>
                    <th class="px-5 py-3">Domain / Subdomain</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="t in super_admin_data.recent_tenants" :key="t.id" class="hover:bg-slate-50/80 transition">
                    <td class="px-5 py-3.5">
                      <div class="font-bold text-slate-800">{{ t.nama_sekolah }}</div>
                      <div class="text-[11px] text-slate-400">NPSN: {{ t.npsn || '-' }}</div>
                    </td>
                    <td class="px-5 py-3.5 font-mono text-[11px]">
                      {{ t.subdomain ? `${t.subdomain}.sinta.id` : '-' }}
                    </td>
                    <td class="px-5 py-3.5">
                      <span
                        class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                        :class="{
                          'bg-emerald-100 text-emerald-800': t.status === 'approved' || t.status === 'aktif',
                          'bg-amber-100 text-amber-800': t.status === 'pending',
                          'bg-rose-100 text-rose-800': t.status === 'suspended' || t.status === 'rejected',
                        }"
                      >
                        {{ t.status }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                      <Link :href="`/core/tenants`" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
                        Detail
                      </Link>
                    </td>
                  </tr>
                  <tr v-if="!super_admin_data.recent_tenants?.length">
                    <td colspan="4" class="px-5 py-8 text-center text-slate-400">Belum ada data tenant terdaftar.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Platform System Health Card -->
          <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
              <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                <i class="bi bi-hdd-rack text-indigo-600"></i>
                Status Server & Database
              </h3>

              <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-500 font-medium">PostgreSQL Multi-Schema</span>
                  <span class="font-bold text-slate-800">{{ super_admin_data.database_size }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-500 font-medium">PHP Version</span>
                  <span class="font-bold text-slate-800 font-mono">{{ super_admin_data.php_version }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-500 font-medium">Laravel Framework</span>
                  <span class="font-bold text-slate-800 font-mono">v{{ super_admin_data.laravel_version }}</span>
                </div>
              </div>
            </div>

            <!-- Recent System Errors -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                  <i class="bi bi-exclamation-octagon text-rose-500"></i>
                  Log Error Terkini
                </h3>
              </div>

              <div class="space-y-2.5">
                <div
                  v-for="(err, idx) in super_admin_data.recent_errors"
                  :key="idx"
                  class="p-3 rounded-xl bg-rose-50/60 border border-rose-100 text-xs space-y-1"
                >
                  <div class="flex items-center justify-between text-[11px] font-bold text-rose-700">
                    <span>{{ err.error_level || 'ERROR' }}</span>
                    <span class="text-rose-400 font-normal">{{ formatDate(err.created_at) }}</span>
                  </div>
                  <p class="text-rose-900 font-medium truncate">{{ err.message }}</p>
                  <p class="text-[10px] text-rose-500 font-mono truncate">{{ err.file }}:{{ err.line }}</p>
                </div>
                <div v-if="!super_admin_data.recent_errors?.length" class="text-center py-4 text-xs text-slate-400">
                  <i class="bi bi-check2-circle text-emerald-500 text-lg block mb-1"></i>
                  Tidak ada catatan error kritis.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- 4B. ROLE VIEW: ADMIN SEKOLAH & KEPSEK & TU (SCHOOL OPERATIONS) -->
      <!-- ========================================================================= -->
      <div v-if="is_admin_sekolah && school_ops_data" class="space-y-6">
        <!-- School Ops KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Siswa Aktif -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Siswa Aktif</span>
              <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="bi bi-people-fill"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ school_ops_data.total_siswa }}</div>
            <div class="text-xs text-emerald-600 font-semibold mt-2 flex items-center gap-1">
              <i class="bi bi-check-circle-fill"></i> {{ school_ops_data.total_kelas }} Rombel / Kelas Terdaftar
            </div>
          </div>

          <!-- GTK & Pendidik -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pendidik & GTK</span>
              <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="bi bi-person-badge-fill"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ school_ops_data.total_gtk }}</div>
            <div class="text-xs text-slate-500 font-medium mt-2">Guru & Tenaga Kependidikan</div>
          </div>

          <!-- Presensi Hari Ini -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kehadiran Siswa Hari Ini</span>
              <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                <i class="bi bi-qr-code-scan"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ school_ops_data.attendance_today.rate }}%</div>
            <div class="text-xs text-slate-500 mt-2 flex items-center gap-2">
              <span class="text-emerald-600 font-bold">{{ school_ops_data.attendance_today.hadir }} Hadir</span>
              <span>•</span>
              <span class="text-amber-600 font-bold">{{ school_ops_data.attendance_today.izin + school_ops_data.attendance_today.sakit }} Izin/Sakit</span>
              <span>•</span>
              <span class="text-rose-600 font-bold">{{ school_ops_data.attendance_today.alpa }} Alpa</span>
            </div>
          </div>

          <!-- Penerimaan SPP Hari Ini -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kas Masuk Hari Ini</span>
              <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="bi bi-wallet2"></i>
              </div>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ formatRupiah(school_ops_data.kas_masuk_hari_ini) }}</div>
            <div class="text-xs text-slate-500 mt-2 flex items-center gap-1">
              <span class="text-rose-600 font-medium">Tunggakan: {{ formatRupiah(school_ops_data.tagihan_tertunggak) }}</span>
            </div>
          </div>
        </div>

        <!-- School Attendance & Operations Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Attendance Trend & Fraud Alert -->
          <div class="lg:col-span-2 space-y-6">
            <!-- 7 Days Trend Chart Bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-bold text-slate-800 text-base">Tren Kehadiran Siswa (7 Hari Terakhir)</h3>
                  <p class="text-xs text-slate-500">Statistik jumlah siswa hadir harian</p>
                </div>
                <Link href="/absensi/presensi-siswa" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                  Buka Rekap Absensi
                </Link>
              </div>

              <div class="grid grid-cols-7 gap-2 pt-4 items-end h-40 border-b border-slate-100 pb-2">
                <div
                  v-for="(t, idx) in school_ops_data.attendance_trend"
                  :key="idx"
                  class="flex flex-col items-center gap-1.5 h-full justify-end"
                >
                  <div class="text-[10px] font-bold text-slate-600">{{ t.hadir_count }}</div>
                  <div
                    class="w-full max-w-[36px] bg-indigo-600 hover:bg-indigo-500 rounded-t-lg transition"
                    :style="{ height: `${Math.max(15, Math.min(100, (t.hadir_count / (school_ops_data.total_siswa || 1)) * 100))}%` }"
                  ></div>
                  <div class="text-[11px] font-semibold text-slate-500">{{ t.day }}</div>
                </div>
              </div>
            </div>

            <!-- Anti-Fraud GPS Alerts -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                  <i class="bi bi-shield-slash text-rose-500"></i>
                  Peringatan Anti-Fraud Presensi GPS
                </h3>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                  {{ school_ops_data.attendance_today.fraud_count }} Indikasi Hari Ini
                </span>
              </div>

              <div class="space-y-2.5">
                <div
                  v-for="(fraud, idx) in school_ops_data.recent_frauds"
                  :key="idx"
                  class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between gap-3"
                >
                  <div class="space-y-0.5 min-w-0">
                    <div class="font-bold text-slate-800 truncate">{{ fraud.nama_pelaku }} ({{ fraud.tipe_pengguna }})</div>
                    <div class="text-[11px] text-rose-600 font-medium">{{ fraud.fraud_reason || 'Mock Location / Diluar Geofence' }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">Jarak: {{ fraud.jarak_meter }}m • Akurasi: {{ fraud.akurasi_meter }}m</div>
                  </div>
                  <span class="px-2 py-1 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800 shrink-0 uppercase">
                    Fraud Alert
                  </span>
                </div>

                <div v-if="!school_ops_data.recent_frauds?.length" class="text-center py-4 text-xs text-slate-400">
                  <i class="bi bi-shield-check text-emerald-500 text-lg block mb-1"></i>
                  Tidak ada aktivitas manipulasi lokasi GPS yang terdeteksi.
                </div>
              </div>
            </div>
          </div>

          <!-- BK & Agenda Sidebar -->
          <div class="space-y-6">
            <!-- BK Pelanggaran Siswa -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                  <i class="bi bi-journal-x text-amber-500"></i>
                  Catatan Pelanggaran BK
                </h3>
                <Link href="/bk/pelanggaran" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">Lihat</Link>
              </div>

              <div class="space-y-2.5">
                <div
                  v-for="(bk, idx) in school_ops_data.recent_violations"
                  :key="idx"
                  class="p-3 rounded-xl bg-amber-50/60 border border-amber-100 text-xs space-y-1"
                >
                  <div class="flex items-center justify-between font-bold text-slate-800">
                    <span class="truncate">{{ bk.snapshot_nama_siswa || 'Siswa' }}</span>
                    <span class="text-amber-700 font-bold shrink-0">+{{ bk.poin_pelanggaran }} Poin</span>
                  </div>
                  <p class="text-slate-600 text-[11px] truncate">{{ bk.nama_pelanggaran }}</p>
                </div>
                <div v-if="!school_ops_data.recent_violations?.length" class="text-center py-4 text-xs text-slate-400">
                  Belum ada catatan pelanggaran baru.
                </div>
              </div>
            </div>

            <!-- Announcements Widget -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
              <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i class="bi bi-megaphone text-blue-600"></i>
                Pengumuman Sekolah
              </h3>

              <div class="space-y-2.5">
                <div
                  v-for="(ann, idx) in announcements"
                  :key="idx"
                  class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-1"
                >
                  <div class="font-bold text-slate-800">{{ ann.judul }}</div>
                  <p class="text-slate-500 text-[11px] line-clamp-2">{{ ann.deskripsi }}</p>
                  <div class="text-[10px] text-slate-400">{{ formatDate(ann.created_at) }}</div>
                </div>
                <div v-if="!announcements?.length" class="text-center py-4 text-xs text-slate-400">
                  Tidak ada pengumuman aktif.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- 4C. ROLE VIEW: GURU & WAKA KURIKULUM (KBM & JURNAL MENGAJAR) -->
      <!-- ========================================================================= -->
      <div v-if="is_guru && teacher_data" class="space-y-6">
        <!-- Guru KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Jurnal Mengajar Hari Ini -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jurnal KBM Hari Ini</span>
              <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                <i class="bi bi-journal-check"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ teacher_data.jurnal_hari_ini }}</div>
            <div class="text-xs font-semibold mt-2" :class="teacher_data.jurnal_hari_ini > 0 ? 'text-emerald-600' : 'text-amber-600'">
              {{ teacher_data.jurnal_hari_ini > 0 ? '✓ Sudah Terisi Hari Ini' : '⚠️ Belum Mengisi Jurnal' }}
            </div>
          </div>

          <!-- Total Jam / Penugasan -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Penugasan Mengajar</span>
              <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="bi bi-calendar-week-fill"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ teacher_data.total_penugasan || '24 Jam' }}</div>
            <div class="text-xs text-slate-500 font-medium mt-2">Beban Mengajar Semester Ini</div>
          </div>

          <!-- Batas Waktu Nilai Rapor -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Deadline Kunci Nilai</span>
              <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="bi bi-clock-history"></i>
              </div>
            </div>
            <div class="text-xl font-black text-slate-800">{{ formatDate(teacher_data.deadline_rapor) }}</div>
            <div class="text-xs text-amber-600 font-medium mt-2 flex items-center gap-1">
              <i class="bi bi-exclamation-circle-fill"></i> Penguncian Rapor Semester
            </div>
          </div>

          <!-- Kelas Ampu -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kelas Bimbingan / Ampu</span>
              <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                <i class="bi bi-easel-fill"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ teacher_data.kelas_list?.length || 0 }}</div>
            <div class="text-xs text-teal-600 font-medium mt-2">Rombongan Belajar Aktif</div>
          </div>
        </div>

        <!-- Teacher Action Center -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-5">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-bold text-slate-800 text-lg">Jurnal & Agenda Mengajar Hari Ini</h3>
                <p class="text-xs text-slate-500">Catat ketercapaian materi dan kehadiran siswa di kelas</p>
              </div>
              <Link
                href="/absensi/jurnal-mengajar"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition inline-flex items-center gap-1.5"
              >
                <i class="bi bi-plus-circle"></i>
                <span>Tulis Jurnal Baru</span>
              </Link>
            </div>

            <div class="p-4 rounded-xl bg-indigo-50/60 border border-indigo-100 flex items-start gap-3">
              <i class="bi bi-info-circle-fill text-indigo-600 text-lg shrink-0 mt-0.5"></i>
              <p class="text-xs text-indigo-950 leading-relaxed">
                Pengisian jurnal mengajar secara tepat waktu menjamin kelancaran rekapitulasi jam kerja dan penilaian capaian kurikulum merdeka.
              </p>
            </div>

            <div class="space-y-3">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Daftar Kelas Bimbingan</h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div
                  v-for="(k, idx) in teacher_data.kelas_list"
                  :key="idx"
                  class="p-3.5 rounded-xl border border-slate-200 hover:border-indigo-300 transition flex items-center justify-between"
                >
                  <div>
                    <div class="font-bold text-slate-800 text-sm">{{ k.nama_kelas || k.nama }}</div>
                    <div class="text-[11px] text-slate-400">Tingkat: {{ k.tingkat || '-' }}</div>
                  </div>
                  <Link
                    :href="`/absensi/presensi-siswa`"
                    class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 font-bold text-xs transition"
                  >
                    Presensi
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- Pengumuman & Agenda Guru -->
          <div class="space-y-6">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
              <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i class="bi bi-calendar-event text-indigo-600"></i>
                Agenda Kegiatan Sekolah
              </h3>

              <div class="space-y-2.5">
                <div
                  v-for="(ag, idx) in agendas"
                  :key="idx"
                  class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-1"
                >
                  <div class="font-bold text-slate-800">{{ ag.nama_agenda_sekolah }}</div>
                  <p class="text-slate-500 text-[11px] line-clamp-2">{{ ag.deskripsi }}</p>
                </div>
                <div v-if="!agendas?.length" class="text-center py-4 text-xs text-slate-400">
                  Tidak ada agenda mendatang.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- 4D. ROLE VIEW: SISWA & ORANG TUA (SELF-SERVICE PORTAL) -->
      <!-- ========================================================================= -->
      <div v-if="is_siswa && student_data" class="space-y-6">
        <!-- Siswa KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Status Presensi Hari Ini -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Presensi Hari Ini</span>
              <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="bi bi-geo-alt-fill"></i>
              </div>
            </div>
            <div class="text-2xl font-black text-slate-800">
              {{ student_data.presensi_hari_ini ? student_data.presensi_hari_ini.status_kehadiran : 'Belum Absen' }}
            </div>
            <div class="text-xs font-medium mt-2" :class="student_data.presensi_hari_ini ? 'text-emerald-600' : 'text-rose-500'">
              {{ student_data.presensi_hari_ini ? `Masuk: ${student_data.presensi_hari_ini.jam_masuk || '-'}` : 'Silakan lakukan check-in GPS' }}
            </div>
          </div>

          <!-- Tingkat Kehadiran Bulan Ini -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kehadiran Bulan Ini</span>
              <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="bi bi-pie-chart-fill"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ student_data.monthly_attendance_rate }}%</div>
            <div class="text-xs text-blue-600 font-medium mt-2 flex items-center gap-1">
              <i class="bi bi-check-circle-fill"></i> Rekapitulasi Kehadiran Normal
            </div>
          </div>

          <!-- Total Tagihan SPP Tertunggak -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tagihan SPP Belum Lunas</span>
              <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="bi bi-credit-card-2-front-fill"></i>
              </div>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ formatRupiah(student_data.total_tunggakan) }}</div>
            <div class="text-xs mt-2" :class="student_data.total_tunggakan === 0 ? 'text-emerald-600 font-bold' : 'text-amber-600 font-medium'">
              {{ student_data.total_tunggakan === 0 ? '✓ Seluruh Tagihan Lunas' : 'Menunggu Pembayaran' }}
            </div>
          </div>

          <!-- Pinjaman Perpustakaan -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Buku Dipinjam</span>
              <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                <i class="bi bi-book-half"></i>
              </div>
            </div>
            <div class="text-3xl font-black text-slate-800">{{ student_data.borrowed_books?.length || 0 }}</div>
            <div class="text-xs text-purple-600 font-medium mt-2">Buku Perpustakaan Aktif</div>
          </div>
        </div>

        <!-- Student Bills & Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Tagihan SPP Table -->
          <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-bold text-slate-800 text-base">Rincian Tagihan SPP & Pembayaran</h3>
                <p class="text-xs text-slate-500">Status administrasi keuangan siswa</p>
              </div>
              <Link
                v-if="student_data.total_tunggakan > 0"
                href="/keuangan/tagihan"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition inline-flex items-center gap-1.5"
              >
                <i class="bi bi-credit-card"></i>
                <span>Bayar Sekarang</span>
              </Link>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                  <tr>
                    <th class="px-4 py-3">No. Tagihan</th>
                    <th class="px-4 py-3">Periode</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="tag in student_data.tagihan_list" :key="tag.id">
                    <td class="px-4 py-3 font-mono font-medium">{{ tag.nomor_tagihan || '-' }}</td>
                    <td class="px-4 py-3">Bulan {{ tag.bulan }}/{{ tag.tahun }}</td>
                    <td class="px-4 py-3 font-bold text-slate-800">{{ formatRupiah(tag.total_tagihan) }}</td>
                    <td class="px-4 py-3">
                      <span
                        class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                        :class="tag.status_pembayaran === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                      >
                        {{ tag.status_pembayaran }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="!student_data.tagihan_list?.length">
                    <td colspan="4" class="px-4 py-6 text-center text-slate-400">Tidak ada tagihan tertunggak.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Announcements & Agendas -->
          <div class="space-y-6">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
              <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i class="bi bi-megaphone-fill text-indigo-600"></i>
                Informasi & Pengumuman
              </h3>

              <div class="space-y-2.5">
                <div
                  v-for="(ann, idx) in announcements"
                  :key="idx"
                  class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-1"
                >
                  <div class="font-bold text-slate-800">{{ ann.judul }}</div>
                  <p class="text-slate-500 text-[11px] line-clamp-2">{{ ann.deskripsi }}</p>
                </div>
                <div v-if="!announcements?.length" class="text-center py-4 text-xs text-slate-400">
                  Belum ada pengumuman terbaru.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- 4E. ROLE VIEW: SPESIALIS (KEUANGAN / KASIR, PERPUSTAKAAN, BK, SARPRAS) -->
      <!-- ========================================================================= -->
      <div v-if="is_keuangan || is_perpus || is_bk || is_sarpras" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-2xs space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="text-lg font-bold text-slate-800">Panel Ringkasan Domain: {{ user?.role_label }}</h3>
              <p class="text-xs text-slate-500">Informasi operasional modul khusus</p>
            </div>
          </div>

          <!-- Keuangan Details -->
          <div v-if="is_keuangan && specialist_data.keuangan" class="space-y-4">
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-emerald-800 uppercase">Penerimaan Kas SPP Hari Ini</span>
                <div class="text-2xl font-black text-emerald-900 mt-0.5">
                  {{ formatRupiah(specialist_data.keuangan.kas_masuk_hari_ini) }}
                </div>
              </div>
              <Link href="/keuangan/transaksi" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition">
                Input Transaksi
              </Link>
            </div>
          </div>

          <!-- Perpustakaan Details -->
          <div v-if="is_perpus && specialist_data.perpustakaan" class="space-y-4">
            <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-indigo-800 uppercase">Total Sirkulasi Buku Aktif</span>
                <div class="text-2xl font-black text-indigo-900 mt-0.5">
                  {{ specialist_data.perpustakaan.total_sirkulasi_aktif }} Buku
                </div>
              </div>
              <Link href="/perpustakaan/sirkulasi" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition">
                Kelola Sirkulasi
              </Link>
            </div>
          </div>

          <!-- BK Details -->
          <div v-if="is_bk && specialist_data.bk" class="space-y-4">
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-amber-800 uppercase">Total Pelanggaran Tercatat</span>
                <div class="text-2xl font-black text-amber-900 mt-0.5">
                  {{ specialist_data.bk.total_pelanggaran }} Catatan
                </div>
              </div>
              <Link href="/bk/pelanggaran" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition">
                Buka Layanan BK
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 5. MODAL SURVEI GURU (SISWA) -->
    <div
      v-if="dashboardState.surveyModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fade-in"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
              <i class="bi bi-star-fill"></i>
            </div>
            <div>
              <h3 class="font-bold text-slate-800 text-base">Survei Kinerja Guru</h3>
              <p class="text-xs text-slate-500">Evaluasi Pembelajaran Siswa (100% Anonim)</p>
            </div>
          </div>
          <button
            @click="dashboardState.surveyModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-sm"
          >
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/60 text-xs text-amber-900 leading-relaxed">
          Umpan balik Anda sangat berharga untuk mengevaluasi kualitas penyampaian materi, kedisiplinan waktu, dan kenyamanan interaksi kelas.
        </div>

        <div class="space-y-3 text-xs">
          <label class="font-bold text-slate-700">Pilih Guru yang Ingin Dinilai</label>
          <select class="w-full p-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500">
            <option>-- Pilih Guru Pengampu Kelas Anda --</option>
            <option>Andi Bahasa, M.Pd (Bahasa Indonesia)</option>
            <option>Budi Santoso, S.Pd (Matematika)</option>
            <option>Dewi Lestari, S.Si (Ilmu Pengetahuan Alam)</option>
          </select>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            @click="dashboardState.surveyModalOpen = false"
            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs"
          >
            Batal
          </button>
          <button
            @click="dashboardState.surveyModalOpen = false"
            class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold text-xs shadow-md"
          >
            Mulai Isi Penilaian
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
