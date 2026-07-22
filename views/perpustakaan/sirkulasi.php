<?php
/**
 * View: Sirkulasi Reguler Perpustakaan
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">🔄 Sirkulasi Transaksi Peminjaman & Pengembalian</h2>
        <p class="text-muted fs-7 mb-0">Kasir Pustakawan Barcode Scanner & Peminjaman Mandiri.</p>
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

<div class="row g-4">
    <!-- Form Scanner Peminjaman -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold text-primary mb-3"><i class="bi bi-box-arrow-up-right me-2"></i> Peminjaman Buku Baru</h5>
            <form id="formPinjamBuku">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nomor Anggota / Scan QR Siswa</label>
                    <input type="text" class="form-control rounded-3" placeholder="Contoh: SIS-2026-001" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kode Barcode Eksemplar Buku</label>
                    <input type="text" class="form-control rounded-3" placeholder="Scan Barcode di Belakang Sampul..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Lama Pinjam (Hari)</label>
                    <input type="number" class="form-control rounded-3" value="7" min="1" max="30">
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
            <form id="formKembaliBuku">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kode Barcode Eksemplar / ID Sirkulasi</label>
                    <input type="text" class="form-control rounded-3" placeholder="Scan Barcode Buku yang Dikembalikan..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kondisi Buku</label>
                    <select class="form-select rounded-3">
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
