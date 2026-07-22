<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚪 Gate Pass Presensi Pintu — Perpustakaan Digital</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .gate-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 2rem;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.4);
        }

        .clock-display {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 2px;
        }
    </style>
</head>
<body class="p-4 p-md-5">

<!-- Clock Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-door-open-fill display-4"></i>
        <div>
            <h2 class="fw-extrabold mb-0">GATE PASS PERPUS</h2>
            <p class="mb-0 text-white-50">Buku Tamu Digital & Presensi Masuk Ruang Perpustakaan</p>
        </div>
    </div>
    <div class="text-end">
        <div id="liveClock" class="clock-display">00:00:00</div>
        <small class="text-white-50"><?= date('l, d F Y') ?></small>
    </div>
</div>

<!-- Main Kiosk Card -->
<div class="row justify-content-center my-auto">
    <div class="col-12 col-lg-7">
        <div class="gate-card p-5 text-center">
            <i class="bi bi-qr-code-scan display-1 mb-4 d-block"></i>
            <h2 class="fw-bold mb-2">SELAMAT DATANG!</h2>
            <p class="fs-5 text-white-50 mb-4">Silakan dekatkan QR Code Kartu Anggota / Ketik NISN Anda untuk mencatat kunjungan.</p>
            
            <form action="/SINTA-SaaS/perpustakaan/buku-tamu" method="POST" class="max-w-400 mx-auto">
                <div class="input-group input-group-lg mb-3">
                    <span class="input-group-text bg-white border-0 text-primary"><i class="bi bi-person-vcard fs-4"></i></span>
                    <input type="text" name="nisn" class="form-control border-0 fs-5" placeholder="Scan QR / Ketik NISN..." autofocus required>
                    <button class="btn btn-dark px-4 fw-bold" type="submit">MASUK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="text-center text-white-50 mt-auto pt-4 fs-7">
    <p>© <?= date('Y') ?> SINTA-SaaS Automated Library Gate Access System.</p>
</footer>

<script>
function updateClock() {
    const now = new Date();
    document.getElementById('liveClock').innerText = now.toLocaleTimeString('id-ID');
}
setInterval(updateClock, 1000);
updateClock();
</script>
</body>
</html>
