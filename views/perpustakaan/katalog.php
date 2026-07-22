<?php
/**
 * View: Katalog & Koleksi Perpustakaan
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📖 Katalog & Koleksi Buku</h2>
        <p class="text-muted fs-7 mb-0">Manajemen Bibliografi, Edit Katalog, Eksemplar Fisik, Lokasi Rak, E-Book Digital, & Export Excel.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <a href="/SINTA-SaaS/perpustakaan/katalog/export-excel<?= !empty($data['active_tenant_id']) ? '?tenant_id=' . urlencode($data['active_tenant_id']) : '' ?>" class="btn btn-success btn-sm rounded-3 px-3 py-2 fs-7" title="Download Excel (.xlsx)">
            <i class="bi bi-file-earmark-excel me-1"></i> Download Excel (.xlsx)
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7" id="btnTambahBukuModal" data-bs-toggle="modal" data-bs-target="#modalTambahBuku">
            <i class="bi bi-plus-circle me-1"></i> Tambah Judul Buku
        </button>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Sekolah / Tenant</th>
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
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i> Belum ada koleksi buku terdaftar. Klik <strong>Tambah Judul Buku</strong> untuk menambahkan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['list'] as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($item['tenant_name'] ?? 'Sekolah Aktif') ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>
                            <td>
                                <small class="d-block text-dark"><?= htmlspecialchars($item['pengarang'] ?? ($item['penulis'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                                <small class="text-muted"><?= htmlspecialchars($item['penerbit'] ?? '-', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)($item['tahun_terbit'] ?? '-')) ?>)</small>
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
                                <button type="button" class="btn btn-outline-warning btn-sm rounded-2 me-1 btn-edit-katalog"
                                        data-id="<?= htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-judul="<?= htmlspecialchars($item['judul'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-ddc="<?= htmlspecialchars($item['klasifikasi_ddc'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-pengarang="<?= htmlspecialchars($item['pengarang'] ?? ($item['penulis'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-penerbit="<?= htmlspecialchars($item['penerbit'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-tahun="<?= htmlspecialchars((string)($item['tahun_terbit'] ?? date('Y')), ENT_QUOTES, 'UTF-8') ?>"
                                        data-isbn="<?= htmlspecialchars($item['isbn'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-ebook="<?= !empty($item['is_ebook']) ? '1' : '0' ?>"
                                        data-tenant="<?= htmlspecialchars($item['tenant_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        title="Edit Katalog Buku">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
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

<!-- Modal Tambah / Edit Judul Buku -->
<div class="modal fade" id="modalTambahBuku" tabindex="-1" aria-labelledby="modalTambahBukuLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalTambahBukuLabel"><i class="bi bi-book me-2"></i> Tambah Judul Buku Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/api/v1/perpustakaan/katalog/simpan" method="POST" id="formTambahBuku" data-turbo="false">
                <input type="hidden" name="id" id="book_id_input" value="">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <?php if ($data['is_super_admin'] ?? false): ?>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Target Sekolah / Tenant <span class="text-danger">*</span></label>
                                <select name="tenant_id" id="book_tenant_select" class="form-select rounded-3 bg-light border-primary" required>
                                    <?php foreach ($data['tenants'] as $t): ?>
                                        <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === ($data['active_tenant_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['nama_sekolah']) ?> (<?= htmlspecialchars($t['npsn']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold">Judul Buku <span class="text-danger">*</span></label>
                            <input type="text" name="judul" id="book_judul_input" class="form-control rounded-3" placeholder="Contoh: Matematika Diskrit SMA" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Kode DDC</label>
                            <input type="text" name="klasifikasi_ddc" id="book_ddc_input" class="form-control rounded-3" placeholder="Contoh: 510">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nama Pengarang / Penulis</label>
                            <input type="text" name="pengarang" id="book_pengarang_input" class="form-control rounded-3" placeholder="Contoh: Prof. Yohanes Surya">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Penerbit & Tahun</label>
                            <div class="input-group">
                                <input type="text" name="penerbit" id="book_penerbit_input" class="form-control rounded-3" placeholder="Nama Penerbit">
                                <input type="number" name="tahun_terbit" id="book_tahun_input" class="form-control rounded-3" placeholder="Tahun" value="<?= date('Y') ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nomor ISBN</label>
                            <input type="text" name="isbn" id="book_isbn_input" class="form-control rounded-3" placeholder="978-602-xxxx-xx-x">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Tipe Koleksi</label>
                            <select name="is_ebook" id="book_ebook_select" class="form-select rounded-3">
                                <option value="0">Buku Fisik cetak</option>
                                <option value="1">E-Book Digital (PDF Reader)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4" id="btnSubmitBookModal"><i class="bi bi-save me-1"></i> Simpan Buku</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    function handleKatalogClick(e) {
        const btnEdit = e.target.closest('.btn-edit-katalog');
        const btnTambah = e.target.closest('#btnTambahBukuModal');

        if (btnTambah) {
            document.getElementById('modalTambahBukuLabel').innerHTML = '<i class="bi bi-book me-2"></i> Tambah Judul Buku Baru';
            document.getElementById('book_id_input').value = '';
            document.getElementById('book_judul_input').value = '';
            document.getElementById('book_ddc_input').value = '';
            document.getElementById('book_pengarang_input').value = '';
            document.getElementById('book_penerbit_input').value = '';
            document.getElementById('book_tahun_input').value = '<?= date('Y') ?>';
            document.getElementById('book_isbn_input').value = '';
            document.getElementById('book_ebook_select').value = '0';
            document.getElementById('btnSubmitBookModal').innerHTML = '<i class="bi bi-save me-1"></i> Simpan Buku';
            return;
        }

        if (btnEdit) {
            const ds = btnEdit.dataset;
            document.getElementById('modalTambahBukuLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Judul Buku';
            document.getElementById('book_id_input').value = ds.id || '';
            document.getElementById('book_judul_input').value = ds.judul || '';
            document.getElementById('book_ddc_input').value = ds.ddc || '';
            document.getElementById('book_pengarang_input').value = ds.pengarang || '';
            document.getElementById('book_penerbit_input').value = ds.penerbit || '';
            document.getElementById('book_tahun_input').value = ds.tahun || '<?= date('Y') ?>';
            document.getElementById('book_isbn_input').value = ds.isbn || '';
            document.getElementById('book_ebook_select').value = ds.ebook || '0';
            
            const tenantSelect = document.getElementById('book_tenant_select');
            if (tenantSelect && ds.tenant) {
                tenantSelect.value = ds.tenant;
            }

            document.getElementById('btnSubmitBookModal').innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Katalog';
            
            const modalEl = document.getElementById('modalTambahBuku');
            if (window.bootstrap && window.bootstrap.Modal) {
                const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }
    }

    document.removeEventListener('click', handleKatalogClick);
    document.addEventListener('click', handleKatalogClick);
})();
</script>
