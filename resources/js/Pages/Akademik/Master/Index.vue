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
          <div class="my-1 md:my-0">
            <select 
              v-model="filterTenantId" 
              @change="applyTenantFilter" 
              class="h-9 px-3 bg-white border border-blue-200 rounded-xl text-xs font-semibold text-slate-800 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[220px]"
            >
              <option value="">-- Semua Sekolah (Global) --</option>
              <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
            </select>
          </div>
        </div>

        <!-- Informational Text -->
        <div class="text-xs text-slate-500 font-medium">
          Menampilkan data milik: 
          <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
        </div>
      </div>

      <!-- 3. Navigation Tabs Modern SINTA (9 NavTabs with 3-Way Horizontal Scroller) -->
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

          <!-- Deretan 9 NavTab -->
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

      <!-- 4. Main Datatable Card Box (Filter Bar + Tabel Data + Footer Pagination dalam 1 Box) -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        
        <!-- Header Card: Filter & Pencarian Bar (Standar Baku AGENTS) -->
        <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
          <form @submit.prevent="fetchData" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
            
            <!-- Filter Jenjang (Khusus Tab Kelas) -->
            <div class="w-36 sm:w-40 shrink-0" v-if="activeTab === 'kelas'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Tingkat Jenjang</label>
              <select v-model="filterJenjang" @change="fetchData" class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                <option value="">-- Semua Jenjang --</option>
                <option v-for="j in listJenjang" :key="j.id" :value="j.id">{{ j.nama_jenjang || j.nama }}</option>
              </select>
            </div>

            <!-- Filter Jurusan (Khusus Tab Kelas) -->
            <div class="w-36 sm:w-40 shrink-0" v-if="activeTab === 'kelas'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Jurusan</label>
              <select v-model="filterJurusan" @change="fetchData" class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                <option value="">-- Semua Jurusan --</option>
                <option v-for="j in listJurusan" :key="j.id" :value="j.id">{{ j.nama_jurusan || j.nama }}</option>
              </select>
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

              <!-- 4. Baris Data GENERIK LAINNYA -->
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
            <select v-model="perPage" @change="fetchData" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
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
              <select 
                v-model="formData.tenant_id" 
                :disabled="isEditMode" 
                @change="onModalTenantChange"
                class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                required
              >
                <option value="" disabled>-- Pilih Sekolah --</option>
                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
              </select>
            </div>

            <!-- Form Khusus KELAS -->
            <template v-if="activeTab === 'kelas'">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Bentuk Pendidikan / Jenjang <span class="text-rose-500">*</span>
                </label>
                <select 
                  v-model="formData.id_jenjang" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                  required
                >
                  <option value="" disabled>-- Pilih Bentuk Pendidikan (SMA/SMK/SMP/SD) --</option>
                  <option v-for="j in listJenjang" :key="j.id" :value="j.id">{{ j.nama_jenjang || j.nama }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Jurusan / Program Keahlian <span class="text-rose-500">*</span>
                </label>
                <select 
                  v-model="formData.id_jurusan" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                  required
                >
                  <option value="" disabled>-- Pilih Jurusan (IPA/IPS/TKJ/RPL/Umum) --</option>
                  <option v-for="j in listJurusan" :key="j.id" :value="j.id">{{ j.nama_jurusan || j.nama }}</option>
                </select>
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
                <select 
                  v-model="formData.tipe_penilaian" 
                  class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                  required
                >
                  <option value="sederhana">Sederhana (Merdeka - Nilai Akhir & Deskripsi Capaian)</option>
                  <option value="klasik">Klasik (KTSP - Kognitif, Psikomotorik, Afektif)</option>
                  <option value="kompleks">Kompleks (K-13 - Pengetahuan KI-3 & Keterampilan KI-4)</option>
                </select>
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
                  <select 
                    v-model="formData.kelompok" 
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                  >
                    <option value="A">Kelompok A (Umum)</option>
                    <option value="B">Kelompok B (Umum)</option>
                    <option value="C">Kelompok C (Peminatan/Kejuruan)</option>
                    <option value="MULOK">Muatan Lokal (Mulok)</option>
                  </select>
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

  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  activeTab: String,
  items: Object,
  tenants: Array,
  listJenjang: Array,
  listJurusan: Array,
  isSuperAdmin: Boolean,
  userRole: String,
  filters: Object,
});

// 9 Master Tabs
const tabs = [
  { id: 'pendidikan', name: 'Pendidikan', icon: 'bi bi-award-fill' },
  { id: 'jenjang', name: 'Jenjang', icon: 'bi bi-award' },
  { id: 'jurusan', name: 'Jurusan', icon: 'bi bi-diagram-3' },
  { id: 'kelas', name: 'Kelas', icon: 'bi bi-mortarboard' },
  { id: 'mata_pelajaran', name: 'Mata Pelajaran', icon: 'bi bi-book' },
  { id: 'program_pengajaran', name: 'Program Pengajaran', icon: 'bi bi-journal-text' },
  { id: 'tahun_ajaran', name: 'Tahun Ajaran', icon: 'bi bi-calendar-check' },
  { id: 'angkatan', name: 'Angkatan', icon: 'bi bi-calendar2-range' },
  { id: 'kurikulum', name: 'Kurikulum', icon: 'bi bi-gear-wide-connected' },
];

const activeTab = ref(props.activeTab || 'pendidikan');
const filterTenantId = ref(props.filters?.tenant_id || '');
const filterJenjang = ref(props.filters?.jenjang_id || '');
const filterJurusan = ref(props.filters?.jurusan_id || '');
const searchQuery = ref(props.filters?.search || '');
const trashMode = ref(props.filters?.trash || false);
const perPage = ref(props.filters?.per_page || 10);

// Modal state
const openFormModal = ref(false);
const isEditMode = ref(false);
const editId = ref(null);
const submitLoading = ref(false);

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
  fetchData();
};

const fetchData = () => {
  router.get('/master-data', {
    tab: activeTab.value,
    tenant_id: filterTenantId.value || undefined,
    jenjang_id: activeTab.value === 'kelas' ? (filterJenjang.value || undefined) : undefined,
    jurusan_id: activeTab.value === 'kelas' ? (filterJurusan.value || undefined) : undefined,
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
