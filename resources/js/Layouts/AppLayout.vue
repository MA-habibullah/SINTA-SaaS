<template>
  <Head :title="title ? `${title}` : ''">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=5">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=5">
    <link rel="icon" type="image/png" href="/favicon.png?v=5">
    <link rel="shortcut icon" type="image/png" href="/favicon.png?v=5">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=5">
  </Head>
  <div class="h-screen bg-slate-50 flex overflow-hidden">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div v-if="mobileSidebarOpen" 
         class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden backdrop-blur-xs transition-opacity"
         @click="mobileSidebarOpen = false"></div>

    <!-- 1. SINTA-SaaS Fixed Left Sidebar - Full Height Independent Scroll -->
    <aside :class="[
      'h-screen flex-shrink-0 bg-white border-r-2 border-slate-200 flex flex-col transition-all duration-300 z-50',
      'shadow-[2px_0_8px_rgba(15,23,42,0.06)]',
      isCollapsed ? 'w-[72px]' : 'w-[270px]',
      mobileSidebarOpen ? 'fixed inset-y-0 left-0 translate-x-0' : 'fixed lg:static -translate-x-full lg:translate-x-0'
    ]">
      <!-- Brand Logo Header -->
      <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100 shrink-0">
        <div class="flex items-center gap-3 overflow-hidden">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-md shrink-0">
            S
          </div>
          <div v-if="!isCollapsed" class="transition-opacity duration-200 overflow-hidden">
            <h1 class="font-extrabold text-slate-800 text-xs leading-tight tracking-tight uppercase">SISTEM INTI AKADEMIK</h1>
            <p class="text-[11px] text-slate-400 font-medium truncate max-w-[170px]">
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

      <!-- Navigation Menus List (100% Dynamic from Database core.menus) -->
      <!-- overflow-y-auto: sidebar bisa scroll vertikal jika menu lebih panjang dari layar -->
      <div class="flex-grow overflow-y-auto overscroll-contain px-3 py-4 space-y-4 select-none"
           style="scrollbar-width: thin; scrollbar-color: #e2e8f0 transparent;">
        <ul class="space-y-1">
          <li v-for="menu in databaseMenus" :key="menu.id">
            <!-- Single Item (Tanpa Anak / Direct Route) -->
            <a v-if="!menu.children || menu.children.length === 0" 
               :href="menu.url" 
               :class="[
                 'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group',
                 isUrlActive(menu.url)
                   ? 'bg-blue-50 text-blue-600 font-bold shadow-2xs border-l-4 border-blue-600 pl-2' 
                   : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
               ]"
               :title="isCollapsed ? menu.title : ''">
              <i :class="[menu.icon || 'bi bi-circle', 'text-base shrink-0', isUrlActive(menu.url) ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-600']"></i>
              <span v-if="!isCollapsed" class="truncate">{{ menu.title }}</span>
            </a>

            <!-- Parent Menu with Submenus (Dropdown Collapsible) -->
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
                  <i :class="[menu.icon || 'bi bi-folder', 'text-base shrink-0', isParentActive(menu) ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-600']"></i>
                  <span v-if="!isCollapsed" class="truncate">{{ menu.title }}</span>
                </div>
                <i v-if="!isCollapsed" 
                   :class="['bi bi-chevron-down text-[10px] transition-transform duration-200 text-slate-400', (expandedMenus[menu.id] || isParentActive(menu)) ? 'rotate-180 text-blue-600' : '']"></i>
              </button>

              <!-- Submenu Items List -->
              <ul v-if="!isCollapsed && (expandedMenus[menu.id] || isParentActive(menu))" 
                  class="mt-1 ml-4 pl-3 border-l-2 border-slate-200/80 space-y-1 py-1">
                <li v-for="sub in menu.children" :key="sub.id || sub.url">
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

      <!-- Sidebar Footer / User Quick Info -->
      <div class="p-3.5 border-t border-slate-200/80 bg-slate-50/60 shrink-0">
        <div v-if="!isCollapsed" class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2.5 overflow-hidden">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
              {{ (user?.nama_lengkap || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="truncate">
              <div class="text-xs font-bold text-slate-800 truncate leading-tight">{{ user?.nama_lengkap || 'Pengguna' }}</div>
              <div class="text-[10px] text-blue-600 font-extrabold uppercase tracking-wider truncate">{{ user?.role || 'Pengguna' }}</div>
            </div>
          </div>
          <button @click="logout" class="text-slate-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition shrink-0" title="Keluar">
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

    <!-- 2. Main Content Wrapper — Clean Single Unified Scroll -->
    <div class="flex-grow flex flex-col min-w-0 h-screen overflow-hidden">
      <!-- Topbar Header -->
      <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shrink-0 px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-2xs z-30">
        <div class="flex items-center gap-3">
          <!-- Mobile Hamburger Toggle -->
          <button @click="mobileSidebarOpen = true" 
                  class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition">
            <i class="bi bi-list text-xl"></i>
          </button>

          <!-- Active School / Tenant Indicator -->
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 py-1.5 px-3 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200/80 shadow-2xs">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
              <i class="bi bi-building text-emerald-600"></i>
              <span class="truncate max-w-[200px] sm:max-w-xs">{{ tenantName || 'Pusat Kendali SaaS (Global)' }}</span>
            </span>
          </div>
        </div>

        <!-- Right Topbar Controls -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2.5 pl-3 border-l border-slate-200">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
              {{ (user?.nama_lengkap || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="hidden sm:block text-right">
              <div class="text-xs font-bold text-slate-800 leading-tight">{{ user?.nama_lengkap || 'Administrator' }}</div>
              <div class="text-[10px] text-blue-600 font-extrabold uppercase tracking-wider">{{ user?.role || 'Staff' }}</div>
            </div>
          </div>
          <button @click="logout" 
                  class="px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition flex items-center gap-1.5 border border-red-200/60 shadow-2xs" title="Keluar dari Akun">
            <i class="bi bi-box-arrow-right"></i>
            <span class="hidden sm:inline">Keluar</span>
          </button>
        </div>
      </header>

      <!-- Main Body Container — Unified Scroll Area (Including Content and Footer) -->
      <main class="flex-grow overflow-y-auto p-4 sm:p-6 lg:p-8 flex flex-col justify-between"
            style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
        <div class="max-w-7xl w-full mx-auto space-y-6 flex-grow">
          <!-- Page Dynamic Slot Content -->
          <slot />
        </div>

        <!-- Standard Unified Footer -->
        <footer class="mt-12 py-6 text-center text-xs text-slate-400 border-t border-slate-200/70 shrink-0">
          &copy; {{ new Date().getFullYear() }} SISTEM INTI AKADEMIK — Multi-Tenant Multi-Schema PostgreSQL Platform
        </footer>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';

const props = defineProps({
  title: {
    type: String,
    default: ''
  }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const tenantName = computed(() => page.props.tenant?.nama_sekolah);

// 100% Dynamic Menus directly fetched from Database core.menus
const databaseMenus = computed(() => {
  return page.props.menus && page.props.menus.length > 0 ? page.props.menus : [];
});

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
  if (!menu.children || menu.children.length === 0) return isUrlActive(menu.url);
  return menu.children.some(child => isUrlActive(child.url));
};

const toggleSubmenu = (menuId) => {
  expandedMenus.value[menuId] = !expandedMenus.value[menuId];
};

const logout = () => {
  router.post('/logout');
};
</script>



