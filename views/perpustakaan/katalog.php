<?php
/**
 * View: Katalog & Koleksi Perpustakaan
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📖 Katalog & Koleksi Buku</h2>
        <p class="text-muted fs-7 mb-0">Manajemen Bibliografi, Eksemplar Fisik, Lokasi Rak, dan E-Book Digital.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7" data-bs-toggle="modal" data-bs-target="#modalTambahBuku">
            <i class="bi bi-plus-circle me-1"></i> Tambah Judul Buku
        </button>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Judul Buku</th>
                    <th>Pengarang / Penerbit</th>
                    <th>ISBN / DDC</th>
                    <th>Total Eksemplar</th>
                    <th>Tersedia</th>
                    <th>Status E-Book</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['list'])): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i> Belum ada koleksi buku terdaftar. Klik <strong>Tambah Judul Buku</strong> untuk menambahkan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['list'] as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>
                            <td>
                                <small class="d-block text-dark"><?= htmlspecialchars($item['pengarang'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small>
                                <small class="text-muted"><?= htmlspecialchars($item['penerbit'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['isbn'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-info-subtle text-info ms-1">DDC: <?= htmlspecialchars($item['klasifikasi_ddc'] ?? '000', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td><?= (int)($item['total_eksemplar'] ?? 0) ?></td>
                            <td>
                                <span class="badge bg-success-subtle text-success fw-bold"><?= (int)($item['total_tersedia'] ?? 0) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($item['is_ebook'])): ?>
                                    <span class="badge bg-primary"><i class="bi bi-file-earmark-pdf me-1"></i> E-Book</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Buku Fisik</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="/SINTA-SaaS/perpustakaan/cetak-label-thermal?barcode=<?= urlencode($item['isbn'] ?? 'BOOK-1') ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-2 me-1" title="Cetak Barcode Thermal">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <?php if (!empty($item['is_ebook'])): ?>
                                    <a href="/SINTA-SaaS/perpustakaan/baca-ebook" class="btn btn-outline-primary btn-sm rounded-2 me-1" title="Baca E-Book Watermark">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
