<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Tailwind CSS (via CDN for print styling ease) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { size: A4 portrait; margin: 0; }
        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; background-color: white !important; }
            .no-print { display: none !important; }
        }
        body { background-color: #f3f4f6; font-family: 'Times New Roman', Times, serif; margin: 0; padding: 0; }
        .print-page { 
            background-color: white; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
            margin: 20px auto; 
            width: 210mm; 
            height: 297mm; 
            padding: 20mm; 
            box-sizing: border-box;
            position: relative;
            page-break-after: always;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
        }
        @media print {
            .print-page { box-shadow: none; margin: 0; padding: 15mm 20mm; page-break-after: always; }
        }
        .signature-img { height: 65px; width: auto; mix-blend-mode: multiply; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 2px; text-align: center; }
        .kop-surat-inner { border-bottom: 1px solid #000; margin-bottom: 20px; }
    </style>
</head>
<body class="text-black">
    <div class="no-print max-w-4xl mx-auto mb-4 mt-6 flex justify-between items-center px-4">
        <div>
            <h1 class="text-2xl font-bold font-sans">Pratinjau Cetak (A4)</h1>
            <p class="text-gray-500 font-sans">Dokumen Portofolio Pendampingan Guru</p>
        </div>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-5 py-2.5 transition-colors font-sans flex items-center gap-2">
            Cetak Dokumen
        </button>
    </div>

    <?php if (empty($laporan)): ?>
        <div class="print-page flex items-center justify-center">
            <h2 class="text-xl text-gray-500 font-sans">Belum ada data sesi pendampingan.</h2>
        </div>
    <?php else: ?>
        <?php foreach ($laporan as $index => $lap): ?>
            <div class="print-page">
                <!-- KOP Surat -->
                <div class="kop-surat">
                    <h2 class="text-xl font-bold uppercase tracking-wider mb-1">Pemerintah Daerah Provinsi</h2>
                    <h1 class="text-2xl font-bold uppercase mb-1">Dinas Pendidikan</h1>
                    <p class="text-sm">Dokumen Portofolio Resmi Pembinaan dan Pengawasan Kinerja Tenaga Pendidik</p>
                </div>
                <div class="kop-surat-inner"></div>

                <!-- Judul Dokumen -->
                <div class="text-center mb-8">
                    <h3 class="text-lg font-bold uppercase underline">Laporan Hasil Sesi Mentoring (3M)</h3>
                    <p class="text-sm mt-1">Nomor: <?= substr($lap['id'], 0, 8) ?>/SINTA/<?= date('Y', strtotime($lap['created_at'])) ?></p>
                </div>

                <!-- Konten Fleksibel -->
                <div class="flex-grow">
                    <table class="w-full mb-6 text-sm">
                        <tr><td class="py-1 w-48 align-top">Nama Pegawai / Guru</td><td class="py-1 w-4 align-top">:</td><td class="py-1 font-bold"><?= htmlspecialchars($lap['nama_guru']) ?></td></tr>
                        <tr><td class="py-1 w-48 align-top">Kategori Masalah</td><td class="py-1 w-4 align-top">:</td><td class="py-1"><?= htmlspecialchars($lap['kategori_masalah']) ?></td></tr>
                        <tr><td class="py-1 w-48 align-top">Tanggal Sesi Mentoring</td><td class="py-1 w-4 align-top">:</td><td class="py-1"><?= date('d F Y', strtotime($lap['tanggal_sesi'])) ?></td></tr>
                        <tr><td class="py-1 w-48 align-top">Sumber Laporan Kasus</td><td class="py-1 w-4 align-top">:</td><td class="py-1"><?= htmlspecialchars($lap['sumber_deteksi']) ?></td></tr>
                    </table>

                    <div class="mb-4">
                        <h4 class="font-bold text-sm mb-1">A. Deskripsi Pelanggaran / Kasus:</h4>
                        <div class="border border-gray-400 p-3 text-justify text-sm bg-gray-50 min-h-[60px]"><?= nl2br(htmlspecialchars($lap['deskripsi_kasus'])) ?></div>
                    </div>
                    <div class="mb-4">
                        <h4 class="font-bold text-sm mb-1">B. Fakta Selama Sesi Berlangsung:</h4>
                        <div class="border border-gray-400 p-3 text-justify text-sm bg-gray-50 min-h-[80px]"><?= nl2br(htmlspecialchars($lap['catatan_fakta'])) ?></div>
                    </div>
                    <div class="mb-4">
                        <h4 class="font-bold text-sm mb-1">C. Komitmen & Rencana Tindak Lanjut:</h4>
                        <div class="border border-gray-400 p-3 text-justify text-sm bg-gray-50 min-h-[80px]"><?= nl2br(htmlspecialchars($lap['rencana_tindak_lanjut'])) ?></div>
                    </div>

                    <?php if (!empty($lap['hasil_evaluasi'])): ?>
                    <div class="mb-4">
                        <h4 class="font-bold text-sm mb-1">D. Hasil Evaluasi Masa Pemantauan:</h4>
                        <table class="w-full border-collapse border border-gray-400 text-sm">
                            <tr><th class="border border-gray-400 p-2 text-left bg-gray-100 w-1/3">Keputusan Akhir</th><td class="border border-gray-400 p-2 font-bold uppercase"><?= htmlspecialchars($lap['tindakan_lanjutan']) ?></td></tr>
                            <tr><th class="border border-gray-400 p-2 text-left bg-gray-100">Catatan Evaluasi</th><td class="border border-gray-400 p-2 text-justify"><?= nl2br(htmlspecialchars($lap['catatan_perkembangan'])) ?></td></tr>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- TTD Bawah -->
                <div class="mt-8 pt-4">
                    <p class="text-right text-sm mb-6">Ditetapkan pada tanggal: <?= date('d F Y', strtotime($lap['tanggal_sesi'])) ?></p>
                    <div class="flex justify-between px-10 text-center">
                        <div class="w-1/3">
                            <p class="text-sm mb-1">Pegawai Yang Dibina,</p>
                            <div class="h-20 flex items-center justify-center my-2">
                                <?php if (!empty($lap['ttd_digital_guru'])): ?><img src="<?= $lap['ttd_digital_guru'] ?>" alt="TTD Guru" class="signature-img"><?php endif; ?>
                            </div>
                            <p class="font-bold text-sm underline"><?= htmlspecialchars($lap['nama_guru']) ?></p>
                        </div>
                        <div class="w-1/3">
                            <p class="text-sm mb-1">Mengetahui & Menyetujui,</p>
                            <p class="text-sm mb-1">Kepala Sekolah / Pengawas</p>
                            <div class="h-20 flex items-center justify-center my-2">
                                <?php if (!empty($lap['ttd_digital_kepsek'])): ?><img src="<?= $lap['ttd_digital_kepsek'] ?>" alt="TTD Kepsek" class="signature-img"><?php endif; ?>
                            </div>
                            <p class="font-bold text-sm underline">......................................</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
