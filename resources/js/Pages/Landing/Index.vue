<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-500 selection:text-white relative overflow-hidden">

    <!-- AMBIENT BACKGROUND GLOW ENGINE -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1200px] h-[550px] bg-gradient-to-b from-blue-600/20 via-indigo-600/10 to-transparent blur-[150px] pointer-events-none -z-10"></div>
    <div class="absolute top-[25%] right-[-150px] w-[650px] h-[650px] bg-purple-600/10 blur-[170px] pointer-events-none -z-10"></div>
    <div class="absolute top-[50%] left-[-200px] w-[650px] h-[650px] bg-blue-600/10 blur-[170px] pointer-events-none -z-10"></div>
    <div class="absolute top-[75%] right-[-150px] w-[600px] h-[600px] bg-emerald-600/10 blur-[160px] pointer-events-none -z-10"></div>

    <!-- 1. NAVBAR HEADER (Ultra-Responsive Modern Glass Island) -->
    <header class="relative z-40 sticky top-0 w-full transition-all duration-300">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-3">
        <div class="h-16 px-4 sm:px-6 rounded-2xl sm:rounded-3xl bg-slate-950/85 backdrop-blur-xl border border-slate-800/80 shadow-2xl shadow-slate-950/50 flex items-center justify-between gap-2 sm:gap-4">
          
          <!-- Logo Brand (Left) -->
          <Link href="/" class="flex items-center gap-2.5 shrink-0 group">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-blue-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 group-hover:scale-105 transition transform duration-200 shrink-0">
              <i class="bi bi-mortarboard-fill text-base sm:text-lg"></i>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-lg sm:text-xl font-black text-white tracking-tight">SINTA</span>
              <span class="hidden md:inline-block px-2 py-0.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-extrabold tracking-wider">
                SaaS v2.0
              </span>
            </div>
          </Link>

          <!-- Desktop Center Navigation (Floating Pill Bar) -->
          <nav class="hidden xl:flex items-center gap-1 p-1 rounded-full bg-slate-900/60 border border-slate-800/80 backdrop-blur-md">
            <a v-for="(nav, nIdx) in navMenuItems" :key="nIdx" 
               :href="nav.cta_link || nav.content || '#'"
               :target="nav.content_json?.target || '_self'"
               class="px-3.5 py-1.5 rounded-full text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800/90 transition-all duration-150 flex items-center gap-1.5">
              <i :class="['bi', nav.icon_class || 'bi-circle-fill text-[7px]', 'text-blue-400 text-xs']"></i>
              <span>{{ nav.title }}</span>
            </a>
          </nav>

          <!-- Desktop & Tablet Auth Actions (Right) -->
          <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <Link href="/login" class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-900 border border-slate-800/60 hover:border-slate-700 transition">
              <i class="bi bi-box-arrow-in-right text-xs"></i>
              <span>Masuk Portal</span>
            </Link>

            <Link href="/daftar-sekolah" class="relative group overflow-hidden px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-extrabold shadow-md shadow-blue-600/30 transition-all duration-200 transform hover:-translate-y-0.5 flex items-center gap-1.5 sm:gap-2 whitespace-nowrap">
              <i class="bi bi-gift-fill text-yellow-300 text-xs"></i>
              <span>Free Trial 1 Bulan</span>
            </Link>

            <!-- Mobile & Tablet Hamburger Toggle Button (Below xl) -->
            <button type="button" 
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="xl:hidden w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-800 transition shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                    title="Menu Navigasi">
              <i :class="['bi text-lg transition-transform duration-200', mobileMenuOpen ? 'bi-x-lg rotate-90 text-red-400' : 'bi-list text-blue-400']"></i>
            </button>
          </div>

        </div>
      </div>
    </header>

    <!-- RESPONSIVE MOBILE / TABLET SLIDE-OVER DRAWER -->
    <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 xl:hidden flex justify-end">
      <!-- Backdrop Overlay -->
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" 
           @click="mobileMenuOpen = false"></div>

      <!-- Drawer Content -->
      <div class="relative w-full max-w-sm bg-slate-900 border-l border-slate-800 shadow-2xl p-6 flex flex-col justify-between h-full overflow-y-auto z-10 animate-in slide-in-from-right duration-200">
        <div>
          <!-- Drawer Header -->
          <div class="flex items-center justify-between pb-5 border-b border-slate-800">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md">
                <i class="bi bi-mortarboard-fill text-sm"></i>
              </div>
              <span class="font-black text-white text-lg tracking-tight">SINTA SaaS</span>
            </div>
            <button type="button" 
                    @click="mobileMenuOpen = false"
                    class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition">
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>

          <!-- Drawer Links -->
          <nav class="space-y-1.5 py-6">
            <a v-for="(nav, nIdx) in navMenuItems" :key="nIdx"
               :href="nav.cta_link || nav.content || '#'"
               :target="nav.content_json?.target || '_self'"
               @click="mobileMenuOpen = false"
               class="flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800/90 transition">
              <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400">
                  <i :class="['bi', nav.icon_class || 'bi-link-45deg']"></i>
                </div>
                <span>{{ nav.title }}</span>
              </div>
              <span v-if="nav.badge_text" class="px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 text-[9px] font-extrabold border border-blue-500/30">
                {{ nav.badge_text }}
              </span>
            </a>
          </nav>
        </div>

        <!-- Drawer Footer -->
        <div class="space-y-3 pt-6 border-t border-slate-800">
          <Link href="/daftar-sekolah" 
                @click="mobileMenuOpen = false"
                class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold text-xs text-center shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2">
            <i class="bi bi-gift-fill text-yellow-300"></i>
            <span>Daftar Free Trial 1 Bulan</span>
          </Link>

          <Link href="/login" 
                @click="mobileMenuOpen = false"
                class="w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-bold text-xs text-center border border-slate-700 transition flex items-center justify-center gap-2">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Masuk Portal Sekolah</span>
          </Link>

          <div class="text-center pt-2">
            <span class="text-[10px] text-slate-400 font-bold">&copy; 2026 SINTA Platform Tata Kelola Sekolah</span>
          </div>
        </div>

      </div>
    </div>

    <!-- 2. HERO SECTION WITH MODERN DASHBOARD MOCKUP PREVIEW -->
    <section class="relative z-10 pt-16 pb-20 lg:pt-24 lg:pb-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      <div class="text-center max-w-4xl mx-auto space-y-6">
        
        <!-- Hero Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-purple-500/10 border border-blue-500/30 text-blue-300 text-xs font-bold shadow-lg shadow-blue-500/10 backdrop-blur-md">
          <i class="bi bi-stars text-amber-400"></i>
          <span>{{ heroItem?.badge_text || 'Platform Tata Kelola Sekolah Multi-Tenant SaaS #1' }}</span>
        </div>

        <!-- Main Title -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.15]">
          {{ heroItem?.title || 'Transformasi Digital Manajemen Sekolah Terpadu & Terintegrasi' }}
        </h1>

        <!-- Subtitle -->
        <p class="text-base sm:text-lg text-slate-400 font-normal leading-relaxed max-w-3xl mx-auto">
          {{ heroItem?.subtitle || 'SINTA menghadirkan ekosistem tata kelola sekolah all-in-one: Rapor Kurikulum Merdeka, Buku Induk Siswa, Tagihan SPP Multi-Channel Midtrans, Presensi Geofencing GPS, hingga BK & Perpustakaan. Coba gratis 1 bulan!' }}
        </p>

        <!-- CTA Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-3">
          <Link href="/daftar-sekolah" 
                class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-700 hover:to-indigo-800 text-white font-extrabold text-sm shadow-xl shadow-blue-500/30 transition-all duration-200 transform hover:-translate-y-1 flex items-center justify-center gap-2.5">
            <i class="bi bi-rocket-takeoff-fill text-yellow-300 text-base"></i>
            <span>{{ heroItem?.cta_text || 'Daftarkan Sekolah (Free Trial 1 Bulan)' }}</span>
          </Link>
          <a href="#kalkulator" 
             class="w-full sm:w-auto px-7 py-4 rounded-2xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 hover:text-white font-bold text-sm border border-slate-700/80 transition flex items-center justify-center gap-2 shadow-sm">
            <i class="bi bi-calculator text-blue-400 text-base"></i>
            <span>Hitung Estimasi Anggaran RKAS</span>
          </a>
        </div>

        <!-- Trust Badges / Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 pt-6 max-w-3xl mx-auto">
          <div class="p-3.5 rounded-2xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-md text-center shadow-xs">
            <span class="block text-2xl font-black text-white tracking-tight">16 Modul</span>
            <span class="text-[11px] text-slate-400 font-medium">Terpadu & Siap Pakai</span>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-md text-center shadow-xs">
            <span class="block text-2xl font-black text-emerald-400 tracking-tight">1 Bulan</span>
            <span class="text-[11px] text-slate-400 font-medium">Free Trial Tanpa Syarat</span>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-md text-center shadow-xs">
            <span class="block text-2xl font-black text-blue-400 tracking-tight">Multi-Schema</span>
            <span class="text-[11px] text-slate-400 font-medium">Isolasi Data Terlindungi</span>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-md text-center shadow-xs">
            <span class="block text-2xl font-black text-amber-400 tracking-tight">100% Ready</span>
            <span class="text-[11px] text-slate-400 font-medium">Format RKAS & Dapodik</span>
          </div>
        </div>

      </div>
    </section>

    <!-- 3. SOCIAL PROOF & LOGO SEKOLAH MITRA -->
    <section class="relative z-10 py-12 border-y border-slate-800/80 bg-slate-900/40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-5">
        <p class="text-xs font-extrabold text-slate-400 uppercase tracking-widest">
          Dipercaya & Diterapkan oleh Berbagai Jenjang Satuan Pendidikan di Indonesia
        </p>
        <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-10 opacity-75 grayscale hover:grayscale-0 transition-all duration-300">
          <div class="flex items-center gap-2 text-slate-300 font-bold text-xs sm:text-sm px-3 py-1.5 rounded-xl bg-slate-800/50 border border-slate-700/50">
            <i class="bi bi-building text-blue-400"></i> SMA Negeri Unggulan
          </div>
          <div class="flex items-center gap-2 text-slate-300 font-bold text-xs sm:text-sm px-3 py-1.5 rounded-xl bg-slate-800/50 border border-slate-700/50">
            <i class="bi bi-tools text-indigo-400"></i> SMK Pusat Keunggulan
          </div>
          <div class="flex items-center gap-2 text-slate-300 font-bold text-xs sm:text-sm px-3 py-1.5 rounded-xl bg-slate-800/50 border border-slate-700/50">
            <i class="bi bi-bank text-emerald-400"></i> Madrasah Aliyah (MA/MTs)
          </div>
          <div class="flex items-center gap-2 text-slate-300 font-bold text-xs sm:text-sm px-3 py-1.5 rounded-xl bg-slate-800/50 border border-slate-700/50">
            <i class="bi bi-mortarboard text-purple-400"></i> Yayasan Pendidikan Islam
          </div>
          <div class="flex items-center gap-2 text-slate-300 font-bold text-xs sm:text-sm px-3 py-1.5 rounded-xl bg-slate-800/50 border border-slate-700/50">
            <i class="bi bi-star-fill text-amber-400"></i> SD / SMP Swasta Terakreditasi A
          </div>
        </div>
      </div>
    </section>

    <!-- 4. SEKSI 16 MODUL TERPADU (Interactive Category Tabs & Grid 4x4) -->
    <section id="fitur" class="relative z-10 py-24 bg-slate-900/30 border-b border-slate-800/70">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-12 space-y-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-bold shadow-xs">
            <i class="bi bi-grid-fill"></i> Arsitektur 16 Modul Komprehensif
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            16 Modul Terpadu untuk Seluruh Kebutuhan Sekolah
          </h2>
          <p class="text-sm text-slate-400 leading-relaxed">
            Hilangkan sistem yang terpisah-pisah. Seluruh data akademik, kesiswaan, keuangan, dan presensi terpusat dalam satu ekosistem PostgreSQL multi-tenant yang aman.
          </p>
        </div>

        <!-- Module Category Filter Tabs (Responsive Modern Wrap Pills) -->
        <div class="mb-12 flex flex-wrap items-center justify-center gap-2 sm:gap-2.5 max-w-5xl mx-auto px-2">
          <button v-for="tab in moduleCategoryTabs" :key="tab.id"
                  type="button" 
                  @click="activeModuleTab = tab.id"
                  :class="['px-4 py-2.5 rounded-2xl text-xs font-bold transition-all duration-200 flex items-center gap-2 cursor-pointer select-none',
                           activeModuleTab === tab.id 
                             ? 'bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-600 text-white shadow-lg shadow-blue-600/30 border border-blue-400/40 scale-102' 
                             : 'bg-slate-900/90 hover:bg-slate-800/90 text-slate-300 hover:text-white border border-slate-800 hover:border-slate-700 backdrop-blur-md shadow-xs']">
            <i :class="['bi', tab.icon, activeModuleTab === tab.id ? 'text-white' : 'text-blue-400']"></i>
            <span>{{ tab.name }}</span>
          </button>
        </div>

        <!-- 4x4 Responsive Grid Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div v-for="(feat, idx) in filteredFeatureItems" :key="idx" 
               class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800/90 hover:border-blue-500/50 hover:bg-slate-900 transition-all duration-300 group shadow-xl flex flex-col justify-between relative overflow-hidden">
            
            <!-- Subtle Top Glow on Hover -->
            <div class="absolute -top-10 -right-10 w-24 h-24 bg-blue-600/10 rounded-full blur-xl pointer-events-none group-hover:bg-blue-600/25 transition"></div>

            <div>
              <!-- Icon & Badge Header -->
              <div class="flex items-center justify-between gap-3 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-600/10 border border-blue-500/20 flex items-center justify-center text-blue-400 group-hover:bg-blue-600 group-hover:text-white transition transform group-hover:scale-105 duration-200">
                  <i :class="['bi', feat.icon_class || 'bi-grid-fill', 'text-xl']"></i>
                </div>
                <span class="px-2.5 py-0.5 rounded-md bg-blue-500/10 text-blue-400 text-[10px] font-extrabold uppercase tracking-wider border border-blue-500/20">
                  {{ feat.badge_text || 'Modul' }}
                </span>
              </div>

              <h3 class="text-base font-bold text-white mb-2 group-hover:text-blue-400 transition leading-snug">
                {{ feat.title }}
              </h3>
              <p class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                {{ feat.subtitle || feat.content }}
              </p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-800/60 flex items-center justify-between text-[11px] text-slate-500 group-hover:text-blue-400 transition">
              <span class="font-semibold">Fitur Terintegrasi</span>
              <i class="bi bi-arrow-right font-bold group-hover:translate-x-1 transition transform"></i>
            </div>

          </div>
        </div>

      </div>
    </section>

    <!-- 5. SEKSI KEUNTUNGAN APLIKASI (Benefits & Enterprise Security) -->
    <section id="keuntungan" class="relative z-10 py-24 border-b border-slate-800/80 bg-slate-950">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/30 text-purple-400 text-xs font-bold shadow-xs">
            <i class="bi bi-shield-check"></i> Mengapa Sekolah Memilih SINTA?
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Keunggulan & Keuntungan Arsitektur SINTA
          </h2>
          <p class="text-sm text-slate-400 leading-relaxed">
            Dirancang dengan standar enterprise untuk menjamin kecepatan operasional, privasi data tingkat tinggi, dan efisiensi anggaran sekolah.
          </p>
        </div>

        <!-- Cards Grid Keuntungan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="(benefit, bIdx) in benefitItems" :key="bIdx"
               class="p-7 rounded-3xl bg-slate-900/90 border border-slate-800/90 hover:border-slate-700 hover:bg-slate-900 transition-all duration-300 group shadow-xl flex flex-col justify-between relative overflow-hidden">
            
            <div class="absolute -top-12 -right-12 w-28 h-28 bg-purple-600/10 rounded-full blur-2xl pointer-events-none group-hover:bg-purple-600/20 transition"></div>

            <div>
              <div class="flex items-center justify-between gap-3 mb-5">
                <div class="w-13 h-13 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 group-hover:bg-purple-600 group-hover:text-white transition transform group-hover:scale-105 shadow-md">
                  <i :class="['bi', benefit.icon_class || 'bi-shield-check', 'text-2xl']"></i>
                </div>
                <span v-if="benefit.badge_text" class="px-2.5 py-1 rounded-full bg-purple-500/10 text-purple-300 text-[10px] font-extrabold uppercase tracking-wider border border-purple-500/20">
                  {{ benefit.badge_text }}
                </span>
              </div>

              <h3 class="text-lg font-black text-white mb-2 group-hover:text-purple-300 transition leading-snug">
                {{ benefit.title }}
              </h3>

              <p class="text-xs text-slate-400 leading-relaxed">
                {{ benefit.subtitle || benefit.content }}
              </p>

              <div v-if="benefit.content_json?.highlights && benefit.content_json.highlights.length > 0" 
                   class="mt-4 pt-4 border-t border-slate-800/80 space-y-2">
                <div v-for="(h, hIdx) in benefit.content_json.highlights" :key="hIdx" 
                     class="text-xs text-slate-300 flex items-center gap-2">
                  <i class="bi bi-check-circle-fill text-emerald-400 text-xs shrink-0"></i>
                  <span class="font-medium">{{ h }}</span>
                </div>
              </div>
            </div>

            <div v-if="benefit.cta_text && benefit.cta_link" class="mt-6 pt-4 border-t border-slate-800/60">
              <a :href="benefit.cta_link" class="text-xs font-bold text-purple-400 hover:text-purple-300 flex items-center gap-1.5 transition">
                <span>{{ benefit.cta_text }}</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>

          </div>
        </div>

      </div>
    </section>

    <!-- 6. INTEGRATED PRICING CALCULATOR & PACKAGES SECTION -->
    <section id="kalkulator" class="relative z-10 py-24 border-b border-slate-800/60 bg-gradient-to-b from-slate-950 via-slate-900/60 to-slate-950">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-14 space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
            <i class="bi bi-calculator-fill"></i> Estimator Anggaran Dinamis & Pilihan Paket
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Transparan, Terukur & Sesuai Anggaran RKAS Sekolah
          </h2>
          <p class="text-sm text-slate-400">
            Hitung estimasi biaya berdasarkan populasi siswa & guru, lalu bandingkan langsung dengan pilihan paket berlangganan.
          </p>

          <!-- Billing Cycle Switcher Toggle -->
          <div class="pt-4 flex items-center justify-center">
            <div class="p-1.5 rounded-2xl bg-slate-900 border border-slate-800 inline-flex flex-wrap items-center justify-center gap-1 shadow-inner">
              <button type="button" 
                      @click="billingCycle = 'annual'" 
                      :class="['px-4 sm:px-6 py-2.5 rounded-xl text-xs font-extrabold transition flex items-center gap-2',
                               billingCycle === 'annual' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white']">
                <span>Tahunan (Annual)</span>
                <span class="px-2 py-0.5 rounded-full bg-amber-400 text-slate-950 text-[10px] font-black">Hemat 25%</span>
              </button>

              <button type="button" 
                      @click="billingCycle = 'semester'" 
                      :class="['px-4 sm:px-5 py-2.5 rounded-xl text-xs font-extrabold transition flex items-center gap-2',
                               billingCycle === 'semester' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white']">
                <span>Semesteran (6 Bln)</span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[10px]">Hemat 10%</span>
              </button>

              <button type="button" 
                      @click="billingCycle = 'monthly'" 
                      :class="['px-4 sm:px-5 py-2.5 rounded-xl text-xs font-extrabold transition',
                               billingCycle === 'monthly' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white']">
                <span>Bulanan</span>
              </button>
            </div>
          </div>
        </div>

        <!-- TANDEM 1: CALCULATOR CARD CONTAINER -->
        <div class="max-w-5xl mx-auto rounded-3xl bg-slate-900/90 border border-slate-800 shadow-2xl p-6 sm:p-10 mb-16">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <!-- Sliders Inputs (7 cols) -->
            <div class="lg:col-span-7 space-y-6">
              
              <!-- Student Slider -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="text-xs font-bold text-slate-300 flex items-center gap-2">
                    <i class="bi bi-people-fill text-blue-400"></i> Jumlah Siswa
                  </label>
                  <span class="px-3 py-1 rounded-xl bg-blue-600/20 border border-blue-500/30 text-blue-300 text-xs font-black">
                    {{ studentCount }} Siswa
                  </span>
                </div>
                <input v-model.number="studentCount" type="range" min="30" max="2500" step="10" 
                       class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-blue-500" />
                <div class="flex justify-between text-[10px] text-slate-500 mt-1 font-semibold">
                  <span>30 Siswa</span>
                  <span>500 Siswa</span>
                  <span>1.000 Siswa</span>
                  <span>2.500+ Siswa</span>
                </div>
              </div>

              <!-- Teacher Slider -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="text-xs font-bold text-slate-300 flex items-center gap-2">
                    <i class="bi bi-person-badge-fill text-indigo-400"></i> Jumlah Guru & Tenaga Kependidikan
                  </label>
                  <span class="px-3 py-1 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-300 text-xs font-black">
                    {{ teacherCount }} Akun Guru
                  </span>
                </div>
                <input v-model.number="teacherCount" type="range" min="5" max="150" step="1" 
                       class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-500" />
                <div class="flex justify-between text-[10px] text-slate-500 mt-1 font-semibold">
                  <span>5 Guru</span>
                  <span>50 Guru</span>
                  <span>100 Guru</span>
                  <span>150 Guru</span>
                </div>
              </div>

              <!-- Tier Information Box -->
              <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 space-y-1.5">
                <div class="flex items-center justify-between text-[11px]">
                  <span>Tier Tarif Per Siswa:</span>
                  <span class="font-bold text-white">Rp {{ formatRupiah(studentTierRate) }} / siswa / bln</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                  <span>Kuota Guru Gratis Termasuk:</span>
                  <span class="font-bold text-emerald-400">{{ freeTeacherQuota }} Guru</span>
                </div>
                <div v-if="extraTeachers > 0" class="flex items-center justify-between text-[11px] text-amber-400">
                  <span>Tambahan Guru ({{ extraTeachers }} akun):</span>
                  <span class="font-bold">+Rp {{ formatRupiah(extraTeachers * 10000) }} / bln</span>
                </div>
              </div>

            </div>

            <!-- Price Output Box (5 cols) -->
            <div class="lg:col-span-5 p-6 rounded-3xl bg-gradient-to-b from-blue-900/40 via-indigo-950/40 to-slate-950 border border-blue-500/30 text-center space-y-4 shadow-xl">
              
              <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider">
                Estimasi Biaya Investasi
              </div>

              <div>
                <div class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                  Rp {{ formatRupiah(effectiveMonthlyPrice) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">
                  per bulan (disetarakan)
                </div>
                <div class="text-[11px] text-blue-400 font-bold mt-1">
                  Hanya ~Rp {{ formatRupiah(pricePerStudentMonthly) }} / siswa / bulan
                </div>
              </div>

              <div class="pt-2 pb-1 border-t border-slate-800 text-xs text-slate-400 space-y-1">
                <div class="flex justify-between">
                  <span>Total Tagihan Periode:</span>
                  <span class="font-extrabold text-white">Rp {{ formatRupiah(totalPeriodPrice) }}</span>
                </div>
                <div v-if="totalSavings > 0" class="flex justify-between text-emerald-400 font-bold">
                  <span>Hemat Periode Ini:</span>
                  <span>Rp {{ formatRupiah(totalSavings) }}</span>
                </div>
              </div>

              <div class="space-y-2 pt-2">
                <Link href="/daftar-sekolah" 
                      class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold text-xs shadow-lg shadow-blue-500/30 transition flex items-center justify-center gap-2">
                  <i class="bi bi-gift-fill text-yellow-300"></i>
                  <span>Daftar Free Trial 1 Bulan</span>
                </Link>

                <a :href="whatsappProposalUrl" target="_blank"
                   class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs border border-slate-700 transition flex items-center justify-center gap-2">
                  <i class="bi bi-whatsapp text-emerald-400"></i>
                  <span>Minta Dokumen Proposal (PDF)</span>
                </a>
              </div>

            </div>

          </div>
        </div>

        <!-- TANDEM 2: STANDARD PRICING PACKAGES CARDS -->
        <div id="paket" class="pt-4">
          <div class="text-center max-w-2xl mx-auto mb-10">
            <h3 class="text-2xl font-bold text-white">Atau Pilih Paket Berlangganan Tetap</h3>
            <p class="text-xs text-slate-400 mt-1">Seluruh paket mendapatkan masa uji coba gratis 1 bulan penuh tanpa biaya pendaftaran.</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch max-w-6xl mx-auto">
            <div v-for="(card, cIdx) in standardCards" :key="cIdx"
                 :class="['p-8 rounded-3xl flex flex-col justify-between transition-all duration-300 relative',
                          card.isPopular ? 'bg-slate-900/95 border-2 border-blue-500 shadow-2xl shadow-blue-500/20 ring-1 ring-blue-500/40' : 'bg-slate-900/60 border border-slate-800 hover:border-slate-700']">
              
              <!-- Radiant Badge on Most Popular -->
              <div v-if="card.isPopular" class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-600 text-white font-black text-[10px] tracking-wider uppercase shadow-lg shadow-blue-500/30">
                Paling Populer & Rekomendasi
              </div>

              <div>
                <div class="flex items-center justify-between mb-4">
                  <span :class="['px-3 py-1 rounded-full text-xs font-bold border', card.badgeClass]">
                    {{ card.badge }}
                  </span>
                </div>

                <h3 class="text-2xl font-black text-white mb-1">{{ card.name }}</h3>
                <p class="text-xs text-slate-400 mb-6">{{ card.subtitle }}</p>

                <div class="mb-6 pb-6 border-b border-slate-800">
                  <div v-if="card.price !== 'Kustom'" class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-white">Rp {{ formatRupiah(card.price) }}</span>
                    <span class="text-xs text-slate-400">/ bulan</span>
                  </div>
                  <div v-else class="text-3xl font-black text-white">
                    Kustom / MoU
                  </div>
                </div>

                <ul class="space-y-3 mb-8">
                  <li v-for="(feat, fIdx) in card.features" :key="fIdx" class="text-xs text-slate-300 flex items-start gap-2.5">
                    <i class="bi bi-check-circle-fill text-emerald-400 text-sm shrink-0 mt-0.5"></i>
                    <span>{{ feat }}</span>
                  </li>
                </ul>
              </div>

              <Link :href="card.ctaLink" 
                    :class="['w-full py-3.5 px-4 rounded-xl text-xs font-extrabold text-center transition flex items-center justify-center gap-2', card.btnClass]">
                <span>{{ card.ctaText }}</span>
              </Link>

            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- 7. SOCIAL PROOF & TESTIMONI KEPALA SEKOLAH -->
    <section class="relative z-10 py-24 border-b border-slate-800/80 bg-slate-900/40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold shadow-xs">
            <i class="bi bi-chat-quote-fill"></i> Cerita Sukses Sekolah Mitra
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Apa Kata Mereka Tentang Platform SINTA?
          </h2>
          <p class="text-sm text-slate-400 leading-relaxed">
            Pengalaman nyata para pimpinan sekolah dan tenaga pendidik setelah mendigitalkan manajemen sekolah bersama SINTA.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="(testi, tIdx) in testimonialItems" :key="tIdx"
               class="p-7 rounded-3xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition duration-300 flex flex-col justify-between shadow-xl">
            
            <div>
              <!-- 5 Stars Rating -->
              <div class="flex items-center gap-1 text-amber-400 text-sm mb-4">
                <i v-for="s in 5" :key="s" class="bi bi-star-fill"></i>
              </div>

              <!-- Quote Text -->
              <p class="text-xs text-slate-300 leading-relaxed italic mb-6">
                "{{ testi.content || testi.subtitle }}"
              </p>
            </div>

            <!-- Profile Info -->
            <div class="flex items-center gap-3 pt-4 border-t border-slate-800">
              <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-black text-xs shrink-0 shadow-md">
                {{ testi.content_json?.avatar || (testi.title ? testi.title.slice(0, 2).toUpperCase() : 'ST') }}
              </div>
              <div class="min-w-0">
                <h4 class="text-xs font-black text-white truncate">{{ testi.title }}</h4>
                <p class="text-[11px] text-slate-400 truncate">{{ testi.subtitle || testi.badge_text }}</p>
                <p v-if="testi.content_json?.school_name" class="text-[10px] text-blue-400 font-semibold truncate">{{ testi.content_json.school_name }} ({{ testi.content_json.city }})</p>
              </div>
            </div>

          </div>
        </div>

      </div>
    </section>

    <!-- 8. FAQ SECTION (Interactive Accordion) -->
    <section id="faq" class="relative z-10 py-24 border-b border-slate-800/80 bg-slate-950">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold">
            <i class="bi bi-question-circle"></i> Tanya Jawab
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Pertanyaan yang Sering Diajukan (FAQ)
          </h2>
          <p class="text-sm text-slate-400">
            Segala hal yang perlu Anda ketahui mengenai pendaftaran, uji coba, dan implementasi SINTA di sekolah Anda.
          </p>
        </div>

        <div class="space-y-3.5">
          <div v-for="(faq, fIdx) in faqItems" :key="fIdx" 
               class="rounded-2xl bg-slate-900/70 border border-slate-800 overflow-hidden transition-all duration-200">
            <button type="button" 
                    @click="toggleFaq(fIdx)"
                    class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-blue-400 transition">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-question-circle text-blue-400 shrink-0"></i>
                <span>{{ faq.title }}</span>
              </span>
              <i :class="['bi transition-transform duration-200', openFaqIndex === fIdx ? 'bi-chevron-up text-blue-400' : 'bi-chevron-down text-slate-500']"></i>
            </button>
            <div v-if="openFaqIndex === fIdx" class="px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3">
              {{ faq.subtitle || faq.content }}
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- 9. FINAL HIGH-CONVERSION CTA BANNER -->
    <section class="relative z-10 py-24 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden p-10 sm:p-14 rounded-3xl bg-gradient-to-tr from-blue-900/80 via-indigo-900/70 to-slate-900 border border-blue-500/40 text-center space-y-6 shadow-2xl">
          
          <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-yellow-400/10 border border-yellow-400/30 text-yellow-300 text-xs font-bold">
            <i class="bi bi-gift-fill"></i> Free Trial 1 Bulan Tanpa Komitmen
          </div>

          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Siap Mewujudkan Digitalisasi Sekolah Anda Hari Ini?
          </h2>
          <p class="text-sm text-slate-300 max-w-2xl mx-auto leading-relaxed">
            Daftarkan sekolah Anda dalam 2 menit. Super Admin akan memverifikasi dan memberikan akses penuh 16 modul selama 1 bulan secara gratis tanpa perlu kartu kredit.
          </p>

          <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link href="/daftar-sekolah" 
                  class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl bg-white text-blue-900 hover:bg-slate-100 font-black text-sm shadow-xl transition transform hover:scale-105">
              <i class="bi bi-gift-fill text-blue-600"></i>
              <span>{{ heroItem?.cta_text || 'Mulai Free Trial 1 Bulan Sekarang' }}</span>
            </Link>
            <a :href="whatsappProposalUrl" target="_blank"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-slate-800/80 hover:bg-slate-800 text-slate-200 hover:text-white font-bold text-sm border border-slate-700 transition">
              <i class="bi bi-whatsapp text-emerald-400"></i>
              <span>Konsultasi via WhatsApp</span>
            </a>
          </div>

          <div class="flex items-center justify-center gap-6 pt-3 text-[11px] text-slate-400 font-medium">
            <span><i class="bi bi-check2 text-emerald-400 font-bold"></i> Tanpa Kartu Kredit</span>
            <span><i class="bi bi-check2 text-emerald-400 font-bold"></i> Setup Instan 2 Menit</span>
            <span><i class="bi bi-check2 text-emerald-400 font-bold"></i> Pendampingan Onboarding</span>
          </div>

        </div>
      </div>
    </section>

    <!-- 10. ENTERPRISE FOOTER -->
    <footer class="relative z-10 border-t border-slate-800/80 bg-slate-950 py-12 text-xs text-slate-500">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-md">
              S
            </div>
            <div>
              <span class="font-black text-white text-sm tracking-tight block">SINTA SaaS Platform</span>
              <span class="text-[11px] text-slate-500">Sistem Informasi Manajemen Tata Kelola Sekolah Terpadu</span>
            </div>
          </div>

          <div class="flex items-center gap-6 flex-wrap justify-center text-xs">
            <a v-for="(nav, nIdx) in navMenuItems" :key="nIdx" 
               :href="nav.cta_link || nav.content || '#'"
               class="hover:text-slate-300 transition">
              {{ nav.title }}
            </a>
            <Link href="/login" class="hover:text-slate-300 transition font-bold">Login Portal</Link>
            <Link href="/daftar-sekolah" class="text-blue-400 hover:text-blue-300 font-bold transition">Daftar Free Trial</Link>
          </div>
        </div>

        <div class="pt-6 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-slate-600">
          <span>&copy; 2026 SINTA Platform. Hak Cipta Dilindungi Undang-Undang.</span>
          <span>Arsitektur PostgreSQL 16 Multi-Tenant &bull; OWASP ASVS L3 Compliance</span>
        </div>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  promotions: {
    type: Object,
    default: () => ({}),
  },
  tenantsCount: {
    type: Number,
    default: 1,
  },
});

// Mobile Sidebar Drawer State
const mobileMenuOpen = ref(false);

// Active Module Category Tab in 16 Modules Section
const activeModuleTab = ref('all');

const moduleCategoryTabs = [
  { id: 'all',         name: 'Semua Modul (16)',         icon: 'bi-grid-fill' },
  { id: 'akademik',    name: 'Akademik & Rapor (4)',      icon: 'bi-journal-check' },
  { id: 'kesiswaan',   name: 'Kesiswaan & BK (3)',        icon: 'bi-people-fill' },
  { id: 'keuangan',    name: 'Keuangan SPP (1)',          icon: 'bi-wallet2' },
  { id: 'presensi',    name: 'Presensi GPS & KBM (2)',    icon: 'bi-geo-alt-fill' },
  { id: 'sarpras',     name: 'Sarpras & Perpus (2)',      icon: 'bi-building' },
  { id: 'kepegawaian', name: 'GTK & Administrasi (4)',    icon: 'bi-person-vcard' },
];

// Interactive FAQ Accordion State
const openFaqIndex = ref(0);

const toggleFaq = (idx) => {
  openFaqIndex.value = openFaqIndex.value === idx ? null : idx;
};

// Billing Cycle State: 'annual' (default - 25% discount), 'semester' (10% discount), 'monthly' (0% discount)
const billingCycle = ref('annual');

// Interactive Calculator State
const studentCount = ref(350);
const teacherCount = ref(25);

// Tiering Formula
const studentTierRate = computed(() => {
  const s = studentCount.value || 0;
  if (s <= 150) return 5000;
  if (s <= 500) return 4000;
  if (s <= 1000) return 3000;
  return 2500;
});

// Free teacher quota: 1 teacher per 20 students
const freeTeacherQuota = computed(() => {
  const s = studentCount.value || 0;
  return Math.max(1, Math.floor(s / 20));
});

// Extra billable teachers
const extraTeachers = computed(() => {
  const t = teacherCount.value || 0;
  return Math.max(0, t - freeTeacherQuota.value);
});

// Base Monthly Normal Price
const rawMonthlyPrice = computed(() => {
  const s = studentCount.value || 0;
  const studentTotal = s * studentTierRate.value;
  const teacherExtraTotal = extraTeachers.value * 10000;
  return studentTotal + teacherExtraTotal;
});

// Discount rate based on billing cycle
const discountRate = computed(() => {
  if (billingCycle.value === 'annual') return 0.25;
  if (billingCycle.value === 'semester') return 0.10;
  return 0;
});

// Effective Monthly Price after discount
const effectiveMonthlyPrice = computed(() => {
  return Math.round(rawMonthlyPrice.value * (1 - discountRate.value));
});

// Total Bill for Period
const totalPeriodPrice = computed(() => {
  if (billingCycle.value === 'annual') return effectiveMonthlyPrice.value * 12;
  if (billingCycle.value === 'semester') return effectiveMonthlyPrice.value * 6;
  return effectiveMonthlyPrice.value;
});

// Price per student per month
const pricePerStudentMonthly = computed(() => {
  const s = studentCount.value || 1;
  return Math.round(effectiveMonthlyPrice.value / s);
});

// Total Savings for Period
const totalSavings = computed(() => {
  const normalPeriod = billingCycle.value === 'annual' ? rawMonthlyPrice.value * 12 : (billingCycle.value === 'semester' ? rawMonthlyPrice.value * 6 : rawMonthlyPrice.value);
  return normalPeriod - totalPeriodPrice.value;
});

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID').format(val || 0);
};

// WhatsApp Proposal link with pre-filled message
const whatsappProposalUrl = computed(() => {
  const cycleName = billingCycle.value === 'annual' ? 'Tahunan (Diskon 25%)' : (billingCycle.value === 'semester' ? 'Semesteran (Diskon 10%)' : 'Bulanan');
  const msg = `Halo Tim Sales SINTA, saya ingin mengajukan Dokumen Proposal & Penawaran Resmi SINTA untuk sekolah kami:\n\n- Jumlah Siswa: ${studentCount.value} Siswa\n- Jumlah Guru: ${teacherCount.value} Guru\n- Estimasi Sistem: Rp ${formatRupiah(effectiveMonthlyPrice.value)} / bulan (Siklus: ${cycleName})\n- Estimasi Biaya/Siswa: Rp ${formatRupiah(pricePerStudentMonthly.value)} / siswa / bulan\n\nMohon dibantu pembuatan Dokumen Proposal (PDF) dan jadwal demo singkat. Terima kasih!`;
  return `https://wa.me/6281234567890?text=${encodeURIComponent(msg)}`;
});

// 3 Standard Pricing Cards reactive prices based on billing cycle
const standardCards = computed(() => {
  const disc = discountRate.value;
  
  // Starter: base ~ Rp 550.000 / mo
  const starterRaw = 550000;
  const starterEff = Math.round(starterRaw * (1 - disc));
  
  // Pro: base ~ Rp 1.650.000 / mo
  const proRaw = 1650000;
  const proEff = Math.round(proRaw * (1 - disc));

  return [
    {
      name: 'Paket Starter',
      subtitle: 'Sekolah Rintisan / Kecil (< 200 Siswa)',
      badge: 'Hemat & Praktis',
      badgeClass: 'bg-slate-800 text-slate-300 border-slate-700',
      isPopular: false,
      price: starterEff,
      rawPrice: starterRaw,
      features: [
        'Kapasitas hingga 200 Siswa',
        'Kuota hingga 15 Akun Guru & Staf',
        'Modul Data Pokok & Buku Induk Siswa',
        'Cetak Rapor Kurikulum Merdeka & K13 PDF',
        'Layanan Presensi & Absensi GTK',
        'Subdomain Resmi Sekolah (.sinta.id)',
        'Dukungan Bantuan Chat WhatsApp'
      ],
      ctaText: 'Coba Gratis 1 Bulan Starter',
      ctaLink: '/daftar-sekolah?paket=starter',
      btnClass: 'bg-slate-800 hover:bg-slate-700 text-white border border-slate-700'
    },
    {
      name: 'Paket Pro Sekolah',
      subtitle: 'Sekolah Menengah (200 – 800 Siswa)',
      badge: 'Paling Populer & Rekomendasi',
      badgeClass: 'bg-blue-600 text-white shadow-md shadow-blue-500/30',
      isPopular: true,
      price: proEff,
      rawPrice: proRaw,
      features: [
        'Kapasitas hingga 800 Siswa',
        'Kuota hingga 50 Akun Guru & Staf',
        'Akses Penuh Seluruh 16 Modul Terpadu',
        'Tagihan SPP Online Multi-Channel & Kasir',
        'Integrasi Payment Gateway Otomatis',
        'Modul Lengkap BK, PDSS & Perpustakaan OPAC',
        'Sesi Training Guru & Staf via Zoom (Gratis)',
        'Prioritas Pendampingan & Migrasi Data Excel'
      ],
      ctaText: '🔥 Coba Gratis 1 Bulan Pro',
      ctaLink: '/daftar-sekolah?paket=pro',
      btnClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black shadow-lg shadow-blue-500/30'
    },
    {
      name: 'Paket Enterprise',
      subtitle: 'Sekolah Besar (> 800 Siswa) & Yayasan',
      badge: 'Multi-Sekolah / Kompleks',
      badgeClass: 'bg-purple-600 text-white shadow-md shadow-purple-500/30',
      isPopular: false,
      price: 'Kustom',
      rawPrice: null,
      features: [
        'Unlimited Siswa, Guru & Tenaga Pendidik',
        'Multi-Unit Sekolah dalam 1 Portal Yayasan',
        'Dedicated Server PostgreSQL & Custom Subdomain',
        'Integrasi Mesin Presensi Fingerprint / RFID',
        'On-Premise / Hybrid Cloud Deployment Option',
        'Dedicated Technical Account Manager',
        'Perjanjian Layanan Tingkat Tinggi (SLA 99.9%)'
      ],
      ctaText: 'Hubungi Tim Penjualan / Kustom',
      ctaLink: 'https://wa.me/6281234567890?text=Halo%20Tim%20SINTA,%20kami%20tertarik%20dengan%20Paket%20Enterprise%20Yayasan%20Sekolah',
      btnClass: 'bg-slate-800 hover:bg-slate-700 text-white border border-slate-700'
    }
  ];
});

// Dynamic Navbar from CMS
const navMenuItems = computed(() => {
  if (props.promotions?.nav_menu && props.promotions.nav_menu.length > 0) {
    return props.promotions.nav_menu;
  }
  return [
    { title: 'Fitur 16 Modul', cta_link: '#fitur', icon_class: 'bi bi-grid-fill', badge_text: null },
    { title: 'Keuntungan Aplikasi', cta_link: '#keuntungan', icon_class: 'bi bi-shield-check', badge_text: 'Keunggulan' },
    { title: 'Simulasi Biaya', cta_link: '#kalkulator', icon_class: 'bi bi-calculator-fill', badge_text: null },
    { title: 'Paket & Harga', cta_link: '#paket', icon_class: 'bi bi-box-seam-fill', badge_text: null },
    { title: 'FAQ', cta_link: '#faq', icon_class: 'bi bi-question-circle', badge_text: null },
  ];
});

const heroItem = computed(() => {
  return props.promotions?.hero?.[0] || null;
});

// Dynamic Benefits from CMS
const benefitItems = computed(() => {
  if (props.promotions?.benefits && props.promotions.benefits.length > 0) {
    return props.promotions.benefits;
  }
  return [
    {
      title: 'Isolasi Skema Database Multi-Tenant',
      subtitle: 'Privasi data sekolah terjamin 100%. Setiap sekolah memiliki skema PostgreSQL mandiri yang terisolasi total.',
      badge_text: 'Keamanan Data',
      icon_class: 'bi-shield-lock-fill',
      content_json: { highlights: ['Dedicated Schema PostgreSQL 16', 'Zero Cross-Tenant Leakage', 'Enkripsi AES-256 HMAC'] }
    },
    {
      title: 'Otomasi Rapor Kurikulum Merdeka & K13',
      subtitle: 'Hitung capaian pembelajaran otomatis, generate deskripsi nilai dinamis, dan cetak lembar rapor PDF resmi dalam hitungan detik.',
      badge_text: 'Kurikulum & Rapor',
      icon_class: 'bi-award-fill',
      content_json: { highlights: ['Format Resmi Kemendikbud', 'Cetak Massal PDF Multi-Page', 'Ekspor Buku Ledger Excel'] }
    },
    {
      title: 'Kasir SPP Kilat & Multi-Channel Payment',
      subtitle: 'Terima pembayaran SPP melalui Kasir POS cepat atau transfer online otomatis via QRIS & Virtual Account Midtrans.',
      badge_text: 'Keuangan Terpadu',
      icon_class: 'bi-wallet2',
      content_json: { highlights: ['Kasir POS Struk Thermal', 'Midtrans QRIS & VA Otomatis', 'Auto Notifikasi WhatsApp'] }
    },
    {
      title: 'Presensi Siswa & GTK Berbasis GPS Geofencing',
      subtitle: 'Presensi mandiri presisi tinggi dengan validasi radius lokasi sekolah, proteksi Anti-Fake GPS, dan jurnal mengajar KBM.',
      badge_text: 'Presensi Anti-Fraud',
      icon_class: 'bi-geo-alt-fill',
      content_json: { highlights: ['Geofencing Radius Presisi', 'Anti-Fake GPS & Mock Shield', 'Auto Compress Bukti < 500 KB'] }
    },
    {
      title: 'Hemat Biaya Infrastruktur & Bebas Pemeliharaan',
      subtitle: 'Sekolah tidak perlu membeli server mahal. Semua sistem dan backup dikelola otomatis di cloud SINTA.',
      badge_text: 'Efisiensi Anggaran',
      icon_class: 'bi-cloud-check-fill',
      content_json: { highlights: ['100% Cloud Managed', 'Auto Backup Harian', 'Akses Cepat Semua Perangkat'] }
    },
    {
      title: 'Keamanan Standar OWASP & Zero Data Leakage',
      subtitle: 'Arsitektur Zero-SSR Client Hydration memastikan data sensitif seperti NIK dan nilai tidak bocor pada View Source browser.',
      badge_text: 'Enterprise Security',
      icon_class: 'bi-cpu-fill',
      content_json: { highlights: ['Zero SSR Data Exposure', 'OWASP ASVS L3 Compliance', 'Memory Security Sanitizer'] }
    },
  ];
});

// Dynamic Features from CMS
const rawFeatures = computed(() => {
  return props.promotions?.features || [];
});

const filteredFeatureItems = computed(() => {
  const all = rawFeatures.value.length > 0 ? rawFeatures.value : [
    { title: 'Buku Induk & Data Siswa', subtitle: 'Pencatatan NISN, biodata terpadu, mutasi, dan cetak lembar buku induk resmi.', icon_class: 'bi-journal-text', badge_text: 'Akademik', content_json: { category: 'akademik' } },
    { title: 'Rapor Kurikulum Merdeka & K13', subtitle: 'Kalkulasi nilai otomatis, deskripsi dinamis, cetak PDF massal & ledger Excel.', icon_class: 'bi-award-fill', badge_text: 'Kurikulum', content_json: { category: 'akademik' } },
    { title: 'Jadwal Pelajaran Anti-Bentrok', subtitle: 'Matriks jadwal kelas & guru, deteksi konflik ruangan, dan ekspor/impor Excel.', icon_class: 'bi-calendar3', badge_text: 'Akademik', content_json: { category: 'akademik' } },
    { title: 'Keuangan & Kasir SPP POS', subtitle: 'Billing tagihan massal, kasir kilat nota thermal, Midtrans QRIS & notifikasi WA.', icon_class: 'bi-wallet2', badge_text: 'Keuangan', content_json: { category: 'keuangan' } },
    { title: 'Presensi Geofencing GPS', subtitle: 'Presensi mandiri radius akurat, proteksi Fake GPS, dan kompresi surat izin < 500 KB.', icon_class: 'bi-geo-alt-fill', badge_text: 'Presensi', content_json: { category: 'presensi' } },
    { title: 'Jurnal Mengajar Guru (KBM)', subtitle: 'Auto-fill jadwal harian, input CP, absensi KBM per jam & upload foto dokumentasi.', icon_class: 'bi-book-half', badge_text: 'Presensi', content_json: { category: 'presensi' } },
    { title: 'PPDB Online & Seleksi Masuk', subtitle: 'Pendaftaran calon siswa mandiri, verifikasi berkas online & jalur zonasi/prestasi.', icon_class: 'bi-person-plus-fill', badge_text: 'Kesiswaan', content_json: { category: 'kesiswaan' } },
    { title: 'Bimbingan Konseling & Disiplin', subtitle: 'Layanan konseling siswa, buku saku poin pelanggaran dan surat panggilan ortu.', icon_class: 'bi-shield-check', badge_text: 'Kesiswaan', content_json: { category: 'kesiswaan' } },
    { title: 'Kesiswaan & Ekstrakurikuler', subtitle: 'Registrasi anggota ekskul, jurnal kegiatan pembina, absensi & predikat nilai.', icon_class: 'bi-trophy-fill', badge_text: 'Kesiswaan', content_json: { category: 'kesiswaan' } },
    { title: 'Perpustakaan Standar INLISLite', subtitle: 'Sirkulasi barcode peminjaman kilat, katalog OPAC, kiosk buku tamu & kartu anggota.', icon_class: 'bi-book-fill', badge_text: 'Sarpras', content_json: { category: 'sarpras' } },
    { title: 'Sarpras & KIR Aset Ruangan', subtitle: 'Barcode QR aset, cetak Kartu Inventaris Ruangan (KIR), stok BHP & peminjaman lab.', icon_class: 'bi-building-fill-check', badge_text: 'Sarpras', content_json: { category: 'sarpras' } },
    { title: 'Kepegawaian & GTK', subtitle: 'Data pokok NIP/NUPTK guru, riwayat pangkat/KGB, sertifikasi & e-recruitment.', icon_class: 'bi-person-vcard-fill', badge_text: 'Kepegawaian', content_json: { category: 'kepegawaian' } },
    { title: 'Vokasi SMK & Prakerin PKL', subtitle: 'Database mitra industri DUDI, plotting siswa magang, jurnal PKL & rubrik UKK LSP.', icon_class: 'bi-tools', badge_text: 'Kejuruan', content_json: { category: 'kepegawaian' } },
    { title: 'Persuratan & E-Disposisi', subtitle: 'Nomor agenda surat otomatis, arsip PDF terenkripsi, dan alur disposisi pimpinan.', icon_class: 'bi-envelope-paper-fill', badge_text: 'Administrasi', content_json: { category: 'kepegawaian' } },
    { title: 'PDSS & Peluang Kampus SNBP', subtitle: 'Pemeringkatan siswa eligible, kalkulasi rerata rapor & analisis alumni di PTN.', icon_class: 'bi-mortarboard-fill', badge_text: 'Alumni', content_json: { category: 'akademik' } },
    { title: 'CMS Website Portal Sekolah', subtitle: 'Publikasi pengumuman, agenda kalender akademik, galeri dan landing page sekolah.', icon_class: 'bi-globe2', badge_text: 'Portal Publik', content_json: { category: 'kepegawaian' } },
  ];

  if (activeModuleTab.value === 'all') {
    return all;
  }
  return all.filter(f => f.content_json?.category === activeModuleTab.value);
});

// Dynamic Testimonials from CMS
const testimonialItems = computed(() => {
  if (props.promotions?.testimonials && props.promotions.testimonials.length > 0) {
    return props.promotions.testimonials;
  }
  return [
    {
      title: 'Drs. H. Ahmad Fauzi, M.Pd.',
      subtitle: 'Kepala Sekolah',
      content: 'Penerapan SINTA memangkas waktu cetak rapor dari 2 minggu menjadi hanya 1 hari. Pengelolaan SPP dan presensi GPS sangat transparan dan memudahkan koordinasi dengan wali murid.',
      badge_text: 'SMAN 1 Teladan',
      content_json: { school_name: 'SMAN 1 Teladan', avatar: 'AF', city: 'Surabaya' }
    },
    {
      title: 'Siti Nurhaliza, S.Kom., M.T.',
      subtitle: 'Waka Kurikulum',
      content: 'Fitur Jurnal Mengajar dan Modul PKL Mitra Industri sangat luar biasa. Guru-guru merasa sangat terbantu karena presensi KBM dan CP otomatis terhubung ke jadwal harian.',
      badge_text: 'SMK Mitra Vokasi',
      content_json: { school_name: 'SMK Mitra Vokasi', avatar: 'SN', city: 'Bandung' }
    },
    {
      title: 'Dra. Hj. Wahyuni Rahayu',
      subtitle: 'Pengurus Yayasan',
      content: 'Sistem Multi-Tenant SINTA memungkinkan yayasan kami memantau 4 unit sekolah (SD, SMP, SMA, SMK) dalam satu dashboard terpadu. Tagihan SPP Midtrans langsung cair otomatis.',
      badge_text: 'Yayasan Al-Hikmah',
      content_json: { school_name: 'Yayasan Pendidikan Al-Hikmah', avatar: 'WR', city: 'Jakarta' }
    },
  ];
});

// Dynamic FAQ from CMS
const faqItems = computed(() => {
  if (props.promotions?.faq && props.promotions.faq.length > 0) {
    return props.promotions.faq;
  }
  return [
    { title: 'Bagaimana cara mendapatkan Free Trial 1 Bulan?', subtitle: 'Cukup isi formulir di menu Daftar Sekolah. Super Admin akan memverifikasi permohonan dalam waktu 1x24 jam dan mengaktifkan akses Anda.' },
    { title: 'Apakah data sekolah aman dan terisolasi?', subtitle: 'Sangat aman. Setiap sekolah memiliki isolasi skema PostgreSQL dan hak akses terenkripsi multi-tenant.' },
    { title: 'Apakah SINTA mendukung cetak Rapor Kurikulum Merdeka & K13?', subtitle: 'Ya, SINTA mendukung penuh perhitungan capaian pembelajaran (CP), deskripsi otomatis, cetak massal 1 rombel format PDF berstandar Kemendikbudristek, dan ekspor ledger nilai Excel.' },
    { title: 'Bagaimana integrasi pembayaran SPP siswa?', subtitle: 'SINTA menyediakan Kasir POS kilat dengan cetak struk thermal 58mm/80mm serta pembayaran online otomatis via QRIS & Virtual Account Midtrans dengan notifikasi WhatsApp.' },
  ];
});
</script>