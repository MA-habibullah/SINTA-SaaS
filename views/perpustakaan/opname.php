<?php
/**
 * View: Stock Opname Audit Koleksi Fisik
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📦 Stock Opname & Audit Inventaris Buku</h2>
        <p class="text-muted fs-7 mb-0">Audit Ketersediaan Buku Fisik di Rak, Pengecekan Selisih Hilang, & Penyesuaian Status Eksemplar.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7" data-bs-toggle="modal" data-bs-target="#modalOpnameBaru">
            <i class="bi bi-qr-code-scan me-1"></i> Mula Sesi Opname Baru
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
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
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-qr-code-scan fs-3 d-block mb-2 text-primary"></i> Belum ada sesi stock opname aktif. Klik <strong>Mulai Sesi Opname Baru</strong> untuk audit fisik rak.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['opname_list'] as $idx => $op): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($op['nama_sesi'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($op['tanggal'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($op['petugas'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)$op['total_scanned'] ?> Eksemplar</td>
                            <td><span class="badge bg-danger"><?= (int)$op['total_selisih'] ?> Hilang</span></td>
                            <td><span class="badge bg-success">Selesai</span></td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm rounded-2">
                                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan Audit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
