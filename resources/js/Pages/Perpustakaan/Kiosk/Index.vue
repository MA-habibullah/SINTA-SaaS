<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    todayVisitors: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_hari_ini: 0,
            total_siswa: 0,
            total_guru: 0,
            total_rombongan: 0,
        }),
    },
    tenant: {
        type: Object,
        default: () => ({}),
    },
    activeTenantId: {
        type: String,
        default: '',
    },
})

// State Waktu & Jam
const currentTime = ref('')
const currentDate = ref('')
let timerInterval = null

const updateClock = () => {
    const now = new Date()
    currentTime.value = now.toLocaleTimeString('id-ID', { hour12: false }) + ' WIB'
    currentDate.value = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

// State Kiosk Scanner
const barcodeInput = ref('')
const isScanning = ref(false)
const scanResult = ref(null)
const scanError = ref(null)
const scannerInputRef = ref(null)

// Audio Speech & Web Audio Sound Feedback
const playAudioGreeting = (text) => {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel()
        const utterance = new SpeechSynthesisUtterance(text)
        utterance.lang = 'id-ID'
        utterance.rate = 1.0
        utterance.pitch = 1.0
        window.speechSynthesis.speak(utterance)
    }
}

const playBeep = (type = 'success') => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)()
        const osc = audioCtx.createOscillator()
        const gain = audioCtx.createGain()
        osc.connect(gain)
        gain.connect(audioCtx.destination)

        if (type === 'success') {
            osc.frequency.setValueAtTime(587.33, audioCtx.currentTime) // D5
            osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1) // A5
            gain.gain.setValueAtTime(0.3, audioCtx.currentTime)
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3)
            osc.start(audioCtx.currentTime)
            osc.stop(audioCtx.currentTime + 0.3)
        } else {
            osc.frequency.setValueAtTime(220, audioCtx.currentTime) // A3
            osc.frequency.setValueAtTime(164.81, audioCtx.currentTime + 0.15) // E3
            gain.gain.setValueAtTime(0.4, audioCtx.currentTime)
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4)
            osc.start(audioCtx.currentTime)
            osc.stop(audioCtx.currentTime + 0.4)
        }
    } catch (e) {
        // Fallback silent
    }
}

// Proses Scan Barcode / NISN Kiosk
const handleBarcodeSubmit = async () => {
    const code = barcodeInput.value.trim()
    if (!code || isScanning.value) return

    isScanning.value = true
    scanResult.value = null
    scanError.value = null

    try {
        const res = await axios.post('/perpustakaan/kiosk/scan-kta', {
            barcode: code,
            tenant_id: props.activeTenantId,
        })

        if (res.data.success) {
            scanResult.value = res.data
            playBeep('success')
            if (res.data.audio_speech) {
                playAudioGreeting(res.data.audio_speech)
            }
            // Auto reload pengunjung setelah 1 detik
            setTimeout(() => {
                router.reload({ only: ['todayVisitors', 'stats'] })
            }, 1000)
        }
    } catch (err) {
        playBeep('error')
        scanError.value = err.response?.data?.message || 'Nomor barcode/ID tidak terdaftar di sistem.'
        playAudioGreeting('Nomor identitas tidak terdaftar. Silakan hubungi petugas.')
    } finally {
        isScanning.value = false
        barcodeInput.value = ''
        if (scannerInputRef.value) {
            scannerInputRef.value.focus()
        }
    }
}

// Modal Kunjungan Rombongan / Tamu Luar
const showRombonganModal = ref(false)
const formRombongan = useForm({
    nama_rombongan: '',
    jumlah_peserta: 1,
    ketua_pendamping: '',
    asal_instansi: '',
    keperluan: 'Kunjungan Studi Literasi Bersama',
    tenant_id: props.activeTenantId,
})

const submitRombongan = () => {
    formRombongan.post('/perpustakaan/kiosk/rombongan', {
        onSuccess: () => {
            showRombonganModal.value = false
            formRombongan.reset()
            playBeep('success')
            playAudioGreeting('Presensi rombongan berhasil direkam. Selamat datang di perpustakaan.')
        },
    })
}

// Modal Survey Kepuasan
const showSurveyModal = ref(false)
const formSurvey = useForm({
    survey_id: 'default-survey',
    nama_responden: '',
    skor: {
        pelayanan: 5,
        fasilitas: 5,
        koleksi: 5,
        kenyamanan: 5,
    },
    saran_masukan: '',
})

// Full Screen Toggle
const isFullScreen = ref(false)
const toggleFullScreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullScreen.value = true
        }).catch(() => {})
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen().then(() => {
                isFullScreen.value = false
            }).catch(() => {})
        }
    }
}

onMounted(() => {
    updateClock()
    timerInterval = setInterval(updateClock, 1000)
    if (scannerInputRef.value) {
        scannerInputRef.value.focus()
    }
})

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})
</script>

<template>
    <Head title="Anjungan Mandiri Presensi Perpustakaan" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between selection:bg-blue-600 selection:text-white relative overflow-hidden font-sans">
        
        <!-- Decorative Ambient Background -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-emerald-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header Bar -->
        <header class="relative z-10 px-6 py-4 sm:px-10 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-blue-400 flex items-center justify-center text-white text-2xl shadow-lg shadow-blue-500/25">
                    <i class="bi bi-book-half"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-black text-white tracking-tight leading-tight">
                        {{ tenant?.nama_sekolah || 'Perpustakaan Digital SINTA' }}
                    </h1>
                    <p class="text-xs text-slate-400 flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 font-medium text-2xs border border-emerald-500/30">
                            <i class="bi bi-circle-fill text-[6px] me-1 text-emerald-400 animate-pulse"></i> KIOSK AKTIF
                        </span>
                        <span>Anjungan Presensi & Buku Tamu Mandiri</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Clock Widget -->
                <div class="hidden sm:block text-right px-4 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/80">
                    <div class="text-sm font-mono font-bold text-emerald-400 leading-tight tracking-wider">{{ currentTime }}</div>
                    <div class="text-2xs text-slate-400">{{ currentDate }}</div>
                </div>

                <!-- Fullscreen Button -->
                <button @click="toggleFullScreen" 
                        class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 flex items-center justify-center transition shadow-xs"
                        :title="isFullScreen ? 'Keluar Layar Penuh' : 'Mode Layar Penuh (Tablet)'">
                    <i :class="isFullScreen ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen'"></i>
                </button>

                <!-- Exit to Admin -->
                <Link :href="route('perpustakaan.sirkulasi')" 
                      class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-semibold flex items-center transition shadow-xs">
                    <i class="bi bi-box-arrow-left me-1.5"></i> Admin
                </Link>
            </div>
        </header>

        <!-- Main Body Interactive Kiosk Section -->
        <main class="relative z-10 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 flex-1 flex flex-col justify-center space-y-6">
            
            <!-- Welcome Banner -->
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-400/30">
                    <i class="bi bi-person-check-fill me-1.5"></i> Presensi Pengunjung Perpustakaan
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Silakan Pindai <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-indigo-300 to-emerald-400">Kartu Anggota</span> Anda
                </h2>
                <p class="text-xs sm:text-sm text-slate-400">
                    Arahkan barcode KTA / NISN ke sensor pemindai atau masukkan nomor identitas secara mandiri.
                </p>
            </div>

            <!-- 2 Interactive Action Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto w-full">
                
                <!-- Card 1: Scanner KTA Mandiri -->
                <div class="bg-slate-900/80 border-2 border-blue-500/40 hover:border-blue-400 rounded-3xl p-6 sm:p-8 flex flex-col justify-between space-y-6 shadow-2xl shadow-blue-500/10 backdrop-blur-md relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-600/10 rounded-full blur-2xl pointer-events-none group-hover:bg-blue-600/20 transition"></div>
                    
                    <div class="space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center text-3xl shadow-inner mx-auto sm:mx-0">
                            <i class="bi bi-upc-scan animate-pulse"></i>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-white flex items-center justify-center sm:justify-start gap-2">
                                <span>Pindai KTA / NISN</span>
                                <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300">Individu</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1 text-center sm:text-left leading-relaxed">
                                Berlaku untuk seluruh Siswa, Dewan Guru, dan Tenaga Kependidikan.
                            </p>
                        </div>
                    </div>

                    <!-- Scanner Input Form -->
                    <form @submit.prevent="handleBarcodeSubmit" class="space-y-3">
                        <div class="relative">
                            <input ref="scannerInputRef"
                                   v-model="barcodeInput"
                                   type="text" 
                                   placeholder="Scan barcode / ketik NISN..."
                                   class="w-full bg-slate-950 border-2 border-slate-700 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 rounded-2xl px-4 py-3.5 text-center sm:text-left text-sm font-mono text-blue-300 placeholder:text-slate-600 transition outline-none"
                                   :disabled="isScanning"
                                   autofocus />
                            <button type="submit" 
                                    class="absolute right-2 top-2 bottom-2 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center transition shadow-md"
                                    :disabled="isScanning || !barcodeInput.trim()">
                                <i v-if="isScanning" class="bi bi-arrow-repeat animate-spin"></i>
                                <span v-else>Masuk</span>
                            </button>
                        </div>
                        <p class="text-2xs text-slate-500 text-center font-mono">
                            <i class="bi bi-lightning-charge-fill text-amber-400 me-1"></i> Auto-Detect Scanner Barcode USB / Bluetooth
                        </p>
                    </form>

                    <!-- Alert Feedback Pop-up Result -->
                    <div v-if="scanResult" class="p-4 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 text-xs space-y-1 animate-fade-in">
                        <div class="font-bold text-sm text-emerald-300 flex items-center">
                            <i class="bi bi-check-circle-fill me-2 text-emerald-400"></i> {{ scanResult.message }}
                        </div>
                        <div class="text-2xs text-emerald-300/80">
                            Nama: <strong class="text-white">{{ scanResult.data.nama }}</strong> &bull; Tipe: <span class="uppercase">{{ scanResult.data.tipe }}</span> &bull; Pukul: {{ scanResult.data.waktu }}
                        </div>
                    </div>

                    <div v-if="scanError" class="p-4 rounded-2xl bg-rose-500/20 border border-rose-500/40 text-rose-200 text-xs flex items-start space-y-1 animate-fade-in">
                        <i class="bi bi-exclamation-octagon-fill text-base text-rose-400 me-2 shrink-0"></i>
                        <div>
                            <div class="font-bold text-rose-300">Gagal Memproses Presensi</div>
                            <div class="text-2xs text-rose-300/80 mt-0.5">{{ scanError }}</div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Kunjungan Rombongan / Tamu Luar -->
                <div class="bg-slate-900/80 border border-slate-800 hover:border-emerald-500/50 rounded-3xl p-6 sm:p-8 flex flex-col justify-between space-y-6 shadow-2xl backdrop-blur-md relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-600/10 rounded-full blur-2xl pointer-events-none group-hover:bg-emerald-600/20 transition"></div>

                    <div class="space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-3xl shadow-inner mx-auto sm:mx-0">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-white flex items-center justify-center sm:justify-start gap-2">
                                <span>Rombongan & Tamu Luar</span>
                                <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300">Grup</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1 text-center sm:text-left leading-relaxed">
                                Untuk presensi kelas studi literasi bersama, kunjungan studi banding, atau tamu umum.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button @click="showRombonganModal = true" 
                                class="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm flex items-center justify-center transition shadow-lg shadow-emerald-600/20">
                            <i class="bi bi-pencil-square me-2 text-base"></i> Isi Presensi Rombongan / Tamu
                        </button>
                        <button @click="showSurveyModal = true" 
                                class="w-full py-2.5 px-4 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold flex items-center justify-center transition border border-slate-700">
                            <i class="bi bi-star-half text-amber-400 me-2"></i> Berikan Penilaian Kepuasan (IKM)
                        </button>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 text-2xs text-slate-400 text-center">
                        Terima kasih telah menjaga ketertiban & kebersihan ruang perpustakaan.
                    </div>
                </div>

            </div>

            <!-- Real-Time Metrics Widget Bar -->
            <div class="max-w-4xl mx-auto w-full grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-3.5 text-center space-y-1">
                    <div class="text-2xs font-semibold text-slate-400 uppercase tracking-wider">Total Kunjungan</div>
                    <div class="text-xl sm:text-2xl font-black text-white">{{ stats.total_hari_ini }}</div>
                    <div class="text-3xs text-emerald-400 font-medium"><i class="bi bi-calendar2-check me-1"></i> Hari Ini</div>
                </div>
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-3.5 text-center space-y-1">
                    <div class="text-2xs font-semibold text-slate-400 uppercase tracking-wider">Siswa Terdata</div>
                    <div class="text-xl sm:text-2xl font-black text-blue-400">{{ stats.total_siswa }}</div>
                    <div class="text-3xs text-slate-500">Pemustaka Siswa</div>
                </div>
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-3.5 text-center space-y-1">
                    <div class="text-2xs font-semibold text-slate-400 uppercase tracking-wider">Guru & Tenaga Kerja</div>
                    <div class="text-xl sm:text-2xl font-black text-indigo-400">{{ stats.total_guru }}</div>
                    <div class="text-3xs text-slate-500">Pendidik & Tendik</div>
                </div>
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-3.5 text-center space-y-1">
                    <div class="text-2xs font-semibold text-slate-400 uppercase tracking-wider">Rombongan Studi</div>
                    <div class="text-xl sm:text-2xl font-black text-emerald-400">{{ stats.total_rombongan }}</div>
                    <div class="text-3xs text-slate-500">Kelompok Literasi</div>
                </div>
            </div>

            <!-- Feed Pengunjung Terbaru Hari Ini -->
            <div class="max-w-4xl mx-auto w-full bg-slate-900/40 border border-slate-800/80 rounded-2xl p-4 space-y-3">
                <div class="flex items-center justify-between text-xs font-bold text-slate-400">
                    <span class="flex items-center"><i class="bi bi-clock-history me-1.5 text-blue-400"></i> Pengunjung Terkini</span>
                    <span class="text-2xs text-slate-500 font-normal">Auto-update real-time</span>
                </div>
                <div v-if="todayVisitors.length > 0" class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                    <div v-for="visitor in todayVisitors" :key="visitor.id" 
                         class="shrink-0 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/80 text-xs flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full" :class="visitor.jenis_pengunjung === 'Siswa' ? 'bg-blue-400' : 'bg-emerald-400'"></span>
                        <span class="font-semibold text-white">{{ visitor.nama_pengunjung }}</span>
                        <span class="text-2xs text-slate-400 font-mono">{{ new Date(visitor.waktu_kunjungan).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                    </div>
                </div>
                <div v-else class="text-center py-2 text-xs text-slate-500">
                    Belum ada rekaman pengunjung hari ini. Silakan scan kartu Anda.
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="relative z-10 px-6 py-4 text-center text-xs text-slate-500 border-t border-slate-800/80 bg-slate-900/40">
            <p>SINTA &bull; Sistem Informasi Terintegrasi Akademik & Perpustakaan Digital Berstandar Nasional</p>
        </footer>

        <!-- MODAL 1: FORMULIR ROMBONGAN (TELEPORT TO BODY) -->
        <Teleport to="body">
            <div v-if="showRombonganModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
                <div class="bg-slate-900 border border-slate-700 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4 text-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-white flex items-center">
                            <i class="bi bi-people-fill text-emerald-400 me-2"></i> Formulir Presensi Rombongan / Tamu
                        </h3>
                        <button @click="showRombonganModal = false" class="text-slate-400 hover:text-white text-lg">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitRombongan" class="space-y-3.5 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Nama Rombongan / Kelas / Instansi *</label>
                            <input v-model="formRombongan.nama_rombongan" type="text" placeholder="Contoh: Kelas X IPA 1 / Rombongan Literasi" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white focus:border-emerald-500 focus:outline-none" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">Jumlah Peserta (Orang) *</label>
                                <input v-model.number="formRombongan.jumlah_peserta" type="number" min="1" max="500" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white focus:border-emerald-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">Ketua / Guru Pendamping *</label>
                                <input v-model="formRombongan.ketua_pendamping" type="text" placeholder="Nama Guru PJ" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white focus:border-emerald-500 focus:outline-none" />
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Asal Sekolah / Instansi</label>
                            <input v-model="formRombongan.asal_instansi" type="text" placeholder="Kosongkan jika dari internal sekolah" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white focus:border-emerald-500 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Keperluan Kunjungan *</label>
                            <textarea v-model="formRombongan.keperluan" rows="2" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-white focus:border-emerald-500 focus:outline-none" placeholder="Tujuan kunjungan belajar / riset..."></textarea>
                        </div>

                        <div class="pt-2 flex justify-end space-x-2">
                            <button type="button" @click="showRombonganModal = false" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formRombongan.processing" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition flex items-center">
                                <i v-if="formRombongan.processing" class="bi bi-arrow-repeat animate-spin me-1.5"></i>
                                Simpan Presensi Rombongan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 2: SURVEY IKM (TELEPORT TO BODY) -->
        <Teleport to="body">
            <div v-if="showSurveyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
                <div class="bg-slate-900 border border-slate-700 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4 text-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-white flex items-center">
                            <i class="bi bi-star-fill text-amber-400 me-2"></i> Indeks Kepuasan Pemustaka (IKM)
                        </h3>
                        <button @click="showSurveyModal = false" class="text-slate-400 hover:text-white text-lg">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <p class="text-slate-400">
                            Mohon berikan penilaian terhadap fasilitas dan pelayanan perpustakaan hari ini (1 = Kurang, 5 = Sangat Baik):
                        </p>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                                <span>1. Keramahan & Kecepatan Petugas</span>
                                <div class="flex gap-1 text-amber-400">
                                    <button v-for="i in 5" :key="i" type="button" @click="formSurvey.skor.pelayanan = i" class="text-base">
                                        <i :class="i <= formSurvey.skor.pelayanan ? 'bi bi-star-fill' : 'bi bi-star text-slate-700'"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                                <span>2. Kelengkapan Koleksi Buku & E-Book</span>
                                <div class="flex gap-1 text-amber-400">
                                    <button v-for="i in 5" :key="i" type="button" @click="formSurvey.skor.koleksi = i" class="text-base">
                                        <i :class="i <= formSurvey.skor.koleksi ? 'bi bi-star-fill' : 'bi bi-star text-slate-700'"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                                <span>3. Kebersihan & Kenyamanan Ruangan</span>
                                <div class="flex gap-1 text-amber-400">
                                    <button v-for="i in 5" :key="i" type="button" @click="formSurvey.skor.kenyamanan = i" class="text-base">
                                        <i :class="i <= formSurvey.skor.kenyamanan ? 'bi bi-star-fill' : 'bi bi-star text-slate-700'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Saran / Masukan untuk Kemajuan Perpustakaan</label>
                            <textarea v-model="formSurvey.saran_masukan" rows="2" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-white focus:border-amber-500 focus:outline-none" placeholder="Tuliskan masukan Anda..."></textarea>
                        </div>

                        <div class="pt-2 flex justify-end space-x-2">
                            <button type="button" @click="showSurveyModal = false" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold transition">
                                Tutup
                            </button>
                            <button type="button" @click="showSurveyModal = false; playBeep('success'); playAudioGreeting('Terima kasih atas ulasan dan penilaian Anda.');" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold transition">
                                Kirim Penilaian
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
