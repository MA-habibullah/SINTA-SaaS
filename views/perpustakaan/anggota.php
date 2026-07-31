<?php
/**
 * View: Administrasi, Keanggotaan, & Bebas Pustaka Terpadu
 */
$pagination = $data['pagination'] ?? [
    'current_page' => 1,
    'per_page' => 10,
    'total_records' => 0,
    'total_pages' => 1,
    'from' => 0,
    'to' => 0
];
$currentPage = $pagination['current_page'];
$totalPages = $pagination['total_pages'];
$activeTenantId = $data['active_tenant_id'] ?? '';
$pengaturan = $data['pengaturan'] ?? [];
$waAktif = (int)($pengaturan['auto_notif_wa_aktif'] ?? 1);
$emailAktif = (int)($pengaturan['auto_notif_email_aktif'] ?? 0);
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">👥 Administrasi & Keanggotaan Perpustakaan</h2>
        <p class="text-muted fs-7 mb-0">Manajemen Anggota, Verifikasi Surat Bebas Pustaka, Log Buku Tamu, Laporan Statistik, & WA Automation.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="<?= $this->getBaseUrl() ?>/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Sleek Navtabs Section -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-2 bg-white rounded-4">
        <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2" id="anggotaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition active" id="anggota-tab" data-bs-toggle="tab" data-bs-target="#anggota-pane" type="button" role="tab" aria-controls="anggota-pane" aria-selected="true">
                        <i class="bi bi-people me-2 text-primary"></i>1. Daftar Anggota
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="bebas-tab" data-bs-toggle="tab" data-bs-target="#bebas-pane" type="button" role="tab" aria-controls="bebas-pane" aria-selected="false">
                        <i class="bi bi-file-earmark-check me-2 text-success"></i>2. Bebas Pustaka
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="tamu-tab" data-bs-toggle="tab" data-bs-target="#tamu-pane" type="button" role="tab" aria-controls="tamu-pane" aria-selected="false" id="btnLoadVisitorLogs">
                        <i class="bi bi-person-workspace me-2 text-warning"></i>3. Buku Tamu / Pengunjung
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="laporan-tab" data-bs-toggle="tab" data-bs-target="#laporan-pane" type="button" role="tab" aria-controls="laporan-pane" aria-selected="false">
                        <i class="bi bi-file-earmark-bar-graph me-2 text-info"></i>4. Laporan Perpustakaan
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="pengaturan-tab" data-bs-toggle="tab" data-bs-target="#pengaturan-pane" type="button" role="tab" aria-controls="pengaturan-pane" aria-selected="false">
                        <i class="bi bi-sliders me-2 text-danger"></i>5. Pengaturan & WA Toggle
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="kompetensi-tab" data-bs-toggle="tab" data-bs-target="#kompetensi-pane" type="button" role="tab" aria-controls="kompetensi-pane" aria-selected="false">
                        <i class="bi bi-award me-2" style="color: #7c3aed;"></i>6. Kompetensi Pustakawan
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Tab Contents -->
<div class="tab-content" id="anggotaTabsContent">

    <!-- Tab 1: Daftar Anggota -->
    <div class="tab-pane fade show active" id="anggota-pane" role="tabpanel" aria-labelledby="anggota-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-bounding-box text-primary me-2"></i> Keanggotaan Terdaftar</h5>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fs-7" data-bs-toggle="modal" data-bs-target="#modalSyncDapodik">
                    <i class="bi bi-arrow-repeat me-1"></i> Sinkron Anggota
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Sekolah / Tenant</th>
                            <th>Nomor Anggota</th>
                            <th>NISN / NIP</th>
                            <th>Nama Anggota</th>
                            <th>Kelas Aktif</th>
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
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i class="bi bi-person-badge fs-3 d-block mb-2 text-primary"></i> Belum ada data anggota terdaftar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['anggota_list'] as $idx => $a): ?>
                                <tr>
                                    <td><?= ($pagination['from'] > 0 ? $pagination['from'] : 1) + $idx ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($a['tenant_name'] ?? 'Sekolah Aktif') ?>
                                        </span>
                                    </td>
                                    <td><code><?= htmlspecialchars($a['no_anggota'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-normal font-monospace">
                                            <i class="bi bi-card-text me-1 text-secondary"></i><?= htmlspecialchars($a['nisn'] ?? ($a['nip'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($a['nama_lengkap'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border">
                                            <i class="bi bi-door-open me-1"></i><?= htmlspecialchars($a['nama_kelas'] ?? ($a['kode_kelas'] ?? 'Umum / Staf'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($a['tipe_anggota'] ?? 'Siswa', ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= (int)($a['pinjam_aktif'] ?? 0) ?> Buku</td>
                                    <td>Rp <?= number_format((float)($a['total_denda'] ?? 0)) ?></td>
                                    <td>
                                        <?php if (empty($a['pinjam_aktif']) && empty($a['total_denda'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> LULUS</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i> Ada Tanggungan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-label-thermal?barcode=<?= urlencode($a['no_anggota']) ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-2 me-1" title="Cetak Kartu Anggota">
                                            <i class="bi bi-qr-code"></i>
                                        </a>
                                        <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-laporan-peminjaman" class="btn btn-outline-success btn-sm rounded-2" title="Cetak Surat Bebas Pustaka">
                                            <i class="bi bi-file-earmark-check"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Navigation -->
            <?php if ($pagination['total_records'] > 0): ?>
                <div class="d-flex flex-wrap align-items-center justify-content-between pt-4 mt-2 border-top gap-3">
                    <div class="fs-7 text-muted">
                        Menampilkan <span class="fw-bold text-dark"><?= $pagination['from'] ?></span> sampai <span class="fw-bold text-dark"><?= $pagination['to'] ?></span> dari <span class="fw-bold text-dark"><?= number_format($pagination['total_records']) ?></span> total anggota.
                    </div>
                    <nav aria-label="Navigasi Halaman Anggota">
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-3 px-3 py-1.5" href="?page=1<?= !empty($activeTenantId) ? '&tenant_id=' . urlencode($activeTenantId) : '' ?>">
                                    « Pertama
                                </a>
                            </li>
                            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-3 px-3 py-1.5" href="?page=<?= max(1, $currentPage - 1) ?><?= !empty($activeTenantId) ? '&tenant_id=' . urlencode($activeTenantId) : '' ?>">
                                    ‹ Sebelum
                                </a>
                            </li>
                            <?php 
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            for ($p = $startPage; $p <= $endPage; $p++): 
                            ?>
                                <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                                    <a class="page-link rounded-3 px-3 py-1.5 <?= ($p === $currentPage) ? 'fw-bold bg-primary border-primary text-white' : '' ?>" href="?page=<?= $p ?><?= !empty($activeTenantId) ? '&tenant_id=' . urlencode($activeTenantId) : '' ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-3 px-3 py-1.5" href="?page=<?= min($totalPages, $currentPage + 1) ?><?= !empty($activeTenantId) ? '&tenant_id=' . urlencode($activeTenantId) : '' ?>">
                                    Berikut »
                                </a>
                            </li>
                            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-3 px-3 py-1.5" href="?page=<?= $totalPages ?><?= !empty($activeTenantId) ? '&tenant_id=' . urlencode($activeTenantId) : '' ?>">
                                    Terakhir »
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab 2: Bebas Pustaka -->
    <div class="tab-pane fade" id="bebas-pane" role="tabpanel" aria-labelledby="bebas-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold text-success mb-3"><i class="bi bi-file-earmark-check me-2"></i> Verifikasi Surat Keterangan Bebas Pustaka</h5>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">Pilih Siswa / Masukkan NISN</label>
                    <select class="form-select rounded-3 border-success" id="bebas_siswa_select">
                        <option value="">-- Pilih Siswa Untuk Verifikasi Kelayakan --</option>
                        <?php if (!empty($data['anggota_list'])): ?>
                            <?php foreach ($data['anggota_list'] as $s): ?>
                                <?php if ($s['tipe_anggota'] === 'Siswa'): ?>
                                    <option value="<?= htmlspecialchars($s['siswa_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($s['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?> - NISN: <?= htmlspecialchars($s['nisn'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-success rounded-3 w-100 py-2 fw-semibold" id="btnCekBebas"><i class="bi bi-shield-check me-1"></i> Cek Kelayakan Tanggungan</button>
                </div>
            </div>

            <!-- Real-time Verification Panel -->
            <div id="panelBebasResult" class="d-none mt-3">
                <div class="card border-0 bg-light p-4 rounded-3">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-clipboard-check me-2"></i> Rincian Tanggungan Buku & Denda</h6>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <small class="text-muted d-block mb-1">Pinjaman Reguler</small>
                                <span class="fw-bold fs-5 text-primary" id="txtPinjamReg">0 Buku</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <small class="text-muted d-block mb-1">Pinjaman Buku Paket</small>
                                <span class="fw-bold fs-5 text-warning" id="txtPinjamPaket">0 Buku</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <small class="text-muted d-block mb-1">Tunggakan Denda</small>
                                <span class="fw-bold fs-5 text-danger" id="txtDendaTunggakan">Rp 0</span>
                            </div>
                        </div>
                        <div class="col-12 mt-4 text-center">
                            <div id="panelStatusLulus" class="alert alert-success d-none border-0 py-3 rounded-3">
                                <h5 class="fw-bold mb-1 text-success-800"><i class="bi bi-check-circle-fill me-2"></i> SISWA BEBAS TANGGUNGAN</h5>
                                <p class="fs-7 text-success-700 mb-3">Siswa ini tidak memiliki pinjaman aktif atau tunggakan denda. Layak menerima Surat Bebas Pustaka.</p>
                                <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-laporan-peminjaman" target="_blank" class="btn btn-success btn-sm rounded-3 px-4 fw-semibold"><i class="bi bi-printer me-1"></i> Cetak Surat Bebas Pustaka (PDF)</a>
                            </div>
                            <div id="panelStatusGagal" class="alert alert-danger d-none border-0 py-3 rounded-3">
                                <h5 class="fw-bold mb-1 text-danger-800"><i class="bi bi-x-circle-fill me-2"></i> SISWA MEMILIKI TANGGUNGAN</h5>
                                <p class="fs-7 text-danger-700 mb-0">Mohon kembalikan semua buku dan lunasi denda sebelum menerbitkan Surat Bebas Pustaka.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Buku Tamu / Pengunjung -->
    <div class="tab-pane fade" id="tamu-pane" role="tabpanel" aria-labelledby="tamu-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-workspace text-warning me-2"></i> Log Presensi Buku Tamu Perpustakaan (Digital Kios)</h5>
                <button type="button" class="btn btn-outline-success btn-sm rounded-3 px-3 fs-7" onclick="window.open('<?= $this->getBaseUrl() ?>/perpustakaan/kios-pintu', '_blank')">
                    <i class="bi bi-display me-1"></i> Kios Presensi Kunjungan
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="visitorLogsTable">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>NISN</th>
                            <th>Nama Pengunjung</th>
                            <th>Kelas</th>
                            <th>Tujuan Kunjungan</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                        </tr>
                    </thead>
                    <tbody id="visitorLogsBody">
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div> Loading logs kunjungan...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 4: Laporan Perpustakaan -->
    <div class="tab-pane fade" id="laporan-pane" role="tabpanel" aria-labelledby="laporan-tab">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold text-primary mb-2"><i class="bi bi-journal-bookmark me-2"></i> Laporan Rekap DDC</h5>
                    <p class="text-muted fs-7">Rekapitulasi jumlah judul & eksemplar buku berdasarkan 10 kelas utama Klasifikasi Persepuluhan Dewey (DDC).</p>
                    <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-laporan-ddc" target="_blank" class="btn btn-outline-primary rounded-3 mt-auto">
                        <i class="bi bi-printer me-1"></i> Cetak Laporan DDC
                    </a>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold text-success mb-2"><i class="bi bi-person-lines-fill me-2"></i> Laporan Peminjaman Per Siswa / Kelas</h5>
                    <p class="text-muted fs-7">Daftar rinci riwayat buku yang pernah dipinjam oleh setiap siswa untuk keperluan kelulusan/kenaikan kelas.</p>
                    <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-laporan-peminjaman" target="_blank" class="btn btn-outline-success rounded-3 mt-auto">
                        <i class="bi bi-printer me-1"></i> Cetak Rekap Per Siswa
                    </a>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold text-warning mb-2"><i class="bi bi-graph-up-arrow me-2"></i> Laporan Kunjungan & Duta Baca</h5>
                    <p class="text-muted fs-7">Grafik & rekapitulasi statistik pengunjung buku tamu harian serta perangkingan siswa paling rajin membaca.</p>
                    <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-laporan-kunjungan" target="_blank" class="btn btn-outline-warning text-dark rounded-3 mt-auto fw-semibold">
                        <i class="bi bi-printer me-1"></i> Cetak Statistik Kunjungan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 5: Pengaturan & WA Toggle -->
    <div class="tab-pane fade" id="pengaturan-pane" role="tabpanel" aria-labelledby="pengaturan-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-sliders text-primary me-2"></i> Parameter Aturan & Automation Toggle</h5>
            <form action="<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/pengaturan/simpan" method="POST" data-turbo="false">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Nama Perpustakaan</label>
                        <input type="text" name="nama_perpustakaan" class="form-control rounded-3" value="<?= htmlspecialchars($pengaturan['nama_perpustakaan'] ?? 'Perpustakaan Utama', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">NPP / Nomor Pokok Perpustakaan</label>
                        <input type="text" name="nomor_pokok" class="form-control rounded-3" value="<?= htmlspecialchars($pengaturan['nomor_pokok'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Tarif Denda Keterlambatan (Rp / Hari)</label>
                        <input type="number" name="tarif_denda_per_hari" class="form-control rounded-3" value="<?= (float)($pengaturan['tarif_denda_per_hari'] ?? 500) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Batas Hari Pinjam Siswa</label>
                        <input type="number" name="max_hari_pinjam_siswa" class="form-control rounded-3" value="<?= (int)($pengaturan['max_hari_pinjam_siswa'] ?? 7) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Batas Hari Pinjam Guru</label>
                        <input type="number" name="max_hari_pinjam_guru" class="form-control rounded-3" value="<?= (int)($pengaturan['max_hari_pinjam_guru'] ?? 14) ?>">
                    </div>
                </div>

                <hr class="my-4">

                <!-- WA Toggles -->
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-whatsapp text-success me-2"></i> Sakelar Notifikasi Otomatis (Automation Toggles)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3">
                            <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                                <div>
                                    <label class="form-check-label fw-bold text-dark fs-6 d-block" for="toggleWA">
                                        📱 Auto Reminder WhatsApp (H-2 Jatuh Tempo)
                                    </label>
                                    <small class="text-muted">Kirim pesan WhatsApp pengingat pengembalian ke siswa secara otomatis.</small>
                                </div>
                                <input class="form-check-input ms-3 fs-4" type="checkbox" role="switch" id="toggleWA" name="auto_notif_wa_aktif" value="1" <?= $waAktif ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3">
                            <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                                <div>
                                    <label class="form-check-label fw-bold text-dark fs-6 d-block" for="toggleEmail">
                                        ✉️ Auto Reminder Email
                                    </label>
                                    <small class="text-muted">Kirim surat elektronik pengingat jatuh tempo ke email siswa.</small>
                                </div>
                                <input class="form-check-input ms-3 fs-4" type="checkbox" role="switch" id="toggleEmail" name="auto_notif_email_aktif" value="1" <?= $emailAktif ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fw-semibold">
                    <i class="bi bi-save me-1"></i> Simpan Pengaturan Perpustakaan
                </button>
            </form>
        </div>
    </div>

    <!-- Tab 6: Kompetensi Pustakawan -->
    <div class="tab-pane fade" id="kompetensi-pane" role="tabpanel" aria-labelledby="kompetensi-tab">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-award text-primary me-2"></i> Log Diklat & Sertifikasi Kompetensi Pengelola Perpustakaan</h5>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fs-7" id="btnTambahKompetensiModal" data-bs-toggle="modal" data-bs-target="#modalTambahKompetensi">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Log Kompetensi
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="kompetensiTable">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Pengelola / Staf</th>
                            <th>Jabatan</th>
                            <th>Nama Kegiatan / Diklat</th>
                            <th>Penyelenggara</th>
                            <th>Tanggal Kegiatan</th>
                            <th>No. Sertifikat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['kompetensi_list'])): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-award fs-3 d-block mb-2 text-secondary"></i> Belum ada log kompetensi/diklat terdaftar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['kompetensi_list'] as $idx => $kp): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($kp['nama_staf'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($kp['jabatan'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($kp['nama_kegiatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($kp['penyelenggara'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($kp['tanggal_kegiatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge bg-success-subtle text-success border font-monospace"><?= htmlspecialchars($kp['sertifikat_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-warning btn-sm rounded-2 me-1 btn-edit-kompetensi"
                                                data-id="<?= htmlspecialchars($kp['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-nama_staf="<?= htmlspecialchars($kp['nama_staf'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-jabatan="<?= htmlspecialchars($kp['jabatan'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-nama_kegiatan="<?= htmlspecialchars($kp['nama_kegiatan'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-penyelenggara="<?= htmlspecialchars($kp['penyelenggara'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-tanggal="<?= htmlspecialchars($kp['tanggal_kegiatan'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-sertifikat="<?= htmlspecialchars($kp['sertifikat_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-2 btn-delete-kompetensi" data-id="<?= htmlspecialchars($kp['id'], ENT_QUOTES, 'UTF-8') ?>">
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

<!-- Modal Sync Dapodik -->
<div class="modal fade" id="modalSyncDapodik" tabindex="-1" aria-labelledby="modalSyncDapodikLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalSyncDapodikLabel"><i class="bi bi-arrow-repeat me-2"></i> Sinkronisasi Anggota Dapodik/EMIS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-people-fill text-primary fs-1 mb-3 d-block"></i>
                <h5 class="fw-bold mb-2">Sinkronisasi Keanggotaan</h5>
                <p class="text-muted fs-7">Sistem akan memverifikasi dan menyinkronkan seluruh data siswa aktif dari Dapodik / EMIS ke dalam basis data anggota perpustakaan secara real-time.</p>
            </div>
            <div class="modal-footer bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="btnSyncAnggotaAction"><i class="bi bi-arrow-repeat me-1"></i> Jalankan Sinkronisasi</button>
            </div>
        </div>
    </div>
<!-- Modal Tambah / Edit Kompetensi Pustakawan -->
<div class="modal fade" id="modalTambahKompetensi" tabindex="-1" aria-labelledby="modalTambahKompetensiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalTambahKompetensiLabel"><i class="bi bi-award me-2"></i> Tambah Log Kompetensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/kompetensi" method="POST" id="formSaveKompetensi" data-turbo="false">
                <input type="hidden" name="id" id="kompetensi_id" value="">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Pengelola / Staf <span class="text-danger">*</span></label>
                        <input type="text" name="nama_staf" id="kompetensi_nama_staf" class="form-control rounded-3" required placeholder="Contoh: Ahmad Fauzi, S.I.Pust.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jabatan di Perpustakaan <span class="text-danger">*</span></label>
                        <input type="text" name="jabatan" id="kompetensi_jabatan" class="form-control rounded-3" required placeholder="Contoh: Kepala Perpustakaan, Staf IT">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kegiatan / Pelatihan / Diklat <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kegiatan" id="kompetensi_kegiatan" class="form-control rounded-3" required placeholder="Contoh: Diklat Kepala Perpustakaan Sekolah 120 JP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Penyelenggara Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="penyelenggara" id="kompetensi_penyelenggara" class="form-control rounded-3" required placeholder="Contoh: Perpustakaan Nasional RI">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_kegiatan" id="kompetensi_tanggal" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Nomor Sertifikat</label>
                            <input type="text" name="sertifikat_no" id="kompetensi_sertifikat" class="form-control rounded-3" placeholder="Contoh: SER-992/PN/2026">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Simpan Log</button>
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
    initAnggotaHandlers();
});

document.addEventListener('DOMContentLoaded', function() {
    initAnggotaHandlers();
});

function initAnggotaHandlers() {
    // 1. Sync Anggota Action via AJAX Event Delegation
    const syncActionBtn = document.getElementById('btnSyncAnggotaAction');
    if (syncActionBtn) {
        syncActionBtn.onclick = function() {
            syncActionBtn.disabled = true;
            syncActionBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sinkronisasi...';

            fetch('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/anggota/sync')
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    window.location.reload();
                } else {
                    alert('Gagal sinkronisasi: ' + (res.error || 'Unknown error'));
                    syncActionBtn.disabled = false;
                    syncActionBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Jalankan Sinkronisasi';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan koneksi server.');
                syncActionBtn.disabled = false;
                syncActionBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Jalankan Sinkronisasi';
            });
        };
    }

    // 2. Cek Bebas Pustaka AJAX Handler
    const btnCekBebas = document.getElementById('btnCekBebas');
    if (btnCekBebas) {
        btnCekBebas.onclick = function() {
            const idSiswa = document.getElementById('bebas_siswa_select').value;
            if (!idSiswa) {
                alert('Silakan pilih siswa terlebih dahulu.');
                return;
            }

            fetch('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/bebas-pustaka/cek?siswa_id=' + encodeURIComponent(idSiswa))
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const data = res.data;
                    document.getElementById('txtPinjamReg').textContent = data.pinjaman_reguler.length + ' Buku';
                    document.getElementById('txtPinjamPaket').textContent = data.pinjaman_paket.length + ' Buku';
                    document.getElementById('txtDendaTunggakan').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.total_denda_tanggungan);

                    const panelResult = document.getElementById('panelBebasResult');
                    panelResult.classList.remove('d-none');

                    const panelLulus = document.getElementById('panelStatusLulus');
                    const panelGagal = document.getElementById('panelStatusGagal');

                    if (data.bebas_pustaka) {
                        panelLulus.classList.remove('d-none');
                        panelGagal.classList.add('d-none');
                    } else {
                        panelLulus.classList.add('d-none');
                        panelGagal.classList.remove('d-none');
                    }
                } else {
                    alert('Gagal memverifikasi status bebas pustaka.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal mengambil data dari server.');
            });
        };
    }

    // 3. Load Visitor Logs on Tab click
    const tamuTab = document.getElementById('tamu-tab');
    if (tamuTab) {
        tamuTab.addEventListener('click', loadVisitorLogs);
    }

    // 4. Staf Kompetensi Form Submit Handler (Fetch)
    const formSaveKompetensi = document.getElementById('formSaveKompetensi');
    if (formSaveKompetensi) {
        formSaveKompetensi.onsubmit = function(e) {
            e.preventDefault();
            const payload = {
                id: document.getElementById('kompetensi_id').value,
                nama_staf: document.getElementById('kompetensi_nama_staf').value,
                jabatan: document.getElementById('kompetensi_jabatan').value,
                nama_kegiatan: document.getElementById('kompetensi_kegiatan').value,
                penyelenggara: document.getElementById('kompetensi_penyelenggara').value,
                tanggal_kegiatan: document.getElementById('kompetensi_tanggal').value,
                sertifikat_no: document.getElementById('kompetensi_sertifikat').value
            };

            fetch('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/kompetensi', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message || 'Data diklat kompetensi berhasil disimpan.');
                    window.location.reload();
                } else {
                    alert('Gagal menyimpan data: ' + (res.error || 'Unknown error'));
                }
            })
            .catch(err => console.error(err));
        };
    }

    // 5. Reset Staf Kompetensi form on Add click
    const addKompetensiBtn = document.getElementById('btnTambahKompetensiModal');
    if (addKompetensiBtn) {
        addKompetensiBtn.onclick = function() {
            document.getElementById('modalTambahKompetensiLabel').innerHTML = '<i class="bi bi-award me-2"></i> Tambah Log Kompetensi';
            document.getElementById('kompetensi_id').value = '';
            if (formSaveKompetensi) formSaveKompetensi.reset();
        };
    }

    // 6. Edit Staf Kompetensi Button Handler
    const editKompetensiBtns = document.querySelectorAll('.btn-edit-kompetensi');
    editKompetensiBtns.forEach(btn => {
        btn.onclick = function() {
            document.getElementById('modalTambahKompetensiLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Log Kompetensi';
            document.getElementById('kompetensi_id').value = this.dataset.id;
            document.getElementById('kompetensi_nama_staf').value = this.dataset.nama_staf;
            document.getElementById('kompetensi_jabatan').value = this.dataset.jabatan;
            document.getElementById('kompetensi_kegiatan').value = this.dataset.nama_kegiatan;
            document.getElementById('kompetensi_penyelenggara').value = this.dataset.penyelenggara;
            document.getElementById('kompetensi_tanggal').value = this.dataset.tanggal;
            document.getElementById('kompetensi_sertifikat').value = this.dataset.sertifikat;

            const modal = new bootstrap.Modal(document.getElementById('modalTambahKompetensi'));
            modal.show();
        };
    });

    // 7. Delete Staf Kompetensi Button Handler
    const deleteKompetensiBtns = document.querySelectorAll('.btn-delete-kompetensi');
    deleteKompetensiBtns.forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            if (confirm('Apakah Anda yakin ingin menghapus log kompetensi staf ini?')) {
                fetch('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/kompetensi?id=' + encodeURIComponent(id), {
                    method: 'DELETE'
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('Log kompetensi berhasil dihapus.');
                        window.location.reload();
                    } else {
                        alert('Gagal menghapus: ' + (res.error || 'Unknown error'));
                    }
                })
                .catch(err => console.error(err));
            }
        };
    });
}

function loadVisitorLogs() {
    fetch('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/visitor-logs')
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data) {
            const body = document.getElementById('visitorLogsBody');
            if (res.data.length === 0) {
                body.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Belum ada kunjungan digital terekam hari ini.</td></tr>';
                return;
            }
            let html = '';
            res.data.forEach((v, idx) => {
                html += `
                    <tr>
                        <td>${idx + 1}</td>
                        <td><span class="badge bg-light text-dark border font-monospace">${v.nisn || '-'}</span></td>
                        <td><strong>${v.nama_pengunjung}</strong></td>
                        <td><span class="badge bg-light text-dark border">${v.kelas || '-'}</span></td>
                        <td>${v.tujuan}</td>
                        <td>${v.tanggal}</td>
                        <td><span class="badge bg-success-subtle text-success border"><i class="bi bi-clock me-1"></i>${v.jam_masuk}</span></td>
                        <td><span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-clock me-1"></i>${v.jam_keluar || '-'}</span></td>
                    </tr>
                `;
            });
            body.innerHTML = html;
        }
    })
    .catch(err => console.error(err));
}
</script>
