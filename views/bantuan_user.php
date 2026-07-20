<?php
/**
 * View: Pusat Bantuan User
 * Location: views/bantuan_user.php
 */
?>
<div class="container-fluid px-4 py-4" id="bantuan-user-app" v-cloak>
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-1 fw-bold text-dark d-flex align-items-center">
                <i class="bi bi-question-circle text-primary me-3 fs-3"></i>
                Pusat Bantuan & Layanan Tiket
            </h1>
            <p class="text-muted mb-0">Laporkan kendala teknis, kritik saran, atau ajukan permohonan fitur baru langsung ke tim dukungan IT.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm" @click="openCreateModal">
                <i class="bi bi-plus-lg"></i>
                Buat Tiket Baru
            </button>
        </div>
    </div>

    <!-- Alert Unread Badge Info -->
    <div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-center gap-3 mb-4" v-if="unreadCount > 0">
        <i class="bi bi-bell-fill text-info fs-4 animate-bounce"></i>
        <div>
            Ada <strong>{{ unreadCount }}</strong> tiket yang memiliki balasan baru dari tim IT Support. Silakan cek riwayat tiket Anda di bawah.
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row">
        <!-- Ticket List Card -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold text-dark">Riwayat Tiket Laporan Anda</span>
                    <!-- Filters -->
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm border rounded-2" v-model="filterStatus" @change="fetchTickets" style="width: 140px;">
                            <option value="">Semua Status</option>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Diproses">Diproses</option>
                            <option value="Selesai">Selesai</option>
                            <option value="Batal">Batal</option>
                        </select>
                        <select class="form-select form-select-sm border rounded-2" v-model="filterCategory" @change="fetchTickets" style="width: 160px;">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <tr>
                                    <th class="px-4 py-3">Tanggal dibuat</th>
                                    <th class="py-3">Judul Laporan</th>
                                    <th class="py-3">Kategori</th>
                                    <th class="py-3 text-center">Urgensi</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3">SLA Deadline</th>
                                    <th class="px-4 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loadingList">
                                    <td colspan="7" class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="ms-2 text-muted">Memuat daftar tiket...</span>
                                    </td>
                                </tr>
                                <tr v-else-if="tickets.length === 0">
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                                        Belum ada tiket laporan yang dibuat.
                                    </td>
                                </tr>
                                <tr v-else v-for="ticket in tickets" :key="ticket.id" :class="{'bg-light-info': ticket.user_unread}">
                                    <td class="px-4 py-3 text-nowrap font-monospace text-muted" style="font-size: 0.85rem;">
                                        {{ formatDate(ticket.created_at) }}
                                    </td>
                                    <td class="py-3 fw-semibold">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-danger p-1 rounded-circle" v-if="ticket.user_unread" title="Ada balasan baru!"></span>
                                            {{ ticket.judul }}
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge bg-light text-dark border px-2 py-1.5 rounded-pill" style="font-size: 0.75rem;">
                                            {{ ticket.nama_kategori }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span :class="getUrgencyBadgeClass(ticket.urgensi)">
                                            {{ ticket.urgensi }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span :class="getStatusBadgeClass(ticket.status)">
                                            {{ ticket.status }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex flex-column" style="font-size: 0.8rem;">
                                            <span class="font-monospace text-muted">{{ formatDate(ticket.sla_deadline) }}</span>
                                            <span class="text-danger fw-bold mt-0.5" v-if="ticket.is_overdue" style="font-size: 0.72rem;">
                                                <i class="bi bi-exclamation-triangle-fill"></i> Melewati Batas SLA
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <button class="btn btn-outline-primary btn-sm rounded-2 d-inline-flex align-items-center gap-1" @click="viewTicketDetail(ticket.id)">
                                            <i class="bi bi-chat-text"></i>
                                            Buka Percakapan
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Buat Tiket Baru -->
    <div class="modal fade" id="createTicketModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-plus text-primary"></i>
                        Buat Tiket Dukungan Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitTicket" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- Judul Laporan -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Judul Laporan Singkat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-2 py-2" v-model="form.judul" @input="debouncedFaqLookup" placeholder="Contoh: Gagal input nilai rapor atau data siswa hilang" required>
                                
                                <!-- FAQ Lookup widget -->
                                <div class="mt-2 border rounded-3 p-3 bg-light-warning shadow-sm" v-if="faqs.length > 0">
                                    <div class="fw-bold text-dark mb-2" style="font-size: 0.8rem;">
                                        <i class="bi bi-lightbulb-fill text-warning me-1.5"></i> 
                                        Rekomendasi Solusi FAQ (Mungkin ini bisa membantu Anda):
                                    </div>
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item bg-transparent px-0 py-2 border-bottom-0" v-for="faq in faqs">
                                            <a href="#" class="text-decoration-none fw-semibold text-primary d-block" @click.prevent="showFaqDetail(faq)">
                                                <i class="bi bi-arrow-right-short"></i> {{ faq.pertanyaan }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kategori & Urgensi -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Kategori Laporan <span class="text-danger">*</span></label>
                                <select class="form-select rounded-2 py-2" v-model="form.category_id" required>
                                    <option value="" disabled selected>Pilih Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Tingkat Urgensi <span class="text-danger">*</span></label>
                                <select class="form-select rounded-2 py-2" v-model="form.urgensi" required>
                                    <option value="Rendah">Rendah (Pertanyaan / Usulan)</option>
                                    <option value="Sedang">Sedang (Kendala Penggunaan)</option>
                                    <option value="Tinggi">Tinggi (Menu Error / Gagal simpan)</option>
                                    <option value="Kritis">Kritis (Sistem blank / Server error)</option>
                                </select>
                            </div>

                            <!-- Deskripsi Laporan -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Deskripsi Detail Masalah <span class="text-danger">*</span></label>
                                <textarea class="form-control rounded-2" v-model="form.deskripsi" rows="5" placeholder="Jelaskan secara rinci kronologi masalah, halaman mana yang bermasalah, dan pesan error apa yang muncul..." required></textarea>
                            </div>

                            <!-- Upload Lampiran -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Unggah Lampiran Tangkapan Layar (Screenshot) <span class="text-muted">(Opsional)</span></label>
                                <input type="file" class="form-control rounded-2" ref="lampiranInput" @change="handleFileUpload" accept="image/png, image/jpeg, image/jpg">
                                <div class="form-text text-muted" style="font-size: 0.75rem;">Maksimal ukuran file: 3 MB. Format yang didukung: PNG, JPG, JPEG.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-3">
                        <button type="button" class="btn btn-light rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-2 px-4 d-inline-flex align-items-center gap-2" :disabled="loadingSubmit">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="loadingSubmit"></span>
                            Kirim Tiket Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Detail FAQ -->
    <div class="modal fade" id="faqDetailModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header bg-light border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        Solusi Pengetahuan Dasar
                    </h6>
                    <button type="button" class="btn-close" @click="closeFaqDetailModal"></button>
                </div>
                <div class="modal-body p-4">
                    <h5 class="fw-bold text-primary mb-3">{{ selectedFaq.pertanyaan }}</h5>
                    <p class="text-dark bg-light p-3 rounded-3 border" style="line-height: 1.6; white-space: pre-wrap;">{{ selectedFaq.jawaban }}</p>
                </div>
                <div class="modal-footer bg-light border-top py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2" @click="closeFaqDetailModal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Thread Detail Percakapan / Chat -->
    <div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3" style="height: 90vh;">
                <!-- Modal Header -->
                <div class="modal-header border-bottom py-3 d-flex flex-column align-items-start gap-2 bg-light">
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-chat-left-dots text-primary"></i>
                            Detail Tiket Laporan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="onDetailModalClose"></button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-1" v-if="activeTicket">
                        <span class="badge bg-light text-dark border">Kategori: {{ activeTicket.nama_kategori }}</span>
                        <span :class="getUrgencyBadgeClass(activeTicket.urgensi)">Urgensi: {{ activeTicket.urgensi }}</span>
                        <span :class="getStatusBadgeClass(activeTicket.status)">Status: {{ activeTicket.status }}</span>
                    </div>
                </div>
                
                <!-- Modal Body (Split Layout: Detail & Chat Thread) -->
                <div class="modal-body p-0 d-flex flex-column h-100 bg-white" style="overflow: hidden;">
                    <div class="d-flex h-100 flex-column flex-md-row" style="min-height: 0;">
                        
                        <!-- Left Panel: Ticket Detail Info -->
                        <div class="col-md-5 border-end p-4 bg-light-subtle d-flex flex-column" style="overflow-y: auto; height: 100%;">
                            <div v-if="activeTicket">
                                <h6 class="fw-bold text-dark mb-1">Subjek Laporan</h6>
                                <p class="text-dark fw-semibold mb-3">{{ activeTicket.judul }}</p>

                                <h6 class="fw-bold text-dark mb-1">Deskripsi Masalah</h6>
                                <p class="text-muted small mb-3" style="white-space: pre-wrap; line-height: 1.5;">{{ activeTicket.deskripsi }}</p>

                                <h6 class="fw-bold text-dark mb-1" v-if="activeTicket.lampiran">Lampiran Tangkapan Layar</h6>
                                <div class="mb-3" v-if="activeTicket.lampiran">
                                    <a :href="'/SINTA-SaaS' + activeTicket.lampiran" target="_blank" class="d-block border rounded-3 p-1 bg-white hover-zoom shadow-sm text-center">
                                        <img :src="'/SINTA-SaaS' + activeTicket.lampiran" class="img-fluid rounded-2" style="max-height: 140px;" alt="Lampiran Laporan">
                                        <span class="d-block text-primary small fw-semibold mt-1"><i class="bi bi-zoom-in"></i> Klik untuk Perbesar</span>
                                    </a>
                                </div>

                                <hr>

                                <div class="small text-muted">
                                    <div class="mb-1"><strong>Tanggal Dibuat:</strong> {{ formatDate(activeTicket.created_at) }}</div>
                                    <div><strong>Batas Respons SLA:</strong> {{ formatDate(activeTicket.sla_deadline) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel: Dynamic Chat Thread -->
                        <div class="col-md-7 d-flex flex-column bg-white h-100" style="overflow: hidden;">
                            <!-- Chat Area (Scrollable) -->
                            <div class="flex-grow-1 p-4" ref="chatContainer" style="overflow-y: auto; background-color: #f8f9fa;">
                                <div class="text-center py-5" v-if="loadingDetail">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    <span class="ms-2 text-muted">Memuat percakapan...</span>
                                </div>
                                <div v-else>
                                    <!-- Initial Ticket Message -->
                                    <div class="d-flex flex-column align-items-end mb-4">
                                        <div class="bg-primary text-white p-3 rounded-3 shadow-sm" style="max-width: 85%; border-top-right-radius: 0 !important;">
                                            <div class="small fw-bold border-bottom pb-1 mb-1" style="font-size: 0.75rem;">Anda (Pelapor)</div>
                                            <div style="font-size: 0.9rem; line-height: 1.5;">{{ activeTicket?.deskripsi }}</div>
                                        </div>
                                        <span class="text-muted mt-1 font-monospace" style="font-size: 0.72rem;">{{ formatDate(activeTicket?.created_at) }}</span>
                                    </div>

                                    <!-- Conversation Threads -->
                                    <div v-for="reply in replies" class="d-flex flex-column mb-4" :class="reply.is_superadmin ? 'align-items-start' : 'align-items-end'">
                                        <div :class="reply.is_superadmin ? 'bg-white border text-dark' : 'bg-primary text-white'" class="p-3 rounded-3 shadow-sm" style="max-width: 85%;" :style="reply.is_superadmin ? 'border-top-left-radius: 0 !important;' : 'border-top-right-radius: 0 !important;'">
                                            <div class="small fw-bold border-bottom pb-1 mb-1" :class="reply.is_superadmin ? 'text-primary' : 'text-white'" style="font-size: 0.75rem;">
                                                {{ reply.is_superadmin ? 'IT Support (Super Admin)' : 'Anda' }}
                                            </div>
                                            <div style="font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap;">{{ reply.pesan }}</div>
                                        </div>
                                        <span class="text-muted mt-1 font-monospace" style="font-size: 0.72rem;">{{ formatDate(reply.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Text Input Box (Footer) -->
                            <div class="p-3 border-top bg-light" v-if="activeTicket && activeTicket.status !== 'Selesai' && activeTicket.status !== 'Batal'">
                                <form @submit.prevent="submitReply" class="d-flex gap-2">
                                    <input type="text" class="form-control rounded-2" v-model="replyText" placeholder="Ketik balasan tanggapan Anda..." required :disabled="loadingReply">
                                    <button type="submit" class="btn btn-primary rounded-2 px-3 d-inline-flex align-items-center gap-1" :disabled="loadingReply || !replyText.trim()">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="loadingReply"></span>
                                        <i class="bi bi-send-fill" v-else></i>
                                    </button>
                                </form>
                            </div>
                            <div class="p-3 border-top bg-light-subtle text-center text-muted small" v-else>
                                <i class="bi bi-lock-fill me-1"></i> Tiket ini telah ditutup (Selesai/Batal) dan percakapan tidak dapat dilanjutkan.
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles Specific to Help Widget -->
<style>
.bg-light-info {
    background-color: rgba(13, 202, 240, 0.05) !important;
}
.bg-light-warning {
    background-color: rgba(255, 193, 7, 0.07);
    border: 1px solid rgba(255, 193, 7, 0.2);
}
.animate-bounce {
    animation: bounce 1.5s infinite;
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.hover-zoom:hover img {
    transform: scale(1.03);
    transition: transform 0.2s ease-in-out;
}
</style>

<!-- Script Integration Vue 3 -->
<script>
window.VueAppRegistry.register('#bantuan-user-app', {
    data() {
        return {
            tickets: [],
            loadingList: false,
            filterStatus: '',
            filterCategory: '',
            unreadCount: 0,

            // Form Create
            form: {
                judul: '',
                category_id: '',
                urgensi: 'Sedang',
                deskripsi: '',
                lampiranFile: null
            },
            loadingSubmit: false,

            // FAQ matching
            faqs: [],
            selectedFaq: { pertanyaan: '', jawaban: '' },
            faqDetailModal: null,

            // Ticket Detail / Thread Chat
            activeTicket: null,
            replies: [],
            replyText: '',
            loadingDetail: false,
            loadingReply: false,
            
            // Instances Modals
            createModal: null,
            detailModal: null,
            pollInterval: null
        };
    },
    mounted() {
        this.fetchTickets();
        this.fetchUnreadCount();

        // Initialize Modals
        this.createModal = new bootstrap.Modal(document.getElementById('createTicketModal'));
        this.detailModal = new bootstrap.Modal(document.getElementById('ticketDetailModal'));
        this.faqDetailModal = new bootstrap.Modal(document.getElementById('faqDetailModal'));

        // Polling unread count every 30 seconds
        this.pollInterval = setInterval(() => {
            this.fetchUnreadCount();
        }, 30000);
    },
    beforeUnmount() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    },
    methods: {
        fetchTickets() {
            this.loadingList = true;
            axios.get('/SINTA-SaaS/api/v1/bantuan/list', {
                params: {
                    status: this.filterStatus,
                    category: this.filterCategory
                }
            })
            .then(res => {
                if (res.data.success) {
                    this.tickets = res.data.data;
                }
            })
            .catch(err => {
                console.error(err);
            })
            .finally(() => {
                this.loadingList = false;
            });
        },
        fetchUnreadCount() {
            axios.get('/SINTA-SaaS/api/v1/bantuan/unread-count')
            .then(res => {
                if (res.data.success) {
                    this.unreadCount = res.data.unread_count;
                }
            })
            .catch(err => console.error(err));
        },
        openCreateModal() {
            this.form = {
                judul: '',
                category_id: '',
                urgensi: 'Sedang',
                deskripsi: '',
                lampiranFile: null
            };
            this.faqs = [];
            if (this.$refs.lampiranInput) {
                this.$refs.lampiranInput.value = '';
            }
            this.createModal.show();
        },
        handleFileUpload(event) {
            this.form.lampiranFile = event.target.files[0];
        },
        debouncedFaqLookup() {
            if (this.faqTimeout) clearTimeout(this.faqTimeout);
            this.faqTimeout = setTimeout(() => {
                this.faqLookup();
            }, 400);
        },
        faqLookup() {
            const q = this.form.judul.trim();
            if (q.length < 3) {
                this.faqs = [];
                return;
            }
            axios.get('/SINTA-SaaS/api/v1/bantuan/faq-lookup', { params: { q } })
            .then(res => {
                if (res.data.success) {
                    this.faqs = res.data.data;
                }
            })
            .catch(err => console.error(err));
        },
        showFaqDetail(faq) {
            this.selectedFaq = faq;
            this.faqDetailModal.show();
        },
        closeFaqDetailModal() {
            this.faqDetailModal.hide();
        },
        submitTicket() {
            this.loadingSubmit = true;
            const formData = new FormData();
            formData.append('judul', this.form.judul);
            formData.append('category_id', this.form.category_id);
            formData.append('urgensi', this.form.urgensi);
            formData.append('deskripsi', this.form.deskripsi);
            formData.append('last_url', window.location.href);
            if (this.form.lampiranFile) {
                formData.append('lampiran', this.form.lampiranFile);
            }

            axios.post('/SINTA-SaaS/api/v1/bantuan/buat', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            .then(res => {
                if (res.data.success) {
                    Swal.fire('Sukses!', 'Tiket laporan berhasil dibuat. Tim IT Support akan segera meninjau masalah Anda.', 'success');
                    this.createModal.hide();
                    this.fetchTickets();
                    this.fetchUnreadCount();
                } else {
                    Swal.fire('Gagal!', res.data.error || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Gagal!', err.response?.data?.error || 'Koneksi ke server gagal.', 'error');
            })
            .finally(() => {
                this.loadingSubmit = false;
            });
        },
        viewTicketDetail(id) {
            this.loadingDetail = true;
            this.activeTicket = null;
            this.replies = [];
            this.replyText = '';
            
            this.detailModal.show();

            axios.get('/SINTA-SaaS/api/v1/bantuan/detail', { params: { id } })
            .then(res => {
                if (res.data.success) {
                    this.activeTicket = res.data.ticket;
                    this.replies = res.data.replies;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            })
            .catch(err => {
                Swal.fire('Error!', err.response?.data?.error || 'Gagal memuat detail tiket.', 'error');
                this.detailModal.hide();
            })
            .finally(() => {
                this.loadingDetail = false;
                this.fetchTickets(); // Refresh list to update read/unread badge
                this.fetchUnreadCount();
            });
        },
        onDetailModalClose() {
            this.fetchTickets();
            this.fetchUnreadCount();
        },
        submitReply() {
            if (!this.replyText.trim()) return;
            this.loadingReply = true;
            
            axios.post('/SINTA-SaaS/api/v1/bantuan/balas', {
                ticket_id: this.activeTicket.id,
                pesan: this.replyText
            })
            .then(res => {
                if (res.data.success) {
                    const newReply = {
                        pesan: this.replyText,
                        is_superadmin: 0,
                        created_at: new Date().toISOString(),
                        nama_pengirim: 'Anda'
                    };
                    this.replies.push(newReply);
                    this.replyText = '';
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                } else {
                    Swal.fire('Gagal!', res.data.error || 'Gagal mengirim pesan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Gagal!', err.response?.data?.error || 'Gagal membalas tiket.', 'error');
            })
            .finally(() => {
                this.loadingReply = false;
            });
        },
        scrollToBottom() {
            const el = this.$refs.chatContainer;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },
        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleString('id-ID', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },
        getUrgencyBadgeClass(urgency) {
            switch (urgency) {
                case 'Rendah': return 'badge bg-secondary px-2 py-1.5 rounded-pill';
                case 'Sedang': return 'badge bg-info text-dark px-2 py-1.5 rounded-pill';
                case 'Tinggi': return 'badge bg-warning text-dark px-2 py-1.5 rounded-pill';
                case 'Kritis': return 'badge bg-danger px-2 py-1.5 rounded-pill';
                default: return 'badge bg-light text-dark px-2 py-1.5 rounded-pill';
            }
        },
        getStatusBadgeClass(status) {
            switch (status) {
                case 'Menunggu': return 'badge bg-primary-subtle border border-primary text-primary px-2.5 py-1.5 rounded-3';
                case 'Diproses': return 'badge bg-warning-subtle border border-warning text-warning-emphasis px-2.5 py-1.5 rounded-3';
                case 'Selesai': return 'badge bg-success-subtle border border-success text-success px-2.5 py-1.5 rounded-3';
                case 'Batal': return 'badge bg-light border text-muted px-2.5 py-1.5 rounded-3';
                default: return 'badge bg-light text-dark px-2.5 py-1.5 rounded-3';
            }
        }
    }
});
</script>
