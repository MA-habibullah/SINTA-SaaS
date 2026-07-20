<?php
/**
 * View: Pusat Bantuan Admin (Super Admin Dashboard)
 * Location: views/bantuan_admin.php
 */
?>
<div class="container-fluid px-4 py-4" id="bantuan-admin-app" v-cloak>
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="h3 mb-1 fw-bold text-dark d-flex align-items-center">
                <i class="bi bi-headset text-primary me-3 fs-3"></i>
                Dashboard Tiket Masuk & Layanan Bantuan
            </h1>
            <p class="text-muted mb-0">Manajemen keluhan, kendala teknis, dan permohonan dari seluruh sekolah/tenant terdaftar.</p>
        </div>
    </div>

    <!-- Alert Unread Badge Info -->
    <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center gap-3 mb-4" v-if="unreadCount > 0">
        <i class="bi bi-exclamation-circle-fill text-warning fs-4 animate-bounce"></i>
        <div>
            Ada <strong>{{ unreadCount }}</strong> tiket baru atau mendapat balasan baru yang membutuhkan perhatian Anda segera.
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row">
        <!-- Ticket List Card -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold text-dark">Daftar Tiket Masuk Seluruh Tenant</span>
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
                                    <th class="px-4 py-3">Asal Sekolah (Tenant)</th>
                                    <th class="py-3">Pelapor (User)</th>
                                    <th class="py-3">Subjek Laporan</th>
                                    <th class="py-3">Kategori</th>
                                    <th class="py-3 text-center">Urgensi</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3">SLA Deadline</th>
                                    <th class="px-4 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loadingList">
                                    <td colspan="8" class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="ms-2 text-muted">Memuat daftar tiket masuk...</span>
                                    </td>
                                </tr>
                                <tr v-else-if="tickets.length === 0">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                                        Tidak ada tiket laporan masuk yang cocok.
                                    </td>
                                </tr>
                                <tr v-else v-for="ticket in tickets" :key="ticket.id" :class="{'bg-light-warning-row': ticket.admin_unread}">
                                    <td class="px-4 py-3 fw-bold text-primary">
                                        {{ ticket.nama_sekolah || 'Super Admin Platform' }}
                                    </td>
                                    <td class="py-3 text-nowrap">
                                        {{ ticket.nama_pelapor }}
                                    </td>
                                    <td class="py-3 fw-semibold">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning p-1 rounded-circle" v-if="ticket.admin_unread" title="Pesan baru!"></span>
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
                                                <i class="bi bi-exclamation-triangle-fill"></i> Melewati SLA!
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <button class="btn btn-primary btn-sm rounded-2 d-inline-flex align-items-center gap-1" @click="viewTicketDetail(ticket.id)">
                                            <i class="bi bi-chat-right-text"></i>
                                            Tanggapi Laporan
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

    <!-- MODAL: Tanggapi Laporan (Super Admin View) -->
    <div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg rounded-3" style="height: 92vh;">
                <!-- Header -->
                <div class="modal-header border-bottom py-3 d-flex flex-column align-items-start gap-2 bg-light">
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-headset"></i>
                            Pusat Respon & Solusi Tiket
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="onDetailModalClose"></button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-1" v-if="activeTicket">
                        <span class="badge bg-primary px-2.5 py-1.5 rounded-3">Tenant: {{ activeTicket.nama_sekolah || 'Super Admin Platform' }}</span>
                        <span class="badge bg-light text-dark border">Kategori: {{ activeTicket.nama_kategori }}</span>
                        <span :class="getUrgencyBadgeClass(activeTicket.urgensi)">Urgensi: {{ activeTicket.urgensi }}</span>
                        <span :class="getStatusBadgeClass(activeTicket.status)">Status: {{ activeTicket.status }}</span>
                    </div>
                </div>

                <!-- Body (Split Layout: Detail & Chat Thread) -->
                <div class="modal-body p-0 d-flex flex-column h-100 bg-white" style="overflow: hidden;">
                    <div class="d-flex h-100 flex-column flex-md-row" style="min-height: 0;">

                        <!-- Left Panel: Ticket Detail & Secret Metadata -->
                        <div class="col-md-4 border-end p-4 bg-light-subtle d-flex flex-column" style="overflow-y: auto; height: 100%;">
                            <div v-if="activeTicket">
                                <h6 class="fw-bold text-dark mb-1">Pelapor</h6>
                                <p class="text-dark mb-3">{{ activeTicket.nama_pelapor }}</p>

                                <h6 class="fw-bold text-dark mb-1">Subjek Laporan</h6>
                                <p class="text-dark fw-semibold mb-3">{{ activeTicket.judul }}</p>

                                <h6 class="fw-bold text-dark mb-1">Deskripsi Masalah</h6>
                                <p class="text-muted small mb-3" style="white-space: pre-wrap; line-height: 1.5;">{{ activeTicket.deskripsi }}</p>

                                <h6 class="fw-bold text-dark mb-1" v-if="activeTicket.lampiran">Lampiran Gambar</h6>
                                <div class="mb-3" v-if="activeTicket.lampiran">
                                    <a :href="'/SINTA-SaaS' + activeTicket.lampiran" target="_blank" class="d-block border rounded-3 p-1 bg-white hover-zoom shadow-sm text-center">
                                        <img :src="'/SINTA-SaaS' + activeTicket.lampiran" class="img-fluid rounded-2" style="max-height: 120px;" alt="Lampiran Laporan">
                                        <span class="d-block text-primary small fw-semibold mt-1"><i class="bi bi-zoom-in"></i> Klik untuk Perbesar</span>
                                    </a>
                                </div>

                                <hr>

                                <!-- Secret Metadata Widget (IT Troubleshooting Helper) -->
                                <div class="card border border-warning bg-light-warning mb-3">
                                    <div class="card-body p-3">
                                        <h6 class="card-title fw-bold text-warning-emphasis mb-2" style="font-size: 0.8rem;">
                                            <i class="bi bi-cpu-fill"></i> Data Pelacak Sistem (IT Debug Metadata)
                                        </h6>
                                        <div class="small" style="font-size: 0.72rem; line-height: 1.4;">
                                            <div class="mb-2"><strong>Browser/OS User-Agent:</strong><br><span class="text-muted font-monospace">{{ activeTicket.user_agent }}</span></div>
                                            <div><strong>Halaman Terakhir Dibuka:</strong><br><a :href="activeTicket.last_url" target="_blank" class="text-primary font-monospace text-break">{{ activeTicket.last_url }}</a></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action: Update Status (Direct Control) -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark" style="font-size: 0.8rem;">Ganti Status Tiket</label>
                                    <select class="form-select form-select-sm rounded-2" v-model="activeTicket.status" @change="updateTicketStatus">
                                        <option value="Menunggu">Menunggu</option>
                                        <option value="Diproses">Diproses</option>
                                        <option value="Selesai">Selesai</option>
                                        <option value="Batal">Batal</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel: Conversation Threads & Canned Responses -->
                        <div class="col-md-8 d-flex flex-column bg-white h-100" style="overflow: hidden;">
                            <!-- Chat Area (Scrollable) -->
                            <div class="flex-grow-1 p-4" ref="chatContainer" style="overflow-y: auto; background-color: #f8f9fa;">
                                <div class="text-center py-5" v-if="loadingDetail">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    <span class="ms-2 text-muted">Memuat percakapan...</span>
                                </div>
                                <div v-else>
                                    <!-- Initial Ticket Message -->
                                    <div class="d-flex flex-column align-items-start mb-4">
                                        <div class="bg-white border text-dark p-3 rounded-3 shadow-sm" style="max-width: 85%; border-top-left-radius: 0 !important;">
                                            <div class="small fw-bold text-primary border-bottom pb-1 mb-1" style="font-size: 0.75rem;">{{ activeTicket?.nama_pelapor }} (Pelapor)</div>
                                            <div style="font-size: 0.9rem; line-height: 1.5;">{{ activeTicket?.deskripsi }}</div>
                                        </div>
                                        <span class="text-muted mt-1 font-monospace" style="font-size: 0.72rem;">{{ formatDate(activeTicket?.created_at) }}</span>
                                    </div>

                                    <!-- Conversation Threads -->
                                    <div v-for="reply in replies" class="d-flex flex-column mb-4" :class="reply.is_superadmin ? 'align-items-end' : 'align-items-start'">
                                        <div :class="reply.is_superadmin ? 'bg-primary text-white' : 'bg-white border text-dark'" class="p-3 rounded-3 shadow-sm" style="max-width: 85%;" :style="reply.is_superadmin ? 'border-top-right-radius: 0 !important;' : 'border-top-left-radius: 0 !important;'">
                                            <div class="small fw-bold border-bottom pb-1 mb-1" :class="reply.is_superadmin ? 'text-white' : 'text-primary'" style="font-size: 0.75rem;">
                                                {{ reply.is_superadmin ? 'Anda (IT Support)' : reply.nama_pengirim }}
                                            </div>
                                            <div style="font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap;">{{ reply.pesan }}</div>
                                        </div>
                                        <span class="text-muted mt-1 font-monospace" style="font-size: 0.72rem;">{{ formatDate(reply.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Canned Responses Dropdown + Chat Input -->
                            <div class="p-3 border-top bg-light" v-if="activeTicket">
                                <!-- Canned responses selector -->
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="small fw-bold text-muted text-nowrap"><i class="bi bi-lightning-fill text-warning"></i> Canned Responses:</span>
                                    <select class="form-select form-select-sm border rounded-2" style="max-width: 250px;" v-model="selectedCannedId" @change="applyCannedResponse">
                                        <option value="">Pilih Template Balasan Cepat</option>
                                        <option v-for="canned in cannedResponses" :key="canned.id" :value="canned.id">{{ canned.judul }}</option>
                                    </select>
                                </div>
                                <form @submit.prevent="submitReply" class="d-flex gap-2">
                                    <textarea class="form-control rounded-2" v-model="replyText" rows="2" placeholder="Ketik balasan solusi atau tanggapan Anda..." required :disabled="loadingReply"></textarea>
                                    <button type="submit" class="btn btn-primary rounded-2 px-4 d-inline-flex align-items-center justify-content-center" :disabled="loadingReply || !replyText.trim()">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="loadingReply"></span>
                                        <i class="bi bi-send-fill" v-else></i>
                                    </button>
                                </form>
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
.bg-light-warning-row {
    background-color: rgba(255, 193, 7, 0.04) !important;
}
.bg-light-warning {
    background-color: rgba(255, 193, 7, 0.08);
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
window.VueAppRegistry.register('#bantuan-admin-app', {
    data() {
        return {
            tickets: [],
            loadingList: false,
            filterStatus: '',
            filterCategory: '',
            unreadCount: 0,

            // Canned responses
            cannedResponses: [],
            selectedCannedId: '',

            // Ticket Detail / Thread Chat
            activeTicket: null,
            replies: [],
            replyText: '',
            loadingDetail: false,
            loadingReply: false,
            
            // Instances Modals
            detailModal: null,
            pollInterval: null
        };
    },
    mounted() {
        this.fetchTickets();
        this.fetchUnreadCount();
        this.fetchCannedResponses();

        // Initialize Modals
        this.detailModal = new bootstrap.Modal(document.getElementById('ticketDetailModal'));

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
        fetchCannedResponses() {
            axios.get('/SINTA-SaaS/api/v1/bantuan/canned-responses')
            .then(res => {
                if (res.data.success) {
                    this.cannedResponses = res.data.data;
                }
            })
            .catch(err => console.error(err));
        },
        applyCannedResponse() {
            if (!this.selectedCannedId) return;
            const canned = this.cannedResponses.find(c => c.id === this.selectedCannedId);
            if (canned) {
                this.replyText = canned.konten;
            }
            this.selectedCannedId = ''; // Reset selection dropdown
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
        updateTicketStatus() {
            if (!this.activeTicket) return;
            axios.post('/SINTA-SaaS/api/v1/bantuan/update-status', {
                ticket_id: this.activeTicket.id,
                status: this.activeTicket.status
            })
            .then(res => {
                if (res.data.success) {
                    this.toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    this.toast.fire({ icon: 'success', title: 'Status tiket diperbarui.' });
                }
            })
            .catch(err => {
                Swal.fire('Gagal!', err.response?.data?.error || 'Gagal memperbarui status.', 'error');
            });
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
                        is_superadmin: 1,
                        created_at: new Date().toISOString(),
                        nama_pengirim: 'Anda'
                    };
                    this.replies.push(newReply);
                    this.replyText = '';
                    // Otomatis ubah status visual menjadi 'Diproses'
                    this.activeTicket.status = 'Diproses';
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
