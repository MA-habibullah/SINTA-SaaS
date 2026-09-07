<template>
  <div class="min-h-screen bg-slate-100 flex flex-col">
    <!-- Topbar Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <!-- Logo & Platform Name -->
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-md">
              S
            </div>
            <div>
              <div class="font-extrabold text-slate-800 text-lg leading-tight tracking-tight">SINTA-SaaS</div>
              <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ tenantName || 'Tenant Sekolah Terhubung' }}</span>
              </div>
            </div>
          </div>

          <!-- Topbar Actions & User Info -->
          <div class="flex items-center gap-4">
            <div class="hidden md:flex flex-col text-right">
              <span class="text-sm font-bold text-slate-700">{{ user?.nama_lengkap || 'Pengguna' }}</span>
              <span class="text-xs text-blue-600 font-semibold capitalize">{{ user?.role || 'Operator' }}</span>
            </div>

            <!-- Logout Button -->
            <button @click="logout" class="p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all" title="Keluar">
              <i class="bi bi-box-arrow-right text-lg"></i>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Container with Horizontal Pill NavTabs -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full">
      <!-- Horizontal NavTabs 3-Way Scroller (Standard SINTA SaaS) -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-6 position-relative">
        <div class="flex items-center relative">
          <!-- Tombol Geser Kiri -->
          <button type="button" 
                  class="hidden md:flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shrink-0 mr-1.5" 
                  @click="scrollNav(-220)"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left text-sm"></i>
          </button>

          <!-- Deretan Tab Menu -->
          <div class="overflow-hidden flex-grow relative">
            <ul id="mainNavTabs" class="flex gap-1.5 overflow-x-auto scrollable-nav-tabs py-1 px-1 whitespace-nowrap select-none">
              <li v-for="menu in menuList" :key="menu.name">
                <a :href="menu.url" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all border border-transparent"
                   :class="activeMenu === menu.name ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'">
                  <i :class="menu.icon"></i>
                  <span>{{ menu.title }}</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- Tombol Geser Kanan -->
          <button type="button" 
                  class="hidden md:flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shrink-0 ml-1.5" 
                  @click="scrollNav(220)"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right text-sm"></i>
          </button>
        </div>
      </div>

      <!-- Page Dynamic Content Slot -->
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
      &copy; {{ new Date().getFullYear() }} SINTA-SaaS Enterprise — Multi-Tenant Multi-Schema PostgreSQL Platform
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const tenantName = computed(() => page.props.tenant?.nama_sekolah);

const activeMenu = ref('dashboard');

const menuList = [
  { name: 'dashboard', title: 'Dashboard', url: '/dashboard', icon: 'bi bi-grid-fill' },
  { name: 'siswa', title: 'Buku Induk Siswa', url: '/siswa/buku-induk', icon: 'bi bi-people-fill' },
  { name: 'ppdb', title: 'PPDB Calon Siswa', url: '/siswa/ppdb', icon: 'bi bi-person-plus-fill' },
  { name: 'akademik', title: 'Master Akademik', url: '/akademik/master', icon: 'bi bi-journal-text' },
  { name: 'penilaian', title: 'Penilaian Rapor', url: '/akademik/penilaian', icon: 'bi bi-award-fill' },
  { name: 'keuangan', title: 'Pos & Tagihan SPP', url: '/keuangan/tagihan', icon: 'bi bi-wallet2' },
  { name: 'kasir', title: 'Kasir Pembayaran', url: '/keuangan/kasir', icon: 'bi bi-cash-stack' },
  { name: 'bk', title: 'BK & Konseling', url: '/bk', icon: 'bi bi-heart-pulse-fill' },
  { name: 'pdss', title: 'PDSS SNBP', url: '/pdss', icon: 'bi bi-mortarboard-fill' },
  { name: 'perpustakaan', title: 'Perpustakaan DDC', url: '/perpustakaan', icon: 'bi bi-book-half' },
  { name: 'persuratan', title: 'Persuratan & Disposisi', url: '/persuratan', icon: 'bi bi-envelope-paper-fill' },
  { name: 'sarpras', title: 'Sarpras & QR Aset', url: '/sarpras', icon: 'bi bi-box-seam-fill' },
  { name: 'smk', title: 'SMK Mitra DUDI & PKL', url: '/smk', icon: 'bi bi-buildings-fill' },
  { name: 'tracer', title: 'Tracer Study Alumni', url: '/tracer', icon: 'bi bi-graph-up-arrow' },
  { name: 'absensi', title: 'Presensi QR/GPS', url: '/absensi', icon: 'bi bi-qr-code-scan' },
  { name: 'kepegawaian', title: 'Kepegawaian GTK', url: '/kepegawaian', icon: 'bi bi-person-badge-fill' },
  { name: 'cms', title: 'CMS & Pengumuman', url: '/cms', icon: 'bi bi-newspaper' },
];

const scrollNav = (offset) => {
  const el = document.getElementById('mainNavTabs');
  if (el) {
    el.scrollBy({ left: offset, behavior: 'smooth' });
  }
};

const logout = () => {
  router.post('/logout');
};
</script>
