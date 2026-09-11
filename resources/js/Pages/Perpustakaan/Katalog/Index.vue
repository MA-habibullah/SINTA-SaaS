<template>
  <AppLayout title="Katalog & Inventori Perpustakaan">
    <div class="space-y-6">
      <!-- Header & Action -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Katalog & Inventori Perpustakaan</h1>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-blue-100 text-blue-700">DDC 000-900</span>
          </div>
          <p class="text-xs text-slate-500 mt-1">Klasifikasi Decimal Dewey Classification, multi-eksemplar fisik, lokasi rak, usulan pengadaan, dan buku digital.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button @click="openModalTambahBuku" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs shadow-blue-500/20">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Tambah Buku Baru</span>
          </button>
        </div>
      </div>

      <!-- Section 2: Banner Filter Sekolah (Khusus Super Admin) -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs shrink-0">
              <i class="bi bi-building text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
                  <i class="bi bi-funnel-fill me-1"></i> Aktif
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Menampilkan data katalog perpustakaan milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600 whitespace-nowrap hidden sm:inline">Pilih Sekolah:</label>
            <select v-model="selectedTenantId" @change="applyTenantFilter" class="text-xs rounded-xl border border-slate-200 bg-white py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium text-slate-700 min-w-[240px] shadow-2xs">
              <option value="">-- Semua Sekolah (Agregat Global) --</option>
              <option v-for="t in tenants" :key="t.id" :value="t.id">
                {{ t.nama_sekolah }} ({{ t.npsn }})
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Quick Stats Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Total Judul</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm"><i class="bi bi-journal-bookmark-fill"></i></span>
          </div>
          <div class="text-xl font-black text-slate-800 mt-2">{{ stats.total_judul || 0 }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Judul Buku Terdaftar</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Total Eksemplar</span>
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm"><i class="bi bi-bookshelf"></i></span>
          </div>
          <div class="text-xl font-black text-indigo-700 mt-2">{{ stats.total_eksemplar || 0 }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Fisik Buku di Perpustakaan</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Tersedia di Rak</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm"><i class="bi bi-check-circle-fill"></i></span>
          </div>
          <div class="text-xl font-black text-emerald-600 mt-2">{{ stats.total_tersedia || 0 }}</div>
          <div class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">Siap Dipinjam</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Sedang Dipinjam</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm"><i class="bi bi-arrow-repeat"></i></span>
          </div>
          <div class="text-xl font-black text-amber-600 mt-2">{{ stats.total_dipinjam || 0 }}</div>
          <div class="text-[11px] text-amber-600/80 mt-0.5 font-medium">Di Tangan Siswa/Guru</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs col-span-2 sm:col-span-1">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">E-Book Digital</span>
            <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm"><i class="bi bi-file-earmark-pdf-fill"></i></span>
          </div>
          <div class="text-xl font-black text-purple-700 mt-2">{{ stats.total_ebook || 0 }}</div>
          <div class="text-[11px] text-purple-600/80 mt-0.5 font-medium">Dapat Dibaca Online</div>
        </div>
      </div>

      <!-- Standard Modern Horizontal NavTabs Scroller (Pill Layout) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-katalog')?.scrollBy({ left: -220, behavior: 'smooth' })"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navtabs-katalog" role="tablist">
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'buku' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'buku'">
                  <i class="bi bi-book-half me-2 text-sm"></i> 1. Katalog & Bibliografi Buku
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'eksemplar' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'eksemplar'">
                  <i class="bi bi-upc-scan me-2 text-sm"></i> 2. Eksemplar & Barcode Fisik
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'rak' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'rak'">
                  <i class="bi bi-grid-3x3-gap-fill me-2 text-sm"></i> 3. Master Lokasi Rak
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'usulan' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'usulan'">
                  <i class="bi bi-lightbulb-fill me-2 text-sm"></i> 4. Usulan Pengadaan Buku
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'serial' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'serial'">
                  <i class="bi bi-newspaper me-2 text-sm"></i> 5. Majalah & Serial Berkala
                </button>
              </li>
            </ul>
          </div>

          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-katalog')?.scrollBy({ left: 220, behavior: 'smooth' })"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- TAB 1: DAFTAR KATALOG BUKU -->
      <div v-if="activeTab === 'buku'" class="space-y-4">
        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
            <div class="relative w-full md:w-64">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="bi bi-search"></i></span>
              <input v-model="searchQuery" @keyup.enter="applySearch" type="text" placeholder="Cari judul, penulis, ISBN..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
            </div>

            <select v-model="filterDdc" @change="applySearch" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
              <option value="">Semua Klasifikasi DDC</option>
              <option value="000">000 - Karya Umum & Komputer</option>
              <option value="100">100 - Filsafat & Psikologi</option>
              <option value="200">200 - Agama</option>
              <option value="300">300 - Ilmu Sosial</option>
              <option value="400">400 - Bahasa</option>
              <option value="500">500 - Sains & Matematika</option>
              <option value="600">600 - Teknologi & Terapan</option>
              <option value="700">700 - Kesenian & Olahraga</option>
              <option value="800">800 - Kesusastraan</option>
              <option value="900">900 - Sejarah & Geografi</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <button @click="viewMode = viewMode === 'table' ? 'grid' : 'table'" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
              <i class="bi" :class="viewMode === 'table' ? 'bi-grid-fill' : 'bi-table'"></i>
              <span>{{ viewMode === 'table' ? 'Tampilan Grid' : 'Tampilan Tabel' }}</span>
            </button>
          </div>
        </div>

        <!-- Mode Tabel -->
        <div v-if="viewMode === 'table'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Buku & Bibliografi</th>
                  <th class="py-3.5 px-3">Klasifikasi / DDC</th>
                  <th class="py-3.5 px-3">Penerbit & Tahun</th>
                  <th class="py-3.5 px-3">Lokasi Rak</th>
                  <th class="py-3.5 px-3 text-center">Stok Fisik</th>
                  <th class="py-3.5 px-3 text-center">OPAC</th>
                  <th class="py-3.5 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="buku in bukuList.data" :key="buku.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-14 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-400">
                        <img v-if="buku.cover_url" :src="buku.cover_url" alt="Cover" class="w-full h-full object-cover" />
                        <i v-else class="bi bi-book text-base text-slate-400"></i>
                      </div>
                      <div>
                        <div class="font-extrabold text-slate-800 text-xs">{{ buku.judul_buku }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Penulis: {{ buku.pengarang }}</div>
                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">ISBN: {{ buku.isbn || '-' }} | Kode: {{ buku.kode_buku }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3 px-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                      {{ buku.nomor_klasifikasi_ddc || '000' }}
                    </span>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ buku.kategori || 'Umum' }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-medium text-slate-700">{{ buku.penerbit || '-' }}</div>
                    <div class="text-[10px] text-slate-400">{{ buku.tahun_terbit || '-' }} ({{ buku.kota_terbit || '-' }})</div>
                  </td>
                  <td class="py-3 px-3">
                    <span class="font-bold text-slate-700">{{ buku.lokasi_rak || '-' }}</span>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <div class="inline-flex flex-col items-center">
                      <span class="font-black text-xs" :class="buku.jumlah_tersedia > 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ buku.jumlah_tersedia }} / {{ buku.jumlah_eksemplar }}
                      </span>
                      <span class="text-[10px] text-slate-400">Tersedia</span>
                    </div>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <button @click="toggleStatus(buku.id)" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border transition"
                            :class="buku.status_opac ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'">
                      {{ buku.status_opac ? 'Publik' : 'Privat' }}
                    </button>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button @click="openModalEditBuku(buku)" class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" title="Edit Data Buku">
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button @click="openModalEksemplar(buku)" class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Kelola Eksemplar">
                        <i class="bi bi-upc"></i>
                      </button>
                      <button @click="confirmDeleteBuku(buku.id)" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus Buku">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!bukuList.data?.length">
                  <td colspan="7" class="py-8 text-center text-slate-400 text-xs">Belum ada data buku perpustakaan yang sesuai filter.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Mode Grid Card -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
          <div v-for="buku in bukuList.data" :key="buku.id" class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs flex flex-col justify-between hover:shadow-md transition">
            <div>
              <div class="h-44 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden relative mb-2.5">
                <img v-if="buku.cover_url" :src="buku.cover_url" alt="Cover" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 p-2 text-center">
                  <i class="bi bi-book text-3xl mb-1"></i>
                  <span class="text-[10px] font-bold">{{ buku.judul_buku }}</span>
                </div>
                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-md bg-slate-900/80 text-white text-[10px] font-bold backdrop-blur-xs">
                  DDC: {{ buku.nomor_klasifikasi_ddc || '000' }}
                </span>
              </div>
              <h4 class="font-extrabold text-slate-800 text-xs line-clamp-2 leading-snug">{{ buku.judul_buku }}</h4>
              <p class="text-[11px] text-slate-500 mt-1 line-clamp-1">Oleh: {{ buku.pengarang }}</p>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
              <span class="text-[11px] font-bold" :class="buku.jumlah_tersedia > 0 ? 'text-emerald-600' : 'text-rose-600'">
                {{ buku.jumlah_tersedia }} Tersedia
              </span>
              <button @click="openModalEditBuku(buku)" class="text-blue-600 hover:text-blue-800 text-xs font-bold">Edit</button>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: EKSEMPLAR & BARCODE -->
      <div v-if="activeTab === 'eksemplar'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-xs">Daftar Eksemplar Fisik & Barcode Peminjaman</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3 px-4">Barcode</th>
                  <th class="py-3 px-4">No. Induk</th>
                  <th class="py-3 px-4">Judul Buku</th>
                  <th class="py-3 px-4">Lokasi Rak</th>
                  <th class="py-3 px-4 text-center">Kondisi / Status</th>
                  <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="eks in eksemplarList.data" :key="eks.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-mono font-bold text-blue-700">{{ eks.barcode }}</td>
                  <td class="py-3 px-4 font-mono text-slate-600">{{ eks.no_induk }}</td>
                  <td class="py-3 px-4 font-bold text-slate-800">{{ eks.buku?.judul_buku || '-' }}</td>
                  <td class="py-3 px-4 text-slate-600">{{ eks.lokasi_rak || '-' }}</td>
                  <td class="py-3 px-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                          :class="eks.status_kondisi === 'Tersedia' ? 'bg-emerald-50 text-emerald-700' : (eks.status_kondisi === 'Dipinjam' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700')">
                      {{ eks.status_kondisi }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <button @click="deleteEksemplar(eks.id)" class="p-1 text-slate-400 hover:text-rose-600 transition"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 3: MASTER RAK -->
      <div v-if="activeTab === 'rak'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs">
          <h3 class="font-extrabold text-slate-800 text-sm mb-3">Tambah Lokasi Rak Baru</h3>
          <form @submit.prevent="submitRak" class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kode Rak</label>
              <input v-model="formRak.kode_rak" type="text" required placeholder="Contoh: RAK-01" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Rak</label>
              <input v-model="formRak.nama_rak" type="text" required placeholder="Rak Sains & Teknologi" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Lantai / Gedung</label>
              <input v-model="formRak.lantai_gedung" type="text" placeholder="Lantai 1 Gedung Perpustakaan" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
            </div>
            <button type="submit" :disabled="formRak.processing" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition">
              Simpan Lokasi Rak
            </button>
          </form>
        </div>

        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="p-4 border-b border-slate-100"><h3 class="font-bold text-slate-800 text-xs">Daftar Lokasi Rak Perpustakaan</h3></div>
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                <th class="py-3 px-4">Kode Rak</th>
                <th class="py-3 px-4">Nama Rak</th>
                <th class="py-3 px-4">Lantai</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="rak in rakList" :key="rak.id">
                <td class="py-3 px-4 font-mono font-bold text-blue-700">{{ rak.kode_rak }}</td>
                <td class="py-3 px-4 font-bold text-slate-800">{{ rak.nama_rak }}</td>
                <td class="py-3 px-4 text-slate-500">{{ rak.lantai_gedung || '-' }}</td>
                <td class="py-3 px-4 text-center">
                  <button @click="deleteRak(rak.id)" class="p-1 text-slate-400 hover:text-rose-600 transition"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 4: USULAN BUKU -->
      <div v-if="activeTab === 'usulan'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-xs">Daftar Usulan Pengadaan Buku Baru dari Siswa & Guru</h3>
            <button @click="showModalUsulan = true" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5">
              <i class="bi bi-plus"></i> Usulkan Buku
            </button>
          </div>
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                <th class="py-3 px-4">Judul Usulan</th>
                <th class="py-3 px-4">Pengarang / Penerbit</th>
                <th class="py-3 px-4">Diusulkan Oleh</th>
                <th class="py-3 px-4">Alasan</th>
                <th class="py-3 px-4 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="u in usulanList" :key="u.id">
                <td class="py-3 px-4 font-bold text-slate-800">{{ u.judul_buku }}</td>
                <td class="py-3 px-4 text-slate-600">{{ u.pengarang }} / {{ u.penerbit }}</td>
                <td class="py-3 px-4 text-slate-700 font-medium">{{ u.pengusul_nama }}</td>
                <td class="py-3 px-4 text-slate-500">{{ u.alasan_usulan || '-' }}</td>
                <td class="py-3 px-4 text-center">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700">{{ u.status_usulan }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 5: SERIAL BERKALA -->
      <div v-if="activeTab === 'serial'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-xs">Daftar Majalah, Jurnal & Surat Kabar Serial</h3>
            <button @click="showModalSerial = true" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5">
              <i class="bi bi-plus"></i> Tambah Serial
            </button>
          </div>
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                <th class="py-3 px-4">Nama Majalah / Jurnal</th>
                <th class="py-3 px-4">Jenis</th>
                <th class="py-3 px-4">ISSN</th>
                <th class="py-3 px-4">Edisi / Nomor</th>
                <th class="py-3 px-4">Frekuensi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="s in serialList" :key="s.id">
                <td class="py-3 px-4 font-bold text-slate-800">{{ s.nama_serial }}</td>
                <td class="py-3 px-4 text-slate-600">{{ s.jenis_serial }}</td>
                <td class="py-3 px-4 font-mono text-slate-500">{{ s.issn || '-' }}</td>
                <td class="py-3 px-4 text-slate-700 font-medium">{{ s.edisi_nomor }}</td>
                <td class="py-3 px-4 text-slate-600">{{ s.frekuensi_terbit }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL TAMBAH / EDIT BUKU -->
    <Teleport to="body">
      <div v-if="showModalBuku" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 relative">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <h3 class="text-base font-black text-slate-800">
              {{ isEditingBuku ? 'Edit Data Buku Bibliografi' : 'Tambah Buku Baru ke Katalog' }}
            </h3>
            <button @click="showModalBuku = false" class="text-slate-400 hover:text-slate-600 text-lg"><i class="bi bi-x-lg"></i></button>
          </div>

          <form @submit.prevent="submitBuku" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Buku <span class="text-red-500">*</span></label>
                <input v-model="formBuku.judul_buku" type="text" required placeholder="Masukkan judul lengkap buku..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pengarang / Penulis <span class="text-red-500">*</span></label>
                <input v-model="formBuku.pengarang" type="text" required placeholder="Nama pengarang..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Penerbit</label>
                <input v-model="formBuku.penerbit" type="text" placeholder="Nama penerbit..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Klasifikasi DDC</label>
                <select v-model="formBuku.nomor_klasifikasi_ddc" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
                  <option value="000">000 - Karya Umum & Komputer</option>
                  <option value="100">100 - Filsafat & Psikologi</option>
                  <option value="200">200 - Agama</option>
                  <option value="300">300 - Ilmu Sosial</option>
                  <option value="400">400 - Bahasa</option>
                  <option value="500">500 - Sains & Matematika</option>
                  <option value="600">600 - Teknologi & Terapan</option>
                  <option value="700">700 - Kesenian & Olahraga</option>
                  <option value="800">800 - Kesusastraan</option>
                  <option value="900">900 - Sejarah & Geografi</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ISBN / Barcode</label>
                <input v-model="formBuku.isbn" type="text" placeholder="978-602-xxx" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Rak</label>
                <input v-model="formBuku.lokasi_rak" type="text" placeholder="Contoh: RAK-01 / Lantai 1" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Eksemplar Fisik</label>
                <input v-model="formBuku.jumlah_eksemplar" type="number" min="1" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Cover Buku (Gambar)</label>
                <input type="file" @change="e => formBuku.cover_file = e.target.files[0]" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Unggah E-Book (PDF/EPUB)</label>
                <input type="file" @change="e => { formBuku.ebook_file = e.target.files[0]; formBuku.is_ebook = true; }" accept=".pdf,.epub" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" />
              </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
              <button type="button" @click="showModalBuku = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">Batal</button>
              <button type="submit" :disabled="formBuku.processing" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2">
                <span v-if="formBuku.processing">Menyimpan...</span>
                <span v-else>Simpan ke Katalog</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  bukuList: Object,
  eksemplarList: Object,
  rakList: Array,
  usulanList: Array,
  serialList: Array,
  stats: Object,
  tenants: Array,
  isSuperAdmin: Boolean,
  activeTenantId: String,
  filters: Object,
});

const activeTab = ref('buku');
const viewMode = ref('table');
const searchQuery = ref(props.filters?.search || '');
const filterDdc = ref(props.filters?.ddc || '');
const selectedTenantId = ref(props.filters?.tenant_id || '');

const getSelectedTenantName = () => {
  if (!selectedTenantId.value) return 'Semua Sekolah (Agregat Global)';
  const found = props.tenants?.find(t => t.id === selectedTenantId.value);
  return found ? `${found.nama_sekolah} (${found.npsn})` : 'Sekolah Terpilih';
};

const showModalBuku = ref(false);
const isEditingBuku = ref(false);
const editingBukuId = ref(null);

const formBuku = useForm({
  judul_buku: '',
  pengarang: '',
  penerbit: '',
  kota_terbit: '',
  tahun_terbit: new Date().getFullYear(),
  isbn: '',
  nomor_klasifikasi_ddc: '000',
  lokasi_rak: 'Rak Utama',
  jumlah_eksemplar: 1,
  cover_file: null,
  ebook_file: null,
  is_ebook: false,
  status_opac: true,
});

const formRak = useForm({
  kode_rak: '',
  nama_rak: '',
  lantai_gedung: 'Lantai 1',
  kapasitas_buku: 100,
});

const applySearch = () => {
  router.get('/perpustakaan/katalog', {
    search: searchQuery.value,
    ddc: filterDdc.value,
    tenant_id: selectedTenantId.value,
  }, { preserveState: true });
};

const applyTenantFilter = () => {
  router.get('/perpustakaan/katalog', {
    tenant_id: selectedTenantId.value,
    search: searchQuery.value,
    ddc: filterDdc.value,
  }, { preserveState: true });
};

const openModalTambahBuku = () => {
  isEditingBuku.value = false;
  editingBukuId.value = null;
  formBuku.reset();
  showModalBuku.value = true;
};

const openModalEditBuku = (buku) => {
  isEditingBuku.value = true;
  editingBukuId.value = buku.id;
  formBuku.judul_buku = buku.judul_buku;
  formBuku.pengarang = buku.pengarang;
  formBuku.penerbit = buku.penerbit;
  formBuku.kota_terbit = buku.kota_terbit;
  formBuku.tahun_terbit = buku.tahun_terbit;
  formBuku.isbn = buku.isbn;
  formBuku.nomor_klasifikasi_ddc = buku.nomor_klasifikasi_ddc || '000';
  formBuku.lokasi_rak = buku.lokasi_rak;
  formBuku.jumlah_eksemplar = buku.jumlah_eksemplar;
  formBuku.is_ebook = buku.is_ebook;
  formBuku.status_opac = buku.status_opac;
  showModalBuku.value = true;
};

const submitBuku = () => {
  if (isEditingBuku.value) {
    formBuku.post(`/perpustakaan/katalog/${editingBukuId.value}`, {
      onSuccess: () => { showModalBuku.value = false; },
    });
  } else {
    formBuku.post('/perpustakaan/katalog', {
      onSuccess: () => { showModalBuku.value = false; formBuku.reset(); },
    });
  }
};

const toggleStatus = (id) => {
  router.post(`/perpustakaan/katalog/${id}/toggle-status`, {}, { preserveScroll: true });
};

const confirmDeleteBuku = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus buku ini dari katalog?')) {
    router.delete(`/perpustakaan/katalog/${id}`, { preserveScroll: true });
  }
};

const submitRak = () => {
  formRak.post('/perpustakaan/master-rak', {
    onSuccess: () => { formRak.reset(); },
  });
};

const deleteRak = (id) => {
  if (confirm('Hapus lokasi rak ini?')) {
    router.delete(`/perpustakaan/master-rak/${id}`, { preserveScroll: true });
  }
};

const deleteEksemplar = (id) => {
  if (confirm('Hapus eksemplar buku ini?')) {
    router.delete(`/perpustakaan/eksemplar/${id}`, { preserveScroll: true });
  }
};
</script>
