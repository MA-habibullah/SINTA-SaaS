<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-between relative overflow-hidden selection:bg-blue-600 selection:text-white">
    <!-- Ambient glowing backgrounds -->
    <div class="absolute top-0 left-1/4 w-[600px] h-[600px] bg-blue-600/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-[600px] h-[600px] bg-indigo-600/15 rounded-full blur-[140px] pointer-events-none"></div>

    <!-- Header / Navbar -->
    <header class="relative z-20 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
        <Link href="/" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform">
            S
          </div>
          <div>
            <span class="font-black text-lg tracking-tight text-white flex items-center gap-1.5">
              SINTA <span class="text-xs px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-400 font-bold border border-blue-500/30">SAAS</span>
            </span>
            <span class="text-[11px] text-slate-400 block -mt-1 font-medium">Sistem Inti Tata Kelola Sekolah Terpadu</span>
          </div>
        </Link>

        <div class="flex items-center gap-4 text-xs font-semibold">
          <span class="text-slate-400 hidden sm:inline">Sudah memiliki akun?</span>
          <Link href="/login" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition flex items-center gap-2">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Masuk Portal</span>
          </Link>
        </div>
      </div>
    </header>

    <!-- Main Registration Section -->
    <main class="relative z-10 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-10 my-auto">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Feature Highlights & Trial Perks -->
        <div class="lg:col-span-4 space-y-6 hidden lg:block">
          <div class="p-6 rounded-3xl bg-slate-800/60 border border-slate-700/70 shadow-xl backdrop-blur-sm">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold mb-4">
              <i class="bi bi-gift-fill"></i> Free Trial 3 Bulan Penuh
            </div>
            <h3 class="text-xl font-black text-white leading-tight">
              Transformasi Digital Sekolah Dimulai di Sini
            </h3>
            <p class="text-xs text-slate-300 mt-2 leading-relaxed">
              Daftarkan sekolah Anda sekarang untuk menikmati seluruh 16 modul terintegrasi tanpa biaya awal. Super Admin akan memverifikasi dalam 1x24 jam.
            </p>

            <div class="mt-6 space-y-3 pt-4 border-t border-slate-700/60">
              <div v-for="(feat, idx) in features" :key="idx" class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center shrink-0 text-sm">
                  <i :class="['bi', feat.icon]"></i>
                </div>
                <div>
                  <h5 class="text-xs font-bold text-white">{{ feat.title }}</h5>
                  <p class="text-[11px] text-slate-400 leading-snug">{{ feat.desc }}</p>
                </div>
              </div>
            </div>

            <div class="mt-6 p-4 rounded-2xl bg-blue-900/30 border border-blue-600/30">
              <div class="flex items-center gap-2 text-xs text-blue-300 font-bold">
                <i class="bi bi-shield-check text-base text-blue-400"></i>
                <span>Keamanan Data Terisolasi Multi-Schema</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1">
                Database dan aset berkas sekolah Anda terenkripsi aman dan terisolasi mandiri.
              </p>
            </div>
          </div>
        </div>

        <!-- Right Column: Registration Form Stepper -->
        <div class="lg:col-span-8">
          <div class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 p-6 sm:p-10 text-slate-800">
            
            <!-- Form Stepper Navigation -->
            <div class="mb-8">
              <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-slate-100 w-full z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-blue-600 transition-all duration-300 z-0"
                     :style="{ width: currentStep === 1 ? '0%' : (currentStep === 2 ? '50%' : '100%') }"></div>

                <!-- Step 1 Indicator -->
                <div class="relative z-10 flex flex-col items-center">
                  <button type="button" @click="currentStep = 1"
                          :class="['w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all',
                                   currentStep === 1 ? 'bg-blue-600 text-white shadow-md shadow-blue-500/40 ring-4 ring-blue-100' : 
                                   (currentStep > 1 ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 border border-slate-300')]">
                    <i v-if="currentStep > 1" class="bi bi-check-lg text-sm"></i>
                    <span v-else>1</span>
                  </button>
                  <span class="text-[11px] font-bold mt-1.5 text-slate-700">Identitas Sekolah</span>
                </div>

                <!-- Step 2 Indicator -->
                <div class="relative z-10 flex flex-col items-center">
                  <button type="button" @click="validateStep1() && (currentStep = 2)"
                          :class="['w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all',
                                   currentStep === 2 ? 'bg-blue-600 text-white shadow-md shadow-blue-500/40 ring-4 ring-blue-100' : 
                                   (currentStep > 2 ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 border border-slate-300')]">
                    <i v-if="currentStep > 2" class="bi bi-check-lg text-sm"></i>
                    <span v-else>2</span>
                  </button>
                  <span class="text-[11px] font-bold mt-1.5 text-slate-700">Kontak PIC</span>
                </div>

                <!-- Step 3 Indicator -->
                <div class="relative z-10 flex flex-col items-center">
                  <button type="button" @click="validateStep1() && validateStep2() && (currentStep = 3)"
                          :class="['w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all',
                                   currentStep === 3 ? 'bg-blue-600 text-white shadow-md shadow-blue-500/40 ring-4 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300']">
                    <span>3</span>
                  </button>
                  <span class="text-[11px] font-bold mt-1.5 text-slate-700">Akun & Trial</span>
                </div>
              </div>
            </div>

            <!-- Error Banner -->
            <div v-if="form.hasErrors" class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs">
              <div class="flex items-center gap-2 font-bold text-red-800">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>Terdapat kesalahan pengisian data:</span>
              </div>
              <ul class="list-disc list-inside mt-1.5 space-y-0.5 text-[11px]">
                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
              </ul>
            </div>

            <form @submit.prevent="submitRegistration">
              <!-- ============================================== -->
              <!-- STEP 1: IDENTITAS SEKOLAH                      -->
              <!-- ============================================== -->
              <div v-show="currentStep === 1" class="space-y-4">
                <div class="border-b border-slate-100 pb-3 mb-4">
                  <h4 class="text-base font-extrabold text-slate-800">1. Data Pokok Instansi Sekolah</h4>
                  <p class="text-xs text-slate-500">Lengkapi identitas resmi sekolah yang akan didaftarkan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Nama Sekolah -->
                  <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Resmi Sekolah <span class="text-red-500">*</span></label>
                    <input v-model="form.nama_sekolah" type="text" required placeholder="Contoh: SMA Negeri 1 Unggulan"
                           @input="autoGenerateSubdomain"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.nama_sekolah" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.nama_sekolah }}</span>
                  </div>

                  <!-- NPSN -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">NPSN (Nomor Pokok Sekolah Nasional) <span class="text-red-500">*</span></label>
                    <input v-model="form.npsn" type="text" required maxlength="20" placeholder="8 digit NPSN, misal: 20109988"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.npsn" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.npsn }}</span>
                  </div>

                  <!-- Bentuk Pendidikan -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Bentuk Pendidikan <span class="text-red-500">*</span></label>
                    <select v-model="form.bentuk_pendidikan" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition">
                      <option value="SMA">SMA (Sekolah Menengah Atas)</option>
                      <option value="SMK">SMK (Sekolah Menengah Kejuruan)</option>
                      <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                      <option value="SD">SD (Sekolah Dasar)</option>
                      <option value="Madrasah">Madrasah (MA / MTs / MI)</option>
                      <option value="Lainnya">Lainnya / Lembaga Kursus</option>
                    </select>
                  </div>

                  <!-- Status Sekolah -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status Sekolah <span class="text-red-500">*</span></label>
                    <select v-model="form.status_sekolah" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition">
                      <option value="Negeri">Negeri</option>
                      <option value="Swasta">Swasta / Yayasan</option>
                    </select>
                  </div>

                  <!-- Subdomain Tenant -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan Subdomain Tenant <span class="text-red-500">*</span></label>
                    <div class="flex items-center">
                      <input v-model="form.subdomain" type="text" required placeholder="sman1unggul"
                             class="grow px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-l-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                      <span class="px-3 py-2.5 bg-slate-100 border border-l-0 border-slate-200 rounded-r-xl text-xs text-slate-500 font-bold">.sinta.id</span>
                    </div>
                    <span v-if="form.errors.subdomain" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.subdomain }}</span>
                  </div>

                  <!-- Kabupaten / Kota -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kabupaten / Kota</label>
                    <input v-model="form.kabupaten_kota" type="text" placeholder="Contoh: Kota Surabaya"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                  </div>

                  <!-- Provinsi -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Provinsi</label>
                    <input v-model="form.provinsi" type="text" placeholder="Contoh: Jawa Timur"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                  </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-end pt-6 border-t border-slate-100">
                  <button type="button" @click="goToStep2"
                          class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center gap-2">
                    <span>Lanjut ke Kontak PIC</span>
                    <i class="bi bi-arrow-right"></i>
                  </button>
                </div>
              </div>

              <!-- ============================================== -->
              <!-- STEP 2: KONTAK PENANGGUNG JAWAB (PIC)          -->
              <!-- ============================================== -->
              <div v-show="currentStep === 2" class="space-y-4">
                <div class="border-b border-slate-100 pb-3 mb-4">
                  <h4 class="text-base font-extrabold text-slate-800">2. Kontak Penanggung Jawab (PIC)</h4>
                  <p class="text-xs text-slate-500">Informasi penanggung jawab untuk verifikasi dan koordinasi sistem.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Nama PIC -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap PIC / Pendaftar <span class="text-red-500">*</span></label>
                    <input v-model="form.pic_nama" type="text" required placeholder="Nama lengkap beserta gelar..."
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.pic_nama" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.pic_nama }}</span>
                  </div>

                  <!-- Jabatan PIC -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jabatan di Sekolah <span class="text-red-500">*</span></label>
                    <input v-model="form.pic_jabatan" type="text" required placeholder="Contoh: Kepala Sekolah / Wakakur / Operator IT"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.pic_jabatan" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.pic_jabatan }}</span>
                  </div>

                  <!-- No Telepon / WA -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp Aktif <span class="text-red-500">*</span></label>
                    <input v-model="form.pic_telepon" type="tel" required placeholder="Contoh: 081234567890"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.pic_telepon" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.pic_telepon }}</span>
                  </div>

                  <!-- Email PIC -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Resmi / Aktif <span class="text-red-500">*</span></label>
                    <input v-model="form.pic_email" type="email" required placeholder="pic.sekolah@gmail.com"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.pic_email" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.pic_email }}</span>
                  </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between pt-6 border-t border-slate-100">
                  <button type="button" @click="currentStep = 1"
                          class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali</span>
                  </button>
                  <button type="button" @click="goToStep3"
                          class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center gap-2">
                    <span>Lanjut ke Akun & Trial</span>
                    <i class="bi bi-arrow-right"></i>
                  </button>
                </div>
              </div>

              <!-- ============================================== -->
              <!-- STEP 3: AKUN ADMIN & PILIHAN FREE TRIAL        -->
              <!-- ============================================== -->
              <div v-show="currentStep === 3" class="space-y-4">
                <div class="border-b border-slate-100 pb-3 mb-4">
                  <h4 class="text-base font-extrabold text-slate-800">3. Pembuatan Akun Administrator & Durasi Uji Coba</h4>
                  <p class="text-xs text-slate-500">Tentukan kredensial login utama sekolah dan durasi Free Trial yang diajukan.</p>
                </div>

                <!-- Trial Duration Cards -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-2">Pilih Paket Uji Coba Gratis (Free Trial)</label>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Option 3 Bulan -->
                    <label :class="['cursor-pointer p-4 rounded-2xl border-2 transition-all flex items-start gap-3.5',
                                   form.trial_duration_months === 3 ? 'border-blue-600 bg-blue-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white']">
                      <input type="radio" v-model="form.trial_duration_months" :value="3" class="mt-1 text-blue-600 focus:ring-blue-500" />
                      <div>
                        <div class="flex items-center gap-2">
                          <span class="font-bold text-xs text-slate-900">3 Bulan Gratis (Rekomendasi)</span>
                          <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-black text-[10px]">Populer</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Uji coba penuh seluruh 16 modul terpadu beserta pendampingan migrasi data.</p>
                      </div>
                    </label>

                    <!-- Option 1 Bulan -->
                    <label :class="['cursor-pointer p-4 rounded-2xl border-2 transition-all flex items-start gap-3.5',
                                   form.trial_duration_months === 1 ? 'border-blue-600 bg-blue-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white']">
                      <input type="radio" v-model="form.trial_duration_months" :value="1" class="mt-1 text-blue-600 focus:ring-blue-500" />
                      <div>
                        <span class="font-bold text-xs text-slate-900">1 Bulan Gratis</span>
                        <p class="text-[11px] text-slate-500 mt-0.5">Uji coba cepat modul pokok akademik, buku induk, dan keuangan sekolah.</p>
                      </div>
                    </label>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                  <!-- Nama Admin -->
                  <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Administrator Sekolah <span class="text-red-500">*</span></label>
                    <input v-model="form.admin_nama" type="text" required placeholder="Nama lengkap admin sekolah..."
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.admin_nama" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.admin_nama }}</span>
                  </div>

                  <!-- Username Admin -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Username Login <span class="text-red-500">*</span></label>
                    <input v-model="form.admin_username" type="text" required placeholder="admin_sekolah"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.admin_username" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.admin_username }}</span>
                  </div>

                  <!-- Password Admin -->
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi (Password) <span class="text-red-500">*</span></label>
                    <input v-model="form.admin_password" type="password" required placeholder="Minimal 6 karakter"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white transition" />
                    <span v-if="form.errors.admin_password" class="text-xs text-red-500 font-semibold mt-1 block">{{ form.errors.admin_password }}</span>
                  </div>
                </div>

                <!-- Terms & Notice Box -->
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                  <div class="flex items-start gap-2.5">
                    <i class="bi bi-info-circle-fill text-amber-600 text-base shrink-0 mt-0.5"></i>
                    <div>
                      <span class="font-bold">Informasi Alur Verifikasi & Approval:</span>
                      <p class="mt-0.5 text-amber-800 leading-relaxed text-[11px]">
                        Setelah formulir pendaftaran ini dikirimkan, permohonan akan diverifikasi oleh Super Admin SINTA SaaS di Pusat Kontrol. Anda dapat login ke portal menggunakan username di atas segera setelah verifikasi disetujui.
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between pt-6 border-t border-slate-100">
                  <button type="button" @click="currentStep = 2"
                          class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali</span>
                  </button>
                  <button type="submit" :disabled="form.processing"
                          class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-black rounded-xl transition shadow-lg shadow-emerald-600/30 disabled:opacity-50 flex items-center gap-2">
                    <span v-if="form.processing">Mengirimkan Pendaftaran...</span>
                    <span v-else>Kirimkan Pendaftaran Sekolah</span>
                    <i class="bi bi-send-fill font-bold"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-slate-800/80 bg-slate-950/80 py-6 text-center text-xs text-slate-500">
      <div class="max-w-7xl mx-auto px-4">
        <p>&copy; 2026 SINTA SaaS &bull; Platform Tata Kelola Sekolah Terpadu & Terintegrasi Multi-Tenant</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  defaultTrial: {
    type: Number,
    default: 3,
  },
  features: {
    type: Array,
    default: () => [],
  },
});

const currentStep = ref(1);

const form = useForm({
  // Identitas Sekolah
  nama_sekolah: '',
  npsn: '',
  bentuk_pendidikan: 'SMA',
  status_sekolah: 'Negeri',
  subdomain: '',
  kabupaten_kota: '',
  provinsi: '',

  // Kontak PIC
  pic_nama: '',
  pic_jabatan: '',
  pic_telepon: '',
  pic_email: '',

  // Akun Admin & Trial
  admin_nama: '',
  admin_username: '',
  admin_password: '',
  trial_duration_months: props.defaultTrial || 3,
});

const autoGenerateSubdomain = () => {
  if (!form.subdomain || form.subdomain.length === 0) {
    const slug = form.nama_sekolah
      .toLowerCase()
      .replace(/[^a-z0-9]/g, '')
      .substring(0, 20);
    form.subdomain = slug;
  }
};

const validateStep1 = () => {
  if (!form.nama_sekolah || !form.npsn || !form.subdomain) {
    alert('Mohon lengkapi Nama Sekolah, NPSN, dan Subdomain.');
    return false;
  }
  return true;
};

const goToStep2 = () => {
  if (validateStep1()) {
    if (!form.pic_nama && form.nama_sekolah) {
      form.admin_nama = form.pic_nama;
    }
    currentStep.value = 2;
  }
};

const validateStep2 = () => {
  if (!form.pic_nama || !form.pic_jabatan || !form.pic_telepon || !form.pic_email) {
    alert('Mohon lengkapi seluruh kontak penanggung jawab (PIC).');
    return false;
  }
  return true;
};

const goToStep3 = () => {
  if (validateStep2()) {
    if (!form.admin_nama) {
      form.admin_nama = form.pic_nama;
    }
    if (!form.admin_username && form.subdomain) {
      form.admin_username = 'admin_' + form.subdomain;
    }
    currentStep.value = 3;
  }
};

const submitRegistration = () => {
  form.post('/daftar-sekolah');
};
</script>
