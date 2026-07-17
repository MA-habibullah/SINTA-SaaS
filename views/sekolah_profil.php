<?php
/**
 * View: Profil / Identitas Sekolah (Tenant Profile)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */
?>



<!-- Custom Styles for Premium UI/UX -->
<style>
    /* Custom font style and transition smoothing */
    .profile-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .profile-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px -8px rgba(37, 99, 235, 0.12);
    }
    .input-premium {
        transition: all 0.2s ease-in-out;
    }
    .input-premium:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12) !important;
        outline: none !important;
    }
    .dropzone-premium {
        transition: all 0.2s dashed;
    }
    .dropzone-premium:hover, .dropzone-premium.dragover {
        border-color: #2563eb !important;
        background-color: #eff6ff !important;
    }
    /* Hide scrollbar for tabs */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<div id="schoolProfileApp" v-cloak class="font-sans antialiased text-slate-800">

    <!-- Super Admin Tenant Filter Panel -->
    <?php if ($user_role === 'super_admin' && !empty($tenantsList)): ?>
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                <i class="bi bi-funnel-fill text-lg"></i>
            </div>
            <div>
                <h5 class="text-sm font-bold text-slate-800 mb-0.5">Filter Data Sekolah</h5>
                <p class="text-xs text-slate-500 mb-0">Sebagai Super Admin, Anda dapat memantau dan mengedit profil setiap sekolah terdaftar.</p>
            </div>
        </div>
        
        <div class="w-full sm:w-auto min-w-[280px]">
            <label for="sa-filter-sekolah" class="visually-hidden">Filter Sekolah</label>
            <select id="sa-filter-sekolah" name="tenant_id" @change="changeTenant($event)" class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                <?php foreach ($tenantsList as $t): ?>
                    <option value="<?= htmlspecialchars($t['id']) ?>" <?= $t['id'] === $tenant['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nama_sekolah']) ?> (NPSN: <?= htmlspecialchars($t['npsn']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header Section with Modern Blue Gradient Banner -->
    <div class="relative bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 rounded-3xl p-6 md:p-8 mb-8 text-white shadow-lg overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-5 rounded-full blur-xl"></div>
        <div class="absolute -left-10 -bottom-10 w-60 h-60 bg-blue-400 opacity-10 rounded-full blur-2xl"></div>
        
        <div class="relative flex flex-col md:flex-row items-center gap-6 justify-between">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 shadow-inner">
                    <i class="bi bi-bank2 text-3xl md:text-4xl text-white"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h2 class="text-xl md:text-2xl font-bold tracking-tight text-white mb-0">{{ tenant.nama_sekolah }}</h2>
                        <span class="bg-emerald-500/90 text-white text-xs font-semibold px-2.5 py-0.5 rounded-full shadow-sm border border-emerald-400/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                            Active
                        </span>
                    </div>
                    <p class="text-white/80 text-sm mb-0 flex items-center gap-3">
                        <span><strong class="text-white">NPSN:</strong> {{ tenant.npsn }}</span>
                        <span class="text-white/40">|</span>
                        <span><strong class="text-white">Kurikulum:</strong> {{ tenant.kurikulum }}</span>
                    </p>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="flex gap-4 border-t border-white/10 pt-4 md:pt-0 md:border-0">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 text-center border border-white/10 min-w-[90px]">
                    <span class="block text-xs text-blue-200">Jenjang</span>
                    <span class="text-sm font-semibold font-mono">{{ tenant.bentuk_pendidikan }}</span>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 text-center border border-white/10 min-w-[90px]">
                    <span class="block text-xs text-blue-200">Status</span>
                    <span class="text-sm font-semibold font-mono">{{ tenant.status_sekolah }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout: 3 Columns on Large Screens -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        
        <!-- Left Column: Visual Profile Card & Navigation Info (Spans 1 Col) -->
        <div class="flex flex-col gap-6">
            
            <!-- Logo Preview Card -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 profile-card flex flex-col items-center text-center">
                <h5 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4 w-full text-left">Logo Sekolah</h5>
                
                <div class="relative group mb-4">
                    <div class="w-36 h-36 rounded-2xl overflow-hidden border-2 border-slate-100 bg-slate-50 flex items-center justify-center shadow-md">
                        <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain" alt="Logo Preview" />
                        <div v-else class="flex flex-col items-center justify-center p-4">
                            <i class="bi bi-image text-4xl text-slate-300 mb-2"></i>
                            <span class="text-xs text-slate-500">Belum Ada Logo</span>
                        </div>
                    </div>
                </div>

                <h4 class="text-lg font-bold text-slate-800 mb-1">{{ tenant.nama_sekolah }}</h4>
                <p class="text-xs text-slate-500 font-mono mb-4">{{ tenant.npsn }}</p>
                
                <div class="w-full bg-slate-50 rounded-2xl p-4 text-left border border-slate-100 flex flex-col gap-3">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 font-medium">Bentuk Pendidikan</span>
                        <span class="text-slate-700 font-semibold">{{ tenant.bentuk_pendidikan }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 font-medium">Status Sekolah</span>
                        <span class="text-slate-700 font-semibold">{{ tenant.status_sekolah }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 font-medium">Paket Langganan</span>
                        <span class="text-slate-700 font-semibold text-blue-600 font-mono">{{ tenant.paket_aktif }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs border-t border-slate-200/60 pt-3">
                        <span class="text-slate-500 font-medium">Sinkronisasi</span>
                        <span class="font-semibold text-emerald-600 flex items-center gap-1">
                            <i class="bi bi-check-circle-fill"></i> {{ tenant.status_sinkronisasi }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Accreditation Status & Details Card -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 profile-card">
                <h5 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4">Legalitas</h5>
                
                <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl p-4 border border-blue-100/60 mb-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl bg-blue-600/10 text-blue-600 flex items-center justify-center">
                            <i class="bi bi-award-fill text-lg"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Status Akreditasi</span>
                            <span class="text-sm font-bold text-slate-800">{{ tenant.akreditasi || 'Belum Terakreditasi' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <span class="text-xs font-semibold text-slate-500">Sertifikat Akreditasi:</span>
                    <div v-if="tenant.sertifikat_akreditasi" class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                        <div class="flex items-center gap-2 truncate">
                            <i class="bi" :class="isPdf(tenant.sertifikat_akreditasi) ? 'bi-file-pdf-fill text-red-500' : 'bi-file-image-fill text-blue-500'"></i>
                            <span class="text-xs text-slate-600 truncate font-mono">{{ getFilename(tenant.sertifikat_akreditasi) }}</span>
                        </div>
                        <a :href="'/SINTA-SaaS/storage/app/public/' + tenant.sertifikat_akreditasi" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                            Lihat <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                    <div v-else class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-dashed border-slate-200 text-center">
                        Berkas sertifikat belum diunggah
                    </div>
                </div>
            </div>
        </div>

        <!-- Right/Middle Column: Interactive Forms (Spans 2 Cols) -->
        <div class="lg:col-span-2">
            <form @submit.prevent="submitProfile" class="flex flex-col gap-6">
                
                <!-- GRUP 1: DATA IDENTITAS POKOK (READ-ONLY) -->
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200/80">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">1</div>
                        <h4 class="text-base font-bold text-slate-800 mb-0">Data Identitas Pokok <span class="text-xs font-normal text-slate-500 font-mono ml-2">(Read-Only)</span></h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="form_nama_sekolah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Instansi</label>
                            <input id="form_nama_sekolah" name="nama_sekolah" type="text" class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm font-medium font-sans cursor-not-allowed focus:outline-none" :value="tenant.nama_sekolah" disabled>
                        </div>
                        
                        <div>
                            <label for="form_npsn" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">NPSN Resmi</label>
                            <input id="form_npsn" name="npsn" type="text" class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm font-mono cursor-not-allowed focus:outline-none" :value="tenant.npsn" disabled>
                        </div>

                        <div>
                            <label for="form_bentuk_pendidikan" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Bentuk Pendidikan</label>
                            <input id="form_bentuk_pendidikan" name="bentuk_pendidikan" type="text" class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm font-medium cursor-not-allowed focus:outline-none" :value="tenant.bentuk_pendidikan" disabled>
                        </div>

                        <div>
                            <label for="form_status_sekolah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Status Sekolah</label>
                            <input id="form_status_sekolah" name="status_sekolah" type="text" class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm font-medium cursor-not-allowed focus:outline-none" :value="tenant.status_sekolah" disabled>
                        </div>

                        <div>
                            <label for="form_kurikulum" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Kurikulum Terapan</label>
                            <input id="form_kurikulum" name="kurikulum" type="text" class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm font-medium cursor-not-allowed focus:outline-none" :value="tenant.kurikulum" disabled>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Status Akun Tenant</span>
                            <div class="flex items-center w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50">
                                <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-emerald-200/50 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GRUP 2: DATA WILAYAH & KONTAK INSTANSI (FORM INPUT) -->
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200/80">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">2</div>
                        <h4 class="text-base font-bold text-slate-800 mb-0">Wilayah & Kontak Instansi</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                        <div class="md:col-span-3">
                            <label for="alamat_sekolah" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Alamat Lengkap Sekolah <span class="text-red-500">*</span></label>
                            <textarea id="alamat_sekolah" name="alamat_sekolah" v-model="form.alamat_sekolah" 
                                class="w-full p-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                rows="3" placeholder="Masukkan alamat jalan, nomor, RT/RW..." 
                                :class="{'border-red-400 ring-1 ring-red-400': errors.alamat_sekolah}"></textarea>
                            <span class="text-xs text-red-500 mt-1 block" v-if="errors.alamat_sekolah">{{ errors.alamat_sekolah[0] }}</span>
                        </div>

                        <div>
                            <label for="rt_rw" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">RT / RW <span class="text-red-500">*</span></label>
                            <input type="text" id="rt_rw" name="rt_rw" v-model="form.rt_rw" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="00/00" :class="{'border-red-400 ring-1 ring-red-400': errors.rt_rw}">
                            <span class="text-xs text-red-500 mt-1 block" v-if="errors.rt_rw">{{ errors.rt_rw[0] }}</span>
                        </div>

                        <div>
                            <label for="kode_pos" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Kode Pos <span class="text-red-500">*</span></label>
                            <input type="number" id="kode_pos" name="kode_pos" v-model="form.kode_pos" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="60181" :class="{'border-red-400 ring-1 ring-red-400': errors.kode_pos}">
                            <span class="text-xs text-red-500 mt-1 block" v-if="errors.kode_pos">{{ errors.kode_pos[0] }}</span>
                        </div>

                        <div>
                            <label for="kelurahan" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Kelurahan <span class="text-red-500">*</span></label>
                            <input type="text" id="kelurahan" name="kelurahan" v-model="form.kelurahan" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="Manukan Kulon" :class="{'border-red-400 ring-1 ring-red-400': errors.kelurahan}">
                            <span class="text-xs text-red-500 mt-1 block" v-if="errors.kelurahan">{{ errors.kelurahan[0] }}</span>
                        </div>

                        <div>
                            <label for="kecamatan" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Kecamatan</label>
                            <input type="text" id="kecamatan" name="kecamatan" v-model="form.kecamatan" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="Kec. Tandes">
                        </div>

                        <div>
                            <label for="kabupaten_kota" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Kabupaten / Kota</label>
                            <input type="text" id="kabupaten_kota" name="kabupaten_kota" v-model="form.kabupaten_kota" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="Kota Surabaya">
                        </div>

                        <div>
                            <label for="provinsi" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Provinsi</label>
                            <input type="text" id="provinsi" name="provinsi" v-model="form.provinsi" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="Prov. Jawa Timur">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label for="no_telp" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">No. Telepon Instansi</label>
                            <input type="text" id="no_telp" name="no_telp" v-model="form.no_telp" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="031-1234567">
                        </div>

                        <div>
                            <label for="email_sekolah" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Email Resmi Sekolah <span class="text-red-500">*</span></label>
                            <input type="email" id="email_sekolah" name="email_sekolah" v-model="form.email_sekolah" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="info@sekolah.sch.id" :class="{'border-red-400 ring-1 ring-red-400': errors.email_sekolah}">
                            <span class="text-xs text-red-500 mt-1 block" v-if="errors.email_sekolah">{{ errors.email_sekolah[0] }}</span>
                        </div>

                        <div>
                            <label for="website" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Website Sekolah</label>
                            <input type="url" id="website" name="website" v-model="form.website" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="https://www.sekolah.sch.id">
                        </div>
                    </div>
                </div>

                <!-- GRUP 3: DATA MANAJEMEN SDM & KEPEMIMPINAN (FORM INPUT) -->
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200/80">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">3</div>
                        <h4 class="text-base font-bold text-slate-800 mb-0">Manajemen SDM & Kepemimpinan</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                        <div>
                            <label for="nama_kepsek" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Nama Kepala Sekolah</label>
                            <input type="text" id="nama_kepsek" name="nama_kepsek" v-model="form.nama_kepsek" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="Nama Kepala Sekolah beserta gelar">
                        </div>

                        <div>
                            <label for="pangkat_kepsek" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Pangkat Kepala Sekolah</label>
                            <input type="text" id="pangkat_kepsek" name="pangkat_kepsek" v-model="form.pangkat_kepsek" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="Contoh: Pembina / Pembina Tk. I">
                        </div>

                        <div>
                            <label for="nip_kepsek" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">NIP Kepala Sekolah</label>
                            <input type="text" id="nip_kepsek" name="nip_kepsek" v-model="form.nip_kepsek" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none font-mono"
                                placeholder="19820101XXXXXXXXXX">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="nama_operator" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Nama Operator Sekolah</label>
                            <input type="text" id="nama_operator" name="nama_operator" v-model="form.nama_operator" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none"
                                placeholder="Nama Penanggung Jawab Operator">
                        </div>

                        <div>
                            <label for="email_operator" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Username / Email Operator <span class="text-red-500">*</span></label>
                            <input type="email" id="email_operator" name="email_operator" v-model="form.email_operator" 
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none font-mono"
                                placeholder="operator@email.com" :class="{'border-red-400 ring-1 ring-red-400': errors.email_operator}">
                            <span class="text-xs text-red-500 mt-1 block" v-if="errors.email_operator">{{ errors.email_operator[0] }}</span>
                        </div>
                    </div>
                </div>

                <!-- GRUP 4: LEGALITAS & MEDIA (UPLOAD FILES) -->
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200/80">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">4</div>
                        <h4 class="text-base font-bold text-slate-800 mb-0">Legalitas & Media (Unggah Berkas)</h4>
                    </div>
 
                    <!-- Accreditation Status Text Input -->
                    <div class="mb-6">
                        <label for="akreditasi" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Status Akreditasi Sekolah <span class="text-red-500">*</span></label>
                        <input type="text" id="akreditasi" name="akreditasi" v-model="form.akreditasi" 
                               class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm bg-white input-premium focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                               placeholder="Contoh: Terakreditasi A (Unggul)">
                    </div>
 
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Drag & Drop Logo Upload -->
                        <div class="flex flex-col gap-2">
                            <label for="logo_file_input" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Unggah Logo Sekolah</label>
                            <div class="dropzone-premium bg-slate-50 rounded-2xl border-2 border-dashed border-slate-300 p-6 flex flex-col items-center justify-center cursor-pointer text-center relative overflow-hidden"
                                 @dragover.prevent="onDragOver('logo', $event)"
                                 @dragleave="onDragLeave('logo')"
                                 @drop.prevent="onDrop('logo', $event)"
                                 @click="triggerInput('logo')"
                                 :class="{'dragover border-blue-500 bg-blue-50/50': dragStates.logo}">
                                
                                <input id="logo_file_input" name="logo" type="file" ref="logoInput" class="hidden" accept=".jpg,.jpeg,.png" @change="onFileChange('logo', $event)">
                                
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center mb-1">
                                        <i class="bi bi-cloud-arrow-up-fill text-xl"></i>
                                    </div>
                                    <span class="text-xs font-bold text-slate-700">Pilih berkas logo atau drag ke sini</span>
                                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Format JPG, JPEG, PNG (Maks. 500 KB)</span>
                                </div>
 
                                <div v-if="logoFile" class="absolute inset-0 bg-white/95 backdrop-blur-sm p-4 flex flex-col items-center justify-center gap-2">
                                    <i class="bi bi-check-circle-fill text-3xl text-emerald-500 animate-bounce"></i>
                                    <span class="text-xs font-bold text-slate-700 truncate max-w-[90%]">{{ logoFile.name }}</span>
                                    <span class="text-[10px] text-slate-500 font-mono">{{ formatBytes(logoFile.size) }}</span>
                                    <button type="button" @click.stop="clearFile('logo')" class="text-xs text-red-500 hover:text-red-700 font-bold border border-red-200 hover:border-red-300 bg-white px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-1 transition">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                            <span class="text-xs text-red-500 mt-1 block" v-if="errors.logo">{{ errors.logo[0] }}</span>
                        </div>
 
                        <!-- Drag & Drop Accreditation Certificate Upload -->
                        <div class="flex flex-col gap-2">
                            <label for="sertifikat_file_input" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Unggah Sertifikat Akreditasi</label>
                            <div class="dropzone-premium bg-slate-50 rounded-2xl border-2 border-dashed border-slate-300 p-6 flex flex-col items-center justify-center cursor-pointer text-center relative overflow-hidden"
                                 @dragover.prevent="onDragOver('sertifikat', $event)"
                                 @dragleave="onDragLeave('sertifikat')"
                                 @drop.prevent="onDrop('sertifikat', $event)"
                                 @click="triggerInput('sertifikat')"
                                 :class="{'dragover border-blue-500 bg-blue-50/50': dragStates.sertifikat}">
                                
                                <input id="sertifikat_file_input" name="sertifikat_akreditasi" type="file" ref="sertifikatInput" class="hidden" accept=".jpg,.jpeg,.png,.pdf" @change="onFileChange('sertifikat', $event)">
                                
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center mb-1">
                                        <i class="bi bi-file-earmark-arrow-up-fill text-xl"></i>
                                    </div>
                                    <span class="text-xs font-bold text-slate-700">Pilih berkas sertifikat atau drag ke sini</span>
                                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Format PDF, JPG, JPEG, PNG (Maks. 500 KB)</span>
                                </div>
 
                                <div v-if="sertifikatFile" class="absolute inset-0 bg-white/95 backdrop-blur-sm p-4 flex flex-col items-center justify-center gap-2">
                                    <i class="bi text-3xl" :class="isPdf(sertifikatFile.name) ? 'bi-file-pdf-fill text-red-500' : 'bi-file-image-fill text-blue-500'"></i>
                                    <span class="text-xs font-bold text-slate-700 truncate max-w-[90%]">{{ sertifikatFile.name }}</span>
                                    <span class="text-[10px] text-slate-500 font-mono">{{ formatBytes(sertifikatFile.size) }}</span>
                                    <button type="button" @click.stop="clearFile('sertifikat')" class="text-xs text-red-500 hover:text-red-700 font-bold border border-red-200 hover:border-red-300 bg-white px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-1 transition">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                            <span class="text-xs text-red-500 mt-1 block" v-if="errors.sertifikat_akreditasi">{{ errors.sertifikat_akreditasi[0] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button and Action Bar -->
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 flex items-center justify-between flex-wrap gap-4">
                    <span class="text-xs text-slate-500 font-medium">Pastikan data bertanda bintang <span class="text-red-500">*</span> terisi secara benar dan valid.</span>
                    <button type="submit" :disabled="saving" class="h-11 px-6 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-md shadow-blue-500/20 hover:shadow-blue-600/30 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="saving" class="spinner-border spinner-border-sm" role="status"></span>
                        <i v-else class="bi bi-cloud-check-fill text-base"></i>
                        {{ saving ? 'Menyimpan Profil...' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 Library -->
<script src="/SINTA-SaaS/assets/js/sweetalert2.all.min.js"></script>

<!-- Vue App Registration Script -->
<script>
{
    const { ref, reactive, onMounted } = Vue;

    window.VueAppRegistry.register('#schoolProfileApp', {
        setup() {
            // Initial empty refs populated onMounted
            const tenant = ref({});
            const logoPreview = ref(null);
            
            const form = reactive({
                alamat_sekolah: '',
                rt_rw: '',
                kode_pos: '',
                kelurahan: '',
                kecamatan: 'Kec. Tandes',
                kabupaten_kota: 'Kota Surabaya',
                provinsi: 'Prov. Jawa Timur',
                no_telp: '',
                email_sekolah: '',
                website: '',
                nama_kepsek: 'Nana Petty Puspitasari',
                pangkat_kepsek: 'Pembina',
                nip_kepsek: '',
                nama_operator: 'Edi Sugiarto',
                email_operator: 'aidasugiarto@gmail.com',
                akreditasi: 'A (Unggul)'
            });

            const loadSchoolProfile = async () => {
                try {
                    const urlParams = new URLSearchParams(window.location.search);
                    const tId = urlParams.get('tenant_id') || '';
                    const response = await axios.get(`/SINTA-SaaS/sekolah/identitas?ajax=1&action=get_profile_detail&tenant_id=${tId}`);
                    if (response.data && response.data.success) {
                        tenant.value = response.data.data;
                        logoPreview.value = tenant.value.logo ? `/SINTA-SaaS/storage/app/public/${tenant.value.logo}` : null;
                        
                        Object.keys(form).forEach(key => {
                            if (key in tenant.value) {
                                form[key] = tenant.value[key] !== null ? tenant.value[key] : '';
                            }
                        });
                    }
                } catch (err) {
                    console.error("Gagal memuat profil sekolah:", err);
                }
            };

            onMounted(() => {
                loadSchoolProfile();
            });

            const errors = ref({});
            const saving = ref(false);

            // Drag States
            const dragStates = reactive({
                logo: false,
                sertifikat: false
            });

            // Upload Files Reactive
            const logoFile = ref(null);
            const sertifikatFile = ref(null);

            // Refs mapping manually
            const logoInput = ref(null);
            const sertifikatInput = ref(null);

            // Trigger click on hidden file input
            const triggerInput = (type) => {
                const el = type === 'logo' 
                    ? document.getElementById('logo_file_input') || logoInput.value 
                    : document.getElementById('sertifikat_file_input') || sertifikatInput.value;
                if (el) el.click();
            };

            // On File Selection Change
            const onFileChange = (type, event) => {
                const files = event.target.files;
                if (files && files.length > 0) {
                    processFile(type, files[0]);
                }
            };

            // Drag & Drop Handlers
            const onDragOver = (type, event) => {
                dragStates[type] = true;
            };

            const onDragLeave = (type) => {
                dragStates[type] = false;
            };

            const onDrop = (type, event) => {
                dragStates[type] = false;
                const files = event.dataTransfer.files;
                if (files && files.length > 0) {
                    processFile(type, files[0]);
                }
            };

            // Process & Validate File Size & Type
            const processFile = (type, file) => {
                const maxSize = 500 * 1024; // 500 KB
                
                // Clear old errors for this file
                if (type === 'logo') {
                    delete errors.value.logo;
                    
                    if (file.size > maxSize) {
                        errors.value.logo = ['Ukuran logo tidak boleh melebihi 500 KB.'];
                        return;
                    }
                    
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (!['jpg', 'jpeg', 'png'].includes(ext)) {
                        errors.value.logo = ['Logo harus berupa gambar (.jpg, .jpeg, .png).'];
                        return;
                    }
                    
                    logoFile.value = file;
                    // Create object url for preview
                    logoPreview.value = URL.createObjectURL(file);
                } else {
                    delete errors.value.sertifikat_akreditasi;
                    
                    if (file.size > maxSize) {
                        errors.value.sertifikat_akreditasi = ['Ukuran sertifikat tidak boleh melebihi 500 KB.'];
                        return;
                    }
                    
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (!['jpg', 'jpeg', 'png', 'pdf'].includes(ext)) {
                        errors.value.sertifikat_akreditasi = ['Sertifikat harus berupa gambar atau PDF (.jpg, .jpeg, .png, .pdf).'];
                        return;
                    }
                    
                    sertifikatFile.value = file;
                }
            };

            // Remove selected file before uploading
            const clearFile = (type) => {
                if (type === 'logo') {
                    logoFile.value = null;
                    logoPreview.value = tenant.value.logo ? `/SINTA-SaaS/storage/app/public/${tenant.value.logo}` : null;
                    const el = document.getElementById('logo_file_input') || logoInput.value;
                    if (el) el.value = '';
                } else {
                    sertifikatFile.value = null;
                    const el = document.getElementById('sertifikat_file_input') || sertifikatInput.value;
                    if (el) el.value = '';
                }
            };

            // Redirect for super admin filter
            const changeTenant = (event) => {
                const selectedId = event.target.value;
                window.location.href = `/SINTA-SaaS/sekolah/identitas?tenant_id=${selectedId}`;
            };

            // Submit Profile Handler
            const submitProfile = async () => {
                saving.value = true;
                errors.value = {};

                // Use FormData to support files upload
                const formData = new FormData();
                
                // Add text values
                for (const key in form) {
                    formData.append(key, form[key]);
                }
                
                // Add files if exists
                if (logoFile.value) {
                    formData.append('logo', logoFile.value);
                }
                if (sertifikatFile.value) {
                    formData.append('sertifikat_akreditasi', sertifikatFile.value);
                }
                
                // Append current active tenant_id
                formData.append('tenant_id', tenant.value.id);

                try {
                    const response = await axios.post('/SINTA-SaaS/api/v1/sekolah/update', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    });

                    if (response.data && response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembaruan Berhasil',
                            text: response.data.message || 'Profil sekolah Anda telah berhasil disimpan.',
                            confirmButtonColor: '#2563eb'
                        }).then(() => {
                            // Reload to get fresh paths and values correctly
                            window.location.reload();
                        });
                    }
                } catch (err) {
                    console.error(err);
                    if (err.response && err.response.status === 422) {
                        errors.value = err.response.data.errors || {};
                        
                        // Fire toast alert for validation issues
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: 'Harap periksa kembali isian form Anda.',
                            confirmButtonColor: '#2563eb'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            text: (err.response && err.response.data && err.response.data.error) || err.message || 'Terjadi kesalahan sistem saat menyimpan.'
                        });
                    }
                } finally {
                    saving.value = false;
                }
            };

            // Helper format bytes to KB/MB
            const formatBytes = (bytes, decimals = 2) => {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            };

            // Helper to extract filename from relative path
            const getFilename = (path) => {
                if (!path) return '';
                return path.split('/').pop();
            };

            // Helper to check if file is PDF
            const isPdf = (filename) => {
                if (!filename) return false;
                return filename.toLowerCase().endsWith('.pdf');
            };

            return {
                tenant,
                form,
                errors,
                saving,
                dragStates,
                logoFile,
                sertifikatFile,
                logoPreview,
                logoInput,
                sertifikatInput,
                triggerInput,
                onFileChange,
                onDragOver,
                onDragLeave,
                onDrop,
                clearFile,
                submitProfile,
                formatBytes,
                getFilename,
                isPdf,
                changeTenant
            };
        }
    });
}
</script>
