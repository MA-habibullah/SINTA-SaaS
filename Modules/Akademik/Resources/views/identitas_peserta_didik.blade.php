<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Identitas Peserta Didik - {{ isset($siswaList) && count($siswaList) > 1 ? 'Cetak Massal Kelas' : ($siswa->nama_lengkap ?? 'Siswa') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.8cm 2.0cm;
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
            padding: 10px 0;
        }
        .print-wrapper:last-child {
            page-break-after: auto;
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
            margin-bottom: 0.5cm;
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
        .photo-box img {
            width: 3cm;
            height: 4cm;
            object-fit: cover;
            display: block;
        }
        .photo-box .placeholder-text {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 9pt;
            font-weight: bold;
            color: #777;
            text-align: center;
            line-height: 1.4;
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
            .no-print, .print-btn-container, .btn-print, header, footer, nav, sidebar {
                display: none !important;
            }
        }
        .print-btn-container {
            padding: 12px;
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .btn-print {
            padding: 8px 22px;
            background-color: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: sans-serif;
            font-size: 10pt;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }
        .watermark-footer {
            position: fixed;
            bottom: 0.4cm;
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
        <button class="btn-print" onclick="window.print()">🖨️ Cetak Dokumen (Print / PDF)</button>
    </div>

    @php
        $studentsToPrint = isset($siswaList) && count($siswaList) > 0 ? $siswaList : [$siswa];
    @endphp

    @foreach($studentsToPrint as $st)
        @php
            $namaLengkap = $st->nama_lengkap ?? '-';
            
            // NIS & NISN
            $nis = !empty($st->nis) ? trim($st->nis) : '';
            $nisn = !empty($st->nisn) ? trim($st->nisn) : '';
            if ($nis !== '' && $nisn !== '') {
                $nomorIndukNisn = $nis . ' / ' . $nisn;
            } elseif ($nis !== '') {
                $nomorIndukNisn = $nis;
            } elseif ($nisn !== '') {
                $nomorIndukNisn = $nisn;
            } else {
                $nomorIndukNisn = '-';
            }

            // Tempat, Tanggal Lahir
            $tempatLahir = !empty($st->tempat_lahir) ? trim($st->tempat_lahir) : '';
            $tanggalLahirStr = '';
            if (!empty($st->tanggal_lahir)) {
                try {
                    $d = new DateTime($st->tanggal_lahir);
                    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $tanggalLahirStr = $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
                } catch (\Throwable $e) {
                    $tanggalLahirStr = (string)$st->tanggal_lahir;
                }
            }
            $tempatTanggalLahir = '-';
            if ($tempatLahir !== '' && $tanggalLahirStr !== '') {
                $tempatTanggalLahir = $tempatLahir . ', ' . $tanggalLahirStr;
            } elseif ($tempatLahir !== '') {
                $tempatTanggalLahir = $tempatLahir;
            } elseif ($tanggalLahirStr !== '') {
                $tempatTanggalLahir = $tanggalLahirStr;
            }

            // Jenis Kelamin
            $jenisKelamin = ($st->jenis_kelamin === 'L' || strcasecmp($st->jenis_kelamin, 'Laki-laki') === 0) ? 'Laki-laki' : 'Perempuan';

            // Agama & Status Anak
            $agama = $st->agama ?: '-';
            $statusDalamKeluarga = $st->status_anak ?: 'Anak Kandung';
            $anakKe = $st->anak_ke ?: '1';

            // Alamat
            $alamatFull = $st->alamat ?? $st->alamat_domisili ?? $st->alamat_tinggal ?? '-';
            if (!empty($st->rt) || !empty($st->rw)) {
                $alamatFull .= ' RT. ' . ($st->rt ?: '00') . ' RW. ' . ($st->rw ?: '00');
            }

            // No Telp
            $noTelpSiswa = $st->no_hp ?? $st->no_telepon_rumah ?? '-';

            // Sekolah Asal
            $sekolahAsal = $st->sekolah_asal ?? '-';

            // Diterima di kelas
            $diterimaDiKelas = $st->kelas_saat_ini ?? '-';
            $diterimaTanggal = $st->created_at ? $st->created_at->translatedFormat('d F Y') : ($tanggal ?? date('d F Y'));

            // Orang Tua
            $namaAyah = $st->nama_ayah ?? '-';
            $namaIbu = $st->nama_ibu ?? '-';
            $pekerjaanAyah = $st->pekerjaan_ayah ?? '-';
            $pekerjaanIbu = $st->pekerjaan_ibu ?? '-';
            $alamatOrangTua = $st->alamat_orang_tua ?? $alamatFull;
            $noTelpOrangTua = $st->no_telepon_orang_tua ?? $st->no_hp ?? '-';

            // Cek data dari relasi orangTua jika ada
            if ($st->orangTua && $st->orangTua->count() > 0) {
                foreach ($st->orangTua as $ot) {
                    if (strcasecmp($ot->hubungan, 'Ayah') === 0 || strcasecmp($ot->hubungan, 'ayah') === 0) {
                        $namaAyah = $ot->nama ?: $namaAyah;
                        $pekerjaanAyah = $ot->pekerjaan ?: $pekerjaanAyah;
                    } elseif (strcasecmp($ot->hubungan, 'Ibu') === 0 || strcasecmp($ot->hubungan, 'ibu') === 0) {
                        $namaIbu = $ot->nama ?: $namaIbu;
                        $pekerjaanIbu = $ot->pekerjaan ?: $pekerjaanIbu;
                    }
                }
            }

            // Wali
            $hasWali = !empty($st->nama_wali) && $st->nama_wali !== '-';
            $namaWali = $hasWali ? $st->nama_wali : '-';
            $alamatWali = $hasWali ? ($st->alamat_wali ?: $alamatFull) : '-';
            $noTelpWali = $hasWali ? ($st->no_telepon_wali ?: '-') : '-';
            $pekerjaanWali = $hasWali ? ($st->pekerjaan_wali ?: '-') : '-';
        @endphp

        <div class="print-wrapper">
            <div class="header">
                IDENTITAS PESERTA DIDIK
            </div>

            <table>
                <tr>
                    <td class="col-num">1.</td>
                    <td class="col-label">Nama Lengkap Peserta Didik</td>
                    <td class="col-colon">:</td>
                    <td class="col-val bold">{{ $namaLengkap }}</td>
                </tr>
                <tr>
                    <td class="col-num">2.</td>
                    <td class="col-label">Nomor Induk / NISN</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $nomorIndukNisn }}</td>
                </tr>
                <tr>
                    <td class="col-num">3.</td>
                    <td class="col-label">Tempat, Tanggal Lahir</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $tempatTanggalLahir }}</td>
                </tr>
                <tr>
                    <td class="col-num">4.</td>
                    <td class="col-label">Jenis Kelamin</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $jenisKelamin }}</td>
                </tr>
                <tr>
                    <td class="col-num">5.</td>
                    <td class="col-label">Agama</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $agama }}</td>
                </tr>
                <tr>
                    <td class="col-num">6.</td>
                    <td class="col-label">Status dalam Keluarga</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $statusDalamKeluarga }}</td>
                </tr>
                <tr>
                    <td class="col-num">7.</td>
                    <td class="col-label">Anak ke</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $anakKe }}</td>
                </tr>
                <tr>
                    <td class="col-num">8.</td>
                    <td class="col-label">Alamat Peserta Didik</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $alamatFull }}</td>
                </tr>
                <tr>
                    <td class="col-num">9.</td>
                    <td class="col-label">Nomor Telepon Rumah / HP</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $noTelpSiswa }}</td>
                </tr>
                <tr>
                    <td class="col-num">10.</td>
                    <td class="col-label">Sekolah Asal</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $sekolahAsal }}</td>
                </tr>
                <tr>
                    <td class="col-num">11.</td>
                    <td class="col-label">Diterima di sekolah ini</td>
                    <td class="col-colon">:</td>
                    <td class="col-val"></td>
                </tr>
                <tr>
                    <td class="col-num"></td>
                    <td class="col-label sub-row">a. Di kelas</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $diterimaDiKelas }}</td>
                </tr>
                <tr>
                    <td class="col-num"></td>
                    <td class="col-label sub-row">b. Pada tanggal</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $diterimaTanggal }}</td>
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
                    <td class="col-val">{{ $namaAyah }}</td>
                </tr>
                <tr>
                    <td class="col-num"></td>
                    <td class="col-label sub-row">b. Ibu</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $namaIbu }}</td>
                </tr>
                <tr>
                    <td class="col-num">13.</td>
                    <td class="col-label">Alamat Orang Tua</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $alamatOrangTua }}</td>
                </tr>
                <tr>
                    <td class="col-num">14.</td>
                    <td class="col-label">Nomor Telepon Rumah Orang Tua</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $noTelpOrangTua }}</td>
                </tr>
                <tr>
                    <td class="col-num">15.</td>
                    <td class="col-label">Pekerjaan Orang Tua</td>
                    <td class="col-colon"></td>
                    <td class="col-val"></td>
                </tr>
                <tr>
                    <td class="col-num"></td>
                    <td class="col-label sub-row">a. Ayah</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $pekerjaanAyah }}</td>
                </tr>
                <tr>
                    <td class="col-num"></td>
                    <td class="col-label sub-row">b. Ibu</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $pekerjaanIbu }}</td>
                </tr>
                <tr>
                    <td class="col-num">16.</td>
                    <td class="col-label">Nama Wali Peserta Didik</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $namaWali }}</td>
                </tr>
                <tr>
                    <td class="col-num">17.</td>
                    <td class="col-label">Alamat Wali Peserta Didik</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $alamatWali }}</td>
                </tr>
                <tr>
                    <td class="col-num">18.</td>
                    <td class="col-label">Nomor Telepon Rumah Wali</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $noTelpWali }}</td>
                </tr>
                <tr>
                    <td class="col-num">19.</td>
                    <td class="col-label">Pekerjaan Wali Peserta Didik</td>
                    <td class="col-colon">:</td>
                    <td class="col-val">{{ $pekerjaanWali }}</td>
                </tr>
            </table>

            <div class="footer-section">
                <div class="photo-cell">
                    <div class="photo-box">
                        @if(!empty($st->foto_url))
                            <img src="{{ $st->foto_url }}" alt="Foto 3x4">
                        @else
                            <div class="placeholder-text">FOTO<br>3 x 4</div>
                        @endif
                    </div>
                </div>

                <div class="signature-right">
                    <div class="signature-container">
                        <div>{{ $tempat ?? 'Jakarta' }}, {{ $tanggal ?? date('d F Y') }}</div>
                        <div>Kepala Sekolah,</div>
                        <div class="signature-space"></div>
                        <div class="bold underline">{{ $identitas->nama_kepala_sekolah ?? $identitas->nama_sekolah ?? 'Kepala Sekolah' }}</div>
                        <div>NIP. {{ $identitas->nip_kepala_sekolah ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="watermark-footer no-print">
        Dokumen Resmi Identitas Peserta Didik SINTA SaaS • Dicetak pada {{ date('d-m-Y H:i:s') }}
    </div>
</body>
</html>
