<?php
/**
 * View: Keanggotaan Perpustakaan & Bebas Pustaka
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">👥 Keanggotaan & Surat Bebas Pustaka</h2>
        <p class="text-muted fs-7 mb-0">Verifikasi Anggota Perpustakaan, Cetak Kartu Digital, & Penerbitan Surat Bebas Pustaka Kelulusan.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7" data-bs-toggle="modal" data-bs-target="#modalSyncDapodik">
            <i class="bi bi-arrow-repeat me-1"></i> Sync Data Anggota (Dapodik/EMIS)
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nomor Anggota</th>
                    <th>Nama Anggota</th>
                    <th>Tipe / Peran</th>
                    <th>Status Pinjaman</th>
                    <th>Tanggungan Denda</th>
                    <th>Status Bebas Pustaka</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['anggota_list'])): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-person-bounding-box fs-3 d-block mb-2"></i> Belum ada data anggota terdaftar. Klik <strong>Sync Data Anggota</strong> untuk mengimpor dari data pokok siswa/guru.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['anggota_list'] as $idx => $a): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><code><?= htmlspecialchars($a['no_anggota'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><strong><?= htmlspecialchars($a['nama_lengkap'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($a['tipe_anggota'] ?? 'Siswa', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= (int)($a['pinjam_aktif'] ?? 0) ?> Buku</td>
                            <td>Rp <?= number_format((float)($a['total_denda'] ?? 0)) ?></td>
                            <td>
                                <?php if (empty($a['pinjam_aktif']) && empty($a['total_denda'])): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> LULUS (Bebas Pustaka)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i> Ada Tanggungan</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm rounded-2 me-1" title="Cetak Kartu Anggota Digital QR">
                                    <i class="bi bi-qr-code me-1"></i> Kartu
                                </button>
                                <button class="btn btn-outline-success btn-sm rounded-2" title="Cetak Surat Bebas Pustaka">
                                    <i class="bi bi-file-earmark-check me-1"></i> Surat Bebas
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
