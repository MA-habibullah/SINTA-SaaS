<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil Belajar Siswa - {{ $siswa->nama_lengkap }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.4; color: #111; margin: 0; padding: 20px; }
        .header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header-table td { vertical-align: middle; }
        .school-name { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
        .school-info { font-size: 10pt; color: #333; }
        .info-table { width: 100%; margin-bottom: 15px; font-size: 11pt; }
        .info-table td { padding: 3px 0; }
        .grade-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grade-table th, .grade-table td { border: 1px solid #333; padding: 6px 8px; font-size: 10.5pt; }
        .grade-table th { background-color: #f0f0f0; text-align: center; }
        .text-center { text-align: center; }
        .signature-table { width: 100%; margin-top: 40px; page-break-inside: avoid; }
        .signature-table td { text-align: center; vertical-align: top; width: 33%; font-size: 11pt; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                @if(!empty($identitas?->logo_url))
                    <img src="{{ $identitas->logo_url }}" style="max-height: 70px;">
                @endif
            </td>
            <td style="text-align: center;">
                <div class="school-name">{{ $identitas?->nama_sekolah ?? 'SEKOLAH SINTA SAAS' }}</div>
                <div class="school-info">NPSN: {{ $identitas?->npsn ?? '-' }} | Alamat: {{ $identitas?->alamat ?? '-' }}</div>
                <div class="school-info">Email: {{ $identitas?->email ?? '-' }} | Telp: {{ $identitas?->nomor_telepon ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 15px;">
        LAPORAN HASIL CAPAIAN KOMPETENSI PESERTA DIDIK
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 15%;">Nama Siswa</td>
            <td style="width: 35%;">: <strong>{{ $siswa->nama_lengkap }}</strong></td>
            <td style="width: 15%;">Kelas / Jurusan</td>
            <td style="width: 35%;">: {{ $siswa->kelas_saat_ini ?? '-' }} / {{ $siswa->jurusan ?? '-' }}</td>
        </tr>
        <tr>
            <td>NISN / NIS</td>
            <td>: {{ $siswa->nisn }} / {{ $siswa->nis }}</td>
            <td>Semester</td>
            <td>: {{ $semester }}</td>
        </tr>
    </table>

    <table class="grade-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 30%;">Mata Pelajaran</th>
                <th style="width: 12%;">Nilai Akhir</th>
                <th style="width: 53%;">Capaian Kompetensi / Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($nilaiList as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $item->mataPelajaran?->nama_mapel ?? '-' }}</strong></td>
                    <td class="text-center" style="font-weight: bold; font-size: 11pt;">{{ number_format($item->nilai_akhir, 0) }}</td>
                    <td>
                        @if($item->capaian_kompetensi_tinggi)
                            <div>Menunjukkan penguasaan sangat baik dalam {{ $item->capaian_kompetensi_tinggi }}.</div>
                        @endif
                        @if($item->capaian_kompetensi_rendah)
                            <div style="color: #444; margin-top: 3px;">Perlu pendampingan lebih lanjut dalam {{ $item->capaian_kompetensi_rendah }}.</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Belum ada data penilaian pada semester ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>Orang Tua / Wali Siswa
                <div style="height: 60px;"></div>
                ( .................................................. )
            </td>
            <td>
                Wali Kelas
                <div style="height: 60px;"></div>
                ( .................................................. )
            </td>
            <td>
                Kepala Sekolah
                <div style="height: 60px;"></div>
                <strong>{{ $identitas?->nama_kepala_sekolah ?? '-' }}</strong><br>
                NIP. {{ $identitas?->nip_kepala_sekolah ?? '-' }}
            </td>
        </tr>
    </table>
</body>
</html>
