<div class="container-fluid py-3 px-0 px-md-4">
    <div class="row mb-4 mx-0">
        <div class="col-12 px-1 px-md-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <a href="/SINTA-SaaS/dashboard" class="btn btn-light rounded-circle shadow-sm" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-left fs-5"></i>
                    </a>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark" style="font-family: 'Inter', sans-serif;">Arsip Pengumuman</h4>
                        <p class="text-muted mb-0 fs-7">Seluruh informasi dan pengumuman untuk Anda</p>
                    </div>
                </div>
                
                <form method="GET" action="/SINTA-SaaS/pengumuman/arsip" class="d-flex flex-wrap gap-2 w-100 mt-3 mt-md-0" style="max-width: 800px;">
                    <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border flex-nowrap" style="min-width: 200px; flex: 1;">
                        <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-transparent shadow-none" placeholder="Cari judul..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                    
                    <select name="kategori" class="form-select shadow-sm rounded-pill border" style="width: auto; min-width: 150px; background-color: white;">
                        <option value="">Semua Kategori</option>
                        <?php foreach (($kategori_list ?? []) as $kat): ?>
                            <option value="<?= $kat['id'] ?>" <?= ($kategori ?? '') === $kat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" name="tanggal" class="form-control shadow-sm rounded-pill border" style="width: auto;" value="<?= htmlspecialchars($tanggal ?? '') ?>" title="Pilih Tanggal">
                    
                    <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" type="submit">Cari</button>
                    
                    <?php if (!empty($search) || !empty($kategori) || !empty($tanggal)): ?>
                        <a href="/SINTA-SaaS/pengumuman/arsip" class="btn btn-light rounded-pill px-3 border shadow-sm text-danger" title="Reset Filter"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="row mb-4 mx-0">
        <div class="col-12 px-1 px-md-3">
            <div class="timeline-container position-relative ps-0 ps-md-2">
                <?php if (empty($pengumuman_list)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-3"><i class="bi bi-inbox fs-1"></i></div>
                        <h5 class="fw-bold">Belum Ada Pengumuman</h5>
                        <p class="text-muted">Tidak ada pengumuman yang sesuai dengan kriteria atau pencarian Anda.</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $currentGroup = '';
                    $bulanIndo = [
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                        '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                    ];
                    
                    foreach ($pengumuman_list as $pengumuman): 
                        $monthNum = date('m', strtotime($pengumuman['created_at']));
                        $year = date('Y', strtotime($pengumuman['created_at']));
                        $groupLabel = $bulanIndo[$monthNum] . " " . $year;
                        
                        if ($currentGroup !== $groupLabel):
                            $currentGroup = $groupLabel;
                    ?>
                    <div class="mb-3 position-relative z-2">
                        <span class="badge px-3 py-2 fs-7 rounded-2 shadow-sm" style="background-color: #0b5ed7; color: white;"><?= $groupLabel ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex position-relative mb-4">
                        <!-- Vertical Line (hides on last item if needed, but we keep it simple here) -->
                        <div class="position-absolute h-100" style="left: 15px; top: 32px; width: 2px; background-color: #dee2e6; z-index: 1;"></div>
                        
                        <!-- Icon -->
                        <div class="flex-shrink-0 z-2 position-relative mt-1" style="width: 32px;">
                            <div class="bg-primary bg-gradient rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm border border-2 border-white" style="width: 32px; height: 32px;">
                                <i class="bi bi-envelope-fill fs-7"></i>
                            </div>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="flex-grow-1 ms-2 ms-sm-3">
                            <div class="card border border-light-subtle shadow-sm rounded-3">
                                <div class="card-body p-2 p-md-3">
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start mb-2 gap-1">
                                        <h5 class="fw-bold mb-0 text-uppercase" style="color: #0d6efd; font-family: 'Inter', sans-serif; font-size: 0.85rem; line-height: 1.4;">
                                            <?= htmlspecialchars($pengumuman['judul']) ?>
                                        </h5>
                                        <div class="text-muted text-end align-self-end align-self-sm-start" style="font-size: 0.75rem;">
                                            <i class="bi bi-calendar-event me-1"></i> <?= date('d-m-Y', strtotime($pengumuman['created_at'])) ?> <i class="bi bi-clock ms-1 me-1"></i> <?= date('H:i', strtotime($pengumuman['created_at'])) ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (($_SESSION['role_name'] ?? '') === 'super_admin'): ?>
                                    <div class="mb-2 d-flex flex-wrap gap-1 align-items-center text-muted" style="font-size: 0.7rem;">
                                        <span><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($pengumuman['nama_pembuat']) ?></span>
                                        <span>•</span>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill border px-2 py-1"><i class="bi bi-building me-1"></i><?= htmlspecialchars($pengumuman['nama_sekolah'] ?? 'Global') ?></span>
                                        <span>•</span>
                                        <?php 
                                            $vis = strtolower($pengumuman['visibilitas']);
                                            $badgeClass = 'bg-primary';
                                            if ($vis === 'siswa') $badgeClass = 'bg-success';
                                            elseif ($vis === 'guru') $badgeClass = 'bg-info';
                                            elseif ($vis === 'private') $badgeClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $badgeClass ?> text-white rounded-pill px-2 py-1"><i class="bi bi-globe2 me-1"></i> <?= ucfirst($vis) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-dark mt-2 pengumuman-content text-break" style="font-size: 0.8rem; line-height: 1.5;">
                                        <?= $pengumuman['isi_pengumuman'] ?>
                                    </div>
                                    
                                    <?php if ($pengumuman['lampiran_file']): ?>
                                    <?php 
                                        $ext = strtolower(pathinfo($pengumuman['lampiran_file'], PATHINFO_EXTENSION)); 
                                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    ?>
                                    <div class="mt-3 pt-2 border-top">
                                        <?php if ($isImage): ?>
                                            <div class="mb-2 rounded-3 overflow-hidden shadow-sm" style="max-height: 150px; cursor: pointer; max-width: 250px;" onclick="window.open('/SINTA-SaaS/storage/app/public/<?= htmlspecialchars($pengumuman['lampiran_file']) ?>', '_blank')">
                                                <img src="/SINTA-SaaS/storage/app/public/<?= htmlspecialchars($pengumuman['lampiran_file']) ?>" class="w-100 h-100 object-fit-cover hover-zoom" alt="Lampiran Pengumuman">
                                            </div>
                                        <?php endif; ?>
                                        <a href="/SINTA-SaaS/storage/app/public/<?= htmlspecialchars($pengumuman['lampiran_file']) ?>" target="_blank" class="btn btn-primary fw-semibold d-flex d-md-inline-flex justify-content-center align-items-center rounded-3 px-3 py-1 shadow-sm w-100 w-md-auto" style="font-size: 0.8rem; transition: all 0.2s ease;">
                                            <i class="bi <?= $isImage ? 'bi-image' : 'bi-cloud-download-fill' ?> me-2"></i> 
                                            <?= $isImage ? 'Lihat Gambar' : 'Unduh Lampiran' ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="row mt-3 mb-5">
        <div class="col-12 d-flex justify-content-center">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-lg border-0 shadow-sm rounded-pill overflow-hidden bg-white">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link border-0 text-dark fw-bold px-4" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&kategori=<?= urlencode($kategori ?? '') ?>&tanggal=<?= urlencode($tanggal ?? '') ?>">Sebelumnya</a>
                    </li>
                    
                    <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link border-0 <?= ($i === $page) ? 'bg-primary bg-gradient shadow-inner text-white' : 'text-dark' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&kategori=<?= urlencode($kategori ?? '') ?>&tanggal=<?= urlencode($tanggal ?? '') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link border-0 text-dark fw-bold px-4" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&kategori=<?= urlencode($kategori ?? '') ?>&tanggal=<?= urlencode($tanggal ?? '') ?>">Selanjutnya</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
    .announcement-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important;
    }
    .hover-zoom:hover {
        transform: scale(1.05);
    }
    .hover-shadow:hover {
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .pagination .page-link {
        transition: all 0.2s ease;
    }
    .pagination .page-item.active .page-link {
        z-index: 3;
        color: #fff !important;
        background-color: #0d6efd;
    }
</style>
