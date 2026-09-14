<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    isSuperAdmin: Boolean,
    tenants: Array,
    activeTenantId: String,
    activeSurvei: Object,
    pertanyaanList: Array,
    surveiList: Object,
    ratingSummary: Array,
    recentFeedback: Array,
    kpi: Object,
    kelasSelector: Array,
    mapelSelector: Array,
    guruSelector: Array,
    filters: Object,
})

// Super Admin Selected Tenant
const selectedTenant = ref(props.activeTenantId || '')

const onTenantChange = () => {
    router.get('/kepala-sekolah/survei-guru', {
        tenant_id: selectedTenant.value || undefined,
        search: search.value || undefined,
        status: status.value || undefined,
        filter_guru: filterGuru.value || undefined,
        filter_kelas: filterKelas.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Tab State
const activeTab = ref('raport_guru') // 'raport_guru', 'form_siswa', 'jadwal_survei', 'bank_pertanyaan', 'umpan_balik'

// Search & Filter State
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')
const filterGuru = ref(props.filters?.filter_guru || '')
const filterKelas = ref(props.filters?.filter_kelas || '')

const applyFilters = () => {
    router.get('/kepala-sekolah/survei-guru', {
        tenant_id: props.isSuperAdmin ? (selectedTenant.value || undefined) : undefined,
        search: search.value || undefined,
        status: status.value || undefined,
        filter_guru: filterGuru.value || undefined,
        filter_kelas: filterKelas.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// --------------------------------------------------------------------------
// 1. Form Pengisian Survei Siswa (Anonim & Dinamis, Skala Likert 5 Poin)
// --------------------------------------------------------------------------
const studentForm = useForm({
    survei_id: props.activeSurvei?.id || null,
    guru_id: '',
    nama_guru: '',
    kelas_id: '',
    nama_kelas: '',
    mapel_id: '',
    nama_mapel: '',
    skor_items: [5, 5, 5, 5, 5, 5, 5, 5, 5, 5], // 10 butir default skala 5
    umpan_balik_positif: '',
    area_pengembangan: '',
})

const onGuruSelected = (e) => {
    const selectedId = e.target.value
    const found = props.guruSelector?.find(g => g.id === selectedId)
    if (found) {
        studentForm.guru_id = found.id
        studentForm.nama_guru = found.nama_lengkap
    } else {
        studentForm.guru_id = ''
        studentForm.nama_guru = ''
    }
}

const onKelasSelected = (e) => {
    studentForm.nama_kelas = e.target.value
}

const onMapelSelected = (e) => {
    studentForm.nama_mapel = e.target.value
}

const submitStudentSurvey = () => {
    studentForm.post('/kepala-sekolah/survei-guru/submit-evaluasi-siswa', {
        preserveScroll: true,
        onSuccess: () => {
            alert('Survei evaluasi guru berhasil dikirimkan secara anonim. Terima kasih atas kontribusi Anda!')
            studentForm.reset()
            studentForm.skor_items = [5, 5, 5, 5, 5, 5, 5, 5, 5, 5]
            activeTab.value = 'raport_guru'
        }
    })
}

const resetForNextTeacher = () => {
    const currentKelas = studentForm.nama_kelas
    studentForm.reset()
    studentForm.nama_kelas = currentKelas
    studentForm.skor_items = [5, 5, 5, 5, 5, 5, 5, 5, 5, 5]
}

// --------------------------------------------------------------------------
// 2. Definisi Skala Likert 5 Poin Baku
// --------------------------------------------------------------------------
const skalaOptions = [
    {
        val: 1,
        label: 'Sangat Kurang',
        desc: 'Kinerja atau kondisi berada di tingkat paling bawah atau jauh dari standar.',
        color: 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100',
        activeColor: 'bg-rose-600 text-white border-rose-600 shadow-xs'
    },
    {
        val: 2,
        label: 'Kurang',
        desc: 'Kinerja belum memenuhi standar minimal dan butuh banyak perbaikan.',
        color: 'bg-orange-50 text-orange-700 border-orange-200 hover:bg-orange-100',
        activeColor: 'bg-orange-500 text-white border-orange-500 shadow-xs'
    },
    {
        val: 3,
        label: 'Cukup',
        desc: 'Kinerja sudah memenuhi standar minimal atau rata-rata (cukup memuaskan).',
        color: 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100',
        activeColor: 'bg-amber-500 text-white border-amber-500 shadow-xs'
    },
    {
        val: 4,
        label: 'Baik',
        desc: 'Kinerja melampaui standar rata-rata dan menunjukkan hasil yang memuaskan.',
        color: 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100',
        activeColor: 'bg-blue-600 text-white border-blue-600 shadow-xs'
    },
    {
        val: 5,
        label: 'Sangat Baik',
        desc: 'Kinerja berada di tingkat tertinggi, sangat istimewa, atau melampaui seluruh ekspektasi.',
        color: 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100',
        activeColor: 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
    },
]

// 10 Indikator Baku
const default10Questions = [
    { no: 1, dim: 'Pedagogik', text: 'Guru menjelaskan tujuan pembelajaran atau materi yang akan dipelajari di awal kelas.' },
    { no: 2, dim: 'Pedagogik', text: 'Guru menyampaikan materi dengan jelas dan menggunakan bahasa yang mudah saya pahami.' },
    { no: 3, dim: 'Pedagogik', text: 'Guru menggunakan media pembelajaran yang bervariasi (slide, video, alat praktikum, atau aplikasi belajar).' },
    { no: 4, dim: 'Pedagogik', text: 'Guru memberikan kesempatan kepada siswa untuk bertanya atau berdiskusi.' },
    { no: 5, dim: 'Pedagogik', text: 'Guru memberikan umpan balik (feedback) atau mengembalikan hasil tugas/ujian yang telah diperiksa.' },
    { no: 6, dim: 'Kepribadian & Sosial', text: 'Guru mengawali dan mengakhiri pembelajaran tepat waktu sesuai jadwal.' },
    { no: 7, dim: 'Kepribadian & Sosial', text: 'Guru bersikap adil dan tidak membeda-bedakan perlakuan kepada setiap siswa di kelas.' },
    { no: 8, dim: 'Kepribadian & Sosial', text: 'Guru menghargai pendapat, ide, atau jawaban dari siswa meskipun belum tepat.' },
    { no: 9, dim: 'Kepribadian & Sosial', text: 'Guru menciptakan suasana kelas yang aman, tertib, dan bebas dari ejekan/perundungan.' },
    { no: 10, dim: 'Kepribadian & Sosial', text: 'Guru mudah dihubungi atau diajak berkonsultasi jika siswa mengalami kesulitan belajar.' },
]

// --------------------------------------------------------------------------
// 3. Pengaturan Jadwal Pelaksanaan Survei (Buka/Tutup)
// --------------------------------------------------------------------------
const isModalJadwalOpen = ref(false)
const jadwalForm = useForm({
    id: props.activeSurvei?.id || '',
    judul_survei: props.activeSurvei?.judul_survei || 'Survei Evaluasi Kinerja Guru Semester Ganjil 2026/2027',
    deskripsi: props.activeSurvei?.deskripsi || 'Instrumen evaluasi kinerja mengajar, pedagogik, dan interaksi kelas oleh siswa.',
    sasaran_survei: props.activeSurvei?.sasaran_survei || 'Asesmen Siswa',
    tahun_ajaran: props.activeSurvei?.tahun_ajaran || '2026/2027',
    semester: props.activeSurvei?.semester || 'Ganjil',
    tanggal_mulai: props.activeSurvei?.tanggal_mulai ? props.activeSurvei.tanggal_mulai.split('T')[0] : new Date().toISOString().split('T')[0],
    tanggal_selesai: props.activeSurvei?.tanggal_selesai ? props.activeSurvei.tanggal_selesai.split('T')[0] : '',
    status: props.activeSurvei?.status || 'Aktif',
})

const openJadwalEditor = (survei) => {
    if (survei) {
        jadwalForm.id = survei.id
        jadwalForm.judul_survei = survei.judul_survei
        jadwalForm.deskripsi = survei.deskripsi || ''
        jadwalForm.sasaran_survei = survei.sasaran_survei || 'Asesmen Siswa'
        jadwalForm.tahun_ajaran = survei.tahun_ajaran || '2026/2027'
        jadwalForm.semester = survei.semester || 'Ganjil'
        jadwalForm.tanggal_mulai = survei.tanggal_mulai ? survei.tanggal_mulai.split('T')[0] : ''
        jadwalForm.tanggal_selesai = survei.tanggal_selesai ? survei.tanggal_selesai.split('T')[0] : ''
        jadwalForm.status = survei.status || 'Aktif'
    }
    isModalJadwalOpen.value = true
}

const submitJadwalForm = () => {
    if (jadwalForm.id) {
        jadwalForm.put(`/kepala-sekolah/survei-guru/${jadwalForm.id}`, {
            onSuccess: () => {
                isModalJadwalOpen.value = false
                alert('Jadwal pelaksanaan survei berhasil diperbarui!')
            }
        })
    } else {
        jadwalForm.post('/kepala-sekolah/survei-guru', {
            onSuccess: () => {
                isModalJadwalOpen.value = false
                alert('Kuesioner dan jadwal survei baru berhasil disimpan!')
            }
        })
    }
}

// --------------------------------------------------------------------------
// 4. Lembar Refleksi Individu Guru (Modal Tertutup Kepala Sekolah)
// --------------------------------------------------------------------------
const isRefleksiModalOpen = ref(false)
const isLoadingRefleksi = ref(false)
const refleksiData = ref(null)

const openLembarRefleksi = async (guruId) => {
    isLoadingRefleksi.value = true
    isRefleksiModalOpen.value = true
    refleksiData.value = null

    try {
        const response = await fetch(`/kepala-sekolah/survei-guru/refleksi/${guruId}`, {
            headers: { 'Accept': 'application/json' }
        })
        const result = await response.json()
        if (result.success) {
            refleksiData.value = result.data
        } else {
            alert(result.message || 'Gagal memuat lembar refleksi.')
            isRefleksiModalOpen.value = false
        }
    } catch (err) {
        alert('Terjadi kesalahan saat memuat data refleksi guru.')
        isRefleksiModalOpen.value = false
    } finally {
        isLoadingRefleksi.value = false
    }
}

const printRefleksiSheet = () => {
    window.print()
}

// --------------------------------------------------------------------------
// 5. Tambah Butir Pertanyaan Dinamis
// --------------------------------------------------------------------------
const isModalPertanyaanOpen = ref(false)
const pertanyaanForm = useForm({
    survei_id: props.activeSurvei?.id || null,
    dimensi: 'Pedagogik',
    nomor_urut: (props.pertanyaanList?.length || 10) + 1,
    pertanyaan: '',
    tipe_skala: 'likert_5',
})

const submitPertanyaan = () => {
    pertanyaanForm.post('/kepala-sekolah/survei-guru/pertanyaan', {
        onSuccess: () => {
            isModalPertanyaanOpen.value = false
            pertanyaanForm.reset()
        }
    })
}

const deletePertanyaan = (item) => {
    if (confirm(`Hapus butir pertanyaan nomor ${item.nomor_urut}?`)) {
        router.delete(`/kepala-sekolah/survei-guru/pertanyaan/${item.id}`, {
            preserveScroll: true
        })
    }
}

const getRaportBadge = (kat) => {
    switch (kat) {
        case 'Sangat Baik':
            return 'bg-emerald-100 text-emerald-800 border-emerald-300'
        case 'Baik':
            return 'bg-blue-100 text-blue-800 border-blue-300'
        case 'Cukup':
            return 'bg-amber-100 text-amber-800 border-amber-300'
        case 'Kurang':
            return 'bg-orange-100 text-orange-800 border-orange-300'
        case 'Sangat Kurang':
            return 'bg-rose-100 text-rose-800 border-rose-300'
        default:
            return 'bg-slate-100 text-slate-800 border-slate-300'
    }
}
</script>

<template>
    <AppLayout title="Kepala Sekolah - Survei Evaluasi Guru oleh Siswa">
        <Head title="Survei Evaluasi Guru oleh Siswa" />

        <div class="space-y-6">
            <!-- Super Admin Multi-Tenant Filter Bar -->
            <div v-if="isSuperAdmin" class="p-4 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                        <i class="bi bi-buildings"></i>
                    </div>
                    <div>
                        <div class="font-extrabold text-xs text-slate-800 uppercase tracking-wide flex items-center gap-2">
                            <span>Mode Platform Super Admin</span>
                            <span class="px-2 py-0.5 rounded text-[10px] bg-blue-600 text-white font-black">Multi-Tenant</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Filter data instrumen survei & raport evaluasi guru per unit sekolah:</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <select v-model="selectedTenant" @change="onTenantChange" 
                            class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 shadow-2xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 min-w-[220px]">
                        <option v-for="t in tenants || []" :key="t.id" :value="t.id">
                            {{ t.nama_sekolah }} ({{ t.npsn || 'NPSN -' }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Header Halaman & Action -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800">
                            MANAJEMEN KEPALA SEKOLAH
                        </span>
                        <span class="text-xs text-slate-400 font-medium">Skala Likert 5 Poin</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight mt-1">Survei Evaluasi Guru oleh Siswa</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Asesmen berkala kinerja pedagogik & interaksi kelas berbasis Skala Likert 5 Poin, Raport Guru, dan Lembar Refleksi.</p>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <button @click="openJadwalEditor(activeSurvei)" 
                            class="px-3.5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-xl border border-indigo-200 transition flex items-center gap-1.5">
                        <i class="bi bi-calendar-range-fill"></i>
                        <span>Atur Jadwal Survei</span>
                    </button>
                    <button @click="activeTab = 'form_siswa'" 
                            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2">
                        <i class="bi bi-pencil-square text-sm"></i>
                        <span>Isi Survei Siswa (Anonim)</span>
                    </button>
                </div>
            </div>

            <!-- Kartu Info Jadwal Pelaksanaan Aktif -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg"
                         :class="activeSurvei?.status === 'Aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-bold text-xs text-slate-800">{{ activeSurvei?.judul_survei || 'Survei Semester Ganjil' }}</h2>
                            <span class="px-2 py-0.5 rounded-full text-2xs font-black"
                                  :class="activeSurvei?.status === 'Aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                                {{ activeSurvei?.status === 'Aktif' ? 'Survei Dibuka' : 'Survei Ditutup' }}
                            </span>
                        </div>
                        <p class="text-2xs text-slate-400 mt-0.5">
                            Periode: {{ activeSurvei?.tanggal_mulai ? activeSurvei.tanggal_mulai.split('T')[0] : '-' }} s/d {{ activeSurvei?.tanggal_selesai ? activeSurvei.tanggal_selesai.split('T')[0] : 'Selesai Semester' }} (T.A. {{ activeSurvei?.tahun_ajaran || '2026/2027' }} {{ activeSurvei?.semester || 'Ganjil' }})
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="openJadwalEditor(activeSurvei)" class="text-2xs font-bold text-blue-600 hover:underline">
                        Ubah Jadwal & Status
                    </button>
                </div>
            </div>

            <!-- KPI Dashboard Cards (Skala 1.0 - 5.0 Likert) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Responden</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-800">{{ kpi?.total_respon || 0 }}</span>
                        <span class="text-2xs font-semibold text-slate-400">evaluasi siswa</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rata-Rata Sekolah</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                            <i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-amber-500">{{ kpi?.rata_rata_sekolah || '0.00' }}</span>
                        <span class="text-2xs font-semibold text-slate-500">/ 5.00 Likert</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kategori Sangat Baik</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="bi bi-award-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-emerald-600">{{ kpi?.guru_sangat_baik || 0 }}</span>
                        <span class="text-2xs font-semibold text-emerald-700">Skor 4.20 – 5.00</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Perlu Pembinaan</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-rose-600">{{ kpi?.guru_butuh_bina || 0 }}</span>
                        <span class="text-2xs font-semibold text-rose-700">Skor &lt; 2.60</span>
                    </div>
                </div>
            </div>

            <!-- Horizontal NavTabs 3-Way Scroller -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsSurveiDinamis')?.scrollBy({ left: -220, behavior: 'smooth' })"
                            title="Geser ke Kiri">
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsSurveiDinamis" role="tablist">
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'raport_guru' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'raport_guru'">
                                    <i class="bi bi-trophy-fill"></i>
                                    <span>Raport & Kategori Evaluasi Guru (Skala 1-5)</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'form_siswa' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'form_siswa'">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Portal Pengisian Survei Siswa (Anonim)</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'bank_pertanyaan' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'bank_pertanyaan'">
                                    <i class="bi bi-card-checklist"></i>
                                    <span>Bank 10 Indikator & Skala 5 Poin</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'umpan_balik' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'umpan_balik'">
                                    <i class="bi bi-chat-heart-fill"></i>
                                    <span>Feed Umpan Balik Kualitatif Siswa</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsSurveiDinamis')?.scrollBy({ left: 220, behavior: 'smooth' })"
                            title="Geser ke Kanan">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- TAB 1: RAPORT & KATEGORI EVALUASI GURU (SKALA 1-5) -->
            <div v-show="activeTab === 'raport_guru'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h2 class="text-xs font-bold text-slate-800">Raport Hasil Evaluasi Kinerja Guru oleh Siswa</h2>
                            <p class="text-2xs text-slate-400 mt-0.5">Klasifikasi otomatis skala Likert 5 Poin: Sangat Baik (4.2-5.0), Baik (3.4-4.19), Cukup (2.6-3.39), Kurang (1.8-2.59), Sangat Kurang (&lt;1.80).</p>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap text-2xs">
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">🟢 4.2-5.0 Sangat Baik</span>
                            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 font-bold">🔵 3.4-4.19 Baik</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold">🟡 2.6-3.39 Cukup</span>
                            <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold">🔴 &lt; 2.6 Perlu Pembinaan</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-3 px-4">Nama Guru Pendidik</th>
                                    <th class="py-3 px-4 text-center">Pedagogik (1-5)</th>
                                    <th class="py-3 px-4 text-center">Kepribadian & Sosial (1-5)</th>
                                    <th class="py-3 px-4 text-center">Rata-Rata Total</th>
                                    <th class="py-3 px-4 text-center">Kategori Raport</th>
                                    <th class="py-3 px-4 text-center">Responden</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                                <tr v-for="item in ratingSummary || []" :key="item.guru_id" class="hover:bg-blue-50/30 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800">{{ item.nama_guru }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-blue-700">
                                        {{ item.avg_pedagogik }} / 5.00
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-indigo-700">
                                        {{ item.avg_sosial_kepribadian }} / 5.00
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 font-black text-slate-800 text-sm">
                                            <i class="bi bi-star-fill text-amber-500 text-xs"></i>
                                            <span>{{ item.rating_keseluruhan }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-2xs font-black border" :class="getRaportBadge(item.kategori_raport)">
                                            {{ item.kategori_raport }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-semibold text-slate-500">
                                        {{ item.total_responden }} siswa
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button @click="openLembarRefleksi(item.guru_id)" 
                                                class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-2xs rounded-xl border border-indigo-200 transition inline-flex items-center gap-1.5">
                                            <i class="bi bi-file-earmark-person"></i>
                                            <span>Lembar Refleksi</span>
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="!ratingSummary || ratingSummary.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <i class="bi bi-clipboard-x text-4xl block mb-2 text-slate-300"></i>
                                        <p class="font-semibold text-xs">Belum ada evaluasi siswa yang masuk.</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Gunakan tab "Portal Pengisian Survei Siswa" untuk memulai pengisian.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PORTAL PENGISIAN SURVEI SISWA (ANONIM, SKALA LIKERT 5 POIN) -->
            <div v-show="activeTab === 'form_siswa'" class="space-y-4">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-2xs p-6 max-w-4xl mx-auto">
                    <!-- Header Form Siswa -->
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-2xs font-bold mb-2">
                            <i class="bi bi-shield-lock-fill"></i> 100% Anonim — Nama & NISN Tidak Direkam
                        </div>
                        <h2 class="text-base font-black text-slate-800">Formulir Evaluasi Kinerja Guru oleh Siswa</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Bantu Bapak/Ibu Guru meningkatkan mutu pembelajaran di kelas dengan memberikan penilaian yang objektif dan bertanggung jawab.</p>
                    </div>

                    <!-- Petunjuk Skala 5 Poin -->
                    <div class="mb-6 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                        <div class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="bi bi-info-circle-fill text-blue-600"></i>
                            <span>Pedoman Skala Penilaian 5 Poin:</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-2xs">
                            <div v-for="s in skalaOptions" :key="s.val" class="p-2.5 rounded-xl border bg-white space-y-1">
                                <div class="font-black" :class="s.val >= 4 ? 'text-blue-700' : (s.val === 3 ? 'text-amber-700' : 'text-rose-700')">
                                    Skor {{ s.val }} : {{ s.label }}
                                </div>
                                <p class="text-slate-500 text-[10px] leading-tight">{{ s.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitStudentSurvey" class="space-y-6">
                        <!-- BAGIAN A: IDENTITAS EVALUASI (DROP-DOWN) -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="bi bi-person-badge-fill text-blue-600"></i>
                                <span>A. Identitas Guru & Kelas yang Dinilai</span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">1. Kelas Kamu *</label>
                                    <select v-model="studentForm.nama_kelas" @change="onKelasSelected" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="">-- Pilih Kelas --</option>
                                        <option v-for="k in kelasSelector" :key="k.id" :value="k.nama_kelas">{{ k.nama_kelas }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">2. Mata Pelajaran *</label>
                                    <select v-model="studentForm.nama_mapel" @change="onMapelSelected" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="">-- Pilih Mata Pelajaran --</option>
                                        <option v-for="m in mapelSelector" :key="m.id" :value="m.nama_mata_pelajaran">{{ m.nama_mata_pelajaran }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">3. Nama Guru yang Dinilai *</label>
                                    <select v-model="studentForm.guru_id" @change="onGuruSelected" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="">-- Pilih Nama Guru --</option>
                                        <option v-for="g in guruSelector" :key="g.id" :value="g.id">{{ g.nama_lengkap }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN B: 10 INDIKATOR PENILAIAN (SKALA LIKERT 1 - 5) -->
                        <div class="space-y-4">
                            <!-- Dimensi I: Pedagogik (Butir 1 - 5) -->
                            <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 space-y-3">
                                <div class="text-xs font-black text-blue-900 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-md bg-blue-600 text-white flex items-center justify-center text-2xs">I</span>
                                    <span>Penyampaian Materi & Cara Mengajar (Pedagogik)</span>
                                </div>

                                <div class="space-y-2.5">
                                    <div v-for="q in default10Questions.slice(0, 5)" :key="'q-' + q.no" 
                                         class="p-3.5 bg-white rounded-xl border border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div class="text-xs font-medium text-slate-700 flex-1">
                                            <span class="font-bold text-blue-600 me-1.5">{{ q.no }}.</span>
                                            {{ q.text }}
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                                            <label v-for="s in skalaOptions" :key="s.val" 
                                                   class="px-2.5 py-1.5 rounded-lg border text-xs font-bold cursor-pointer transition select-none flex items-center gap-1"
                                                   :class="studentForm.skor_items[q.no - 1] === s.val ? s.activeColor : s.color"
                                                   :title="s.label + ' : ' + s.desc">
                                                <input type="radio" :name="'q_' + q.no" :value="s.val" v-model.number="studentForm.skor_items[q.no - 1]" class="hidden">
                                                <span>{{ s.val }}</span>
                                                <span class="text-[10px] hidden md:inline font-semibold">({{ s.label.split(' ')[0] }})</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dimensi II: Kepribadian & Sosial (Butir 6 - 10) -->
                            <div class="bg-emerald-50/50 p-4 rounded-2xl border border-emerald-100 space-y-3">
                                <div class="text-xs font-black text-emerald-900 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-md bg-emerald-600 text-white flex items-center justify-center text-2xs">II</span>
                                    <span>Interaksi & Suasana Kelas (Kepribadian & Sosial)</span>
                                </div>

                                <div class="space-y-2.5">
                                    <div v-for="q in default10Questions.slice(5, 10)" :key="'q-' + q.no" 
                                         class="p-3.5 bg-white rounded-xl border border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div class="text-xs font-medium text-slate-700 flex-1">
                                            <span class="font-bold text-emerald-600 me-1.5">{{ q.no }}.</span>
                                            {{ q.text }}
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                                            <label v-for="s in skalaOptions" :key="s.val" 
                                                   class="px-2.5 py-1.5 rounded-lg border text-xs font-bold cursor-pointer transition select-none flex items-center gap-1"
                                                   :class="studentForm.skor_items[q.no - 1] === s.val ? s.activeColor : s.color"
                                                   :title="s.label + ' : ' + s.desc">
                                                <input type="radio" :name="'q_' + q.no" :value="s.val" v-model.number="studentForm.skor_items[q.no - 1]" class="hidden">
                                                <span>{{ s.val }}</span>
                                                <span class="text-[10px] hidden md:inline font-semibold">({{ s.label.split(' ')[0] }})</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN C: UMPAN BALIK KUALITATIF -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="bi bi-chat-quote-fill text-indigo-600"></i>
                                <span>C. Umpan Balik Kualitatif (Pertanyaan Terbuka)</span>
                            </h3>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">
                                        1. Hal Positif: Apa hal yang paling kamu sukai dari cara mengajar Bapak/Ibu Guru ini? *
                                    </label>
                                    <textarea v-model="studentForm.umpan_balik_positif" rows="2" required 
                                              placeholder="Tuliskan hal-hal baik yang membuatmu bersemangat belajar di kelas ini..." 
                                              class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs"></textarea>
                                </div>

                                <div>
                                    <label class="block text-2xs font-bold text-slate-600 uppercase mb-1">
                                        2. Saran Perbaikan: Apa saran atau harapanmu untuk perbaikan cara mengajar di semester depan? *
                                    </label>
                                    <textarea v-model="studentForm.area_pengembangan" rows="2" required 
                                              placeholder="Tuliskan saran yang membangun untuk perbaikan proses belajar mengajar..." 
                                              class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <button type="button" @click="resetForNextTeacher" 
                                    class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                                Reset & Nilai Guru Lain di Kelas Ini
                            </button>
                            <button type="submit" :disabled="studentForm.processing || !studentForm.guru_id || !studentForm.nama_kelas" 
                                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition disabled:opacity-50 flex items-center justify-center gap-2">
                                <i class="bi bi-send-fill"></i>
                                <span>{{ studentForm.processing ? 'Mengirim...' : 'Kirim Penilaian Anonim' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 3: BANK 10 INDIKATOR & SKALA 5 POIN -->
            <div v-show="activeTab === 'bank_pertanyaan'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-xs font-bold text-slate-800">Daftar Indikator Penilaian Kinerja Guru (Skala Likert 5 Poin)</h2>
                            <p class="text-2xs text-slate-400 mt-0.5">Pertanyaan baku & kuesioner dinamis yang dapat disesuaikan per semester.</p>
                        </div>
                        <button @click="isModalPertanyaanOpen = true" class="px-3 py-1.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700">
                            + Tambah Indikator
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4 w-40">Dimensi</th>
                                    <th class="py-3 px-4">Pernyataan Indikator Penilaian</th>
                                    <th class="py-3 px-4 text-center">Skala</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                                <tr v-for="q in pertanyaanList || []" :key="q.id" class="hover:bg-slate-50/60">
                                    <td class="py-3 px-4 text-center font-bold text-slate-400">{{ q.nomor_urut }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-lg text-2xs font-bold" 
                                              :class="q.dimensi === 'Pedagogik' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                                            {{ q.dimensi }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-slate-800">{{ q.pertanyaan }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-slate-500">1 - 5 (Likert)</td>
                                    <td class="py-3 px-4 text-right">
                                        <button @click="deletePertanyaan(q)" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus Butir">
                                            <i class="bi bi-trash text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: FEED UMPAN BALIK KUALITATIF SISWA -->
            <div v-show="activeTab === 'umpan_balik'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-4 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                        <h2 class="text-xs font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-chat-heart-fill text-rose-500"></i>
                            <span>Semua Umpan Balik Positif & Saran Perbaikan Siswa</span>
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div v-for="fb in recentFeedback || []" :key="fb.id" class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-xs text-slate-800">{{ fb.nama_guru }}</span>
                                    <span class="text-2xs text-slate-400 block">{{ fb.nama_mapel || 'Umum' }} — Kelas {{ fb.nama_kelas || 'Reguler' }}</span>
                                </div>
                                <div class="px-2.5 py-1 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 text-xs font-black">
                                    {{ fb.rata_rata_skor }} / 5.0 ⭐
                                </div>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div>
                                    <span class="text-[10px] font-bold uppercase text-emerald-700 block">✨ Hal Positif yang Disukai:</span>
                                    <p class="text-slate-700 bg-emerald-50/60 p-2.5 rounded-xl border border-emerald-100 text-xs italic">
                                        "{{ fb.umpan_balik_positif || 'Cara mengajar sangat menyenangkan dan interaktif.' }}"
                                    </p>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase text-amber-700 block">💡 Saran & Harapan Semester Depan:</span>
                                    <p class="text-slate-700 bg-amber-50/60 p-2.5 rounded-xl border border-amber-100 text-xs italic">
                                        "{{ fb.area_pengembangan || 'Semoga dapat diperbanyak sesi tanya jawab dan kuis kelompok.' }}"
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="!recentFeedback || recentFeedback.length === 0" class="col-span-2 py-10 text-center text-slate-400 text-xs">
                            Belum ada umpan balik kualitatif yang masuk.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL ATUR JADWAL SURVEI (<Teleport to="body">) -->
        <Teleport to="body">
            <div v-if="isModalJadwalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-black text-slate-800">Atur Jadwal & Status Pelaksanaan Survei</h2>
                            <p class="text-2xs text-slate-400 mt-0.5">Tentukan periode kapan survei ditampilkan dan dapat diisi oleh siswa.</p>
                        </div>
                        <button @click="isModalJadwalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitJadwalForm" class="mt-4 space-y-3">
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Judul Kuesioner Survei *</label>
                            <input type="text" v-model="jadwalForm.judul_survei" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Tahun Ajaran</label>
                                <input type="text" v-model="jadwalForm.tahun_ajaran" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Semester</label>
                                <select v-model="jadwalForm.semester" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Tanggal Mulai Ditampilkan *</label>
                                <input type="date" v-model="jadwalForm.tanggal_mulai" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Tanggal Berakhir (Tutup)</label>
                                <input type="date" v-model="jadwalForm.tanggal_selesai" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                            </div>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Status Kuesioner *</label>
                            <select v-model="jadwalForm.status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="Aktif">🟢 Aktif (Dibuka untuk Siswa)</option>
                                <option value="Ditutup">🔴 Ditutup (Tidak Bisa Diisi)</option>
                                <option value="Draft">⚪ Draft (Konsep Internal)</option>
                            </select>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="isModalJadwalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold">
                                Batal
                            </button>
                            <button type="submit" :disabled="jadwalForm.processing" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition">
                                Simpan Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL LEMBAR REFLEKSI INDIVIDU GURU (<Teleport to="body">) -->
        <Teleport to="body">
            <div v-if="isRefleksiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
                <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 my-8 overflow-hidden">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                        <div>
                            <span class="text-2xs font-bold text-indigo-600 uppercase tracking-wider">Dokumen Rahasia & Tertutup</span>
                            <h2 class="text-lg font-black text-slate-800">Lembar Refleksi Individu Guru</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="printRefleksiSheet" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200 transition flex items-center gap-1.5">
                                <i class="bi bi-printer-fill"></i> Cetak Lembar
                            </button>
                            <button @click="isRefleksiModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div v-if="isLoadingRefleksi" class="py-12 text-center text-slate-400 text-xs">
                        <i class="bi bi-arrow-repeat animate-spin text-2xl block mb-2 text-indigo-600"></i>
                        Memuat data refleksi guru...
                    </div>

                    <div v-else-if="refleksiData" class="mt-4 space-y-5 text-xs">
                        <!-- Profil Singkat & Skor Header (Skala 1-5) -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h3 class="text-base font-black text-slate-800">{{ refleksiData.nama_guru }}</h3>
                                <p class="text-2xs text-slate-500 mt-0.5">Total Responden: {{ refleksiData.total_responden }} Siswa</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <div class="text-2xs text-slate-400 uppercase font-bold">Skor Rata-Rata</div>
                                    <div class="text-xl font-black text-slate-800">{{ refleksiData.rata_rata_total }} <span class="text-xs text-slate-400">/ 5.00</span></div>
                                </div>
                                <span class="px-3 py-1.5 rounded-xl text-xs font-black border" :class="getRaportBadge(refleksiData.predikat_kategori)">
                                    {{ refleksiData.predikat_kategori }}
                                </span>
                            </div>
                        </div>

                        <!-- Skor 2 Dimensi (Skala 1-5) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-blue-50/60 p-3.5 rounded-xl border border-blue-100">
                                <span class="text-2xs font-bold text-blue-700 uppercase block">Dimensi I: Pedagogik & Mengajar</span>
                                <span class="text-lg font-black text-blue-900">{{ refleksiData.avg_pedagogik }} / 5.00</span>
                            </div>
                            <div class="bg-emerald-50/60 p-3.5 rounded-xl border border-emerald-100">
                                <span class="text-2xs font-bold text-emerald-700 uppercase block">Dimensi II: Kepribadian & Sosial</span>
                                <span class="text-lg font-black text-emerald-900">{{ refleksiData.avg_sosial_kepribadian }} / 5.00</span>
                            </div>
                        </div>

                        <!-- Breakdown Rata-Rata 10 Indikator (Skala 1 - 5) -->
                        <div>
                            <h4 class="font-bold text-slate-700 mb-2">Rata-Rata Skor per Butir Indikator (Skala Likert 1 - 5):</h4>
                            <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-2xl border border-slate-200">
                                <div v-for="q in default10Questions" :key="'ind-' + q.no" class="flex items-center justify-between text-2xs py-1 border-b border-slate-200/60 last:border-0">
                                    <span class="text-slate-700 flex-1 me-2">{{ q.no }}. {{ q.text }}</span>
                                    <span class="font-black text-slate-800 shrink-0 px-2.5 py-0.5 rounded bg-white border border-slate-200">
                                        {{ refleksiData.indikator_averages?.[q.no] || '-' }} / 5.00
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Komparasi Antar Kelas -->
                        <div v-if="refleksiData.kelas_breakdown && refleksiData.kelas_breakdown.length > 0">
                            <h4 class="font-bold text-slate-700 mb-2">Perbandingan Skor Antar-Kelas yang Diajar:</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div v-for="kb in refleksiData.kelas_breakdown" :key="kb.nama_kelas" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-2xs">
                                    <div class="font-bold text-slate-800">{{ kb.nama_kelas }}</div>
                                    <div class="text-slate-500 mt-1">Skor: <span class="font-black text-slate-800">{{ kb.rata_rata_skor }}</span> ({{ kb.total_responden }} siswa)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Kumpulan Komentar Positif & Saran -->
                        <div class="space-y-3 pt-2">
                            <div>
                                <h4 class="font-bold text-emerald-800 mb-1.5">Apresiasi & Komentar Positif Siswa:</h4>
                                <div class="max-h-36 overflow-y-auto space-y-1 bg-emerald-50/40 p-3 rounded-xl border border-emerald-100 text-2xs italic">
                                    <div v-for="(txt, idx) in refleksiData.umpan_balik_positif || []" :key="'pos-' + idx" class="text-slate-700">
                                        • "{{ txt }}"
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-bold text-amber-800 mb-1.5">Saran & Harapan Perbaikan Siswa:</h4>
                                <div class="max-h-36 overflow-y-auto space-y-1 bg-amber-50/40 p-3 rounded-xl border border-amber-100 text-2xs italic">
                                    <div v-for="(txt, idx) in refleksiData.saran_perbaikan || []" :key="'sar-' + idx" class="text-slate-700">
                                        • "{{ txt }}"
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-200 flex justify-end">
                        <button @click="isRefleksiModalOpen = false" class="px-5 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition">
                            Tutup Lembar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL TAMBAH BUTIR PERTANYAAN (<Teleport to="body">) -->
        <Teleport to="body">
            <div v-if="isModalPertanyaanOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-base font-black text-slate-800">Tambah Indikator Pertanyaan Baru</h2>
                        <button @click="isModalPertanyaanOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitPertanyaan" class="mt-4 space-y-3">
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Dimensi Kompetensi *</label>
                            <select v-model="pertanyaanForm.dimensi" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                                <option value="Pedagogik">Pedagogik (Cara Mengajar)</option>
                                <option value="Kepribadian & Sosial">Kepribadian & Sosial (Interaksi Kelas)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Nomor Urut *</label>
                            <input type="number" min="1" v-model.number="pertanyaanForm.nomor_urut" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Teks Pernyataan Indikator *</label>
                            <textarea v-model="pertanyaanForm.pertanyaan" rows="3" required placeholder="Contoh: Guru memberikan bimbingan khusus bagi siswa yang mengalami kesulitan..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs"></textarea>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="isModalPertanyaanOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold">
                                Batal
                            </button>
                            <button type="submit" :disabled="pertanyaanForm.processing || !pertanyaanForm.pertanyaan" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition disabled:opacity-50">
                                Simpan Indikator
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
