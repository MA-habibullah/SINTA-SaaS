<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import axios from 'axios'

const props = defineProps({
  categories: {
    type: Array,
    default: () => []
  },
  unreadCount: {
    type: Number,
    default: 0
  },
  isSuperAdmin: {
    type: Boolean,
    default: false
  },
  userRole: {
    type: String,
    default: 'user'
  },
  initialFaqs: {
    type: Array,
    default: () => []
  },
  cannedResponses: {
    type: Array,
    default: () => []
  }
})

// Tab Navigation State
const activeTab = ref('tickets') // 'tickets', 'feature_requests', 'faq', 'admin_manage', 'hotline'

// Modul List for Feature Requests (16 Modul SINTA)
const modulList = [
  'Akademik',
  'Siswa',
  'Keuangan',
  'Perpustakaan',
  'BK',
  'Absensi',
  'Kepegawaian',
  'Sarpras',
  'Persuratan',
  'Kesiswaan',
  'PDSS',
  'Tracer',
  'SMK',
  'CMS',
  'Sistem',
  'Core'
]

// ==========================================
// SEARCHABLE DROPDOWN OPTIONS DATA
// ==========================================
const ticketStatusFilterOptions = [
  { id: '', label: 'Semua Status' },
  { id: 'Menunggu', label: 'Menunggu' },
  { id: 'Diproses', label: 'Diproses' },
  { id: 'Selesai', label: 'Selesai' },
  { id: 'Batal', label: 'Batal' }
]

const ticketCategoryFilterOptions = computed(() => [
  { id: '', label: 'Semua Kategori' },
  ...props.categories.map(c => ({
    id: c.id,
    label: c.nama_kategori,
    subLabel: `SLA ≤ ${c.sla_hours} Jam`
  }))
])

const ticketCategoryFormOptions = computed(() =>
  props.categories.map(c => ({
    id: c.id,
    label: c.nama_kategori,
    subLabel: `SLA ≤ ${c.sla_hours} Jam`
  }))
)

const ticketUrgencyOptions = [
  { id: 'Rendah', label: 'Rendah', subLabel: 'Pertanyaan / Konsultasi' },
  { id: 'Sedang', label: 'Sedang', subLabel: 'Kendala Penggunaan Minor' },
  { id: 'Tinggi', label: 'Tinggi', subLabel: 'Fitur Error / Gagal Simpan' },
  { id: 'Kritis', label: 'Kritis', subLabel: 'Sistem Blank / Server Error' }
]

const ticketStatusChangeOptions = computed(() => {
  if (props.isSuperAdmin) {
    return [
      { id: 'Menunggu', label: 'Menunggu' },
      { id: 'Diproses', label: 'Diproses' },
      { id: 'Selesai', label: 'Tandai Selesai' },
      { id: 'Batal', label: 'Batalkan Tiket' }
    ]
  }
  return [
    { id: 'Selesai', label: 'Tandai Selesai' },
    { id: 'Batal', label: 'Batalkan Tiket' }
  ]
})

const featureModulFilterOptions = [
  { id: '', label: 'Semua Modul (16 Modul)' },
  ...modulList.map(m => ({ id: m, label: `Modul ${m}` }))
]

const featureModulFormOptions = modulList.map(m => ({
  id: m,
  label: `Modul ${m}`
}))

const featureStatusFilterOptions = [
  { id: '', label: 'Semua Status' },
  { id: 'Review', label: 'Tahap Review' },
  { id: 'Disetujui', label: 'Disetujui' },
  { id: 'Sedang Dikembangkan', label: 'Sedang Dikembangkan' },
  { id: 'Selesai', label: 'Telah Rilis' },
  { id: 'Ditolak', label: 'Ditolak' }
]

const featureUrgencyOptions = [
  { id: 'Rendah', label: 'Rendah', subLabel: 'Penyempurnaan Tampilan' },
  { id: 'Sedang', label: 'Sedang', subLabel: 'Peningkatan Efisiensi Kerja' },
  { id: 'Tinggi', label: 'Tinggi', subLabel: 'Sangat Dibutuhkan Rutinitas' },
  { id: 'Sangat Mendesak', label: 'Sangat Mendesak', subLabel: 'Regulasi / Kebutuhan Kritis' }
]

const featureSortOptions = [
  { id: 'popular', label: '🔥 Paling Banyak Vote' },
  { id: 'newest', label: '✨ Terbaru Diajukan' }
]

const featureManageStatusOptions = [
  { id: 'Review', label: 'Review (Sedang Dikaji)' },
  { id: 'Disetujui', label: 'Disetujui (Masuk Antrian Rilis)' },
  { id: 'Sedang Dikembangkan', label: 'Sedang Dikembangkan (In Progress)' },
  { id: 'Selesai', label: 'Selesai (Telah Rilis di Aplikasi)' },
  { id: 'Ditolak', label: 'Ditolak' }
]

// ==========================================
// TICKET LISTING STATE
// ==========================================
const tickets = ref([])
const loadingTickets = ref(false)
const filterStatus = ref('')
const filterCategory = ref('')
const searchQuery = ref('')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0
})

const localUnreadCount = ref(props.unreadCount)

// Modal: Buat Tiket
const isCreateModalOpen = ref(false)
const createForm = reactive({
  judul: '',
  category_id: '',
  urgensi: 'Sedang',
  deskripsi: '',
  lampiran: null,
  last_url: typeof window !== 'undefined' ? window.location.href : ''
})
const createLampiranPreview = ref(null)
const submittingTicket = ref(false)
const liveFaqs = ref([])
const liveFaqLoading = ref(false)
let debounceTimer = null

// Modal: Detail & Percakapan Tiket
const isDetailModalOpen = ref(false)
const activeTicket = ref(null)
const activeReplies = ref([])
const loadingDetail = ref(false)
const replyText = ref('')
const replyLampiran = ref(null)
const sendingReply = ref(false)
const updatingStatus = ref(false)
const selectedCanned = ref('')
const chatContainerRef = ref(null)

// ==========================================
// FEATURE REQUESTS STATE
// ==========================================
const featureRequests = ref([])
const loadingFeatures = ref(false)
const filterFeatureModul = ref('')
const filterFeatureStatus = ref('')
const sortFeature = ref('popular')
const searchFeature = ref('')
const featurePagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0
})
const featureStats = ref({
  total: 0,
  sedang_dikembangkan: 0,
  selesai: 0,
  disetujui: 0
})

// Modal: Ajukan Feature Request Baru
const isCreateFeatureModalOpen = ref(false)
const featureForm = reactive({
  judul_fitur: '',
  modul_terkait: 'Akademik',
  urgensi_bisnis: 'Sedang',
  deskripsi_kebutuhan: '',
  ekspektasi_solusi: '',
  lampiran: null
})
const featureLampiranPreview = ref(null)
const submittingFeature = ref(false)

// Modal: Kelola Status Feature Request (Super Admin)
const isManageFeatureModalOpen = ref(false)
const activeFeature = ref(null)
const manageFeatureForm = reactive({
  status: 'Review',
  estimasi_rilis: '',
  catatan_pengembang: ''
})
const updatingFeatureStatus = ref(false)

// ==========================================
// FAQ & ADMIN MANAGE STATE
// ==========================================
const faqSearchQuery = ref('')
const selectedFaqCategory = ref(0)
const openedFaqId = ref(null)
const allFaqs = ref(props.initialFaqs || [])

const allCannedResponses = ref(props.cannedResponses || [])
const cannedSelectOptions = computed(() => [
  { id: '', label: 'Pilih template respon cepat...' },
  ...allCannedResponses.value.map(c => ({
    id: c.id,
    label: c.judul,
    subLabel: c.konten
  }))
])

const newFaqForm = reactive({
  category_id: '',
  pertanyaan: '',
  jawaban: '',
  is_active: true
})
const submittingFaq = ref(false)

const newCannedForm = reactive({
  judul: '',
  konten: ''
})
const submittingCanned = ref(false)

// Toast Alert Notification
const toast = reactive({
  show: false,
  message: '',
  type: 'success'
})

function showToast(msg, type = 'success') {
  toast.message = msg
  toast.type = type
  toast.show = true
  setTimeout(() => {
    toast.show = false
  }, 4000)
}

// ==========================================
// TICKET METHODS
// ==========================================
async function fetchTickets(page = 1) {
  loadingTickets.value = true
  try {
    const params = {
      page,
      status: filterStatus.value || undefined,
      category_id: filterCategory.value || undefined,
      search: searchQuery.value.trim() || undefined
    }
    const res = await axios.get('/bantuan/tickets', { params })
    if (res.data && res.data.success) {
      tickets.value = res.data.data.data || []
      pagination.value = {
        current_page: res.data.data.current_page || 1,
        last_page: res.data.data.last_page || 1,
        total: res.data.data.total || 0,
        from: res.data.data.from || 0,
        to: res.data.data.to || 0
      }
    }
  } catch (err) {
    console.error('Error fetching tickets:', err)
  } finally {
    loadingTickets.value = false
  }
}

async function refreshUnreadCount() {
  try {
    const res = await axios.get('/bantuan/unread-count')
    if (res.data && res.data.success) {
      localUnreadCount.value = res.data.unread_count || 0
    }
  } catch (err) {
    // silent
  }
}

function openCreateModal() {
  createForm.judul = ''
  createForm.category_id = props.categories.length > 0 ? props.categories[0].id : ''
  createForm.urgensi = 'Sedang'
  createForm.deskripsi = ''
  createForm.lampiran = null
  createLampiranPreview.value = null
  liveFaqs.value = []
  isCreateModalOpen.value = true
}

function closeCreateModal() {
  isCreateModalOpen.value = false
}

function handleFileChange(e) {
  const file = e.target.files[0]
  if (file) {
    if (file.size > 3 * 1024 * 1024) {
      showToast('Ukuran file maksimal 3MB.', 'error')
      e.target.value = ''
      return
    }
    createForm.lampiran = file
    if (file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (event) => {
        createLampiranPreview.value = event.target.result
      }
      reader.readAsDataURL(file)
    } else {
      createLampiranPreview.value = null
    }
  }
}

function removeAttachment() {
  createForm.lampiran = null
  createLampiranPreview.value = null
}

function onJudulInput() {
  clearTimeout(debounceTimer)
  const q = createForm.judul.trim()
  if (q.length < 3) {
    liveFaqs.value = []
    return
  }

  debounceTimer = setTimeout(async () => {
    liveFaqLoading.value = true
    try {
      const res = await axios.get('/bantuan/faqs/search', { params: { q } })
      if (res.data && res.data.success) {
        liveFaqs.value = res.data.data || []
      }
    } catch (err) {
      // silent
    } finally {
      liveFaqLoading.value = false
    }
  }, 350)
}

async function submitNewTicket() {
  if (!createForm.judul || !createForm.deskripsi || !createForm.category_id) {
    showToast('Judul, Kategori, dan Deskripsi wajib diisi.', 'error')
    return
  }

  submittingTicket.value = true
  const formData = new FormData()
  formData.append('judul', createForm.judul)
  formData.append('category_id', createForm.category_id)
  formData.append('urgensi', createForm.urgensi)
  formData.append('deskripsi', createForm.deskripsi)
  formData.append('last_url', window.location.href)
  if (createForm.lampiran) {
    formData.append('lampiran', createForm.lampiran)
  }

  try {
    const res = await axios.post('/bantuan/tickets', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (res.data && res.data.success) {
      showToast(res.data.message || 'Tiket berhasil dibuat!')
      closeCreateModal()
      fetchTickets(1)
      refreshUnreadCount()
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal membuat tiket.'
    showToast(msg, 'error')
  } finally {
    submittingTicket.value = false
  }
}

async function openTicketDetail(ticketId) {
  loadingDetail.value = true
  activeTicket.value = null
  activeReplies.value = []
  replyText.value = ''
  replyLampiran.value = null
  selectedCanned.value = ''
  isDetailModalOpen.value = true

  try {
    const res = await axios.get(`/bantuan/tickets/${ticketId}`)
    if (res.data && res.data.success) {
      activeTicket.value = res.data.data
      activeReplies.value = res.data.data.replies || []
      refreshUnreadCount()
      const found = tickets.value.find(t => t.id === ticketId)
      if (found) {
        if (props.isSuperAdmin) found.admin_unread = false
        else found.user_unread = false
      }
      scrollToChatBottom()
    }
  } catch (err) {
    showToast('Gagal memuat percakapan tiket.', 'error')
  } finally {
    loadingDetail.value = false
  }
}

function closeDetailModal() {
  isDetailModalOpen.value = false
  activeTicket.value = null
}

function scrollToChatBottom() {
  nextTick(() => {
    if (chatContainerRef.value) {
      chatContainerRef.value.scrollTop = chatContainerRef.value.scrollHeight
    }
  })
}

async function sendReply() {
  if (!replyText.value.trim() || !activeTicket.value) return

  sendingReply.value = true
  const formData = new FormData()
  formData.append('pesan', replyText.value.trim())
  if (replyLampiran.value) {
    formData.append('lampiran', replyLampiran.value)
  }

  try {
    const res = await axios.post(`/bantuan/tickets/${activeTicket.value.id}/reply`, formData)
    if (res.data && res.data.success) {
      activeReplies.value.push(res.data.data)
      replyText.value = ''
      replyLampiran.value = null
      selectedCanned.value = ''
      if (activeTicket.value.status === 'Menunggu' && props.isSuperAdmin) {
        activeTicket.value.status = 'Diproses'
      }
      showToast('Balasan pesan berhasil terkirim.')
      scrollToChatBottom()
      fetchTickets(pagination.value.current_page)
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal mengirim balasan.'
    showToast(msg, 'error')
  } finally {
    sendingReply.value = false
  }
}

function applyCannedResponse(cannedId) {
  if (!cannedId) return
  const found = allCannedResponses.value.find(c => c.id === cannedId)
  if (found) {
    replyText.value = found.konten
  }
}

async function changeTicketStatus(newStatus) {
  if (!activeTicket.value || activeTicket.value.status === newStatus) return
  updatingStatus.value = true
  try {
    const res = await axios.put(`/bantuan/tickets/${activeTicket.value.id}/status`, { status: newStatus })
    if (res.data && res.data.success) {
      activeTicket.value.status = newStatus
      showToast(`Status tiket berhasil diubah menjadi: ${newStatus}`)
      fetchTickets(pagination.value.current_page)
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal memperbarui status tiket.'
    showToast(msg, 'error')
  } finally {
    updatingStatus.value = false
  }
}

// ==========================================
// FEATURE REQUESTS METHODS
// ==========================================
async function fetchFeatureRequests(page = 1) {
  loadingFeatures.value = true
  try {
    const params = {
      page,
      modul: filterFeatureModul.value || undefined,
      status: filterFeatureStatus.value || undefined,
      sort: sortFeature.value || 'popular',
      search: searchFeature.value.trim() || undefined
    }
    const res = await axios.get('/bantuan/feature-requests', { params })
    if (res.data && res.data.success) {
      featureRequests.value = res.data.data.data || []
      featurePagination.value = {
        current_page: res.data.data.current_page || 1,
        last_page: res.data.data.last_page || 1,
        total: res.data.data.total || 0,
        from: res.data.data.from || 0,
        to: res.data.data.to || 0
      }
      if (res.data.stats) {
        featureStats.value = res.data.stats
      }
    }
  } catch (err) {
    console.error('Error fetching feature requests:', err)
  } finally {
    loadingFeatures.value = false
  }
}

function openCreateFeatureModal() {
  featureForm.judul_fitur = ''
  featureForm.modul_terkait = 'Akademik'
  featureForm.urgensi_bisnis = 'Sedang'
  featureForm.deskripsi_kebutuhan = ''
  featureForm.ekspektasi_solusi = ''
  featureForm.lampiran = null
  featureLampiranPreview.value = null
  isCreateFeatureModalOpen.value = true
}

function closeCreateFeatureModal() {
  isCreateFeatureModalOpen.value = false
}

function handleFeatureFileChange(e) {
  const file = e.target.files[0]
  if (file) {
    if (file.size > 3 * 1024 * 1024) {
      showToast('Ukuran file maksimal 3MB.', 'error')
      e.target.value = ''
      return
    }
    featureForm.lampiran = file
    if (file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (event) => {
        featureLampiranPreview.value = event.target.result
      }
      reader.readAsDataURL(file)
    } else {
      featureLampiranPreview.value = null
    }
  }
}

async function submitNewFeatureRequest() {
  if (!featureForm.judul_fitur || !featureForm.deskripsi_kebutuhan || !featureForm.modul_terkait) {
    showToast('Judul, Modul, dan Deskripsi Kebutuhan wajib diisi.', 'error')
    return
  }

  submittingFeature.value = true
  const formData = new FormData()
  formData.append('judul_fitur', featureForm.judul_fitur)
  formData.append('modul_terkait', featureForm.modul_terkait)
  formData.append('urgensi_bisnis', featureForm.urgensi_bisnis)
  formData.append('deskripsi_kebutuhan', featureForm.deskripsi_kebutuhan)
  if (featureForm.ekspektasi_solusi) {
    formData.append('ekspektasi_solusi', featureForm.ekspektasi_solusi)
  }
  if (featureForm.lampiran) {
    formData.append('lampiran', featureForm.lampiran)
  }

  try {
    const res = await axios.post('/bantuan/feature-requests', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (res.data && res.data.success) {
      showToast(res.data.message || 'Usulan fitur berhasil diajukan!')
      closeCreateFeatureModal()
      fetchFeatureRequests(1)
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal mengajukan fitur baru.'
    showToast(msg, 'error')
  } finally {
    submittingFeature.value = false
  }
}

async function voteFeature(feature) {
  try {
    const res = await axios.post(`/bantuan/feature-requests/${feature.id}/vote`)
    if (res.data && res.data.success) {
      feature.has_voted = res.data.has_voted
      feature.votes_count = res.data.votes_count
      showToast(res.data.message, res.data.has_voted ? 'success' : 'info')
    }
  } catch (err) {
    showToast('Gagal memberikan vote suara.', 'error')
  }
}

function openManageFeatureModal(feature) {
  activeFeature.value = feature
  manageFeatureForm.status = feature.status || 'Review'
  manageFeatureForm.estimasi_rilis = feature.estimasi_rilis || ''
  manageFeatureForm.catatan_pengembang = feature.catatan_pengembang || ''
  isManageFeatureModalOpen.value = true
}

function closeManageFeatureModal() {
  isManageFeatureModalOpen.value = false
  activeFeature.value = null
}

async function submitManageFeature() {
  if (!activeFeature.value) return
  updatingFeatureStatus.value = true
  try {
    const res = await axios.put(`/bantuan/feature-requests/${activeFeature.value.id}/status`, manageFeatureForm)
    if (res.data && res.data.success) {
      showToast('Status usulan fitur berhasil diperbarui!')
      closeManageFeatureModal()
      fetchFeatureRequests(featurePagination.value.current_page)
    }
  } catch (err) {
    showToast('Gagal memperbarui status fitur.', 'error')
  } finally {
    updatingFeatureStatus.value = false
  }
}

// ==========================================
// FAQ & ADMIN MANAGE METHODS
// ==========================================
const filteredFaqs = computed(() => {
  let list = allFaqs.value || []
  if (selectedFaqCategory.value) {
    list = list.filter(f => f.category_id === Number(selectedFaqCategory.value))
  }
  if (faqSearchQuery.value.trim()) {
    const q = faqSearchQuery.value.toLowerCase()
    list = list.filter(f =>
      (f.pertanyaan && f.pertanyaan.toLowerCase().includes(q)) ||
      (f.jawaban && f.jawaban.toLowerCase().includes(q))
    )
  }
  return list
})

function toggleFaq(id) {
  openedFaqId.value = openedFaqId.value === id ? null : id
}

async function submitNewFaq() {
  if (!newFaqForm.pertanyaan || !newFaqForm.jawaban) {
    showToast('Pertanyaan dan Jawaban FAQ wajib diisi.', 'error')
    return
  }
  submittingFaq.value = true
  try {
    const res = await axios.post('/bantuan/faqs', newFaqForm)
    if (res.data && res.data.success) {
      allFaqs.value.unshift(res.data.data)
      newFaqForm.pertanyaan = ''
      newFaqForm.jawaban = ''
      showToast('FAQ berhasil ditambahkan!')
    }
  } catch (err) {
    showToast('Gagal menambahkan FAQ.', 'error')
  } finally {
    submittingFaq.value = false
  }
}

async function deleteFaqItem(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus FAQ ini?')) return
  try {
    const res = await axios.delete(`/bantuan/faqs/${id}`)
    if (res.data && res.data.success) {
      allFaqs.value = allFaqs.value.filter(f => f.id !== id)
      showToast('FAQ berhasil dihapus.')
    }
  } catch (err) {
    showToast('Gagal menghapus FAQ.', 'error')
  }
}

async function submitNewCanned() {
  if (!newCannedForm.judul || !newCannedForm.konten) {
    showToast('Judul dan Konten balasan cepat wajib diisi.', 'error')
    return
  }
  submittingCanned.value = true
  try {
    const res = await axios.post('/bantuan/canned-responses', newCannedForm)
    if (res.data && res.data.success) {
      allCannedResponses.value.push(res.data.data)
      newCannedForm.judul = ''
      newCannedForm.konten = ''
      showToast('Template respon cepat berhasil disimpan!')
    }
  } catch (err) {
    showToast('Gagal menyimpan respon cepat.', 'error')
  } finally {
    submittingCanned.value = false
  }
}

async function deleteCannedItem(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus template ini?')) return
  try {
    const res = await axios.delete(`/bantuan/canned-responses/${id}`)
    if (res.data && res.data.success) {
      allCannedResponses.value = allCannedResponses.value.filter(c => c.id !== id)
      showToast('Template berhasil dihapus.')
    }
  } catch (err) {
    showToast('Gagal menghapus template.', 'error')
  }
}

// Helpers
function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getUrgencyBadge(urgensi) {
  switch (urgensi) {
    case 'Kritis':
    case 'Sangat Mendesak':
      return 'bg-rose-100 text-rose-700 border-rose-200 animate-pulse font-bold'
    case 'Tinggi':
      return 'bg-amber-100 text-amber-800 border-amber-200 font-semibold'
    case 'Sedang':
      return 'bg-blue-100 text-blue-700 border-blue-200'
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200'
  }
}

function getStatusBadge(status) {
  switch (status) {
    case 'Menunggu':
    case 'Review':
      return 'bg-amber-50 text-amber-700 border-amber-200 ring-1 ring-amber-400/30'
    case 'Diproses':
    case 'Disetujui':
      return 'bg-blue-50 text-blue-700 border-blue-200 ring-1 ring-blue-400/30'
    case 'Sedang Dikembangkan':
      return 'bg-purple-50 text-purple-700 border-purple-200 ring-1 ring-purple-400/30 font-bold'
    case 'Selesai':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-1 ring-emerald-400/30'
    case 'Batal':
    case 'Ditolak':
      return 'bg-slate-100 text-slate-600 border-slate-200'
    default:
      return 'bg-slate-50 text-slate-600 border-slate-200'
  }
}

onMounted(() => {
  fetchTickets(1)
  fetchFeatureRequests(1)
})
</script>

<template>
  <AppLayout title="Pusat Bantuan & Layanan Tiket">
    <Head title="Pusat Bantuan & Layanan Tiket" />

    <!-- Toast Notification Popup -->
    <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="toast.show" class="fixed top-5 right-5 z-50 max-w-md bg-white rounded-2xl shadow-2xl border p-4 flex items-center gap-3" :class="toast.type === 'error' ? 'border-rose-200 bg-rose-50/90 text-rose-900' : 'border-emerald-200 bg-emerald-50/90 text-emerald-900'">
        <i class="bi text-xl shrink-0" :class="toast.type === 'error' ? 'bi-exclamation-octagon-fill text-rose-600' : 'bi-check-circle-fill text-emerald-600'"></i>
        <div class="text-sm font-medium grow">{{ toast.message }}</div>
        <button type="button" class="text-slate-400 hover:text-slate-700 text-lg leading-none" @click="toast.show = false">&times;</button>
      </div>
    </transition>

    <div class="space-y-6">
      <!-- Header Hero Card -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2 max-w-2xl">
            <div class="flex items-center gap-2">
              <span class="px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-semibold uppercase tracking-wider backdrop-blur-md">
                <i class="bi bi-headset me-1.5"></i> IT Helpdesk & Product Feedback
              </span>
              <span v-if="isSuperAdmin" class="px-2.5 py-0.5 rounded-full bg-amber-400 text-slate-950 text-xs font-bold uppercase">
                Super Admin Mode
              </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Pusat Bantuan & Request Fitur</h1>
            <p class="text-blue-200 text-xs sm:text-sm leading-relaxed">
              Laporkan kendala sistem, ajukan usulan fitur baru untuk roadmap pengembangan SINTA, atau telusuri basis pengetahuan FAQ secara mandiri.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-3 shrink-0">
            <button
              type="button"
              class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 active:scale-95"
              @click="openCreateFeatureModal"
            >
              <i class="bi bi-lightbulb-fill text-base text-yellow-300"></i>
              <span>Request Fitur Baru</span>
            </button>
            <button
              type="button"
              class="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg shadow-blue-600/30 transition flex items-center gap-2 active:scale-95"
              @click="openCreateModal"
            >
              <i class="bi bi-plus-circle-fill text-base"></i>
              <span>Buat Tiket Kendala</span>
            </button>
          </div>
        </div>

        <!-- Decorative background blurs -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- Unread Notification Banner (If Any) -->
      <div v-if="localUnreadCount > 0" class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border-l-4 border-amber-500 p-4 rounded-r-2xl bg-white shadow-xs flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0">
            <i class="bi bi-bell-fill animate-bounce"></i>
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-800">
              Ada <span class="text-amber-600 font-extrabold">{{ localUnreadCount }}</span> tiket yang memiliki pesan/balasan baru!
            </h4>
            <p class="text-xs text-slate-500 mt-0.5">Silakan periksa riwayat tiket di bawah untuk membaca tanggapan terbaru dari tim teknis.</p>
          </div>
        </div>
        <button type="button" class="text-xs font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 px-3 py-1.5 rounded-lg transition shrink-0" @click="fetchTickets(1)">
          Segarkan
        </button>
      </div>

      <!-- NavTabs Horizontal Scroller -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <button
            type="button"
            class="btn btn-sm border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5"
            onclick="document.getElementById('navTabsBantuan')?.scrollBy({ left: -220, behavior: 'smooth' })"
            title="Geser ke Kiri"
          >
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsBantuan" role="tablist">
              <!-- TAB: TIKET -->
              <li class="nav-item">
                <button
                  type="button"
                  class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === 'tickets' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                  @click="activeTab = 'tickets'"
                >
                  <i class="bi bi-ticket-detailed text-sm"></i>
                  <span>Riwayat Tiket Laporan</span>
                  <span v-if="localUnreadCount > 0" class="px-1.5 py-0.2 bg-rose-500 text-white text-[10px] font-black rounded-full">
                    {{ localUnreadCount }}
                  </span>
                </button>
              </li>

              <!-- TAB: REQUEST FITUR -->
              <li class="nav-item">
                <button
                  type="button"
                  class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === 'feature_requests' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                  @click="activeTab = 'feature_requests'"
                >
                  <i class="bi bi-rocket-takeoff-fill text-sm"></i>
                  <span>Request & Usulan Fitur Baru</span>
                  <span class="px-1.5 py-0.2 bg-amber-400 text-slate-900 text-[10px] font-extrabold rounded-full">
                    {{ featureStats.total || featureRequests.length }}
                  </span>
                </button>
              </li>

              <!-- TAB: FAQ -->
              <li class="nav-item">
                <button
                  type="button"
                  class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === 'faq' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                  @click="activeTab = 'faq'"
                >
                  <i class="bi bi-question-circle text-sm"></i>
                  <span>Basis Pengetahuan (FAQ)</span>
                  <span class="px-1.5 py-0.2 bg-slate-200 text-slate-700 text-[10px] font-bold rounded-full">
                    {{ allFaqs.length }}
                  </span>
                </button>
              </li>

              <!-- TAB: SUPER ADMIN -->
              <li v-if="isSuperAdmin" class="nav-item">
                <button
                  type="button"
                  class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === 'admin_manage' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                  @click="activeTab = 'admin_manage'"
                >
                  <i class="bi bi-sliders text-sm"></i>
                  <span>Kelola FAQ & Canned Responses</span>
                </button>
              </li>

              <!-- TAB: HOTLINE -->
              <li class="nav-item">
                <button
                  type="button"
                  class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === 'hotline' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                  @click="activeTab = 'hotline'"
                >
                  <i class="bi bi-telephone-inbound text-sm"></i>
                  <span>Hotline & Kontak Darurat</span>
                </button>
              </li>
            </ul>
          </div>

          <button
            type="button"
            class="btn btn-sm border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5"
            onclick="document.getElementById('navTabsBantuan')?.scrollBy({ left: 220, behavior: 'smooth' })"
            title="Geser ke Kanan"
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 1: RIWAYAT TIKET -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'tickets'" class="space-y-4">
        <!-- Filter Bar with Searchable Dropdowns -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2.5 grow">
            <!-- Search Input -->
            <div class="relative grow max-w-sm">
              <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input
                type="text"
                v-model="searchQuery"
                @keyup.enter="fetchTickets(1)"
                placeholder="Cari nomor tiket, judul, deskripsi..."
                class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-hidden transition"
              />
            </div>

            <!-- Searchable Dropdown: Filter Status -->
            <div class="w-36">
              <SearchableSelect
                v-model="filterStatus"
                :options="ticketStatusFilterOptions"
                placeholder="Semua Status"
                search-placeholder="Cari status..."
                @change="fetchTickets(1)"
              />
            </div>

            <!-- Searchable Dropdown: Filter Kategori -->
            <div class="w-48">
              <SearchableSelect
                v-model="filterCategory"
                :options="ticketCategoryFilterOptions"
                placeholder="Semua Kategori"
                search-placeholder="Cari kategori..."
                @change="fetchTickets(1)"
              />
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold flex items-center gap-1.5 transition"
              @click="fetchTickets(1)"
            >
              <i class="bi bi-arrow-clockwise" :class="{'animate-spin': loadingTickets}"></i>
              <span>Segarkan</span>
            </button>
          </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                  <th class="py-3.5 px-4">No. Tiket & Tanggal</th>
                  <th v-if="isSuperAdmin" class="py-3.5 px-4">Asal Sekolah (Tenant)</th>
                  <th v-if="isSuperAdmin" class="py-3.5 px-4">Pelapor</th>
                  <th class="py-3.5 px-4">Subjek Kendala / Laporan</th>
                  <th class="py-3.5 px-4">Kategori</th>
                  <th class="py-3.5 px-4 text-center">Urgensi</th>
                  <th class="py-3.5 px-4 text-center">Status</th>
                  <th class="py-3.5 px-4">Target SLA</th>
                  <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                <tr v-if="loadingTickets">
                  <td :colspan="isSuperAdmin ? 9 : 7" class="py-12 text-center text-slate-400">
                    <div class="inline-block animate-spin w-7 h-7 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
                    <p class="font-medium text-xs">Memuat data tiket laporan...</p>
                  </td>
                </tr>

                <tr v-else-if="tickets.length === 0">
                  <td :colspan="isSuperAdmin ? 9 : 7" class="py-14 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl mb-3 shadow-inner">
                      <i class="bi bi-inbox-fill"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800">Tidak Ada Tiket Laporan</h4>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                      Belum ada laporan kendala yang dibuat atau tidak ada tiket yang cocok dengan filter yang Anda pilih.
                    </p>
                    <button
                      type="button"
                      class="mt-4 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition inline-flex items-center gap-1.5"
                      @click="openCreateModal"
                    >
                      <i class="bi bi-plus-lg"></i> Buat Tiket Sekarang
                    </button>
                  </td>
                </tr>

                <tr
                  v-else
                  v-for="t in tickets"
                  :key="t.id"
                  class="hover:bg-blue-50/40 transition group cursor-pointer"
                  :class="((isSuperAdmin && t.admin_unread) || (!isSuperAdmin && t.user_unread)) ? 'bg-amber-50/50' : ''"
                  @click="openTicketDetail(t.id)"
                >
                  <td class="py-3.5 px-4 whitespace-nowrap">
                    <div class="font-mono font-bold text-blue-700">{{ t.nomor_tiket || ('#' + t.id.substring(0,8)) }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">{{ formatDate(t.created_at) }}</div>
                  </td>

                  <td v-if="isSuperAdmin" class="py-3.5 px-4">
                    <div class="font-bold text-slate-800 line-clamp-1">{{ t.tenant?.nama_sekolah || 'Super Admin Platform' }}</div>
                    <div class="text-[10px] text-slate-400">NPSN: {{ t.tenant?.npsn || '-' }}</div>
                  </td>

                  <td v-if="isSuperAdmin" class="py-3.5 px-4 whitespace-nowrap">
                    <div class="font-medium text-slate-800">{{ t.user?.nama_lengkap || '-' }}</div>
                    <div class="text-[10px] text-slate-400">{{ t.user?.username || '' }}</div>
                  </td>

                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2">
                      <span
                        v-if="(isSuperAdmin && t.admin_unread) || (!isSuperAdmin && t.user_unread)"
                        class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping shrink-0"
                        title="Balasan baru!"
                      ></span>
                      <span class="font-bold text-slate-900 group-hover:text-blue-600 transition line-clamp-1">
                        {{ t.judul }}
                      </span>
                    </div>
                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ t.deskripsi }}</div>
                  </td>

                  <td class="py-3.5 px-4 whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-medium border border-slate-200">
                      {{ t.category?.nama_kategori || 'Umum' }}
                    </span>
                  </td>

                  <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-full text-[11px] border" :class="getUrgencyBadge(t.urgensi)">
                      {{ t.urgensi }}
                    </span>
                  </td>

                  <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border" :class="getStatusBadge(t.status)">
                      {{ t.status }}
                    </span>
                  </td>

                  <td class="py-3.5 px-4 whitespace-nowrap">
                    <div class="text-[11px] text-slate-600 font-mono">{{ formatDate(t.sla_deadline) }}</div>
                    <div v-if="t.is_overdue" class="text-[10px] font-bold text-rose-600 flex items-center gap-1 mt-0.5">
                      <i class="bi bi-exclamation-triangle-fill"></i> Lewat SLA
                    </div>
                  </td>

                  <td class="py-3.5 px-4 text-right whitespace-nowrap" @click.stop>
                    <button
                      type="button"
                      class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-bold text-xs transition flex items-center gap-1.5 ml-auto"
                      @click="openTicketDetail(t.id)"
                    >
                      <i class="bi bi-chat-text-fill"></i>
                      <span>Percakapan</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Bar -->
          <div class="py-3.5 px-4 bg-slate-50/60 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div>
              Menampilkan <span class="font-bold text-slate-800">{{ pagination.from || 0 }}</span> sampai <span class="font-bold text-slate-800">{{ pagination.to || 0 }}</span> dari <span class="font-bold text-slate-800">{{ pagination.total || 0 }}</span> tiket
            </div>
            <div class="flex items-center gap-1.5">
              <button
                type="button"
                class="px-3 py-1.5 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                :disabled="pagination.current_page <= 1"
                @click="fetchTickets(pagination.current_page - 1)"
              >
                Sebelumnya
              </button>
              <span class="px-2 font-bold text-slate-800">{{ pagination.current_page }} / {{ pagination.last_page || 1 }}</span>
              <button
                type="button"
                class="px-3 py-1.5 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                :disabled="pagination.current_page >= pagination.last_page"
                @click="fetchTickets(pagination.current_page + 1)"
              >
                Selanjutnya
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: REQUEST & USULAN FITUR PENGEMBANGAN SINTA -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'feature_requests'" class="space-y-6">
        <!-- Stats Top Summary -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-black shrink-0">
              <i class="bi bi-lightbulb-fill"></i>
            </div>
            <div>
              <div class="text-lg font-black text-slate-900">{{ featureStats.total || 0 }}</div>
              <div class="text-[11px] text-slate-500 font-medium">Total Usulan Fitur</div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-black shrink-0">
              <i class="bi bi-gear-wide-connected animate-spin"></i>
            </div>
            <div>
              <div class="text-lg font-black text-purple-700">{{ featureStats.sedang_dikembangkan || 0 }}</div>
              <div class="text-[11px] text-slate-500 font-medium">Sedang Dikerjakan</div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-black shrink-0">
              <i class="bi bi-check2-circle"></i>
            </div>
            <div>
              <div class="text-lg font-black text-blue-700">{{ featureStats.disetujui || 0 }}</div>
              <div class="text-[11px] text-slate-500 font-medium">Disetujui Masuk Rilis</div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black shrink-0">
              <i class="bi bi-patch-check-fill"></i>
            </div>
            <div>
              <div class="text-lg font-black text-emerald-700">{{ featureStats.selesai || 0 }}</div>
              <div class="text-[11px] text-slate-500 font-medium">Telah Rilis di Sistem</div>
            </div>
          </div>
        </div>

        <!-- Filter & Action Bar with Searchable Dropdowns -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2.5 grow">
            <!-- Search -->
            <div class="relative grow max-w-xs">
              <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input
                type="text"
                v-model="searchFeature"
                @keyup.enter="fetchFeatureRequests(1)"
                placeholder="Cari ide & usulan fitur..."
                class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-hidden transition"
              />
            </div>

            <!-- Searchable Dropdown: Filter Modul -->
            <div class="w-48">
              <SearchableSelect
                v-model="filterFeatureModul"
                :options="featureModulFilterOptions"
                placeholder="Semua Modul"
                search-placeholder="Cari modul..."
                @change="fetchFeatureRequests(1)"
              />
            </div>

            <!-- Searchable Dropdown: Filter Status -->
            <div class="w-40">
              <SearchableSelect
                v-model="filterFeatureStatus"
                :options="featureStatusFilterOptions"
                placeholder="Semua Status"
                search-placeholder="Cari status..."
                @change="fetchFeatureRequests(1)"
              />
            </div>

            <!-- Searchable Dropdown: Sort By -->
            <div class="w-48">
              <SearchableSelect
                v-model="sortFeature"
                :options="featureSortOptions"
                placeholder="Urutkan..."
                search-placeholder="Cari urutan..."
                @change="fetchFeatureRequests(1)"
              />
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5"
              @click="openCreateFeatureModal"
            >
              <i class="bi bi-plus-lg"></i>
              <span>Ajukan Ide Fitur</span>
            </button>
          </div>
        </div>

        <!-- Feature Request Cards Grid -->
        <div v-if="loadingFeatures" class="py-16 text-center text-slate-400">
          <div class="inline-block animate-spin w-8 h-8 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
          <p class="font-medium text-xs">Memuat usulan fitur pengembangan...</p>
        </div>

        <div v-else-if="featureRequests.length === 0" class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center shadow-xs">
          <div class="w-16 h-16 mx-auto rounded-3xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl mb-4 shadow-inner">
            <i class="bi bi-rocket-takeoff"></i>
          </div>
          <h3 class="text-base font-bold text-slate-800">Belum Ada Usulan Fitur</h3>
          <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
            Punya ide atau fitur yang sangat dibutuhkan sekolah Anda untuk mempercepat pekerjaan? Ajukan usulan sekarang agar dapat divoting dan dipertimbangkan dalam roadmap SINTA!
          </p>
          <button
            type="button"
            class="mt-5 px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition inline-flex items-center gap-2"
            @click="openCreateFeatureModal"
          >
            <i class="bi bi-plus-lg"></i> Ajukan Ide Pertama
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="f in featureRequests"
            :key="f.id"
            class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between space-y-4 relative overflow-hidden group"
          >
            <div class="space-y-3">
              <div class="flex items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-1.5">
                  <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-[11px] font-bold border border-indigo-100">
                    <i class="bi bi-folder2-open me-1"></i> Modul {{ f.modul_terkait }}
                  </span>
                  <span class="px-2 py-0.5 rounded-lg text-[10px] border" :class="getUrgencyBadge(f.urgensi_bisnis)">
                    {{ f.urgensi_bisnis }}
                  </span>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border" :class="getStatusBadge(f.status)">
                  {{ f.status }}
                </span>
              </div>

              <div>
                <h3 class="text-sm font-black text-slate-900 group-hover:text-indigo-600 transition leading-snug">
                  {{ f.judul_fitur }}
                </h3>
                <p class="text-xs text-slate-600 mt-1.5 leading-relaxed whitespace-pre-line line-clamp-3">
                  {{ f.deskripsi_kebutuhan }}
                </p>
              </div>

              <div v-if="f.ekspektasi_solusi" class="p-3 rounded-2xl bg-slate-50 border border-slate-100 text-[11px] text-slate-700 space-y-1">
                <div class="font-bold text-slate-800 flex items-center gap-1">
                  <i class="bi bi-diagram-3-fill text-indigo-600"></i> Alur yang Diharapkan:
                </div>
                <p class="line-clamp-2 text-slate-600">{{ f.ekspektasi_solusi }}</p>
              </div>

              <div v-if="f.catatan_pengembang" class="p-3 rounded-2xl bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-100 text-[11px] space-y-1">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-purple-900 flex items-center gap-1">
                    <i class="bi bi-terminal-fill text-purple-600"></i> Respon Tim Developer:
                  </span>
                  <span v-if="f.estimasi_rilis" class="px-2 py-0.5 rounded bg-purple-200/80 text-purple-900 text-[10px] font-extrabold">
                    {{ f.estimasi_rilis }}
                  </span>
                </div>
                <p class="text-purple-800 leading-relaxed">{{ f.catatan_pengembang }}</p>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3 text-xs">
              <button
                type="button"
                class="px-3 py-1.5 rounded-xl border font-bold text-xs flex items-center gap-1.5 transition active:scale-95"
                :class="f.has_voted ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200'"
                @click="voteFeature(f)"
              >
                <i class="bi bi-caret-up-fill" :class="f.has_voted ? 'text-yellow-300' : ''"></i>
                <span>{{ f.votes_count || 0 }} Dukungan</span>
              </button>

              <div class="flex items-center gap-2">
                <button
                  v-if="isSuperAdmin"
                  type="button"
                  class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition"
                  @click="openManageFeatureModal(f)"
                >
                  <i class="bi bi-pencil-square me-1"></i> Status
                </button>

                <div class="text-[10px] text-slate-400 text-right">
                  <div>{{ f.tenant?.nama_sekolah || 'Sekolah' }}</div>
                  <div>{{ formatDate(f.created_at) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="featurePagination.last_page > 1" class="py-3.5 px-4 bg-white rounded-2xl border border-slate-200/80 flex items-center justify-between text-xs text-slate-500">
          <div>Halaman {{ featurePagination.current_page }} dari {{ featurePagination.last_page }}</div>
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              :disabled="featurePagination.current_page <= 1"
              @click="fetchFeatureRequests(featurePagination.current_page - 1)"
            >
              Sebelumnya
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              :disabled="featurePagination.current_page >= featurePagination.last_page"
              @click="fetchFeatureRequests(featurePagination.current_page + 1)"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 3: BASIS PENGETAHUAN (FAQ) -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'faq'" class="space-y-6">
        <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-white rounded-3xl border border-blue-100 p-6 sm:p-8 text-center space-y-4 shadow-xs">
          <h2 class="text-xl sm:text-2xl font-black text-slate-900">Pencarian Jawaban Cepat (FAQ)</h2>
          <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto">
            Temukan jawaban atas pertanyaan lazim seputar pengoperasian modul sistem tanpa perlu menunggu tanggapan tiket.
          </p>
          <div class="relative max-w-xl mx-auto">
            <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
            <input
              type="text"
              v-model="faqSearchQuery"
              placeholder="Ketik kata kunci (misal: 'reset password', 'import siswa', 'tagihan spp')..."
              class="w-full pl-11 pr-4 py-3 text-sm rounded-2xl border border-blue-200 bg-white shadow-md focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-hidden transition"
            />
          </div>

          <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
            <button
              type="button"
              class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition"
              :class="selectedFaqCategory === 0 ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
              @click="selectedFaqCategory = 0"
            >
              Semua Topik
            </button>
            <button
              v-for="cat in categories"
              :key="cat.id"
              type="button"
              class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition"
              :class="selectedFaqCategory === cat.id ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
              @click="selectedFaqCategory = cat.id"
            >
              {{ cat.nama_kategori }}
            </button>
          </div>
        </div>

        <div class="space-y-3">
          <div v-if="filteredFaqs.length === 0" class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-400">
            <i class="bi bi-search text-3xl mb-2 d-block"></i>
            <p class="text-sm font-semibold">Tidak ditemukan FAQ yang sesuai dengan kata kunci.</p>
          </div>

          <div
            v-for="faq in filteredFaqs"
            :key="faq.id"
            class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden transition"
            :class="openedFaqId === faq.id ? 'ring-2 ring-blue-500/30 border-blue-300' : 'hover:border-slate-300'"
          >
            <button
              type="button"
              class="w-full py-4 px-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-slate-800 hover:text-blue-600 transition"
              @click="toggleFaq(faq.id)"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 text-sm">
                  <i class="bi bi-question-lg font-black"></i>
                </div>
                <div>
                  <span>{{ faq.pertanyaan }}</span>
                  <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                    Kategori: {{ faq.category?.nama_kategori || 'Umum' }}
                  </div>
                </div>
              </div>
              <i class="bi text-base text-slate-400 transition transform duration-200" :class="openedFaqId === faq.id ? 'bi-chevron-up text-blue-600' : 'bi-chevron-down'"></i>
            </button>

            <div v-if="openedFaqId === faq.id" class="px-5 pb-5 pt-1 text-xs text-slate-600 leading-relaxed border-t border-slate-100 bg-slate-50/50">
              <div class="prose prose-xs max-w-none whitespace-pre-line text-slate-700">
                {{ faq.jawaban }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 4: ADMIN MANAGE (SUPER ADMIN ONLY) -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'admin_manage' && isSuperAdmin" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Form Tambah FAQ with Searchable Category -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
          <div class="flex items-center gap-2.5 border-b pb-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
              <i class="bi bi-patch-question-fill"></i>
            </div>
            <div>
              <h3 class="font-bold text-sm text-slate-900">Tambah FAQ Baru</h3>
              <p class="text-[11px] text-slate-400">Tambahkan pertanyaan & panduan resmi ke basis pengetahuan platform.</p>
            </div>
          </div>

          <form @submit.prevent="submitNewFaq" class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kategori FAQ</label>
              <SearchableSelect
                v-model="newFaqForm.category_id"
                :options="ticketCategoryFormOptions"
                placeholder="Pilih Kategori..."
                search-placeholder="Cari kategori..."
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Pertanyaan <span class="text-rose-500">*</span></label>
              <input type="text" v-model="newFaqForm.pertanyaan" placeholder="Contoh: Bagaimana cara reset password?" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500" required />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Jawaban / Solusi Lengkap <span class="text-rose-500">*</span></label>
              <textarea v-model="newFaqForm.jawaban" rows="4" placeholder="Jelaskan langkah demi langkah solusinya..." class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500" required></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition disabled:opacity-50" :disabled="submittingFaq">
              <span v-if="submittingFaq">Menyimpan...</span>
              <span v-else><i class="bi bi-plus-lg me-1"></i> Simpan FAQ</span>
            </button>
          </form>

          <div class="border-t pt-3 space-y-2 max-h-64 overflow-y-auto pr-1">
            <h4 class="text-xs font-bold text-slate-800">Daftar FAQ Terdaftar ({{ allFaqs.length }})</h4>
            <div v-for="f in allFaqs" :key="f.id" class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/50 flex items-center justify-between gap-2 text-xs">
              <div class="truncate">
                <div class="font-bold text-slate-800 truncate">{{ f.pertanyaan }}</div>
                <div class="text-[10px] text-slate-400">{{ f.category?.nama_kategori || 'Umum' }}</div>
              </div>
              <button type="button" class="text-rose-500 hover:text-rose-700 p-1" @click="deleteFaqItem(f.id)" title="Hapus FAQ">
                <i class="bi bi-trash3-fill"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Form Tambah Canned Response -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
          <div class="flex items-center gap-2.5 border-b pb-3">
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
              <i class="bi bi-chat-quote-fill"></i>
            </div>
            <div>
              <h3 class="font-bold text-sm text-slate-900">Template Respon Cepat (Canned)</h3>
              <p class="text-[11px] text-slate-400">Template teks standar untuk membalas tiket pertanyaan berulang.</p>
            </div>
          </div>

          <form @submit.prevent="submitNewCanned" class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Judul Template <span class="text-rose-500">*</span></label>
              <input type="text" v-model="newCannedForm.judul" placeholder="Contoh: Solusi Hard Refresh Cache" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500" required />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Konten Pesan Balasan <span class="text-rose-500">*</span></label>
              <textarea v-model="newCannedForm.konten" rows="4" placeholder="Ketik draf pesan balasan cepat..." class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500" required></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition disabled:opacity-50" :disabled="submittingCanned">
              <span v-if="submittingCanned">Menyimpan...</span>
              <span v-else><i class="bi bi-plus-lg me-1"></i> Simpan Template Respon</span>
            </button>
          </form>

          <div class="border-t pt-3 space-y-2 max-h-64 overflow-y-auto pr-1">
            <h4 class="text-xs font-bold text-slate-800">Template Tersedia ({{ allCannedResponses.length }})</h4>
            <div v-for="c in allCannedResponses" :key="c.id" class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/50 flex items-center justify-between gap-2 text-xs">
              <div class="truncate">
                <div class="font-bold text-slate-800 truncate">{{ c.judul }}</div>
                <div class="text-[10px] text-slate-400 line-clamp-1">{{ c.konten }}</div>
              </div>
              <button type="button" class="text-rose-500 hover:text-rose-700 p-1" @click="deleteCannedItem(c.id)" title="Hapus Template">
                <i class="bi bi-trash3-fill"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 5: HOTLINE & KONTAK DARURAT -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'hotline'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-emerald-500 to-teal-700 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col justify-between">
          <div class="space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl">
              <i class="bi bi-whatsapp"></i>
            </div>
            <h3 class="text-lg font-black">Layanan WhatsApp Resmi</h3>
            <p class="text-xs text-emerald-100 leading-relaxed">
              Konsultasi langsung dengan Tim Support SINTA melalui pesan instan untuk respon cepat pada jam kerja.
            </p>
          </div>
          <div class="pt-6">
            <a href="https://wa.me/6281388884043?text=Halo%20Admin%20SINTA,%20saya%20butuh%20bantuan%20sistem" target="_blank" class="w-full py-3 rounded-2xl bg-white text-emerald-900 font-bold text-xs text-center block shadow-md hover:bg-emerald-50 transition">
              Chat Sekarang (+62 813-8888-4043)
            </a>
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <div class="space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">
              <i class="bi bi-clock-history"></i>
            </div>
            <h3 class="text-lg font-black text-slate-900">Jam Operasional Layanan</h3>
            <ul class="text-xs text-slate-600 space-y-2">
              <li class="flex justify-between border-b pb-1.5">
                <span>Senin - Jumat</span>
                <span class="font-bold text-slate-800">08:00 - 17:00 WIB</span>
              </li>
              <li class="flex justify-between border-b pb-1.5">
                <span>Sabtu</span>
                <span class="font-bold text-slate-800">08:00 - 13:00 WIB</span>
              </li>
              <li class="flex justify-between">
                <span>Minggu & Libur Nasional</span>
                <span class="font-bold text-rose-600">On-Call (Kasus Kritis)</span>
              </li>
            </ul>
          </div>
          <div class="pt-4 text-[11px] text-slate-400">
            * Tiket dengan urgensi <strong>Kritis</strong> dimonitor 24/7 oleh tim siaga server.
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <div class="space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl">
              <i class="bi bi-shield-check"></i>
            </div>
            <h3 class="text-lg font-black text-slate-900">Kebijakan Target SLA</h3>
            <ul class="text-xs text-slate-600 space-y-2">
              <li class="flex items-center justify-between">
                <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 font-bold text-[10px]">Kritis</span>
                <span class="font-bold text-slate-800">&le; 2 Jam</span>
              </li>
              <li class="flex items-center justify-between">
                <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-bold text-[10px]">Tinggi</span>
                <span class="font-bold text-slate-800">&le; 24 Jam</span>
              </li>
              <li class="flex items-center justify-between">
                <span class="px-2 py-0.5 rounded-md bg-blue-100 text-blue-700 font-bold text-[10px]">Sedang</span>
                <span class="font-bold text-slate-800">&le; 48 Jam</span>
              </li>
              <li class="flex items-center justify-between">
                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-[10px]">Rendah</span>
                <span class="font-bold text-slate-800">&le; 72 Jam</span>
              </li>
            </ul>
          </div>
          <div class="pt-4 text-[11px] text-slate-400">
            SLA dihitung otomatis sejak waktu pengiriman tiket laporan.
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 1: BUAT TIKET BARU (<Teleport to="body">) -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full border border-slate-100 overflow-hidden transform transition-all my-8">
          <div class="px-6 py-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-lg shadow-md shadow-blue-500/20">
                <i class="bi bi-file-earmark-plus-fill"></i>
              </div>
              <div>
                <h3 class="text-base font-black text-slate-900">Buat Tiket Bantuan Baru</h3>
                <p class="text-xs text-slate-500">Laporkan kendala atau konsultasikan kebutuhan modul sistem.</p>
              </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-xl bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition" @click="closeCreateModal">
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>

          <form @submit.prevent="submitNewTicket">
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Judul Kendala / Laporan <span class="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="createForm.judul"
                  @input="onJudulInput"
                  placeholder="Contoh: Gagal simpan nilai rapor siswa kelas X..."
                  class="w-full text-xs py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-hidden transition"
                  required
                />

                <div v-if="liveFaqs.length > 0" class="mt-2.5 p-3 rounded-2xl bg-amber-50/80 border border-amber-200/80 text-xs text-amber-900 space-y-2 animate-fadeIn">
                  <div class="font-bold flex items-center gap-1.5 text-amber-800">
                    <i class="bi bi-lightbulb-fill text-amber-600"></i>
                    <span>Rekomendasi Solusi Cepat (Mungkin ini membantu Anda):</span>
                  </div>
                  <ul class="space-y-1 pl-5 list-disc text-[11px] text-amber-800">
                    <li v-for="f in liveFaqs" :key="f.id">
                      <span class="font-bold">{{ f.pertanyaan }}</span>: {{ f.jawaban }}
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Searchable Dropdowns for Kategori & Urgensi -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    Kategori <span class="text-rose-500">*</span>
                  </label>
                  <SearchableSelect
                    v-model="createForm.category_id"
                    :options="ticketCategoryFormOptions"
                    placeholder="Pilih Kategori..."
                    search-placeholder="Cari kategori..."
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    Tingkat Urgensi <span class="text-rose-500">*</span>
                  </label>
                  <SearchableSelect
                    v-model="createForm.urgensi"
                    :options="ticketUrgencyOptions"
                    placeholder="Pilih Urgensi..."
                    search-placeholder="Cari urgensi..."
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Deskripsi Kronologi & Detail Masalah <span class="text-rose-500">*</span>
                </label>
                <textarea
                  v-model="createForm.deskripsi"
                  rows="4"
                  placeholder="Jelaskan secara rinci apa yang terjadi, langkah yang dilakukan sebelum error muncul, dan pesan error yang tertera pada layar..."
                  class="w-full text-xs py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-hidden transition"
                  required
                ></textarea>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Unggah Tangkapan Layar (Screenshot) <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <div class="border-2 border-dashed border-slate-200 rounded-2xl p-4 text-center hover:border-blue-400 transition bg-slate-50/50">
                  <input
                    type="file"
                    id="ticketLampiranInput"
                    class="hidden"
                    @change="handleFileChange"
                    accept="image/png, image/jpeg, image/jpg, image/webp, application/pdf"
                  />
                  <label for="ticketLampiranInput" class="cursor-pointer flex flex-col items-center justify-center gap-1.5">
                    <i class="bi bi-cloud-arrow-up text-2xl text-blue-600"></i>
                    <span class="text-xs font-bold text-slate-700">Pilih file atau seret screenshot ke sini</span>
                    <span class="text-[10px] text-slate-400">Format JPG, PNG, WEBP, atau PDF (Maksimal 3 MB)</span>
                  </label>
                </div>

                <div v-if="createForm.lampiran" class="mt-2.5 p-2.5 rounded-xl bg-slate-100 flex items-center justify-between gap-3 text-xs">
                  <div class="flex items-center gap-2 truncate">
                    <img v-if="createLampiranPreview" :src="createLampiranPreview" class="w-10 h-10 object-cover rounded-lg border" />
                    <i v-else class="bi bi-file-earmark-pdf-fill text-rose-600 text-xl"></i>
                    <span class="font-medium text-slate-800 truncate">{{ createForm.lampiran.name }}</span>
                  </div>
                  <button type="button" class="text-rose-500 hover:text-rose-700 font-bold p-1" @click="removeAttachment">
                    <i class="bi bi-trash3-fill"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
              <button
                type="button"
                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-100 transition"
                @click="closeCreateModal"
              >
                Batal
              </button>
              <button
                type="submit"
                class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition disabled:opacity-50 flex items-center gap-2"
                :disabled="submittingTicket"
              >
                <span v-if="submittingTicket" class="inline-block animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                <i v-else class="bi bi-send-fill text-xs"></i>
                <span>{{ submittingTicket ? 'Mengirim...' : 'Kirim Tiket Laporan' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ============================================================== -->
    <!-- MODAL 2: DETAIL & PERCAKAPAN TIKET (<Teleport to="body">) -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isDetailModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-5xl w-full border border-slate-100 overflow-hidden transform transition-all h-[90vh] flex flex-col">
          <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3 truncate">
              <div class="w-10 h-10 rounded-2xl bg-blue-600 flex items-center justify-center text-lg text-white font-mono font-black shrink-0">
                <i class="bi bi-chat-dots-fill"></i>
              </div>
              <div class="truncate">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-xs font-bold text-blue-400">{{ activeTicket?.nomor_tiket || 'TIKET' }}</span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="getStatusBadge(activeTicket?.status)">
                    {{ activeTicket?.status }}
                  </span>
                </div>
                <h3 class="text-sm font-black text-white truncate mt-0.5">{{ activeTicket?.judul || 'Memuat...' }}</h3>
              </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition shrink-0" @click="closeDetailModal">
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-12 grow overflow-hidden">
            <div class="md:col-span-4 bg-slate-50/80 border-r border-slate-200/80 p-5 overflow-y-auto space-y-4 text-xs">
              <div v-if="loadingDetail" class="text-center py-10 text-slate-400">
                <div class="inline-block animate-spin w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full"></div>
                <p class="text-xs mt-2">Memuat detail...</p>
              </div>

              <div v-else-if="activeTicket" class="space-y-4">
                <div class="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2">
                  <div class="text-[11px] font-bold text-slate-400 uppercase">Kelola Status Tiket</div>
                  <!-- Searchable Status Selector -->
                  <SearchableSelect
                    :model-value="activeTicket.status"
                    :options="ticketStatusChangeOptions"
                    placeholder="Ubah Status..."
                    search-placeholder="Cari status..."
                    :disabled="updatingStatus"
                    @update:model-value="changeTicketStatus"
                  />
                </div>

                <div class="space-y-2.5">
                  <div v-if="isSuperAdmin" class="p-3 rounded-2xl bg-white border border-slate-200/80 space-y-1">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Asal Sekolah (Tenant)</div>
                    <div class="font-bold text-slate-800">{{ activeTicket.tenant?.nama_sekolah || 'Super Admin Platform' }}</div>
                    <div class="text-[10px] text-slate-500">NPSN: {{ activeTicket.tenant?.npsn || '-' }}</div>
                  </div>

                  <div class="p-3 rounded-2xl bg-white border border-slate-200/80 space-y-1">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Pelapor (User)</div>
                    <div class="font-bold text-slate-800">{{ activeTicket.user?.nama_lengkap || 'User' }}</div>
                    <div class="text-[10px] text-slate-500">{{ activeTicket.user?.email || activeTicket.user?.username }}</div>
                  </div>

                  <div class="p-3 rounded-2xl bg-white border border-slate-200/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                      <span class="text-[10px] font-bold text-slate-400 uppercase">Kategori</span>
                      <span class="font-semibold text-slate-700">{{ activeTicket.category?.nama_kategori }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-[10px] font-bold text-slate-400 uppercase">Tingkat Urgensi</span>
                      <span class="px-2 py-0.5 rounded-md text-[10px] border" :class="getUrgencyBadge(activeTicket.urgensi)">
                        {{ activeTicket.urgensi }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-[10px] font-bold text-slate-400 uppercase">Batas Waktu SLA</span>
                      <span class="font-mono text-[11px] text-slate-700">{{ formatDate(activeTicket.sla_deadline) }}</span>
                    </div>
                    <div v-if="activeTicket.is_overdue" class="text-[10px] font-bold text-rose-600 flex items-center gap-1">
                      <i class="bi bi-exclamation-triangle-fill"></i> Melewati Target SLA!
                    </div>
                  </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 space-y-2">
                  <div class="text-[10px] font-bold text-slate-400 uppercase">Deskripsi Laporan Awal</div>
                  <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ activeTicket.deskripsi }}</p>
                  
                  <div v-if="activeTicket.lampiran" class="pt-2 border-t border-slate-100">
                    <div class="text-[10px] font-bold text-slate-400 mb-1">Lampiran Laporan:</div>
                    <a :href="'/storage/' + activeTicket.lampiran" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-2.5 py-1.5 rounded-lg">
                      <i class="bi bi-paperclip"></i> Lihat Berkas Lampiran
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <div class="md:col-span-8 flex flex-col h-full bg-slate-100/60 overflow-hidden">
              <div ref="chatContainerRef" class="grow p-4 sm:p-6 overflow-y-auto space-y-4">
                <div v-if="activeTicket" class="flex items-start gap-2.5">
                  <div class="w-8 h-8 rounded-full bg-slate-300 text-slate-700 flex items-center justify-center text-xs font-bold shrink-0">
                    <i class="bi bi-person-fill"></i>
                  </div>
                  <div class="max-w-xl bg-white rounded-2xl rounded-tl-xs p-4 shadow-2xs border border-slate-200/80 space-y-1">
                    <div class="flex items-center justify-between gap-4">
                      <span class="font-bold text-xs text-slate-800">{{ activeTicket.user?.nama_lengkap || 'Pelapor' }}</span>
                      <span class="text-[10px] text-slate-400">{{ formatDate(activeTicket.created_at) }}</span>
                    </div>
                    <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ activeTicket.deskripsi }}</p>
                    <div v-if="activeTicket.lampiran" class="pt-2">
                      <a :href="'/storage/' + activeTicket.lampiran" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:underline">
                        <i class="bi bi-image"></i> Unduh / Buka Lampiran
                      </a>
                    </div>
                  </div>
                </div>

                <div
                  v-for="rep in activeReplies"
                  :key="rep.id"
                  class="flex items-start gap-2.5"
                  :class="rep.is_superadmin ? 'flex-row-reverse' : ''"
                >
                  <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                    :class="rep.is_superadmin ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-300 text-slate-700'"
                  >
                    <i :class="rep.is_superadmin ? 'bi-headset' : 'bi-person-fill'"></i>
                  </div>
                  <div
                    class="max-w-xl rounded-2xl p-4 shadow-2xs border space-y-1"
                    :class="rep.is_superadmin ? 'bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-tr-xs border-blue-600' : 'bg-white text-slate-800 rounded-tl-xs border-slate-200/80'"
                  >
                    <div class="flex items-center justify-between gap-4">
                      <span class="font-bold text-xs" :class="rep.is_superadmin ? 'text-blue-100' : 'text-slate-800'">
                        {{ rep.user?.nama_lengkap || (rep.is_superadmin ? 'Tim IT Support' : 'Pengguna') }}
                        <span v-if="rep.is_superadmin" class="ml-1 px-1.5 py-0.2 bg-white/20 rounded text-[9px] uppercase tracking-wider font-extrabold">Support</span>
                      </span>
                      <span class="text-[10px]" :class="rep.is_superadmin ? 'text-blue-200' : 'text-slate-400'">
                        {{ formatDate(rep.created_at) }}
                      </span>
                    </div>
                    <p class="text-xs whitespace-pre-line leading-relaxed" :class="rep.is_superadmin ? 'text-blue-50' : 'text-slate-700'">
                      {{ rep.pesan }}
                    </p>
                    <div v-if="rep.lampiran" class="pt-2">
                      <a :href="'/storage/' + rep.lampiran" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold" :class="rep.is_superadmin ? 'text-white underline' : 'text-blue-600 underline'">
                        <i class="bi bi-paperclip"></i> Lihat Lampiran Balasan
                      </a>
                    </div>
                  </div>
                </div>

                <div v-if="activeReplies.length === 0" class="text-center py-6 text-slate-400 text-xs">
                  <i class="bi bi-chat-heart text-2xl mb-1 d-block text-slate-300"></i>
                  Belum ada pesan balasan. Tim Dukungan akan segera menanggapi laporan Anda.
                </div>
              </div>

              <!-- Message Input Footer with Searchable Canned Responses -->
              <div class="p-4 bg-white border-t border-slate-200 shrink-0 space-y-2">
                <div v-if="isSuperAdmin && allCannedResponses.length > 0" class="flex items-center gap-2">
                  <span class="text-[11px] font-bold text-slate-500 shrink-0">Template Cepat:</span>
                  <div class="grow">
                    <SearchableSelect
                      v-model="selectedCanned"
                      :options="cannedSelectOptions"
                      placeholder="Pilih atau cari template respon cepat..."
                      search-placeholder="Ketik judul template..."
                      @change="applyCannedResponse"
                    />
                  </div>
                </div>

                <div v-if="activeTicket && !['Selesai', 'Batal'].includes(activeTicket.status)" class="flex items-end gap-2">
                  <div class="grow">
                    <textarea
                      v-model="replyText"
                      @keydown.ctrl.enter="sendReply"
                      rows="2"
                      placeholder="Ketik balasan Anda di sini (Tekan Ctrl + Enter untuk kirim)..."
                      class="w-full text-xs p-3 rounded-2xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-hidden resize-none"
                    ></textarea>
                  </div>
                  <button
                    type="button"
                    class="px-4 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5 active:scale-95 disabled:opacity-50"
                    :disabled="sendingReply || !replyText.trim()"
                    @click="sendReply"
                  >
                    <span v-if="sendingReply" class="inline-block animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                    <i v-else class="bi bi-send-fill text-xs"></i>
                    <span class="hidden sm:inline">{{ sendingReply ? 'Mengirim...' : 'Kirim' }}</span>
                  </button>
                </div>

                <div v-else class="text-center py-2 bg-slate-100 rounded-xl text-xs text-slate-500 font-medium">
                  <i class="bi bi-lock-fill me-1"></i> Tiket ini telah berstatus <strong>{{ activeTicket?.status }}</strong> dan percakapan ditutup.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ============================================================== -->
    <!-- MODAL 3: AJUKAN REQUEST FITUR BARU (<Teleport to="body">) -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isCreateFeatureModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full border border-slate-100 overflow-hidden transform transition-all my-8">
          <div class="px-6 py-5 bg-gradient-to-r from-indigo-900 to-purple-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-md text-yellow-300 flex items-center justify-center text-xl shadow-inner">
                <i class="bi bi-lightbulb-fill"></i>
              </div>
              <div>
                <h3 class="text-base font-black">Ajukan Request Fitur Baru</h3>
                <p class="text-xs text-indigo-200">Usulkan fitur atau modul baru untuk pengembangan aplikasi SINTA.</p>
              </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition" @click="closeCreateFeatureModal">
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>

          <form @submit.prevent="submitNewFeatureRequest">
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Nama / Judul Fitur yang Diusulkan <span class="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="featureForm.judul_fitur"
                  placeholder="Contoh: Ekspor Rapor K-Merdeka Format Excel, Integrasi QR-Code Presensi..."
                  class="w-full text-xs py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-hidden transition"
                  required
                />
              </div>

              <!-- Searchable Dropdowns for Modul & Urgensi -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    Modul Terkait (16 Modul) <span class="text-rose-500">*</span>
                  </label>
                  <SearchableSelect
                    v-model="featureForm.modul_terkait"
                    :options="featureModulFormOptions"
                    placeholder="Pilih Modul..."
                    search-placeholder="Cari nama modul..."
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    Tingkat Urgensi Operasional <span class="text-rose-500">*</span>
                  </label>
                  <SearchableSelect
                    v-model="featureForm.urgensi_bisnis"
                    :options="featureUrgencyOptions"
                    placeholder="Pilih Urgensi..."
                    search-placeholder="Cari tingkat urgensi..."
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Deskripsi Kebutuhan & Latar Belakang <span class="text-rose-500">*</span>
                </label>
                <textarea
                  v-model="featureForm.deskripsi_kebutuhan"
                  rows="3"
                  placeholder="Jelaskan kendala apa yang dialami saat ini dan mengapa fitur ini sangat bermanfaat bagi operasional sekolah..."
                  class="w-full text-xs py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-hidden transition"
                  required
                ></textarea>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Ekspektasi Alur Kerja & Hasil yang Diinginkan <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <textarea
                  v-model="featureForm.ekspektasi_solusi"
                  rows="3"
                  placeholder="Contoh: Pengguna memilih rombel > klik tombol Generate > sistem memproses background job dan menghasilkan file zip rapor..."
                  class="w-full text-xs py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-hidden transition"
                ></textarea>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Lampiran Konsep / Mockup / Contoh Dokumen <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <input
                  type="file"
                  class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50"
                  @change="handleFeatureFileChange"
                  accept="image/png, image/jpeg, image/jpg, image/webp, application/pdf"
                />
                <span class="text-[10px] text-slate-400 mt-1 block">Maksimal 3MB (Format PNG, JPG, PDF)</span>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
              <button
                type="button"
                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-100 transition"
                @click="closeCreateFeatureModal"
              >
                Batal
              </button>
              <button
                type="submit"
                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-500/20 transition disabled:opacity-50 flex items-center gap-2"
                :disabled="submittingFeature"
              >
                <span v-if="submittingFeature" class="inline-block animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                <i v-else class="bi bi-send-check-fill text-xs"></i>
                <span>{{ submittingFeature ? 'Mengirim Usulan...' : 'Kirim Usulan Fitur' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ============================================================== -->
    <!-- MODAL 4: KELOLA STATUS FEATURE (SUPER ADMIN - <Teleport to="body">) -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isManageFeatureModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full border border-slate-100 overflow-hidden transform transition-all my-8">
          <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 class="text-sm font-black flex items-center gap-2">
              <i class="bi bi-sliders"></i> Kelola Roadmap Fitur
            </h3>
            <button type="button" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition" @click="closeManageFeatureModal">
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>

          <form @submit.prevent="submitManageFeature" class="p-6 space-y-4">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase">Judul Fitur:</div>
              <div class="text-xs font-bold text-slate-900 mt-0.5">{{ activeFeature?.judul_fitur }}</div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Status Usulan</label>
              <SearchableSelect
                v-model="manageFeatureForm.status"
                :options="featureManageStatusOptions"
                placeholder="Pilih Status..."
                search-placeholder="Cari status..."
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Estimasi Target Rilis</label>
              <input type="text" v-model="manageFeatureForm.estimasi_rilis" placeholder="Contoh: Versi 2.6.0 (Q4 2026)" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500" />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tim Pengembang (Tampil ke Pengguna)</label>
              <textarea v-model="manageFeatureForm.catatan_pengembang" rows="3" placeholder="Tuliskan respon resmi atau progres implementasi fitur..." class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <div class="pt-3 border-t flex justify-end gap-2">
              <button type="button" class="px-4 py-2 rounded-xl border text-xs font-bold text-slate-600 hover:bg-slate-50" @click="closeManageFeatureModal">Batal</button>
              <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md disabled:opacity-50" :disabled="updatingFeatureStatus">
                {{ updatingFeatureStatus ? 'Menyimpan...' : 'Perbarui Status' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
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
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.25s ease-out forwards;
}
</style>
