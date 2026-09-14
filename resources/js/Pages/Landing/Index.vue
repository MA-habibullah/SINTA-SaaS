<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-500 selection:text-white relative overflow-hidden">

    <!-- BACKGROUND GLOW EFFECTS -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[450px] bg-gradient-to-b from-blue-600/20 via-indigo-600/10 to-transparent blur-[140px] pointer-events-none -z-10"></div>
    <div class="absolute top-[40%] right-[-200px] w-[600px] h-[600px] bg-purple-600/10 blur-[160px] pointer-events-none -z-10"></div>
    <div class="absolute top-[75%] left-[-200px] w-[600px] h-[600px] bg-blue-600/10 blur-[160px] pointer-events-none -z-10"></div>

    <!-- NAVBAR HEADER -->
    <header class="relative z-30 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-lg sticky top-0">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between gap-4">
        
        <!-- Logo Brand (Shrink 0) -->
        <Link href="/" class="flex items-center gap-3 shrink-0 group">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/20 group-hover:scale-105 transition transform">
            <i class="bi bi-mortarboard-fill text-lg"></i>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xl font-black text-white tracking-tight">SINTA</span>
          </div>
        </Link>

        <!-- Desktop Navigation Links (Visible on lg and above) -->
        <nav class="hidden lg:flex items-center gap-6 xl:gap-8 text-xs font-bold text-slate-300 shrink-0">
          <a href="#fitur" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-900 transition flex items-center gap-1.5">
            <i class="bi bi-grid-fill text-blue-400"></i>
            <span>Fitur Unggulan</span>
          </a>
          <a href="#kalkulator" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-900 transition flex items-center gap-1.5">
            <i class="bi bi-calculator-fill text-emerald-400"></i>
            <span>Simulasi Biaya</span>
          </a>
          <a href="#paket" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-900 transition flex items-center gap-1.5">
            <i class="bi bi-box-seam-fill text-indigo-400"></i>
            <span>Paket & Harga</span>
          </a>
          <a href="#faq" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-900 transition flex items-center gap-1.5">
            <i class="bi bi-question-circle text-amber-400"></i>
            <span>FAQ</span>
          </a>
        </nav>

        <!-- Desktop & Tablet Auth Actions -->
        <div class="hidden sm:flex items-center gap-2.5 shrink-0">
          <Link href="/login" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-900 transition flex items-center gap-1.5">
            <i class="bi bi-box-arrow-in-right text-sm"></i>
            <span>Masuk</span>
          </Link>
          <Link href="/daftar-sekolah" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-extrabold shadow-md shadow-blue-600/30 transition transform hover:-translate-y-0.5 flex items-center gap-2 whitespace-nowrap">
            <i class="bi bi-gift-fill text-yellow-300"></i>
            <span>Coba Gratis 1 Bulan</span>
          </Link>
        </div>

        <!-- Mobile & Tablet Hamburger Toggle Button -->
        <div class="flex items-center gap-2 lg:hidden">
          <Link href="/login" class="sm:hidden px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-900">
            Masuk
          </Link>
          <button type="button" 
                  @click="mobileMenuOpen = !mobileMenuOpen"
                  class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-800 transition shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                  title="Buka Navigasi">
            <i :class="['bi text-lg transition-transform duration-200', mobileMenuOpen ? 'bi-x-lg rotate-90 text-red-400' : 'bi-list text-blue-400']"></i>
          </button>
        </div>

      </div>
    </header>

    <!-- RESPONSIVE MOBILE SIDEBAR DRAWER -->
    <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 lg:hidden flex justify-end">
      <!-- Backdrop Overlay -->
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" 
           @click="mobileMenuOpen = false"></div>

      <!-- Slide-in Drawer Container -->
      <div class="relative w-full max-w-xs bg-slate-900 border-l border-slate-800 shadow-2xl p-6 flex flex-col justify-between h-full overflow-y-auto z-10 transition-transform">
        
        <div>
          <!-- Drawer Header -->
          <div class="flex items-center justify-between pb-5 border-b border-slate-800">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md">
                <i class="bi bi-mortarboard-fill text-sm"></i>
              </div>
              <span class="font-black text-white text-lg tracking-tight">SINTA</span>
            </div>
            <button type="button" 
                    @click="mobileMenuOpen = false"
                    class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition">
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>

          <!-- Drawer Navigation Links -->
          <nav class="space-y-1.5 py-6">
            <a href="#fitur" 
               @click="mobileMenuOpen = false"
               class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800/80 transition">
              <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400">
                <i class="bi bi-grid-fill"></i>
              </div>
              <span>Fitur Unggulan</span>
            </a>

            <a href="#kalkulator" 
               @click="mobileMenuOpen = false"
               class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800/80 transition">
              <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                <i class="bi bi-calculator-fill"></i>
              </div>
              <span>Simulasi Biaya & RKAS</span>
            </a>

            <a href="#paket" 
               @click="mobileMenuOpen = false"
               class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800/80 transition">
              <div class="w-7 h-7 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                <i class="bi bi-box-seam-fill"></i>
              </div>
              <span>Pilihan Paket & Harga</span>
            </a>

            <a href="#faq" 
               @click="mobileMenuOpen = false"
               class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800/80 transition">
              <div class="w-7 h-7 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-400">
                <i class="bi bi-question-circle"></i>
              </div>
              <span>Tanya Jawab (FAQ)</span>
            </a>
          </nav>
        </div>

        <!-- Drawer Footer Actions -->
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
            <span>Login Portal Sekolah</span>
          </Link>

          <div class="text-center pt-2">
            <span class="text-[10px] text-slate-400 font-bold">&copy; 2026 SINTA Platform Tata Kelola Sekolah</span>
          </div>
        </div>

      </div>
    </div>

    <!-- HERO SECTION -->
    <section class="relative z-10 pt-16 pb-20 lg:pt-24 lg:pb-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      <div class="text-center max-w-4xl mx-auto space-y-6">
        
        <!-- Hero Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-blue-500/10 to-indigo-500/10 border border-blue-500/30 text-blue-400 text-xs font-bold shadow-xs">
          <i class="bi bi-stars text-amber-400"></i>
          <span>{{ heroItem?.badge_text || 'Platform Manajemen Sekolah Multi-Tenant #1' }}</span>
        </div>

        <!-- Main Title -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.15]">
          {{ heroItem?.title || 'Transformasi Digital Manajemen Sekolah Terpadu & Terintegrasi' }}
        </h1>

        <!-- Subtitle -->
        <p class="text-base sm:text-lg text-slate-400 font-normal leading-relaxed max-w-3xl mx-auto">
          {{ heroItem?.subtitle || 'SINTA menghadirkan ekosistem tata kelola sekolah all-in-one: Rapor Kurikulum Merdeka, Buku Induk, Tagihan SPP Multi-Channel, PPDB, hingga BK & Presensi. Nikmati masa uji coba gratis 1 bulan penuh!' }}
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
          <Link href="/daftar-sekolah" 
                class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-700 hover:to-indigo-800 text-white font-extrabold text-sm shadow-xl shadow-blue-500/30 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-2.5">
            <i class="bi bi-rocket-takeoff-fill text-yellow-300 text-base"></i>
            <span>{{ heroItem?.cta_text || 'Daftarkan Sekolah Anda (Free Trial 1 Bulan)' }}</span>
          </Link>
          <a href="#kalkulator" 
             class="w-full sm:w-auto px-7 py-4 rounded-2xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 hover:text-white font-bold text-sm border border-slate-700/80 transition flex items-center justify-center gap-2">
            <i class="bi bi-calculator text-blue-400 text-base"></i>
            <span>Hitung Simulasi Anggaran RKAS</span>
          </a>
        </div>

        <!-- Trust Badges / Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-10 max-w-3xl mx-auto">
          <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs text-center">
            <span class="block text-2xl font-black text-white tracking-tight">16</span>
            <span class="text-xs text-slate-400 font-medium">Modul Terintegrasi</span>
          </div>
          <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs text-center">
            <span class="block text-2xl font-black text-emerald-400 tracking-tight">1 Bulan</span>
            <span class="text-xs text-slate-400 font-medium">Free Trial Bebas Biaya</span>
          </div>
          <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs text-center">
            <span class="block text-2xl font-black text-blue-400 tracking-tight">Multi-Schema</span>
            <span class="text-xs text-slate-400 font-medium">Isolasi Data Terlindungi</span>
          </div>
          <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs text-center">
            <span class="block text-2xl font-black text-amber-400 tracking-tight">100%</span>
            <span class="text-xs text-slate-400 font-medium">Format RKAS/RAPBS Ready</span>
          </div>
        </div>

      </div>
    </section>

    <!-- FITUR UNGGULAN SECTION -->
    <section id="fitur" class="relative z-10 py-20 border-t border-slate-800/60 bg-slate-900/20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-bold">
            <i class="bi bi-grid-fill"></i> Arsitektur Modul Komprehensif
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            16 Modul Terpadu untuk Seluruh Kebutuhan Sekolah
          </h2>
          <p class="text-sm text-slate-400">
            Hilangkan sistem yang terpisah-pisah. Seluruh data akademik, kesiswaan, keuangan, dan presensi terpusat dalam satu database.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="(feat, idx) in featureItems" :key="idx" 
               class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 hover:bg-slate-900 transition-all group shadow-xl">
            <div class="w-12 h-12 rounded-2xl bg-blue-600/10 border border-blue-500/20 flex items-center justify-center text-blue-400 group-hover:bg-blue-600 group-hover:text-white transition transform group-hover:scale-110 mb-5">
              <i :class="['bi', feat.icon_class || 'bi-check-circle-fill', 'text-xl']"></i>
            </div>
            <div class="flex items-center gap-2 mb-2">
              <span class="px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-400 text-[10px] font-extrabold uppercase tracking-wider">
                {{ feat.badge_text || 'Modul Inti' }}
              </span>
            </div>
            <h3 class="text-lg font-bold text-white mb-2 group-hover:text-blue-400 transition">
              {{ feat.title }}
            </h3>
            <p class="text-xs text-slate-400 leading-relaxed">
              {{ feat.subtitle || feat.content }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- INTERACTIVE PRICING CALCULATOR SECTION -->
    <section id="kalkulator" class="relative z-10 py-24 border-t border-slate-800/60 bg-gradient-to-b from-slate-950 via-slate-900/50 to-slate-950">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-14 space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
            <i class="bi bi-calculator-fill"></i> Estimator Anggaran Dinamis (RKAS Friendly)
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Hitung Kebutuhan Investasi Sesuai Jumlah Siswa & Guru
          </h2>
          <p class="text-sm text-slate-400">
            Transparan tanpa biaya tersembunyi. Semakin besar populasi sekolah Anda, biaya per siswa semakin terjangkau (*Volume Discount*).
          </p>

          <!-- BILLING CYCLE TOGGLE -->
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

        <!-- CALCULATOR CARD CONTAINER -->
        <div class="max-w-4xl mx-auto rounded-3xl bg-slate-900/90 border border-slate-800 shadow-2xl p-6 sm:p-10">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <!-- LEFT: SLIDERS & CONFIG -->
            <div class="lg:col-span-7 space-y-8">
              
              <!-- Slider Siswa -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold text-slate-300 flex items-center gap-2">
                    <i class="bi bi-people-fill text-blue-400"></i>
                    <span>Jumlah Siswa Aktif</span>
                  </label>
                  <div class="flex items-center gap-2">
                    <input type="number" 
                           v-model.number="studentCount" 
                           min="20" max="3000" step="10"
                           class="w-24 h-9 bg-slate-800 border border-slate-700 rounded-xl px-2.5 text-right font-black text-sm text-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <span class="text-xs font-semibold text-slate-400">Siswa</span>
                  </div>
                </div>

                <input type="range" 
                       v-model.number="studentCount" 
                       min="20" max="2000" step="10"
                       class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-blue-600" />

                <div class="flex items-center justify-between text-[10px] text-slate-500 font-semibold">
                  <span>20 Siswa</span>
                  <span>500 Siswa</span>
                  <span>1.000 Siswa</span>
                  <span>2.000+ Siswa</span>
                </div>
              </div>

              <!-- Slider Guru & Staf -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-xs font-bold text-slate-300 flex items-center gap-2">
                      <i class="bi bi-person-workspace text-indigo-400"></i>
                      <span>Jumlah Guru & Tenaga Kependidikan</span>
                    </label>
                    <span class="text-[11px] text-slate-400">
                      (Termasuk kuota gratis: <strong class="text-emerald-400">{{ freeTeacherQuota }} Guru</strong>)
                    </span>
                  </div>
                  <div class="flex items-center gap-2">
                    <input type="number" 
                           v-model.number="teacherCount" 
                           min="1" max="250" step="1"
                           class="w-20 h-9 bg-slate-800 border border-slate-700 rounded-xl px-2.5 text-right font-black text-sm text-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    <span class="text-xs font-semibold text-slate-400">Guru</span>
                  </div>
                </div>

                <input type="range" 
                       v-model.number="teacherCount" 
                       min="1" max="150" step="1"
                       class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-600" />

                <div class="flex items-center justify-between text-[10px] text-slate-500 font-semibold">
                  <span>1 Guru</span>
                  <span>50 Guru</span>
                  <span>100 Guru</span>
                  <span>150+ Guru</span>
                </div>
              </div>

              <!-- Breakdown Note -->
              <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-xs space-y-2">
                <div class="flex items-center justify-between text-slate-400">
                  <span>Tarif Volume Siswa (Tier {{ (studentCount || 0) <= 150 ? '1' : ((studentCount || 0) <= 500 ? '2' : ((studentCount || 0) <= 1000 ? '3' : '4')) }}):</span>
                  <span class="font-bold text-slate-200">Rp {{ formatRupiah(studentTierRate) }} / siswa / bln</span>
                </div>
                <div class="flex items-center justify-between text-slate-400">
                  <span>Rasio Guru Gratis (1 per 20 siswa):</span>
                  <span class="font-bold text-emerald-400">{{ freeTeacherQuota }} Guru Bebas Biaya</span>
                </div>
                <div v-if="extraTeachers > 0" class="flex items-center justify-between text-slate-400">
                  <span>Add-on Guru Ekstra ({{ extraTeachers }} Guru &times; Rp 10.000):</span>
                  <span class="font-bold text-amber-400">+Rp {{ formatRupiah(extraTeachers * 10000) }} / bln</span>
                </div>
              </div>

            </div>

            <!-- RIGHT: SUMMARY CARD -->
            <div class="lg:col-span-5 p-6 sm:p-7 rounded-3xl bg-gradient-to-b from-blue-950/70 via-slate-950 to-slate-950 border-2 border-blue-500/40 shadow-xl flex flex-col justify-between space-y-6">
              
              <div>
                <div class="flex items-center justify-between gap-2 mb-2">
                  <span class="text-[11px] font-extrabold text-blue-400 uppercase tracking-wider">Estimasi Investasi Sekolah</span>
                  <span v-if="discountRate > 0" class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[10px] font-black">
                    Diskon {{ Math.round(discountRate * 100) }}% Aktif
                  </span>
                </div>

                <div class="my-4">
                  <div class="flex items-baseline gap-1.5">
                    <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">Rp {{ formatRupiah(effectiveMonthlyPrice) }}</span>
                    <span class="text-xs text-slate-400 font-semibold">/ bulan</span>
                  </div>
                  <p class="text-xs text-slate-400 mt-1">
                    Tagihan per periode: <strong class="text-slate-200">Rp {{ formatRupiah(totalPeriodPrice) }}</strong> 
                    <span class="text-slate-400">({{ billingCycle === 'annual' ? 'Per 1 Tahun' : (billingCycle === 'semester' ? 'Per 6 Bulan' : 'Per Bulan') }})</span>
                  </p>
                </div>

                <!-- Per Student Impact Indicator -->
                <div class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 text-xs space-y-1 mb-2">
                  <div class="flex items-center justify-between">
                    <span class="text-slate-400">Beban per Siswa:</span>
                    <span class="font-black text-emerald-400 text-sm">Rp {{ formatRupiah(pricePerStudentMonthly) }} / anak / bln</span>
                  </div>
                  <p class="text-[10px] text-slate-400 leading-tight">
                    *Sangat efisien dibebankan pada komponen iuran SPP atau anggaran operasional BOS sekolah.
                  </p>
                </div>

                <div v-if="totalSavings > 0" class="text-center">
                  <span class="text-[11px] font-bold text-amber-400">
                    🎉 Sekolah Anda Menghemat Rp {{ formatRupiah(totalSavings) }} pada siklus ini!
                  </span>
                </div>
              </div>

              <!-- ACTION BUTTONS -->
              <div class="space-y-2.5 pt-2">
                <Link href="/daftar-sekolah" 
                      class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold text-xs text-center shadow-lg shadow-blue-500/25 transition flex items-center justify-center gap-2">
                  <i class="bi bi-rocket-takeoff-fill text-yellow-300"></i>
                  <span>Mulai Free Trial 1 Bulan</span>
                </Link>

                <a :href="whatsappProposalUrl" target="_blank" 
                   class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white font-bold text-xs text-center border border-slate-700/80 transition flex items-center justify-center gap-2">
                  <i class="bi bi-file-earmark-pdf-fill text-red-400"></i>
                  <span>Minta Proposal Resmi via WhatsApp</span>
                </a>
              </div>

            </div>

          </div>
        </div>

      </div>
    </section>

    <!-- 3 CARDS PRICING SECTION -->
    <section id="paket" class="relative z-10 py-24 border-t border-slate-800/60 bg-slate-900/30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-bold">
            <i class="bi bi-box-seam-fill"></i> Pilihan Paket Standar
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Pilihan Paket Terpadu Sesuai Skala Sekolah Anda
          </h2>
          <p class="text-sm text-slate-400">
            Seluruh paket mencakup hak akses multi-peran (Kepala Sekolah, Guru, Staf TU, Siswa, & Orang Tua) dan update sistem gratis selamanya.
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
          
          <div v-for="(card, cIdx) in standardCards" :key="cIdx" 
               :class="['p-8 rounded-3xl transition-all duration-200 flex flex-col justify-between relative',
                        card.isPopular ? 'bg-gradient-to-b from-blue-950/80 via-slate-900 to-slate-950 border-2 border-blue-500/80 shadow-2xl shadow-blue-500/20 transform lg:-translate-y-2' :
                                         'bg-slate-900/90 border border-slate-800 hover:border-slate-700 shadow-xl']">
            
            <!-- POPULAR BADGE -->
            <div v-if="card.isPopular" class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-black text-[10px] uppercase tracking-wider shadow-md">
              Paling Direkomendasikan
            </div>

            <div>
              <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-xs font-bold text-blue-400 uppercase tracking-wider">{{ card.name }}</span>
                <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border', card.badgeClass]">
                  {{ card.badge }}
                </span>
              </div>

              <h3 class="text-2xl font-black text-white">{{ card.name }}</h3>
              <p class="text-xs text-slate-400 mt-1">{{ card.subtitle }}</p>

              <!-- Price Tag -->
              <div class="my-6 p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                <div v-if="typeof card.price === 'number'">
                  <div class="flex items-baseline gap-1">
                    <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">Rp {{ formatRupiah(card.price) }}</span>
                    <span class="text-xs text-slate-400 font-medium">/ bulan</span>
                  </div>
                  <div class="flex items-center gap-2 mt-1">
                    <span v-if="card.rawPrice && card.rawPrice > card.price" class="text-xs text-slate-500 line-through">
                      Rp {{ formatRupiah(card.rawPrice) }}/bln
                    </span>
                    <span v-if="discountRate > 0" class="text-[11px] text-emerald-400 font-bold">
                      (Siklus {{ billingCycle === 'annual' ? 'Tahunan' : 'Semesteran' }})
                    </span>
                  </div>
                </div>
                <div v-else>
                  <span class="text-3xl font-black text-white tracking-tight">{{ card.price }}</span>
                  <span class="block text-xs text-slate-400 mt-1">Sesuai Kebutuhan & Multi-Tenant Yayasan</span>
                </div>
              </div>

              <!-- Feature Checklists -->
              <div class="space-y-3 py-4 border-t border-slate-800 text-xs text-slate-300">
                <div v-for="(feat, fIdx) in card.features" :key="fIdx" class="flex items-start gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold shrink-0 mt-0.5"></i>
                  <span class="leading-relaxed">{{ feat }}</span>
                </div>
              </div>
            </div>

            <!-- CTA Button -->
            <div class="mt-8">
              <a v-if="card.ctaLink.startsWith('http')" :href="card.ctaLink" target="_blank" 
                 :class="['w-full py-3.5 px-6 rounded-2xl text-xs font-black text-center transition block', card.btnClass]">
                {{ card.ctaText }}
              </a>
              <Link v-else :href="card.ctaLink" 
                    :class="['w-full py-3.5 px-6 rounded-2xl text-xs font-black text-center transition block', card.btnClass]">
                {{ card.ctaText }}
              </Link>
            </div>

          </div>

        </div>

      </div>
    </section>

    <!-- FAQ SECTION -->
    <section id="faq" class="relative z-10 py-20 border-t border-slate-800/60 bg-slate-900/20">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 space-y-2">
          <h2 class="text-3xl font-black text-white tracking-tight">Pertanyaan yang Sering Diajukan (FAQ)</h2>
          <p class="text-xs text-slate-400">Pahami alur aktivasi dan kemudahan implementasi SINTA di sekolah Anda.</p>
        </div>

        <div class="space-y-4">
          <div v-for="(faq, idx) in faqItems" :key="idx" 
               class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800">
            <h4 class="text-sm font-bold text-white flex items-center gap-2">
              <i class="bi bi-question-circle text-blue-400"></i>
              <span>{{ faq.title }}</span>
            </h4>
            <p class="text-xs text-slate-400 mt-2 leading-relaxed pl-6">
              {{ faq.subtitle || faq.content }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FINAL CTA BANNER -->
    <section class="relative z-10 py-20 border-t border-slate-800/80">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-10 sm:p-14 rounded-3xl bg-gradient-to-tr from-blue-900/70 via-indigo-900/60 to-slate-900 border border-blue-500/40 text-center space-y-6 shadow-2xl">
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Siap Mewujudkan Digitalisasi Sekolah Anda Hari Ini?
          </h2>
          <p class="text-sm text-slate-300 max-w-2xl mx-auto">
            Daftarkan sekolah Anda dalam 2 menit. Super Admin akan memverifikasi dan memberikan akses penuh 1 bulan secara gratis.
          </p>
          <div class="pt-2">
            <Link href="/daftar-sekolah" 
                  class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl bg-white text-blue-900 hover:bg-slate-100 font-black text-sm shadow-xl transition transform hover:scale-105">
              <i class="bi bi-gift-fill text-blue-600"></i>
              <span>{{ heroItem?.cta_text || 'Mulai Free Trial 1 Bulan Sekarang' }}</span>
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="relative z-10 border-t border-slate-800/80 bg-slate-950 py-10 text-xs text-slate-500">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-sm">S</div>
          <span class="font-bold text-slate-400">&copy; 2026 SINTA Platform Tata Kelola Sekolah</span>
        </div>
        <div class="flex items-center gap-6">
          <a href="#fitur" class="hover:text-slate-300 transition">Fitur</a>
          <a href="#kalkulator" class="hover:text-slate-300 transition">Simulasi Biaya</a>
          <a href="#paket" class="hover:text-slate-300 transition">Harga & Trial</a>
          <Link href="/login" class="hover:text-slate-300 transition">Login Portal</Link>
          <Link href="/daftar-sekolah" class="text-blue-400 hover:text-blue-300 font-bold transition">Daftar Free Trial</Link>
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

const heroItem = computed(() => {
  return props.promotions?.hero?.[0] || null;
});

const featureItems = computed(() => {
  if (props.promotions?.features && props.promotions.features.length > 0) {
    return props.promotions.features;
  }
  return [
    { title: 'Buku Induk & Profil Siswa', subtitle: 'Pencatatan NISN, biodata, mutasi, riwayat beasiswa dan cetak lembar buku induk resmi.', icon_class: 'bi-journal-text', badge_text: 'Data Pokok' },
    { title: 'Cetak Rapor Kurikulum Merdeka & K13', subtitle: 'Kalkulasi nilai otomatis, deskripsi capaian pembelajaran dinamis, dan cetak PDF instan.', icon_class: 'bi-award', badge_text: 'Kurikulum' },
    { title: 'Manajemen Keuangan SPP & Kasir', subtitle: 'Pos tarif, tagihan siswa, kuitansi kasir, dan integrasi notifikasi WA.', icon_class: 'bi-wallet2', badge_text: 'Keuangan' },
    { title: 'PPDB & Seleksi Siswa Baru Online', subtitle: 'Pendaftaran mandiri, alur seleksi, dan verifikasi berkas pendaftar.', icon_class: 'bi-person-plus', badge_text: 'Kesiswaan' },
    { title: 'Bimbingan Konseling & Kedisiplinan', subtitle: 'Layanan konseling siswa, pencatatan poin pelanggaran dan surat panggilan orang tua.', icon_class: 'bi-shield-check', badge_text: 'Bimbingan' },
    { title: 'Perpustakaan & Katalog Buku OPAC', subtitle: 'Sirkulasi peminjaman, barcode buku, kartu anggota, dan pelaporan denda.', icon_class: 'bi-book', badge_text: 'Perpustakaan' },
  ];
});

const faqItems = computed(() => {
  if (props.promotions?.faq && props.promotions.faq.length > 0) {
    return props.promotions.faq;
  }
  return [
    { title: 'Bagaimana cara mendapatkan Free Trial 1 Bulan?', subtitle: 'Cukup isi formulir di menu Daftar Sekolah. Super Admin akan memverifikasi permohonan dalam waktu 1x24 jam dan mengaktifkan akses Anda.' },
    { title: 'Apakah data sekolah aman?', subtitle: 'Sangat aman. Setiap sekolah memiliki isolasi skema PostgreSQL dan hak akses terenkripsi multi-tenant.' },
    { title: 'Apakah bisa melakukan penyesuaian modul yang diizinkan?', subtitle: 'Bisa. Super Admin dapat mengatur menu dan modul mana saja yang diaktifkan untuk sekolah Anda saat masa trial maupun langganan.' },
  ];
});
</script>