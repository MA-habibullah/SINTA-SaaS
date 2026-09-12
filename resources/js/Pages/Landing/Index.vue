<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 selection:bg-blue-600 selection:text-white font-sans antialiased overflow-x-hidden">
    
    <!-- Ambient glowing backgrounds -->
    <div class="fixed top-0 left-1/3 w-[650px] h-[650px] bg-blue-600/10 rounded-full blur-[160px] pointer-events-none z-0"></div>
    <div class="fixed bottom-0 right-1/4 w-[650px] h-[650px] bg-indigo-600/10 rounded-full blur-[160px] pointer-events-none z-0"></div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/70 backdrop-blur-xl transition-all">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <!-- Logo -->
        <Link href="/" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/30 group-hover:scale-105 transition-transform">
            S
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-black text-xl tracking-tight text-white">SINTA</span>
              <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400 font-bold border border-blue-500/30 tracking-wider"></span>
            </div>
            <span class="text-[11px] text-slate-400 block -mt-1 font-medium">Sistem Inti Akademik Terpadu</span>
          </div>
        </Link>

        <!-- Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-xs font-semibold text-slate-300">
          <a href="#fitur" class="hover:text-white transition">Fitur Modular</a>
          <a href="#keunggulan" class="hover:text-white transition">Keunggulan</a>
          <a href="#paket" class="hover:text-white transition">Paket & Free Trial</a>
          <a href="#faq" class="hover:text-white transition">FAQ</a>
        </nav>

        <!-- Auth Actions -->
        <div class="flex items-center gap-3">
          <Link href="/login" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800/80 transition flex items-center gap-1.5">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Masuk</span>
          </Link>
          <Link href="/daftar-sekolah" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-extrabold shadow-lg shadow-blue-600/30 transition transform hover:-translate-y-0.5 flex items-center gap-2">
            <i class="bi bi-gift-fill text-yellow-300"></i>
            <span>Coba Gratis 3 Bulan</span>
          </Link>
        </div>
      </div>
    </header>

    <!-- HERO SECTION -->
    <section class="relative z-10 pt-16 pb-24 lg:pt-24 lg:pb-32 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      <div class="text-center max-w-4xl mx-auto space-y-6">
        
        <!-- Hero Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-blue-500/10 to-indigo-500/10 border border-blue-500/30 text-blue-400 text-xs font-bold shadow-xs">
          <i class="bi bi-stars text-amber-400"></i>
          <span>{{ heroItem?.badge_text || 'Platform Manajemen Sekolah Multi-Tenant #1' }}</span>
        </div>

        <!-- Main Title -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.15]">
          {{ heroItem?.title || 'Transformasi Digital Manajemen Sekolah Terpadu & Terintegrasi' }}
        </h1>

        <!-- Subtitle -->
        <p class="text-base sm:text-lg text-slate-400 font-normal leading-relaxed max-w-3xl mx-auto">
          {{ heroItem?.subtitle || 'SINTA menghadirkan ekosistem tata kelola sekolah all-in-one: Rapor Kurikulum Merdeka, Buku Induk, Tagihan SPP Multi-Channel, PPDB, hingga BK & Presensi. Nikmati masa uji coba gratis 3 bulan penuh!' }}
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
          <Link href="/daftar-sekolah" 
                class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-700 hover:to-indigo-800 text-white font-extrabold text-sm shadow-xl shadow-blue-500/30 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-2.5">
            <i class="bi bi-rocket-takeoff-fill text-yellow-300 text-base"></i>
            <span>Daftarkan Sekolah Anda (Free Trial 3 Bulan)</span>
          </Link>
          <Link href="/login" 
                class="w-full sm:w-auto px-7 py-4 rounded-2xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 hover:text-white font-bold text-sm border border-slate-700/80 transition flex items-center justify-center gap-2">
            <i class="bi bi-box-arrow-in-right text-base"></i>
            <span>Login Portal Sekolah</span>
          </Link>
        </div>

        <!-- Trust Badges / Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-12 max-w-3xl mx-auto">
          <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs text-center">
            <span class="block text-2xl font-black text-white tracking-tight">16</span>
            <span class="text-xs text-slate-400 font-medium">Modul Terintegrasi</span>
          </div>
          <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs text-center">
            <span class="block text-2xl font-black text-emerald-400 tracking-tight">3 Bulan</span>
            <span class="text-xs text-slate-400 font-medium">Free Trial Bebas Biaya</span>
          </div>
          <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs text-center">
            <span class="block text-2xl font-black text-blue-400 tracking-tight">Multi-Schema</span>
            <span class="text-xs text-slate-400 font-medium">Isolasi Data Aman</span>
          </div>
          <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs text-center">
            <span class="block text-2xl font-black text-purple-400 tracking-tight">100%</span>
            <span class="text-xs text-slate-400 font-medium">Standar Kurikulum</span>
          </div>
        </div>
      </div>
    </section>

    <!-- MODULAR FEATURES SECTION -->
    <section id="fitur" class="relative z-10 py-20 border-t border-slate-800/60 bg-slate-900/40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold">
            <i class="bi bi-grid-fill"></i> Solusi Terlengkap
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            16 Modul Mandiri Siap Pakai untuk Seluruh Kebutuhan Sekolah
          </h2>
          <p class="text-sm text-slate-400">
            Seluruh data saling tersinkronisasi otomatis dari buku induk, penilaian rapor, hingga pelaporan keuangan.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="(feat, idx) in featureItems" :key="idx" 
               class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 hover:border-blue-500/50 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-300 group">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600/20 to-indigo-600/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
              <i :class="['bi', feat.icon_class || 'bi-stars']"></i>
            </div>
            <div v-if="feat.badge_text" class="inline-block text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded bg-blue-500/15 text-blue-300 border border-blue-500/20 mb-2">
              {{ feat.badge_text }}
            </div>
            <h3 class="text-lg font-black text-white group-hover:text-blue-400 transition-colors">
              {{ feat.title }}
            </h3>
            <p class="text-xs text-slate-400 mt-2 leading-relaxed">
              {{ feat.subtitle || feat.content }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FREE TRIAL & PRICING SECTION -->
    <section id="paket" class="relative z-10 py-24 border-t border-slate-800/60">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
            <i class="bi bi-gift-fill"></i> Pilihan Masa Uji Coba Gratis
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Mulai Tanpa Risiko, Pilih Durasi Free Trial Anda
          </h2>
          <p class="text-sm text-slate-400">
            Dapatkan akses penuh dan dukungan migrasi data tanpa dipungut biaya sepeserpun.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
          <!-- Card 3 Bulan Free Trial (Featured) -->
          <div class="p-8 rounded-3xl bg-gradient-to-b from-blue-950/70 to-slate-900 border-2 border-blue-500/80 shadow-2xl shadow-blue-500/10 relative overflow-hidden flex flex-col justify-between">
            <div class="absolute top-4 right-4 px-3 py-1 rounded-full bg-blue-500 text-white font-black text-[10px] uppercase tracking-wider shadow-md">
              Paling Populer
            </div>

            <div>
              <div class="inline-flex items-center gap-2 text-blue-400 text-xs font-bold mb-2">
                <i class="bi bi-stars"></i> Rekomendasi Utama
              </div>
              <h3 class="text-2xl font-black text-white">Free Trial 3 Bulan Penuh</h3>
              <p class="text-xs text-slate-300 mt-1">Uji coba komprehensif untuk satu semester akademik.</p>

              <div class="my-6">
                <span class="text-4xl font-black text-white tracking-tight">Rp 0</span>
                <span class="text-xs text-slate-400 ml-2 font-medium">/ 3 Bulan Percobaan Penuh</span>
              </div>

              <div class="space-y-3 py-4 border-t border-slate-700/60 text-xs text-slate-200">
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Akses Seluruh 16 Modul Tanpa Pembatasan</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Unlimited Data Siswa, Guru & Tenaga Kependidikan</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Generator Cetak Rapor Kurikulum Merdeka & K13</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Kustom Subdomain Sekolah Resmi (.sinta.id)</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Pendampingan Teknis & Migrasi Data Gratis</span>
                </div>
              </div>
            </div>

            <Link href="/daftar-sekolah?trial=3" 
                  class="mt-8 w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-xs text-center shadow-lg shadow-blue-500/25 transition">
              Daftar Sekarang & Coba 3 Bulan Gratis
            </Link>
          </div>

          <!-- Card 1 Bulan Free Trial -->
          <div class="p-8 rounded-3xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 shadow-xl flex flex-col justify-between">
            <div>
              <div class="inline-flex items-center gap-2 text-slate-400 text-xs font-bold mb-2">
                <i class="bi bi-lightning-charge"></i> Uji Coba Cepat
              </div>
              <h3 class="text-2xl font-black text-white">Free Trial 1 Bulan</h3>
              <p class="text-xs text-slate-400 mt-1">Eksplorasi singkat fitur pokok manajemen akademik.</p>

              <div class="my-6">
                <span class="text-4xl font-black text-white tracking-tight">Rp 0</span>
                <span class="text-xs text-slate-400 ml-2 font-medium">/ 1 Bulan Percobaan</span>
              </div>

              <div class="space-y-3 py-4 border-t border-slate-800 text-xs text-slate-300">
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Modul Data Siswa, GTK & Buku Induk</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Manajemen Keuangan SPP & Kasir</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Presensi & Absensi Siswa GTK</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <i class="bi bi-check2-circle text-emerald-400 text-base font-bold"></i>
                  <span>Dukungan Teknis via Chat WhatsApp</span>
                </div>
              </div>
            </div>

            <Link href="/daftar-sekolah?trial=1" 
                  class="mt-8 w-full py-3.5 px-6 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-black text-xs text-center border border-slate-700 transition">
              Coba 1 Bulan Gratis
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ SECTION -->
    <section id="faq" class="relative z-10 py-20 border-t border-slate-800/60 bg-slate-900/30">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 space-y-2">
          <h2 class="text-3xl font-black text-white tracking-tight">Pertanyaan yang Sering Diajukan (FAQ)</h2>
          <p class="text-xs text-slate-400">Pahami alur aktivasi dan kemudahan implementasi SINTA di sekolah Anda.</p>
        </div>

        <div class="space-y-4">
          <div v-for="(faq, idx) in faqItems" :key="idx" 
               class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800">
            <h4 class="text-sm font-bold text-white flex items-center gap-2">
              <i class="bi bi-question-circle text-blue-400"></i>
              <span>{{ faq.title }}</span>
            </h4>
            <p class="text-xs text-slate-400 mt-2 leading-relaxed pl-6">
              {{ faq.subtitle || faq.content }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FINAL CTA BANNER -->
    <section class="relative z-10 py-20 border-t border-slate-800/80">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-10 sm:p-14 rounded-3xl bg-gradient-to-tr from-blue-900/70 via-indigo-900/60 to-slate-900 border border-blue-500/40 text-center space-y-6 shadow-2xl">
          <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Siap Mewujudkan Digitalisasi Sekolah Anda Hari Ini?
          </h2>
          <p class="text-sm text-slate-300 max-w-2xl mx-auto">
            Daftarkan sekolah Anda dalam 2 menit. Super Admin akan memverifikasi dan memberikan akses penuh 3 bulan secara gratis.
          </p>
          <div class="pt-2">
            <Link href="/daftar-sekolah" 
                  class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl bg-white text-blue-900 hover:bg-slate-100 font-black text-sm shadow-xl transition transform hover:scale-105">
              <i class="bi bi-gift-fill text-blue-600"></i>
              <span>Mulai Free Trial 3 Bulan Sekarang</span>
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="relative z-10 border-t border-slate-800/80 bg-slate-950 py-10 text-xs text-slate-500">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-sm">S</div>
          <span class="font-bold text-slate-400">&copy; 2026 SINTA Platform Tata Kelola Sekolah</span>
        </div>
        <div class="flex items-center gap-6">
          <a href="#fitur" class="hover:text-slate-300 transition">Fitur</a>
          <a href="#paket" class="hover:text-slate-300 transition">Harga & Trial</a>
          <Link href="/login" class="hover:text-slate-300 transition">Login Portal</Link>
          <Link href="/daftar-sekolah" class="text-blue-400 hover:text-blue-300 font-bold transition">Daftar Free Trial</Link>
        </div>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  promotions: {
    type: Object,
    default: () => ({}),
  },
  tenantsCount: {
    type: Number,
    default: 1,
  },
});

const heroItem = computed(() => {
  return props.promotions?.hero?.[0] || null;
});

const featureItems = computed(() => {
  if (props.promotions?.features && props.promotions.features.length > 0) {
    return props.promotions.features;
  }
  return [
    { title: 'Buku Induk & Profil Siswa', subtitle: 'Pencatatan NISN, biodata, mutasi, riwayat beasiswa dan cetak lembar buku induk resmi.', icon_class: 'bi-journal-text', badge_text: 'Data Pokok' },
    { title: 'Cetak Rapor Kurikulum Merdeka & K13', subtitle: 'Kalkulasi nilai otomatis, deskripsi capaian pembelajaran dinamis, dan cetak PDF instan.', icon_class: 'bi-award', badge_text: 'Kurikulum' },
    { title: 'Manajemen Keuangan SPP & Kasir', subtitle: 'Pos tarif, tagihan siswa, kuitansi kasir, dan integrasi notifikasi WA.', icon_class: 'bi-wallet2', badge_text: 'Keuangan' },
    { title: 'PPDB & Seleksi Siswa Baru Online', subtitle: 'Pendaftaran mandiri, alur seleksi, dan verifikasi berkas pendaftar.', icon_class: 'bi-person-plus', badge_text: 'Kesiswaan' },
    { title: 'Bimbingan Konseling & Kedisiplinan', subtitle: 'Layanan konseling siswa, pencatatan poin pelanggaran dan surat panggilan orang tua.', icon_class: 'bi-shield-check', badge_text: 'Bimbingan' },
    { title: 'Perpustakaan & Katalog Buku OPAC', subtitle: 'Sirkulasi peminjaman, barcode buku, kartu anggota, dan pelaporan denda.', icon_class: 'bi-book', badge_text: 'Perpustakaan' },
  ];
});

const faqItems = computed(() => {
  if (props.promotions?.faq && props.promotions.faq.length > 0) {
    return props.promotions.faq;
  }
  return [
    { title: 'Bagaimana cara mendapatkan Free Trial 3 Bulan?', subtitle: 'Cukup isi formulir di menu Daftar Sekolah. Super Admin akan memverifikasi permohonan dalam waktu 1x24 jam dan mengaktifkan akses Anda.' },
    { title: 'Apakah data sekolah aman?', subtitle: 'Sangat aman. Setiap sekolah memiliki isolasi skema PostgreSQL dan hak akses terenkripsi multi-tenant.' },
    { title: 'Apakah bisa melakukan penyesuaian modul yang diizinkan?', subtitle: 'Bisa. Super Admin dapat mengatur menu dan modul mana saja yang diaktifkan untuk sekolah Anda saat masa trial maupun langganan.' },
  ];
});
</script>
