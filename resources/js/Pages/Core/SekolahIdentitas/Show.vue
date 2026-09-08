<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  identitas: {
    type: Object,
    default: () => ({})
  },
  tenantsList: {
    type: Array,
    default: () => []
  },
  userRole: {
    type: [String, Object],
    default: 'admin_sekolah'
  },
  flash: {
    type: Object,
    default: () => ({})
  }
})

const page = usePage()

// Form reactive state
const form = reactive({
  tenant_id: props.identitas?.id || '',
  nama_sekolah: props.identitas?.nama_sekolah || '',
  npsn: props.identitas?.npsn || '',
  bentuk_pendidikan: props.identitas?.bentuk_pendidikan || '',
  status_sekolah: props.identitas?.status_sekolah || '',
  kurikulum_terapan: props.identitas?.kurikulum_terapan || '',
  akreditasi: props.identitas?.akreditasi || '',
  subdomain: props.identitas?.subdomain || '',
  
  // Wilayah & Kontak
  alamat: props.identitas?.alamat || '',
  rt_rw: props.identitas?.rt_rw || '',
  kode_pos: props.identitas?.kode_pos || '',
  kelurahan: props.identitas?.kelurahan || '',
  kecamatan: props.identitas?.kecamatan || '',
  kabupaten_kota: props.identitas?.kabupaten_kota || '',
  provinsi: props.identitas?.provinsi || '',
  telepon: props.identitas?.telepon || '',
  email: props.identitas?.email || '',
  website: props.identitas?.website || '',
  
  // Manajemen SDM
  nama_kepsek: props.identitas?.nama_kepsek || '',
  pangkat_kepsek: props.identitas?.pangkat_kepsek || '',
  nip_kepsek: props.identitas?.nip_kepsek || '',
  nama_operator: props.identitas?.nama_operator || '',
  email_operator: props.identitas?.email_operator || '',
})

// File state
const logoFile = ref(null)
const logoPreview = ref(
  props.identitas?.logo 
    ? (props.identitas.logo.startsWith('http') ? props.identitas.logo : `/storage/${props.identitas.logo}`) 
    : null
)
const deleteLogoFlag = ref(false)

const certFile = ref(null)
const certFileName = ref('')
const certExisting = ref(props.identitas?.sertifikat_akreditasi || null)
const deleteCertFlag = ref(false)

// Watch for prop changes (e.g. when switching tenants)
watch(() => props.identitas, (newVal) => {
  if (newVal) {
    form.tenant_id = newVal.id || ''
    form.nama_sekolah = newVal.nama_sekolah || ''
    form.npsn = newVal.npsn || ''
    form.bentuk_pendidikan = newVal.bentuk_pendidikan || ''
    form.status_sekolah = newVal.status_sekolah || ''
    form.kurikulum_terapan = newVal.kurikulum_terapan || ''
    form.akreditasi = newVal.akreditasi || ''
    form.subdomain = newVal.subdomain || ''
    form.alamat = newVal.alamat || ''
    form.rt_rw = newVal.rt_rw || ''
    form.kode_pos = newVal.kode_pos || ''
    form.kelurahan = newVal.kelurahan || ''
    form.kecamatan = newVal.kecamatan || ''
    form.kabupaten_kota = newVal.kabupaten_kota || ''
    form.provinsi = newVal.provinsi || ''
    form.telepon = newVal.telepon || ''
    form.email = newVal.email || ''
    form.website = newVal.website || ''
    form.nama_kepsek = newVal.nama_kepsek || ''
    form.pangkat_kepsek = newVal.pangkat_kepsek || ''
    form.nip_kepsek = newVal.nip_kepsek || ''
    form.nama_operator = newVal.nama_operator || ''
    form.email_operator = newVal.email_operator || ''

    logoFile.value = null
    deleteLogoFlag.value = false
    logoPreview.value = newVal.logo 
      ? (newVal.logo.startsWith('http') ? newVal.logo : `/storage/${newVal.logo}`) 
      : null

    certFile.value = null
    certFileName.value = ''
    certExisting.value = newVal.sertifikat_akreditasi || null
    deleteCertFlag.value = false
  }
}, { deep: true })

const errors = ref({})
const isSubmitting = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

// Drag states
const dragStates = reactive({
  logo: false,
  cert: false
})

const isSuperAdmin = computed(() => {
  const roleName = typeof props.userRole === 'object' ? props.userRole?.nama_role : props.userRole
  return roleName === 'super_admin' || (props.tenantsList && props.tenantsList.length > 0)
})

// File Input triggers
const triggerFileInput = (id) => {
  const el = document.getElementById(id)
  if (el) el.click()
}

// Logo handling
const handleLogoChange = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    processLogoFile(file)
  }
}

const processLogoFile = (file) => {
  if (file.size > 2 * 1024 * 1024) {
    alert('Ukuran file logo maksimal 2 MB!')
    return
  }
  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/svg+xml']
  if (!validTypes.includes(file.type)) {
    alert('Format file logo harus berupa gambar (JPG, PNG, WebP, SVG)!')
    return
  }
  logoFile.value = file
  deleteLogoFlag.value = false
  logoPreview.value = URL.createObjectURL(file)
}

const removeLogo = () => {
  logoFile.value = null
  logoPreview.value = null
  deleteLogoFlag.value = true
  const input = document.getElementById('logo_input')
  if (input) input.value = ''
}

// Certificate handling
const handleCertChange = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    processCertFile(file)
  }
}

const processCertFile = (file) => {
  if (file.size > 5 * 1024 * 1024) {
    alert('Ukuran file sertifikat maksimal 5 MB!')
    return
  }
  certFile.value = file
  certFileName.value = file.name
  deleteCertFlag.value = false
}

const removeCert = () => {
  certFile.value = null
  certFileName.value = ''
  certExisting.value = null
  deleteCertFlag.value = true
  const input = document.getElementById('cert_input')
  if (input) input.value = ''
}

// Drag & Drop
const onDragOver = (type) => {
  dragStates[type] = true
}

const onDragLeave = (type) => {
  dragStates[type] = false
}

const onDrop = (type, e) => {
  dragStates[type] = false
  const file = e.dataTransfer?.files?.[0]
  if (!file) return
  if (type === 'logo') {
    processLogoFile(file)
  } else if (type === 'cert') {
    processCertFile(file)
  }
}

// Super Admin switch tenant
const handleTenantSwitch = (e) => {
  const tId = e.target.value
  if (tId) {
    router.visit(`/sekolah/identitas?tenant_id=${tId}`, {
      preserveScroll: true
    })
  }
}

// Format file size
const formatBytes = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const getCertUrl = (path) => {
  if (!path) return '#'
  return path.startsWith('http') ? path : `/storage/${path}`
}

const isPdf = (filename) => {
  if (!filename) return false
  return filename.toLowerCase().endsWith('.pdf')
}

// Submit Form
const submitForm = () => {
  isSubmitting.value = true
  errors.value = {}

  const formData = new FormData()
  
  // Append fields
  Object.keys(form).forEach(key => {
    if (form[key] !== null && form[key] !== undefined) {
      formData.append(key, form[key])
    }
  })

  // Append files if selected
  if (logoFile.value) {
    formData.append('logo', logoFile.value)
  }
  if (deleteLogoFlag.value) {
    formData.append('delete_logo', '1')
  }

  if (certFile.value) {
    formData.append('sertifikat_akreditasi', certFile.value)
  }
  if (deleteCertFlag.value) {
    formData.append('delete_sertifikat', '1')
  }

  // Use POST with method spoofing if needed
  router.post('/sekolah/identitas', formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: (res) => {
      isSubmitting.value = false
      triggerToast('Identitas dan profil sekolah berhasil disimpan!', 'success')
      if (res.props?.flash?.success) {
        triggerToast(res.props.flash.success, 'success')
      }
    },
    onError: (err) => {
      isSubmitting.value = false
      errors.value = err || {}
      triggerToast('Gagal menyimpan profil. Mohon periksa isian data Anda.', 'error')
    }
  })
}

const triggerToast = (msg, type = 'success') => {
  toastMessage.value = msg
  toastType.value = type
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 4000)
}
</script>

<template>
  <AppLayout title="Identitas Sekolah">
    <div class="max-w-7xl mx-auto space-y-6 pb-12">
      
      <!-- Toast Notification -->
      <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div 
          v-if="showToast"
          class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-sm font-semibold backdrop-blur-md"
          :class="toastType === 'success' ? 'bg-emerald-600/95 text-white border-emerald-500 shadow-emerald-500/20' : 'bg-red-600/95 text-white border-red-500 shadow-red-500/20'"
        >
          <i class="bi" :class="toastType === 'success' ? 'bi-check-circle-fill text-lg' : 'bi-exclamation-triangle-fill text-lg'"></i>
          <span>{{ toastMessage }}</span>
          <button @click="showToast = false" class="text-white/80 hover:text-white ml-2 text-xs">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </transition>

      <!-- Super Admin Filter Bar -->
      <div v-if="isSuperAdmin && tenantsList && tenantsList.length > 0" class="bg-white rounded-3xl p-5 shadow-2xs border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold shrink-0 border border-blue-100">
            <i class="bi bi-funnel-fill text-lg"></i>
          </div>
          <div>
            <h5 class="text-sm font-bold text-slate-800 mb-0.5">Filter Data Sekolah Multi-Tenant</h5>
            <p class="text-xs text-slate-500">Sebagai Super Admin, Anda dapat memilih dan mengelola profil setiap lembaga terdaftar.</p>
          </div>
        </div>
        
        <div class="w-full sm:w-80 shrink-0">
          <select 
            :value="identitas?.id" 
            @change="handleTenantSwitch"
            class="w-full h-11 px-3.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white focus:bg-white text-slate-700 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 transition cursor-pointer"
          >
            <option v-for="t in tenantsList" :key="t.id" :value="t.id">
              {{ t.nama_sekolah }} (NPSN: {{ t.npsn || '-' }})
            </option>
          </select>
        </div>
      </div>

      <!-- Modern Hero Gradient Banner -->
      <div class="relative bg-gradient-to-r from-blue-700 via-indigo-600 to-blue-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl overflow-hidden">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        <div class="absolute -left-12 -bottom-12 w-56 h-56 bg-indigo-400/20 rounded-full blur-3xl"></div>
        
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex items-center gap-5">
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 shadow-inner shrink-0 overflow-hidden">
              <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-1" alt="Logo" />
              <i v-else class="bi bi-bank2 text-3xl sm:text-4xl text-white"></i>
            </div>
            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">{{ form.nama_sekolah || identitas?.nama_sekolah || 'Nama Sekolah Belum Diisi' }}</h1>
                <span class="bg-emerald-500/90 text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full shadow-xs border border-emerald-400/30 flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                  {{ identitas?.status === 'active' || identitas?.status === 'aktif' ? 'Tenant Aktif' : 'Aktif' }}
                </span>
              </div>
              <p class="text-white/80 text-xs sm:text-sm flex flex-wrap items-center gap-3">
                <span><strong>NPSN:</strong> <span class="font-mono">{{ form.npsn || '-' }}</span></span>
                <span class="text-white/40">|</span>
                <span><strong>Kurikulum:</strong> {{ form.kurikulum_terapan || '-' }}</span>
                <span class="text-white/40">|</span>
                <span><strong>Akreditasi:</strong> {{ form.akreditasi || '-' }}</span>
              </p>
            </div>
          </div>
          
          <!-- Quick Badges -->
          <div class="flex items-center gap-3 border-t border-white/15 pt-4 md:pt-0 md:border-0 shrink-0">
            <div class="bg-white/10 backdrop-blur-md rounded-2xl px-4 py-2.5 text-center border border-white/15 min-w-[95px]">
              <span class="block text-[10px] uppercase font-bold tracking-wider text-blue-200">Jenjang</span>
              <span class="text-xs sm:text-sm font-bold font-mono">{{ form.bentuk_pendidikan || '-' }}</span>
            </div>
            <div class="bg-white/10 backdrop-blur-md rounded-2xl px-4 py-2.5 text-center border border-white/15 min-w-[95px]">
              <span class="block text-[10px] uppercase font-bold tracking-wider text-blue-200">Status</span>
              <span class="text-xs sm:text-sm font-bold font-mono">{{ form.status_sekolah || '-' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid Layout -->
      <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- LEFT COLUMN: Visual Media & Summary Cards (1 Col) -->
        <div class="space-y-6">
          
          <!-- Logo & Branding Card -->
          <div class="bg-white rounded-3xl p-6 shadow-2xs border border-slate-200/80 flex flex-col items-center text-center relative">
            <h5 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4 w-full text-left flex items-center justify-between">
              <span>Logo Lembaga</span>
              <span class="text-[10px] font-normal lowercase text-slate-400">jpg, png, webp, svg</span>
            </h5>
            
            <!-- Logo Dropzone Viewport -->
            <div 
              class="w-40 h-40 rounded-3xl overflow-hidden border-2 border-dashed flex flex-col items-center justify-center p-3 relative cursor-pointer transition group"
              :class="dragStates.logo ? 'border-blue-500 bg-blue-50/50' : 'border-slate-200 hover:border-blue-400 bg-slate-50 hover:bg-slate-50/80'"
              @dragover.prevent="onDragOver('logo')"
              @dragleave="onDragLeave('logo')"
              @drop.prevent="onDrop('logo', $event)"
              @click="triggerFileInput('logo_input')"
              title="Klik untuk memilih logo atau seret gambar ke sini"
            >
              <input 
                id="logo_input" 
                type="file" 
                class="hidden" 
                accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml"
                @change="handleLogoChange"
              />
              
              <template v-if="logoPreview">
                <img :src="logoPreview" class="w-full h-full object-contain" alt="Logo Sekolah" />
                <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition flex flex-col items-center justify-center text-white gap-1 p-2 text-center rounded-3xl">
                  <i class="bi bi-camera-fill text-xl"></i>
                  <span class="text-[11px] font-bold">Ganti Logo</span>
                </div>
              </template>
              
              <template v-else>
                <div class="w-12 h-12 rounded-2xl bg-blue-100/80 text-blue-600 flex items-center justify-center mb-2 group-hover:scale-110 transition">
                  <i class="bi bi-cloud-arrow-up-fill text-xl"></i>
                </div>
                <span class="text-xs font-bold text-slate-700">Unggah Logo</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Maks. 2 MB</span>
              </template>
            </div>

            <!-- Remove logo button if exists -->
            <button 
              v-if="logoPreview" 
              type="button" 
              @click="removeLogo"
              class="mt-3 text-xs font-bold text-red-500 hover:text-red-700 px-3 py-1 rounded-xl hover:bg-red-50 transition flex items-center gap-1.5"
            >
              <i class="bi bi-trash"></i> Hapus Logo
            </button>

            <!-- Institution Summary Details -->
            <div class="w-full bg-slate-50 rounded-2xl p-4 text-left border border-slate-100 flex flex-col gap-2.5 mt-5">
              <div class="flex justify-between items-center text-xs">
                <span class="text-slate-500 font-medium">Bentuk Lembaga</span>
                <span class="text-slate-800 font-bold">{{ form.bentuk_pendidikan || '-' }}</span>
              </div>
              <div class="flex justify-between items-center text-xs">
                <span class="text-slate-500 font-medium">Status Lembaga</span>
                <span class="text-slate-800 font-bold">{{ form.status_sekolah || '-' }}</span>
              </div>
              <div class="flex justify-between items-center text-xs">
                <span class="text-slate-500 font-medium">Paket Langganan</span>
                <span class="text-blue-600 font-bold font-mono">{{ identitas?.paket_aktif || 'Enterprise SaaS' }}</span>
              </div>
              <div class="flex justify-between items-center text-xs border-t border-slate-200/80 pt-2.5">
                <span class="text-slate-500 font-medium">Sinkronisasi</span>
                <span class="font-bold text-emerald-600 flex items-center gap-1">
                  <i class="bi bi-check-circle-fill"></i> {{ identitas?.status_sinkronisasi || 'Tersinkronisasi' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Legalitas & Sertifikat Akreditasi Card -->
          <div class="bg-white rounded-3xl p-6 shadow-2xs border border-slate-200/80 space-y-4">
            <h5 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
              <span>Legalitas & Akreditasi</span>
              <span class="text-[10px] font-normal lowercase text-slate-400">pdf, jpg, png</span>
            </h5>
            
            <!-- Accreditation Grade Callout -->
            <div class="bg-gradient-to-br from-indigo-50/80 to-blue-50/80 rounded-2xl p-4 border border-blue-100 flex items-center gap-3.5">
              <div class="w-11 h-11 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-black text-lg shadow-sm shrink-0">
                <i class="bi bi-award-fill"></i>
              </div>
              <div>
                <span class="block text-[10px] uppercase font-bold text-slate-500 tracking-wider">Status Akreditasi</span>
                <span class="text-sm font-extrabold text-slate-800">{{ form.akreditasi || 'Belum Terakreditasi' }}</span>
              </div>
            </div>

            <!-- Upload / Download Certificate -->
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-700">Berkas Sertifikat Akreditasi</label>
              
              <!-- Existing Certificate File View -->
              <div v-if="certExisting && !certFile" class="flex items-center justify-between p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                <div class="flex items-center gap-2.5 truncate max-w-[190px]">
                  <i class="bi text-xl" :class="isPdf(certExisting) ? 'bi-file-pdf-fill text-red-500' : 'bi-file-image-fill text-blue-500'"></i>
                  <span class="text-xs text-slate-700 truncate font-semibold font-mono">Sertifikat_Akreditasi</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <a :href="getCertUrl(certExisting)" target="_blank" class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl text-xs font-bold flex items-center gap-1 transition">
                    <i class="bi bi-box-arrow-up-right"></i> Lihat
                  </a>
                  <button type="button" @click="removeCert" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition" title="Hapus Berkas">
                    <i class="bi bi-trash text-sm"></i>
                  </button>
                </div>
              </div>

              <!-- New Selected Certificate File View -->
              <div v-else-if="certFile" class="flex items-center justify-between p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200">
                <div class="flex items-center gap-2.5 truncate max-w-[200px]">
                  <i class="bi bi-check-circle-fill text-emerald-600 text-lg"></i>
                  <div>
                    <p class="text-xs text-slate-800 truncate font-bold font-mono">{{ certFileName }}</p>
                    <span class="text-[10px] text-slate-500">{{ formatBytes(certFile.size) }}</span>
                  </div>
                </div>
                <button type="button" @click="removeCert" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition" title="Batalkan">
                  <i class="bi bi-x-lg text-sm"></i>
                </button>
              </div>

              <!-- Dropzone for Certificate -->
              <div 
                v-else
                class="border-2 border-dashed border-slate-200 hover:border-blue-400 bg-slate-50 hover:bg-slate-50/80 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition"
                :class="dragStates.cert ? 'border-blue-500 bg-blue-50/50' : ''"
                @dragover.prevent="onDragOver('cert')"
                @dragleave="onDragLeave('cert')"
                @drop.prevent="onDrop('cert', $event)"
                @click="triggerFileInput('cert_input')"
              >
                <input 
                  id="cert_input" 
                  type="file" 
                  class="hidden" 
                  accept=".pdf,.jpg,.jpeg,.png,.webp"
                  @change="handleCertChange"
                />
                <i class="bi bi-file-earmark-arrow-up-fill text-2xl text-slate-400 mb-1"></i>
                <span class="text-xs font-bold text-slate-700">Unggah Dokumen Sertifikat</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Format PDF atau Foto (Maks. 5 MB)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Interactive 4-Group Profile Form (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- GRUP 1: DATA IDENTITAS POKOK -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-slate-200/80 space-y-6">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
              <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">1</div>
              <div>
                <h3 class="text-base font-bold text-slate-800">Identitas Pokok & Lembaga</h3>
                <p class="text-xs text-slate-500">Informasi legalitas dasar dan struktur kelembagaan sekolah.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <!-- Nama Sekolah -->
              <div class="sm:col-span-2 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Nama Sekolah Resmi <span class="text-red-500">*</span></label>
                <input 
                  v-model="form.nama_sekolah" 
                  type="text" 
                  required
                  placeholder="Contoh: SMA Teladan Bangsa"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                  :class="{'border-red-400 bg-red-50/20': errors.nama_sekolah}"
                />
                <span v-if="errors.nama_sekolah" class="text-xs text-red-500 font-medium">{{ errors.nama_sekolah }}</span>
              </div>

              <!-- NPSN -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">NPSN Resmi <span class="text-red-500">*</span></label>
                <input 
                  v-model="form.npsn" 
                  type="text" 
                  required
                  placeholder="Contoh: 20512345"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-bold font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                  :class="{'border-red-400 bg-red-50/20': errors.npsn}"
                />
                <span v-if="errors.npsn" class="text-xs text-red-500 font-medium">{{ errors.npsn }}</span>
              </div>

              <!-- Bentuk Pendidikan -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Bentuk Pendidikan <span class="text-red-500">*</span></label>
                <select 
                  v-model="form.bentuk_pendidikan" 
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white cursor-pointer"
                >
                  <option value="">-- Pilih Bentuk Pendidikan --</option>
                  <option value="SD">SD (Sekolah Dasar)</option>
                  <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                  <option value="SMA">SMA (Sekolah Menengah Atas)</option>
                  <option value="SMK">SMK (Sekolah Menengah Kejuruan)</option>
                  <option value="MI">MI (Madrasah Ibtidaiyah)</option>
                  <option value="MTs">MTs (Madrasah Tsanawiyah)</option>
                  <option value="MA">MA (Madrasah Aliyah)</option>
                  <option value="SLB">SLB (Sekolah Luar Biasa)</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>

              <!-- Status Sekolah -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Status Sekolah <span class="text-red-500">*</span></label>
                <select 
                  v-model="form.status_sekolah" 
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white cursor-pointer"
                >
                  <option value="">-- Pilih Status Sekolah --</option>
                  <option value="Negeri">Negeri</option>
                  <option value="Swasta">Swasta</option>
                </select>
              </div>

              <!-- Kurikulum Terapan -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Kurikulum Terapan</label>
                <input 
                  v-model="form.kurikulum_terapan" 
                  type="text" 
                  placeholder="Kurikulum Merdeka / K13"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Subdomain Platform -->
              <div class="sm:col-span-2 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Subdomain SaaS Platform</label>
                <div class="flex items-center">
                  <span class="h-11 px-3.5 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl text-xs font-mono text-slate-500 flex items-center shrink-0">https://</span>
                  <input 
                    v-model="form.subdomain" 
                    type="text" 
                    placeholder="nama-sekolah"
                    class="w-full h-11 px-3 border border-slate-200 text-slate-800 text-xs sm:text-sm font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                  />
                  <span class="h-11 px-3.5 bg-slate-100 border border-l-0 border-slate-200 rounded-r-xl text-xs font-mono text-slate-500 flex items-center shrink-0">.sinta-saas.id</span>
                </div>
              </div>
            </div>
          </div>

          <!-- GRUP 2: DATA WILAYAH & KONTAK INSTANSI -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-slate-200/80 space-y-6">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
              <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">2</div>
              <div>
                <h3 class="text-base font-bold text-slate-800">Wilayah & Kontak Instansi</h3>
                <p class="text-xs text-slate-500">Alamat operasional resmi, telekomunikasi, dan media komunikasi digital.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
              <!-- Alamat Lengkap -->
              <div class="sm:col-span-3 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Alamat Jalan & Nomor <span class="text-red-500">*</span></label>
                <textarea 
                  v-model="form.alamat" 
                  rows="2"
                  placeholder="Jl. Raya Pendidikan No. 123..."
                  class="w-full p-3.5 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                  :class="{'border-red-400 bg-red-50/20': errors.alamat}"
                ></textarea>
                <span v-if="errors.alamat" class="text-xs text-red-500 font-medium">{{ errors.alamat }}</span>
              </div>

              <!-- RT / RW -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">RT / RW</label>
                <input 
                  v-model="form.rt_rw" 
                  type="text" 
                  placeholder="004/002"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Kode Pos -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Kode Pos</label>
                <input 
                  v-model="form.kode_pos" 
                  type="text" 
                  placeholder="60181"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Kelurahan / Desa -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Kelurahan / Desa</label>
                <input 
                  v-model="form.kelurahan" 
                  type="text" 
                  placeholder="Kelurahan..."
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Kecamatan -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Kecamatan</label>
                <input 
                  v-model="form.kecamatan" 
                  type="text" 
                  placeholder="Kecamatan..."
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Kabupaten / Kota -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Kabupaten / Kota</label>
                <input 
                  v-model="form.kabupaten_kota" 
                  type="text" 
                  placeholder="Kabupaten/Kota..."
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Provinsi -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Provinsi</label>
                <input 
                  v-model="form.provinsi" 
                  type="text" 
                  placeholder="Provinsi..."
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Nomor Telepon -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">No. Telepon Instansi</label>
                <input 
                  v-model="form.telepon" 
                  type="text" 
                  placeholder="031-1234567"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Email Resmi -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Email Resmi Lembaga</label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  placeholder="info@sekolah.sch.id"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                  :class="{'border-red-400 bg-red-50/20': errors.email}"
                />
                <span v-if="errors.email" class="text-xs text-red-500 font-medium">{{ errors.email }}</span>
              </div>

              <!-- Website Sekolah -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Website Resmi</label>
                <input 
                  v-model="form.website" 
                  type="text" 
                  placeholder="https://www.sekolah.sch.id"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>
            </div>
          </div>

          <!-- GRUP 3: MANAJEMEN SDM & KEPEMIMPINAN -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-slate-200/80 space-y-6">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
              <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">3</div>
              <div>
                <h3 class="text-base font-bold text-slate-800">Manajemen SDM & Kepemimpinan</h3>
                <p class="text-xs text-slate-500">Penanggung jawab eksekutif dan operator sistem sekolah.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
              <!-- Nama Kepala Sekolah -->
              <div class="sm:col-span-2 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Nama Kepala Sekolah (Beserta Gelar)</label>
                <input 
                  v-model="form.nama_kepsek" 
                  type="text" 
                  placeholder="Drs. H. Ahmad Fauzi, M.Pd."
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Pangkat Kepsek -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Pangkat / Golongan</label>
                <input 
                  v-model="form.pangkat_kepsek" 
                  type="text" 
                  placeholder="Pembina Tk. I / IV-b"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- NIP Kepsek -->
              <div class="sm:col-span-3 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">NIP Kepala Sekolah</label>
                <input 
                  v-model="form.nip_kepsek" 
                  type="text" 
                  placeholder="19750101XXXXXXXXXX"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Nama Operator -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Nama Operator Sekolah / TI</label>
                <input 
                  v-model="form.nama_operator" 
                  type="text" 
                  placeholder="Nama Penanggung Jawab Operator"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                />
              </div>

              <!-- Email Operator -->
              <div class="sm:col-span-2 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Email / Akun Operator</label>
                <input 
                  v-model="form.email_operator" 
                  type="email" 
                  placeholder="operator@sekolah.sch.id"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                  :class="{'border-red-400 bg-red-50/20': errors.email_operator}"
                />
                <span v-if="errors.email_operator" class="text-xs text-red-500 font-medium">{{ errors.email_operator }}</span>
              </div>
            </div>
          </div>

          <!-- GRUP 4: STATUS AKREDITASI -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-slate-200/80 space-y-6">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
              <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">4</div>
              <div>
                <h3 class="text-base font-bold text-slate-800">Status Akreditasi & Legalitas</h3>
                <p class="text-xs text-slate-500">Peringkat akreditasi BAN-S/M atau badan akreditasi resmi.</p>
              </div>
            </div>

            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-slate-700">Peringkat / Status Akreditasi <span class="text-red-500">*</span></label>
              <input 
                v-model="form.akreditasi" 
                type="text" 
                placeholder="Contoh: Terakreditasi A (Unggul)"
                class="w-full h-11 px-4 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                :class="{'border-red-400 bg-red-50/20': errors.akreditasi}"
              />
              <span v-if="errors.akreditasi" class="text-xs text-red-500 font-medium">{{ errors.akreditasi }}</span>
            </div>
          </div>

          <!-- Sticky Action Bar -->
          <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-xs text-slate-500">
              <i class="bi bi-info-circle text-blue-600"></i>
              <span>Pastikan data bertanda bintang <span class="text-red-500 font-bold">*</span> terisi secara valid.</span>
            </div>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
              <button 
                type="submit" 
                :disabled="isSubmitting"
                class="w-full sm:w-auto h-12 px-8 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm rounded-2xl shadow-lg shadow-blue-500/25 hover:shadow-blue-600/35 transition flex items-center justify-center gap-2.5 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
              >
                <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <i v-else class="bi bi-cloud-check-fill text-lg"></i>
                <span>{{ isSubmitting ? 'Menyimpan Profil...' : 'Simpan Perubahan' }}</span>
              </button>
            </div>
          </div>

        </div>

      </form>
    </div>
  </AppLayout>
</template>
