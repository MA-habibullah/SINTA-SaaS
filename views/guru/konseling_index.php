<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap justify-between items-end gap-4">
        <div>
            <h1 class="h3 mb-0 text-slate-800 font-bold"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="text-slate-500 text-sm mt-1">Ruang Pengajuan Konseling Personal & Dispensasi</p>
        </div>
        <div>
            <button class="btn bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl px-4 py-2 text-sm shadow-sm shadow-blue-500/30 transition-all flex items-center gap-2" data-bs-toggle="modal" data-bs-target="#pengajuanModal">
                <i class="bi bi-pencil-square"></i> Buat Pengajuan Baru
            </button>
        </div>
    </div>

    <!-- Active Cases/Sessions -->
    <div class="glass-card rounded-3xl p-6 hover-lift mb-8">
        <h5 class="font-bold text-slate-800 text-lg mb-6 border-b border-slate-100 pb-4">
            <i class="bi bi-calendar-event text-blue-500 mr-2"></i> Jadwal Bimbingan & Pemantauan Anda
        </h5>
        
        <?php if (empty($kasusAktif)): ?>
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                    <i class="bi bi-emoji-smile text-3xl text-emerald-400"></i>
                </div>
                <h6 class="font-bold text-slate-500 m-0">Kinerja Anda Luar Biasa!</h6>
                <p class="text-sm text-slate-400 mt-1 mb-0">Tidak ada jadwal pembinaan atau pemantauan yang aktif saat ini.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($kasusAktif as $kasus): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="border border-slate-100 rounded-2xl p-5 bg-white shadow-sm hover:shadow-md transition-all h-100 flex flex-col relative overflow-hidden">
                            <?php if ($kasus['status_kasus'] === 'Merah'): ?>
                                <div class="absolute top-0 left-0 w-1 h-100 bg-red-500"></div>
                            <?php else: ?>
                                <div class="absolute top-0 left-0 w-1 h-100 bg-yellow-400"></div>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">
                                        <?= htmlspecialchars($kasus['sumber_deteksi']) ?>
                                    </span>
                                </div>
                                <?php if ($kasus['status_kasus'] === 'Merah'): ?>
                                    <span class="badge bg-red-100 text-red-600 border border-red-200">Peringatan</span>
                                <?php else: ?>
                                    <span class="badge bg-yellow-100 text-yellow-600 border border-yellow-200">Pemantauan</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-sm text-slate-700 font-medium line-clamp-3 mb-4 flex-grow">
                                "<?= htmlspecialchars($kasus['deskripsi_kasus']) ?>"
                            </p>
                            
                            <div class="pt-4 border-t border-slate-100">
                                <?php if ($kasus['status_sesi'] === 'Dijadwalkan' && !empty($kasus['tanggal_sesi'])): ?>
                                    <div class="flex items-center gap-2 text-blue-600 bg-blue-50 p-2 rounded-lg text-sm font-semibold">
                                        <i class="bi bi-calendar-check"></i>
                                        Sesi: <?= date('d M Y H:i', strtotime($kasus['tanggal_sesi'])) ?>
                                    </div>
                                <?php elseif ($kasus['status_sesi'] === 'Selesai'): ?>
                                    <button class="btn btn-sm w-100 bg-slate-800 text-white font-semibold rounded-lg">
                                        Lihat Komitmen RTL
                                    </button>
                                <?php else: ?>
                                    <div class="text-xs text-slate-500 flex items-center gap-1">
                                        <i class="bi bi-clock"></i> Menunggu penjadwalan dari Kepala Sekolah
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Pengajuan Baru -->
<div class="modal fade" id="pengajuanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-3xl shadow-xl overflow-hidden">
            <div class="modal-header bg-slate-50 border-b border-slate-100 px-6 py-4">
                <h5 class="modal-title font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-envelope-paper text-blue-500"></i> Pengajuan Konseling Privat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-6 py-5">
                <form action="/SINTA-SaaS/konseling/store" method="POST">
                    <div class="mb-4">
                        <p class="text-sm text-slate-500 bg-blue-50 text-blue-800 p-3 rounded-xl border border-blue-100 mb-4">
                            <i class="bi bi-info-circle mr-1"></i> Form ini digunakan untuk mengajukan pertemuan bimbingan tatap muka dengan Kepala Sekolah untuk kasus personal atau permintaan dispensasi khusus.
                        </p>
                    </div>
                    
                    <div class="mb-5">
                        <label class="form-label text-sm font-semibold text-slate-700">Alasan / Detail Pengajuan</label>
                        <textarea name="deskripsi_kasus" rows="4" class="form-control border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500" placeholder="Jelaskan secara singkat alasan Anda..." required></textarea>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="button" class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl py-2 px-4 transition-colors" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl py-2 px-4 transition-colors flex-grow">
                            <i class="bi bi-send"></i> Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
