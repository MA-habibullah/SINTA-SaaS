<template>
  <AppLayout title="Kejuruan SMK & Prakerin PKL">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <i class="bi bi-gear-wide-connected text-indigo-600"></i>
          Kejuruan SMK & Prakerin PKL
        </h1>
        <p class="text-xs sm:text-sm text-slate-500">
          Kemitraan Industri DUDI, Penempatan Praktek Kerja Lapangan (PKL), Jurnal Harian, dan Uji Kompetensi Keahlian (UKK).
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="activeTab === 'mitra'"
          @click="openMitraModal()"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition"
        >
          <i class="bi bi-building-add"></i> Tambah Mitra DUDI
        </button>
        <button
          v-else-if="activeTab === 'pkl'"
          @click="showPklModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition"
        >
          <i class="bi bi-person-plus"></i> Plotting Penempatan PKL
        </button>
        <button
          v-else-if="activeTab === 'jurnal'"
          @click="showJurnalModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition"
        >
          <i class="bi bi-journal-plus"></i> Catat Jurnal Harian
        </button>
        <button
          v-else-if="activeTab === 'ukk'"
          @click="showUkkModal = true"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition"
        >
          <i class="bi bi-patch-check"></i> Input Penilaian UKK
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

    <!-- TAB 1: MITRA DUDI & MOU -->
    <div v-if="activeTab === 'mitra'" class="space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Total Mitra DUDI</div>
          <div class="text-xl sm:text-2xl font-black text-slate-800 mt-1">{{ tabData.stats?.total_mitra || 0 }}</div>
          <div class="text-2xs text-slate-500 mt-1">Perusahaan Rekanan</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-emerald-500 uppercase tracking-wider">MoU Aktif</div>
          <div class="text-xl sm:text-2xl font-black text-emerald-600 mt-1">{{ tabData.stats?.mitra_aktif || 0 }}</div>
          <div class="text-2xs text-slate-500 mt-1">Kerjasama Berlaku</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-indigo-500 uppercase tracking-wider">Total Kuota PKL</div>
          <div class="text-xl sm:text-2xl font-black text-indigo-600 mt-1">{{ tabData.stats?.total_kuota || 0 }}</div>
          <div class="text-2xs text-slate-500 mt-1">Kapasitas Tampung Siswa</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="text-2xs font-bold text-amber-500 uppercase tracking-wider">Siswa Sedang PKL</div>
          <div class="text-xl sm:text-2xl font-black text-amber-600 mt-1">{{ tabData.stats?.total_terisi || 0 }}</div>
          <div class="text-2xs text-slate-500 mt-1">Aktif di Lapangan</div>
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
            placeholder="Cari perusahaan, bidang usaha, kota, PIC..."
            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          />
        </div>
        <div class="w-full md:w-56">
          <SearchableSelect
            v-model="filters.status_kerjasama"
            :options="[{ id: '', nama: '-- Semua Status Kerjasama --' }, { id: 'Aktif', nama: 'Aktif' }, { id: 'Kadaluarsa', nama: 'Kadaluarsa' }, { id: 'Nonaktif', nama: 'Nonaktif' }]"
            placeholder="-- Status Kerjasama --"
            @change="fetchTabData"
          />
        </div>
      </div>

      <!-- Table Mitra DUDI -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat data Mitra DUDI...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-building-x text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada data Mitra Industri DUDI.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Perusahaan / Industri</th>
                <th class="p-3.5">Bidang Usaha & Kota</th>
                <th class="p-3.5">Kontak PIC</th>
                <th class="p-3.5">MoU Kerjasama</th>
                <th class="p-3.5 text-center">Kuota & Terisi</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5">
                  <div class="font-bold text-slate-800">{{ item.nama_perusahaan }}</div>
                  <div class="text-2xs text-slate-400">{{ item.alamat_perusahaan || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-indigo-50 text-indigo-700">
                    {{ item.bidang_usaha }}
                  </span>
                  <div class="text-2xs text-slate-500 mt-0.5">{{ item.kota || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <div class="font-bold text-slate-700">{{ item.contact_person_nama || '-' }}</div>
                  <div class="text-2xs text-slate-400">{{ item.contact_person_hp || '-' }} | {{ item.email_perusahaan || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <div class="font-mono text-2xs font-bold">{{ item.nomor_mou_kerjasama || '-' }}</div>
                  <div class="text-2xs text-slate-400">
                    {{ item.tanggal_mulai_mou || '-' }} s.d. {{ item.tanggal_akhir_mou || '-' }}
                  </div>
                </td>
                <td class="p-3.5 text-center">
                  <span class="font-black text-indigo-600">{{ item.pkl_count || 0 }}</span>
                  <span class="text-slate-400"> / {{ item.kuota_penerimaan_pkl || 0 }} Siswa</span>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="item.status_kerjasama === 'Aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                  >
                    {{ item.status_kerjasama }}
                  </span>
                </td>
                <td class="p-3.5 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      @click="openMitraModal(item)"
                      class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                      title="Edit Mitra"
                    >
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <button
                      @click="deleteMitra(item.id)"
                      class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                      title="Hapus Mitra"
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

    <!-- TAB 2: PENEMPATAN PKL -->
    <div v-else-if="activeTab === 'pkl'" class="space-y-6">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80 relative">
          <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
          <input
            v-model="filters.search"
            @input="debounceSearch"
            type="text"
            placeholder="Cari siswa, NISN, kelas, perusahaan..."
            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden"
          />
        </div>
        <div class="w-full md:w-56">
          <SearchableSelect
            v-model="filters.status_pkl"
            :options="[{ id: '', nama: '-- Semua Status PKL --' }, { id: 'Sedang Berjalan', nama: 'Sedang Berjalan' }, { id: 'Selesai', nama: 'Selesai' }, { id: 'Ditarik', nama: 'Ditarik' }]"
            placeholder="-- Status PKL --"
            @change="fetchTabData"
          />
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat daftar penempatan PKL...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-person-x text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada penempatan PKL siswa.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Siswa</th>
                <th class="p-3.5">Tempat PKL (DUDI)</th>
                <th class="p-3.5">Periode Magang</th>
                <th class="p-3.5">Pembimbing</th>
                <th class="p-3.5 text-center">Nilai Akhir</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  {{ item.nama_siswa || item.siswa?.nama_lengkap }}
                  <div class="text-2xs text-slate-400 font-mono">NISN: {{ item.nisn || item.siswa?.nisn || '-' }} | {{ item.nama_kelas || item.siswa?.kelas_saat_ini || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <div class="font-bold text-indigo-600">{{ item.nama_perusahaan || item.mitra?.nama_perusahaan }}</div>
                  <div class="text-2xs text-slate-400">{{ item.mitra?.kota || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <div class="font-bold text-slate-700">{{ item.tanggal_mulai }} s.d. {{ item.tanggal_selesai }}</div>
                </td>
                <td class="p-3.5">
                  <div class="text-2xs font-bold text-slate-700">Sekolah: {{ item.pembimbing_sekolah_nama || '-' }}</div>
                  <div class="text-2xs text-slate-500">DUDI: {{ item.pembimbing_dudi_nama || '-' }}</div>
                </td>
                <td class="p-3.5 text-center">
                  <div v-if="item.nilai_akhir_pkl" class="font-black text-sm text-emerald-600">
                    {{ item.nilai_akhir_pkl }}
                  </div>
                  <div v-else class="text-2xs text-slate-400 italic">Belum Dinilai</div>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="item.status_pkl === 'Selesai' ? 'bg-emerald-50 text-emerald-700' : (item.status_pkl === 'Sedang Berjalan' ? 'bg-blue-50 text-blue-700' : 'bg-rose-50 text-rose-700')"
                  >
                    {{ item.status_pkl }}
                  </span>
                </td>
                <td class="p-3.5 text-center">
                  <button
                    @click="openNilaiModal(item)"
                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-2xs rounded-lg transition"
                  >
                    Input Nilai
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 3: JURNAL HARIAN PKL -->
    <div v-else-if="activeTab === 'jurnal'" class="space-y-6">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80 relative">
          <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
          <input
            v-model="filters.search"
            @input="debounceSearch"
            type="text"
            placeholder="Cari nama siswa, kegiatan..."
            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden"
          />
        </div>
        <div class="w-full md:w-56">
          <SearchableSelect
            v-model="filters.status_verifikasi"
            :options="[{ id: '', nama: '-- Semua Status Jurnal --' }, { id: 'Pending', nama: 'Pending (Belum Diperiksa)' }, { id: 'Disetujui', nama: 'Disetujui' }, { id: 'Revisi', nama: 'Revisi' }]"
            placeholder="-- Status Verifikasi --"
            @change="fetchTabData"
          />
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat jurnal harian PKL...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-journal-x text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada jurnal harian PKL.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Tanggal & Jam</th>
                <th class="p-3.5">Nama Siswa & DUDI</th>
                <th class="p-3.5">Kegiatan Pekerjaan</th>
                <th class="p-3.5">Alat / Bahan</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800 whitespace-nowrap">
                  {{ item.tanggal }}
                  <div class="text-2xs text-slate-400 font-normal">{{ item.jam_masuk || '-' }} - {{ item.jam_pulang || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <div class="font-bold text-slate-800">{{ item.siswa?.nama_lengkap || '-' }}</div>
                  <div class="text-2xs text-indigo-600">{{ item.penempatan?.mitra?.nama_perusahaan || '-' }}</div>
                </td>
                <td class="p-3.5 max-w-sm">
                  <p class="text-slate-700">{{ item.kegiatan_pekerjaan }}</p>
                  <div v-if="item.catatan_pembimbing" class="text-2xs text-amber-600 italic mt-1">
                    Catatan Pembimbing: "{{ item.catatan_pembimbing }}"
                  </div>
                </td>
                <td class="p-3.5 text-slate-500 text-2xs">{{ item.alat_bahan_digunakan || '-' }}</td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="item.status_verifikasi === 'Disetujui' ? 'bg-emerald-50 text-emerald-700' : (item.status_verifikasi === 'Revisi' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700')"
                  >
                    {{ item.status_verifikasi }}
                  </span>
                </td>
                <td class="p-3.5 text-center">
                  <button
                    @click="openVerifikasiModal(item)"
                    class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-100 rounded-lg text-2xs font-bold text-slate-700 transition"
                  >
                    Verifikasi
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 4: UJI KOMPETENSI KEAHLIAN (UKK) -->
    <div v-else-if="activeTab === 'ukk'" class="space-y-6">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
          <p class="text-xs mt-2">Memuat data penilaian UKK...</p>
        </div>
        <div v-else-if="!tabData.items?.data?.length" class="p-12 text-center text-slate-400">
          <i class="bi bi-patch-check text-3xl"></i>
          <p class="text-xs mt-2 font-bold">Belum ada data Uji Kompetensi Keahlian (UKK).</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
              <tr>
                <th class="p-3.5">Nama Siswa & NISN</th>
                <th class="p-3.5">Jurusan & Paket Soal</th>
                <th class="p-3.5">Penguji Internal & Eksternal</th>
                <th class="p-3.5 text-center">Skor Rincian</th>
                <th class="p-3.5 text-center">Total Skor</th>
                <th class="p-3.5 text-center">Predikat</th>
                <th class="p-3.5">No. Sertifikat</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in tabData.items?.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                <td class="p-3.5 font-bold text-slate-800">
                  {{ item.nama_siswa || item.siswa?.nama_lengkap }}
                  <div class="text-2xs text-slate-400 font-mono">NISN: {{ item.nisn || item.siswa?.nisn || '-' }}</div>
                </td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-indigo-50 text-indigo-700">
                    {{ item.jurusan }}
                  </span>
                  <div class="text-2xs text-slate-600 mt-0.5 font-bold">{{ item.nama_paket_soal }}</div>
                </td>
                <td class="p-3.5 text-2xs">
                  <div>Internal: {{ item.penguji_internal || '-' }}</div>
                  <div class="text-slate-400">DUDI: {{ item.penguji_eksternal_dudi || '-' }}</div>
                </td>
                <td class="p-3.5 text-center text-2xs">
                  <div>P: {{ item.skor_perencanaan }} | K: {{ item.skor_proses_kerja }}</div>
                  <div>H: {{ item.skor_hasil_produk }} | S: {{ item.skor_sikap_k3 }}</div>
                </td>
                <td class="p-3.5 text-center font-black text-sm text-indigo-600">
                  {{ item.skor_total }}
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md text-2xs font-bold"
                    :class="item.predikat === 'Sangat Kompeten' || item.predikat === 'Kompeten' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                  >
                    {{ item.predikat }}
                  </span>
                </td>
                <td class="p-3.5 font-mono text-2xs font-bold text-slate-700">
                  {{ item.nomor_sertifikat || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL TAMBAH / EDIT MITRA DUDI -->
    <div v-if="showMitraModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">
            {{ isEditingMitra ? 'Edit Data Mitra Industri' : 'Tambah Mitra Industri (DUDI) Baru' }}
          </h3>
          <button @click="showMitraModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitMitraForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Nama Perusahaan / Instansi *</label>
            <input v-model="mitraForm.nama_perusahaan" required type="text" placeholder="PT. ..." class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Bidang Usaha / Industri *</label>
              <input v-model="mitraForm.bidang_usaha" required type="text" placeholder="Teknologi Informasi / Manufaktur" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Kota / Domisili</label>
              <input v-model="mitraForm.kota" type="text" placeholder="Jakarta Selatan / Surabaya" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Contact Person (PIC)</label>
              <input v-model="mitraForm.contact_person_nama" type="text" placeholder="Nama HRD / Manager" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">No. HP / WhatsApp PIC</label>
              <input v-model="mitraForm.contact_person_hp" type="text" placeholder="08..." class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor MoU Kerjasama</label>
              <input v-model="mitraForm.nomor_mou_kerjasama" type="text" placeholder="MOU/2026/..." class="w-full p-2.5 border border-slate-200 rounded-xl font-mono" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Kuota Siswa PKL (Orang)</label>
              <input v-model="mitraForm.kuota_penerimaan_pkl" type="number" min="0" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Mulai MoU</label>
              <input v-model="mitraForm.tanggal_mulai_mou" type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Berakhir MoU</label>
              <input v-model="mitraForm.tanggal_akhir_mou" type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Alamat Lengkap Perusahaan</label>
            <textarea v-model="mitraForm.alamat_perusahaan" rows="2" class="w-full p-2.5 border border-slate-200 rounded-xl"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showMitraModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan Data Mitra</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL PLOTTING PENEMPATAN PKL -->
    <div v-if="showPklModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Plotting Penempatan PKL Siswa</h3>
          <button @click="showPklModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitPklForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Pilih Siswa *</label>
            <SearchableSelect
              v-model="pklForm.siswa_id"
              :options="siswaDropdownOptions"
              placeholder="-- Pilih Siswa --"
            />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Pilih Mitra Industri DUDI *</label>
            <SearchableSelect
              v-model="pklForm.mitra_dudi_id"
              :options="mitraDropdownOptions"
              placeholder="-- Pilih Perusahaan --"
            />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Pembimbing Sekolah</label>
              <input v-model="pklForm.pembimbing_sekolah_nama" type="text" placeholder="Nama Guru Pembimbing" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Pembimbing DUDI / Industri</label>
              <input v-model="pklForm.pembimbing_dudi_nama" type="text" placeholder="Nama Mentor Industri" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Mulai PKL *</label>
              <input v-model="pklForm.tanggal_mulai" required type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Selesai PKL *</label>
              <input v-model="pklForm.tanggal_selesai" required type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showPklModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan Penempatan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL INPUT NILAI PKL -->
    <div v-if="showNilaiModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Input Nilai Evaluasi PKL</h3>
          <button @click="showNilaiModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="mb-3 text-xs bg-slate-50 p-3 rounded-xl">
          <div class="font-bold text-slate-800">{{ selectedPkl?.nama_siswa || selectedPkl?.siswa?.nama_lengkap }}</div>
          <div class="text-slate-500 text-2xs">{{ selectedPkl?.nama_perusahaan || selectedPkl?.mitra?.nama_perusahaan }}</div>
        </div>

        <form @submit.prevent="submitNilaiForm" class="space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nilai Kinerja DUDI (60%) *</label>
              <input v-model="nilaiForm.nilai_kinerja_dudi" required type="number" step="0.1" min="0" max="100" placeholder="85.5" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nilai Laporan (40%) *</label>
              <input v-model="nilaiForm.nilai_laporan_sekolah" required type="number" step="0.1" min="0" max="100" placeholder="88" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Status PKL *</label>
            <SearchableSelect
              v-model="nilaiForm.status_pkl"
              :options="[{ id: 'Sedang Berjalan', nama: 'Sedang Berjalan' }, { id: 'Selesai', nama: 'Selesai' }, { id: 'Ditarik', nama: 'Ditarik' }]"
              placeholder="-- Status --"
            />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Catatan Evaluasi Pembimbing</label>
            <textarea v-model="nilaiForm.catatan_evaluasi" rows="2" placeholder="Catatan etos kerja, kedisiplinan..." class="w-full p-2.5 border border-slate-200 rounded-xl"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showNilaiModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan Nilai</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL INPUT JURNAL HARIAN -->
    <div v-if="showJurnalModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Catat Jurnal Harian PKL</h3>
          <button @click="showJurnalModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitJurnalForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Pilih Penempatan Siswa *</label>
            <SearchableSelect
              v-model="jurnalForm.penempatan_id"
              :options="penempatanDropdownOptions"
              placeholder="-- Pilih Siswa PKL --"
            />
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal *</label>
              <input v-model="jurnalForm.tanggal" required type="date" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jam Masuk</label>
              <input v-model="jurnalForm.jam_masuk" type="time" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jam Pulang</label>
              <input v-model="jurnalForm.jam_pulang" type="time" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Kegiatan / Pekerjaan yang Dilakukan *</label>
            <textarea v-model="jurnalForm.kegiatan_pekerjaan" required rows="3" placeholder="Rincian aktivitas praktik kerja harian..." class="w-full p-2.5 border border-slate-200 rounded-xl"></textarea>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Alat & Bahan yang Digunakan</label>
            <input v-model="jurnalForm.alat_bahan_digunakan" type="text" placeholder="Contoh: Mesin bubut, kabel UTP, VS Code..." class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showJurnalModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan Jurnal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL VERIFIKASI JURNAL -->
    <div v-if="showVerifikasiModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Verifikasi Jurnal Harian Siswa</h3>
          <button @click="showVerifikasiModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="mb-3 text-xs bg-slate-50 p-3 rounded-xl">
          <div class="font-bold text-slate-800">{{ selectedJurnal?.siswa?.nama_lengkap }} ({{ selectedJurnal?.tanggal }})</div>
          <p class="text-slate-600 text-2xs mt-1">{{ selectedJurnal?.kegiatan_pekerjaan }}</p>
        </div>

        <form @submit.prevent="submitVerifikasiForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Status Verifikasi *</label>
            <SearchableSelect
              v-model="verifikasiForm.status_verifikasi"
              :options="[{ id: 'Disetujui', nama: 'Disetujui' }, { id: 'Revisi', nama: 'Revisi' }, { id: 'Pending', nama: 'Pending' }]"
              placeholder="-- Pilih Status --"
            />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Catatan Koreksi / Masukan</label>
            <textarea v-model="verifikasiForm.catatan_pembimbing" rows="2" placeholder="Masukan untuk siswa..." class="w-full p-2.5 border border-slate-200 rounded-xl"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showVerifikasiModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan Verifikasi</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL PENILAIAN UKK -->
    <div v-if="showUkkModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-slate-800 text-base">Input Penilaian Uji Kompetensi Keahlian (UKK)</h3>
          <button @click="showUkkModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitUkkForm" class="space-y-4 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Pilih Siswa Peserta UKK *</label>
            <SearchableSelect
              v-model="ukkForm.siswa_id"
              :options="siswaDropdownOptions"
              placeholder="-- Pilih Siswa --"
            />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jurusan / Kompetensi *</label>
              <input v-model="ukkForm.jurusan" required type="text" placeholder="Rekayasa Perangkat Lunak" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tahun Ajaran *</label>
              <input v-model="ukkForm.tahun_ajaran" required type="text" placeholder="2026/2027" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Nama Paket Soal / Skema LSP *</label>
            <input v-model="ukkForm.nama_paket_soal" required type="text" placeholder="Paket 2: Pembuatan Web App E-Commerce" class="w-full p-2.5 border border-slate-200 rounded-xl" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Penguji Internal (Guru)</label>
              <input v-model="ukkForm.penguji_internal" type="text" placeholder="Nama Guru Penguji" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Penguji Eksternal (DUDI)</label>
              <input v-model="ukkForm.penguji_eksternal_dudi" type="text" placeholder="Nama Asesor Industri" class="w-full p-2.5 border border-slate-200 rounded-xl" />
            </div>
          </div>

          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
            <div class="font-bold text-slate-800 text-2xs uppercase tracking-wider">Komponen Skor UKK (0-100)</div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-2xs text-slate-600 block">Perencanaan (15%)</label>
                <input v-model="ukkForm.skor_perencanaan" required type="number" min="0" max="100" class="w-full p-2 border border-slate-200 rounded-lg bg-white" />
              </div>
              <div>
                <label class="text-2xs text-slate-600 block">Proses Kerja (35%)</label>
                <input v-model="ukkForm.skor_proses_kerja" required type="number" min="0" max="100" class="w-full p-2 border border-slate-200 rounded-lg bg-white" />
              </div>
              <div>
                <label class="text-2xs text-slate-600 block">Hasil Produk (35%)</label>
                <input v-model="ukkForm.skor_hasil_produk" required type="number" min="0" max="100" class="w-full p-2 border border-slate-200 rounded-lg bg-white" />
              </div>
              <div>
                <label class="text-2xs text-slate-600 block">Sikap & K3 (15%)</label>
                <input v-model="ukkForm.skor_sikap_k3" required type="number" min="0" max="100" class="w-full p-2 border border-slate-200 rounded-lg bg-white" />
              </div>
            </div>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Nomor Sertifikat Kompetensi</label>
            <input v-model="ukkForm.nomor_sertifikat" type="text" placeholder="SERT/UKK/2026/..." class="w-full p-2.5 border border-slate-200 rounded-xl font-mono" />
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showUkkModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan Penilaian</button>
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
    default: 'mitra',
  },
});

const activeTab = ref(props.initialTab || 'mitra');
const loading = ref(false);
const saving = ref(false);
const tabData = ref({});

const filters = ref({
  search: '',
  status_kerjasama: '',
  status_pkl: '',
  status_verifikasi: '',
  predikat: '',
});

useMemorySecurity([tabData, filters]);

const allTabs = [
  { id: 'mitra', name: 'Mitra Industri (DUDI)', icon: 'bi-building' },
  { id: 'pkl', name: 'Penempatan PKL', icon: 'bi-person-workspace' },
  { id: 'jurnal', name: 'Jurnal Harian PKL', icon: 'bi-journal-check' },
  { id: 'ukk', name: 'Uji Kompetensi Keahlian (UKK)', icon: 'bi-patch-check' },
];

const allowedTabs = ref([]);
const availableTabs = computed(() => {
  if (!allowedTabs.value || allowedTabs.value.length === 0) return allTabs;
  return allTabs.filter(t => allowedTabs.value.includes(t.id));
});

const siswaDropdownOptions = computed(() => {
  const list = tabData.value.siswaList || [];
  return list.map(s => ({
    id: s.id,
    nama: s.nama_lengkap,
    subLabel: `NISN: ${s.nisn || '-'} | Kelas: ${s.kelas_saat_ini || '-'}`,
  }));
});

const mitraDropdownOptions = computed(() => {
  const list = tabData.value.mitraList || [];
  return list.map(m => ({
    id: m.id,
    nama: m.nama_perusahaan,
    subLabel: `${m.bidang_usaha} (${m.kota || '-'})`,
  }));
});

const penempatanDropdownOptions = computed(() => {
  const list = tabData.value.penempatanList || [];
  return list.map(p => ({
    id: p.id,
    nama: p.nama_siswa,
    subLabel: `DUDI: ${p.nama_perusahaan}`,
  }));
});

// Modals State
const showMitraModal = ref(false);
const isEditingMitra = ref(false);
const editingMitraId = ref(null);
const mitraForm = ref({
  nama_perusahaan: '',
  bidang_usaha: '',
  alamat_perusahaan: '',
  kota: '',
  contact_person_nama: '',
  contact_person_hp: '',
  nomor_mou_kerjasama: '',
  tanggal_mulai_mou: '',
  tanggal_akhir_mou: '',
  kuota_penerimaan_pkl: 5,
  status_kerjasama: 'Aktif',
});

const showPklModal = ref(false);
const pklForm = ref({
  siswa_id: '',
  mitra_dudi_id: '',
  pembimbing_sekolah_nama: '',
  pembimbing_dudi_nama: '',
  tanggal_mulai: '',
  tanggal_selesai: '',
  status_pkl: 'Sedang Berjalan',
});

const showNilaiModal = ref(false);
const selectedPkl = ref(null);
const nilaiForm = ref({
  nilai_kinerja_dudi: '',
  nilai_laporan_sekolah: '',
  status_pkl: 'Selesai',
  catatan_evaluasi: '',
});

const showJurnalModal = ref(false);
const jurnalForm = ref({
  penempatan_id: '',
  tanggal: new Date().toISOString().slice(0, 10),
  jam_masuk: '08:00',
  jam_pulang: '16:00',
  kegiatan_pekerjaan: '',
  alat_bahan_digunakan: '',
});

const showVerifikasiModal = ref(false);
const selectedJurnal = ref(null);
const verifikasiForm = ref({
  status_verifikasi: 'Disetujui',
  catatan_pembimbing: '',
});

const showUkkModal = ref(false);
const ukkForm = ref({
  siswa_id: '',
  jurusan: '',
  tahun_ajaran: '2026/2027',
  nama_paket_soal: '',
  penguji_internal: '',
  penguji_eksternal_dudi: '',
  skor_perencanaan: 85,
  skor_proses_kerja: 88,
  skor_hasil_produk: 90,
  skor_sikap_k3: 85,
  nomor_sertifikat: '',
});

// Data Fetching
const fetchTabData = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/smk', {
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
    console.error('Error fetching SMK data:', err);
  } finally {
    loading.value = false;
  }
};

const switchTab = (tabId) => {
  activeTab.value = tabId;
  filters.value = {
    search: '',
    status_kerjasama: '',
    status_pkl: '',
    status_verifikasi: '',
    predikat: '',
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

// Actions
const openMitraModal = (item = null) => {
  if (item) {
    isEditingMitra.value = true;
    editingMitraId.value = item.id;
    mitraForm.value = { ...item };
  } else {
    isEditingMitra.value = false;
    editingMitraId.value = null;
    mitraForm.value = {
      nama_perusahaan: '',
      bidang_usaha: '',
      alamat_perusahaan: '',
      kota: '',
      contact_person_nama: '',
      contact_person_hp: '',
      nomor_mou_kerjasama: '',
      tanggal_mulai_mou: '',
      tanggal_akhir_mou: '',
      kuota_penerimaan_pkl: 5,
      status_kerjasama: 'Aktif',
    };
  }
  showMitraModal.value = true;
};

const submitMitraForm = async () => {
  saving.value = true;
  try {
    if (isEditingMitra.value) {
      await axios.put(`/smk/mitra/${editingMitraId.value}`, mitraForm.value);
    } else {
      await axios.post('/smk/mitra', mitraForm.value);
    }
    showMitraModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan Mitra DUDI.');
  } finally {
    saving.value = false;
  }
};

const deleteMitra = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus data Mitra Industri ini?')) return;
  try {
    await axios.delete(`/smk/mitra/${id}`);
    fetchTabData();
  } catch (err) {
    alert('Gagal menghapus mitra.');
  }
};

const submitPklForm = async () => {
  saving.value = true;
  try {
    await axios.post('/smk/pkl', pklForm.value);
    showPklModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan penempatan PKL.');
  } finally {
    saving.value = false;
  }
};

const openNilaiModal = (item) => {
  selectedPkl.value = item;
  nilaiForm.value = {
    nilai_kinerja_dudi: item.nilai_kinerja_dudi || '',
    nilai_laporan_sekolah: item.nilai_laporan_sekolah || '',
    status_pkl: item.status_pkl || 'Selesai',
    catatan_evaluasi: item.catatan_evaluasi || '',
  };
  showNilaiModal.value = true;
};

const submitNilaiForm = async () => {
  if (!selectedPkl.value) return;
  saving.value = true;
  try {
    await axios.put(`/smk/pkl/${selectedPkl.value.id}/nilai`, nilaiForm.value);
    showNilaiModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan nilai PKL.');
  } finally {
    saving.value = false;
  }
};

const submitJurnalForm = async () => {
  saving.value = true;
  try {
    await axios.post('/smk/jurnal', jurnalForm.value);
    showJurnalModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mencatat jurnal.');
  } finally {
    saving.value = false;
  }
};

const openVerifikasiModal = (item) => {
  selectedJurnal.value = item;
  verifikasiForm.value = {
    status_verifikasi: item.status_verifikasi || 'Disetujui',
    catatan_pembimbing: item.catatan_pembimbing || '',
  };
  showVerifikasiModal.value = true;
};

const submitVerifikasiForm = async () => {
  if (!selectedJurnal.value) return;
  saving.value = true;
  try {
    await axios.put(`/smk/jurnal/${selectedJurnal.value.id}/verifikasi`, verifikasiForm.value);
    showVerifikasiModal.value = false;
    fetchTabData();
  } catch (err) {
    alert('Gagal memperbarui verifikasi jurnal.');
  } finally {
    saving.value = false;
  }
};

const submitUkkForm = async () => {
  saving.value = true;
  try {
    await axios.post('/smk/ukk', ukkForm.value);
    showUkkModal.value = false;
    fetchTabData();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan penilaian UKK.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchTabData();
});
</script>
