<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🖥️ Kios Peminjaman Mandiri — Perpustakaan Digital</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .kiosk-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .scanner-glow {
            border: 2px dashed #38bdf8;
            background: rgba(56, 189, 248, 0.05);
            border-radius: 1rem;
            animation: pulse-glow 2s infinite alternate;
        }

        @keyframes pulse-glow {
            0% { box-shadow: 0 0 10px rgba(56, 189, 248, 0.2); }
            100% { box-shadow: 0 0 25px rgba(56, 189, 248, 0.6); }
        }
    </style>
</head>
<body class="p-3 p-md-5">

<!-- Navigation Top Bar -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-display text-info display-5"></i>
        <div>
            <h3 class="fw-extrabold text-white mb-0">KIOS PEMINJAMAN MANDIRI</h3>
            <small class="text-info">Self-Service Book Checkout & Return Station</small>
        </div>
    </div>
    <div>
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-light rounded-pill px-4">
            <i class="bi bi-box-arrow-left me-1"></i> Keluar Mode Kios
        </a>
    </div>
</div>

<!-- Main Kiosk Body -->
<div class="row g-4 justify-content-center my-auto">
    <!-- Step 1: Scan Member Card -->
    <div class="col-12 col-lg-5">
        <div class="kiosk-card p-4 p-md-5 h-100 text-center d-flex flex-column justify-content-center">
            <span class="badge bg-info-subtle text-info rounded-pill mb-3 align-self-center px-3 py-2 fs-7">LANGKAH 1</span>
            <h4 class="fw-bold text-white mb-3">Scan Kartu Anggota (QR / Barcode)</h4>
            <div class="scanner-glow p-4 my-3 text-center">
                <i class="bi bi-qr-code-scan text-info display-1 mb-2 d-block"></i>
                <small class="text-slate-300">Dekatkan QR Code pada Kartu Siswa/Guru ke Laser Scanner</small>
            </div>
            <div class="mt-3">
                <input type="text" class="form-control form-control-lg text-center bg-dark text-white border-secondary rounded-3" 
                       placeholder="Atau Ketik No Anggota (e.g. ANG-2026-0001)..." autofocus>
            </div>
        </div>
    </div>

    <!-- Step 2: Scan Book Barcode -->
    <div class="col-12 col-lg-5">
        <div class="kiosk-card p-4 p-md-5 h-100 text-center d-flex flex-column justify-content-center">
            <span class="badge bg-success-subtle text-success rounded-pill mb-3 align-self-center px-3 py-2 fs-7">LANGKAH 2</span>
            <h4 class="fw-bold text-white mb-3">Scan Barcode Eksemplar Buku</h4>
            <div class="scanner-glow p-4 my-3 text-center" style="border-color: #4ade80;">
                <i class="bi bi-upc-scan text-success display-1 mb-2 d-block"></i>
                <small class="text-slate-300">Scan Barcode Buku di Belakang Sampul (LIB-xxxx)</small>
            </div>
            <div class="mt-3">
                <button type="button" class="btn btn-success btn-lg w-100 rounded-3 fw-bold py-3">
                    <i class="bi bi-check-circle-fill me-2"></i> KELUARKAN BUKU (PROSES PINJAM)
                </button>
            </div>
        </div>
    </div>
</div>

<footer class="text-center text-slate-400 mt-auto pt-4 fs-7">
    <p class="mb-0">Perpustakaan Digital SINTA-SaaS — Standar Akreditasi Kios Mandiri Kemenristekdikti.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
