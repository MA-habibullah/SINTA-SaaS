<?php
/**
 * View: Denda & Billing SPP Integrasi Perpustakaan
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">💰 Denda & Billing Integrasi SPP</h2>
        <p class="text-muted fs-7 mb-0">Manajemen Pembayaran Denda Keterlambatan Tunai / Otomatis Masuk Tagihan SPP Keuangan Sekolah.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
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
                                    <button class="btn btn-success btn-sm rounded-2 me-1">
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
</div>
