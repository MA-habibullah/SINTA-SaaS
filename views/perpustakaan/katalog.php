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

<!-- Modal Tambah Judul Buku -->
<div class="modal fade" id="modalTambahBuku" tabindex="-1" aria-labelledby="modalTambahBukuLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalTambahBukuLabel"><i class="bi bi-book me-2"></i> Tambah Judul Buku Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/api/v1/perpustakaan/katalog/simpan" method="POST" id="formTambahBuku">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold">Judul Buku <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control rounded-3" placeholder="Contoh: Matematika Diskrit SMA" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Kode DDC</label>
                            <input type="text" name="klasifikasi_ddc" class="form-control rounded-3" placeholder="Contoh: 510">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nama Pengarang / Penulis</label>
                            <input type="text" name="pengarang" class="form-control rounded-3" placeholder="Contoh: Prof. Yohanes Surya">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Penerbit & Tahun</label>
                            <div class="input-group">
                                <input type="text" name="penerbit" class="form-control rounded-3" placeholder="Nama Penerbit">
                                <input type="number" name="tahun_terbit" class="form-control rounded-3" placeholder="Tahun" value="<?= date('Y') ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nomor ISBN</label>
                            <input type="text" name="isbn" class="form-control rounded-3" placeholder="978-602-xxxx-xx-x">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Tipe Koleksi</label>
                            <select name="is_ebook" class="form-select rounded-3">
                                <option value="0">Buku Fisik cetak</option>
                                <option value="1">E-Book Digital (PDF Reader)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Simpan Buku</button>
                </div>
            </form>
        </div>
    </div>
</div>
