<?php
// views/dashboard_view.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .dashboard-header {
            background-color: #0d6efd;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .card-menu {
            transition: transform 0.2s;
        }
        .card-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="dashboard-header">
    <div class="container d-flex justify-content-between align-items-center">
        <h2>Dashboard SINTA-SaaS</h2>
        <div>
            <span>Selamat datang, <strong><?= htmlspecialchars($data['user']['nama'] ?? 'Guest', ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($data['user']['role'] ?? 'No Role', ENT_QUOTES, 'UTF-8') ?>)</span>
            <a href="/sinta/logout" class="btn btn-sm btn-outline-light ms-3">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card card-menu h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Profil Siswa</h5>
                    <p class="card-text text-muted">Lihat dan lengkapi data profil siswa.</p>
                    <a href="/sinta/siswa/profil" class="btn btn-primary btn-sm">Buka Menu</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card card-menu h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Akademik</h5>
                    <p class="card-text text-muted">Informasi nilai dan absensi akademik.</p>
                    <a href="/sinta/akademik/dashboard" class="btn btn-primary btn-sm">Buka Menu</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card card-menu h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Keuangan</h5>
                    <p class="card-text text-muted">Informasi tagihan dan pembayaran SPP.</p>
                    <a href="/sinta/keuangan/tagihan" class="btn btn-primary btn-sm">Buka Menu</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card card-menu h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Perpustakaan</h5>
                    <p class="card-text text-muted">Lihat katalog dan status peminjaman buku.</p>
                    <a href="/sinta/perpustakaan/katalog" class="btn btn-primary btn-sm">Buka Menu</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
