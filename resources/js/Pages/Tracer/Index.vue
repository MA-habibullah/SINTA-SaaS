<template>
  <AppLayout title="Alumni & Tracer Study">
    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      
      <!-- ═══════════════════════════════════════════════════════════════════════
           1. HERO BANNER EXECUTIVE
           ═══════════════════════════════════════════════════════════════════════ -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-900 via-indigo-900 to-teal-900 p-6 sm:p-8 text-white shadow-xl">
        <!-- Ambient Background Effects -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 -bottom-16 h-64 w-64 rounded-full bg-teal-500/20 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div class="space-y-2">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1 text-xs font-semibold backdrop-blur-md border border-white/15">
              <i class="bi bi-mortarboard-fill text-amber-300"></i>
              <span>Penelusuran Karir & Portofolio Alumni</span>
              <span class="bg-indigo-500/50 text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider font-bold">Executive Hub</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
              Alumni & Tracer Study
            </h1>
            <p class="text-xs sm:text-sm text-slate-200/90 max-w-2xl leading-relaxed">
              Pusat pelacakan rekam jejak lulusan sekolah, kelanjutan studi ke Perguruan Tinggi Negeri/Swasta, serta keterserapan di dunia industri dan wirausaha mandiri.
            </p>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <button
              type="button"
              @click="refreshData()"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/20 text-white border border-white/20 shadow-sm transition-all hover:scale-[1.02] active:scale-95 backdrop-blur-md"
            >
              <i class="bi bi-arrow-clockwise" :class="{ 'animate-spin': isRefreshing }"></i>
              <span>Refresh Data</span>
            </button>
            <a
              :href="`/bk/alumni/export-excel?tab=${activeTab}${selectedTenant ? '&tenant_id=' + selectedTenant : ''}`"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md transition-all hover:scale-[1.02] active:scale-95"
            >
              <i class="bi bi-file-earmark-excel-fill"></i>
              <span>Ekspor Excel (.xlsx)</span>
            </a>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════
           2. FILTER SEKOLAH / TENANT BANNER (STANDAR BAKU SUPER ADMIN AGENTS.MD)
           ═══════════════════════════════════════════════════════════════════════ -->
      <div v-if="isSuperAdmin" class="p-4 sm:px-5 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5">
          <i class="bi bi-building text-blue-600 text-lg"></i>
          <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
            <i class="bi bi-funnel-fill me-1"></i> Aktif
          </span>

          <!-- Dropdown Filter Sekolah (Khusus Super Admin) -->
          <div class="my-1 md:my-0">
            <select
              v-model="selectedTenant"
              @change="applyTenantFilter"
              class="h-9 px-3 bg-white border border-blue-200 rounded-xl text-xs font-semibold text-slate-800 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[240px] cursor-pointer"
            >
              <option value="">-- Semua Sekolah (Global) --</option>
              <option v-for="t in tenants" :key="t.id" :value="t.id">
                {{ t.nama_sekolah }} {{ t.npsn ? `(${t.npsn})` : '' }}
              </option>
            </select>
          </div>
        </div>

        <!-- Informational Text -->
        <div class="text-xs text-slate-500 font-medium">
          Menampilkan data milik: 
          <strong class="text-blue-700 font-bold ml-1">
            {{ getSelectedTenantName() }}
          </strong>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════
           3. SUMMARY METRIC CARDS
           ═══════════════════════════════════════════════════════════════════════ -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Alumni -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:shadow-md transition-all">
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0 font-bold shadow-xs">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Alumni Terdata</span>
            <div class="text-2xl font-black text-slate-800 tracking-tight mt-0.5">{{ metrics?.totalResponden || 0 }}</div>
          </div>
        </div>

        <!-- Card 2: Melanjutkan Kuliah -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:shadow-md transition-all">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 font-bold shadow-xs">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Studi PTN / PTS</span>
            <div class="text-2xl font-black text-blue-600 tracking-tight mt-0.5">{{ metrics?.totalKuliah || 0 }}</div>
          </div>
        </div>

        <!-- Card 3: Bekerja -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:shadow-md transition-all">
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0 font-bold shadow-xs">
            <i class="bi bi-briefcase-fill"></i>
          </div>
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Karyawan Bekerja</span>
            <div class="text-2xl font-black text-emerald-600 tracking-tight mt-0.5">{{ metrics?.totalBekerja || 0 }}</div>
          </div>
        </div>

        <!-- Card 4: Wirausaha -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:shadow-md transition-all">
          <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0 font-bold shadow-xs">
            <i class="bi bi-shop"></i>
          </div>
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Wirausaha Mandiri</span>
            <div class="text-2xl font-black text-purple-600 tracking-tight mt-0.5">{{ metrics?.totalWirausaha || 0 }}</div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════
           4. HORIZONTAL NAVTABS SCROLLER BAR (3-WAY INTERACTION)
           ═══════════════════════════════════════════════════════════════════════ -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <!-- Chevron Left -->
          <button
            type="button"
            class="w-[34px] h-[34px] hidden md:flex items-center justify-center shrink-0 rounded-xl border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shadow-2xs me-1.5"
            @click="scrollTabs(-220)"
            title="Geser ke Kiri"
          >
            <i class="bi bi-chevron-left text-xs"></i>
          </button>

          <!-- Navtabs Container -->
          <div class="grow overflow-hidden relative">
            <ul
              ref="navTabsContainer"
              class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap gap-1.5 px-1 select-none no-scrollbar py-0.5"
              role="tablist"
              @wheel="handleTabsWheel"
              @mousedown="handleTabsMouseDown"
              @mouseleave="handleTabsMouseLeave"
              @mouseup="handleTabsMouseUp"
              @mousemove="handleTabsMouseMove"
            >
              <!-- Tab 1: Riwayat Kuliah -->
              <li>
                <button
                  type="button"
                  @click="switchTab('kuliah')"
                  class="font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === 'kuliah' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                >
                  <i class="bi bi-mortarboard text-sm"></i>
                  <span>Riwayat Kuliah / PTN / PTS</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="activeTab === 'kuliah' ? 'bg-blue-700/80 text-white' : 'bg-slate-100 text-slate-600'"
                  >
                    {{ riwayatKuliah?.total || 0 }}
                  </span>
                </button>
              </li>

              <!-- Tab 2: Riwayat Pekerjaan -->
              <li>
                <button
                  type="button"
                  @click="switchTab('pekerjaan')"
                  class="font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === 'pekerjaan' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                >
                  <i class="bi bi-briefcase text-sm"></i>
                  <span>Riwayat Karir & Pekerjaan</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="activeTab === 'pekerjaan' ? 'bg-emerald-700/80 text-white' : 'bg-slate-100 text-slate-600'"
                  >
                    {{ riwayatPekerjaan?.total || 0 }}
                  </span>
                </button>
              </li>

              <!-- Tab 3: Tracking Direktori Alumni -->
              <li>
                <button
                  type="button"
                  @click="switchTab('tracking')"
                  class="font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === 'tracking' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                >
                  <i class="bi bi-search text-sm"></i>
                  <span>Tracking Direktori Siswa Lulus</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="activeTab === 'tracking' ? 'bg-indigo-700/80 text-white' : 'bg-slate-100 text-slate-600'"
                  >
                    {{ alumniTracking?.total || 0 }}
                  </span>
                </button>
              </li>
            </ul>
          </div>

          <!-- Chevron Right -->
          <button
            type="button"
            class="w-[34px] h-[34px] hidden md:flex items-center justify-center shrink-0 rounded-xl border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shadow-2xs ms-1.5"
            @click="scrollTabs(220)"
            title="Geser ke Kanan"
          >
            <i class="bi bi-chevron-right text-xs"></i>
          </button>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════
           TAB CONTENT 1: RIWAYAT KULIAH
           ═══════════════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'kuliah'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        
        <!-- Toolbar & Filter Kuliah -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
              <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-800">Riwayat Pendidikan Tinggi Alumni</h2>
              <p class="text-[11px] text-slate-400">Daftar lulusan yang melanjutkan studi di PTN, PTS, atau Kedinasan.</p>
            </div>
          </div>

          <!-- Filters -->
          <div class="flex items-center gap-2 flex-wrap">
            <div class="relative">
              <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input
                type="text"
                v-model="searchKuliah"
                @input="debounceSearch"
                placeholder="Cari alumni, kampus, prodi..."
                class="rounded-xl border border-slate-200 pl-8 pr-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 w-52 shadow-2xs bg-slate-50/50 focus:bg-white"
              />
            </div>

            <select
              v-model="filterStatusKuliah"
              @change="applyFilter"
              class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs cursor-pointer"
            >
              <option value="">Semua Status Kuliah</option>
              <option value="Aktif">Aktif</option>
              <option value="Lulus">Lulus</option>
              <option value="Drop">Drop Out</option>
            </select>

            <select
              v-model="filterTahunKuliah"
              @change="applyFilter"
              class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs cursor-pointer"
            >
              <option value="">Semua Tahun Masuk</option>
              <option v-for="t in listTahun" :key="t" :value="t">{{ t }}</option>
            </select>

            <button
              type="button"
              @click="openModalTambahKuliah()"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-all hover:scale-[1.02] active:scale-95"
            >
              <i class="bi bi-plus-lg"></i>
              <span>Tambah Riwayat Kuliah</span>
            </button>
          </div>
        </div>

        <!-- Data Table Kuliah -->
        <div v-if="riwayatKuliah?.data?.length > 0" class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-2xs">
          <table class="w-full text-left border-collapse text-xs text-slate-700">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3 px-3 text-center w-12">#</th>
                <th class="py-3 px-3">Nama Alumni & Identitas</th>
                <th v-if="isSuperAdmin" class="py-3 px-3">Sekolah / Instansi</th>
                <th class="py-3 px-3">Perguruan Tinggi / Kampus</th>
                <th class="py-3 px-3">Program Studi & Jenjang</th>
                <th class="py-3 px-3 text-center">Jalur Masuk</th>
                <th class="py-3 px-3 text-center">Periode</th>
                <th class="py-3 px-3 text-center">Status</th>
                <th class="py-3 px-3 text-center w-24">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, idx) in riwayatKuliah.data" :key="item.id" class="hover:bg-blue-50/40 transition">
                <td class="py-3 px-3 text-center font-bold text-slate-400">
                  {{ (riwayatKuliah.current_page - 1) * riwayatKuliah.per_page + idx + 1 }}
                </td>
                <td class="py-3 px-3">
                  <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    <span>{{ item.is_manual ? item.nama_alumni : (item.siswa?.nama_lengkap || item.nama_alumni) }}</span>
                    <span
                      v-if="item.is_manual"
                      class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                    >
                      Luar Sistem
                    </span>
                    <span
                      v-else
                      class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                    >
                      Siswa Sistem
                    </span>
                  </div>
                  <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                    NISN: {{ item.is_manual ? (item.nisn || '—') : (item.siswa?.nisn || item.nisn || '—') }}
                  </div>
                </td>
                <td v-if="isSuperAdmin" class="py-3 px-3">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                    <i class="bi bi-building"></i>
                    {{ item.tenant?.nama_sekolah || '-' }}
                  </span>
                </td>
                <td class="py-3 px-3">
                  <div class="font-bold text-indigo-700 text-xs flex items-center gap-1">
                    <i class="bi bi-building text-indigo-500"></i>
                    <span>{{ item.nama_kampus }}</span>
                  </div>
                  <div v-if="item.is_kampus_swasta" class="text-[10px] text-amber-600 font-semibold flex items-center gap-1 mt-0.5">
                    <i class="bi bi-patch-check"></i> Kampus Swasta / LN
                  </div>
                </td>
                <td class="py-3 px-3">
                  <div class="font-semibold text-slate-800">{{ item.nama_prodi || '—' }}</div>
                  <div class="text-[10px] text-slate-500 mt-0.5">
                    {{ item.jenjang || 'S1' }} {{ item.fakultas ? ' • Fakultas ' + item.fakultas : '' }}
                  </div>
                </td>
                <td class="py-3 px-3 text-center">
                  <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ item.jalur_masuk || '—' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center font-mono text-[11px]">
                  {{ item.tahun_masuk }} <span class="text-slate-400">s/d</span> {{ item.tahun_lulus || 'Aktif' }}
                </td>
                <td class="py-3 px-3 text-center">
                  <span
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1"
                    :class="{
                      'bg-emerald-50 text-emerald-700 border border-emerald-200': item.status_kuliah === 'Aktif',
                      'bg-blue-50 text-blue-700 border border-blue-200': item.status_kuliah === 'Lulus',
                      'bg-rose-50 text-rose-700 border border-rose-200': item.status_kuliah === 'Drop',
                    }"
                  >
                    <i
                      class="bi"
                      :class="{
                        'bi-clock-fill': item.status_kuliah === 'Aktif',
                        'bi-check-circle-fill': item.status_kuliah === 'Lulus',
                        'bi-x-circle-fill': item.status_kuliah === 'Drop',
                      }"
                    ></i>
                    {{ item.status_kuliah === 'Drop' ? 'Drop Out' : item.status_kuliah }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center">
                  <div class="inline-flex items-center gap-1">
                    <button
                      type="button"
                      @click="openModalEditKuliah(item)"
                      class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition"
                      title="Edit Riwayat Kuliah"
                    >
                      <i class="bi bi-pencil-square text-sm"></i>
                    </button>
                    <button
                      type="button"
                      @click="confirmDeleteKuliah(item)"
                      class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition"
                      title="Hapus Riwayat Kuliah"
                    >
                      <i class="bi bi-trash3 text-sm"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State Kuliah -->
        <div v-else class="text-center py-12 px-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 text-xl">
            <i class="bi bi-mortarboard"></i>
          </div>
          <h3 class="text-sm font-bold text-slate-700">Belum Ada Data Riwayat Kuliah</h3>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">Tambahkan data mahasiswa baru atau alumni yang melanjutkan pendidikan tinggi.</p>
          <button
            type="button"
            @click="openModalTambahKuliah()"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-sm"
          >
            <i class="bi bi-plus-circle-fill"></i>
            <span>Tambah Riwayat Kuliah Baru</span>
          </button>
        </div>

        <!-- Footer Pagination Kuliah -->
        <div v-if="riwayatKuliah?.data?.length > 0" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
          <div class="flex items-center gap-2">
            <span>Menampilkan {{ riwayatKuliah.from || 0 }} - {{ riwayatKuliah.to || 0 }} dari {{ riwayatKuliah.total }} data</span>
            <span class="text-slate-300">•</span>
            <label class="flex items-center gap-1.5 text-xs text-slate-600">
              <span>Baris per halaman:</span>
              <select
                v-model="perPageKuliah"
                @change="applyFilter"
                class="rounded-lg border border-slate-200 py-1 px-2 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </label>
          </div>

          <!-- Pagination Links -->
          <div class="flex items-center gap-1 flex-wrap">
            <button
              v-for="(link, i) in riwayatKuliah.links"
              :key="i"
              type="button"
              @click="goToPage(link.url)"
              :disabled="!link.url || link.active"
              v-html="link.label"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
              :class="{
                'bg-blue-600 text-white shadow-xs': link.active,
                'text-slate-600 hover:bg-slate-100': !link.active && link.url,
                'text-slate-300 cursor-not-allowed': !link.url,
              }"
            ></button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════
           TAB CONTENT 2: RIWAYAT PEKERJAAN
           ═══════════════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'pekerjaan'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        
        <!-- Toolbar & Filter Pekerjaan -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
              <i class="bi bi-briefcase-fill"></i>
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-800">Riwayat Karir & Pekerjaan Alumni</h2>
              <p class="text-[11px] text-slate-400">Daftar alumni yang berkarir di instansi swasta, BUMN, instansi negeri, atau wirausaha.</p>
            </div>
          </div>

          <!-- Filters -->
          <div class="flex items-center gap-2 flex-wrap">
            <div class="relative">
              <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input
                type="text"
                v-model="searchPekerjaan"
                @input="debounceSearch"
                placeholder="Cari alumni, perusahaan, posisi..."
                class="rounded-xl border border-slate-200 pl-8 pr-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 w-52 shadow-2xs bg-slate-50/50 focus:bg-white"
              />
            </div>

            <select
              v-model="filterStatusKerja"
              @change="applyFilter"
              class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs cursor-pointer"
            >
              <option value="">Semua Status Karir</option>
              <option value="Tetap">Karyawan Tetap</option>
              <option value="Kontrak">Karyawan Kontrak</option>
              <option value="Magang">Magang / Internship</option>
              <option value="Wirausaha">Wirausaha / Freelance</option>
            </select>

            <select
              v-model="filterTahunPekerjaan"
              @change="applyFilter"
              class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs cursor-pointer"
            >
              <option value="">Semua Tahun Mulai</option>
              <option v-for="t in listTahun" :key="t" :value="t">{{ t }}</option>
            </select>

            <button
              type="button"
              @click="openModalTambahPekerjaan()"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all hover:scale-[1.02] active:scale-95"
            >
              <i class="bi bi-plus-lg"></i>
              <span>Tambah Riwayat Karir</span>
            </button>
          </div>
        </div>

        <!-- Data Table Pekerjaan -->
        <div v-if="riwayatPekerjaan?.data?.length > 0" class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-2xs">
          <table class="w-full text-left border-collapse text-xs text-slate-700">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3 px-3 text-center w-12">#</th>
                <th class="py-3 px-3">Nama Alumni & Identitas</th>
                <th v-if="isSuperAdmin" class="py-3 px-3">Sekolah / Instansi</th>
                <th class="py-3 px-3">Perusahaan / Tempat Kerja</th>
                <th class="py-3 px-3">Posisi / Jabatan</th>
                <th class="py-3 px-3 text-center">Periode Kerja</th>
                <th class="py-3 px-3 text-center">Pendapatan</th>
                <th class="py-3 px-3 text-center">Status Karir</th>
                <th class="py-3 px-3 text-center w-24">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, idx) in riwayatPekerjaan.data" :key="item.id" class="hover:bg-emerald-50/40 transition">
                <td class="py-3 px-3 text-center font-bold text-slate-400">
                  {{ (riwayatPekerjaan.current_page - 1) * riwayatPekerjaan.per_page + idx + 1 }}
                </td>
                <td class="py-3 px-3">
                  <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    <span>{{ item.is_manual ? item.nama_alumni : (item.siswa?.nama_lengkap || item.nama_alumni) }}</span>
                    <span
                      v-if="item.is_manual"
                      class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                    >
                      Luar Sistem
                    </span>
                    <span
                      v-else
                      class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                    >
                      Siswa Sistem
                    </span>
                  </div>
                  <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                    NISN: {{ item.is_manual ? (item.nisn || '—') : (item.siswa?.nisn || item.nisn || '—') }}
                  </div>
                </td>
                <td v-if="isSuperAdmin" class="py-3 px-3">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                    <i class="bi bi-building"></i>
                    {{ item.tenant?.nama_sekolah || '-' }}
                  </span>
                </td>
                <td class="py-3 px-3">
                  <div class="font-bold text-slate-800 text-xs flex items-center gap-1">
                    <i class="bi bi-buildings text-slate-400"></i>
                    <span>{{ item.nama_perusahaan }}</span>
                  </div>
                  <div class="text-[10px] text-slate-500 mt-0.5">{{ item.jenis_instansi || 'Perusahaan Swasta' }}</div>
                </td>
                <td class="py-3 px-3">
                  <div class="font-semibold text-slate-800">{{ item.posisi_jabatan || item.posisi || '—' }}</div>
                </td>
                <td class="py-3 px-3 text-center font-mono text-[11px]">
                  {{ item.tahun_mulai }} <span class="text-slate-400">s/d</span> {{ item.tahun_selesai || 'Sekarang' }}
                </td>
                <td class="py-3 px-3 text-center text-slate-600 font-medium">
                  {{ item.pendapatan_bulanan || '—' }}
                </td>
                <td class="py-3 px-3 text-center">
                  <span
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1"
                    :class="{
                      'bg-emerald-50 text-emerald-700 border border-emerald-200': item.status_kerja === 'Tetap',
                      'bg-amber-50 text-amber-700 border border-amber-200': item.status_kerja === 'Kontrak',
                      'bg-blue-50 text-blue-700 border border-blue-200': item.status_kerja === 'Magang',
                      'bg-purple-50 text-purple-700 border border-purple-200': item.status_kerja === 'Wirausaha',
                    }"
                  >
                    <i class="bi bi-briefcase-fill"></i>
                    {{ item.status_kerja }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center">
                  <div class="inline-flex items-center gap-1">
                    <button
                      type="button"
                      @click="openModalEditPekerjaan(item)"
                      class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition"
                      title="Edit Riwayat Pekerjaan"
                    >
                      <i class="bi bi-pencil-square text-sm"></i>
                    </button>
                    <button
                      type="button"
                      @click="confirmDeletePekerjaan(item)"
                      class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition"
                      title="Hapus Riwayat Pekerjaan"
                    >
                      <i class="bi bi-trash3 text-sm"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State Pekerjaan -->
        <div v-else class="text-center py-12 px-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 text-xl">
            <i class="bi bi-briefcase"></i>
          </div>
          <h3 class="text-sm font-bold text-slate-700">Belum Ada Data Riwayat Karir</h3>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">Tambahkan data alumni yang sudah bekerja di instansi atau mendirikan wirausaha mandiri.</p>
          <button
            type="button"
            @click="openModalTambahPekerjaan()"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
          >
            <i class="bi bi-plus-circle-fill"></i>
            <span>Tambah Riwayat Karir Baru</span>
          </button>
        </div>

        <!-- Footer Pagination Pekerjaan -->
        <div v-if="riwayatPekerjaan?.data?.length > 0" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
          <div class="flex items-center gap-2">
            <span>Menampilkan {{ riwayatPekerjaan.from || 0 }} - {{ riwayatPekerjaan.to || 0 }} dari {{ riwayatPekerjaan.total }} data</span>
            <span class="text-slate-300">•</span>
            <label class="flex items-center gap-1.5 text-xs text-slate-600">
              <span>Baris per halaman:</span>
              <select
                v-model="perPagePekerjaan"
                @change="applyFilter"
                class="rounded-lg border border-slate-200 py-1 px-2 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
              >
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </label>
          </div>

          <!-- Pagination Links -->
          <div class="flex items-center gap-1 flex-wrap">
            <button
              v-for="(link, i) in riwayatPekerjaan.links"
              :key="i"
              type="button"
              @click="goToPage(link.url)"
              :disabled="!link.url || link.active"
              v-html="link.label"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
              :class="{
                'bg-emerald-600 text-white shadow-xs': link.active,
                'text-slate-600 hover:bg-slate-100': !link.active && link.url,
                'text-slate-300 cursor-not-allowed': !link.url,
              }"
            ></button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════
           TAB CONTENT 3: TRACKING DIREKTORI SISWA LULUS
           ═══════════════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'tracking'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        
        <!-- Toolbar Tracking -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">
              <i class="bi bi-search"></i>
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-800">Direktori Siswa Lulusan & Buku Induk</h2>
              <p class="text-[11px] text-slate-400">Pencarian pangkalan data siswa lulusan untuk penelusuran status alumni.</p>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <div class="relative">
              <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input
                type="text"
                v-model="searchTracking"
                @input="debounceSearch"
                placeholder="Cari nama siswa, NISN, jurusan..."
                class="rounded-xl border border-slate-200 pl-8 pr-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64 shadow-2xs bg-slate-50/50 focus:bg-white"
              />
            </div>
          </div>
        </div>

        <!-- Data Table Tracking -->
        <div v-if="alumniTracking?.data?.length > 0" class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-2xs">
          <table class="w-full text-left border-collapse text-xs text-slate-700">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3 px-3 text-center w-12">#</th>
                <th class="py-3 px-3">Nama Lengkap Siswa</th>
                <th v-if="isSuperAdmin" class="py-3 px-3">Sekolah / Instansi</th>
                <th class="py-3 px-3">NISN / NIS</th>
                <th class="py-3 px-3">Jurusan & Kelas Terakhir</th>
                <th class="py-3 px-3 text-center">Jenis Kelamin</th>
                <th class="py-3 px-3 text-center">Status Siswa</th>
                <th class="py-3 px-3 text-center w-36">Aksi Cepat</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, idx) in alumniTracking.data" :key="item.id" class="hover:bg-indigo-50/40 transition">
                <td class="py-3 px-3 text-center font-bold text-slate-400">
                  {{ (alumniTracking.current_page - 1) * alumniTracking.per_page + idx + 1 }}
                </td>
                <td class="py-3 px-3 font-bold text-slate-800">
                  {{ item.nama_lengkap }}
                </td>
                <td v-if="isSuperAdmin" class="py-3 px-3">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                    <i class="bi bi-building"></i>
                    {{ item.tenant?.nama_sekolah || '-' }}
                  </span>
                </td>
                <td class="py-3 px-3 font-mono text-[11px] text-slate-600">
                  {{ item.nisn || item.nis || '—' }}
                </td>
                <td class="py-3 px-3">
                  <div class="font-semibold text-slate-700">{{ item.jurusan || 'Umum' }}</div>
                  <div class="text-[10px] text-slate-400 mt-0.5">Kelas: {{ item.kelas_saat_ini || 'Alumni' }}</div>
                </td>
                <td class="py-3 px-3 text-center">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="item.jenis_kelamin === 'L' ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700'">
                    {{ item.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ item.is_active ? 'Aktif' : 'Lulus / Alumni' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center">
                  <div class="inline-flex items-center gap-1">
                    <button
                      type="button"
                      @click="quickAddKuliahForStudent(item)"
                      class="px-2 py-1 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 transition"
                      title="Tambah Riwayat Kuliah"
                    >
                      <i class="bi bi-mortarboard-fill me-1"></i> Kuliah
                    </button>
                    <button
                      type="button"
                      @click="quickAddPekerjaanForStudent(item)"
                      class="px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition"
                      title="Tambah Riwayat Pekerjaan"
                    >
                      <i class="bi bi-briefcase-fill me-1"></i> Karir
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State Tracking -->
        <div v-else class="text-center py-12 px-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3 text-xl">
            <i class="bi bi-people"></i>
          </div>
          <h3 class="text-sm font-bold text-slate-700">Tidak Ada Data Siswa Lulusan Ditemukan</h3>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">Gunakan kata kunci lain pada kolom pencarian di atas.</p>
        </div>

        <!-- Footer Pagination Tracking -->
        <div v-if="alumniTracking?.data?.length > 0" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
          <div>Menampilkan {{ alumniTracking.from || 0 }} - {{ alumniTracking.to || 0 }} dari {{ alumniTracking.total }} siswa</div>
          <div class="flex items-center gap-1 flex-wrap">
            <button
              v-for="(link, i) in alumniTracking.links"
              :key="i"
              type="button"
              @click="goToPage(link.url)"
              :disabled="!link.url || link.active"
              v-html="link.label"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
              :class="{
                'bg-indigo-600 text-white shadow-xs': link.active,
                'text-slate-600 hover:bg-slate-100': !link.active && link.url,
                'text-slate-300 cursor-not-allowed': !link.url,
              }"
            ></button>
          </div>
        </div>
      </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         5. MODAL POP-UP: FORM RIWAYAT KULIAH (TAMBAH & EDIT)
         ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div
        v-if="modalKuliah.show"
        class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
        @click.self="modalKuliah.show = false"
      >
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl w-full max-w-2xl overflow-hidden my-8 animate-in fade-in zoom-in-95 duration-150">
          
          <!-- Header Modal -->
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-lg shadow-sm">
                <i class="bi" :class="modalKuliah.isEdit ? 'bi-pencil-square' : 'bi-mortarboard-fill'"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-800">
                  {{ modalKuliah.isEdit ? 'Edit Riwayat Kuliah Alumni' : 'Tambah Riwayat Kuliah Alumni' }}
                </h3>
                <p class="text-[11px] text-slate-400">Pencatatan perguruan tinggi, jenjang studi, dan status kelulusan alumni.</p>
              </div>
            </div>
            <button
              type="button"
              @click="modalKuliah.show = false"
              class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition"
            >
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>

          <!-- Body Modal Form -->
          <form @submit.prevent="submitModalKuliah" class="p-6 space-y-5 text-xs">
            
            <!-- LANGKAH 1: IDENTITAS ALUMNI -->
            <div class="space-y-3">
              <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                <span class="text-xs font-black text-indigo-700 uppercase tracking-wider flex items-center gap-1.5">
                  <span class="w-5 h-5 rounded-md bg-indigo-600 text-white flex items-center justify-center text-[10px]">1</span>
                  IDENTITAS ALUMNI
                </span>

                <!-- Mode Toggle -->
                <div v-if="!modalKuliah.isEdit">
                  <button
                    v-if="!modalKuliah.form.is_manual"
                    type="button"
                    @click="setKuliahManualMode(true)"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition flex items-center gap-1"
                  >
                    <i class="bi bi-pencil-square"></i> Alumni Luar Sistem (Manual)
                  </button>
                  <button
                    v-else
                    type="button"
                    @click="setKuliahManualMode(false)"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition flex items-center gap-1"
                  >
                    <i class="bi bi-database-check"></i> Pilih dari Database Siswa
                  </button>
                </div>
                <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                  {{ modalKuliah.form.is_manual ? 'Alumni Luar Sistem' : 'Siswa Terdaftar' }}
                </span>
              </div>

              <!-- Pilihan Sekolah jika Super Admin & Mode Manual -->
              <div v-if="isSuperAdmin && !modalKuliah.isEdit && modalKuliah.form.is_manual">
                <label class="block font-bold text-slate-700 mb-1">Sekolah / Tenant Pemilik Data</label>
                <select
                  v-model="modalKuliah.form.tenant_id"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">-- Gunakan Sekolah Terpilih Saat Ini --</option>
                  <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                </select>
              </div>

              <!-- Mode Edit Display -->
              <div v-if="modalKuliah.isEdit" class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center text-sm shadow-xs shrink-0">
                  {{ (modalKuliah.form.nama_alumni || 'A').charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="font-bold text-slate-800 text-xs">{{ modalKuliah.form.nama_alumni }}</div>
                  <div class="text-[10px] text-slate-400 font-mono mt-0.5">NISN: {{ modalKuliah.form.nisn || '—' }}</div>
                </div>
              </div>

              <!-- Mode Tambah: Database Siswa Autocomplete -->
              <div v-else-if="!modalKuliah.form.is_manual" class="space-y-2">
                <label class="block font-bold text-slate-700">
                  Cari Siswa di Database Buku Induk <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-indigo-600 text-sm"></i>
                  <input
                    type="text"
                    v-model="searchStudentQueryKuliah"
                    @input="searchStudents('kuliah')"
                    @focus="showSearchDropdownKuliah = true"
                    placeholder="Ketik nama lengkap atau NISN siswa lulus..."
                    class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white"
                  />
                  <!-- Dropdown Search Results -->
                  <div
                    v-if="showSearchDropdownKuliah && searchStudentResults.length > 0"
                    class="absolute z-50 left-0 right-0 top-full mt-1.5 bg-white rounded-xl border border-slate-200 shadow-xl max-h-48 overflow-y-auto p-1.5 space-y-1"
                  >
                    <button
                      type="button"
                      v-for="s in searchStudentResults"
                      :key="s.id"
                      @click="selectStudentForKuliah(s)"
                      class="w-full text-left p-2 rounded-lg hover:bg-indigo-50 transition flex items-center justify-between text-xs"
                    >
                      <div>
                        <div class="font-bold text-slate-800">{{ s.nama_lengkap }}</div>
                        <div class="text-[10px] text-slate-400">
                          NISN: {{ s.nisn || '—' }} | Kelas: {{ s.kelas_saat_ini || 'Alumni' }}
                          <span v-if="s.tenant?.nama_sekolah" class="text-indigo-600 font-semibold ml-1">({{ s.tenant.nama_sekolah }})</span>
                        </div>
                      </div>
                      <i class="bi bi-chevron-right text-slate-300 text-xs"></i>
                    </button>
                  </div>
                </div>

                <!-- Terpilih Card -->
                <div v-if="selectedStudentKuliah" class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-200 flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <i class="bi bi-check-circle-fill text-indigo-600 text-base"></i>
                    <div>
                      <span class="block font-bold text-indigo-950 text-xs">{{ selectedStudentKuliah.nama_lengkap }}</span>
                      <span class="text-indigo-700 text-[10px]">
                        NISN: {{ selectedStudentKuliah.nisn || '-' }} | Terpilih dari database buku induk
                        <span v-if="selectedStudentKuliah.tenant?.nama_sekolah" class="font-semibold text-indigo-900"> • {{ selectedStudentKuliah.tenant.nama_sekolah }}</span>
                      </span>
                    </div>
                  </div>
                  <button
                    type="button"
                    @click="selectedStudentKuliah = null; modalKuliah.form.siswa_id = ''"
                    class="px-2 py-1 rounded-md text-[10px] font-bold bg-white text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition"
                  >
                    Ganti
                  </button>
                </div>
              </div>

              <!-- Mode Tambah: Input Manual -->
              <div v-else class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-7">
                  <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Alumni <span class="text-rose-500">*</span></label>
                  <input
                    type="text"
                    v-model="modalKuliah.form.nama_alumni"
                    placeholder="Contoh: Budi Santoso"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                  />
                </div>
                <div class="sm:col-span-5">
                  <label class="block font-bold text-slate-700 mb-1">NISN / NIS (Opsional)</label>
                  <input
                    type="text"
                    v-model="modalKuliah.form.nisn"
                    placeholder="Nomor Induk Siswa..."
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
              </div>
            </div>

            <!-- LANGKAH 2: PERGURUAN TINGGI & PRODI -->
            <div class="space-y-3">
              <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                <span class="text-xs font-black text-blue-700 uppercase tracking-wider flex items-center gap-1.5">
                  <span class="w-5 h-5 rounded-md bg-blue-600 text-white flex items-center justify-center text-[10px]">2</span>
                  PERGURUAN TINGGI & PROGRAM STUDI
                </span>

                <!-- Mode Kampus Toggle -->
                <div>
                  <button
                    v-if="!modalKuliah.form.is_kampus_swasta"
                    type="button"
                    @click="modalKuliah.form.is_kampus_swasta = true"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition flex items-center gap-1"
                  >
                    <i class="bi bi-pencil-square"></i> Ketik Manual (Swasta / LN)
                  </button>
                  <button
                    v-else
                    type="button"
                    @click="modalKuliah.form.is_kampus_swasta = false"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition flex items-center gap-1"
                  >
                    <i class="bi bi-building-check"></i> Pilih dari Master PDSS
                  </button>
                </div>
              </div>

              <!-- Dropdown Master Kampus PDSS -->
              <div v-if="!modalKuliah.form.is_kampus_swasta" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Perguruan Tinggi (PDSS) <span class="text-rose-500">*</span></label>
                  <select
                    v-model="modalKuliah.form.kampus_id"
                    @change="onKampusChange"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">-- Pilih Perguruan Tinggi --</option>
                    <option v-for="k in masterKampus" :key="k.id" :value="k.id">
                      {{ k.nama_kampus }} ({{ k.jenis_kampus || 'PTN' }})
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Program Studi <span class="text-rose-500">*</span></label>
                  <select
                    v-model="modalKuliah.form.prodi_id"
                    @change="onProdiChange"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="loadingProdi || listProdi.length === 0"
                    required
                  >
                    <option value="">{{ loadingProdi ? 'Memuat prodi...' : '-- Pilih Program Studi --' }}</option>
                    <option v-for="p in listProdi" :key="p.id" :value="p.id">
                      {{ p.program_studi }} ({{ p.jenjang || 'S1' }})
                    </option>
                  </select>
                </div>
              </div>

              <!-- Input Manual Kampus Swasta / LN -->
              <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Nama Kampus Swasta / Luar Negeri <span class="text-rose-500">*</span></label>
                  <input
                    type="text"
                    v-model="modalKuliah.form.nama_kampus"
                    placeholder="Contoh: Universitas Telkom / Monash Univ"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Program Studi <span class="text-rose-500">*</span></label>
                  <input
                    type="text"
                    v-model="modalKuliah.form.nama_prodi"
                    placeholder="Contoh: Sistem Informasi / Informatika"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
              </div>

              <!-- Jalur, Jenjang, Fakultas -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Jalur Masuk / Seleksi</label>
                  <select
                    v-model="modalKuliah.form.jalur_masuk_id"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">-- Pilih Jalur Masuk --</option>
                    <option v-for="j in masterJalur" :key="j.id" :value="j.id">{{ j.nama_jalur }}</option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Jenjang Pendidikan</label>
                  <select
                    v-model="modalKuliah.form.jenjang"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="S1">S1 (Sarjana)</option>
                    <option value="D4">D4 (Sarjana Terapan)</option>
                    <option value="D3">D3 (Diploma Tiga)</option>
                    <option value="S2">S2 (Magister)</option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Fakultas (Opsional)</label>
                  <input
                    type="text"
                    v-model="modalKuliah.form.fakultas"
                    placeholder="Contoh: Teknik / MIPA"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>

            <!-- LANGKAH 3: PERIODE & STATUS -->
            <div class="space-y-3">
              <div class="pb-1 border-b border-slate-100">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                  <span class="w-5 h-5 rounded-md bg-slate-700 text-white flex items-center justify-center text-[10px]">3</span>
                  PERIODE & STATUS KELULUSAN
                </span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tahun Masuk <span class="text-rose-500">*</span></label>
                  <input
                    type="number"
                    v-model.number="modalKuliah.form.tahun_masuk"
                    :min="1990"
                    :max="currentYear + 1"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                    required
                  />
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tahun Lulus (Opsional)</label>
                  <input
                    type="number"
                    v-model.number="modalKuliah.form.tahun_lulus"
                    :min="modalKuliah.form.tahun_masuk || 1990"
                    :max="currentYear + 10"
                    placeholder="Kosongkan jika aktif"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                  />
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Status Kuliah <span class="text-rose-500">*</span></label>
                  <select
                    v-model="modalKuliah.form.status_kuliah"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Drop">Drop Out</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Footer Modal -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <button
                type="button"
                @click="modalKuliah.show = false"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="modalKuliah.saving"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md transition"
              >
                <i v-if="modalKuliah.saving" class="bi bi-arrow-clockwise animate-spin"></i>
                <i v-else class="bi bi-check2-circle"></i>
                <span>{{ modalKuliah.isEdit ? 'Simpan Perubahan' : 'Simpan Riwayat Kuliah' }}</span>
              </button>
            </div>

          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════════════════
         6. MODAL POP-UP: FORM RIWAYAT PEKERJAAN (TAMBAH & EDIT)
         ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div
        v-if="modalPekerjaan.show"
        class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
        @click.self="modalPekerjaan.show = false"
      >
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl w-full max-w-2xl overflow-hidden my-8 animate-in fade-in zoom-in-95 duration-150">
          
          <!-- Header Modal -->
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-600 text-white flex items-center justify-center text-lg shadow-sm">
                <i class="bi" :class="modalPekerjaan.isEdit ? 'bi-pencil-square' : 'bi-briefcase-fill'"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-800">
                  {{ modalPekerjaan.isEdit ? 'Edit Riwayat Karir Alumni' : 'Tambah Riwayat Karir Alumni' }}
                </h3>
                <p class="text-[11px] text-slate-400">Pencatatan perusahaan, jabatan, pendapatan, dan status karir alumni.</p>
              </div>
            </div>
            <button
              type="button"
              @click="modalPekerjaan.show = false"
              class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition"
            >
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>

          <!-- Body Modal Form -->
          <form @submit.prevent="submitModalPekerjaan" class="p-6 space-y-5 text-xs">
            
            <!-- LANGKAH 1: IDENTITAS ALUMNI -->
            <div class="space-y-3">
              <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                <span class="text-xs font-black text-emerald-700 uppercase tracking-wider flex items-center gap-1.5">
                  <span class="w-5 h-5 rounded-md bg-emerald-600 text-white flex items-center justify-center text-[10px]">1</span>
                  IDENTITAS ALUMNI
                </span>

                <!-- Mode Toggle -->
                <div v-if="!modalPekerjaan.isEdit">
                  <button
                    v-if="!modalPekerjaan.form.is_manual"
                    type="button"
                    @click="setPekerjaanManualMode(true)"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition flex items-center gap-1"
                  >
                    <i class="bi bi-pencil-square"></i> Alumni Luar Sistem (Manual)
                  </button>
                  <button
                    v-else
                    type="button"
                    @click="setPekerjaanManualMode(false)"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition flex items-center gap-1"
                  >
                    <i class="bi bi-database-check"></i> Pilih dari Database Siswa
                  </button>
                </div>
                <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ modalPekerjaan.form.is_manual ? 'Alumni Luar Sistem' : 'Siswa Terdaftar' }}
                </span>
              </div>

              <!-- Pilihan Sekolah jika Super Admin & Mode Manual -->
              <div v-if="isSuperAdmin && !modalPekerjaan.isEdit && modalPekerjaan.form.is_manual">
                <label class="block font-bold text-slate-700 mb-1">Sekolah / Tenant Pemilik Data</label>
                <select
                  v-model="modalPekerjaan.form.tenant_id"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
                  <option value="">-- Gunakan Sekolah Terpilih Saat Ini --</option>
                  <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                </select>
              </div>

              <!-- Mode Edit Display -->
              <div v-if="modalPekerjaan.isEdit" class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-600 text-white font-bold flex items-center justify-center text-sm shadow-xs shrink-0">
                  {{ (modalPekerjaan.form.nama_alumni || 'A').charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="font-bold text-slate-800 text-xs">{{ modalPekerjaan.form.nama_alumni }}</div>
                  <div class="text-[10px] text-slate-400 font-mono mt-0.5">NISN: {{ modalPekerjaan.form.nisn || '—' }}</div>
                </div>
              </div>

              <!-- Mode Tambah: Database Siswa Autocomplete -->
              <div v-else-if="!modalPekerjaan.form.is_manual" class="space-y-2">
                <label class="block font-bold text-slate-700">
                  Cari Siswa di Database Buku Induk <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-600 text-sm"></i>
                  <input
                    type="text"
                    v-model="searchStudentQueryPekerjaan"
                    @input="searchStudents('pekerjaan')"
                    @focus="showSearchDropdownPekerjaan = true"
                    placeholder="Ketik nama lengkap atau NISN siswa lulus..."
                    class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50/50 focus:bg-white"
                  />
                  <!-- Dropdown Search Results -->
                  <div
                    v-if="showSearchDropdownPekerjaan && searchStudentResultsPekerjaan.length > 0"
                    class="absolute z-50 left-0 right-0 top-full mt-1.5 bg-white rounded-xl border border-slate-200 shadow-xl max-h-48 overflow-y-auto p-1.5 space-y-1"
                  >
                    <button
                      type="button"
                      v-for="s in searchStudentResultsPekerjaan"
                      :key="s.id"
                      @click="selectStudentForPekerjaan(s)"
                      class="w-full text-left p-2 rounded-lg hover:bg-emerald-50 transition flex items-center justify-between text-xs"
                    >
                      <div>
                        <div class="font-bold text-slate-800">{{ s.nama_lengkap }}</div>
                        <div class="text-[10px] text-slate-400">
                          NISN: {{ s.nisn || '—' }} | Kelas: {{ s.kelas_saat_ini || 'Alumni' }}
                          <span v-if="s.tenant?.nama_sekolah" class="text-emerald-600 font-semibold ml-1">({{ s.tenant.nama_sekolah }})</span>
                        </div>
                      </div>
                      <i class="bi bi-chevron-right text-slate-300 text-xs"></i>
                    </button>
                  </div>
                </div>

                <!-- Terpilih Card -->
                <div v-if="selectedStudentPekerjaan" class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <i class="bi bi-check-circle-fill text-emerald-600 text-base"></i>
                    <div>
                      <span class="block font-bold text-emerald-950 text-xs">{{ selectedStudentPekerjaan.nama_lengkap }}</span>
                      <span class="text-emerald-700 text-[10px]">
                        NISN: {{ selectedStudentPekerjaan.nisn || '-' }} | Terpilih dari database buku induk
                        <span v-if="selectedStudentPekerjaan.tenant?.nama_sekolah" class="font-semibold text-emerald-900"> • {{ selectedStudentPekerjaan.tenant.nama_sekolah }}</span>
                      </span>
                    </div>
                  </div>
                  <button
                    type="button"
                    @click="selectedStudentPekerjaan = null; modalPekerjaan.form.siswa_id = ''"
                    class="px-2 py-1 rounded-md text-[10px] font-bold bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition"
                  >
                    Ganti
                  </button>
                </div>
              </div>

              <!-- Mode Tambah: Input Manual -->
              <div v-else class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-7">
                  <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Alumni <span class="text-rose-500">*</span></label>
                  <input
                    type="text"
                    v-model="modalPekerjaan.form.nama_alumni"
                    placeholder="Contoh: Siti Rahmawati"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    required
                  />
                </div>
                <div class="sm:col-span-5">
                  <label class="block font-bold text-slate-700 mb-1">NISN / NIS (Opsional)</label>
                  <input
                    type="text"
                    v-model="modalPekerjaan.form.nisn"
                    placeholder="Nomor Induk Siswa..."
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                  />
                </div>
              </div>
            </div>

            <!-- LANGKAH 2: INFORMASI PERUSAHAAN & KARIR -->
            <div class="space-y-3">
              <div class="pb-1 border-b border-slate-100">
                <span class="text-xs font-black text-teal-700 uppercase tracking-wider flex items-center gap-1.5">
                  <span class="w-5 h-5 rounded-md bg-teal-600 text-white flex items-center justify-center text-[10px]">2</span>
                  INFORMASI PERUSAHAAN & KARIR
                </span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Nama Perusahaan / Tempat Kerja <span class="text-rose-500">*</span></label>
                  <input
                    type="text"
                    v-model="modalPekerjaan.form.nama_perusahaan"
                    placeholder="Contoh: PT. Telkom Indonesia"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    required
                  />
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Posisi / Jabatan Pekerjaan <span class="text-rose-500">*</span></label>
                  <input
                    type="text"
                    v-model="modalPekerjaan.form.posisi_jabatan"
                    placeholder="Contoh: Junior Software Developer"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    required
                  />
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Jenis Instansi</label>
                  <select
                    v-model="modalPekerjaan.form.jenis_instansi"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                  >
                    <option value="Swasta">Perusahaan Swasta / Multinasional</option>
                    <option value="BUMN">BUMN / BUMD</option>
                    <option value="Pemerintah">Pemerintah / Instansi Negeri</option>
                    <option value="Wirausaha">Wirausaha / Bisnis Mandiri</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Rentang Pendapatan Bulanan</label>
                  <select
                    v-model="modalPekerjaan.form.pendapatan_bulanan"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                  >
                    <option value="">-- Pilih Rentang Pendapatan --</option>
                    <option value="< 3 Juta">< Rp 3.000.000</option>
                    <option value="3 - 5 Juta">Rp 3.000.000 - Rp 5.000.000</option>
                    <option value="5 - 10 Juta">Rp 5.000.000 - Rp 10.000.000</option>
                    <option value="> 10 Juta">> Rp 10.000.000</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- LANGKAH 3: PERIODE & STATUS KERJA -->
            <div class="space-y-3">
              <div class="pb-1 border-b border-slate-100">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                  <span class="w-5 h-5 rounded-md bg-slate-700 text-white flex items-center justify-center text-[10px]">3</span>
                  PERIODE & STATUS KARIR
                </span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tahun Mulai Bekerja <span class="text-rose-500">*</span></label>
                  <input
                    type="number"
                    v-model.number="modalPekerjaan.form.tahun_mulai"
                    :min="1990"
                    :max="currentYear + 1"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
                    required
                  />
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tahun Selesai (Opsional)</label>
                  <input
                    type="number"
                    v-model.number="modalPekerjaan.form.tahun_selesai"
                    :min="modalPekerjaan.form.tahun_mulai || 1990"
                    :max="currentYear + 10"
                    placeholder="Kosongkan jika aktif"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
                  />
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Status Karir <span class="text-rose-500">*</span></label>
                  <select
                    v-model="modalPekerjaan.form.status_kerja"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    required
                  >
                    <option value="Tetap">Karyawan Tetap</option>
                    <option value="Kontrak">Karyawan Kontrak</option>
                    <option value="Magang">Magang / Internship</option>
                    <option value="Wirausaha">Wirausaha / Freelance</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Footer Modal -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <button
                type="button"
                @click="modalPekerjaan.show = false"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="modalPekerjaan.saving"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition"
              >
                <i v-if="modalPekerjaan.saving" class="bi bi-arrow-clockwise animate-spin"></i>
                <i v-else class="bi bi-check2-circle"></i>
                <span>{{ modalPekerjaan.isEdit ? 'Simpan Perubahan' : 'Simpan Riwayat Karir' }}</span>
              </button>
            </div>

          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════════════════
         7. MODAL KONFIRMASI HAPUS
         ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div
        v-if="modalDelete.show"
        class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
        @click.self="modalDelete.show = false"
      >
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl w-full max-w-md overflow-hidden p-6 text-center space-y-4 animate-in fade-in zoom-in-95 duration-150">
          <div class="w-14 h-14 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto text-2xl">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-800">Konfirmasi Hapus Data</h3>
            <p class="text-xs text-slate-500 mt-1">
              Apakah Anda yakin ingin menghapus data {{ modalDelete.type === 'kuliah' ? 'riwayat kuliah' : 'riwayat pekerjaan' }} milik
              <strong class="text-slate-800">{{ modalDelete.itemTitle }}</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
          </div>
          <div class="flex items-center justify-center gap-3 pt-2">
            <button
              type="button"
              @click="modalDelete.show = false"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition"
            >
              Batal
            </button>
            <button
              type="button"
              @click="executeDelete"
              :disabled="modalDelete.deleting"
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition"
            >
              <i v-if="modalDelete.deleting" class="bi bi-arrow-clockwise animate-spin"></i>
              <span>{{ modalDelete.deleting ? 'Menghapus...' : 'Ya, Hapus Data' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  riwayatKuliah: Object,
  riwayatPekerjaan: Object,
  alumniTracking: Object,
  masterJalur: Array,
  masterKampus: Array,
  listTahun: Array,
  isSuperAdmin: Boolean,
  tenants: Array,
  selectedTenant: String,
  metrics: Object,
  filters: Object,
});

const currentYear = new Date().getFullYear();
const activeTab = ref(props.filters?.tab || 'kuliah');
const isRefreshing = ref(false);
const selectedTenant = ref(props.selectedTenant || props.filters?.tenant_id || '');

// Filter states
const searchKuliah = ref(props.filters?.tab === 'kuliah' ? props.filters?.search || '' : '');
const searchPekerjaan = ref(props.filters?.tab === 'pekerjaan' ? props.filters?.search || '' : '');
const searchTracking = ref(props.filters?.tab === 'tracking' ? props.filters?.search || '' : '');
const filterStatusKuliah = ref(props.filters?.status_kuliah || '');
const filterStatusKerja = ref(props.filters?.status_kerja || '');
const filterTahunKuliah = ref(props.filters?.tahun || '');
const filterTahunPekerjaan = ref(props.filters?.tahun || '');
const perPageKuliah = ref(props.filters?.per_page || 15);
const perPagePekerjaan = ref(props.filters?.per_page || 15);

// Helper Nama Tenant Terpilih (Standard AGENTS.md)
const getSelectedTenantName = () => {
  if (!selectedTenant.value) return 'Semua Sekolah Terdaftar (Super Admin)';
  const t = props.tenants?.find(item => item.id === selectedTenant.value);
  return t ? t.nama_sekolah : 'Semua Sekolah Terdaftar (Super Admin)';
};

const applyTenantFilter = () => {
  applyFilter();
};

// 3-Way Scroller Reference
const navTabsContainer = ref(null);
let isDown = false;
let startX = 0;
let scrollLeftPos = 0;

const scrollTabs = (distance) => {
  if (navTabsContainer.value) {
    navTabsContainer.value.scrollBy({ left: distance, behavior: 'smooth' });
  }
};

const handleTabsWheel = (e) => {
  if (e.deltaY !== 0 && navTabsContainer.value) {
    e.preventDefault();
    navTabsContainer.value.scrollLeft += e.deltaY;
  }
};

const handleTabsMouseDown = (e) => {
  isDown = true;
  startX = e.pageX - navTabsContainer.value.offsetLeft;
  scrollLeftPos = navTabsContainer.value.scrollLeft;
};

const handleTabsMouseLeave = () => { isDown = false; };
const handleTabsMouseUp = () => { isDown = false; };
const handleTabsMouseMove = (e) => {
  if (!isDown) return;
  e.preventDefault();
  const x = e.pageX - navTabsContainer.value.offsetLeft;
  const walk = (x - startX) * 1.5;
  navTabsContainer.value.scrollLeft = scrollLeftPos - walk;
};

// Switch Tab
const switchTab = (tabName) => {
  activeTab.value = tabName;
  applyFilter();
};

let searchDebounceTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchDebounceTimeout);
  searchDebounceTimeout = setTimeout(() => {
    applyFilter();
  }, 400);
};

const applyFilter = () => {
  const currentSearch = activeTab.value === 'kuliah' 
    ? searchKuliah.value 
    : (activeTab.value === 'pekerjaan' ? searchPekerjaan.value : searchTracking.value);

  const params = {
    tab: activeTab.value,
    tenant_id: selectedTenant.value || undefined,
    search: currentSearch || undefined,
    status_kuliah: activeTab.value === 'kuliah' ? filterStatusKuliah.value || undefined : undefined,
    status_kerja: activeTab.value === 'pekerjaan' ? filterStatusKerja.value || undefined : undefined,
    tahun: activeTab.value === 'kuliah' ? filterTahunKuliah.value || undefined : (activeTab.value === 'pekerjaan' ? filterTahunPekerjaan.value || undefined : undefined),
    per_page: activeTab.value === 'kuliah' ? perPageKuliah.value : (activeTab.value === 'pekerjaan' ? perPagePekerjaan.value : 15),
  };

  router.get('/bk/alumni', params, {
    preserveState: true,
    preserveScroll: true,
  });
};

const goToPage = (url) => {
  if (!url) return;
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  });
};

const refreshData = () => {
  isRefreshing.value = true;
  router.reload({
    onFinish: () => {
      isRefreshing.value = false;
    },
  });
};

// =========================================================================
// SEARCH SISWA AUTOCOMPLETE
// =========================================================================
const searchStudentQueryKuliah = ref('');
const showSearchDropdownKuliah = ref(false);
const searchStudentResults = ref([]);
const selectedStudentKuliah = ref(null);

const searchStudentQueryPekerjaan = ref('');
const showSearchDropdownPekerjaan = ref(false);
const searchStudentResultsPekerjaan = ref([]);
const selectedStudentPekerjaan = ref(null);

let studentDebounce = null;
const searchStudents = (type) => {
  clearTimeout(studentDebounce);
  const q = type === 'kuliah' ? searchStudentQueryKuliah.value : searchStudentQueryPekerjaan.value;
  if (!q || q.length < 2) {
    if (type === 'kuliah') searchStudentResults.value = [];
    else searchStudentResultsPekerjaan.value = [];
    return;
  }

  studentDebounce = setTimeout(async () => {
    try {
      const tenantParam = selectedTenant.value ? `&tenant_id=${selectedTenant.value}` : '';
      const res = await axios.get(`/bk/alumni/api/siswa-alumni?q=${encodeURIComponent(q)}${tenantParam}`);
      if (res.data?.success) {
        if (type === 'kuliah') searchStudentResults.value = res.data.data;
        else searchStudentResultsPekerjaan.value = res.data.data;
      }
    } catch (e) {
      console.error('Failed to search student alumni', e);
    }
  }, 300);
};

const selectStudentForKuliah = (student) => {
  selectedStudentKuliah.value = student;
  modalKuliah.form.siswa_id = student.id;
  modalKuliah.form.tenant_id = student.tenant_id || selectedTenant.value || '';
  modalKuliah.form.nama_alumni = student.nama_lengkap;
  modalKuliah.form.nisn = student.nisn;
  showSearchDropdownKuliah.value = false;
  searchStudentQueryKuliah.value = '';
};

const selectStudentForPekerjaan = (student) => {
  selectedStudentPekerjaan.value = student;
  modalPekerjaan.form.siswa_id = student.id;
  modalPekerjaan.form.tenant_id = student.tenant_id || selectedTenant.value || '';
  modalPekerjaan.form.nama_alumni = student.nama_lengkap;
  modalPekerjaan.form.nisn = student.nisn;
  showSearchDropdownPekerjaan.value = false;
  searchStudentQueryPekerjaan.value = '';
};

// =========================================================================
// MODAL RIWAYAT KULIAH
// =========================================================================
const modalKuliah = reactive({
  show: false,
  isEdit: false,
  saving: false,
  form: {
    id: null,
    tenant_id: '',
    is_manual: false,
    is_kampus_swasta: false,
    siswa_id: '',
    nama_alumni: '',
    nisn: '',
    kampus_id: '',
    prodi_id: '',
    nama_kampus: '',
    nama_prodi: '',
    jalur_masuk_id: '',
    fakultas: '',
    jenjang: 'S1',
    tahun_masuk: currentYear,
    tahun_lulus: null,
    status_kuliah: 'Aktif',
  },
});

const listProdi = ref([]);
const loadingProdi = ref(false);

const setKuliahManualMode = (isManual) => {
  modalKuliah.form.is_manual = isManual;
  modalKuliah.form.siswa_id = '';
  selectedStudentKuliah.value = null;
  searchStudentQueryKuliah.value = '';
};

const onKampusChange = async () => {
  const kampusId = modalKuliah.form.kampus_id;
  modalKuliah.form.prodi_id = '';
  listProdi.value = [];
  if (!kampusId) return;

  loadingProdi.value = true;
  try {
    const res = await axios.get(`/bk/alumni/api/prodi-by-kampus/${kampusId}`);
    if (res.data?.success) {
      listProdi.value = res.data.data;
    }
  } catch (e) {
    console.error('Failed to load prodi', e);
  } finally {
    loadingProdi.value = false;
  }
};

const onProdiChange = () => {
  const p = listProdi.value.find(item => item.id === modalKuliah.form.prodi_id);
  if (p) {
    if (p.jenjang) modalKuliah.form.jenjang = p.jenjang;
    if (p.fakultas && p.fakultas !== '-') modalKuliah.form.fakultas = p.fakultas;
  }
};

const openModalTambahKuliah = () => {
  modalKuliah.isEdit = false;
  modalKuliah.saving = false;
  modalKuliah.form = {
    id: null,
    tenant_id: selectedTenant.value || '',
    is_manual: false,
    is_kampus_swasta: false,
    siswa_id: '',
    nama_alumni: '',
    nisn: '',
    kampus_id: '',
    prodi_id: '',
    nama_kampus: '',
    nama_prodi: '',
    jalur_masuk_id: '',
    fakultas: '',
    jenjang: 'S1',
    tahun_masuk: currentYear,
    tahun_lulus: null,
    status_kuliah: 'Aktif',
  };
  selectedStudentKuliah.value = null;
  listProdi.value = [];
  modalKuliah.show = true;
};

const openModalEditKuliah = async (item) => {
  modalKuliah.isEdit = true;
  modalKuliah.saving = false;
  modalKuliah.form = {
    id: item.id,
    tenant_id: item.tenant_id || '',
    is_manual: !!item.is_manual,
    is_kampus_swasta: !!item.is_kampus_swasta,
    siswa_id: item.siswa_id || '',
    nama_alumni: item.is_manual ? item.nama_alumni : (item.siswa?.nama_lengkap || item.nama_alumni),
    nisn: item.is_manual ? item.nisn : (item.siswa?.nisn || item.nisn),
    kampus_id: item.kampus_id || '',
    prodi_id: item.prodi_id || '',
    nama_kampus: item.nama_kampus || '',
    nama_prodi: item.nama_prodi || '',
    jalur_masuk_id: item.jalur_masuk_id || '',
    fakultas: item.fakultas || '',
    jenjang: item.jenjang || 'S1',
    tahun_masuk: item.tahun_masuk || currentYear,
    tahun_lulus: item.tahun_lulus || null,
    status_kuliah: item.status_kuliah || 'Aktif',
  };

  listProdi.value = [];
  if (item.kampus_id) {
    loadingProdi.value = true;
    try {
      const res = await axios.get(`/bk/alumni/api/prodi-by-kampus/${item.kampus_id}`);
      if (res.data?.success) {
        listProdi.value = res.data.data;
      }
    } catch (e) {
      console.error(e);
    } finally {
      loadingProdi.value = false;
    }
  }

  modalKuliah.show = true;
};

const submitModalKuliah = async () => {
  modalKuliah.saving = true;
  try {
    if (modalKuliah.isEdit) {
      await axios.put(`/bk/alumni/kuliah/${modalKuliah.form.id}`, modalKuliah.form);
    } else {
      await axios.post('/bk/alumni/kuliah', modalKuliah.form);
    }
    modalKuliah.show = false;
    router.reload({ preserveScroll: true });
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.data?.error || 'Gagal menyimpan riwayat kuliah.';
    alert(msg);
  } finally {
    modalKuliah.saving = false;
  }
};

const quickAddKuliahForStudent = (student) => {
  openModalTambahKuliah();
  selectStudentForKuliah(student);
};

// =========================================================================
// MODAL RIWAYAT PEKERJAAN
// =========================================================================
const modalPekerjaan = reactive({
  show: false,
  isEdit: false,
  saving: false,
  form: {
    id: null,
    tenant_id: '',
    is_manual: false,
    siswa_id: '',
    nama_alumni: '',
    nisn: '',
    nama_perusahaan: '',
    posisi_jabatan: '',
    jenis_instansi: 'Swasta',
    pendapatan_bulanan: '',
    status_kerja: 'Tetap',
    tahun_mulai: currentYear,
    tahun_selesai: null,
  },
});

const setPekerjaanManualMode = (isManual) => {
  modalPekerjaan.form.is_manual = isManual;
  modalPekerjaan.form.siswa_id = '';
  selectedStudentPekerjaan.value = null;
  searchStudentQueryPekerjaan.value = '';
};

const openModalTambahPekerjaan = () => {
  modalPekerjaan.isEdit = false;
  modalPekerjaan.saving = false;
  modalPekerjaan.form = {
    id: null,
    tenant_id: selectedTenant.value || '',
    is_manual: false,
    siswa_id: '',
    nama_alumni: '',
    nisn: '',
    nama_perusahaan: '',
    posisi_jabatan: '',
    jenis_instansi: 'Swasta',
    pendapatan_bulanan: '',
    status_kerja: 'Tetap',
    tahun_mulai: currentYear,
    tahun_selesai: null,
  };
  selectedStudentPekerjaan.value = null;
  modalPekerjaan.show = true;
};

const openModalEditPekerjaan = (item) => {
  modalPekerjaan.isEdit = true;
  modalPekerjaan.saving = false;
  modalPekerjaan.form = {
    id: item.id,
    tenant_id: item.tenant_id || '',
    is_manual: !!item.is_manual,
    siswa_id: item.siswa_id || '',
    nama_alumni: item.is_manual ? item.nama_alumni : (item.siswa?.nama_lengkap || item.nama_alumni),
    nisn: item.is_manual ? item.nisn : (item.siswa?.nisn || item.nisn),
    nama_perusahaan: item.nama_perusahaan || '',
    posisi_jabatan: item.posisi_jabatan || item.posisi || '',
    jenis_instansi: item.jenis_instansi || 'Swasta',
    pendapatan_bulanan: item.pendapatan_bulanan || '',
    status_kerja: item.status_kerja || 'Tetap',
    tahun_mulai: item.tahun_mulai || currentYear,
    tahun_selesai: item.tahun_selesai || null,
  };
  modalPekerjaan.show = true;
};

const submitModalPekerjaan = async () => {
  modalPekerjaan.saving = true;
  try {
    if (modalPekerjaan.isEdit) {
      await axios.put(`/bk/alumni/pekerjaan/${modalPekerjaan.form.id}`, modalPekerjaan.form);
    } else {
      await axios.post('/bk/alumni/pekerjaan', modalPekerjaan.form);
    }
    modalPekerjaan.show = false;
    router.reload({ preserveScroll: true });
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.data?.error || 'Gagal menyimpan riwayat pekerjaan.';
    alert(msg);
  } finally {
    modalPekerjaan.saving = false;
  }
};

const quickAddPekerjaanForStudent = (student) => {
  openModalTambahPekerjaan();
  selectStudentForPekerjaan(student);
};

// =========================================================================
// MODAL DELETE KONFIRMASI
// =========================================================================
const modalDelete = reactive({
  show: false,
  type: 'kuliah', // 'kuliah' or 'pekerjaan'
  id: null,
  itemTitle: '',
  deleting: false,
});

const confirmDeleteKuliah = (item) => {
  modalDelete.type = 'kuliah';
  modalDelete.id = item.id;
  modalDelete.itemTitle = item.is_manual ? item.nama_alumni : (item.siswa?.nama_lengkap || item.nama_alumni);
  modalDelete.deleting = false;
  modalDelete.show = true;
};

const confirmDeletePekerjaan = (item) => {
  modalDelete.type = 'pekerjaan';
  modalDelete.id = item.id;
  modalDelete.itemTitle = item.is_manual ? item.nama_alumni : (item.siswa?.nama_lengkap || item.nama_alumni);
  modalDelete.deleting = false;
  modalDelete.show = true;
};

const executeDelete = async () => {
  modalDelete.deleting = true;
  try {
    if (modalDelete.type === 'kuliah') {
      await axios.delete(`/bk/alumni/kuliah/${modalDelete.id}`);
    } else {
      await axios.delete(`/bk/alumni/pekerjaan/${modalDelete.id}`);
    }
    modalDelete.show = false;
    router.reload({ preserveScroll: true });
  } catch (error) {
    alert('Gagal menghapus data: ' + (error.response?.data?.message || error.message));
  } finally {
    modalDelete.deleting = false;
  }
};
</script>

<style scoped>
/* Custom hide scrollbar */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
