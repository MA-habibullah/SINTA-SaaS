<?php
$title = "Ekstrakurikuler - Kesiswaan";
ob_start();
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Modul Ekstrakurikuler</h1>
            <p class="text-muted mb-0">Kelola master data, anggota, jurnal kegiatan, dan penilaian ekskul.</p>
        </div>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-pills mb-4" id="ekskulTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="master-tab" data-bs-toggle="tab" data-bs-target="#master" type="button" role="tab" aria-controls="master" aria-selected="true">
                <i class="bi bi-diagram-3 me-2"></i>Master Ekskul
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="anggota-tab" data-bs-toggle="tab" data-bs-target="#anggota" type="button" role="tab" aria-controls="anggota" aria-selected="false">
                <i class="bi bi-people me-2"></i>Kelola Anggota
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="jurnal-tab" data-bs-toggle="tab" data-bs-target="#jurnal" type="button" role="tab" aria-controls="jurnal" aria-selected="false">
                <i class="bi bi-journal-text me-2"></i>Jurnal Kegiatan
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="nilai-tab" data-bs-toggle="tab" data-bs-target="#nilai" type="button" role="tab" aria-controls="nilai" aria-selected="false">
                <i class="bi bi-check2-circle me-2"></i>Penilaian & Presensi
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="ekskulTabContent">
        
        <!-- Tab: Master Ekskul -->
        <div class="tab-pane fade show active" id="master" role="tabpanel" aria-labelledby="master-tab">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Ekstrakurikuler</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tambahEkskulModal">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Ekskul
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nama Ekskul</th>
                                    <th>Kategori</th>
                                    <th>Guru Pembina</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($master_ekskul)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada data ekstrakurikuler.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($master_ekskul as $ekskul): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= htmlspecialchars($ekskul['nama_ekskul']) ?></td>
                                        <td>
                                            <?php if($ekskul['kategori'] === 'Wajib'): ?>
                                                <span class="badge bg-danger">Wajib</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark">Pilihan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($ekskul['nama_pembina'] ?? 'Belum Ditugaskan') ?></td>
                                        <td class="text-center pe-4">
                                            <button class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Kelola Anggota -->
        <div class="tab-pane fade" id="anggota" role="tabpanel" aria-labelledby="anggota-tab">
            <div class="card shadow-sm border-0">
                <div class="card-body py-5 text-center">
                    <i class="bi bi-tools text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5>Fitur Kelola Anggota Sedang Dibangun</h5>
                    <p class="text-muted">Modul untuk memasukkan siswa ke dalam ekskul menggunakan multi-select akan segera tersedia.</p>
                </div>
            </div>
        </div>

        <!-- Tab: Jurnal Kegiatan -->
        <div class="tab-pane fade" id="jurnal" role="tabpanel" aria-labelledby="jurnal-tab">
            <div class="card shadow-sm border-0">
                <div class="card-body py-5 text-center">
                    <i class="bi bi-tools text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5>Fitur Jurnal Kegiatan Sedang Dibangun</h5>
                    <p class="text-muted">Tempat pembina mencatat jadwal rutin dan log kegiatan harian ekskul.</p>
                </div>
            </div>
        </div>

        <!-- Tab: Penilaian & Presensi -->
        <div class="tab-pane fade" id="nilai" role="tabpanel" aria-labelledby="nilai-tab">
            <div class="card shadow-sm border-0">
                <div class="card-body py-5 text-center">
                    <i class="bi bi-tools text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5>Fitur Penilaian & Presensi Sedang Dibangun</h5>
                    <p class="text-muted">Modul penilaian terintegrasi E-Rapor akan segera tersedia.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Tambah Ekskul -->
<div class="modal fade" id="tambahEkskulModal" tabindex="-1" aria-labelledby="tambahEkskulModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/SINTA-SaaS/api/v1/ekskul/tambah" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="tambahEkskulModalLabel">Tambah Ekstrakurikuler</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Ekskul</label>
                <input type="text" name="nama_ekskul" class="form-control" required placeholder="Contoh: Pramuka">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Kategori</label>
                <select name="kategori" class="form-select" required>
                    <option value="Pilihan">Pilihan</option>
                    <option value="Wajib">Wajib</option>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout/master.php';
?>
