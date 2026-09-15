<template>
  <AppLayout title="Presensi & Jurnal Mengajar Terpadu">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <i class="bi bi-calendar2-check-fill text-indigo-600"></i>
          Presensi Siswa & Jurnal Mengajar Guru
        </h1>
        <p class="text-xs sm:text-sm text-slate-500">
          Presensi GPS Mandiri, Input Massal Wali Kelas, Verifikasi Surat Izin/Sakit, dan Jurnal Mengajar KBM Terintegrasi.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-if="activeTab === 'siswa'"
          @click="openSiswaGpsModal"
          class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer"
        >
          <i class="bi bi-geo-alt-fill"></i> Presensi GPS / Izin Siswa
        </button>
        <button
          v-if="activeTab === 'siswa'"
          @click="showScanModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer"
        >
          <i class="bi bi-qr-code-scan"></i> Scanner QR Kartu Pelajar
        </button>
        <button
          v-else-if="activeTab === 'wali_kelas'"
          @click="handleSaveWaliKelasSheet"
          :disabled="submitting || !waliKelasRows.length"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer"
        >
          <i class="bi bi-save"></i> {{ submitting ? 'Menyimpan...' : 'Simpan Presensi Rombel' }}
        </button>
        <button
          v-else-if="activeTab === 'jurnal'"
          @click="openCreateJurnalModal(null)"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer"
        >
          <i class="bi bi-journal-plus"></i> Buat Jurnal KBM Baru
        </button>
        <button
          v-else-if="activeTab === 'gtk'"
          @click="openGtkGpsModal"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer"
        >
          <i class="bi bi-geo-alt"></i> Presensi GPS GTK
        </button>
        <button
          v-else-if="activeTab === 'izin'"
          @click="showIzinModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer"
        >
          <i class="bi bi-envelope-plus"></i> Form Pengajuan Izin / Cuti
        </button>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 mb-6 gap-2 sm:gap-6 overflow-x-auto">
      <button
        v-for="t in tabs"
        :key="t.id"
        @click="switchTab(t.id)"
        class="pb-3 text-xs sm:text-sm font-bold flex items-center gap-2 border-b-2 whitespace-nowrap transition cursor-pointer"
        :class="activeTab === t.id ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
      >
        <i :class="['bi', t.icon]"></i>
        {{ t.name }}
        <span v-if="t.badge" class="px-1.5 py-0.2 text-2xs rounded-full bg-rose-100 text-rose-600 font-black">
          {{ t.badge }}
        </span>
      </button>
    </div>

    <!-- TAB 1: PRESENSI SISWA HARIAN -->
    <div v-if="activeTab === 'siswa'" class="space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-slate-400 uppercase">Total Siswa Aktif</div>
          <div class="text-xl font-black text-slate-800 mt-0.5">{{ tabData.stats?.total_siswa || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-emerald-500 uppercase">Hadir Tepat Waktu</div>
          <div class="text-xl font-black text-emerald-600 mt-0.5">{{ tabData.stats?.hadir || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-amber-500 uppercase">Terlambat</div>
          <div class="text-xl font-black text-amber-600 mt-0.5">{{ tabData.stats?.terlambat || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-blue-500 uppercase">Sakit / Izin</div>
          <div class="text-xl font-black text-blue-600 mt-0.5">{{ tabData.stats?.sakit_izin || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-rose-500 uppercase">Alpa / Tanpa Ket.</div>
          <div class="text-xl font-black text-rose-600 mt-0.5">{{ tabData.stats?.alpa || 0 }}</div>
        </div>
        <div class="bg-amber-50/60 p-3.5 rounded-2xl border border-amber-200/60 shadow-2xs">
          <div class="text-2xs font-bold text-amber-600 uppercase flex items-center gap-1">
            <i class="bi bi-clock-history"></i> Menunggu Verif
          </div>
          <div class="text-xl font-black text-amber-700 mt-0.5">{{ tabData.stats?.menunggu_verif || 0 }}</div>
        </div>
      </div>

      <!-- Filters & Date -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="flex items-center gap-3 w-full md:w-auto">
          <input
            v-model="filters.tanggal"
            @change="fetchTabData"
            type="date"
            class="p-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-700"
          />
          <div class="w-full sm:w-64 relative">
            <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              placeholder="Cari siswa, NISN, kelas..."
              class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden"
            />
          </div>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto">
          <div class="w-44">
            <SearchableSelect
              v-model="filters.status_kehadiran"
              :options="statusOptions"
              placeholder="-- Status Presensi --"
              @change="fetchTabData"
            />
          </div>
          <div class="w-44">
            <SearchableSelect
              v-model="filters.status_verifikasi"
              :options="[
                { id: '', nama: '-- Semua Verifikasi --' },
                { id: 'Menunggu', nama: 'Menunggu Verifikasi' },
                { id: 'Terverifikasi', nama: 'Terverifikasi' },
                { id: 'Ditolak', nama: 'Ditolak' },
              ]"
              placeholder="-- Status Verifikasi --"
              @change="fetchTabData"
            />
          </div>
        </div>
      </div>

      <!-- Table Presensi Siswa -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat log presensi siswa...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-calendar-x text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada data presensi pada tanggal ini.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Siswa</th>
                <th class="p-3.5">NISN & Rombel</th>
                <th class="p-3.5">Jam Masuk</th>
                <th class="p-3.5">Metode / Diinput</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5">Surat Bukti / Izin</th>
                <th class="p-3.5 text-center">Verifikasi Wali Kelas</th>
                <th class="p-3.5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  <div class="flex items-center gap-2">
                    <i v-if="item.metode_presensi === 'Geolokasi_GPS'" class="bi bi-geo-alt-fill text-emerald-500"></i>
                    <i v-else-if="item.metode_presensi === 'Manual_WaliKelas'" class="bi bi-person-workspace text-indigo-500"></i>
                    <i v-else class="bi bi-qr-code-scan text-blue-500"></i>
                    <span>{{ item.nama_siswa || item.siswa?.nama_lengkap }}</span>
                  </div>
                </td>
                <td class="p-3.5">
                  <span class="font-mono text-2xs font-bold">{{ item.nisn || item.siswa?.nisn || '-' }}</span>
                  <div class="text-2xs text-slate-400">{{ item.nama_kelas || item.siswa?.kelas_saat_ini || '-' }}</div>
                </td>
                <td class="p-3.5 font-bold text-slate-700">{{ item.jam_masuk || '-' }}</td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded text-2xs font-bold bg-slate-100 text-slate-700">
                    {{ item.metode_presensi }}
                  </span>
                  <div class="text-3xs text-slate-400 capitalize mt-0.5">Oleh: {{ item.diinput_oleh || 'siswa' }}</div>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="getPresensiBadgeClass(item.status_kehadiran)"
                  >
                    {{ item.status_kehadiran }}
                  </span>
                </td>
                <td class="p-3.5">
                  <div v-if="item.bukti_izin_url" class="flex items-center gap-2">
                    <a
                      :href="item.bukti_izin_url"
                      target="_blank"
                      class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-2xs flex items-center gap-1 transition"
                    >
                      <i class="bi bi-file-earmark-text"></i>
                      <span>Lihat Surat</span>
                    </a>
                    <span v-if="item.ukuran_berkas_kb" class="text-3xs text-slate-400">
                      ({{ item.ukuran_berkas_kb }} KB)
                    </span>
                  </div>
                  <span v-else-if="item.status_kehadiran === 'Sakit' || item.status_kehadiran === 'Izin'" class="text-2xs text-amber-600 italic">
                    Tanpa lampiran (input wali kelas)
                  </span>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="{
                      'bg-amber-50 text-amber-700 border border-amber-200': item.status_verifikasi === 'Menunggu',
                      'bg-emerald-50 text-emerald-700 border border-emerald-200': item.status_verifikasi === 'Terverifikasi',
                      'bg-rose-50 text-rose-700 border border-rose-200': item.status_verifikasi === 'Ditolak',
                    }"
                  >
                    {{ item.status_verifikasi || 'Terverifikasi' }}
                  </span>
                  <div v-if="item.verifikator" class="text-3xs text-slate-400 mt-0.5">
                    Oleh: {{ item.verifikator.nama_lengkap }}
                  </div>
                </td>
                <td class="p-3.5 text-center">
                  <button
                    v-if="item.status_verifikasi === 'Menunggu' || item.bukti_izin_url"
                    @click="openVerifikasiModal(item)"
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-2xs transition cursor-pointer"
                  >
                    <i class="bi bi-check2-square"></i> Verifikasi
                  </button>
                  <span v-else class="text-slate-300 text-2xs">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: PRESENSI MASSAL WALI KELAS / ROMBEL -->
    <div v-if="activeTab === 'wali_kelas'" class="space-y-6">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3 w-full sm:w-auto">
          <div class="w-60">
            <SearchableSelect
              v-model="waliKelasFilters.kelas_id"
              :options="kelasSelectOptions"
              placeholder="-- Pilih Rombel / Kelas --"
              @change="fetchTabData"
            />
          </div>
          <input
            v-model="waliKelasFilters.tanggal"
            @change="fetchTabData"
            type="date"
            class="p-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-700"
          />
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="setAllKehadiran('Hadir')"
            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl border border-emerald-200 transition cursor-pointer"
          >
            <i class="bi bi-check-all"></i> Set Semua Hadir
          </button>
        </div>
      </div>

      <!-- Table Lembar Presensi Siswa Rombel -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat daftar siswa kelas...</p>
        </div>
        <div v-else-if="!waliKelasRows.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-people text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Pilih kelas di atas untuk menampilkan lembar presensi.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5 w-12 text-center">No</th>
                <th class="p-3.5">Nama Peserta Didik</th>
                <th class="p-3.5">NISN & Gender</th>
                <th class="p-3.5 text-center">Status Kehadiran</th>
                <th class="p-3.5">Catatan / Keterangan Wali Kelas</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="(row, idx) in waliKelasRows" :key="row.siswa_id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                <td class="p-3.5 font-bold text-slate-800">{{ row.nama_lengkap }}</td>
                <td class="p-3.5">
                  <span class="font-mono text-2xs">{{ row.nisn || '-' }}</span>
                  <span class="text-2xs text-slate-400 ml-2">({{ row.jenis_kelamin || 'L' }})</span>
                </td>
                <td class="p-3.5">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      v-for="st in ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat']"
                      :key="st"
                      type="button"
                      @click="row.status_kehadiran = st"
                      class="px-2.5 py-1 text-2xs font-bold rounded-lg transition cursor-pointer"
                      :class="row.status_kehadiran === st ? getPresensiButtonActiveClass(st) : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                    >
                      {{ st }}
                    </button>
                  </div>
                </td>
                <td class="p-3.5">
                  <input
                    v-model="row.catatan"
                    type="text"
                    placeholder="Catatan tambahan..."
                    class="w-full p-1.5 text-xs rounded-lg border border-slate-200 focus:outline-hidden"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 3: JURNAL MENGAJAR GURU (KBM) -->
    <div v-if="activeTab === 'jurnal'" class="space-y-6">
      <!-- Card Jadwal Mengajar Hari Ini -->
      <div v-if="tabData.jadwalHariIni?.length" class="bg-indigo-900 text-white p-5 rounded-2xl shadow-xs space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm flex items-center gap-2 text-indigo-100">
            <i class="bi bi-clock-history text-indigo-400"></i>
            Jadwal Mengajar Anda Hari Ini
          </h3>
          <span class="text-2xs font-mono font-bold bg-indigo-800 px-2 py-0.5 rounded text-indigo-200">
            {{ formatDateHeader(filters.tanggal) }}
          </span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div
            v-for="j in tabData.jadwalHariIni"
            :key="j.id"
            @click="openCreateJurnalModal(j)"
            class="bg-indigo-800/60 hover:bg-indigo-800 p-3.5 rounded-xl border border-indigo-700/50 cursor-pointer transition space-y-1.5"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-black text-white">{{ j.mapel?.nama_mata_pelajaran }}</span>
              <span class="text-2xs font-mono text-indigo-300">Jam {{ j.jam_ke || '1-2' }}</span>
            </div>
            <div class="text-2xs text-indigo-200 flex items-center justify-between">
              <span>Kelas {{ j.kelas?.nama_kelas }}</span>
              <span>{{ j.jam_mulai }} - {{ j.jam_selesai }}</span>
            </div>
            <div class="pt-1 flex items-center justify-between text-3xs text-emerald-300 font-bold">
              <span>Klik untuk isi jurnal KBM</span>
              <i class="bi bi-arrow-right"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Jurnal -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="flex items-center gap-3 w-full md:w-auto">
          <input
            v-model="filters.tanggal"
            @change="fetchTabData"
            type="date"
            class="p-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-700"
          />
          <div class="w-48">
            <SearchableSelect
              v-model="filters.kelas_id"
              :options="kelasSelectOptions"
              placeholder="-- Semua Kelas --"
              @change="fetchTabData"
            />
          </div>
          <div class="w-48">
            <SearchableSelect
              v-model="filters.mapel_id"
              :options="mapelSelectOptions"
              placeholder="-- Semua Mapel --"
              @change="fetchTabData"
            />
          </div>
        </div>
      </div>

      <!-- Table Jurnal Mengajar -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat jurnal mengajar guru...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-journal-x text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada jurnal mengajar yang diinput pada tanggal ini.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Mata Pelajaran & Kelas</th>
                <th class="p-3.5">Guru Pengajar</th>
                <th class="p-3.5">Waktu & Jam Ke</th>
                <th class="p-3.5">Materi / Capaian Pembelajaran</th>
                <th class="p-3.5 text-center">Rekap Kehadiran</th>
                <th class="p-3.5 text-center">Foto KBM</th>
                <th class="p-3.5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="j in tabData.items?.data" :key="j.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  <div>{{ j.nama_mapel || j.mapel?.nama_mata_pelajaran }}</div>
                  <span class="text-2xs text-slate-400 font-normal">Kelas: {{ j.nama_kelas || j.kelas?.nama_kelas }}</span>
                </td>
                <td class="p-3.5">
                  <div class="font-bold text-slate-700">{{ j.nama_guru || j.guru?.nama_lengkap }}</div>
                  <span class="px-1.5 py-0.2 rounded text-3xs font-bold bg-indigo-50 text-indigo-700">
                    {{ j.status_kbm }}
                  </span>
                </td>
                <td class="p-3.5 text-slate-600 font-mono text-2xs">
                  <div>Jam {{ j.jam_ke }}</div>
                  <div class="text-slate-400 font-sans">{{ j.jam_mulai }} - {{ j.jam_selesai }}</div>
                </td>
                <td class="p-3.5 max-w-sm">
                  <div class="font-bold text-slate-800 line-clamp-1">{{ j.capaian_pembelajaran }}</div>
                  <p class="text-2xs text-slate-500 line-clamp-2 mt-0.5">{{ j.aktivitas_pembelajaran }}</p>
                </td>
                <td class="p-3.5 text-center">
                  <div class="flex items-center justify-center gap-1 text-2xs font-bold font-mono">
                    <span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded" title="Hadir">{{ j.jumlah_hadir }}H</span>
                    <span class="text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded" title="Sakit">{{ j.jumlah_sakit }}S</span>
                    <span class="text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded" title="Izin">{{ j.jumlah_izin }}I</span>
                    <span class="text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded" title="Alpa">{{ j.jumlah_alpa }}A</span>
                  </div>
                </td>
                <td class="p-3.5 text-center">
                  <a
                    v-if="j.foto_kegiatan_url"
                    :href="j.foto_kegiatan_url"
                    target="_blank"
                    class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-2xs font-bold inline-flex items-center gap-1 transition"
                  >
                    <i class="bi bi-image"></i>
                    <span>Foto</span>
                  </a>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-3.5 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      @click="handleDeleteJurnal(j.id)"
                      class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-2xs transition cursor-pointer"
                      title="Hapus Jurnal"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 4: PRESENSI GTK -->
    <div v-if="activeTab === 'gtk'" class="space-y-6">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-slate-400 uppercase">Total Guru & Tendik</div>
          <div class="text-xl font-black text-slate-800 mt-0.5">{{ tabData.stats?.total_gtk || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-emerald-500 uppercase">Hadir di Radius</div>
          <div class="text-xl font-black text-emerald-600 mt-0.5">{{ tabData.stats?.hadir || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-rose-500 uppercase">Luar Radius Sekolah</div>
          <div class="text-xl font-black text-rose-600 mt-0.5">{{ tabData.stats?.luar_radius || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-blue-500 uppercase">Cuti / Dinas Luar</div>
          <div class="text-xl font-black text-blue-600 mt-0.5">{{ tabData.stats?.cuti_dinas || 0 }}</div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat log kehadiran GTK...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-geo-alt text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada log presensi GTK pada tanggal ini.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Guru / PTK</th>
                <th class="p-3.5">NIP & Jabatan</th>
                <th class="p-3.5">Jam Masuk</th>
                <th class="p-3.5">Koordinat GPS</th>
                <th class="p-3.5">Jarak Sekolah</th>
                <th class="p-3.5 text-center">Status Radius</th>
                <th class="p-3.5 text-center">Status Hadir</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">{{ item.nama_ptk || item.gtk?.nama_lengkap }}</td>
                <td class="p-3.5">
                  <span class="font-mono text-2xs font-bold">{{ item.nip || item.gtk?.nip || '-' }}</span>
                  <div class="text-2xs text-slate-400">{{ item.gtk?.jabatan || '-' }}</div>
                </td>
                <td class="p-3.5 font-bold text-slate-700">{{ item.jam_masuk || '-' }}</td>
                <td class="p-3.5 font-mono text-2xs text-slate-500">
                  {{ item.latitude }}, {{ item.longitude }}
                </td>
                <td class="p-3.5 font-bold">
                  <span :class="item.jarak_meter <= 150 ? 'text-emerald-600' : 'text-rose-600'">
                    {{ item.jarak_meter }} meter
                  </span>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded text-2xs font-bold"
                    :class="item.status_geofence === 'Valid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                  >
                    {{ item.status_geofence }}
                  </span>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="getPresensiBadgeClass(item.status_kehadiran)"
                  >
                    {{ item.status_kehadiran }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 5: AUDIT ANTI-FRAUD GPS -->
    <div v-if="activeTab === 'fraud'" class="space-y-6">
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-slate-400 uppercase">Total Percobaan Dicegat</div>
          <div class="text-xl font-black text-rose-600 mt-0.5">{{ tabData.stats?.total_fraud || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-amber-500 uppercase">Mock / Fake GPS</div>
          <div class="text-xl font-black text-amber-600 mt-0.5">{{ tabData.stats?.mock_gps || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-rose-500 uppercase">Di Luar Radius Sekolah</div>
          <div class="text-xl font-black text-rose-600 mt-0.5">{{ tabData.stats?.outside_radius || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-blue-500 uppercase">Anomali Akurasi GPS</div>
          <div class="text-xl font-black text-blue-600 mt-0.5">{{ tabData.stats?.accuracy_anomaly || 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-purple-500 uppercase">Emulator / Webdriver</div>
          <div class="text-xl font-black text-purple-600 mt-0.5">{{ tabData.stats?.device_emulation || 0 }}</div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat log audit anti-fraud...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-shield-check text-4xl text-emerald-500"></i>
          <p class="text-xs mt-2 font-bold text-slate-700">Aman! Belum ada log kecurangan lokasi yang terdeteksi.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-rose-50/60 border-b border-rose-100 text-rose-800 font-bold">
              <tr>
                <th class="p-3.5">Waktu & Tanggal</th>
                <th class="p-3.5">Nama Pelaku / NISN</th>
                <th class="p-3.5">Tipe Fraud</th>
                <th class="p-3.5">Jarak & Akurasi</th>
                <th class="p-3.5">Alasan Pencegatan (Anti-Fraud)</th>
                <th class="p-3.5 text-center">Status Blokir</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-rose-50/30 transition">
                <td class="p-3.5 font-mono text-2xs text-slate-600">
                  {{ formatDate(item.created_at) }}
                </td>
                <td class="p-3.5">
                  <div class="font-bold text-slate-800">{{ item.nama_pelaku }}</div>
                  <span class="text-2xs text-slate-400 font-mono">{{ item.identifier || '-' }} ({{ item.tipe_pengguna }})</span>
                </td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded text-2xs font-black bg-rose-100 text-rose-700 border border-rose-200">
                    {{ item.fraud_type }}
                  </span>
                </td>
                <td class="p-3.5 font-mono text-2xs">
                  <div>{{ item.jarak_meter || 0 }}m dari sekolah</div>
                  <div class="text-slate-400">Akurasi: ±{{ Math.round(item.akurasi_meter || 0) }}m</div>
                </td>
                <td class="p-3.5 text-rose-700 font-medium max-w-sm">
                  <div class="flex items-start gap-1">
                    <i class="bi bi-shield-x text-rose-600 mt-0.5"></i>
                    <span>{{ item.fraud_reason }}</span>
                  </div>
                </td>
                <td class="p-3.5 text-center">
                  <span class="px-2 py-0.5 rounded text-2xs font-bold bg-rose-600 text-white shadow-2xs">
                    BLOCKED
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 6: PENGATURAN GEOFENCE -->
    <div v-if="activeTab === 'setting'" class="space-y-6 max-w-3xl">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
        <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
          <i class="bi bi-geo-alt-fill text-indigo-600"></i>
          Konfigurasi Titik Pusat Geofence Sekolah
        </h3>
        <p class="text-xs text-slate-500">
          Atur koordinat pusat sekolah dan radius toleransi jarak (dalam meter).
        </p>

        <form @submit.prevent="handleSaveSetting" class="space-y-4 pt-2">
          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Nama Lokasi / Kampus</label>
            <input
              v-model="settingForm.nama_lokasi"
              type="text"
              class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold focus:outline-hidden"
              required
            />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Latitude Pusat</label>
              <input
                v-model="settingForm.latitude_pusat"
                type="number"
                step="any"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-mono font-bold focus:outline-hidden"
                required
              />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Longitude Pusat</label>
              <input
                v-model="settingForm.longitude_pusat"
                type="number"
                step="any"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-mono font-bold focus:outline-hidden"
                required
              />
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Radius Toleransi (Meter)</label>
              <input
                v-model="settingForm.radius_meter"
                type="number"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold focus:outline-hidden"
                required
              />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Jam Masuk Normal</label>
              <input
                v-model="settingForm.jam_masuk_normal"
                type="time"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold focus:outline-hidden"
                required
              />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Toleransi Terlambat (Menit)</label>
              <input
                v-model="settingForm.toleransi_terlambat_menit"
                type="number"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold focus:outline-hidden"
                required
              />
            </div>
          </div>
          <div class="pt-4 flex justify-end">
            <button
              type="submit"
              class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
            >
              <i class="bi bi-save"></i> Simpan Konfigurasi Geofencing
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 1: PRESENSI MANDIRI SISWA (DENGAN ATURAN BUKTI IZIN/SAKIT) -->
    <div v-if="showSiswaGpsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 bg-emerald-600 text-white flex items-center justify-between">
          <h3 class="font-bold text-sm flex items-center gap-2">
            <i class="bi bi-geo-alt-fill"></i>
            Presensi Mandiri Siswa
          </h3>
          <button @click="showSiswaGpsModal = false" class="text-white/80 hover:text-white cursor-pointer">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="handleSiswaGpsSubmit" class="p-6 space-y-4 overflow-y-auto">
          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Pilih Siswa / Peserta Didik</label>
            <SearchableSelect
              v-model="siswaGpsForm.siswa_id"
              :options="siswaSelectOptions"
              placeholder="-- Cari Siswa (Nama / NISN) --"
              required
            />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Status Kehadiran</label>
            <SearchableSelect
              v-model="siswaGpsForm.status_kehadiran"
              :options="[
                { id: 'Hadir', nama: 'Hadir Tepat Waktu (Wajib di Radius Sekolah)' },
                { id: 'Sakit', nama: 'Sakit (Wajib Unggah Surat Dokter)' },
                { id: 'Izin', nama: 'Izin (Wajib Unggah Surat Izin Orang Tua)' },
                { id: 'Dispensasi', nama: 'Dispensasi Tugas Sekolah' },
              ]"
              placeholder="-- Pilih Status Kehadiran --"
              required
            />
          </div>

          <!-- Info Box Jika Status Hadir (GPS Validation) -->
          <div
            v-if="siswaGpsForm.status_kehadiran === 'Hadir'"
            class="p-4 rounded-xl border text-xs"
            :class="{
              'bg-amber-50 border-amber-200 text-amber-800': gpsStatus === 'locating',
              'bg-emerald-50 border-emerald-200 text-emerald-800': gpsStatus === 'ready' && isWithinRadius,
              'bg-rose-50 border-rose-200 text-rose-800': gpsStatus === 'ready' && !isWithinRadius,
              'bg-rose-50 border-rose-300 text-rose-900': gpsStatus === 'error' || gpsStatus === 'fraud',
            }"
          >
            <div class="flex items-center gap-2 font-bold mb-1">
              <i v-if="gpsStatus === 'locating'" class="bi bi-arrow-repeat animate-spin text-amber-600"></i>
              <i v-else-if="gpsStatus === 'ready' && isWithinRadius" class="bi bi-check-circle-fill text-emerald-600 text-base"></i>
              <i v-else class="bi bi-exclamation-triangle-fill text-rose-600 text-base"></i>
              <span>{{ gpsStatusTitle }}</span>
            </div>
            <p class="text-2xs leading-relaxed">{{ gpsStatusDesc }}</p>

            <div v-if="gpsCoords.latitude" class="mt-3 pt-2 border-t border-slate-200/50 flex flex-wrap items-center justify-between text-2xs font-mono font-bold">
              <span>Lat: {{ gpsCoords.latitude.toFixed(6) }}</span>
              <span>Long: {{ gpsCoords.longitude.toFixed(6) }}</span>
              <span>Jarak: {{ calculatedDistance }}m</span>
              <span class="text-slate-500">(±{{ Math.round(gpsCoords.accuracy) }}m)</span>
            </div>
          </div>

          <!-- Upload Berkas Bukti (WAJIB JIKA IZIN / SAKIT) -->
          <div v-if="siswaGpsForm.status_kehadiran === 'Sakit' || siswaGpsForm.status_kehadiran === 'Izin'" class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl space-y-2">
            <label class="block text-2xs font-bold text-amber-900 uppercase">
              <i class="bi bi-file-earmark-medical"></i> Unggah Surat Bukti (Wajib Foto/PDF)
            </label>
            <p class="text-3xs text-amber-700">
              Sistem akan otomatis me-resize & mengompres berkas agar selalu <strong>di bawah 500 KB</strong> tanpa merusak kejelasan dokumen.
            </p>
            <input
              type="file"
              accept="image/*,application/pdf"
              @change="handleBuktiFileChange"
              class="w-full text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-200 file:text-amber-800 hover:file:bg-amber-300 cursor-pointer"
              required
            />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Keterangan / Alasan Tambahan</label>
            <textarea
              v-model="siswaGpsForm.keterangan"
              rows="2"
              class="w-full p-2.5 border border-slate-200 rounded-xl text-xs focus:outline-hidden"
              placeholder="Tuliskan keterangan jika ada..."
            ></textarea>
          </div>

          <div class="pt-4 flex items-center justify-between border-t border-slate-100">
            <button
              v-if="siswaGpsForm.status_kehadiran === 'Hadir'"
              type="button"
              @click="acquireGeolocation"
              class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer"
            >
              <i class="bi bi-arrow-clockwise"></i> Refresh GPS
            </button>
            <div class="flex items-center gap-2 ml-auto">
              <button
                type="button"
                @click="showSiswaGpsModal = false"
                class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="submitting || (siswaGpsForm.status_kehadiran === 'Hadir' && (!isWithinRadius || gpsStatus !== 'ready'))"
                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-2"
              >
                <i v-if="submitting" class="bi bi-arrow-repeat animate-spin"></i>
                <span>{{ submitting ? 'Memproses...' : 'Kirim Presensi Siswa' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: BUAT JURNAL MENGAJAR GURU & REKAP KBM KELAS -->
    <div v-if="showJurnalModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between">
          <h3 class="font-bold text-sm flex items-center gap-2">
            <i class="bi bi-journal-check"></i>
            Input Jurnal Mengajar & Presensi KBM Kelas
          </h3>
          <button @click="showJurnalModal = false" class="text-white/80 hover:text-white cursor-pointer">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="handleJurnalSubmit" class="p-6 space-y-4 overflow-y-auto">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Rombel / Kelas</label>
              <SearchableSelect
                v-model="jurnalForm.kelas_id"
                :options="kelasSelectOptions"
                placeholder="-- Pilih Kelas --"
                @change="handleJurnalKelasChange"
                required
              />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Mata Pelajaran</label>
              <SearchableSelect
                v-model="jurnalForm.mapel_id"
                :options="mapelSelectOptions"
                placeholder="-- Pilih Mata Pelajaran --"
                required
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Jam Ke-</label>
              <input
                v-model="jurnalForm.jam_ke"
                type="text"
                placeholder="1-2"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold"
                required
              />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Jam Mulai</label>
              <input
                v-model="jurnalForm.jam_mulai"
                type="time"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold"
                required
              />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Jam Selesai</label>
              <input
                v-model="jurnalForm.jam_selesai"
                type="time"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold"
                required
              />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Capaian Pembelajaran (CP) / Topik Materi</label>
            <input
              v-model="jurnalForm.capaian_pembelajaran"
              type="text"
              placeholder="Contoh: Pemrograman Berorientasi Objek - Konsep Inheritance & Polymorphism"
              class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold focus:outline-hidden"
              required
            />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Aktivitas KBM & Kegiatan Pembelajaran</label>
            <textarea
              v-model="jurnalForm.aktivitas_pembelajaran"
              rows="3"
              placeholder="Jelaskan alur pembelajaran di kelas..."
              class="w-full p-2.5 border border-slate-200 rounded-xl text-xs focus:outline-hidden"
              required
            ></textarea>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Kendala / Hambatan Siswa di Kelas (Opsional)</label>
            <input
              v-model="jurnalForm.kendala_pembelajaran"
              type="text"
              placeholder="Contoh: 3 unit komputer lab perlu instalasi driver compiler"
              class="w-full p-2.5 border border-slate-200 rounded-xl text-xs focus:outline-hidden"
            />
          </div>

          <!-- Upload Foto Kegiatan KBM (Auto-Compressed < 500 KB) -->
          <div class="p-3.5 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-1.5">
            <label class="block text-2xs font-bold text-indigo-900 uppercase">
              <i class="bi bi-camera"></i> Foto Dokumentasi KBM (Auto-Resize & Kompres &lt; 500 KB)
            </label>
            <input
              type="file"
              accept="image/*"
              @change="handleJurnalFotoChange"
              class="w-full text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-200 file:text-indigo-800 hover:file:bg-indigo-300 cursor-pointer"
            />
          </div>

          <!-- Lembar Checklist Kehadiran Siswa di Kelas Saat Jam Ini -->
          <div v-if="jurnalSiswaRows.length" class="space-y-2 pt-2 border-t border-slate-100">
            <div class="flex items-center justify-between">
              <label class="block text-2xs font-bold text-slate-700 uppercase">
                <i class="bi bi-check2-circle text-indigo-600"></i> Rekap Presensi Siswa Kelas ({{ jurnalSiswaRows.length }} Siswa)
              </label>
              <button
                type="button"
                @click="setAllJurnalKehadiran('Hadir')"
                class="text-3xs font-bold text-indigo-600 hover:underline cursor-pointer"
              >
                Set Semua Hadir
              </button>
            </div>
            <div class="max-h-48 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 text-xs">
              <div
                v-for="s in jurnalSiswaRows"
                :key="s.siswa_id"
                class="p-2.5 flex items-center justify-between hover:bg-slate-50 transition"
              >
                <div>
                  <span class="font-bold text-slate-800">{{ s.nama_lengkap }}</span>
                  <span class="text-3xs text-slate-400 font-mono ml-2">{{ s.nisn || '-' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <button
                    v-for="st in ['Hadir', 'Sakit', 'Izin', 'Alpa']"
                    :key="st"
                    type="button"
                    @click="s.status_kehadiran = st"
                    class="px-2 py-0.5 text-3xs font-bold rounded transition cursor-pointer"
                    :class="s.status_kehadiran === st ? getPresensiButtonActiveClass(st) : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                  >
                    {{ st }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
            <button
              type="button"
              @click="showJurnalModal = false"
              class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-2"
            >
              <i v-if="submitting" class="bi bi-arrow-repeat animate-spin"></i>
              <span>{{ submitting ? 'Menyimpan...' : 'Simpan Jurnal KBM' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 3: VERIFIKASI PRESENSI MANDIRI SISWA OLEH WALI KELAS -->
    <div v-if="showVerifikasiModal && selectedPresensiForVerif" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-800 text-white flex items-center justify-between">
          <h3 class="font-bold text-sm flex items-center gap-2">
            <i class="bi bi-shield-check text-emerald-400"></i>
            Verifikasi Presensi / Surat Izin Siswa
          </h3>
          <button @click="showVerifikasiModal = false" class="text-white/80 hover:text-white cursor-pointer">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="handleVerifikasiSubmit" class="p-6 space-y-4">
          <div class="p-3 bg-slate-50 rounded-xl space-y-1 text-xs">
            <div class="font-bold text-slate-800">{{ selectedPresensiForVerif.nama_siswa }}</div>
            <div class="text-slate-500">NISN: {{ selectedPresensiForVerif.nisn }} | Kelas: {{ selectedPresensiForVerif.nama_kelas }}</div>
            <div class="text-slate-500">Tanggal: {{ selectedPresensiForVerif.tanggal }} | Status: <strong>{{ selectedPresensiForVerif.status_kehadiran }}</strong></div>
          </div>

          <div v-if="selectedPresensiForVerif.bukti_izin_url" class="p-3 bg-indigo-50/60 rounded-xl flex items-center justify-between text-xs">
            <span class="font-bold text-indigo-900">Lampiran Surat Bukti Siswa:</span>
            <a
              :href="selectedPresensiForVerif.bukti_izin_url"
              target="_blank"
              class="px-3 py-1 bg-indigo-600 text-white rounded-lg font-bold text-2xs flex items-center gap-1 hover:bg-indigo-700 transition"
            >
              <i class="bi bi-eye"></i> Buka Dokumen
            </a>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Keputusan Verifikasi</label>
            <SearchableSelect
              v-model="verifikasiForm.status_verifikasi"
              :options="[
                { id: 'Terverifikasi', nama: 'Terverifikasi (Disetujui)' },
                { id: 'Ditolak', nama: 'Ditolak (Ubah Status Menjadi Alpa)' },
              ]"
              placeholder="-- Keputusan --"
              required
            />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Catatan Wali Kelas</label>
            <input
              v-model="verifikasiForm.catatan_wali_kelas"
              type="text"
              placeholder="Contoh: Surat dokter telah diverifikasi sah."
              class="w-full p-2.5 border border-slate-200 rounded-xl text-xs focus:outline-hidden"
            />
          </div>

          <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
            <button
              type="button"
              @click="showVerifikasiModal = false"
              class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
            >
              {{ submitting ? 'Menyimpan...' : 'Simpan Verifikasi' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js'
import axios from 'axios'
import { ref, reactive, computed, onMounted } from 'vue'

const props = defineProps({
  initialTab: {
    type: String,
    default: 'siswa',
  },
})

const activeTab = ref(props.initialTab || 'siswa')
const loading = ref(false)
const submitting = ref(false)
const tabData = ref({})

// Modals
const showScanModal = ref(false)
const showSiswaGpsModal = ref(false)
const showGtkModal = ref(false)
const showIzinModal = ref(false)
const showJurnalModal = ref(false)
const showVerifikasiModal = ref(false)

const selectedPresensiForVerif = ref(null)

// Geolocation & Anti-Fraud State
const gpsStatus = ref('locating')
const gpsCoords = reactive({
  latitude: null,
  longitude: null,
  accuracy: null,
  isMock: false,
})

const tabs = [
  { id: 'siswa', name: 'Presensi Siswa Harian', icon: 'bi-person-check-fill' },
  { id: 'wali_kelas', name: 'Input Rombel / Wali Kelas', icon: 'bi-person-workspace' },
  { id: 'jurnal', name: 'Jurnal Mengajar Guru (KBM)', icon: 'bi-journal-text' },
  { id: 'gtk', name: 'Presensi GTK Geofence', icon: 'bi-geo-alt-fill' },
  { id: 'fraud', name: 'Audit Anti-Fraud GPS', icon: 'bi-shield-shaded' },
  { id: 'setting', name: 'Pengaturan Geofence', icon: 'bi-gear-fill' },
]

const filters = reactive({
  tanggal: new Date().toISOString().split('T')[0],
  bulan: new Date().toISOString().slice(0, 7),
  search: '',
  status_kehadiran: '',
  status_verifikasi: '',
  kelas_id: '',
  mapel_id: '',
})

const waliKelasFilters = reactive({
  kelas_id: '',
  tanggal: new Date().toISOString().split('T')[0],
})

const waliKelasRows = ref([])
const jurnalSiswaRows = ref([])

const siswaGpsForm = reactive({
  siswa_id: '',
  status_kehadiran: 'Hadir',
  keterangan: '',
  bukti_file: null,
})

const verifikasiForm = reactive({
  status_verifikasi: 'Terverifikasi',
  catatan_wali_kelas: '',
})

const jurnalForm = reactive({
  jadwal_id: '',
  kelas_id: '',
  mapel_id: '',
  tanggal: new Date().toISOString().split('T')[0],
  jam_ke: '1-2',
  jam_mulai: '07:30',
  jam_selesai: '09:00',
  capaian_pembelajaran: '',
  aktivitas_pembelajaran: '',
  kendala_pembelajaran: '',
  status_kbm: 'Selesai',
  foto_kegiatan: null,
})

const settingForm = reactive({
  nama_lokasi: 'Gedung Utama Sekolah',
  latitude_pusat: -6.2088,
  longitude_pusat: 106.8456,
  radius_meter: 150,
  jam_masuk_normal: '07:00',
  jam_pulang_normal: '15:30',
  toleransi_terlambat_menit: 15,
})

useMemorySecurity([tabData, siswaGpsForm, jurnalForm, settingForm, waliKelasRows])

// Select Options
const statusOptions = [
  { id: '', nama: '-- Semua Status --' },
  { id: 'Hadir', nama: 'Hadir' },
  { id: 'Terlambat', nama: 'Terlambat' },
  { id: 'Sakit', nama: 'Sakit' },
  { id: 'Izin', nama: 'Izin' },
  { id: 'Alpa', nama: 'Alpa' },
]

const siswaSelectOptions = computed(() => {
  return (tabData.value.siswaList || []).map((s) => ({
    id: s.id,
    nama: s.nama_lengkap,
    subLabel: `NISN: ${s.nisn || '-'} | Kelas: ${s.kelas_saat_ini || '-'}`,
  }))
})

const kelasSelectOptions = computed(() => {
  return (tabData.value.kelasList || []).map((k) => ({
    id: k.id,
    nama: k.nama_kelas,
    subLabel: k.kode_kelas || '',
  }))
})

const mapelSelectOptions = computed(() => {
  return (tabData.value.mapelList || []).map((m) => ({
    id: m.id,
    nama: m.nama_mata_pelajaran,
    subLabel: m.kategori || '',
  }))
})

const currentCenterLat = computed(() => tabData.value.setting?.latitude_pusat ?? settingForm.latitude_pusat ?? -6.2088)
const currentCenterLng = computed(() => tabData.value.setting?.longitude_pusat ?? settingForm.longitude_pusat ?? 106.8456)
const currentMaxRadius = computed(() => tabData.value.setting?.radius_meter ?? settingForm.radius_meter ?? 150)

const calculatedDistance = computed(() => {
  if (!gpsCoords.latitude || !gpsCoords.longitude) return 0
  const lat1 = gpsCoords.latitude
  const lon1 = gpsCoords.longitude
  const lat2 = Number(currentCenterLat.value)
  const lon2 = Number(currentCenterLng.value)

  const theta = lon1 - lon2
  let dist = Math.sin((lat1 * Math.PI) / 180) * Math.sin((lat2 * Math.PI) / 180) +
             Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.cos((theta * Math.PI) / 180)
  dist = Math.acos(Math.max(-1, Math.min(1, dist)))
  dist = (dist * 180) / Math.PI
  const miles = dist * 60 * 1.1515
  return Math.round(miles * 1609.344)
})

const isWithinRadius = computed(() => {
  if (!gpsCoords.latitude || !gpsCoords.longitude) return false
  return calculatedDistance.value <= currentMaxRadius.value
})

const gpsStatusTitle = computed(() => {
  if (gpsStatus.value === 'locating') return 'Mendeteksi Posisi Satelit GPS...'
  if (gpsStatus.value === 'error') return 'Gagal Mengakses GPS Perangkat'
  if (gpsStatus.value === 'fraud') return 'Kecurangan Lokasi Terdeteksi!'
  if (isWithinRadius.value) return 'Lokasi Terverifikasi di Radius Sekolah'
  return `Di Luar Radius Sekolah (${calculatedDistance.value}m)`
})

const gpsStatusDesc = computed(() => {
  if (gpsStatus.value === 'locating') return 'Mohon pastikan GPS perangkat aktif dan berada di area terbuka.'
  if (isWithinRadius.value) return `Posisi GPS Anda berada dalam radius aman sekolah (${calculatedDistance.value} meter dari titik pusat, batas maksimal ${currentMaxRadius.value} meter).`
  return `Jarak Anda saat ini ${calculatedDistance.value} meter dari titik pusat sekolah. Batas maksimal yang diizinkan adalah ${currentMaxRadius.value} meter.`
})

const acquireGeolocation = () => {
  gpsStatus.value = 'locating'
  if (!navigator.geolocation) {
    gpsStatus.value = 'error'
    return
  }

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      gpsCoords.latitude = pos.coords.latitude
      gpsCoords.longitude = pos.coords.longitude
      gpsCoords.accuracy = pos.coords.accuracy
      gpsCoords.isMock = pos.mocked === true || pos.coords.isFromMockProvider === true
      gpsStatus.value = 'ready'
    },
    (err) => {
      console.warn('GPS Error', err)
      gpsStatus.value = 'error'
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  )
}

const openSiswaGpsModal = () => {
  showSiswaGpsModal.value = true
  acquireGeolocation()
}

const handleBuktiFileChange = (e) => {
  siswaGpsForm.bukti_file = e.target.files[0] || null
}

const handleJurnalFotoChange = (e) => {
  jurnalForm.foto_kegiatan = e.target.files[0] || null
}

// Data Fetching
const fetchTabData = async () => {
  loading.value = true
  try {
    const params = {
      async: 1,
      tab: activeTab.value,
      ...filters,
    }
    if (activeTab.value === 'wali_kelas') {
      params.kelas_id = waliKelasFilters.kelas_id
      params.tanggal = waliKelasFilters.tanggal
    }

    const res = await axios.get('/absensi', { params })
    tabData.value = res.data.data || {}

    if (activeTab.value === 'wali_kelas' && tabData.value.rows) {
      waliKelasRows.value = tabData.value.rows
      if (!waliKelasFilters.kelas_id && tabData.value.kelasId) {
        waliKelasFilters.kelas_id = tabData.value.kelasId
      }
    }

    if (activeTab.value === 'setting' && tabData.value.setting) {
      Object.assign(settingForm, tabData.value.setting)
    }
  } catch (err) {
    console.error('Failed to load absensi data', err)
  } finally {
    loading.value = false
  }
}

const switchTab = (tabId) => {
  activeTab.value = tabId
  fetchTabData()
}

let debounceTimer = null
const debounceSearch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchTabData()
  }, 400)
}

// Handlers
const handleSiswaGpsSubmit = async () => {
  if (!siswaGpsForm.siswa_id) return
  submitting.value = true
  try {
    const fd = new FormData()
    fd.append('siswa_id', siswaGpsForm.siswa_id)
    fd.append('status_kehadiran', siswaGpsForm.status_kehadiran)
    fd.append('keterangan', siswaGpsForm.keterangan || '')

    if (gpsCoords.latitude && gpsCoords.longitude) {
      fd.append('latitude', gpsCoords.latitude)
      fd.append('longitude', gpsCoords.longitude)
      fd.append('akurasi_meter', gpsCoords.accuracy || '')
      fd.append('is_mock', gpsCoords.isMock ? '1' : '0')
    }

    if (siswaGpsForm.bukti_file) {
      fd.append('bukti_file', siswaGpsForm.bukti_file)
    }

    const res = await axios.post('/absensi/presensi-siswa-gps', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    alert(res.data.message || 'Presensi siswa berhasil dicatat!')
    showSiswaGpsModal.value = false
    siswaGpsForm.siswa_id = ''
    siswaGpsForm.bukti_file = null
    fetchTabData()
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal mengirim presensi.'
    alert(`[PRESENSI DITOLAK] ${msg}`)
  } finally {
    submitting.value = false
  }
}

const setAllKehadiran = (status) => {
  waliKelasRows.value.forEach((r) => {
    r.status_kehadiran = status
  })
}

const handleSaveWaliKelasSheet = async () => {
  if (!waliKelasFilters.kelas_id) return
  submitting.value = true
  try {
    const payload = {
      kelas_id: waliKelasFilters.kelas_id,
      tanggal: waliKelasFilters.tanggal,
      records: waliKelasRows.value.map((r) => ({
        siswa_id: r.siswa_id,
        status_kehadiran: r.status_kehadiran,
        catatan: r.catatan,
      })),
    }

    const res = await axios.post('/absensi/presensi-wali-kelas', payload)
    alert(res.data.message || 'Presensi kelas berhasil disimpan!')
    fetchTabData()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan presensi kelas.')
  } finally {
    submitting.value = false
  }
}

const openVerifikasiModal = (presensi) => {
  selectedPresensiForVerif.value = presensi
  verifikasiForm.status_verifikasi = presensi.status_verifikasi === 'Menunggu' ? 'Terverifikasi' : presensi.status_verifikasi
  verifikasiForm.catatan_wali_kelas = presensi.catatan_wali_kelas || ''
  showVerifikasiModal.value = true
}

const handleVerifikasiSubmit = async () => {
  if (!selectedPresensiForVerif.value) return
  submitting.value = true
  try {
    const res = await axios.put(`/absensi/presensi-siswa/${selectedPresensiForVerif.value.id}/verifikasi`, verifikasiForm)
    alert(res.data.message || 'Presensi siswa berhasil diverifikasi!')
    showVerifikasiModal.value = false
    fetchTabData()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal memverifikasi presensi.')
  } finally {
    submitting.value = false
  }
}

// Jurnal Mengajar Handlers
const openCreateJurnalModal = async (jadwal) => {
  if (jadwal) {
    jurnalForm.jadwal_id = jadwal.id
    jurnalForm.kelas_id = jadwal.kelas_id
    jurnalForm.mapel_id = jadwal.mapel_id
    jurnalForm.jam_ke = jadwal.jam_ke || '1-2'
    jurnalForm.jam_mulai = jadwal.jam_mulai || '07:30'
    jurnalForm.jam_selesai = jadwal.jam_selesai || '09:00'
    await loadSiswaForJurnal(jadwal.kelas_id)
  } else {
    jurnalForm.jadwal_id = ''
    jurnalForm.kelas_id = tabData.value.kelasList?.[0]?.id || ''
    jurnalForm.mapel_id = tabData.value.mapelList?.[0]?.id || ''
    if (jurnalForm.kelas_id) {
      await loadSiswaForJurnal(jurnalForm.kelas_id)
    }
  }
  showJurnalModal.value = true
}

const handleJurnalKelasChange = async (kelasId) => {
  if (kelasId) {
    await loadSiswaForJurnal(kelasId)
  }
}

const loadSiswaForJurnal = async (kelasId) => {
  try {
    const res = await axios.get(`/absensi/kelas/${kelasId}/siswa`)
    jurnalSiswaRows.value = (res.data.data || []).map((s) => ({
      siswa_id: s.id,
      nama_lengkap: s.nama_lengkap,
      nisn: s.nisn,
      status_kehadiran: 'Hadir',
      catatan: '',
    }))
  } catch (err) {
    console.error('Failed to load class students', err)
  }
}

const setAllJurnalKehadiran = (st) => {
  jurnalSiswaRows.value.forEach((s) => {
    s.status_kehadiran = st
  })
}

const handleJurnalSubmit = async () => {
  submitting.value = true
  try {
    const fd = new FormData()
    fd.append('jadwal_id', jurnalForm.jadwal_id || '')
    fd.append('kelas_id', jurnalForm.kelas_id)
    fd.append('mapel_id', jurnalForm.mapel_id)
    fd.append('tanggal', jurnalForm.tanggal)
    fd.append('jam_ke', jurnalForm.jam_ke)
    fd.append('jam_mulai', jurnalForm.jam_mulai)
    fd.append('jam_selesai', jurnalForm.jam_selesai)
    fd.append('capaian_pembelajaran', jurnalForm.capaian_pembelajaran)
    fd.append('aktivitas_pembelajaran', jurnalForm.aktivitas_pembelajaran)
    fd.append('kendala_pembelajaran', jurnalForm.kendala_pembelajaran || '')
    fd.append('status_kbm', jurnalForm.status_kbm)

    if (jurnalForm.foto_kegiatan) {
      fd.append('foto_kegiatan', jurnalForm.foto_kegiatan)
    }

    jurnalSiswaRows.value.forEach((s, idx) => {
      fd.append(`presensi_kbm[${idx}][siswa_id]`, s.siswa_id)
      fd.append(`presensi_kbm[${idx}][status_kehadiran]`, s.status_kehadiran)
      fd.append(`presensi_kbm[${idx}][catatan]`, s.catatan || '')
    })

    const res = await axios.post('/absensi/jurnal', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    alert(res.data.message || 'Jurnal Mengajar berhasil disimpan!')
    showJurnalModal.value = false
    jurnalForm.capaian_pembelajaran = ''
    jurnalForm.aktivitas_pembelajaran = ''
    jurnalForm.foto_kegiatan = null
    fetchTabData()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan jurnal mengajar.')
  } finally {
    submitting.value = false
  }
}

const handleDeleteJurnal = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus jurnal mengajar ini?')) return
  try {
    const res = await axios.delete(`/absensi/jurnal/${id}`)
    alert(res.data.message || 'Jurnal berhasil dihapus.')
    fetchTabData()
  } catch (err) {
    alert('Gagal menghapus jurnal.')
  }
}

const handleSaveSetting = async () => {
  try {
    const res = await axios.post('/absensi/setting', settingForm)
    alert(res.data.message || 'Pengaturan geofence berhasil disimpan!')
    fetchTabData()
  } catch (err) {
    alert('Gagal menyimpan pengaturan.')
  }
}

const getPresensiBadgeClass = (status) => {
  switch (status) {
    case 'Hadir':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'Terlambat':
      return 'bg-amber-50 text-amber-700 border border-amber-200'
    case 'Sakit':
      return 'bg-blue-50 text-blue-700 border border-blue-200'
    case 'Izin':
      return 'bg-indigo-50 text-indigo-700 border border-indigo-200'
    case 'Alpa':
      return 'bg-rose-50 text-rose-700 border border-rose-200'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

const getPresensiButtonActiveClass = (status) => {
  switch (status) {
    case 'Hadir':
      return 'bg-emerald-600 text-white shadow-2xs'
    case 'Terlambat':
      return 'bg-amber-500 text-white shadow-2xs'
    case 'Sakit':
      return 'bg-blue-600 text-white shadow-2xs'
    case 'Izin':
      return 'bg-indigo-600 text-white shadow-2xs'
    case 'Alpa':
      return 'bg-rose-600 text-white shadow-2xs'
    default:
      return 'bg-slate-800 text-white'
  }
}

const formatDate = (d) => {
  if (!d) return '-'
  return new Date(d).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatDateHeader = (d) => {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

onMounted(() => {
  fetchTabData()
})
</script>
