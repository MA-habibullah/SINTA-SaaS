<template>
  <div class="min-h-screen bg-slate-50 flex">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div v-if="mobileSidebarOpen" 
         class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden backdrop-blur-xs transition-opacity"
         @click="mobileSidebarOpen = false"></div>

    <!-- 1. Authentic SINTA-SaaS Fixed Left Sidebar with Multi-Level Submenus -->
    <aside :class="[
      'fixed inset-y-0 left-0 z-50 bg-white border-r border-slate-200/80 flex flex-col transition-all duration-300 shadow-sm lg:static lg:translate-x-0',
      isCollapsed ? 'w-[72px]' : 'w-[270px]',
      mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]">
      <!-- Brand Logo Header -->
      <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100 shrink-0">
        <div class="flex items-center gap-3 overflow-hidden">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-md shrink-0">
            S
          </div>
          <div v-if="!isCollapsed" class="transition-opacity duration-200">
            <h1 class="font-extrabold text-slate-800 text-base leading-tight tracking-tight">SINTA-SaaS</h1>
            <p class="text-[11px] text-slate-400 font-medium truncate max-w-[150px]">
              {{ tenantName || 'Sistem Sekolah' }}
            </p>
          </div>
        </div>

        <!-- Desktop Collapse Toggle Button -->
        <button v-if="!isCollapsed" @click="isCollapsed = !isCollapsed" 
                class="hidden lg:flex w-7 h-7 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 items-center justify-center transition" 
                title="Kecilkan Sidebar">
          <i class="bi bi-layout-sidebar-inset text-sm"></i>
        </button>
      </div>

      <!-- Expand Button when Collapsed on Desktop -->
      <div v-if="isCollapsed" class="hidden lg:flex justify-center py-2 border-b border-slate-100">
        <button @click="isCollapsed = false" 
                class="w-8 h-8 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition" 
                title="Buka Sidebar">
          <i class="bi bi-chevron-double-right text-xs"></i>
        </button>
      </div>

      <!-- Navigation Menus List -->
      <div class="flex-grow overflow-y-auto px-3 py-4 space-y-5 select-none scrollbar-thin">
        <div v-for="(group, gIdx) in menuStructure" :key="gIdx" class="space-y-1">
          <!-- Group Title Header -->
          <div v-if="!isCollapsed" class="px-3 text-[10px] font-extrabold text-slate-400 tracking-wider uppercase">
            {{ group.groupName }}
          </div>
          <div v-else class="h-1 w-6 bg-slate-200 mx-auto my-2 rounded-full"></div>

          <!-- Menu Items in Group -->
          <ul class="space-y-0.5">
            <li v-for="menu in group.menus" :key="menu.id">
              <!-- Single Item (No Children) -->
              <a v-if="!menu.children || menu.children.length === 0" 
                 :href="menu.url" 
                 :class="[
                   'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group',
                   isUrlActive(menu.url)
                     ? 'bg-blue-50 text-blue-600 font-bold shadow-2xs border-l-4 border-blue-600 pl-2' 
                     : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
                 ]"
                 :title="isCollapsed ? menu.title : ''">
                <i :class="[menu.icon, 'text-base shrink-0', isUrlActive(menu.url) ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-600']"></i>
                <span v-if="!isCollapsed" class="truncate">{{ menu.title }}</span>
              </a>

              <!-- Parent Menu with Submenus (Dropdown) -->
              <div v-else>
                <button type="button" 
                        @click="toggleSubmenu(menu.id)" 
                        :class="[
                          'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group',
                          isParentActive(menu)
                            ? 'bg-slate-100/80 text-blue-600 font-bold' 
                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
                        ]"
                        :title="isCollapsed ? menu.title : ''">
                  <div class="flex items-center gap-3 truncate">
                    <i :class="[menu.icon, 'text-base shrink-0', isParentActive(menu) ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-600']"></i>
                    <span v-if="!isCollapsed" class="truncate">{{ menu.title }}</span>
                  </div>
                  <i v-if="!isCollapsed" 
                     :class="['bi bi-chevron-down text-[10px] transition-transform duration-200 text-slate-400', expandedMenus[menu.id] ? 'rotate-180 text-blue-600' : '']"></i>
                </button>

                <!-- Submenu Items List -->
                <ul v-if="!isCollapsed && (expandedMenus[menu.id] || isParentActive(menu))" 
                    class="mt-1 ml-4 pl-3 border-l-2 border-slate-200/80 space-y-1 py-1">
                  <li v-for="sub in menu.children" :key="sub.url">
                    <a :href="sub.url" 
                       :class="[
                         'flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs transition-all',
                         isUrlActive(sub.url)
                           ? 'text-blue-600 font-bold bg-blue-50/80' 
                           : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/60'
                       ]">
                      <i :class="[sub.icon || 'bi bi-dot', 'text-sm shrink-0', isUrlActive(sub.url) ? 'text-blue-600' : 'text-slate-400']"></i>
                      <span class="truncate">{{ sub.title }}</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Sidebar Footer / User Quick Info -->
      <div class="p-3 border-t border-slate-100 bg-slate-50/50 shrink-0">
        <div v-if="!isCollapsed" class="flex items-center justify-between">
          <div class="flex items-center gap-2.5 overflow-hidden">
            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
              {{ (user?.nama_lengkap || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="truncate">
              <div class="text-xs font-bold text-slate-800 truncate">{{ user?.nama_lengkap || 'Pengguna' }}</div>
              <div class="text-[10px] text-slate-400 capitalize truncate">{{ user?.role || 'Pengguna' }}</div>
            </div>
          </div>
          <button @click="logout" class="text-slate-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition" title="Keluar">
            <i class="bi bi-box-arrow-right text-sm"></i>
          </button>
        </div>
        <div v-else class="flex justify-center">
          <button @click="logout" class="text-slate-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition" title="Keluar">
            <i class="bi bi-box-arrow-right text-sm"></i>
          </button>
        </div>
      </div>
    </aside>

    <!-- 2. Main Content Wrapper -->
    <div class="flex-grow flex flex-col min-w-0">
      <!-- Topbar Header -->
      <header class="h-16 bg-white border-b border-slate-200/80 sticky top-0 z-30 px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-3">
          <!-- Mobile Hamburger Toggle -->
          <button @click="mobileSidebarOpen = true" 
                  class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition">
            <i class="bi bi-list text-xl"></i>
          </button>

          <!-- Breadcrumb / Active School Indicator -->
          <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200/60">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              {{ tenantName || 'Tenant Sekolah Terhubung' }}
            </span>
          </div>
        </div>

        <!-- Right Topbar Controls -->
        <div class="flex items-center gap-3">
          <!-- User Dropdown Details -->
          <div class="flex items-center gap-3 pl-3 border-l border-slate-200">
            <div class="hidden sm:block text-right">
              <div class="text-xs font-bold text-slate-800">{{ user?.nama_lengkap || 'Administrator' }}</div>
              <div class="text-[10px] text-blue-600 font-semibold uppercase">{{ user?.role || 'Staff' }}</div>
            </div>
            <button @click="logout" 
                    class="px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition flex items-center gap-1.5">
              <i class="bi bi-box-arrow-right"></i>
              <span class="hidden sm:inline">Keluar</span>
            </button>
          </div>
        </div>
      </header>

      <!-- Main Body Container with 3-Way Scroller NavTabs -->
      <main class="flex-grow p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
        <!-- Horizontal NavTabs 3-Way Scroller (Standard SINTA SaaS) -->
        <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
          <div class="flex items-center relative">
            <!-- 1 Tombol Panah Kiri -->
            <button type="button" 
                    class="hidden md:flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shrink-0 mr-1.5 shadow-2xs z-5" 
                    @click="scrollNavTabs(-220)"
                    title="Geser ke Kiri">
              <i class="bi bi-chevron-left text-xs"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="grow overflow-hidden relative">
              <ul id="sintaSubNavTabs" class="flex gap-1.5 overflow-x-auto scrollable-nav-tabs py-0.5 px-1 whitespace-nowrap select-none no-scrollbar">
                <li v-for="menu in flatMenuList" :key="menu.url">
                  <a :href="menu.url" 
                     :class="[
                       'inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold transition border border-transparent',
                       isUrlActive(menu.url)
                         ? 'bg-blue-600 text-white shadow-xs font-bold' 
                         : 'text-slate-600 hover:bg-slate-100'
                     ]">
                    <i :class="menu.icon"></i>
                    <span>{{ menu.title }}</span>
                  </a>
                </li>
              </ul>
            </div>

            <!-- 1 Tombol Panah Kanan -->
            <button type="button" 
                    class="hidden md:flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shrink-0 ml-1.5 shadow-2xs z-5" 
                    @click="scrollNavTabs(220)"
                    title="Geser ke Kanan">
              <i class="bi bi-chevron-right text-xs"></i>
            </button>
          </div>
        </div>

        <!-- Page Dynamic Slot Content -->
        <slot />
      </main>

      <!-- Standard SINTA Footer -->
      <footer class="bg-white border-t border-slate-200/80 py-4 px-6 text-center text-xs text-slate-400">
        &copy; {{ new Date().getFullYear() }} SINTA-SaaS Enterprise — Multi-Tenant Multi-Schema PostgreSQL Platform
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const tenantName = computed(() => page.props.tenant?.nama_sekolah);

const isCollapsed = ref(false);
const mobileSidebarOpen = ref(false);
const expandedMenus = ref({});

const isUrlActive = (url) => {
  const current = page.url;
  if (!url || url === '#') return false;
  if (url === '/dashboard') return current === '/dashboard' || current === '/';
  return current.startsWith(url);
};

const isParentActive = (menu) => {
  if (!menu.children) return isUrlActive(menu.url);
  return menu.children.some(child => isUrlActive(child.url));
};

const toggleSubmenu = (menuId) => {
  expandedMenus.value[menuId] = !expandedMenus.value[menuId];
};

// SINTA-SaaS Canonical Grouped Menus & Submenus Hierarchy (Database Aligned)
const menuStructure = [
  {
    groupName: 'Utama',
    menus: [
      { id: 'm_dashboard', title: 'Dashboard', url: '/dashboard', icon: 'bi bi-grid-fill' },
    ]
  },
  {
    groupName: 'Data Pokok & Siswa',
    menus: [
      {
        id: 'm_siswa',
        title: 'Buku Induk Siswa',
        icon: 'bi bi-people-fill',
        children: [
          { title: 'Buku Induk Lengkap', url: '/siswa/buku-induk', icon: 'bi bi-person-lines-fill' },
          { title: 'Mutasi & Registrasi', url: '/siswa/mutasi', icon: 'bi bi-arrow-left-right' },
          { title: 'Prestasi Siswa', url: '/siswa/prestasi', icon: 'bi bi-trophy-fill' },
        ]
      },
      {
        id: 'm_ppdb',
        title: 'Penerimaan Siswa (PPDB)',
        icon: 'bi bi-person-plus-fill',
        children: [
          { title: 'Pendaftar & SPMB', url: '/siswa/ppdb', icon: 'bi bi-person-badge' },
        ]
      },
    ]
  },
  {
    groupName: 'Akademik & Penilaian',
    menus: [
      {
        id: 'm_akademik',
        title: 'Kurikulum & Rombel',
        icon: 'bi bi-journal-text',
        children: [
          { title: 'Master Akademik', url: '/akademik/master', icon: 'bi bi-database-fill' },
          { title: 'Penilaian Rapor', url: '/akademik/penilaian', icon: 'bi bi-award-fill' },
          { title: 'Presensi QR & GPS', url: '/absensi', icon: 'bi bi-qr-code-scan' },
        ]
      },
    ]
  },
  {
    groupName: 'Keuangan & SPP',
    menus: [
      {
        id: 'm_keuangan',
        title: 'Keuangan & Pembayaran',
        icon: 'bi bi-wallet2',
        children: [
          { title: 'Pos & Tagihan SPP', url: '/keuangan/tagihan', icon: 'bi bi-receipt' },
          { title: 'Kasir & Loket', url: '/keuangan/kasir', icon: 'bi bi-cash-coin' },
          { title: 'Laporan Keuangan', url: '/keuangan/laporan', icon: 'bi bi-file-earmark-bar-graph' },
        ]
      },
    ]
  },
  {
    groupName: 'Layanan Khusus',
    menus: [
      {
        id: 'm_bk',
        title: 'Bimbingan Konseling (BK)',
        icon: 'bi bi-heart-pulse-fill',
        children: [
          { title: 'Catatan Konseling', url: '/bk', icon: 'bi bi-chat-heart' },
          { title: 'Kesiapan PDSS SNBP', url: '/pdss', icon: 'bi bi-mortarboard-fill' },
          { title: 'Tracer Study Alumni', url: '/tracer', icon: 'bi bi-graph-up-arrow' },
        ]
      },
      {
        id: 'm_perpus',
        title: 'Perpustakaan Digital',
        icon: 'bi bi-book-half',
        children: [
          { title: 'Katalog & Sirkulasi', url: '/perpustakaan', icon: 'bi bi-journal-bookmark' },
        ]
      },
      {
        id: 'm_persuratan',
        title: 'Persuratan & Tata Usaha',
        icon: 'bi bi-envelope-paper-fill',
        children: [
          { title: 'Surat Masuk & Keluar', url: '/persuratan', icon: 'bi bi-inbox-fill' },
        ]
      },
      {
        id: 'm_sarpras',
        title: 'Sarpras & SMK',
        icon: 'bi bi-box-seam-fill',
        children: [
          { title: 'Inventaris & QR Aset', url: '/sarpras', icon: 'bi bi-qr-code' },
          { title: 'SMK Mitra DUDI & PKL', url: '/smk', icon: 'bi bi-buildings-fill' },
        ]
      },
    ]
  },
  {
    groupName: 'Sistem & Pengaturan',
    menus: [
      { id: 'm_gtk', title: 'Kepegawaian GTK', url: '/kepegawaian', icon: 'bi bi-person-badge-fill' },
      { id: 'm_cms', title: 'CMS & Pengumuman', url: '/cms', icon: 'bi bi-newspaper' },
      { id: 'm_audit', title: 'Log Aktivitas & Audit', url: '/sistem/activity-logs', icon: 'bi bi-shield-check' },
    ]
  }
];

const flatMenuList = computed(() => {
  const list = [];
  menuStructure.forEach(group => {
    group.menus.forEach(menu => {
      if (menu.children && menu.children.length > 0) {
        menu.children.forEach(sub => list.push(sub));
      } else {
        list.push(menu);
      }
    });
  });
  return list;
});

const scrollNavTabs = (offset) => {
  const el = document.getElementById('sintaSubNavTabs');
  if (el) {
    el.scrollBy({ left: offset, behavior: 'smooth' });
  }
};

const logout = () => {
  router.post('/logout');
};
</script>


