<div v-if="userRole === 'super_admin' && !currentTenantId" class="bg-amber-50 border border-amber-100 rounded-2xl p-8 text-center shadow-sm">
    <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
        <i class="bi bi-funnel-fill text-2xl"></i>
    </div>
    <h4 class="font-bold text-slate-800 text-base">Pilih Sekolah Terlebih Dahulu</h4>
    <p class="text-slate-500 text-xs mt-1 max-w-sm mx-auto">Silakan pilih sekolah pada filter di bagian atas halaman untuk menampilkan data.</p>
</div>
<template v-else>
    <!-- TAB: MASTER KAMPUS -->
    <div v-show="activeTab === 'master_kampus'">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-wrap items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-building-lock text-lg"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-800">Master Data Kampus</h4>
                    <p class="text-xs text-slate-500">Kelola daftar kampus beserta fakultas dan program studi.</p>
                </div>
            </div>
            <div v-if="canWrite" class="flex gap-2">
                <button class="btn btn-outline-success border rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1.5 hover:bg-emerald-50" @click="modalImportExcel.show = true">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Import Excel
                </button>
                <button class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1.5 shadow-sm" @click="openKampusModal()">
                    <i class="bi bi-plus-lg"></i> Tambah Kampus
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs text-slate-400 font-semibold uppercase">
                            <th class="ps-6 py-3">Nama Kampus</th>
                            <th class="py-3 text-center">Jenis</th>
                            <th class="py-3">Kota</th>
                            <th class="py-3 text-center">Total Prodi</th>
                            <th class="py-3 text-end pe-6">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loadingKampus"><td colspan="5" class="text-center py-6 text-slate-400 text-xs"><div class="spinner-border spinner-border-sm me-2"></div>Memuat...</td></tr>
                        <tr v-else-if="listKampus.length === 0"><td colspan="5" class="text-center py-6 text-slate-400 text-xs">Belum ada data kampus.</td></tr>
                        <tr v-else v-for="k in listKampus" :key="k.id" class="text-sm border-b border-slate-100 hover:bg-slate-50">
                            <td class="ps-6 py-3 font-bold text-slate-800">
                                {{ k.nama_kampus }}<br>
                                <span class="text-xs text-slate-400 font-normal">{{ k.alamat_kampus || '-' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge text-xs" :class="{ 'bg-blue-100 text-blue-700': k.jenis_kampus === 'Negeri', 'bg-indigo-100 text-indigo-700': k.jenis_kampus === 'Swasta', 'bg-amber-100 text-amber-700': k.jenis_kampus === 'Kedinasan' }">
                                    {{ k.jenis_kampus }}
                                </span>
                            </td>
                            <td class="text-slate-600">{{ k.kota_kampus || '-' }}</td>
                            <td class="text-center font-bold text-blue-600 cursor-pointer hover:underline" @click="manageProdi(k)">
                                {{ k.total_prodi }} Prodi <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </td>
                            <td class="text-end pe-6">
                                <button v-if="canWrite" class="btn btn-sm btn-light border px-2 py-1 text-slate-600 rounded-lg text-xs me-1" @click="openKampusModal(k)"><i class="bi bi-pencil"></i></button>
                                <button v-if="canWrite" class="btn btn-sm btn-light border px-2 py-1 text-rose-600 rounded-lg text-xs" @click="deleteKampus(k.id)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: MASTER JALUR MASUK -->
    <div v-show="activeTab === 'master_jalur'">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-wrap items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-signpost-split-fill text-lg"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-800">Master Jalur Masuk</h4>
                    <p class="text-xs text-slate-500">Kelola daftar jalur masuk universitas secara dinamis.</p>
                </div>
            </div>
            <div v-if="canWrite">
                <button class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1.5 shadow-sm" @click="openJalurModal()">
                    <i class="bi bi-plus-lg"></i> Tambah Jalur
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs text-slate-400 font-semibold uppercase">
                            <th class="ps-6 py-3">Nama Jalur</th>
                            <th class="py-3">Kategori</th>
                            <th class="py-3 text-end pe-6">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loadingJalur"><td colspan="3" class="text-center py-6 text-slate-400 text-xs"><div class="spinner-border spinner-border-sm me-2"></div>Memuat...</td></tr>
                        <tr v-else-if="listJalur.length === 0"><td colspan="3" class="text-center py-6 text-slate-400 text-xs">Belum ada data jalur masuk.</td></tr>
                        <tr v-else v-for="j in listJalur" :key="j.id" class="text-sm border-b border-slate-100 hover:bg-slate-50">
                            <td class="ps-6 py-3 font-bold text-slate-800">{{ j.nama_jalur }}</td>
                            <td class="text-slate-600"><span class="badge bg-slate-200 text-slate-700">{{ j.kategori }}</span></td>
                            <td class="text-end pe-6">
                                <button v-if="canWrite" class="btn btn-sm btn-light border px-2 py-1 text-slate-600 rounded-lg text-xs me-1" @click="openJalurModal(j)"><i class="bi bi-pencil"></i></button>
                                <button v-if="canWrite" class="btn btn-sm btn-light border px-2 py-1 text-rose-600 rounded-lg text-xs" @click="deleteJalur(j.id)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL KAMPUS -->
    <Teleport to="body">
    <div v-if="modalMstKampus.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                    <i class="bi" :class="modalMstKampus.form.id ? 'bi-pencil-square text-indigo-500' : 'bi-plus-circle text-blue-500'"></i>
                    {{ modalMstKampus.form.id ? 'Edit Kampus' : 'Tambah Kampus' }}
                </h3>
                <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalMstKampus.show = false">&times;</button>
            </div>
            <form @submit.prevent="saveKampus">
                <div class="p-5 space-y-3 text-left">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Kampus <span class="text-rose-500">*</span></label>
                        <input type="text" v-model="modalMstKampus.form.nama_kampus" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Kota</label>
                        <input type="text" v-model="modalMstKampus.form.kota_kampus" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Alamat</label>
                        <textarea v-model="modalMstKampus.form.alamat_kampus" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis <span class="text-rose-500">*</span></label>
                        <select v-model="modalMstKampus.form.jenis_kampus" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                            <option value="Negeri">Negeri</option>
                            <option value="Swasta">Swasta</option>
                            <option value="Kedinasan">Kedinasan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50">
                    <button type="button" class="btn btn-light rounded-xl px-4 py-2 text-xs font-semibold" @click="modalMstKampus.show = false">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    </Teleport>

    <!-- MODAL PRODI & RIWAYAT -->
    <Teleport to="body">
    <div v-if="modalProdi.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden animate-fade-in flex flex-col max-h-[90vh]">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                    <i class="bi bi-diagram-3 text-blue-500"></i>
                    Kelola Program Studi: {{ modalProdi.kampus?.nama_kampus }}
                </h3>
                <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalProdi.show = false">&times;</button>
            </div>
            <div class="p-5 flex-1 overflow-y-auto flex flex-col md:flex-row gap-6">
                <!-- FORM PRODI BARU -->
                <div class="w-full md:w-1/3 bg-slate-50 rounded-xl p-4 border border-slate-100 h-fit">
                    <h4 class="font-bold text-sm text-slate-700 mb-4">{{ modalProdi.form.id ? 'Edit Prodi' : 'Tambah Prodi Baru' }}</h4>
                    <form @submit.prevent="saveProdi">
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kode Prodi <span class="text-rose-500">*</span></label>
                                    <input type="text" v-model="modalProdi.form.kode_prodi" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Fakultas</label>
                                    <input type="text" v-model="modalProdi.form.fakultas" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Program Studi <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="modalProdi.form.program_studi" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Jenjang <span class="text-rose-500">*</span></label>
                                    <select v-model="modalProdi.form.jenjang" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                        <option value="D1">D1</option><option value="D2">D2</option><option value="D3">D3</option>
                                        <option value="D4">D4</option><option value="S1">S1</option><option value="S2">S2</option>
                                        <option value="S3">S3</option><option value="Profesi">Profesi</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Portofolio</label>
                                    <input type="text" v-model="modalProdi.form.jenis_portofolio" placeholder="ex: Olahraga, Tidak Ada" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                </div>
                            </div>
                            <div class="pt-2">
                                <button type="button" v-if="modalProdi.form.id" @click="resetFormProdi" class="btn btn-sm btn-light border text-xs w-full mb-2">Batal Edit</button>
                                <button type="submit" class="btn btn-sm btn-primary w-full text-xs font-semibold">Simpan Prodi</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- DAFTAR PRODI -->
                <div class="w-full md:w-2/3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-400 font-semibold uppercase">
                                    <th>Program Studi</th>
                                    <th>Fakultas</th>
                                    <th>Portofolio</th>
                                    <th>Jenjang</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loadingProdi"><td colspan="5" class="text-center py-4">Memuat...</td></tr>
                                <tr v-else-if="listProdi.length === 0"><td colspan="5" class="text-center py-4 text-slate-500">Belum ada prodi.</td></tr>
                                <template v-for="p in listProdi" :key="p.id">
                                    <tr class="border-b border-slate-100">
                                        <td class="font-bold text-slate-800">
                                            <div v-if="p.kode_prodi" class="text-[10px] text-blue-600 mb-0.5">{{ p.kode_prodi }}</div>
                                            {{ p.program_studi }}
                                            <div class="mt-1">
                                                <button class="text-blue-500 hover:underline bg-transparent border-0 p-0 text-[10px]" @click="manageRiwayatProdi(p)">
                                                    <i class="bi bi-clock-history"></i> Riwayat Keketatan
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-slate-600">{{ p.fakultas || '-' }}</td>
                                        <td class="text-slate-600">{{ p.jenis_portofolio || 'Tidak Ada' }}</td>
                                        <td><span class="badge bg-slate-200 text-slate-700">{{ p.jenjang }}</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light border px-2 py-1 text-slate-600 rounded me-1" @click="editProdi(p)"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light border px-2 py-1 text-rose-600 rounded" @click="deleteProdi(p.id)"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <!-- EXPANDED RIWAYAT KEKETATAN -->
                                    <tr v-if="modalProdi.expandedProdiId === p.id" class="bg-slate-50">
                                        <td colspan="5" class="p-4 border-b border-slate-200">
                                            <div class="bg-white rounded-lg border p-3 shadow-sm">
                                                <h5 class="text-xs font-bold text-slate-800 mb-3 flex items-center justify-between">
                                                    Riwayat Keketatan: {{ p.program_studi }}
                                                    <button class="text-slate-400 hover:text-slate-700 text-lg leading-none bg-transparent border-0 p-0" @click="modalProdi.expandedProdiId = null">&times;</button>
                                                </h5>
                                                <!-- Form Tambah Riwayat -->
                                                <form @submit.prevent="saveRiwayat" class="flex flex-wrap gap-2 mb-3 bg-slate-50 p-2 rounded border">
                                                    <input type="number" v-model="formRiwayat.tahun" placeholder="Tahun (ex: 2024)" required min="2000" class="rounded border border-slate-200 px-2 py-1 text-xs w-24">
                                                    <input type="number" v-model="formRiwayat.daya_tampung" placeholder="Daya Tampung" required min="0" class="rounded border border-slate-200 px-2 py-1 text-xs w-28">
                                                    <input type="number" v-model="formRiwayat.jumlah_pendaftar" placeholder="Pendaftar" required min="0" class="rounded border border-slate-200 px-2 py-1 text-xs w-28">
                                                    <button type="submit" class="btn btn-primary btn-sm text-[10px] px-2 py-1 rounded">Simpan Riwayat</button>
                                                </form>
                                                <table class="table table-sm text-[11px] mb-0">
                                                    <thead><tr><th>Tahun</th><th>Daya Tampung</th><th>Pendaftar</th><th class="text-end">Hapus</th></tr></thead>
                                                    <tbody>
                                                        <tr v-if="listRiwayat.length === 0"><td colspan="4" class="text-center py-2 text-slate-500">Tidak ada riwayat.</td></tr>
                                                        <tr v-for="r in listRiwayat" :key="r.id">
                                                            <td class="font-bold">{{ r.tahun }}</td>
                                                            <td>{{ r.daya_tampung }}</td>
                                                            <td>{{ r.jumlah_pendaftar }}</td>
                                                            <td class="text-end"><button class="text-rose-500 hover:text-rose-700 bg-transparent border-0 p-0" @click="deleteRiwayat(r.id)"><i class="bi bi-trash"></i></button></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </Teleport>

    <!-- MODAL JALUR MASUK -->
    <Teleport to="body">
    <div v-if="modalMstJalur.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                    <i class="bi" :class="modalMstJalur.form.id ? 'bi-pencil-square text-indigo-500' : 'bi-plus-circle text-blue-500'"></i>
                    {{ modalMstJalur.form.id ? 'Edit Jalur' : 'Tambah Jalur' }}
                </h3>
                <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalMstJalur.show = false">&times;</button>
            </div>
            <form @submit.prevent="saveJalur">
                <div class="p-5 space-y-3 text-left">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Jalur (ex: SNBP) <span class="text-rose-500">*</span></label>
                        <input type="text" v-model="modalMstJalur.form.nama_jalur" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        <select v-model="modalMstJalur.form.kategori" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                            <option value="SNBP">SNBP</option>
                            <option value="SNBT">SNBT</option>
                            <option value="Mandiri">Mandiri</option>
                            <option value="Kedinasan">Kedinasan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50">
                    <button type="button" class="btn btn-light rounded-xl px-4 py-2 text-xs font-semibold" @click="modalMstJalur.show = false">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    </Teleport>

    <!-- MODAL IMPORT EXCEL -->
    <Teleport to="body">
    <div v-if="modalImportExcel.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet text-emerald-500"></i> Import Data dari Excel
                </h3>
                <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalImportExcel.show = false">&times;</button>
            </div>
            <div class="p-5 space-y-4 text-left">
                <p class="text-xs text-slate-600">
                    Anda dapat memasukkan banyak data kampus, program studi, dan riwayat keketatan sekaligus menggunakan file Excel. Pastikan format kolom sesuai dengan template standar.
                </p>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-bold text-blue-800">Template Standar</span>
                        <span class="block text-[10px] text-blue-600">Unduh format Excel (.xlsx)</span>
                    </div>
                    <a :href="`${baseUrl}/api/v1/kampus/template`" target="_blank" class="btn btn-sm btn-light border text-xs text-blue-600 px-3 py-1.5 rounded-lg flex items-center gap-1 hover:bg-white hover:text-blue-700 font-semibold shadow-sm">
                        <i class="bi bi-download"></i> Unduh
                    </a>
                </div>
                
                <form @submit.prevent="importExcelData">
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Upload File Excel (Max 5MB) <span class="text-rose-500">*</span></label>
                        <input type="file" ref="excelFileInput" accept=".xlsx, .xls" required class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl">
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-light border rounded-xl px-4 py-2 text-xs font-semibold text-slate-600" @click="modalImportExcel.show = false">Batal</button>
                        <button type="submit" class="btn btn-success rounded-xl px-4 py-2 text-xs font-semibold text-white flex items-center gap-1.5 shadow-sm" :disabled="importingExcel">
                            <i class="bi" :class="importingExcel ? 'bi-hourglass-split' : 'bi-cloud-upload'"></i>
                            {{ importingExcel ? 'Memproses...' : 'Mulai Import' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </Teleport>

</template>
