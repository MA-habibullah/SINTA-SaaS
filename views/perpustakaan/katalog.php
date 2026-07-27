<?php
/**
 * View: Katalog & Inventori Terpadu
 * @phpstan-var array<string, mixed> $data
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
                                        <button type="button" class="btn btn-outline-info btn-sm rounded-2 me-1 btn-audit-katalog"
                                                data-id="<?= htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-judul="<?= htmlspecialchars($item['judul'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                title="Audit & Tracking Unit Eksemplar">
                                            <i class="bi bi-search me-1"></i> Audit
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm rounded-2 me-1 btn-edit-katalog"
                                                 data-id="<?= htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-judul="<?= htmlspecialchars($item['judul'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-judul_seri="<?= htmlspecialchars($item['judul_seri'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-ddc="<?= htmlspecialchars($item['klasifikasi_ddc'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-panggil="<?= htmlspecialchars($item['nomor_panggil'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-pengarang="<?= htmlspecialchars($pengarangShow, ENT_QUOTES, 'UTF-8') ?>"
                                                 data-penerbit="<?= htmlspecialchars($item['penerbit'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-kota="<?= htmlspecialchars($item['kota_terbit'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-tahun="<?= htmlspecialchars((string)($item['tahun_terbit'] ?? date('Y')), ENT_QUOTES, 'UTF-8') ?>"
                                                 data-edisi="<?= htmlspecialchars($item['edisi'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-halaman="<?= htmlspecialchars((string)($item['halaman'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                 data-dimensi="<?= htmlspecialchars($item['dimensi'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-bahasa="<?= htmlspecialchars($item['bahasa'] ?? 'Indonesia', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-isbn="<?= htmlspecialchars($item['isbn'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-jenis="<?= htmlspecialchars($item['jenis_buku'] ?? 'Umum', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-subjek="<?= htmlspecialchars(is_array($item['subjek'] ?? null) ? implode('; ', $item['subjek']) : (is_string($item['subjek'] ?? null) && strpos($item['subjek'], '[') === 0 ? implode('; ', json_decode($item['subjek'], true) ?: [$item['subjek']]) : ($item['subjek'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                 data-abstrak="<?= htmlspecialchars($item['abstrak'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-opac="<?= htmlspecialchars((string)($item['status_opac'] ?? 1), ENT_QUOTES, 'UTF-8') ?>"
                                                 data-ebook="<?= !empty($item['is_ebook']) ? (string)$item['is_ebook'] : '0' ?>"
                                                 data-cover="<?= htmlspecialchars($item['cover'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-ebook_file="<?= htmlspecialchars($item['file_ebook'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 data-tenant="<?= htmlspecialchars($item['tenant_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                 title="Edit Katalog Buku Lengkap">
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalTambahBukuLabel"><i class="bi bi-book me-2"></i> Tambah Judul Buku Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/api/v1/perpustakaan/katalog/simpan" method="POST" id="formTambahBuku" enctype="multipart/form-data" data-turbo="false">
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

                        <!-- Judul Buku & Judul Seri -->
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold">Judul Buku <span class="text-danger">*</span></label>
                            <input type="text" name="judul" id="book_judul_input" class="form-control rounded-3" placeholder="Contoh: Matematika Diskrit SMA" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Judul Seri / Jilid</label>
                            <input type="text" name="judul_seri" id="book_seri_input" class="form-control rounded-3" placeholder="Contoh: Jilid 2A / Seri Sains">
                        </div>

                        <!-- Nomor Panggil & DDC Dropdown -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nomor Panggil (Call Number)</label>
                            <input type="text" name="nomor_panggil" id="book_panggil_input" class="form-control rounded-3" placeholder="Contoh: 510 SUR m">
                            <small class="text-muted fs-8">Format standar: [Kode DDC] [3 Huruf Pengarang] [1 Huruf Judul]</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Klasifikasi DDC</label>
                            <div class="position-relative">
                                <input type="text" id="book_ddc_search" class="form-control rounded-3 rounded-bottom-0" placeholder="🔍 Cari kode / nama DDC..." autocomplete="off">
                                <select name="klasifikasi_ddc" id="book_ddc_input" class="form-select rounded-3 rounded-top-0" size="4" style="max-height:120px; overflow-y:auto; border-top: 1px solid #dee2e6;">
                                    <option value="">-- Pilih Klasifikasi DDC --</option>
                                    <?php foreach ($data['ddc_categories'] as $ddc): ?>
                                        <option value="<?= htmlspecialchars($ddc['kode']) ?>" data-search="<?= htmlspecialchars(strtolower($ddc['kode'] . ' ' . $ddc['nama'])) ?>">
                                            <?= htmlspecialchars($ddc['kode']) ?> — <?= htmlspecialchars($ddc['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="book_ddc_selected_badge" class="mt-1 d-none">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 fs-8">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    <span id="book_ddc_selected_label">-</span>
                                    <button type="button" class="btn-close btn-close-sm ms-1 py-0" id="book_ddc_clear" style="font-size:0.55rem;" aria-label="Hapus"></button>
                                </span>
                            </div>
                        </div>

                        <!-- Pengarang & Edisi -->
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold">Nama Pengarang / Penulis</label>
                            <input type="text" name="pengarang" id="book_pengarang_input" class="form-control rounded-3" placeholder="Contoh: Prof. Yohanes Surya">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Edisi / Cetakan</label>
                            <input type="text" name="edisi" id="book_edisi_input" class="form-control rounded-3" placeholder="Contoh: Edisi Revisi 2024">
                        </div>

                        <!-- Penerbit, Kota, & Tahun -->
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-semibold">Penerbit</label>
                            <input type="text" name="penerbit" id="book_penerbit_input" class="form-control rounded-3" placeholder="Penerbit">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Kota Terbit</label>
                            <input type="text" name="kota_terbit" id="book_kota_input" class="form-control rounded-3" placeholder="Contoh: Jakarta">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Tahun Terbit</label>
                            <input type="number" name="tahun_terbit" id="book_tahun_input" class="form-control rounded-3" placeholder="Tahun" value="<?= date('Y') ?>">
                        </div>

                        <!-- ISBN & Bahasa -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">ISBN / ISSN</label>
                            <input type="text" name="isbn" id="book_isbn_input" class="form-control rounded-3" placeholder="978-...">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Bahasa Buku</label>
                            <select name="bahasa" id="book_bahasa_input" class="form-select rounded-3">
                                <option value="Indonesia">Bahasa Indonesia</option>
                                <option value="Inggris">Bahasa Inggris</option>
                                <option value="Arab">Bahasa Arab</option>
                                <option value="Jawa">Bahasa Jawa / Daerah</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <!-- Halaman & Dimensi -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Jumlah Halaman</label>
                            <input type="number" name="halaman" id="book_halaman_input" class="form-control rounded-3" placeholder="Contoh: 254">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Dimensi / Ukuran Buku</label>
                            <input type="text" name="dimensi" id="book_dimensi_input" class="form-control rounded-3" placeholder="Contoh: 21 cm / 21 x 29 cm">
                        </div>

                        <!-- Jenis Buku & Status OPAC Publik -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Jenis Buku</label>
                            <select name="jenis_buku" id="book_jenis_input" class="form-select rounded-3">
                                <option value="Umum">Umum / Pengayaan</option>
                                <option value="Non-Fiksi">Non-Fiksi</option>
                                <option value="Fiksi">Fiksi / Sastra</option>
                                <option value="Paket Pelajaran">Paket Pelajaran (Buku Teks)</option>
                                <option value="Referensi">Referensi (Kamus, Ensiklopedi)</option>
                                <option value="OSN">OSN / Olimpiade</option>
                                <option value="Majalah">Majalah / Berkala</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Status Publikasi OPAC</label>
                            <select name="status_opac" id="book_opac_input" class="form-select rounded-3">
                                <option value="1">🌐 Tampilkan di Katalog OPAC Publik</option>
                                <option value="0">🔒 Sembunyikan dari OPAC (Khusus Internal)</option>
                            </select>
                        </div>

                        <!-- Subjek / Topik / Kata Kunci -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Subjek / Kata Kunci Topik</label>
                            <input type="text" name="subjek" id="book_subjek_input" class="form-control rounded-3" placeholder="Contoh: Matematika; Aljabar; SMA (Pisahkan dengan titik koma)">
                        </div>

                        <!-- Abstrak / Sinopsis Buku -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Abstrak / Ringkasan Sinopsis Buku</label>
                            <textarea name="abstrak" id="book_abstrak_input" class="form-control rounded-3" rows="2" placeholder="Tuliskan gambaran ringkas isi buku..."></textarea>
                        </div>

                        <!-- Status Media Buku -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Status Media Buku</label>
                            <select name="is_ebook" id="book_ebook_select" class="form-select rounded-3">
                                <option value="0">📚 Buku Fisik Saja</option>
                                <option value="1">💻 E-Book Digital</option>
                                <option value="2">📚💻 Fisik + E-Book Digital</option>
                            </select>
                        </div>

                        <!-- Upload Cover Buku (selalu tampil) -->
                        <div class="col-12 col-md-6" id="sectionUploadCover">
                            <label class="form-label fw-semibold">Cover Buku <span class="text-muted fw-normal">(JPG/PNG, maks 2MB)</span></label>
                            <input type="file" name="cover_file" id="book_cover_file" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp">
                            <div id="cover_preview_wrap" class="mt-2 d-none">
                                <img id="cover_preview_img" src="" alt="Preview Cover" class="rounded-3 border" style="max-height:100px; max-width:80px; object-fit:cover;">
                            </div>
                            <div id="cover_existing_wrap" class="mt-2 d-none">
                                <small class="text-muted"><i class="bi bi-image me-1"></i>Cover saat ini:</small><br>
                                <img id="cover_existing_img" src="" alt="Cover saat ini" class="rounded-3 border mt-1" style="max-height:80px; max-width:64px; object-fit:cover;">
                            </div>
                        </div>
                                <small class="text-muted"><i class="bi bi-image me-1"></i>Cover saat ini:</small><br>
                                <img id="cover_existing_img" src="" alt="Cover saat ini" class="rounded-3 border mt-1" style="max-height:80px; max-width:64px; object-fit:cover;">
                            </div>
                        </div>

                        <!-- Section Upload E-Book (muncul hanya jika E-Book dipilih) -->
                        <div class="col-12" id="sectionEbookUpload" style="display:none;">
                            <div class="card border-0 bg-info-subtle rounded-4 p-3">
                                <h6 class="fw-bold text-info mb-3"><i class="bi bi-file-earmark-pdf me-2"></i>Upload File E-Book Digital</h6>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold mb-1">File E-Book <span class="text-danger">*</span> <span class="text-muted fw-normal">(PDF/EPUB, maks 50MB)</span></label>
                                    <input type="file" name="ebook_file" id="book_ebook_file" class="form-control rounded-3" accept=".pdf,.epub">
                                    <div id="ebook_existing_wrap" class="mt-2 d-none">
                                        <small class="text-success"><i class="bi bi-file-earmark-check me-1"></i> File ebook sudah ada. Upload baru untuk mengganti.</small>
                                        <br><small class="text-muted" id="ebook_existing_name"></small>
                                    </div>
                                </div>
                                <small class="text-muted fs-8"><i class="bi bi-shield-check me-1"></i>File hanya dapat diakses oleh anggota terdaftar perpustakaan.</small>
                            </div>
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

<!-- Modal Audit & Tracking Lifecycle Eksemplar -->
<div class="modal fade" id="modalAuditEksemplar" tabindex="-1" aria-labelledby="modalAuditEksemplarLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalAuditEksemplarLabel"><i class="bi bi-shield-check text-info me-2"></i> Audit & Tracking Lifecycle Eksemplar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <!-- Book Summary Bar -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="badge bg-primary-subtle text-primary border me-2">DDC: <span id="audit_book_ddc">-</span></span>
                            <h5 class="fw-bold mb-1 text-dark d-inline" id="audit_book_judul">Loading...</h5>
                            <small class="d-block text-muted mt-1"><i class="bi bi-person me-1"></i>Pengarang: <span id="audit_book_pengarang">-</span> | Penerbit: <span id="audit_book_penerbit">-</span> | ISBN: <span id="audit_book_isbn">-</span></small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary rounded-3 btn-sm" id="btnTambahEksemplarModal">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Unit Eksemplar Baru
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Audit Summary KPI Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 p-3 text-center bg-white">
                            <small class="text-muted fw-semibold d-block mb-1">Total Unit</small>
                            <h4 class="fw-bold mb-0 text-dark" id="audit_stat_total">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 p-3 text-center bg-white">
                            <small class="text-muted fw-semibold d-block mb-1">Tersedia di Rak</small>
                            <h4 class="fw-bold mb-0 text-success" id="audit_stat_tersedia">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 p-3 text-center bg-white">
                            <small class="text-muted fw-semibold d-block mb-1">Dipinjam</small>
                            <h4 class="fw-bold mb-0 text-warning" id="audit_stat_dipinjam">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 p-3 text-center bg-white">
                            <small class="text-muted fw-semibold d-block mb-1">Kondisi Rusak</small>
                            <h4 class="fw-bold mb-0 text-danger" id="audit_stat_rusak">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 p-3 text-center bg-white">
                            <small class="text-muted fw-semibold d-block mb-1">Afkir / Dibuang</small>
                            <h4 class="fw-bold mb-0 text-secondary" id="audit_stat_afkir">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 p-3 text-center bg-white">
                            <small class="text-muted fw-semibold d-block mb-1">Total Investasi</small>
                            <h6 class="fw-bold mb-0 text-primary" id="audit_stat_investasi">Rp 0</h6>
                        </div>
                    </div>
                </div>

                <!-- Table Tracking Eksemplar -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-columns-reverse me-2"></i>Rincian Unit Eksemplar & Tracking Lifecycle</h6>
                        <span class="badge bg-light text-dark border fs-8" id="audit_breakdown_sumber">BOS: 0 | BPOPP: 0 | Sumbangan: 0</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-8" id="tableAuditEksemplar">
                            <thead class="table-light">
                                <tr>
                                    <th>Barcode / No. Induk</th>
                                    <th>Perolehan & Sumber Dana</th>
                                    <th>Lokasi Fisik (Rak/Gudang)</th>
                                    <th>Status Peminjam Aktif</th>
                                    <th>Terakhir Dipinjam / Dibaca</th>
                                    <th>Kondisi & Status Audit</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="audit_eksemplar_tbody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>Memuat data audit eksemplar...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit / Tambah Unit Eksemplar -->
<div class="modal fade" id="modalEditEksemplar" tabindex="-1" aria-labelledby="modalEditEksemplarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalEditEksemplarLabel"><i class="bi bi-box-seam me-2"></i> Tambah / Edit Unit Eksemplar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/api/v1/perpustakaan/eksemplar/simpan" method="POST" id="formSaveEksemplar" data-turbo="false">
                <input type="hidden" name="id" id="eks_id_input" value="">
                <input type="hidden" name="bibliografi_id" id="eks_bib_id_input" value="">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Kode Barcode <span class="text-danger">*</span></label>
                            <input type="text" name="barcode" id="eks_barcode_input" class="form-control rounded-3" placeholder="Contoh: B-2026-001" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nomor Induk Inventaris</label>
                            <input type="text" name="nomor_induk" id="eks_no_induk_input" class="form-control rounded-3" placeholder="Contoh: IND-0012">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Tanggal Masuk / Beli</label>
                            <input type="date" name="tanggal_masuk" id="eks_tgl_masuk_input" class="form-control rounded-3" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Harga Perolehan (Rp)</label>
                            <input type="number" name="harga_perolehan" id="eks_harga_input" class="form-control rounded-3" placeholder="0">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Sumber Perolehan Dana</label>
                            <select name="sumber_buku" id="eks_sumber_select" class="form-select rounded-3">
                                <option value="Dana BOS">Dana BOS</option>
                                <option value="Dana BPOPP">Dana BPOPP</option>
                                <option value="Sumbangan Siswa">Sumbangan Siswa</option>
                                <option value="Sumbangan Alumni">Sumbangan Alumni</option>
                                <option value="Hibah Pemerintah">Hibah Pemerintah</option>
                                <option value="Hibah Pemda">Hibah Pemda</option>
                                <option value="Pembelian Mandiri">Pembelian Mandiri</option>
                                <option value="Sumbangan Perorangan">Sumbangan Perorangan</option>
                                <option value="Bantuan Lainnya">Bantuan Lainnya</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Sumber Pemberi / Vendor</label>
                            <input type="text" name="sumber_pemberi" id="eks_pemberi_input" class="form-control rounded-3" placeholder="Contoh: PT Gramedia / Alumni 2024">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Lokasi Rak Penempatan</label>
                            <select name="lokasi_rak_id" id="eks_rak_select" class="form-select rounded-3">
                                <option value="">-- Tanpa Rak (Di Gudang) --</option>
                                <?php foreach ($data['rak_list'] as $rak): ?>
                                    <option value="<?= htmlspecialchars($rak['id']) ?>">
                                        <?= htmlspecialchars($rak['nama']) ?> (Rak <?= htmlspecialchars($rak['nama_rak'] ?? $rak['kode']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Kondisi Fisik</label>
                            <select name="kondisi" id="eks_kondisi_select" class="form-select rounded-3">
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                                <option value="Afkir/Dihapuskan">Afkir / Dihapuskan</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Status Eksemplar</label>
                            <select name="status" id="eks_status_select" class="form-select rounded-3">
                                <option value="Tersedia">Tersedia</option>
                                <option value="Dipinjam Reguler">Dipinjam Reguler</option>
                                <option value="Di Gudang">Di Gudang</option>
                                <option value="Diperbaiki">Diperbaiki</option>
                                <option value="Rusak">Rusak</option>
                                <option value="Hilang">Hilang</option>
                                <option value="Dihapuskan/Afkir">Dihapuskan / Afkir</option>
                            </select>
                        </div>

                        <!-- Section Penghapusan / Afkir (Muncul jika status/kondisi Afkir) -->
                        <div class="col-12" id="sectionPenghapusanEksemplar" style="display:none;">
                            <div class="card border-0 bg-danger-subtle rounded-4 p-3">
                                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-trash me-2"></i>Data Audit Penghapusan / Afkir</h6>
                                <div class="row g-2">
                                    <div class="col-12 col-md-5">
                                        <label class="form-label fw-semibold mb-1">Tanggal Penghapusan</label>
                                        <input type="date" name="tanggal_penghapusan" id="eks_tgl_hapus_input" class="form-control rounded-3" value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <label class="form-label fw-semibold mb-1">Alasan Penghapusan / Afkir</label>
                                        <input type="text" name="alasan_penghapusan" id="eks_alasan_hapus_input" class="form-control rounded-3" placeholder="Contoh: Halaman terlepas total / Banjir">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Simpan Unit Eksemplar</button>
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
// Helper: Update DDC selection badge
function updateDdcBadge(val) {
    const badgeWrap = document.getElementById('book_ddc_selected_badge');
    const badgeLabel = document.getElementById('book_ddc_selected_label');
    const ddcSel = document.getElementById('book_ddc_input');
    if (!badgeWrap || !badgeLabel) return;
    if (val && ddcSel) {
        const opt = ddcSel.querySelector('option[value="' + val + '"]');
        const text = opt ? opt.textContent.trim() : val;
        badgeLabel.textContent = text;
        badgeWrap.classList.remove('d-none');
    } else {
        badgeWrap.classList.add('d-none');
    }
}

// Helper: Toggle ebook upload section visibility
function toggleEbookSection(val) {
    const section = document.getElementById('sectionEbookUpload');
    const ebookFileInput = document.getElementById('book_ebook_file');
    if (!section) return;
    const isEbook = (val === '1' || val === '2');
    section.style.display = isEbook ? '' : 'none';
    if (ebookFileInput) {
        ebookFileInput.required = (val === '1'); // wajib hanya jika pure ebook, opsional jika fisik+ebook
    }
}

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
            document.getElementById('modalTambahBukuLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Katalog Buku Lengkap';
            document.getElementById('book_id_input').value = this.dataset.id;
            document.getElementById('book_judul_input').value = this.dataset.judul || '';
            const seriEl = document.getElementById('book_seri_input');
            if (seriEl) seriEl.value = this.dataset.judul_seri || '';
            const panggilEl = document.getElementById('book_panggil_input');
            if (panggilEl) panggilEl.value = this.dataset.panggil || '';
            document.getElementById('book_pengarang_input').value = this.dataset.pengarang || '';
            const edisiEl = document.getElementById('book_edisi_input');
            if (edisiEl) edisiEl.value = this.dataset.edisi || '';
            document.getElementById('book_penerbit_input').value = this.dataset.penerbit || '';
            const kotaEl = document.getElementById('book_kota_input');
            if (kotaEl) kotaEl.value = this.dataset.kota || '';
            document.getElementById('book_tahun_input').value = this.dataset.tahun || new Date().getFullYear();
            const halamanEl = document.getElementById('book_halaman_input');
            if (halamanEl) halamanEl.value = this.dataset.halaman || '';
            const dimensiEl = document.getElementById('book_dimensi_input');
            if (dimensiEl) dimensiEl.value = this.dataset.dimensi || '';
            const bahasaEl = document.getElementById('book_bahasa_input');
            if (bahasaEl) bahasaEl.value = this.dataset.bahasa || 'Indonesia';
            document.getElementById('book_isbn_input').value = this.dataset.isbn || '';

            // Set DDC dropdown nilai
            const ddcSel = document.getElementById('book_ddc_input');
            const ddcVal = this.dataset.ddc || '';
            if (ddcSel) ddcSel.value = ddcVal;
            updateDdcBadge(ddcVal);

            // Set jenis buku
            const jenisSel = document.getElementById('book_jenis_input');
            if (jenisSel) jenisSel.value = this.dataset.jenis || 'Umum';

            // Set subjek & abstrak
            const subjekEl = document.getElementById('book_subjek_input');
            if (subjekEl) subjekEl.value = this.dataset.subjek || '';
            const abstrakEl = document.getElementById('book_abstrak_input');
            if (abstrakEl) abstrakEl.value = this.dataset.abstrak || '';

            // Set status OPAC
            const opacEl = document.getElementById('book_opac_input');
            if (opacEl) opacEl.value = this.dataset.opac || '1';

            // Set ebook select + toggle upload section
            const ebookSel = document.getElementById('book_ebook_select');
            const ebookVal = this.dataset.ebook || '0';
            if (ebookSel) ebookSel.value = ebookVal;
            toggleEbookSection(ebookVal);

            // Tampilkan cover existing jika ada
            const coverPath = this.dataset.cover || '';
            const coverExistWrap = document.getElementById('cover_existing_wrap');
            if (coverPath && coverExistWrap) {
                document.getElementById('cover_existing_img').src = '/SINTA-SaaS/' + coverPath;
                coverExistWrap.classList.remove('d-none');
            } else if (coverExistWrap) {
                coverExistWrap.classList.add('d-none');
            }

            // Tampilkan ebook existing jika ada
            const ebookPath = this.dataset.ebook_file || '';
            const ebookExistWrap = document.getElementById('ebook_existing_wrap');
            if (ebookPath && ebookExistWrap) {
                document.getElementById('ebook_existing_name').textContent = ebookPath.split('/').pop();
                ebookExistWrap.classList.remove('d-none');
            } else if (ebookExistWrap) {
                ebookExistWrap.classList.add('d-none');
            }

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
            // Reset DDC badge
            updateDdcBadge('');
            // Reset ebook section
            toggleEbookSection('0');
            // Reset cover preview
            const prevWrap = document.getElementById('cover_preview_wrap');
            if (prevWrap) prevWrap.classList.add('d-none');
            const existWrap = document.getElementById('cover_existing_wrap');
            if (existWrap) existWrap.classList.add('d-none');
            const ebookExistWrap = document.getElementById('ebook_existing_wrap');
            if (ebookExistWrap) ebookExistWrap.classList.add('d-none');
        };
    }

    // DDC Search Filter (client-side live filter pada <select>)
    const ddcSearch = document.getElementById('book_ddc_search');
    const ddcSelect = document.getElementById('book_ddc_input');
    if (ddcSearch && ddcSelect) {
        ddcSearch.oninput = function() {
            const q = this.value.toLowerCase().trim();
            Array.from(ddcSelect.options).forEach(opt => {
                if (!opt.dataset.search) { opt.style.display = ''; return; } // placeholder
                opt.style.display = (q === '' || opt.dataset.search.includes(q)) ? '' : 'none';
            });
        };
        ddcSelect.onchange = function() {
            updateDdcBadge(this.value);
        };
    }

    // DDC Clear badge button
    const ddcClear = document.getElementById('book_ddc_clear');
    if (ddcClear) {
        ddcClear.onclick = function() {
            if (ddcSelect) { ddcSelect.value = ''; }
            if (ddcSearch) { ddcSearch.value = ''; ddcSearch.dispatchEvent(new Event('input')); }
            updateDdcBadge('');
        };
    }

    // E-Book section toggle
    const ebookSelectEl = document.getElementById('book_ebook_select');
    if (ebookSelectEl) {
        ebookSelectEl.onchange = function() { toggleEbookSection(this.value); };
    }

    // Cover file preview
    const coverFileInput = document.getElementById('book_cover_file');
    if (coverFileInput) {
        coverFileInput.onchange = function() {
            const file = this.files[0];
            const prevWrap = document.getElementById('cover_preview_wrap');
            const prevImg = document.getElementById('cover_preview_img');
            if (file && prevWrap && prevImg) {
                const reader = new FileReader();
                reader.onload = e => { prevImg.src = e.target.result; prevWrap.classList.remove('d-none'); };
                reader.readAsDataURL(file);
            }
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

    // 11. Audit Lifecycle & Traceability Eksemplar Handler
    let activeAuditBibliografiId = '';
    const auditBtns = document.querySelectorAll('.btn-audit-katalog');
    auditBtns.forEach(btn => {
        btn.onclick = function() {
            activeAuditBibliografiId = this.dataset.id || '';
            const judul = this.dataset.judul || 'Buku';
            document.getElementById('audit_book_judul').textContent = judul;
            document.getElementById('audit_book_pengarang').textContent = '...';
            document.getElementById('audit_book_penerbit').textContent = '...';
            document.getElementById('audit_book_isbn').textContent = '...';
            document.getElementById('audit_book_ddc').textContent = '-';
            document.getElementById('audit_eksemplar_tbody').innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>Memuat data audit eksemplar...</td></tr>';

            const auditModal = new bootstrap.Modal(document.getElementById('modalAuditEksemplar'));
            auditModal.show();

            fetchTraceabilityData(activeAuditBibliografiId);
        };
    });

    function fetchTraceabilityData(bibId) {
        if (!bibId) return;
        fetch('/SINTA-SaaS/api/v1/perpustakaan/katalog/traceability?id=' + encodeURIComponent(bibId))
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    alert('Gagal memuat data audit: ' + (res.error || 'Unknown error'));
                    return;
                }
                const b = res.buku || {};
                const stats = res.stats || {};
                const items = res.items || [];

                document.getElementById('audit_book_judul').textContent = b.judul || '-';
                document.getElementById('audit_book_pengarang').textContent = b.pengarang || b.penulis || '-';
                document.getElementById('audit_book_penerbit').textContent = (b.penerbit || '-') + ' (' + (b.tahun_terbit || '-') + ')';
                document.getElementById('audit_book_isbn').textContent = b.isbn || '-';
                document.getElementById('audit_book_ddc').textContent = b.klasifikasi_ddc || '000';

                document.getElementById('audit_stat_total').textContent = stats.total_unit || 0;
                document.getElementById('audit_stat_tersedia').textContent = stats.tersedia || 0;
                document.getElementById('audit_stat_dipinjam').textContent = stats.dipinjam || 0;
                document.getElementById('audit_stat_rusak').textContent = stats.rusak || 0;
                document.getElementById('audit_stat_afkir').textContent = stats.afkir_dihapuskan || 0;
                document.getElementById('audit_stat_investasi').textContent = 'Rp ' + Number(stats.total_investasi || 0).toLocaleString('id-ID');

                // Breakdown sumber
                const bySumber = stats.by_sumber || {};
                let sumberTxt = [];
                for (let s in bySumber) {
                    sumberTxt.push(s + ': ' + bySumber[s]);
                }
                document.getElementById('audit_breakdown_sumber').textContent = sumberTxt.length ? sumberTxt.join(' | ') : 'Belum ada data perolehan';

                // Render table rows
                const tbody = document.getElementById('audit_eksemplar_tbody');
                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-info-circle me-1"></i> Belum ada unit eksemplar terdaftar untuk buku ini. Silakan klik <strong>Tambah Unit Eksemplar Baru</strong>.</td></tr>';
                    return;
                }

                let html = '';
                items.forEach((item) => {
                    // 1. Barcode & No Induk
                    const barcodeStr = item.barcode ? `<span class="fw-bold text-dark">${escapeHtml(item.barcode)}</span>` : '-';
                    const noIndukStr = item.nomor_induk ? `<br><small class="text-muted">No. Induk: ${escapeHtml(item.nomor_induk)}</small>` : '';

                    // 2. Perolehan & Sumber Dana
                    const sumberDana = escapeHtml(item.sumber_buku || 'Dana BOS');
                    const sumberPemberi = item.sumber_pemberi ? `<br><small class="text-muted"><i class="bi bi-building me-1"></i>${escapeHtml(item.sumber_pemberi)}</small>` : '';
                    const tglMasuk = item.tanggal_masuk ? `<br><small class="text-muted"><i class="bi bi-calendar3 me-1"></i>${item.tanggal_masuk}</small>` : '';
                    const hargaStr = item.harga_perolehan > 0 ? `<br><small class="text-primary fw-semibold">Rp ${Number(item.harga_perolehan).toLocaleString('id-ID')}</small>` : '';

                    // 3. Lokasi Fisik
                    let lokasiStr = '';
                    if (item.rak_nama) {
                        lokasiStr = `<span class="badge bg-light text-dark border"><i class="bi bi-geo-alt me-1 text-primary"></i>${escapeHtml(item.rak_nama)}</span>`;
                        if (item.rak_ruangan || item.rak_baris) {
                            lokasiStr += `<br><small class="text-muted">${escapeHtml(item.rak_ruangan || '')} (Baris: ${escapeHtml(item.rak_baris || '-')})</small>`;
                        }
                    } else {
                        lokasiStr = `<span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-box me-1"></i>Di Gudang / Tanpa Rak</span>`;
                    }

                    // 4. Status Peminjam Aktif
                    let pinjamAktifStr = '<span class="text-muted">-</span>';
                    if (item.peminjam_aktif_nama) {
                        pinjamAktifStr = `<span class="fw-bold text-warning-emphasis"><i class="bi bi-person-fill me-1"></i>${escapeHtml(item.peminjam_aktif_nama)}</span>`;
                        pinjamAktifStr += `<br><small class="text-muted">Pinjam: ${item.pinjam_aktif_tgl || '-'} | Kembali: ${item.pinjam_aktif_jatuh_tempo || '-'}</small>`;
                    } else if (item.status === 'Tersedia') {
                        pinjamAktifStr = `<span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Siap Dipinjam</span>`;
                    }

                    // 5. Terakhir Dipinjam / Dibaca
                    let lastBorrowStr = '<span class="text-muted">-</span>';
                    if (item.last_peminjam_nama) {
                        lastBorrowStr = `<span class="text-dark fw-semibold">${escapeHtml(item.last_peminjam_nama)}</span>`;
                        lastBorrowStr += `<br><small class="text-muted"><i class="bi bi-clock-history me-1"></i>${item.last_pinjam_tgl || '-'}</small>`;
                    }

                    // 6. Kondisi & Status Audit
                    let kondisiBadge = `<span class="badge bg-success-subtle text-success border">${escapeHtml(item.kondisi || 'Baik')}</span>`;
                    if (item.kondisi === 'Rusak Ringan') kondisiBadge = `<span class="badge bg-warning-subtle text-warning border">Rusak Ringan</span>`;
                    if (item.kondisi === 'Rusak Berat') kondisiBadge = `<span class="badge bg-danger-subtle text-danger border">Rusak Berat</span>`;
                    if (item.kondisi === 'Afkir/Dihapuskan' || item.status === 'Dihapuskan/Afkir') {
                        kondisiBadge = `<span class="badge bg-dark text-white"><i class="bi bi-trash me-1"></i>Afkir / Dihapuskan</span>`;
                        if (item.tanggal_penghapusan || item.alasan_penghapusan) {
                            kondisiBadge += `<br><small class="text-danger d-block mt-1">Tgl: ${item.tanggal_penghapusan || '-'}<br>Alasan: ${escapeHtml(item.alasan_penghapusan || '-')}</small>`;
                        }
                    }

                    let statusBadge = `<small class="text-muted d-block mt-1">${escapeHtml(item.status || 'Tersedia')}</small>`;

                    html += `<tr>
                        <td>${barcodeStr}${noIndukStr}</td>
                        <td><span class="badge bg-info-subtle text-info border">${sumberDana}</span>${sumberPemberi}${tglMasuk}${hargaStr}</td>
                        <td>${lokasiStr}</td>
                        <td>${pinjamAktifStr}</td>
                        <td>${lastBorrowStr}</td>
                        <td>${kondisiBadge}${statusBadge}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-warning btn-sm rounded-2 btn-edit-eksemplar-item"
                                    data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'
                                    title="Edit Unit Eksemplar">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>`;
                });

                tbody.innerHTML = html;

                // Attach edit item listeners
                document.querySelectorAll('.btn-edit-eksemplar-item').forEach(b => {
                    b.onclick = function() {
                        const dataItem = JSON.parse(this.dataset.item);
                        openModalEditEksemplar(dataItem);
                    };
                });
            })
            .catch(err => {
                console.error(err);
                document.getElementById('audit_eksemplar_tbody').innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data audit. Silakan coba lagi.</td></tr>';
            });
    }

    // Modal Tambah Eksemplar Baru
    const btnTambahEks = document.getElementById('btnTambahEksemplarModal');
    if (btnTambahEks) {
        btnTambahEks.onclick = function() {
            openModalEditEksemplar({ bibliografi_id: activeAuditBibliografiId });
        };
    }

    function openModalEditEksemplar(item) {
        item = item || {};
        document.getElementById('modalEditEksemplarLabel').innerHTML = item.id ? '<i class="bi bi-pencil-square me-2"></i> Edit Data Unit Eksemplar' : '<i class="bi bi-box-seam me-2"></i> Tambah Unit Eksemplar Baru';
        document.getElementById('eks_id_input').value = item.id || '';
        document.getElementById('eks_bib_id_input').value = item.bibliografi_id || activeAuditBibliografiId;
        document.getElementById('eks_barcode_input').value = item.barcode || ('B-' + Math.floor(Date.now() / 1000));
        document.getElementById('eks_no_induk_input').value = item.nomor_induk || '';
        document.getElementById('eks_tgl_masuk_input').value = item.tanggal_masuk || new Date().toISOString().split('T')[0];
        document.getElementById('eks_harga_input').value = item.harga_perolehan || 0;
        document.getElementById('eks_sumber_select').value = item.sumber_buku || 'Dana BOS';
        document.getElementById('eks_pemberi_input').value = item.sumber_pemberi || '';
        document.getElementById('eks_rak_select').value = item.lokasi_rak_id || '';
        document.getElementById('eks_kondisi_select').value = item.kondisi || 'Baik';
        document.getElementById('eks_status_select').value = item.status || 'Tersedia';
        document.getElementById('eks_tgl_hapus_input').value = item.tanggal_penghapusan || new Date().toISOString().split('T')[0];
        document.getElementById('eks_alasan_hapus_input').value = item.alasan_penghapusan || '';

        togglePenghapusanSection();

        const modalEks = new bootstrap.Modal(document.getElementById('modalEditEksemplar'));
        modalEks.show();
    }

    // Toggle section penghapusan afkir
    const kondisiSel = document.getElementById('eks_kondisi_select');
    const statusSel = document.getElementById('eks_status_select');
    function togglePenghapusanSection() {
        const sec = document.getElementById('sectionPenghapusanEksemplar');
        if (!sec) return;
        const isAfkir = (kondisiSel && kondisiSel.value === 'Afkir/Dihapuskan') || (statusSel && statusSel.value === 'Dihapuskan/Afkir');
        sec.style.display = isAfkir ? '' : 'none';
    }

    if (kondisiSel) kondisiSel.onchange = togglePenghapusanSection;
    if (statusSel) statusSel.onchange = togglePenghapusanSection;

    // Helper escapeHtml
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

}
</script>
