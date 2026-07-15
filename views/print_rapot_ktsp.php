<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rapor Kurikulum KTSP</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.3; margin: 0; padding: 20px; }
        .header-title { text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 13pt; }
        .section-title { font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .identitas-table td { border: none; padding: 3px; }
        .identitas-table td:nth-child(2) { width: 1%; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 6px 5px; vertical-align: middle; }
        .data-table th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .signature-box { width: 100%; margin-top: 40px; display: table; }
        .signature-col { display: table-cell; width: 33.33%; text-align: center; }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="header-title">
        LAPORAN HASIL BELAJAR PESERTA DIDIK (RAPOR) KTSP<br>
        SEKOLAH MENENGAH ATAS (SMA)
    </div>

    <div class="section-title">A. IDENTITAS PESERTA DIDIK</div>
    <table class="identitas-table" style="width: 100%;">
        <tr>
            <td width="15%">Nama Siswa</td><td>:</td><td width="35%"><?= htmlspecialchars($siswa['nama_lengkap'] ?? '-') ?></td>
            <td width="15%">Kelas</td><td>:</td><td width="35%"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
        </tr>
        <tr>
            <td>NISN / NIS</td><td>:</td><td><?= htmlspecialchars($siswa['nisn'] ?? '-') ?> / <?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
            <td>Semester</td><td>:</td><td><?= htmlspecialchars($_GET['semester'] ?? '-') ?></td>
        </tr>
        <tr>
            <td>Nama Sekolah</td><td>:</td><td><?= htmlspecialchars($siswa['tenant_info']['nama_sekolah'] ?? '-') ?></td>
            <td>Tahun Pelajaran</td><td>:</td><td><?= htmlspecialchars($_GET['ta'] ?? '-') ?></td>
        </tr>
    </table>

    <div class="section-title">B. NILAI AKADEMIK</div>
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" width="5%">NO.</th>
                <th rowspan="2" width="35%">MATA PELAJARAN</th>
                <th rowspan="2" width="10%">KKM</th>
                <th colspan="3">NILAI HASIL BELAJAR</th>
            </tr>
            <tr>
                <th width="15%">Kognitif (Pengetahuan)</th>
                <th width="15%">Psikomotorik (Praktik)</th>
                <th width="20%">Afektif (Sikap)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($grades)): 
                foreach ($grades as $index => $g):
                    $detail = !empty($g['nilai_detail_json']) ? json_decode($g['nilai_detail_json'], true) : [];
                    $kognitif = isset($detail['kognitif']) && $detail['kognitif'] !== '' ? $detail['kognitif'] : '-';
                    $psikomotorik = isset($detail['psikomotorik']) && $detail['psikomotorik'] !== '' ? $detail['psikomotorik'] : '-';
                    $afektif = isset($detail['afektif']) && $detail['afektif'] !== '' ? $detail['afektif'] : '-';
                    $kkm = !empty($g['kkm']) ? floatval($g['kkm']) : '-';
            ?>
            <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($g['nama_mapel']) ?></td>
                <td class="text-center"><?= $kkm ?></td>
                <td class="text-center"><?= $kognitif ?></td>
                <td class="text-center"><?= $psikomotorik ?></td>
                <td class="text-center"><?= $afektif ?></td>
            </tr>
            <?php 
                endforeach;
            else: 
            ?>
            <tr>
                <td colspan="6" class="text-center"><i>Belum ada data nilai akademik.</i></td>
            </tr>
            <?php 
            endif; 
            ?>
        </tbody>
    </table>

    <div class="signature-box">
        <div class="signature-col">
            Mengetahui<br>
            Orang Tua/Wali<br><br><br><br><br>
            ................................................
        </div>
        <div class="signature-col">
            <br>
            Wali Kelas<br><br><br><br><br>
            ................................................<br>
            NIP. ........................................
        </div>
        <div class="signature-col">
            <?php
                $tanggalCetak = $_GET['tanggal_cetak'] ?? date('Y-m-d');
                $timestamp = strtotime($tanggalCetak);
                $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                $tglIndo = date('j', $timestamp) . ' ' . $bulan[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
            ?>
            <?= htmlspecialchars($siswa['tenant_info']['kabupaten_kota'] ?? 'Kota') ?>, <?= $tglIndo ?><br>
            Kepala Sekolah<br><br><br><br><br>
            <?= htmlspecialchars($siswa['tenant_info']['nama_kepsek'] ?? '................................................') ?><br>
            NIP. <?= htmlspecialchars($siswa['tenant_info']['nip_kepsek'] ?? '........................................') ?>
        </div>
    </div>
</body>
</html>
