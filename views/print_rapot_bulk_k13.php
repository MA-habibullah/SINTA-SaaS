<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rapor Massal - Kurikulum 2013</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 2.0cm 2.2cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
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
            break-inside: avoid;
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .header-title { text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 12pt; }
        .section-title { font-weight: bold; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .identitas-table td { border: none; padding: 2px; }
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
            LAPORAN HASIL BELAJAR PESERTA DIDIK (RAPOR) KURIKULUM 2013<br>
            SEKOLAH MENENGAH ATAS (SMA)
        </div>

        <div class="section-title">A. IDENTITAS PESERTA DIDIK</div>
        <table class="identitas-table" style="width: 100%;">
            <tr>
                <td width="15%">Nama Siswa</td><td>:</td><td width="35%"><b><?= htmlspecialchars($siswa['nama_lengkap'] ?? '-') ?></b></td>
                <td width="15%">Kelas</td><td>:</td><td width="35%"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
            </tr>
            <tr>
                <td>NISN / NIS</td><td>:</td><td><?= htmlspecialchars($siswa['nisn'] ?? '-') ?> / <?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
                <td>Semester</td><td>:</td><td><?= htmlspecialchars($semester ?? '-') ?></td>
            </tr>
            <tr>
                <td>Nama Sekolah</td><td>:</td><td><?= htmlspecialchars($siswa['tenant_info']['nama_sekolah'] ?? '-') ?></td>
                <td>Tahun Pelajaran</td><td>:</td><td><?= htmlspecialchars($ta ?? '-') ?></td>
            </tr>
        </table>

        <?php 
        $sId = $siswa['id'] ?? '';
        $sikapInfo = isset($sikapK13[$sId]) ? $sikapK13[$sId] : [
            'predikat_spiritual' => '-', 'deskripsi_spiritual' => '-',
            'predikat_sosial' => '-', 'deskripsi_sosial' => '-'
        ];
        ?>
        <div class="section-title">B. SIKAP</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="30%">ASPEK</th>
                    <th width="15%">PREDIKAT</th>
                    <th width="55%">DESKRIPSI</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: bold;">1. Sikap Spiritual (KI-1)</td>
                    <td class="text-center" style="vertical-align: middle;"><?= htmlspecialchars($sikapInfo['predikat_spiritual'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($sikapInfo['deskripsi_spiritual'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">2. Sikap Sosial (KI-2)</td>
                    <td class="text-center" style="vertical-align: middle;"><?= htmlspecialchars($sikapInfo['predikat_sosial'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($sikapInfo['deskripsi_sosial'] ?? '-') ?></td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">C. PENGETAHUAN DAN KETERAMPILAN</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" width="5%">NO.</th>
                    <th rowspan="2" width="30%">MATA PELAJARAN</th>
                    <th rowspan="2" width="8%">KKM</th>
                    <th colspan="3">PENGETAHUAN (KI-3)</th>
                    <th colspan="3">KETERAMPILAN (KI-4)</th>
                </tr>
                <tr>
                    <th width="7%">Nilai</th>
                    <th width="5%">Pred</th>
                    <th width="20%">Deskripsi</th>
                    <th width="7%">Nilai</th>
                    <th width="5%">Pred</th>
                    <th width="20%">Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($siswa['grades'])): 
                    foreach ($siswa['grades'] as $index => $g):
                        $detail = !empty($g['nilai_detail_json']) ? json_decode($g['nilai_detail_json'], true) : [];
                        
                        $pNilai = isset($detail['pengetahuan_nilai']) && $detail['pengetahuan_nilai'] !== '' ? $detail['pengetahuan_nilai'] : '-';
                        $pPred = isset($detail['pengetahuan_predikat']) && $detail['pengetahuan_predikat'] !== '' ? $detail['pengetahuan_predikat'] : '-';
                        $pDesk = isset($detail['pengetahuan_deskripsi']) && $detail['pengetahuan_deskripsi'] !== '' ? $detail['pengetahuan_deskripsi'] : '-';
                        
                        $kNilai = isset($detail['keterampilan_nilai']) && $detail['keterampilan_nilai'] !== '' ? $detail['keterampilan_nilai'] : '-';
                        $kPred = isset($detail['keterampilan_predikat']) && $detail['keterampilan_predikat'] !== '' ? $detail['keterampilan_predikat'] : '-';
                        $kDesk = isset($detail['keterampilan_deskripsi']) && $detail['keterampilan_deskripsi'] !== '' ? $detail['keterampilan_deskripsi'] : '-';
                        
                        $kkm = !empty($g['kkm']) ? floatval($g['kkm']) : '-';
                ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($g['nama_mapel']) ?></td>
                    <td class="text-center" style="vertical-align: middle;"><?= $kkm ?></td>
                    <td class="text-center" style="vertical-align: middle;"><?= $pNilai ?></td>
                    <td class="text-center" style="vertical-align: middle;"><?= $pPred ?></td>
                    <td style="font-size: 9pt;"><?= htmlspecialchars($pDesk) ?></td>
                    <td class="text-center" style="vertical-align: middle;"><?= $kNilai ?></td>
                    <td class="text-center" style="vertical-align: middle;"><?= $kPred ?></td>
                    <td style="font-size: 9pt;"><?= htmlspecialchars($kDesk) ?></td>
                </tr>
                <?php 
                    endforeach;
                else: 
                ?>
                <tr>
                    <td colspan="9" class="text-center"><i>Belum ada data nilai akademik.</i></td>
                </tr>
                <?php 
                endif; 
                ?>
            </tbody>
        </table>

        <div class="signature-box">
            <div class="signature-col">
                Mengetahui<br>
                Orang Tua/Wali<br><br><br><br>
                ................................................
            </div>
            <div class="signature-col">
                <br>
                Wali Kelas<br><br><br><br>
                ................................................<br>
                NIP. ........................................
            </div>
            <div class="signature-col">
                <?php
                    $tanggalCetak = $tanggal ?: date('Y-m-d');
                    $timestamp = strtotime($tanggalCetak);
                    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $tglIndo = date('j', $timestamp) . ' ' . $bulan[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
                ?>
                <?= htmlspecialchars($siswa['tenant_info']['kabupaten_kota'] ?? 'Kota') ?>, <?= $tglIndo ?><br>
                Kepala Sekolah<br><br><br><br>
                <b><?= htmlspecialchars($siswa['tenant_info']['nama_kepsek'] ?? '-') ?></b><br>
                NIP. <?= htmlspecialchars($siswa['tenant_info']['nip_kepsek'] ?? '-') ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</body>
</html>
