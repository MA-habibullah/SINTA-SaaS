<?php
/**
 * View: Pengaturan Perpustakaan & Toggle Switch Notifikasi WA/Email
 */
$pengaturan = $data['pengaturan'] ?? [];
$waAktif = (int)($pengaturan['auto_notif_wa_aktif'] ?? 1);
$emailAktif = (int)($pengaturan['auto_notif_email_aktif'] ?? 0);
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">⚙️ Pengaturan Perpustakaan & Automation Toggle</h2>
        <p class="text-muted fs-7 mb-0">Konfigurasi Tarif Denda, Aturan Peminjaman, & Sakelar ON/OFF WhatsApp Reminder.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-sliders text-primary me-2"></i> Parameter Aturan & Notifikasi</h5>

    <form action="/api/v1/perpustakaan/pengaturan/simpan" method="POST">
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Nama Perpustakaan</label>
                <input type="text" name="nama_perpustakaan" class="form-control rounded-3" value="<?= htmlspecialchars($pengaturan['nama_perpustakaan'] ?? 'Perpustakaan Utama', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">NPP / Nomor Pokok Perpustakaan</label>
                <input type="text" name="nomor_pokok" class="form-control rounded-3" value="<?= htmlspecialchars($pengaturan['nomor_pokok'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Tarif Denda Keterlambatan (Rp / Hari)</label>
                <input type="number" name="tarif_denda_per_hari" class="form-control rounded-3" value="<?= (float)($pengaturan['tarif_denda_per_hari'] ?? 500) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Batas Hari Pinjam Siswa</label>
                <input type="number" name="max_hari_pinjam_siswa" class="form-control rounded-3" value="<?= (int)($pengaturan['max_hari_pinjam_siswa'] ?? 7) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Batas Hari Pinjam Guru</label>
                <input type="number" name="max_hari_pinjam_guru" class="form-control rounded-3" value="<?= (int)($pengaturan['max_hari_pinjam_guru'] ?? 14) ?>">
            </div>
        </div>

        <hr class="my-4">

        <!-- TOGGLE SWITCH NOTIFIKASI WA & EMAIL (SESUAI REQUEST USER) -->
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-whatsapp text-success me-2"></i> Sakelar Notifikasi Otomatis (Automation Toggles)</h5>
        
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="card border-0 bg-light p-3 rounded-3">
                    <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                        <div>
                            <label class="form-check-label fw-bold text-dark fs-6 d-block" for="toggleWA">
                                📱 Auto Reminder WhatsApp (H-2 Jatuh Tempo)
                            </label>
                            <small class="text-muted">Kirim pesan WhatsApp pengingat pengembalian ke siswa secara otomatis.</small>
                        </div>
                        <input class="form-check-input ms-3 fs-4" type="checkbox" role="switch" id="toggleWA" name="auto_notif_wa_aktif" value="1" <?= $waAktif ? 'checked' : '' ?>>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card border-0 bg-light p-3 rounded-3">
                    <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                        <div>
                            <label class="form-check-label fw-bold text-dark fs-6 d-block" for="toggleEmail">
                                ✉️ Auto Reminder Email
                            </label>
                            <small class="text-muted">Kirim surat elektronik pengingat jatuh tempo ke email siswa.</small>
                        </div>
                        <input class="form-check-input ms-3 fs-4" type="checkbox" role="switch" id="toggleEmail" name="auto_notif_email_aktif" value="1" <?= $emailAktif ? 'checked' : '' ?>>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fw-semibold">
            <i class="bi bi-save me-1"></i> Simpan Pengaturan Perpustakaan
        </button>
    </form>
</div>
