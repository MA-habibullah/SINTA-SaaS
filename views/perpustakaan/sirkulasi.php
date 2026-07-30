<?php
/**
 * View: Sirkulasi & Layanan Terpadu
 * @phpstan-var array<string, mixed> $data
 */
if (!function_exists('renderPerpusPagination')) {
    function renderPerpusPagination(array $pagination, string $label = 'data'): void {
        $totalRecords = isset($pagination['total_records']) ? (int)$pagination['total_records'] : 0;
        if (empty($pagination) || $totalRecords <= 0) return;
        $page = (int)($pagination['current_page'] ?? 1);
        $totalPages = (int)($pagination['total_pages'] ?? 1);
        $param = isset($pagination['param']) && is_string($pagination['param']) ? $pagination['param'] : 'page';
        $from = (int)($pagination['from'] ?? 0);
        $to = (int)($pagination['to'] ?? 0);
        ?>
        <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 rounded-bottom-4">
            <div class="text-muted fs-8">
                Menampilkan <span class="fw-bold text-dark"><?= $from ?></span> sampai <span class="fw-bold text-dark"><?= $to ?></span> dari <span class="fw-bold text-dark"><?= number_format($totalRecords) ?></span> total <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>.
            </div>
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-2" href="?<?= http_build_query(array_merge($_GET, [$param => 1])) ?>"><i class="bi bi-chevron-double-left"></i></a>
                        </li>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-2" href="?<?= http_build_query(array_merge($_GET, [$param => max(1, $page - 1)])) ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <?php
                        $startP = max(1, $page - 2);
                        $endP = min($totalPages, $page + 2);
                        for ($p = $startP; $p <= $endP; $p++):
                        ?>
                            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                <a class="page-link rounded-2" href="?<?= http_build_query(array_merge($_GET, [$param => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-2" href="?<?= http_build_query(array_merge($_GET, [$param => min($totalPages, $page + 1)])) ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-2" href="?<?= http_build_query(array_merge($_GET, [$param => $totalPages])) ?>"><i class="bi bi-chevron-double-right"></i></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">🔄 Sirkulasi & Layanan Perpustakaan</h2>
        <p class="text-muted fs-7 mb-0">Kasir Sirkulasi Reguler, Distribusi Buku Paket Massal, Peminjaman Event Khusus (OSN), & Tagihan Denda SPP.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <a href="/SINTA-SaaS/perpustakaan/kios-mandiri" target="_blank" class="btn btn-outline-success btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-display me-1"></i> Buka Kios Mandiri
        </a>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Sleek Navtabs Section -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-2 bg-white rounded-4">
        <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2" id="sirkulasiTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition active" id="reguler-tab" data-bs-toggle="tab" data-bs-target="#reguler-pane" type="button" role="tab" aria-controls="reguler-pane" aria-selected="true">
                        <i class="bi bi-arrow-repeat me-2 text-primary"></i>1. Sirkulasi Reguler
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="paket-tab" data-bs-toggle="tab" data-bs-target="#paket-pane" type="button" role="tab" aria-controls="paket-pane" aria-selected="false">
                        <i class="bi bi-box-seam me-2 text-success"></i>2. Buku Paket Pelajaran
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="event-tab" data-bs-toggle="tab" data-bs-target="#event-pane" type="button" role="tab" aria-controls="event-pane" aria-selected="false">
                        <i class="bi bi-trophy me-2 text-warning"></i>3. Event Khusus (OSN)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="denda-tab" data-bs-toggle="tab" data-bs-target="#denda-pane" type="button" role="tab" aria-controls="denda-pane" aria-selected="false">
                        <i class="bi bi-cash-coin me-2 text-danger"></i>4. Denda & Billing SPP
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Tab Contents -->
<div class="tab-content" id="sirkulasiTabsContent">

    <!-- Tab 1: Sirkulasi Reguler -->
    <div class="tab-pane fade show active" id="reguler-pane" role="tabpanel" aria-labelledby="reguler-tab">
        <div class="row g-4">
            <!-- Form Scanner Peminjaman -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-box-arrow-up-right me-2"></i> Peminjaman Buku Baru</h5>
                    <form id="formPinjamBuku" data-turbo="false">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor Anggota / Scan QR Siswa</label>
                            <input type="text" name="anggota_id" id="pinjam_anggota_id" class="form-control rounded-3" placeholder="Contoh: SIS-2026-001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Barcode Eksemplar Buku</label>
                            <input type="text" name="eksemplar_id" id="pinjam_eksemplar_id" class="form-control rounded-3" placeholder="Scan Barcode di Belakang Sampul..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lama Pinjam (Hari)</label>
                            <input type="number" name="durasi_hari" id="pinjam_durasi_hari" class="form-control rounded-3" value="7" min="1" max="30">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Proses Peminjaman
                        </button>
                    </form>
                </div>
            </div>

            <!-- Form Scanner Pengembalian -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-success mb-3"><i class="bi bi-box-arrow-in-down-left me-2"></i> Pengembalian & Hitung Denda</h5>
                    <form id="formKembaliBuku" data-turbo="false">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Barcode Eksemplar / ID Sirkulasi</label>
                            <input type="text" name="sirkulasi_id" id="kembali_sirkulasi_id" class="form-control rounded-3" placeholder="Scan Barcode Buku yang Dikembalikan..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kondisi Buku</label>
                            <select name="kondisi" id="kembali_kondisi" class="form-select rounded-3">
                                <option value="Baik">Baik / Utuh</option>
                                <option value="Rusak">Rusak Ringan (+ Denda Perbaikan)</option>
                                <option value="Hilang">Hilang (+ Denda Penggantian)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100 rounded-3 py-2 fw-semibold">
                            <i class="bi bi-arrow-down-left-circle me-1"></i> Proses Pengembalian
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Buku Paket Pelajaran -->
    <div class="tab-pane fade" id="paket-pane" role="tabpanel" aria-labelledby="paket-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam text-success me-2"></i> Rekam Distribusi Buku Paket Pelajaran</h5>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fs-7" data-bs-toggle="modal" data-bs-target="#modalDistribusiPaket">
                    <i class="bi bi-plus-circle me-1"></i> Distribusi Paket Baru
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Sekolah / Tenant</th>
                            <th>Nama Paket</th>
                            <th>Kelas / Tingkat</th>
                            <th>Tahun Ajaran</th>
                            <th>Total Judul</th>
                            <th>Siswa Penerima</th>
                            <th>Status Pengembalian</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['paket_list'])): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-box-seam fs-3 d-block mb-2 text-success"></i> Belum ada rekaman distribusi buku paket pelajaran.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['paket_list'] as $idx => $p): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($p['tenant_name'] ?? 'Sekolah Aktif') ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['nama_paket'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($p['kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($p['tahun_ajaran'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int)($p['total_buku'] ?? 0) ?> Judul</td>
                                    <td><?= (int)($p['total_siswa'] ?? 0) ?> Siswa</td>
                                    <td><span class="badge bg-success">Berjalan (Semester 1)</span></td>
                                    <td class="text-center">
                                        <a href="/SINTA-SaaS/perpustakaan/cetak-laporan-peminjaman" class="btn btn-outline-primary btn-sm rounded-2">
                                            <i class="bi bi-printer me-1"></i> Cetak Laporan
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php renderPerpusPagination($data['paket_pagination'] ?? [], 'buku paket'); ?>
        </div>
    </div>

    <!-- Tab 3: Event OSN / Kontingen Lomba -->
    <div class="tab-pane fade" id="event-pane" role="tabpanel" aria-labelledby="event-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-trophy text-warning me-2"></i> Peminjaman Buku Event Olimpiade & Lomba</h5>
                <button type="button" class="btn btn-warning btn-sm rounded-3 px-3 fs-7 text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalEventOSN">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Event OSN
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Sekolah / Tenant</th>
                            <th>Nama Event / Lomba</th>
                            <th>Bidang Studi / Cabang</th>
                            <th>Siswa Peserta (Kontingen)</th>
                            <th>Buku Referensi Diberikan</th>
                            <th>Batas Pengembalian</th>
                            <th>Status Event</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['event_list'])): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-trophy fs-3 d-block mb-2 text-warning"></i> Belum ada event khusus/OSN terdaftar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['event_list'] as $idx => $ev): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($ev['tenant_name'] ?? 'Sekolah Aktif') ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($ev['nama_event'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><span class="badge bg-warning-subtle text-dark"><?= htmlspecialchars($ev['bidang'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($ev['nama_siswa'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($ev['judul_buku'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($ev['tanggal_kembali_rencana'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge bg-success">Aktif / Berjalan</span></td>
                                    <td class="text-center">
                                        <a href="/SINTA-SaaS/perpustakaan/cetak-laporan-peminjaman" class="btn btn-outline-primary btn-sm rounded-2">
                                            <i class="bi bi-file-earmark-text me-1"></i> Berita Acara
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php renderPerpusPagination($data['event_pagination'] ?? [], 'event osn'); ?>
        </div>
    </div>

    <!-- Tab 4: Denda & Billing SPP -->
    <div class="tab-pane fade" id="denda-pane" role="tabpanel" aria-labelledby="denda-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cash-coin text-danger me-2"></i> Manajemen Tagihan Denda Siswa</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa / Anggota</th>
                            <th>Judul Buku</th>
                            <th>Terlambat (Hari)</th>
                            <th>Nominal Denda</th>
                            <th>Status Pembayaran</th>
                            <th>Metode Bayar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['denda_list'])): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-cash-coin fs-3 d-block mb-2 text-success"></i> Tidak ada tunggakan denda perpustakaan yang belum dibayar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['denda_list'] as $idx => $d): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($d['nama_siswa'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= htmlspecialchars($d['judul_buku'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge bg-warning text-dark"><?= (int)$d['hari_terlambat'] ?> Hari</span></td>
                                    <td><strong class="text-danger">Rp <?= number_format((float)$d['jumlah_denda']) ?></strong></td>
                                    <td>
                                        <?php if ($d['status'] === 'Lunas'): ?>
                                            <span class="badge bg-success">Lunas</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Belum Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($d['metode_bayar'] ?? 'Tunai / SPP', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
                                        <?php if ($d['status'] !== 'Lunas'): ?>
                                            <button class="btn btn-success btn-sm rounded-2 btn-bayar-denda" data-id="<?= htmlspecialchars($d['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-check-circle me-1"></i> Bayar Tunai
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php renderPerpusPagination($data['denda_pagination'] ?? [], 'tagihan denda'); ?>
        </div>
    </div>

</div>

<!-- Modal Distribusi Paket Baru -->
<div class="modal fade" id="modalDistribusiPaket" tabindex="-1" aria-labelledby="modalDistribusiPaketLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalDistribusiPaketLabel"><i class="bi bi-box-seam me-2"></i> Form Distribusi Buku Paket Pelajaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/perpustakaan/buku-paket" method="POST" data-turbo="false">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <?php if ($data['is_super_admin'] ?? false): ?>
                            <div class="col-12">
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
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nama Paket Pelajaran <span class="text-danger">*</span></label>
                            <input type="text" name="nama_paket" class="form-control rounded-3" placeholder="Contoh: Paket Teks Kurikulum Merdeka Kelas X" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Target Kelas</label>
                            <select name="kelas" class="form-select rounded-3" required>
                                <option value="X-IPA-1">X IPA 1</option>
                                <option value="X-IPA-2">X IPA 2</option>
                                <option value="XI-IPA-1">XI IPA 1</option>
                                <option value="XII-IPA-1">XII IPA 1</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-check-circle me-1"></i> Simpan & Distribusikan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Event OSN -->
<div class="modal fade" id="modalEventOSN" tabindex="-1" aria-labelledby="modalEventOSNLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalEventOSNLabel"><i class="bi bi-trophy me-2"></i> Pendaftaran Event OSN / Kontingen Lomba</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/perpustakaan/event" method="POST" data-turbo="false">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <?php if ($data['is_super_admin'] ?? false): ?>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Target Sekolah / Tenant <span class="text-danger">*</span></label>
                                <select name="tenant_id" class="form-select rounded-3 bg-light border-warning" required>
                                    <?php foreach ($data['tenants'] as $t): ?>
                                        <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === ($data['active_tenant_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['nama_sekolah']) ?> (<?= htmlspecialchars($t['npsn']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nama Event / Olimpiade <span class="text-danger">*</span></label>
                            <input type="text" name="nama_event" class="form-control rounded-3" placeholder="Contoh: OSN Fisika Tingkat Provinsi" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Bidang Studi / Subjek</label>
                            <input type="text" name="bidang" class="form-control rounded-3" placeholder="Contoh: Fisika / Matematika / Biologi">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-3 px-4 fw-semibold"><i class="bi bi-save me-1"></i> Simpan Event</button>
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
    initSirkulasiHandlers();
});

document.addEventListener('DOMContentLoaded', function() {
    initSirkulasiHandlers();
});

function initSirkulasiHandlers() {
    // 1. Submit Peminjaman Reguler via AJAX
    const formPinjam = document.getElementById('formPinjamBuku');
    if (formPinjam) {
        formPinjam.onsubmit = function(e) {
            e.preventDefault();
            const anggotaId = document.getElementById('pinjam_anggota_id').value;
            const eksemplarId = document.getElementById('pinjam_eksemplar_id').value;
            const durasiHari = document.getElementById('pinjam_durasi_hari').value;

            fetch('/SINTA-SaaS/api/v1/perpustakaan/sirkulasi/pinjam', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    anggota_id: anggotaId,
                    eksemplar_id: eksemplarId,
                    durasi_hari: durasiHari
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('Sukses: Peminjaman berhasil diproses.');
                    formPinjam.reset();
                } else {
                    alert('Gagal Peminjaman: ' + (res.error || 'Terjadi kesalahan sistem'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal mengirim data ke server.');
            });
        };
    }

    // 2. Submit Pengembalian Reguler via AJAX
    const formKembali = document.getElementById('formKembaliBuku');
    if (formKembali) {
        formKembali.onsubmit = function(e) {
            e.preventDefault();
            const sirkulasiId = document.getElementById('kembali_sirkulasi_id').value;
            const kondisi = document.getElementById('kembali_kondisi').value;

            fetch('/SINTA-SaaS/api/v1/perpustakaan/sirkulasi/kembali', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    sirkulasi_id: sirkulasiId,
                    kondisi: kondisi
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    let msg = 'Sukses: Buku berhasil dikembalikan.';
                    if (res.denda && res.denda > 0) {
                        msg += '\nSiswa dikenakan denda keterlambatan sebesar: Rp ' + new Intl.NumberFormat('id-ID').format(res.denda);
                    }
                    alert(msg);
                    formKembali.reset();
                } else {
                    alert('Gagal Pengembalian: ' + (res.error || 'Terjadi kesalahan sistem'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal mengirim data ke server.');
            });
        };
    }

    // 3. Bayar Denda Handler
    const payBtns = document.querySelectorAll('.btn-bayar-denda');
    payBtns.forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            if (confirm('Apakah Anda yakin ingin memproses pembayaran denda tunai secara langsung?')) {
                fetch('/SINTA-SaaS/api/v1/perpustakaan/denda/bayar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ denda_id: id })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('Denda berhasil dibayar lunas.');
                        window.location.reload();
                    } else {
                        alert('Gagal bayar denda: ' + (res.error || 'Unknown error'));
                    }
                })
                .catch(err => console.error(err));
            }
        };
    });
}
</script>
