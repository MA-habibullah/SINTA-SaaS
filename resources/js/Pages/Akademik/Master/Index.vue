<template>
  <AppLayout title="Master Data Kelembagaan & Akademik">
    <div class="space-y-6">
      
      <!-- 1. Header Row -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">
              <i class="bi bi-diagram-3-fill"></i>
            </span>
            Master Data Kelembagaan
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Kelola konfigurasi data pokok, kelas, jurusan, jenjang, mapel, dan tahun ajaran dalam satu atap.
          </p>
        </div>

        <!-- Tombol Toggle Tong Sampah & Tambah Data -->
        <div class="flex items-center gap-2 flex-wrap">
          <template v-if="activeTab === 'pemetaan_mapel' && !trashMode">
            <button 
              type="button" 
              @click="downloadTemplateJadwalDirect"
              class="px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition flex items-center gap-1.5"
              title="Unduh format template CSV/Excel untuk mengisi jadwal"
            >
              <i class="bi bi-file-earmark-arrow-down text-blue-600"></i>
              <span>Unduh Template</span>
            </button>

            <button 
              type="button" 
              @click="openImportJadwalModal"
              class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition flex items-center gap-1.5"
              title="Import data jadwal pelajaran dari file CSV/Excel"
            >
              <i class="bi bi-file-earmark-arrow-up"></i>
              <span>Import Jadwal</span>
            </button>

            <button 
              type="button" 
              @click="exportJadwalDirect"
              class="px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition flex items-center gap-1.5"
              title="Ekspor data jadwal CSV sesuai filter Tahun Ajaran & Semester aktif"
            >
              <i class="bi bi-file-earmark-text text-blue-600"></i>
              <span>Ekspor CSV</span>
            </button>

            <button 
              type="button" 
              @click="exportToExcelSheetJS"
              class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition flex items-center gap-1.5"
              title="Ekspor seluruh data jadwal saat ini langsung ke file Excel .xlsx"
            >
              <i class="bi bi-file-earmark-excel-fill"></i>
              <span>Ekspor Excel (.xlsx)</span>
            </button>

            <button 
              type="button" 
              @click="openCopyJadwalModal"
              class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold shadow-2xs transition flex items-center gap-1.5"
              title="Salin seluruh jadwal dari semester/tahun ajaran sebelumnya"
            >
              <i class="bi bi-copy text-indigo-600"></i>
              <span>Salin Antar-Semester</span>
            </button>
          </template>

          <button 
            type="button"
            @click="toggleTrashMode" 
            class="px-3.5 py-2 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 shadow-2xs"
            :class="trashMode ? 'bg-rose-600 text-white border-rose-600 hover:bg-rose-700 shadow-rose-500/20' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
          >
            <i class="bi" :class="trashMode ? 'bi-table' : 'bi-trash3'"></i>
            <span>{{ trashMode ? 'Kembali ke Data Aktif' : 'Lihat Tong Sampah' }}</span>
          </button>

          <button 
            v-if="!trashMode"
            type="button" 
            @click="openCreateModal"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5"
          >
            <i class="bi bi-plus-lg"></i>
            <span>Tambah {{ getActiveTabName() }}</span>
          </button>
        </div>
      </div>

      <!-- 2. Filter Sekolah Banner (Legacy Design Standard) -->
      <div 
        v-if="isSuperAdmin" 
        class="p-4 sm:px-5 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3"
      >
        <div class="flex flex-wrap items-center gap-2.5">
          <i class="bi bi-building text-blue-600 text-lg"></i>
          <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
            <i class="bi bi-funnel-fill me-1"></i> Aktif
          </span>

          <!-- Dropdown Filter Sekolah (Khusus Super Admin) -->
          <div class="my-1 md:my-0 w-64">
            <SearchableSelect 
              v-model="filterTenantId" 
              :options="tenantOptions"
              placeholder="-- Semua Sekolah (Global) --"
              search-placeholder="Cari nama sekolah..."
              @change="applyTenantFilter" 
            />
          </div>
        </div>

        <!-- Informational Text -->
        <div class="text-xs text-slate-500 font-medium">
          Menampilkan data milik: 
          <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
        </div>
      </div>

      <!-- 3. Navigation Tabs Modern SINTA (10 NavTabs with 3-Way Horizontal Scroller) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <!-- Tombol Panah Kiri -->
          <button 
            type="button" 
            class="w-[34px] h-[34px] rounded-xl border border-slate-200/80 shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition z-5" 
            onclick="document.getElementById('masterDataNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
            title="Geser ke Kiri"
          >
            <i class="bi bi-chevron-left"></i>
          </button>

          <!-- Deretan NavTab -->
          <div class="nav-tabs-wrapper grow overflow-hidden relative" @wheel.passive="handleNavWheel">
            <ul 
              class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar cursor-grab" 
              id="masterDataNavTabs" 
              role="tablist"
              @mousedown="startDrag"
              @mousemove="onDrag"
              @mouseup="stopDrag"
              @mouseleave="stopDrag"
            >
              <li class="nav-item" v-for="tab in tabs" :key="tab.id">
                <button 
                  type="button"
                  class="border-0 font-semibold px-3.5 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                  :class="activeTab === tab.id ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'" 
                  @click="switchTab(tab.id)"
                >
                  <i :class="tab.icon" class="text-sm"></i>
                  <span>{{ tab.name }}</span>
                </button>
              </li>
            </ul>
          </div>

          <!-- Tombol Panah Kanan -->
          <button 
            type="button" 
            class="w-[34px] h-[34px] rounded-xl border border-slate-200/80 shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition z-5" 
            onclick="document.getElementById('masterDataNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
            title="Geser ke Kanan"
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- 3.5. KPI Stats Cards & Quick Day Filter (Khusus Tab Jadwal & Pengampu) -->
      <template v-if="activeTab === 'pemetaan_mapel' && !trashMode">
        <!-- 4 KPI Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
          <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Jadwal KBM</div>
              <div class="text-xl sm:text-2xl font-black text-slate-800 mt-1 font-mono">
                {{ jadwalStats?.total_jadwal || items?.total || 0 }}
              </div>
              <div class="text-[10px] text-emerald-600 font-bold flex items-center gap-1 mt-0.5">
                <i class="bi bi-check-circle-fill"></i> Terpetakan Aktif
              </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold">
              <i class="bi bi-calendar3"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Guru Pengampu</div>
              <div class="text-xl sm:text-2xl font-black text-slate-800 mt-1 font-mono">
                {{ jadwalStats?.total_guru || listGuru?.length || 0 }}
              </div>
              <div class="text-[10px] text-slate-400 font-medium mt-0.5">Tenaga Pendidik</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold">
              <i class="bi bi-person-workspace"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Ruang Terpakai</div>
              <div class="text-xl sm:text-2xl font-black text-slate-800 mt-1 font-mono">
                {{ jadwalStats?.total_ruang || listRuangan?.length || 0 }}
              </div>
              <div class="text-[10px] text-emerald-600 font-bold mt-0.5">Ruang Kelas & Lab</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold">
              <i class="bi bi-door-open-fill"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Beban JP</div>
              <div class="text-xl sm:text-2xl font-black text-slate-800 mt-1 font-mono">
                {{ jadwalStats?.total_jp || 0 }} JP
              </div>
              <div class="text-[10px] text-slate-400 font-medium mt-0.5">Jam Pelajaran / Pekan</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
        </div>

        <!-- Quick Day Filter NavTabs -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-1.5 overflow-x-auto no-scrollbar">
          <div class="flex items-center gap-1.5 min-w-max">
            <button 
              v-for="h in ['Semua', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']" 
              :key="h"
              type="button" 
              @click="setQuickDay(h)"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
              :class="(filterHari === h || (h === 'Semua' && !filterHari)) ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-blue-600'"
            >
              <span v-if="h !== 'Semua'" class="w-1.5 h-1.5 rounded-full" :class="getDayDotColor(h)"></span>
              <i v-else class="bi bi-grid-fill text-xs"></i>
              <span>{{ h === 'Semua' ? 'Semua Hari' : h }}</span>
            </button>
          </div>
        </div>
      </template>

      <!-- 4. Main Datatable Card Box (Filter Bar + Tabel Data + Footer Pagination dalam 1 Box) -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        
        <!-- Header Card: Filter & Pencarian Bar (Standar Baku AGENTS) -->
        <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
          <form @submit.prevent="fetchData" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
            
            <!-- Filter Jenjang (Khusus Tab Kelas) -->
            <div class="w-44 shrink-0" v-if="activeTab === 'kelas'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Tingkat Jenjang</label>
              <SearchableSelect 
                v-model="filterJenjang" 
                :options="jenjangOptions"
                placeholder="-- Semua Jenjang --"
                search-placeholder="Cari jenjang..."
                @change="fetchData" 
              />
            </div>

            <!-- Filter Jurusan (Khusus Tab Kelas) -->
            <div class="w-44 shrink-0" v-if="activeTab === 'kelas'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Jurusan</label>
              <SearchableSelect 
                v-model="filterJurusan" 
                :options="jurusanOptions"
                placeholder="-- Semua Jurusan --"
                search-placeholder="Cari jurusan..."
                @change="fetchData" 
              />
            </div>

            <!-- Filter Tahun Ajaran (Khusus Tab Pemetaan Mapel / Jadwal) -->
            <div class="w-40 shrink-0" v-if="activeTab === 'pemetaan_mapel'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Tahun Ajaran</label>
              <SearchableSelect 
                v-model="filterTahunAjaran" 
                :options="tahunAjaranOptions"
                placeholder="-- Semua TA --"
                search-placeholder="Cari TA..."
                @change="fetchData" 
              />
            </div>

            <!-- Filter Semester (Khusus Tab Pemetaan Mapel / Jadwal) -->
            <div class="w-36 shrink-0" v-if="activeTab === 'pemetaan_mapel'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Semester</label>
              <SearchableSelect 
                v-model="filterSemester" 
                :options="semesterOptions"
                placeholder="-- Semua Semester --"
                search-placeholder="Cari semester..."
                @change="fetchData" 
              />
            </div>

            <!-- Filter Kelas (Khusus Tab Pemetaan Mapel / Jadwal) -->
            <div class="w-44 shrink-0" v-if="activeTab === 'pemetaan_mapel'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Kelas / Rombel</label>
              <SearchableSelect 
                v-model="filterKelas" 
                :options="kelasOptions"
                placeholder="-- Semua Kelas --"
                search-placeholder="Cari kelas..."
                @change="fetchData" 
              />
            </div>

            <!-- Filter Ruangan (Khusus Tab Pemetaan Mapel / Jadwal) -->
            <div class="w-40 shrink-0" v-if="activeTab === 'pemetaan_mapel'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Ruangan / Lab</label>
              <SearchableSelect 
                v-model="filterRuangan" 
                :options="ruanganOptions"
                placeholder="-- Semua Ruangan --"
                search-placeholder="Cari ruangan..."
                @change="fetchData" 
              />
            </div>

            <!-- Search Input (Proposional w-64 s.d. w-80) -->
            <div class="w-64 sm:w-72 md:w-80 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian</label>
              <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       v-model="searchQuery" 
                       @input="handleSearchDebounce"
                       placeholder="Cari kode, nama data, atau kategori..." 
                       class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" />
                <button v-if="searchQuery" 
                        @click="searchQuery = ''; fetchData()" 
                        type="button" 
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                        title="Hapus Pencarian">
                  <i class="bi bi-x-circle-fill text-xs"></i>
                </button>
              </div>
            </div>

            <!-- Tombol Cari & Reset -->
            <div class="flex items-center gap-1.5 shrink-0">
              <button type="submit" class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap">
                <i class="bi bi-search text-xs"></i> <span>Cari</span>
              </button>
              <button type="button" @click="resetFilters" class="h-9 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs transition shadow-2xs whitespace-nowrap" title="Reset Semua Filter">
                Reset
              </button>
            </div>
          </form>
        </div>

        <!-- Tabel Data Dinamis -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600 whitespace-nowrap min-w-[750px]">
            <thead class="bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-wider border-b border-slate-200">
              
              <!-- Head Table Khusus KELAS -->
              <tr v-if="activeTab === 'kelas'">
                <th class="py-3 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3 px-4 whitespace-nowrap">Kode Kelas</th>
                <th class="py-3 px-4 whitespace-nowrap">Nama Rombel / Kelas</th>
                <th class="py-3 px-4 whitespace-nowrap">Jenjang</th>
                <th class="py-3 px-4 whitespace-nowrap">Jurusan</th>
                <th class="py-3 px-4 text-center w-28 whitespace-nowrap">Status</th>
                <th class="py-3 px-4 text-center w-32 whitespace-nowrap">Aksi</th>
              </tr>

              <!-- Head Table Khusus TAHUN AJARAN & ANGKATAN -->
              <tr v-else-if="activeTab === 'tahun_ajaran' || activeTab === 'angkatan'">
                <th class="py-3 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ activeTab === 'tahun_ajaran' ? 'Tahun Ajaran' : 'Tahun Angkatan' }}</th>
                <th class="py-3 px-4 text-center w-28 whitespace-nowrap">Status</th>
                <th class="py-3 px-4 text-center w-32 whitespace-nowrap">Aksi</th>
              </tr>

              <!-- Head Table Khusus PEMETAAN MAPEL / JADWAL -->
              <tr v-else-if="activeTab === 'pemetaan_mapel'">
                <th class="py-3 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3 px-4 whitespace-nowrap">Tahun / Smt</th>
                <th class="py-3 px-4 whitespace-nowrap">Kelas / Rombel</th>
                <th class="py-3 px-4 whitespace-nowrap">Mata Pelajaran</th>
                <th class="py-3 px-4 whitespace-nowrap">Guru Pengampu</th>
                <th class="py-3 px-4 whitespace-nowrap">Hari & Waktu</th>
                <th class="py-3 px-4 whitespace-nowrap">Ruangan</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Jam / KKM</th>
                <th class="py-3 px-4 text-center w-28 whitespace-nowrap">Status</th>
                <th class="py-3 px-4 text-center w-32 whitespace-nowrap">Aksi</th>
              </tr>

              <!-- Head Table Khusus KURIKULUM -->
              <tr v-else-if="activeTab === 'kurikulum'">
                <th class="py-3 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th class="py-3 px-4 whitespace-nowrap">Cakupan Sekolah</th>
                <th class="py-3 px-4 whitespace-nowrap">Nama Kurikulum</th>
                <th class="py-3 px-4 whitespace-nowrap">Tipe Penilaian Rapor</th>
                <th class="py-3 px-4 text-center w-32 whitespace-nowrap">Status</th>
                <th class="py-3 px-4 text-center w-32 whitespace-nowrap">Aksi</th>
              </tr>

              <!-- Head Table GENERIK LAINNYA (Pendidikan, Jenjang, Jurusan, Mapel, Program Pengajaran) -->
              <tr v-else>
                <th class="py-3 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3 px-4 whitespace-nowrap">Kode</th>
                <th class="py-3 px-4 whitespace-nowrap">Nama Data</th>
                <th class="py-3 px-4 text-center w-28 whitespace-nowrap">Status</th>
                <th class="py-3 px-4 text-center w-32 whitespace-nowrap">Aksi</th>
              </tr>

            </thead>

            <tbody class="divide-y divide-slate-100 font-medium">
              
              <!-- 1. Baris Data KELAS -->
              <template v-if="activeTab === 'kelas'">
                <tr 
                  v-for="(item, idx) in items?.data || []" 
                  :key="item.id" 
                  class="hover:bg-blue-50/40 transition"
                  :class="{'bg-rose-50/50 text-slate-400': trashMode}"
                >
                  <td class="py-3.5 px-3 text-center font-mono text-slate-400 w-12">
                    {{ ((items?.current_page || 1) - 1) * (items?.per_page || 10) + idx + 1 }}
                  </td>
                  <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                    <div class="flex items-center gap-1.5">
                      <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0">
                        <i class="bi bi-building"></i>
                      </span>
                      <span class="font-bold text-slate-800">
                        {{ item.nama_sekolah || '-' }}
                      </span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-mono font-bold text-blue-600">
                    <span class="px-2 py-0.5 rounded-lg bg-blue-50 border border-blue-200/60 text-blue-700">
                      {{ item.kode_kelas || '-' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 font-bold text-slate-800">
                    {{ item.nama_kelas }}
                  </td>
                  <td class="py-3.5 px-4 text-slate-600">{{ item.nama_jenjang || '-' }}</td>
                  <td class="py-3.5 px-4 text-slate-600">{{ item.nama_jurusan || '-' }}</td>
                  <td class="py-3.5 px-4 text-center">
                    <!-- Toggle Switch -->
                    <label v-if="!trashMode" class="relative inline-flex items-center cursor-pointer">
                      <input 
                        type="checkbox" 
                        :checked="item.is_active" 
                        @change="toggleStatus(item)" 
                        class="sr-only peer"
                      />
                      <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                    <span v-else class="px-2 py-0.5 rounded text-2xs font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                      Terhapus
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5" v-if="!trashMode">
                      <button 
                        type="button" 
                        @click="openEditModal(item)" 
                        class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                        title="Edit Data"
                      >
                        <i class="bi bi-pencil-square text-sm"></i>
                      </button>
                      <button 
                        type="button" 
                        @click="deleteItem(item)" 
                        class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                        title="Hapus ke Tong Sampah"
                      >
                        <i class="bi bi-trash3 text-sm"></i>
                      </button>
                    </div>
                    <div class="flex items-center justify-center gap-1.5" v-else>
                      <button 
                        type="button" 
                        @click="restoreItem(item)" 
                        class="px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition flex items-center gap-1" 
                        title="Pulihkan Data"
                      >
                        <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- 2. Baris Data TAHUN AJARAN & ANGKATAN -->
              <template v-else-if="activeTab === 'tahun_ajaran' || activeTab === 'angkatan'">
                <tr 
                  v-for="(item, idx) in items?.data || []" 
                  :key="item.id" 
                  class="hover:bg-blue-50/40 transition"
                  :class="{'bg-rose-50/50 text-slate-400': trashMode}"
                >
                  <td class="py-3.5 px-3 text-center font-mono text-slate-400 w-12">
                    {{ ((items?.current_page || 1) - 1) * (items?.per_page || 10) + idx + 1 }}
                  </td>
                  <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                    <div class="flex items-center gap-1.5">
                      <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0">
                        <i class="bi bi-building"></i>
                      </span>
                      <span class="font-bold text-slate-800">
                        {{ item.nama_sekolah || '-' }}
                      </span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-mono font-bold text-slate-800 text-sm">
                    {{ activeTab === 'tahun_ajaran' ? item.tahun_ajaran : item.tahun_angkatan }}
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <label v-if="!trashMode" class="relative inline-flex items-center cursor-pointer">
                      <input 
                        type="checkbox" 
                        :checked="item.is_active" 
                        @change="toggleStatus(item)" 
                        class="sr-only peer"
                      />
                      <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                    <span v-else class="px-2 py-0.5 rounded text-2xs font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                      Terhapus
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5" v-if="!trashMode">
                      <button 
                        type="button" 
                        @click="openEditModal(item)" 
                        class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                        title="Edit Data"
                      >
                        <i class="bi bi-pencil-square text-sm"></i>
                      </button>
                      <button 
                        type="button" 
                        @click="deleteItem(item)" 
                        class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                        title="Hapus ke Tong Sampah"
                      >
                        <i class="bi bi-trash3 text-sm"></i>
                      </button>
                    </div>
                    <div class="flex items-center justify-center gap-1.5" v-else>
                      <button 
                        type="button" 
                        @click="restoreItem(item)" 
                        class="px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition flex items-center gap-1"
                      >
                        <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- 3. Baris Data KURIKULUM -->
              <template v-else-if="activeTab === 'kurikulum'">
                <tr 
                  v-for="(item, idx) in items?.data || []" 
                  :key="item.id" 
                  class="hover:bg-blue-50/40 transition"
                  :class="{'bg-rose-50/50 text-slate-400': trashMode}"
                >
                  <td class="py-3.5 px-3 text-center font-mono text-slate-400 w-12">
                    {{ ((items?.current_page || 1) - 1) * (items?.per_page || 10) + idx + 1 }}
                  </td>
                  <td class="py-3.5 px-4 font-semibold text-slate-700">
                    {{ item.is_system ? 'Sistem (Pemerintah)' : (item.nama_sekolah || 'Sistem (Pemerintah)') }}
                  </td>
                  <td class="py-3.5 px-4 font-bold text-slate-800">
                    <div class="flex items-center gap-2">
                      <span>{{ item.nama_kurikulum }}</span>
                      <span 
                        v-if="item.is_system" 
                        class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-200"
                      >
                        <i class="bi bi-shield-fill-check me-0.5"></i>Nasional
                      </span>
                      <span 
                        v-else 
                        class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-700 border border-amber-200"
                      >
                        <i class="bi bi-building me-0.5"></i>Kustom
                      </span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4">
                    <span 
                      class="px-2.5 py-1 rounded-full text-2xs font-extrabold border"
                      :class="getKurikulumCategoryClass(item)"
                    >
                      {{ getKurikulumCategoryLabel(item) }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <template v-if="!trashMode && !item.is_system">
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input 
                          type="checkbox" 
                          :checked="item.is_active" 
                          @change="toggleStatus(item)" 
                          class="sr-only peer"
                        />
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                      </label>
                    </template>
                    <span 
                      v-else-if="item.is_system" 
                      class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-teal-600 text-white"
                    >
                      Aktif Bawaan
                    </span>
                    <span v-else class="px-2 py-0.5 rounded text-2xs font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                      Terhapus
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5" v-if="!trashMode">
                      <template v-if="item.is_system">
                        <span class="text-slate-400 text-xs flex items-center gap-1 font-semibold">
                          <i class="bi bi-lock-fill"></i> Terkunci
                        </span>
                      </template>
                      <template v-else>
                        <button 
                          type="button" 
                          @click="openEditModal(item)" 
                          class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                          title="Edit Data"
                        >
                          <i class="bi bi-pencil-square text-sm"></i>
                        </button>
                        <button 
                          type="button" 
                          @click="deleteItem(item)" 
                          class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                          title="Hapus ke Tong Sampah"
                        >
                          <i class="bi bi-trash3 text-sm"></i>
                        </button>
                      </template>
                    </div>
                    <div class="flex items-center justify-center gap-1.5" v-else>
                      <button 
                        v-if="!item.is_system"
                        type="button" 
                        @click="restoreItem(item)" 
                        class="px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition flex items-center gap-1"
                        >
                        <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                      </button>
                      <span v-else class="text-slate-400 text-xs">-</span>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- 4. Baris Data PEMETAAN MAPEL / JADWAL -->
              <template v-else-if="activeTab === 'pemetaan_mapel'">
                <tr 
                  v-for="(item, idx) in items?.data || []" 
                  :key="item.id" 
                  class="hover:bg-blue-50/40 transition"
                  :class="{'bg-rose-50/50 text-slate-400': trashMode}"
                >
                  <td class="py-3.5 px-3 text-center font-mono text-slate-400 w-12">
                    {{ ((items?.current_page || 1) - 1) * (items?.per_page || 10) + idx + 1 }}
                  </td>
                  <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                    <div class="flex items-center gap-1.5">
                      <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0">
                        <i class="bi bi-building"></i>
                      </span>
                      <span class="font-bold text-slate-800">
                        {{ item.nama_sekolah || '-' }}
                      </span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-mono text-xs whitespace-nowrap">
                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-bold border border-slate-200 whitespace-nowrap">
                      {{ item.tahun_ajaran || '-' }} &bull; {{ item.semester || 'Ganjil' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 font-bold text-slate-800 whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 font-extrabold border border-blue-200/60 whitespace-nowrap">
                      {{ item.nama_kelas }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 font-bold text-slate-800">
                    <div>{{ item.nama_mapel }}</div>
                    <span v-if="item.kelompok_id" class="text-[10px] text-slate-400 font-medium">{{ item.kelompok_id }}</span>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ (item.nama_guru || 'G').charAt(0).toUpperCase() }}
                      </div>
                      <span class="font-bold text-slate-700">{{ item.nama_guru }}</span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 text-xs font-semibold text-slate-700 whitespace-nowrap">
                    <div v-if="item.hari || item.jam_mulai" class="flex items-center gap-1">
                      <i class="bi bi-clock-history text-blue-600"></i>
                      <span>{{ item.hari || 'Mingguan' }}{{ item.jam_mulai ? ', ' + item.jam_mulai + ' - ' + (item.jam_selesai || '') : '' }}</span>
                    </div>
                    <span v-else class="text-slate-400 italic">Belum diset</span>
                  </td>
                  <td class="py-3.5 px-4 text-xs font-medium text-slate-600 whitespace-nowrap">
                    {{ item.ruangan || '-' }}
                  </td>
                  <td class="py-3.5 px-4 text-center text-xs font-bold text-slate-700 whitespace-nowrap">
                    {{ item.jam_pelajaran || 2 }} JP / KKM: {{ item.kkm || 75 }}
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <span 
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border"
                      :class="getSessionStatusClass(item)"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="getSessionStatusDot(item)"></span>
                      {{ getSessionStatusText(item) }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1" v-if="!trashMode">
                      <button 
                        type="button" 
                        @click="openDetailJadwal(item)" 
                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                        title="Lihat Detail Jadwal"
                      >
                        <i class="bi bi-eye text-sm"></i>
                      </button>
                      <button 
                        type="button" 
                        @click="openEditModal(item)" 
                        class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" 
                        title="Edit Data"
                      >
                        <i class="bi bi-pencil-square text-sm"></i>
                      </button>
                      <button 
                        type="button" 
                        @click="deleteItem(item)" 
                        class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                        title="Hapus ke Tong Sampah"
                      >
                        <i class="bi bi-trash3 text-sm"></i>
                      </button>
                    </div>
                    <div class="flex items-center justify-center gap-1.5" v-else>
                      <button 
                        type="button" 
                        @click="restoreItem(item)" 
                        class="px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition flex items-center gap-1"
                      >
                        <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- 5. Baris Data GENERIK LAINNYA -->
              <template v-else>
                <tr 
                  v-for="(item, idx) in items?.data || []" 
                  :key="item.id" 
                  class="hover:bg-blue-50/40 transition"
                  :class="{'bg-rose-50/50 text-slate-400': trashMode}"
                >
                  <td class="py-3.5 px-3 text-center font-mono text-slate-400 w-12">
                    {{ ((items?.current_page || 1) - 1) * (items?.per_page || 10) + idx + 1 }}
                  </td>
                  <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                    <div class="flex items-center gap-1.5">
                      <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0">
                        <i class="bi bi-building"></i>
                      </span>
                      <span class="font-bold text-slate-800">
                        {{ item.nama_sekolah || '-' }}
                      </span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-mono font-bold text-blue-600">
                    <span class="px-2 py-0.5 rounded-lg bg-blue-50 border border-blue-200/60 text-blue-700">
                      {{ item.kode || '-' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 font-bold text-slate-800">
                    {{ item.nama }}
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <label v-if="!trashMode" class="relative inline-flex items-center cursor-pointer">
                      <input 
                        type="checkbox" 
                        :checked="item.is_active" 
                        @change="toggleStatus(item)" 
                        class="sr-only peer"
                      />
                      <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                    <span v-else class="px-2 py-0.5 rounded text-2xs font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                      Terhapus
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5" v-if="!trashMode">
                      <button 
                        type="button" 
                        @click="openEditModal(item)" 
                        class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                        title="Edit Data"
                      >
                        <i class="bi bi-pencil-square text-sm"></i>
                      </button>
                      <button 
                        type="button" 
                        @click="deleteItem(item)" 
                        class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                        title="Hapus ke Tong Sampah"
                      >
                        <i class="bi bi-trash3 text-sm"></i>
                      </button>
                    </div>
                    <div class="flex items-center justify-center gap-1.5" v-else>
                      <button 
                        type="button" 
                        @click="restoreItem(item)" 
                        class="px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition flex items-center gap-1"
                      >
                        <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-if="!items?.data || items.data.length === 0">
                <td :colspan="isSuperAdmin ? 8 : 7" class="text-center py-16 px-4">
                  <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-2xl mb-3">
                    <i class="bi bi-inbox"></i>
                  </div>
                  <h3 class="text-sm font-bold text-slate-700">Belum Ada Data {{ getActiveTabName() }}</h3>
                  
                  <div class="text-xs text-slate-400 max-w-md mx-auto mt-2 leading-relaxed">
                    <template v-if="activeTab === 'jenjang'">
                      <strong>Bentuk Pendidikan:</strong> SMA, SMK, SMP, SD, MA, MTs.<br />
                      <strong>Jenjang (Tingkat):</strong> 7, 8, 9, 10, 11, 12.
                    </template>
                    <template v-else-if="activeTab === 'jurusan'">
                      <strong>SMA:</strong> Umum, IPA, IPS, Bahasa.<br />
                      <strong>SMK:</strong> TKJ, RPL, AKL, DKV, TKR, TBSM.
                    </template>
                    <template v-else-if="activeTab === 'kelas'">
                      <strong>Contoh Rombel:</strong> VII A, VIII B, X IPA 1, XI TKJ 1, XII DKV 1.
                    </template>
                    <template v-else-if="activeTab === 'program_pengajaran'">
                      <strong>Contoh:</strong> PROG-REG-01 (Reguler 5 Hari Kerja), PROG-VOK-01 (Vokasi & Kelas Industri).
                    </template>
                    <template v-else>
                      Silakan klik tombol <strong>"Tambah {{ getActiveTabName() }}"</strong> di atas untuk menambahkan data baru.
                    </template>
                  </div>
                </td>
              </tr>

            </tbody>
          </table>
        </div>

        <!-- Footer Card: Table Pagination Bar (Menyatu dalam 1 Box & Responsif) -->
        <div 
          v-if="items?.total > 0" 
          class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80"
        >
          <!-- Info Tampilkan Baris -->
          <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
            <span>Tampilkan</span>
            <div class="w-20">
              <SearchableSelect 
                v-model="perPage" 
                :options="perPageOptions"
                placeholder="10"
                @change="fetchData" 
              />
            </div>
            <span class="whitespace-nowrap">baris per halaman</span>
            <span class="text-slate-300 hidden sm:inline">|</span>
            <span class="whitespace-nowrap">
              Menampilkan <span class="font-bold text-slate-800">{{ items.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ items.to || items.total }}</span> dari <span class="font-bold text-slate-800">{{ items.total }}</span> baris
            </span>
          </div>

          <!-- Pagination Links (Smart Windowing & Compact Chevrons) -->
          <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
            <template v-for="(link, i) in getSmartPaginationLinks(items)" :key="i">
              <button 
                v-if="link.url && !link.active" 
                type="button"
                @click="goToPage(link.url)"
                class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
                :title="link.isPrev ? 'Halaman Sebelumnya' : (link.isNext ? 'Halaman Berikutnya' : 'Halaman ' + link.label)"
              >
                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                <span v-else>{{ link.label }}</span>
              </button>
              <span 
                v-else-if="link.active"
                class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs"
              >
                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                <span v-else>{{ link.label }}</span>
              </span>
              <span 
                v-else 
                class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400"
              >
                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                <span v-else>{{ link.label }}</span>
              </span>
            </template>
          </div>
        </div>

      </div>

    </div>

    <!-- 5. Universal Form Modal (Tambah / Edit) -->
    <Teleport to="body">
      <div 
        v-if="openFormModal" 
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
      >
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200 relative z-10">
        
        <!-- Header Modal -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
              <i :class="isEditMode ? 'bi bi-pencil-square' : 'bi bi-plus-circle'"></i>
            </span>
            <h3 class="text-base font-bold text-slate-800">
              {{ isEditMode ? 'Edit ' + getActiveTabName() : 'Tambah ' + getActiveTabName() }}
            </h3>
          </div>
          <button 
            type="button" 
            @click="openFormModal = false" 
            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition"
          >
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitForm">
          <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            
            <!-- Input Sekolah khusus Super Admin -->
            <div v-if="isSuperAdmin">
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Sekolah / Tenant <span class="text-rose-500">*</span>
              </label>
              <SearchableSelect 
                v-model="formData.tenant_id" 
                :options="tenantOptions"
                :disabled="isEditMode" 
                placeholder="-- Pilih Sekolah --"
                search-placeholder="Cari sekolah..."
                @change="onModalTenantChange"
              />
            </div>

            <!-- Form Khusus KELAS -->
            <template v-if="activeTab === 'kelas'">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Bentuk Pendidikan / Jenjang <span class="text-rose-500">*</span>
                </label>
                <SearchableSelect 
                  v-model="formData.id_jenjang" 
                  :options="jenjangOptions"
                  placeholder="-- Pilih Bentuk Pendidikan (SMA/SMK/SMP/SD) --"
                  search-placeholder="Cari jenjang..."
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Jurusan / Program Keahlian <span class="text-rose-500">*</span>
                </label>
                <SearchableSelect 
                  v-model="formData.id_jurusan" 
                  :options="jurusanOptions"
                  placeholder="-- Pilih Jurusan (IPA/IPS/TKJ/RPL/Umum) --"
                  search-placeholder="Cari jurusan..."
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Kode Kelas <span class="text-rose-500">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="formData.kode_kelas" 
                  placeholder="Contoh: KLS-XIPA1, KLS-XRPL1, KLS-7A" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                  required 
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Nama Kelas / Rombel <span class="text-rose-500">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="formData.nama_kelas" 
                  placeholder="Contoh: VII A, VIII B, X IPA 1, XI TKJ 1, XII DKV 1" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                  required 
                />
              </div>
            </template>

            <!-- Form Khusus TAHUN AJARAN -->
            <template v-else-if="activeTab === 'tahun_ajaran'">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Tahun Ajaran <span class="text-rose-500">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="formData.kode" 
                  placeholder="Contoh: 2025/2026 atau 2026/2027" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 font-mono" 
                  required 
                />
                <p class="text-[11px] text-slate-400 mt-1">Gunakan format YYYY/YYYY (contoh: 2026/2027).</p>
              </div>
            </template>

            <!-- Form Khusus ANGKATAN -->
            <template v-else-if="activeTab === 'angkatan'">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Tahun Angkatan <span class="text-rose-500">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="formData.kode" 
                  placeholder="Contoh: 2026" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 font-mono" 
                  required 
                />
                <p class="text-[11px] text-slate-400 mt-1">Masukkan 4 digit angka tahun masuk siswa (misal: 2026).</p>
              </div>
            </template>

            <!-- Form Khusus PEMETAAN MAPEL / JADWAL -->
            <template v-else-if="activeTab === 'pemetaan_mapel'">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Kelas / Rombongan Belajar <span class="text-rose-500">*</span>
                </label>
                <SearchableSelect 
                  v-model="formData.kelas_id" 
                  :options="kelasOptions"
                  placeholder="-- Pilih Kelas --"
                  search-placeholder="Cari kelas..."
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Mata Pelajaran <span class="text-rose-500">*</span>
                </label>
                <SearchableSelect 
                  v-model="formData.mapel_id" 
                  :options="mapelOptions"
                  placeholder="-- Pilih Mata Pelajaran --"
                  search-placeholder="Cari mata pelajaran..."
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Guru Pengampu <span class="text-rose-500">*</span>
                </label>
                <SearchableSelect 
                  v-model="formData.guru_id" 
                  :options="guruOptions"
                  placeholder="-- Pilih Guru Pengampu --"
                  search-placeholder="Cari guru..."
                />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                  <SearchableSelect 
                    v-model="formData.tahun_ajaran" 
                    :options="tahunAjaranOptions"
                    placeholder="-- Pilih Tahun Ajaran --"
                    search-placeholder="Cari TA..."
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Semester <span class="text-rose-500">*</span></label>
                  <SearchableSelect 
                    v-model="formData.semester" 
                    :options="semesterOptions"
                    placeholder="-- Pilih Semester --"
                    search-placeholder="Cari semester..."
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Jam Pelajaran (JP)</label>
                  <input 
                    type="number" 
                    v-model.number="formData.jam_pelajaran" 
                    min="1" 
                    max="10" 
                    placeholder="2" 
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">KKM / Kriteria Minimum</label>
                  <input 
                    type="number" 
                    v-model.number="formData.kkm" 
                    min="0" 
                    max="100" 
                    step="0.1" 
                    placeholder="75" 
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Hari Pelajaran</label>
                  <SearchableSelect 
                    v-model="formData.hari" 
                    :options="hariOptions"
                    placeholder="-- Pilih Hari (Opsional) --"
                    search-placeholder="Cari hari..."
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Ruang Kelas / Lab</label>
                  <input 
                    type="text" 
                    v-model="formData.ruangan" 
                    placeholder="Contoh: R. 101, Lab Komputer" 
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai</label>
                  <input 
                    type="text" 
                    v-model="formData.jam_mulai" 
                    placeholder="Contoh: 07:30" 
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 font-mono" 
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai</label>
                  <input 
                    type="text" 
                    v-model="formData.jam_selesai" 
                    placeholder="Contoh: 09:00" 
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 font-mono" 
                  />
                </div>
              </div>
            </template>

            <!-- Form Khusus KURIKULUM -->
            <template v-else-if="activeTab === 'kurikulum'">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Nama Kurikulum <span class="text-rose-500">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="formData.nama_kurikulum" 
                  placeholder="Contoh: Kurikulum Merdeka Mandiri, K-13 Plus" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                  required 
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Tipe Penilaian / Rapor <span class="text-rose-500">*</span>
                </label>
                <SearchableSelect 
                  v-model="formData.tipe_penilaian" 
                  :options="tipePenilaianOptions"
                  placeholder="-- Pilih Tipe Penilaian --"
                  search-placeholder="Cari tipe..."
                />
              </div>
            </template>

            <!-- Form GENERIK (Pendidikan, Jenjang, Jurusan, Mapel, Program Pengajaran) -->
            <template v-else>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Kode {{ getActiveTabName() }} <span class="text-rose-500">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="formData.kode" 
                  :placeholder="activeTab === 'jenjang' ? 'Contoh Kode: SMA, SMK, SMP, SD' : activeTab === 'jurusan' ? 'Contoh: IPA, IPS, RPL, TKJ' : 'Masukkan kode...'" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 font-mono" 
                  required 
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Nama {{ getActiveTabName() }} <span class="text-rose-500">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="formData.nama" 
                  :placeholder="activeTab === 'jenjang' ? 'Contoh: Sekolah Menengah Atas' : activeTab === 'jurusan' ? 'Contoh: Rekayasa Perangkat Lunak' : 'Masukkan nama...'" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                  required 
                />
              </div>

              <!-- Input Khusus MAPEL (Kelompok, KKM, Urutan) -->
              <div v-if="activeTab === 'mata_pelajaran'" class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Kelompok Mapel</label>
                  <SearchableSelect 
                    v-model="formData.kelompok" 
                    :options="kelompokMapelOptions"
                    placeholder="-- Pilih Kelompok --"
                    search-placeholder="Cari kelompok..."
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Urutan Tampil</label>
                  <input 
                    type="number" 
                    v-model.number="formData.urutan" 
                    min="1" 
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                  />
                </div>
              </div>

              <div v-if="activeTab === 'mata_pelajaran'">
                <label class="block text-xs font-bold text-slate-700 mb-1">KKM / Kriteria Ketercapaian (KKTP)</label>
                <input 
                  type="number" 
                  v-model.number="formData.kkm" 
                  min="0" 
                  max="100" 
                  step="0.1" 
                  placeholder="Contoh: 75" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" 
                />
              </div>
            </template>

            <!-- Deskripsi Tambahan (Opsional) -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan / Deskripsi</label>
              <textarea 
                v-model="formData.deskripsi" 
                rows="2" 
                placeholder="Catatan tambahan (opsional)..." 
                class="w-full p-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              ></textarea>
            </div>

            <!-- Status Aktif Toggle -->
            <div class="flex items-center gap-3 pt-2">
              <input 
                type="checkbox" 
                id="modalIsActive" 
                v-model="formData.is_active" 
                class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" 
              />
              <label for="modalIsActive" class="text-xs font-bold text-slate-700 select-none cursor-pointer">
                Data Aktif & Digunakan dalam Sistem
              </label>
            </div>

          </div>

          <!-- Footer Modal Actions -->
          <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <button 
              type="button" 
              @click="openFormModal = false" 
              class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-600 rounded-xl text-xs font-bold border border-slate-200 transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="submitLoading"
              class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5"
            >
              <i v-if="submitLoading" class="bi bi-arrow-repeat animate-spin"></i>
              <span>{{ isEditMode ? 'Simpan Perubahan' : 'Simpan Data' }}</span>
            </button>
          </div>
        </form>

      </div>
    </div>
  </Teleport>

  <!-- 6. Modal Import Jadwal Pelajaran (CSV/Excel) -->
  <Teleport to="body">
    <div 
      v-if="openImportModal" 
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
    >
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200 relative z-10">
        
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-emerald-600 to-teal-700 text-white">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-white/20 text-white flex items-center justify-center font-bold text-sm">
              <i class="bi bi-file-earmark-arrow-up"></i>
            </span>
            <div>
              <h3 class="text-sm font-bold text-white">Import Jadwal & Pemetaan Mapel</h3>
              <p class="text-[11px] text-emerald-100">Upload file CSV/Excel untuk memproses jadwal massal</p>
            </div>
          </div>
          <button 
            type="button" 
            @click="openImportModal = false" 
            class="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition"
          >
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="submitImportJadwal">
          <div class="p-6 space-y-4">
            
            <!-- Download Template Box -->
            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between gap-3">
              <div class="flex items-center gap-2.5">
                <i class="bi bi-info-circle-fill text-emerald-600 text-lg"></i>
                <div>
                  <div class="text-xs font-bold text-emerald-900">Gunakan Format Template Resmi</div>
                  <div class="text-[11px] text-emerald-700">Pastikan urutan kolom sesuai template agar impor lancar</div>
                </div>
              </div>
              <button 
                type="button" 
                @click="downloadTemplateJadwalDirect"
                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shrink-0"
              >
                <i class="bi bi-download"></i> Template
              </button>
            </div>

            <!-- Input File CSV -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Pilih File CSV / Excel (.csv, .txt) <span class="text-rose-500">*</span>
              </label>
              <input 
                type="file" 
                ref="importFileInput"
                accept=".csv,.txt" 
                @change="handleFileSelect"
                class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-xl p-1" 
                required
              />
            </div>

            <!-- Target Periode Akademik -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran Target</label>
                <SearchableSelect 
                  v-model="importTargetTA" 
                  :options="tahunAjaranOptions"
                  placeholder="-- Ikuti Baris File CSV --"
                  search-placeholder="Cari TA..."
                />
                <p class="text-[10px] text-slate-400 mt-0.5">Pilih untuk mengunci tahun ajaran seluruh baris</p>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Semester Target</label>
                <SearchableSelect 
                  v-model="importTargetSem" 
                  :options="semesterOptions"
                  placeholder="-- Ikuti File --"
                  search-placeholder="Cari semester..."
                />
              </div>
            </div>

            <!-- Mode Import -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Metode Pengisian</label>
              <div class="grid grid-cols-2 gap-2">
                <label class="p-2.5 rounded-xl border text-xs font-semibold flex items-center gap-2 cursor-pointer transition"
                       :class="importMode === 'append' ? 'border-emerald-600 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 text-slate-700'">
                  <input type="radio" v-model="importMode" value="append" class="text-emerald-600" />
                  <span>Tambahkan (Append)</span>
                </label>
                <label class="p-2.5 rounded-xl border text-xs font-semibold flex items-center gap-2 cursor-pointer transition"
                       :class="importMode === 'replace' ? 'border-rose-600 bg-rose-50 text-rose-900 font-bold' : 'border-slate-200 text-slate-700'">
                  <input type="radio" v-model="importMode" value="replace" class="text-rose-600" />
                  <span>Ganti Baru (Replace)</span>
                </label>
              </div>
            </div>

          </div>

          <!-- Footer Modal -->
          <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <button 
              type="button" 
              @click="openImportModal = false" 
              class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-600 rounded-xl text-xs font-bold border border-slate-200 transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="isImporting"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition flex items-center gap-1.5"
            >
              <i v-if="isImporting" class="bi bi-arrow-repeat animate-spin"></i>
              <span>{{ isImporting ? 'Memproses...' : 'Mulai Import Jadwal' }}</span>
            </button>
          </div>
        </form>

      </div>
    </div>
  </Teleport>

  <!-- 7. Modal Salin Jadwal Antar-Semester / Periode -->
  <Teleport to="body">
    <div 
      v-if="openCopyModal" 
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
    >
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200 relative z-10">
        
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-indigo-600 to-purple-700 text-white">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-white/20 text-white flex items-center justify-center font-bold text-sm">
              <i class="bi bi-copy"></i>
            </span>
            <div>
              <h3 class="text-sm font-bold text-white">Salin Jadwal Antar-Semester</h3>
              <p class="text-[11px] text-indigo-100">Kloning pemetaan mapel dari periode sebelumnya</p>
            </div>
          </div>
          <button 
            type="button" 
            @click="openCopyModal = false" 
            class="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition"
          >
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="submitCopyJadwal">
          <div class="p-6 space-y-4">
            
            <!-- Sumber Jadwal -->
            <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-200/80 space-y-2.5">
              <div class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                <i class="bi bi-box-arrow-up-right text-indigo-600"></i>
                Periode Sumber (Jadwal yang Disalin):
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">Tahun Ajaran Asal</label>
                  <SearchableSelect 
                    v-model="copyFromTA" 
                    :options="tahunAjaranOptions"
                    placeholder="Pilih TA Asal"
                    search-placeholder="Cari TA..."
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">Semester Asal</label>
                  <SearchableSelect 
                    v-model="copyFromSem" 
                    :options="semesterOptions"
                    placeholder="Pilih Semester Asal"
                    search-placeholder="Cari semester..."
                  />
                </div>
              </div>
            </div>

            <!-- Target Jadwal -->
            <div class="p-3.5 rounded-2xl bg-purple-50/70 border border-purple-200/80 space-y-2.5">
              <div class="text-xs font-bold text-purple-900 flex items-center gap-1.5">
                <i class="bi bi-box-arrow-in-down-left text-purple-600"></i>
                Periode Target (Tujuan Salin):
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">Tahun Ajaran Baru</label>
                  <SearchableSelect 
                    v-model="copyToTA" 
                    :options="tahunAjaranOptions"
                    placeholder="Pilih TA Baru"
                    search-placeholder="Cari TA..."
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">Semester Baru</label>
                  <SearchableSelect 
                    v-model="copyToSem" 
                    :options="semesterOptions"
                    placeholder="Pilih Semester Baru"
                    search-placeholder="Cari semester..."
                  />
                </div>
              </div>
            </div>

            <!-- Mode Salin -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Metode Salin</label>
              <div class="grid grid-cols-2 gap-2">
                <label class="p-2.5 rounded-xl border text-xs font-semibold flex items-center gap-2 cursor-pointer transition"
                       :class="copyMode === 'append' ? 'border-indigo-600 bg-indigo-50 text-indigo-900 font-bold' : 'border-slate-200 text-slate-700'">
                  <input type="radio" v-model="copyMode" value="append" class="text-indigo-600" />
                  <span>Tambahkan (Append)</span>
                </label>
                <label class="p-2.5 rounded-xl border text-xs font-semibold flex items-center gap-2 cursor-pointer transition"
                       :class="copyMode === 'replace' ? 'border-rose-600 bg-rose-50 text-rose-900 font-bold' : 'border-slate-200 text-slate-700'">
                  <input type="radio" v-model="copyMode" value="replace" class="text-rose-600" />
                  <span>Ganti Bersih (Replace)</span>
                </label>
              </div>
            </div>

          </div>

          <!-- Footer Modal -->
          <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <button 
              type="button" 
              @click="openCopyModal = false" 
              class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-600 rounded-xl text-xs font-bold border border-slate-200 transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="isCopying"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition flex items-center gap-1.5"
            >
              <i v-if="isCopying" class="bi bi-arrow-repeat animate-spin"></i>
              <span>{{ isCopying ? 'Menyalin...' : 'Salin Jadwal Sekarang' }}</span>
            </button>
          </div>
        </form>

      </div>
    </div>
  </Teleport>

  <!-- 8. Modal Detail Jadwal Pelajaran -->
  <Teleport to="body">
    <div 
      v-if="openDetailModal && selectedDetailJadwal" 
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
    >
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200 relative z-10">
        
        <!-- Header Modal Detail -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-white/20 text-white flex items-center justify-center font-bold text-sm">
              <i class="bi bi-info-circle-fill"></i>
            </span>
            <div>
              <h3 class="text-sm font-bold text-white">Detail Jadwal Pelajaran</h3>
              <p class="text-[11px] text-blue-100">Informasi lengkap alokasi KBM & guru pengampu</p>
            </div>
          </div>
          <button 
            type="button" 
            @click="openDetailModal = false; selectedDetailJadwal = null" 
            class="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition"
          >
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <!-- Body Modal Detail -->
        <div class="p-6 space-y-4">
          
          <!-- Banner Mata Pelajaran & Kelas -->
          <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-100 flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Mata Pelajaran</div>
              <div class="text-base font-black text-slate-800 mt-0.5">
                {{ selectedDetailJadwal.nama_mapel }}
              </div>
              <div class="text-xs text-slate-500 font-medium mt-0.5">
                {{ selectedDetailJadwal.kelompok_id || 'Kelompok Wajib / Umum' }}
              </div>
            </div>
            <div class="text-right">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kelas</div>
              <span class="inline-block mt-0.5 px-3 py-1 rounded-xl bg-blue-600 text-white font-black text-xs shadow-xs">
                {{ selectedDetailJadwal.nama_kelas }}
              </span>
            </div>
          </div>

          <!-- Grid Informasi -->
          <div class="grid grid-cols-2 gap-3.5">
            
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i class="bi bi-person-workspace text-indigo-600"></i> Guru Pengampu
              </div>
              <div class="text-xs font-bold text-slate-800 mt-1">
                {{ selectedDetailJadwal.nama_guru || '-' }}
              </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i class="bi bi-calendar3 text-blue-600"></i> Tahun Ajaran / Smt
              </div>
              <div class="text-xs font-bold text-slate-800 mt-1 font-mono">
                {{ selectedDetailJadwal.tahun_ajaran || '-' }} ({{ selectedDetailJadwal.semester || 'Ganjil' }})
              </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i class="bi bi-clock-history text-amber-600"></i> Hari & Jam KBM
              </div>
              <div class="text-xs font-bold text-slate-800 mt-1">
                {{ selectedDetailJadwal.hari || 'Belum diatur' }}
                <span v-if="selectedDetailJadwal.jam_mulai" class="text-slate-500 font-normal">
                  ({{ selectedDetailJadwal.jam_mulai }} - {{ selectedDetailJadwal.jam_selesai || '' }})
                </span>
              </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i class="bi bi-door-open-fill text-emerald-600"></i> Lokasi Ruangan
              </div>
              <div class="text-xs font-bold text-slate-800 mt-1">
                {{ selectedDetailJadwal.ruangan || 'Ruang Kelas Reguler' }}
              </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i class="bi bi-hourglass-split text-purple-600"></i> Alokasi JP
              </div>
              <div class="text-xs font-bold text-slate-800 mt-1">
                {{ selectedDetailJadwal.jam_pelajaran || 2 }} JP / Pekan
              </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i class="bi bi-award-fill text-rose-600"></i> KKM Mapel
              </div>
              <div class="text-xs font-bold text-slate-800 mt-1">
                {{ selectedDetailJadwal.kkm || 75 }} Poin
              </div>
            </div>

          </div>

          <!-- Status & ID Tag -->
          <div class="p-3 rounded-xl bg-slate-100/70 flex items-center justify-between text-xs">
            <span class="text-slate-500 font-mono text-[10px]">ID: {{ selectedDetailJadwal.id }}</span>
            <span 
              class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
              :class="selectedDetailJadwal.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
            >
              <i class="bi" :class="selectedDetailJadwal.is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
              {{ selectedDetailJadwal.is_active ? 'Status: Aktif' : 'Status: Non-Aktif' }}
            </span>
          </div>

        </div>

        <!-- Footer Modal Detail -->
        <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
          <button 
            type="button" 
            @click="openEditModal(selectedDetailJadwal); openDetailModal = false" 
            class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-bold border border-blue-200 transition flex items-center gap-1.5"
          >
            <i class="bi bi-pencil-square"></i> Edit Jadwal
          </button>
          <button 
            type="button" 
            @click="openDetailModal = false; selectedDetailJadwal = null" 
            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition"
          >
            Tutup
          </button>
        </div>

      </div>
    </div>
  </Teleport>

  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js';
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  activeTab: String,
  items: Object,
  tenants: Array,
  listJenjang: Array,
  listJurusan: Array,
  listKelas: Array,
  listMapel: Array,
  listGuru: Array,
  listTahunAjaran: Array,
  listRuangan: Array,
  jadwalStats: Object,
  isSuperAdmin: Boolean,
  userRole: String,
  filters: Object,
});

const localItems = ref(props.items || null);
const localTenants = ref(props.tenants || []);
const localListJenjang = ref(props.listJenjang || []);
const localListJurusan = ref(props.listJurusan || []);
const localListKelas = ref(props.listKelas || []);
const localListMapel = ref(props.listMapel || []);
const localListGuru = ref(props.listGuru || []);
const localListTahunAjaran = ref(props.listTahunAjaran || []);
const localListRuangan = ref(props.listRuangan || []);
const localJadwalStats = ref(props.jadwalStats || {});
const isFetching = ref(false);

const items = computed(() => localItems.value || props.items || { data: [], total: 0 });

// Computed Select Options for Standardized SearchableSelect
const tenantOptions = computed(() => {
  const list = localTenants.value.length > 0 ? localTenants.value : (props.tenants || []);
  return list.map(t => ({
    id: t.id,
    nama: t.nama_sekolah,
    subLabel: t.npsn ? `NPSN: ${t.npsn}` : undefined,
  }));
});

const jenjangOptions = computed(() => {
  const list = localListJenjang.value.length > 0 ? localListJenjang.value : (props.listJenjang || []);
  return list.map(j => ({
    id: j.id,
    nama: j.nama_jenjang || j.nama,
    subLabel: j.kode_jenjang || j.kode || undefined,
  }));
});

const jurusanOptions = computed(() => {
  const list = localListJurusan.value.length > 0 ? localListJurusan.value : (props.listJurusan || []);
  return list.map(j => ({
    id: j.id,
    nama: j.nama_jurusan || j.nama,
    subLabel: j.kategori || j.kode || undefined,
  }));
});

const tahunAjaranOptions = computed(() => {
  const list = localListTahunAjaran.value.length > 0 ? localListTahunAjaran.value : (props.listTahunAjaran || []);
  return list.map(ta => ({
    id: ta.nama,
    nama: ta.nama,
  }));
});

const semesterOptions = [
  { id: 'Ganjil', nama: 'Ganjil' },
  { id: 'Genap', nama: 'Genap' },
];

const kelasOptions = computed(() => {
  const list = localListKelas.value.length > 0 ? localListKelas.value : (props.listKelas || []);
  return list.map(k => ({
    id: k.id,
    nama: k.nama_kelas,
    subLabel: k.kode_kelas ? `Kode: ${k.kode_kelas}` : undefined,
  }));
});

const mapelOptions = computed(() => {
  const list = localListMapel.value.length > 0 ? localListMapel.value : (props.listMapel || []);
  return list.map(m => ({
    id: m.id,
    nama: m.nama_mata_pelajaran || m.nama,
    subLabel: m.kategori ? `Kategori: ${m.kategori}` : undefined,
  }));
});

const guruOptions = computed(() => {
  const list = localListGuru.value.length > 0 ? localListGuru.value : (props.listGuru || []);
  return list.map(g => ({
    id: g.id,
    nama: g.nama_lengkap || g.nama,
    subLabel: g.email || undefined,
  }));
});

const ruanganOptions = computed(() => {
  const list = localListRuangan.value.length > 0 ? localListRuangan.value : (props.listRuangan || []);
  return list.map(r => ({
    id: r,
    nama: r,
  }));
});

const tipePenilaianOptions = [
  { id: 'sederhana', nama: 'Sederhana (Merdeka - Nilai Akhir & Capaian)' },
  { id: 'klasik', nama: 'Klasik (KTSP - Kognitif, Psikomotorik, Afektif)' },
  { id: 'kompleks', nama: 'Kompleks (K-13 - KI-3 & KI-4)' },
];

const kelompokMapelOptions = [
  { id: 'A', nama: 'Kelompok A (Umum)' },
  { id: 'B', nama: 'Kelompok B (Umum)' },
  { id: 'C', nama: 'Kelompok C (Peminatan/Kejuruan)' },
  { id: 'MULOK', nama: 'Muatan Lokal (Mulok)' },
];

const hariOptions = [
  { id: 'Senin', nama: 'Senin' },
  { id: 'Selasa', nama: 'Selasa' },
  { id: 'Rabu', nama: 'Rabu' },
  { id: 'Kamis', nama: 'Kamis' },
  { id: 'Jumat', nama: 'Jumat' },
  { id: 'Sabtu', nama: 'Sabtu' },
];

const perPageOptions = [
  { id: 10, nama: '10' },
  { id: 15, nama: '15' },
  { id: 25, nama: '25' },
  { id: 50, nama: '50' },
  { id: 100, nama: '100' },
];

// 10 Master Tabs
const tabs = [
  { id: 'pendidikan', name: 'Pendidikan', icon: 'bi bi-award-fill' },
  { id: 'jenjang', name: 'Jenjang', icon: 'bi bi-award' },
  { id: 'jurusan', name: 'Jurusan', icon: 'bi bi-diagram-3' },
  { id: 'kelas', name: 'Kelas', icon: 'bi bi-mortarboard' },
  { id: 'mata_pelajaran', name: 'Mata Pelajaran', icon: 'bi bi-book' },
  { id: 'pemetaan_mapel', name: 'Jadwal & Pengampu', icon: 'bi bi-calendar-range-fill' },
  { id: 'program_pengajaran', name: 'Program Pengajaran', icon: 'bi bi-journal-text' },
  { id: 'tahun_ajaran', name: 'Tahun Ajaran', icon: 'bi bi-calendar-check' },
  { id: 'angkatan', name: 'Angkatan', icon: 'bi bi-calendar2-range' },
  { id: 'kurikulum', name: 'Kurikulum', icon: 'bi bi-gear-wide-connected' },
];

const activeTab = ref(props.activeTab || 'pendidikan');
const filterTenantId = ref(props.filters?.tenant_id || '');
const filterJenjang = ref(props.filters?.jenjang_id || '');
const filterJurusan = ref(props.filters?.jurusan_id || '');
const filterTahunAjaran = ref(props.filters?.tahun_ajaran || '');
const filterSemester = ref(props.filters?.semester || '');
const filterKelas = ref(props.filters?.kelas_id || '');
const filterHari = ref(props.filters?.hari || '');
const filterRuangan = ref(props.filters?.ruangan || '');
const searchQuery = ref(props.filters?.search || '');
const trashMode = ref(props.filters?.trash || false);
const perPage = ref(props.filters?.per_page || 10);

const fetchDataAsync = async () => {
  isFetching.value = true;
  try {
    const res = await axios.get('/master-data', {
      params: {
        async: 1,
        tab: activeTab.value,
        search: searchQuery.value,
        tenant_id: filterTenantId.value,
        jenjang_id: filterJenjang.value,
        jurusan_id: filterJurusan.value,
        tahun_ajaran: filterTahunAjaran.value,
        semester: filterSemester.value,
        kelas_id: filterKelas.value,
        hari: filterHari.value,
        ruangan: filterRuangan.value,
        trash: trashMode.value ? 1 : 0,
        per_page: perPage.value,
      },
      headers: {
        'Accept': 'application/json',
      },
    });
    if (res.data?.success) {
      localItems.value = res.data.data;
      if (res.data.tenants) localTenants.value = res.data.tenants;
      if (res.data.listJenjang) localListJenjang.value = res.data.listJenjang;
      if (res.data.listJurusan) localListJurusan.value = res.data.listJurusan;
      if (res.data.listKelas) localListKelas.value = res.data.listKelas;
      if (res.data.listMapel) localListMapel.value = res.data.listMapel;
      if (res.data.listGuru) localListGuru.value = res.data.listGuru;
      if (res.data.listTahunAjaran) localListTahunAjaran.value = res.data.listTahunAjaran;
      if (res.data.listRuangan) localListRuangan.value = res.data.listRuangan;
      if (res.data.jadwalStats) localJadwalStats.value = res.data.jadwalStats;
    }
  } catch (err) {
    console.error('Failed to load async master-data:', err);
  } finally {
    isFetching.value = false;
  }
};

watch(() => props.items, (newVal) => {
  if (newVal) localItems.value = newVal;
});

// Modal states
const openFormModal = ref(false);
const isEditMode = ref(false);
const editId = ref(null);
const submitLoading = ref(false);

const openDetailModal = ref(false);
const selectedDetailJadwal = ref(null);

const openImportModal = ref(false);
const isImporting = ref(false);
const importFile = ref(null);
const importTargetTA = ref('');
const importTargetSem = ref('');
const importMode = ref('append');
const importFileInput = ref(null);

const openCopyModal = ref(false);
const isCopying = ref(false);
const copyFromTA = ref(props.listTahunAjaran?.[1]?.nama || props.listTahunAjaran?.[0]?.nama || '2025/2026');
const copyFromSem = ref('Ganjil');
const copyToTA = ref(props.listTahunAjaran?.[0]?.nama || '2026/2027');
const copyToSem = ref('Ganjil');
const copyMode = ref('append');

const formData = reactive({
  tenant_id: '',
  kode: '',
  nama: '',
  kode_kelas: '',
  nama_kelas: '',
  id_jenjang: '',
  id_jurusan: '',
  nama_kurikulum: '',
  tipe_penilaian: 'sederhana',
  kelas_id: '',
  mapel_id: '',
  guru_id: '',
  tahun_ajaran: props.listTahunAjaran?.[0]?.nama || '2026/2027',
  semester: 'Ganjil',
  kelompok_id: 'Kelompok A (Umum)',
  jam_pelajaran: 2,
  kkm: 75,
  hari: '',
  jam_mulai: '',
  jam_selesai: '',
  ruangan: '',
  is_active: true,
});

// 3-Way Horizontal Scroller NavTabs Drag Logic
let isDown = false;
let startX = 0;
let scrollLeft = 0;

const startDrag = (e) => {
  const slider = document.getElementById('masterDataNavTabs');
  if (!slider) return;
  isDown = true;
  startX = e.pageX - slider.offsetLeft;
  scrollLeft = slider.scrollLeft;
};

const stopDrag = () => {
  isDown = false;
};

const onDrag = (e) => {
  if (!isDown) return;
  e.preventDefault();
  const slider = document.getElementById('masterDataNavTabs');
  if (!slider) return;
  const x = e.pageX - slider.offsetLeft;
  const walk = (x - startX) * 1.5;
  slider.scrollLeft = scrollLeft - walk;
};

const handleNavWheel = (e) => {
  const slider = document.getElementById('masterDataNavTabs');
  if (!slider) return;
  if (e.deltaY !== 0) {
    slider.scrollLeft += e.deltaY;
  }
};

// Filter & Navigation Handlers
let searchDebounceTimer = null;
const handleSearchDebounce = () => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    fetchData();
  }, 400);
};

const switchTab = (tabId) => {
  activeTab.value = tabId;
  trashMode.value = false;
  searchQuery.value = '';
  filterJenjang.value = '';
  filterJurusan.value = '';
  fetchData();
};

const toggleTrashMode = () => {
  trashMode.value = !trashMode.value;
  fetchData();
};

const applyTenantFilter = () => {
  fetchData();
};

const resetTenantFilter = () => {
  filterTenantId.value = '';
  fetchData();
};

const resetFilters = () => {
  searchQuery.value = '';
  filterJenjang.value = '';
  filterJurusan.value = '';
  filterTahunAjaran.value = '';
  filterSemester.value = '';
  filterKelas.value = '';
  filterHari.value = '';
  filterRuangan.value = '';
  fetchData();
};

const fetchData = () => {
  router.get('/master-data', {
    tab: activeTab.value,
    tenant_id: filterTenantId.value || undefined,
    jenjang_id: activeTab.value === 'kelas' ? (filterJenjang.value || undefined) : undefined,
    jurusan_id: activeTab.value === 'kelas' ? (filterJurusan.value || undefined) : undefined,
    tahun_ajaran: activeTab.value === 'pemetaan_mapel' ? (filterTahunAjaran.value || undefined) : undefined,
    semester: activeTab.value === 'pemetaan_mapel' ? (filterSemester.value || undefined) : undefined,
    kelas_id: activeTab.value === 'pemetaan_mapel' ? (filterKelas.value || undefined) : undefined,
    hari: activeTab.value === 'pemetaan_mapel' ? (filterHari.value || undefined) : undefined,
    ruangan: activeTab.value === 'pemetaan_mapel' ? (filterRuangan.value || undefined) : undefined,
    search: searchQuery.value || undefined,
    trash: trashMode.value ? 'true' : undefined,
    per_page: perPage.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const goToPage = (url) => {
  if (!url) return;
  router.visit(url, { preserveState: true, preserveScroll: true });
};

const getSmartPaginationLinks = (pagination) => {
  if (!pagination?.links || pagination.links.length === 0) return [];
  const rawLinks = pagination.links;
  const prevLink = rawLinks[0];
  const nextLink = rawLinks[rawLinks.length - 1];
  const pageLinks = rawLinks.slice(1, -1);
  const current = pagination.current_page || 1;
  const last = pagination.last_page || (pageLinks.length ? Number(pageLinks[pageLinks.length - 1].label) || 1 : 1);

  const result = [];
  result.push({
    ...prevLink,
    isPrev: true,
    isNext: false,
    label: prevLink.label,
  });

  if (last <= 7) {
    pageLinks.forEach(l => {
      result.push({
        ...l,
        isPrev: false,
        isNext: false,
        label: l.label,
      });
    });
  } else {
    const pagesToShow = new Set([1, last]);
    for (let p = current - 1; p <= current + 1; p++) {
      if (p >= 1 && p <= last) pagesToShow.add(p);
    }
    const sortedPages = Array.from(pagesToShow).sort((a, b) => a - b);
    let prevPage = null;
    sortedPages.forEach(p => {
      if (prevPage !== null && p - prevPage > 1) {
        result.push({
          label: '...',
          url: null,
          active: false,
          isPrev: false,
          isNext: false,
        });
      }
      const foundRaw = pageLinks.find(l => l.label == p.toString());
      result.push({
        label: p.toString(),
        url: foundRaw ? foundRaw.url : null,
        active: p === current,
        isPrev: false,
        isNext: false,
      });
      prevPage = p;
    });
  }

  result.push({
    ...nextLink,
    isPrev: false,
    isNext: true,
    label: nextLink.label,
  });

  return result;
};

const getActiveTabName = () => {
  const tab = tabs.find(t => t.id === activeTab.value);
  return tab ? tab.name : 'Data';
};

const getSelectedTenantName = () => {
  if (!filterTenantId.value) return 'Semua Sekolah Terdaftar (Super Admin)';
  const t = (props.tenants || []).find(t => t.id === filterTenantId.value);
  return t ? t.nama_sekolah : 'Semua Sekolah Terdaftar (Super Admin)';
};

// Kurikulum helpers
const getKurikulumCategoryLabel = (item) => {
  const kat = (item.tipe_penilaian || item.kategori || '').toLowerCase();
  const name = (item.nama_kurikulum || '').toLowerCase();
  if (kat === 'kompleks' || name.includes('2013') || name.includes('k-13') || name.includes('vokasi')) {
    return 'Kompleks (K-13)';
  } else if (kat === 'klasik' || name.includes('ktsp') || name.includes('kbk')) {
    return 'Klasik (KTSP)';
  } else {
    return 'Sederhana (Merdeka)';
  }
};

const getKurikulumCategoryClass = (item) => {
  const label = getKurikulumCategoryLabel(item);
  if (label.includes('Kompleks')) {
    return 'bg-rose-50 text-rose-700 border-rose-200';
  } else if (label.includes('Klasik')) {
    return 'bg-blue-50 text-blue-700 border-blue-200';
  } else {
    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  }
};

// CRUD Modal Actions
const resetForm = () => {
  formData.tenant_id = filterTenantId.value || (props.tenants?.[0]?.id || '');
  formData.kode = '';
  formData.nama = '';
  formData.kode_kelas = '';
  formData.nama_kelas = '';
  formData.id_jenjang = props.listJenjang?.[0]?.id || '';
  formData.id_jurusan = props.listJurusan?.[0]?.id || '';
  formData.nama_kurikulum = '';
  formData.tipe_penilaian = 'sederhana';
  formData.kelas_id = props.listKelas?.[0]?.id || '';
  formData.mapel_id = props.listMapel?.[0]?.id || '';
  formData.guru_id = props.listGuru?.[0]?.id || '';
  formData.tahun_ajaran = filterTahunAjaran.value || props.listTahunAjaran?.[0]?.nama || '2026/2027';
  formData.semester = filterSemester.value || 'Ganjil';
  formData.kelompok_id = 'Kelompok A (Umum)';
  formData.jam_pelajaran = 2;
  formData.kkm = 75;
  formData.hari = '';
  formData.jam_mulai = '';
  formData.jam_selesai = '';
  formData.ruangan = '';
  formData.is_active = true;
};

const openCreateModal = () => {
  isEditMode.value = false;
  editId.value = null;
  resetForm();
  openFormModal.value = true;
};

const openEditModal = (item) => {
  isEditMode.value = true;
  editId.value = item.id;
  
  formData.tenant_id = item.tenant_id || '';
  formData.kode = item.kode || item.kode_kelas || item.kode_jenjang || item.kode_program || item.tahun_ajaran || item.tahun_angkatan || '';
  formData.nama = item.nama || item.nama_kelas || item.nama_jenjang || item.nama_jurusan || item.nama_mata_pelajaran || item.nama_program || '';
  formData.kode_kelas = item.kode_kelas || item.kode || '';
  formData.nama_kelas = item.nama_kelas || item.nama || '';
  formData.id_jenjang = item.id_jenjang || '';
  formData.id_jurusan = item.id_jurusan || '';
  formData.nama_kurikulum = item.nama_kurikulum || item.nama || '';
  formData.tipe_penilaian = item.tipe_penilaian || item.kategori || 'sederhana';
  formData.kelas_id = item.kelas_id || '';
  formData.mapel_id = item.mapel_id || '';
  formData.guru_id = item.guru_id || '';
  formData.tahun_ajaran = item.tahun_ajaran || '2026/2027';
  formData.semester = item.semester || 'Ganjil';
  formData.kelompok_id = item.kelompok_id || 'Kelompok A (Umum)';
  formData.jam_pelajaran = item.jam_pelajaran || 2;
  formData.kkm = item.kkm || 75;
  formData.hari = item.hari || '';
  formData.jam_mulai = item.jam_mulai || '';
  formData.jam_selesai = item.jam_selesai || '';
  formData.ruangan = item.ruangan || '';
  formData.is_active = item.is_active !== undefined ? item.is_active : true;
  
  openFormModal.value = true;
};

const onModalTenantChange = async () => {
  if (activeTab.value === 'kelas' && formData.tenant_id) {
    try {
      const res = await axios.get('/master-data/options', { params: { tenant_id: formData.tenant_id } });
      if (res.data?.success) {
        if (res.data.data.jenjang?.length > 0) formData.id_jenjang = res.data.data.jenjang[0].id;
        if (res.data.data.jurusan?.length > 0) formData.id_jurusan = res.data.data.jurusan[0].id;
      }
    } catch (e) {
      console.error(e);
    }
  }
};

const submitForm = () => {
  submitLoading.value = true;
  
  const payload = {
    tab: activeTab.value,
    tenant_id: formData.tenant_id,
    kode: formData.kode,
    nama: formData.nama,
    kode_kelas: formData.kode_kelas,
    nama_kelas: formData.nama_kelas,
    id_jenjang: formData.id_jenjang,
    id_jurusan: formData.id_jurusan,
    nama_kurikulum: formData.nama_kurikulum,
    tipe_penilaian: formData.tipe_penilaian,
    kelas_id: formData.kelas_id,
    mapel_id: formData.mapel_id,
    guru_id: formData.guru_id,
    tahun_ajaran: formData.tahun_ajaran,
    semester: formData.semester,
    kelompok_id: formData.kelompok_id,
    jam_pelajaran: formData.jam_pelajaran,
    kkm: formData.kkm,
    hari: formData.hari,
    jam_mulai: formData.jam_mulai,
    jam_selesai: formData.jam_selesai,
    ruangan: formData.ruangan,
    is_active: formData.is_active,
  };

  const url = isEditMode.value ? `/master-data/update/${editId.value}` : '/master-data/store';

  router.post(url, payload, {
    onSuccess: () => {
      submitLoading.value = false;
      openFormModal.value = false;
      fetchData();
    },
    onError: (err) => {
      submitLoading.value = false;
      alert('Gagal menyimpan: ' + (Object.values(err)[0] || 'Terjadi kesalahan'));
    },
  });
};

const toggleStatus = async (item) => {
  try {
    const res = await axios.post(`/master-data/toggle-status/${item.id}`, { tab: activeTab.value });
    if (res.data?.success) {
      item.is_active = res.data.is_active;
    }
  } catch (e) {
    console.error(e);
    alert('Gagal mengubah status aktif');
  }
};

const deleteItem = (item) => {
  if (!confirm(`Apakah Anda yakin ingin memindahkan data "${item.nama || item.nama_kelas || item.nama_kurikulum || 'ini'}" ke tong sampah?`)) {
    return;
  }
  router.post(`/master-data/destroy/${item.id}`, { tab: activeTab.value }, {
    onSuccess: () => fetchData(),
  });
};

const restoreItem = (item) => {
  router.post(`/master-data/restore/${item.id}`, { tab: activeTab.value }, {
    onSuccess: () => fetchData(),
  });
};

// JADWAL EXPORT, IMPORT & COPY ACTIONS
const downloadTemplateJadwalDirect = () => {
  window.location.href = '/akademik/jadwal/template';
};

const exportJadwalDirect = () => {
  const params = new URLSearchParams();
  if (filterTahunAjaran.value) params.append('tahun_ajaran', filterTahunAjaran.value);
  if (filterSemester.value) params.append('semester', filterSemester.value);
  if (filterKelas.value) params.append('kelas_id', filterKelas.value);
  if (filterTenantId.value) params.append('tenant_id', filterTenantId.value);
  window.location.href = '/akademik/jadwal/export?' + params.toString();
};

const openImportJadwalModal = () => {
  importTargetTA.value = filterTahunAjaran.value || '2026/2027';
  importTargetSem.value = filterSemester.value || 'Ganjil';
  importFile.value = null;
  importMode.value = 'append';
  if (importFileInput.value) {
    importFileInput.value.value = '';
  }
  openImportModal.value = true;
};

const handleFileSelect = (e) => {
  importFile.value = e.target.files[0] || null;
};

const submitImportJadwal = async () => {
  if (!importFile.value) {
    alert('Silakan pilih file CSV/Excel terlebih dahulu.');
    return;
  }

  isImporting.value = true;
  try {
    const fd = new FormData();
    fd.append('file', importFile.value);
    if (importTargetTA.value) fd.append('tahun_ajaran', importTargetTA.value);
    if (importTargetSem.value) fd.append('semester', importTargetSem.value);
    fd.append('mode', importMode.value);
    if (filterTenantId.value) fd.append('tenant_id', filterTenantId.value);

    const res = await axios.post('/akademik/jadwal/import', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data?.success) {
      alert(res.data.message || 'Import jadwal berhasil.');
      openImportModal.value = false;
      fetchData();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal memproses import file.');
  } finally {
    isImporting.value = false;
  }
};

const openCopyJadwalModal = () => {
  copyFromTA.value = '2025/2026';
  copyFromSem.value = 'Ganjil';
  copyToTA.value = filterTahunAjaran.value || '2026/2027';
  copyToSem.value = filterSemester.value || 'Ganjil';
  copyMode.value = 'append';
  openCopyModal.value = true;
};

const submitCopyJadwal = async () => {
  isCopying.value = true;
  try {
    const payload = {
      from_tahun_ajaran: copyFromTA.value,
      from_semester: copyFromSem.value,
      to_tahun_ajaran: copyToTA.value,
      to_semester: copyToSem.value,
      mode: copyMode.value,
      tenant_id: filterTenantId.value || undefined,
    };

    const res = await axios.post('/akademik/jadwal/copy', payload);
    if (res.data?.success) {
      alert(res.data.message || 'Salin jadwal berhasil.');
      openCopyModal.value = false;
      fetchData();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyalin jadwal.');
  } finally {
    isCopying.value = false;
  }
};

// QUICK DAY FILTER & BADGE HELPERS
const setQuickDay = (h) => {
  filterHari.value = (h === 'Semua' || h === filterHari.value) ? '' : h;
  fetchData();
};

const getDayDotColor = (h) => {
  const map = {
    'Senin': 'bg-blue-500',
    'Selasa': 'bg-emerald-500',
    'Rabu': 'bg-purple-500',
    'Kamis': 'bg-amber-500',
    'Jumat': 'bg-rose-500',
    'Sabtu': 'bg-cyan-500',
    'Minggu': 'bg-slate-500',
  };
  return map[h] || 'bg-slate-400';
};

const getDayName = (dayIndex) => {
  const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  return days[dayIndex] || '';
};

const getSessionStatusText = (item) => {
  if (!item.hari) return 'Terjadwal';
  const now = new Date();
  const currentDay = getDayName(now.getDay());
  if (item.hari.toLowerCase() === currentDay.toLowerCase()) {
    return 'Hari Ini (Aktif)';
  }
  return 'Akan Datang';
};

const getSessionStatusClass = (item) => {
  const status = getSessionStatusText(item);
  if (status === 'Hari Ini (Aktif)') {
    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  }
  return 'bg-slate-100 text-slate-600 border-slate-200';
};

const getSessionStatusDot = (item) => {
  const status = getSessionStatusText(item);
  if (status === 'Hari Ini (Aktif)') {
    return 'bg-emerald-500 animate-pulse';
  }
  return 'bg-slate-400';
};

const openDetailJadwal = (item) => {
  selectedDetailJadwal.value = item;
  openDetailModal.value = true;
};

// EXPORT JADWAL TO EXCEL (.XLSX) VIA SHEETJS
const exportToExcelSheetJS = () => {
  const rawData = props.items?.data || [];
  if (rawData.length === 0) {
    alert('Tidak ada data jadwal untuk diekspor pada filter aktif.');
    return;
  }

  if (typeof window.XLSX === 'undefined') {
    // Fallback direct CSV export
    exportJadwalDirect();
    return;
  }

  const excelRows = rawData.map((item, idx) => ({
    'No': idx + 1,
    'Tahun Ajaran': item.tahun_ajaran || filterTahunAjaran.value || '',
    'Semester': item.semester || filterSemester.value || 'Ganjil',
    'Kelas / Rombel': item.nama_kelas || item.kelas?.nama_kelas || '',
    'Kode Mapel': item.kode || item.mapel?.kode || '',
    'Mata Pelajaran': item.nama_mapel || item.mapel?.nama_mata_pelajaran || '',
    'Kelompok Mapel': item.kelompok_id || '',
    'Guru Pengampu': item.nama_guru || item.guru?.nama || '',
    'Hari': item.hari || '-',
    'Jam Mulai': item.jam_mulai || '-',
    'Jam Selesai': item.jam_selesai || '-',
    'Ruangan': item.ruangan || '-',
    'Beban JP': item.jam_pelajaran || 2,
    'KKM': item.kkm || 75,
    'Status': item.is_active ? 'Aktif' : 'Non-Aktif',
  }));

  const worksheet = window.XLSX.utils.json_to_sheet(excelRows);
  
  // Auto-fit column widths
  worksheet['!cols'] = [
    { wch: 5 },  // No
    { wch: 14 }, // Tahun Ajaran
    { wch: 10 }, // Semester
    { wch: 18 }, // Kelas
    { wch: 12 }, // Kode Mapel
    { wch: 30 }, // Mata Pelajaran
    { wch: 22 }, // Kelompok
    { wch: 28 }, // Guru
    { wch: 10 }, // Hari
    { wch: 12 }, // Jam Mulai
    { wch: 12 }, // Jam Selesai
    { wch: 16 }, // Ruangan
    { wch: 10 }, // Beban JP
    { wch: 8 },  // KKM
    { wch: 12 }, // Status
  ];

  const workbook = window.XLSX.utils.book_new();
  window.XLSX.utils.book_append_sheet(workbook, worksheet, 'Jadwal Pelajaran');

  const taLabel = (filterTahunAjaran.value || 'Semua_TA').replace(/[^a-zA-Z0-9]/g, '_');
  const semLabel = filterSemester.value || 'Semua_Semester';
  const filename = `Jadwal_Pelajaran_${taLabel}_${semLabel}_${new Date().toISOString().slice(0, 10)}.xlsx`;

  window.XLSX.writeFile(workbook, filename);
};

useMemorySecurity([
  localItems,
  formData,
  importTargetTA,
  importTargetSem,
  searchQuery,
  filterTenantId,
  filterJenjang,
  filterJurusan,
  filterTahunAjaran,
  filterSemester,
  filterKelas,
  filterHari,
  filterRuangan,
  selectedDetailJadwal,
]);

onMounted(() => {
  if (!props.items || !props.items.data || localListJenjang.value.length === 0) {
    fetchDataAsync();
  }
});
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
