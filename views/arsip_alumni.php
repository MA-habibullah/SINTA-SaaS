<?php
/**
 * View Component: Arsip Alumni (Brankas Berkas Digital)
 * Hanya dimuat di dalam tab utama Buku Induk Siswa.
 */
?>
<div class="card-body p-3 p-md-4">
    <div v-if="userRole === 'super_admin' && !filterTenantId" class="py-5 text-center bg-white rounded-4 border-0">
        <i class="bi bi-building text-secondary display-4 d-block mb-3"></i>
        <h5 class="fw-bold text-dark">Pilih Sekolah Terlebih Dahulu</h5>
        <p class="text-muted fs-7 mx-auto" style="max-width: 480px;">
            Silakan pilih Sekolah terlebih dahulu pada filter "Pilih Sekolah" di atas untuk mengelola Arsip Dokumen Alumni.
        </p>
    </div>
    
    <div v-else>
        <div class="row g-4">
            <!-- LEFT COLUMN: Alumni Directory Search -->
            <div class="col-12 col-lg-5 border-end-lg">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-extrabold text-dark mb-0"><i class="bi bi-person-badge-fill text-primary me-2"></i>Daftar Alumni Lintas Dekade</h6>
                        <span class="badge bg-primary rounded-pill">{{ alumniTotal }} Alumni</span>
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control bg-light border-start-0 rounded-end-3" 
                               placeholder="Cari nama atau NISN alumni..." 
                               v-model="alumniSearch" 
                               @input="debounceAlumniSearch">
                    </div>

                    <!-- Alumni List -->
                    <div v-if="alumniLoading" class="text-center py-5">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <p class="text-muted fs-8 mt-2">Memuat daftar alumni...</p>
                    </div>
                    <div v-else class="list-group rounded-3 overflow-hidden border border-light-subtle fs-8">
                        <button v-for="siswa in alumniList" 
                                :key="siswa.id"
                                type="button" 
                                class="list-group-item list-group-item-action py-3 px-3 border-0 border-bottom d-flex align-items-center justify-content-between transition"
                                :class="{ 'bg-primary-subtle text-primary fw-bold': selectedAlumniId === siswa.id }"
                                @click="selectAlumni(siswa)">
                            <div class="d-flex flex-column text-start">
                                <span class="fs-7 text-dark fw-bold mb-0.5">{{ siswa.nama_lengkap }}</span>
                                <span class="text-muted text-uppercase" style="font-size:0.7rem;">
                                    NISN: {{ siswa.nisn || '-' }} | Kelas: {{ siswa.nama_kelas || '-' }}
                                </span>
                            </div>
                            <i class="bi bi-chevron-right fs-7 text-muted"></i>
                        </button>
                        <div v-if="alumniList.length === 0" class="text-center py-5 text-muted bg-light">
                            Tidak ada data alumni terdaftar.
                        </div>
                    </div>

                    <!-- Pagination Controls -->
                    <nav v-if="alumniTotalPages > 1" class="d-flex justify-content-center mt-2">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: alumniCurrentPage === 1 }">
                                <a class="page-link cursor-pointer" @click="fetchAlumni(alumniCurrentPage - 1)">&laquo;</a>
                            </li>
                            <li class="page-item" v-for="p in alumniTotalPages" :key="p" :class="{ active: alumniCurrentPage === p }">
                                <a class="page-link cursor-pointer" @click="fetchAlumni(p)">{{ p }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: alumniCurrentPage === alumniTotalPages }">
                                <a class="page-link cursor-pointer" @click="fetchAlumni(alumniCurrentPage + 1)">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- RIGHT COLUMN: Document Archive Vault -->
            <div class="col-12 col-lg-7">
                <!-- If no alumni selected -->
                <div v-if="!selectedAlumniId" class="h-100 d-flex flex-column align-items-center justify-content-center py-5 text-center text-muted">
                    <i class="bi bi-safe2 display-2 text-secondary-subtle mb-3"></i>
                    <h5 class="fw-bold text-dark">Pilih Alumni Terlebih Dahulu</h5>
                    <p class="fs-8 px-4" style="max-width: 400px;">
                        Silakan klik salah satu nama alumni di panel kiri untuk membuka brankas berkas digital dan mengelola dokumen mereka.
                    </p>
                </div>

                <!-- If alumni selected -->
                <div v-else class="d-flex flex-column gap-4">
                    <!-- Alumni Header card -->
                    <div class="card bg-primary-subtle border-0 rounded-4">
                        <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 46px; height: 46px; font-size: 1.25rem;">
                                    {{ selectedAlumniName.charAt(0) }}
                                </div>
                                <div class="d-flex flex-column">
                                    <h6 class="fw-extrabold text-dark mb-0.5">{{ selectedAlumniName }}</h6>
                                    <span class="text-primary-emphasis fs-8 fw-semibold">Status: Alumni / Lulus</span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3 px-3 fs-8" @click="selectedAlumniId = ''; clearCapture();">
                                <i class="bi bi-x-circle me-1"></i>Tutup
                            </button>
                        </div>
                    </div>

                    <!-- Upload Form Section -->
                    <div class="card border border-light-subtle rounded-4 p-3.5 shadow-sm bg-white">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i>Arsipkan Berkas Baru</h6>
                        
                        <div class="row g-3">
                            <!-- Document Category -->
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold text-dark fs-8 mb-1.5">Jenis Dokumen</label>
                                <select class="form-select form-select-sm rounded-3" v-model="arsipJenisDokumen">
                                    <option value="Buku Induk">Buku Induk (Multi-halaman)</option>
                                    <option value="Ijazah">Ijazah</option>
                                    <option value="SKHUN">SKHUN</option>
                                    <option value="Sertifikat/SKL">Sertifikat / SKL</option>
                                    <option value="Lainnya">Dokumen Lainnya</option>
                                </select>
                            </div>
                            
                            <!-- Notes/Keterangan -->
                            <div class="col-12 col-md-8">
                                <label class="form-label fw-semibold text-dark fs-8 mb-1.5">Keterangan / Catatan Singkat</label>
                                <input type="text" class="form-control form-control-sm rounded-3" 
                                       placeholder="Contoh: Ijazah depan belakang asli atau Lembar 1-5 Buku Induk" 
                                       v-model="arsipKeterangan">
                            </div>

                            <!-- Upload Inputs Toggle (Direct PDF vs Camera Photo Capture) -->
                            <div class="col-12 mt-3.5">
                                <div class="bg-light p-3 rounded-4 border">
                                    <div class="d-flex flex-column gap-3.5">
                                        <!-- Source selection labels -->
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="fw-bold text-dark fs-8">Pilih Sumber Berkas:</span>
                                        </div>

                                        <div class="row g-3">
                                            <!-- Option 1: Mobile Camera HP / Multi-halaman JPG -->
                                            <div class="col-12 col-md-6">
                                                <div class="border rounded-3 p-3 bg-white hover-shadow transition d-flex flex-column align-items-center text-center">
                                                    <i class="bi bi-camera-fill text-primary display-6 mb-2"></i>
                                                    <h6 class="fw-bold fs-8 text-dark mb-1">Ambil Foto HP (Multi-Halaman)</h6>
                                                    <p class="text-muted fs-9 mb-3">Foto berkas langsung pakai kamera HP secara bertahap</p>
                                                    <label class="btn btn-primary btn-sm rounded-pill px-3 fs-9 cursor-pointer w-100">
                                                        <i class="bi bi-plus-lg me-1"></i> Foto Halaman
                                                        <input type="file" accept="image/jpeg,image/png" capture="environment" multiple class="d-none" @change="addCapturePage">
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Option 2: Direct PDF File -->
                                            <div class="col-12 col-md-6">
                                                <div class="border rounded-3 p-3 bg-white hover-shadow transition d-flex flex-column align-items-center text-center">
                                                    <i class="bi bi-file-earmark-pdf-fill text-danger display-6 mb-2"></i>
                                                    <h6 class="fw-bold fs-8 text-dark mb-1">Unggah PDF Langsung</h6>
                                                    <p class="text-muted fs-9 mb-3">Unggah 1 file PDF yang sudah digabungkan sebelumnya</p>
                                                    <label class="btn btn-outline-danger btn-sm rounded-pill px-3 fs-9 cursor-pointer w-100">
                                                        <i class="bi bi-upload me-1"></i> Pilih PDF
                                                        <input type="file" ref="pdfFileInput" accept="application/pdf" class="d-none" @change="capturedAlumniImages = []">
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Captured Image Thumbnails Preview Grid -->
                                        <div v-if="capturedAlumniImageUrls.length > 0" class="mt-2.5">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold text-dark fs-8">Kumpulan Foto Terambil ({{ capturedAlumniImageUrls.length }} Halaman):</span>
                                                <button type="button" class="btn btn-link text-danger fs-9 p-0 text-decoration-none" @click="clearCapture">
                                                    <i class="bi bi-trash me-1"></i>Bersihkan Semua
                                                </button>
                                            </div>
                                            <!-- Responsive Flex thumbnails -->
                                            <div class="d-flex flex-wrap gap-2.5 p-2 bg-white rounded-3 border">
                                                <div v-for="(url, idx) in capturedAlumniImageUrls" :key="idx" class="position-relative" style="width: 76px; height: 104px;">
                                                    <img :src="url" class="w-100 h-100 object-fit-cover rounded border border-secondary-subtle">
                                                    <span class="position-absolute bottom-0 start-0 bg-dark text-white fw-bold px-1.5 py-0.5 rounded-end" style="font-size:0.6rem;">
                                                        Hal {{ idx + 1 }}
                                                    </span>
                                                    <button type="button" 
                                                            class="position-absolute top-0 end-0 bg-danger text-white rounded-circle border-0 d-flex align-items-center justify-content-center shadow" 
                                                            style="width: 20px; height: 20px; font-size: 0.65rem;"
                                                            @click="removeCapturePage(idx)">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="text-primary d-block mt-2 font-monospace fs-9">
                                                <i class="bi bi-info-circle-fill me-1"></i>Seluruh foto akan dikompresi & digabungkan otomatis menjadi 1 file PDF tunggal di server.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit action buttons -->
                            <div class="col-12 mt-3.5 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-3 fs-8" @click="clearCapture" :disabled="uploadingAlumniDoc">
                                    Reset Form
                                </button>
                                <button type="button" class="btn btn-primary btn-sm rounded-3 px-4.5 fs-8" @click="handleAlumniDocUpload" :disabled="uploadingAlumniDoc">
                                    <span v-if="uploadingAlumniDoc" class="spinner-border spinner-border-sm me-1.5" role="status"></span>
                                    <i v-else class="bi bi-check2-circle me-1.5"></i>
                                    {{ uploadingAlumniDoc ? 'Mengompresi & Menggabung...' : 'Unggah & Gabungkan ke PDF' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Document List Section (Vault) -->
                    <div class="card border border-light-subtle rounded-4 p-3.5 shadow-sm bg-white">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Brankas Dokumen Terarsip</h6>
                        
                        <div v-if="alumniDocsLoading" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                        <div v-else-if="alumniDocs.length === 0" class="text-center py-4 text-muted fs-8">
                            Belum ada dokumen digital yang diarsipkan untuk siswa ini.
                        </div>
                        <div v-else class="table-responsive rounded-3 border">
                            <table class="table table-hover align-middle mb-0 fs-8">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jenis Berkas</th>
                                        <th>Ukuran</th>
                                        <th>Catatan/Keterangan</th>
                                        <th>Diunggah Pada</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="doc in alumniDocs" :key="doc.id">
                                        <td class="fw-bold">
                                            <i class="bi bi-file-earmark-pdf text-danger me-1.5"></i>{{ doc.jenis_dokumen }}
                                        </td>
                                        <td class="text-muted">
                                            {{ doc.file_size >= 1048576 ? (doc.file_size / 1048576).toFixed(2) + ' MB' : (doc.file_size / 1024).toFixed(0) + ' KB' }}
                                        </td>
                                        <td>{{ doc.keterangan || '-' }}</td>
                                        <td class="text-muted">{{ formatTanggal(doc.created_at) }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm rounded-3 py-1 px-2.5 fs-9 fw-bold" @click="viewAlumniDoc(doc.id, doc.jenis_dokumen + ' - ' + selectedAlumniName)">
                                                    <i class="bi bi-eye-fill"></i> Lihat
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm rounded-3 py-1 px-2 fs-9" @click="deleteAlumniDoc(doc.id)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
