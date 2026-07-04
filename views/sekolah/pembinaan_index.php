<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap justify-between items-end gap-4">
        <div>
            <h1 class="h3 mb-0 text-slate-800 font-bold"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="text-slate-500 text-sm mt-1">Dasbor Pemantauan dan Pendampingan Kinerja Guru</p>
        </div>
        <div class="flex gap-2">
            <?php
            $tenantParam = isset($_GET['tenant_id']) ? '?tenant_id=' . urlencode($_GET['tenant_id']) : '';
            ?>
            <button class="btn bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl px-4 py-2 text-sm shadow-sm transition-all flex items-center gap-2">
                <i class="bi bi-file-earmark-pdf text-red-500"></i> Laporan Akreditasi
            </button>
            <a href="/SINTA-SaaS/pembinaan/cetak<?= $tenantParam ?>" target="_blank" class="btn bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold rounded-xl px-4 py-2 text-sm shadow-sm transition-all flex items-center gap-2">
                <i class="bi bi-printer"></i> Cetak Laporan
            </a>
            <button class="btn bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl px-4 py-2 text-sm shadow-sm shadow-blue-500/30 transition-all flex items-center gap-2" data-bs-toggle="modal" data-bs-target="#addKasusModal">
                <i class="bi bi-plus-lg"></i> Input Kasus Manual
            </button>
        </div>
    </div>

    <!-- Traffic Light Status Cards -->
    <div class="row g-4 mb-8">
        <!-- Merah (Peringatan) -->
        <div class="col-12 col-md-4">
            <div class="glass-card rounded-3xl p-6 hover-lift h-100 border-l-4 border-l-red-500 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="bi bi-exclamation-octagon-fill text-8xl text-red-600"></i>
                </div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center text-red-600 shadow-inner">
                        <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                    </div>
                    <div>
                        <h6 class="font-bold text-slate-700 m-0">Butuh Tindakan Cepat</h6>
                        <span class="text-xs font-semibold text-red-500 uppercase tracking-wider">Status Merah</span>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <h2 class="text-4xl font-black text-slate-800 m-0 leading-none"><?= $stats['Merah'] ?? 0 ?></h2>
                    <span class="text-slate-500 font-medium pb-1">Guru</span>
                </div>
                <p class="text-xs text-slate-500 mt-4 mb-0 leading-relaxed">
                    Terdeteksi pelanggaran disiplin/kinerja. Segera jadwalkan sesi mentoring (Coaching Clinic).
                </p>
            </div>
        </div>
        
        <!-- Kuning (Pemantauan) -->
        <div class="col-12 col-md-4">
            <div class="glass-card rounded-3xl p-6 hover-lift h-100 border-l-4 border-l-yellow-400 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="bi bi-arrow-repeat text-8xl text-yellow-500"></i>
                </div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-100 flex items-center justify-center text-yellow-600 shadow-inner">
                        <i class="bi bi-eye-fill text-xl"></i>
                    </div>
                    <div>
                        <h6 class="font-bold text-slate-700 m-0">Masa Pemantauan</h6>
                        <span class="text-xs font-semibold text-yellow-600 uppercase tracking-wider">Status Kuning</span>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <h2 class="text-4xl font-black text-slate-800 m-0 leading-none"><?= $stats['Kuning'] ?? 0 ?></h2>
                    <span class="text-slate-500 font-medium pb-1">Guru</span>
                </div>
                <p class="text-xs text-slate-500 mt-4 mb-0 leading-relaxed">
                    Sedang dalam tahap evaluasi pasca-pendampingan (2-4 minggu) menuju perbaikan.
                </p>
            </div>
        </div>
        
        <!-- Hijau (Selesai/Normal) -->
        <div class="col-12 col-md-4">
            <div class="glass-card rounded-3xl p-6 hover-lift h-100 border-l-4 border-l-emerald-500 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="bi bi-check-circle-fill text-8xl text-emerald-600"></i>
                </div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 shadow-inner">
                        <i class="bi bi-shield-check text-xl"></i>
                    </div>
                    <div>
                        <h6 class="font-bold text-slate-700 m-0">Kinerja Normal</h6>
                        <span class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Status Hijau</span>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <h2 class="text-4xl font-black text-slate-800 m-0 leading-none"><?= $stats['Hijau'] ?? 0 ?></h2>
                    <span class="text-slate-500 font-medium pb-1">Guru</span>
                </div>
                <p class="text-xs text-slate-500 mt-4 mb-0 leading-relaxed">
                    Guru dengan performa stabil atau telah menyelesaikan evaluasi dengan hasil membaik.
                </p>
            </div>
        </div>
    </div>

    <!-- Active Cases Table -->
    <div class="bg-white rounded-3xl p-6 shadow-sm mb-8 border border-slate-100">
        <div class="flex justify-between items-center mb-6">
            <h5 class="font-bold text-slate-800 text-lg m-0 flex items-center gap-2">
                <i class="bi bi-list-task text-blue-500"></i>
                Daftar Peringatan & Pemantauan Aktif
            </h5>
        </div>
        
        <?php if (empty($kasusAktif)): ?>
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                    <i class="bi bi-emoji-smile text-3xl text-emerald-400"></i>
                </div>
                <h6 class="font-bold text-slate-500 m-0">Luar Biasa!</h6>
                <p class="text-sm text-slate-400 mt-1 mb-0">Tidak ada kasus peringatan aktif saat ini. Kinerja sekolah stabil.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table id="table-kasus-aktif" class="table w-100 align-middle">
                <thead>
                    <tr class="text-slate-700 text-sm font-semibold">
                        <th class="pb-3 w-48 border-0 border-t border-b border-slate-200">Nama Guru</th>
                        <th class="pb-3 w-32 border-0 border-t border-b border-slate-200">Kategori</th>
                        <th class="pb-3 border-0 border-t border-b border-slate-200">Deskripsi Kasus</th>
                        <th class="pb-3 w-40 text-center border-0 border-t border-b border-slate-200">Status</th>
                        <th class="text-center pb-3 w-40 border-0 border-t border-b border-slate-200">Aksi Pembinaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                        <?php foreach ($kasusAktif as $kasus): ?>
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="py-4">
                                    <div class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($kasus['nama_guru']) ?></div>
                                    <div class="text-xs text-slate-500 mt-1"><i class="bi bi-clock-history"></i> Sejak <?= date('d M Y', strtotime($kasus['created_at'])) ?></div>
                                </td>
                                <td class="py-4">
                                    <?php 
                                        $dotColor = match($kasus['kategori_masalah']) {
                                            'Kedisiplinan' => 'bg-purple-500',
                                            'Akademik' => 'bg-blue-500',
                                            'Personal' => 'bg-orange-500',
                                            default => 'bg-slate-500'
                                        };
                                    ?>
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                                        <span class="w-2 h-2 rounded-full <?= $dotColor ?>"></span>
                                        <?= htmlspecialchars($kasus['kategori_masalah']) ?>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <div class="text-sm font-semibold text-slate-800 line-clamp-2"><?= htmlspecialchars($kasus['deskripsi_kasus']) ?></div>
                                    <div class="text-xs font-medium text-slate-500 mt-1 flex items-center gap-1">
                                        <i class="bi bi-person-fill text-slate-400"></i> <?= htmlspecialchars($kasus['sumber_deteksi']) ?>
                                    </div>
                                    <?php if (!empty($kasus['lampiran_bukti'])): ?>
                                        <a href="/SINTA-SaaS/<?= htmlspecialchars($kasus['lampiran_bukti']) ?>" target="_blank" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 px-2 py-1 rounded-md transition-colors">
                                            <i class="bi bi-paperclip"></i> Lampiran File
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 text-center">
                                    <?php if ($kasus['status_kasus'] === 'Merah'): ?>
                                        <div class="inline-flex flex-col items-center">
                                            <span class="badge bg-red-100 text-red-700 px-3 py-1.5 rounded-full border border-red-200"><i class="bi bi-exclamation-octagon-fill me-1"></i> Perlu Pembinaan</span>
                                            <?php if (!empty($kasus['sesi_id'])): ?>
                                                <span class="text-xs text-blue-600 font-semibold mt-1"><i class="bi bi-calendar-check"></i> Sesi Terjadwal</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($kasus['status_kasus'] === 'Kuning'): ?>
                                        <span class="badge bg-yellow-100 text-yellow-700 px-3 py-1.5 rounded-full border border-yellow-200"><i class="bi bi-hourglass-split me-1"></i> Sedang Dipantau</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php if ($kasus['status_kasus'] === 'Merah'): ?>
                                            <?php if (!empty($kasus['sesi_id'])): ?>
                                                <?php
                                                $sesiUrl = '/SINTA-SaaS/pembinaan/sesi?id=' . urlencode($kasus['sesi_id']);
                                                if (isset($_GET['tenant_id'])) {
                                                    $sesiUrl .= '&tenant_id=' . urlencode($_GET['tenant_id']);
                                                }
                                                ?>
                                                <a href="<?= $sesiUrl ?>" class="btn btn-sm bg-white border border-slate-200 text-blue-600 hover:bg-slate-50 rounded-lg p-2 w-9 h-9 flex items-center justify-center transition-colors" title="Mulai Sesi">
                                                    <i class="bi bi-play-circle text-base"></i>
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-lg p-2 w-9 h-9 flex items-center justify-center transition-colors" title="Jadwalkan Sesi" onclick="openJadwalModal('<?= $kasus['id'] ?>', '<?= htmlspecialchars($kasus['nama_guru'], ENT_QUOTES) ?>')">
                                                    <i class="bi bi-calendar-plus text-base"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm bg-white border border-slate-200 text-green-600 hover:bg-slate-50 rounded-lg p-2 w-9 h-9 flex items-center justify-center transition-colors" title="Form Evaluasi" onclick="openEvaluasiModal('<?= $kasus['id'] ?>', '<?= $kasus['sesi_id'] ?>', '<?= htmlspecialchars($kasus['nama_guru'], ENT_QUOTES) ?>')">
                                                <i class="bi bi-clipboard2-check text-base"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Riwayat Kasus Selesai (Hijau) -->
    <div class="bg-white rounded-3xl p-6 shadow-sm mt-8 border border-slate-100">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h6 class="font-bold text-slate-800 m-0">Riwayat Kasus Terselesaikan</h6>
                <p class="text-xs text-slate-500 m-0">Guru yang telah menyelesaikan masa pemantauan (Status Hijau)</p>
            </div>
        </div>

        <?php if (empty($riwayatKasus)): ?>
            <div class="text-center py-10">
                <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="bi bi-inbox text-2xl"></i>
                </div>
                <h6 class="font-bold text-slate-600">Belum Ada Riwayat</h6>
                <p class="text-slate-500 text-sm">Belum ada kasus yang telah selesai diproses.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="table-riwayat" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-slate-700 text-sm font-semibold">
                            <th class="py-3 px-4 border-0 border-t border-b border-slate-200">Nama Guru</th>
                            <th class="py-3 border-0 border-t border-b border-slate-200">Kategori Kasus</th>
                            <th class="py-3 border-0 border-t border-b border-slate-200">Sumber Laporan</th>
                            <th class="py-3 border-0 border-t border-b border-slate-200 text-center">Laporan Akreditasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayatKasus as $kasus): ?>
                        <tr>
                            <td class="py-4 px-4">
                                <div class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($kasus['nama_guru']) ?></div>
                                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                    <i class="bi bi-clock text-slate-400"></i> Tgl: <?= date('d M Y', strtotime($kasus['updated_at'])) ?>
                                </div>
                            </td>
                            <td class="py-4 text-sm text-slate-800 font-semibold">
                                <?php 
                                    $dotColor = match($kasus['kategori_masalah']) {
                                        'Kedisiplinan' => 'bg-purple-500',
                                        'Akademik' => 'bg-blue-500',
                                        'Personal' => 'bg-orange-500',
                                        default => 'bg-slate-500'
                                    };
                                ?>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                                    <span class="w-2 h-2 rounded-full <?= $dotColor ?>"></span>
                                    <?= htmlspecialchars($kasus['kategori_masalah']) ?>
                                </div>
                            </td>
                            <td class="py-4">
                                <div class="text-sm font-semibold text-slate-800 line-clamp-2"><?= htmlspecialchars($kasus['deskripsi_kasus']) ?></div>
                                <div class="text-xs font-medium text-slate-500 mt-1 flex items-center gap-1">
                                    <i class="bi bi-person-fill text-slate-400"></i> <?= htmlspecialchars($kasus['sumber_deteksi']) ?>
                                </div>
                                <?php if (!empty($kasus['lampiran_bukti'])): ?>
                                    <a href="/SINTA-SaaS/<?= htmlspecialchars($kasus['lampiran_bukti']) ?>" target="_blank" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 px-2 py-1 rounded-md transition-colors">
                                        <i class="bi bi-paperclip"></i> Lihat Lampiran
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 text-center">
                                <?php
                                $cetakUrl = '/SINTA-SaaS/pembinaan/cetak?id=' . urlencode($kasus['id']);
                                if (isset($_GET['tenant_id'])) {
                                    $cetakUrl .= '&tenant_id=' . urlencode($_GET['tenant_id']);
                                }
                                ?>
                                <div class="flex justify-center">
                                    <a href="<?= $cetakUrl ?>" target="_blank" class="btn btn-sm bg-white border border-slate-200 text-red-500 hover:bg-slate-50 hover:text-red-600 rounded-lg p-2 w-9 h-9 flex items-center justify-center transition-colors" title="Cetak Akreditasi PDF">
                                        <i class="bi bi-file-earmark-pdf text-base"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Placeholder (Input Kasus Manual) -->
<div class="modal fade" id="addKasusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-3xl shadow-xl overflow-hidden">
            <div class="modal-header bg-slate-50 border-b border-slate-100 px-6 py-4">
                <h5 class="modal-title font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-journal-plus text-blue-500"></i> Input Kasus Pembinaan Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-6 py-5">
                <form action="/SINTA-SaaS/pembinaan/store" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="tenant_id" value="<?= isset($_GET['tenant_id']) ? htmlspecialchars($_GET['tenant_id']) : '' ?>">
                    <div class="mb-4">
                        <label class="form-label text-sm font-semibold text-slate-700">Pilih Guru</label>
                        <select name="guru_id" class="form-select border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">-- Pilih Guru Bermasalah --</option>
                            <?php foreach ($guruList as $guru): ?>
                                <option value="<?= htmlspecialchars($guru['id']) ?>"><?= htmlspecialchars($guru['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-sm font-semibold text-slate-700">Kategori Masalah</label>
                        <select name="kategori_masalah" class="form-select border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Kedisiplinan">Kedisiplinan (Kehadiran, Terlambat, dll)</option>
                            <option value="Akademik">Akademik (Kualitas Mengajar, RPP, dll)</option>
                            <option value="Personal">Personal (Konflik, Etika, Masalah Pribadi)</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-sm font-semibold text-slate-700">Sumber Deteksi</label>
                        <select name="sumber_deteksi" class="form-select border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="Manual Atasan">Laporan Manual Atasan</option>
                            <option value="Sistem Otomatis">Tarikan Sistem Otomatis (Absensi)</option>
                        </select>
                        <div class="form-text text-xs mt-1">Catatan: Pengajuan mandiri hanya bisa dilakukan melalui akun guru terkait.</div>
                    </div>
                    
                    <div class="mb-5">
                        <label class="form-label text-sm font-semibold text-slate-700">Deskripsi Singkat Kasus</label>
                        <textarea name="deskripsi_kasus" rows="3" class="form-control border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500" placeholder="Jelaskan secara singkat apa yang terjadi..." required></textarea>
                    </div>
                    
                    <div class="mb-5">
                        <label class="form-label text-sm font-semibold text-slate-700">Lampiran Bukti <span class="text-xs font-normal text-slate-500">(Opsional)</span></label>
                        <input type="file" name="lampiran_bukti" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-control border-slate-200 rounded-xl file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <div class="form-text text-xs mt-1">Format: JPG, PNG, PDF, DOC. Foto akan dikompres otomatis.</div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="button" class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl py-2 px-4 transition-colors" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl py-2 px-4 transition-colors flex-grow">
                            <i class="bi bi-save"></i> Simpan Kasus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Jadwalkan Sesi -->
<div class="modal fade" id="jadwalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-3xl shadow-xl overflow-hidden">
            <div class="modal-header bg-slate-50 border-b border-slate-100 px-6 py-4">
                <h5 class="modal-title font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-calendar-date text-purple-500"></i> Jadwalkan Sesi Mentoring
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-6 py-5">
                <form action="/SINTA-SaaS/pembinaan/jadwal" method="POST">
                    <input type="hidden" name="tenant_id" value="<?= isset($_GET['tenant_id']) ? htmlspecialchars($_GET['tenant_id']) : '' ?>">
                    <input type="hidden" name="monitoring_id" id="jadwal_monitoring_id">
                    
                    <div class="mb-4">
                        <p class="text-sm text-slate-600">Jadwalkan sesi bimbingan tatap muka untuk guru: <strong id="jadwal_nama_guru" class="text-slate-800"></strong></p>
                    </div>
                    
                    <div class="mb-5">
                        <label class="form-label text-sm font-semibold text-slate-700">Tanggal & Waktu Pelaksanaan</label>
                        <input type="datetime-local" name="tanggal_sesi" class="form-control border-slate-200 rounded-xl focus:border-purple-500 focus:ring-purple-500" required>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="button" class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl py-2 px-4 transition-colors" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl py-2 px-4 transition-colors flex-grow">
                            <i class="bi bi-calendar-check"></i> Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openJadwalModal(monitoringId, namaGuru) {
    document.getElementById('jadwal_monitoring_id').value = monitoringId;
    document.getElementById('jadwal_nama_guru').textContent = namaGuru;
    new bootstrap.Modal(document.getElementById('jadwalModal')).show();
}

function openEvaluasiModal(monitoringId, sesiId, namaGuru) {
    if (!sesiId) {
        alert("Sesi Mentoring belum diselesaikan. Tidak bisa melakukan evaluasi.");
        return;
    }
    document.getElementById('eval_monitoring_id').value = monitoringId;
    document.getElementById('eval_sesi_id').value = sesiId;
    document.getElementById('eval_nama_guru').textContent = namaGuru;
    new bootstrap.Modal(document.getElementById('evaluasiModal')).show();
}
</script>

<!-- DataTables CSS & JS for Pagination -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- DataTables CSS/JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<style>
    /* Styling adjustments for DataTables to match the Agenda design */
    div.dataTables_wrapper .row { margin-bottom: 1rem; align-items: center; }
    div.dataTables_wrapper div.dataTables_length { text-align: left; color: #64748b; font-size: 0.875rem; }
    div.dataTables_wrapper div.dataTables_length select { border-radius: 0.5rem; border-color: #e2e8f0; padding: 0.25rem 2rem 0.25rem 0.75rem; color: #475569; font-size: 0.875rem; box-shadow: none; outline: none; margin: 0 0.5rem; }
    div.dataTables_wrapper div.dataTables_length select:focus { border-color: #94a3b8; box-shadow: 0 0 0 1px #94a3b8; }
    
    div.dataTables_wrapper div.dataTables_filter { text-align: right; }
    div.dataTables_wrapper div.dataTables_filter input { border-radius: 0.5rem; border-color: #e2e8f0; padding: 0.375rem 0.75rem; color: #475569; font-size: 0.875rem; box-shadow: none; outline: none; margin-left: 0; min-width: 200px; }
    div.dataTables_wrapper div.dataTables_filter input:focus { border-color: #94a3b8; box-shadow: 0 0 0 1px #94a3b8; }
    
    div.dataTables_wrapper div.dataTables_info { color: #64748b; font-size: 0.875rem; padding-top: 1rem; }
    div.dataTables_wrapper div.dataTables_paginate { padding-top: 1rem; }
    .page-item.active .page-link { background-color: #f1f5f9; border-color: #e2e8f0; color: #0f172a; font-weight: 600; }
    .page-link { color: #64748b; border-color: #e2e8f0; font-size: 0.875rem; padding: 0.375rem 0.75rem; }
    .page-link:hover { background-color: #f8fafc; color: #0f172a; }
    
    /* Table specific overrides */
    table.dataTable.table-hover > tbody > tr:hover > * { background-color: #f8fafc; }
    table.dataTable { border-collapse: collapse !important; }
    table.dataTable thead th, table.dataTable thead td { border-bottom: 1px solid #e2e8f0 !important; border-top: 1px solid #e2e8f0 !important; font-weight: 700; color: #1e293b; text-transform: none; padding-top: 1rem; padding-bottom: 1rem; }
    table.dataTable tbody td { border-bottom: 1px solid #e2e8f0; padding-top: 1rem; padding-bottom: 1rem; }
</style>
<script>
document.addEventListener("turbo:load", function() {
    if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') return;
    
    // Prevent double initialization
    if ($.fn.DataTable.isDataTable('#table-kasus-aktif')) return;

    const dtConfig = {
        "language": {
            "lengthMenu": "_MENU_ data per halaman",
            "zeroRecords": "Tidak ada data yang ditemukan",
            "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 - 0 dari 0 data",
            "infoFiltered": "(difilter dari _MAX_ total data)",
            "search": "",
            "searchPlaceholder": "Cari data...",
            "paginate": {
                "first": "Awal",
                "last": "Akhir",
                "next": "›",
                "previous": "‹"
            }
        },
        "pageLength": 10,
        "lengthMenu": [[5, 10, 25, -1], [5, 10, 25, "Semua"]],
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>"
    };

    $('#table-kasus-aktif').DataTable(dtConfig);
    $('#table-riwayat').DataTable(dtConfig);
});
</script>

<!-- Modal Evaluasi -->
<div class="modal fade" id="evaluasiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-3xl shadow-xl overflow-hidden">
            <div class="modal-header bg-slate-50 border-b border-slate-100 px-6 py-4">
                <h5 class="modal-title font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-clipboard2-check text-yellow-500"></i> Evaluasi Kinerja (Masa Pemantauan)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-6 py-5">
                <form action="/SINTA-SaaS/pembinaan/evaluasi" method="POST">
                    <input type="hidden" name="tenant_id" value="<?= isset($_GET['tenant_id']) ? htmlspecialchars($_GET['tenant_id']) : '' ?>">
                    <input type="hidden" name="monitoring_id" id="eval_monitoring_id">
                    <input type="hidden" name="sesi_id" id="eval_sesi_id">
                    
                    <div class="mb-4">
                        <p class="text-sm text-slate-600">Evaluasi pasca-sesi pembinaan untuk guru: <strong id="eval_nama_guru" class="text-slate-800"></strong></p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-sm font-semibold text-slate-700">Hasil Pengamatan</label>
                        <select name="hasil_evaluasi" class="form-select border-slate-200 rounded-xl focus:border-yellow-500 focus:ring-yellow-500" required>
                            <option value="Membaik">Ada Peningkatan / Membaik</option>
                            <option value="Tetap">Tidak Ada Perubahan Berarti</option>
                            <option value="Memburuk">Kondisi Makin Memburuk</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-sm font-semibold text-slate-700">Catatan Perkembangan</label>
                        <textarea name="catatan_perkembangan" rows="3" class="form-control border-slate-200 rounded-xl focus:border-yellow-500 focus:ring-yellow-500" placeholder="Berikan catatan singkat..."></textarea>
                    </div>
                    
                    <div class="mb-5">
                        <label class="form-label text-sm font-semibold text-slate-700">Tindakan Lanjutan (Rekomendasi Sistem)</label>
                        <select name="tindakan_lanjutan" class="form-select border-slate-200 rounded-xl focus:border-yellow-500 focus:ring-yellow-500" required>
                            <option value="Selesai">Bimbingan Selesai (Kasus Ditutup - Status Hijau)</option>
                            <option value="Perpanjang">Perpanjang Masa Pemantauan (Tetap Kuning)</option>
                            <option value="Teguran">Rekomendasi SP 1 / Teguran Dinas (Kembali Merah)</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="button" class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl py-2 px-4 transition-colors" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn bg-yellow-400 hover:bg-yellow-500 text-yellow-900 font-bold rounded-xl py-2 px-4 transition-colors flex-grow">
                            <i class="bi bi-save"></i> Simpan Evaluasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
