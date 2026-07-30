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
                <a href="<?= htmlspecialchars($streamUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-primary rounded-pill btn-sm px-3" title="Unduh / Open Direct">
                    <i class="bi bi-download me-1"></i> File Direct
                </a>
            </div>
        </div>
    </div>

    <!-- Toolbar Kontrol PDF.js -->
    <div class="card border-0 bg-secondary text-white rounded-top-4 shadow-sm mb-0">
        <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-dark border-secondary" id="page_baca_prev" title="Halaman Sebelumnya">
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <span class="fs-8 text-white">Halaman <strong id="page_baca_num" class="text-warning">1</strong> / <span id="page_baca_count" class="text-white">1</span></span>
                <button type="button" class="btn btn-sm btn-dark border-secondary" id="page_baca_next" title="Halaman Selanjutnya">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-dark border-secondary" id="page_baca_zoom_out" title="Perkecil Zoom">
                    <i class="bi bi-zoom-out"></i>
                </button>
                <span class="fs-8 text-white"><strong id="page_baca_zoom_val" class="text-info">120%</strong></span>
                <button type="button" class="btn btn-sm btn-dark border-secondary" id="page_baca_zoom_in" title="Perbesar Zoom">
                    <i class="bi bi-zoom-in"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Viewer Container dengan Protection Watermark -->
    <div class="position-relative bg-dark rounded-bottom-4 border shadow-sm overflow-auto" style="height: 80vh; min-height: 550px;">
        <!-- Watermark Overlay -->
        <div class="position-absolute top-50 start-50 translate-middle pointer-events-none opacity-25 text-center user-select-none" style="z-index: 10; transform: translate(-50%, -50%) rotate(-25deg); width: 100%;">
            <h1 class="fw-black text-white text-uppercase fs-1 m-0">PERPUSTAKAAN DIGITAL SINTA-SaaS</h1>
            <p class="fs-5 fw-bold text-white mb-0">Diakses oleh: <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?> — <?= date('d M Y H:i') ?> WIB</p>
            <p class="fs-7 text-white">Dilarang Menggandakan / Mengedarkan Tanpa Izin Lisensi Perpustakaan</p>
        </div>

        <!-- Canvas PDF.js Renderer -->
        <div class="d-flex justify-content-center p-3" style="min-height: 100%;">
            <canvas id="page_baca_canvas" class="shadow-lg rounded-2 bg-white my-auto"></canvas>
        </div>
        <div id="page_baca_spinner" class="position-absolute top-50 start-50 translate-middle text-white text-center">
            <div class="spinner-border text-info mb-2" role="status"></div>
            <div class="fs-8">Memuat Dokumen E-Book Digital...</div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}

(function() {
    let pdfDoc = null,
        pageNum = 1,
        pageRendering = false,
        pageNumPending = null,
        scale = 1.2,
        streamUrl = <?= json_encode($streamUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function renderPage(num) {
        pageRendering = true;
        const canvas = document.getElementById('page_baca_canvas');
        if (!canvas || !pdfDoc) return;
        const ctx = canvas.getContext('2d');

        pdfDoc.getPage(num).then(function(page) {
            const viewport = page.getViewport({ scale: scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            const renderTask = page.render(renderContext);

            renderTask.promise.then(function() {
                pageRendering = false;
                if (pageNumPending !== null) {
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
            });
        }).catch(function() { pageRendering = false; });

        const numEl = document.getElementById('page_baca_num');
        if (numEl) numEl.textContent = num;
    }

    function queueRenderPage(num) {
        if (pageRendering) pageNumPending = num;
        else renderPage(num);
    }

    function initPdfReader() {
        const spinner = document.getElementById('page_baca_spinner');
        if (spinner) spinner.style.display = 'block';

        if (!streamUrl || typeof pdfjsLib === 'undefined') return;

        pdfjsLib.getDocument(streamUrl).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            const countEl = document.getElementById('page_baca_count');
            if (countEl) countEl.textContent = pdfDoc.numPages;
            if (spinner) spinner.style.display = 'none';
            pageNum = 1;
            renderPage(pageNum);
        }).catch(function(err) {
            console.error("PDF.js load error:", err);
            if (spinner) spinner.innerHTML = '<div class="text-danger p-3"><i class="bi bi-exclamation-triangle fs-1"></i><p>Gagal memuat e-book PDF.</p></div>';
        });
    }

    document.getElementById('page_baca_prev').onclick = function() {
        if (pageNum <= 1) return;
        pageNum--;
        queueRenderPage(pageNum);
    };
    document.getElementById('page_baca_next').onclick = function() {
        if (!pdfDoc || pageNum >= pdfDoc.numPages) return;
        pageNum++;
        queueRenderPage(pageNum);
    };
    document.getElementById('page_baca_zoom_in').onclick = function() {
        scale += 0.25;
        document.getElementById('page_baca_zoom_val').textContent = Math.round(scale * 100) + '%';
        queueRenderPage(pageNum);
    };
    document.getElementById('page_baca_zoom_out').onclick = function() {
        if (scale <= 0.5) return;
        scale -= 0.25;
        document.getElementById('page_baca_zoom_val').textContent = Math.round(scale * 100) + '%';
        queueRenderPage(pageNum);
    };

    initPdfReader();
})();
</script>
