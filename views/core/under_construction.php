<?php /** @var \App\Core\Controller $this */ ?>
<div class="container-fluid py-5 d-flex justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="text-center" style="max-width: 600px;">
        <div class="mb-4">
            <i class="bi bi-tools text-primary" style="font-size: 5rem; text-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);"></i>
        </div>
        <h1 class="display-5 fw-bold text-dark mb-3" style="letter-spacing: -1px;">Modul Dalam Tahap Pengembangan</h1>
        <p class="lead text-secondary mb-4">
            Halaman <strong><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?></strong> saat ini sedang dibangun oleh tim pengembang kami untuk memberikan fitur terbaik bagi Anda. 
            Silakan kembali lagi nanti!
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= $this->getBaseUrl() ?>/dashboard" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" style="transition: all 0.2s;">
                <i class="bi bi-house-door me-2"></i>Kembali ke Beranda
            </a>
            <button onclick="window.history.back()" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold" style="transition: all 0.2s;">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </button>
        </div>
        
        <div class="mt-5 pt-4 border-top">
            <p class="text-muted small mb-0">SINTA SaaS Platform &copy; <?= date('Y') ?></p>
        </div>
    </div>
</div>

<style>
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4) !important;
    }
    .btn-outline-secondary:hover {
        transform: translateY(-2px);
        background-color: #f8f9fa;
    }
</style>
