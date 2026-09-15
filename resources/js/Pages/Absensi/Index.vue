<template>
  <AppLayout title="Presensi & Absensi Terpadu">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <i class="bi bi-geo-alt-fill text-indigo-600"></i>
          Presensi Siswa & GTK Geofencing
        </h1>
        <p class="text-xs sm:text-sm text-slate-500">
          Presensi Berbasis GPS Geolocation, Proteksi Anti-Fake GPS, Deteksi Mock Location, dan Audit Keamanan.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-if="activeTab === 'siswa'"
          @click="openSiswaGpsModal"
          class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer"
        >
          <i class="bi bi-geo-alt-fill"></i> Presensi GPS Siswa
        </button>
        <button
          v-if="activeTab === 'siswa'"
          @click="showScanModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer"
        >
          <i class="bi bi-qr-code-scan"></i> Scanner QR Kartu Pelajar
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
        <span v-if="t.badge" class="px-1.5 py-0.5 text-2xs rounded-full bg-rose-100 text-rose-600 font-black">
          {{ t.badge }}
        </span>
      </button>
    </div>

    <!-- TAB 1: PRESENSI SISWA -->
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
        <div class="bg-rose-50/50 p-3.5 rounded-2xl border border-rose-200/60 shadow-2xs">
          <div class="text-2xs font-bold text-rose-500 uppercase flex items-center gap-1">
            <i class="bi bi-shield-exclamation"></i> Fraud Terdeteksi
          </div>
          <div class="text-xl font-black text-rose-600 mt-0.5">{{ tabData.stats?.fraud_count || 0 }}</div>
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
        <div class="w-full md:w-56">
          <SearchableSelect
            v-model="filters.status_kehadiran"
            :options="statusOptions"
            placeholder="-- Status Presensi --"
            @change="fetchTabData"
          />
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
                <th class="p-3.5">Metode Presensi</th>
                <th class="p-3.5">Koordinat & Jarak</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5">Keterangan & Security</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  <div class="flex items-center gap-2">
                    <i v-if="item.metode_presensi === 'Geolokasi_GPS'" class="bi bi-geo-alt-fill text-emerald-500"></i>
                    <i v-else class="bi bi-qr-code-scan text-indigo-500"></i>
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
                </td>
                <td class="p-3.5 font-mono text-2xs">
                  <div v-if="item.latitude && item.longitude" class="space-y-0.5">
                    <span class="text-slate-600">{{ item.latitude }}, {{ item.longitude }}</span>
                    <div class="flex items-center gap-1 font-sans">
                      <span class="px-1.5 py-0.2 rounded font-bold text-emerald-700 bg-emerald-50">
                        {{ item.jarak_meter || 0 }}m dari sekolah
                      </span>
                      <span v-if="item.akurasi_meter" class="text-slate-400">
                        (±{{ Math.round(item.akurasi_meter) }}m)
                      </span>
                    </div>
                  </div>
                  <span v-else class="text-slate-400 italic">Tanpa Koordinat</span>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="getPresensiBadgeClass(item.status_kehadiran)"
                  >
                    {{ item.status_kehadiran }}
                  </span>
                </td>
                <td class="p-3.5 text-slate-500 max-w-xs truncate">
                  <div class="flex items-center gap-1.5">
                    <i v-if="item.is_suspicious" class="bi bi-exclamation-triangle-fill text-amber-500" title="Suspicious activity"></i>
                    <i v-else class="bi bi-shield-check text-emerald-500" title="Valid verified"></i>
                    <span>{{ item.keterangan || '-' }}</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: PRESENSI GTK -->
    <div v-if="activeTab === 'gtk'" class="space-y-6">
      <!-- Stats Cards -->
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

      <!-- Filters -->
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
              placeholder="Cari nama GTK, NIP..."
              class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden"
            />
          </div>
        </div>
      </div>

      <!-- Table Presensi GTK -->
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

    <!-- TAB 3: PENGAJUAN IZIN & CUTI -->
    <div v-if="activeTab === 'izin'" class="space-y-6">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat daftar izin & cuti...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-inbox text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada permohonan izin/cuti yang tercatat.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Pemohon</th>
                <th class="p-3.5">Jenis Permohonan</th>
                <th class="p-3.5">Rentang Tanggal</th>
                <th class="p-3.5">Alasan</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-center">Aksi Persetujuan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  {{ item.nama_pemohon }}
                  <span class="text-2xs font-normal text-slate-400 block uppercase">({{ item.pemohon_type }})</span>
                </td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded text-2xs font-bold bg-indigo-50 text-indigo-700">
                    {{ item.jenis_izin }}
                  </span>
                </td>
                <td class="p-3.5 text-slate-600">
                  {{ item.tanggal_mulai }} s/d {{ item.tanggal_selesai }}
                </td>
                <td class="p-3.5 text-slate-500 max-w-xs truncate">{{ item.alasan }}</td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded text-2xs font-bold"
                    :class="{
                      'bg-amber-50 text-amber-700 border border-amber-200': item.status_persetujuan === 'Menunggu',
                      'bg-emerald-50 text-emerald-700 border border-emerald-200': item.status_persetujuan === 'Disetujui',
                      'bg-rose-50 text-rose-700 border border-rose-200': item.status_persetujuan === 'Ditolak',
                    }"
                  >
                    {{ item.status_persetujuan }}
                  </span>
                </td>
                <td class="p-3.5 text-center">
                  <div v-if="item.status_persetujuan === 'Menunggu'" class="flex items-center justify-center gap-1">
                    <button
                      @click="handleUpdateIzin(item.id, 'Disetujui')"
                      class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-2xs transition cursor-pointer"
                    >
                      <i class="bi bi-check-lg"></i> Setujui
                    </button>
                    <button
                      @click="handleUpdateIzin(item.id, 'Ditolak')"
                      class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-2xs transition cursor-pointer"
                    >
                      <i class="bi bi-x-lg"></i> Tolak
                    </button>
                  </div>
                  <span v-else class="text-2xs text-slate-400">Selesai</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 4: AUDIT ANTI-FRAUD & KEAMANAN GPS -->
    <div v-if="activeTab === 'fraud'" class="space-y-6">
      <!-- Stats Cards -->
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

      <!-- Filters -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full sm:w-72 relative">
          <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
          <input
            v-model="filters.search"
            @input="debounceSearch"
            type="text"
            placeholder="Cari nama pelaku, NISN, alasan..."
            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden"
          />
        </div>
        <div class="w-full md:w-64">
          <SearchableSelect
            v-model="filters.fraud_type"
            :options="fraudFilterOptions"
            placeholder="-- Jenis Pelanggaran Fraud --"
            @change="fetchTabData"
          />
        </div>
      </div>

      <!-- Table Fraud Logs -->
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
                  <div v-if="item.device_info" class="text-3xs text-slate-400 truncate mt-0.5">
                    {{ item.device_info }}
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

    <!-- TAB 5: PENGATURAN GEOFENCING -->
    <div v-if="activeTab === 'setting'" class="space-y-6 max-w-3xl">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
        <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
          <i class="bi bi-geo-alt-fill text-indigo-600"></i>
          Konfigurasi Titik Pusat Geofence Sekolah
        </h3>
        <p class="text-xs text-slate-500">
          Atur koordinat pusat sekolah dan radius toleransi jarak (dalam meter). Siswa/Guru di luar radius ini akan otomatis ditolak dan dicatat di log audit.
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

    <!-- TAB 6: REKAPITULASI BULANAN -->
    <div v-if="activeTab === 'rekap'" class="space-y-6">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex items-center gap-3">
        <input
          v-model="filters.bulan"
          @change="fetchTabData"
          type="month"
          class="p-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-700"
        />
        <span class="text-xs text-slate-500">Pilih bulan rekapitulasi presensi ledger siswa</span>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat rekapitulasi bulanan...</p>
        </div>
        <div v-else-if="!tabData.rekapSiswa?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-table text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada rekapitulasi kehadiran untuk bulan ini.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Siswa</th>
                <th class="p-3.5">Rombel</th>
                <th class="p-3.5 text-center text-emerald-600">Hadir</th>
                <th class="p-3.5 text-center text-amber-600">Terlambat</th>
                <th class="p-3.5 text-center text-blue-600">Sakit</th>
                <th class="p-3.5 text-center text-indigo-600">Izin</th>
                <th class="p-3.5 text-center text-rose-600">Alpa</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="r in tabData.rekapSiswa?.data" :key="r.siswa_id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">{{ r.nama_siswa }}</td>
                <td class="p-3.5 font-mono text-2xs text-slate-500">{{ r.nama_kelas || '-' }}</td>
                <td class="p-3.5 text-center font-bold text-emerald-600">{{ r.hadir }}</td>
                <td class="p-3.5 text-center font-bold text-amber-600">{{ r.terlambat }}</td>
                <td class="p-3.5 text-center font-bold text-blue-600">{{ r.sakit }}</td>
                <td class="p-3.5 text-center font-bold text-indigo-600">{{ r.izin }}</td>
                <td class="p-3.5 text-center font-bold text-rose-600">{{ r.alpa }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL 1: PRESENSI MANDIRI GPS SISWA (DENGAN ANTI-FAKE GPS) -->
    <div v-if="showSiswaGpsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 bg-emerald-600 text-white flex items-center justify-between">
          <h3 class="font-bold text-sm flex items-center gap-2">
            <i class="bi bi-geo-alt-fill"></i>
            Presensi GPS Mandiri Siswa
          </h3>
          <button @click="showSiswaGpsModal = false" class="text-white/80 hover:text-white cursor-pointer">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="handleSiswaGpsSubmit" class="p-6 space-y-4 overflow-y-auto">
          <!-- Info GPS Status Box -->
          <div
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

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Pilih Siswa / Peserta Didik</label>
            <SearchableSelect
              v-model="siswaGpsForm.siswa_id"
              :options="siswaSelectOptions"
              placeholder="-- Cari Siswa (Nama / NISN) --"
              required
            />
          </div>

          <div v-if="antiFraudWarnings.length" class="p-3 bg-rose-50 border border-rose-200 rounded-xl space-y-1">
            <div class="text-2xs font-bold text-rose-700 flex items-center gap-1">
              <i class="bi bi-shield-x"></i> Peringatan Keamanan Terdeteksi:
            </div>
            <ul class="list-disc list-inside text-3xs text-rose-600 space-y-0.5">
              <li v-for="(w, idx) in antiFraudWarnings" :key="idx">{{ w }}</li>
            </ul>
          </div>

          <div class="pt-4 flex items-center justify-between border-t border-slate-100">
            <button
              type="button"
              @click="acquireGeolocation"
              class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer"
            >
              <i class="bi bi-arrow-clockwise"></i> Refresh GPS
            </button>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="showSiswaGpsModal = false"
                class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="submitting || gpsStatus !== 'ready' || !isWithinRadius"
                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-2"
              >
                <i v-if="submitting" class="bi bi-arrow-repeat animate-spin"></i>
                <span>{{ submitting ? 'Memvalidasi...' : 'Kirim Presensi GPS' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: SCANNER QR SISWA -->
    <div v-if="showScanModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between">
          <h3 class="font-bold text-sm flex items-center gap-2">
            <i class="bi bi-qr-code-scan"></i>
            Scanner Presensi QR Siswa
          </h3>
          <button @click="showScanModal = false" class="text-white/80 hover:text-white cursor-pointer">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="handleScanSubmit" class="p-6 space-y-4">
          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Pilih / Scan Siswa</label>
            <SearchableSelect
              v-model="scanForm.siswa_id"
              :options="siswaSelectOptions"
              placeholder="-- Cari Nama / NISN Siswa --"
              required
            />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Status Kehadiran</label>
            <SearchableSelect
              v-model="scanForm.status_kehadiran"
              :options="[
                { id: 'Hadir', nama: 'Hadir Tepat Waktu' },
                { id: 'Terlambat', nama: 'Terlambat' },
                { id: 'Dispensasi', nama: 'Dispensasi' },
              ]"
              placeholder="-- Status --"
              required
            />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Keterangan (Opsional)</label>
            <input
              v-model="scanForm.keterangan"
              type="text"
              placeholder="Contoh: Petugas Piket Pintu Gerbang"
              class="w-full p-2.5 border border-slate-200 rounded-xl text-xs focus:outline-hidden"
            />
          </div>

          <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
            <button
              type="button"
              @click="showScanModal = false"
              class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
            >
              {{ submitting ? 'Menyimpan...' : 'Catat Presensi QR' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 3: PRESENSI GPS GTK -->
    <div v-if="showGtkModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between">
          <h3 class="font-bold text-sm flex items-center gap-2">
            <i class="bi bi-geo-alt-fill"></i>
            Presensi GPS Guru & Pegawai (GTK)
          </h3>
          <button @click="showGtkModal = false" class="text-white/80 hover:text-white cursor-pointer">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="handleGtkGpsSubmit" class="p-6 space-y-4">
          <!-- Info GPS Status Box -->
          <div
            class="p-4 rounded-xl border text-xs"
            :class="isWithinRadius ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'"
          >
            <div class="flex items-center gap-2 font-bold mb-1">
              <i :class="isWithinRadius ? 'bi bi-check-circle-fill text-emerald-600' : 'bi bi-exclamation-triangle-fill text-rose-600'"></i>
              <span>{{ isWithinRadius ? 'Di Dalam Radius Sekolah' : 'Di Luar Radius Sekolah' }}</span>
            </div>
            <p class="text-2xs">Jarak Anda ke sekolah: <strong>{{ calculatedDistance }} meter</strong> (Radius Max: {{ currentMaxRadius }}m).</p>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Pilih Guru / PTK</label>
            <SearchableSelect
              v-model="gtkForm.ptk_id"
              :options="gtkSelectOptions"
              placeholder="-- Cari Guru / PTK --"
              required
            />
          </div>

          <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
            <button
              type="button"
              @click="showGtkModal = false"
              class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting || !isWithinRadius"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
            >
              {{ submitting ? 'Memvalidasi...' : 'Kirim Presensi GPS' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 4: FORM PENGAJUAN IZIN -->
    <div v-if="showIzinModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between">
          <h3 class="font-bold text-sm flex items-center gap-2">
            <i class="bi bi-envelope-plus"></i>
            Form Pengajuan Izin / Cuti
          </h3>
          <button @click="showIzinModal = false" class="text-white/80 hover:text-white cursor-pointer">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="handleIzinSubmit" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Tipe Pemohon</label>
              <SearchableSelect
                v-model="izinForm.pemohon_type"
                :options="[{ id: 'siswa', nama: 'Siswa / Peserta Didik' }, { id: 'gtk', nama: 'Guru / Tenaga Pendidik' }]"
                placeholder="-- Tipe --"
                required
              />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Jenis Izin</label>
              <SearchableSelect
                v-model="izinForm.jenis_izin"
                :options="[{ id: 'Sakit', nama: 'Sakit (Surat Dokter)' }, { id: 'Izin', nama: 'Izin Keperluan Keluarga' }, { id: 'Cuti', nama: 'Cuti Resmi' }, { id: 'Dinas Luar', nama: 'Tugas Dinas Luar' }]"
                placeholder="-- Jenis --"
                required
              />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Pilih Pemohon</label>
            <SearchableSelect
              v-if="izinForm.pemohon_type === 'siswa'"
              v-model="izinForm.pemohon_id"
              :options="siswaSelectOptions"
              placeholder="-- Cari Siswa --"
              required
            />
            <SearchableSelect
              v-else
              v-model="izinForm.pemohon_id"
              :options="gtkSelectOptions"
              placeholder="-- Cari Guru / PTK --"
              required
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Tanggal Mulai</label>
              <input
                v-model="izinForm.tanggal_mulai"
                type="date"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold"
                required
              />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Tanggal Selesai</label>
              <input
                v-model="izinForm.tanggal_selesai"
                type="date"
                class="w-full p-2.5 border border-slate-200 rounded-xl text-xs font-bold"
                required
              />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">Alasan Permohonan</label>
            <textarea
              v-model="izinForm.alasan"
              rows="3"
              class="w-full p-2.5 border border-slate-200 rounded-xl text-xs focus:outline-hidden"
              placeholder="Tuliskan keterangan detail alasan izin..."
              required
            ></textarea>
          </div>

          <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
            <button
              type="button"
              @click="showIzinModal = false"
              class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
            >
              {{ submitting ? 'Mengirim...' : 'Kirim Permohonan' }}
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

// Geolocation & Anti-Fraud State
const gpsStatus = ref('locating') // 'locating', 'ready', 'error', 'fraud'
const gpsCoords = reactive({
  latitude: null,
  longitude: null,
  accuracy: null,
  isMock: false,
  speed: null,
})
const antiFraudWarnings = ref([])

const tabs = [
  { id: 'siswa', name: 'Presensi Siswa (GPS & QR)', icon: 'bi-person-check-fill' },
  { id: 'gtk', name: 'Presensi GTK Geofencing', icon: 'bi-geo-alt-fill' },
  { id: 'izin', name: 'Pengajuan Izin & Cuti', icon: 'bi-envelope-paper' },
  { id: 'fraud', name: 'Audit Anti-Fraud GPS', icon: 'bi-shield-shaded' },
  { id: 'setting', name: 'Pengaturan Geofence', icon: 'bi-gear-fill' },
  { id: 'rekap', name: 'Rekapitulasi Bulanan', icon: 'bi-table' },
]

const filters = reactive({
  tanggal: new Date().toISOString().split('T')[0],
  bulan: new Date().toISOString().slice(0, 7),
  search: '',
  status_kehadiran: '',
  fraud_type: '',
})

const scanForm = reactive({
  siswa_id: '',
  status_kehadiran: 'Hadir',
  keterangan: '',
})

const siswaGpsForm = reactive({
  siswa_id: '',
})

const gtkForm = reactive({
  ptk_id: '',
})

const izinForm = reactive({
  pemohon_type: 'siswa',
  pemohon_id: '',
  jenis_izin: 'Sakit',
  tanggal_mulai: new Date().toISOString().split('T')[0],
  tanggal_selesai: new Date().toISOString().split('T')[0],
  alasan: '',
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

useMemorySecurity([tabData, scanForm, siswaGpsForm, gtkForm, izinForm, settingForm])

// Dropdown Options
const statusOptions = [
  { id: '', nama: '-- Semua Status --' },
  { id: 'Hadir', nama: 'Hadir' },
  { id: 'Terlambat', nama: 'Terlambat' },
  { id: 'Sakit', nama: 'Sakit' },
  { id: 'Izin', nama: 'Izin' },
  { id: 'Alpa', nama: 'Alpa' },
]

const fraudFilterOptions = [
  { id: '', nama: '-- Semua Jenis Fraud --' },
  { id: 'MOCK_PROVIDER', nama: 'Mock / Fake GPS Provider' },
  { id: 'OUTSIDE_GEOFENCE', nama: 'Di Luar Radius Sekolah' },
  { id: 'ACCURACY_ANOMALY', nama: 'Anomali Akurasi GPS' },
  { id: 'SPEED_ANOMALY', nama: 'Anomali Kecepatan / Teleport' },
  { id: 'DEVICE_EMULATION', nama: 'Emulator / Automated Webdriver' },
  { id: 'FAKE_GPS', nama: 'Simulasi Titik Nol (Fake GPS)' },
]

const siswaSelectOptions = computed(() => {
  return (tabData.value.siswaList || []).map((s) => ({
    id: s.id,
    nama: s.nama_lengkap,
    subLabel: `NISN: ${s.nisn || '-'} | Kelas: ${s.kelas_saat_ini || '-'}`,
  }))
})

const gtkSelectOptions = computed(() => {
  return (tabData.value.gtkList || []).map((g) => ({
    id: g.id,
    nama: g.nama_lengkap,
    subLabel: `NIP: ${g.nip || '-'} | ${g.jabatan || 'Guru'}`,
  }))
})

const currentCenterLat = computed(() => tabData.value.setting?.latitude_pusat ?? settingForm.latitude_pusat ?? -6.2088)
const currentCenterLng = computed(() => tabData.value.setting?.longitude_pusat ?? settingForm.longitude_pusat ?? 106.8456)
const currentMaxRadius = computed(() => tabData.value.setting?.radius_meter ?? settingForm.radius_meter ?? 150)

// Hitung jarak Haversine secara reaktif di sisi klien
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
  if (gpsStatus.value === 'locating') return 'Mohon pastikan GPS perangkat aktif dan berada di area terbuka untuk mendapatkan akurasi optimal.'
  if (gpsStatus.value === 'error') return 'Izin akses lokasi ditolak atau perangkat tidak mendukung Geolocation API. Silakan izinkan akses lokasi di browser Anda.'
  if (gpsStatus.value === 'fraud') return 'Sistem Anti-Fraud mendeteksi penggunaan Fake GPS / Mock Location pada perangkat Anda. Presensi tidak diizinkan!'
  if (isWithinRadius.value) return `Posisi GPS Anda berada dalam radius aman sekolah (${calculatedDistance.value} meter dari titik pusat, batas maksimal ${currentMaxRadius.value} meter).`
  return `Jarak Anda saat ini ${calculatedDistance.value} meter dari titik pusat sekolah. Batas maksimal yang diizinkan adalah ${currentMaxRadius.value} meter.`
})

// Client-Side Geolocation & Anti-Fraud Engine
const acquireGeolocation = () => {
  gpsStatus.value = 'locating'
  antiFraudWarnings.value = []

  if (!navigator.geolocation) {
    gpsStatus.value = 'error'
    return
  }

  // Check 1: Webdriver / Automation / Emulator Check
  if (navigator.webdriver) {
    antiFraudWarnings.value.push('Automated WebDriver / Virtual Browser terdeteksi.')
  }

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      gpsCoords.latitude = pos.coords.latitude
      gpsCoords.longitude = pos.coords.longitude
      gpsCoords.accuracy = pos.coords.accuracy
      gpsCoords.speed = pos.coords.speed

      // Check 2: Mock Location / Spoofing provider flag
      const isMock = pos.mocked === true || pos.coords.isFromMockProvider === true
      gpsCoords.isMock = isMock
      if (isMock) {
        antiFraudWarnings.value.push('Aplikasi Mock Location / Fake GPS Provider aktif.')
      }

      // Check 3: Suspicious accuracy bounds
      if (pos.coords.accuracy > 250) {
        antiFraudWarnings.value.push(`Akurasi GPS terlalu lemah (±${Math.round(pos.coords.accuracy)}m). Batas aman <250m.`)
      }
      if (pos.coords.accuracy === 0) {
        antiFraudWarnings.value.push('Akurasi GPS bernilai 0.0m (Simulasi software palsu).')
      }

      // Check 4: Null island coordinates
      if (pos.coords.latitude === 0 && pos.coords.longitude === 0) {
        antiFraudWarnings.value.push('Koordinat titik nol (Null Island) terdeteksi.')
      }

      if (antiFraudWarnings.value.length > 0 && isMock) {
        gpsStatus.value = 'fraud'
      } else {
        gpsStatus.value = 'ready'
      }
    },
    (err) => {
      console.warn('Geolocation Error:', err)
      gpsStatus.value = 'error'
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0,
    }
  )
}

const openSiswaGpsModal = () => {
  showSiswaGpsModal.value = true
  acquireGeolocation()
}

const openGtkGpsModal = () => {
  showGtkModal.value = true
  acquireGeolocation()
}

// Data Fetching
const fetchTabData = async () => {
  loading.value = true
  try {
    const res = await axios.get('/absensi', {
      params: {
        async: 1,
        tab: activeTab.value,
        ...filters,
      },
    })
    tabData.value = res.data.data || {}

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
    const res = await axios.post('/absensi/presensi-siswa-gps', {
      siswa_id: siswaGpsForm.siswa_id,
      latitude: gpsCoords.latitude,
      longitude: gpsCoords.longitude,
      akurasi_meter: gpsCoords.accuracy,
      is_mock: gpsCoords.isMock,
      device_info: `${navigator.userAgent} | Screen: ${window.screen.width}x${window.screen.height}`,
    })
    alert(res.data.message || 'Presensi GPS Siswa berhasil dicatat!')
    showSiswaGpsModal.value = false
    siswaGpsForm.siswa_id = ''
    fetchTabData()
  } catch (err) {
    const msg = err.response?.data?.message || 'Terjadi kesalahan saat memproses presensi GPS.'
    alert(`[PRESENSI DITOLAK] ${msg}`)
  } finally {
    submitting.value = false
  }
}

const handleScanSubmit = async () => {
  if (!scanForm.siswa_id) return
  submitting.value = true
  try {
    const payload = {
      siswa_id: scanForm.siswa_id,
      status_kehadiran: scanForm.status_kehadiran,
      keterangan: scanForm.keterangan,
    }
    if (gpsCoords.latitude && gpsCoords.longitude) {
      payload.latitude = gpsCoords.latitude
      payload.longitude = gpsCoords.longitude
      payload.akurasi_meter = gpsCoords.accuracy
      payload.is_mock = gpsCoords.isMock
    }

    const res = await axios.post('/absensi/scan-siswa', payload)
    alert(res.data.message || 'Presensi QR berhasil dicatat!')
    showScanModal.value = false
    scanForm.siswa_id = ''
    scanForm.keterangan = ''
    fetchTabData()
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal mencatat presensi QR.'
    alert(`[PRESENSI GAGAL] ${msg}`)
  } finally {
    submitting.value = false
  }
}

const handleGtkGpsSubmit = async () => {
  if (!gtkForm.ptk_id) return
  submitting.value = true
  try {
    const res = await axios.post('/absensi/presensi-gtk-gps', {
      ptk_id: gtkForm.ptk_id,
      latitude: gpsCoords.latitude,
      longitude: gpsCoords.longitude,
      akurasi_meter: gpsCoords.accuracy,
      is_mock: gpsCoords.isMock,
    })
    alert(res.data.message || 'Presensi GTK berhasil dicatat!')
    showGtkModal.value = false
    gtkForm.ptk_id = ''
    fetchTabData()
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal memproses presensi GPS GTK.'
    alert(`[PRESENSI DITOLAK] ${msg}`)
  } finally {
    submitting.value = false
  }
}

const handleIzinSubmit = async () => {
  submitting.value = true
  try {
    const res = await axios.post('/absensi/izin', izinForm)
    alert(res.data.message || 'Pengajuan izin berhasil dikirim!')
    showIzinModal.value = false
    izinForm.alasan = ''
    fetchTabData()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengirim pengajuan izin.')
  } finally {
    submitting.value = false
  }
}

const handleUpdateIzin = async (id, status) => {
  if (!confirm(`Apakah Anda yakin ingin mengubah status menjadi ${status}?`)) return
  try {
    const res = await axios.put(`/absensi/izin/${id}/status`, {
      status_persetujuan: status,
    })
    alert(res.data.message || 'Status izin diperbarui.')
    fetchTabData()
  } catch (err) {
    alert('Gagal memperbarui status izin.')
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

onMounted(() => {
  fetchTabData()
})
</script>
