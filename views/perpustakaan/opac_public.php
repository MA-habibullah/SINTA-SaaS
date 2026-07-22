<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? 'OPAC Publik — Katalog Perpustakaan Digital') ?></title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --accent-gradient: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            --card-hover-shadow: 0 20px 30px -10px rgba(37, 99, 235, 0.15);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }

        /* Hero Banner */
        .hero-banner {
            background: var(--primary-gradient);
            color: white;
            padding: 4rem 1rem 5rem 1rem;
            border-radius: 0 0 2rem 2rem;
            position: relative;
            box-shadow: 0 15px 30px rgba(30, 58, 138, 0.2);
        }

        .search-card {
            margin-top: -3rem;
            z-index: 10;
        }

        .book-card {
            border: none;
            border-radius: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--card-shadow);
            background: #ffffff;
            height: 100%;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }

        .book-cover-placeholder {
            height: 200px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 3.5rem;
        }

        .badge-ddc {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 0.5rem;
        }

        .nav-brand-logo {
            font-weight: 800;
            letter-spacing: -0.5px;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 nav-brand-logo" href="/SINTA-SaaS/perpustakaan/opac">
            <i class="bi bi-journal-bookmark-fill text-primary fs-3"></i>
            <span>SINTA OPAC <span class="badge bg-primary fs-8">Publik</span></span>
        </a>
        <div class="d-flex gap-2">
            <a href="/SINTA-SaaS/login" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login Anggota / Pustakawan
            </a>
        </div>
    </div>
</nav>

<!-- Hero Search Banner -->
<header class="hero-banner text-center">
    <div class="container max-w-768">
        <h1 class="fw-extrabold display-5 mb-2">Pencarian Katalog Perpustakaan Digital</h1>
        <p class="text-blue-100 fs-5 mb-4">Cari referensi buku fisik, e-book, publikasi ilmiah, dan modul pembelajaran secara online.</p>
    </div>
</header>

<!-- Main Container -->
<main class="container mb-5">
    <!-- Search Box Card -->
    <div class="row justify-content-center search-card mb-5">
        <div class="col-12 col-lg-9">
            <div class="card border-0 shadow-lg rounded-4 p-3 p-md-4 bg-white">
                <form method="GET" action="/SINTA-SaaS/perpustakaan/opac" class="row g-2 align-items-center">
                    <div class="col-12 col-md-9 position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted fs-5"></i>
                        <input type="text" name="q" class="form-control form-control-lg ps-5 rounded-3 border-0 bg-light fs-6" 
                               placeholder="Ketik judul buku, pengarang, penerbit, atau kode DDC..." 
                               value="<?= htmlspecialchars($data['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autofocus>
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3 fw-semibold fs-6">
                            <i class="bi bi-search me-1"></i> Cari Katalog
                        </button>
                    </div>
                </form>

                <!-- DDC Quick Filter Pills -->
                <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top align-items-center">
                    <small class="fw-bold text-muted me-2"><i class="bi bi-tags me-1"></i> Kategori DDC:</small>
                    <a href="?q=" class="btn btn-xs btn-outline-secondary rounded-pill fs-8">Semua</a>
                    <a href="?q=000" class="btn btn-xs btn-outline-primary rounded-pill fs-8">000 Karya Umum</a>
                    <a href="?q=300" class="btn btn-xs btn-outline-primary rounded-pill fs-8">300 Ilmu Sosial</a>
                    <a href="?q=500" class="btn btn-xs btn-outline-primary rounded-pill fs-8">500 Sains</a>
                    <a href="?q=600" class="btn btn-xs btn-outline-primary rounded-pill fs-8">600 Teknologi</a>
                    <a href="?q=800" class="btn btn-xs btn-outline-primary rounded-pill fs-8">800 Sastra</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Hasil Pencarian Katalog</h4>
            <small class="text-muted">Menampilkan <?= count($data['list']) ?> koleksi buku yang tersedia.</small>
        </div>
        <?php if (!empty($data['query'])): ?>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7">
                Kata Kunci: "<?= htmlspecialchars($data['query'], ENT_QUOTES, 'UTF-8') ?>"
            </span>
        <?php endif; ?>
    </div>

    <!-- Book Grid -->
    <div class="row g-4">
        <?php if (empty($data['list'])): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <i class="bi bi-search-heart text-muted display-1 mb-3"></i>
                    <h5 class="fw-bold text-dark">Koleksi Tidak Ditemukan</h5>
                    <p class="text-muted mb-3">Tidak ada buku yang cocok dengan kata kunci pencarian Anda. Silakan coba kata kunci lain.</p>
                    <div>
                        <a href="/SINTA-SaaS/perpustakaan/opac" class="btn btn-outline-primary rounded-pill px-4">
                            Reset Pencarian
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($data['list'] as $book): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card book-card p-3 d-flex flex-column">
                        <!-- Book Cover Placeholder / Image -->
                        <div class="book-cover-placeholder mb-3">
                            <?php if (!empty($book['cover'])): ?>
                                <img src="<?= htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8') ?>" alt="Cover" class="w-100 h-100 object-fit-cover rounded-3">
                            <?php else: ?>
                                <i class="bi bi-book"></i>
                            <?php endif; ?>
                        </div>

                        <!-- Book Details -->
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge-ddc">DDC <?= htmlspecialchars($book['klasifikasi_ddc'] ?? '000', ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($book['is_ebook'])): ?>
                                    <span class="badge bg-primary-subtle text-primary"><i class="bi bi-file-earmark-pdf me-1"></i> E-Book</span>
                                <?php endif; ?>
                            </div>
                            <h5 class="fw-bold text-dark mb-1 text-truncate-2" style="min-height: 2.8rem;">
                                <?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?>
                            </h5>
                            <p class="text-muted fs-7 mb-1"><i class="bi bi-person me-1"></i> <?= htmlspecialchars($book['pengarang'] ?? 'Penulis Tidak Diketahui', ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-muted fs-8 mb-3"><i class="bi bi-building me-1"></i> <?= htmlspecialchars($book['penerbit'] ?? 'Penerbit -', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <!-- Footer & Status -->
                        <div class="border-top pt-3 mt-2 d-flex justify-content-between align-items-center">
                            <div>
                                <?php $tersedia = (int)($book['total_tersedia'] ?? 0); ?>
                                <?php if ($tersedia > 0): ?>
                                    <span class="badge bg-success-subtle text-success fs-8"><i class="bi bi-check-circle me-1"></i> Tersedia: <?= $tersedia ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger fs-8"><i class="bi bi-x-circle me-1"></i> Dipinjam Semua</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($book['is_ebook'])): ?>
                                <a href="/SINTA-SaaS/perpustakaan/baca-ebook" class="btn btn-sm btn-primary rounded-pill px-3 fs-8">
                                    <i class="bi bi-eye me-1"></i> Baca Online
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Footer Section -->
<footer class="bg-dark text-white-50 py-4 mt-auto">
    <div class="container text-center fs-7">
        <p class="mb-1">© <?= date('Y') ?> <strong>SINTA-SaaS Perpustakaan Digital</strong>. Seluruh Hak Cipta Dilindungi.</p>
        <small class="text-muted">Online Public Access Catalog (OPAC) — Standar Akreditasi Perpustakaan Sekolah Indonesia.</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
