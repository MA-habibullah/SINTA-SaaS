<template>
  <AppLayout title="Manajemen Pengguna">
    <div class="space-y-5 pb-10">
      
      <!-- Flash Alert Notification -->
      <div v-if="flashMessage" 
           class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-2xs animate-fade-in">
        <div class="flex items-center gap-3">
          <i class="bi bi-check-circle-fill text-emerald-600 text-lg"></i>
          <span class="text-xs font-bold">{{ flashMessage }}</span>
        </div>
        <button @click="flashMessage = ''" class="text-emerald-500 hover:text-emerald-700 text-sm"><i class="bi bi-x-lg"></i></button>
      </div>

      <!-- 1. Header & Actions Bar -->
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-xl shadow-md shadow-blue-500/20">
              <i class="bi bi-people-fill"></i>
            </span>
            <div>
              <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Manajemen Pengguna</h1>
              <p class="text-xs text-slate-500 mt-0.5">
                Kelola data akademik dan non-akademik sekolah (Siswa, Guru, Karyawan, dan Operator) secara terintegrasi.
              </p>
            </div>
          </div>
        </div>

        <!-- Action Bar Buttons -->
        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto" v-if="activeTab !== 'profile_rapot'">
          <!-- Toggle Trash Mode -->
          <button @click="toggleTrash" 
                  :class="[
                    'px-3.5 py-2 text-xs font-bold rounded-xl border transition flex items-center gap-1.5 shadow-2xs',
                    filters.trash 
                      ? 'bg-red-600 text-white border-red-600 shadow-xs hover:bg-red-700' 
                      : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                  ]">
            <i :class="['bi', filters.trash ? 'bi-table' : 'bi-trash3']"></i>
            <span>{{ filters.trash ? 'Kembali ke Data Aktif' : 'Lihat Tong Sampah' }}</span>
          </button>

          <!-- Download / Export Excel (Tab Siswa) -->
          <a v-if="activeTab === 'siswa' && !filters.trash"
             :href="`/pengguna/export-excel?tab=siswa${filterTenantId ? '&tenant_id=' + filterTenantId : ''}`" 
             target="_blank"
             class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
            <i class="bi bi-download text-blue-600"></i>
            <span>Download Excel</span>
          </a>

          <!-- Import Siswa via Excel Modal Trigger -->
          <button v-if="activeTab === 'siswa' && !filters.trash" 
                  @click="openImportModal = true"
                  class="px-3.5 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
            <i class="bi bi-file-earmark-excel-fill text-emerald-600"></i>
            <span>Import Siswa</span>
          </button>

          <!-- Registrasi Cepat Siswa -->
          <button v-if="activeTab === 'siswa' && !filters.trash" 
                  @click="openQuickAddModal = true"
                  class="px-3.5 py-2 text-xs font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
            <i class="bi bi-lightning-fill text-amber-600"></i>
            <span>Registrasi Cepat</span>
          </button>

          <!-- Tambah Siswa Halaman Lengkap (Khusus Tab Siswa) -->
          <Link v-if="activeTab === 'siswa' && !filters.trash" 
                href="/siswa/tambah"
                class="px-4 py-2 text-xs font-extrabold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
            <i class="bi bi-person-plus-fill"></i>
            <span>Tambah Siswa Lengkap</span>
          </Link>

          <!-- Tambah Data Modal Trigger (Untuk Guru, Karyawan, Operator) -->
          <button v-if="!filters.trash && activeTab !== 'siswa' && activeTab !== 'naikkan_kelas' && activeTab !== 'mutasi'" 
                  @click="openAddModal"
                  class="px-4 py-2 text-xs font-extrabold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah {{ getActiveTabTitle() }}</span>
          </button>
        </div>
      </div>

      <!-- 2. Filter Sekolah Banner (Legacy Design Standard) -->
      <div class="p-4 sm:px-5 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5">
          <i class="bi bi-building text-blue-600 text-lg"></i>
          <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
            <i class="bi bi-funnel-fill me-1"></i> Aktif
          </span>

          <!-- Dropdown Filter Sekolah (Khusus Super Admin) -->
          <div v-if="isSuperAdmin" class="my-1 md:my-0">
            <select v-model="filterTenantId" 
                    @change="applyFilters"
                    class="h-9 px-3 bg-white border border-blue-200 rounded-xl text-xs font-semibold text-slate-800 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[220px]">
              <option value="">-- Semua Sekolah (Global) --</option>
              <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
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

      <!-- 3. Statistics Summary Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold shrink-0">
            <i class="bi bi-mortarboard"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Siswa Aktif</p>
            <p class="text-base sm:text-lg font-black text-slate-800">{{ stats?.total_siswa || 0 }}</p>
          </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold shrink-0">
            <i class="bi bi-person-badge"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Tenaga Pendidik</p>
            <p class="text-base sm:text-lg font-black text-slate-800">{{ stats?.total_guru || 0 }}</p>
          </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold shrink-0">
            <i class="bi bi-briefcase"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Karyawan / TU</p>
            <p class="text-base sm:text-lg font-black text-slate-800">{{ stats?.total_karyawan || 0 }}</p>
          </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold shrink-0">
            <i class="bi bi-person-gear"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Operator Sistem</p>
            <p class="text-base sm:text-lg font-black text-slate-800">{{ stats?.total_operator || 0 }}</p>
          </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3 col-span-2 sm:col-span-1">
          <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-bold shrink-0">
            <i class="bi bi-person-x"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Log Mutasi</p>
            <p class="text-base sm:text-lg font-black text-slate-800">{{ stats?.total_mutasi || 0 }}</p>
          </div>
        </div>
      </div>

      <!-- 4. Navigation Tabs Modern (7 Tabs Sentral) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <!-- Tombol Panah Kiri -->
          <button type="button" 
                  class="hidden md:flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shrink-0 mr-1.5 shadow-2xs z-5" 
                  @click="scrollTabs(-220)"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left text-xs"></i>
          </button>

          <!-- Container Deretan Tab -->
          <div class="grow overflow-hidden relative">
            <ul id="penggunaSubNavTabs" class="flex gap-1.5 overflow-x-auto scrollable-nav-tabs py-0.5 px-1 whitespace-nowrap select-none no-scrollbar">
              <li v-for="tab in tabList" :key="tab.id">
                <button type="button"
                        @click="switchTab(tab.id)"
                        :class="[
                          'inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition border border-transparent',
                          activeTab === tab.id
                            ? 'bg-blue-600 text-white shadow-xs' 
                            : 'text-slate-600 hover:bg-slate-100'
                        ]">
                  <i :class="[tab.icon, 'text-sm']"></i>
                  <span>{{ tab.name }}</span>
                </button>
              </li>
            </ul>
          </div>

          <!-- Tombol Panah Kanan -->
          <button type="button" 
                  class="hidden md:flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shrink-0 ml-1.5 shadow-2xs z-5" 
                  @click="scrollTabs(220)"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right text-xs"></i>
          </button>
        </div>
      </div>

      <!-- 5. Main Unified Datatable Card Box (Pencarian + Tabel Data + Pagination Terpadu dalam 1 Box) -->
      <div v-if="activeTab !== 'naikkan_kelas' && activeTab !== 'profile_rapot'" 
           class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        
        <!-- Header Card: Filter & Pencarian Bar -->
        <!-- Header Card: Filter & Pencarian Bar (Proposional & Profesional) -->
        <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
          <form @submit.prevent="applyFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
            
            <!-- Filter Jenjang (Khusus Siswa & Mutasi) -->
            <div class="w-36 sm:w-40 shrink-0" v-if="activeTab === 'siswa' || activeTab === 'mutasi'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Tingkat Jenjang</label>
              <select v-model="filterJenjang" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                <option value="">-- Semua Jenjang --</option>
                <option v-for="j in jenjangList" :key="j.id" :value="j.id">{{ j.nama || j.nama_jenjang }}</option>
              </select>
            </div>

            <!-- Filter Kelas / Rombel -->
            <div class="w-36 sm:w-40 shrink-0" v-if="activeTab === 'siswa' || activeTab === 'mutasi'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Kelas / Rombel</label>
              <select v-model="filterKelas" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                <option value="">-- Semua Kelas --</option>
                <option v-for="k in filteredKelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
              </select>
            </div>

            <!-- Filter Status Siswa -->
            <div class="w-32 sm:w-36 shrink-0" v-if="activeTab === 'siswa'">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Status Siswa</label>
              <select v-model="filterStatus" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                <option value="">-- Semua Status --</option>
                <option value="Aktif">Aktif</option>
                <option value="Lulus">Lulus</option>
                <option value="Pindah">Pindah</option>
                <option value="Non-Aktif">Non-Aktif / Arsip</option>
              </select>
            </div>

            <!-- Search Input (Proposional w-72 s.d. w-84) -->
            <div class="w-64 sm:w-72 md:w-80 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian</label>
              <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       v-model="searchQuery" 
                       @input="handleSearchDebounce"
                       placeholder="Cari nama, NISN, NIS, email, atau NIP..." 
                       class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" />
                <button v-if="searchQuery" 
                        @click="searchQuery = ''; applyFilters()" 
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

        <!-- Body Card: Datatable Content -->
        <div class="overflow-x-auto">
          <!-- Tabel Siswa -->
          <table v-if="activeTab === 'siswa'" class="w-full text-left text-xs text-slate-600 whitespace-nowrap min-w-[950px]">
            <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80 uppercase tracking-wider">
              <tr>
                <th class="py-3.5 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3.5 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Nama Lengkap Siswa</th>
                <th class="py-3.5 px-4 whitespace-nowrap min-w-[100px]">Kelas</th>
                <th class="py-3.5 px-4 whitespace-nowrap">NISN & NIS</th>
                <th class="py-3.5 px-3 text-center whitespace-nowrap">L/P</th>
                <th class="py-3.5 px-4 whitespace-nowrap">TTL</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Kelengkapan Data</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3.5 px-4 text-center w-32 whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="(s, idx) in items?.data || []" :key="s.id" class="hover:bg-slate-50/80 transition" :class="{'bg-red-50/30': !s.is_active}">
                <td class="py-3.5 px-3 text-center font-mono text-slate-400">
                  {{ ((items?.current_page || 1) - 1) * (items?.per_page || 15) + idx + 1 }}
                </td>
                <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                  <div class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0">
                      <i class="bi bi-building"></i>
                    </span>
                    <span class="font-bold text-slate-800">
                      {{ s.tenant?.nama_sekolah || '-' }}
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">
                      {{ (s.nama_lengkap || 'S').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-800">{{ s.nama_lengkap }}</div>
                      <div class="text-[11px] text-slate-400 max-w-[200px] truncate">{{ s.alamat_tinggal || 'Alamat belum diisi' }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200 whitespace-nowrap">
                    {{ s.kelas_saat_ini || '-' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-mono font-bold text-slate-700 whitespace-nowrap">
                  <div>{{ s.nisn || '-' }}</div>
                  <div class="text-[11px] text-slate-400 font-normal">NIS: {{ s.nis || '-' }}</div>
                </td>
                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                  <span class="px-2 py-0.5 rounded text-2xs font-extrabold" :class="s.jenis_kelamin === 'L' ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700'">
                    {{ s.jenis_kelamin || 'L' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="text-slate-700 font-medium">{{ s.tempat_lahir || '-' }}</div>
                  <div class="text-[11px] text-slate-400 font-mono">{{ s.tanggal_lahir ? s.tanggal_lahir.substring(0, 10) : '-' }}</div>
                </td>
                <td class="py-3.5 px-4 min-w-[130px]">
                  <!-- Kelengkapan Profil Progress Bar (Step 1 - Step 5) -->
                  <div class="space-y-1 w-28 group relative cursor-help" 
                       :title="s.kelengkapan_step ? `Step 1 (Identitas): ${s.kelengkapan_step.step1}/20%\nStep 2 (Alamat): ${s.kelengkapan_step.step2}/20%\nStep 3 (Fisik): ${s.kelengkapan_step.step3}/20%\nStep 4 (Orang Tua): ${s.kelengkapan_step.step4}/20%\nStep 5 (Registrasi): ${s.kelengkapan_step.step5}/20%` : `${calculateCompleteness(s)}% Lengkap`">
                    <div class="flex justify-between items-center text-[10px] font-bold">
                      <span :class="calculateCompleteness(s) < 50 ? 'text-rose-600' : (calculateCompleteness(s) < 100 ? 'text-amber-600' : 'text-emerald-600')">
                        {{ calculateCompleteness(s) }}%
                      </span>
                      <i v-if="calculateCompleteness(s) === 100" class="bi bi-check-circle-fill text-emerald-600 text-2xs"></i>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full rounded-full transition-all duration-300"
                           :class="calculateCompleteness(s) < 50 ? 'bg-rose-500' : (calculateCompleteness(s) < 100 ? 'bg-amber-500' : 'bg-emerald-500')"
                           :style="{ width: calculateCompleteness(s) + '%' }"></div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="s.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                    {{ s.is_active ? 'Aktif' : 'Non-Aktif' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <!-- Halaman Penuh Edit Siswa -->
                    <Link v-if="!filters.trash" :href="`/siswa/${s.id}/edit`" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Buka Halaman Lengkap Edit Siswa">
                      <i class="bi bi-pencil-square"></i>
                    </Link>
                    <button v-if="!filters.trash" @click="deleteItem(s.id, 'siswa')" class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Pindahkan ke Tong Sampah">
                      <i class="bi bi-trash"></i>
                    </button>
                    <button v-if="filters.trash" @click="restoreItem(s.id, 'siswa')" class="px-2.5 py-1 text-2xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition flex items-center gap-1">
                      <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!items?.data || items.data.length === 0">
                <td colspan="10" class="text-center py-12 text-slate-400">
                  <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                  Tidak ada data siswa yang ditemukan.
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Tabel Guru -->
          <table v-else-if="activeTab === 'guru'" class="w-full text-left text-xs text-slate-600 whitespace-nowrap min-w-[950px]">
            <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80 uppercase tracking-wider">
              <tr>
                <th class="py-3.5 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3.5 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Nama Lengkap & NIP</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Email & No. WhatsApp</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Jenis GTK & Jabatan</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Peran & Multi-Tugas</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3.5 px-4 text-center w-28 whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="(g, idx) in items?.data || []" :key="g.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3.5 px-3 text-center font-mono text-slate-400">
                  {{ ((items?.current_page || 1) - 1) * (items?.per_page || 15) + idx + 1 }}
                </td>
                <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                  <div class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs shrink-0">
                      <i class="bi bi-building"></i>
                    </span>
                    <span class="font-bold text-slate-800">
                      {{ g.tenant?.nama_sekolah || '-' }}
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0">
                      {{ (g.nama_lengkap || 'G').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-800">{{ g.nama_lengkap }}</div>
                      <div class="text-[11px] text-slate-400 font-mono">NIP: {{ g.nip || '-' }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-semibold text-slate-700 font-mono">{{ g.email }}</div>
                  <div class="text-[11px] text-slate-400 font-mono">{{ g.no_hp || '-' }}</div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="px-2 py-0.5 rounded text-2xs font-extrabold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ g.jenis_gtk || 'Guru Pengajar' }}
                  </span>
                  <div v-if="g.status_kepegawaian" class="text-[10px] text-slate-400 mt-0.5">{{ g.status_kepegawaian }}</div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="flex flex-wrap gap-1">
                    <span class="px-2 py-0.5 rounded text-2xs font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                      Guru
                    </span>
                    <span v-if="g.is_wali_kelas" class="px-2 py-0.5 rounded text-2xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      Wali Kelas
                    </span>
                    <span v-if="g.is_bk" class="px-2 py-0.5 rounded text-2xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                      BK
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="g.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'">
                    {{ g.is_active ? 'Aktif' : 'Non-Aktif' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button v-if="!filters.trash" @click="editUser(g)" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit GTK">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <button v-if="!filters.trash" @click="deleteItem(g.id, 'user')" class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                      <i class="bi bi-trash"></i>
                    </button>
                    <button v-if="filters.trash" @click="restoreItem(g.id, 'user')" class="px-2.5 py-1 text-2xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition flex items-center gap-1">
                      <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!items?.data || items.data.length === 0">
                <td colspan="8" class="text-center py-12 text-slate-400">
                  <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                  Tidak ada data guru yang ditemukan.
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Tabel Karyawan -->
          <table v-else-if="activeTab === 'karyawan'" class="w-full text-left text-xs text-slate-600 whitespace-nowrap min-w-[950px]">
            <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80 uppercase tracking-wider">
              <tr>
                <th class="py-3.5 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3.5 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Nama Lengkap & Gelar</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Email & Kontak</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Peran & Akses Modul</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3.5 px-4 text-center w-28 whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="(k, idx) in items?.data || []" :key="k.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3.5 px-3 text-center font-mono text-slate-400">
                  {{ ((items?.current_page || 1) - 1) * (items?.per_page || 15) + idx + 1 }}
                </td>
                <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                  <div class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs shrink-0">
                      <i class="bi bi-building"></i>
                    </span>
                    <span class="font-bold text-slate-800">
                      {{ k.tenant?.nama_sekolah || '-' }}
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                      {{ (k.nama_lengkap || 'K').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-800">{{ k.nama_lengkap }}</div>
                      <div class="text-[11px] text-slate-400 font-mono">@{{ k.username }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-semibold text-slate-700 font-mono">{{ k.email }}</div>
                  <div class="text-[11px] text-slate-400 font-mono">{{ k.no_hp || '-' }}</div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wider">
                    {{ k.role?.nama_role || k.role || 'Staff TU' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="k.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'">
                    {{ k.is_active ? 'Aktif' : 'Non-Aktif' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button v-if="!filters.trash" @click="editUser(k)" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <button v-if="!filters.trash" @click="deleteItem(k.id, 'user')" class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                      <i class="bi bi-trash"></i>
                    </button>
                    <button v-if="filters.trash" @click="restoreItem(k.id, 'user')" class="px-2.5 py-1 text-2xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition flex items-center gap-1">
                      <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!items?.data || items.data.length === 0">
                <td colspan="7" class="text-center py-12 text-slate-400">
                  <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                  Tidak ada data karyawan yang ditemukan.
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Tabel Operator -->
          <table v-else-if="activeTab === 'operator'" class="w-full text-left text-xs text-slate-600 whitespace-nowrap min-w-[950px]">
            <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80 uppercase tracking-wider">
              <tr>
                <th class="py-3.5 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3.5 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Nama Operator</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Email & Akun</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Tingkat Hak Akses</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3.5 px-4 text-center w-28 whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="(op, idx) in items?.data || []" :key="op.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3.5 px-3 text-center font-mono text-slate-400">
                  {{ ((items?.current_page || 1) - 1) * (items?.per_page || 15) + idx + 1 }}
                </td>
                <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                  <div class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs shrink-0">
                      <i class="bi bi-building"></i>
                    </span>
                    <span class="font-bold text-slate-800">
                      {{ op.tenant?.nama_sekolah || '-' }}
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs shrink-0">
                      {{ (op.nama_lengkap || 'O').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-800">{{ op.nama_lengkap }}</div>
                      <div class="text-[11px] text-slate-400 font-mono">@{{ op.username }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-semibold text-slate-700 font-mono">{{ op.email }}</div>
                  <div class="text-[11px] text-slate-400 font-mono">{{ op.no_hp || '-' }}</div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-amber-50 text-amber-800 border border-amber-200">
                    {{ op.role?.nama_role || op.role || 'Admin Sekolah' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="op.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'">
                    {{ op.is_active ? 'Aktif' : 'Non-Aktif' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button v-if="!filters.trash" @click="editUser(op)" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <button v-if="!filters.trash" @click="deleteItem(op.id, 'user')" class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                      <i class="bi bi-trash"></i>
                    </button>
                    <button v-if="filters.trash" @click="restoreItem(op.id, 'user')" class="px-2.5 py-1 text-2xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition flex items-center gap-1">
                      <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!items?.data || items.data.length === 0">
                <td colspan="7" class="text-center py-12 text-slate-400">
                  <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                  Tidak ada data operator yang ditemukan.
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Tabel Mutasi -->
          <table v-else-if="activeTab === 'mutasi'" class="w-full text-left text-xs text-slate-600 whitespace-nowrap min-w-[950px]">
            <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80 uppercase tracking-wider">
              <tr>
                <th class="py-3.5 px-3 text-center w-12 whitespace-nowrap">No</th>
                <th v-if="isSuperAdmin" class="py-3.5 px-4 whitespace-nowrap">Sekolah</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Nama Siswa</th>
                <th class="py-3.5 px-4 whitespace-nowrap">NISN & NIS</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Rombel / Kelas</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Tipe Mutasi / Aksi</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Tahun Ajaran</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Waktu / Tanggal</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Keterangan / Catatan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="(m, idx) in items?.data || []" :key="m.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3.5 px-3 text-center font-mono text-slate-400">
                  {{ ((items?.current_page || 1) - 1) * (items?.per_page || 15) + idx + 1 }}
                </td>
                <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-700 text-xs whitespace-nowrap">
                  <div class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs shrink-0">
                      <i class="bi bi-building"></i>
                    </span>
                    <span class="font-bold text-slate-800">
                      {{ m.tenant?.nama_sekolah || '-' }}
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-bold text-slate-800">{{ m.siswa?.nama_lengkap || m.nama_lengkap || 'Siswa Mutasi' }}</div>
                  <div class="text-[11px] text-slate-400 font-mono">{{ m.siswa?.nisn ? `NISN: ${m.siswa.nisn}` : '' }}</div>
                </td>
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-700 whitespace-nowrap">
                  {{ m.siswa?.nisn || m.nisn || '-' }}
                  <span v-if="m.siswa?.nis || m.nis" class="text-slate-400 font-normal"> / {{ m.siswa?.nis || m.nis }}</span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-1.5 font-semibold text-slate-700">
                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-bold text-2xs">{{ m.dari_kelas || m.kelas_asal || '-' }}</span>
                    <i v-if="m.ke_kelas" class="bi bi-arrow-right text-slate-400 text-xs"></i>
                    <span v-if="m.ke_kelas" class="px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 font-bold text-2xs">{{ m.ke_kelas }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span v-if="m.status === 'naik' || m.jenis_mutasi === 'naik'" class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200 inline-flex items-center gap-1">
                    <i class="bi bi-arrow-up-circle-fill"></i> Naik Kelas
                  </span>
                  <span v-else-if="m.status === 'pindah' || m.jenis_mutasi === 'pindah'" class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 inline-flex items-center gap-1">
                    <i class="bi bi-arrow-left-right"></i> Pindah Rombel
                  </span>
                  <span v-else-if="m.status === 'tinggal' || m.jenis_mutasi === 'tinggal'" class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1">
                    <i class="bi bi-arrow-repeat"></i> Tinggal Kelas
                  </span>
                  <span v-else-if="m.status === 'lulus' || m.jenis_mutasi === 'lulus'" class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                    <i class="bi bi-mortarboard-fill"></i> Kelulusan Siswa
                  </span>
                  <span v-else-if="m.status === 'mutasi_keluar' || m.status === 'keluar' || m.jenis_mutasi === 'keluar'" class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1">
                    <i class="bi bi-box-arrow-right"></i> Siswa Keluar
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ m.status || m.jenis_mutasi || 'Mutasi' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-700 whitespace-nowrap">{{ m.tahun_ajaran || '-' }}</td>
                <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px] whitespace-nowrap">
                  {{ m.created_at ? new Date(m.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-' }}
                </td>
                <td class="py-3.5 px-4 text-slate-600 text-xs max-w-xs truncate whitespace-nowrap" :title="m.catatan || m.alasan || '-'">
                  {{ m.catatan || m.alasan || '-' }}
                </td>
              </tr>
              <tr v-if="!items?.data || items.data.length === 0">
                <td :colspan="isSuperAdmin ? 9 : 8" class="text-center py-12 text-slate-400">
                  <i class="bi bi-clock-history text-3xl block mb-2 text-slate-300"></i>
                  Belum ada catatan riwayat mutasi / kenaikan kelas siswa.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer Card: Table Pagination Bar (Menyatu dalam 1 Box & Responsif) -->
        <div v-if="items?.total > 0" 
             class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
          <!-- Info Tampilkan Baris -->
          <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
            <span>Tampilkan</span>
            <select v-model="perPage" @change="applyFilters" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
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

          <!-- Pagination Links (Single Clean Horizontal Line) -->
          <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
            <template v-for="(link, i) in getSmartPaginationLinks(items)" :key="i">
              <button v-if="link.url && !link.active" 
                      type="button"
                      @click="goToPage(link.url)"
                      class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
                      :title="link.isPrev ? 'Halaman Sebelumnya' : (link.isNext ? 'Halaman Berikutnya' : 'Halaman ' + link.label)">
                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                <span v-else>{{ link.label }}</span>
              </button>
              <span v-else-if="link.active"
                    class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs">
                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                <span v-else>{{ link.label }}</span>
              </span>
              <span v-else 
                    class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400">
                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                <span v-else>{{ link.label }}</span>
              </span>
            </template>
          </div>
        </div>

      </div>

      <!-- Panel F: Naikkan Kelas & Mutasi / Kelulusan Terpadu -->
      <div v-if="activeTab === 'naikkan_kelas'" class="space-y-6">
        <!-- Card 1: Parameter Form & Mode Aksi -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-6">
          
          <!-- 1. Header Judul & Deskripsi (Lega & Bebas Terhimpit 100%) -->
          <div class="flex items-start gap-3.5 pb-4 border-b border-slate-100">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-xl shadow-md shadow-blue-500/20 shrink-0 mt-0.5">
              <i class="bi bi-arrow-up-circle-fill"></i>
            </span>
            <div class="grow min-w-0">
              <h2 class="text-lg sm:text-xl font-black text-slate-800 tracking-tight">
                Kenaikan Kelas & Mutasi Siswa
              </h2>
              <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Kelola kenaikan tingkat kelas, mutasi rombel paralel, penetapan tinggal kelas, dan kelulusan siswa secara selektif atau massal.
              </p>
            </div>
          </div>

          <!-- 2. Mode Aksi Selector Pills (Bilah Horizontal Mandiri Responsif) -->
          <div class="space-y-2">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Pilih Mode Aksi</label>
            <div class="p-1.5 bg-slate-100/90 border border-slate-200/80 rounded-2xl flex flex-wrap items-center gap-1.5 select-none">
              <button type="button" 
                      @click="promoteForm.mode = 'promote'; promoteForm.kelas_tujuan_id = ''" 
                      class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap"
                      :class="promoteForm.mode === 'promote' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'">
                <i class="bi bi-arrow-up-circle-fill"></i> <span>Naik Kelas</span>
              </button>
              <button type="button" 
                      @click="promoteForm.mode = 'pindah'; promoteForm.kelas_tujuan_id = ''" 
                      class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap"
                      :class="promoteForm.mode === 'pindah' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'">
                <i class="bi bi-arrow-left-right"></i> <span>Pindah Rombel</span>
              </button>
              <button type="button" 
                      @click="promoteForm.mode = 'retain'; promoteForm.kelas_tujuan_id = ''" 
                      class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap"
                      :class="promoteForm.mode === 'retain' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'">
                <i class="bi bi-arrow-repeat"></i> <span>Tinggal Kelas</span>
              </button>
              <button type="button" 
                      @click="promoteForm.mode = 'graduate'; promoteForm.kelas_tujuan_id = ''" 
                      class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap"
                      :class="promoteForm.mode === 'graduate' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'">
                <i class="bi bi-mortarboard-fill"></i> <span>Kelulusan</span>
              </button>
              <button type="button" 
                      @click="promoteForm.mode = 'mutasi_keluar'; promoteForm.kelas_tujuan_id = ''" 
                      class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap"
                      :class="promoteForm.mode === 'mutasi_keluar' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'">
                <i class="bi bi-box-arrow-right"></i> <span>Siswa Keluar</span>
              </button>
            </div>
          </div>

          <!-- Parameter Fields Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Kelas Asal -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1">
                <i class="bi bi-door-open-fill text-blue-600"></i> Rombel / Kelas Asal <span class="text-rose-500">*</span>
              </label>
              <select v-model="promoteForm.kelas_asal_id" 
                      @change="onKelasAsalChange" 
                      required 
                      class="w-full h-10 px-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                <option value="">-- Pilih Kelas Asal Siswa --</option>
                <option v-for="k in filteredKelasList" :key="k.id" :value="k.id">
                  {{ k.kategori ? `[${k.kategori}] ` : '' }}{{ k.nama_kelas }}
                </option>
              </select>
            </div>

            <!-- Kelas Tujuan (Muncul untuk Promote, Pindah, Retain) -->
            <div v-if="['promote', 'pindah', 'retain'].includes(promoteForm.mode)">
              <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1">
                <i class="bi bi-door-closed-fill text-emerald-600"></i> Rombel / Kelas Tujuan <span class="text-rose-500">*</span>
              </label>
              <select v-model="promoteForm.kelas_tujuan_id" 
                      required 
                      :disabled="!promoteForm.kelas_asal_id"
                      class="w-full h-10 px-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 outline-none disabled:opacity-50 transition">
                <option value="">-- Pilih Kelas Tujuan --</option>
                <option v-for="k in filteredKelasList.filter(k => promoteForm.mode !== 'promote' || k.id !== promoteForm.kelas_asal_id)" :key="k.id" :value="k.id">
                  {{ k.kategori ? `[${k.kategori}] ` : '' }}{{ k.nama_kelas }}
                </option>
              </select>
            </div>

            <!-- Tahun Ajaran -->
            <div :class="['graduate', 'mutasi_keluar'].includes(promoteForm.mode) ? 'sm:col-span-1' : ''">
              <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1">
                <i class="bi bi-calendar3 text-slate-500"></i> Tahun Ajaran Target <span class="text-rose-500">*</span>
              </label>
              <select v-model="promoteForm.tahun_ajaran" required class="w-full h-10 px-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:border-blue-600 outline-none transition">
                <option v-for="ta in (tahunAjaranList || []).filter(t => t.tahun_ajaran || t.nama_tahun_ajaran)" :key="ta.id" :value="ta.tahun_ajaran || ta.nama_tahun_ajaran">
                  {{ ta.tahun_ajaran || ta.nama_tahun_ajaran }}
                </option>
                <option v-if="!tahunAjaranList || tahunAjaranList.length === 0" value="2026/2027">2026/2027</option>
                <option v-if="!tahunAjaranList || tahunAjaranList.length === 0" value="2027/2028">2027/2028</option>
              </select>
            </div>
          </div>

          <!-- Catatan / Keterangan Tambahan -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan / Keterangan Mutasi (Opsional)</label>
            <input type="text" 
                   v-model="promoteForm.catatan" 
                   placeholder="Misal: Kenaikan kelas reguler semester genap / Pindah rombel paralel atas permohonan wali murid..." 
                   class="w-full h-10 px-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:border-blue-600 outline-none transition" />
          </div>
        </div>

        <!-- Card 2: Interactive Student Checklist Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          
          <!-- Top Action Toolbar -->
          <div class="p-4 bg-slate-50/80 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" 
                       :checked="promoteSelectAll" 
                       @change="togglePromoteSelectAll" 
                       :disabled="promoteListSiswa.length === 0"
                       class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-800">
                  Pilih Semua Siswa ({{ promoteListSiswa.length }} Siswa Aktif)
                </span>
              </label>

              <span v-if="promoteForm.siswa_ids.length > 0" 
                    class="px-2.5 py-1 rounded-full text-2xs font-extrabold"
                    :class="promoteForm.mode === 'promote' ? 'bg-blue-100 text-blue-800' : (promoteForm.mode === 'graduate' ? 'bg-emerald-100 text-emerald-800' : (promoteForm.mode === 'mutasi_keluar' ? 'bg-rose-100 text-rose-800' : 'bg-indigo-100 text-indigo-800'))">
                {{ promoteForm.siswa_ids.length }} siswa dipilih
              </span>
            </div>

            <!-- Top Submit Action Button -->
            <button type="button" 
                    @click="submitPromote" 
                    :disabled="promoteSubmitLoading || promoteForm.siswa_ids.length === 0 || (['promote', 'pindah', 'retain'].includes(promoteForm.mode) && !promoteForm.kelas_tujuan_id)" 
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white shadow-xs transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="promoteForm.mode === 'promote' ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20' : (promoteForm.mode === 'graduate' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20' : (promoteForm.mode === 'mutasi_keluar' ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-500/20' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-500/20'))">
              <span v-if="promoteSubmitLoading" class="spinner-border spinner-border-sm"></span>
              <template v-else>
                <i v-if="promoteForm.mode === 'promote'" class="bi bi-arrow-up-circle-fill"></i>
                <i v-else-if="promoteForm.mode === 'pindah'" class="bi bi-arrow-left-right"></i>
                <i v-else-if="promoteForm.mode === 'retain'" class="bi bi-arrow-repeat"></i>
                <i v-else-if="promoteForm.mode === 'graduate'" class="bi bi-mortarboard-fill"></i>
                <i v-else-if="promoteForm.mode === 'mutasi_keluar'" class="bi bi-box-arrow-right"></i>
                <span>
                  {{ 
                    promoteForm.mode === 'promote' ? `Naikkan ${promoteForm.siswa_ids.length || 0} Siswa Terpilih` :
                    promoteForm.mode === 'pindah' ? `Pindahkan ${promoteForm.siswa_ids.length || 0} Siswa Terpilih` :
                    promoteForm.mode === 'retain' ? `Tetapkan Tinggal ${promoteForm.siswa_ids.length || 0} Siswa` :
                    promoteForm.mode === 'graduate' ? `Luluskan ${promoteForm.siswa_ids.length || 0} Siswa (Alumni)` :
                    `Keluarkan / Mutasi ${promoteForm.siswa_ids.length || 0} Siswa`
                  }}
                </span>
              </template>
            </button>
          </div>

          <!-- Content Body -->
          <div v-if="!promoteForm.kelas_asal_id" class="text-center py-16 px-4">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 mx-auto flex items-center justify-center text-2xl mb-3">
              <i class="bi bi-door-open"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-700">Pilih Rombel / Kelas Asal Terlebih Dahulu</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Silakan tentukan kelas asal pada dropdown di atas untuk menampilkan daftar seluruh siswa aktif yang dapat dinaikkan atau dipindahkan.</p>
          </div>

          <div v-else-if="promoteLoadingSiswa" class="text-center py-16">
            <div class="spinner-border text-blue-600" role="status"></div>
            <p class="text-xs text-slate-500 font-semibold mt-3">Mengambil data siswa aktif di kelas...</p>
          </div>

          <div v-else-if="promoteListSiswa.length === 0" class="text-center py-16 px-4">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center text-2xl mb-3">
              <i class="bi bi-person-x"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-700">Tidak Ada Siswa Aktif di Kelas Ini</h3>
            <p class="text-xs text-slate-400 mt-1">Seluruh siswa di rombel ini mungkin sudah lulus, non-aktif, atau belum memiliki penempatan kelas aktif.</p>
          </div>

          <!-- Table Siswa Checklist -->
          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80 uppercase tracking-wider">
                <tr>
                  <th class="py-3.5 px-3 text-center w-12">
                    <input type="checkbox" 
                           :checked="promoteSelectAll" 
                           @change="togglePromoteSelectAll" 
                           class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 cursor-pointer" />
                  </th>
                  <th class="py-3.5 px-3 text-center w-12">No</th>
                  <th class="py-3.5 px-4">Nama Lengkap Siswa</th>
                  <th class="py-3.5 px-4">NISN & NIS</th>
                  <th class="py-3.5 px-3 text-center">L/P</th>
                  <th class="py-3.5 px-4">Kelas Saat Ini</th>
                  <th class="py-3.5 px-4 text-center">Status</th>
                  <th class="py-3.5 px-4 text-center w-28">Riwayat</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(s, idx) in promoteListSiswa" 
                    :key="s.id" 
                    class="hover:bg-blue-50/30 transition cursor-pointer"
                    :class="promoteForm.siswa_ids.includes(s.id) ? 'bg-blue-50/20' : ''"
                    @click="toggleSelectStudent(s.id)">
                  <td class="py-3.5 px-3 text-center" @click.stop>
                    <input type="checkbox" 
                           :value="s.id" 
                           :checked="promoteForm.siswa_ids.includes(s.id)"
                           @change="toggleSelectStudent(s.id)"
                           class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 cursor-pointer" />
                  </td>
                  <td class="py-3.5 px-3 text-center font-mono text-slate-400">{{ idx + 1 }}</td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                        {{ (s.nama_lengkap || 'S').charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-bold text-slate-800">{{ s.nama_lengkap }}</div>
                        <div class="text-[11px] text-slate-400 font-mono">ID: {{ s.id.substring(0, 8) }}...</div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                    <div>{{ s.nisn || '-' }}</div>
                    <div class="text-[10px] text-slate-400 font-normal">{{ s.nis ? `NIS: ${s.nis}` : '' }}</div>
                  </td>
                  <td class="py-3.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded text-2xs font-extrabold" :class="s.jenis_kelamin === 'L' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-pink-50 text-pink-700 border border-pink-200'">
                      {{ s.jenis_kelamin || 'L' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-1 rounded-lg text-2xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                      {{ s.kelas_saat_ini || '-' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center" @click.stop>
                    <button type="button" 
                            @click="viewRiwayatSiswa(s)" 
                            class="px-2.5 py-1 rounded-lg text-2xs font-bold text-blue-600 hover:bg-blue-50 border border-blue-200 transition flex items-center gap-1 mx-auto" 
                            title="Lihat riwayat kenaikan & mutasi siswa">
                      <i class="bi bi-clock-history"></i> Histori
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Bottom Action Toolbar -->
          <div v-if="promoteListSiswa.length > 0" 
               class="p-4 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="text-xs font-semibold text-slate-500">
              Menampilkan total <span class="font-bold text-slate-800">{{ promoteListSiswa.length }}</span> siswa aktif di kelas <span class="font-bold text-blue-600">{{ promoteListSiswa[0]?.kelas_saat_ini || '' }}</span>
            </div>

            <button type="button" 
                    @click="submitPromote" 
                    :disabled="promoteSubmitLoading || promoteForm.siswa_ids.length === 0 || (['promote', 'pindah', 'retain'].includes(promoteForm.mode) && !promoteForm.kelas_tujuan_id)" 
                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-xs transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="promoteForm.mode === 'promote' ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20' : (promoteForm.mode === 'graduate' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20' : (promoteForm.mode === 'mutasi_keluar' ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-500/20' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-500/20'))">
              <span v-if="promoteSubmitLoading" class="spinner-border spinner-border-sm"></span>
              <template v-else>
                <i class="bi bi-check-circle-fill"></i>
                <span>
                  {{ 
                    promoteForm.mode === 'promote' ? `Proses Kenaikan Kelas (${promoteForm.siswa_ids.length} Siswa Terpilih)` :
                    promoteForm.mode === 'pindah' ? `Proses Pindah Rombel (${promoteForm.siswa_ids.length} Siswa Terpilih)` :
                    promoteForm.mode === 'retain' ? `Proses Tinggal Kelas (${promoteForm.siswa_ids.length} Siswa)` :
                    promoteForm.mode === 'graduate' ? `Proses Kelulusan (${promoteForm.siswa_ids.length} Siswa Terpilih)` :
                    `Proses Mutasi Keluar (${promoteForm.siswa_ids.length} Siswa)`
                  }}
                </span>
              </template>
            </button>
          </div>

        </div>
      </div>

      <!-- Panel G: Profile Rapot (Identitas Peserta Didik) -->
      <div v-if="activeTab === 'profile_rapot'" class="space-y-6">
        <!-- Header Panel -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-100 gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-600 text-white flex items-center justify-center font-bold text-lg shadow-xs shrink-0">
                <i class="bi bi-file-earmark-person-fill"></i>
              </div>
              <div>
                <h2 class="text-base font-bold text-slate-800">Profile Rapot (Identitas Peserta Didik)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Unduh lembar Identitas Peserta Didik per siswa atau per rombel kelas dengan format A4 standar resmi.</p>
              </div>
            </div>
          </div>

          <!-- Banner Unggah Foto Profil Masal (ZIP) -->
          <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="w-9 h-9 rounded-xl bg-emerald-600/10 text-emerald-700 flex items-center justify-center text-lg shrink-0 mt-0.5">
                <i class="bi bi-images"></i>
              </div>
              <div>
                <h4 class="text-xs font-bold text-emerald-900">Unggah Foto Profil Siswa Masal (ZIP)</h4>
                <p class="text-[11px] text-emerald-800 mt-0.5 leading-relaxed">
                  Unggah berkas ZIP berisi foto seluruh siswa dengan penamaan file <code class="bg-emerald-100/80 px-1 py-0.5 rounded font-mono font-bold">NPSN_NISN.jpg/png</code> atau <code class="bg-emerald-100/80 px-1 py-0.5 rounded font-mono font-bold">NISN.jpg</code> (Maks. 500 KB per foto).
                </p>
              </div>
            </div>
            <button type="button" 
                    @click="openBulkPhotoModal = true"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-2xs transition flex items-center gap-1.5 shrink-0">
              <i class="bi bi-cloud-arrow-up-fill"></i> Unggah Foto Masal
            </button>
          </div>

          <!-- Filter & Parameter Penandatanganan Rapot -->
          <form @submit.prevent="applyFilters" class="space-y-3.5 pt-2">
            <!-- Baris 1: Parameter Kelas & TTD (4 Kolom Terstruktur) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <!-- Filter Kelas -->
              <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Rombel / Kelas <span class="text-rose-500">*</span></label>
                <select v-model="filterKelas" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                  <option value="">-- Pilih Kelas / Rombel --</option>
                  <option v-for="k in filteredKelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                </select>
              </div>

              <!-- Filter Status Siswa -->
              <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Status Siswa</label>
                <select v-model="filterStatus" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                  <option value="">Semua Status</option>
                  <option value="Aktif">Aktif</option>
                  <option value="Lulus">Lulus (Alumni)</option>
                  <option value="Pindah">Pindah</option>
                  <option value="Non-Aktif">Non-Aktif</option>
                </select>
              </div>

              <!-- Tempat Tanda Tangan -->
              <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Kota TTD <span class="text-rose-500">*</span></label>
                <input type="text" v-model="printTempat" placeholder="Misal: Jakarta" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
              </div>

              <!-- Tanggal Tanda Tangan -->
              <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Tanggal TTD <span class="text-rose-500">*</span></label>
                <input type="text" v-model="printTanggal" placeholder="Contoh: 10 Juli 2026" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
              </div>
            </div>

            <!-- Baris 2: Pencarian & Tombol Aksi (Responsif di Dalam Card) -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2.5">
              <!-- Search Bar -->
              <div class="grow">
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Pencarian Siswa</label>
                <div class="relative">
                  <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                  <input type="text" 
                         v-model="searchQuery" 
                         @input="handleSearchDebounce"
                         placeholder="Cari nama, NISN, NIS, atau NIK siswa..." 
                         class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition" />
                  <button v-if="searchQuery" 
                          type="button" 
                          @click="searchQuery = ''; applyFilters()" 
                          class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                          title="Hapus pencarian">
                    <i class="bi bi-x-circle-fill text-xs"></i>
                  </button>
                </div>
              </div>

              <!-- Tombol Cari & Reset -->
              <div class="flex items-center gap-2 shrink-0">
                <button type="submit" class="h-9 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center justify-center gap-1.5 min-w-[80px]">
                  <i class="bi bi-search"></i> Cari
                </button>
                <button type="button" @click="resetFilters" class="h-9 px-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs shadow-2xs transition min-w-[70px]">
                  Reset
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl">
            <div class="flex items-center justify-between">
              <span class="text-emerald-800 font-bold text-xs uppercase tracking-wider">Biodata Siap Cetak (≥ 80%)</span>
              <i class="bi bi-check-circle-fill text-emerald-600 text-lg"></i>
            </div>
            <div class="text-2xl font-black text-emerald-900 mt-1">
              {{ (stats?.rapot_siap_cetak !== undefined ? stats.rapot_siap_cetak : (items?.data || []).filter(s => s.kelengkapan_persen >= 80).length) }} Siswa
            </div>
          </div>
          <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl">
            <div class="flex items-center justify-between">
              <span class="text-amber-800 font-bold text-xs uppercase tracking-wider">Perlu Dilengkapi (< 80%)</span>
              <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg"></i>
            </div>
            <div class="text-2xl font-black text-amber-900 mt-1">
              {{ (stats?.rapot_perlu_dilengkapi !== undefined ? stats.rapot_perlu_dilengkapi : (items?.data || []).filter(s => (s.kelengkapan_persen || 0) < 80).length) }} Siswa
            </div>
          </div>
          <div class="p-4 bg-blue-50 border border-blue-200 rounded-2xl">
            <div class="flex items-center justify-between">
              <span class="text-blue-800 font-bold text-xs uppercase tracking-wider">Total Terdaftar di Filter</span>
              <i class="bi bi-people-fill text-blue-600 text-lg"></i>
            </div>
            <div class="text-2xl font-black text-blue-900 mt-1">{{ (stats?.rapot_total !== undefined ? stats.rapot_total : (items?.total || (items?.data || []).length)) }} Siswa</div>
          </div>
        </div>

        <!-- Datatable Card Box -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          
          <div class="p-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between gap-3">
            <span class="text-xs font-bold text-slate-700">
              Menampilkan <span class="text-blue-600 font-black">{{ items?.total || (items?.data || []).length }}</span> Siswa (Status: {{ filterStatus || 'Semua Status' }})
            </span>

            <button type="button" 
                    @click="printBulkRapot"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5">
              <i class="bi bi-printer-fill"></i> Cetak Rapot Kelas (Bulk)
            </button>
          </div>

          <div v-if="!filterKelas && isSuperAdmin && !filterTenantId" class="text-center py-16 px-4">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 mx-auto flex items-center justify-center text-2xl mb-3">
              <i class="bi bi-building"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-700">Pilih Instansi Sekolah Terlebih Dahulu</h3>
            <p class="text-xs text-slate-400 mt-1">Silakan tentukan instansi sekolah pada filter di atas.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80 uppercase tracking-wider">
                <tr>
                  <th class="py-3.5 px-3 text-center w-12">No</th>
                  <th class="py-3.5 px-4">Nama Lengkap Siswa</th>
                  <th class="py-3.5 px-4">NISN & NIS</th>
                  <th class="py-3.5 px-4">Kelas / Rombel</th>
                  <th class="py-3.5 px-4">Kelengkapan Data Rapor</th>
                  <th class="py-3.5 px-4 text-center">Status</th>
                  <th class="py-3.5 px-4 text-center w-28">Aksi Cetak</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(item, idx) in items?.data || []" :key="item.id" class="hover:bg-slate-50/80 transition">
                  <td class="py-3.5 px-3 text-center font-mono text-slate-400">
                    {{ ((items?.current_page || 1) - 1) * (items?.per_page || 15) + idx + 1 }}
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                        {{ (item.nama_lengkap || 'S').charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-bold text-slate-800">{{ item.nama_lengkap }}</div>
                        <div class="text-[11px] text-slate-400 font-mono">
                          {{ item.nik ? `NIK: ${item.nik}` : (item.tempat_lahir ? `${item.tempat_lahir}, ${item.tanggal_lahir || ''}` : '-') }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                    <div>{{ item.nisn || '-' }}</div>
                    <div class="text-[10px] text-slate-400 font-normal">{{ item.nis ? `NIS: ${item.nis}` : '' }}</div>
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-1 rounded-lg text-2xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                      {{ item.kelas_saat_ini || '-' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="space-y-1">
                      <div class="flex items-center justify-between text-2xs font-bold">
                        <span :class="item.kelengkapan_persen >= 80 ? 'text-emerald-700' : 'text-amber-700'">
                          {{ item.kelengkapan_persen || 0 }}% Lengkap
                        </span>
                        <span v-if="item.kelengkapan_persen >= 80" class="text-emerald-600 flex items-center gap-0.5">
                          <i class="bi bi-check-circle-fill"></i> Siap Cetak
                        </span>
                        <span v-else class="text-amber-600 flex items-center gap-0.5">
                          <i class="bi bi-exclamation-circle-fill"></i> Kurang Data
                        </span>
                      </div>
                      <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all" 
                             :class="item.kelengkapan_persen >= 80 ? 'bg-emerald-500' : 'bg-amber-500'" 
                             :style="{ width: `${item.kelengkapan_persen || 0}%` }"></div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold" 
                          :class="item.status_siswa === 'Lulus' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : (item.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200')">
                      {{ item.status_siswa || (item.is_active ? 'Aktif' : 'Non-Aktif') }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <button type="button" 
                            @click="printSingleRapot(item.id)" 
                            class="px-3 py-1.5 rounded-xl text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition flex items-center gap-1.5 mx-auto" 
                            title="Cetak lembar Identitas Peserta Didik">
                      <i class="bi bi-printer"></i> Cetak
                    </button>
                  </td>
                </tr>
                <tr v-if="!items?.data || items.data.length === 0">
                  <td colspan="7" class="text-center py-12 text-slate-400">
                    <i class="bi bi-file-earmark-x text-3xl block mb-2 text-slate-300"></i>
                    Tidak ada data siswa ditemukan pada kriteria filter ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Table Pagination Bar -->
          <div v-if="items?.total > 0" 
               class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
              <span>Tampilkan</span>
              <select v-model="perPage" @change="applyFilters" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
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

            <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(items)" :key="i">
                <button v-if="link.url && !link.active" 
                        type="button"
                        @click="goToPage(link.url)"
                        class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
                        :title="link.isPrev ? 'Halaman Sebelumnya' : (link.isNext ? 'Halaman Berikutnya' : 'Halaman ' + link.label)">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                  <span v-else>{{ link.label }}</span>
                </button>
                <span v-else-if="link.active"
                      class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                  <span v-else>{{ link.label }}</span>
                </span>
                <span v-else 
                      class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                  <span v-else>{{ link.label }}</span>
                </span>
              </template>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL-MODAL TERPADU SINTA SAAS (Siswa, GTK Multi-Role, Import Excel) -->
    <!-- ========================================================================= -->

    <!-- 1. Modal Registrasi Cepat Siswa -->
    <div v-if="openQuickAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 animate-fade-in">
      <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg overflow-hidden border border-slate-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-emerald-50 to-white">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">
              <i class="bi bi-lightning-fill"></i>
            </span>
            <h3 class="font-black text-slate-800 text-base">Registrasi Cepat Siswa</h3>
          </div>
          <button @click="openQuickAddModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitQuickAdd" class="p-5 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Siswa *</label>
            <input type="text" v-model="quickAddForm.nama_lengkap" required placeholder="Nama lengkap siswa" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">NISN *</label>
              <input type="text" v-model="quickAddForm.nisn" required placeholder="10 Digit NISN" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">NIS *</label>
              <input type="text" v-model="quickAddForm.nis" required placeholder="NIS Siswa" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin *</label>
              <select v-model="quickAddForm.jenis_kelamin" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="L">Laki-laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Rombel / Kelas</label>
              <select v-model="quickAddForm.kelas_saat_ini" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="">- Pilih Kelas -</option>
                <option v-for="k in kelasList" :key="k.id" :value="k.nama_kelas">{{ k.nama_kelas }}</option>
              </select>
            </div>
          </div>

          <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
            <button type="button" @click="openQuickAddModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</button>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs">Simpan Siswa</button>
          </div>
        </form>
      </div>
    </div>

    <!-- 2. Modal Tambah / Edit GTK Multi-Role (Guru, Karyawan, Operator) -->
    <div v-if="openUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 animate-fade-in overflow-y-auto">
      <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl overflow-hidden border border-slate-200 my-8">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-blue-50 to-white">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
              <i class="bi bi-person-gear"></i>
            </span>
            <h3 class="font-black text-slate-800 text-base">
              {{ isEditingUser ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}
            </h3>
          </div>
          <button @click="openUserModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitUserForm" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap & Gelar *</label>
            <input type="text" v-model="userForm.nama_lengkap" required placeholder="Contoh: Drs. H. Ahmad Dahlan, M.Pd" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">NIP (Nomor Induk Pegawai)</label>
              <input type="text" v-model="userForm.nip" placeholder="18 digit NIP" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Email Login *</label>
              <input type="email" v-model="userForm.email" required placeholder="email@sekolah.sch.id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Username Login *</label>
              <input type="text" v-model="userForm.username" required placeholder="username" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">{{ isEditingUser ? 'Password Baru (Kosongkan jika tetap)' : 'Password *' }}</label>
              <input type="password" v-model="userForm.password" :required="!isEditingUser" placeholder="Min. 6 karakter" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Peran Akses Utama (Role) *</label>
              <select v-model="userForm.role" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="guru">Guru / Tenaga Pendidik</option>
                <option value="karyawan">Karyawan / Tenaga Kependidikan (TU)</option>
                <option value="operator_sekolah">Operator Sekolah</option>
                <option value="admin_sekolah">Admin Sekolah</option>
                <option value="keuangan">Staff Keuangan & Kasir</option>
                <option value="perpustakaan">Staff Perpustakaan</option>
                <option value="sarpras">Staff Sarana Prasarana</option>
                <option value="bk">Guru BK</option>
                <option value="super_admin">Super Administrator</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP</label>
              <input type="text" v-model="userForm.no_hp" placeholder="Contoh: 081234567890" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>
          </div>

          <!-- Multi-Role & Penugasan Tambahan -->
          <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-800"><i class="bi bi-person-badge-fill text-blue-600 me-1"></i> Penugasan Peran Tambahan (Multi-Role)</span>
              <span class="text-2xs font-extrabold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-md">Otomatisasi Akses Multi-Modul</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2">
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_wali_kelas" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-person-workspace text-blue-600 me-1"></i> Wali Kelas</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_bk" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-chat-heart-fill text-purple-600 me-1"></i> Tim BK (Bimbingan Konseling)</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_kurikulum" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-journal-bookmark-fill text-indigo-600 me-1"></i> Tim / Waka Kurikulum</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_kesiswaan" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-award-fill text-amber-600 me-1"></i> Tim / Waka Kesiswaan</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_sarpras" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-buildings-fill text-cyan-600 me-1"></i> Tim / Waka Sarana & Prasarana</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_humas" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-megaphone-fill text-rose-600 me-1"></i> Tim / Waka Humas</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_perpustakaan" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-book-half text-emerald-600 me-1"></i> Pengelola Perpustakaan</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_keuangan" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-cash-stack text-teal-600 me-1"></i> Bendahara / Keuangan</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_pembina_ekskul" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-trophy-fill text-yellow-600 me-1"></i> Pembina Ekstrakurikuler</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition">
                <input type="checkbox" v-model="userForm.is_operator" class="rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-700"><i class="bi bi-gear-wide-connected text-blue-600 me-1"></i> Operator Sekolah</span>
              </label>
            </div>
          </div>

          <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
            <button type="button" @click="openUserModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</button>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs">Simpan Pengguna</button>
          </div>
        </form>
      </div>
    </div>

    <!-- 3. Modal Import Siswa via Excel (.xlsx) -->
    <div v-if="openImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 animate-fade-in">
      <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg overflow-hidden border border-slate-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-emerald-50 to-white">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">
              <i class="bi bi-file-earmark-excel-fill"></i>
            </span>
            <h3 class="font-black text-slate-800 text-base">Import Siswa via Excel (.xlsx)</h3>
          </div>
          <button @click="openImportModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="p-6 space-y-4">
          <div>
            <div class="flex justify-between items-center mb-1.5">
              <label class="block text-xs font-bold text-slate-700">Pilih Berkas Excel (.xlsx) *</label>
              <a href="/pengguna/export-excel?tab=siswa" download class="text-2xs font-extrabold text-emerald-700 hover:underline flex items-center gap-1">
                <i class="bi bi-download"></i> Download Template Excel
              </a>
            </div>
            <input type="file" accept=".xlsx,.xls,.csv" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none" />
          </div>

          <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-2xl space-y-2">
            <span class="text-xs font-bold text-blue-900 flex items-center gap-1"><i class="bi bi-info-circle-fill"></i> Petunjuk Import:</span>
            <ul class="text-[11px] text-blue-800 space-y-1 list-disc pl-4">
              <li>Pastikan format kolom: <code class="bg-blue-100 px-1 rounded">Nama Lengkap, NISN, NIS, Jenis Kelamin (L/P), Rombel, HP</code>.</li>
              <li>Password akun siswa otomatis dibuat menggunakan tanggal lahir atau default <code class="bg-blue-100 px-1 rounded">siswa123</code>.</li>
            </ul>
          </div>

          <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
            <button type="button" @click="openImportModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</button>
            <button type="button" @click="openImportModal = false; flashMessage = 'Fitur import siswa siap diproses!'" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs flex items-center gap-1.5">
              <i class="bi bi-cloud-arrow-up-fill"></i> Mulai Import
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Modal Riwayat Kenaikan & Mutasi Siswa -->
    <div v-if="openRiwayatModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 animate-fade-in">
      <div class="bg-white rounded-3xl shadow-xl w-full max-w-3xl overflow-hidden border border-slate-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-blue-50 to-white">
          <div class="flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-base shadow-xs">
              <i class="bi bi-clock-history"></i>
            </span>
            <div>
              <h3 class="font-black text-slate-800 text-base">Riwayat Kenaikan & Mutasi Siswa</h3>
              <p class="text-xs text-slate-500 font-medium">
                {{ selectedSiswaForRiwayat?.nama_lengkap }} (NISN: {{ selectedSiswaForRiwayat?.nisn || '-' }})
              </p>
            </div>
          </div>
          <button @click="openRiwayatModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
          <div v-if="loadingRiwayat" class="text-center py-10">
            <div class="spinner-border text-blue-600" role="status"></div>
            <p class="text-xs text-slate-400 font-semibold mt-2">Memuat riwayat mutasi...</p>
          </div>

          <div v-else-if="listRiwayatSiswa.length === 0" class="text-center py-10 text-slate-400">
            <i class="bi bi-folder2-open text-3xl block mb-2 text-slate-300"></i>
            Siswa baru atau belum memiliki riwayat siklus kenaikan/pindah kelas.
          </div>

          <div v-else class="border border-slate-200/80 rounded-2xl overflow-x-auto shadow-2xs">
            <table class="w-full text-left text-xs text-slate-600 whitespace-nowrap min-w-[650px]">
              <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200/80 uppercase tracking-wider">
                <tr>
                  <th class="py-3 px-3.5 text-center w-12 whitespace-nowrap">No</th>
                  <th class="py-3 px-3.5 whitespace-nowrap">Tahun Ajaran</th>
                  <th class="py-3 px-3.5 whitespace-nowrap">Dari Kelas</th>
                  <th class="py-3 px-3.5 whitespace-nowrap">Ke Kelas</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap min-w-[130px]">Aksi / Status</th>
                  <th class="py-3 px-3.5 whitespace-nowrap">Waktu</th>
                  <th class="py-3 px-4 whitespace-nowrap">Catatan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(r, idx) in listRiwayatSiswa" :key="r.id" class="hover:bg-slate-50/70 transition">
                  <td class="py-3 px-3.5 text-center font-mono text-slate-400">{{ idx + 1 }}</td>
                  <td class="py-3 px-3.5 font-bold text-slate-800 font-mono">{{ r.tahun_ajaran }}</td>
                  <td class="py-3 px-3.5 font-bold text-slate-700">{{ r.dari_kelas || '-' }}</td>
                  <td class="py-3 px-3.5 font-bold text-blue-600">{{ r.ke_kelas || '-' }}</td>
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span v-if="r.status === 'naik' || r.status === 'promote'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200 whitespace-nowrap">
                      <i class="bi bi-arrow-up-circle-fill"></i> Naik Kelas
                    </span>
                    <span v-else-if="r.status === 'pindah'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap">
                      <i class="bi bi-arrow-left-right"></i> Pindah Rombel
                    </span>
                    <span v-else-if="r.status === 'tinggal' || r.status === 'retain'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                      <i class="bi bi-arrow-repeat"></i> Tinggal Kelas
                    </span>
                    <span v-else-if="r.status === 'lulus' || r.status === 'graduate'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                      <i class="bi bi-mortarboard-fill"></i> Lulus
                    </span>
                    <span v-else-if="r.status === 'mutasi_keluar' || r.status === 'keluar'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-rose-50 text-rose-700 border border-rose-200 whitespace-nowrap">
                      <i class="bi bi-box-arrow-right"></i> Keluar
                    </span>
                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap">
                      {{ r.status || 'Aktif' }}
                    </span>
                  </td>
                  <td class="py-3 px-3.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                    {{ r.created_at ? new Date(r.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-' }}
                  </td>
                  <td class="py-3 px-4 text-slate-600 text-xs whitespace-nowrap">{{ r.catatan || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="pt-3 flex justify-end border-t border-slate-100">
            <button type="button" @click="openRiwayatModal = false" class="px-5 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl shadow-2xs transition">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- 5. Modal Unggah Foto Siswa Masal (ZIP) -->
    <div v-if="openBulkPhotoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 animate-fade-in">
      <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg overflow-hidden border border-slate-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-emerald-50 to-white">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">
              <i class="bi bi-file-earmark-zip-fill"></i>
            </span>
            <h3 class="font-black text-slate-800 text-base">Unggah Foto Siswa Masal (ZIP)</h3>
          </div>
          <button @click="openBulkPhotoModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitBulkPhotos" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Berkas ZIP Berisi Foto (.zip) *</label>
            <input type="file" 
                   accept=".zip" 
                   required
                   @change="onBulkPhotoFileChange" 
                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none" />
          </div>

          <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-2">
            <span class="text-xs font-bold text-emerald-900 flex items-center gap-1"><i class="bi bi-info-circle-fill"></i> Ketentuan Penamaan File:</span>
            <ul class="text-[11px] text-emerald-800 space-y-1 list-disc pl-4">
              <li>Penamaan file di dalam ZIP: <code class="bg-emerald-100 font-bold px-1 rounded font-mono">NPSN_NISN.jpg</code> atau <code class="bg-emerald-100 font-bold px-1 rounded font-mono">NISN.jpg</code> (Contoh: <code class="bg-emerald-100 px-1 rounded font-mono">0012345678.jpg</code>).</li>
              <li>Format didukung: <strong>JPG, JPEG, PNG, WEBP</strong>. Maksimal ukuran <strong>500 KB</strong> per foto.</li>
              <li>Sistem akan otomatis mencocokkan NISN dan memperbarui foto profil siswa ke database.</li>
            </ul>
          </div>

          <div v-if="bulkPhotoResult" class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800">
            {{ bulkPhotoResult }}
          </div>

          <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
            <button type="button" @click="openBulkPhotoModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</button>
            <button type="submit" 
                    :disabled="bulkPhotoLoading" 
                    class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs flex items-center gap-1.5 disabled:opacity-50">
              <i v-if="bulkPhotoLoading" class="spinner-border spinner-border-sm" role="status"></i>
              <i v-else class="bi bi-cloud-arrow-up-fill"></i>
              {{ bulkPhotoLoading ? 'Mengekstrak & Memproses...' : 'Unggah & Ekstrak ZIP' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  activeTab: { type: String, default: 'siswa' },
  items: Object,
  stats: Object,
  tenants: Array,
  kelasList: Array,
  jenjangList: Array,
  tahunAjaranList: Array,
  isSuperAdmin: Boolean,
  filters: Object,
});

const flashMessage = ref('');

const tabList = [
  { id: 'siswa', name: 'Siswa', icon: 'bi bi-mortarboard' },
  { id: 'guru', name: 'Guru', icon: 'bi bi-person-badge' },
  { id: 'karyawan', name: 'Karyawan', icon: 'bi bi-briefcase' },
  { id: 'operator', name: 'Operator', icon: 'bi bi-person-gear' },
  { id: 'naikkan_kelas', name: 'Naikkan Kelas', icon: 'bi bi-arrow-up-circle' },
  { id: 'mutasi', name: 'Log Mutasi', icon: 'bi bi-person-x' },
  { id: 'profile_rapot', name: 'Profile Rapot', icon: 'bi bi-file-earmark-person' },
];

const searchQuery = ref(props.filters?.search || '');
const filterTenantId = ref(props.filters?.tenant_id || '');
const filterJenjang = ref(props.filters?.jenjang || '');
const filterKelas = ref(props.filters?.kelas || '');
const filterStatus = ref(props.filters?.status || 'Aktif');
const perPage = ref(Number(props.filters?.per_page) || 15);

// Computed Reactive Kelas List berdasarkan Tenant yang dipilih
const filteredKelasList = computed(() => {
  if (!props.kelasList || props.kelasList.length === 0) return [];
  const distinctTenants = new Set(props.kelasList.map(k => k.tenant_id).filter(Boolean));
  if (props.isSuperAdmin && filterTenantId.value && distinctTenants.size > 1) {
    return props.kelasList.filter(k => k.tenant_id === filterTenantId.value);
  }
  return props.kelasList;
});

// Parameter Penandatanganan Rapot
const printTempat = ref(props.filters?.tempat || 'Jakarta');
const printTanggal = ref(props.filters?.tanggal || new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }));

// Modal Unggah Foto Masal (ZIP)
const openBulkPhotoModal = ref(false);
const bulkPhotoFile = ref(null);
const bulkPhotoLoading = ref(false);
const bulkPhotoResult = ref('');

const onBulkPhotoFileChange = (e) => {
  if (e.target.files && e.target.files[0]) {
    bulkPhotoFile.value = e.target.files[0];
  }
};

const submitBulkPhotos = async () => {
  if (!bulkPhotoFile.value) {
    alert('Silakan pilih berkas ZIP terlebih dahulu.');
    return;
  }
  bulkPhotoLoading.value = true;
  bulkPhotoResult.value = '';
  
  const formData = new FormData();
  formData.append('file_zip', bulkPhotoFile.value);
  if (filterTenantId.value) {
    formData.append('tenant_id', filterTenantId.value);
  }

  try {
    const res = await axios.post('/pengguna/upload-bulk-photos', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    if (res.data?.success) {
      bulkPhotoResult.value = res.data.message;
      flashMessage.value = res.data.message;
      setTimeout(() => {
        openBulkPhotoModal.value = false;
        applyFilters();
      }, 1500);
    } else {
      bulkPhotoResult.value = res.data?.message || 'Gagal mengunggah foto masal.';
    }
  } catch (err) {
    console.error('Upload bulk photo error:', err);
    bulkPhotoResult.value = 'Terjadi kesalahan saat mengunggah berkas ZIP: ' + (err.response?.data?.message || err.message);
  } finally {
    bulkPhotoLoading.value = false;
  }
};

const printSingleRapot = (siswaId) => {
  const url = `/cetak-rapot?id=${siswaId}&tempat=${encodeURIComponent(printTempat.value || 'Jakarta')}&tanggal=${encodeURIComponent(printTanggal.value || '')}`;
  window.open(url, '_blank');
};

const printBulkRapot = () => {
  if (!filterKelas.value) {
    alert('Silakan pilih kelas / rombel terlebih dahulu untuk mencetak rapot kelas masal.');
    return;
  }
  let url = `/cetak-rapot-kelas?kelas_id=${filterKelas.value}&tempat=${encodeURIComponent(printTempat.value || 'Jakarta')}&tanggal=${encodeURIComponent(printTanggal.value || '')}`;
  if (filterStatus.value) {
    url += `&status=${encodeURIComponent(filterStatus.value)}`;
  }
  if (filterTenantId.value) {
    url += `&tenant_id=${encodeURIComponent(filterTenantId.value)}`;
  }
  window.open(url, '_blank');
};

let searchDebounceTimeout = null;
const handleSearchDebounce = () => {
  clearTimeout(searchDebounceTimeout);
  searchDebounceTimeout = setTimeout(() => {
    applyFilters();
  }, 350);
};

const applyFilters = () => {
  router.get('/pengguna', {
    tab: props.activeTab,
    search: searchQuery.value,
    tenant_id: filterTenantId.value,
    jenjang: filterJenjang.value,
    kelas: filterKelas.value,
    status: filterStatus.value,
    tempat: printTempat.value,
    tanggal: printTanggal.value,
    per_page: perPage.value,
    trash: props.filters?.trash ? 1 : 0,
  }, {
    preserveState: true,
    replace: true,
  });
};

const resetFilters = () => {
  searchQuery.value = '';
  filterJenjang.value = '';
  filterKelas.value = '';
  filterStatus.value = '';
  applyFilters();
};

const goToPage = (url) => {
  if (!url) return;
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  });
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

const switchTab = (tabId) => {
  router.get('/pengguna', {
    tab: tabId,
    tenant_id: filterTenantId.value,
    trash: props.filters?.trash ? 1 : 0,
  }, {
    preserveState: true,
    replace: true,
  });
};

const toggleTrash = () => {
  router.get('/pengguna', {
    tab: props.activeTab,
    tenant_id: filterTenantId.value,
    trash: props.filters?.trash ? 0 : 1,
  }, {
    preserveState: true,
    replace: true,
  });
};

const scrollTabs = (offset) => {
  const container = document.getElementById('penggunaSubNavTabs');
  if (container) {
    container.scrollBy({ left: offset, behavior: 'smooth' });
  }
};

const getActiveTabTitle = () => {
  switch (props.activeTab) {
    case 'siswa': return 'Siswa';
    case 'guru': return 'Guru';
    case 'karyawan': return 'Karyawan';
    case 'operator': return 'Operator';
    case 'naikkan_kelas': return 'Kenaikan & Mutasi';
    case 'mutasi': return 'Log Mutasi';
    case 'profile_rapot': return 'Profile Rapot';
    default: return 'Data';
  }
};

const getSelectedTenantName = () => {
  if (!filterTenantId.value) {
    return 'Semua Sekolah Terdaftar (Super Admin)';
  }
  const found = (props.tenants || []).find(t => t.id === filterTenantId.value);
  return found ? found.nama_sekolah : 'Sekolah Terpilih';
};

const calculateCompleteness = (s) => {
  if (typeof s?.persentase_kelengkapan === 'number') {
    return s.persentase_kelengkapan;
  }
  if (typeof s?.kelengkapan_persen === 'number') {
    return s.kelengkapan_persen;
  }
  if (s?.kelengkapan_step) {
    const total = Object.values(s.kelengkapan_step).reduce((acc, val) => acc + (Number(val) || 0), 0);
    return Math.min(100, Math.round(total));
  }

  // Step 1: Identitas & Akademik (20%)
  const s1 = [s?.nama_lengkap, s?.nisn, s?.nis, s?.nik, s?.jenis_kelamin, s?.tempat_lahir, s?.tanggal_lahir, s?.agama, s?.kewarganegaraan, s?.kelas_saat_ini];
  const s1Score = (s1.filter(f => f && String(f).trim() !== '').length / s1.length) * 20;

  // Step 2: Alamat & Kontak (20%)
  const s2 = [s?.alamat || s?.alamat_kk, s?.alamat_domisili || s?.alamat, s?.rt, s?.rw, s?.kode_pos, s?.id_provinsi, s?.status_tinggal, s?.email, s?.no_hp || s?.no_telepon_siswa];
  const s2Score = (s2.filter(f => f && String(f).trim() !== '').length / s2.length) * 20;

  // Step 3: Fisik & Kesejahteraan (20%)
  const s3 = [s?.tinggi_badan, s?.berat_badan, s?.lingkar_kepala, s?.golongan_darah, s?.anak_ke, s?.jumlah_saudara, s?.jarak_rumah, s?.transportasi];
  const s3Score = (s3.filter(f => f !== null && f !== undefined && String(f).trim() !== '').length / s3.length) * 20;

  // Step 4: Orang Tua / Wali (20%)
  const s4Has = (s?.orangTua && s.orangTua.length > 0) || s?.nama_ibu || s?.nama_ayah || s?.nik_ibu;
  const s4Score = s4Has ? 20 : 0;

  // Step 5: Registrasi & Riwayat Masuk (20%)
  const s5 = [s?.status_siswa || s?.jenis_pendaftaran, s?.tahun_masuk || s?.tanggal_masuk, s?.sekolah_asal || s?.asal_sekolah];
  const s5Score = (s5.filter(f => f && String(f).trim() !== '').length / s5.length) * 20;

  return Math.min(100, Math.round(s1Score + s2Score + s3Score + s4Score + s5Score));
};

// ==========================================
// MODAL STATES & LOGIC
// ==========================================

const openImportModal = ref(false);

// Quick Add Siswa
const openQuickAddModal = ref(false);
const quickAddForm = ref({
  nama_lengkap: '',
  nisn: '',
  nis: '',
  jenis_kelamin: 'L',
  kelas_saat_ini: '',
  tenant_id: filterTenantId.value || '',
});

const submitQuickAdd = () => {
  quickAddForm.value.tenant_id = filterTenantId.value || '';
  router.post('/pengguna/quick-add', quickAddForm.value, {
    onSuccess: () => {
      openQuickAddModal.value = false;
      quickAddForm.value = { nama_lengkap: '', nisn: '', nis: '', jenis_kelamin: 'L', kelas_saat_ini: '', tenant_id: '' };
      flashMessage.value = 'Siswa baru berhasil diregistrasi dengan cepat!';
    },
  });
};

// User Form (GTK Multi-Role)
const openUserModal = ref(false);
const isEditingUser = ref(false);
const editingUserId = ref(null);
const userForm = ref({
  nama_lengkap: '',
  nip: '',
  username: '',
  email: '',
  password: '',
  role: 'guru',
  no_hp: '',
  is_wali_kelas: false,
  is_bk: false,
  is_kurikulum: false,
  is_kesiswaan: false,
  is_sarpras: false,
  is_humas: false,
  is_perpustakaan: false,
  is_keuangan: false,
  is_pembina_ekskul: false,
  is_operator: false,
});

const openAddModal = () => {
  isEditingUser.value = false;
  editingUserId.value = null;
  userForm.value = {
    nama_lengkap: '',
    nip: '',
    username: '',
    email: '',
    password: '',
    role: props.activeTab === 'guru' ? 'guru' : (props.activeTab === 'karyawan' ? 'karyawan' : 'operator_sekolah'),
    no_hp: '',
    is_wali_kelas: false,
    is_bk: false,
    is_kurikulum: false,
    is_kesiswaan: false,
    is_sarpras: false,
    is_humas: false,
    is_perpustakaan: false,
    is_keuangan: false,
    is_pembina_ekskul: false,
    is_operator: false,
  };
  openUserModal.value = true;
};

const editUser = (item) => {
  isEditingUser.value = true;
  editingUserId.value = item.id;
  userForm.value = {
    nama_lengkap: item.nama_lengkap || '',
    nip: item.nip || '',
    username: item.username || '',
    email: item.email || '',
    password: '',
    role: item.role?.nama_role || item.role || 'guru',
    no_hp: item.no_hp || '',
    is_wali_kelas: Boolean(item.is_wali_kelas),
    is_bk: Boolean(item.is_bk),
    is_kurikulum: Boolean(item.is_kurikulum),
    is_kesiswaan: Boolean(item.is_kesiswaan),
    is_sarpras: Boolean(item.is_sarpras),
    is_humas: Boolean(item.is_humas),
    is_perpustakaan: Boolean(item.is_perpustakaan),
    is_keuangan: Boolean(item.is_keuangan),
    is_pembina_ekskul: Boolean(item.is_pembina_ekskul),
    is_operator: Boolean(item.is_operator),
  };
  openUserModal.value = true;
};

const submitUserForm = () => {
  const payload = { ...userForm.value, tab: 'user', tenant_id: filterTenantId.value || '' };
  if (isEditingUser.value) {
    router.put(`/pengguna/${editingUserId.value}`, payload, {
      onSuccess: () => {
        openUserModal.value = false;
        flashMessage.value = 'Data pengguna berhasil diperbarui!';
      },
    });
  } else {
    router.post('/pengguna', payload, {
      onSuccess: () => {
        openUserModal.value = false;
        flashMessage.value = 'Data pengguna berhasil ditambahkan!';
      },
    });
  }
};

// Delete & Restore
const deleteItem = (id, type) => {
  if (confirm('Apakah Anda yakin ingin memindahkan data ini ke tong sampah?')) {
    router.delete(`/pengguna/${id}`, {
      data: { tab: type },
      onSuccess: () => {
        flashMessage.value = 'Data berhasil dipindahkan ke tong sampah.';
      },
    });
  }
};

const restoreItem = (id, type) => {
  router.post(`/pengguna/restore/${id}`, { tab: type }, {
    onSuccess: () => {
      flashMessage.value = 'Data berhasil dipulihkan kembali ke daftar aktif!';
    },
  });
};

// Naikkan Kelas & Mutasi Siswa
const promoteForm = ref({
  mode: 'promote',
  kelas_asal_id: '',
  kelas_tujuan_id: '',
  tahun_ajaran: props.tahunAjaranList?.[0]?.tahun_ajaran || props.tahunAjaranList?.[0]?.nama_tahun_ajaran || '2026/2027',
  catatan: '',
  siswa_ids: [],
});

const promoteListSiswa = ref(props.items?.initialSiswaList || []);
const promoteLoadingSiswa = ref(false);
const promoteSubmitLoading = ref(false);
const promoteSelectAll = ref(false);

// Modal Riwayat Siswa
const openRiwayatModal = ref(false);
const selectedSiswaForRiwayat = ref(null);
const listRiwayatSiswa = ref([]);
const loadingRiwayat = ref(false);

const onTenantChangePromote = () => {
  promoteForm.value.kelas_asal_id = '';
  promoteForm.value.kelas_tujuan_id = '';
  promoteListSiswa.value = [];
  promoteForm.value.siswa_ids = [];
  promoteSelectAll.value = false;
};

// Pastikan pilihan kelas dan siswa ter-reset saat filter sekolah di atas berganti
watch(filterTenantId, () => {
  onTenantChangePromote();
});

watch(() => props.filters?.tenant_id, (newVal) => {
  if (filterTenantId.value !== newVal) {
    filterTenantId.value = newVal || '';
  }
  onTenantChangePromote();
});

const onKelasAsalChange = async () => {
  promoteForm.value.siswa_ids = [];
  promoteSelectAll.value = false;
  if (!promoteForm.value.kelas_asal_id) {
    promoteListSiswa.value = [];
    return;
  }
  promoteLoadingSiswa.value = true;
  try {
    const res = await axios.get('/pengguna/siswa-by-kelas', {
      params: {
        kelas_id: promoteForm.value.kelas_asal_id,
        tenant_id: filterTenantId.value,
      },
    });
    if (res.data?.success) {
      promoteListSiswa.value = res.data.data || [];
    } else {
      promoteListSiswa.value = [];
    }
  } catch (err) {
    console.error('Gagal mengambil daftar siswa:', err);
    promoteListSiswa.value = [];
  } finally {
    promoteLoadingSiswa.value = false;
  }
};

const togglePromoteSelectAll = () => {
  promoteSelectAll.value = !promoteSelectAll.value;
  if (promoteSelectAll.value) {
    promoteForm.value.siswa_ids = promoteListSiswa.value.map(s => s.id);
  } else {
    promoteForm.value.siswa_ids = [];
  }
};

const toggleSelectStudent = (id) => {
  const index = promoteForm.value.siswa_ids.indexOf(id);
  if (index > -1) {
    promoteForm.value.siswa_ids.splice(index, 1);
  } else {
    promoteForm.value.siswa_ids.push(id);
  }
  promoteSelectAll.value = promoteListSiswa.value.length > 0 && promoteForm.value.siswa_ids.length === promoteListSiswa.value.length;
};

const viewRiwayatSiswa = async (siswa) => {
  selectedSiswaForRiwayat.value = siswa;
  openRiwayatModal.value = true;
  loadingRiwayat.value = true;
  listRiwayatSiswa.value = [];
  try {
    const res = await axios.get(`/pengguna/riwayat-siswa/${siswa.id}`);
    if (res.data?.success) {
      listRiwayatSiswa.value = res.data.data || [];
    }
  } catch (e) {
    console.error('Gagal memuat riwayat:', e);
  } finally {
    loadingRiwayat.value = false;
  }
};

const submitPromote = () => {
  if (!promoteForm.value.kelas_asal_id) {
    alert('Silakan pilih kelas asal terlebih dahulu.');
    return;
  }
  if (['promote', 'pindah', 'retain'].includes(promoteForm.value.mode) && !promoteForm.value.kelas_tujuan_id) {
    alert('Silakan pilih kelas tujuan terlebih dahulu.');
    return;
  }
  if (promoteForm.value.siswa_ids.length === 0) {
    alert('Pilih minimal 1 siswa yang akan diproses.');
    return;
  }

  const actionLabels = {
    promote: 'Kenaikan Kelas',
    pindah: 'Pindah Rombel Paralel',
    retain: 'Penetapan Tinggal Kelas',
    graduate: 'Kelulusan Siswa (Alumni)',
    mutasi_keluar: 'Siswa Keluar / Mutasi',
  };
  const actName = actionLabels[promoteForm.value.mode] || 'Aksi ini';

  const confirmMsg = `Konfirmasi ${actName}:\n\nAnda akan memproses ${promoteForm.value.siswa_ids.length} siswa untuk Tahun Ajaran ${promoteForm.value.tahun_ajaran}.\n\nLanjutkan proses ini?`;
  if (!confirm(confirmMsg)) return;

  promoteSubmitLoading.value = true;
  router.post('/pengguna/promote', {
    ...promoteForm.value,
    tenant_id: filterTenantId.value,
  }, {
    onSuccess: (page) => {
      promoteSubmitLoading.value = false;
      flashMessage.value = `Berhasil memproses ${actName} untuk ${promoteForm.value.siswa_ids.length} siswa!`;
      onKelasAsalChange(); // Refresh list siswa di kelas asal
    },
    onError: (errors) => {
      promoteSubmitLoading.value = false;
      alert('Terjadi kesalahan: ' + Object.values(errors).join(', '));
    },
    onFinish: () => {
      promoteSubmitLoading.value = false;
    }
  });
};

onMounted(() => {
  if (props.activeTab === 'naikkan_kelas' && promoteForm.value.kelas_asal_id) {
    onKelasAsalChange();
  }
});
</script>

