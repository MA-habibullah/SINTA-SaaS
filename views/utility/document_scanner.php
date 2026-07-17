<?php
/**
 * View: Pemindai & Kompresor Dokumen (AeroScan)
 * Berjalan sepenuhnya di client-side untuk privasi dan efisiensi penuh.
 */
?>

<!-- Pustaka External khusus untuk Halaman Scanner -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="https://unpkg.com/lucide@latest" onload="window._lucideReady = true; if (typeof window.safeCreateIcons === 'function') window.safeCreateIcons(); if (typeof window._flushToastQueue === 'function') window._flushToastQueue();"></script>

<!-- Stylesheet Kustom AeroScan diselaraskan dengan desain SINTA-SaaS -->
<style>
    .aeroscan-container {
        --as-primary:    #2563eb;
        --as-primary-h:  #1d4ed8;
        --as-primary-lt: #eff6ff;
        --as-success:    #16a34a;
        --as-warning:    #d97706;
        --as-danger:     #dc2626;
        --as-bg:         #f8fafc;
        --as-surface:    #ffffff;
        --as-border:     #e2e8f0;
        --as-text:       #1e293b;
        --as-text-muted: #64748b;
        --as-radius-lg:  12px;
        --as-radius-md:  8px;
        --as-shadow-sm:  0 1px 3px rgba(15,23,42,0.07);
        --as-shadow-md:  0 4px 12px rgba(15,23,42,0.1);
        --accent-cyan:   #2563eb;
        --accent-pink:   #db2777;
        --accent-violet: #7c3aed;
        --text-light:    #1e293b;
        --text-muted:    #64748b;
        --glass-border:  #e2e8f0;
        color: var(--as-text);
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
    }
    .aeroscan-card { background: var(--as-surface); border: 1px solid var(--as-border); border-radius: var(--as-radius-lg); padding: 18px; box-shadow: var(--as-shadow-sm); margin-bottom: 16px; }
    .aeroscan-card-title { font-size: 0.88rem; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--as-text); margin-bottom: 14px; border-bottom: 1px solid var(--as-border); padding-bottom: 10px; }
    .aeroscan-upload-zone { border: 2px dashed var(--as-border); border-radius: var(--as-radius-md); padding: 28px 16px; text-align: center; cursor: pointer; transition: all 0.25s ease; background: var(--as-bg); position: relative; }
    .aeroscan-upload-zone:hover { border-color: var(--as-primary); background: var(--as-primary-lt); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .aeroscan-upload-zone.dragover { border-color: var(--as-primary); background: var(--as-primary-lt); }
    .aeroscan-upload-zone input { display: none; }
    .aeroscan-upload-icon { font-size: 2rem; margin-bottom: 10px; color: var(--as-primary); display: inline-block; }
    .aeroscan-parameter-group { display: flex; flex-direction: column; gap: 14px; }
    .aeroscan-slider-container { display: flex; flex-direction: column; gap: 5px; }
    .aeroscan-slider-header { display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--as-text-muted); }
    .aeroscan-slider-val { color: var(--as-primary); font-weight: 700; }
    .aeroscan-container input[type="range"] { -webkit-appearance: none; width: 100%; height: 5px; border-radius: 3px; background: #e2e8f0; outline: none; }
    .aeroscan-container input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 16px; height: 16px; border-radius: 50%; background: var(--as-primary); cursor: pointer; transition: transform 0.1s ease; box-shadow: 0 1px 4px rgba(37,99,235,0.4); border: 2px solid #fff; }
    .aeroscan-container input[type="range"]::-webkit-slider-thumb:hover { transform: scale(1.2); }
    .aeroscan-filter-selector { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; background: #f1f5f9; padding: 3px; border-radius: 8px; border: 1px solid var(--as-border); }
    .aeroscan-filter-btn { background: transparent; border: none; color: var(--as-text-muted); padding: 6px 4px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-align: center; }
    .aeroscan-filter-btn.active { background: var(--as-primary); color: #ffffff; font-weight: 700; box-shadow: 0 2px 6px rgba(37,99,235,0.3); }
    .aeroscan-filter-btn:hover:not(.active) { background: #e2e8f0; color: var(--as-text); }
    .aeroscan-canvas-container { position: relative; background: #f1f5f9; border-radius: var(--as-radius-md); overflow: hidden; display: flex; align-items: center; justify-content: center; min-height: 400px; max-height: 520px; border: 1px solid var(--as-border); }
    .aeroscan-canvas-container canvas { max-width: 100%; max-height: 100%; display: block; object-fit: contain; }
    .aeroscan-wrapper-el { position: relative; display: inline-block; }
    .aeroscan-overlay-svg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 10; }
    .aeroscan-corner-handle { position: absolute; width: 20px; height: 20px; background: var(--as-primary); border: 3px solid #ffffff; border-radius: 50%; cursor: move; transform: translate(-50%, -50%); z-index: 20; box-shadow: 0 0 0 2px var(--as-primary), 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.15s ease; touch-action: none; }
    .aeroscan-corner-handle:hover { transform: translate(-50%, -50%) scale(1.3); }
    .aeroscan-corner-handle.right-handle { background: var(--accent-pink); box-shadow: 0 0 0 2px var(--accent-pink), 0 2px 6px rgba(0,0,0,0.3); }
    .aeroscan-status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 5px 14px; background: #f1f5f9; border: 1px solid var(--as-border); border-radius: 50px; font-size: 0.8rem; font-weight: 600; color: var(--as-text); }
    .aeroscan-status-dot { width: 9px; height: 9px; border-radius: 50%; background-color: var(--as-danger); }
    .aeroscan-status-dot.ready { background-color: var(--as-success); box-shadow: 0 0 6px var(--as-success); }
    .aeroscan-status-dot.processing { background-color: var(--as-warning); box-shadow: 0 0 6px var(--as-warning); animation: pulse 1s infinite alternate; }
    .aeroscan-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--as-text-muted); gap: 10px; text-align: center; padding: 20px; position: absolute; pointer-events: none; }
    .aeroscan-empty-state-icon { font-size: 2.8rem; opacity: 0.3; color: var(--as-primary); }
    .aeroscan-empty-state p { font-size: 0.83rem; max-width: 250px; opacity: 0.75; }
    .aeroscan-loader-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.93); backdrop-filter: blur(4px); z-index: 999; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; border-radius: var(--as-radius-lg); transition: opacity 0.5s ease, visibility 0.5s ease; }
    .aeroscan-loader-overlay h4 { color: var(--as-text); }
    .aeroscan-loader-overlay p  { color: var(--as-text-muted); }
    .aeroscan-loader-overlay.fade-out { opacity: 0; visibility: hidden; }
    .aeroscan-spinner { width: 44px; height: 44px; border: 4px solid #e2e8f0; border-top-color: var(--as-primary); border-radius: 50%; animation: spin 1s linear infinite; }
    .aeroscan-stats-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 12px; }
    .aeroscan-stat-box { background: #f8fafc; padding: 10px 8px; border-radius: var(--as-radius-md); border: 1px solid var(--as-border); text-align: center; }
    .aeroscan-stat-label { font-size: 0.68rem; color: var(--as-text-muted); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px; }
    .aeroscan-stat-value { font-size: 0.9rem; font-weight: 700; color: var(--as-text); }
    .aeroscan-stat-value.savings { color: var(--as-success); }
    .aeroscan-toast { background: #ffffff; border: 1px solid var(--as-border); border-left: 4px solid var(--as-primary); padding: 11px 16px; border-radius: var(--as-radius-md); color: var(--as-text); display: flex; align-items: center; gap: 10px; box-shadow: var(--as-shadow-md); margin-bottom: 8px; font-size: 0.84rem; pointer-events: auto; transform: translateX(120%); opacity: 0; transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s; }
    .aeroscan-toast.show { transform: translateX(0); opacity: 1; }
    .aeroscan-toast.toast-success { border-left-color: var(--as-success); }
    .aeroscan-toast.toast-error   { border-left-color: var(--as-danger); }
    .aeroscan-toast.toast-info    { border-left-color: var(--as-primary); }
    .aeroscan-info-banner { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--as-radius-md); padding: 10px 12px; font-size: 0.78rem; line-height: 1.45; color: #1d4ed8; display: flex; gap: 8px; margin-top: 10px; }
    #ocr-activate-banner { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; padding: 40px 20px; text-align: center; color: var(--as-text-muted); }

    /* ── RESPONSIVE MOBILE OPTIMIZATIONS ───────────────────────────── */
    @media (max-width: 768px) {
        .aeroscan-card-title {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 10px !important;
        }
        .aeroscan-card-title > span {
            font-size: 0.85rem;
        }
        .aeroscan-card-title .d-flex {
            justify-content: flex-start !important;
            width: 100% !important;
            flex-wrap: wrap;
        }
        .aeroscan-tab-group {
            width: 100% !important;
        }
        .aeroscan-tab-group button {
            flex: 1;
            font-size: 0.72rem !important;
            padding: 8px 4px !important;
        }
        #output-res-badge {
            align-self: flex-start !important;
        }
    }

    @media (max-width: 576px) {
        .aeroscan-stats-container {
            grid-template-columns: 1fr !important;
            gap: 6px !important;
        }
        .aeroscan-stat-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px !important;
            text-align: left !important;
        }
        .aeroscan-stat-label {
            margin-bottom: 0 !important;
        }
        .aeroscan-stat-value {
            font-size: 0.85rem !important;
        }
        #zoom-percent {
            min-width: 38px !important;
        }
    }

    @keyframes spin  { to { transform: rotate(360deg); } }
    @keyframes pulse { from { opacity: 0.45; } to { opacity: 1; } }
    .hidden {
        display: none !important;
    }
</style>

<!-- MAIN APP UI CONTAINER -->
<div class="aeroscan-container container-fluid p-0">

    <!-- INPUT SOURCE SELECTION MODAL -->
    <div id="source-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,23,42,0.55); backdrop-filter: blur(8px); z-index: 10500; align-items: center; justify-content: center;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 36px 44px; max-width: 460px; width: 90%; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
            <div style="width: 60px; height: 60px; background: #eff6ff; border: 2px solid #bfdbfe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px;">
                <i data-lucide="scan-line" style="width: 28px; height: 28px; color: #2563eb;"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: #1e293b;">Pilih Sumber Dokumen</h4>
            <p class="text-muted mb-4" style="font-size: 0.85rem;">Bagaimana Anda ingin memasukkan dokumen yang akan dipindai?</p>
            <div class="d-flex flex-column gap-3">
                <button id="source-upload-btn" class="btn btn-outline-primary fw-bold py-3" style="border-radius: 10px;">
                    <i data-lucide="cloud-upload" style="width: 20px; height: 20px;" class="me-2 inline-block align-middle"></i>
                    <span class="align-middle">Unggah File dari Komputer</span>
                </button>
                <button id="source-camera-btn" class="btn btn-outline-secondary fw-bold py-3" style="border-radius: 10px;">
                    <i data-lucide="camera" style="width: 20px; height: 20px;" class="me-2 inline-block align-middle"></i>
                    <span class="align-middle">Ambil Foto via Kamera</span>
                </button>
                <button id="source-cancel-btn" class="btn btn-link text-muted fs-8 mt-1">Batal</button>
            </div>
        </div>
    </div>

    <!-- Real-time Camera Auto-Scan Modal Overlay -->
    <div id="camera-modal"
         style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #000; z-index: 10000; flex-direction: column; align-items: center; justify-content: center;">
        <video id="video-stream" autoplay playsinline
               style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;"></video>
        <canvas id="camera-overlay-canvas"
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 10001;"></canvas>

        <!-- Top info status -->
        <div id="camera-status-overlay"
             style="position: absolute; top: 30px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.1rem; font-weight: 600; background: rgba(11, 19, 41, 0.85); border: 1px solid rgba(255,255,255,0.1); padding: 12px 24px; border-radius: 50px; z-index: 10002; color: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.5); backdrop-filter: blur(8px);">
            Posisikan dokumen di dalam kamera...
        </div>

        <!-- Bottom controls -->
        <div style="position: absolute; bottom: 40px; display: flex; gap: 24px; z-index: 10002;">
            <button class="btn btn-danger" id="close-camera-btn"
                    style="padding: 14px 28px; font-size: 1rem; border-radius: 50px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);">
                Batal
            </button>
            <button class="btn btn-secondary" id="manual-capture-btn"
                    style="padding: 14px 28px; font-size: 1rem; border-radius: 50px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.4);">
                Ambil Manual
            </button>
        </div>
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1">AeroScan - Pemindai Dokumen</h2>
            <p class="text-muted fs-7">Ubah foto dokumen menjadi dokumen cetak digital yang rapi, lurus, dan terkompresi tinggi. (100% Client-Side).</p>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <div class="aeroscan-status-badge">
                <span class="aeroscan-status-dot" id="status-dot"></span>
                <span id="status-text">Menginisialisasi...</span>
            </div>
            <a href="/SINTA-SaaS/dashboard" class="btn btn-outline-secondary btn-sm d-flex align-items-center rounded-3 px-3 py-2 fs-7">
                <i class="bi bi-arrow-left me-2"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Loader Overlay (OpenCV.js loading) -->
    <div class="aeroscan-card position-relative" style="min-height: 500px;">
        <div class="aeroscan-loader-overlay" id="loading-overlay">
            <div class="aeroscan-spinner"></div>
            <h4 class="fw-bold mb-1" id="loader-status" style="color: var(--as-text);">Memuat OpenCV.js...</h4>
            <p class="text-muted text-center fs-8">AeroScan siap dalam beberapa detik setelah OpenCV terinisialisasi.</p>
        </div>

        <div class="row">
            <!-- COLUMN 1: CONTROL SIDEBAR -->
            <div class="col-lg-4 col-md-5">

                <!-- Mode Dokumen -->
                <div class="aeroscan-card">
                    <div class="aeroscan-card-title">
                        <i data-lucide="book-open" style="width: 18px; height: 18px; color: var(--as-primary);"></i>
                        Mode Pemindaian
                    </div>
                    <div class="aeroscan-filter-selector mb-3" style="grid-template-columns: 1fr 1fr;">
                        <button class="aeroscan-filter-btn active" id="mode-single-btn">Halaman Tunggal</button>
                        <button class="aeroscan-filter-btn" id="mode-book-btn">Buku (2 Hal)</button>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm w-100 fs-8" id="toggle-spine-btn">
                        <i data-lucide="split" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                        <span class="align-middle">Aktifkan Pembatas Buku</span>
                    </button>
                </div>

                <!-- Input Foto -->
                <div class="aeroscan-card">
                    <div class="aeroscan-card-title">
                        <i data-lucide="image-plus" style="width: 18px; height: 18px; color: var(--as-primary);"></i>
                        Sumber Gambar
                    </div>
                    <div class="aeroscan-upload-zone mb-3" id="drop-zone">
                        <i data-lucide="cloud-upload" class="aeroscan-upload-icon"></i>
                        <h6 class="fw-bold mb-1 fs-8">Klik atau Tarik Foto ke Sini</h6>
                        <p class="text-muted fs-9 mb-0">Format: JPEG, PNG (Maks. 20MB)</p>
                        <input type="file" id="file-input" accept="image/*" multiple>
                    </div>
                    <button class="btn btn-primary w-100 fs-7 fw-bold mb-2 shadow" id="camera-btn">
                        <i data-lucide="camera" style="width: 16px; height: 16px;" class="me-1 inline-block align-middle"></i>
                        <span class="align-middle">Ambil Foto via Kamera</span>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm w-100 fs-8" id="demo-btn">
                        <i data-lucide="sparkles" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                        <span class="align-middle">Gunakan Contoh Dokumen</span>
                    </button>
                </div>

                <!-- Parameter Editor -->
                <div class="aeroscan-card">
                    <div class="aeroscan-card-title">
                        <i data-lucide="sliders" style="width: 18px; height: 18px; color: #7c3aed;"></i>
                        Koreksi & Kualitas Hasil
                    </div>
                    <div class="aeroscan-parameter-group">
                        <div class="aeroscan-slider-container">
                            <label class="fs-8 text-muted">Resolusi Output</label>
                            <select id="resolution-preset" class="form-select form-select-sm">
                                <option value="standard" selected>Standar (1200 x 1842)</option>
                                <option value="hd">High-Definition HD (1600 x 2456)</option>
                                <option value="super-hd">Super-HD Ultra (2400 x 3684)</option>
                            </select>
                        </div>
                        <div class="aeroscan-slider-container">
                            <div class="aeroscan-slider-header">
                                <span>Kualitas JPEG (Kompresi)</span>
                                <span class="aeroscan-slider-val" id="quality-val">0.85</span>
                            </div>
                            <input type="range" id="quality-slider" min="0.1" max="1.0" step="0.05" value="0.85">
                        </div>
                        <div class="aeroscan-slider-container">
                            <div class="aeroscan-slider-header">
                                <span>Penajaman (USM Sharpen)</span>
                                <span class="aeroscan-slider-val" id="sharpen-val">0.8</span>
                            </div>
                            <input type="range" id="sharpen-slider" min="0.0" max="2.0" step="0.1" value="0.8">
                        </div>
                        <div class="aeroscan-slider-container">
                            <div class="aeroscan-slider-header">
                                <span>Kecerahan (Brightness)</span>
                                <span class="aeroscan-slider-val" id="brightness-val">10</span>
                            </div>
                            <input type="range" id="brightness-slider" min="-60" max="60" step="5" value="10">
                        </div>
                        <div class="aeroscan-slider-container">
                            <div class="aeroscan-slider-header">
                                <span>Kontras (Contrast)</span>
                                <span class="aeroscan-slider-val" id="contrast-val">1.40</span>
                            </div>
                            <input type="range" id="contrast-slider" min="0.6" max="1.8" step="0.05" value="1.40">
                        </div>
                        <div class="aeroscan-slider-container">
                            <label class="fs-8 text-muted">Filter Warna</label>
                            <div class="aeroscan-filter-selector">
                                <button class="aeroscan-filter-btn" data-mode="magic">Magic</button>
                                <button class="aeroscan-filter-btn active" data-mode="color">Warna</button>
                                <button class="aeroscan-filter-btn" data-mode="gray">Abu</button>
                                <button class="aeroscan-filter-btn" data-mode="bw">B&W</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aksi Tambahan & PDF -->
                <div class="aeroscan-card">
                    <div class="aeroscan-card-title">
                        <i data-lucide="file-text" style="width: 18px; height: 18px; color: var(--as-primary);"></i>
                        Ekspor PDF & Unduh
                    </div>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <button class="btn btn-outline-secondary btn-sm disabled" id="rotate-btn">
                            <i data-lucide="rotate-cw" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                            <span class="align-middle">Putar Gambar 90°</span>
                        </button>
                        <button class="btn btn-outline-danger btn-sm disabled" id="reset-corners-btn">
                            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                            <span class="align-middle">Reset Posisi Sudut</span>
                        </button>
                    </div>

                    <div class="aeroscan-parameter-group border-top pt-3">
                        <div class="aeroscan-slider-container">
                            <label class="fs-8 text-muted">Nama Berkas PDF</label>
                            <input type="text" id="pdf-filename" value="Dokumen_Scan_Aero" class="form-control form-control-sm">
                        </div>
                        <button class="btn btn-outline-info btn-sm disabled w-100" id="add-to-pdf-btn" style="color: var(--accent-cyan); border-color: var(--as-primary);">
                            <i data-lucide="plus-circle" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                            <span class="align-middle">Simpan Halaman Aktif</span>
                        </button>
                        <button class="btn btn-primary disabled w-100 fw-bold" id="generate-pdf-btn">
                            <i data-lucide="file-check-2" style="width: 16px; height: 16px;" class="me-1 inline-block align-middle"></i>
                            <span class="align-middle">Download PDF (<span id="pdf-pages-count">0</span> Hal)</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- COLUMN 2: WORKSPACE -->
            <div class="col-lg-8 col-md-7">

                <!-- PANEL 1: AREA FOTO ASLI (DETEKSI) -->
                <div class="aeroscan-card">
                    <div class="aeroscan-card-title d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                        <span class="d-flex align-items-center gap-2">
                            <span style="background: var(--as-success); width: 8px; height: 8px; border-radius: 50%;"></span>
                            Foto Dokumen Asli
                        </span>
                        <!-- Zoom & Pan Controls -->
                        <div class="d-flex gap-2 align-items-center flex-wrap" style="z-index: 10;">
                            <div class="btn-group border rounded" style="background: #ffffff; padding: 2px;">
                                <button class="btn btn-link btn-sm p-1 border-0" id="zoom-out-btn" style="width: 28px; height: 28px; text-decoration: none;" title="Zoom Out">
                                    <i data-lucide="zoom-out" style="width: 14px; height: 14px; color: var(--as-text-muted);"></i>
                                </button>
                                <span id="zoom-percent" class="fs-9 text-muted fw-bold d-flex align-items-center justify-content-center" style="min-width: 38px; text-align: center; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">100%</span>
                                <button class="btn btn-link btn-sm p-1 border-0" id="zoom-in-btn" style="width: 28px; height: 28px; text-decoration: none;" title="Zoom In">
                                    <i data-lucide="zoom-in" style="width: 14px; height: 14px; color: var(--as-text-muted);"></i>
                                </button>
                                <button class="btn btn-link btn-sm p-1 border-0" id="zoom-reset-btn" style="width: 28px; height: 28px; text-decoration: none;" title="Reset Zoom">
                                    <i data-lucide="maximize-2" style="width: 14px; height: 14px; color: var(--as-text-muted);"></i>
                                </button>
                            </div>
                            <span class="badge bg-light text-muted border fs-9 py-2 px-2.5" id="orig-res-badge">0 x 0 px</span>
                        </div>
                    </div>

                    <div class="aeroscan-canvas-container" id="original-canvas-container">
                        <div class="aeroscan-empty-state" id="orig-empty-state">
                            <i data-lucide="file-image" class="aeroscan-empty-state-icon"></i>
                            <p>Unggah foto dokumen atau ambil via kamera untuk mendeteksi tepian secara otomatis.</p>
                        </div>

                        <!-- Interactive Area -->
                        <div id="original-wrapper" class="aeroscan-wrapper-el hidden">
                            <canvas id="canvas-original"></canvas>
                            <svg class="aeroscan-overlay-svg" id="overlay-svg">
                                <polygon id="doc-overlay" points="0,0 0,0 0,0 0,0" stroke="#2563eb" stroke-width="2.5" fill="rgba(37,99,235,0.12)"></polygon>
                                <polygon id="doc-overlay-right" points="0,0 0,0 0,0 0,0" stroke="#db2777" stroke-width="2.5" fill="rgba(219,39,119,0.12)" class="hidden"></polygon>
                            </svg>

                            <!-- Spine Divider Line for Books -->
                            <div id="spine-divider" class="hidden" style="position: absolute; top: 0; bottom: 0; width: 6px; background: #eab308; cursor: ew-resize; z-index: 15; transform: translateX(-50%); box-shadow: 0 0 10px rgba(234, 179, 8, 0.8); border-left: 1px dashed #fff; border-right: 1px dashed #fff;">
                                <div style="position: absolute; top: 15px; left: 50%; transform: translateX(-50%); background: #eab308; color: #0b1329; font-size: 0.6rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; white-space: nowrap; pointer-events: none;">
                                    BATAS BUKU (SPINE)
                                </div>
                            </div>

                            <!-- Left Handles / Single Handles -->
                            <div class="aeroscan-corner-handle" id="handle-tl" data-corner="tl"></div>
                            <div class="aeroscan-corner-handle" id="handle-tr" data-corner="tr"></div>
                            <div class="aeroscan-corner-handle" id="handle-br" data-corner="br"></div>
                            <div class="aeroscan-corner-handle" id="handle-bl" data-corner="bl"></div>

                            <!-- Right Handles (Book Mode) -->
                            <div class="aeroscan-corner-handle right-handle hidden" id="handle-rtl" data-corner="rtl"></div>
                            <div class="aeroscan-corner-handle right-handle hidden" id="handle-rtr" data-corner="rtr"></div>
                            <div class="aeroscan-corner-handle right-handle hidden" id="handle-rbr" data-corner="rbr"></div>
                            <div class="aeroscan-corner-handle right-handle hidden" id="handle-rbl" data-corner="rbl"></div>
                        </div>
                    </div>

                    <div class="aeroscan-info-banner">
                        <i data-lucide="info" style="width: 16px; height: 16px; flex-shrink: 0;" class="inline-block"></i>
                        <span id="tip-text"><strong>Tips:</strong> Geser titik penanda berwarna biru (cyan) pada sudut dokumen di atas jika hasil potong otomatis kurang presisi.</span>
                    </div>
                </div>

                <!-- PANEL 2: HASIL SCAN & OCR -->
                <div class="aeroscan-card">
                    <div class="aeroscan-card-title d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-0">
                        <div class="aeroscan-tab-group d-flex p-1 rounded border" style="background: #f1f5f9; gap: 4px;">
                            <button class="aeroscan-filter-btn active px-3 py-1.5" id="tab-image-btn" style="border-radius: 6px;">
                                <i data-lucide="image" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                                <span class="align-middle">Gambar Hasil</span>
                            </button>
                            <button class="aeroscan-filter-btn px-3 py-1.5 d-flex align-items-center gap-1" id="tab-ocr-btn" style="border-radius: 6px;" title="Aktifkan Ekstraksi Teks (OCR) - Membutuhkan unduhan model ~10MB">
                                <i data-lucide="languages" style="width: 14px; height: 14px;" class="inline-block align-middle"></i>
                                <span class="align-middle">Ekstrak Teks (OCR)</span>
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">Opsional</span>
                            </button>
                        </div>
                        <span class="badge bg-light text-muted border fs-9 py-2 px-3 align-self-end align-self-sm-center" id="output-res-badge">0 x 0 px</span>
                    </div>

                    <div class="aeroscan-canvas-container mt-3" style="min-height: 420px; position: relative;">
                        <!-- Tab View 1: Image -->
                        <div id="output-image-view" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%;">
                            <div class="aeroscan-empty-state" id="out-empty-state">
                                <i data-lucide="file-check" class="aeroscan-empty-state-icon"></i>
                                <p>Dokumen yang diluruskan dan dioptimalkan kualitasnya akan tampil di sini.</p>
                            </div>

                            <div id="output-single-wrapper" class="w-100 h-100 d-flex align-items-center justify-content-center">
                                <canvas id="canvas-output" class="hidden"></canvas>
                            </div>

                            <div id="output-book-wrapper" class="hidden w-100 p-3 row">
                                <div class="col-6 d-flex flex-column align-items-center gap-2">
                                    <span class="fs-9 text-muted fw-bold">Halaman Kiri (1)</span>
                                    <canvas id="canvas-output-left" style="max-height: 380px; max-width: 100%; border-radius: 6px; box-shadow: 0 2px 8px rgba(15,23,42,0.12);"></canvas>
                                </div>
                                <div class="col-6 d-flex flex-column align-items-center gap-2">
                                    <span class="fs-9 text-muted fw-bold">Halaman Kanan (2)</span>
                                    <canvas id="canvas-output-right" style="max-height: 380px; max-width: 100%; border-radius: 6px; box-shadow: 0 2px 8px rgba(15,23,42,0.12);"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Tab View 2: OCR Panel -->
                        <div id="output-ocr-view" style="display: none; position: absolute; inset: 0; width: 100%; height: 100%; flex-direction: column; background: #f8fafc; border-radius: 8px; overflow-y: auto;">

                            <!-- OCR OPT-IN BANNER (shown by default, hidden once OCR is activated) -->
                            <div id="ocr-activate-banner">
                                <i data-lucide="scan-text" style="width: 48px; height: 48px; color: #94a3b8;"></i>
                                <div>
                                    <p class="fw-bold mb-1" style="color: #1e293b; font-size: 0.95rem;">Ekstraksi Teks (OCR) Belum Aktif</p>
                                    <p class="fs-8 text-muted mb-0">Fitur OCR memerlukan unduhan model bahasa (~10MB).<br>Aktifkan hanya jika Anda membutuhkan ekstraksi teks.</p>
                                </div>
                                <button class="btn btn-primary btn-sm px-4 fw-bold" id="ocr-enable-btn">
                                    <i data-lucide="play-circle" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                                    <span class="align-middle">Aktifkan OCR</span>
                                </button>
                            </div>

                            <!-- OCR ACTIVE PANEL (hidden until user activates OCR) -->
                            <div id="ocr-active-panel" class="hidden p-3 d-flex flex-column h-100">
                                <div class="d-flex gap-3 align-items-center mb-3 flex-wrap bg-white p-2 rounded border">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="fs-8 text-muted mb-0">Bahasa OCR:</label>
                                        <select id="ocr-lang-select" class="form-select form-select-sm" style="width: auto;">
                                            <option value="ind" selected>Bahasa Indonesia (ind)</option>
                                            <option value="eng">Bahasa Inggris (eng)</option>
                                        </select>
                                    </div>
                                    <button class="btn btn-primary btn-sm fw-bold px-3 ms-auto" id="run-ocr-btn">
                                        <i data-lucide="play" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                                        <span class="align-middle">Jalankan OCR</span>
                                    </button>
                                </div>

                                <!-- Progress OCR -->
                                <div id="ocr-progress-container" class="hidden mb-3 bg-white p-3 rounded border">
                                    <div class="d-flex justify-content-between fs-9 mb-1">
                                        <span id="ocr-progress-status" style="color: var(--as-primary); font-weight: 600;">Memuat model OCR...</span>
                                        <span id="ocr-progress-percent" class="text-muted fw-bold">0%</span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div id="ocr-progress-bar" class="progress-bar bg-primary" role="progressbar" style="width: 0%;"></div>
                                    </div>
                                </div>

                                <!-- Text Editor Hasil OCR -->
                                <div class="flex-grow-1 mb-3" style="min-height: 220px;">
                                    <textarea id="ocr-result-text" placeholder="Teks hasil OCR akan muncul di sini. Anda bisa langsung mengedit hasilnya..." class="form-control w-100 h-100" style="font-size: 0.85rem; line-height: 1.5; resize: none; font-family: 'Inter', monospace; min-height: 220px;"></textarea>
                                </div>

                                <!-- OCR Action Buttons -->
                                <div class="d-flex gap-2 justify-content-end">
                                    <button class="btn btn-outline-secondary btn-sm" id="ocr-copy-btn">
                                        <i data-lucide="copy" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                                        <span class="align-middle">Salin Teks</span>
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm" id="ocr-download-btn">
                                        <i data-lucide="download-cloud" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                                        <span class="align-middle">Unduh .txt</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Downloader & Compression Stats -->
                    <div class="row align-items-center mt-3">
                        <div class="col-md-6">
                            <!-- Download Current Scan -->
                            <button class="btn btn-primary w-100 fw-bold disabled" id="download-btn">
                                <i data-lucide="download" style="width: 16px; height: 16px;" class="me-1 inline-block align-middle"></i>
                                <span class="align-middle">Unduh Hasil Scan (.jpg)</span>
                            </button>
                            <!-- Book dual download buttons -->
                            <div id="book-download-group" class="hidden d-flex gap-2 w-100">
                                <button class="btn btn-primary flex-fill fw-bold disabled" id="download-left-btn" style="font-size: 0.8rem;">
                                    <i data-lucide="download" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                                    Unduh Hal Kiri (1)
                                </button>
                                <button class="btn btn-danger flex-fill fw-bold disabled" id="download-right-btn" style="font-size: 0.8rem;">
                                    <i data-lucide="download" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i>
                                    Unduh Hal Kanan (2)
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="aeroscan-stats-container mt-0">
                                <div class="aeroscan-stat-box">
                                    <div class="aeroscan-stat-label">Ukuran Asli</div>
                                    <div class="aeroscan-stat-value" id="orig-size">-</div>
                                </div>
                                <div class="aeroscan-stat-box">
                                    <div class="aeroscan-stat-label" id="comp-size-label">Ukuran Hasil</div>
                                    <div class="aeroscan-stat-value" id="comp-size">-</div>
                                </div>
                                <div class="aeroscan-stat-box">
                                    <div class="aeroscan-stat-label" id="comp-ratio-label">Rasio Hemat</div>
                                    <div class="aeroscan-stat-value savings" id="comp-ratio">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL 3: DAFTAR BATCH HALAMAN PDF -->
                <div class="aeroscan-card" id="pdf-manager-panel">
                    <div class="aeroscan-card-title d-flex justify-content-between align-items-center">
                        <span class="d-flex align-items-center gap-2">
                            <span style="background: var(--as-primary); width: 8px; height: 8px; border-radius: 50%;"></span>
                            Daftar Antrean Halaman PDF
                        </span>
                        <span class="badge bg-light text-muted border fs-9" id="queue-total-badge">0 Halaman</span>
                    </div>

                    <div id="pdf-thumbnails-container" class="p-3 d-flex gap-3 overflow-x-auto align-items-center rounded border" style="background: #f8fafc; min-height: 180px; min-width: 0;">
                        <div id="pdf-queue-empty-state" class="w-100 text-center text-muted fs-8 py-4">
                            <i data-lucide="list-ordered" style="width: 24px; height: 24px; opacity: 0.4;" class="mb-2"></i>
                            <p class="mb-0">Belum ada halaman disimpan. Unggah beberapa foto sekaligus (batch) atau tambahkan halaman aktif di atas.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11000; pointer-events: none;"></div>

    <!-- FLOATING MAGNIFIER (KACA PEMBESAR) -->
    <div id="magnifier" style="position: fixed; width: 120px; height: 120px; border-radius: 50%; border: 3px solid var(--as-primary); background: #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.6); z-index: 99999; pointer-events: none; overflow: hidden; display: none; transform: translate(-50%, -100%) translateY(-20px);">
        <canvas id="magnifier-canvas" width="120" height="120" style="width: 120px; height: 120px; border-radius: 50%;"></canvas>
    </div>

</div>

<!-- OPENCV.JS LOADER CONFIG -->
<script>
    window.Module = {
        onRuntimeInitialized: function () {
            console.log("OpenCV.js siap digunakan.");
            if (typeof window.onOpenCvReady === 'function') {
                window.onOpenCvReady();
            }
        }
    };
</script>
<!-- Memuat OpenCV secara asynchronous -->
<script async src="https://docs.opencv.org/4.5.4/opencv.js" type="text/javascript"></script>

<!-- JAVASCRIPT LOGIC INTEGRATED PURE CLIENT-SIDE -->
<script>
(function() {
    // ── LUCIDE SAFE WRAPPER ─────────────────────────────────────────────
    // Toast queue: if Lucide CDN hasn't loaded when a toast is requested,
    // the message is buffered and flushed as soon as Lucide becomes available.
    let _toastQueue = [];
    let _lucideReady = false;

    function safeCreateIcons(attrs = null) {
        const lib = (typeof lucide !== 'undefined') ? lucide
                  : (typeof window.lucide !== 'undefined') ? window.lucide
                  : null;
        if (lib) {
            attrs ? lib.createIcons(attrs) : lib.createIcons();
        }
        // silently skip — no warn needed; Lucide fires onload callback when ready
    }
    window.safeCreateIcons = safeCreateIcons;

    // Called from Lucide's onload attribute (see <script> tag above)
    window._flushToastQueue = function() {
        _lucideReady = true;
        _toastQueue.forEach(function(item) { _renderToast(item.message, item.type); });
        _toastQueue = [];
    };

    window.onerror = function (message, source, lineno, colno, error) {
        const errStr = `Aeroscan JS Error: ${message} (Line ${lineno}:${colno})`;
        if (typeof showToast === 'function') {
            showToast(errStr, 'error');
        }
        console.error(errStr, error);
        return false;
    };

    window.addEventListener('unhandledrejection', function (event) {
        const errStr = `Aeroscan Unhandled Promise: ${event.reason}`;
        if (typeof showToast === 'function') {
            showToast(errStr, 'error');
        }
        console.error(event.reason);
    });

    // STATE & GLOBAL VARIABLES
    let resizeHandler = null;
    let panMouseMoveHandler = null;
    let panMouseUpHandler = null;

    let originalMat = null;     // OpenCV Mat untuk gambar asli
    let originalFileSize = 0;   // Ukuran berkas asli dalam bytes
    let activeFilter = 'color'; // Mode filter aktif (Default: color)
    let rotationAngle = 0;      // Sudut rotasi (0, 90, 180, 270)
    let isBookMode = false;     // Status mode buku (2 halaman)
    let hasAutoRotated = false; // Mencegah loop auto-rotate tak terbatas per file
    let spineX = 1.0;           // Posisi pembatas lipatan buku
    let isSpineActive = false;  // Status pembatas buku di halaman tunggal
    let pdfPageQueue = [];      // Antrean halaman PDF gabungan
    let editingPageId = null;   // ID halaman yang sedang diedit (null = tambah baru)

    // Zoom & Pan variables
    let zoomScale = 1.0;
    let panX = 0;
    let panY = 0;
    let isPanning = false;
    let panStartX = 0;
    let panStartY = 0;

    // Blobs stores for download
    let singleBlob = null;
    let leftBlob = null;
    let rightBlob = null;

    // Camera scanner stream variables
    let streamInstance = null;
    let cameraActive = false;
    let prevCamPoints = null;
    let stableFrameCount = 0;

    // Low-res canvas untuk real-time camera calculations (willReadFrequently to optimize readback)
    let cameraCanvas = document.createElement('canvas');
    cameraCanvas.width = 480;
    cameraCanvas.height = 360;
    let cameraCtx = cameraCanvas.getContext('2d', { willReadFrequently: true });

    // Koordinat 4 sudut dokumen halaman tunggal / halaman kiri (normalized 0.0 to 1.0)
    let corners = {
        tl: { x: 0.1, y: 0.1 },
        tr: { x: 0.9, y: 0.1 },
        br: { x: 0.9, y: 0.9 },
        bl: { x: 0.1, y: 0.9 }
    };

    // Koordinat 4 sudut halaman kanan (hanya untuk Mode Buku)
    let cornersRight = {
        tl: { x: 0.55, y: 0.1 },
        tr: { x: 0.95, y: 0.1 },
        br: { x: 0.95, y: 0.9 },
        bl: { x: 0.55, y: 0.9 }
    };

    // DOM ELEMENTS
    const fileInput = document.getElementById('file-input');
    const dropZone = document.getElementById('drop-zone');
    const demoBtn = document.getElementById('demo-btn');
    const rotateBtn = document.getElementById('rotate-btn');
    const resetCornersBtn = document.getElementById('reset-corners-btn');
    const downloadBtn = document.getElementById('download-btn');
    const qualitySlider = document.getElementById('quality-slider');
    const brightnessSlider = document.getElementById('brightness-slider');
    const contrastSlider = document.getElementById('contrast-slider');
    const sharpenSlider = document.getElementById('sharpen-slider');
    const resPresetSelect = document.getElementById('resolution-preset');
    const toggleSpineBtn = document.getElementById('toggle-spine-btn');

    // PDF DOM
    const pdfFilenameInput = document.getElementById('pdf-filename');
    const addToPdfBtn = document.getElementById('add-to-pdf-btn');
    const generatePdfBtn = document.getElementById('generate-pdf-btn');
    const pdfPagesCountSpan = document.getElementById('pdf-pages-count');
    const pdfThumbnailsContainer = document.getElementById('pdf-thumbnails-container');
    const queueTotalBadge = document.getElementById('queue-total-badge');

    // Zoom & Magnifier DOM
    const zoomInBtn = document.getElementById('zoom-in-btn');
    const zoomOutBtn = document.getElementById('zoom-out-btn');
    const zoomResetBtn = document.getElementById('zoom-reset-btn');
    const zoomPercentSpan = document.getElementById('zoom-percent');
    const magnifierEl = document.getElementById('magnifier');
    const magnifierCanvas = document.getElementById('magnifier-canvas');
    const originalCanvasContainer = document.getElementById('original-canvas-container');

    // OCR DOM
    const tabImageBtn = document.getElementById('tab-image-btn');
    const tabOcrBtn = document.getElementById('tab-ocr-btn');
    const outputImageView = document.getElementById('output-image-view');
    const outputOcrView = document.getElementById('output-ocr-view');
    const ocrLangSelect = document.getElementById('ocr-lang-select');
    const runOcrBtn = document.getElementById('run-ocr-btn');
    const ocrProgressContainer = document.getElementById('ocr-progress-container');
    const ocrProgressStatus = document.getElementById('ocr-progress-status');
    const ocrProgressPercent = document.getElementById('ocr-progress-percent');
    const ocrProgressBar = document.getElementById('ocr-progress-bar');
    const ocrResultText = document.getElementById('ocr-result-text');
    const ocrCopyBtn = document.getElementById('ocr-copy-btn');
    const ocrDownloadBtn = document.getElementById('ocr-download-btn');

    const qualityVal = document.getElementById('quality-val');
    const brightnessVal = document.getElementById('brightness-val');
    const contrastVal = document.getElementById('contrast-val');
    const sharpenVal = document.getElementById('sharpen-val');

    const statusDot = document.getElementById('status-dot');
    const statusText = document.getElementById('status-text');
    const loadingOverlay = document.getElementById('loading-overlay');
    const loaderStatus = document.getElementById('loader-status');
    const filterBtns = document.querySelectorAll('.aeroscan-filter-btn[data-mode]');

    const modeSingleBtn = document.getElementById('mode-single-btn');
    const modeBookBtn = document.getElementById('mode-book-btn');
    const bookDownloadGroup = document.getElementById('book-download-group');
    const downloadLeftBtn = document.getElementById('download-left-btn');
    const downloadRightBtn = document.getElementById('download-right-btn');

    // Camera Scan
    const cameraBtn = document.getElementById('camera-btn');
    const cameraModal = document.getElementById('camera-modal');
    const videoStream = document.getElementById('video-stream');
    const cameraOverlayCanvas = document.getElementById('camera-overlay-canvas');
    const closeCameraBtn = document.getElementById('close-camera-btn');
    const manualCaptureBtn = document.getElementById('manual-capture-btn');
    const cameraStatusOverlay = document.getElementById('camera-status-overlay');

    const origEmptyState = document.getElementById('orig-empty-state');
    const outEmptyState = document.getElementById('out-empty-state');
    const origWrapper = document.getElementById('original-wrapper');
    const canvasOriginal = document.getElementById('canvas-original');
    const canvasOutput = document.getElementById('canvas-output');
    const canvasOutputLeft = document.getElementById('canvas-output-left');
    const canvasOutputRight = document.getElementById('canvas-output-right');

    const outputSingleWrapper = document.getElementById('output-single-wrapper');
    const outputBookWrapper = document.getElementById('output-book-wrapper');
    const docOverlayRight = document.getElementById('doc-overlay-right');
    const rightHandles = document.querySelectorAll('.right-handle');
    const spineDivider = document.getElementById('spine-divider');

    // 1. INITIALIZE & CHECKS
    function initAeroScan() {
        if (window._aeroScanCleanup) {
            window._aeroScanCleanup();
        }

        safeCreateIcons();
        initDragAndDropHandlers();
        initParametersListeners();
        initDragCorners();
        initModeSelector();
        initDownloadListeners();
        initCameraScanner();
        initSourceModal();
        initSpineDividerDrag();
        initPdfListeners();
        initZoomAndPan();
        initOcrListeners();

        resizeHandler = () => {
            if (originalMat) {
                updateSvgOverlay();
            }
        };
        window.addEventListener('resize', resizeHandler);

        window._aeroScanCleanup = function() {
            stopCameraScan();
            if (resizeHandler) window.removeEventListener('resize', resizeHandler);
            if (panMouseMoveHandler) window.removeEventListener('mousemove', panMouseMoveHandler);
            if (panMouseUpHandler) window.removeEventListener('mouseup', panMouseUpHandler);
            window._aeroScanCleanup = null;
        };
    }

    // Jalankan inisialisasi secara langsung saat berkas dimuat
    initAeroScan();

    window.onOpenCvReady = function() {
        const _dot     = document.getElementById('status-dot');
        const _text    = document.getElementById('status-text');
        const _overlay = document.getElementById('loading-overlay');
        if (_dot)     _dot.className = "aeroscan-status-dot ready";
        if (_text)    _text.innerText = "Sistem Siap";
        if (_overlay) _overlay.classList.add('fade-out');
        showToast('OpenCV.js berhasil dimuat. Siap memindai!', 'success');
    };

    // 2. PARAMS & MODE SELECTOR
    function initModeSelector() {
        modeSingleBtn.addEventListener('click', () => {
            if (isBookMode) {
                isBookMode = false;
                modeSingleBtn.classList.add('active');
                modeBookBtn.classList.remove('active');
                toggleSpineBtn.classList.remove('hidden');

                document.getElementById('tip-text').innerHTML = `<strong>Tips:</strong> Geser titik penanda berwarna biru (cyan) pada sudut dokumen di atas jika hasil potong otomatis kurang presisi.`;
                document.getElementById('comp-size-label').innerText = "Ukuran Hasil";
                document.getElementById('comp-ratio-label').innerText = "Rasio Hemat";

                rightHandles.forEach(h => h.classList.add('hidden'));
                docOverlayRight.classList.add('hidden');
                outputSingleWrapper.classList.remove('hidden');
                outputBookWrapper.classList.add('hidden');

                downloadBtn.classList.remove('hidden');
                bookDownloadGroup.classList.add('hidden');

                if (isSpineActive) {
                    spineDivider.classList.remove('hidden');
                } else {
                    spineDivider.classList.add('hidden');
                    spineX = 1.0;
                }

                if (originalMat) {
                    statusDot.className = "aeroscan-status-dot processing";
                    statusText.innerText = "Memproses...";
                    setTimeout(() => {
                        detectCorners(originalMat);
                        processAndCompress();
                        statusDot.className = "aeroscan-status-dot ready";
                        statusText.innerText = "Sistem Siap";
                    }, 15);
                }
            }
        });

        modeBookBtn.addEventListener('click', () => {
            if (!isBookMode) {
                isBookMode = true;
                modeBookBtn.classList.add('active');
                modeSingleBtn.classList.remove('active');
                toggleSpineBtn.classList.add('hidden');

                document.getElementById('tip-text').innerHTML = `<strong>Mode Buku:</strong> Geser pin <strong style="color:var(--accent-cyan)">Cyan</strong> untuk halaman kiri, dan pin <strong style="color:var(--accent-pink)">Pink/Magenta</strong> untuk halaman kanan.`;
                document.getElementById('comp-size-label').innerText = "Ukuran Total";
                document.getElementById('comp-ratio-label').innerText = "Rasio Hemat";

                rightHandles.forEach(h => h.classList.remove('hidden'));
                docOverlayRight.classList.remove('hidden');
                outputSingleWrapper.classList.add('hidden');
                outputBookWrapper.classList.remove('hidden');

                downloadBtn.classList.add('hidden');
                bookDownloadGroup.classList.remove('hidden');

                if (spineX > 0.95) spineX = 0.5;
                spineDivider.classList.remove('hidden');

                if (originalMat) {
                    statusDot.className = "aeroscan-status-dot processing";
                    statusText.innerText = "Memproses...";
                    setTimeout(() => {
                        detectCorners(originalMat);
                        processAndCompress();
                        statusDot.className = "aeroscan-status-dot ready";
                        statusText.innerText = "Sistem Siap";
                    }, 15);
                }
            }
        });

        toggleSpineBtn.addEventListener('click', () => {
            if (isBookMode) return;

            isSpineActive = !isSpineActive;
            if (isSpineActive) {
                toggleSpineBtn.classList.add('active');
                toggleSpineBtn.innerHTML = `<i data-lucide="split" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i><span class="align-middle">Nonaktifkan Pembatas Buku</span>`;
                spineDivider.classList.remove('hidden');
                if (spineX > 0.95) spineX = 0.5;
                showToast('Pembatas buku diaktifkan. Seret garis kuning ke lipatan buku.', 'info');
            } else {
                toggleSpineBtn.classList.remove('active');
                toggleSpineBtn.innerHTML = `<i data-lucide="split" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i><span class="align-middle">Aktifkan Pembatas Buku</span>`;
                spineDivider.classList.add('hidden');
                spineX = 1.0;
                showToast('Pembatas buku dinonaktifkan.', 'info');
            }
            safeCreateIcons();

            if (originalMat) {
                statusDot.className = "aeroscan-status-dot processing";
                statusText.innerText = "Memproses...";
                setTimeout(() => {
                    detectCorners(originalMat);
                    processAndCompress();
                    statusDot.className = "aeroscan-status-dot ready";
                    statusText.innerText = "Sistem Siap";
                }, 15);
            }
        });
    }

    // ── DEBOUNCE HELPER (prevents concurrent OpenCV calls from rapid slider moves)
    let _processDebounceTimer = null;
    function debouncedProcess(isPreview = false, delay = 80) {
        clearTimeout(_processDebounceTimer);
        _processDebounceTimer = setTimeout(() => processAndCompress(isPreview), delay);
    }

    function initParametersListeners() {
        qualitySlider.addEventListener('input', (e) => {
            qualityVal.innerText = parseFloat(e.target.value).toFixed(2);
            debouncedProcess(true);
        });
        qualitySlider.addEventListener('change', () => {
            debouncedProcess(false);
        });

        sharpenSlider.addEventListener('input', (e) => {
            sharpenVal.innerText = parseFloat(e.target.value).toFixed(1);
            debouncedProcess(true);
        });
        sharpenSlider.addEventListener('change', () => {
            debouncedProcess(false);
        });

        brightnessSlider.addEventListener('input', (e) => {
            brightnessVal.innerText = e.target.value;
            debouncedProcess(true);
        });
        brightnessSlider.addEventListener('change', () => {
            debouncedProcess(false);
        });

        contrastSlider.addEventListener('input', (e) => {
            contrastVal.innerText = parseFloat(e.target.value).toFixed(2);
            debouncedProcess(true);
        });
        contrastSlider.addEventListener('change', () => {
            debouncedProcess(false);
        });

        resPresetSelect.addEventListener('change', () => {
            const val = resPresetSelect.value;
            let targetW = 1200;
            let targetH = 1842;
            if (val === 'hd') {
                targetW = 1600;
                targetH = 2456;
            } else if (val === 'super-hd') {
                targetW = 2400;
                targetH = 3684;
            }
            document.getElementById('output-res-badge').innerText = `${targetW} x ${targetH} px`;
            statusDot.className = "aeroscan-status-dot processing";
            statusText.innerText = "Memproses...";
            setTimeout(() => {
                processAndCompress();
                statusDot.className = "aeroscan-status-dot ready";
                statusText.innerText = "Sistem Siap";
            }, 15);
        });

        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeFilter = btn.dataset.mode;
                statusDot.className = "aeroscan-status-dot processing";
                statusText.innerText = "Memproses...";
                setTimeout(() => {
                    processAndCompress();
                    statusDot.className = "aeroscan-status-dot ready";
                    statusText.innerText = "Sistem Siap";
                }, 15);
            });
        });

        rotateBtn.addEventListener('click', rotateInputImage);
        resetCornersBtn.addEventListener('click', () => {
            if (originalMat) {
                statusDot.className = "aeroscan-status-dot processing";
                statusText.innerText = "Memproses...";
                setTimeout(() => {
                    detectCorners(originalMat);
                    processAndCompress();
                    statusDot.className = "aeroscan-status-dot ready";
                    statusText.innerText = "Sistem Siap";
                }, 15);
            }
        });
    }

    // 3. SHUTTER SOUND SYNTHESIZER
    function playShutterSound() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const bufferSize = audioCtx.sampleRate * 0.12;
            const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = Math.random() * 2 - 1;
            }
            const noise = audioCtx.createBufferSource();
            noise.buffer = buffer;

            const filter = audioCtx.createBiquadFilter();
            filter.type = 'bandpass';
            filter.frequency.value = 1200;

            const gainNode = audioCtx.createGain();
            gainNode.gain.setValueAtTime(0.8, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.11);

            noise.connect(filter);
            filter.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            noise.start();
        } catch (e) {
            console.log("Audio snap failed", e);
        }
    }

    // 4. CAMERA SCANNER HANDLERS
    function initCameraScanner() {
        // camera-btn opens source-selection modal first
        cameraBtn.addEventListener('click', () => {
            const modal = document.getElementById('source-modal');
            if (modal) { modal.style.display = 'flex'; safeCreateIcons(); }
        });
        closeCameraBtn.addEventListener('click', stopCameraScan);
        manualCaptureBtn.addEventListener('click', captureFrameFromVideo);
    }

    function initSourceModal() {
        const modal      = document.getElementById('source-modal');
        const uploadBtn  = document.getElementById('source-upload-btn');
        const camBtn     = document.getElementById('source-camera-btn');
        const cancelBtn  = document.getElementById('source-cancel-btn');
        const fileInput  = document.getElementById('file-input');

        function closeModal() { if (modal) modal.style.display = 'none'; }

        if (uploadBtn) uploadBtn.addEventListener('click', () => { closeModal(); if (fileInput) fileInput.click(); });
        if (camBtn)    camBtn.addEventListener('click',    () => { closeModal(); startCameraScan(); });
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (modal)     modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    }

    async function startCameraScan() {
        try {
            statusDot.className = "aeroscan-status-dot processing";
            statusText.innerText = "Mengakses Kamera...";

            streamInstance = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } }
            });
            videoStream.srcObject = streamInstance;
            cameraModal.style.display = 'flex';
            cameraActive = true;
            stableFrameCount = 0;
            prevCamPoints = null;

            videoStream.onloadedmetadata = () => {
                cameraOverlayCanvas.width = videoStream.clientWidth;
                cameraOverlayCanvas.height = videoStream.clientHeight;
                requestAnimationFrame(cameraProcessLoop);
            };
            showToast('Kamera diaktifkan. Tahan stabil di atas dokumen.', 'info');
        } catch (err) {
            console.error(err);
            showToast('Gagal mengakses kamera: ' + err.message, 'error');
            statusDot.className = "aeroscan-status-dot ready";
            statusText.innerText = "Sistem Siap";
        }
    }

    function stopCameraScan() {
        cameraActive = false;
        if (streamInstance) {
            streamInstance.getTracks().forEach(track => track.stop());
            streamInstance = null;
        }
        videoStream.srcObject = null;
        cameraModal.style.display = 'none';
        statusDot.className = "aeroscan-status-dot ready";
        statusText.innerText = "Sistem Siap";
    }

    function cameraProcessLoop() {
        if (!cameraActive || !videoStream.videoWidth) return;

        const clientW = videoStream.clientWidth;
        const clientH = videoStream.clientHeight;
        if (cameraOverlayCanvas.width !== clientW || cameraOverlayCanvas.height !== clientH) {
            cameraOverlayCanvas.width = clientW;
            cameraOverlayCanvas.height = clientH;
        }

        cameraCtx.drawImage(videoStream, 0, 0, 480, 360);

        let src = null;
        let pts = null;
        try {
            src = cv.imread(cameraCanvas);
            pts = detectCornersForMat(src, 0);
        } catch (e) {
            console.error("Camera frame evaluation failed", e);
        } finally {
            if (src) src.delete();
        }

        let normalizedPts = null;
        if (pts) {
            normalizedPts = pts.map(p => ({ x: p.x / 480, y: p.y / 360 }));

            if (prevCamPoints) {
                let shift = 0;
                for (let i = 0; i < 4; i++) {
                    shift += Math.hypot(normalizedPts[i].x - prevCamPoints[i].x, normalizedPts[i].y - prevCamPoints[i].y);
                }

                if (shift < 0.05) {
                    stableFrameCount++;
                    if (stableFrameCount >= 25) {
                        captureFrameFromVideo();
                        return;
                    }
                } else {
                    stableFrameCount = 0;
                }
            } else {
                stableFrameCount = 0;
            }
            prevCamPoints = normalizedPts;
        } else {
            stableFrameCount = 0;
            prevCamPoints = null;
        }

        const overlayCtx = cameraOverlayCanvas.getContext('2d');
        overlayCtx.clearRect(0, 0, clientW, clientH);

        if (normalizedPts) {
            overlayCtx.strokeStyle = '#22c55e';
            overlayCtx.lineWidth = 4;
            overlayCtx.fillStyle = 'rgba(34, 197, 94, 0.15)';
            overlayCtx.beginPath();
            overlayCtx.moveTo(normalizedPts[0].x * clientW, normalizedPts[0].y * clientH);
            overlayCtx.lineTo(normalizedPts[1].x * clientW, normalizedPts[1].y * clientH);
            overlayCtx.lineTo(normalizedPts[2].x * clientW, normalizedPts[2].y * clientH);
            overlayCtx.lineTo(normalizedPts[3].x * clientW, normalizedPts[3].y * clientH);
            overlayCtx.closePath();
            overlayCtx.stroke();
            overlayCtx.fill();

            const centerX = clientW / 2;
            const centerY = clientH / 2;
            const progress = Math.min(stableFrameCount / 25, 1.0);

            overlayCtx.beginPath();
            overlayCtx.arc(centerX, centerY, 40, 0, 2 * Math.PI);
            overlayCtx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
            overlayCtx.lineWidth = 6;
            overlayCtx.stroke();

            overlayCtx.beginPath();
            overlayCtx.arc(centerX, centerY, 40, -Math.PI / 2, -Math.PI / 2 + (progress * 2 * Math.PI));
            overlayCtx.strokeStyle = 'var(--accent-cyan)';
            overlayCtx.lineWidth = 6;
            overlayCtx.stroke();

            cameraStatusOverlay.innerText = "Tahan Kamera Diam... " + Math.round(progress * 100) + "%";
        } else {
            cameraStatusOverlay.innerText = "Mendeteksi Dokumen...";
        }

        if (cameraActive) {
            requestAnimationFrame(cameraProcessLoop);
        }
    }

    function captureFrameFromVideo() {
        if (!videoStream || !cameraActive) return;

        playShutterSound();

        const capCanvas = document.createElement('canvas');
        capCanvas.width = videoStream.videoWidth;
        capCanvas.height = videoStream.videoHeight;
        const ctx = capCanvas.getContext('2d');
        ctx.drawImage(videoStream, 0, 0);

        canvasOriginal.width = videoStream.videoWidth;
        canvasOriginal.height = videoStream.videoHeight;
        const oCtx = canvasOriginal.getContext('2d');
        oCtx.drawImage(capCanvas, 0, 0);

        document.getElementById('orig-res-badge').innerText = `${videoStream.videoWidth} x ${videoStream.videoHeight} px`;

        if (originalMat) originalMat.delete();

        hasAutoRotated = false;
        originalMat = cv.imread(canvasOriginal);
        applyAutoLighting();
        originalFileSize = Math.round(videoStream.videoWidth * videoStream.videoHeight * 0.15);
        document.getElementById('orig-size').innerText = `${(originalFileSize / (1024 * 1024)).toFixed(2)} MB`;

        stopCameraScan();

        fitOriginalCanvas(videoStream.videoWidth, videoStream.videoHeight);

        origEmptyState.classList.add('hidden');
        outEmptyState.classList.add('hidden');
        origWrapper.classList.remove('hidden');

        if (!isBookMode) {
            canvasOutput.classList.remove('hidden');
        } else {
            canvasOutputLeft.classList.remove('hidden');
            canvasOutputRight.classList.remove('hidden');
        }

        rotateBtn.classList.remove('disabled');
        resetCornersBtn.classList.remove('disabled');

        runSpineDetection(originalMat);
        detectCorners(originalMat);
        processAndCompress();

        showToast('Gambar berhasil diambil otomatis!', 'success');
    }

    // 5. DOWNLOAD TRIGGERS
    function initDownloadListeners() {
        downloadBtn.addEventListener('click', () => {
            if (singleBlob) {
                downloadBlob(singleBlob, `scan_folio_${Date.now()}.jpg`);
                showToast('Unduhan halaman tunggal berhasil dimulai.', 'success');
            }
        });

        downloadLeftBtn.addEventListener('click', () => {
            if (leftBlob) {
                downloadBlob(leftBlob, `scan_folio_hal1_kiri_${Date.now()}.jpg`);
                showToast('Unduhan Halaman 1 (Kiri) berhasil dimulai.', 'success');
            }
        });

        downloadRightBtn.addEventListener('click', () => {
            if (rightBlob) {
                downloadBlob(rightBlob, `scan_folio_hal2_kanan_${Date.now()}.jpg`);
                showToast('Unduhan Halaman 2 (Kanan) berhasil dimulai.', 'success');
            }
        });
    }

    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 2000);
    }

    // 6. TOAST SYSTEM
    // _renderToast: physically creates the DOM toast element.
    // Only call this when Lucide is already loaded.
    function _renderToast(message, type) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `aeroscan-toast toast-${type}`;

        let icon = 'info';
        if (type === 'success') icon = 'check-circle-2';
        if (type === 'error')   icon = 'alert-triangle';

        toast.innerHTML = `
            <i data-lucide="${icon}" style="width: 18px; height: 18px;"></i>
            <span>${message}</span>
        `;

        container.appendChild(toast);
        safeCreateIcons({ attrs: { class: 'lucide-icon' } });

        setTimeout(() => toast.classList.add('show'), 50);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }

    // showToast: queue-aware. If Lucide isn't ready yet, buffer the toast.
    // It will be flushed automatically by window._flushToastQueue (called from
    // Lucide's onload attribute).
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return; // DOM not ready at all — silently skip

        const lucideLib = (typeof lucide !== 'undefined') ? lucide
                        : (typeof window.lucide !== 'undefined') ? window.lucide
                        : null;

        if (lucideLib) {
            _renderToast(message, type);
        } else {
            // Queue for later — Lucide onload will call _flushToastQueue
            _toastQueue.push({ message, type });
        }
    }

    // 7. FILE & DRAG-DROP HANDLERS
    function initDragAndDropHandlers() {
        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 1) {
                handleUploadedMultipleFiles(e.target.files);
            } else if (e.target.files.length === 1) {
                handleUploadedFile(e.target.files[0]);
            }
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 1) {
                handleUploadedMultipleFiles(e.dataTransfer.files);
            } else if (e.dataTransfer.files.length === 1) {
                handleUploadedFile(e.dataTransfer.files[0]);
            }
        });

        demoBtn.addEventListener('click', loadDemoDocument);
    }

    function applyAutoLighting() {
        if (!originalMat) return;
        try {
            let tempGray = new cv.Mat();
            cv.cvtColor(originalMat, tempGray, cv.COLOR_RGBA2GRAY);
            let meanScalar = cv.mean(tempGray);
            const meanVal = meanScalar[0];
            tempGray.delete();

            let autoBrightness = Math.round((160 - meanVal) * 0.45);
            autoBrightness = Math.max(-25, Math.min(autoBrightness, 30));

            brightnessSlider.value = autoBrightness;
            brightnessVal.innerText = autoBrightness;

            let autoContrast = 1.0;
            if (meanVal < 130) {
                autoContrast = 1.15;
            } else if (meanVal > 190) {
                autoContrast = 0.95;
            }
            contrastSlider.value = autoContrast;
            contrastVal.innerText = autoContrast.toFixed(2);
        } catch (e) {
            console.error("Auto lighting failed:", e);
        }
    }

    // 8. HANDLING FILE LOAD
    function handleUploadedFile(file) {
        if (!file.type.match('image.*')) {
            showToast('Berkas harus berupa gambar!', 'error');
            return;
        }

        originalFileSize = file.size;
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        document.getElementById('orig-size').innerText = `${sizeMB} MB`;

        statusDot.className = "aeroscan-status-dot processing";
        statusText.innerText = "Memproses Gambar...";

        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                canvasOriginal.width = img.width;
                canvasOriginal.height = img.height;
                const ctx = canvasOriginal.getContext('2d');
                ctx.drawImage(img, 0, 0);

                document.getElementById('orig-res-badge').innerText = `${img.width} x ${img.height} px`;

                if (originalMat) originalMat.delete();

                rotationAngle = 0;
                hasAutoRotated = false;
                originalMat = cv.imread(canvasOriginal);
                applyAutoLighting();
                fitOriginalCanvas(img.width, img.height);

                origEmptyState.classList.add('hidden');
                outEmptyState.classList.add('hidden');
                origWrapper.classList.remove('hidden');

                if (!isBookMode) {
                    canvasOutput.classList.remove('hidden');
                } else {
                    canvasOutputLeft.classList.remove('hidden');
                    canvasOutputRight.classList.remove('hidden');
                }

                rotateBtn.classList.remove('disabled');
                resetCornersBtn.classList.remove('disabled');

                runSpineDetection(originalMat);
                detectCorners(originalMat);
                processAndCompress();

                statusDot.className = "aeroscan-status-dot ready";
                statusText.innerText = "Sistem Siap";
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    async function handleUploadedMultipleFiles(files) {
        statusDot.className = "aeroscan-status-dot processing";
        statusText.innerText = `Memproses 1/${files.length}...`;
        showToast(`Mulai memproses ${files.length} berkas secara otomatis...`, 'info');

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            statusText.innerText = `Memproses ${i + 1}/${files.length}...`;

            await new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = new Image();
                    img.onload = function () {
                        canvasOriginal.width = img.width;
                        canvasOriginal.height = img.height;
                        const ctx = canvasOriginal.getContext('2d');
                        ctx.drawImage(img, 0, 0);

                        if (originalMat) originalMat.delete();

                        rotationAngle = 0;
                        hasAutoRotated = false;
                        originalMat = cv.imread(canvasOriginal);
                        applyAutoLighting();

                        runSpineDetection(originalMat);
                        detectCorners(originalMat);
                        processAndCompressBatchHelper(file.name, e.target.result, resolve);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        if (files.length > 0) {
            const lastFile = files[files.length - 1];
            handleUploadedFile(lastFile);
        }

        statusDot.className = "aeroscan-status-dot ready";
        statusText.innerText = "Sistem Siap";
        showToast(`Selesai memproses ${files.length} berkas. Antrean PDF diperbarui!`, 'success');
    }

    function processAndCompressBatchHelper(filename, originalDataURL, resolve) {
        const imgW = originalMat.cols;
        const imgH = originalMat.rows;
        const quality = parseFloat(qualitySlider.value);
        const resPreset = resPresetSelect.value;

        let targetW = 1200;
        let targetH = 1842;
        if (resPreset === 'hd') {
            targetW = 1600;
            targetH = 2456;
        } else if (resPreset === 'super-hd') {
            targetW = 2400;
            targetH = 3684;
        }

        if (!isBookMode) {
            const pts = {
                tl: { x: corners.tl.x * imgW, y: corners.tl.y * imgH },
                tr: { x: corners.tr.x * imgW, y: corners.tr.y * imgH },
                br: { x: corners.br.x * imgW, y: corners.br.y * imgH },
                bl: { x: corners.bl.x * imgW, y: corners.bl.y * imgH }
            };

            warpAndFilterMat(originalMat, pts, 'canvas-output', targetW, targetH);

            getCanvasBlob(canvasOutput, quality, function (blob) {
                const dataUrl = canvasOutput.toDataURL('image/jpeg', quality);

                pdfPageQueue.push({
                    id: Date.now() + Math.random().toString(36).substr(2, 9),
                    filename: filename,
                    originalSrc: originalDataURL,
                    corners: JSON.parse(JSON.stringify(corners)),
                    cornersRight: JSON.parse(JSON.stringify(cornersRight)),
                    isBookMode: false,
                    rotationAngle: rotationAngle,
                    spineX: spineX,
                    isSpineActive: isSpineActive,
                    activeFilter: activeFilter,
                    brightness: parseInt(brightnessSlider.value),
                    contrast: parseFloat(contrastSlider.value),
                    sharpen: parseFloat(sharpenSlider.value),
                    resolutionPreset: resPreset,
                    jpegQuality: quality,
                    canvasDataURL: dataUrl,
                    blobSize: blob.size
                });

                renderThumbnails();
                resolve();
            });
        } else {
            const ptsL = {
                tl: { x: corners.tl.x * imgW, y: corners.tl.y * imgH },
                tr: { x: corners.tr.x * imgW, y: corners.tr.y * imgH },
                br: { x: corners.br.x * imgW, y: corners.br.y * imgH },
                bl: { x: corners.bl.x * imgW, y: corners.bl.y * imgH }
            };

            const ptsR = {
                tl: { x: cornersRight.tl.x * imgW, y: cornersRight.tl.y * imgH },
                tr: { x: cornersRight.tr.x * imgW, y: cornersRight.tr.y * imgH },
                br: { x: cornersRight.br.x * imgW, y: cornersRight.br.y * imgH },
                bl: { x: cornersRight.bl.x * imgW, y: cornersRight.bl.y * imgH }
            };

            warpAndFilterMat(originalMat, ptsL, 'canvas-output-left', targetW, targetH);
            warpAndFilterMat(originalMat, ptsR, 'canvas-output-right', targetW, targetH);

            getCanvasBlob(canvasOutputLeft, quality, function (blobL) {
                const dataUrlL = canvasOutputLeft.toDataURL('image/jpeg', quality);

                getCanvasBlob(canvasOutputRight, quality, function (blobR) {
                    const dataUrlR = canvasOutputRight.toDataURL('image/jpeg', quality);

                    const baseId = Date.now();
                    pdfPageQueue.push({
                        id: baseId + '_L',
                        filename: filename + ' (Kiri)',
                        originalSrc: originalDataURL,
                        corners: JSON.parse(JSON.stringify(corners)),
                        cornersRight: JSON.parse(JSON.stringify(cornersRight)),
                        isBookMode: true,
                        rotationAngle: rotationAngle,
                        spineX: spineX,
                        isSpineActive: isSpineActive,
                        activeFilter: activeFilter,
                        brightness: parseInt(brightnessSlider.value),
                        contrast: parseFloat(contrastSlider.value),
                        sharpen: parseFloat(sharpenSlider.value),
                        resolutionPreset: resPreset,
                        jpegQuality: quality,
                        canvasDataURL: dataUrlL,
                        blobSize: blobL.size,
                        side: 'left'
                    });

                    pdfPageQueue.push({
                        id: baseId + '_R',
                        filename: filename + ' (Kanan)',
                        originalSrc: originalDataURL,
                        corners: JSON.parse(JSON.stringify(corners)),
                        cornersRight: JSON.parse(JSON.stringify(cornersRight)),
                        isBookMode: true,
                        rotationAngle: rotationAngle,
                        spineX: spineX,
                        isSpineActive: isSpineActive,
                        activeFilter: activeFilter,
                        brightness: parseInt(brightnessSlider.value),
                        contrast: parseFloat(contrastSlider.value),
                        sharpen: parseFloat(sharpenSlider.value),
                        resolutionPreset: resPreset,
                        jpegQuality: quality,
                        canvasDataURL: dataUrlR,
                        blobSize: blobR.size,
                        side: 'right'
                    });

                    renderThumbnails();
                    resolve();
                });
            });
        }
    }

    // 9. SPINE DETECTION
    function autoDetectSpine(grayMat) {
        const width = grayMat.cols;
        const height = grayMat.rows;

        const startCol = Math.floor(width * 0.22);
        const endCol = Math.floor(width * 0.78);

        let colSums = [];
        let overallSum = 0;

        for (let x = 0; x < width; x++) {
            let sum = 0;
            for (let y = 0; y < height; y++) {
                sum += grayMat.ucharAt(y, x);
            }
            colSums[x] = sum / height;
            overallSum += colSums[x];
        }

        const overallAvg = overallSum / width;
        let minVal = 999999;
        let detectedX = -1;

        for (let x = startCol; x < endCol; x++) {
            if (colSums[x] < minVal) {
                minVal = colSums[x];
                detectedX = x;
            }
        }

        if (detectedX !== -1 && minVal < overallAvg * 0.88) {
            console.log("Auto-Spine split detected at X:", detectedX, "Val:", minVal, "Avg:", overallAvg);
            return detectedX / width;
        }

        return 1.0;
    }

    function runSpineDetection(mat) {
        let gray = new cv.Mat();
        let small = new cv.Mat();
        cv.cvtColor(mat, gray, cv.COLOR_RGBA2GRAY);
        cv.resize(gray, small, new cv.Size(200, 150), 0, 0, cv.INTER_AREA);

        const detectedX = autoDetectSpine(small);

        gray.delete();
        small.delete();

        if (detectedX < 0.95 && detectedX > 0.05) {
            spineX = detectedX;
            isSpineActive = true;
            toggleSpineBtn.classList.add('active');
            toggleSpineBtn.innerHTML = `<i data-lucide="split" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i><span class="align-middle">Nonaktifkan Pembatas Buku</span>`;

            spineDivider.classList.remove('hidden');
            spineDivider.style.left = `${spineX * 100}%`;
            showToast('Lipatan buku (spine) terdeteksi otomatis. Batasan pangkas aktif.', 'info');
        } else {
            isSpineActive = false;
            toggleSpineBtn.classList.remove('active');
            toggleSpineBtn.innerHTML = `<i data-lucide="split" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i><span class="align-middle">Aktifkan Pembatas Buku</span>`;
            if (!isBookMode) {
                spineDivider.classList.add('hidden');
                spineX = 1.0;
            } else {
                spineX = 0.5;
                spineDivider.classList.remove('hidden');
                spineDivider.style.left = `50%`;
            }
        }
        safeCreateIcons();
    }

    // 10. SPINE DIVIDER INTERACTIVE DRAG
    function initSpineDividerDrag() {
        let isDraggingSpine = false;
        let wrapperRect = null;

        spineDivider.addEventListener('mousedown', startSpineDrag);
        spineDivider.addEventListener('touchstart', startSpineDrag, { passive: false });

        function startSpineDrag(e) {
            e.preventDefault();
            isDraggingSpine = true;
            wrapperRect = document.getElementById('original-wrapper').getBoundingClientRect();

            window.addEventListener('mousemove', dragSpine);
            window.addEventListener('touchmove', dragSpine, { passive: false });
            window.addEventListener('mouseup', stopSpineDrag);
            window.addEventListener('touchend', stopSpineDrag);
        }

        function dragSpine(e) {
            if (!isDraggingSpine || !wrapperRect) return;

            let clientX = e.clientX;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
            }

            let x = (clientX - wrapperRect.left) / wrapperRect.width;
            x = Math.max(0.15, Math.min(x, 0.85));

            spineX = x;
            spineDivider.style.left = `${spineX * 100}%`;

            constrainHandles();
            updateSvgOverlay();
        }

        function stopSpineDrag() {
            isDraggingSpine = false;
            window.removeEventListener('mousemove', dragSpine);
            window.removeEventListener('touchmove', dragSpine);
            window.removeEventListener('mouseup', stopSpineDrag);
            window.removeEventListener('touchend', stopSpineDrag);

            processAndCompress();
        }
    }

    function constrainHandles() {
        if (spineX >= 0.98) return;

        if (corners.tl.x > spineX) corners.tl.x = spineX;
        if (corners.tr.x > spineX) corners.tr.x = spineX;
        if (corners.br.x > spineX) corners.br.x = spineX;
        if (corners.bl.x > spineX) corners.bl.x = spineX;

        if (cornersRight.tl.x < spineX) cornersRight.tl.x = spineX;
        if (cornersRight.tr.x < spineX) cornersRight.tr.x = spineX;
        if (cornersRight.br.x < spineX) cornersRight.br.x = spineX;
        if (cornersRight.bl.x < spineX) cornersRight.bl.x = spineX;
    }

    // 11. GENERATE DEMO DOCUMENT PROGRAMMATICALLY
    function loadDemoDocument() {
        statusDot.className = "aeroscan-status-dot processing";
        statusText.innerText = "Membuat Demo...";
        showToast('Membuat dokumen demo beresolusi tinggi...', 'info');

        const demoCanvas = document.createElement('canvas');
        demoCanvas.width = 2400;
        demoCanvas.height = 1600;
        const ctx = demoCanvas.getContext('2d');

        // Draw table background
        const tableGrad = ctx.createLinearGradient(0, 0, 2400, 1600);
        tableGrad.addColorStop(0, '#1a100a');
        tableGrad.addColorStop(1, '#0c0704');
        ctx.fillStyle = tableGrad;
        ctx.fillRect(0, 0, 2400, 1600);

        ctx.strokeStyle = 'rgba(0,0,0,0.6)';
        ctx.lineWidth = 14;
        for (let i = 150; i < 2400; i += 300) {
            ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i + 120, 1600); ctx.stroke();
        }

        ctx.save();
        ctx.translate(1200, 800);
        ctx.rotate(0.06);

        if (isBookMode || isSpineActive) {
            ctx.shadowColor = 'rgba(0, 0, 0, 0.75)';
            ctx.shadowBlur = 45;
            ctx.shadowOffsetX = 12;
            ctx.shadowOffsetY = 24;

            // Left page
            ctx.save();
            ctx.translate(-460, -600);
            ctx.rotate(-0.02);
            ctx.fillStyle = '#faf8f0';
            ctx.fillRect(0, 0, 460, 1200);

            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1.2;
            for (let y = 150; y < 1100; y += 38) {
                ctx.beginPath(); ctx.moveTo(50, y); ctx.lineTo(410, y); ctx.stroke();
            }

            ctx.strokeStyle = 'rgba(220, 38, 38, 0.2)';
            ctx.beginPath(); ctx.moveTo(350, 40); ctx.lineTo(350, 1160); ctx.stroke();

            ctx.fillStyle = '#1e293b';
            ctx.font = 'bold 24px Outfit, sans-serif';
            ctx.fillText('BUKU REGISTER: BAGIAN PERTAMA', 50, 100);

            ctx.fillStyle = '#475569';
            ctx.font = '500 13px Inter, sans-serif';
            const leftTexts = [
                'Bab I - Ketentuan Umum Administrasi Negara',
                'Ini adalah halaman sebelah KIRI dari cetakan buku terbuka.',
                'Pencatatan data dilakukan secara manual sejak abad 19.',
                '',
                'Tabel 1.1: Log Aktivitas Harian Tim Teknis',
                '- Pengaturan awal OpenCV.js: Sukses terhubung.',
                '- Penyesuaian layout responsive: Selesai dibuat.',
                '- Pengujian kompresi target 300KB: Bekerja baik.',
                '',
                'Seluruh dokumen penting yang difoto menggunakan ponsel',
                'dapat secara otomatis dikoreksi kemiringannya dengan',
                'algoritma warpPerspective. Modul ini secara cerdas',
                'membagi area kiri dan kanan berdasarkan Spine pembagi',
                'buku di bagian tengah gambar.',
                '',
                'Catatan Kaki:',
                'Dokumen ini dicetak terbatas hanya untuk keperluan demo.'
            ];
            let ly = 170;
            leftTexts.forEach(line => {
                if (line.includes('Bab I') || line.includes('Tabel 1.1')) {
                    ctx.font = 'bold 15px Outfit, sans-serif';
                    ctx.fillStyle = '#0f172a';
                    ctx.fillText(line, 50, ly);
                } else {
                    ctx.font = '500 13px Inter, sans-serif';
                    ctx.fillStyle = '#475569';
                    ctx.fillText(line, 50, ly);
                }
                ly += 38;
            });

            ctx.restore();

            // Right page
            ctx.save();
            ctx.translate(0, -600);
            ctx.rotate(0.02);
            ctx.fillStyle = '#faf8f0';
            ctx.fillRect(0, 0, 460, 1200);

            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1.2;
            for (let y = 150; y < 1100; y += 38) {
                ctx.beginPath(); ctx.moveTo(50, y); ctx.lineTo(410, y); ctx.stroke();
            }

            ctx.strokeStyle = 'rgba(220, 38, 38, 0.2)';
            ctx.beginPath(); ctx.moveTo(110, 40); ctx.lineTo(110, 1160); ctx.stroke();

            ctx.fillStyle = '#1e293b';
            ctx.font = 'bold 24px Outfit, sans-serif';
            ctx.fillText('SURAT KESEPAKATAN TERMIN', 130, 100);

            ctx.fillStyle = '#475569';
            ctx.font = '500 13px Inter, sans-serif';
            const rightTexts = [
                'Pasal 4 - Hak Cipta & Lisensi Sistem',
                'Ini adalah halaman sebelah KANAN dari cetakan buku terbuka.',
                'Pihak Kedua menyerahkan seluruh source code web app',
                'kepada Pihak Pertama sebagai aset kekayaan intelektual.',
                '',
                'Termin Penyelesaian Pekerjaan:',
                'Milestone 1: Perencanaan & Wireframing (15%)',
                'Milestone 2: Integrasi OpenCV & UI Glassmorphism (50%)',
                'Milestone 3: Fitur Deteksi Ganda Buku & Rilis Akhir (35%)',
                '',
                'Tanda Tangan Pengesahan Dokumen:',
                'Disetujui oleh Direksi dan ditandatangani di bawah ini',
                'pada tanggal pencatatan sistem otomatis.',
                '',
                'Ahmad Subarjo                         Budi Setiawan',
                '(PIHAK PERTAMA)                      (PIHAK KEDUA)'
            ];
            let ry = 170;
            rightTexts.forEach(line => {
                if (line.includes('Pasal 4') || line.includes('Termin Penyelesaian') || line.includes('Tanda Tangan')) {
                    ctx.font = 'bold 15px Outfit, sans-serif';
                    ctx.fillStyle = '#0f172a';
                    ctx.fillText(line, 130, ry);
                } else {
                    ctx.font = '500 13px Inter, sans-serif';
                    ctx.fillStyle = '#475569';
                    ctx.fillText(line, 130, ry);
                }
                ry += 38;
            });

            ctx.restore();

            // Spine shadow
            ctx.shadowColor = 'transparent';
            const spineGrad = ctx.createLinearGradient(-30, -600, 30, -600);
            spineGrad.addColorStop(0, 'rgba(0,0,0,0.15)');
            spineGrad.addColorStop(0.5, 'rgba(0,0,0,0.5)');
            spineGrad.addColorStop(1, 'rgba(0,0,0,0.15)');
            ctx.fillStyle = spineGrad;
            ctx.fillRect(-25, -600, 50, 1200);

        } else {
            // Single page demo
            ctx.shadowColor = 'rgba(0, 0, 0, 0.7)';
            ctx.shadowBlur = 40;
            ctx.shadowOffsetX = 15;
            ctx.shadowOffsetY = 24;

            ctx.fillStyle = '#faf8f2';
            ctx.fillRect(-450, -600, 900, 1200);
            ctx.shadowColor = 'transparent';

            ctx.strokeStyle = 'rgba(220, 38, 38, 0.25)';
            ctx.lineWidth = 2.5;
            ctx.beginPath(); ctx.moveTo(-310, -550); ctx.lineTo(-310, 550); ctx.stroke();

            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1.2;
            for (let y = -450; y < 500; y += 40) {
                ctx.beginPath(); ctx.moveTo(-380, y); ctx.lineTo(380, y); ctx.stroke();
            }

            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 36px Outfit, sans-serif';
            ctx.fillText('DOKUMEN REGISTER HALAMAN TUNGGAL', -280, -500);

            ctx.fillStyle = '#334155';
            ctx.font = '500 17px Inter, sans-serif';
            const singleTexts = [
                'Bab 1: Definisi dan Pengoperasian Aplikasi Pemindai',
                'Ini adalah contoh simulasi berkas halaman tunggal beresolusi tinggi.',
                'Sistem otomatis memindai tepi dan meluruskan perspektif dengan warp.',
                '',
                '- Masukkan file dokumen Anda ke drag zone di kiri.',
                '- Sistem mendeteksi 4 titik sudut dokumen.',
                '- Sesuaikan letak pin penanda jika terdeteksi kurang tepat.',
                '- Kualitas JPEG dikompresi sebesar 85% secara default.',
                '- Ukuran hasil berkas akan berkurang secara signifikan.',
                '',
                'Penggunaan OpenCV.js terbukti berkinerja tinggi pada web browser',
                'modern, berjalan secara client-side sepenuhnya tanpa server backend.',
                '',
                'Tanda Tangan Pengesahan Administrasi:',
                'Ahmad Subarjo (Pihak Pertama)  &  Budi Setiawan (Pihak Kedua)'
            ];

            let textY = -400;
            singleTexts.forEach(line => {
                if (line.includes('Bab 1') || line.includes('Tanda Tangan')) {
                    ctx.font = 'bold 18px Outfit, sans-serif';
                    ctx.fillStyle = '#1e293b';
                    ctx.fillText(line, -280, textY);
                } else {
                    ctx.font = '500 17px Inter, sans-serif';
                    ctx.fillStyle = '#475569';
                    ctx.fillText(line, -280, textY);
                }
                textY += 40;
            });

            ctx.strokeStyle = '#2563eb';
            ctx.lineWidth = 3;
            ctx.beginPath(); ctx.moveTo(-280, 200); ctx.bezierCurveTo(-260, 180, -270, 220, -230, 205); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(50, 200); ctx.bezierCurveTo(70, 185, 60, 220, 100, 205); ctx.stroke();
        }

        ctx.restore();

        const vigGrad = ctx.createRadialGradient(1200, 800, 500, 1200, 800, 1300);
        vigGrad.addColorStop(0, 'rgba(0, 0, 0, 0)');
        vigGrad.addColorStop(1, 'rgba(0, 0, 0, 0.45)');
        ctx.fillStyle = vigGrad;
        ctx.fillRect(0, 0, 2400, 1600);

        originalFileSize = 5242880;
        document.getElementById('orig-size').innerText = `5.00 MB`;

        canvasOriginal.width = 2400;
        canvasOriginal.height = 1600;
        const oCtx = canvasOriginal.getContext('2d');
        oCtx.drawImage(demoCanvas, 0, 0);

        document.getElementById('orig-res-badge').innerText = `2400 x 1600 px`;

        if (originalMat) originalMat.delete();

        rotationAngle = 0;
        hasAutoRotated = false;
        originalMat = cv.imread(canvasOriginal);
        applyAutoLighting();
        fitOriginalCanvas(2400, 1600);

        origEmptyState.classList.add('hidden');
        outEmptyState.classList.add('hidden');
        origWrapper.classList.remove('hidden');

        if (!isBookMode) {
            canvasOutput.classList.remove('hidden');
            canvasOutputLeft.classList.add('hidden');
            canvasOutputRight.classList.add('hidden');
        } else {
            canvasOutput.classList.add('hidden');
            canvasOutputLeft.classList.remove('hidden');
            canvasOutputRight.classList.remove('hidden');
        }

        rotateBtn.classList.remove('disabled');
        resetCornersBtn.classList.remove('disabled');

        runSpineDetection(originalMat);
        detectCorners(originalMat);
        processAndCompress();

        statusDot.className = "aeroscan-status-dot ready";
        statusText.innerText = "Sistem Siap";
        showToast('Gambar demo berhasil dibuat dan dipindai secara otomatis!', 'success');
    }

    // 10. CANVAS WRAPPER FITTER
    function fitOriginalCanvas(imgW, imgH) {
        const container = document.getElementById('original-canvas-container');
        const maxW = container.clientWidth - 32;
        const maxH = 500;

        let ratio = Math.min(maxW / imgW, maxH / imgH);
        let displayW = imgW * ratio;
        let displayH = imgH * ratio;

        const wrapper = document.getElementById('original-wrapper');
        wrapper.style.width = `${displayW}px`;
        wrapper.style.height = `${displayH}px`;

        canvasOriginal.style.width = '100%';
        canvasOriginal.style.height = '100%';
    }

    // 11. DETECT CORNERS
    function detectCornersForMat(src, offsetX = 0) {
        const maxDim = 600;
        const scale = Math.min(maxDim / src.cols, maxDim / src.rows, 1.0);

        let resized = new cv.Mat();
        let dsize = new cv.Size(src.cols * scale, src.rows * scale);
        cv.resize(src, resized, dsize, 0, 0, cv.INTER_AREA);

        let gray = new cv.Mat();
        cv.cvtColor(resized, gray, cv.COLOR_RGBA2GRAY);

        let contrastGray = new cv.Mat();
        cv.convertScaleAbs(gray, contrastGray, 1.45, -55);

        let blurred = new cv.Mat();
        cv.GaussianBlur(contrastGray, blurred, new cv.Size(11, 11), 0);

        let edges = new cv.Mat();
        cv.Canny(blurred, edges, 60, 240, 3, false);

        let dKernel = cv.getStructuringElement(cv.MORPH_RECT, new cv.Size(3, 3));
        let dilated = new cv.Mat();
        cv.dilate(edges, dilated, dKernel);

        let contours = new cv.MatVector();
        let hierarchy = new cv.Mat();
        cv.findContours(dilated, contours, hierarchy, cv.RETR_LIST, cv.CHAIN_APPROX_SIMPLE);

        let foundQuad = false;
        let maxArea = 0;
        let bestApprox = null;

        for (let i = 0; i < contours.size(); ++i) {
            let contour = contours.get(i);
            let area = cv.contourArea(contour);

            const minContourArea = (resized.cols * resized.rows) * 0.05;
            if (area > minContourArea && area > maxArea) {
                let peri = cv.arcLength(contour, true);
                let approx = new cv.Mat();
                cv.approxPolyDP(contour, approx, 0.02 * peri, true);

                if (approx.rows === 4 && cv.isContourConvex(approx)) {
                    maxArea = area;
                    if (bestApprox) bestApprox.delete();
                    bestApprox = approx;
                    foundQuad = true;
                } else {
                    approx.delete();
                }
            }
            contour.delete();
        }

        let result = null;
        if (foundQuad && bestApprox) {
            let pts = [];
            for (let i = 0; i < 4; i++) {
                pts.push({
                    x: (bestApprox.data32S[i * 2] / scale) + offsetX,
                    y: (bestApprox.data32S[i * 2 + 1] / scale)
                });
            }
            result = sortPoints(pts);
        }

        resized.delete(); gray.delete(); contrastGray.delete(); blurred.delete(); edges.delete();
        dKernel.delete(); dilated.delete(); contours.delete(); hierarchy.delete();
        if (bestApprox) bestApprox.delete();

        return result;
    }

    function sortPoints(pts) {
        const sumSorted = [...pts].sort((a, b) => (a.x + a.y) - (b.x + b.y));
        const tl = sumSorted[0];
        const br = sumSorted[3];

        const diffSorted = [...pts].sort((a, b) => (a.y - a.x) - (b.y - b.x));
        const tr = diffSorted[0];
        const bl = diffSorted[3];

        return [tl, tr, br, bl];
    }

    // 12. CORNER DETECT ROUTING
    function detectCorners(src) {
        if (!isBookMode) {
            let pts = null;
            if (spineX < 0.98) {
                if (spineX >= 0.5) {
                    let cropW = Math.floor(spineX * src.cols);
                    let rect = new cv.Rect(0, 0, cropW, src.rows);
                    let croppedMat = src.roi(rect);
                    pts = detectCornersForMat(croppedMat, 0);
                    croppedMat.delete();
                } else {
                    let startX = Math.floor(spineX * src.cols);
                    let rect = new cv.Rect(startX, 0, src.cols - startX, src.rows);
                    let croppedMat = src.roi(rect);
                    pts = detectCornersForMat(croppedMat, startX);
                    croppedMat.delete();
                }
            } else {
                pts = detectCornersForMat(src, 0);
            }

            if (pts) {
                if (!hasAutoRotated) {
                    const topLen = Math.hypot(pts[1].x - pts[0].x, pts[1].y - pts[0].y);
                    const leftLen = Math.hypot(pts[3].x - pts[0].x, pts[3].y - pts[0].y);

                    if (topLen / leftLen > 1.15) {
                        hasAutoRotated = true;
                        showToast('Mendeteksi dokumen miring (landscape). Memutar otomatis...', 'info');
                        setTimeout(() => {
                            rotateInputImage();
                        }, 150);
                        return;
                    }
                }

                corners.tl = { x: pts[0].x / src.cols, y: pts[0].y / src.rows };
                corners.tr = { x: pts[1].x / src.cols, y: pts[1].y / src.rows };
                corners.br = { x: pts[2].x / src.cols, y: pts[2].y / src.rows };
                corners.bl = { x: pts[3].x / src.cols, y: pts[3].y / src.rows };
                showToast('Sudut dokumen terdeteksi secara otomatis.', 'success');
            } else {
                if (spineX < 0.98) {
                    if (spineX >= 0.5) {
                        corners.tl = { x: 0.05, y: 0.1 };
                        corners.tr = { x: spineX - 0.05, y: 0.1 };
                        corners.br = { x: spineX - 0.05, y: 0.9 };
                        corners.bl = { x: 0.05, y: 0.9 };
                    } else {
                        corners.tl = { x: spineX + 0.05, y: 0.1 };
                        corners.tr = { x: 0.95, y: 0.1 };
                        corners.br = { x: 0.95, y: 0.9 };
                        corners.bl = { x: spineX + 0.05, y: 0.9 };
                    }
                } else {
                    corners.tl = { x: 0.1, y: 0.1 };
                    corners.tr = { x: 0.9, y: 0.1 };
                    corners.br = { x: 0.9, y: 0.9 };
                    corners.bl = { x: 0.1, y: 0.9 };
                }
                showToast('Tepi dokumen halaman tunggal gagal dideteksi otomatis. Penyesuaian manual diaktifkan.', 'error');
            }
        } else {
            const midX = Math.floor(spineX * src.cols);

            let rectL = new cv.Rect(0, 0, midX, src.rows);
            let leftMat = src.roi(rectL);
            const ptsL = detectCornersForMat(leftMat, 0);
            leftMat.delete();

            if (ptsL) {
                corners.tl = { x: ptsL[0].x / src.cols, y: ptsL[0].y / src.rows };
                corners.tr = { x: ptsL[1].x / src.cols, y: ptsL[1].y / src.rows };
                corners.br = { x: ptsL[2].x / src.cols, y: ptsL[2].y / src.rows };
                corners.bl = { x: ptsL[3].x / src.cols, y: ptsL[3].y / src.rows };
            } else {
                corners.tl = { x: 0.05, y: 0.15 };
                corners.tr = { x: spineX - 0.05, y: 0.15 };
                corners.br = { x: spineX - 0.05, y: 0.85 };
                corners.bl = { x: 0.05, y: 0.85 };
            }

            let rectR = new cv.Rect(midX, 0, src.cols - midX, src.rows);
            let rightMat = src.roi(rectR);
            const ptsR = detectCornersForMat(rightMat, midX);
            rightMat.delete();

            if (ptsR) {
                cornersRight.tl = { x: ptsR[0].x / src.cols, y: ptsR[0].y / src.rows };
                cornersRight.tr = { x: ptsR[1].x / src.cols, y: ptsR[1].y / src.rows };
                cornersRight.br = { x: ptsR[2].x / src.cols, y: ptsR[2].y / src.rows };
                cornersRight.bl = { x: ptsR[3].x / src.cols, y: ptsR[3].y / src.rows };
            } else {
                cornersRight.tl = { x: spineX + 0.05, y: 0.15 };
                cornersRight.tr = { x: 0.95, y: 0.15 };
                cornersRight.br = { x: 0.95, y: 0.85 };
                cornersRight.bl = { x: spineX + 0.05, y: 0.85 };
            }

            if (ptsL && ptsR) {
                showToast('Kedua halaman buku berhasil dideteksi secara otomatis.', 'success');
            } else if (ptsL || ptsR) {
                showToast('Salah satu halaman gagal dideteksi. Atur letak manual.', 'error');
            } else {
                showToast('Kedua halaman gagal dideteksi otomatis. Penyesuaian manual diaktifkan.', 'error');
            }
        }

        constrainHandles();
        updateSvgOverlay();
    }

    // 13. UPDATE INTERACTIVE HANDLES & SVG LINES
    function updateSvgOverlay() {
        const wrapper = document.getElementById('original-wrapper');
        if (!wrapper) return;
        const w = wrapper.clientWidth;
        const h = wrapper.clientHeight;

        if (isBookMode || isSpineActive) {
            spineDivider.style.left = `${spineX * 100}%`;
        }

        document.getElementById('handle-tl').style.left = `${corners.tl.x * 100}%`;
        document.getElementById('handle-tl').style.top = `${corners.tl.y * 100}%`;
        document.getElementById('handle-tr').style.left = `${corners.tr.x * 100}%`;
        document.getElementById('handle-tr').style.top = `${corners.tr.y * 100}%`;
        document.getElementById('handle-br').style.left = `${corners.br.x * 100}%`;
        document.getElementById('handle-br').style.top = `${corners.br.y * 100}%`;
        document.getElementById('handle-bl').style.left = `${corners.bl.x * 100}%`;
        document.getElementById('handle-bl').style.top = `${corners.bl.y * 100}%`;

        const pts = [
            `${corners.tl.x * w},${corners.tl.y * h}`,
            `${corners.tr.x * w},${corners.tr.y * h}`,
            `${corners.br.x * w},${corners.br.y * h}`,
            `${corners.bl.x * w},${corners.bl.y * h}`
        ].join(' ');
        document.getElementById('doc-overlay').setAttribute('points', pts);

        if (isBookMode) {
            document.getElementById('handle-rtl').style.left = `${cornersRight.tl.x * 100}%`;
            document.getElementById('handle-rtl').style.top = `${cornersRight.tl.y * 100}%`;
            document.getElementById('handle-rtr').style.left = `${cornersRight.tr.x * 100}%`;
            document.getElementById('handle-rtr').style.top = `${cornersRight.tr.y * 100}%`;
            document.getElementById('handle-rbr').style.left = `${cornersRight.br.x * 100}%`;
            document.getElementById('handle-rbr').style.top = `${cornersRight.br.y * 100}%`;
            document.getElementById('handle-rbl').style.left = `${cornersRight.bl.x * 100}%`;
            document.getElementById('handle-rbl').style.top = `${cornersRight.bl.y * 100}%`;

            const ptsR = [
                `${cornersRight.tl.x * w},${cornersRight.tl.y * h}`,
                `${cornersRight.tr.x * w},${cornersRight.tr.y * h}`,
                `${cornersRight.br.x * w},${cornersRight.br.y * h}`,
                `${cornersRight.bl.x * w},${cornersRight.bl.y * h}`
            ].join(' ');
            docOverlayRight.setAttribute('points', ptsR);
        }
    }

    // 14. DRAG CORNERS CONTROLLER
    function initDragCorners() {
        const handles = document.querySelectorAll('.aeroscan-corner-handle');
        let activeCorner = null;
        let wrapperRect = null;

        handles.forEach(handle => {
            const startDrag = (e) => {
                e.preventDefault();
                activeCorner = handle.dataset.corner;
                wrapperRect = document.getElementById('original-wrapper').getBoundingClientRect();
                magnifierEl.style.display = 'block';

                window.addEventListener('mousemove', dragMove);
                window.addEventListener('touchmove', dragMove, { passive: false });
                window.addEventListener('mouseup', endDrag);
                window.addEventListener('touchend', endDrag);

                let clientX = e.clientX;
                let clientY = e.clientY;
                if (e.touches && e.touches.length > 0) {
                    clientX = e.touches[0].clientX;
                    clientY = e.touches[0].clientY;
                }
                let x = activeCorner.startsWith('r') ? cornersRight[activeCorner.substring(1)].x : corners[activeCorner].x;
                let y = activeCorner.startsWith('r') ? cornersRight[activeCorner.substring(1)].y : corners[activeCorner].y;
                updateMagnifier(clientX, clientY, x, y);
            };

            handle.addEventListener('mousedown', startDrag);
            handle.addEventListener('touchstart', startDrag, { passive: false });
        });

        function dragMove(e) {
            if (!activeCorner || !wrapperRect) return;

            let clientX = e.clientX;
            let clientY = e.clientY;

            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }

            let x = (clientX - wrapperRect.left) / wrapperRect.width;
            let y = (clientY - wrapperRect.top) / wrapperRect.height;

            x = Math.max(0, Math.min(x, 1));
            y = Math.max(0, Math.min(y, 1));

            if (activeCorner.startsWith('r')) {
                const key = activeCorner.substring(1);
                cornersRight[key] = { x, y };
            } else {
                corners[activeCorner] = { x, y };
            }

            constrainHandles();
            updateSvgOverlay();
            updateMagnifier(clientX, clientY, x, y);
        }

        function endDrag() {
            activeCorner = null;
            wrapperRect = null;
            magnifierEl.style.display = 'none';

            window.removeEventListener('mousemove', dragMove);
            window.removeEventListener('touchmove', dragMove);
            window.removeEventListener('mouseup', endDrag);
            window.removeEventListener('touchend', endDrag);

            processAndCompress();
        }

        function updateMagnifier(clientX, clientY, x, y) {
            magnifierEl.style.left = `${clientX}px`;
            magnifierEl.style.top = `${clientY}px`;

            const mCtx = magnifierCanvas.getContext('2d');
            mCtx.clearRect(0, 0, 120, 120);

            const imgX = x * canvasOriginal.width;
            const imgY = y * canvasOriginal.height;
            const cropSize = 80;
            const halfCrop = cropSize / 2;

            try {
                mCtx.drawImage(
                    canvasOriginal,
                    imgX - halfCrop, imgY - halfCrop, cropSize, cropSize,
                    0, 0, 120, 120
                );
            } catch (e) {}

            mCtx.strokeStyle = 'var(--accent-cyan)';
            mCtx.lineWidth = 3;
            mCtx.beginPath();
            mCtx.arc(60, 60, 58, 0, 2 * Math.PI);
            mCtx.stroke();

            mCtx.strokeStyle = '#ef4444';
            mCtx.lineWidth = 1.5;
            mCtx.beginPath();
            mCtx.moveTo(0, 60); mCtx.lineTo(120, 60);
            mCtx.moveTo(60, 0); mCtx.lineTo(60, 120);
            mCtx.stroke();
        }
    }

    // 15. ROTATE INPUT IMAGE
    function rotateInputImage() {
        if (!originalMat) return;

        statusDot.className = "aeroscan-status-dot processing";
        statusText.innerText = "Memutar Gambar...";
        showToast('Memutar gambar 90° searah jarum jam...', 'info');

        setTimeout(() => {
            let rotated = new cv.Mat();
            cv.rotate(originalMat, rotated, cv.ROTATE_90_CLOCKWISE);
            originalMat.delete();
            originalMat = rotated;

            cv.imshow(canvasOriginal, originalMat);

            fitOriginalCanvas(originalMat.cols, originalMat.rows);
            document.getElementById('orig-res-badge').innerText = `${originalMat.cols} x ${originalMat.rows} px`;

            runSpineDetection(originalMat);
            detectCorners(originalMat);
            processAndCompress();

            statusDot.className = "aeroscan-status-dot ready";
            statusText.innerText = "Sistem Siap";
        }, 15);
    }

    // 17. WARPING, ENHANCEMENT & SHARPENING PIPELINE
    function warpAndFilterMat(src, pts, targetCanvasId, targetW = 1200, targetH = 1842) {
        // ── Guard: validate all 4 corner points are finite numbers ──
        const keys = ['tl', 'tr', 'br', 'bl'];
        for (const k of keys) {
            if (!pts[k] || !isFinite(pts[k].x) || !isFinite(pts[k].y)) {
                console.warn('warpAndFilterMat: invalid corner point', k, pts[k]);
                return;
            }
        }

        // ── Guard: check for degenerate (collinear) quad ──
        // If all points are nearly the same or form a line, getPerspectiveTransform throws BindingError
        const dx = Math.max(
            Math.abs(pts.tl.x - pts.tr.x), Math.abs(pts.bl.x - pts.br.x),
            Math.abs(pts.tl.x - pts.bl.x), Math.abs(pts.tr.x - pts.br.x)
        );
        const dy = Math.max(
            Math.abs(pts.tl.y - pts.tr.y), Math.abs(pts.bl.y - pts.br.y),
            Math.abs(pts.tl.y - pts.bl.y), Math.abs(pts.tr.y - pts.br.y)
        );
        if (dx < 5 || dy < 5) {
            console.warn('warpAndFilterMat: degenerate quad (too small or collinear), skipping');
            return;
        }

        let srcCoords = null, dstCoords = null, M = null, warped = null;
        let adjusted = null, filtered = null;

        try {
            srcCoords = cv.matFromArray(4, 1, cv.CV_32FC2, [
                pts.tl.x, pts.tl.y,
                pts.tr.x, pts.tr.y,
                pts.br.x, pts.br.y,
                pts.bl.x, pts.bl.y
            ]);

            dstCoords = cv.matFromArray(4, 1, cv.CV_32FC2, [
                0, 0,
                targetW, 0,
                targetW, targetH,
                0, targetH
            ]);

            M = cv.getPerspectiveTransform(srcCoords, dstCoords);
            warped = new cv.Mat();
            const dsize = new cv.Size(targetW, targetH);

            cv.warpPerspective(src, warped, M, dsize, cv.INTER_CUBIC, cv.BORDER_CONSTANT, new cv.Scalar());

            const brightness = parseInt(brightnessSlider.value);
            const contrast   = parseFloat(contrastSlider.value);
            adjusted = new cv.Mat();
            cv.convertScaleAbs(warped, adjusted, contrast, brightness);

            filtered = new cv.Mat();

            if (activeFilter === 'magic') {
                let channels = new cv.MatVector();
                cv.split(adjusted, channels);

                for (let i = 0; i < 3; i++) {
                    let ch = channels.get(i);

                    let small = new cv.Mat();
                    let smallW = 150;
                    let smallH = Math.round(150 * ch.rows / ch.cols);
                    cv.resize(ch, small, new cv.Size(smallW, smallH), 0, 0, cv.INTER_LINEAR);

                    let blurredSmall = new cv.Mat();
                    cv.GaussianBlur(small, blurredSmall, new cv.Size(25, 25), 0, 0, cv.BORDER_DEFAULT);

                    let bg = new cv.Mat();
                    cv.resize(blurredSmall, bg, ch.size(), 0, 0, cv.INTER_LINEAR);

                    let divided = new cv.Mat();
                    cv.divide(ch, bg, divided, 255, -1);

                    let stretched = new cv.Mat();
                    cv.convertScaleAbs(divided, stretched, 1.25, -45);

                    channels.set(i, stretched);

                    ch.delete(); small.delete(); blurredSmall.delete(); bg.delete(); divided.delete(); stretched.delete();
                }
                cv.merge(channels, filtered);
                channels.delete();

            } else if (activeFilter === 'enhance') {
                let sharpKernel = cv.matFromArray(3, 3, cv.CV_32F, [
                    0, -1, 0,
                    -1, 5, -1,
                    0, -1, 0
                ]);
                cv.filter2D(adjusted, filtered, -1, sharpKernel);
                sharpKernel.delete();
            } else if (activeFilter === 'gray') {
                cv.cvtColor(adjusted, filtered, cv.COLOR_RGBA2GRAY);
            } else if (activeFilter === 'bw') {
                let tempGray = new cv.Mat();
                cv.cvtColor(adjusted, tempGray, cv.COLOR_RGBA2GRAY);

                let cVal = 8 - (parseInt(brightnessSlider.value) / 5.0);
                cVal = Math.max(1, Math.min(cVal, 25));

                cv.adaptiveThreshold(tempGray, filtered, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY, 15, cVal);
                tempGray.delete();
            } else {
                adjusted.copyTo(filtered);
            }

            const sharpStrength = parseFloat(sharpenSlider.value);
            if (sharpStrength > 0.0) {
                let sharpEnhanced = new cv.Mat();
                const w = sharpStrength;

                let sharpKernel = cv.matFromArray(3, 3, cv.CV_32F, [
                    0,    -w / 4, 0,
                    -w / 4, 1 + w, -w / 4,
                    0,    -w / 4, 0
                ]);

                cv.filter2D(filtered, sharpEnhanced, -1, sharpKernel);
                cv.imshow(targetCanvasId, sharpEnhanced);

                sharpKernel.delete();
                sharpEnhanced.delete();
            } else {
                cv.imshow(targetCanvasId, filtered);
            }

        } catch (err) {
            // BindingError or any OpenCV error — log and continue; cleanup happens in finally
            console.error('warpAndFilterMat error:', err);
        } finally {
            // Guarantee cleanup regardless of success or failure
            if (srcCoords) try { srcCoords.delete(); } catch(e) {}
            if (dstCoords) try { dstCoords.delete(); } catch(e) {}
            if (M)         try { M.delete();         } catch(e) {}
            if (warped)    try { warped.delete();    } catch(e) {}
            if (adjusted)  try { adjusted.delete();  } catch(e) {}
            if (filtered)  try { filtered.delete();  } catch(e) {}
        }
    }

    function getCanvasBlob(canvas, quality, callback) {
        try {
            canvas.toBlob(function (blob) {
                if (blob) {
                    callback(blob);
                } else {
                    fallbackDataURL();
                }
            }, 'image/jpeg', quality);
        } catch (err) {
            fallbackDataURL();
        }

        function fallbackDataURL() {
            try {
                const dataUrl = canvas.toDataURL('image/jpeg', quality);
                const blobFallback = dataURLToBlob(dataUrl);
                callback(blobFallback);
            } catch (e) {
                showToast("Gagal membuat blob: " + e.message, "error");
            }
        }
    }

    function dataURLToBlob(dataurl) {
        var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
            bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new Blob([u8arr], { type: mime });
    }

    // 18. MAIN COMPRESS & RESOLUTION WRAPPER
    function processAndCompress(isPreview = false) {
        if (!originalMat) return;

        const imgW = originalMat.cols;
        const imgH = originalMat.rows;
        const quality = parseFloat(qualitySlider.value);

        let targetW = 1200;
        let targetH = 1842;

        if (isPreview) {
            targetW = 600;
            targetH = 921;
        } else {
            const resPreset = resPresetSelect.value;
            if (resPreset === 'hd') {
                targetW = 1600;
                targetH = 2456;
            } else if (resPreset === 'super-hd') {
                targetW = 2400;
                targetH = 3684;
            }
        }

        if (!isBookMode) {
            const pts = {
                tl: { x: corners.tl.x * imgW, y: corners.tl.y * imgH },
                tr: { x: corners.tr.x * imgW, y: corners.tr.y * imgH },
                br: { x: corners.br.x * imgW, y: corners.br.y * imgH },
                bl: { x: corners.bl.x * imgW, y: corners.bl.y * imgH }
            };

            warpAndFilterMat(originalMat, pts, 'canvas-output', targetW, targetH);

            if (!isPreview) {
                getCanvasBlob(canvasOutput, quality, function (blob) {
                    if (!blob) return;
                    singleBlob = blob;

                    const sizeKB = blob.size / 1024;
                    document.getElementById('comp-size').innerText = sizeKB > 1024 ? `${(sizeKB / 1024).toFixed(2)} MB` : `${sizeKB.toFixed(1)} KB`;

                    const savingsRatio = ((1 - blob.size / originalFileSize) * 100).toFixed(1);
                    document.getElementById('comp-ratio').innerText = `${savingsRatio}% Saved`;

                    downloadBtn.classList.remove('disabled');
                    addToPdfBtn.classList.remove('disabled');
                });
            }

        } else {
            const ptsL = {
                tl: { x: corners.tl.x * imgW, y: corners.tl.y * imgH },
                tr: { x: corners.tr.x * imgW, y: corners.tr.y * imgH },
                br: { x: corners.br.x * imgW, y: corners.br.y * imgH },
                bl: { x: corners.bl.x * imgW, y: corners.bl.y * imgH }
            };

            const ptsR = {
                tl: { x: cornersRight.tl.x * imgW, y: cornersRight.tl.y * imgH },
                tr: { x: cornersRight.tr.x * imgW, y: cornersRight.tr.y * imgH },
                br: { x: cornersRight.br.x * imgW, y: cornersRight.br.y * imgH },
                bl: { x: cornersRight.bl.x * imgW, y: cornersRight.bl.y * imgH }
            };

            warpAndFilterMat(originalMat, ptsL, 'canvas-output-left', targetW, targetH);
            warpAndFilterMat(originalMat, ptsR, 'canvas-output-right', targetW, targetH);

            if (!isPreview) {
                getCanvasBlob(canvasOutputLeft, quality, function (blobL) {
                    if (!blobL) return;
                    leftBlob = blobL;

                    getCanvasBlob(canvasOutputRight, quality, function (blobR) {
                        if (!blobR) return;
                        rightBlob = blobR;

                        const totalBytes = blobL.size + blobR.size;
                        const sizeKB = totalBytes / 1024;
                        document.getElementById('comp-size').innerText = sizeKB > 1024 ? `${(sizeKB / 1024).toFixed(2)} MB` : `${sizeKB.toFixed(1)} KB`;

                        const savingsRatio = ((1 - totalBytes / originalFileSize) * 100).toFixed(1);
                        document.getElementById('comp-ratio').innerText = `${savingsRatio}% Saved`;

                        downloadLeftBtn.classList.remove('disabled');
                        downloadRightBtn.classList.remove('disabled');
                        addToPdfBtn.classList.remove('disabled');
                    });
                });
            }
        }
    }

    // 19. BATCH PDF SCAN MANAGER LOGIC
    function initPdfListeners() {
        addToPdfBtn.addEventListener('click', addCurrentToPdfQueue);
        generatePdfBtn.addEventListener('click', generatePdf);
    }

    function addCurrentToPdfQueue() {
        if (!originalMat) return;

        statusDot.className = "aeroscan-status-dot processing";
        statusText.innerText = "Menambahkan...";
        addToPdfBtn.disabled = true;
        addToPdfBtn.classList.add('disabled');

        setTimeout(() => {
            const imgW = originalMat.cols;
            const imgH = originalMat.rows;
            const quality = parseFloat(qualitySlider.value);
            const resPreset = resPresetSelect.value;

            let targetW = 1200;
            let targetH = 1842;
            if (resPreset === 'hd') {
                targetW = 1600;
                targetH = 2456;
            } else if (resPreset === 'super-hd') {
                targetW = 2400;
                targetH = 3684;
            }

            if (editingPageId !== null) {
                const pageIndex = pdfPageQueue.findIndex(p => p.id === editingPageId);
                if (pageIndex !== -1) {
                    const page = pdfPageQueue[pageIndex];

                    if (!isBookMode) {
                        const pts = {
                            tl: { x: corners.tl.x * imgW, y: corners.tl.y * imgH },
                            tr: { x: corners.tr.x * imgW, y: corners.tr.y * imgH },
                            br: { x: corners.br.x * imgW, y: corners.br.y * imgH },
                            bl: { x: corners.bl.x * imgW, y: corners.bl.y * imgH }
                        };

                        warpAndFilterMat(originalMat, pts, 'canvas-output', targetW, targetH);
                        const dataUrl = canvasOutput.toDataURL('image/jpeg', quality);

                        page.corners = JSON.parse(JSON.stringify(corners));
                        page.isBookMode = false;
                        page.rotationAngle = rotationAngle;
                        page.spineX = spineX;
                        page.isSpineActive = isSpineActive;
                        page.activeFilter = activeFilter;
                        page.brightness = parseInt(brightnessSlider.value);
                        page.contrast = parseFloat(contrastSlider.value);
                        page.sharpen = parseFloat(sharpenSlider.value);
                        page.resolutionPreset = resPreset;
                        page.jpegQuality = quality;
                        page.canvasDataURL = dataUrl;
                    } else {
                        const ptsL = {
                            tl: { x: corners.tl.x * imgW, y: corners.tl.y * imgH },
                            tr: { x: corners.tr.x * imgW, y: corners.tr.y * imgH },
                            br: { x: corners.br.x * imgW, y: corners.br.y * imgH },
                            bl: { x: corners.bl.x * imgW, y: corners.bl.y * imgH }
                        };
                        warpAndFilterMat(originalMat, ptsL, 'canvas-output-left', targetW, targetH);
                        const dataUrlL = canvasOutputLeft.toDataURL('image/jpeg', quality);
                        page.canvasDataURL = dataUrlL;
                        page.isBookMode = true;
                    }

                    showToast(`Halaman ${pageIndex + 1} berhasil diperbarui!`, 'success');
                }

                editingPageId = null;
                addToPdfBtn.innerHTML = `<i data-lucide="plus-circle" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i><span class="align-middle">Simpan Halaman Aktif</span>`;
                addToPdfBtn.style.borderColor = 'var(--accent-cyan)';
                addToPdfBtn.style.color = 'var(--accent-cyan)';
                safeCreateIcons();
            } else {
                const filename = "Scan Halaman";
                const originalDataURL = canvasOriginal.toDataURL('image/jpeg', 0.85);

                if (!isBookMode) {
                    const pts = {
                        tl: { x: corners.tl.x * imgW, y: corners.tl.y * imgH },
                        tr: { x: corners.tr.x * imgW, y: corners.tr.y * imgH },
                        br: { x: corners.br.x * imgW, y: corners.br.y * imgH },
                        bl: { x: corners.bl.x * imgW, y: corners.bl.y * imgH }
                    };

                    warpAndFilterMat(originalMat, pts, 'canvas-output', targetW, targetH);
                    const dataUrl = canvasOutput.toDataURL('image/jpeg', quality);

                    pdfPageQueue.push({
                        id: Date.now() + Math.random().toString(36).substr(2, 9),
                        filename: filename,
                        originalSrc: originalDataURL,
                        corners: JSON.parse(JSON.stringify(corners)),
                        cornersRight: JSON.parse(JSON.stringify(cornersRight)),
                        isBookMode: false,
                        rotationAngle: rotationAngle,
                        spineX: spineX,
                        isSpineActive: isSpineActive,
                        activeFilter: activeFilter,
                        brightness: parseInt(brightnessSlider.value),
                        contrast: parseFloat(contrastSlider.value),
                        sharpen: parseFloat(sharpenSlider.value),
                        resolutionPreset: resPreset,
                        jpegQuality: quality,
                        canvasDataURL: dataUrl
                    });

                    showToast("Halaman berhasil ditambahkan ke antrean PDF.", "success");
                } else {
                    const ptsL = {
                        tl: { x: corners.tl.x * imgW, y: corners.tl.y * imgH },
                        tr: { x: corners.tr.x * imgW, y: corners.tr.y * imgH },
                        br: { x: corners.br.x * imgW, y: corners.br.y * imgH },
                        bl: { x: corners.bl.x * imgW, y: corners.bl.y * imgH }
                    };

                    const ptsR = {
                        tl: { x: cornersRight.tl.x * imgW, y: cornersRight.tl.y * imgH },
                        tr: { x: cornersRight.tr.x * imgW, y: cornersRight.tr.y * imgH },
                        br: { x: cornersRight.br.x * imgW, y: cornersRight.br.y * imgH },
                        bl: { x: cornersRight.bl.x * imgW, y: cornersRight.bl.y * imgH }
                    };

                    warpAndFilterMat(originalMat, ptsL, 'canvas-output-left', targetW, targetH);
                    warpAndFilterMat(originalMat, ptsR, 'canvas-output-right', targetW, targetH);

                    const dataUrlL = canvasOutputLeft.toDataURL('image/jpeg', quality);
                    const dataUrlR = canvasOutputRight.toDataURL('image/jpeg', quality);

                    const baseId = Date.now();
                    pdfPageQueue.push({
                        id: baseId + '_L',
                        filename: filename + ' (Kiri)',
                        originalSrc: originalDataURL,
                        corners: JSON.parse(JSON.stringify(corners)),
                        cornersRight: JSON.parse(JSON.stringify(cornersRight)),
                        isBookMode: true,
                        rotationAngle: rotationAngle,
                        spineX: spineX,
                        isSpineActive: isSpineActive,
                        activeFilter: activeFilter,
                        brightness: parseInt(brightnessSlider.value),
                        contrast: parseFloat(contrastSlider.value),
                        sharpen: parseFloat(sharpenSlider.value),
                        resolutionPreset: resPreset,
                        jpegQuality: quality,
                        canvasDataURL: dataUrlL
                    });

                    pdfPageQueue.push({
                        id: baseId + '_R',
                        filename: filename + ' (Kanan)',
                        originalSrc: originalDataURL,
                        corners: JSON.parse(JSON.stringify(corners)),
                        cornersRight: JSON.parse(JSON.stringify(cornersRight)),
                        isBookMode: true,
                        rotationAngle: rotationAngle,
                        spineX: spineX,
                        isSpineActive: isSpineActive,
                        activeFilter: activeFilter,
                        brightness: parseInt(brightnessSlider.value),
                        contrast: parseFloat(contrastSlider.value),
                        sharpen: parseFloat(sharpenSlider.value),
                        resolutionPreset: resPreset,
                        jpegQuality: quality,
                        canvasDataURL: dataUrlR
                    });

                    showToast("Dua halaman buku berhasil ditambahkan ke antrean PDF.", "success");
                }
            }

            renderThumbnails();
            addToPdfBtn.disabled = false;
            addToPdfBtn.classList.remove('disabled');
            statusDot.className = "aeroscan-status-dot ready";
            statusText.innerText = "Sistem Siap";
        }, 15);
    }

    function deletePage(pageId) {
        pdfPageQueue = pdfPageQueue.filter(p => p.id !== pageId);
        showToast("Halaman dihapus dari antrean.", "info");
        renderThumbnails();
    }

    function movePage(index, direction) {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= pdfPageQueue.length) return;

        const temp = pdfPageQueue[index];
        pdfPageQueue[index] = pdfPageQueue[newIndex];
        pdfPageQueue[newIndex] = temp;

        renderThumbnails();
    }

    function loadPageForEditing(page) {
        editingPageId = page.id;
        addToPdfBtn.innerHTML = `<i data-lucide="check-circle" style="width: 14px; height: 14px;" class="me-1 inline-block align-middle"></i><span class="align-middle">Simpan Perubahan</span>`;
        addToPdfBtn.style.borderColor = 'var(--accent-pink)';
        addToPdfBtn.style.color = 'var(--accent-pink)';
        safeCreateIcons();

        statusDot.className = "aeroscan-status-dot processing";
        statusText.innerText = "Memuat Halaman...";

        const img = new Image();
        img.onload = function () {
            canvasOriginal.width = img.width;
            canvasOriginal.height = img.height;
            const ctx = canvasOriginal.getContext('2d');
            ctx.drawImage(img, 0, 0);

            if (originalMat) originalMat.delete();
            originalMat = cv.imread(canvasOriginal);

            rotationAngle = page.rotationAngle;
            if (rotationAngle > 0) {
                let rotated = new cv.Mat();
                if (rotationAngle === 90) cv.rotate(originalMat, rotated, cv.ROTATE_90_CLOCKWISE);
                else if (rotationAngle === 180) cv.rotate(originalMat, rotated, cv.ROTATE_180);
                else if (rotationAngle === 270) cv.rotate(originalMat, rotated, cv.ROTATE_90_COUNTERCLOCKWISE);
                originalMat.delete();
                originalMat = rotated;
                cv.imshow(canvasOriginal, originalMat);
            }

            fitOriginalCanvas(originalMat.cols, originalMat.rows);

            isBookMode = page.isBookMode;
            if (isBookMode) {
                modeBookBtn.classList.add('active');
                modeSingleBtn.classList.remove('active');
                toggleSpineBtn.classList.add('hidden');
                rightHandles.forEach(h => h.classList.remove('hidden'));
                docOverlayRight.classList.remove('hidden');
                outputSingleWrapper.classList.add('hidden');
                outputBookWrapper.classList.remove('hidden');
                downloadBtn.classList.add('hidden');
                bookDownloadGroup.classList.remove('hidden');
            } else {
                modeSingleBtn.classList.add('active');
                modeBookBtn.classList.remove('active');
                toggleSpineBtn.classList.remove('hidden');
                rightHandles.forEach(h => h.classList.add('hidden'));
                docOverlayRight.classList.add('hidden');
                outputSingleWrapper.classList.remove('hidden');
                outputBookWrapper.classList.add('hidden');
                downloadBtn.classList.remove('hidden');
                bookDownloadGroup.classList.add('hidden');
            }

            spineX = page.spineX;
            isSpineActive = page.isSpineActive;
            if (isSpineActive || isBookMode) {
                spineDivider.classList.remove('hidden');
                spineDivider.style.left = `${spineX * 100}%`;
                if (isSpineActive) toggleSpineBtn.classList.add('active');
            } else {
                spineDivider.classList.add('hidden');
                toggleSpineBtn.classList.remove('active');
            }

            brightnessSlider.value = page.brightness;
            brightnessVal.innerText = page.brightness;
            contrastSlider.value = page.contrast;
            contrastVal.innerText = parseFloat(page.contrast).toFixed(2);
            sharpenSlider.value = page.sharpen;
            sharpenVal.innerText = parseFloat(page.sharpen).toFixed(1);
            qualitySlider.value = page.jpegQuality;
            qualityVal.innerText = parseFloat(page.jpegQuality).toFixed(2);
            resPresetSelect.value = page.resolutionPreset;

            activeFilter = page.activeFilter;
            filterBtns.forEach(b => {
                b.classList.remove('active');
                if (b.dataset.mode === activeFilter) b.classList.add('active');
            });

            corners = JSON.parse(JSON.stringify(page.corners));
            cornersRight = JSON.parse(JSON.stringify(page.cornersRight));

            updateSvgOverlay();
            processAndCompress();

            statusDot.className = "aeroscan-status-dot ready";
            statusText.innerText = "Sistem Siap";
            const pageNum = pdfPageQueue.findIndex(p => p.id === page.id) + 1;
            showToast(`Halaman ${pageNum} berhasil dimuat untuk penyesuaian.`, 'success');
        };
        img.src = page.originalSrc;
    }

    function renderThumbnails() {
        queueTotalBadge.innerText = `${pdfPageQueue.length} Halaman`;
        pdfPagesCountSpan.innerText = pdfPageQueue.length;

        if (pdfPageQueue.length === 0) {
            pdfThumbnailsContainer.innerHTML = `
              <div id="pdf-queue-empty-state" class="w-100 text-center text-muted fs-8 py-4">
                <i data-lucide="list-ordered" style="width: 24px; height: 24px; opacity: 0.4;" class="mb-2"></i>
                <p class="mb-0">Belum ada halaman disimpan. Unggah beberapa foto sekaligus (batch) atau tambahkan halaman aktif di atas.</p>
              </div>
            `;
            safeCreateIcons();
            generatePdfBtn.classList.add('disabled');
            return;
        }

        pdfThumbnailsContainer.innerHTML = '';

        pdfPageQueue.forEach((page, index) => {
            const card = document.createElement('div');
            card.style = `position: relative; width: 104px; display: flex; flex-direction: column; gap: 4px; flex-shrink: 0; background: rgba(11, 19, 41, 0.45); padding: 8px; border-radius: 8px; border: 1px solid var(--glass-border); text-align: center; transition: all 0.2s ease;`;

            if (editingPageId === page.id) {
                card.style.borderColor = 'var(--accent-pink)';
                card.style.boxShadow = '0 0 8px rgba(236, 72, 153, 0.4)';
            }

            const img = document.createElement('img');
            img.src = page.canvasDataURL;
            img.style = `width: 86px; height: 110px; object-fit: contain; border-radius: 4px; background: #020617; cursor: pointer; border: 1px solid rgba(255,255,255,0.05);`;
            img.title = "Klik untuk mengedit halaman ini";
            img.addEventListener('click', () => loadPageForEditing(page));

            const label = document.createElement('div');
            label.innerText = `Halaman ${index + 1}`;
            label.style = `font-size: 0.72rem; font-weight: 600; color: var(--text-light); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;`;

            const ctrlBar = document.createElement('div');
            ctrlBar.style = `display: flex; justify-content: space-between; align-items: center; margin-top: 4px;`;

            const moveLeft = document.createElement('button');
            moveLeft.innerHTML = `&larr;`;
            moveLeft.style = `background: none; border: none; color: var(--text-muted); font-size: 0.8rem; cursor: pointer; padding: 2px 6px;`;
            if (index === 0) moveLeft.style.opacity = '0.35';
            moveLeft.addEventListener('click', (e) => {
                e.stopPropagation();
                movePage(index, -1);
            });

            const moveRight = document.createElement('button');
            moveRight.innerHTML = `&rarr;`;
            moveRight.style = `background: none; border: none; color: var(--text-muted); font-size: 0.8rem; cursor: pointer; padding: 2px 6px;`;
            if (index === pdfPageQueue.length - 1) moveRight.style.opacity = '0.35';
            moveRight.addEventListener('click', (e) => {
                e.stopPropagation();
                movePage(index, 1);
            });

            const delBtn = document.createElement('button');
            delBtn.innerHTML = `&times;`;
            delBtn.style = `background: none; border: none; color: #f43f5e; font-size: 1.1rem; font-weight: bold; cursor: pointer; padding: 0 4px; line-height: 1;`;
            delBtn.title = "Hapus Halaman";
            delBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                deletePage(page.id);
            });

            ctrlBar.appendChild(moveLeft);
            ctrlBar.appendChild(delBtn);
            ctrlBar.appendChild(moveRight);

            card.appendChild(img);
            card.appendChild(label);
            card.appendChild(ctrlBar);

            pdfThumbnailsContainer.appendChild(card);
        });

        generatePdfBtn.classList.remove('disabled');
    }

    function generatePdf() {
        if (pdfPageQueue.length === 0) return;

        statusDot.className = "aeroscan-status-dot processing";
        statusText.innerText = "Membuat PDF...";
        showToast('Mengompilasi halaman PDF, harap tunggu...', 'info');
        generatePdfBtn.disabled = true;
        generatePdfBtn.classList.add('disabled');

        setTimeout(() => {
            try {
                const { jsPDF } = window.jspdf;

                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: [215, 330] // Folio Size
                });

                pdfPageQueue.forEach((page, index) => {
                    if (index > 0) {
                        pdf.addPage([215, 330], 'portrait');
                    }
                    pdf.addImage(page.canvasDataURL, 'JPEG', 0, 0, 215, 330, undefined, 'FAST');
                });

                const pdfName = pdfFilenameInput.value.trim() || 'Dokumen_Scan_Aero';
                pdf.save(`${pdfName}.pdf`);

                showToast('PDF berhasil dibuat dan diunduh!', 'success');
            } catch (err) {
                console.error("PDF generation failed:", err);
                showToast('Gagal membuat PDF: ' + err.message, 'error');
            } finally {
                statusDot.className = "aeroscan-status-dot ready";
                statusText.innerText = "Sistem Siap";
                generatePdfBtn.disabled = false;
                generatePdfBtn.classList.remove('disabled');
            }
        }, 15);
    }

    // 20. ZOOM & PAN LOGIC
    function initZoomAndPan() {
        function updateZoomTransform() {
            origWrapper.style.transform = `scale(${zoomScale}) translate(${panX}px, ${panY}px)`;
            zoomPercentSpan.innerText = `${Math.round(zoomScale * 100)}%`;
        }

        zoomInBtn.addEventListener('click', () => {
            if (!originalMat) return;
            zoomScale = Math.min(4.0, zoomScale + 0.15);
            updateZoomTransform();
        });

        zoomOutBtn.addEventListener('click', () => {
            if (!originalMat) return;
            zoomScale = Math.max(0.5, zoomScale - 0.15);
            updateZoomTransform();
        });

        zoomResetBtn.addEventListener('click', () => {
            if (!originalMat) return;
            zoomScale = 1.0;
            panX = 0;
            panY = 0;
            updateZoomTransform();
        });

        originalCanvasContainer.addEventListener('wheel', (e) => {
            if (!originalMat) return;
            e.preventDefault();
            const zoomFactor = 0.1;
            if (e.deltaY < 0) {
                zoomScale = Math.min(4.0, zoomScale + zoomFactor);
            } else {
                zoomScale = Math.max(0.5, zoomScale - zoomFactor);
            }
            updateZoomTransform();
        }, { passive: false });

        let startClientX = 0;
        let startClientY = 0;
        let startPanX = 0;
        let startPanY = 0;

        originalCanvasContainer.addEventListener('mousedown', (e) => {
            if (!originalMat) return;
            if (e.target.classList.contains('aeroscan-corner-handle') || e.target.id === 'spine-divider') return;

            isPanning = true;
            originalCanvasContainer.style.cursor = 'grabbing';
            startClientX = e.clientX;
            startClientY = e.clientY;
            startPanX = panX;
            startPanY = panY;
        });

        panMouseMoveHandler = (e) => {
            if (!isPanning) return;
            const dx = e.clientX - startClientX;
            const dy = e.clientY - startClientY;
            panX = startPanX + dx / zoomScale;
            panY = startPanY + dy / zoomScale;
            updateZoomTransform();
        };

        panMouseUpHandler = () => {
            if (isPanning) {
                isPanning = false;
                originalCanvasContainer.style.cursor = 'default';
            }
        };

        window.addEventListener('mousemove', panMouseMoveHandler);
        window.addEventListener('mouseup', panMouseUpHandler);

        originalCanvasContainer.addEventListener('touchstart', (e) => {
            if (!originalMat || e.touches.length > 1) return;
            const touch = e.touches[0];
            if (touch.target.classList.contains('aeroscan-corner-handle') || touch.target.id === 'spine-divider') return;

            isPanning = true;
            startClientX = touch.clientX;
            startClientY = touch.clientY;
            startPanX = panX;
            startPanY = panY;
        });

        originalCanvasContainer.addEventListener('touchmove', (e) => {
            if (!isPanning || e.touches.length > 1) return;
            const touch = e.touches[0];
            const dx = touch.clientX - startClientX;
            const dy = touch.clientY - startClientY;
            panX = startPanX + dx / zoomScale;
            panY = startPanY + dy / zoomScale;
            updateZoomTransform();
        }, { passive: true });

        originalCanvasContainer.addEventListener('touchend', () => {
            isPanning = false;
        });
    }

    // 21. OPTICAL CHARACTER RECOGNITION (OCR)
    function initOcrListeners() {
        tabImageBtn.addEventListener('click', () => {
            tabImageBtn.classList.add('active');
            tabOcrBtn.classList.remove('active');
            outputImageView.style.display = 'flex';
            outputOcrView.style.display   = 'none';
        });

        tabOcrBtn.addEventListener('click', () => {
            tabOcrBtn.classList.add('active');
            tabImageBtn.classList.remove('active');
            outputOcrView.style.display   = 'flex';
            outputImageView.style.display = 'none';
            if (!window._ocrEnabled) {
                const banner = document.getElementById('ocr-activate-banner');
                const panel  = document.getElementById('ocr-active-panel');
                if (banner) banner.style.display = 'flex';
                if (panel)  panel.style.display  = 'none';
            }
        });

        const ocrEnableBtn = document.getElementById('ocr-enable-btn');
        if (ocrEnableBtn) {
            ocrEnableBtn.addEventListener('click', () => {
                window._ocrEnabled = true;
                const banner = document.getElementById('ocr-activate-banner');
                const panel  = document.getElementById('ocr-active-panel');
                if (banner) banner.style.display = 'none';
                if (panel)  panel.classList.remove('hidden');
                safeCreateIcons();
                showToast('OCR diaktifkan. Model bahasa akan diunduh saat dijalankan.', 'info');
            });
        }

        runOcrBtn.addEventListener('click', runOcr);
        ocrCopyBtn.addEventListener('click', copyOcrText);
        ocrDownloadBtn.addEventListener('click', downloadOcrText);
    }

    async function runOcr() {
        if (!originalMat) {
            showToast("Unggah gambar dokumen terlebih dahulu!", "error");
            return;
        }

        const lang = ocrLangSelect.value;
        runOcrBtn.disabled = true;
        runOcrBtn.classList.add('disabled');
        ocrProgressContainer.classList.remove('hidden');
        ocrResultText.value = '';

        ocrProgressStatus.innerText = "Menginisialisasi Tesseract.js...";
        ocrProgressPercent.innerText = "0%";
        ocrProgressBar.style.width = "0%";

        try {
            if (!isBookMode) {
                const result = await Tesseract.recognize(
                    canvasOutput,
                    lang,
                    {
                        logger: m => updateOcrProgress(m, 1, 1)
                    }
                );
                ocrResultText.value = result.data.text;
                showToast("OCR selesai! Teks berhasil diekstrak.", "success");
            } else {
                ocrProgressStatus.innerText = "Membaca Halaman Kiri (1/2)...";
                const resultLeft = await Tesseract.recognize(
                    canvasOutputLeft,
                    lang,
                    {
                        logger: m => updateOcrProgress(m, 1, 2)
                    }
                );

                ocrProgressStatus.innerText = "Membaca Halaman Kanan (2/2)...";
                const resultRight = await Tesseract.recognize(
                    canvasOutputRight,
                    lang,
                    {
                        logger: m => updateOcrProgress(m, 2, 2)
                    }
                );

                ocrResultText.value = `--- HALAMAN 1 (KIRI) ---\n\n${resultLeft.data.text}\n\n\n--- HALAMAN 2 (KANAN) ---\n\n${resultRight.data.text}`;
                showToast("OCR Buku selesai! Kedua halaman berhasil diekstrak.", "success");
            }

            ocrProgressStatus.innerText = "Selesai!";
            ocrProgressPercent.innerText = "100%";
            ocrProgressBar.style.width = "100%";
        } catch (err) {
            console.error(err);
            showToast("Gagal memproses OCR: " + err.message, "error");
            ocrProgressStatus.innerText = "Gagal memproses.";
        } finally {
            runOcrBtn.disabled = false;
            runOcrBtn.classList.remove('disabled');
        }
    }

    function updateOcrProgress(m, currentPage, totalPages) {
        if (m.status === 'recognizing text') {
            const progress = Math.round(m.progress * 100);
            ocrProgressStatus.innerText = `Membaca Halaman ${currentPage}/${totalPages}: ${progress}%`;

            const overallProgress = Math.round(((currentPage - 1) * 100 + progress) / totalPages);
            ocrProgressPercent.innerText = `${overallProgress}%`;
            ocrProgressBar.style.width = `${overallProgress}%`;
        } else {
            let statusFriendly = m.status;
            if (m.status.includes('loading tesseract core')) statusFriendly = "Memuat Core OCR...";
            else if (m.status.includes('loading')) statusFriendly = "Memuat kamus bahasa...";
            else if (m.status.includes('initializing api')) statusFriendly = "Menyiapkan mesin OCR...";

            ocrProgressStatus.innerText = `${statusFriendly}`;
        }
    }

    function copyOcrText() {
        const text = ocrResultText.value;
        if (!text) {
            showToast("Tidak ada teks untuk disalin!", "error");
            return;
        }
        navigator.clipboard.writeText(text)
            .then(() => showToast("Teks berhasil disalin ke clipboard!", "success"))
            .catch(err => showToast("Gagal menyalin teks: " + err.message, "error"));
    }

    function downloadOcrText() {
        const text = ocrResultText.value;
        if (!text) {
            showToast("Tidak ada teks untuk diunduh!", "error");
            return;
        }
        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `hasil_ocr_${Date.now()}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        showToast("Teks berhasil diunduh sebagai berkas .txt!", "success");
    }

    // Clean up camera stream and window listeners on Turbo visit / navigation
    document.addEventListener("turbo:before-cache", function cleanup() {
        if (window._aeroScanCleanup) {
            window._aeroScanCleanup();
        }
        document.removeEventListener("turbo:before-cache", cleanup);
    });

    // Check if OpenCV is already loaded and ready
    if (typeof cv !== 'undefined' && cv.Mat) {
        window.onOpenCvReady();
    }
})();
</script>
