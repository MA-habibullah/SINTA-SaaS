<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Buku Induk - {{ $siswa['nama_lengkap'] ?? 'Peserta Didik' }}</title>
    <style>
        @page {
            size: 215mm 330mm; /* Standar Folio / F4 Resmi Buku Induk */
            margin: 1.2cm 1.0cm 1.2cm 2.2cm; /* Margin kiri 2.2cm untuk arsip jilid */
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .no-print {
            background: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
            padding: 12px 20px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .print-btn {
            background: #1d4ed8;
            color: #fff;
            padding: 8px 24px;
            border: none;
            border-radius: 8px;
            font-size: 10.5pt;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(29, 78, 216, 0.3);
            transition: background 0.2s;
        }
        .print-btn:hover {
            background: #1e40af;
        }
        .page {
            position: relative;
            page-break-after: always;
            padding-bottom: 15px;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .header-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .table-meta {
            width: 78%;
            margin-bottom: 15px;
            font-size: 9.5pt;
            border-collapse: collapse;
        }
        .table-meta td {
            padding: 2px 0;
            vertical-align: top;
        }
        .section-title {
            font-weight: bold;
            font-size: 9.5pt;
            margin-top: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        table.list-table {
            width: 78%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        table.list-table td {
            vertical-align: top;
            padding: 1.5px 0;
        }
        .col-no { width: 4%; font-weight: bold; }
        .col-label { width: 34%; }
        .col-colon { width: 2.5%; text-align: center; }
        .col-val { width: 59.5%; font-weight: 500; }
        .sub-label { padding-left: 12px; }
        .sub-label-2 { padding-left: 24px; }

        /* 3 Pas Foto 3x4 Area */
        .photo-container {
            position: absolute;
            right: 0;
            top: 45px;
            width: 3.1cm;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .photo-box {
            width: 3cm;
            height: 4cm;
            border: 1px solid #000;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4px;
            box-sizing: border-box;
            font-size: 8pt;
            line-height: 1.2;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-cap {
            font-size: 6.5pt;
            margin-top: 4px;
            color: #333;
            line-height: 1.1;
        }

        /* QR Code Verifikasi */
        .qr-box {
            position: absolute;
            right: 0;
            top: -5px;
            text-align: center;
            border: 1px solid #cbd5e1;
            padding: 3px;
            border-radius: 4px;
            background: #fff;
            width: 72px;
        }
        .qr-box span {
            font-size: 5.5pt;
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        /* Tabel Data / Matriks */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 12px;
            font-size: 8.5pt;
            text-align: center;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 3.5px 4px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
        }

        .signature-section {
            margin-top: 25px;
            width: 100%;
            display: table;
            table-layout: fixed;
            page-break-inside: avoid;
        }
        .signature-col-left {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            font-size: 9pt;
        }
        .signature-col-right {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            text-align: right;
            font-size: 9pt;
        }
        .signature-box {
            display: inline-block;
            text-align: left;
            min-width: 220px;
        }
        .signature-space {
            height: 1.8cm;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: transparent; }
            .page { padding-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div style="font-size: 11pt; font-weight: bold; color: #0f172a; margin-bottom: 4px;">
            🖨️ Cetak Lembar Buku Induk Peserta Didik (Resmi)
        </div>
        <div style="font-size: 9pt; color: #475569; margin-bottom: 8px;">
            Standar format resmi <strong>Folio / F4 (215 x 330 mm)</strong> atau <strong>A4</strong>. Atur Margin ke "Default" atau "None".
        </div>
        <button class="print-btn" onclick="window.print()">
            <svg style="width:16px;height:16px;fill:currentColor" viewBox="0 0 16 16">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Cetak Lembar Buku Induk
        </button>
    </div>

    @php
        // Resolve variables safely
        $namaLengkap    = $siswa['nama_lengkap'] ?? '-';
        $namaPanggilan  = $siswa['nama_panggilan'] ?? '-';
        $nis            = $siswa['nis'] ?? '......................';
        $nisn           = $siswa['nisn'] ?? '......................';
        $nik            = $siswa['nik'] ?? '......................';
        $noKk           = $siswa['no_kk'] ?? '......................';
        $tempatLahir    = $siswa['tempat_lahir'] ?? '......................';
        
        $tanggalLahirStr = '......................';
        if (!empty($siswa['tanggal_lahir'])) {
            try {
                $d = new DateTime($siswa['tanggal_lahir']);
                $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                $tanggalLahirStr = $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
            } catch (\Throwable $e) {
                $tanggalLahirStr = (string)$siswa['tanggal_lahir'];
            }
        }

        $jk = ($siswa['jenis_kelamin'] ?? '') === 'L' || strcasecmp($siswa['jenis_kelamin'] ?? '', 'Laki-laki') === 0 ? 'Laki-laki' : 'Perempuan';
        $agama = $siswa['agama'] ?? '......................';
        $kewarganegaraan = $siswa['kewarganegaraan'] ?? 'Indonesia';
        $anakKe = $siswa['anak_ke'] ?? '.......';
        $jmlSaudara = $siswa['jumlah_saudara'] ?? '.......';
        $saudaraTiri = $siswa['saudara_tiri'] ?? '.......';
        $saudaraAngkat = $siswa['saudara_angkat'] ?? '.......';
        $bahasa = $siswa['bahasa_sehari_hari'] ?? 'Bahasa Indonesia';
        $golDarah = $siswa['fisik']['golongan_darah'] ?? ($siswa['golongan_darah'] ?? '.......');

        // Alamat
        $rt = $siswa['rt'] ?? '.......';
        $rw = $siswa['rw'] ?? '.......';
        $desa = $siswa['nama_kelurahan'] ?? '......................';
        $kecamatan = $siswa['nama_kecamatan'] ?? '......................';
        $kota = $siswa['nama_kota'] ?? '......................';
        $provinsi = $siswa['nama_provinsi'] ?? '......................';
        $kodePos = $siswa['kode_pos'] ?? '.......';
        $noTelp = $siswa['no_hp'] ?? ($siswa['no_telepon_rumah'] ?? '......................');
        $statusTinggal = $siswa['status_tinggal'] ?? ($siswa['tinggal_dengan'] ?? 'Bersama Orang Tua');
        $jarakSekolah = $siswa['jarak_rumah'] ? $siswa['jarak_rumah'] . ' Km' : '......................';

        // Orang Tua (Ayah, Ibu, Wali)
        $ortuList = $siswa['orang_tua'] ?? [];
        $ayah = null;
        $ibu = null;
        $wali = null;
        if (is_array($ortuList) || is_object($ortuList)) {
            foreach ($ortuList as $ot) {
                $otArr = (array)$ot;
                $hub = strtolower($otArr['hubungan'] ?? '');
                if ($hub === 'ayah') $ayah = $otArr;
                elseif ($hub === 'ibu') $ibu = $otArr;
                elseif ($hub === 'wali') $wali = $otArr;
            }
        }

        $namaAyah = $ayah['nama'] ?? ($siswa['nama_ayah'] ?? '......................');
        $nikAyah = $ayah['nik'] ?? ($siswa['nik_ayah'] ?? '......................');
        $statusAyah = $ayah['status_hidup'] ?? ($ayah['status'] ?? 'Masih Hidup');
        $pendidikanAyah = $ayah['pendidikan'] ?? ($siswa['pendidikan_ayah'] ?? '......................');
        $pekerjaanAyah = $ayah['pekerjaan'] ?? ($siswa['pekerjaan_ayah'] ?? '......................');
        $penghasilanAyah = $ayah['penghasilan'] ?? ($siswa['penghasilan_ayah'] ?? '......................');

        $namaIbu = $ibu['nama'] ?? ($siswa['nama_ibu'] ?? '......................');
        $nikIbu = $ibu['nik'] ?? ($siswa['nik_ibu'] ?? '......................');
        $statusIbu = $ibu['status_hidup'] ?? ($ibu['status'] ?? 'Masih Hidup');
        $pendidikanIbu = $ibu['pendidikan'] ?? ($siswa['pendidikan_ibu'] ?? '......................');
        $pekerjaanIbu = $ibu['pekerjaan'] ?? ($siswa['pekerjaan_ibu'] ?? '......................');
        $penghasilanIbu = $ibu['penghasilan'] ?? ($siswa['penghasilan_ibu'] ?? '......................');

        $namaWali = $wali['nama'] ?? ($siswa['nama_wali'] ?? '');
        $hubWali = $wali['hubungan'] ?? ($siswa['hubungan_wali'] ?? 'Wali');
        $pendidikanWali = $wali['pendidikan'] ?? ($siswa['pendidikan_wali'] ?? '-');
        $pekerjaanWali = $wali['pekerjaan'] ?? ($siswa['pekerjaan_wali'] ?? '-');

        // Pendidikan Sebelumnya & Mutasi
        $sekolahAsal = $siswa['sekolah_asal'] ?? ($siswa['asal_sekolah'] ?? '......................');
        $noIjazahSebelumnya = $siswa['no_ijazah_sebelumnya'] ?? '......................';
        $tglIjazahSebelumnya = !empty($siswa['tanggal_ijazah_sebelumnya']) ? date('d-m-Y', strtotime($siswa['tanggal_ijazah_sebelumnya'])) : '......................';
        $tglIjazahGabung = ($noIjazahSebelumnya !== '......................') ? $tglIjazahSebelumnya . ' / ' . $noIjazahSebelumnya : '......................';

        $pindahSekolahAsal = $siswa['sekolah_asal_mutasi'] ?? '......................';
        $pindahTingkat = $siswa['pindah_dari_tingkat'] ?? '......................';
        $pindahTglMasuk = !empty($siswa['tanggal_masuk']) ? date('d-m-Y', strtotime($siswa['tanggal_masuk'])) : '......................';
        $pindahNoSurat = $siswa['pindah_no_surat'] ?? '......................';

        // Meninggalkan Sekolah
        $tamatTahun = !empty($siswa['tanggal_lulus']) ? date('Y', strtotime($siswa['tanggal_lulus'])) : ($siswa['tahun_lulus'] ?? '......................');
        $tamatIjazah = $siswa['nomor_ijazah_kelulusan'] ?? '......................';
        $melanjutkanKe = $siswa['keterangan_setelah_lulus'] ?? '......................';

        // Riwayat Tahun Ajaran (TA 1, TA 2, TA 3)
        $ta1 = '........ / ........';
        $ta2 = '........ / ........';
        $ta3 = '........ / ........';
        $startYear = !empty($siswa['angkatan']) ? (int)$siswa['angkatan'] : (!empty($siswa['tahun_masuk']) ? (int)$siswa['tahun_masuk'] : (int)date('Y'));
        if ($startYear > 2000) {
            $ta1 = $startYear . ' / ' . ($startYear + 1);
            $ta2 = ($startYear + 1) . ' / ' . ($startYear + 2);
            $ta3 = ($startYear + 2) . ' / ' . ($startYear + 3);
        }

        // QR Code URL
        $urlVerifikasi = url('/verify-transkrip?id=' . ($siswa['id'] ?? ''));
    @endphp

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- HALAMAN 1: IDENTITAS SISWA, KELUARGA & PERKEMBANGAN MASUK               -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div class="page">
        <!-- QR Code Verifikasi -->
        <div class="qr-box">
            <span>Verifikasi</span>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=64x64&data={{ urlencode($urlVerifikasi) }}" alt="QR Code" style="width: 62px; height: 62px; display: block; margin: 0 auto;">
        </div>

        <div class="header-title">III. LEMBAR BUKU INDUK PESERTA DIDIK</div>

        <!-- Meta Identitas Atas -->
        <table class="table-meta">
            <tr>
                <td style="width: 25%; font-weight: bold;">NOMOR INDUK SISWA</td>
                <td style="width: 2%;">:</td>
                <td style="width: 28%;">{{ $nis }}</td>
                <td style="width: 17%; font-weight: bold;">KECAMATAN</td>
                <td style="width: 2%;">:</td>
                <td style="width: 26%;">{{ $tenant['kecamatan'] ?? ($siswa['nama_kecamatan'] ?? '.................') }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">NISN</td>
                <td>:</td>
                <td>{{ $nisn }}</td>
                <td style="font-weight: bold;">KAB / KOTA</td>
                <td>:</td>
                <td>{{ $tenant['kabupaten_kota'] ?? ($siswa['nama_kota'] ?? '.................') }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">NPSN</td>
                <td>:</td>
                <td>{{ $tenant['npsn'] ?? '.................' }}</td>
                <td style="font-weight: bold;">PROVINSI</td>
                <td>:</td>
                <td>{{ $tenant['provinsi'] ?? ($siswa['nama_provinsi'] ?? '.................') }}</td>
            </tr>
        </table>

        <!-- 3 Pas Foto 3x4 (Tahun 1, 2, 3) -->
        <div class="photo-container">
            <div class="photo-box">
                @if(!empty($siswa['foto_url']))
                    <img src="{{ $siswa['foto_url'] }}" alt="Foto Siswa">
                @else
                    <strong>Pas Photo</strong><br>Ukuran 3 x 4 cm
                @endif
                <div class="photo-cap">Tahun Pertama Masuk<br>(Cap 3 Jari Kiri)</div>
            </div>
            <div class="photo-box">
                <strong>Pas Photo</strong><br>Ukuran 3 x 4 cm
                <div class="photo-cap">Kenaikan Tingkat<br>(Cap 3 Jari Kiri)</div>
            </div>
            <div class="photo-box">
                <strong>Pas Photo</strong><br>Ukuran 3 x 4 cm
                <div class="photo-cap">Saat Kelulusan / Tamat<br>(Cap 3 Jari Kiri)</div>
            </div>
        </div>

        <!-- A. KETERANGAN SISWA -->
        <div class="section-title">A. KETERANGAN SISWA</div>
        <table class="list-table">
            <tr><td class="col-no">1.</td><td class="col-label">Nama Murid</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Lengkap</td><td class="col-colon">:</td><td class="col-val" style="font-weight: bold; text-transform: uppercase;">{{ $namaLengkap }}</td></tr>
            <tr><td></td><td class="sub-label">b. Panggilan</td><td class="col-colon">:</td><td class="col-val">{{ $namaPanggilan }}</td></tr>

            <tr><td class="col-no">2.</td><td class="col-label">Jenis Kelamin</td><td class="col-colon">:</td><td class="col-val">{{ $jk }}</td></tr>

            <tr><td class="col-no">3.</td><td class="col-label">Kelahiran</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Tanggal Lahir</td><td class="col-colon">:</td><td class="col-val">{{ $tanggalLahirStr }}</td></tr>
            <tr><td></td><td class="sub-label">b. Tempat Lahir</td><td class="col-colon">:</td><td class="col-val">{{ $tempatLahir }}</td></tr>

            <tr><td class="col-no">4.</td><td class="col-label">Agama</td><td class="col-colon">:</td><td class="col-val">{{ $agama }}</td></tr>
            <tr><td class="col-no">5.</td><td class="col-label">Kewarganegaraan</td><td class="col-colon">:</td><td class="col-val">{{ $kewarganegaraan }}</td></tr>
            <tr><td class="col-no">6.</td><td class="col-label">Anak Ke-</td><td class="col-colon">:</td><td class="col-val">{{ $anakKe }} (dari {{ $jmlSaudara }} bersaudara)</td></tr>

            <tr><td class="col-no">7.</td><td class="col-label">Bahasa Sehari-hari di Rumah</td><td class="col-colon">:</td><td class="col-val">{{ $bahasa }}</td></tr>
            <tr><td class="col-no">8.</td><td class="col-label">Golongan Darah</td><td class="col-colon">:</td><td class="col-val" style="font-weight: bold;">{{ $golDarah }}</td></tr>

            <tr><td class="col-no">9.</td><td class="col-label">Alamat Tempat Tinggal</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Jalan / RT / RW</td><td class="col-colon">:</td><td class="col-val">{{ $siswa['alamat'] ?? '-' }} (RT {{ $rt }} / RW {{ $rw }})</td></tr>
            <tr><td></td><td class="sub-label">b. Desa / Kelurahan</td><td class="col-colon">:</td><td class="col-val">{{ $desa }}</td></tr>
            <tr><td></td><td class="sub-label">c. Kecamatan</td><td class="col-colon">:</td><td class="col-val">{{ $kecamatan }}</td></tr>
            <tr><td></td><td class="sub-label">d. Kab / Kota & Prov</td><td class="col-colon">:</td><td class="col-val">{{ $kota }}, {{ $provinsi }} (Kode Pos: {{ $kodePos }})</td></tr>

            <tr><td class="col-no">10.</td><td class="col-label">Nomor Telepon / HP</td><td class="col-colon">:</td><td class="col-val">{{ $noTelp }}</td></tr>
            <tr><td class="col-no">11.</td><td class="col-label">Bertempat Tinggal Pada</td><td class="col-colon">:</td><td class="col-val">{{ $statusTinggal }}</td></tr>
            <tr><td class="col-no">12.</td><td class="col-label">Jarak Rumah ke Sekolah</td><td class="col-colon">:</td><td class="col-val">{{ $jarakSekolah }}</td></tr>
        </table>

        <!-- B. KETERANGAN ORANG TUA / WALI -->
        <div class="section-title" style="margin-top: 12px;">B. KETERANGAN ORANG TUA / WALI PESERTA DIDIK</div>
        <table class="list-table">
            <tr><td class="col-no">13.</td><td class="col-label">Orang Tua Kandung</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Ayah Kandung</td><td class="col-colon">:</td><td class="col-val" style="font-weight: bold;">{{ $namaAyah }}</td></tr>
            <tr><td></td><td class="sub-label-2">1) NIK Ayah</td><td class="col-colon">:</td><td class="col-val">{{ $nikAyah }}</td></tr>
            <tr><td></td><td class="sub-label-2">2) Pendidikan Terakhir</td><td class="col-colon">:</td><td class="col-val">{{ $pendidikanAyah }}</td></tr>
            <tr><td></td><td class="sub-label-2">3) Pekerjaan & Penghasilan</td><td class="col-colon">:</td><td class="col-val">{{ $pekerjaanAyah }} ({{ $penghasilanAyah }})</td></tr>

            <tr><td></td><td class="sub-label">b. Ibu Kandung</td><td class="col-colon">:</td><td class="col-val" style="font-weight: bold;">{{ $namaIbu }}</td></tr>
            <tr><td></td><td class="sub-label-2">1) NIK Ibu</td><td class="col-colon">:</td><td class="col-val">{{ $nikIbu }}</td></tr>
            <tr><td></td><td class="sub-label-2">2) Pendidikan Terakhir</td><td class="col-colon">:</td><td class="col-val">{{ $pendidikanIbu }}</td></tr>
            <tr><td></td><td class="sub-label-2">3) Pekerjaan & Penghasilan</td><td class="col-colon">:</td><td class="col-val">{{ $pekerjaanIbu }} ({{ $penghasilanIbu }})</td></tr>

            @if(!empty($namaWali) && $namaWali !== '-')
            <tr><td class="col-no">14.</td><td class="col-label">Wali Peserta Didik</td><td class="col-colon"></td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Nama Wali</td><td class="col-colon">:</td><td class="col-val" style="font-weight: bold;">{{ $namaWali }}</td></tr>
            <tr><td></td><td class="sub-label">b. Hubungan Keluarga</td><td class="col-colon">:</td><td class="col-val">{{ $hubWali }}</td></tr>
            <tr><td></td><td class="sub-label">c. Pekerjaan</td><td class="col-colon">:</td><td class="col-val">{{ $pekerjaanWali }} (Pendidikan: {{ $pendidikanWali }})</td></tr>
            @endif
        </table>

        <!-- C. PERKEMBANGAN PESERTA DIDIK -->
        <div class="section-title" style="margin-top: 12px;">C. PERKEMBANGAN PESERTA DIDIK</div>
        <table class="list-table">
            <tr><td class="col-no">{{ (!empty($namaWali) && $namaWali !== '-') ? '15.' : '14.' }}</td><td class="col-label">Pendidikan Sebelumnya</td><td class="col-colon">:</td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Asal Sekolah</td><td class="col-colon">:</td><td class="col-val">{{ $sekolahAsal }}</td></tr>
            <tr><td></td><td class="sub-label">b. Tanggal & No. Ijazah/STTB</td><td class="col-colon">:</td><td class="col-val">{{ $tglIjazahGabung }}</td></tr>
            <tr><td></td><td class="sub-label">c. Pindahan (Bila Mutasi)</td><td class="col-colon">:</td><td class="col-val">{{ $pindahSekolahAsal }} (Tingkat: {{ $pindahTingkat }})</td></tr>
            <tr><td></td><td class="sub-label">d. Diterima Tanggal & No. Surat</td><td class="col-colon">:</td><td class="col-val">{{ $pindahTglMasuk }} (No Surat: {{ $pindahNoSurat }})</td></tr>
        </table>

        <!-- D. MENINGGALKAN SEKOLAH -->
        <div class="section-title" style="margin-top: 12px;">D. MENINGGALKAN SEKOLAH / KELULUSAN</div>
        <table class="list-table">
            <tr><td class="col-no">{{ (!empty($namaWali) && $namaWali !== '-') ? '16.' : '15.' }}</td><td class="col-label">Tamat Belajar / Lulus</td><td class="col-colon">:</td><td class="col-val"></td></tr>
            <tr><td></td><td class="sub-label">a. Tahun Kelulusan</td><td class="col-colon">:</td><td class="col-val">{{ $tamatTahun }}</td></tr>
            <tr><td></td><td class="sub-label">b. Nomor Ijazah Kelulusan</td><td class="col-colon">:</td><td class="col-val">{{ $tamatIjazah }}</td></tr>
            <tr><td></td><td class="sub-label">c. Melanjutkan Ke</td><td class="col-colon">:</td><td class="col-val">{{ $melanjutkanKe }}</td></tr>
        </table>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- HALAMAN 2: MATRIKS KESEHATAN, PRESTASI, BEASISWA, BK & TRACER STUDY    -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div class="page" style="margin-top: 20px;">
        <div class="header-title" style="margin-bottom: 15px;">E. CATATAN KESEHATAN, PRESTASI & REKAM JEJAK SISWA</div>

        <!-- 1. TABEL TINGGI & BERAT BADAN -->
        <div class="section-title">1. TINGGI DAN BERAT BADAN PESERTA DIDIK</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="3" style="width: 5%;">NO.</th>
                    <th rowspan="3" style="width: 25%;">Aspek yang Dinilai</th>
                    <th colspan="2">Tahun Ajaran {{ $ta1 }}</th>
                    <th colspan="2">Tahun Ajaran {{ $ta2 }}</th>
                    <th colspan="2">Tahun Ajaran {{ $ta3 }}</th>
                </tr>
                <tr>
                    <th colspan="2">Semester</th>
                    <th colspan="2">Semester</th>
                    <th colspan="2">Semester</th>
                </tr>
                <tr>
                    <th style="width: 11%;">Ganjil (1)</th>
                    <th style="width: 11%;">Genap (2)</th>
                    <th style="width: 11%;">Ganjil (3)</th>
                    <th style="width: 11%;">Genap (4)</th>
                    <th style="width: 11%;">Ganjil (5)</th>
                    <th style="width: 11%;">Genap (6)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td style="text-align: left; font-weight: bold;">Tinggi Badan (cm)</td>
                    <td>{{ !empty($siswa['kesehatan'][1]['tinggi_badan']) ? $siswa['kesehatan'][1]['tinggi_badan'] . ' cm' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][2]['tinggi_badan']) ? $siswa['kesehatan'][2]['tinggi_badan'] . ' cm' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][3]['tinggi_badan']) ? $siswa['kesehatan'][3]['tinggi_badan'] . ' cm' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][4]['tinggi_badan']) ? $siswa['kesehatan'][4]['tinggi_badan'] . ' cm' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][5]['tinggi_badan']) ? $siswa['kesehatan'][5]['tinggi_badan'] . ' cm' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][6]['tinggi_badan']) ? $siswa['kesehatan'][6]['tinggi_badan'] . ' cm' : '-' }}</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td style="text-align: left; font-weight: bold;">Berat Badan (kg)</td>
                    <td>{{ !empty($siswa['kesehatan'][1]['berat_badan']) ? $siswa['kesehatan'][1]['berat_badan'] . ' kg' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][2]['berat_badan']) ? $siswa['kesehatan'][2]['berat_badan'] . ' kg' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][3]['berat_badan']) ? $siswa['kesehatan'][3]['berat_badan'] . ' kg' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][4]['berat_badan']) ? $siswa['kesehatan'][4]['berat_badan'] . ' kg' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][5]['berat_badan']) ? $siswa['kesehatan'][5]['berat_badan'] . ' kg' : '-' }}</td>
                    <td>{{ !empty($siswa['kesehatan'][6]['berat_badan']) ? $siswa['kesehatan'][6]['berat_badan'] . ' kg' : '-' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- 2. TABEL KONDISI KESEHATAN -->
        <div class="section-title">2. KONDISI KESEHATAN BERKALA</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">NO.</th>
                    <th rowspan="2" style="width: 25%;">Aspek Pemeriksaan</th>
                    <th>Tahun Ajaran {{ $ta1 }}</th>
                    <th>Tahun Ajaran {{ $ta2 }}</th>
                    <th>Tahun Ajaran {{ $ta3 }}</th>
                </tr>
                <tr>
                    <th>Keterangan</th>
                    <th>Keterangan</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td style="text-align: left; font-weight: bold;">Pendengaran (Telinga)</td>
                    <td>{{ $siswa['kesehatan'][2]['pendengaran'] ?? ($siswa['kesehatan'][1]['pendengaran'] ?? 'Baik / Normal') }}</td>
                    <td>{{ $siswa['kesehatan'][4]['pendengaran'] ?? ($siswa['kesehatan'][3]['pendengaran'] ?? 'Baik / Normal') }}</td>
                    <td>{{ $siswa['kesehatan'][6]['pendengaran'] ?? ($siswa['kesehatan'][5]['pendengaran'] ?? 'Baik / Normal') }}</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td style="text-align: left; font-weight: bold;">Penglihatan (Mata)</td>
                    <td>{{ $siswa['kesehatan'][2]['pengelihatan'] ?? ($siswa['kesehatan'][1]['pengelihatan'] ?? 'Baik / Normal') }}</td>
                    <td>{{ $siswa['kesehatan'][4]['pengelihatan'] ?? ($siswa['kesehatan'][3]['pengelihatan'] ?? 'Baik / Normal') }}</td>
                    <td>{{ $siswa['kesehatan'][6]['pengelihatan'] ?? ($siswa['kesehatan'][5]['pengelihatan'] ?? 'Baik / Normal') }}</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td style="text-align: left; font-weight: bold;">Kesehatan Gigi</td>
                    <td>{{ $siswa['kesehatan'][2]['gigi'] ?? ($siswa['kesehatan'][1]['gigi'] ?? 'Baik / Bersih') }}</td>
                    <td>{{ $siswa['kesehatan'][4]['gigi'] ?? ($siswa['kesehatan'][3]['gigi'] ?? 'Baik / Bersih') }}</td>
                    <td>{{ $siswa['kesehatan'][6]['gigi'] ?? ($siswa['kesehatan'][5]['gigi'] ?? 'Baik / Bersih') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- 3. TABEL PRESTASI -->
        <div class="section-title">3. REKAM JEJAK PRESTASI & KEJUARAAN</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 32%;">Nama Lomba / Kegiatan</th>
                    <th style="width: 15%;">Tingkat</th>
                    <th style="width: 18%;">Juara / Capaian</th>
                    <th style="width: 20%;">Penyelenggara</th>
                    <th style="width: 10%;">Tahun</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswa['prestasi'] ?? [] as $idx => $p)
                    @php $pArr = (array)$p; @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td style="text-align: left; font-weight: bold;">{{ $pArr['nama_lomba'] ?? '-' }}</td>
                        <td>{{ $pArr['tingkat'] ?? '-' }}</td>
                        <td style="font-weight: bold;">{{ $pArr['juara_ke'] ?? ($pArr['capaian'] ?? '-') }}</td>
                        <td style="text-align: left;">{{ $pArr['penyelenggara'] ?? '-' }}</td>
                        <td>{{ !empty($pArr['tanggal_lomba']) ? substr($pArr['tanggal_lomba'], 0, 4) : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="color: #64748b; padding: 6px;">Tidak ada catatan prestasi kejuaraan khusus.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 4. TABEL BEASISWA -->
        <div class="section-title">4. RIWAYAT PENERIMAAN BEASISWA</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 35%;">Nama Program Beasiswa</th>
                    <th style="width: 30%;">Penyelenggara / Sumber Dana</th>
                    <th style="width: 15%;">Tahun</th>
                    <th style="width: 15%;">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswa['beasiswa'] ?? [] as $bIdx => $b)
                    @php $bArr = (array)$b; @endphp
                    <tr>
                        <td>{{ $bIdx + 1 }}</td>
                        <td style="text-align: left; font-weight: bold;">{{ $bArr['nama_beasiswa'] ?? '-' }}</td>
                        <td style="text-align: left;">{{ $bArr['penyelenggara'] ?? ($bArr['sumber'] ?? '-') }}</td>
                        <td>{{ $bArr['tahun_menerima'] ?? ($bArr['tahun_mulai'] ?? '-') }}</td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ !empty($bArr['nominal']) ? 'Rp ' . number_format($bArr['nominal'], 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="color: #64748b; padding: 6px;">Tidak ada riwayat penerimaan beasiswa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 5. TABEL TRACER STUDY & KEDISIPLINAN -->
        @if(!empty($siswa['tracer_kuliah']) && count($siswa['tracer_kuliah']) > 0 || !empty($siswa['tracer_pekerjaan']) && count($siswa['tracer_pekerjaan']) > 0)
        <div class="section-title">5. REKAM JEJAK KELULUSAN & TRACER STUDY</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Kategori</th>
                    <th style="width: 45%;">Nama Institusi / Perusahaan</th>
                    <th style="width: 30%;">Program Studi / Jabatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($siswa['tracer_kuliah'] ?? [] as $tk)
                    @php $tkArr = (array)$tk; @endphp
                    <tr>
                        <td style="font-weight: bold;">Perguruan Tinggi</td>
                        <td style="text-align: left;">{{ $tkArr['nama_kampus'] ?? '-' }}</td>
                        <td style="text-align: left;">{{ $tkArr['program_studi'] ?? ($tkArr['jenjang'] ?? '-') }}</td>
                    </tr>
                @endforeach
                @foreach($siswa['tracer_pekerjaan'] ?? [] as $tp)
                    @php $tpArr = (array)$tp; @endphp
                    <tr>
                        <td style="font-weight: bold;">Dunia Kerja / Karir</td>
                        <td style="text-align: left;">{{ $tpArr['nama_perusahaan'] ?? '-' }}</td>
                        <td style="text-align: left;">{{ $tpArr['jabatan'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- TANDA TANGAN KEPALA SEKOLAH -->
        <div class="signature-section">
            <div class="signature-col-left">
                Mengetahui Orang Tua / Wali,<br><br>
                <div class="signature-space"></div>
                <strong>( {{ $namaAyah !== '......................' ? $namaAyah : ($namaWali ?: '..........................................') }} )</strong>
            </div>
            <div class="signature-col-right">
                <div class="signature-box">
                    {{ $tenant['kabupaten_kota'] ?? 'Kabupaten' }}, {{ $tanggalCetakFormatted ?? date('d F Y') }}<br>
                    Kepala Sekolah,<br><br>
                    <div class="signature-space"></div>
                    <strong style="text-decoration: underline;">{{ $kepsek['nama_kepsek'] ?? ($tenant['nama_kepsek'] ?? '..........................................') }}</strong><br>
                    NIP. {{ $kepsek['nip_kepsek'] ?? ($tenant['nip_kepsek'] ?? '..........................................') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
