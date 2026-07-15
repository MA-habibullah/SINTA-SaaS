<!-- ═══════════════════════════════════════════════════════════
          TAB SIMULASI PEMILIHAN KAMPUS & PRODI PDSS
     ════════════════════════════════════════════════════════════ -->
<div class="space-y-6">

    <!-- SELECTOR TAHUN AJARAN & KONTROL FASE SIMULASI -->
    <div class="card border-0 shadow-sm rounded-2xl bg-white border border-slate-100 mb-4">
        <div class="card-body p-4 flex flex-wrap items-center justify-between gap-4">
            <!-- Left Info -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-mortarboard-fill text-lg"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-800 mb-0.5">Tahun Ajaran Evaluasi</h4>
                    <p class="text-xs text-slate-500 mb-0">Pilih tahun ajaran target untuk menjalankan simulasi pemilihan prodi.</p>
                </div>
            </div>
            <!-- Right Dropdown -->
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-xs font-semibold text-slate-600">Pilih Tahun Ajaran:</span>
                <select v-model="filterAcademicYear" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer min-w-[180px]" @change="fetchSimulasi(); fetchSimulasiSettings();">
                    <option value="" disabled>— Pilih Tahun Ajaran —</option>
                    <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                        {{ yr.tahun_ajaran }} <span v-if="parseInt(yr.is_active) === 1">(Aktif)</span>
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- TABS SIMULASI 1, 2, 3 -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-3">
        <!-- Selector Pills -->
        <div class="flex bg-slate-100 p-1 rounded-xl">
            <button v-for="num in [1, 2, 3]" :key="num"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
                    :class="activeNoSimulasi === num ? 'bg-white text-purple-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    @click="activeNoSimulasi = num; fetchSimulasi();">
                Simulasi {{ num }}
            </button>
        </div>

        <!-- Action Control Buttons for BK/Admin -->
        <div v-if="canWrite" class="flex items-center gap-2">
            <!-- Status Badge -->
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold"
                 :class="simulasiSettings[activeNoSimulasi]?.is_locked ? 'bg-red-50 text-red-700 border border-red-100' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-100 text-slate-600')">
                <i class="bi" :class="simulasiSettings[activeNoSimulasi]?.is_locked ? 'bi-lock-fill' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'bi-check-circle-fill' : 'bi-dash-circle')"></i>
                <span>
                    {{ simulasiSettings[activeNoSimulasi]?.is_locked ? 'DIKUNCI' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'PENGISIAN DIBUKA' : 'DITUTUP') }}
                </span>
            </div>

            <!-- Controls -->
            <button v-if="!simulasiSettings[activeNoSimulasi]?.is_locked && !simulasiSettings[activeNoSimulasi]?.is_open"
                    class="btn btn-sm btn-outline-success rounded-xl font-bold flex items-center gap-1"
                    @click="toggleSimulasiSetting(activeNoSimulasi, 'open')">
                <i class="bi bi-play-fill"></i> Buka Pengisian
            </button>
            <button v-if="!simulasiSettings[activeNoSimulasi]?.is_locked && simulasiSettings[activeNoSimulasi]?.is_open"
                    class="btn btn-sm btn-outline-warning rounded-xl font-bold flex items-center gap-1"
                    @click="toggleSimulasiSetting(activeNoSimulasi, 'close')">
                <i class="bi bi-pause-fill"></i> Tutup Pengisian
            </button>
            <button v-if="!simulasiSettings[activeNoSimulasi]?.is_locked"
                    class="btn btn-sm btn-outline-danger rounded-xl font-bold flex items-center gap-1"
                    @click="toggleSimulasiSetting(activeNoSimulasi, 'lock')">
                <i class="bi bi-lock-fill"></i> Kunci Permanen
            </button>
            
            <!-- Export Button -->
            <button class="btn btn-sm btn-outline-primary rounded-xl font-bold flex items-center gap-1"
                    @click="exportSimulasi">
                <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- INFO ALERT FOR PHASE -->
    <div class="alert border-0 rounded-2xl flex items-start gap-3 p-4"
         :class="simulasiSettings[activeNoSimulasi]?.is_locked ? 'alert-danger bg-red-50 text-red-800' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'alert-success bg-emerald-50 text-emerald-800' : 'alert-warning bg-amber-50 text-amber-800')">
        <div class="text-lg leading-none">
            <i class="bi" :class="simulasiSettings[activeNoSimulasi]?.is_locked ? 'bi-lock-fill' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill')"></i>
        </div>
        <div class="text-xs">
            <div class="font-bold mb-0.5">Informasi Fase Simulasi {{ activeNoSimulasi }}</div>
            <span v-if="simulasiSettings[activeNoSimulasi]?.is_locked">
                Fase Simulasi {{ activeNoSimulasi }} telah **Dikunci Permanen**. Tidak ada pengubahan pilihan atau data yang dapat dilakukan oleh siswa maupun guru BK.
            </span>
            <span v-else-if="simulasiSettings[activeNoSimulasi]?.is_open">
                Pengisian Simulasi {{ activeNoSimulasi }} sedang **Dibuka**. Siswa dapat mengisi pilihan jurusan melalui akun mereka, dan BK dapat menginput atau mengedit secara langsung di halaman ini.
            </span>
            <span v-else>
                Pengisian Simulasi {{ activeNoSimulasi }} sedang **Ditutup / Belum Dibuka**. Siswa tidak dapat mengisi atau mengubah data pilihan dari panel mereka.
            </span>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Eligible -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-people-fill text-lg"></i>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Total Eligible</span>
                <span class="text-lg font-extrabold text-slate-800">{{ simulasiStats.total_eligible }}</span>
            </div>
        </div>
        <!-- Sudah Isi -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-check-circle-fill text-lg"></i>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Sudah Mengisi</span>
                <span class="text-lg font-extrabold text-slate-800">{{ simulasiStats.sudah_isi }}</span>
            </div>
        </div>
        <!-- Belum Isi -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-exclamation-circle-fill text-lg"></i>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Belum Mengisi</span>
                <span class="text-lg font-extrabold text-slate-800">{{ simulasiStats.belum_isi }}</span>
            </div>
        </div>
        <!-- Total Konflik -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-exclamation-triangle-fill text-lg"></i>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Terjadi Konflik</span>
                <span class="text-lg font-extrabold text-slate-800">{{ simulasiStats.total_konflik }}</span>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
        <div class="row g-3">
            <!-- Search -->
            <div class="col-md-3">
                <label class="form-label text-slate-500 text-xs font-semibold mb-1">Cari Siswa</label>
                <div class="relative">
                    <input type="text" v-model="filterSimulasi.search" class="form-control rounded-xl text-xs pl-8 focus:ring-purple-500 focus:border-purple-500 border-slate-200" placeholder="Nama / NISN...">
                    <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                </div>
            </div>
            <!-- Major Filter -->
            <div class="col-md-3">
                <label class="form-label text-slate-500 text-xs font-semibold mb-1">Filter Jurusan</label>
                <select v-model="filterSimulasi.major" class="form-select rounded-xl text-xs border-slate-200 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Semua Jurusan</option>
                    <option v-for="mj in uniqueMajors" :key="mj" :value="mj">{{ mj }}</option>
                </select>
            </div>
            <!-- Conflict Status -->
            <div class="col-md-3">
                <label class="form-label text-slate-500 text-xs font-semibold mb-1">Status Konflik</label>
                <select v-model="filterSimulasi.status_konflik" class="form-select rounded-xl text-xs border-slate-200 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Semua Status</option>
                    <option value="konflik">Terjadi Konflik</option>
                    <option value="aman">Aman / Tidak Konflik</option>
                </select>
            </div>
            <!-- Fill Status -->
            <div class="col-md-3">
                <label class="form-label text-slate-500 text-xs font-semibold mb-1">Status Pengisian</label>
                <select v-model="filterSimulasi.sudah_isi" class="form-select rounded-xl text-xs border-slate-200 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Semua</option>
                    <option value="sudah">Sudah Mengisi</option>
                    <option value="belum">Belum Mengisi</option>
                </select>
            </div>
        </div>
    </div>

    <!-- MAIN DATA TABLE -->
    <div class="card border-0 shadow-sm rounded-2xl bg-white border border-slate-100 overflow-hidden">
        <div v-if="loadingSimulasi" class="p-8 text-center">
            <div class="spinner-border text-purple-600 spinner-border-sm" role="status"></div>
            <p class="text-xs text-slate-400 mt-2 mb-0">Memuat data simulasi...</p>
        </div>
        <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-slate-700">
                <thead class="bg-slate-50 text-slate-500 text-[10px] font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="pl-6 py-3.5 text-center" style="width: 60px;">Rank</th>
                        <th class="py-3.5">Nama Siswa</th>
                        <th class="py-3.5" style="width: 110px;">Kelas / Jurusan</th>
                        <th class="py-3.5 text-center" style="width: 80px;">Rata-rata</th>
                        <th class="py-3.5">Pilihan 1</th>
                        <th class="py-3.5">Pilihan 2</th>
                        <th v-if="activeNoSimulasi === 3" class="py-3.5" style="width: 140px;">Bukti Upload</th>
                        <th class="py-3.5 text-right pr-6" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    <tr v-for="s in simulasiData.filter(item => {
                        if (filterSimulasi.search && !item.nama_lengkap.toLowerCase().includes(filterSimulasi.search.toLowerCase()) && !item.nisn.includes(filterSimulasi.search)) return false;
                        if (filterSimulasi.major && item.nama_jurusan !== filterSimulasi.major) return false;
                        if (filterSimulasi.status_konflik === 'konflik' && !item.is_konflik_1 && !item.is_konflik_2) return false;
                        if (filterSimulasi.status_konflik === 'aman' && (item.is_konflik_1 || item.is_konflik_2)) return false;
                        if (filterSimulasi.sudah_isi === 'sudah' && !item.sudah_isi) return false;
                        if (filterSimulasi.sudah_isi === 'belum' && item.sudah_isi) return false;
                        return true;
                    })" :key="s.siswa_id">
                        
                        <!-- Peringkat Eligible -->
                        <td class="pl-6 text-center font-bold text-slate-700">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs"
                                  :class="s.rank_eligible <= 3 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'">
                                #{{ s.rank_eligible }}
                            </span>
                        </td>

                        <!-- Profil Siswa -->
                        <td>
                            <div class="font-bold text-slate-800">{{ s.nama_lengkap }}</div>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">NISN: {{ s.nisn }}</div>
                        </td>

                        <!-- Kelas / Jurusan -->
                        <td>
                            <div class="font-semibold text-slate-700">{{ s.nama_kelas }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ s.kode_jurusan }}</div>
                        </td>

                        <!-- Rerata Nilai -->
                        <td class="text-center font-bold text-slate-800">
                            {{ s.rata_rata }}
                        </td>

                        <!-- Pilihan 1 -->
                        <td>
                            <div v-if="s.kampus_nama_1">
                                <span class="font-bold text-purple-700 block">{{ s.kampus_nama_1 }}</span>
                                <span class="text-slate-600">{{ s.prodi_nama_1 }}</span>
                                
                                <!-- Conflict Warning Alert (Tooltip simulation) -->
                                <div v-if="s.is_konflik_1" class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-red-50 text-[10px] text-red-700 border border-red-100 font-bold"
                                          :title="'Sudah dipilih oleh: ' + s.konflik_info_1.nama + ' (Peringkat #' + s.konflik_info_1.rank + ' - ' + s.konflik_info_1.kelas + ')'">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Konflik
                                    </span>
                                </div>
                                <div v-else class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-[10px] text-emerald-700 border border-emerald-100 font-semibold">
                                        <i class="bi bi-check-circle-fill"></i> Prioritas Utama
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-slate-300 italic">Belum mengisi</div>
                        </td>

                        <!-- Pilihan 2 -->
                        <td>
                            <div v-if="s.kampus_nama_2">
                                <span class="font-bold text-slate-700 block">{{ s.kampus_nama_2 }}</span>
                                <span class="text-slate-600">{{ s.prodi_nama_2 }}</span>
                                
                                <!-- Conflict Warning Alert -->
                                <div v-if="s.is_konflik_2" class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-red-50 text-[10px] text-red-700 border border-red-100 font-bold"
                                          :title="'Sudah dipilih oleh: ' + s.konflik_info_2.nama + ' (Peringkat #' + s.konflik_info_2.rank + ' - ' + s.konflik_info_2.kelas + ')'">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Konflik
                                    </span>
                                </div>
                                <div v-else class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-50 text-[10px] text-slate-700 border border-slate-100 font-medium">
                                        <i class="bi bi-check-circle-fill"></i> Pilihan 2
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-slate-300 italic">Kosong</div>
                        </td>

                        <!-- Bukti Upload (Sim 3 Only) -->
                        <td v-if="activeNoSimulasi === 3">
                            <div v-if="s.bukti_file">
                                <a :href="'/SINTA-SaaS/' + s.bukti_file" target="_blank" class="inline-flex items-center gap-1.5 text-purple-600 font-bold hover:underline">
                                    <i class="bi bi-file-earmark-pdf"></i> Lihat Bukti
                                </a>
                                <div class="text-[9px] text-slate-400 truncate max-w-[120px]" :title="s.bukti_filename">{{ s.bukti_filename }}</div>
                            </div>
                            <div v-else class="text-red-500 italic font-semibold">Belum upload</div>
                        </td>

                        <!-- Aksi -->
                        <td class="text-right pr-6">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Isi/Edit Button -->
                                <button v-if="!simulasiSettings[activeNoSimulasi]?.is_locked"
                                        class="btn btn-xs rounded-lg p-1.5 flex items-center justify-center text-slate-600 hover:text-purple-600 hover:bg-slate-50"
                                        title="Isi/Edit Pilihan"
                                        @click="openModalSimulasi(s)">
                                    <i class="bi bi-pencil-square fs-6"></i>
                                </button>

                                <!-- Upload Bukti (Only Simulasi 3 & Open & Submitted) -->
                                <button v-if="activeNoSimulasi === 3 && !simulasiSettings[activeNoSimulasi]?.is_locked && s.sudah_isi"
                                        class="btn btn-xs rounded-lg p-1.5 flex items-center justify-center text-slate-600 hover:text-emerald-600 hover:bg-slate-50"
                                        title="Upload Bukti Pendaftaran"
                                        @click="openModalUploadBukti(s)">
                                    <i class="bi bi-cloud-arrow-up-fill fs-6"></i>
                                </button>

                                <!-- Delete Pilihan -->
                                <button v-if="s.sudah_isi && !simulasiSettings[activeNoSimulasi]?.is_locked"
                                        class="btn btn-xs rounded-lg p-1.5 flex items-center justify-center text-slate-600 hover:text-red-600 hover:bg-slate-50"
                                        title="Hapus Pilihan"
                                        @click="deleteSimulasi(s)">
                                    <i class="bi bi-trash-fill fs-6"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Empty State -->
                    <tr v-if="simulasiData.length === 0">
                        <td colspan="8" class="text-center py-12 text-slate-400 italic">
                            <i class="bi bi-people text-3xl block mb-2 text-slate-300"></i>
                            Tidak ada data siswa eligible untuk simulasi di tahun ajaran ini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ MODAL EDIT/ISI PILIHAN SIMULASI ═══ -->
    <div v-if="modalSimulasi.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.45); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-2xl shadow-xl bg-white overflow-hidden">
                <div class="modal-header border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-slate-800 text-sm">Pilihan Jurusan & Kampus</h5>
                            <p class="text-[10px] text-slate-500 mb-0">Simulasi {{ activeNoSimulasi }}</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" @click="modalSimulasi.show = false"></button>
                </div>
                
                <div class="modal-body px-6 py-4 space-y-4">
                    <!-- Student Info Card -->
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 text-xs">
                        <div class="font-bold text-slate-800 mb-0.5">{{ modalSimulasi.siswa.nama_lengkap }}</div>
                        <div class="text-[10px] text-slate-500">
                            Kelas: {{ modalSimulasi.siswa.nama_kelas }} | Peringkat Eligible: <strong>#{{ modalSimulasi.siswa.rank_eligible }} ({{ modalSimulasi.siswa.nama_jurusan }})</strong>
                        </div>
                    </div>

                    <!-- PILIHAN 1 -->
                    <div>
                        <label class="form-label text-slate-600 text-xs font-semibold mb-1">Kampus Pilihan 1 <span class="text-red-500">*</span></label>
                        <select v-model="modalSimulasi.form.kampus_id_1" class="form-select rounded-xl text-xs border-slate-200" @change="onKampusChange(1)">
                            <option value="">-- Pilih Kampus --</option>
                            <option v-for="c in listKampusFlat" :key="c.id" :value="c.id">{{ c.nama_kampus }}</option>
                        </select>
                    </div>

                    <div v-if="modalSimulasi.form.kampus_id_1">
                        <label class="form-label text-slate-600 text-xs font-semibold mb-1">Program Studi Pilihan 1 <span class="text-red-500">*</span></label>
                        <select v-model="modalSimulasi.form.prodi_id_1" class="form-select rounded-xl text-xs border-slate-200">
                            <option value="">-- Pilih Program Studi --</option>
                            <option v-for="p in listProdiByKampus[modalSimulasi.form.kampus_id_1] || []" :key="p.id" :value="p.id">
                                {{ p.fakultas }} — {{ p.program_studi }} ({{ p.jenjang }})
                            </option>
                        </select>
                    </div>

                    <!-- PILIHAN 2 (OPTIONAL) -->
                    <div class="border-t border-slate-100 pt-3">
                        <label class="form-label text-slate-600 text-xs font-semibold mb-1">Kampus Pilihan 2 <small class="text-slate-400 font-normal">(Opsional)</small></label>
                        <select v-model="modalSimulasi.form.kampus_id_2" class="form-select rounded-xl text-xs border-slate-200" @change="onKampusChange(2)">
                            <option value="">-- Pilih Kampus --</option>
                            <option v-for="c in listKampusFlat" :key="c.id" :value="c.id">{{ c.nama_kampus }}</option>
                        </select>
                    </div>

                    <div v-if="modalSimulasi.form.kampus_id_2">
                        <label class="form-label text-slate-600 text-xs font-semibold mb-1">Program Studi Pilihan 2 <small class="text-slate-400 font-normal">(Opsional)</small></label>
                        <select v-model="modalSimulasi.form.prodi_id_2" class="form-select rounded-xl text-xs border-slate-200">
                            <option value="">-- Pilih Program Studi --</option>
                            <option v-for="p in listProdiByKampus[modalSimulasi.form.kampus_id_2] || []" :key="p.id" :value="p.id">
                                {{ p.fakultas }} — {{ p.program_studi }} ({{ p.jenjang }})
                            </option>
                        </select>
                    </div>

                    <!-- Catatan / Note -->
                    <div>
                        <label class="form-label text-slate-600 text-xs font-semibold mb-1">Catatan Siswa</label>
                        <textarea v-model="modalSimulasi.form.catatan_siswa" class="form-control rounded-xl text-xs border-slate-200" rows="2" placeholder="Hasil psikotes, prodi alternatif, atau keterangan tambahan..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-t border-slate-100 px-6 py-4 flex items-center justify-end gap-2 bg-slate-50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-bold px-4" @click="modalSimulasi.show = false">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 flex items-center gap-1.5" :disabled="modalSimulasi.saving" @click="submitSimulasi">
                        <span v-if="modalSimulasi.saving" class="spinner-border spinner-border-sm" role="status"></span>
                        Simpan Pilihan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ MODAL UPLOAD BUKTI (SIMULASI 3 ONLY) ═══ -->
    <div v-if="modalUploadBukti.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.45); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-2xl shadow-xl bg-white overflow-hidden">
                <div class="modal-header border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="bi bi-cloud-arrow-up-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-slate-800 text-sm">Upload Bukti Pemilihan</h5>
                            <p class="text-[10px] text-slate-500 mb-0">Simulasi 3 — Final Verification</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" @click="modalUploadBukti.show = false"></button>
                </div>

                <div class="modal-body px-6 py-4 space-y-4 text-xs">
                    <!-- Target Student -->
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <div class="font-bold text-slate-800 mb-0.5">{{ modalUploadBukti.siswa.nama_lengkap }}</div>
                        <div class="text-[10px] text-slate-500">
                            Pilihan 1: <strong>{{ modalUploadBukti.siswa.kampus_nama_1 }} — {{ modalUploadBukti.siswa.prodi_nama_1 }}</strong>
                        </div>
                    </div>

                    <!-- File input -->
                    <div>
                        <label class="form-label text-slate-600 text-xs font-semibold mb-1">Pilih File Bukti <span class="text-red-500">*</span></label>
                        <input type="file" ref="buktiFileInput" class="form-control rounded-xl text-xs" @change="handleFileUpload" accept=".pdf,.png,.jpg,.jpeg">
                        <p class="text-[10px] text-slate-400 mt-1 mb-0">Format file yang diperbolehkan: **PDF, PNG, JPG/JPEG** (Maksimal **2MB**).</p>
                    </div>

                    <!-- Uploaded Indicator -->
                    <div v-if="modalUploadBukti.siswa.bukti_file" class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl p-3 flex items-start gap-2">
                        <i class="bi bi-info-circle-fill text-base mt-0.5"></i>
                        <div>
                            <div class="font-semibold">Bukti Saat Ini Sudah Ada:</div>
                            <div class="text-[10px] text-emerald-600 truncate max-w-[340px]">{{ modalUploadBukti.siswa.bukti_filename }}</div>
                            <span class="text-[10px] text-slate-400 block mt-0.5">Mengupload file baru akan menggantikan file bukti yang lama.</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-t border-slate-100 px-6 py-4 flex items-center justify-end gap-2 bg-slate-50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-bold px-4" @click="modalUploadBukti.show = false">Batal</button>
                    <button type="button" class="btn btn-sm btn-success rounded-xl font-bold px-4 flex items-center gap-1.5" :disabled="modalUploadBukti.uploading" @click="submitUploadBukti">
                        <span v-if="modalUploadBukti.uploading" class="spinner-border spinner-border-sm" role="status"></span>
                        Mulai Upload
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
