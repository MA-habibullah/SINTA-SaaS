<?php
// Prepare variables securely
$namaLengkap = $siswa['nama_lengkap'] ?? '';
$namaPanggilan = $siswa['nama_panggilan'] ?? '';
$nis = $siswa['nis'] ?? '......................';
$nisn = $siswa['nisn'] ?? '......................';
$nik = $siswa['nik'] ?? '......................';
$noKk = $siswa['no_kk'] ?? '......................';
$sekolah = $siswa['nama_sekolah'] ?? '......................';
$tempatLahir = $siswa['tempat_lahir'] ?? '......................';
$tanggalLahirStr = '......................';
if (!empty($siswa['tanggal_lahir'])) {
    $d = new DateTime($siswa['tanggal_lahir']);
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $tanggalLahirStr = $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
}
$jk = ($siswa['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : (($siswa['jenis_kelamin'] ?? '') === 'P' ? 'Perempuan' : 'Laki-laki / Perempuan *)');
$agama = $siswa['agama'] ?? '......................';
$kewarganegaraan = $siswa['kewarganegaraan'] ?? '......................';
$anakKe = $siswa['anak_ke'] ?? '.......';
$jmlSaudara = $siswa['jumlah_saudara'] ?? '.......';
$bahasa = $siswa['bahasa_sehari_hari'] ?? '......................';
$golDarah = $siswa['golongan_darah'] ?? '.......';

// Alamat
$rt = !empty($siswa['rt']) ? $siswa['rt'] : '.......';
$rw = !empty($siswa['rw']) ? $siswa['rw'] : '.......';
$desa = !empty($siswa['nama_kelurahan']) ? $siswa['nama_kelurahan'] : '......................';
$kecamatan = !empty($siswa['nama_kecamatan']) ? $siswa['nama_kecamatan'] : '......................';
$kota = !empty($siswa['nama_kota']) ? $siswa['nama_kota'] : '......................';
$provinsi = !empty($siswa['nama_provinsi']) ? $siswa['nama_provinsi'] : '......................';
$kodePos = !empty($siswa['kode_pos']) ? $siswa['kode_pos'] : '.......';
$noTelp = !empty($siswa['no_telepon_rumah']) ? $siswa['no_telepon_rumah'] : (!empty($siswa['no_telepon_siswa']) ? $siswa['no_telepon_siswa'] : '......................');

$bertempatTinggalPada = $siswa['status_tinggal'] ?? '......................';
$jarakSekolah = $siswa['jarak_rumah'] ?? '......................';

// Orang Tua
$ayah = $siswa['nama_ayah'] ?? '......................';
$ibu = $siswa['nama_ibu'] ?? '......................';
$pendidikanAyah = $siswa['pendidikan_ayah'] ?? '......................';
$pendidikanIbu = $siswa['pendidikan_ibu'] ?? '......................';
$pekerjaanAyah = $siswa['pekerjaan_ayah'] ?? '......................';
$pekerjaanIbu = $siswa['pekerjaan_ibu'] ?? '......................';

// Wali
$wali = !empty($siswa['nama_wali']) ? $siswa['nama_wali'] : '......................';
$hubWali = !empty($siswa['hubungan_wali']) ? $siswa['hubungan_wali'] : '......................';
$pendidikanWali = !empty($siswa['pendidikan_wali']) ? $siswa['pendidikan_wali'] : '......................';
$pekerjaanWali = !empty($siswa['pekerjaan_wali']) ? $siswa['pekerjaan_wali'] : '......................';

// Pendidikan Sebelumnya
$asalSekolahRaw = $siswa['sekolah_asal'] ?? '......................';
$asalSekolahTingkat = '......................';
$asalSekolahNama = '......................';

if (!empty($siswa['sekolah_asal'])) {
    $asalLower = strtolower($siswa['sekolah_asal']);
    if (str_contains($asalLower, 'smp') || str_contains($asalLower, 'tsanawiyah') || str_contains($asalLower, 'mts')) {
        $asalSekolahTingkat = 'SMP / MTs';
    } elseif (str_contains($asalLower, 'sd') || str_contains($asalLower, 'ibtidaiyah') || str_contains($asalLower, 'mi')) {
        $asalSekolahTingkat = 'SD / MI';
    } elseif (str_contains($asalLower, 'sma') || str_contains($asalLower, 'aliyah') || str_contains($asalLower, 'ma') || str_contains($asalLower, 'smk')) {
        $asalSekolahTingkat = 'SMA / MA / SMK';
    }
    $asalSekolahNama = $siswa['sekolah_asal'];
}

$tglIjazah = '......................';
if (!empty($siswa['tanggal_ijazah_sebelumnya'])) {
    $tglIjazah = date('d-m-Y', strtotime($siswa['tanggal_ijazah_sebelumnya']));
}
$noIjazah = $siswa['no_ijazah_sebelumnya'] ?? '......................';
if ($tglIjazah !== '......................' && $noIjazah !== '......................') {
    $tglIjazahGabung = $tglIjazah . ' / ' . $noIjazah;
} else {
    $tglIjazahGabung = '......................';
}

$jmlSaudaraTiri = isset($siswa['saudara_tiri']) && $siswa['saudara_tiri'] > 0 ? $siswa['saudara_tiri'] : '.......';
$jmlSaudaraAngkat = isset($siswa['saudara_angkat']) && $siswa['saudara_angkat'] > 0 ? $siswa['saudara_angkat'] : '.......';

$pindahDariSekolah = !empty($siswa['sekolah_asal_mutasi']) ? $siswa['sekolah_asal_mutasi'] : '......................';
$pindahDariTingkat = !empty($siswa['pindah_dari_tingkat']) ? $siswa['pindah_dari_tingkat'] : '......................';
$pindahDiterimaTgl = !empty($siswa['tanggal_masuk']) && $siswa['jenis_pendaftaran'] === 'Pindahan' ? date('d-m-Y', strtotime($siswa['tanggal_masuk'])) : '......................';
$pindahNoSurat = !empty($siswa['pindah_no_surat']) ? $siswa['pindah_no_surat'] : '......................';

// Tamat Belajar
$tamatTahun = !empty($siswa['tanggal_lulus']) ? (new DateTime($siswa['tanggal_lulus']))->format('Y') : '......................';
$tamatIjazah = !empty($siswa['nomor_ijazah_kelulusan']) ? $siswa['nomor_ijazah_kelulusan'] : '......................';
$melanjutkanKe = !empty($siswa['keterangan_setelah_lulus']) ? $siswa['keterangan_setelah_lulus'] : '......................';

// Pindah Sekolah
$pindahKeSekolah = (!empty($siswa['keluar_karena']) && $siswa['keluar_karena'] === 'Mutasi' && !empty($siswa['sekolah_tujuan'])) ? $siswa['sekolah_tujuan'] : '......................';
$pindahKeTingkat = (!empty($siswa['diterima_di_tingkat'])) ? $siswa['diterima_di_tingkat'] : '......................';
$pindahTingkatDitinggalkan = (!empty($siswa['tingkat_ditinggalkan'])) ? $siswa['tingkat_ditinggalkan'] : '......................';

// Keluar Sekolah
$keluarAlasan = (!empty($siswa['keluar_karena']) && $siswa['keluar_karena'] !== 'Mutasi' && $siswa['keluar_karena'] !== 'Lulus') ? $siswa['alasan_keluar'] : '......................';
$keluarTanggal = (!empty($siswa['tanggal_keluar'])) ? date('d-m-Y', strtotime($siswa['tanggal_keluar'])) : '......................';

// Resolve academic years (TA1, TA2, TA3)
$ta1 = '........ / ........';
$ta2 = '........ / ........';
$ta3 = '........ / ........';

if (!empty($siswa['tahun_ajaran_mulai']) && preg_match('/^(\d{4})\/(\d{4})$/', $siswa['tahun_ajaran_mulai'], $matches)) {
    $startYear = (int)$matches[1];
    $endYear = (int)$matches[2];
    
    $ta1 = $startYear . ' / ' . $endYear;
    $ta2 = ($startYear + 1) . ' / ' . ($endYear + 1);
    $ta3 = ($startYear + 2) . ' / ' . ($endYear + 2);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Induk - <?= htmlspecialchars($namaLengkap) ?></title>
    <style>
        @page {
            size: 215mm 330mm; /* Folio / F4 */
            margin: 1cm 0.8cm 1cm 2.5cm; /* top right bottom left */
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .page {
            position: relative;
        }
        .header-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 10pt;
        }
        .grid-header {
            display: grid;
            grid-template-columns: 200px 10px auto 200px 10px auto;
            row-gap: 5px;
            margin-bottom: 20px;
        }
        .photo-area {
            position: absolute;
            right: 0;
            top: 250px;
            width: 3cm;
            height: 4cm;
            border: 1px solid #000;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9pt;
            padding: 5px;
            box-sizing: border-box;
        }
        .photo-area-2 {
            position: absolute;
            right: 0;
            top: 480px;
            width: 3cm;
            height: 4cm;
            border: 1px solid #000;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9pt;
            padding: 5px;
            box-sizing: border-box;
        }
        .photo-area-3 {
            position: absolute;
            right: 0;
            top: 800px;
            width: 3cm;
            height: 4cm;
            border: 1px solid #000;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9pt;
            padding: 5px;
            box-sizing: border-box;
        }
        .section-title {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 10pt;
        }
        table.list-table {
            width: 85%;
            border-collapse: collapse;
        }
        table.list-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        .col-no { width: 4%; }
        .col-label { width: 33%; }
        .col-colon { width: 3%; }
        .col-val { width: 60%; }
        
        .sub-label { padding-left: 15px; width: 33%; }
        .sub-label-2 { padding-left: 30px; width: 33%; }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
            font-size: 9pt;
            text-align: center;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 4px;
        }
        .signature-box {
            margin-top: 30px;
            float: right;
            width: 6cm;
            text-align: left;
            margin-bottom: 50px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: none; }
        }
        .print-btn {
            background: #2563eb; color: #fff; padding: 10px 20px; border: none; cursor: pointer;
            margin: 10px; font-weight: bold; border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f1f5f9; padding: 15px; text-align: center; border-bottom: 2px solid #cbd5e1; margin-bottom: 20px;">
        <h4 style="margin: 0 0 10px 0; color: #0f172a;">Informasi Cetak</h4>
        <p style="margin: 0 0 15px 0; color: #475569; font-size: 11pt;">Pastikan Anda menggunakan ukuran kertas <strong>Folio / F4 (215 x 330 mm)</strong>. Atur margin ke "None" atau "Default".</p>
        <button class="print-btn" onclick="window.print()">Cetak Buku Induk</button>
    </div>

    <!-- Halaman 1 -->
    <div class="page">
        <?php if (isset($showQrCode) && $showQrCode): ?>
            <div style="position: absolute; right: 0; top: 40px; text-align: center; border: 1px solid #ccc; padding: 5px; border-radius: 4px; background-color: #fff; width: 85px; z-index: 10;">
                <span style="font-size: 6px; font-weight: bold; display: block; margin-bottom: 2px; text-transform: uppercase; font-family: sans-serif;">Verifikasi</span>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=75x75&data=<?= urlencode($urlVerifikasi) ?>" alt="QR Code" style="width: 75px; height: 75px; display: block; margin: 0 auto;">
            </div>
        <?php endif; ?>

        <div class="header-title">III. LEMBAR BUKU INDUK PESERTA DIDIK</div>
        <table style="width: 82%; margin-bottom: 25px; font-size: 10pt; line-height: 1.6;">
            <tr>
                <td style="width: 25%; font-weight: bold;">NOMOR INDUK SISWA</td>
                <td style="width: 2%">:</td>
                <td style="width: 28%"><?= htmlspecialchars($nis) ?></td>
                <td style="width: 15%; font-weight: bold;">KECAMATAN</td>
                <td style="width: 2%">:</td>
                <td style="width: 28%"><?= htmlspecialchars(!empty($siswa['sekolah_kecamatan']) ? $siswa['sekolah_kecamatan'] : '.................') ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">NISN</td>
                <td>:</td>
                <td><?= htmlspecialchars($nisn) ?></td>
                <td style="font-weight: bold;">KAB/KOTA</td>
                <td>:</td>
                <td><?= htmlspecialchars(!empty($siswa['sekolah_kabupaten']) ? $siswa['sekolah_kabupaten'] : '.................') ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">NPSN</td>
                <td>:</td>
                <td><?= htmlspecialchars(!empty($siswa['npsn']) ? $siswa['npsn'] : '.................') ?></td>
                <td style="font-weight: bold;">PROVINSI</td>
                <td>:</td>
                <td><?= htmlspecialchars(!empty($siswa['sekolah_provinsi']) ? $siswa['sekolah_provinsi'] : '.................') ?></td>
            </tr>
        </table>

        <div class="photo-area">
            Pas Photo<br>Ukuran :<br>3 X 4<br><br><span style="font-size:7pt">Cap tiga jari tengah kiri mengenai pas photo bagian bawah</span>
        </div>
        <div class="photo-area-2">
            Pas Photo<br>Ukuran :<br>3 X 4<br><br><span style="font-size:7pt">Cap tiga jari tengah kiri mengenai pas photo bagian bawah</span>
        </div>
        <div class="photo-area-3">
            Pas Photo<br>Ukuran :<br>3 X 4<br><br><span style="font-size:7pt">Cap tiga jari tengah kiri mengenai pas photo bagian bawah</span>
        </div>

        <div class="section-title">A. KETERANGAN SISWA</div>
        <table class="list-table">
            <tr><td class="col-no">1.</td><td class="col-label">Nama Murid</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Lengkap</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($namaLengkap) ?></td></tr>
            <tr><td></td><td class="sub-label">b. Panggilan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($namaPanggilan) ?></td></tr>
            
            <tr><td class="col-no">2.</td><td class="col-label">Jenis Kelamin</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($jk) ?></td></tr>
            
            <tr><td class="col-no">3.</td><td class="col-label">Kelahiran</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Tanggal lahir</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($tanggalLahirStr) ?></td></tr>
            <tr><td></td><td class="sub-label">b. Tempat lahir</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($tempatLahir) ?></td></tr>
            
            <tr><td class="col-no">4.</td><td class="col-label">Agama</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($agama) ?></td></tr>
            <tr><td class="col-no">5.</td><td class="col-label">Kewarganegaraan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($kewarganegaraan) ?></td></tr>
            <tr><td class="col-no">6.</td><td class="col-label">Anak ke</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($anakKe) ?></td></tr>
            
            <tr><td class="col-no">7.</td><td class="col-label">Jumlah saudara kandung</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($jmlSaudara) ?></td></tr>
            
            <tr><td class="col-no">8.</td><td class="col-label">Bahasa sehari-hari dirumah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($bahasa) ?></td></tr>
            <tr><td class="col-no">9.</td><td class="col-label">Golongan Darah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($golDarah) ?></td></tr>
            
            <tr><td class="col-no">10.</td><td class="col-label">Alamat saat diterima</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. RT / RW</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($rt) ?> / <?= htmlspecialchars($rw) ?></td></tr>
            <tr><td></td><td class="sub-label">b. Desa / Kelurahan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($desa) ?></td></tr>
            <tr><td></td><td class="sub-label">c. Kecamatan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($kecamatan) ?></td></tr>
            <tr><td></td><td class="sub-label">d. Kabupaten / Kota</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($kota) ?></td></tr>
            <tr><td></td><td class="sub-label">e. Provinsi</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($provinsi) ?></td></tr>
            
            <tr><td class="col-no">11.</td><td class="col-label">Kode Pos & No. Tlp.</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($kodePos) ?> / <?= htmlspecialchars($noTelp) ?></td></tr>
            <tr><td class="col-no">12.</td><td class="col-label">Bertempat tinggal pada</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($bertempatTinggalPada) ?></td></tr>
            <tr><td class="col-no">13.</td><td class="col-label">Jarak ke sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($jarakSekolah) ?></td></tr>
        </table>

        <div class="section-title">B. KETERANGAN ORANG TUA/WALI PESERTA DIDIK</div>
        <table class="list-table">
            <tr><td class="col-no">14.</td><td class="col-label">Nama Orang tua Kandung</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Ayah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($ayah) ?></td></tr>
            <tr><td></td><td class="sub-label-2">1) NIK Ayah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($siswa['nik_ayah'] ?? '......................') ?></td></tr>
            <tr><td></td><td class="sub-label-2">2) Tanggal Lahir / Status</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars(($siswa['tanggal_lahir_ayah'] ?? '......................') . ' / ' . ($siswa['status_hidup_ayah'] ?? '......................')) ?></td></tr>
            <tr><td></td><td class="sub-label-2">3) Agama / Kewarganegaraan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars(($siswa['agama_ayah'] ?? '......................') . ' / ' . ($siswa['kewarganegaraan_ayah'] ?? '......................')) ?></td></tr>
            <tr><td></td><td class="sub-label-2">4) Pendidikan Terakhir</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pendidikanAyah) ?></td></tr>
            <tr><td></td><td class="sub-label-2">5) Pekerjaan Utama</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pekerjaanAyah) ?></td></tr>
            <tr><td></td><td class="sub-label-2">6) Rata-rata Penghasilan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($siswa['penghasilan_ayah'] ?? '......................') ?></td></tr>
            
            <tr><td></td><td class="sub-label">b. Ibu</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($ibu) ?></td></tr>
            <tr><td></td><td class="sub-label-2">1) NIK Ibu</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($siswa['nik_ibu'] ?? '......................') ?></td></tr>
            <tr><td></td><td class="sub-label-2">2) Tanggal Lahir / Status</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars(($siswa['tanggal_lahir_ibu'] ?? '......................') . ' / ' . ($siswa['status_hidup_ibu'] ?? '......................')) ?></td></tr>
            <tr><td></td><td class="sub-label-2">3) Agama / Kewarganegaraan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars(($siswa['agama_ibu'] ?? '......................') . ' / ' . ($siswa['kewarganegaraan_ibu'] ?? '......................')) ?></td></tr>
            <tr><td></td><td class="sub-label-2">4) Pendidikan Terakhir</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pendidikanIbu) ?></td></tr>
            <tr><td></td><td class="sub-label-2">5) Pekerjaan Utama</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pekerjaanIbu) ?></td></tr>
            <tr><td></td><td class="sub-label-2">6) Rata-rata Penghasilan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($siswa['penghasilan_ibu'] ?? '......................') ?></td></tr>
            
            <?php if (!empty($wali) && $wali !== '......................'): ?>
            <tr><td class="col-no">15.</td><td class="col-label">Wali Murid</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Nama</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($wali) ?></td></tr>
            <tr><td></td><td class="sub-label">b. Hubungan Keluarga</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($hubWali) ?></td></tr>
            <tr><td></td><td class="sub-label">c. Pendidikan terakhir</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pendidikanWali) ?></td></tr>
            <tr><td></td><td class="sub-label">d. Pekerjaan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pekerjaanWali) ?></td></tr>
            <?php endif; ?>
        </table>

        <div class="section-title">C. PERKEMBANGAN PESERTA DIDIK</div>
        <table class="list-table">
            <tr><td class="col-no"><?= (!empty($wali) && $wali !== '......................') ? '16.' : '15.' ?></td><td class="col-label">Pendidikan sebelumnya</td><td class="col-colon">:</td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Masuk menjadi peserta didik baru</td><td class="col-colon">:</td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label-2">1) Asal Sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($asalSekolahTingkat) ?></td></tr>
            <tr><td></td><td class="sub-label-2">2) Nama Sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($asalSekolahNama) ?></td></tr>
            <tr><td></td><td class="sub-label-2">3) Tanggal dan Nomor Ijazah/STTB</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($tglIjazahGabung) ?></td></tr>
            <tr><td></td><td class="sub-label">b. Pindahan dari sekolah lain</td><td class="col-colon">:</td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label-2">1) Nama Sekolah asal</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pindahDariSekolah) ?></td></tr>
            <tr><td></td><td class="sub-label-2">2) Dari Tingkat</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pindahDariTingkat) ?></td></tr>
            <tr><td></td><td class="sub-label-2">3) Diterima Tanggal</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pindahDiterimaTgl) ?></td></tr>
            <tr><td></td><td class="sub-label-2">4) No. Surat Keterangan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pindahNoSurat) ?></td></tr>
        </table>

        <div class="section-title" style="margin-top:20px;">D. MENINGGALKAN SEKOLAH</div>
        <table class="list-table">
            <tr><td class="col-no"><?= (!empty($wali) && $wali !== '......................') ? '17.' : '16.' ?></td><td class="col-label">Tamat Belajar/Lulus</td><td class="col-colon">:</td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Tahun</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($tamatTahun) ?></td></tr>
            <tr><td></td><td class="sub-label">b. Nomor Ijazah/STTB</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($tamatIjazah) ?></td></tr>
            <tr><td></td><td class="sub-label">c. Melanjutkan ke sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($melanjutkanKe) ?></td></tr>
            
            <tr><td class="col-no"><?= (!empty($wali) && $wali !== '......................') ? '18.' : '17.' ?></td><td class="col-label">Pindah sekolah</td><td class="col-colon">:</td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Tingkat/Kelas yang ditinggalkan</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pindahTingkatDitinggalkan) ?></td></tr>
            <tr><td></td><td class="sub-label">b. ke Sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pindahKeSekolah) ?></td></tr>
            <tr><td></td><td class="sub-label">c. ke Tingkat</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($pindahKeTingkat) ?></td></tr>
            
            <tr><td class="col-no"><?= (!empty($wali) && $wali !== '......................') ? '19.' : '18.' ?></td><td class="col-label">Keluar Sekolah</td><td class="col-colon">:</td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Alasan Keluar sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($keluarAlasan) ?></td></tr>
            <tr><td></td><td class="sub-label">b. Hari dan tanggal Keluar sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($keluarTanggal) ?></td></tr>
        </table>

        <div class="section-title">E. LAIN-LAIN</div>
        <div>1. TINGGI DAN BERAT BADAN PESERTA DIDIK</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="3" style="width:5%">NO.</th>
                    <th rowspan="3" style="width:20%">Aspek yang dinilai</th>
                    <th colspan="2">Thn Pelajaran <?= htmlspecialchars($ta1) ?></th>
                    <th colspan="2">Thn Pelajaran <?= htmlspecialchars($ta2) ?></th>
                    <th colspan="2">Thn Pelajaran <?= htmlspecialchars($ta3) ?></th>
                </tr>
                <tr>
                    <th colspan="2">Semester</th>
                    <th colspan="2">Semester</th>
                    <th colspan="2">Semester</th>
                </tr>
                <tr>
                    <th>Ganjil</th>
                    <th>Genap</th>
                    <th>Ganjil</th>
                    <th>Genap</th>
                    <th>Ganjil</th>
                    <th>Genap</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td style="text-align:left">Tinggi Badan</td>
                    <td><?= !empty($siswa['kesehatan'][1]['tinggi_badan']) ? $siswa['kesehatan'][1]['tinggi_badan'] . ' cm' : '..... Cm' ?></td>
                    <td><?= !empty($siswa['kesehatan'][2]['tinggi_badan']) ? $siswa['kesehatan'][2]['tinggi_badan'] . ' cm' : '..... Cm' ?></td>
                    <td><?= !empty($siswa['kesehatan'][3]['tinggi_badan']) ? $siswa['kesehatan'][3]['tinggi_badan'] . ' cm' : '..... Cm' ?></td>
                    <td><?= !empty($siswa['kesehatan'][4]['tinggi_badan']) ? $siswa['kesehatan'][4]['tinggi_badan'] . ' cm' : '..... Cm' ?></td>
                    <td><?= !empty($siswa['kesehatan'][5]['tinggi_badan']) ? $siswa['kesehatan'][5]['tinggi_badan'] . ' cm' : '..... Cm' ?></td>
                    <td><?= !empty($siswa['kesehatan'][6]['tinggi_badan']) ? $siswa['kesehatan'][6]['tinggi_badan'] . ' cm' : '..... Cm' ?></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td style="text-align:left">Berat Badan</td>
                    <td><?= !empty($siswa['kesehatan'][1]['berat_badan']) ? $siswa['kesehatan'][1]['berat_badan'] . ' kg' : '..... Kg' ?></td>
                    <td><?= !empty($siswa['kesehatan'][2]['berat_badan']) ? $siswa['kesehatan'][2]['berat_badan'] . ' kg' : '..... Kg' ?></td>
                    <td><?= !empty($siswa['kesehatan'][3]['berat_badan']) ? $siswa['kesehatan'][3]['berat_badan'] . ' kg' : '..... Kg' ?></td>
                    <td><?= !empty($siswa['kesehatan'][4]['berat_badan']) ? $siswa['kesehatan'][4]['berat_badan'] . ' kg' : '..... Kg' ?></td>
                    <td><?= !empty($siswa['kesehatan'][5]['berat_badan']) ? $siswa['kesehatan'][5]['berat_badan'] . ' kg' : '..... Kg' ?></td>
                    <td><?= !empty($siswa['kesehatan'][6]['berat_badan']) ? $siswa['kesehatan'][6]['berat_badan'] . ' kg' : '..... Kg' ?></td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 15px;">2. KONDISI KESEHATAN</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width:5%">NO.</th>
                    <th rowspan="2" style="width:20%">Aspek yang dinilai</th>
                    <th>Thn Pelajaran <?= htmlspecialchars($ta1) ?></th>
                    <th>Thn Pelajaran <?= htmlspecialchars($ta2) ?></th>
                    <th>Thn Pelajaran <?= htmlspecialchars($ta3) ?></th>
                </tr>
                <tr>
                    <th>Keterangan</th>
                    <th>Keterangan</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1.</td>
                    <td style="text-align:left">Pendengaran</td>
                    <td><?= !empty($siswa['kesehatan'][2]['pendengaran']) ? $siswa['kesehatan'][2]['pendengaran'] : '...................' ?></td>
                    <td><?= !empty($siswa['kesehatan'][4]['pendengaran']) ? $siswa['kesehatan'][4]['pendengaran'] : '...................' ?></td>
                    <td><?= !empty($siswa['kesehatan'][6]['pendengaran']) ? $siswa['kesehatan'][6]['pendengaran'] : '...................' ?></td>
                </tr>
                <tr>
                    <td>2.</td>
                    <td style="text-align:left">Pengelihatan</td>
                    <td><?= !empty($siswa['kesehatan'][2]['pengelihatan']) ? $siswa['kesehatan'][2]['pengelihatan'] : '...................' ?></td>
                    <td><?= !empty($siswa['kesehatan'][4]['pengelihatan']) ? $siswa['kesehatan'][4]['pengelihatan'] : '...................' ?></td>
                    <td><?= !empty($siswa['kesehatan'][6]['pengelihatan']) ? $siswa['kesehatan'][6]['pengelihatan'] : '...................' ?></td>
                </tr>
                <tr>
                    <td>3.</td>
                    <td style="text-align:left">Gigi</td>
                    <td><?= !empty($siswa['kesehatan'][2]['gigi']) ? $siswa['kesehatan'][2]['gigi'] : '...................' ?></td>
                    <td><?= !empty($siswa['kesehatan'][4]['gigi']) ? $siswa['kesehatan'][4]['gigi'] : '...................' ?></td>
                    <td><?= !empty($siswa['kesehatan'][6]['gigi']) ? $siswa['kesehatan'][6]['gigi'] : '...................' ?></td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 15px;">3. PRESTASI PESERTA DIDIK</div>
        <table class="data-table">
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
                <?php if (empty($siswa['prestasi'])): ?>
                    <tr>
                        <td style="height: 30px;"></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <td style="height: 30px;"></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($siswa['prestasi'] as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['jenis_lomba'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['tingkat_kejuaraan'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['nama_lomba'] ?? '') ?></td>
                            <td><?= htmlspecialchars(substr($p['tanggal_lomba'] ?? '', 0, 4)) ?></td>
                            <td><?= htmlspecialchars($p['penyelenggara'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['juara'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 15px;">4. BEASISWA PESERTA DIDIK</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Jenis Beasiswa</th>
                    <th>Keterangan</th>
                    <th>Tahun Mulai</th>
                    <th>Tahun Selesai</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($siswa['beasiswa'])): ?>
                    <tr>
                        <td style="height: 30px;"></td><td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <td style="height: 30px;"></td><td></td><td></td><td></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($siswa['beasiswa'] as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['jenis_beasiswa'] ?? '') ?></td>
                            <td><?= htmlspecialchars($b['sumber'] ?? '') ?></td>
                            <td><?= htmlspecialchars($b['tahun_menerima'] ?? '') ?></td>
                            <td><?= htmlspecialchars($b['tahun_menerima'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
