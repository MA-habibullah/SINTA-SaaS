<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rapor Massal - Kurikulum Merdeka</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 2.0cm 2.2cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }
        .page {
            page-break-after: always;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .header-title { text-align: center; margin-bottom: 20px; font-weight: bold; }
        .section-title { font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .identitas-table td { border: none; padding: 3px; }
        .identitas-table td:nth-child(2) { width: 1%; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        .data-table th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        .signature-box { width: 100%; margin-top: 30px; display: table; }
        .signature-col { display: table-cell; width: 33.33%; text-align: center; }
        @media print {
            body { padding: 0; }
            .no-print, .print-btn-container {
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
    </style>
</head>
<body>

    <div class="print-btn-container no-print">
        <button class="btn-print" onclick="window.print()">Cetak Semua Rapor Rombel</button>
    </div>

    <?php foreach ($studentsData as $siswa): ?>
    <div class="page">
        <div class="header-title">
            IV. LAPORAN HASIL CAPAIAN PEMBELAJARAN PESERTA DIDIK KURIKULUM MERDEKA<br>
            SEKOLAH MENENGAH ATAS (SMA)
        </div>

        <div class="section-title">A. IDENTITAS PESERTA DIDIK</div>
        <table class="identitas-table" style="width: 100%;">
            <tr>
                <td width="15%">Nama Peserta Didik</td><td>:</td><td width="35%"><b><?= htmlspecialchars($siswa['nama_lengkap'] ?? '-') ?></b></td>
                <td width="15%">Kelas</td><td>:</td><td width="35%"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
            </tr>
            <tr>
                <td>NISN / NIS</td><td>:</td><td><?= htmlspecialchars($siswa['nisn'] ?? '-') ?> / <?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
                <td>Fase</td><td>:</td><td>F</td>
            </tr>
            <tr>
                <td>Nama Sekolah</td><td>:</td><td><?= htmlspecialchars($siswa['tenant_info']['nama_sekolah'] ?? '-') ?></td>
                <td>Semester</td><td>:</td><td><?= htmlspecialchars($semester ?? '-') ?></td>
            </tr>
            <tr>
                <td>Alamat</td><td>:</td><td><?= htmlspecialchars($siswa['tenant_info']['alamat'] ?? '-') ?></td>
                <td>Tahun Pelajaran</td><td>:</td><td><?= htmlspecialchars($ta ?? '-') ?></td>
            </tr>
        </table>

        <div class="section-title">B. INTRAKURIKULER</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">NO.</th>
                    <th width="35%">MATA PELAJARAN</th>
                    <th width="10%">NILAI AKHIR</th>
                    <th width="40%">CAPAIAN PEMBELAJARAN</th>
                    <th width="10%">KKTP</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $jumlahNilai = 0;
                $count = 0;
                if (!empty($siswa['grades'])): 
                    foreach ($siswa['grades'] as $index => $g):
                        $nilaiAkhir = floatval($g['nilai_akhir'] ?? 0);
                        $jumlahNilai += $nilaiAkhir;
                        $count++;

                        $detail = !empty($g['nilai_detail_json']) ? json_decode($g['nilai_detail_json'], true) : [];
                        $highest = isset($detail['deskripsi_tertinggi']) && $detail['deskripsi_tertinggi'] !== '' ? $detail['deskripsi_tertinggi'] : '';
                        $lowest = isset($detail['deskripsi_terendah']) && $detail['deskripsi_terendah'] !== '' ? $detail['deskripsi_terendah'] : '';
                        
                        $capaian = '';
                        if ($highest && $lowest) {
                            $capaian = "Capaian Tertinggi: " . $highest . "\nCapaian Terendah: " . $lowest;
                        } elseif ($highest) {
                            $capaian = "Capaian Tertinggi: " . $highest;
                        } elseif ($lowest) {
                            $capaian = "Capaian Terendah: " . $lowest;
                        } else {
                            $capaian = '-';
                        }
                        ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($g['nama_mapel'] ?? '-') ?></td>
                            <td class="text-center"><b><?= round($nilaiAkhir) ?></b></td>
                            <td style="white-space: pre-line;"><?= htmlspecialchars($capaian) ?></td>
                            <td class="text-center"><?= htmlspecialchars($g['kkm'] ?? '75') ?></td>
                        </tr>
                    <?php 
                    endforeach;
                else: 
                ?>
                    <tr>
                        <td colspan="5" class="text-center">Nilai rapor belum diisi untuk semester ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signature-box">
            <div class="signature-col">
                Mengetahui,<br>
                Orang Tua/Wali Siswa<br><br><br><br>
                .............................................
            </div>
            <div class="signature-col"></div>
            <div class="signature-col">
                <?= htmlspecialchars($siswa['tenant_info']['kabupaten_kota'] ?? 'Kota') ?>, <?= date('j F Y') ?><br>
                Kepala Sekolah<br><br><br><br>
                <b><?= htmlspecialchars($siswa['tenant_info']['nama_kepsek'] ?? '-') ?></b><br>
                NIP. <?= htmlspecialchars($siswa['tenant_info']['nip_kepsek'] ?? '-') ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</body>
</html>
