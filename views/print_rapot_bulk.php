<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Identitas Peserta Didik - Kelas</title>
    <style>
        @page {
            size: A4;
            margin: 1.0cm 1.5cm 0.8cm 1.5cm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 10pt;
            line-height: 1.25;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }
        .page {
            page-break-after: always;
            page-break-inside: avoid;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            text-transform: uppercase;
            margin-bottom: 0.5cm;
            letter-spacing: 0.5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.4cm;
        }
        td {
            padding: 1.2px 0;
            vertical-align: top;
        }
        .col-num {
            width: 4%;
        }
        .col-label {
            width: 38%;
        }
        .col-colon {
            width: 3%;
            text-align: center;
        }
        .col-val {
            width: 55%;
        }
        .sub-row {
            padding-left: 15px;
        }
        .footer-section {
            width: 100%;
            margin-top: 0.3cm;
            display: table;
            page-break-inside: avoid;
        }
        .photo-box {
            display: table-cell;
            width: 3cm;
            height: 4cm;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            font-size: 8.5pt;
            color: #555;
            background-color: #fcfcfc;
            box-sizing: border-box;
        }
        .photo-box img {
            width: 3cm;
            height: 4cm;
            object-fit: cover;
            display: block;
        }
        .signature-box {
            display: table-cell;
            text-align: right;
            vertical-align: top;
        }
        .signature-container {
            display: inline-block;
            text-align: left;
            min-width: 220px;
        }
        .signature-space {
            height: 1.3cm;
        }
        .bold {
            font-weight: bold;
        }
        .underline {
            text-decoration: underline;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn-container {
            padding: 10px;
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
        }
        .btn-print {
            padding: 6px 16px;
            background-color: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-family: sans-serif;
            font-size: 9pt;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="print-btn-container no-print">
        <button class="btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Semua Halaman</button>
    </div>
    
    <?php foreach ($studentsData as $siswa): ?>
    <div class="page">
        <div class="header">
            IDENTITAS PESERTA DIDIK
        </div>
        
        <table>
            <tr>
                <td class="col-num">1.</td>
                <td class="col-label">Nama Lengkap</td>
                <td class="col-colon">:</td>
                <td class="col-val bold"><?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
            </tr>
            <tr>
                <td class="col-num">2.</td>
                <td class="col-label">NIS</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['nis'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="col-num">3.</td>
                <td class="col-label">NISN</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['nisn'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="col-num">4.</td>
                <td class="col-label">Tempat, Tanggal lahir</td>
                <td class="col-colon">:</td>
                <td class="col-val">
                    <?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?>, 
                    <?php
                    if (!empty($siswa['tanggal_lahir'])) {
                        $d = new DateTime($siswa['tanggal_lahir']);
                        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        echo $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td class="col-num">5.</td>
                <td class="col-label">Jenis Kelamin</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
            </tr>
            <tr>
                <td class="col-num">6.</td>
                <td class="col-label">Agama</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['agama'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num">7.</td>
                <td class="col-label">Status dalam Keluarga</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['status_anak'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num">8.</td>
                <td class="col-label">Anak ke</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['anak_ke'] ?? '1') ?></td>
            </tr>
            <tr>
                <td class="col-num">9.</td>
                <td class="col-label">Alamat</td>
                <td class="col-colon">:</td>
                <td class="col-val">
                    <?php
                    $alamatFull = $siswa['alamat_kk'] ?? $siswa['alamat'] ?? '';
                    if (!empty($siswa['rt']) || !empty($siswa['rw'])) {
                        $alamatFull .= ' RT. ' . ($siswa['rt'] ?: '00') . ' RW. ' . ($siswa['rw'] ?: '00');
                    }
                    if (!empty($siswa['nama_kelurahan'])) {
                        $alamatFull .= ', Kel. ' . $siswa['nama_kelurahan'];
                    }
                    if (!empty($siswa['nama_kecamatan'])) {
                        $alamatFull .= ', Kec. ' . $siswa['nama_kecamatan'];
                    }
                    if (!empty($siswa['nama_kota'])) {
                        $alamatFull .= ', ' . $siswa['nama_kota'];
                    }
                    if (!empty($siswa['nama_provinsi'])) {
                        $alamatFull .= ', Prov. ' . $siswa['nama_provinsi'];
                    }
                    echo htmlspecialchars($alamatFull ?: '-');
                    ?>
                </td>
            </tr>
            <tr>
                <td class="col-num">10.</td>
                <td class="col-label">Nomor Telepon Rumah</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['no_telepon_rumah'] ?? $siswa['no_telepon_siswa'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num">11.</td>
                <td class="col-label">Madrasah Asal (SMP/MTs)</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['sekolah_asal'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num">12.</td>
                <td class="col-label">Diterima di madrasah ini</td>
                <td class="col-colon"></td>
                <td class="col-val"></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">a. Di kelas</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">b. Pada Tanggal</td>
                <td class="col-colon">:</td>
                <td class="col-val">
                    <?php
                    if (!empty($siswa['tanggal_masuk'])) {
                        $d = new DateTime($siswa['tanggal_masuk']);
                        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        echo $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td class="col-num">13.</td>
                <td class="col-label">Orang Tua</td>
                <td class="col-colon"></td>
                <td class="col-val"></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">a. Nama Ayah</td>
                <td class="col-colon">:</td>
                <td class="col-val bold"><?= htmlspecialchars($siswa['nama_ayah'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">b. Pekerjaan</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['pekerjaan_ayah'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">c. Nomor Telepon/HP</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['no_telepon_orang_tua'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">d. Alamat</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['alamat_kk'] ?? $siswa['alamat'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">e. Nama Ibu</td>
                <td class="col-colon">:</td>
                <td class="col-val bold"><?= htmlspecialchars($siswa['nama_ibu'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">f. Pekerjaan</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['pekerjaan_ibu'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">g. Nomor Telepon/HP</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['no_telepon_orang_tua'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">h. Alamat</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['alamat_kk'] ?? $siswa['alamat'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num">14.</td>
                <td class="col-label">Wali</td>
                <td class="col-colon"></td>
                <td class="col-val"></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">a. Nama Wali</td>
                <td class="col-colon">:</td>
                <td class="col-val bold"><?= htmlspecialchars($siswa['nama_wali'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">b. Pekerjaan</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['pekerjaan_wali'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">c. Nomor Telepon/HP</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['kontak_wali'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="col-num"></td>
                <td class="col-label sub-row">d. Alamat</td>
                <td class="col-colon">:</td>
                <td class="col-val"><?= htmlspecialchars($siswa['alamat_kk'] ?? $siswa['alamat'] ?? '-') ?></td>
            </tr>
        </table>
        
        <div class="footer-section">
            <div class="photo-box">
                <?php if (!empty($siswa['foto_profil'])): ?>
                    <img src="/SINTA-SaaS/storage/app/public/<?= htmlspecialchars($siswa['foto_profil']) ?>" alt="Foto Siswa">
                <?php else: ?>
                    FOTO<br>3 x 4
                <?php endif; ?>
            </div>
            <div class="signature-box">
                <div class="signature-container">
                    <div style="margin-bottom: 2px;"><?= htmlspecialchars($tempat) ?>, <?= htmlspecialchars($tanggal) ?></div>
                    <div style="margin-bottom: 20px;">Kepala Sekolah</div>
                    <div class="signature-space"></div>
                    <div class="bold underline"><?= htmlspecialchars($siswa['nama_kepsek']) ?></div>
                    <div>NIP. <?= htmlspecialchars($siswa['nip_kepsek'] ?? '-') ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>
