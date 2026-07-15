<?php
if (!isset($siswa)) {
    die('Data siswa tidak ditemukan.');
}

// Persiapkan data
$namaLengkap = $siswa['nama_lengkap'] ?? '';
$nis = $siswa['nis'] ?? '';
$nisn = $siswa['nisn'] ?? '';
$namaSekolah = $siswa['tenant_info']['nama_sekolah'] ?? '-';
$alamatSekolah = $siswa['tenant_info']['alamat'] ?? '-';

// Group grades by Subject
$mapelData = [];

$years = array_unique(array_column($siswa['transkrip_grades'], 'tahun_ajaran'));
sort($years);
$yearMap = [];
foreach ($years as $idx => $y) {
    $yearMap[$y] = ($idx * 2) + 1; // 1, 3, 5
}

foreach ($siswa['transkrip_grades'] as $g) {
    $mapelName = $g['nama_mapel'];
    if (!isset($mapelData[$mapelName])) {
        $mapelData[$mapelName] = [
            'kelompok' => $g['kelompok'] ?? 'Umum',
            's1' => '-', 's2' => '-', 's3' => '-', 's4' => '-', 's5' => '-', 's6' => '-',
            'us' => '-', 'ns' => '-', 'rata_rapor' => '-'
        ];
    }
    
    $ta = $g['tahun_ajaran'];
    $sem = strtolower($g['semester']);
    
    if ($sem === 'ujian sekolah') {
        $mapelData[$mapelName]['us'] = round((float)$g['nilai_akhir']);
    } else {
        $baseSem = $yearMap[$ta] ?? 1;
        if (strpos($sem, 'genap') !== false) {
            $smt = $baseSem + 1;
        } else {
            $smt = $baseSem;
        }
        if ($smt >= 1 && $smt <= 6) {
            $mapelData[$mapelName]["s$smt"] = round((float)$g['nilai_akhir']);
        }
    }
}

// Calculate averages and school final score (NS)
foreach ($mapelData as $k => $v) {
    $sum = 0;
    $count = 0;
    for ($i=1; $i<=6; $i++) {
        if ($v["s$i"] !== '-') {
            $sum += $v["s$i"];
            $count++;
        }
    }
    if ($count > 0) {
        $rataRapor = $sum / $count;
        $mapelData[$k]['rata_rapor'] = round($rataRapor);
        
        if ($v['us'] !== '-') {
            // Formula: 60% Rata-rata Rapor + 40% Ujian Sekolah
            $ns = (0.6 * $rataRapor) + (0.4 * $v['us']);
            $mapelData[$k]['ns'] = round($ns);
        } else {
            $mapelData[$k]['ns'] = round($rataRapor);
        }
    } else {
        if ($v['us'] !== '-') {
            $mapelData[$k]['ns'] = $v['us'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transkrip Nilai - <?= htmlspecialchars($namaLengkap) ?></title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; line-height: 1.4; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 16px; font-weight: bold; }
        .header h3 { margin: 5px 0; font-size: 18px; font-weight: bold; }
        .header p { margin: 0; font-size: 12px; }
        .student-info { width: 100%; margin-bottom: 15px; }
        .student-info td { padding: 2px 5px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; }
        .data-table td.left { text-align: left; }
        .signature-box { width: 100%; margin-top: 30px; display: table; }
        .signature-col { display: table-cell; width: 33%; text-align: center; vertical-align: top; }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</h2>
        <h3><?= htmlspecialchars($namaSekolah) ?></h3>
        <p><?= htmlspecialchars($alamatSekolah) ?></p>
        <h3 style="margin-top:15px; text-decoration:underline;">TRANSKRIP NILAI KELULUSAN</h3>
    </div>

    <table class="student-info">
        <tr>
            <td width="20%">Nama Lengkap</td>
            <td width="2%">:</td>
            <td width="78%"><b><?= htmlspecialchars($namaLengkap) ?></b></td>
        </tr>
        <tr>
            <td>NIS / NISN</td>
            <td>:</td>
            <td><?= htmlspecialchars($nis) ?> / <?= htmlspecialchars($nisn) ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" width="5%">No</th>
                <th rowspan="2" width="30%">Mata Pelajaran</th>
                <th colspan="6">Nilai Rapor Semester</th>
                <th rowspan="2" width="10%">Rata-Rata Rapor</th>
                <th rowspan="2" width="10%">Ujian Sekolah</th>
                <th rowspan="2" width="10%">Nilai Sekolah (Ijazah)</th>
            </tr>
            <tr>
                <th width="6%">1</th>
                <th width="6%">2</th>
                <th width="6%">3</th>
                <th width="6%">4</th>
                <th width="6%">5</th>
                <th width="6%">6</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($mapelData as $name => $data) {
                echo "<tr>";
                echo "<td>{$no}</td>";
                echo "<td class='left'>" . htmlspecialchars($name) . "</td>";
                echo "<td>{$data['s1']}</td>";
                echo "<td>{$data['s2']}</td>";
                echo "<td>{$data['s3']}</td>";
                echo "<td>{$data['s4']}</td>";
                echo "<td>{$data['s5']}</td>";
                echo "<td>{$data['s6']}</td>";
                echo "<td>{$data['rata_rapor']}</td>";
                echo "<td>{$data['us']}</td>";
                echo "<td><b>{$data['ns']}</b></td>";
                echo "</tr>";
                $no++;
            }
            if (empty($mapelData)) {
                echo "<tr><td colspan='11'>Belum ada data nilai tercatat.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="signature-box">
        <div class="signature-col" style="text-align: left; padding-left: 20px;">
            <?php if (isset($showQrCode) && $showQrCode): ?>
                <div style="text-align: center; border: 1px solid #ccc; padding: 6px; border-radius: 4px; display: inline-block; background-color: #fff;">
                    <span style="font-size: 7px; font-weight: bold; display: block; margin-bottom: 3px; text-transform: uppercase; font-family: sans-serif;">Verifikasi Dokumen</span>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=<?= urlencode($urlVerifikasi) ?>" alt="QR Code Verifikasi" style="width: 85px; height: 85px; display: block; margin: 0 auto;">
                    <span style="font-size: 7px; display: block; margin-top: 3px; color: #555; font-family: sans-serif;">Scan untuk validasi data</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="signature-col"></div>
        <div class="signature-col" style="text-align: left;">
            <?php
                $tanggalCetak = $_GET['tanggal_cetak'] ?? date('Y-m-d');
                $timestamp = strtotime($tanggalCetak);
                $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                $tglIndo = date('j', $timestamp) . ' ' . $bulan[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
            ?>
            <?= htmlspecialchars($siswa['tenant_info']['kabupaten_kota'] ?? 'Kota') ?>, <?= $tglIndo ?><br>
            Kepala Sekolah<br><br><br><br><br>
            <b><?= htmlspecialchars($siswa['tenant_info']['nama_kepsek'] ?? '................................................') ?></b><br>
            NIP. <?= htmlspecialchars($siswa['tenant_info']['nip_kepsek'] ?? '........................................') ?>
        </div>
    </div>
</body>
</html>
