<?php
/**
 * View: Katalog & Inventori Terpadu
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📦 Katalog & Inventori Perpustakaan</h2>
        <p class="text-muted fs-7 mb-0">Manajemen Koleksi Buku, Sesi Stock Opname, Penataan Rak, Klasifikasi DDC, & Usulan Pengadaan Buku.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Sleek Navtabs Section -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-2 bg-white rounded-4">
        <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2" id="katalogTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition active" id="katalog-tab" data-bs-toggle="tab" data-bs-target="#katalog-pane" type="button" role="tab" aria-controls="katalog-pane" aria-selected="true">
                        <i class="bi bi-book me-2 text-primary"></i>1. Katalog Buku
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="opname-tab" data-bs-toggle="tab" data-bs-target="#opname-pane" type="button" role="tab" aria-controls="opname-pane" aria-selected="false">
                        <i class="bi bi-qr-code-scan me-2 text-success"></i>2. Stock Opname / Audit
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="rak-tab" data-bs-toggle="tab" data-bs-target="#rak-pane" type="button" role="tab" aria-controls="rak-pane" aria-selected="false">
                        <i class="bi bi-tag me-2 text-warning"></i>3. Lokasi Rak Buku
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="ddc-tab" data-bs-toggle="tab" data-bs-target="#ddc-pane" type="button" role="tab" aria-controls="ddc-pane" aria-selected="false">
                        <i class="bi bi-diagram-3 me-2 text-info"></i>4. Klasifikasi DDC
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="usulan-tab" data-bs-toggle="tab" data-bs-target="#usulan-pane" type="button" role="tab" aria-controls="usulan-pane" aria-selected="false">
                        <i class="bi bi-lightbulb me-2 text-danger"></i>5. Usulan Pengadaan Buku
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="serial-tab" data-bs-toggle="tab" data-bs-target="#serial-pane" type="button" role="tab" aria-controls="serial-pane" aria-selected="false">
                        <i class="bi bi-newspaper me-2" style="color: #7c3aed;"></i>6. Serial & Terbitan Berkala
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Tab Contents -->
<div class="tab-content" id="katalogTabsContent">

    <!-- Tab 1: Katalog Buku -->
    <div class="tab-pane fade show active" id="katalog-pane" role="tabpanel" aria-labelledby="katalog-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-stars text-primary me-2"></i> Daftar Koleksi Buku & E-Book</h5>
                <div class="d-flex gap-2">
                    <a href="/SINTA-SaaS/perpustakaan/katalog/export-excel<?= !empty($data['active_tenant_id']) ? '?tenant_id=' . urlencode($data['active_tenant_id']) : '' ?>" class="btn btn-success btn-sm rounded-3 px-3 fs-7" title="Download Excel (.xlsx)">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel (.xlsx)
                    </a>
                    <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fs-7" id="btnTambahBukuModal" data-bs-toggle="modal" data-bs-target="#modalTambahBuku">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Judul
                    </button>
                </div>
            </div>
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
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i> Belum ada koleksi buku terdaftar.
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
                                    <td>
                                        <?php 
                                        $pengarangShow = $item['pengarang'] ?? ($item['penulis'] ?? '-');
                                        if (is_string($pengarangShow) && strpos($pengarangShow, '[') === 0) {
                                            $dec = json_decode($pengarangShow, true);
                                            if (is_array($dec)) {
                                                $pengarangShow = implode(', ', $dec);
                                            }
                                        }
                                        ?>
                                        <small class="d-block text-dark"><?= htmlspecialchars($pengarangShow, ENT_QUOTES, 'UTF-8') ?></small>
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
                                                data-pengarang="<?= htmlspecialchars($pengarangShow, ENT_QUOTES, 'UTF-8') ?>"
                                                data-penerbit="<?= htmlspecialchars($item['penerbit'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-tahun="<?= htmlspecialchars((string)($item['tahun_terbit'] ?? date('Y')), ENT_QUOTES, 'UTF-8') ?>"
                                                data-isbn="<?= htmlspecialchars($item['isbn'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-ebook="<?= !empty($item['is_ebook']) ? '1' : '0' ?>"
                                                data-tenant="<?= htmlspecialchars($item['tenant_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                title="Edit Katalog Buku">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="/SINTA-SaaS/perpustakaan/cetak-label-thermal?barcode=<?= urlencode($item['isbn'] ?? 'BOOK-1') ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-2 me-1" title="Cetak Barcode Thermal">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if (!empty($item['is_ebook'])): ?>
                                            <a href="/SINTA-SaaS/perpustakaan/baca-ebook" class="btn btn-outline-primary btn-sm rounded-2" title="Baca E-Book Watermark">
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
    </div>

    <!-- Tab 2: Stock Opname / Audit -->
    <div class="tab-pane fade" id="opname-pane" role="tabpanel" aria-labelledby="opname-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-qr-code-scan text-success me-2"></i> Audit Stock Opname Inventaris</h5>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fs-7" data-bs-toggle="modal" data-bs-target="#modalOpnameBaru">
                    <i class="bi bi-plus-circle me-1"></i> Mulai Sesi Opname
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Sekolah / Tenant</th>
                            <th>Judul Sesi Opname</th>
                            <th>Tanggal Pelaksanaan</th>
                            <th>Petugas Pustakawan</th>
                            <th>Total Buku Di-Scan</th>
                            <th>Buku Hilang / Selisih</th>
                            <th>Status Audit</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['opname_list'])): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-qr-code-scan fs-3 d-block mb-2 text-success"></i> Belum ada sesi stock opname aktif.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['opname_list'] as $idx => $op): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($op['tenant_name'] ?? 'Sekolah Aktif') ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($op['nama_sesi'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= htmlspecialchars($op['tanggal'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($op['petugas'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int)$op['total_scanned'] ?> Eksemplar</td>
                                    <td><span class="badge bg-danger"><?= (int)$op['total_selisih'] ?> Hilang</span></td>
                                    <td><span class="badge bg-success">Selesai</span></td>
                                    <td class="text-center">
                                        <a href="/SINTA-SaaS/perpustakaan/cetak-laporan-ddc" class="btn btn-outline-primary btn-sm rounded-2">
                                            <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan Audit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Lokasi Rak Buku -->
    <div class="tab-pane fade" id="rak-pane" role="tabpanel" aria-labelledby="rak-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="row">
                <!-- Form Tambah Rak (Left: 4-cols) -->
                <div class="col-12 col-md-4 mb-4 mb-md-0 border-end">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-plus-circle-fill text-warning me-2"></i> Tambah Lokasi Rak Baru</h6>
                    <form action="/SINTA-SaaS/api/v1/perpustakaan/rak" method="POST" id="formSaveRak" data-turbo="false">
                        <input type="hidden" name="id" id="rak_id" value="">
                        <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-7">Kode Rak <span class="text-danger">*</span></label>
                            <input type="text" name="kode" id="rak_kode" class="form-control rounded-3" placeholder="Contoh: R-01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-7">Nama / Deskripsi Rak <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="rak_nama" class="form-control rounded-3" placeholder="Contoh: Rak Buku IPA" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-7">Kapasitas Maksimal (Buku)</label>
                            <input type="number" name="kapasitas" id="rak_kapasitas" class="form-control rounded-3" value="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-7">Lokasi Ruangan</label>
                            <input type="text" name="ruangan" id="rak_ruangan" class="form-control rounded-3" placeholder="Contoh: Ruang Utama Lantai 1">
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm rounded-3 w-100 py-2 fw-semibold"><i class="bi bi-save me-1"></i> Simpan Data Rak</button>
                    </form>
                </div>
                <!-- Tabel Rak (Right: 8-cols) -->
                <div class="col-12 col-md-8 ps-md-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-table text-warning me-2"></i> Daftar Tata Letak Rak Fisik</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Kode Rak</th>
                                    <th>Nama Rak</th>
                                    <th>Ruangan</th>
                                    <th>Kapasitas</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['rak_list'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2 text-warning"></i> Belum ada lokasi rak terdaftar.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data['rak_list'] as $idx => $r): ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td><span class="badge bg-warning-subtle text-warning border fw-bold"><?= htmlspecialchars($r['kode'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td><strong><?= htmlspecialchars($r['nama'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td><?= htmlspecialchars($r['ruangan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= (int)($r['kapasitas'] ?? 50) ?> Buku</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-warning btn-sm btn-edit-rak" 
                                                    data-id="<?= htmlspecialchars($r['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-kode="<?= htmlspecialchars($r['kode'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nama="<?= htmlspecialchars($r['nama'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-kapasitas="<?= (int)($r['kapasitas'] ?? 50) ?>"
                                                    data-ruangan="<?= htmlspecialchars($r['ruangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="bi bi-pencil"></i>
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
    </div>

    <!-- Tab 4: Klasifikasi DDC -->
    <div class="tab-pane fade" id="ddc-pane" role="tabpanel" aria-labelledby="ddc-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-diagram-3 text-info me-2"></i> Indeks Dewey Decimal Classification (DDC)</h5>
                <input type="text" id="searchDdcInput" class="form-control form-control-sm rounded-3 w-25" placeholder="Cari Kode atau Nama Kategori...">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="ddcTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%;">Kode DDC</th>
                            <th>Kategori Utama / Subjek Klasifikasi</th>
                            <th>Tingkat Hierarki</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['ddc_categories'])): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="bi bi-diagram-3 fs-3 d-block mb-2 text-info"></i> Klasifikasi DDC kosong.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['ddc_categories'] as $c): ?>
                                <tr>
                                    <td><span class="badge bg-info-subtle text-info border fw-bold"><?= htmlspecialchars($c['kode'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><strong><?= htmlspecialchars($c['nama'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td>Tingkat <?= (int)($c['tingkat'] ?? 1) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 5: Usulan Pengadaan Buku -->
    <div class="tab-pane fade" id="usulan-pane" role="tabpanel" aria-labelledby="usulan-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-lightbulb text-danger me-2"></i> Rekomendasi Usulan Buku Baru (Siswa & Guru)</h5>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fs-7" data-bs-toggle="modal" data-bs-target="#modalTambahUsulan">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Usulan
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Judul Buku Usulan</th>
                            <th>Pengarang / Penerbit</th>
                            <th>Nama Pengusul</th>
                            <th>Tanggal Usulan</th>
                            <th>Status Pengadaan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['usulan_list'])): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-lightbulb fs-3 d-block mb-2 text-danger"></i> Belum ada usulan buku diajukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['usulan_list'] as $idx => $us): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($us['judul'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= htmlspecialchars($us['pengarang'] ?? '-', ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($us['penerbit'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($us['pengusul_nama'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($us['tanggal_usulan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($us['status'] === 'Diajukan'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass me-1"></i> Diajukan</span>
                                        <?php elseif ($us['status'] === 'Disetujui'): ?>
                                            <span class="badge bg-info"><i class="bi bi-check-circle me-1"></i> Disetujui</span>
                                        <?php elseif ($us['status'] === 'Ditolak'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Ditolak</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="bi bi-bag-check me-1"></i> Sudah Dibeli</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-warning btn-sm rounded-2 me-1 btn-edit-usulan"
                                                data-id="<?= htmlspecialchars($us['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-judul="<?= htmlspecialchars($us['judul'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-pengarang="<?= htmlspecialchars($us['pengarang'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-penerbit="<?= htmlspecialchars($us['penerbit'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-pengusul="<?= htmlspecialchars($us['pengusul_nama'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-tanggal="<?= htmlspecialchars($us['tanggal_usulan'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= htmlspecialchars($us['status'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-2 btn-delete-usulan" data-id="<?= htmlspecialchars($us['id'], ENT_QUOTES, 'UTF-8') ?>">
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

    <!-- Tab 6: Serial & Berkala -->
    <div class="tab-pane fade" id="serial-pane" role="tabpanel" aria-labelledby="serial-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-newspaper text-primary me-2"></i> Daftar Surat Kabar, Majalah, & Terbitan Berkala</h5>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fs-7" id="btnTambahSerialModal" data-bs-toggle="modal" data-bs-target="#modalTambahSerial">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Media Berkala
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="serialTable">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Media</th>
                            <th>Jenis</th>
                            <th>Frekuensi Terbit</th>
                            <th>ISSN</th>
                            <th>Tanggal Mulai Langganan</th>
                            <th>Status Keaktifan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['serial_list'])): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-newspaper fs-3 d-block mb-2 text-secondary"></i> Belum ada data koran/majalah terdaftar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['serial_list'] as $idx => $sr): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($sr['nama_media'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sr['jenis'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($sr['frekuensi'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($sr['issn'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($sr['tanggal_berlangganan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($sr['status_aktif']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i> Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-x-circle me-1"></i> Non-Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-warning btn-sm rounded-2 me-1 btn-edit-serial"
                                                data-id="<?= htmlspecialchars($sr['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-nama="<?= htmlspecialchars($sr['nama_media'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-jenis="<?= htmlspecialchars($sr['jenis'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-frekuensi="<?= htmlspecialchars($sr['frekuensi'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-issn="<?= htmlspecialchars($sr['issn'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-tanggal="<?= htmlspecialchars($sr['tanggal_berlangganan'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= htmlspecialchars($sr['status_aktif'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-2 btn-delete-serial" data-id="<?= htmlspecialchars($sr['id'], ENT_QUOTES, 'UTF-8') ?>">
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
                                <input type="text" name="penerbit" id="book_penerbit_input" class="form-control rounded-start-3" placeholder="Penerbit">
                                <input type="number" name="tahun_terbit" id="book_tahun_input" class="form-control rounded-end-3" placeholder="Tahun" value="<?= date('Y') ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">ISBN</label>
                            <input type="text" name="isbn" id="book_isbn_input" class="form-control rounded-3" placeholder="978-...">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Status Media Buku</label>
                            <select name="is_ebook" id="book_ebook_select" class="form-select rounded-3">
                                <option value="0">Buku Fisik Saja</option>
                                <option value="1">E-Book Digital</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Simpan Katalog</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Mulai Sesi Opname Baru -->
<div class="modal fade" id="modalOpnameBaru" tabindex="-1" aria-labelledby="modalOpnameBaruLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalOpnameBaruLabel"><i class="bi bi-qr-code-scan me-2"></i> Buat Sesi Audit Stock Opname</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/perpustakaan/opname" method="POST" data-turbo="false">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="modal-body p-4">
                    <?php if ($data['is_super_admin'] ?? false): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Sekolah / Tenant <span class="text-danger">*</span></label>
                            <select name="tenant_id" class="form-select rounded-3 bg-light border-primary" required>
                                <?php foreach ($data['tenants'] as $t): ?>
                                    <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === ($data['active_tenant_id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['nama_sekolah']) ?> (<?= htmlspecialchars($t['npsn']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Sesi Audit Opname <span class="text-danger">*</span></label>
                        <input type="text" name="nama_sesi" class="form-control rounded-3" value="Stock Opname Semester <?= date('Y') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Petugas Pustakawan</label>
                        <input type="text" name="petugas" class="form-control rounded-3" value="Tim Pustakawan Utama">
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-play-fill me-1"></i> Mulai Sesi Audit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Usulan Pengadaan Buku -->
<div class="modal fade" id="modalTambahUsulan" tabindex="-1" aria-labelledby="modalTambahUsulanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalTambahUsulanLabel"><i class="bi bi-lightbulb me-2"></i> Usulan Pengadaan Buku Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/api/v1/perpustakaan/usulan" method="POST" id="formSaveUsulan" data-turbo="false">
                <input type="hidden" name="id" id="usulan_id" value="">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Buku <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="usulan_judul" class="form-control rounded-3" required placeholder="Contoh: Kamus Fisika Modern">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pengarang</label>
                        <input type="text" name="pengarang" id="usulan_pengarang" class="form-control rounded-3" placeholder="Contoh: Albert Einstein">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Penerbit</label>
                        <input type="text" name="penerbit" id="usulan_penerbit" class="form-control rounded-3" placeholder="Contoh: Erlangga">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Pengusul</label>
                        <input type="text" name="pengusul_nama" id="usulan_pengusul" class="form-control rounded-3" value="Pustakawan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Usulan</label>
                        <select name="status" id="usulan_status" class="form-select rounded-3">
                            <option value="Diajukan">Diajukan</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Ditolak">Ditolak</option>
                            <option value="Sudah Dibeli">Sudah Dibeli</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Simpan Usulan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Serial Berkala -->
<div class="modal fade" id="modalTambahSerial" tabindex="-1" aria-labelledby="modalTambahSerialLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalTambahSerialLabel"><i class="bi bi-newspaper me-2"></i> Tambah Media Berkala</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/api/v1/perpustakaan/serial" method="POST" id="formSaveSerial" data-turbo="false">
                <input type="hidden" name="id" id="serial_id" value="">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Media / Publikasi <span class="text-danger">*</span></label>
                        <input type="text" name="nama_media" id="serial_nama" class="form-control rounded-3" required placeholder="Contoh: Jawa Pos, Gatra, Jurnal Ilmiah">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Jenis Media</label>
                            <select name="jenis" id="serial_jenis" class="form-select rounded-3">
                                <option value="Surat Kabar">Surat Kabar (Koran)</option>
                                <option value="Majalah">Majalah</option>
                                <option value="Jurnal">Jurnal Ilmiah</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Frekuensi Terbit</label>
                            <input type="text" name="frekuensi" id="serial_frekuensi" class="form-control rounded-3" value="Harian" required placeholder="Contoh: Harian, Mingguan">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">ISSN / Reg ID</label>
                            <input type="text" name="issn" id="serial_issn" class="form-control rounded-3" placeholder="Contoh: 0124-5678">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tanggal Berlangganan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_berlangganan" id="serial_tanggal" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Langganan</label>
                        <select name="status_aktif" id="serial_status" class="form-select rounded-3">
                            <option value="1">Aktif Berlangganan</option>
                            <option value="0">Berhenti</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Simpan Media</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Sleek Scrollable Underline Nav Tabs */
.nav-tabs-wrapper {
    position: relative;
    width: 100%;
}
.scrollable-nav-tabs {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.scrollable-nav-tabs::-webkit-scrollbar {
    height: 4px;
}
.scrollable-nav-tabs::-webkit-scrollbar-track {
    background: transparent;
}
.scrollable-nav-tabs::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.scrollable-nav-tabs .nav-link {
    color: #64748b;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    transition: all 0.2s ease;
}
.scrollable-nav-tabs .nav-link:hover {
    color: #3b82f6;
    border-bottom-color: #93c5fd;
}
.scrollable-nav-tabs .nav-link.active {
    color: #2563eb;
    font-weight: 700 !important;
    border-bottom: 3px solid #2563eb;
    background: transparent !important;
}
</style>

<script>
document.addEventListener('turbo:load', function() {
    initKatalogHandlers();
});

document.addEventListener('DOMContentLoaded', function() {
    initKatalogHandlers();
});

function initKatalogHandlers() {
    // 1. Edit Katalog Handler
    const editKatalogBtns = document.querySelectorAll('.btn-edit-katalog');
    editKatalogBtns.forEach(btn => {
        btn.onclick = function() {
            document.getElementById('modalTambahBukuLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Katalog Buku';
            document.getElementById('book_id_input').value = this.dataset.id;
            document.getElementById('book_judul_input').value = this.dataset.judul;
            document.getElementById('book_ddc_input').value = this.dataset.ddc;
            document.getElementById('book_pengarang_input').value = this.dataset.pengarang;
            document.getElementById('book_penerbit_input').value = this.dataset.penerbit;
            document.getElementById('book_tahun_input').value = this.dataset.tahun;
            document.getElementById('book_isbn_input').value = this.dataset.isbn;
            document.getElementById('book_ebook_select').value = this.dataset.ebook;

            const modal = new bootstrap.Modal(document.getElementById('modalTambahBuku'));
            modal.show();
        };
    });

    // Reset modal on add
    const addKatalogBtn = document.getElementById('btnTambahBukuModal');
    if (addKatalogBtn) {
        addKatalogBtn.onclick = function() {
            document.getElementById('modalTambahBukuLabel').innerHTML = '<i class="bi bi-book me-2"></i> Tambah Judul Buku Baru';
            document.getElementById('book_id_input').value = '';
            document.getElementById('formTambahBuku').reset();
        };
    }

    // 2. Edit Rak Handler
    const editRakBtns = document.querySelectorAll('.btn-edit-rak');
    editRakBtns.forEach(btn => {
        btn.onclick = function() {
            document.getElementById('rak_id').value = this.dataset.id;
            document.getElementById('rak_kode').value = this.dataset.kode;
            document.getElementById('rak_nama').value = this.dataset.nama;
            document.getElementById('rak_kapasitas').value = this.dataset.kapasitas;
            document.getElementById('rak_ruangan').value = this.dataset.ruangan;
        };
    });

    // 3. Edit Usulan Handler
    const editUsulanBtns = document.querySelectorAll('.btn-edit-usulan');
    editUsulanBtns.forEach(btn => {
        btn.onclick = function() {
            document.getElementById('modalTambahUsulanLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Usulan Pengadaan';
            document.getElementById('usulan_id').value = this.dataset.id;
            document.getElementById('usulan_judul').value = this.dataset.judul;
            document.getElementById('usulan_pengarang').value = this.dataset.pengarang;
            document.getElementById('usulan_penerbit').value = this.dataset.penerbit;
            document.getElementById('usulan_pengusul').value = this.dataset.pengusul;
            document.getElementById('usulan_status').value = this.dataset.status;

            const modal = new bootstrap.Modal(document.getElementById('modalTambahUsulan'));
            modal.show();
        };
    });

    // 4. Delete Usulan Handler
    const deleteUsulanBtns = document.querySelectorAll('.btn-delete-usulan');
    deleteUsulanBtns.forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            if (confirm('Apakah Anda yakin ingin menghapus usulan pengadaan buku ini?')) {
                fetch('/SINTA-SaaS/api/v1/perpustakaan/usulan?id=' + encodeURIComponent(id), {
                    method: 'DELETE'
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('Usulan berhasil dihapus.');
                        window.location.reload();
                    } else {
                        alert('Gagal menghapus usulan: ' + (res.error || 'Unknown error'));
                    }
                })
                .catch(err => console.error(err));
            }
        };
    });

    // 6. Serial & Berkala Form Submit Handler (Fetch)
    const formSaveSerial = document.getElementById('formSaveSerial');
    if (formSaveSerial) {
        formSaveSerial.onsubmit = function(e) {
            e.preventDefault();
            const payload = {
                id: document.getElementById('serial_id').value,
                nama_media: document.getElementById('serial_nama').value,
                jenis: document.getElementById('serial_jenis').value,
                frekuensi: document.getElementById('serial_frekuensi').value,
                issn: document.getElementById('serial_issn').value,
                tanggal_berlangganan: document.getElementById('serial_tanggal').value,
                status_aktif: document.getElementById('serial_status').value
            };

            fetch('/SINTA-SaaS/api/v1/perpustakaan/serial', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message || 'Data media berkala berhasil disimpan.');
                    window.location.reload();
                } else {
                    alert('Gagal menyimpan data: ' + (res.error || 'Unknown error'));
                }
            })
            .catch(err => console.error(err));
        };
    }

    // 7. Reset Serial form on Add click
    const addSerialBtn = document.getElementById('btnTambahSerialModal');
    if (addSerialBtn) {
        addSerialBtn.onclick = function() {
            document.getElementById('modalTambahSerialLabel').innerHTML = '<i class="bi bi-newspaper me-2"></i> Tambah Media Berkala';
            document.getElementById('serial_id').value = '';
            if (formSaveSerial) formSaveSerial.reset();
        };
    }

    // 8. Edit Serial Button Handler
    const editSerialBtns = document.querySelectorAll('.btn-edit-serial');
    editSerialBtns.forEach(btn => {
        btn.onclick = function() {
            document.getElementById('modalTambahSerialLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Media Berkala';
            document.getElementById('serial_id').value = this.dataset.id;
            document.getElementById('serial_nama').value = this.dataset.nama;
            document.getElementById('serial_jenis').value = this.dataset.jenis;
            document.getElementById('serial_frekuensi').value = this.dataset.frekuensi;
            document.getElementById('serial_issn').value = this.dataset.issn;
            document.getElementById('serial_tanggal').value = this.dataset.tanggal;
            document.getElementById('serial_status').value = this.dataset.status;

            const modal = new bootstrap.Modal(document.getElementById('modalTambahSerial'));
            modal.show();
        };
    });

    // 9. Delete Serial Button Handler
    const deleteSerialBtns = document.querySelectorAll('.btn-delete-serial');
    deleteSerialBtns.forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            if (confirm('Apakah Anda yakin ingin menghapus media berkala ini?')) {
                fetch('/SINTA-SaaS/api/v1/perpustakaan/serial?id=' + encodeURIComponent(id), {
                    method: 'DELETE'
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('Media berkala berhasil dihapus.');
                        window.location.reload();
                    } else {
                        alert('Gagal menghapus: ' + (res.error || 'Unknown error'));
                    }
                })
                .catch(err => console.error(err));
            }
        };
    });

    // 10. Search DDC Client Side Filter
    const searchDdc = document.getElementById('searchDdcInput');
    if (searchDdc) {
        searchDdc.oninput = function() {
            const val = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ddcTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(val) ? '' : 'none';
            });
        };
    }
}
</script>
