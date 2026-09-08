<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, usePage, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, watch } from 'vue'

const props = defineProps({
  siswa: { type: Object, default: () => ({}) },
  academicOptions: { type: Object, default: () => ({}) },
  provinces: { type: Array, default: () => [] },
  tenants: { type: Array, default: () => [] },
  isCreate: { type: Boolean, default: false },
  userRole: { type: String, default: 'admin_sekolah' },
})

const page = usePage()
const flashSuccess = computed(() => page.props.flash?.success)

// Wizard Steps
const currentStep = ref(1)
const stepNames = [
  'Data Pokok & Akademik',
  'Detail Alamat & Kontak',
  'Fisik, Riwayat & Bantuan',
  'Data Orang Tua / Wali',
  'Registrasi & Berkas Upload',
]

// Sub-tab Orang Tua
const activeParentTab = ref('father')

// Geographic options state
const kotaList = ref([])
const kecamatanList = ref([])
const kelurahanList = ref([])
const allKotaList = ref([])
const loadingKota = ref(false)
const loadingKecamatan = ref(false)
const loadingKelurahan = ref(false)

// File Previews & Selected file names
const filePreviews = ref({})
const filesSelected = ref({})

// Document Viewer Modal State
const showDocModal = ref(false)
const docModalTitle = ref('')
const docModalUrl = ref('')
const isDocModalPdf = computed(() => docModalUrl.value?.toLowerCase().endsWith('.pdf') || docModalUrl.value?.includes('application/pdf'))

// Form State with all fields 1:1
const form = useForm({
  id: props.siswa?.id || '',
  tenant_id: props.siswa?.tenant_id || '',

  // Step 1: Identitas & Akademik
  nik: props.siswa?.nik || '',
  no_kk: props.siswa?.no_kk || '',
  nisn: props.siswa?.nisn || '',
  nis: props.siswa?.nis || '',
  password: '',
  nama_lengkap: props.siswa?.nama_lengkap || '',
  nama_panggilan: props.siswa?.nama_panggilan || '',
  jenis_kelamin: props.siswa?.jenis_kelamin || 'L',
  agama: props.siswa?.agama || 'Islam',
  kewarganegaraan: props.siswa?.kewarganegaraan || 'WNI',
  bahasa_sehari_hari: props.siswa?.bahasa_sehari_hari || 'Indonesia',
  tempat_lahir: props.siswa?.tempat_lahir || '',
  tanggal_lahir: props.siswa?.tanggal_lahir ? String(props.siswa.tanggal_lahir).substring(0, 10) : '',
  sekolah_asal: props.siswa?.sekolah_asal || '',
  no_ijazah_sebelumnya: props.siswa?.no_ijazah_sebelumnya || '',
  tanggal_ijazah_sebelumnya: props.siswa?.tanggal_ijazah_sebelumnya ? String(props.siswa.tanggal_ijazah_sebelumnya).substring(0, 10) : '',
  lama_belajar_sebelumnya: props.siswa?.lama_belajar_sebelumnya || 3,
  status: props.siswa?.status || props.siswa?.status_siswa || 'Aktif',
  id_angkatan: props.siswa?.id_angkatan || '',
  id_tahun_ajaran: props.siswa?.id_tahun_ajaran || '',
  id_jenjang: props.siswa?.id_jenjang || '',
  id_jurusan: props.siswa?.id_jurusan || '',
  id_kelas: props.siswa?.id_kelas || '',
  id_pendidikan: props.siswa?.id_pendidikan || '',
  ukuran_seragam_sekolah: props.siswa?.ukuran_seragam_sekolah || '',
  ukuran_seragam_olahraga: props.siswa?.ukuran_seragam_olahraga || '',

  // Step 2: Alamat & Kontak
  alamat_kk: props.siswa?.alamat_kk || '',
  alamat_domisili: props.siswa?.alamat_domisili || '',
  rt: props.siswa?.rt || '001',
  rw: props.siswa?.rw || '001',
  kode_pos: props.siswa?.kode_pos || '',
  id_provinsi: props.siswa?.id_provinsi || '',
  id_kota: props.siswa?.id_kota || '',
  id_kecamatan: props.siswa?.id_kecamatan || '',
  id_kelurahan: props.siswa?.id_kelurahan || '',
  status_tinggal: props.siswa?.status_tinggal || 'Milik Sendiri',
  tinggal_dengan: props.siswa?.tinggal_dengan || 'Orang Tua',
  email: props.siswa?.email || '',
  no_telepon_rumah: props.siswa?.no_telepon_rumah || '',
  no_telepon_siswa: props.siswa?.no_telepon_siswa || '',
  no_telepon_orang_tua: props.siswa?.no_telepon_orang_tua || '',

  // Step 3: Fisik & Bantuan
  tinggi_badan: props.siswa?.tinggi_badan || '',
  berat_badan: props.siswa?.berat_badan || '',
  lingkar_kepala: props.siswa?.lingkar_kepala || '',
  golongan_darah: props.siswa?.golongan_darah || 'O',
  anak_ke: props.siswa?.anak_ke || 1,
  jumlah_saudara: props.siswa?.jumlah_saudara ?? 0,
  saudara_tiri: props.siswa?.saudara_tiri ?? 0,
  saudara_angkat: props.siswa?.saudara_angkat ?? 0,
  penyakit_yang_diderita: props.siswa?.penyakit_yang_diderita || '',
  kelainan_jasmani: props.siswa?.kelainan_jasmani || 'Tidak Ada',
  jarak_rumah: props.siswa?.jarak_rumah || 1000,
  transportasi: props.siswa?.transportasi || 'Motor',
  status_anak: props.siswa?.status_anak || 'Bukan Yatim/Piatu',
  penerima_kps: props.siswa?.penerima_kps ? 1 : 0,
  punya_kip: props.siswa?.punya_kip ? 1 : 0,
  layak_kip: props.siswa?.layak_kip ? 1 : 0,
  no_kip: props.siswa?.no_kip || '',
  alasan_layak: props.siswa?.alasan_layak || '',
  kesehatan: props.siswa?.kesehatan || {
    1: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
    2: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
    3: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
    4: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
    5: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
    6: { tinggi_badan: '', berat_badan: '', pendengaran: '', pengelihatan: '', gigi: '' },
  },

  // Step 4: Orang Tua
  // Ayah
  nik_ayah: props.siswa?.nik_ayah || '',
  nama_ayah: props.siswa?.nama_ayah || '',
  id_tempat_lahir_ayah: props.siswa?.id_tempat_lahir_ayah || '',
  tempat_lahir_ayah: props.siswa?.tempat_lahir_ayah || '',
  tanggal_lahir_ayah: props.siswa?.tanggal_lahir_ayah ? String(props.siswa.tanggal_lahir_ayah).substring(0, 10) : '',
  kewarganegaraan_ayah: props.siswa?.kewarganegaraan_ayah || 'WNI',
  status_hidup_ayah: props.siswa?.status_hidup_ayah || 'Hidup',
  pendidikan_ayah: props.siswa?.pendidikan_ayah || 'SMA',
  pekerjaan_ayah: props.siswa?.pekerjaan_ayah || '',
  penghasilan_ayah: props.siswa?.penghasilan_ayah || 'Rp2.000.000 sampai Rp4.999.999',
  agama_ayah: props.siswa?.agama_ayah || 'Islam',

  // Ibu
  nik_ibu: props.siswa?.nik_ibu || '',
  nama_ibu: props.siswa?.nama_ibu || '',
  id_tempat_lahir_ibu: props.siswa?.id_tempat_lahir_ibu || '',
  tempat_lahir_ibu: props.siswa?.tempat_lahir_ibu || '',
  tanggal_lahir_ibu: props.siswa?.tanggal_lahir_ibu ? String(props.siswa.tanggal_lahir_ibu).substring(0, 10) : '',
  kewarganegaraan_ibu: props.siswa?.kewarganegaraan_ibu || 'WNI',
  status_hidup_ibu: props.siswa?.status_hidup_ibu || 'Hidup',
  pendidikan_ibu: props.siswa?.pendidikan_ibu || 'SMA',
  pekerjaan_ibu: props.siswa?.pekerjaan_ibu || '',
  penghasilan_ibu: props.siswa?.penghasilan_ibu || 'Tidak Berpenghasilan',
  agama_ibu: props.siswa?.agama_ibu || 'Islam',

  // Wali
  nik_wali: props.siswa?.nik_wali || '',
  nama_wali: props.siswa?.nama_wali || '',
  id_tempat_lahir_wali: props.siswa?.id_tempat_lahir_wali || '',
  tempat_lahir_wali: props.siswa?.tempat_lahir_wali || '',
  tanggal_lahir_wali: props.siswa?.tanggal_lahir_wali ? String(props.siswa.tanggal_lahir_wali).substring(0, 10) : '',
  kewarganegaraan_wali: props.siswa?.kewarganegaraan_wali || 'WNI',
  hubungan_wali: props.siswa?.hubungan_wali || '',
  pendidikan_wali: props.siswa?.pendidikan_wali || '',
  pekerjaan_wali: props.siswa?.pekerjaan_wali || '',
  penghasilan_wali: props.siswa?.penghasilan_wali || '',
  agama_wali: props.siswa?.agama_wali || 'Islam',

  // Step 5: Registrasi & Keluar
  jenis_pendaftaran: props.siswa?.jenis_pendaftaran || 'Siswa Baru',
  jalur_diterima: props.siswa?.jalur_diterima || 'Zonasi',
  tanggal_masuk: props.siswa?.tanggal_masuk ? String(props.siswa.tanggal_masuk).substring(0, 10) : new Date().toISOString().substring(0, 10),
  hobi: props.siswa?.hobi || 'Membaca',
  paud_formal: props.siswa?.paud_formal ? 1 : 0,
  paud_non_formal: props.siswa?.paud_non_formal ? 1 : 0,
  sekolah_asal_mutasi: props.siswa?.sekolah_asal_mutasi || '',
  pindah_dari_tingkat: props.siswa?.pindah_dari_tingkat || '',
  pindah_no_surat: props.siswa?.pindah_no_surat || '',
  keluar_karena: props.siswa?.keluar_karena || '',
  tanggal_keluar: props.siswa?.tanggal_keluar ? String(props.siswa.tanggal_keluar).substring(0, 10) : '',
  alasan_keluar: props.siswa?.alasan_keluar || '',
  sekolah_tujuan: props.siswa?.sekolah_tujuan || '',
  nomor_skp: props.siswa?.nomor_skp || '',
  tingkat_ditinggalkan: props.siswa?.tingkat_ditinggalkan || '',
  diterima_di_tingkat: props.siswa?.diterima_di_tingkat || '',
  nomor_ijazah_kelulusan: props.siswa?.nomor_ijazah_kelulusan || '',
  nomor_skl: props.siswa?.nomor_skl || '',
  keterangan_setelah_lulus: props.siswa?.keterangan_setelah_lulus || '',

  // Dokumen Files (Uploaded File Objects)
  foto_profil: null,
  berkas_kk: null,
  berkas_akta: null,
  berkas_ijazah_sd: null,
  berkas_ijazah_smp: null,
  berkas_ijazah_sma: null,
  berkas_mutasi_masuk: null,
  berkas_mutasi_keluar: null,
  berkas_kip: null,
  berkas_pernyataan_baru: null,
  berkas_pernyataan_tka: null,
})

// Simpan existing document URLs dari backend
const existingDocs = ref({
  foto_profil: props.siswa?.foto_profil || '',
  berkas_kk: props.siswa?.berkas_kk || '',
  berkas_akta: props.siswa?.berkas_akta || '',
  berkas_ijazah_sd: props.siswa?.berkas_ijazah_sd || '',
  berkas_ijazah_smp: props.siswa?.berkas_ijazah_smp || '',
  berkas_ijazah_sma: props.siswa?.berkas_ijazah_sma || '',
  berkas_mutasi_masuk: props.siswa?.berkas_mutasi_masuk || '',
  berkas_mutasi_keluar: props.siswa?.berkas_mutasi_keluar || '',
  berkas_kip: props.siswa?.berkas_kip || '',
  berkas_pernyataan_baru: props.siswa?.berkas_pernyataan_baru || '',
  berkas_pernyataan_tka: props.siswa?.berkas_pernyataan_tka || '',
})

// Reactive filtered Jurusan based on Jenjang
const filteredJurusan = computed(() => {
  const allJurusan = props.academicOptions?.jurusan || []
  const allKelas = props.academicOptions?.kelas || []
  if (!form.id_jenjang) return allJurusan
  const allowedJurusanIds = allKelas
    .filter(k => String(k.id_jenjang) === String(form.id_jenjang))
    .map(k => String(k.id_jurusan))
  if (allowedJurusanIds.length === 0) return allJurusan
  return allJurusan.filter(j => allowedJurusanIds.includes(String(j.id)))
})

// Reactive filtered Kelas based on Jenjang & Jurusan
const filteredKelas = computed(() => {
  const allKelas = props.academicOptions?.kelas || []
  if (!form.id_jenjang) return allKelas
  return allKelas.filter(k => {
    const matchJenjang = String(k.id_jenjang) === String(form.id_jenjang)
    const matchJurusan = !form.id_jurusan || String(k.id_jurusan) === String(form.id_jurusan)
    return matchJenjang && matchJurusan
  })
})

// Fetch Kota on Provinsi change
const onProvinsiChange = async () => {
  form.id_kota = ''
  form.id_kecamatan = ''
  form.id_kelurahan = ''
  kotaList.value = []
  kecamatanList.value = []
  kelurahanList.value = []
  if (!form.id_provinsi) return
  loadingKota.value = true
  try {
    const res = await fetch(`/wilayah/kota/${form.id_provinsi}`)
    kotaList.value = await res.json()
  } catch (e) {
    console.error('Failed to load kota:', e)
  } finally {
    loadingKota.value = false
  }
}

// Fetch Kecamatan on Kota change
const onKotaChange = async () => {
  form.id_kecamatan = ''
  form.id_kelurahan = ''
  kecamatanList.value = []
  kelurahanList.value = []
  if (!form.id_kota) return
  loadingKecamatan.value = true
  try {
    const res = await fetch(`/wilayah/kecamatan/${form.id_kota}`)
    kecamatanList.value = await res.json()
  } catch (e) {
    console.error('Failed to load kecamatan:', e)
  } finally {
    loadingKecamatan.value = false
  }
}

// Fetch Kelurahan on Kecamatan change
const onKecamatanChange = async () => {
  form.id_kelurahan = ''
  kelurahanList.value = []
  if (!form.id_kecamatan) return
  loadingKelurahan.value = true
  try {
    const res = await fetch(`/wilayah/kelurahan/${form.id_kecamatan}`)
    kelurahanList.value = await res.json()
  } catch (e) {
    console.error('Failed to load kelurahan:', e)
  } finally {
    loadingKelurahan.value = false
  }
}

// Initialize geographic dropdowns if editing
onMounted(async () => {
  // Fetch initial all-cities list for birthplace
  try {
    const resAll = await fetch('/wilayah/semua-kota')
    allKotaList.value = await resAll.json()
  } catch (e) {
    console.error('Failed to load all kota:', e)
  }

  // If editing and has id_provinsi
  if (form.id_provinsi) {
    try {
      const resK = await fetch(`/wilayah/kota/${form.id_provinsi}`)
      kotaList.value = await resK.json()
    } catch (e) {}
  }
  if (form.id_kota) {
    try {
      const resKec = await fetch(`/wilayah/kecamatan/${form.id_kota}`)
      kecamatanList.value = await resKec.json()
    } catch (e) {}
  }
  if (form.id_kecamatan) {
    try {
      const resKel = await fetch(`/wilayah/kelurahan/${form.id_kecamatan}`)
      kelurahanList.value = await resKel.json()
    } catch (e) {}
  }
})

// State for file compression & feedback
const compressingFiles = ref({})
const compressionStats = ref({})

// Client-side smart image compressor (Canvas to WebP/JPEG under 500 KB)
const compressImageClient = (file, maxSizeBytes = 500 * 1024, maxWidth = 1600) => {
  return new Promise((resolve) => {
    if (!file || !file.type.startsWith('image/')) {
      resolve({ file, originalSize: file ? file.size : 0, compressedSize: file ? file.size : 0, wasCompressed: false })
      return
    }

    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = (event) => {
      const img = new Image()
      img.src = event.target.result
      img.onload = () => {
        let width = img.width
        let height = img.height

        if (width > maxWidth) {
          height = Math.round((height * maxWidth) / width)
          width = maxWidth
        }

        const canvas = document.createElement('canvas')
        const ctx = canvas.getContext('2d')

        let quality = 0.85
        const tryCompress = (q, w, h) => {
          canvas.width = w
          canvas.height = h
          ctx.fillStyle = '#FFFFFF'
          ctx.fillRect(0, 0, w, h)
          ctx.drawImage(img, 0, 0, w, h)

          canvas.toBlob(
            (blob) => {
              if (!blob) {
                resolve({ file, originalSize: file.size, compressedSize: file.size, wasCompressed: false })
                return
              }
              if (blob.size <= maxSizeBytes || (q <= 0.35 && w <= 400)) {
                const cleanName = file.name.replace(/\.[^/.]+$/, '') + '.webp'
                const convertedFile = new File([blob], cleanName, {
                  type: 'image/webp',
                  lastModified: Date.now(),
                })
                resolve({
                  file: convertedFile,
                  originalSize: file.size,
                  compressedSize: blob.size,
                  wasCompressed: file.size > maxSizeBytes || blob.size < file.size,
                })
              } else {
                const nextQ = Math.max(0.35, q - 0.15)
                const nextW = Math.round(w * 0.85)
                const nextH = Math.round(h * 0.85)
                tryCompress(nextQ, nextW, nextH)
              }
            },
            'image/webp',
            q
          )
        }

        tryCompress(quality, width, height)
      }
      img.onerror = () => resolve({ file, originalSize: file.size, compressedSize: file.size, wasCompressed: false })
    }
    reader.onerror = () => resolve({ file, originalSize: file.size, compressedSize: file.size, wasCompressed: false })
  })
}

// File upload handler with automatic client-side compression
const onFileChange = async (event, fieldKey) => {
  const file = event.target.files[0]
  if (!file) return

  // Max raw file limit check (10 MB)
  if (file.size > 10 * 1024 * 1024) {
    alert(`Ukuran berkas "${file.name}" (${(file.size / (1024 * 1024)).toFixed(1)} MB) melebihi batas maksimal 10 MB! Harap pilih berkas yang lebih kecil.`)
    event.target.value = ''
    return
  }

  compressingFiles.value[fieldKey] = true

  try {
    if (file.type.startsWith('image/')) {
      // Auto-compress image to WebP under 500 KB
      const result = await compressImageClient(file, 500 * 1024, 1600)
      const finalFile = result.file
      form[fieldKey] = finalFile

      const origKb = Math.round(result.originalSize / 1024)
      const compKb = Math.round(result.compressedSize / 1024)
      filesSelected.value[fieldKey] = finalFile.name
      compressionStats.value[fieldKey] = result.wasCompressed
        ? `${origKb} KB ➔ ${compKb} KB (Auto-Compress)`
        : `${compKb} KB`

      // Generate preview DataURL
      const reader = new FileReader()
      reader.onload = (e) => {
        filePreviews.value[fieldKey] = e.target.result
      }
      reader.readAsDataURL(finalFile)
    } else {
      // PDF or other documents
      form[fieldKey] = file
      const sizeKb = Math.round(file.size / 1024)
      filesSelected.value[fieldKey] = file.name
      compressionStats.value[fieldKey] = `${sizeKb} KB`
      filePreviews.value[fieldKey] = null
    }
  } catch (err) {
    console.error('File compression error:', err)
    form[fieldKey] = file
    filesSelected.value[fieldKey] = file.name
  } finally {
    compressingFiles.value[fieldKey] = false
  }
}

// Open modal document viewer
const openDocViewer = (url, title) => {
  docModalTitle.value = title
  docModalUrl.value = url
  showDocModal.value = true
}

// Validation Engine & Error State
const clientErrors = ref({})
const showValidationModal = ref(false)
const validationAlert = ref({
  show: false,
  step: 1,
  title: '',
  message: '',
  missingFields: [],
})

const fieldLabels = {
  // Step 1
  nik: 'NIK Siswa (16 Digit)',
  no_kk: 'No. KK',
  nisn: 'NISN (10 Digit)',
  nis: 'NIS',
  nama_lengkap: 'Nama Lengkap Siswa',
  nama_panggilan: 'Nama Panggilan',
  jenis_kelamin: 'Jenis Kelamin',
  tempat_lahir: 'Tempat Lahir Siswa',
  tanggal_lahir: 'Tanggal Lahir Siswa',
  agama: 'Agama',
  kewarganegaraan: 'Kewarganegaraan',
  bahasa_sehari_hari: 'Bahasa Sehari-hari',
  status: 'Status Siswa',
  id_angkatan: 'Tahun Angkatan',
  id_tahun_ajaran: 'Tahun Ajaran',
  id_jenjang: 'Jenjang Pendidikan',
  id_jurusan: 'Jurusan',
  id_kelas: 'Rombel / Kelas',

  // Step 2
  alamat_kk: 'Alamat Sesuai KK',
  alamat_domisili: 'Alamat Domisili',
  rt: 'RT',
  rw: 'RW',
  kode_pos: 'Kode Pos (5 Digit)',
  id_provinsi: 'Provinsi',
  id_kota: 'Kabupaten / Kota',
  id_kecamatan: 'Kecamatan',
  id_kelurahan: 'Kelurahan',
  status_tinggal: 'Status Kepemilikan Tinggal',
  tinggal_dengan: 'Tinggal Bersama',
  email: 'Email Siswa',
  no_telepon_siswa: 'No. HP / WhatsApp Siswa',

  // Step 3
  tinggi_badan: 'Tinggi Badan (cm)',
  berat_badan: 'Berat Badan (kg)',
  lingkar_kepala: 'Lingkar Kepala (cm)',
  golongan_darah: 'Golongan Darah',
  anak_ke: 'Anak Ke-',
  jumlah_saudara: 'Jumlah Saudara Kandung',
  kelainan_jasmani: 'Kelainan Jasmani / Disabilitas',
  jarak_rumah: 'Jarak Rumah ke Sekolah',
  transportasi: 'Alat Transportasi',
  no_kip: 'Nomor KIP',
  alasan_layak: 'Alasan Layak KIP',

  // Step 4
  nik_ibu: 'NIK Ibu Kandung (16 Digit)',
  nama_ibu: 'Nama Lengkap Ibu Kandung',
  tempat_lahir_ibu: 'Tempat Lahir Ibu Kandung',
  tanggal_lahir_ibu: 'Tanggal Lahir Ibu Kandung',
  pendidikan_ibu: 'Pendidikan Terakhir Ibu',
  pekerjaan_ibu: 'Pekerjaan Ibu',
  penghasilan_ibu: 'Penghasilan Bulanan Ibu',
  agama_ibu: 'Agama Ibu',

  // Step 5
  jenis_pendaftaran: 'Jenis Pendaftaran',
  tanggal_masuk: 'Tanggal Masuk / Terdaftar',
  hobi: 'Hobi Siswa',
  sekolah_asal_mutasi: 'Nama Sekolah Asal Mutasi',
}

// Step Fields mapping
const stepFields = [
  ['nik', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'kewarganegaraan', 'bahasa_sehari_hari', 'status', 'id_angkatan', 'id_tahun_ajaran', 'id_jenjang', 'id_jurusan', 'id_kelas'],
  ['alamat_kk', 'alamat_domisili', 'rt', 'rw', 'kode_pos', 'id_provinsi', 'id_kota', 'id_kecamatan', 'id_kelurahan', 'status_tinggal', 'tinggal_dengan', 'email', 'no_telepon_siswa'],
  ['tinggi_badan', 'berat_badan', 'lingkar_kepala', 'golongan_darah', 'anak_ke', 'jumlah_saudara', 'kelainan_jasmani', 'jarak_rumah', 'transportasi', 'no_kip', 'alasan_layak'],
  ['nik_ibu', 'nama_ibu', 'tempat_lahir_ibu', 'tanggal_lahir_ibu', 'pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu', 'agama_ibu'],
  ['jenis_pendaftaran', 'tanggal_masuk', 'hobi', 'sekolah_asal_mutasi'],
]

// All combined errors (Client + Server)
const allErrors = computed(() => {
  const merged = { ...clientErrors.value }
  const serverErrs = form.errors || {}
  for (const k in serverErrs) {
    if (serverErrs[k]) merged[k] = serverErrs[k]
  }
  return merged
})

const stepErrorCount = computed(() => {
  const errs = allErrors.value
  return stepFields.map((fields) => fields.filter((f) => errs[f]).length)
})

const hasAnyError = computed(() => Object.keys(allErrors.value).length > 0)

// Validate single step
const validateStep = (stepNumber) => {
  const fieldsInStep = stepFields[stepNumber - 1] || []
  fieldsInStep.forEach((f) => {
    delete clientErrors.value[f]
    if (form.errors && form.errors[f]) delete form.errors[f]
  })

  if (stepNumber === 1) {
    if (!form.nik || String(form.nik).trim() === '') {
      clientErrors.value.nik = 'NIK (Nomor Induk Kependudukan) wajib diisi.'
    } else if (String(form.nik).trim().length !== 16) {
      clientErrors.value.nik = 'NIK harus tepat 16 digit angka.'
    }

    if (!form.nisn || String(form.nisn).trim() === '') {
      clientErrors.value.nisn = 'NISN wajib diisi.'
    } else if (String(form.nisn).trim().length !== 10) {
      clientErrors.value.nisn = 'NISN harus tepat 10 digit angka.'
    }

    if (!form.nama_lengkap || String(form.nama_lengkap).trim().length < 3) {
      clientErrors.value.nama_lengkap = 'Nama lengkap siswa wajib diisi (minimal 3 karakter).'
    }
    if (!form.jenis_kelamin) clientErrors.value.jenis_kelamin = 'Pilih jenis kelamin.'
    if (!form.tempat_lahir || String(form.tempat_lahir).trim() === '') clientErrors.value.tempat_lahir = 'Tempat lahir wajib diisi.'
    if (!form.tanggal_lahir) clientErrors.value.tanggal_lahir = 'Tanggal lahir wajib diisi.'
    if (!form.agama) clientErrors.value.agama = 'Pilih agama siswa.'
    if (!form.kewarganegaraan) clientErrors.value.kewarganegaraan = 'Pilih kewarganegaraan.'
    if (!form.bahasa_sehari_hari || String(form.bahasa_sehari_hari).trim() === '') clientErrors.value.bahasa_sehari_hari = 'Bahasa sehari-hari wajib diisi.'
    if (!form.status) clientErrors.value.status = 'Status siswa wajib dipilih.'
    if (!form.id_angkatan) clientErrors.value.id_angkatan = 'Pilih tahun angkatan.'
    if (!form.id_tahun_ajaran) clientErrors.value.id_tahun_ajaran = 'Pilih tahun ajaran.'
    if (!form.id_jenjang) clientErrors.value.id_jenjang = 'Pilih jenjang pendidikan.'
    if (!form.id_jurusan) clientErrors.value.id_jurusan = 'Pilih jurusan.'
    if (!form.id_kelas) clientErrors.value.id_kelas = 'Pilih rombel / kelas.'
  } else if (stepNumber === 2) {
    if (!form.alamat_kk || String(form.alamat_kk).trim().length < 5) clientErrors.value.alamat_kk = 'Alamat sesuai KK wajib diisi (minimal 5 karakter).'
    if (!form.alamat_domisili || String(form.alamat_domisili).trim().length < 5) clientErrors.value.alamat_domisili = 'Alamat domisili wajib diisi (minimal 5 karakter).'
    if (!form.rt || String(form.rt).trim() === '') clientErrors.value.rt = 'RT wajib diisi.'
    if (!form.rw || String(form.rw).trim() === '') clientErrors.value.rw = 'RW wajib diisi.'
    if (!form.kode_pos || String(form.kode_pos).trim() === '') {
      clientErrors.value.kode_pos = 'Kode pos wajib diisi.'
    } else if (String(form.kode_pos).trim().length !== 5) {
      clientErrors.value.kode_pos = 'Kode pos harus 5 digit angka.'
    }
    if (!form.id_provinsi) clientErrors.value.id_provinsi = 'Pilih provinsi.'
    if (!form.id_kota) clientErrors.value.id_kota = 'Pilih kabupaten/kota.'
    if (!form.id_kecamatan) clientErrors.value.id_kecamatan = 'Pilih kecamatan.'
    if (!form.id_kelurahan) clientErrors.value.id_kelurahan = 'Pilih kelurahan.'
    if (!form.status_tinggal) clientErrors.value.status_tinggal = 'Pilih status tinggal.'
    if (!form.tinggal_dengan) clientErrors.value.tinggal_dengan = 'Pilih tinggal bersama siapa.'
    if (!form.email || String(form.email).trim() === '') {
      clientErrors.value.email = 'Email siswa wajib diisi.'
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
      clientErrors.value.email = 'Format email tidak valid (contoh: siswa@sekolah.sch.id).'
    }
    if (!form.no_telepon_siswa || String(form.no_telepon_siswa).trim().length < 10) {
      clientErrors.value.no_telepon_siswa = 'Nomor HP/WhatsApp siswa wajib diisi (minimal 10 digit).'
    }
  } else if (stepNumber === 3) {
    if (!form.tinggi_badan || Number(form.tinggi_badan) <= 0) clientErrors.value.tinggi_badan = 'Tinggi badan wajib diisi (cm).'
    if (!form.berat_badan || Number(form.berat_badan) <= 0) clientErrors.value.berat_badan = 'Berat badan wajib diisi (kg).'
    if (!form.lingkar_kepala || Number(form.lingkar_kepala) <= 0) clientErrors.value.lingkar_kepala = 'Lingkar kepala wajib diisi (cm).'
    if (!form.golongan_darah) clientErrors.value.golongan_darah = 'Pilih golongan darah.'
    if (form.anak_ke === '' || form.anak_ke === null || Number(form.anak_ke) < 1) clientErrors.value.anak_ke = 'Urutan anak ke- berapa wajib diisi.'
    if (form.jumlah_saudara === '' || form.jumlah_saudara === null || Number(form.jumlah_saudara) < 0) clientErrors.value.jumlah_saudara = 'Jumlah saudara kandung wajib diisi.'
    if (!form.kelainan_jasmani || String(form.kelainan_jasmani).trim() === '') clientErrors.value.kelainan_jasmani = 'Kelainan jasmani wajib diisi (isi "Tidak Ada" jika normal).'
    if (!form.jarak_rumah || Number(form.jarak_rumah) <= 0) clientErrors.value.jarak_rumah = 'Jarak rumah ke sekolah wajib diisi (meter).'
    if (!form.transportasi) clientErrors.value.transportasi = 'Pilih alat transportasi utama.'
    if (form.punya_kip == 1 && (!form.no_kip || String(form.no_kip).trim() === '')) {
      clientErrors.value.no_kip = 'Nomor KIP wajib diisi jika memiliki KIP.'
    }
    if (form.layak_kip == 1 && !form.alasan_layak) {
      clientErrors.value.alasan_layak = 'Alasan layak KIP wajib dipilih.'
    }
  } else if (stepNumber === 4) {
    if (!form.nik_ibu || String(form.nik_ibu).trim() === '') {
      clientErrors.value.nik_ibu = 'NIK ibu kandung wajib diisi.'
    } else if (String(form.nik_ibu).trim().length !== 16) {
      clientErrors.value.nik_ibu = 'NIK ibu kandung harus tepat 16 digit angka.'
    }
    if (!form.nama_ibu || String(form.nama_ibu).trim().length < 3) clientErrors.value.nama_ibu = 'Nama lengkap ibu kandung wajib diisi.'
    if (!form.tempat_lahir_ibu && !form.id_tempat_lahir_ibu) clientErrors.value.tempat_lahir_ibu = 'Tempat lahir ibu kandung wajib diisi.'
    if (!form.tanggal_lahir_ibu) clientErrors.value.tanggal_lahir_ibu = 'Tanggal lahir ibu kandung wajib diisi.'
    if (!form.pendidikan_ibu) clientErrors.value.pendidikan_ibu = 'Pilih pendidikan terakhir ibu.'
    if (!form.pekerjaan_ibu) clientErrors.value.pekerjaan_ibu = 'Pilih pekerjaan ibu.'
    if (!form.penghasilan_ibu) clientErrors.value.penghasilan_ibu = 'Pilih penghasilan bulanan ibu.'
    if (!form.agama_ibu) clientErrors.value.agama_ibu = 'Pilih agama ibu.'
  } else if (stepNumber === 5) {
    if (!form.jenis_pendaftaran) clientErrors.value.jenis_pendaftaran = 'Pilih jenis pendaftaran.'
    if (!form.tanggal_masuk) clientErrors.value.tanggal_masuk = 'Tanggal masuk / diterima wajib diisi.'
    if (!form.hobi || String(form.hobi).trim() === '') clientErrors.value.hobi = 'Hobi siswa wajib diisi.'
    if (form.jenis_pendaftaran === 'Pindahan' && (!form.sekolah_asal_mutasi || String(form.sekolah_asal_mutasi).trim() === '')) {
      clientErrors.value.sekolah_asal_mutasi = 'Nama sekolah asal mutasi wajib diisi untuk siswa pindahan.'
    }
  }

  const stepErrorKeys = fieldsInStep.filter((f) => clientErrors.value[f])
  return stepErrorKeys.length === 0
}

// Validate all steps from 1 to 5
const validateAllSteps = () => {
  let allValid = true
  for (let s = 1; s <= 5; s++) {
    const valid = validateStep(s)
    if (!valid) allValid = false
  }
  return allValid
}

// Comprehensive breakdown of missing fields grouped by step
const missingStepsOverview = computed(() => {
  const result = []
  for (let s = 1; s <= 5; s++) {
    const fieldsInStep = stepFields[s - 1] || []
    const stepErrs = []

    fieldsInStep.forEach((f) => {
      const err = allErrors.value[f]
      if (err) {
        stepErrs.push({
          field: f,
          label: fieldLabels[f] || f,
          message: err,
        })
      }
    })

    result.push({
      step: s,
      stepName: stepNames[s - 1],
      isComplete: stepErrs.length === 0,
      errorCount: stepErrs.length,
      errors: stepErrs,
    })
  }
  return result
})

// Jump directly to specific step and input field
const jumpToField = (stepNumber, fieldName) => {
  currentStep.value = stepNumber
  showValidationModal.value = false
  validationAlert.value.show = false
  setTimeout(() => {
    const el = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`) || document.querySelector(`[data-field="${fieldName}"]`)
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      el.focus()
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  }, 150)
}

// Scroll and focus on first error input
const scrollToFirstError = (stepNumber) => {
  setTimeout(() => {
    const fieldsInStep = stepFields[stepNumber - 1] || []
    for (const f of fieldsInStep) {
      if (allErrors.value[f]) {
        const el = document.getElementById(f) || document.querySelector(`[name="${f}"]`) || document.querySelector(`[data-field="${f}"]`)
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'center' })
          el.focus()
          break
        }
      }
    }
  }, 100)
}

// Step navigation with strict protective guards
const goToStep = (targetStep) => {
  if (targetStep < currentStep.value) {
    // Navigating backwards is always allowed
    currentStep.value = targetStep
    validationAlert.value.show = false
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }

  // Navigating forward: validate all intermediate steps sequentially
  for (let s = 1; s < targetStep; s++) {
    const isValid = validateStep(s)
    if (!isValid) {
      currentStep.value = s
      const missing = stepFields[s - 1].filter((f) => allErrors.value[f]).map((f) => fieldLabels[f] || f)
      validationAlert.value = {
        show: true,
        step: s,
        title: `Langkah ${s} Belum Lengkap (${stepNames[s - 1]})`,
        message: `Anda tidak dapat melanjutkan ke Langkah ${targetStep} sebelum seluruh data wajib pada Langkah ${s} diisi dengan benar.`,
        missingFields: missing,
      }
      showValidationModal.value = true
      scrollToFirstError(s)
      return
    }
  }

  currentStep.value = targetStep
  validationAlert.value.show = false
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const nextStep = () => {
  const isValid = validateStep(currentStep.value)
  if (!isValid) {
    const missing = stepFields[currentStep.value - 1].filter((f) => allErrors.value[f]).map((f) => fieldLabels[f] || f)
    validationAlert.value = {
      show: true,
      step: currentStep.value,
      title: `Data Langkah ${currentStep.value} Belum Lengkap`,
      message: `Mohon lengkapi seluruh kolom wajib bertanda bintang (*) pada Langkah ${currentStep.value} (${stepNames[currentStep.value - 1]}) berikut:`,
      missingFields: missing,
    }
    showValidationModal.value = true
    scrollToFirstError(currentStep.value)
    return
  }

  if (currentStep.value < 5) {
    currentStep.value++
    validationAlert.value.show = false
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    validationAlert.value.show = false
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Form submission handler with full pre-validation
const savingStep = ref(null)

// Save single step independently
const saveCurrentStep = (stepNumber) => {
  const isValid = validateStep(stepNumber)
  if (!isValid) {
    const missing = stepFields[stepNumber - 1].filter((f) => allErrors.value[f]).map((f) => fieldLabels[f] || f)
    validationAlert.value = {
      show: true,
      step: stepNumber,
      title: `Data Langkah ${stepNumber} Belum Lengkap (${stepNames[stepNumber - 1]})`,
      message: `Mohon lengkapi seluruh kolom wajib bertanda bintang (*) pada Langkah ${stepNumber} sebelum menyimpan data langkah ini:`,
      missingFields: missing,
    }
    showValidationModal.value = true
    scrollToFirstError(stepNumber)
    return
  }

  savingStep.value = stepNumber

  const options = {
    preserveScroll: true,
    forceFormData: true,
    onFinish: () => {
      savingStep.value = null
    },
    onError: (serverErrors) => {
      const stepErrs = stepFields[stepNumber - 1].filter((f) => serverErrors[f]).map((f) => `${fieldLabels[f] || f}: ${serverErrors[f]}`)
      if (stepErrs.length > 0) {
        let errorMsg = `Terdapat kesalahan data dari server pada Langkah ${stepNumber} (${stepNames[stepNumber - 1]}):`
        if (stepNumber === 5) {
          errorMsg = `Gagal menyimpan berkas / data pada Langkah 5. Tips: Jika mengalami kendala saat upload berkas, silakan unggah 1 file terlebih dahulu lalu klik 'Simpan Langkah 5', dan lakukan berulang hingga semua file terunggah.`
        }
        validationAlert.value = {
          show: true,
          step: stepNumber,
          title: `Validasi Server Gagal pada Langkah ${stepNumber}`,
          message: errorMsg,
          missingFields: stepErrs,
        }
        showValidationModal.value = true
        scrollToFirstError(stepNumber)
      }
    },
    onSuccess: () => {
      validationAlert.value.show = false
      showValidationModal.value = false
    },
  }

  const payloadModifier = (data) => ({
    ...data,
    current_step: stepNumber,
  })

  if (props.isCreate) {
    form.transform(payloadModifier).post('/siswa', options)
  } else {
    form.transform(payloadModifier).post(`/siswa/${props.siswa.id}`, options)
  }
}

// Full form submission handler
const submitForm = () => {
  const allValid = validateAllSteps()
  if (!allValid) {
    // Find the first step that failed validation
    for (let s = 1; s <= 5; s++) {
      if (!validateStep(s)) {
        currentStep.value = s
        const missing = stepFields[s - 1].filter((f) => allErrors.value[f]).map((f) => fieldLabels[f] || f)
        let customMessage = `Penyimpanan dibatalkan karena terdapat kolom wajib yang masih kosong. Silakan periksa rincian data per-langkah di bawah ini:`
        if (s === 5) {
          customMessage += ` Jika terjadi kendala saat upload file, unggah 1 file lalu simpan (klik Simpan Langkah 5), dan ulangi hingga semua file terupload.`
        }
        validationAlert.value = {
          show: true,
          step: s,
          title: `Gagal Menyimpan: Data Formulir Belum Lengkap`,
          message: customMessage,
          missingFields: missing,
        }
        showValidationModal.value = true
        scrollToFirstError(s)
        break
      }
    }
    return
  }

  // Submit form via Inertia
  const options = {
    preserveScroll: true,
    forceFormData: true,
    onError: (serverErrors) => {
      // Find the first step containing a server error
      for (let s = 1; s <= 5; s++) {
        if (stepFields[s - 1].some((f) => serverErrors[f])) {
          currentStep.value = s
          const missing = stepFields[s - 1].filter((f) => serverErrors[f]).map((f) => `${fieldLabels[f] || f}: ${serverErrors[f]}`)
          let errorMsg = `Terdapat kesalahan isian data dari server pada Langkah ${s} (${stepNames[s - 1]}):`
          if (s === 5) {
            errorMsg = `Gagal menyimpan berkas pada Langkah 5. Tips: Jika mengalami kendala/error saat upload berkas, silakan unggah 1 file lalu simpan (klik 'Simpan Langkah 5'), dan ulangi hingga seluruh file terupload.`
          }
          validationAlert.value = {
            show: true,
            step: s,
            title: `Validasi Server Gagal pada Langkah ${s}`,
            message: errorMsg,
            missingFields: missing,
          }
          showValidationModal.value = true
          scrollToFirstError(s)
          break
        }
      }
    },
    onSuccess: () => {
      validationAlert.value.show = false
      showValidationModal.value = false
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
  }

  form.transform((data) => {
    const copy = { ...data }
    delete copy.current_step
    return copy
  })

  if (props.isCreate) {
    form.post('/siswa', options)
  } else {
    form.post(`/siswa/${props.siswa.id}`, options)
  }
}

// Options Static Lists
const agamaOptions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu']
const statusTinggalOptions = ['Milik Sendiri', 'Menumpang', 'Kos', 'Kontrak / Sewa', 'Asrama Sekolah', 'Rumah Dinas', 'Lainnya']
const tinggalDenganOptions = ['Orang Tua', 'Wali', 'Kos', 'Asrama', 'Lainnya']
const transportasiOptions = ['Jalan Kaki', 'Sepeda', 'Motor', 'Mobil', 'Antar Jemput', 'Angkutan Umum', 'Lainnya']
const pendidikanOptions = [
  'Tidak Tamat Sekolah',
  'SD / MI',
  'SMP / MTs',
  'SMA / SMK Sederajat',
  'D3 / Akademi',
  'S1 / Sarjana',
  'S2 / Magister',
  'S3 / Doktoral',
]
const pekerjaanOptions = [
  'Tidak Bekerja',
  'Buruh',
  'Petani',
  'Nelayan',
  'Pedagang',
  'Wiraswasta',
  'Pegawai Swasta',
  'PNS / TNI / Polri',
  'Guru / Dosen',
  'Dokter / Perawat',
  'Meninggal',
]
const penghasilanOptions = [
  'Tidak Berpenghasilan',
  'Kurang dari Rp500.000',
  'Rp500.000 sampai Rp999.999',
  'Rp1.000.000 sampai Rp1.999.999',
  'Rp2.000.000 sampai Rp4.999.999',
  'Rp5.000.000 sampai Rp20.000.000',
  'Lebih dari Rp20.000.000',
]
const ukuranOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']
</script>

<template>
  <AppLayout :title="isCreate ? 'Tambah Siswa Baru' : 'Edit Data Siswa'">
    <div class="space-y-5 pb-24">

      <!-- Flash Success Alert -->
      <div v-if="flashSuccess"
           class="flex items-center gap-3 px-5 py-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold shadow-2xs">
        <i class="bi bi-check-circle-fill text-emerald-600 text-lg"></i>
        <span>{{ flashSuccess }}</span>
      </div>

      <!-- Validation Warning & Error Banner -->
      <div v-if="hasAnyError || validationAlert.show"
           class="bg-gradient-to-r from-red-50 to-rose-50 border-2 border-red-300/80 rounded-2xl p-4 sm:p-5 shadow-xs transition-all duration-300">
        <div class="flex items-start gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-xl shrink-0 shadow-sm shadow-red-500/20">
            <i class="bi bi-shield-exclamation"></i>
          </div>
          <div class="grow">
            <div class="flex items-center justify-between gap-2">
              <h3 class="text-sm font-black text-red-800 tracking-tight">
                {{ validationAlert.title || `Terdapat ${Object.keys(allErrors).length} Kolom Data Wajib yang Belum Diisi` }}
              </h3>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-red-200 text-red-800">
                Wajib Dilengkapi
              </span>
            </div>
            <p class="text-xs text-red-700 font-medium mt-1">
              {{ validationAlert.message || 'Harap periksa dan lengkapi kolom-kolom bertanda bintang merah (*) pada formulir sebelum berpindah langkah atau menyimpan data.' }}
            </p>

            <!-- List Missing Field Badges -->
            <div class="mt-3 pt-3 border-t border-red-200/80 flex flex-wrap gap-1.5">
              <button type="button" v-for="(msg, field) in allErrors" :key="field"
                      @click="() => {
                        for (let s = 1; s <= 5; s++) {
                          if (stepFields[s - 1].includes(field)) {
                            currentStep = s;
                            scrollToFirstError(s);
                            break;
                          }
                        }
                      }"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-red-300 text-red-700 text-[11px] font-bold shadow-2xs hover:bg-red-600 hover:text-white hover:border-red-600 transition cursor-pointer">
                <i class="bi bi-exclamation-circle-fill text-red-500"></i>
                <span>{{ fieldLabels[field] || field }}:</span>
                <span class="font-normal opacity-90 truncate max-w-[200px]">{{ msg }}</span>
              </button>
            </div>

            <!-- Solusi Khusus Saat Mengalami Kendala Upload Berkas -->
            <div v-if="currentStep === 5" class="mt-3 p-3 bg-amber-500/15 border border-amber-400/50 rounded-xl flex items-start gap-2.5 text-xs text-amber-950">
              <i class="bi bi-info-circle-fill text-amber-700 text-base mt-0.5 shrink-0"></i>
              <div>
                <span class="font-black text-amber-950">Petunjuk Solusi Upload Berkas:</span>
                <p class="text-amber-900 text-[11px] mt-0.5 leading-relaxed">
                  Jika mengalami kendala jaringan atau error saat mengunggah banyak berkas, silakan <strong>unggah 1 file terlebih dahulu lalu klik tombol "Simpan Langkah 5" di bawah</strong>, dan lakukan berulang hingga semua berkas terupload lengkap.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 1. Header Bar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs">
        <div class="flex items-center space-x-4">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-2xl font-bold shadow-md shadow-blue-500/20">
            <i :class="isCreate ? 'bi bi-person-plus-fill' : 'bi bi-person-gear'"></i>
          </div>
          <div>
            <div class="flex items-center space-x-2 text-[10px] font-bold text-slate-400 mb-0.5 uppercase tracking-wider">
              <Link href="/pengguna" class="hover:text-blue-600 transition flex items-center">
                <i class="bi bi-people me-1"></i> Manajemen Pengguna
              </Link>
              <span>/</span>
              <span class="text-blue-600 font-extrabold">{{ isCreate ? 'Tambah Siswa' : 'Edit Data Siswa' }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">
              {{ isCreate ? 'Tambah Siswa Baru' : (form.nama_lengkap || 'Edit Data Siswa') }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ isCreate ? 'Lengkapi formulir buku induk multi-step di bawah ini sesuai standar Dapodik' : `NISN: ${form.nisn || '-'} | NIS: ${form.nis || '-'} | Status: ${form.status || 'Aktif'}` }}
            </p>
          </div>
        </div>
        <div class="flex items-center space-x-2.5">
          <Link href="/pengguna" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition flex items-center gap-1.5 shadow-2xs">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
          </Link>
          <button type="button" @click="submitForm" :disabled="form.processing"
                  class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
            <i class="bi bi-floppy2-fill" v-if="!form.processing"></i>
            <span v-else class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            {{ form.processing ? 'Menyimpan...' : (isCreate ? 'Simpan Siswa' : 'Simpan / Update') }}
          </button>
        </div>
      </div>

      <!-- 2. Wizard Progress Bar -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs">
        <div class="relative">
          <div class="flex justify-between items-start relative z-10 select-none">
            <div v-for="step in 5" :key="step"
                 @click="goToStep(step)"
                 class="flex flex-col items-center text-center flex-1 cursor-pointer group">
              <div class="relative">
                <div :class="[
                       'w-9 h-9 rounded-full font-extrabold text-xs flex items-center justify-center transition-all duration-300',
                       currentStep === step
                         ? 'bg-blue-600 text-white ring-4 ring-blue-500/20 shadow-md shadow-blue-500/30'
                         : (currentStep > step ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400 border border-slate-200 group-hover:bg-slate-200')
                     ]">
                  <i v-if="currentStep > step" class="bi bi-check-lg text-sm"></i>
                  <span v-else>{{ step }}</span>
                </div>
                <!-- Error badge -->
                <span v-if="stepErrorCount[step-1] > 0"
                      class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-red-500 text-white text-[9px] font-black flex items-center justify-center shadow-xs">
                  {{ stepErrorCount[step-1] }}
                </span>
              </div>
              <span :class="[
                      'text-[10px] font-bold mt-2 hidden md:inline-block transition-colors leading-tight',
                      currentStep === step ? 'text-blue-600 font-black' : (currentStep > step ? 'text-slate-700' : 'text-slate-400')
                    ]">
                {{ stepNames[step - 1] }}
              </span>
            </div>
          </div>
          <div class="hidden md:block absolute top-[18px] left-[10%] right-[10%] h-1 bg-slate-100 rounded-full z-0">
            <div class="h-full bg-emerald-500 rounded-full transition-all duration-300"
                 :style="{ width: ((currentStep - 1) / 4 * 100) + '%' }"></div>
          </div>
        </div>
        <div class="md:hidden mt-4 text-center">
          <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
            Langkah {{ currentStep }} dari 5: {{ stepNames[currentStep - 1] }}
          </span>
        </div>
      </div>

      <!-- 3. Form Body -->
      <form @submit.prevent="submitForm">

        <!-- ===== LANGKAH 1: DATA POKOK & AKADEMIK ===== -->
        <div v-show="currentStep === 1" class="space-y-4">

          <!-- Identitas Utama -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-sm font-black text-slate-800 flex items-center gap-2">
                  <i class="bi bi-person-badge-fill text-blue-600"></i> Langkah 1: Data Pokok & Akademik
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Identitas utama kependudukan, nomor registrasi, dan akun login.</p>
              </div>
              <span class="text-[10px] font-extrabold px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">1 / 5</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

              <!-- Sekolah / Tenant (Super Admin Only) -->
              <div v-if="userRole === 'super_admin'" class="md:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1">Sekolah / Tenant <span class="text-red-500">*</span></label>
                <select v-model="form.tenant_id" :disabled="!isCreate"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Sekolah --</option>
                  <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                </select>
              </div>

              <!-- NIK -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">NIK (Nomor Induk Kependudukan)</label>
                <input type="text" v-model="form.nik" maxlength="16" placeholder="16 digit NIK"
                       class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-mono focus:bg-white outline-hidden"
                       :class="form.errors.nik ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
                <p v-if="form.errors.nik" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.nik }}</p>
              </div>

              <!-- No KK -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">No. KK (Kartu Keluarga)</label>
                <input type="text" v-model="form.no_kk" maxlength="16" placeholder="16 digit No. KK"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- NISN -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">NISN <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.nisn" maxlength="10" placeholder="10 digit NISN" :readonly="userRole === 'siswa'"
                       class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-mono focus:bg-white outline-hidden"
                       :class="form.errors.nisn ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
                <p v-if="form.errors.nisn" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.nisn }}</p>
              </div>

              <!-- NIS -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">NIS (Nomor Induk Siswa) <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.nis" maxlength="20" placeholder="Masukkan NIS sekolah" :readonly="userRole === 'siswa'"
                       class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-mono focus:bg-white outline-hidden"
                       :class="form.errors.nis ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
                <p v-if="form.errors.nis" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.nis }}</p>
              </div>

              <!-- Nama Lengkap -->
              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.nama_lengkap" placeholder="Masukkan nama lengkap sesuai ijazah" :readonly="userRole === 'siswa'"
                       class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-bold text-slate-800 uppercase focus:bg-white outline-hidden"
                       :class="form.errors.nama_lengkap ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
                <p v-if="form.errors.nama_lengkap" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.nama_lengkap }}</p>
              </div>

              <!-- Nama Panggilan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Panggilan</label>
                <input type="text" v-model="form.nama_panggilan" placeholder="Nama panggilan"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Jenis Kelamin -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label>
                <select v-model="form.jenis_kelamin"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="L">Laki-laki (L)</option>
                  <option value="P">Perempuan (P)</option>
                </select>
              </div>

              <!-- Agama -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Agama <span class="text-red-500">*</span></label>
                <select v-model="form.agama"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option v-for="a in agamaOptions" :key="a" :value="a">{{ a }}</option>
                </select>
              </div>

              <!-- Kewarganegaraan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kewarganegaraan <span class="text-red-500">*</span></label>
                <select v-model="form.kewarganegaraan"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="WNI">Warga Negara Indonesia (WNI)</option>
                  <option value="WNA">Warga Negara Asing (WNA)</option>
                </select>
              </div>

              <!-- Bahasa Sehari-hari -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Bahasa Sehari-hari</label>
                <input type="text" v-model="form.bahasa_sehari_hari" placeholder="Contoh: Indonesia, Jawa"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Tempat Lahir -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Lahir <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.tempat_lahir" placeholder="Masukkan kota tempat lahir"
                       class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs focus:bg-white outline-hidden"
                       :class="form.errors.tempat_lahir ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
                <p v-if="form.errors.tempat_lahir" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.tempat_lahir }}</p>
              </div>

              <!-- Tanggal Lahir -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                <input type="date" v-model="form.tanggal_lahir"
                       class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-mono focus:bg-white outline-hidden"
                       :class="form.errors.tanggal_lahir ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
                <p v-if="form.errors.tanggal_lahir" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.tanggal_lahir }}</p>
              </div>

              <!-- Status Siswa -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Status Siswa <span class="text-red-500">*</span></label>
                <select v-model="form.status" :disabled="!['super_admin', 'operator_sekolah', 'admin_sekolah'].includes(userRole)"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-blue-700 focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="Aktif">Aktif</option>
                  <option value="Lulus">Lulus</option>
                  <option value="Pindah">Pindah / Mutasi Keluar</option>
                </select>
              </div>

              <!-- Ubah Password (Optional) -->
              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">{{ isCreate ? 'Password Login Siswa (Opsional)' : 'Ubah Password Siswa (Opsional)' }}</label>
                <input type="password" v-model="form.password" placeholder="Kosongkan jika tidak ingin mengubah (Default: tgl lahir YYYYMMDD)"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

            </div>
          </div>

          <!-- Penempatan Akademik Relasional -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="bi bi-mortarboard-fill text-blue-600"></i> Data Penempatan Akademik
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

              <!-- Angkatan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Angkatan <span class="text-red-500">*</span></label>
                <select v-model="form.id_angkatan"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Angkatan --</option>
                  <option v-for="a in academicOptions.angkatan" :key="a.id" :value="a.id">{{ a.tahun_angkatan }}</option>
                </select>
              </div>

              <!-- Tahun Ajaran -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran <span class="text-red-500">*</span></label>
                <select v-model="form.id_tahun_ajaran"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Tahun Ajaran --</option>
                  <option v-for="ta in academicOptions.tahun_ajaran" :key="ta.id" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                </select>
              </div>

              <!-- Jenjang -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jenjang Pendidikan <span class="text-red-500">*</span></label>
                <select v-model="form.id_jenjang"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Jenjang --</option>
                  <option v-for="j in academicOptions.jenjang" :key="j.id" :value="j.id">{{ j.nama_jenjang }}</option>
                </select>
              </div>

              <!-- Jurusan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jurusan / Peminatan <span class="text-red-500">*</span></label>
                <select v-model="form.id_jurusan"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Jurusan --</option>
                  <option v-for="jr in filteredJurusan" :key="jr.id" :value="jr.id">{{ jr.nama_jurusan }}</option>
                </select>
              </div>

              <!-- Kelas / Rombel -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Rombel / Kelas <span class="text-red-500">*</span></label>
                <select v-model="form.id_kelas"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-blue-700 focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Rombel --</option>
                  <option v-for="k in filteredKelas" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                </select>
              </div>

              <!-- Pendidikan Terakhir / Ditempuh -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pendidikan Ditempuh <span class="text-red-500">*</span></label>
                <select v-model="form.id_pendidikan"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Pendidikan --</option>
                  <option v-for="p in academicOptions.pendidikan" :key="p.id" :value="p.id">{{ p.nama_pendidikan }}</option>
                </select>
              </div>

            </div>
          </div>

          <!-- Asal Sekolah & Seragam -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="bi bi-building text-blue-600"></i> Asal Sekolah Sebelumnya & Ukuran Seragam
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Asal Sekolah Sebelumnya</label>
                <input type="text" v-model="form.sekolah_asal" placeholder="Contoh: SMP Negeri 1 Jakarta"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">No. Ijazah Sebelumnya</label>
                <input type="text" v-model="form.no_ijazah_sebelumnya" placeholder="Nomor Ijazah SMP / MTs"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Ijazah Sebelumnya</label>
                <input type="date" v-model="form.tanggal_ijazah_sebelumnya"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Lama Belajar Sebelumnya (Tahun)</label>
                <input type="number" min="1" max="10" v-model.number="form.lama_belajar_sebelumnya"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Ukuran Seragam Sekolah</label>
                <select v-model="form.ukuran_seragam_sekolah"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold uppercase focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="">-- Pilih Ukuran --</option>
                  <option v-for="u in ukuranOptions" :key="u" :value="u">{{ u }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Ukuran Seragam Olahraga</label>
                <select v-model="form.ukuran_seragam_olahraga"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold uppercase focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="">-- Pilih Ukuran --</option>
                  <option v-for="u in ukuranOptions" :key="u" :value="u">{{ u }}</option>
                </select>
              </div>

            </div>
          </div>

        </div>

        <!-- ===== LANGKAH 2: ALAMAT & KONTAK ===== -->
        <div v-show="currentStep === 2" class="space-y-4">
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-sm font-black text-slate-800 flex items-center gap-2">
                  <i class="bi bi-geo-alt-fill text-blue-600"></i> Langkah 2: Detail Alamat & Kontak
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Alamat KK, domisili saat ini, wilayah administratif, dan kontak aktif.</p>
              </div>
              <span class="text-[10px] font-extrabold px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">2 / 5</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

              <!-- Alamat KK -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Sesuai KK <span class="text-red-500">*</span></label>
                <textarea rows="3" v-model="form.alamat_kk" placeholder="Masukkan alamat lengkap sesuai Kartu Keluarga"
                          class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs focus:bg-white outline-hidden"
                          :class="form.errors.alamat_kk ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'"></textarea>
                <p v-if="form.errors.alamat_kk" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.alamat_kk }}</p>
              </div>

              <!-- Alamat Domisili -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Domisili Sekarang <span class="text-red-500">*</span></label>
                <textarea rows="3" v-model="form.alamat_domisili" placeholder="Masukkan alamat tempat tinggal sekarang"
                          class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs focus:bg-white outline-hidden"
                          :class="form.errors.alamat_domisili ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'"></textarea>
                <p v-if="form.errors.alamat_domisili" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.alamat_domisili }}</p>
              </div>

              <!-- RT / RW / Kode Pos -->
              <div class="grid grid-cols-3 gap-2">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">RT <span class="text-red-500">*</span></label>
                  <input type="text" v-model="form.rt" maxlength="3" placeholder="001"
                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-center focus:bg-white focus:border-blue-600 outline-hidden">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">RW <span class="text-red-500">*</span></label>
                  <input type="text" v-model="form.rw" maxlength="3" placeholder="001"
                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-center focus:bg-white focus:border-blue-600 outline-hidden">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Kode Pos <span class="text-red-500">*</span></label>
                  <input type="text" v-model="form.kode_pos" maxlength="5" placeholder="5 digit"
                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-center focus:bg-white focus:border-blue-600 outline-hidden">
                </div>
              </div>

              <!-- Cascading Dropdown: Provinsi -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
                <select v-model="form.id_provinsi" @change="onProvinsiChange"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Provinsi --</option>
                  <option v-for="p in provinces" :key="p.id_provinsi" :value="p.id_provinsi">{{ p.nama_provinsi }}</option>
                </select>
              </div>

              <!-- Cascading Dropdown: Kabupaten / Kota -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kabupaten / Kota <span class="text-red-500">*</span></label>
                <select v-model="form.id_kota" @change="onKotaChange" :disabled="loadingKota || !form.id_provinsi"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>{{ loadingKota ? 'Memuat data kota...' : '-- Pilih Kota --' }}</option>
                  <option v-for="c in kotaList" :key="c.id_kota" :value="c.id_kota">{{ c.nama_kota }}</option>
                </select>
              </div>

              <!-- Cascading Dropdown: Kecamatan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
                <select v-model="form.id_kecamatan" @change="onKecamatanChange" :disabled="loadingKecamatan || !form.id_kota"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>{{ loadingKecamatan ? 'Memuat kecamatan...' : '-- Pilih Kecamatan --' }}</option>
                  <option v-for="d in kecamatanList" :key="d.id_kecamatan" :value="d.id_kecamatan">{{ d.nama_kecamatan }}</option>
                </select>
              </div>

              <!-- Cascading Dropdown: Kelurahan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kelurahan / Desa <span class="text-red-500">*</span></label>
                <select v-model="form.id_kelurahan" :disabled="loadingKelurahan || !form.id_kecamatan"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>{{ loadingKelurahan ? 'Memuat kelurahan...' : '-- Pilih Kelurahan --' }}</option>
                  <option v-for="k in kelurahanList" :key="k.id_kelurahan" :value="k.id_kelurahan">{{ k.nama_kelurahan }}</option>
                </select>
              </div>

              <!-- Status Tinggal -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Status Tempat Tinggal <span class="text-red-500">*</span></label>
                <select v-model="form.status_tinggal"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option v-for="st in statusTinggalOptions" :key="st" :value="st">{{ st }}</option>
                </select>
              </div>

              <!-- Tinggal Dengan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tinggal Bersama <span class="text-red-500">*</span></label>
                <select v-model="form.tinggal_dengan"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option v-for="td in tinggalDenganOptions" :key="td" :value="td">{{ td }}</option>
                </select>
              </div>

              <!-- Email Siswa -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Email Siswa <span class="text-red-500">*</span></label>
                <input type="email" v-model="form.email" placeholder="siswa@gmail.com"
                       class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-mono focus:bg-white outline-hidden"
                       :class="form.errors.email ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
                <p v-if="form.errors.email" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.email }}</p>
              </div>

              <!-- No Telepon Rumah -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">No. Telepon Rumah</label>
                <input type="text" v-model="form.no_telepon_rumah" maxlength="15" placeholder="Contoh: 021-xxxx"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- No HP Siswa -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">No. HP / WhatsApp Siswa <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.no_telepon_siswa" maxlength="15" placeholder="Contoh: 081234567890"
                       class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-mono focus:bg-white outline-hidden"
                       :class="form.errors.no_telepon_siswa ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
                <p v-if="form.errors.no_telepon_siswa" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.no_telepon_siswa }}</p>
              </div>

              <!-- No HP Orang Tua -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">No. HP Orang Tua / Wali</label>
                <input type="text" v-model="form.no_telepon_orang_tua" maxlength="15" placeholder="Contoh: 081234567890"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

            </div>
          </div>
        </div>

        <!-- ===== LANGKAH 3: FISIK, RIWAYAT & BANTUAN ===== -->
        <div v-show="currentStep === 3" class="space-y-4">
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-sm font-black text-slate-800 flex items-center gap-2">
                  <i class="bi bi-heart-pulse-fill text-blue-600"></i> Langkah 3: Kondisi Fisik, Riwayat & Kesejahteraan
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Ukuran fisik siswa, status yatim, program bantuan PIP/KIP, dan riwayat kesehatan.</p>
              </div>
              <span class="text-[10px] font-extrabold px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">3 / 5</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

              <!-- Tinggi Badan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tinggi Badan (cm) <span class="text-red-500">*</span></label>
                <input type="number" min="30" max="255" v-model.number="form.tinggi_badan" placeholder="Contoh: 165"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Berat Badan -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Berat Badan (kg) <span class="text-red-500">*</span></label>
                <input type="number" min="5" max="255" v-model.number="form.berat_badan" placeholder="Contoh: 55"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Lingkar Kepala -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Lingkar Kepala (cm) <span class="text-red-500">*</span></label>
                <input type="number" min="20" max="255" v-model.number="form.lingkar_kepala" placeholder="Contoh: 54"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Golongan Darah -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Golongan Darah <span class="text-red-500">*</span></label>
                <select v-model="form.golongan_darah"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="AB">AB</option>
                  <option value="O">O</option>
                </select>
              </div>

              <!-- Anak Ke- -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Anak Ke- <span class="text-red-500">*</span></label>
                <input type="number" min="1" max="255" v-model.number="form.anak_ke"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Jumlah Saudara -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Saudara Kandung <span class="text-red-500">*</span></label>
                <input type="number" min="0" max="255" v-model.number="form.jumlah_saudara"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Jarak Rumah ke Sekolah -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jarak ke Sekolah (Meter) <span class="text-red-500">*</span></label>
                <input type="number" min="1" max="65535" v-model.number="form.jarak_rumah" placeholder="Contoh: 1500"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Alat Transportasi -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alat Transportasi <span class="text-red-500">*</span></label>
                <select v-model="form.transportasi"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option v-for="tr in transportasiOptions" :key="tr" :value="tr">{{ tr }}</option>
                </select>
              </div>

              <!-- Riwayat Penyakit -->
              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Riwayat Penyakit yang Pernah Diderita</label>
                <input type="text" v-model="form.penyakit_yang_diderita" placeholder="Asma, jantung, alergi, dsb (Opsional)"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Kelainan Jasmani -->
              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Kelainan Jasmani / Disabilitas <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.kelainan_jasmani" placeholder="Contoh: Tidak Ada, Tuli, Low Vision, dll"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Status Anak (Yatim/Piatu) -->
              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Status Anak (Yatim / Piatu)</label>
                <select v-model="form.status_anak"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="Bukan Yatim/Piatu">Lengkap (Bukan Yatim/Piatu)</option>
                  <option value="Yatim">Yatim (Tidak Ada Ayah)</option>
                  <option value="Piatu">Piatu (Tidak Ada Ibu)</option>
                  <option value="Yatim Piatu">Yatim Piatu (Tidak Ada Orang Tua)</option>
                </select>
              </div>

            </div>
          </div>

          <!-- Program Bantuan KPS / KIP -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="bi bi-award-fill text-blue-600"></i> Program Bantuan & Kesejahteraan
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

              <!-- Penerima KPS / KKS -->
              <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                <label class="block text-xs font-bold text-slate-800 mb-2">Penerima KPS / KKS?</label>
                <div class="flex items-center gap-4 text-xs font-bold">
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :value="1" v-model.number="form.penerima_kps" class="w-4 h-4 accent-blue-600"> Ya
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :value="0" v-model.number="form.penerima_kps" class="w-4 h-4 accent-blue-600"> Tidak
                  </label>
                </div>
              </div>

              <!-- Punya KIP -->
              <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                <label class="block text-xs font-bold text-slate-800 mb-2">Memiliki Kartu KIP?</label>
                <div class="flex items-center gap-4 text-xs font-bold">
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :value="1" v-model.number="form.punya_kip" class="w-4 h-4 accent-blue-600"> Ya
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :value="0" v-model.number="form.punya_kip" class="w-4 h-4 accent-blue-600"> Tidak
                  </label>
                </div>
              </div>

              <!-- Layak KIP -->
              <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                <label class="block text-xs font-bold text-slate-800 mb-2">Layak Menerima PIP / KIP?</label>
                <div class="flex items-center gap-4 text-xs font-bold">
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :value="1" v-model.number="form.layak_kip" class="w-4 h-4 accent-blue-600"> Ya
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :value="0" v-model.number="form.layak_kip" class="w-4 h-4 accent-blue-600"> Tidak
                  </label>
                </div>
              </div>

              <!-- Nomor KIP (Muncul jika punya_kip === 1) -->
              <div v-if="form.punya_kip == 1">
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Kartu KIP <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.no_kip" placeholder="Masukkan nomor KIP"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Alasan Layak KIP (Muncul jika layak_kip === 1) -->
              <div v-if="form.layak_kip == 1" class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Layak KIP / PIP <span class="text-red-500">*</span></label>
                <select v-model="form.alasan_layak"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="" disabled>-- Pilih Alasan --</option>
                  <option value="Siswa Miskin">Siswa Miskin</option>
                  <option value="Daerah Konflik">Daerah Konflik</option>
                  <option value="Dampak Bencana Alam">Dampak Bencana Alam</option>
                  <option value="Kelainan Fisik">Kelainan Fisik</option>
                  <option value="Keluarga Terpidana / Berada di LAPAS">Keluarga Terpidana / Berada di LAPAS</option>
                  <option value="Pemegang PKH / KPS / KKS">Pemegang PKH / KPS / KKS</option>
                  <option value="Pernah Drop Out">Pernah Drop Out</option>
                  <option value="Tidak Ada">Tidak Ada</option>
                </select>
              </div>

            </div>
          </div>

          <!-- Riwayat Kesehatan Per Semester (Semester 1 s/d 6) -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="bi bi-heart-pulse text-red-500"></i> Riwayat Kesehatan Siswa (Per Semester)
            </h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
              <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 text-center">
                  <tr>
                    <th class="p-2.5 w-16">Semester</th>
                    <th class="p-2.5 w-32">Tinggi (cm)</th>
                    <th class="p-2.5 w-32">Berat (kg)</th>
                    <th class="p-2.5">Pendengaran</th>
                    <th class="p-2.5">Penglihatan</th>
                    <th class="p-2.5">Kondisi Gigi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="sem in 6" :key="sem" class="hover:bg-slate-50/50">
                    <td class="p-2.5 text-center font-bold text-slate-700 bg-slate-50/50">{{ sem }}</td>
                    <td class="p-2">
                      <input type="number" min="0" v-model.number="form.kesehatan[sem].tinggi_badan" placeholder="cm"
                             class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-center focus:border-blue-600 outline-hidden">
                    </td>
                    <td class="p-2">
                      <input type="number" min="0" v-model.number="form.kesehatan[sem].berat_badan" placeholder="kg"
                             class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-center focus:border-blue-600 outline-hidden">
                    </td>
                    <td class="p-2">
                      <input type="text" v-model="form.kesehatan[sem].pendengaran" placeholder="Normal / Kurang"
                             class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:border-blue-600 outline-hidden">
                    </td>
                    <td class="p-2">
                      <input type="text" v-model="form.kesehatan[sem].pengelihatan" placeholder="Normal / Minus"
                             class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:border-blue-600 outline-hidden">
                    </td>
                    <td class="p-2">
                      <input type="text" v-model="form.kesehatan[sem].gigi" placeholder="Bersih / Berlubang"
                             class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:border-blue-600 outline-hidden">
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- ===== LANGKAH 4: DATA ORANG TUA & WALI ===== -->
        <div v-show="currentStep === 4" class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-5">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
              <h2 class="text-sm font-black text-slate-800 flex items-center gap-2">
                <i class="bi bi-people-fill text-blue-600"></i> Langkah 4: Data Orang Tua & Wali
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">Identitas ayah kandung, ibu kandung (wajib), dan wali siswa.</p>
            </div>
            <span class="text-[10px] font-extrabold px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">4 / 5</span>
          </div>

          <!-- Sub-tab Navigation -->
          <div class="flex gap-1.5 p-1.5 bg-slate-100/80 rounded-xl w-fit flex-wrap">
            <button type="button" @click="activeParentTab = 'father'"
                    :class="activeParentTab === 'father' ? 'bg-white text-blue-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
              <i class="bi bi-gender-male text-blue-600"></i> Data Ayah Kandung
            </button>
            <button type="button" @click="activeParentTab = 'mother'"
                    :class="activeParentTab === 'mother' ? 'bg-white text-pink-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
              <i class="bi bi-gender-female text-pink-600"></i> Data Ibu Kandung <span class="text-red-500">*</span>
            </button>
            <button type="button" @click="activeParentTab = 'guardian'"
                    :class="activeParentTab === 'guardian' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
              <i class="bi bi-person-bounding-box text-indigo-600"></i> Data Wali (Opsional)
            </button>
          </div>

          <!-- SUB-TAB 1: AYAH KANDUNG -->
          <div v-show="activeParentTab === 'father'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">NIK Ayah</label>
              <input type="text" v-model="form.nik_ayah" maxlength="16" placeholder="Masukkan 16 digit NIK"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Ayah</label>
              <input type="text" v-model="form.nama_ayah" placeholder="Nama lengkap tanpa gelar"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold uppercase focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Lahir Ayah</label>
              <select v-model="form.id_tempat_lahir_ayah"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="">-- Pilih Kota --</option>
                <option v-for="c in allKotaList" :key="c.id_kota" :value="c.id_kota">{{ c.nama_kota }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir Ayah</label>
              <input type="date" v-model="form.tanggal_lahir_ayah"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kewarganegaraan Ayah</label>
              <select v-model="form.kewarganegaraan_ayah"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="WNI">Warga Negara Indonesia (WNI)</option>
                <option value="WNA">Warga Negara Asing (WNA)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Status Hidup Ayah</label>
              <select v-model="form.status_hidup_ayah"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="Hidup">Masih Hidup</option>
                <option value="Meninggal">Wafat / Meninggal</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Pendidikan Terakhir Ayah</label>
              <select v-model="form.pendidikan_ayah"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="">-- Pilih Pendidikan --</option>
                <option v-for="p in pendidikanOptions" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Pekerjaan Ayah</label>
              <select v-model="form.pekerjaan_ayah"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="">-- Pilih Pekerjaan --</option>
                <option v-for="pk in pekerjaanOptions" :key="pk" :value="pk">{{ pk }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Penghasilan Bulanan Ayah</label>
              <select v-model="form.penghasilan_ayah"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="">-- Pilih Penghasilan --</option>
                <option v-for="ph in penghasilanOptions" :key="ph" :value="ph">{{ ph }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Agama Ayah</label>
              <select v-model="form.agama_ayah"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option v-for="a in agamaOptions" :key="a" :value="a">{{ a }}</option>
              </select>
            </div>
          </div>

          <!-- SUB-TAB 2: IBU KANDUNG (WAJIB) -->
          <div v-show="activeParentTab === 'mother'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">NIK Ibu <span class="text-red-500">*</span></label>
              <input type="text" v-model="form.nik_ibu" maxlength="16" placeholder="Masukkan 16 digit NIK"
                     class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-mono focus:bg-white outline-hidden"
                     :class="form.errors.nik_ibu ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
              <p v-if="form.errors.nik_ibu" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.nik_ibu }}</p>
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Ibu <span class="text-red-500">*</span></label>
              <input type="text" v-model="form.nama_ibu" placeholder="Nama lengkap tanpa gelar"
                     class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-bold uppercase focus:bg-white outline-hidden"
                     :class="form.errors.nama_ibu ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
              <p v-if="form.errors.nama_ibu" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.nama_ibu }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Lahir Ibu <span class="text-red-500">*</span></label>
              <input type="text" v-model="form.tempat_lahir_ibu" placeholder="Kota tempat lahir"
                     class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs focus:bg-white outline-hidden"
                     :class="form.errors.tempat_lahir_ibu ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
              <p v-if="form.errors.tempat_lahir_ibu" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.tempat_lahir_ibu }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir Ibu <span class="text-red-500">*</span></label>
              <input type="date" v-model="form.tanggal_lahir_ibu"
                     class="w-full px-3.5 py-2 bg-slate-50 border rounded-xl text-xs font-mono focus:bg-white outline-hidden"
                     :class="form.errors.tanggal_lahir_ibu ? 'border-red-400 bg-red-50' : 'border-slate-200 focus:border-blue-600'">
              <p v-if="form.errors.tanggal_lahir_ibu" class="text-[10px] text-red-600 font-semibold mt-1">{{ form.errors.tanggal_lahir_ibu }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kewarganegaraan Ibu</label>
              <select v-model="form.kewarganegaraan_ibu"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="WNI">Warga Negara Indonesia (WNI)</option>
                <option value="WNA">Warga Negara Asing (WNA)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Status Hidup Ibu</label>
              <select v-model="form.status_hidup_ibu"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="Hidup">Masih Hidup</option>
                <option value="Meninggal">Wafat / Meninggal</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Pendidikan Terakhir Ibu <span class="text-red-500">*</span></label>
              <select v-model="form.pendidikan_ibu"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option v-for="p in pendidikanOptions" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Pekerjaan Ibu <span class="text-red-500">*</span></label>
              <select v-model="form.pekerjaan_ibu"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option v-for="pk in pekerjaanOptions" :key="pk" :value="pk">{{ pk }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Penghasilan Bulanan Ibu <span class="text-red-500">*</span></label>
              <select v-model="form.penghasilan_ibu"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option v-for="ph in penghasilanOptions" :key="ph" :value="ph">{{ ph }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Agama Ibu <span class="text-red-500">*</span></label>
              <select v-model="form.agama_ibu"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option v-for="a in agamaOptions" :key="a" :value="a">{{ a }}</option>
              </select>
            </div>
          </div>

          <!-- SUB-TAB 3: WALI MURID (OPSIONAL) -->
          <div v-show="activeParentTab === 'guardian'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">NIK Wali</label>
              <input type="text" v-model="form.nik_wali" maxlength="16" placeholder="16 digit NIK"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Wali</label>
              <input type="text" v-model="form.nama_wali" placeholder="Nama lengkap tanpa gelar"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold uppercase focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Hubungan Keluarga</label>
              <input type="text" v-model="form.hubungan_wali" placeholder="Contoh: Paman, Kakek, Kakak Kandung"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Lahir Wali</label>
              <input type="text" v-model="form.tempat_lahir_wali" placeholder="Kota kelahiran"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir Wali</label>
              <input type="date" v-model="form.tanggal_lahir_wali"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Pendidikan Terakhir Wali</label>
              <select v-model="form.pendidikan_wali"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="">-- Pilih --</option>
                <option v-for="p in pendidikanOptions" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Pekerjaan Wali</label>
              <input type="text" v-model="form.pekerjaan_wali" placeholder="Pekerjaan wali"
                     class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Penghasilan Bulanan Wali</label>
              <select v-model="form.penghasilan_wali"
                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                <option value="">-- Pilih --</option>
                <option v-for="ph in penghasilanOptions" :key="ph" :value="ph">{{ ph }}</option>
              </select>
            </div>
          </div>

        </div>

        <!-- ===== LANGKAH 5: REGISTRASI & BERKAS UPLOAD ===== -->
        <div v-show="currentStep === 5" class="space-y-4">

          <!-- Info Registrasi -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-sm font-black text-slate-800 flex items-center gap-2">
                  <i class="bi bi-file-earmark-check-fill text-blue-600"></i> Langkah 5: Registrasi, Keluar & Dokumen Berkas
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Jalur pendaftaran, tanggal masuk, riwayat mutasi/keluar, dan upload berkas pendukung.</p>
              </div>
              <span class="text-[10px] font-extrabold px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">5 / 5</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

              <!-- Jenis Pendaftaran -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Pendaftaran <span class="text-red-500">*</span></label>
                <select v-model="form.jenis_pendaftaran"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="Siswa Baru">Siswa Baru</option>
                  <option value="Pindahan">Pindahan</option>
                  <option value="Kembali Sekolah">Kembali Sekolah</option>
                </select>
              </div>

              <!-- Jalur Diterima -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jalur Pendaftaran / Diterima</label>
                <select v-model="form.jalur_diterima"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:border-blue-600 outline-hidden">
                  <option value="Zonasi">Zonasi</option>
                  <option value="Afirmasi">Afirmasi</option>
                  <option value="Prestasi Akademik">Prestasi Akademik</option>
                  <option value="Prestasi Non-akademik">Prestasi Non-akademik</option>
                  <option value="Perpindahan Tugas">Perpindahan Tugas Orang Tua / Wali</option>
                  <option value="Anak Guru / Tenaga Kependidikan">Anak Guru / GTK</option>
                  <option value="Khusus">Jalur Khusus / Kemitraan</option>
                </select>
              </div>

              <!-- Tanggal Masuk -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Masuk / Terdaftar <span class="text-red-500">*</span></label>
                <input type="date" v-model="form.tanggal_masuk"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- Hobi -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Hobi Siswa <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.hobi" placeholder="Contoh: Membaca, Olahraga, Kesenian"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-600 outline-hidden">
              </div>

              <!-- PAUD Formal & Non-Formal -->
              <div class="flex items-center gap-6 md:col-span-2 pt-5">
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                  <input type="checkbox" :checked="form.paud_formal == 1" @change="form.paud_formal = $event.target.checked ? 1 : 0" class="w-4 h-4 accent-blue-600">
                  Pernah Mengikuti PAUD Formal (TK/RA)
                </label>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                  <input type="checkbox" :checked="form.paud_non_formal == 1" @change="form.paud_non_formal = $event.target.checked ? 1 : 0" class="w-4 h-4 accent-blue-600">
                  Pernah PAUD Non-Formal (KB/TPA)
                </label>
              </div>

              <!-- Mutasi Masuk Fields (If Pindahan) -->
              <div v-if="form.jenis_pendaftaran === 'Pindahan'" class="md:col-span-3 p-4 bg-blue-50/60 border border-blue-200 rounded-xl space-y-3">
                <h4 class="text-xs font-black text-blue-900 flex items-center gap-2">
                  <i class="bi bi-box-arrow-in-right"></i> Data Asal Pindahan Siswa
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Nama Sekolah Asal Mutasi</label>
                    <input type="text" v-model="form.sekolah_asal_mutasi" placeholder="SMP/SMA asal"
                           class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-hidden">
                  </div>
                  <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Pindah dari Tingkat / Kelas</label>
                    <input type="text" v-model="form.pindah_dari_tingkat" placeholder="Contoh: VII / X"
                           class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-hidden">
                  </div>
                  <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Surat Keterangan Pindah</label>
                    <input type="text" v-model="form.pindah_no_surat" placeholder="Nomor SKP asal"
                           class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-hidden">
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Form Keluar / Mutasi Siswa (If status !== 'Aktif') -->
          <div v-if="form.status !== 'Aktif'" class="bg-amber-50/70 border border-amber-200 p-6 rounded-2xl shadow-2xs space-y-4">
            <h3 class="text-xs font-black text-amber-900 uppercase tracking-wider flex items-center gap-2">
              <i class="bi bi-box-arrow-right text-amber-600"></i> Form Registrasi Keluar / Mutasi Siswa
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Keluar Karena</label>
                <select v-model="form.keluar_karena"
                        class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:border-amber-600 outline-hidden">
                  <option value="">-- Pilih Alasan Keluar --</option>
                  <option value="Lulus">Lulus</option>
                  <option value="Mutasi">Mutasi / Pindah Sekolah</option>
                  <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                  <option value="Putus Sekolah">Putus Sekolah</option>
                  <option value="Dikeluarkan">Dikeluarkan</option>
                  <option value="Wafat">Wafat / Meninggal Dunia</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Keluar</label>
                <input type="date" v-model="form.tanggal_keluar"
                       class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-mono outline-hidden">
              </div>

              <!-- Mutasi Keluar details -->
              <div v-if="form.keluar_karena === 'Mutasi'">
                <label class="block text-xs font-bold text-slate-700 mb-1">Sekolah Tujuan</label>
                <input type="text" v-model="form.sekolah_tujuan" placeholder="Nama sekolah tujuan"
                       class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-hidden">
              </div>
              <div v-if="form.keluar_karena === 'Mutasi'">
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor SKP</label>
                <input type="text" v-model="form.nomor_skp" placeholder="Nomor Surat Keterangan Pindah"
                       class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-hidden">
              </div>
              <div v-if="form.keluar_karena === 'Mutasi'">
                <label class="block text-xs font-bold text-slate-700 mb-1">Tingkat yang Ditinggalkan</label>
                <input type="text" v-model="form.tingkat_ditinggalkan" placeholder="Contoh: X"
                       class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-hidden">
              </div>
              <div v-if="form.keluar_karena === 'Mutasi'">
                <label class="block text-xs font-bold text-slate-700 mb-1">Diterima di Tingkat</label>
                <input type="text" v-model="form.diterima_di_tingkat" placeholder="Contoh: X"
                       class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-hidden">
              </div>

              <!-- Lulus details -->
              <div v-if="form.keluar_karena === 'Lulus'">
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Ijazah Kelulusan</label>
                <input type="text" v-model="form.nomor_ijazah_kelulusan" placeholder="Nomor blangko ijazah"
                       class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-hidden">
              </div>
              <div v-if="form.keluar_karena === 'Lulus'">
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor SKL</label>
                <input type="text" v-model="form.nomor_skl" placeholder="Nomor SKL"
                       class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-hidden">
              </div>
              <div v-if="form.keluar_karena === 'Lulus'">
                <label class="block text-xs font-bold text-slate-700 mb-1">Rencana Setelah Lulus</label>
                <select v-model="form.keterangan_setelah_lulus"
                        class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold outline-hidden">
                  <option value="">-- Pilih Rencana --</option>
                  <option value="Kuliah">Kuliah / Melanjutkan Studi</option>
                  <option value="Bekerja">Bekerja</option>
                  <option value="Wirausaha">Wirausaha</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>

              <div class="md:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1">Uraian / Alasan Keluar</label>
                <textarea rows="2" v-model="form.alasan_keluar" placeholder="Keterangan resmi alasan keluar atau nama sekolah tujuan"
                          class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-hidden"></textarea>
              </div>
            </div>
          </div>

          <!-- UPLOAD AREA: 11 Modern Document Cards -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-slate-100">
              <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                <i class="bi bi-cloud-arrow-up-fill text-blue-600"></i> Upload Berkas & Dokumen Pendukung (11 Jenis)
              </h3>
              <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">
                Format: PDF, JPG, PNG, WebP (Maks 10 MB)
              </span>
            </div>

            <!-- Petunjuk & Solusi Upload Berkas -->
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-2xl flex items-start gap-3.5 text-xs text-blue-900 shadow-2xs">
              <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center text-base shrink-0 shadow-xs">
                <i class="bi bi-lightbulb-fill"></i>
              </div>
              <div class="space-y-1">
                <h4 class="font-black text-blue-950">Petunjuk & Solusi Pengunggahan Berkas:</h4>
                <p class="text-blue-800 leading-relaxed">
                  Semua berkas foto akan <strong>dikompresi otomatis di bawah 500 KB</strong>. Jika koneksi lambat atau mengalami kendala saat mengunggah banyak berkas sekaligus, silakan <strong>pilih 1 berkas terlebih dahulu lalu klik tombol "Simpan Langkah 5"</strong>, dan lakukan berulang hingga semua berkas terunggah lengkap.
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

              <!-- Upload Card Component Template Helper Function -->
              <!-- 1. Foto Profil -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-person-bounding-box text-blue-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Foto Profil Murid</span>
                  </div>
                  <span v-if="compressingFiles.foto_profil" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.foto_profil" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.foto_profil || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.foto_profil" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center gap-3">
                  <img v-if="filePreviews.foto_profil || existingDocs.foto_profil"
                       :src="filePreviews.foto_profil || existingDocs.foto_profil"
                       class="w-14 h-14 object-cover rounded-lg border border-slate-200 shadow-2xs">
                  <div v-else class="w-14 h-14 bg-slate-100 rounded-lg border border-dashed border-slate-300 flex items-center justify-center text-slate-400">
                    <i class="bi bi-image text-xl"></i>
                  </div>
                  <div class="grow text-xs">
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-700 font-bold hover:bg-slate-100 shadow-2xs">
                      <i class="bi bi-upload"></i> {{ (filePreviews.foto_profil || existingDocs.foto_profil) ? 'Ganti Foto' : 'Pilih Foto' }}
                      <input type="file" accept="image/*" class="hidden" @change="onFileChange($event, 'foto_profil')">
                    </label>
                  </div>
                </div>
              </div>

              <!-- 2. Berkas KK -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-people-fill text-blue-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Kartu Keluarga (KK)</span>
                  </div>
                  <span v-if="compressingFiles.berkas_kk" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_kk" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_kk || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_kk" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_kk ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_kk')">
                  </label>
                  <button v-if="existingDocs.berkas_kk" type="button" @click="openDocViewer(existingDocs.berkas_kk, 'Kartu Keluarga (KK)')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 3. Berkas Akta Lahir -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-file-earmark-person-fill text-blue-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Akta Kelahiran</span>
                  </div>
                  <span v-if="compressingFiles.berkas_akta" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_akta" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_akta || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_akta" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_akta ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_akta')">
                  </label>
                  <button v-if="existingDocs.berkas_akta" type="button" @click="openDocViewer(existingDocs.berkas_akta, 'Akta Kelahiran')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 4. Ijazah SD -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-mortarboard-fill text-blue-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Ijazah SD / MI</span>
                  </div>
                  <span v-if="compressingFiles.berkas_ijazah_sd" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_ijazah_sd" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_ijazah_sd || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_ijazah_sd" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_ijazah_sd ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_ijazah_sd')">
                  </label>
                  <button v-if="existingDocs.berkas_ijazah_sd" type="button" @click="openDocViewer(existingDocs.berkas_ijazah_sd, 'Ijazah SD / MI')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 5. Ijazah SMP -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-mortarboard-fill text-blue-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Ijazah SMP / MTs</span>
                  </div>
                  <span v-if="compressingFiles.berkas_ijazah_smp" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_ijazah_smp" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_ijazah_smp || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_ijazah_smp" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_ijazah_smp ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_ijazah_smp')">
                  </label>
                  <button v-if="existingDocs.berkas_ijazah_smp" type="button" @click="openDocViewer(existingDocs.berkas_ijazah_smp, 'Ijazah SMP / MTs')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 6. Ijazah SMA -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-mortarboard-fill text-blue-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Ijazah SMA / MA</span>
                  </div>
                  <span v-if="compressingFiles.berkas_ijazah_sma" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_ijazah_sma" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_ijazah_sma || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_ijazah_sma" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_ijazah_sma ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_ijazah_sma')">
                  </label>
                  <button v-if="existingDocs.berkas_ijazah_sma" type="button" @click="openDocViewer(existingDocs.berkas_ijazah_sma, 'Ijazah SMA / MA')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 7. Surat Mutasi Masuk -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-box-arrow-in-right text-blue-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Surat Mutasi Masuk</span>
                  </div>
                  <span v-if="compressingFiles.berkas_mutasi_masuk" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_mutasi_masuk" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_mutasi_masuk || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_mutasi_masuk" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_mutasi_masuk ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_mutasi_masuk')">
                  </label>
                  <button v-if="existingDocs.berkas_mutasi_masuk" type="button" @click="openDocViewer(existingDocs.berkas_mutasi_masuk, 'Surat Mutasi Masuk')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 8. Surat Mutasi Keluar -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-box-arrow-right text-amber-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Surat Mutasi Keluar</span>
                  </div>
                  <span v-if="compressingFiles.berkas_mutasi_keluar" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_mutasi_keluar" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_mutasi_keluar || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_mutasi_keluar" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_mutasi_keluar ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_mutasi_keluar')">
                  </label>
                  <button v-if="existingDocs.berkas_mutasi_keluar" type="button" @click="openDocViewer(existingDocs.berkas_mutasi_keluar, 'Surat Mutasi Keluar')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 9. Kartu KIP / PKH -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-credit-card-2-front-fill text-emerald-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Kartu KIP / PKH</span>
                  </div>
                  <span v-if="compressingFiles.berkas_kip" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_kip" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_kip || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_kip" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_kip ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_kip')">
                  </label>
                  <button v-if="existingDocs.berkas_kip" type="button" @click="openDocViewer(existingDocs.berkas_kip, 'Kartu KIP / PKH')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 10. Surat Pernyataan Baru & Ortu -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-file-earmark-text-fill text-slate-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Surat Pernyataan Baru & Ortu</span>
                  </div>
                  <span v-if="compressingFiles.berkas_pernyataan_baru" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_pernyataan_baru" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_pernyataan_baru || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_pernyataan_baru" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_pernyataan_baru ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_pernyataan_baru')">
                  </label>
                  <button v-if="existingDocs.berkas_pernyataan_baru" type="button" @click="openDocViewer(existingDocs.berkas_pernyataan_baru, 'Surat Pernyataan Baru & Orang Tua')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

              <!-- 11. Surat Pernyataan TKA -->
              <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="bi bi-award-fill text-amber-600 text-lg"></i>
                    <span class="text-xs font-bold text-slate-800">Surat Pernyataan TKA</span>
                  </div>
                  <span v-if="compressingFiles.berkas_pernyataan_tka" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <span class="inline-block w-2.5 h-2.5 border-2 border-amber-600 border-t-transparent rounded-full animate-spin"></span> Mengompres...
                  </span>
                  <span v-else-if="filesSelected.berkas_pernyataan_tka" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                    <i class="bi bi-check2"></i> {{ compressionStats.berkas_pernyataan_tka || 'Siap Simpan' }}
                  </span>
                  <span v-else-if="existingDocs.berkas_pernyataan_tka" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Terunggah</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-2xs">
                    <i class="bi bi-upload"></i> {{ existingDocs.berkas_pernyataan_tka ? 'Ganti Berkas' : 'Pilih Berkas' }}
                    <input type="file" accept="image/*,application/pdf" class="hidden" @change="onFileChange($event, 'berkas_pernyataan_tka')">
                  </label>
                  <button v-if="existingDocs.berkas_pernyataan_tka" type="button" @click="openDocViewer(existingDocs.berkas_pernyataan_tka, 'Surat Pernyataan TKA')"
                          class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200 hover:bg-emerald-100">
                    <i class="bi bi-eye"></i> Lihat
                  </button>
                </div>
              </div>

            </div>
          </div>

        </div>

        <!-- 4. Bottom Navigation Bar -->
        <div class="mt-6 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2 w-full md:w-auto">
            <button v-if="currentStep > 1" type="button" @click="prevStep"
                    class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
              <i class="bi bi-chevron-left"></i> Langkah Sebelumnya
            </button>
            <Link v-else href="/pengguna" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold transition shadow-2xs">
              Batal
            </Link>
          </div>

          <div class="flex flex-wrap items-center justify-end gap-2.5 w-full md:w-auto">
            <!-- Tombol Simpan Progress Langkah Ini -->
            <button type="button" @click="saveCurrentStep(currentStep)" :disabled="form.processing"
                    class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-xs font-black shadow-md shadow-blue-500/20 transition flex items-center gap-2"
                    :title="`Simpan data khusus Langkah ${currentStep} (${stepNames[currentStep-1]}) ke database`">
              <i class="bi bi-cloud-arrow-up-fill text-sm" v-if="savingStep !== currentStep"></i>
              <span v-else class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {{ savingStep === currentStep ? 'Menyimpan...' : `Simpan Langkah ${currentStep}` }}
            </button>

            <!-- Tombol Lanjut ke Langkah Berikutnya (jika bukan langkah 5) -->
            <button v-if="currentStep < 5" type="button" @click="nextStep"
                    class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition flex items-center gap-1.5 border border-slate-200 shadow-2xs">
              Lanjut Langkah {{ currentStep + 1 }} <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Tombol Simpan Semua Data (Final) -->
            <button type="submit" :disabled="form.processing"
                    class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-xs font-black shadow-md shadow-emerald-600/25 transition flex items-center gap-2"
                    title="Simpan seluruh data formulir siswa dari Langkah 1 hingga Langkah 5">
              <i class="bi bi-check-all text-base" v-if="!form.processing || savingStep !== null"></i>
              <span v-else class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {{ (form.processing && savingStep === null) ? 'Menyimpan Semua...' : (isCreate ? 'Simpan Semua (Final)' : 'Simpan Semua Data') }}
            </button>
          </div>
        </div>

      </form>

      <!-- Document Viewer Modal -->
      <div v-if="showDocModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden">
          <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <h3 class="text-xs font-bold text-slate-800 flex items-center gap-2">
              <i class="bi bi-file-earmark-text-fill text-blue-600"></i> {{ docModalTitle }}
            </h3>
            <button type="button" @click="showDocModal = false" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="p-4 grow overflow-auto flex items-center justify-center bg-slate-100/50 min-h-[350px]">
            <iframe v-if="isDocModalPdf" :src="docModalUrl" class="w-full h-[550px] rounded-lg border-0"></iframe>
            <img v-else :src="docModalUrl" class="max-h-[500px] object-contain rounded-lg shadow-sm">
          </div>
          <div class="px-5 py-3 border-t border-slate-200 flex justify-between bg-white">
            <a :href="docModalUrl" target="_blank" class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-700 flex items-center gap-1.5">
              <i class="bi bi-box-arrow-up-right"></i> Buka di Tab Baru
            </a>
            <button type="button" @click="showDocModal = false" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold">
              Tutup
            </button>
          </div>
        </div>
      </div>
      <!-- Comprehensive Step-by-Step Validation Modal Popup -->
      <div v-if="showValidationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fade-in">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[88vh] flex flex-col overflow-hidden">
          <!-- Modal Header -->
          <div class="p-5 bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 text-white flex items-start justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3.5">
              <div class="w-11 h-11 rounded-2xl bg-white/20 text-white flex items-center justify-center text-2xl shrink-0 backdrop-blur-md">
                <i class="bi bi-shield-exclamation"></i>
              </div>
              <div>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/25 text-white mb-0.5">
                  Pemeriksaan Kelengkapan Data
                </span>
                <h3 class="text-base font-black tracking-tight leading-tight">
                  Rincian Kolom yang Belum Lengkap
                </h3>
                <p class="text-xs text-white/90 mt-0.5">
                  Berikut rincian langkah dan kolom data wajib yang masih kosong / belum valid:
                </p>
              </div>
            </div>
            <button type="button" @click="showValidationModal = false" class="text-white/80 hover:text-white text-xl p-1">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <!-- Modal Body (Grouped by Step) -->
          <div class="p-5 overflow-y-auto grow space-y-4 bg-slate-50/50">
            <div v-for="stepItem in missingStepsOverview" :key="stepItem.step"
                 :class="[
                   'p-4 rounded-2xl border transition-all duration-200 shadow-2xs',
                   stepItem.isComplete 
                     ? 'bg-emerald-50/50 border-emerald-200' 
                     : 'bg-white border-red-200 ring-1 ring-red-100'
                 ]">
              <!-- Step Header -->
              <div class="flex items-center justify-between gap-2 border-b pb-2.5 mb-3"
                   :class="stepItem.isComplete ? 'border-emerald-100' : 'border-slate-100'">
                <div class="flex items-center gap-2.5">
                  <div :class="[
                    'w-7 h-7 rounded-xl font-black text-xs flex items-center justify-center shadow-xs',
                    stepItem.isComplete ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'
                  ]">
                    <i v-if="stepItem.isComplete" class="bi bi-check-lg"></i>
                    <span v-else>{{ stepItem.step }}</span>
                  </div>
                  <div>
                    <h4 class="text-xs font-black text-slate-800">
                      Langkah {{ stepItem.step }}: {{ stepItem.stepName }}
                    </h4>
                  </div>
                </div>

                <!-- Step Badge Status -->
                <span v-if="stepItem.isComplete"
                      class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-800 flex items-center gap-1">
                  <i class="bi bi-check-circle-fill"></i> Lengkap
                </span>
                <span v-else
                      class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-red-100 text-red-700 flex items-center gap-1">
                  <i class="bi bi-x-circle-fill"></i> {{ stepItem.errorCount }} Kolom Belum Diisi
                </span>
              </div>

              <!-- List Missing Inputs in this Step -->
              <div v-if="!stepItem.isComplete" class="space-y-2">
                <div v-for="err in stepItem.errors" :key="err.field"
                     class="flex items-center justify-between gap-2 p-2.5 rounded-xl bg-red-50/70 border border-red-100/80 text-xs">
                  <div class="flex items-start gap-2">
                    <i class="bi bi-dot text-red-500 text-lg shrink-0 -mt-1"></i>
                    <div>
                      <span class="font-bold text-slate-800">{{ err.label }}:</span>
                      <span class="text-red-600 font-medium ml-1.5">{{ err.message }}</span>
                    </div>
                  </div>
                  <button type="button" @click="jumpToField(stepItem.step, err.field)"
                          class="shrink-0 px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded-lg shadow-xs transition flex items-center gap-1">
                    <span>Isi</span> <i class="bi bi-arrow-right-short"></i>
                  </button>
                </div>
              </div>

              <!-- Complete State Message -->
              <div v-else class="text-xs text-emerald-700 font-semibold flex items-center gap-1.5">
                <i class="bi bi-check2-all text-emerald-600 text-base"></i> Seluruh data wajib pada langkah ini telah lengkap.
              </div>

              <!-- Petunjuk Solusi Upload untuk Langkah 5 -->
              <div v-if="stepItem.step === 5" class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-950 flex items-start gap-2.5">
                <i class="bi bi-info-circle-fill text-amber-600 text-base shrink-0 mt-0.5"></i>
                <div>
                  <span class="font-black text-amber-950">Tips Mengatasi Kendala Upload Berkas:</span>
                  <p class="text-amber-900 text-[11px] mt-0.5 leading-relaxed">
                    Jika Anda mengalami kendala koneksi atau error saat mengunggah banyak berkas, silakan <strong>unggah 1 file terlebih dahulu lalu simpan (klik "Simpan Langkah 5")</strong>, dan lakukan berulang hingga semua berkas terupload lengkap.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="p-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span class="text-xs font-bold text-slate-500">
              Total: {{ Object.keys(allErrors).length }} kolom wajib belum valid
            </span>
            <div class="flex items-center gap-2 w-full sm:w-auto">
              <button type="button" @click="showValidationModal = false"
                      class="w-full sm:w-auto px-4 py-2 border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl transition">
                Tutup
              </button>
              <button type="button"
                      @click="() => {
                        for (let s = 1; s <= 5; s++) {
                          if (!validateStep(s)) {
                            currentStep = s;
                            showValidationModal = false;
                            scrollToFirstError(s);
                            break;
                          }
                        }
                      }"
                      class="w-full sm:w-auto px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                <i class="bi bi-pencil-square"></i> Mulai Lengkapi Data
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
