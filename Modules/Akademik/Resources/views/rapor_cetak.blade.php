<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hasil Belajar (Rapor) - {{ isset($siswaList) && count($siswaList) > 1 ? ($namaKelas ?? 'Satu Rombel') : ($siswa->nama_lengkap ?? 'Siswa') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm 1.8cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #111;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .no-print-bar {
            background-color: #1e293b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: system-ui, -apple-system, sans-serif;
        }
        .no-print-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .no-print-btn:hover {
            background: #1d4ed8;
        }
        .page-sheet {
            background-color: #fff;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 20mm 20mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            page-break-after: always;
            break-after: page;
        }
        .page-sheet:last-child {
            page-break-after: auto;
            break-after: auto;
        }
        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #000;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .school-name {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .school-info {
            font-size: 9.5pt;
            color: #222;
            margin-top: 2px;
        }
        .report-title {
            text-align: center;
            font-weight: bold;
            font-size: 12.5pt;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 14px;
            font-size: 10.5pt;
        }
        .info-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .grade-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 20px;
        }
        .grade-table th, .grade-table td {
            border: 1px solid #111;
            padding: 5px 8px;
            font-size: 10pt;
        }
        .grade-table th {
            background-color: #f1f5f9;
            text-align: center;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 33.33%;
            font-size: 10.5pt;
        }
        @media print {
            body {
                background-color: transparent !important;
            }
            .no-print, .no-print-bar {
                display: none !important;
            }
            .page-sheet {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div style="display: flex; align-items: center; gap: 10px;">
            <strong style="font-size: 14px;">🖨️ SINTA - Pratinjau Lembar Rapor Hasil Belajar</strong>
            <span style="font-size: 12px; opacity: 0.8;">| {{ isset($siswaList) ? 'Cetak Massal (' . count($siswaList) . ' Siswa)' : ($siswa->nama_lengkap ?? 'Siswa') }}</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="no-print-btn">
                <span>Cetak / Simpan PDF</span>
            </button>
            <button onclick="window.close()" class="no-print-btn" style="background: #475569;">
                <span>Tutup</span>
            </button>
        </div>
    </div>

    @php
        $targetList = isset($siswaList) && $siswaList->isNotEmpty() ? $siswaList : (isset($siswa) ? collect([$siswa]) : collect());
    @endphp

    @forelse($targetList as $currentSiswa)
        @php
            $currentNilaiList = isset($nilaiGrouped) ? ($nilaiGrouped->get($currentSiswa->id, collect())) : (isset($nilaiList) ? $nilaiList : collect());
        @endphp
        <div class="page-sheet">
            <!-- KOP SEKOLAH -->
            <table class="header-table">
                <tr>
                    <td style="width: 75px; text-align: center;">
                        @if(!empty($identitas?->logo_url))
                            <img src="{{ $identitas->logo_url }}" style="max-height: 65px; max-width: 70px;">
                        @else
                            <div style="width: 55px; height: 55px; border: 1.5px dashed #666; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold; border-radius: 6px;">LOGO</div>
                        @endif
                    </td>
                    <td style="text-align: center; padding-left: 10px;">
                        <div class="school-name">{{ $identitas?->nama_sekolah ?? 'SEKOLAH SINTA' }}</div>
                        <div class="school-info">NPSN: {{ $identitas?->npsn ?? '-' }} | NSS/NDS: {{ $identitas?->nss ?? '-' }} | Akreditasi: {{ $identitas?->akreditasi ?? 'A' }}</div>
                        <div class="school-info">Alamat: {{ $identitas?->alamat ?? 'Alamat Sekolah' }} {{ $identitas?->kabupaten_kota ? ', ' . $identitas->kabupaten_kota : '' }}</div>
                        <div class="school-info">Email: {{ $identitas?->email ?? '-' }} | Telp: {{ $identitas?->nomor_telepon ?? '-' }} | Website: {{ $identitas?->website ?? '-' }}</div>
                    </td>
                </tr>
            </table>

            <div class="report-title">
                LAPORAN HASIL CAPAIAN KOMPETENSI PESERTA DIDIK
            </div>

            <!-- IDENTITAS SISWA SINGKAT -->
            <table class="info-table">
                <tr>
                    <td style="width: 16%;">Nama Peserta Didik</td>
                    <td style="width: 44%;">: <strong>{{ $currentSiswa->nama_lengkap }}</strong></td>
                    <td style="width: 16%;">Kelas / Rombel</td>
                    <td style="width: 24%;">: {{ $currentSiswa->kelas_saat_ini ?? ($namaKelas ?? '-') }}</td>
                </tr>
                <tr>
                    <td>NISN / NIS</td>
                    <td>: {{ $currentSiswa->nisn ?? '-' }} / {{ $currentSiswa->nis ?? '-' }}</td>
                    <td>Fase / Semester</td>
                    <td>: {{ $semester ?? 'Ganjil' }}</td>
                </tr>
                <tr>
                    <td>Nama Sekolah</td>
                    <td>: {{ $identitas?->nama_sekolah ?? 'SINTA School' }}</td>
                    <td>Tahun Pelajaran</td>
                    <td>: {{ $tahunAjaran ?? ($currentSiswa->tahun_masuk ?? date('Y') . '/' . (date('Y') + 1)) }}</td>
                </tr>
            </table>

            <!-- TABEL NILAI CAPAIAN BELAJAR -->
            <table class="grade-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 28%;">Mata Pelajaran</th>
                        <th style="width: 12%;">Nilai Akhir</th>
                        <th style="width: 55%;">Capaian Kompetensi / Deskripsi Pembelajaran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($currentNilaiList as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td><strong>{{ $item->mataPelajaran?->nama_mata_pelajaran ?? ($item->mataPelajaran?->nama_mapel ?? 'Mata Pelajaran') }}</strong></td>
                            <td class="text-center" style="font-weight: bold; font-size: 11pt;">{{ number_format($item->nilai_akhir, 0) }}</td>
                            <td>
                                @if(!empty($item->capaian_kompetensi_tinggi))
                                    <div><strong>Tercapai Baik:</strong> Menunjukkan penguasaan optimal dalam {{ $item->capaian_kompetensi_tinggi }}.</div>
                                @endif
                                @if(!empty($item->capaian_kompetensi_rendah))
                                    <div style="margin-top: 3px; color: #333;"><strong>Perlu Peningkatan:</strong> Perlu pendampingan lebih lanjut dalam {{ $item->capaian_kompetensi_rendah }}.</div>
                                @endif
                                @if(empty($item->capaian_kompetensi_tinggi) && empty($item->capaian_kompetensi_rendah))
                                    <div>{{ $item->deskripsi_nilai ?? 'Menunjukkan penguasaan kompetensi materi yang baik sesuai kriteria capaian pembelajaran.' }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center" style="padding: 20px; font-style: italic; color: #666;">Belum ada catatan nilai yang terinput pada semester ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- TANDA TANGAN RESMI -->
            <table class="signature-table">
                <tr>
                    <td>
                        Mengetahui,<br>Orang Tua / Wali Peserta Didik
                        <div style="height: 65px;"></div>
                        ( ..................................................... )
                    </td>
                    <td>
                        {{ $tempat ?? ($identitas?->kabupaten_kota ?? 'Jakarta') }}, {{ $tanggal ?? date('d F Y') }}<br>Wali Kelas
                        <div style="height: 65px;"></div>
                        <strong>{{ $waliKelas ?? 'Wali Kelas' }}</strong><br>
                        NIP. ........................................
                    </td>
                    <td>
                        Mengetahui,<br>Kepala Sekolah
                        <div style="height: 65px;"></div>
                        <strong>{{ $identitas?->nama_kepala_sekolah ?? 'Kepala Sekolah' }}</strong><br>
                        NIP. {{ $identitas?->nip_kepala_sekolah ?? '-' }}
                    </td>
                </tr>
            </table>
        </div>
    @empty
        <div style="text-align: center; padding: 50px; font-family: sans-serif;">
            <h3>Data siswa tidak ditemukan untuk dicetak.</h3>
        </div>
    @endforelse

</body>
</html>

