<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Identitas Peserta Didik - <?= htmlspecialchars($siswa['nama_lengkap']) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 2.0cm 2.2cm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.25;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }
        .print-wrapper {
            page-break-after: always;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box;
            width: 100%;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
            margin-bottom: 0.6cm;
            letter-spacing: 0.5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.6cm;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        td {
            padding: 3.5px 0;
            vertical-align: top;
        }
        .col-num {
            width: 5%;
        }
        .col-label {
            width: 38%;
        }
        .col-colon {
            width: 3%;
            text-align: center;
        }
        .col-val {
            width: 54%;
        }
        .sub-row {
            padding-left: 20px;
        }
        .footer-section {
            width: 100%;
            margin-top: 0.6cm;
            display: table;
            table-layout: fixed;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-left {
            display: table-cell;
            width: 20%;
            text-align: left;
            vertical-align: top;
        }
        .photo-cell {
            display: table-cell;
            width: 25%;
            text-align: left;
            vertical-align: middle;
        }
        .photo-box {
            display: inline-block;
            width: 3cm;
            height: 4cm;
            min-width: 3cm;
            max-width: 3cm;
            min-height: 4cm;
            max-height: 4cm;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            font-size: 9pt;
            color: #555;
            background-color: #fcfcfc;
            box-sizing: border-box;
            position: relative;
        }
        .photo-box.has-photo {
            border: none;
            background-color: transparent;
        }
        .photo-box img {
            width: 3cm;
            height: 4cm;
            object-fit: cover;
            display: block;
            position: relative;
            color: transparent;
        }
        /* Fallback styling for broken image */
        .photo-box img::after {
            content: "FOTO\A 3 x 4";
            white-space: pre-wrap;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #fcfcfc;
            color: #555;
            font-family: "Times New Roman", Times, serif;
            font-size: 9pt;
            line-height: 1.4;
            text-align: center;
            box-sizing: border-box;
            z-index: 2;
            border: 1px solid #000;
        }
        .signature-right {
            display: table-cell;
            width: 55%;
            text-align: right;
            vertical-align: top;
        }
        .signature-container {
            display: inline-block;
            text-align: left;
            min-width: 200px;
            white-space: nowrap;
        }
        .signature-space {
            height: 1.8cm;
        }
        .bold {
            font-weight: bold;
        }
        .underline {
            text-decoration: underline;
        }
        @media print {
            html, body {
                background-color: #fff;
                margin: 0;
                padding: 0;
            }
            .no-print, .print-btn-container, .btn-print, header, footer, nav, sidebar, #sidebar, #navbar {
                display: none !important;
            }
        }
        .print-btn-container {
            padding: 12px;
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
        }
        .btn-print {
            padding: 8px 20px;
            background-color: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-family: sans-serif;
            font-size: 10pt;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 24pt;
            color: rgba(220, 220, 220, 0.18);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
            z-index: -1000;
            pointer-events: none;
            white-space: nowrap;
            text-align: center;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }
        .watermark-footer {
            position: fixed;
            bottom: 0.5cm;
            left: 0;
            right: 0;
            font-size: 8pt;
            color: #aaa;
            text-align: center;
            font-family: sans-serif;
            z-index: 1000;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="print-btn-container no-print">
        <button class="btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Halaman Ini</button>
    </div>
    
    <div class="watermark">RAHASIA & TERBATAS • <?= htmlspecialchars($siswa['nama_sekolah'] ?? '-') ?></div>
    <div class="watermark-footer">Dokumen resmi Buku Induk | Dicetak oleh: <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'System') ?> (<?= htmlspecialchars($_SESSION['role_name'] ?? 'Staff') ?>) pada <?= date('d-m-Y H:i:s') ?></div>
    
    <div class="print-wrapper">
        <div class="header">
            IDENTITAS PESERTA DIDIK
        </div>
        
        <?php
        // 1. Nama Lengkap Peserta Didik
        $namaLengkap = $siswa['nama_lengkap'] ?? '';

        // 2. Nomor Induk/NISN
        $nis = !empty($siswa['nis']) ? trim($siswa['nis']) : '';
        $nisn = !empty($siswa['nisn']) ? trim($siswa['nisn']) : '';
        if ($nis !== '' && $nisn !== '') {
            $nomorIndukNisn = $nis . '/ ' . $nisn;
        } elseif ($nis !== '') {
            $nomorIndukNisn = $nis;
        } elseif ($nisn !== '') {
            $nomorIndukNisn = $nisn;
        } else {
            $nomorIndukNisn = '-';
        }

        // 3. Tempat, Tanggal Lahir
        $tempatLahir = !empty($siswa['tempat_lahir']) ? trim($siswa['tempat_lahir']) : '';
        $tanggalLahirStr = '';
        if (!empty($siswa['tanggal_lahir'])) {
            $d = new DateTime($siswa['tanggal_lahir']);
            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tanggalLahirStr = $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
        }
        $tempatTanggalLahir = '-';
        if ($tempatLahir !== '' && $tanggalLahirStr !== '') {
            $tempatTanggalLahir = $tempatLahir . ', ' . $tanggalLahirStr;
        } elseif ($tempatLahir !== '') {
            $tempatTanggalLahir = $tempatLahir;
        } elseif ($tanggalLahirStr !== '') {
            $tempatTanggalLahir = $tanggalLahirStr;
        }

        // 4. Jenis Kelamin
        $jenisKelamin = ($siswa['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan';

        // 5. Agama
        $agama = $siswa['agama'] ?? '-';

        // 6. Status dalam Keluarga
        $statusDalamKeluarga = $siswa['status_anak'] ?? '-';

        // 7. Anak ke
        $anakKe = $siswa['anak_ke'] ?? '-';

        // 8. Alamat Peserta Didik
        $alamatFull = $siswa['alamat_kk'] ?? $siswa['alamat'] ?? '';
        if (!empty($siswa['rt']) || !empty($siswa['rw'])) {
            $alamatFull .= ' RT. ' . ($siswa['rt'] ?: '00') . ' RW. ' . ($siswa['rw'] ?: '00');
        }
        $alamatExtra = [];
        if (!empty($siswa['nama_kelurahan'])) {
            $alamatExtra[] = 'Kel. ' . $siswa['nama_kelurahan'];
        }
        if (!empty($siswa['nama_kecamatan'])) {
            $alamatExtra[] = 'Kec. ' . $siswa['nama_kecamatan'];
        }
        $alamatExtraStr = implode(' ', $alamatExtra);
        if (!empty($siswa['nama_kota'])) {
            if (!empty($alamatExtraStr)) {
                $alamatExtraStr .= '-' . $siswa['nama_kota'];
            } else {
                $alamatExtraStr = $siswa['nama_kota'];
            }
        }
        if (!empty($siswa['nama_provinsi'])) {
            $alamatExtraStr .= ', ' . $siswa['nama_provinsi'];
        }
        if (!empty($alamatExtraStr)) {
            $alamatFull .= ' ' . $alamatExtraStr;
        }
        if (empty($alamatFull)) {
            $alamatFull = '-';
        }

        // 9. Nomor Telepon Rumah (Siswa)
        $noTelpSiswa = $siswa['no_telepon_rumah'] ?? $siswa['no_telepon_siswa'] ?? '';
        if (empty($noTelpSiswa)) {
            $noTelpSiswa = '-';
        }

        // 10. Sekolah Asal
        $sekolahAsal = $siswa['sekolah_asal'] ?? '-';

        // 11. Diterima di sekolah ini
        // a. Di kelas
        $diterimaDiKelas = $siswa['nama_kelas'] ?? '-';
        // b. Pada tanggal
        $diterimaTanggal = '-';
        if (!empty($siswa['tanggal_masuk'])) {
            $d = new DateTime($siswa['tanggal_masuk']);
            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $diterimaTanggal = $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
        }

        // Parents Info
        $namaAyah = $siswa['nama_ayah'] ?? '';
        $namaIbu = $siswa['nama_ibu'] ?? '';

        // 13. Alamat Orang Tua
        $alamatOrangTua = $alamatFull;

        // 14. Nomor Telepon Rumah (Orang Tua)
        $noTelpOrangTua = $siswa['no_telepon_orang_tua'] ?? '';
        if (empty($noTelpOrangTua)) {
            $noTelpOrangTua = '-';
        }

        // 15. Pekerjaan Orang Tua
        $pekerjaanAyah = $siswa['pekerjaan_ayah'] ?? '';
        $pekerjaanIbu = $siswa['pekerjaan_ibu'] ?? '';

        // Guardian Info
        $hasWali = !empty($siswa['nama_wali']) && $siswa['nama_wali'] !== '-';
        $namaWali = $hasWali ? $siswa['nama_wali'] : '-';
        $alamatWali = $hasWali ? $alamatFull : '-';
        $noTelpWali = $hasWali ? ($siswa['kontak_wali'] ?? '-') : '-';
        $pekerjaanWali = $hasWali ? ($siswa['pekerjaan_wali'] ?? '-') : '-';
        ?>
        <table>
            <tr>
                <td class="col-num">1.</td>
                <td class="col-label">Nama Lengkap Peserta Didik</td>
                <td class="col-colon">:</td>
                <td class="col-val bold"><?= htmlspecialchars($namaLengkap) ?></td>
            </tr>
            <tr>
                <td class="col-num">2.</td>
                <td class="col-label">Nomor Induk/NISN</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($nomorIndukNisn) ?></td>
            </tr>
            <tr>
                <td class="col-num">3.</td>
                <td class="col-label">Tempat, Tanggal Lahir</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($tempatTanggalLahir) ?></td>
            </tr>
            <tr>
                <td class="col-num">4.</td>
                <td class="col-label">Jenis Kelamin</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($jenisKelamin) ?></td>
            </tr>
            <tr>
                <td class="col-num">5.</td>
                <td class="col-label">Agama</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($agama) ?></td>
            </tr>
            <tr>
                <td class="col-num">6.</td>
                <td class="col-label">Status dalam Keluarga</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($statusDalamKeluarga) ?></td>
            </tr>
            <tr>
                <td class="col-num">7.</td>
                <td class="col-label">Anak ke</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($anakKe) ?></td>
            </tr>
            <tr>
                <td class="col-num">8.</td>
                <td class="col-label">Alamat Peserta Didik</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($alamatFull) ?></td>
            </tr>
            <tr>
                <td class="col-num">9.</td>
                <td class="col-label">Nomor Telepon Rumah</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($noTelpSiswa) ?></td>
            </tr>
            <tr>
                <td class="col-num">10.</td>
                <td class="col-label">Sekolah Asal</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($sekolahAsal) ?></td>
            </tr>
            <tr>
                <td class="col-num">11.</td>
                <td class="col-label">Diterima di sekolah ini</td>
                <td class="col-colon">:</td>
                <td class="col-val"></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">Di kelas</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($diterimaDiKelas) ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">Pada tanggal</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($diterimaTanggal) ?></td>
            </tr>
            <tr>
                <td class="col-num">12.</td>
                <td class="col-label">Nama Orang Tua</td>
                <td class="col-colon"></td>
                <td class="col-val"></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">a. Ayah</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($namaAyah ?: '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">b. Ibu</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($namaIbu ?: '-') ?></td>
            </tr>
            <tr>
                <td class="col-num">13.</td>
                <td class="col-label">Alamat Orang Tua</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($alamatOrangTua) ?></td>
            </tr>
            <tr>
                <td class="col-num">14.</td>
                <td class="col-label">Nomor Telepon Rumah</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($noTelpOrangTua) ?></td>
            </tr>
            <tr>
                <td class="col-num">15.</td>
                <td class="col-label">Pekerjaan Orang Tua</td>
                <td class="col-colon">:</td>
                <td class="col-val"></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">a. Ayah</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($pekerjaanAyah ?: '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">b. Ibu</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($pekerjaanIbu ?: '-') ?></td>
            </tr>
            <tr>
                <td class="col-num">16.</td>
                <td class="col-label">Nama Wali Siswa</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($namaWali) ?></td>
            </tr>
            <tr>
                <td class="col-num">17.</td>
                <td class="col-label">Alamat Wali Peserta Didik</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($alamatWali) ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">Nomor Telepon Rumah</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($noTelpWali) ?></td>
            </tr>
            <tr>
                <td class="col-num">18.</td>
                <td class="col-label">Pekerjaan Wali Peserta Didik</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($pekerjaanWali) ?></td>
            </tr>
        </table>
        
        <div class="footer-section">
            <div class="signature-left">
                <!-- Left column remains empty for spacing alignment -->
            </div>
            <div class="photo-cell">
                <div class="photo-box"></div>
            </div>
            <div class="signature-right">
                <div class="signature-container">
                    <div style="margin-bottom: 2px;"><?= htmlspecialchars($tempat) ?>, <?= htmlspecialchars($tanggal) ?></div>
                    <div style="margin-bottom: 0;">Kepala Sekolah</div>
                    <div class="signature-space"></div>
                    <div class="bold underline"><?= htmlspecialchars($siswa['nama_kepsek']) ?></div>
                    <?php if (!empty($siswa['pangkat_kepsek'])): ?>
                        <div><?= htmlspecialchars($siswa['pangkat_kepsek']) ?></div>
                    <?php endif; ?>
                    <div>NIP. <?= htmlspecialchars($siswa['nip_kepsek'] ?? '-') ?></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
