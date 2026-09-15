<template>
  <AppLayout title="Pencetakan Rapor & Hasil Belajar">
    <div class="space-y-6 max-w-7xl mx-auto pb-16">
      
      <!-- HERO HEADER SECTION -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 text-white p-6 sm:p-8 rounded-3xl shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none text-9xl">
          <i class="bi bi-file-earmark-pdf-fill"></i>
        </div>

        <div class="space-y-1.5 relative z-10">
          <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-semibold text-indigo-200 border border-white/10">
            <i class="bi bi-patch-check-fill text-emerald-400"></i> Modul Akademik & Penilaian
          </div>
          <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
            Pusat Pencetakan Rapor & Hasil Belajar
          </h1>
          <p class="text-xs sm:text-sm text-slate-300 max-w-2xl font-normal leading-relaxed">
            Monitoring kelengkapan capaian kompetensi siswa, pratinjau lembar rapor interaktif, ekspor ledger nilai, dan cetak massal satu rombel secara instan.
          </p>
        </div>

        <!-- QUICK ACTION BUTTONS -->
        <div class="flex flex-wrap items-center gap-2.5 relative z-10">
          <button 
            @click="activeTab = 'massal'"
            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-extrabold shadow-lg shadow-blue-600/30 transition-all flex items-center gap-2 active:scale-95"
          >
            <i class="bi bi-printer-fill"></i> Cetak Massal Rombel
          </button>
          
          <button 
            @click="downloadLedger"
            :disabled="!filterForm.kelas_id || loading"
            class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl text-xs font-bold transition-all flex items-center gap-2 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <i class="bi bi-file-earmark-excel-fill text-emerald-400"></i> Unduh Ledger (.xlsx)
          </button>

          <button 
            @click="fetchRaporData"
            :disabled="loading"
            class="p-2.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl text-xs font-bold transition-all flex items-center justify-center active:scale-95"
            title="Muat Ulang Data"
          >
            <i class="bi bi-arrow-clockwise text-base" :class="{'animate-spin': loading}"></i>
          </button>
        </div>
      </div>

      <!-- FILTER CONTROLS BAR -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
            <i class="bi bi-sliders text-indigo-600 text-sm"></i> Filter Rombel & Parameter Rapor
          </div>
          <span class="text-[11px] text-slate-400">
            Status: <span class="font-semibold text-slate-600">{{ filterForm.nama_kelas || 'Pilih Rombel' }}</span> (Semester {{ filterForm.semester }})
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end">
          
          <!-- MULTI-TENANT SWITCHER (Super Admin) -->
          <div v-if="tenants && tenants.length > 0" class="lg:col-span-3">
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Sekolah / Tenant</label>
            <SearchableSelect 
              v-model="filterForm.tenant_id"
              :options="tenants"
              placeholder="-- Pilih Sekolah --"
              search-placeholder="Cari nama sekolah / NPSN..."
              @change="handleFilterChange"
            />
          </div>

          <!-- TAHUN AJARAN -->
          <div :class="tenants && tenants.length > 0 ? 'lg:col-span-3' : 'lg:col-span-3'">
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Tahun Ajaran</label>
            <SearchableSelect 
              v-model="filterForm.tahun_ajaran_id"
              :options="tahunAjaranOptions"
              placeholder="-- Pilih Tahun Ajaran --"
              search-placeholder="Cari tahun ajaran..."
              @change="handleFilterChange"
            />
          </div>

          <!-- SEMESTER -->
          <div :class="tenants && tenants.length > 0 ? 'lg:col-span-2' : 'lg:col-span-3'">
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Semester</label>
            <SearchableSelect 
              v-model="filterForm.semester"
              :options="semesterOptions"
              placeholder="-- Pilih Semester --"
              search-placeholder="Pilih semester..."
              @change="handleFilterChange"
            />
          </div>

          <!-- KELAS / ROMBEL -->
          <div :class="tenants && tenants.length > 0 ? 'lg:col-span-4' : 'lg:col-span-4'">
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel / Kelas Target</label>
            <SearchableSelect 
              v-model="filterForm.kelas_id"
              :options="kelasOptions"
              placeholder="-- Pilih Rombel / Kelas --"
              search-placeholder="Cari rombel atau jurusan..."
              @change="handleFilterChange"
            />
          </div>

          <!-- SEARCH FILTER -->
          <div :class="tenants && tenants.length > 0 ? 'lg:col-span-12' : 'lg:col-span-2'">
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Pencarian Siswa</label>
            <div class="relative">
              <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input 
                v-model="filterForm.search"
                @input="handleSearchDebounced"
                type="text"
                placeholder="Cari nama / NISN..."
                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition"
              />
            </div>
          </div>

        </div>
      </div>

      <!-- STAT CARDS METRICS -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        
        <!-- Total Siswa -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="min-w-0">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Siswa</div>
            <div class="text-xl font-black text-slate-800 tracking-tight">{{ stats.total_siswa }}</div>
          </div>
        </div>

        <!-- Siap Cetak (Lengkap) -->
        <div class="bg-white rounded-2xl border border-emerald-100 p-4 shadow-xs flex items-center gap-3.5 bg-emerald-50/20">
          <div class="w-11 h-11 rounded-xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center text-xl shrink-0">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="min-w-0">
            <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Siap Cetak</div>
            <div class="text-xl font-black text-emerald-800 tracking-tight">{{ stats.siap_cetak }}</div>
          </div>
        </div>

        <!-- Belum Lengkap -->
        <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-xs flex items-center gap-3.5 bg-amber-50/20">
          <div class="w-11 h-11 rounded-xl bg-amber-100/80 text-amber-700 flex items-center justify-center text-xl shrink-0">
            <i class="bi bi-exclamation-circle-fill"></i>
          </div>
          <div class="min-w-0">
            <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Belum Lengkap</div>
            <div class="text-xl font-black text-amber-800 tracking-tight">{{ stats.belum_lengkap }}</div>
          </div>
        </div>

        <!-- Rata-rata Nilai Kelas -->
        <div class="bg-white rounded-2xl border border-purple-100 p-4 shadow-xs flex items-center gap-3.5 bg-purple-50/20">
          <div class="w-11 h-11 rounded-xl bg-purple-100/80 text-purple-700 flex items-center justify-center text-xl shrink-0">
            <i class="bi bi-trophy-fill"></i>
          </div>
          <div class="min-w-0">
            <div class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">Rerata Rombel</div>
            <div class="text-xl font-black text-purple-900 tracking-tight">{{ stats.rata_rata_kelas }}</div>
          </div>
        </div>

        <!-- Total Mapel Terdaftar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex items-center gap-3.5 col-span-2 sm:col-span-1">
          <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
          <div class="min-w-0">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mapel Rombel</div>
            <div class="text-xl font-black text-slate-800 tracking-tight">{{ stats.total_mapel }} <span class="text-xs font-normal text-slate-400">Mapel</span></div>
          </div>
        </div>

      </div>

      <!-- MAIN TABS NAVIGATION -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex border-b border-slate-200 bg-slate-50/50 p-2 gap-1.5 overflow-x-auto">
          
          <button 
            @click="activeTab = 'siswa'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap',
              activeTab === 'siswa' 
                ? 'bg-white text-blue-600 shadow-xs border border-slate-200/80' 
                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
            ]"
          >
            <i class="bi bi-person-lines-fill"></i>
            Daftar Siswa & Status Rapor
            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-600">
              {{ students.length }}
            </span>
          </button>

          <button 
            @click="activeTab = 'massal'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap',
              activeTab === 'massal' 
                ? 'bg-white text-blue-600 shadow-xs border border-slate-200/80' 
                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
            ]"
          >
            <i class="bi bi-printer-fill"></i>
            Cetak Massal & Worker Queue
          </button>

          <button 
            @click="activeTab = 'ledger'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap',
              activeTab === 'ledger' 
                ? 'bg-white text-blue-600 shadow-xs border border-slate-200/80' 
                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
            ]"
          >
            <i class="bi bi-table"></i>
            Matriks Ledger Nilai Rombel
          </button>

        </div>

        <!-- TAB 1: DAFTAR SISWA & STATUS RAPOR -->
        <div v-if="activeTab === 'siswa'" class="p-5 space-y-4">
          
          <!-- FILTER STATUS CHIPS -->
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-xs font-bold text-slate-500 mr-1">Filter Kelengkapan:</span>
              <button 
                @click="setStatusFilter('semua')"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                  filterForm.status === 'semua' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                ]"
              >
                Semua ({{ students.length }})
              </button>
              <button 
                @click="setStatusFilter('siap_cetak')"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                  filterForm.status === 'siap_cetak' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                ]"
              >
                Siap Cetak ({{ stats.siap_cetak }})
              </button>
              <button 
                @click="setStatusFilter('belum_lengkap')"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                  filterForm.status === 'belum_lengkap' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                ]"
              >
                Belum Lengkap ({{ stats.belum_lengkap }})
              </button>
            </div>

            <div class="text-xs text-slate-500">
              Menampilkan <span class="font-bold text-slate-800">{{ filteredStudents.length }}</span> dari {{ students.length }} siswa
            </div>
          </div>

          <!-- LOADING STATE -->
          <div v-if="loading" class="py-16 text-center space-y-3">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
            <p class="text-xs font-semibold text-slate-500">Memuat data siswa dan capaian penilaian...</p>
          </div>

          <!-- EMPTY STATE -->
          <div v-else-if="filteredStudents.length === 0" class="py-16 text-center space-y-3">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center text-3xl mx-auto">
              <i class="bi bi-people"></i>
            </div>
            <h4 class="text-sm font-bold text-slate-700">Tidak Ada Data Siswa Ditemukan</h4>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
              Silakan periksa pilihan rombel atau ubah kata kunci pencarian Anda.
            </p>
          </div>

          <!-- TABLE SISWA -->
          <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-black text-slate-500 uppercase tracking-wider">
                  <th class="py-3 px-4 w-12 text-center">No</th>
                  <th class="py-3 px-4">Nama Siswa & NISN</th>
                  <th class="py-3 px-4 text-center">L/P</th>
                  <th class="py-3 px-4">Progres Mapel Terisi</th>
                  <th class="py-3 px-4 text-center">Rerata Nilai</th>
                  <th class="py-3 px-4 text-center">Status Rapor</th>
                  <th class="py-3 px-4 text-center w-56">Aksi Pratinjau & Cetak</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs text-slate-700 font-medium">
                <tr v-for="(student, index) in filteredStudents" :key="student.id" class="hover:bg-slate-50/80 transition">
                  <td class="py-3 px-4 text-center font-bold text-slate-400">{{ index + 1 }}</td>
                  
                  <td class="py-3 px-4">
                    <div class="font-extrabold text-slate-900">{{ student.nama_lengkap }}</div>
                    <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                      <span>NISN: <strong class="text-slate-600">{{ student.nisn }}</strong></span>
                      <span>•</span>
                      <span>NIS: {{ student.nis }}</span>
                    </div>
                  </td>

                  <td class="py-3 px-4 text-center">
                    <span 
                      class="px-2 py-0.5 rounded text-[10px] font-extrabold"
                      :class="student.jenis_kelamin === 'L' || student.jenis_kelamin === 'Laki-laki' ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700'"
                    >
                      {{ student.jenis_kelamin === 'L' || student.jenis_kelamin === 'Laki-laki' ? 'L' : 'P' }}
                    </span>
                  </td>

                  <td class="py-3 px-4">
                    <div class="space-y-1.5 min-w-[140px]">
                      <div class="flex items-center justify-between text-[11px]">
                        <span class="font-bold text-slate-700">{{ student.mapel_terisi }} / {{ student.total_mapel }} Mapel</span>
                        <span class="font-black text-slate-500">{{ student.progress_percent }}%</span>
                      </div>
                      <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div 
                          class="h-full rounded-full transition-all duration-300"
                          :class="student.is_lengkap ? 'bg-emerald-500' : 'bg-amber-500'"
                          :style="{ width: `${student.progress_percent}%` }"
                        ></div>
                      </div>
                    </div>
                  </td>

                  <td class="py-3 px-4 text-center">
                    <span 
                      class="px-2.5 py-1 rounded-lg font-black text-xs"
                      :class="student.rata_rata_nilai >= 75 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                    >
                      {{ student.rata_rata_nilai || '0' }}
                    </span>
                  </td>

                  <td class="py-3 px-4 text-center">
                    <span 
                      v-if="student.is_lengkap"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100/80 text-emerald-800"
                    >
                      <i class="bi bi-check-circle-fill text-emerald-600"></i> Siap Cetak
                    </span>
                    <span 
                      v-else
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100/80 text-amber-800"
                    >
                      <i class="bi bi-clock-history text-amber-600"></i> Belum Lengkap
                    </span>
                  </td>

                  <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      
                      <!-- Preview Rapor Modal -->
                      <button 
                        @click="openPreviewModal(student, 'rapor')"
                        class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold transition flex items-center gap-1"
                        title="Pratinjau Lembar Rapor Nilai"
                      >
                        <i class="bi bi-eye-fill"></i>
                        <span class="hidden sm:inline">Rapor</span>
                      </button>

                      <!-- Cetak Langsung New Tab -->
                      <a 
                        :href="`/akademik/rapor/preview-html/${student.id}?semester=${filterForm.semester}&tahun_ajaran=${encodeURIComponent(filterForm.tahun_ajaran)}`"
                        target="_blank"
                        class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition"
                        title="Buka Cetak Lembar Rapor (Tab Baru)"
                      >
                        <i class="bi bi-printer"></i>
                      </a>

                      <!-- Preview Identitas Siswa -->
                      <button 
                        @click="openPreviewModal(student, 'identitas')"
                        class="p-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition"
                        title="Lembar Identitas Buku Induk"
                      >
                        <i class="bi bi-person-vcard"></i>
                      </button>

                      <!-- Direct Link Input Nilai -->
                      <a 
                        :href="`/akademik/penilaian?kelas_id=${filterForm.kelas_id}&semester=${filterForm.semester}`"
                        class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg text-xs transition"
                        title="Buka Lembar Penilaian Rombel"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </a>

                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

        <!-- TAB 2: CETAK MASSAL & WORKER QUEUE -->
        <div v-if="activeTab === 'massal'" class="p-6 space-y-6">
          
          <div class="max-w-3xl mx-auto space-y-6">
            
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200/80 rounded-2xl p-5 space-y-2">
              <h3 class="text-sm font-extrabold text-blue-900 flex items-center gap-2">
                <i class="bi bi-printer-fill text-blue-600"></i> Pengaturan Cetak Massal Rapor Satu Rombel
              </h3>
              <p class="text-xs text-blue-700 leading-relaxed">
                Anda dapat mencetak seluruh lembar rapor siswa pada rombel <strong>{{ filterForm.nama_kelas || 'Rombel Terpilih' }}</strong> secara langsung melalui dialog cetak browser atau menjadwalkan render PDF resolusi tinggi via antrean Background Worker.
              </p>
            </div>

            <!-- FORM PARAMETER CETAK -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-2xs">
              <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2.5">
                Parameter Lembar Cetak
              </h4>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Titimangsa Rapor</label>
                  <input 
                    v-model="bulkForm.tempat"
                    type="text"
                    placeholder="Contoh: Jakarta / Surabaya"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:bg-white focus:outline-none"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Cetak Rapor</label>
                  <input 
                    v-model="bulkForm.tanggal"
                    type="text"
                    placeholder="Contoh: 15 Desember 2026"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:bg-white focus:outline-none"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1">Nama Wali Kelas</label>
                  <input 
                    v-model="bulkForm.wali_kelas"
                    type="text"
                    placeholder="Nama lengkap wali kelas beserta gelar..."
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:bg-white focus:outline-none"
                  />
                </div>
              </div>
            </div>

            <!-- PILIHAN OPSI CETAK MASSAL -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              
              <!-- 1. Cetak HTML Massal Instan (Multi-Page) -->
              <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3 flex flex-col justify-between hover:border-blue-300 transition">
                <div class="space-y-2">
                  <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="bi bi-file-earmark-richtext-fill"></i>
                  </div>
                  <h4 class="text-sm font-extrabold text-slate-800">Cetak Massal HTML (Semua Siswa)</h4>
                  <p class="text-xs text-slate-500 leading-relaxed">
                    Membuka seluruh lembar capaian nilai siswa dalam satu dokumen print-ready lengkap dengan pemisah halaman otomatis (Page Breaks).
                  </p>
                </div>

                <a 
                  :href="`/akademik/rapor/preview-kelas?kelas_id=${filterForm.kelas_id}&semester=${filterForm.semester}&tempat=${encodeURIComponent(bulkForm.tempat)}&tanggal=${encodeURIComponent(bulkForm.tanggal)}&wali_kelas=${encodeURIComponent(bulkForm.wali_kelas)}`"
                  target="_blank"
                  class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-extrabold transition shadow-sm flex items-center justify-center gap-2 text-center"
                >
                  <i class="bi bi-printer-fill"></i> Buka Lembar Cetak 1 Rombel
                </a>
              </div>

              <!-- 2. Cetak Massal Identitas Siswa -->
              <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3 flex flex-col justify-between hover:border-indigo-300 transition">
                <div class="space-y-2">
                  <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                    <i class="bi bi-person-vcard-fill"></i>
                  </div>
                  <h4 class="text-sm font-extrabold text-slate-800">Cetak Massal Lembar Identitas</h4>
                  <p class="text-xs text-slate-500 leading-relaxed">
                    Mencetak seluruh lembar data diri dan buku induk siswa satu kelas format resmi A4 Kemendikbud.
                  </p>
                </div>

                <a 
                  :href="`/akademik/rapor/preview-identitas-kelas?kelas_id=${filterForm.kelas_id}&tempat=${encodeURIComponent(bulkForm.tempat)}&tanggal=${encodeURIComponent(bulkForm.tanggal)}`"
                  target="_blank"
                  class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-extrabold transition shadow-sm flex items-center justify-center gap-2 text-center"
                >
                  <i class="bi bi-printer-fill"></i> Buka Identitas 1 Rombel
                </a>
              </div>

            </div>

            <!-- 3. Jadwalkan Antrean PDF Background Worker -->
            <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-md space-y-4">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-600/30 border border-blue-400/30 text-blue-400 flex items-center justify-center text-2xl shrink-0">
                  <i class="bi bi-cpu-fill"></i>
                </div>
                <div class="space-y-1">
                  <h4 class="text-sm font-extrabold text-white">Generate PDF Massal via Background Queue Worker</h4>
                  <p class="text-xs text-slate-300 leading-relaxed">
                    Tugas cetak PDF akan dieksekusi secara asinkronus oleh service worker Puppeteer/Browsershot di latar belakang server tanpa membebani browser pengguna.
                  </p>
                </div>
              </div>

              <div class="pt-2">
                <button 
                  @click="dispatchBulkJob"
                  :disabled="dispatching || !filterForm.kelas_id"
                  class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-extrabold transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed active:scale-98"
                >
                  <i class="bi bi-cloud-arrow-up-fill" :class="{'animate-bounce': dispatching}"></i>
                  {{ dispatching ? 'Menjadwalkan Antrean Worker...' : 'Jadwalkan Eksekusi PDF Massal ke Antrean Server' }}
                </button>
              </div>
            </div>

          </div>

        </div>

        <!-- TAB 3: LEDGER NILAI KELAS -->
        <div v-if="activeTab === 'ledger'" class="p-5 space-y-4">
          
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-sm font-bold text-slate-800">Matriks Buku Ledger Nilai Siswa</h3>
              <p class="text-xs text-slate-400">Rekapitulasi seluruh capaian nilai mata pelajaran rombel {{ filterForm.nama_kelas }} (Semester {{ filterForm.semester }}).</p>
            </div>

            <button 
              @click="downloadLedger"
              :disabled="!filterForm.kelas_id"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-sm transition flex items-center gap-2 self-start active:scale-95 disabled:opacity-50"
            >
              <i class="bi bi-file-earmark-excel-fill"></i> Unduh Ledger Lengkap (.xlsx)
            </button>
          </div>

          <!-- TABLE LEDGER MATRIX -->
          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-black text-slate-700">
                  <th class="py-2.5 px-3 w-10 text-center border-r border-slate-200">No</th>
                  <th class="py-2.5 px-3 min-w-[180px] border-r border-slate-200">Nama Siswa</th>
                  <th class="py-2.5 px-2.5 w-12 text-center border-r border-slate-200">NISN</th>
                  <th 
                    v-for="sub in subjects" 
                    :key="sub.id" 
                    class="py-2.5 px-2 text-center border-r border-slate-200 min-w-[70px]"
                    :title="sub.nama_mapel"
                  >
                    {{ sub.kode_mapel || sub.nama_mapel.substring(0, 4) }}
                  </th>
                  <th class="py-2.5 px-3 text-center bg-blue-50/80 text-blue-900 border-r border-slate-200 w-16">Total</th>
                  <th class="py-2.5 px-3 text-center bg-indigo-50/80 text-indigo-900 w-16">Rerata</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                <tr v-for="(student, index) in students" :key="student.id" class="hover:bg-slate-50 transition">
                  <td class="py-2 px-3 text-center font-bold text-slate-400 border-r border-slate-200">{{ index + 1 }}</td>
                  <td class="py-2 px-3 font-bold text-slate-900 border-r border-slate-200">{{ student.nama_lengkap }}</td>
                  <td class="py-2 px-2.5 text-center text-[11px] text-slate-500 border-r border-slate-200">{{ student.nisn }}</td>
                  
                  <td 
                    v-for="sub in subjects" 
                    :key="sub.id" 
                    class="py-2 px-2 text-center border-r border-slate-200 text-[11px]"
                  >
                    <span 
                      v-if="getStudentScore(student, sub.id) !== null"
                      :class="getStudentScore(student, sub.id) >= 75 ? 'font-bold text-slate-800' : 'font-bold text-rose-600'"
                    >
                      {{ getStudentScore(student, sub.id) }}
                    </span>
                    <span v-else class="text-slate-300">-</span>
                  </td>

                  <td class="py-2 px-3 text-center font-black text-blue-700 bg-blue-50/30 border-r border-slate-200">
                    {{ getStudentTotal(student) }}
                  </td>

                  <td class="py-2 px-3 text-center font-black text-indigo-700 bg-indigo-50/30">
                    {{ student.rata_rata_nilai }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

      </div>

      <!-- IN-APP LIVE PREVIEW MODAL -->
      <div 
        v-if="showModalPreview"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/80 backdrop-blur-xs animate-fade-in"
      >
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl h-[92vh] flex flex-col overflow-hidden border border-slate-200">
          
          <!-- MODAL HEADER -->
          <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-lg text-white">
                <i :class="modalType === 'rapor' ? 'bi-file-earmark-richtext-fill' : 'bi-person-vcard-fill'"></i>
              </div>
              <div>
                <h3 class="text-sm font-extrabold text-white">
                  {{ modalType === 'rapor' ? 'Pratinjau Lembar Rapor Hasil Belajar' : 'Pratinjau Lembar Identitas Siswa' }}
                </h3>
                <p class="text-xs text-slate-300">
                  {{ selectedStudent?.nama_lengkap }} (NISN: {{ selectedStudent?.nisn }}) - {{ filterForm.nama_kelas }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button 
                @click="printIframe"
                class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
              >
                <i class="bi bi-printer-fill"></i> Cetak Dokumen
              </button>

              <button 
                @click="closePreviewModal"
                class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-white/10 transition"
              >
                <i class="bi bi-x-lg text-base"></i>
              </button>
            </div>
          </div>

          <!-- MODAL BODY IFRAME -->
          <div class="flex-1 bg-slate-100 p-2 sm:p-4 overflow-hidden relative">
            <iframe 
              ref="previewIframeRef"
              :src="previewUrl" 
              class="w-full h-full bg-white rounded-2xl shadow-inner border border-slate-200"
            ></iframe>
          </div>

        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js';

const props = defineProps({
  kelasList: Array,
  tahunAjaranList: Array,
  students: Array,
  subjects: Array,
  stats: Object,
  filters: Object,
  tenants: Array,
  activeTenantId: String,
});

// Reactive States
const loading = ref(false);
const dispatching = ref(false);
const activeTab = ref('siswa'); // 'siswa', 'massal', 'ledger'

const kelasList = ref([]);
const tahunAjaranList = ref([]);
const students = ref([]);
const subjects = ref([]);
const stats = ref({
  total_siswa: 0,
  siap_cetak: 0,
  belum_lengkap: 0,
  rata_rata_kelas: 0,
  total_mapel: 0,
});

const filterForm = ref({
  tenant_id: props.activeTenantId || '',
  tahun_ajaran_id: '',
  tahun_ajaran: '',
  semester: 'Ganjil',
  kelas_id: '',
  nama_kelas: '',
  search: '',
  status: 'semua',
});

const bulkForm = ref({
  tempat: 'Jakarta',
  tanggal: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
  wali_kelas: 'Wali Kelas',
});

// Modal Preview State
const showModalPreview = ref(false);
const modalType = ref('rapor'); // 'rapor' or 'identitas'
const selectedStudent = ref(null);
const previewIframeRef = ref(null);

useMemorySecurity([students, subjects, stats]);

// Select Options
const semesterOptions = [
  { id: 'Ganjil', nama: 'Semester Ganjil' },
  { id: 'Genap', nama: 'Semester Genap' },
];

const tahunAjaranOptions = computed(() => {
  return tahunAjaranList.value.map(t => ({
    id: t.id,
    nama: t.nama || t.nama_tahun_ajaran,
    subLabel: t.subLabel || (t.is_active ? 'Aktif' : ''),
  }));
});

const kelasOptions = computed(() => {
  return kelasList.value.map(k => ({
    id: k.id,
    nama: k.nama || k.nama_kelas,
    subLabel: k.subLabel || '',
  }));
});

// Filtered Students
const filteredStudents = computed(() => {
  let list = students.value || [];
  if (filterForm.value.search) {
    const q = filterForm.value.search.toLowerCase();
    list = list.filter(s => 
      s.nama_lengkap.toLowerCase().includes(q) || 
      (s.nisn && s.nisn.toLowerCase().includes(q)) ||
      (s.nis && s.nis.toLowerCase().includes(q))
    );
  }
  if (filterForm.value.status === 'siap_cetak') {
    list = list.filter(s => s.is_lengkap);
  } else if (filterForm.value.status === 'belum_lengkap') {
    list = list.filter(s => !s.is_lengkap);
  }
  return list;
});

// Modal Preview URL
const previewUrl = computed(() => {
  if (!selectedStudent.value) return '';
  if (modalType.value === 'identitas') {
    return `/akademik/rapor/preview-identitas/${selectedStudent.value.id}?tempat=${encodeURIComponent(bulkForm.value.tempat)}&tanggal=${encodeURIComponent(bulkForm.value.tanggal)}`;
  }
  return `/akademik/rapor/preview-html/${selectedStudent.value.id}?semester=${filterForm.value.semester}&tahun_ajaran=${encodeURIComponent(filterForm.value.tahun_ajaran)}&tempat=${encodeURIComponent(bulkForm.value.tempat)}&tanggal=${encodeURIComponent(bulkForm.value.tanggal)}&wali_kelas=${encodeURIComponent(bulkForm.value.wali_kelas)}`;
});

// Helper for Ledger Scores
const getStudentScore = (student, subjectId) => {
  if (!student.grades) return null;
  const g = student.grades.find(item => item.mata_pelajaran_id === subjectId);
  return g ? g.nilai_akhir : null;
};

const getStudentTotal = (student) => {
  if (!student.grades || student.grades.length === 0) return 0;
  return student.grades.reduce((sum, g) => sum + (g.nilai_akhir || 0), 0);
};

// Fetch Data via Axios API
const fetchRaporData = async () => {
  loading.value = true;
  try {
    const params = {
      async: 1,
      tenant_id: filterForm.value.tenant_id || undefined,
      tahun_ajaran_id: filterForm.value.tahun_ajaran_id || undefined,
      semester: filterForm.value.semester || undefined,
      kelas_id: filterForm.value.kelas_id || undefined,
      search: filterForm.value.search || undefined,
      status: filterForm.value.status || undefined,
    };

    const res = await axios.get('/akademik/rapor', { params });
    if (res.data?.success && res.data?.data) {
      const data = res.data.data;
      kelasList.value = data.kelasList || [];
      tahunAjaranList.value = data.tahunAjaranList || [];
      students.value = data.students || [];
      subjects.value = data.subjects || [];
      stats.value = data.stats || stats.value;

      if (data.filters) {
        filterForm.value.tahun_ajaran_id = data.filters.tahun_ajaran_id || filterForm.value.tahun_ajaran_id;
        filterForm.value.tahun_ajaran = data.filters.tahun_ajaran || filterForm.value.tahun_ajaran;
        filterForm.value.nama_kelas = data.filters.nama_kelas || filterForm.value.nama_kelas;
        filterForm.value.kelas_id = data.filters.kelas_id || filterForm.value.kelas_id;
      }
    }
  } catch (err) {
    console.error('Gagal memuat data rapor:', err);
  } finally {
    loading.value = false;
  }
};

const handleFilterChange = () => {
  const k = kelasList.value.find(item => item.id === filterForm.value.kelas_id);
  if (k) filterForm.value.nama_kelas = k.nama || k.nama_kelas;

  const t = tahunAjaranList.value.find(item => item.id === filterForm.value.tahun_ajaran_id);
  if (t) filterForm.value.tahun_ajaran = t.nama || t.nama_tahun_ajaran;

  fetchRaporData();
};

let searchTimer = null;
const handleSearchDebounced = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    // client search happens reactively
  }, 250);
};

const setStatusFilter = (st) => {
  filterForm.value.status = st;
};

// Modal Functions
const openPreviewModal = (student, type = 'rapor') => {
  selectedStudent.value = student;
  modalType.value = type;
  showModalPreview.value = true;
};

const closePreviewModal = () => {
  showModalPreview.value = false;
  selectedStudent.value = null;
};

const printIframe = () => {
  if (previewIframeRef.value && previewIframeRef.value.contentWindow) {
    previewIframeRef.value.contentWindow.print();
  }
};

// Ledger Download
const downloadLedger = () => {
  if (!filterForm.value.kelas_id) {
    alert('Silakan pilih rombel terlebih dahulu.');
    return;
  }
  const url = `/akademik/rapor/ledger?kelas_id=${filterForm.value.kelas_id}&semester=${filterForm.value.semester}&tahun_ajaran_id=${filterForm.value.tahun_ajaran_id}&tenant_id=${filterForm.value.tenant_id || ''}`;
  window.open(url, '_blank');
};

// Dispatch Bulk Queue
const dispatchBulkJob = async () => {
  if (!filterForm.value.kelas_id) {
    alert('Silakan pilih rombel terlebih dahulu.');
    return;
  }

  dispatching.value = true;
  try {
    const res = await axios.post('/akademik/rapor/bulk-queue', {
      kelas_id: filterForm.value.kelas_id,
      tahun_ajaran_id: filterForm.value.tahun_ajaran_id || undefined,
      semester: filterForm.value.semester,
      tenant_id: filterForm.value.tenant_id || undefined,
    });

    if (res.data?.success) {
      alert(res.data.message || 'Pencetakan PDF massal berhasil dijadwalkan.');
    } else {
      alert('Pencetakan berhasil dikirim ke antrean server.');
    }
  } catch (err) {
    console.error('Gagal mengirim antrean cetak:', err);
    alert('Gagal menjadwalkan pencetakan massal.');
  } finally {
    dispatching.value = false;
  }
};

// Lifecycle Hydration
onMounted(() => {
  fetchRaporData();
});
</script>

