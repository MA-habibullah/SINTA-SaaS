<template>
  <AppLayout title="Kepegawaian & GTK">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <i class="bi bi-person-badge text-indigo-600"></i>
          Kepegawaian & GTK
        </h1>
        <p class="text-xs sm:text-sm text-slate-500">
          Manajemen Buku Induk Guru & Tenaga Kependidikan, Riwayat Kepangkatan, Sertifikasi, dan E-Recruitment.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="activeTab === 'gtk'"
          @click="openGtkModal()"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition"
        >
          <i class="bi bi-plus-lg"></i> Tambah GTK Baru
        </button>
        <button
          v-else-if="activeTab === 'pangkat'"
          @click="showPangkatModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition"
        >
          <i class="bi bi-plus-lg"></i> Catat Kepangkatan
        </button>
        <button
          v-else-if="activeTab === 'sertifikasi'"
          @click="showSertifikasiModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition"
        >
          <i class="bi bi-plus-lg"></i> Tambah Sertifikasi
        </button>
        <button
          v-else-if="activeTab === 'recruitment'"
          @click="showLowonganModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition"
        >
          <i class="bi bi-briefcase"></i> Buka Lowongan Baru
        </button>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 mb-6 gap-2 sm:gap-6 overflow-x-auto">
      <button
        v-for="t in availableTabs"
        :key="t.id"
        @click="switchTab(t.id)"
        class="pb-3 text-xs sm:text-sm font-bold flex items-center gap-2 border-b-2 whitespace-nowrap transition cursor-pointer"
        :class="activeTab === t.id ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
      >
        <i :class="['bi', t.icon]"></i>
        {{ t.name }}
      </button>
    </div>

    <!-- TAB 1: BUKU INDUK GTK -->
    <div v-if="activeTab === 'gtk'" class="space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Total GTK</div>
          <div class="text-xl sm:text-2xl font-black text-slate-800 mt-1">{{ tabData.stats?.total_gtk || 0 }}</div>
          <div class="text-2xs text-slate-500 mt-1">Seluruh Pendidik & Tendik</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-indigo-500 uppercase tracking-wider">Tenaga Pendidik (Guru)</div>
          <div class="text-xl sm:text-2xl font-black text-indigo-600 mt-1">{{ tabData.stats?.total_guru || 0 }}</div>
          <div class="text-2xs text-slate-500 mt-1">Guru Mapel & BK</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-emerald-500 uppercase tracking-wider">Tenaga Kependidikan</div>
          <div class="text-xl sm:text-2xl font-black text-emerald-600 mt-1">{{ tabData.stats?.total_tendik || 0 }}</div>
          <div class="text-2xs text-slate-500 mt-1">Staf Tata Usaha & Laboran</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-amber-500 uppercase tracking-wider">Status ASN / PPPK</div>
          <div class="text-xl sm:text-2xl font-black text-amber-600 mt-1">{{ tabData.stats?.total_pns_pppk || 0 }}</div>
          <div class="text-2xs text-slate-500 mt-1">PNS & PPPK Aktif</div>
        </div>
      </div>

      <!-- Filters & Live Search -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80 relative">
          <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
          <input
            v-model="filters.search"
            @input="debounceSearch"
            type="text"
            placeholder="Cari nama, NIP, NUPTK, jabatan..."
            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          />
        </div>
        <div class="flex flex-wrap sm:flex-nowrap gap-2 w-full md:w-auto">
          <div class="w-full sm:w-44">
            <SearchableSelect
              v-model="filters.jenis_ptk"
              :options="jenisPtkFilterOptions"
              placeholder="-- Semua Jenis PTK --"
              @change="fetchTabData"
            />
          </div>
          <div class="w-full sm:w-44">
            <SearchableSelect
              v-model="filters.status_kepegawaian"
              :options="statusKepegawaianFilterOptions"
              placeholder="-- Status Pegawai --"
              @change="fetchTabData"
            />
          </div>
        </div>
      </div>

      <!-- Table GTK -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat data Buku Induk GTK...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-person-x text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada data Guru & Tenaga Kependidikan.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama & Gelar</th>
                <th class="p-3.5">NIP / NUPTK / NIK</th>
                <th class="p-3.5">Jenis PTK & Jabatan</th>
                <th class="p-3.5">Status & Golongan</th>
                <th class="p-3.5">Kontak & Pendidikan</th>
                <th class="p-3.5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5">
                  <div class="font-bold text-slate-800 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-black flex items-center justify-center text-2xs">
                      {{ (item.nama_lengkap || 'G').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div>{{ formatNamaGtk(item) }}</div>
                      <span class="text-2xs text-slate-400">{{ item.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                    </div>
                  </div>
                </td>
                <td class="p-3.5">
                  <div class="font-mono text-2xs text-slate-700">NIP: {{ item.nip || '-' }}</div>
                  <div class="font-mono text-2xs text-slate-400">NUPTK: {{ item.nuptk || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-indigo-50 text-indigo-700">
                    {{ item.jenis_ptk }}
                  </span>
                  <div class="text-2xs text-slate-500 mt-0.5">{{ item.jabatan || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="getStatusBadgeClass(item.status_kepegawaian)"
                  >
                    {{ item.status_kepegawaian }}
                  </span>
                  <div v-if="item.riwayat_kepangkatan?.length" class="text-2xs text-indigo-600 font-bold mt-0.5">
                    Gol: {{ item.riwayat_kepangkatan[0]?.golongan_pangkat || '-' }}
                  </div>
                </td>
                <td class="p-3.5">
                  <div class="text-2xs font-medium">{{ item.email || '-' }}</div>
                  <div class="text-2xs text-slate-400">{{ item.no_hp || '-' }} | {{ item.pendidikan_terakhir || '-' }}</div>
                </td>
                <td class="p-3.5 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      @click="openGtkModal(item)"
                      class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                      title="Edit Data GTK"
                    >
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <button
                      @click="deleteGtk(item.id)"
                      class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                      title="Hapus Data"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="tabData.items?.total > 15" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <div>Menampilkan {{ tabData.items.from }} - {{ tabData.items.to }} dari {{ tabData.items.total }} data</div>
          <div class="flex gap-1">
            <button
              :disabled="!tabData.items.prev_page_url"
              @click="fetchTabData(tabData.items.current_page - 1)"
              class="px-3 py-1 rounded-lg border border-slate-200 disabled:opacity-40"
            >
              Prev
            </button>
            <button
              :disabled="!tabData.items.next_page_url"
              @click="fetchTabData(tabData.items.current_page + 1)"
              class="px-3 py-1 rounded-lg border border-slate-200 disabled:opacity-40"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: RIWAYAT KEPANGKATAN -->
    <div v-else-if="activeTab === 'pangkat'" class="space-y-6">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat riwayat kepangkatan...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-award text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada catatan riwayat kepangkatan & KGB.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Pegawai</th>
                <th class="p-3.5">Golongan / Pangkat</th>
                <th class="p-3.5">Nomor & Tanggal SK</th>
                <th class="p-3.5">TMT Pangkat</th>
                <th class="p-3.5">Pejabat Penetap</th>
                <th class="p-3.5">Gaji Pokok</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  {{ item.gtk?.nama_lengkap || '-' }}
                  <div class="text-2xs text-slate-400 font-mono">NIP: {{ item.gtk?.nip || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-indigo-50 text-indigo-700">
                    {{ item.golongan_pangkat }}
                  </span>
                  <span v-if="item.is_terakhir" class="ml-1 px-1.5 py-0.5 rounded text-3xs font-black bg-emerald-100 text-emerald-700">PANGKAT AKTIF</span>
                </td>
                <td class="p-3.5">
                  <div class="font-mono text-2xs">{{ item.nomor_sk || '-' }}</div>
                  <div class="text-2xs text-slate-400">{{ item.tanggal_sk || '-' }}</div>
                </td>
                <td class="p-3.5 font-bold text-slate-700">{{ item.tmt_pangkat }}</td>
                <td class="p-3.5 text-slate-600">{{ item.pejabat_penetap || '-' }}</td>
                <td class="p-3.5 font-bold text-emerald-700">
                  Rp {{ Number(item.gaji_pokok || 0).toLocaleString('id-ID') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 3: SERTIFIKASI PTK -->
    <div v-else-if="activeTab === 'sertifikasi'" class="space-y-6">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat sertifikasi GTK...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-patch-check text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada sertifikasi pendidik terdaftar.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Guru</th>
                <th class="p-3.5">Jenis Sertifikasi</th>
                <th class="p-3.5">Nomor Sertifikat / Peserta</th>
                <th class="p-3.5">Bidang Studi</th>
                <th class="p-3.5">Tahun & Lembaga</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  {{ item.gtk?.nama_lengkap || '-' }}
                  <div class="text-2xs text-slate-400 font-mono">NIP: {{ item.gtk?.nip || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-amber-50 text-amber-800">
                    {{ item.jenis_sertifikasi }}
                  </span>
                </td>
                <td class="p-3.5">
                  <div class="font-mono text-2xs font-bold">{{ item.nomor_sertifikat }}</div>
                  <div class="font-mono text-2xs text-slate-400">No. Peserta: {{ item.nomor_peserta || '-' }}</div>
                </td>
                <td class="p-3.5 font-bold text-slate-700">{{ item.bidang_studi }}</td>
                <td class="p-3.5">
                  <div class="font-bold text-slate-800">{{ item.tahun_sertifikasi }}</div>
                  <div class="text-2xs text-slate-400">{{ item.lembaga_penerbit || '-' }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 4: E-RECRUITMENT (LOWONGAN & PELAMAR) -->
    <div v-else-if="activeTab === 'recruitment'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- List Lowongan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
          <h3 class="font-extrabold text-slate-800 text-sm mb-3 flex items-center justify-between">
            <span>Lowongan Kerja Dibuka</span>
            <span class="text-2xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">{{ tabData.lowongan?.length || 0 }} Posisi</span>
          </h3>
          <div class="space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="low in tabData.lowongan"
              :key="low.id"
              class="p-3.5 rounded-xl border transition cursor-pointer"
              :class="filters.lowongan_id === low.id ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-100 bg-slate-50/50 hover:bg-slate-50'"
              @click="filterPelamarByLowongan(low.id)"
            >
              <div class="flex justify-between items-start">
                <div class="font-bold text-slate-800 text-xs">{{ low.judul_posisi }}</div>
                <span
                  class="px-2 py-0.5 rounded text-3xs font-black uppercase"
                  :class="low.status === 'Buka' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                >
                  {{ low.status }}
                </span>
              </div>
              <div class="text-2xs text-slate-500 mt-1">
                {{ low.jenis_pekerjaan }} | Kuota: {{ low.kuota_dibutuhkan }} orang
              </div>
              <div class="text-2xs text-slate-400 mt-1 flex justify-between items-center">
                <span>Batas: {{ low.tanggal_tutup }}</span>
                <span class="font-bold text-indigo-600">{{ low.pelamar_count || 0 }} Pelamar</span>
              </div>
            </div>
          </div>
        </div>

        <!-- List Pelamar -->
        <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <h3 class="font-extrabold text-slate-800 text-sm">Daftar Pelamar Kerja</h3>
            <button
              @click="showPelamarModal = true"
              class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-2xs rounded-lg shadow-2xs flex items-center gap-1.5 transition self-start sm:self-auto"
            >
              <i class="bi bi-person-plus"></i> Input Pelamar
            </button>
          </div>

          <div v-if="!tabData.pelamar?.data?.length" class="p-12 text-center text-slate-400">
            <i class="bi bi-people text-3xl"></i>
            <p class="text-xs mt-2 font-bold">Belum ada data pelamar pada posisi ini.</p>
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="p in tabData.pelamar?.data"
              :key="p.id"
              class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3"
            >
              <div>
                <div class="font-bold text-slate-800 text-xs flex items-center gap-2">
                  {{ p.nama_lengkap }}
                  <span class="px-2 py-0.5 rounded text-3xs font-black bg-indigo-100 text-indigo-800">
                    {{ p.lowongan?.judul_posisi || 'Lowongan' }}
                  </span>
                </div>
                <div class="text-2xs text-slate-500 mt-1">
                  {{ p.pendidikan_terakhir }} {{ p.jurusan }} (IPK: {{ p.ipk || '-' }}) | {{ p.email }} | {{ p.no_hp }}
                </div>
                <div v-if="p.catatan_seleksi" class="text-2xs text-slate-400 italic mt-1">
                  Catatan: "{{ p.catatan_seleksi }}"
                </div>
              </div>
              <div class="flex items-center gap-2 self-end md:self-center">
                <span
                  class="px-2.5 py-1 rounded-lg text-2xs font-bold"
                  :class="getTahapanBadgeClass(p.status_tahapan)"
                >
                  {{ p.status_tahapan }}
                </span>
                <button
                  @click="openTahapanModal(p)"
                  class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-100 rounded-lg text-2xs font-bold text-slate-700 transition"
                >
                  Update Tahapan
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 5: SUPERVISI & PEMBINAAN -->
    <div v-else-if="activeTab === 'supervisi'" class="space-y-6">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat catatan supervisi guru...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-clipboard-check text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada riwayat supervisi guru tercatat.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Guru & Mapel</th>
                <th class="p-3.5">Tanggal & Semester</th>
                <th class="p-3.5">Jenis Supervisi</th>
                <th class="p-3.5 text-center">Skor Total</th>
                <th class="p-3.5">Predikat</th>
                <th class="p-3.5">Catatan & Tindak Lanjut</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  {{ item.nama_guru }}
                  <div class="text-2xs text-slate-400">Mapel: {{ item.mata_pelajaran || '-' }} ({{ item.kelas_rombel || '-' }})</div>
                </td>
                <td class="p-3.5">
                  <div class="font-bold text-slate-700">{{ item.tanggal_supervisi }}</div>
                  <div class="text-2xs text-slate-400">{{ item.tahun_ajaran }} - {{ item.semester }}</div>
                </td>
                <td class="p-3.5 font-medium text-slate-700">{{ item.jenis_supervisi }}</td>
                <td class="p-3.5 text-center font-black text-indigo-600 text-sm">{{ item.skor_total || 0 }}</td>
                <td class="p-3.5">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="item.predikat === 'Amat Baik' || item.predikat === 'Sangat Baik' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'"
                  >
                    {{ item.predikat || 'Baik' }}
                  </span>
                </td>
                <td class="p-3.5 text-slate-600 text-2xs max-w-xs">
                  <div>{{ item.catatan_observasi || '-' }}</div>
                  <div v-if="item.rekomendasi_pembinaan" class="text-indigo-600 font-semibold mt-0.5">
                    Rekomendasi: {{ item.rekomendasi_pembinaan }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL TAMBAH / EDIT GTK -->
    <div v-if="showGtkModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">
            {{ isEditingGtk ? 'Edit Data Pendidik / Tendik' : 'Tambah Guru & Tenaga Kependidikan Baru' }}
          </h3>
          <button @click="showGtkModal = false" class="text-slate-400 hover:text-slate-600 text-lg">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="submitGtkForm" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Lengkap (Tanpa Gelar) *</label>
              <input v-model="gtkForm.nama_lengkap" required type="text" placeholder="Contoh: Budi Santoso" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Gelar Depan & Belakang</label>
              <div class="grid grid-cols-2 gap-2">
                <input v-model="gtkForm.gelar_depan" type="text" placeholder="Dr. / Drs." class="w-full p-2.5 border border-slate-200 rounded-xl" />
                <input v-model="gtkForm.gelar_belakang" type="text" placeholder="S.Pd., M.Pd." class="w-full p-2.5 border border-slate-200 rounded-xl" />
              </div>
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">NIP (Nomor Induk Pegawai)</label>
              <input v-model="gtkForm.nip" type="text" placeholder="19800101..." class="w-full p-2.5 border border-slate-200 rounded-xl font-mono" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">NUPTK</label>
              <input v-model="gtkForm.nuptk" type="text" placeholder="16 digit NUPTK" class="w-full p-2.5 border border-slate-200 rounded-xl font-mono" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jenis Kelamin *</label>
              <SearchableSelect
                v-model="gtkForm.jenis_kelamin"
                :options="[{ id: 'L', nama: 'Laki-laki' }, { id: 'P', nama: 'Perempuan' }]"
                placeholder="-- Pilih Gender --"
              />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jenis PTK *</label>
              <SearchableSelect
                v-model="gtkForm.jenis_ptk"
                :options="jenisPtkOptions"
                placeholder="-- Pilih Jenis PTK --"
              />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Status Kepegawaian *</label>
              <SearchableSelect
                v-model="gtkForm.status_kepegawaian"
                :options="statusKepegawaianOptions"
                placeholder="-- Status Pegawai --"
              />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jabatan Struktural / Tugas Tambahan</label>
              <input v-model="gtkForm.jabatan" type="text" placeholder="Contoh: Wakasek Kurikulum, Kepala Lab" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Pendidikan Terakhir</label>
              <SearchableSelect
                v-model="gtkForm.pendidikan_terakhir"
                :options="[{ id: 'SMA/SMK', nama: 'SMA/SMK' }, { id: 'D3', nama: 'D3' }, { id: 'D4', nama: 'D4' }, { id: 'S1', nama: 'S1' }, { id: 'S2', nama: 'S2' }, { id: 'S3', nama: 'S3' }]"
                placeholder="-- Pilih Pendidikan --"
              />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jurusan Pendidikan</label>
              <input v-model="gtkForm.jurusan_pendidikan" type="text" placeholder="Contoh: Pendidikan Matematika" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">No. WhatsApp / HP</label>
              <input v-model="gtkForm.no_hp" type="text" placeholder="08123456789" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Email</label>
              <input v-model="gtkForm.email" type="email" placeholder="nama@sekolah.sch.id" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showGtkModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600 hover:bg-slate-50">
              Batal
            </button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs disabled:opacity-50">
              {{ saving ? 'Menyimpan...' : 'Simpan Data GTK' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CATAT KEPANGKATAN -->
    <div v-if="showPangkatModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Catat Riwayat Kepangkatan & KGB</h3>
          <button @click="showPangkatModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitPangkatForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Pilih Pegawai (GTK) *</label>
            <SearchableSelect
              v-model="pangkatForm.ptk_id"
              :options="gtkDropdownOptions"
              placeholder="-- Pilih Guru / Tendik --"
            />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Golongan / Pangkat *</label>
              <SearchableSelect
                v-model="pangkatForm.golongan_pangkat"
                :options="golonganOptions"
                placeholder="-- Pilih Golongan --"
              />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">TMT Pangkat *</label>
              <input v-model="pangkatForm.tmt_pangkat" required type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor SK</label>
              <input v-model="pangkatForm.nomor_sk" type="text" placeholder="SK-..." class="w-full p-2.5 border border-slate-200 rounded-xl font-mono" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal SK</label>
              <input v-model="pangkatForm.tanggal_sk" type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Pejabat Penetap SK</label>
            <input v-model="pangkatForm.pejabat_penetap" type="text" placeholder="Contoh: Kepala Dinas Pendidikan / Yayasan" class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Gaji Pokok Baru (Rp)</label>
            <input v-model="pangkatForm.gaji_pokok" type="number" placeholder="3500000" class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showPangkatModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan SK</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CATAT SERTIFIKASI -->
    <div v-if="showSertifikasiModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Tambah Sertifikasi Pendidik</h3>
          <button @click="showSertifikasiModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitSertifikasiForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Pilih Guru *</label>
            <SearchableSelect
              v-model="sertifikasiForm.ptk_id"
              :options="gtkDropdownOptions"
              placeholder="-- Pilih Guru --"
            />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jenis Sertifikasi *</label>
              <SearchableSelect
                v-model="sertifikasiForm.jenis_sertifikasi"
                :options="[{ id: 'Pendidik', nama: 'Sertifikat Pendidik (Serdik)' }, { id: 'Profesi', nama: 'Sertifikat Profesi' }, { id: 'Keahlian', nama: 'Sertifikat Keahlian / Asesor' }]"
                placeholder="-- Pilih Jenis --"
              />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tahun Sertifikasi *</label>
              <input v-model="sertifikasiForm.tahun_sertifikasi" required type="number" placeholder="2024" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor Sertifikat *</label>
              <input v-model="sertifikasiForm.nomor_sertifikat" required type="text" placeholder="12345/..." class="w-full p-2.5 border border-slate-200 rounded-xl font-mono" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor Peserta PPG</label>
              <input v-model="sertifikasiForm.nomor_peserta" type="text" placeholder="No. Peserta..." class="w-full p-2.5 border border-slate-200 rounded-xl font-mono" />
            </div>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Bidang Studi Sertifikasi *</label>
            <input v-model="sertifikasiForm.bidang_studi" required type="text" placeholder="Contoh: Teknik Informatika / Bahasa Indonesia" class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Lembaga Penerbit / LPTK</label>
            <input v-model="sertifikasiForm.lembaga_penerbit" type="text" placeholder="Contoh: Universitas Negeri Malang / Kemendikbud" class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showSertifikasiModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan Sertifikasi</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL BUKA LOWONGAN -->
    <div v-if="showLowonganModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Buka Lowongan Kerja Baru</h3>
          <button @click="showLowonganModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitLowonganForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Judul Posisi Lowongan *</label>
            <input v-model="lowonganForm.judul_posisi" required type="text" placeholder="Contoh: Guru Produktif RPL / Staf TU Keuangan" class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jenis Pekerjaan</label>
              <SearchableSelect
                v-model="lowonganForm.jenis_pekerjaan"
                :options="[{ id: 'Full Time', nama: 'Full Time' }, { id: 'Part Time', nama: 'Part Time' }, { id: 'Kontrak', nama: 'Kontrak' }, { id: 'Honorer', nama: 'Honorer' }]"
                placeholder="-- Jenis --"
              />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Kuota Dibutuhkan</label>
              <input v-model="lowonganForm.kuota_dibutuhkan" required type="number" min="1" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Dibuka *</label>
              <input v-model="lowonganForm.tanggal_buka" required type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Ditutup *</label>
              <input v-model="lowonganForm.tanggal_tutup" required type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Kualifikasi Pendidikan Minimum</label>
            <input v-model="lowonganForm.kualifikasi_pendidikan" type="text" placeholder="Contoh: S1 Komputer / D3 Akuntansi" class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Persyaratan & Deskripsi Pekerjaan</label>
            <textarea v-model="lowonganForm.persyaratan" rows="3" placeholder="Persyaratan kompetensi, pengalaman, dll." class="w-full p-2.5 border border-slate-200 rounded-xl"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showLowonganModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Publikasikan Lowongan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL INPUT PELAMAR -->
    <div v-if="showPelamarModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Input Data Pelamar Baru</h3>
          <button @click="showPelamarModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitPelamarForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Pilih Posisi Lowongan *</label>
            <SearchableSelect
              v-model="pelamarForm.lowongan_id"
              :options="lowonganDropdownOptions"
              placeholder="-- Pilih Lowongan --"
            />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Nama Lengkap Pelamar *</label>
            <input v-model="pelamarForm.nama_lengkap" required type="text" placeholder="Nama Pelamar..." class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Email *</label>
              <input v-model="pelamarForm.email" required type="email" placeholder="pelamar@email.com" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">No. WhatsApp *</label>
              <input v-model="pelamarForm.no_hp" required type="text" placeholder="08..." class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Pendidikan Terakhir</label>
              <input v-model="pelamarForm.pendidikan_terakhir" type="text" placeholder="S1" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">IPK</label>
              <input v-model="pelamarForm.ipk" type="number" step="0.01" placeholder="3.50" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Jurusan / Keahlian</label>
            <input v-model="pelamarForm.jurusan" type="text" placeholder="Pendidikan Bahasa Inggris" class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showPelamarModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan Pelamar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL UPDATE TAHAPAN PELAMAR -->
    <div v-if="showTahapanModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Update Tahapan Seleksi Pelamar</h3>
          <button @click="showTahapanModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="mb-3 text-xs bg-slate-50 p-3 rounded-xl">
          <div class="font-bold text-slate-800">{{ selectedPelamar?.nama_lengkap }}</div>
          <div class="text-slate-500 text-2xs">{{ selectedPelamar?.lowongan?.judul_posisi }}</div>
        </div>

        <form @submit.prevent="submitTahapanForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Status Tahapan *</label>
            <SearchableSelect
              v-model="tahapanForm.status_tahapan"
              :options="tahapanOptions"
              placeholder="-- Pilih Tahapan --"
            />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Catatan Evaluasi / Hasil Wawancara</label>
            <textarea v-model="tahapanForm.catatan_seleksi" rows="3" placeholder="Catatan pewawancara, skor tes..." class="w-full p-2.5 border border-slate-200 rounded-xl"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showTahapanModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Perbarui Status</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js';
import axios from 'axios';

const props = defineProps({
  initialTab: {
    type: String,
    default: 'gtk',
  },
});

const activeTab = ref(props.initialTab || 'gtk');
const loading = ref(false);
const saving = ref(false);
const tabData = ref({});

const filters = ref({
  search: '',
  jenis_ptk: '',
  status_kepegawaian: '',
  lowongan_id: '',
  status_tahapan: '',
});

const allTabs = [
  { id: 'gtk', name: 'Buku Induk GTK', icon: 'bi-people' },
  { id: 'pangkat', name: 'Riwayat Kepangkatan & KGB', icon: 'bi-award' },
  { id: 'sertifikasi', name: 'Sertifikasi Pendidik', icon: 'bi-patch-check' },
  { id: 'recruitment', name: 'E-Recruitment Pegawai', icon: 'bi-briefcase' },
  { id: 'supervisi', name: 'Supervisi Akademik', icon: 'bi-clipboard-check' },
];

const allowedTabs = ref([]);
const availableTabs = computed(() => {
  if (!allowedTabs.value || allowedTabs.value.length === 0) return allTabs;
  return allTabs.filter(t => allowedTabs.value.includes(t.id));
});

const jenisPtkOptions = [
  { id: 'Guru Mapel', nama: 'Guru Mata Pelajaran' },
  { id: 'Guru BK', nama: 'Guru Bimbingan Konseling' },
  { id: 'Guru Kelas', nama: 'Guru Kelas / Wali Kelas' },
  { id: 'Kepala Sekolah', nama: 'Kepala Sekolah' },
  { id: 'Tenaga Administrasi', nama: 'Tenaga Administrasi / Tata Usaha' },
  { id: 'Laboran', nama: 'Laboran / Teknisi IT' },
  { id: 'Pustakawan', nama: 'Tenaga Perpustakaan' },
  { id: 'Tenaga Kebersihan/Keamanan', nama: 'Tenaga Keamanan / Kebersihan' },
];

const jenisPtkFilterOptions = computed(() => [
  { id: '', nama: '-- Semua Jenis PTK --' },
  ...jenisPtkOptions,
]);

const statusKepegawaianOptions = [
  { id: 'PNS', nama: 'PNS (Pegawai Negeri Sipil)' },
  { id: 'PPPK', nama: 'PPPK (Pegawai Pemerintah dg Perjanjian Kerja)' },
  { id: 'GTY', nama: 'GTY (Guru Tetap Yayasan)' },
  { id: 'GTT', nama: 'GTT (Guru Tidak Tetap / Honorer)' },
  { id: 'PTY', nama: 'PTY (Pegawai Tetap Yayasan)' },
  { id: 'PTT', nama: 'PTT (Pegawai Tidak Tetap / Honorer)' },
];

const statusKepegawaianFilterOptions = computed(() => [
  { id: '', nama: '-- Semua Status Pegawai --' },
  ...statusKepegawaianOptions,
]);

const golonganOptions = [
  { id: 'I/a', nama: 'Gol. I/a (Juru Muda)' },
  { id: 'I/b', nama: 'Gol. I/b (Juru Muda Tingkat I)' },
  { id: 'I/c', nama: 'Gol. I/c (Juru)' },
  { id: 'I/d', nama: 'Gol. I/d (Juru Tingkat I)' },
  { id: 'II/a', nama: 'Gol. II/a (Pengatur Muda)' },
  { id: 'II/b', nama: 'Gol. II/b (Pengatur Muda Tingkat I)' },
  { id: 'II/c', nama: 'Gol. II/c (Pengatur)' },
  { id: 'II/d', nama: 'Gol. II/d (Pengatur Tingkat I)' },
  { id: 'III/a', nama: 'Gol. III/a (Penata Muda)' },
  { id: 'III/b', nama: 'Gol. III/b (Penata Muda Tingkat I)' },
  { id: 'III/c', nama: 'Gol. III/c (Penata)' },
  { id: 'III/d', nama: 'Gol. III/d (Penata Tingkat I)' },
  { id: 'IV/a', nama: 'Gol. IV/a (Pembina)' },
  { id: 'IV/b', nama: 'Gol. IV/b (Pembina Tingkat I)' },
  { id: 'IV/c', nama: 'Gol. IV/c (Pembina Utama Muda)' },
  { id: 'IV/d', nama: 'Gol. IV/d (Pembina Utama Madya)' },
  { id: 'IV/e', nama: 'Gol. IV/e (Pembina Utama)' },
];

const tahapanOptions = [
  { id: 'Pendaftaran', nama: 'Pendaftaran Berkas' },
  { id: 'Seleksi Administrasi', nama: 'Seleksi Administrasi Lolos' },
  { id: 'Tes Tertulis/Microteaching', nama: 'Tes Tertulis / Microteaching' },
  { id: 'Wawancara', nama: 'Tahap Wawancara' },
  { id: 'Diterima', nama: '✅ DITERIMA' },
  { id: 'Ditolak', nama: '❌ DITOLAK' },
];

const gtkDropdownOptions = computed(() => {
  const list = tabData.value.gtks || [];
  return list.map(g => ({
    id: g.id,
    nama: g.nama_lengkap,
    subLabel: `NIP: ${g.nip || '-'} | ${g.status_kepegawaian || ''}`,
  }));
});

const lowonganDropdownOptions = computed(() => {
  const list = tabData.value.lowongan || [];
  return list.map(l => ({
    id: l.id,
    nama: l.judul_posisi,
    subLabel: `${l.jenis_pekerjaan} | Status: ${l.status}`,
  }));
});

// Modals State
const showGtkModal = ref(false);
const isEditingGtk = ref(false);
const editingGtkId = ref(null);
const gtkForm = ref({
  nama_lengkap: '',
  gelar_depan: '',
  gelar_belakang: '',
  nip: '',
  nuptk: '',
  nik: '',
  jenis_kelamin: 'L',
  tempat_lahir: '',
  tanggal_lahir: '',
  jenis_ptk: 'Guru Mapel',
  status_kepegawaian: 'GTY',
  jabatan: '',
  pendidikan_terakhir: 'S1',
  jurusan_pendidikan: '',
  no_hp: '',
  email: '',
  alamat_tinggal: '',
});

const showPangkatModal = ref(false);
const pangkatForm = ref({
  ptk_id: '',
  golongan_pangkat: 'III/a',
  nomor_sk: '',
  tanggal_sk: '',
  tmt_pangkat: '',
  pejabat_penetap: '',
  gaji_pokok: '',
  is_terakhir: true,
});

const showSertifikasiModal = ref(false);
const sertifikasiForm = ref({
  ptk_id: '',
  jenis_sertifikasi: 'Pendidik',
  nomor_peserta: '',
  nomor_sertifikat: '',
  tahun_sertifikasi: new Date().getFullYear(),
  bidang_studi: '',
  lembaga_penerbit: '',
});

const showLowonganModal = ref(false);
const lowonganForm = ref({
  judul_posisi: '',
  jenis_pekerjaan: 'Full Time',
  kualifikasi_pendidikan: '',
  persyaratan: '',
  tanggal_buka: new Date().toISOString().slice(0, 10),
  tanggal_tutup: '',
  kuota_dibutuhkan: 1,
  status: 'Buka',
});

const showPelamarModal = ref(false);
const pelamarForm = ref({
  lowongan_id: '',
  nama_lengkap: '',
  email: '',
  no_hp: '',
  pendidikan_terakhir: 'S1',
  jurusan: '',
  ipk: '',
  status_tahapan: 'Pendaftaran',
});

const showTahapanModal = ref(false);
const selectedPelamar = ref(null);
const tahapanForm = ref({
  status_tahapan: '',
  catatan_seleksi: '',
});

// Formatters
const formatNamaGtk = (item) => {
  let name = item.nama_lengkap || '';
  if (item.gelar_depan) name = `${item.gelar_depan} ${name}`;
  if (item.gelar_belakang) name = `${name}, ${item.gelar_belakang}`;
  return name;
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'PNS': return 'bg-emerald-50 text-emerald-700';
    case 'PPPK': return 'bg-teal-50 text-teal-700';
    case 'GTY': case 'PTY': return 'bg-blue-50 text-blue-700';
    default: return 'bg-slate-100 text-slate-700';
  }
};

const getTahapanBadgeClass = (status) => {
  switch (status) {
    case 'Diterima': return 'bg-emerald-100 text-emerald-800';
    case 'Ditolak': return 'bg-rose-100 text-rose-800';
    case 'Wawancara': return 'bg-purple-100 text-purple-800';
    case 'Tes Tertulis/Microteaching': return 'bg-amber-100 text-amber-800';
    case 'Seleksi Administrasi': return 'bg-blue-100 text-blue-800';
    default: return 'bg-slate-100 text-slate-700';
  }
};

// Data Fetching
const fetchTabData = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/kepegawaian', {
      params: {
        async: 1,
        tab: activeTab.value,
        page: typeof page === 'number' ? page : 1,
        ...filters.value,
      },
    });
    if (res.data?.success) {
      if (res.data.allowed_tabs && Array.isArray(res.data.allowed_tabs)) {
        allowedTabs.value = res.data.allowed_tabs;
        if (!res.data.allowed_tabs.includes(activeTab.value) && res.data.allowed_tabs.length > 0) {
          activeTab.value = res.data.allowed_tabs[0];
          return fetchTabData();
        }
      }
      tabData.value = res.data.data;
    }
  } catch (err) {
    console.error('Error fetching kepegawaian data:', err);
  } finally {
    loading.value = false;
  }
};

const switchTab = (tabId) => {
  activeTab.value = tabId;
  filters.value = {
    search: '',
    jenis_ptk: '',
    status_kepegawaian: '',
    lowongan_id: '',
    status_tahapan: '',
  };
  fetchTabData();
};

let searchTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchTabData();
  }, 350);
};

const filterPelamarByLowongan = (lowonganId) => {
  if (filters.value.lowongan_id === lowonganId) {
    filters.value.lowongan_id = '';
  } else {
    filters.value.lowongan_id = lowonganId;
  }
  fetchTabData();
};

// Actions
const openGtkModal = (item = null) => {
  if (item) {
    isEditingGtk.value = true;
    editingGtkId.value = item.id;
    gtkForm.value = { ...item };
  } else {
    isEditingGtk.value = false;
    editingGtkId.value = null;
    gtkForm.value = {
      nama_lengkap: '',
      gelar_depan: '',
      gelar_belakang: '',
      nip: '',
      nuptk: '',
      nik: '',
      jenis_kelamin: 'L',
      tempat_lahir: '',
      tanggal_lahir: '',
      jenis_ptk: 'Guru Mapel',
      status_kepegawaian: 'GTY',
      jabatan: '',
      pendidikan_terakhir: 'S1',
      jurusan_pendidikan: '',
      no_hp: '',
      email: '',
      alamat_tinggal: '',
    };
  }
  showGtkModal.value = true;
};

const submitGtkForm = async () => {
  saving.value = true;
  try {
    if (isEditingGtk.value) {
      await axios.put(`/kepegawaian/gtk/${editingGtkId.value}`, gtkForm.value);
    } else {
      await axios.post('/kepegawaian/gtk', gtkForm.value);
    }
    showGtkModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan data GTK.');
  } finally {
    saving.value = false;
  }
};

const deleteGtk = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus data pendidik/tenaga kependidikan ini?')) return;
  try {
    await axios.delete(`/kepegawaian/gtk/${id}`);
    fetchTabData();
  } catch (err) {
    alert('Gagal menghapus data.');
  }
};

const submitPangkatForm = async () => {
  saving.value = true;
  try {
    await axios.post('/kepegawaian/pangkat', pangkatForm.value);
    showPangkatModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan kepangkatan.');
  } finally {
    saving.value = false;
  }
};

const submitSertifikasiForm = async () => {
  saving.value = true;
  try {
    await axios.post('/kepegawaian/sertifikasi', sertifikasiForm.value);
    showSertifikasiModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan sertifikasi.');
  } finally {
    saving.value = false;
  }
};

const submitLowonganForm = async () => {
  saving.value = true;
  try {
    await axios.post('/kepegawaian/lowongan', lowonganForm.value);
    showLowonganModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal membuka lowongan.');
  } finally {
    saving.value = false;
  }
};

const submitPelamarForm = async () => {
  saving.value = true;
  try {
    await axios.post('/kepegawaian/pelamar', pelamarForm.value);
    showPelamarModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan pelamar.');
  } finally {
    saving.value = false;
  }
};

const openTahapanModal = (pelamar) => {
  selectedPelamar.value = pelamar;
  tahapanForm.value = {
    status_tahapan: pelamar.status_tahapan,
    catatan_seleksi: pelamar.catatan_seleksi || '',
  };
  showTahapanModal.value = true;
};

const submitTahapanForm = async () => {
  if (!selectedPelamar.value) return;
  saving.value = true;
  try {
    await axios.put(`/kepegawaian/pelamar/${selectedPelamar.value.id}/tahapan`, tahapanForm.value);
    showTahapanModal.value = false;
    fetchTabData();
  } catch (err) {
    alert('Gagal memperbarui tahapan seleksi.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchTabData();
});
</script>
