<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cetak Rapor Kurikulum Merdeka</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.3; margin: 0; padding: 20px; }
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
        }
    </style>
</head>
<body>
    <div class='header-title'>
        IV. LAPORAN HASIL CAPAIAN PEMBELAJARAN PESERTA DIDIK KURIKULUM MERDEKA<br>
        SEKOLAH MENENGAH ATAS (SMA)
    </div>

    <div class='section-title'>A. IDENTITAS PESERTA DIDIK</div>
    <table class='identitas-table' style='width: 100%;'>
        <tr>
            <td width='15%'>Nama Peserta Didik</td><td>:</td><td width='35%'><?= htmlspecialchars($siswa['nama_lengkap'] ?? '-') ?></td>
            <td width='15%'>Kelas</td><td>:</td><td width='35%'><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
        </tr>
        <tr>
            <td>NISN / NIS</td><td>:</td><td><?= htmlspecialchars($siswa['nisn'] ?? '-') ?> / <?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
            <td>Fase</td><td>:</td><td><?= (isset($siswa['id_kelas']) && $siswa['id_kelas'] <= 10) ? 'E' : 'F' ?></td>
        </tr>
        <tr>
            <td>Nama Sekolah</td><td>:</td><td><?= htmlspecialchars($siswa['tenant_info']['nama_sekolah'] ?? '-') ?></td>
            <td>Semester</td><td>:</td><td><?= htmlspecialchars($_GET['semester'] ?? '-') ?></td>
        </tr>
        <tr>
            <td>Alamat</td><td>:</td><td><?= htmlspecialchars($siswa['tenant_info']['alamat_sekolah'] ?? '-') ?></td>
            <td>Tahun Pelajaran</td><td>:</td><td><?= htmlspecialchars($_GET['ta'] ?? '-') ?></td>
        </tr>
    </table>

    <div class='section-title'>B. INTRAKURIKULER</div>
    <table class='data-table'>
        <thead>
            <tr>
                <th width='5%'>NO.</th>
                <th width='35%'>MATA PELAJARAN</th>
                <th width='10%'>NILAI AKHIR</th>
                <th width='40%'>CAPAIAN PEMBELAJARAN</th>
                <th width='10%'>KKTP</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $jumlahNilai = 0;
            $count = 0;
            if (!empty($grades)): 
                foreach ($grades as $index => $g):
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
                        $capaian = "Tercapai dengan baik.";
                    }

                    $kktp = !empty($g['kkm']) ? floatval($g['kkm']) : '-';
            ?>
            <tr>
                <td class='text-center'><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($g['nama_mapel']) ?></td>
                <td class='text-center'><?= $nilaiAkhir ?></td>
                <td style="white-space: pre-line; font-size: 9.5pt;"><?= htmlspecialchars($capaian) ?></td>
                <td class='text-center'><?= $kktp ?></td>
            </tr>
            <?php 
                endforeach;
            else: 
            ?>
            <tr>
                <td colspan='5' class='text-center'><i>Belum ada data nilai intrakurikuler di semester ini.</i></td>
            </tr>
            <?php 
            endif; 
            
            $rataRata = $count > 0 ? number_format($jumlahNilai / $count, 2) : 0;
            ?>
            <tr>
                <td colspan='2' style='text-align: right; font-weight: bold;'>Jumlah Nilai</td>
                <td class='text-center' style='font-weight: bold;'><?= $jumlahNilai ?></td>
                <td colspan='2'></td>
            </tr>
            <tr>
                <td colspan='2' style='text-align: right; font-weight: bold;'>Rata-rata</td>
                <td class='text-center' style='font-weight: bold;'><?= $rataRata ?></td>
                <td colspan='2'></td>
            </tr>
        </tbody>
    </table>

    <div class='section-title'>C. PROJEK PENGUATAN PROFIL PELAJAR PANCASILA (P5)</div>
    <table class='identitas-table'>
        <tr><td width='15%'>Tema Projek 1</td><td>:</td><td>-</td></tr>
        <tr><td>Tema Projek 2</td><td>:</td><td>-</td></tr>
        <tr><td>Tema Projek 3</td><td>:</td><td>-</td></tr>
    </table>
    <table class='data-table'>
        <thead>
            <tr>
                <th width='5%'>No.</th>
                <th width='20%'>Dimensi</th>
                <th width='25%'>Elemen</th>
                <th width='25%'>Sub-Elemen</th>
                <th width='25%'>Target Pencapaian di akhir Fase</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class='text-center'>1</td>
                <td>Beriman, Bertakwa kepada Tuhan Yang Maha Esa, dan Berakhlak Mulia</td>
                <td></td><td></td><td></td>
            </tr>
            <tr>
                <td class='text-center'>2</td>
                <td>Berkebhinekaan Global</td>
                <td></td><td></td><td></td>
            </tr>
            <tr>
                <td class='text-center'>3</td>
                <td>Bergotong Royong</td>
                <td></td><td></td><td></td>
            </tr>
            <tr>
                <td class='text-center'>4</td>
                <td>Mandiri</td>
                <td></td><td></td><td></td>
            </tr>
            <tr>
                <td class='text-center'>5</td>
                <td>Bernalar Kritis</td>
                <td></td><td></td><td></td>
            </tr>
            <tr>
                <td class='text-center'>6</td>
                <td>Kreatif</td>
                <td></td><td></td><td></td>
            </tr>
        </tbody>
    </table>

    <div class='section-title'>D. EKSTRAKURIKULER</div>
    <table class='data-table'>
        <thead>
            <tr>
                <th width='5%'>No.</th>
                <th width='35%'>Ekstrakurikuler</th>
                <th width='50%'>Keterangan</th>
                <th width='10%'>Nilai</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class='text-center'>1</td>
                <td>Praja Muda Karana (Pramuka)</td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class='text-center'>2</td>
                <td>Usaha Kesehatan Sekolah (UKS)</td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class='text-center'>3</td>
                <td></td><td></td><td></td>
            </tr>
        </tbody>
    </table>

    <div class='section-title'>E. PRESTASI</div>
    <table class='data-table'>
        <thead>
            <tr>
                <th>Jenis Prestasi</th>
                <th>Tingkat Prestasi</th>
                <th>Nama Prestasi</th>
                <th>Tahun Prestasi</th>
                <th>Penyelenggara</th>
                <th>Peringkat</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan='6' class='text-center'><i>Tidak ada data prestasi</i></td>
            </tr>
        </tbody>
    </table>

    <div class='section-title'>F. KETIDAKHADIRAN</div>
    <table class='data-table' style='width: 50%;'>
        <tbody>
            <tr>
                <td rowspan='3' width='40%' style='vertical-align: middle; font-weight: bold;'>KETIDAKHADIRAN</td>
                <td width='40%'>1. Sakit</td>
                <td width='20%' class='text-center'>.... Hari</td>
            </tr>
            <tr>
                <td>2. Izin</td>
                <td class='text-center'>.... Hari</td>
            </tr>
            <tr>
                <td>3. Tanpa Keterangan</td>
                <td class='text-center'>.... Hari</td>
            </tr>
        </tbody>
    </table>

    <div class='signature-box'>
        <div class='signature-col'>
            Mengetahui<br>
            Orang Tua/Wali<br><br><br><br><br>
            ................................................
        </div>
        <div class='signature-col'>
            <br>
            Wali Kelas<br><br><br><br><br>
            ................................................<br>
            NIP. ........................................
        </div>
        <div class='signature-col'>
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
