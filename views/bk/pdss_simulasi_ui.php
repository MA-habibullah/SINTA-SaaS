<!-- ═══════════════════════════════════════════════════════════
          TAB SIMULASI PEMILIHAN KAMPUS & PRODI PDSS
     ════════════════════════════════════════════════════════════ -->
<div class="space-y-5">

    <!-- WARNING BANNER IF STEP 4 IS UNLOCKED -->
    <div v-if="!locks[4]?.is_locked" class="bg-amber-50/90 border border-amber-200 rounded-2xl p-4 mb-4 flex items-center gap-3 shadow-xs">
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0 shadow-2xs">
            <i class="bi bi-exclamation-triangle-fill text-lg"></i>
        </div>
        <div>
            <h5 class="font-bold text-xs text-amber-900 mb-0.5">Perhatian BK: Langkah 4 (Finalisasi Eligible) Belum Dikunci</h5>
            <p class="text-[11px] text-amber-700 mb-0">Untuk memastikan validitas kuota dan peserta simulasi SNBP, pastikan data kelayakan dan surat pengunduran diri pada Langkah 4 telah selesai diverifikasi dan dikunci.</p>
        </div>
    </div>

    <!-- SELECTOR TAHUN AJARAN & KONTROL FASE SIMULASI -->
    <div class="card border-0 shadow-sm rounded-2xl bg-white border border-slate-200/80 mb-4">
        <div class="card-body p-4 flex flex-wrap items-center justify-between gap-4">
            <!-- Left Info -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 shadow-2xs border border-indigo-100">
                    <i class="bi bi-mortarboard-fill text-lg"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-800 mb-0.5">Tahun Ajaran Evaluasi</h4>
                    <p class="text-xs text-slate-500 mb-0">Pilih tahun ajaran target untuk menjalankan simulasi pemilihan prodi SNBP.</p>
                </div>
            </div>
            <!-- Right Dropdown -->
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-xs font-semibold text-slate-600">Pilih Tahun Ajaran:</span>
                <select v-model="filterAcademicYear" class="form-select text-xs rounded-xl border-slate-300 bg-white py-2 px-3.5 text-slate-700 font-bold focus:ring-2 focus:ring-indigo-500 cursor-pointer shadow-2xs" style="min-width: 200px;" @change="fetchSimulasi(); fetchSimulasiSettings();">
                    <option value="" disabled>— Pilih Tahun Ajaran —</option>
                    <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                        {{ yr.tahun_ajaran }} <span v-if="parseInt(yr.is_active) === 1">(Aktif)</span>
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- TABS SIMULASI 1, 2, 3 & ACTION CONTROLS -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs mb-4">
        <!-- Selector Pills -->
        <div class="flex bg-slate-100 p-1 rounded-xl gap-1">
            <button v-for="num in [1, 2, 3]" :key="num"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5"
                    :class="activeNoSimulasi === num ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                    @click="activeNoSimulasi = num; fetchSimulasi();">
                <i class="bi" :class="activeNoSimulasi === num ? 'bi-stars text-amber-300' : 'bi-diagram-3'"></i>
                <span>Simulasi {{ num }}</span>
            </button>
        </div>

        <!-- Action Control Buttons for BK/Admin -->
        <div v-if="canWrite" class="flex items-center gap-2 flex-wrap">
            <!-- Status Badge -->
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold shadow-2xs"
                 :class="simulasiSettings[activeNoSimulasi]?.is_locked ? 'bg-rose-50 text-rose-700 border border-rose-200' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200')">
                <i class="bi" :class="simulasiSettings[activeNoSimulasi]?.is_locked ? 'bi-lock-fill text-rose-600' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'bi-check-circle-fill text-emerald-600' : 'bi-dash-circle text-slate-500')"></i>
                <span class="uppercase tracking-wide text-[11px]">
                    {{ simulasiSettings[activeNoSimulasi]?.is_locked ? 'DIKUNCI' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'PENGISIAN DIBUKA' : 'DITUTUP') }}
                </span>
            </div>

            <!-- Controls -->
            <button v-if="!simulasiSettings[activeNoSimulasi]?.is_locked && !simulasiSettings[activeNoSimulasi]?.is_open"
                    class="btn btn-sm btn-success rounded-xl font-bold flex items-center gap-1.5 px-3 py-1.5 shadow-2xs"
                    @click="toggleSimulasiSetting(activeNoSimulasi, 'open')">
                <i class="bi bi-play-fill text-sm"></i> Buka Pengisian
            </button>
            <button v-if="!simulasiSettings[activeNoSimulasi]?.is_locked && simulasiSettings[activeNoSimulasi]?.is_open"
                    class="btn btn-sm btn-warning text-slate-900 rounded-xl font-bold flex items-center gap-1.5 px-3 py-1.5 shadow-2xs"
                    @click="toggleSimulasiSetting(activeNoSimulasi, 'close')">
                <i class="bi bi-pause-fill text-sm"></i> Tutup Pengisian
            </button>
            <button v-if="!simulasiSettings[activeNoSimulasi]?.is_locked"
                    class="btn btn-sm btn-outline-danger rounded-xl font-bold flex items-center gap-1.5 px-3 py-1.5 shadow-2xs"
                    @click="toggleSimulasiSetting(activeNoSimulasi, 'lock')">
                <i class="bi bi-lock-fill text-sm"></i> Kunci Permanen
            </button>
            
            <!-- Export Button (Emerald Style) -->
            <button class="btn btn-sm text-white rounded-xl font-bold flex items-center gap-1.5 px-3.5 py-1.5 shadow-2xs"
                    style="background-color: #059669; border: 1px solid #047857; color: #ffffff !important;"
                    @click="exportSimulasi">
                <i class="bi bi-file-earmark-excel-fill text-sm"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- INFO ALERT FOR PHASE (FORMATTED & RICH) -->
    <div class="alert border rounded-2xl flex items-start gap-3 p-4 mb-4 shadow-2xs"
         :class="simulasiSettings[activeNoSimulasi]?.is_locked ? 'bg-rose-50/80 border-rose-200 text-rose-900' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'bg-emerald-50/80 border-emerald-200 text-emerald-900' : 'bg-amber-50/80 border-amber-200 text-amber-900')">
        <div class="text-xl leading-none mt-0.5">
            <i class="bi" :class="simulasiSettings[activeNoSimulasi]?.is_locked ? 'bi-shield-lock-fill text-rose-600' : (simulasiSettings[activeNoSimulasi]?.is_open ? 'bi-check-circle-fill text-emerald-600' : 'bi-exclamation-triangle-fill text-amber-600')"></i>
        </div>
        <div class="text-xs flex-1">
            <div class="font-bold text-sm mb-1">Informasi Fase Simulasi {{ activeNoSimulasi }}</div>
            <p class="mb-0 leading-relaxed text-slate-700" v-if="simulasiSettings[activeNoSimulasi]?.is_locked">
                Fase Simulasi {{ activeNoSimulasi }} telah <strong>Dikunci Permanen</strong>. Tidak ada perubahan pilihan prodi yang dapat dilakukan baik oleh siswa maupun guru BK.
            </p>
            <p class="mb-0 leading-relaxed text-slate-700" v-else-if="simulasiSettings[activeNoSimulasi]?.is_open">
                Pengisian Simulasi {{ activeNoSimulasi }} sedang <strong>Dibuka</strong>. Siswa yang dinyatakan <em>Eligible</em> dapat menginput atau mengubah pilihan prodi melalui panel siswa, dan Guru BK dapat mendampingi secara langsung di halaman ini.
            </p>
            <p class="mb-0 leading-relaxed text-slate-700" v-else>
                Pengisian Simulasi {{ activeNoSimulasi }} sedang <strong>Ditutup / Belum Dibuka</strong>. Siswa tidak dapat mengisi atau mengubah pilihan prodi secara mandiri dari panel mereka.
            </p>
        </div>
    </div>

    <!-- STATS CARDS (RESPONSIVE 4-COLUMN GRID) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Eligible -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="bg-white rounded-2xl shadow-sm border border-indigo-100 hover:border-indigo-300 p-4 h-100 flex items-center justify-between gap-3 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 shadow-2xs border border-indigo-100">
                        <i class="bi bi-people-fill text-xl"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Total Eligible</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-2xl font-black text-slate-900">{{ simulasiStats.total_eligible }}</span>
                            <span class="text-[11px] font-bold text-indigo-600">Siswa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Sudah Mengisi -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 hover:border-emerald-300 p-4 h-100 flex items-center justify-between gap-3 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0 shadow-2xs border border-emerald-100">
                        <i class="bi bi-check2-circle text-xl"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Sudah Mengisi</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-2xl font-black text-emerald-600">{{ simulasiStats.sudah_isi }}</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800" v-if="simulasiStats.total_eligible > 0">
                                {{ Math.round((simulasiStats.sudah_isi / simulasiStats.total_eligible) * 100) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Belum Mengisi -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 hover:border-amber-300 p-4 h-100 flex items-center justify-between gap-3 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0 shadow-2xs border border-amber-100">
                        <i class="bi bi-clock-history text-xl"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Belum Mengisi</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-2xl font-black text-amber-600">{{ simulasiStats.belum_isi }}</span>
                            <span class="text-[11px] font-bold text-slate-400">Siswa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Terjadi Konflik -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 hover:border-rose-300 p-4 h-100 flex items-center justify-between gap-3 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0 shadow-2xs border border-rose-100">
                        <i class="bi bi-shield-exclamation text-xl"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Terjadi Konflik</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-2xl font-black" :class="simulasiStats.total_konflik > 0 ? 'text-rose-600' : 'text-slate-700'">
                                {{ simulasiStats.total_konflik }}
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.2 rounded" :class="simulasiStats.total_konflik > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600'">
                                {{ simulasiStats.total_konflik > 0 ? 'Konflik' : 'Aman' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 mb-4">
        <div class="row g-3">
            <!-- Search -->
            <div class="col-12 col-md-3">
                <label class="form-label text-slate-600 text-xs font-bold mb-1.5 flex items-center gap-1.5">
                    <i class="bi bi-search text-indigo-600"></i> Cari Siswa:
                </label>
                <div class="relative flex items-center">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="text" v-model="filterSimulasi.search" 
                           class="form-control rounded-xl text-xs border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500 shadow-2xs" 
                           style="padding-left: 2.25rem !important;" 
                           placeholder="Ketik Nama / NISN...">
                </div>
            </div>

            <!-- Major Filter -->
            <div class="col-12 col-md-3">
                <label class="form-label text-slate-600 text-xs font-bold mb-1.5 flex items-center gap-1.5">
                    <i class="bi bi-funnel-fill text-indigo-600"></i> Filter Jurusan:
                </label>
                <select v-model="filterSimulasi.major" class="form-select rounded-xl text-xs border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500 shadow-2xs">
                    <option value="">— Semua Jurusan —</option>
                    <option v-for="mj in uniqueMajors" :key="mj" :value="mj">{{ mj }}</option>
                </select>
            </div>

            <!-- Conflict Status -->
            <div class="col-12 col-md-3">
                <label class="form-label text-slate-600 text-xs font-bold mb-1.5 flex items-center gap-1.5">
                    <i class="bi bi-shield-check text-indigo-600"></i> Status Konflik:
                </label>
                <select v-model="filterSimulasi.status_konflik" class="form-select rounded-xl text-xs border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500 shadow-2xs">
                    <option value="">— Semua Status —</option>
                    <option value="konflik">Terjadi Konflik Prodi</option>
                    <option value="aman">Aman / Tidak Ada Konflik</option>
                </select>
            </div>

            <!-- Fill Status -->
            <div class="col-12 col-md-3">
                <label class="form-label text-slate-600 text-xs font-bold mb-1.5 flex items-center gap-1.5">
                    <i class="bi bi-check2-square text-indigo-600"></i> Status Pengisian:
                </label>
                <select v-model="filterSimulasi.sudah_isi" class="form-select rounded-xl text-xs border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500 shadow-2xs">
                    <option value="">— Semua Siswa —</option>
                    <option value="sudah">Sudah Mengisi Pilihan</option>
                    <option value="belum">Belum Mengisi Pilihan</option>
                </select>
            </div>
        </div>
    </div>

    <!-- MAIN DATA TABLE -->
    <div class="card border-0 shadow-sm rounded-2xl bg-white border border-slate-100 overflow-hidden">
        <div v-if="loadingSimulasi" class="p-8 text-center">
            <div class="spinner-border text-indigo-600 spinner-border-sm" role="status"></div>
            <p class="text-xs text-slate-400 mt-2 mb-0">Memuat data simulasi...</p>
        </div>
        <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-slate-700">
                <thead class="bg-slate-50/80 text-slate-500 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200/80">
                    <tr>
                        <th class="pl-5 py-3.5 text-center" style="width: 70px;">Peringkat</th>
                        <th class="py-3.5">Nama & NISN Siswa</th>
                        <th class="py-3.5" style="width: 140px;">Kelas & Jurusan</th>
                        <th class="py-3.5 text-center" style="width: 100px;">Nilai Rerata</th>
                        <th class="py-3.5" style="min-width: 220px;">Pilihan 1 (Utama)</th>
                        <th class="py-3.5" style="min-width: 200px;">Pilihan 2</th>
                        <th v-if="activeNoSimulasi === 3" class="py-3.5" style="width: 140px;">Bukti Upload</th>
                        <th class="py-3.5 text-right pr-5" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    <tr v-for="s in simulasiData.filter(item => {
                        if (!item.is_eligible) return false;
                        if (filterSimulasi.search && !item.nama_lengkap.toLowerCase().includes(filterSimulasi.search.toLowerCase()) && !item.nisn.includes(filterSimulasi.search)) return false;
                        if (filterSimulasi.major && item.nama_jurusan !== filterSimulasi.major) return false;
                        if (filterSimulasi.status_konflik === 'konflik' && !item.is_konflik_1 && !item.is_konflik_2) return false;
                        if (filterSimulasi.status_konflik === 'aman' && (item.is_konflik_1 || item.is_konflik_2)) return false;
                        if (filterSimulasi.sudah_isi === 'sudah' && !item.sudah_isi) return false;
                        if (filterSimulasi.sudah_isi === 'belum' && item.sudah_isi) return false;
                        return true;
                    })" :key="s.siswa_id" class="hover:bg-slate-50/60 transition-colors">
                        
                        <!-- Peringkat Eligible -->
                        <td class="pl-5 text-center font-bold">
                            <span v-if="s.rank_eligible === 1" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-amber-400 to-amber-500 text-white font-black text-xs shadow-xs" title="Peringkat 1 Jurusan">
                                🥇 1
                            </span>
                            <span v-else-if="s.rank_eligible === 2" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-slate-400 to-slate-500 text-white font-black text-xs shadow-xs" title="Peringkat 2 Jurusan">
                                🥈 2
                            </span>
                            <span v-else-if="s.rank_eligible === 3" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-amber-600 to-amber-700 text-white font-black text-xs shadow-xs" title="Peringkat 3 Jurusan">
                                🥉 3
                            </span>
                            <span v-else class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-100 font-bold text-xs shadow-2xs">
                                #{{ s.rank_eligible }}
                            </span>
                        </td>

                        <!-- Profil Siswa -->
                        <td>
                            <div class="font-bold text-slate-800 text-sm mb-0.5">{{ s.nama_lengkap }}</div>
                            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 font-mono">
                                <span class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 font-semibold">NISN: {{ s.nisn }}</span>
                                <span v-if="s.nis" class="text-slate-400">NIS: {{ s.nis }}</span>
                            </div>
                        </td>

                        <!-- Kelas & Jurusan -->
                        <td>
                            <div class="font-bold text-slate-700 text-xs mb-1">{{ s.nama_kelas }}</div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold"
                                  :class="s.nama_jurusan?.toLowerCase().includes('ipa') ? 'bg-blue-50 text-blue-700 border border-blue-100' : (s.nama_jurusan?.toLowerCase().includes('ips') ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-slate-100 text-slate-700')">
                                {{ s.nama_jurusan }}
                            </span>
                        </td>

                        <!-- Nilai Rerata -->
                        <td class="text-center">
                            <button type="button" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-black shadow-2xs hover:bg-emerald-100 hover:border-emerald-300 hover:scale-105 transition-all cursor-pointer"
                                    title="Klik untuk melihat rincian transkrip nilai rapor semester 1-5"
                                    @click="showAuditModal(s.siswa_id)">
                                <i class="bi bi-file-earmark-spreadsheet-fill text-emerald-600"></i>
                                <span>{{ s.rata_rata }}</span>
                            </button>
                        </td>

                        <!-- Pilihan 1 -->
                        <td>
                            <div v-if="s.kampus_nama_1" class="space-y-1">
                                <div class="font-bold text-indigo-700 text-xs flex items-center gap-1">
                                    <i class="bi bi-building text-indigo-500"></i> {{ s.kampus_nama_1 }}
                                </div>
                                <div class="text-slate-700 font-medium text-[11px]">{{ s.prodi_nama_1 }}</div>
                                
                                <!-- Conflict Warning Alert (Interactive) -->
                                <div v-if="s.is_konflik_1">
                                    <button type="button" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-[10px] text-rose-700 border border-rose-200 font-bold shadow-2xs transition-all hover:scale-105 cursor-pointer"
                                            title="Klik untuk melihat detail & analisis persaingan internal prodi ini"
                                            @click="openModalDetailKonflik(s, 1)">
                                        <i class="bi bi-exclamation-triangle-fill text-rose-600 animate-pulse"></i> 
                                        <span>Konflik Pilihan</span>
                                        <span class="bg-rose-200 text-rose-800 text-[9px] px-1 py-0.2 rounded-full font-extrabold">{{ (s.konflik_detail_1 || []).length || 2 }}</span>
                                    </button>
                                </div>
                                <div v-else>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-emerald-50 text-[9px] text-emerald-700 border border-emerald-100 font-semibold">
                                        <i class="bi bi-check-circle-fill"></i> Pilihan Utama
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-slate-400 italic text-xs flex items-center gap-1 bg-slate-50 px-2.5 py-1.5 rounded-xl border border-dashed border-slate-200 w-fit">
                                <i class="bi bi-dash-circle text-slate-300"></i> Belum mengisi
                            </div>
                        </td>

                        <!-- Pilihan 2 -->
                        <td>
                            <div v-if="s.kampus_nama_2" class="space-y-1">
                                <div class="font-bold text-slate-800 text-xs flex items-center gap-1">
                                    <i class="bi bi-building text-slate-400"></i> {{ s.kampus_nama_2 }}
                                </div>
                                <div class="text-slate-600 font-medium text-[11px]">{{ s.prodi_nama_2 }}</div>
                                
                                <!-- Conflict Warning Alert (Interactive) -->
                                <div v-if="s.is_konflik_2">
                                    <button type="button" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-[10px] text-rose-700 border border-rose-200 font-bold shadow-2xs transition-all hover:scale-105 cursor-pointer"
                                            title="Klik untuk melihat detail & analisis persaingan internal prodi ini"
                                            @click="openModalDetailKonflik(s, 2)">
                                        <i class="bi bi-exclamation-triangle-fill text-rose-600 animate-pulse"></i> 
                                        <span>Konflik Pilihan</span>
                                        <span class="bg-rose-200 text-rose-800 text-[9px] px-1 py-0.2 rounded-full font-extrabold">{{ (s.konflik_detail_2 || []).length || 2 }}</span>
                                    </button>
                                </div>
                                <div v-else>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-50 text-[9px] text-slate-600 border border-slate-200 font-medium">
                                        <i class="bi bi-check2"></i> Pilihan 2
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-slate-300 italic text-[11px]">
                                — Kosong —
                            </div>
                        </td>

                        <!-- Bukti Upload (Sim 3 Only) -->
                        <td v-if="activeNoSimulasi === 3">
                            <div v-if="s.bukti_file">
                                <a :href="'<?= $this->getBaseUrl() ?>/' + s.bukti_file" target="_blank" class="inline-flex items-center gap-1.5 text-indigo-600 font-bold hover:underline">
                                    <i class="bi bi-file-earmark-pdf"></i> Lihat Bukti
                                </a>
                                <div class="text-[9px] text-slate-400 truncate max-w-[120px]" :title="s.bukti_filename">{{ s.bukti_filename }}</div>
                            </div>
                            <div v-else class="text-rose-500 italic font-semibold text-[11px]">Belum upload</div>
                        </td>

                        <!-- Aksi -->
                        <td class="text-right pr-5">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- View Nilai / Rapor Button -->
                                <button type="button"
                                        class="btn btn-sm btn-light border border-slate-200 rounded-xl px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300 flex items-center gap-1 shadow-2xs transition-all"
                                        title="Lihat Rincian Transkrip Nilai Rapor Siswa"
                                        @click="showAuditModal(s.siswa_id)">
                                    <i class="bi bi-eye-fill text-emerald-600"></i>
                                    <span>Nilai</span>
                                </button>

                                <!-- Isi/Edit Button — hanya untuk siswa eligible -->
                                <button v-if="!simulasiSettings[activeNoSimulasi]?.is_locked"
                                        class="btn btn-sm btn-light border border-slate-200 rounded-xl px-2.5 py-1 text-xs font-semibold text-slate-700 hover:text-indigo-600 hover:border-indigo-300 flex items-center gap-1 shadow-2xs transition-all"
                                        title="Isi / Ubah Pilihan Siswa"
                                        @click="openModalSimulasi(s)">
                                    <i class="bi bi-pencil-square text-indigo-500"></i>
                                    <span>Pilih</span>
                                </button>

                                <!-- Upload Bukti (Only Simulasi 3 & Open & Submitted & Eligible) -->
                                <button v-if="activeNoSimulasi === 3 && !simulasiSettings[activeNoSimulasi]?.is_locked && s.sudah_isi"
                                        class="btn btn-sm btn-light border border-slate-200 rounded-xl px-2 py-1 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 shadow-2xs"
                                        title="Upload Bukti Pendaftaran"
                                        @click="openModalUploadBukti(s)">
                                    <i class="bi bi-cloud-arrow-up-fill"></i>
                                </button>

                                <!-- Delete Pilihan — jika sudah isi -->
                                <button v-if="s.sudah_isi && !simulasiSettings[activeNoSimulasi]?.is_locked"
                                        class="btn btn-sm btn-light border border-slate-200 rounded-xl px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 shadow-2xs"
                                        title="Kosongkan Pilihan"
                                        @click="deleteSimulasi(s.siswa_id)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Empty State -->
                    <tr v-if="simulasiData.length === 0">
                        <td :colspan="activeNoSimulasi === 3 ? 8 : 7" class="text-center py-12 text-slate-400">
                            <i class="bi bi-people text-3xl block mb-2 text-slate-300"></i>
                            <div class="font-bold text-slate-600">Tidak ada data siswa eligible untuk simulasi di tahun ajaran ini</div>
                            <p class="text-xs text-slate-400 mt-1">Pastikan evaluasi kesiapan nilai dan kuota ranking pada Langkah 1-4 telah dihitung.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ MODAL EDIT/ISI PILIHAN SIMULASI ═══ -->
    <div v-if="modalSimulasi.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.45); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-2xl shadow-xl bg-white" style="overflow: visible;">
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
                    <div class="space-y-1 relative">
                        <label class="form-label text-slate-600 text-xs font-semibold mb-0 flex items-center justify-between">
                            <span>Kampus Pilihan 1 <span class="text-red-500">*</span></span>
                        </label>
                        
                        <!-- Custom Searchable Dropdown Button -->
                        <div class="relative">
                            <button type="button" 
                                    @click="modalSimulasi.showDropdown1 = !modalSimulasi.showDropdown1; modalSimulasi.showDropdown2 = false;"
                                    class="form-select rounded-xl text-xs border-slate-200 w-full text-left bg-white flex items-center justify-between py-2 px-3 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <span :class="modalSimulasi.form.kampus_id_1 ? 'text-slate-800 font-medium' : 'text-slate-400'">
                                    {{ getKampusName(modalSimulasi.form.kampus_id_1) || '-- Pilih Kampus --' }}
                                </span>
                            </button>

                            <!-- Dropdown List Container -->
                            <div v-if="modalSimulasi.showDropdown1" 
                                 class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg p-2.5 max-h-60 overflow-hidden flex flex-col"
                                 style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);">
                                <!-- Input Pencarian di dalam Dropdown -->
                                <div class="relative mb-2">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                                        <i class="bi bi-search text-[10px]"></i>
                                    </span>
                                    <input type="text" v-model="modalSimulasi.searchKampus1" placeholder="Cari kampus..." 
                                           class="form-control pl-7 pr-3 py-1.5 rounded-lg text-xs border-slate-200 focus:ring-purple-500 focus:border-purple-500 w-full"
                                           @click.stop>
                                </div>
                                <!-- Scrollable Opsi Kampus -->
                                <div class="overflow-y-auto flex-1 space-y-0.5 max-h-40">
                                    <div @click="selectKampus(1, null)"
                                         class="p-2 hover:bg-slate-50 hover:text-purple-600 rounded-lg cursor-pointer text-xs text-slate-500 italic">
                                        -- Kosongkan Pilihan --
                                    </div>
                                    <div v-for="c in filteredKampus1" :key="c.id" @click="selectKampus(1, c)"
                                         class="p-2 hover:bg-purple-50 hover:text-purple-600 rounded-lg cursor-pointer text-xs text-slate-700 font-medium flex items-center justify-between"
                                         :class="modalSimulasi.form.kampus_id_1 === c.id ? 'bg-purple-50 text-purple-600 font-bold' : ''">
                                        <span>{{ c.nama_kampus }}</span>
                                        <i v-if="modalSimulasi.form.kampus_id_1 === c.id" class="bi bi-check text-purple-600 text-sm"></i>
                                    </div>
                                    <div v-if="filteredKampus1.length === 0" class="p-3 text-center text-slate-400 italic text-xs">
                                        Kampus tidak ditemukan
                                    </div>
                                </div>
                            </div>
                        </div>
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

                    <!-- PILIHAN 2 (Hanya tampil jika Kampus 1 & Prodi 1 sudah diisi) -->
                    <div v-if="modalSimulasi.form.kampus_id_1 && modalSimulasi.form.prodi_id_1" class="border-t border-slate-100 pt-3 space-y-1 relative">
                        <label class="form-label text-slate-600 text-xs font-semibold mb-0 flex items-center justify-between">
                            <span>Kampus Pilihan 2 <small class="text-slate-400 font-normal">(Opsional)</small></span>
                        </label>
                        
                        <!-- Custom Searchable Dropdown Button -->
                        <div class="relative">
                            <button type="button" 
                                    @click="modalSimulasi.showDropdown2 = !modalSimulasi.showDropdown2; modalSimulasi.showDropdown1 = false;"
                                    class="form-select rounded-xl text-xs border-slate-200 w-full text-left bg-white flex items-center justify-between py-2 px-3 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <span :class="modalSimulasi.form.kampus_id_2 ? 'text-slate-800 font-medium' : 'text-slate-400'">
                                    {{ getKampusName(modalSimulasi.form.kampus_id_2) || '-- Pilih Kampus --' }}
                                </span>
                            </button>

                            <!-- Dropdown List Container -->
                            <div v-if="modalSimulasi.showDropdown2" 
                                 class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg p-2.5 max-h-60 overflow-hidden flex flex-col"
                                 style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);">
                                <!-- Input Pencarian di dalam Dropdown -->
                                <div class="relative mb-2">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                                        <i class="bi bi-search text-[10px]"></i>
                                    </span>
                                    <input type="text" v-model="modalSimulasi.searchKampus2" placeholder="Cari kampus..." 
                                           class="form-control pl-7 pr-3 py-1.5 rounded-lg text-xs border-slate-200 focus:ring-purple-500 focus:border-purple-500 w-full"
                                           @click.stop>
                                </div>
                                <!-- Scrollable Opsi Kampus -->
                                <div class="overflow-y-auto flex-1 space-y-0.5 max-h-40">
                                    <div @click="selectKampus(2, null)"
                                         class="p-2 hover:bg-slate-50 hover:text-purple-600 rounded-lg cursor-pointer text-xs text-slate-500 italic">
                                        -- Kosongkan Pilihan --
                                    </div>
                                    <div v-for="c in filteredKampus2" :key="c.id" @click="selectKampus(2, c)"
                                         class="p-2 hover:bg-purple-50 hover:text-purple-600 rounded-lg cursor-pointer text-xs text-slate-700 font-medium flex items-center justify-between"
                                         :class="modalSimulasi.form.kampus_id_2 === c.id ? 'bg-purple-50 text-purple-600 font-bold' : ''">
                                        <span>{{ c.nama_kampus }}</span>
                                        <i v-if="modalSimulasi.form.kampus_id_2 === c.id" class="bi bi-check text-purple-600 text-sm"></i>
                                    </div>
                                    <div v-if="filteredKampus2.length === 0" class="p-3 text-center text-slate-400 italic text-xs">
                                        Kampus tidak ditemukan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="modalSimulasi.form.kampus_id_1 && modalSimulasi.form.prodi_id_1 && modalSimulasi.form.kampus_id_2">
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

                <div class="modal-footer border-t border-slate-100 px-6 py-4 flex items-center justify-end gap-2 bg-slate-50 rounded-b-2xl">
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

    <!-- ═══ MODAL DETAIL ANALISIS KONFLIK PILIHAN SNBP ═══ -->
    <div v-if="modalDetailKonflik && modalDetailKonflik.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.55); z-index: 1060; backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-3xl shadow-2xl bg-white overflow-hidden">
                <!-- Header -->
                <div class="modal-header border-b border-rose-100 px-6 py-4 flex items-center justify-between bg-gradient-to-r from-rose-50 via-white to-orange-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0 shadow-2xs border border-rose-200">
                            <i class="bi bi-shield-exclamation text-xl"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-black text-slate-800 text-sm md:text-base flex items-center gap-2">
                                Detail & Analisis Konflik Pilihan SNBP
                                <span class="badge bg-rose-100 text-rose-700 text-[10px] font-bold px-2 py-0.5 rounded-lg border border-rose-200 uppercase">
                                    {{ (modalDetailKonflik.conflicts || []).length }} Siswa Bersaing
                                </span>
                            </h5>
                            <p class="text-[11px] text-slate-500 mb-0">
                                Evaluasi persaingan internal sekolah pada universitas & program studi tujuan
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" @click="modalDetailKonflik.show = false"></button>
                </div>

                <!-- Body -->
                <div class="modal-body px-6 py-5 space-y-4 text-xs">
                    <!-- Target Prodi Info Box -->
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 shadow-2xs">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Kampus & Program Studi Bentrok:</span>
                            <div class="font-black text-indigo-700 text-sm flex items-center gap-1.5">
                                <i class="bi bi-building"></i> {{ modalDetailKonflik.kampusNama }}
                            </div>
                            <div class="text-slate-800 font-bold text-xs flex items-center gap-1.5">
                                <i class="bi bi-mortarboard-fill text-indigo-500"></i> {{ modalDetailKonflik.prodiNama }}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-semibold text-slate-500 block">Slot Pemilihan Siswa Terpilih:</span>
                            <span class="badge bg-indigo-50 text-indigo-700 font-bold px-2.5 py-1 rounded-xl border border-indigo-100 text-xs mt-0.5">
                                Pilihan {{ modalDetailKonflik.slot }}
                            </span>
                        </div>
                    </div>

                    <!-- Explanatory Alert Banner -->
                    <div class="bg-indigo-50/80 border border-indigo-200 text-indigo-900 rounded-2xl p-3.5 flex items-start gap-3">
                        <i class="bi bi-info-circle-fill text-indigo-600 text-lg flex-shrink-0 mt-0.5"></i>
                        <div class="text-[11px] leading-relaxed">
                            <strong class="font-bold text-indigo-950">Aturan Prioritas Seleksi SNBP:</strong>
                            <p class="mb-1 text-indigo-900">
                                1. <strong>Pilihan 1 adalah Prioritas Utama PTN</strong>: Universitas memproses seluruh pendaftar yang memilih program studi pada <strong>Pilihan 1</strong> terlebih dahulu.
                            </p>
                            <p class="mb-0 text-indigo-800">
                                2. <strong>Pilihan 2 sebagai Cadangan</strong>: Pendaftar Pilihan 2 hanya dipertimbangkan jika kuota seleksi Pilihan 1 masih tersisa. Di antara sesama pilihan, siswa dengan <strong>nilai rapor tertinggi</strong> memiliki prioritas penerimaan.
                            </p>
                        </div>
                    </div>

                    <!-- Table of Conflicting Students -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-slate-700 text-xs uppercase tracking-wider">
                                Urutan Prioritas Siswa Bersaing di Sekolah Ini:
                            </span>
                            <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                                Disortir: Pilihan 1 &gt; Pilihan 2, lalu Rerata Tertinggi
                            </span>
                        </div>

                        <div class="table-responsive rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                            <table class="table table-hover align-middle mb-0 text-slate-700">
                                <thead class="bg-slate-50 text-slate-500 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="py-2.5 px-3 text-center" style="width: 50px;">Prioritas</th>
                                        <th class="py-2.5">Nama Siswa & NISN</th>
                                        <th class="py-2.5" style="width: 120px;">Kelas & Jurusan</th>
                                        <th class="py-2.5 text-center" style="width: 90px;">Rerata Rapor</th>
                                        <th class="py-2.5 text-center" style="width: 95px;">Pilihan Ke</th>
                                        <th class="py-2.5 text-center" style="width: 170px;">Status Peluang SNBP</th>
                                    </tr>
                                </thead>
                                <tbody class="text-xs divide-y divide-slate-100">
                                    <tr v-for="(cs, cidx) in (modalDetailKonflik.conflicts || [])" :key="cs.siswa_id"
                                        :class="cs.siswa_id === modalDetailKonflik.siswa?.siswa_id ? 'bg-indigo-50/50 font-semibold' : ''">
                                        
                                        <!-- Urutan Prioritas -->
                                        <td class="py-2.5 px-3 text-center font-bold">
                                            <span v-if="cidx === 0" class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-600 text-white font-black text-xs shadow-2xs" title="Prioritas #1">
                                                🥇 1
                                            </span>
                                            <span v-else-if="cidx === 1" class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-slate-400 text-white font-black text-xs shadow-2xs" title="Prioritas #2">
                                                🥈 2
                                            </span>
                                            <span v-else-if="cidx === 2" class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-amber-600 text-white font-black text-xs shadow-2xs" title="Prioritas #3">
                                                🥉 3
                                            </span>
                                            <span v-else class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-slate-200 text-slate-700 font-bold text-xs">
                                                {{ cidx + 1 }}
                                            </span>
                                        </td>

                                        <!-- Profil Siswa -->
                                        <td class="py-2.5">
                                            <div class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                                {{ cs.nama_lengkap }}
                                                <span v-if="cs.siswa_id === modalDetailKonflik.siswa?.siswa_id" class="badge bg-indigo-600 text-white text-[9px] px-1.5 py-0.2 rounded font-bold">
                                                    Siswa Ini
                                                </span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono">NISN: {{ cs.nisn }}</div>
                                        </td>

                                        <!-- Kelas & Jurusan -->
                                        <td class="py-2.5">
                                            <div class="text-xs font-semibold text-slate-700">{{ cs.nama_kelas }}</div>
                                            <div class="text-[10px] text-slate-500">{{ cs.nama_jurusan }}</div>
                                        </td>

                                        <!-- Rerata Rapor -->
                                        <td class="py-2.5 text-center">
                                            <span class="inline-block font-mono font-black text-xs px-2 py-0.5 rounded-lg"
                                                  :class="cidx === 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'">
                                                {{ cs.rata_rata }}
                                            </span>
                                        </td>

                                        <!-- Pilihan Ke -->
                                        <td class="py-2.5 text-center">
                                            <span class="badge rounded-lg font-bold text-[10px]"
                                                  :class="cs.no_pilihan === 1 ? 'bg-indigo-100 text-indigo-800 border border-indigo-200 font-black' : 'bg-slate-100 text-slate-600 border border-slate-200'">
                                                {{ cs.no_pilihan === 1 ? '⭐ Pilihan 1' : 'Pilihan 2' }}
                                            </span>
                                        </td>

                                        <!-- Status Peluang -->
                                        <td class="py-2.5 text-center">
                                            <!-- Pilihan 1 & Rank 1 -->
                                            <span v-if="cidx === 0 && cs.no_pilihan === 1" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-1 rounded-xl flex items-center justify-center gap-1">
                                                <i class="bi bi-trophy-fill text-emerald-600"></i> Prioritas Utama (Pil 1)
                                            </span>
                                            <!-- Pilihan 1 tapi kalah nilai dari siswa Pilihan 1 lainnya -->
                                            <span v-else-if="cs.no_pilihan === 1" class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold px-2 py-1 rounded-xl flex items-center justify-center gap-1">
                                                <i class="bi bi-shield-x text-rose-600"></i> Kalah Nilai (Pil 1)
                                            </span>
                                            <!-- Pilihan 2 tapi rank 1 prodi (karena tidak ada yang memilih Pilihan 1) -->
                                            <span v-else-if="cidx === 0" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-1 rounded-xl flex items-center justify-center gap-1">
                                                <i class="bi bi-check-circle-fill text-emerald-600"></i> Prioritas (Pil 2)
                                            </span>
                                            <!-- Pilihan 2 umum -->
                                            <span v-else class="badge bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-bold px-2 py-1 rounded-xl flex items-center justify-center gap-1">
                                                <i class="bi bi-hourglass-split text-amber-600"></i> Cadangan (Pil 2)
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Strategic Advice Box for BK -->
                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 text-indigo-950 space-y-1.5 shadow-2xs">
                        <div class="font-black text-xs text-indigo-950 flex items-center gap-1.5">
                            <i class="bi bi-lightbulb-fill text-indigo-600"></i> Rekomendasi Strategis Guru BK:
                        </div>
                        <ul class="text-[11px] text-indigo-800 list-disc list-inside space-y-1 mb-0">
                            <li>
                                <strong>Siswa Prioritas #1 ({{ (modalDetailKonflik.conflicts || [])[0]?.nama_lengkap }}):</strong> Memilih prodi ini pada <strong>Pilihan {{ (modalDetailKonflik.conflicts || [])[0]?.no_pilihan }}</strong> dengan nilai rerata ({{ (modalDetailKonflik.conflicts || [])[0]?.rata_rata }}). Direkomendasikan mempertahankan pilihan ini.
                            </li>
                            <li v-if="(modalDetailKonflik.conflicts || []).length > 1">
                                <strong>Siswa Pilihan 2 / Kalah Saing:</strong> Mengingat PTN mengutamakan pendaftar Pilihan 1, siswa yang menempatkan prodi ini pada <em>Pilihan 2</em> berisiko tinggi tergeser. Disarankan berkonsultasi untuk memindahkan ke prodi alternatif agar peluang kelulusan SNBP tetap maksimal.
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer border-t border-slate-100 px-6 py-4 flex items-center justify-between bg-slate-50">
                    <div class="text-[11px] text-slate-400">
                        <i class="bi bi-info-circle"></i> Gunakan tombol "Pilih" di tabel untuk mengubah prodi alternatif siswa.
                    </div>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4" @click="modalDetailKonflik.show = false">
                        Tutup Analisis
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
