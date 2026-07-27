<?php
/**
 * View Reader E-Book Digital Perpustakaan SINTA-SaaS
 */
$buku = $data['buku'] ?? [];
$streamUrl = $data['stream_url'] ?? '';
$userName = $data['user_name'] ?? 'Anggota Perpustakaan';
$fileExt = strtolower(pathinfo($buku['file_ebook'] ?? '', PATHINFO_EXTENSION));
?>

<div class="container-fluid p-0 my--3">
    <!-- Header Control Bar -->
    <div class="card border-0 bg-dark text-white rounded-4 shadow-lg mb-3">
        <div class="card-body p-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="/SINTA-SaaS/perpustakaan/katalog" class="btn btn-outline-light rounded-pill btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
                </a>
                <div>
                    <h5 class="fw-bold mb-0 text-white"><i class="bi bi-book-half text-info me-2"></i><?= htmlspecialchars($buku['judul'] ?? 'Judul Buku', ENT_QUOTES, 'UTF-8') ?></h5>
                    <small class="text-white-50">
                        Penulis: <?= htmlspecialchars(is_array($buku['penulis'] ?? null) ? implode(', ', $buku['penulis']) : (is_string($buku['penulis'] ?? null) && strpos($buku['penulis'], '[') === 0 ? implode(', ', json_decode($buku['penulis'], true) ?: [$buku['penulis']]) : ($buku['penulis'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?> 
                        • ISBN: <?= htmlspecialchars($buku['isbn'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fs-8">
                    <i class="bi bi-shield-check me-1"></i> Akses Terverifikasi
                </span>
                <a href="<?= htmlspecialchars($streamUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-primary rounded-pill btn-sm px-3" title="Buka Tab Baru">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Fullscreen
                </a>
            </div>
        </div>
    </div>

    <!-- Viewer Container dengan Protection Watermark -->
    <div class="position-relative bg-secondary-subtle rounded-4 border shadow-sm overflow-hidden" style="height: 80vh; min-height: 550px;">
        <!-- Watermark Overlay -->
        <div class="position-absolute top-50 start-50 translate-middle pointer-events-none opacity-25 text-center user-select-none" style="z-index: 10; transform: translate(-50%, -50%) rotate(-25deg); width: 100%;">
            <h1 class="fw-black text-dark text-uppercase fs-1 m-0">PERPUSTAKAAN DIGITAL SINTA-SaaS</h1>
            <p class="fs-5 fw-bold text-dark mb-0">Diakses oleh: <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?> — <?= date('d M Y H:i') ?> WIB</p>
            <p class="fs-7 text-dark">Dilarang Menggandakan / Mengedarkan Tanpa Izin Lisensi Perpustakaan</p>
        </div>

        <?php if ($fileExt === 'pdf'): ?>
            <!-- PDF Viewer Embed -->
            <iframe src="<?= htmlspecialchars($streamUrl, ENT_QUOTES, 'UTF-8') ?>" class="w-100 h-100 border-0" title="Viewer E-Book PDF"></iframe>
        <?php else: ?>
            <!-- Alternative Embed / Object Viewer -->
            <object data="<?= htmlspecialchars($streamUrl, ENT_QUOTES, 'UTF-8') ?>" type="application/pdf" class="w-100 h-100">
                <div class="p-5 text-center">
                    <i class="bi bi-file-earmark-pdf fs-1 text-primary mb-3 display-3"></i>
                    <h4>File E-Book Digital Siap Dibaca</h4>
                    <p class="text-muted">Browser Anda memerlukan plugin viewer PDF untuk membaca secara langsung di dalam halaman.</p>
                    <a href="<?= htmlspecialchars($streamUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary rounded-pill px-4" target="_blank">
                        <i class="bi bi-download me-1"></i> Klik untuk Buka E-Book PDF
                    </a>
                </div>
            </object>
        <?php endif; ?>
    </div>
</div>
