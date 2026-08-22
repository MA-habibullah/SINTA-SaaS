<?php

/**
 * Migration PostgreSQL: Upgrade Schema Persuratan, Pengajuan Panggilan BK, Template Naskah Dinas, dan Registrasi Menu Tata Usaha
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== UPGRADING SCHEMA PERSURATAN & TATA USAHA (PostgreSQL Multi-Schema) ===\n";

        // 1. Pastikan Schema persuratan tersedia
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS persuratan;");

        // 2. Upgrade / Perbaiki 9 Tabel Skema persuratan
        $pdo->exec("
            -- Table 1: Kode Klasifikasi Surat (Standar Baku Kemendikbud/Kemenag)
            CREATE TABLE IF NOT EXISTS persuratan.kode_klasifikasi_surat (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                kode_klasifikasi VARCHAR(50) NOT NULL,
                nama_klasifikasi TEXT NOT NULL,
                nama_kode_klasifikasi_surat TEXT NULL,
                parent_kode VARCHAR(50) NULL,
                level_klasifikasi INT DEFAULT 1,
                kategori_utama VARCHAR(100) NULL,
                kategori VARCHAR(100) DEFAULT 'Umum',
                deskripsi TEXT NULL,
                retensi_aktif_tahun INT DEFAULT 5,
                retensi_inaktif_tahun INT DEFAULT 5,
                retensi_tahun INT DEFAULT 5,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS kode_klasifikasi VARCHAR(50) NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS nama_klasifikasi TEXT NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS nama_kode_klasifikasi_surat TEXT NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ALTER COLUMN nama_klasifikasi TYPE TEXT;
            ALTER TABLE persuratan.kode_klasifikasi_surat ALTER COLUMN nama_kode_klasifikasi_surat TYPE TEXT;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS parent_kode VARCHAR(50) NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS level_klasifikasi INT DEFAULT 1;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS kategori_utama VARCHAR(100) NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_aktif_tahun INT DEFAULT 5;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_inaktif_tahun INT DEFAULT 5;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_tahun INT DEFAULT 5;

            -- Table 2: Kop Surat Sekolah
            CREATE TABLE IF NOT EXISTS persuratan.kop_surat (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_instansi_atas VARCHAR(255) NULL,
                nama_sekolah VARCHAR(255) NULL,
                npsn VARCHAR(50) NULL,
                akreditasi VARCHAR(20) NULL,
                alamat TEXT NULL,
                kelurahan VARCHAR(100) NULL,
                kecamatan VARCHAR(100) NULL,
                kota_kabupaten VARCHAR(100) NULL,
                provinsi VARCHAR(100) NULL,
                kode_pos VARCHAR(20) NULL,
                telepon VARCHAR(50) NULL,
                email VARCHAR(100) NULL,
                website VARCHAR(100) NULL,
                logo_kiri VARCHAR(512) NULL,
                logo_kanan VARCHAR(512) NULL,
                garis_kop BOOLEAN DEFAULT TRUE,
                is_default BOOLEAN DEFAULT TRUE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS nama_instansi_atas VARCHAR(255) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS nama_sekolah VARCHAR(255) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS npsn VARCHAR(50) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS akreditasi VARCHAR(20) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS alamat TEXT NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS telepon VARCHAR(50) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS website VARCHAR(100) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS logo_kiri VARCHAR(512) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS logo_kanan VARCHAR(512) NULL;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS garis_kop BOOLEAN DEFAULT TRUE;
            ALTER TABLE persuratan.kop_surat ADD COLUMN IF NOT EXISTS is_default BOOLEAN DEFAULT TRUE;

            -- Table 3: Jenis Surat Dinas
            CREATE TABLE IF NOT EXISTS persuratan.jenis_surat (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                kode_jenis VARCHAR(50) NULL,
                nama_jenis_surat VARCHAR(255) NOT NULL,
                kategori VARCHAR(100) DEFAULT 'Keluar',
                format_penomoran VARCHAR(255) DEFAULT '{nomor}/{kode_klasifikasi}/{tenant_code}/{bulan_romawi}/{tahun}',
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE persuratan.jenis_surat ADD COLUMN IF NOT EXISTS kode_jenis VARCHAR(50) NULL;
            ALTER TABLE persuratan.jenis_surat ADD COLUMN IF NOT EXISTS nama_jenis_surat VARCHAR(255) NULL;
            ALTER TABLE persuratan.jenis_surat ADD COLUMN IF NOT EXISTS format_penomoran VARCHAR(255) NULL;

            -- Table 4: Template Naskah Dinas
            CREATE TABLE IF NOT EXISTS persuratan.template_surat (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                id_jenis_surat UUID NULL,
                nama_template_surat VARCHAR(255) NOT NULL,
                kode_template VARCHAR(100) NULL,
                judul_surat VARCHAR(255) NULL,
                perihal_default VARCHAR(255) NULL,
                konten_html TEXT NULL,
                variabel_tersedia JSONB NULL,
                kategori VARCHAR(100) DEFAULT 'Umum',
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE persuratan.template_surat ADD COLUMN IF NOT EXISTS id_jenis_surat UUID NULL;
            ALTER TABLE persuratan.template_surat ADD COLUMN IF NOT EXISTS kode_template VARCHAR(100) NULL;
            ALTER TABLE persuratan.template_surat ADD COLUMN IF NOT EXISTS judul_surat VARCHAR(255) NULL;
            ALTER TABLE persuratan.template_surat ADD COLUMN IF NOT EXISTS perihal_default VARCHAR(255) NULL;
            ALTER TABLE persuratan.template_surat ADD COLUMN IF NOT EXISTS konten_html TEXT NULL;
            ALTER TABLE persuratan.template_surat ADD COLUMN IF NOT EXISTS variabel_tersedia JSONB NULL;

            -- Table 5: Surat Masuk
            CREATE TABLE IF NOT EXISTS persuratan.surat_masuk (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                no_agenda VARCHAR(50) NULL,
                no_surat VARCHAR(255) NOT NULL,
                pengirim VARCHAR(255) NOT NULL,
                perihal VARCHAR(500) NOT NULL,
                tgl_surat DATE NULL,
                tgl_terima DATE NULL,
                ringkasan_isi TEXT NULL,
                file_lampiran VARCHAR(512) NULL,
                status_disposisi VARCHAR(50) DEFAULT 'Menunggu Disposisi',
                tingkat_keamanan VARCHAR(50) DEFAULT 'Biasa',
                sifat_surat VARCHAR(50) DEFAULT 'Biasa',
                kategori VARCHAR(100) DEFAULT 'Umum',
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS no_agenda VARCHAR(50) NULL;
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS no_surat VARCHAR(255) NULL;
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS pengirim VARCHAR(255) NULL;
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS perihal VARCHAR(500) NULL;
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS tgl_surat DATE NULL;
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS tgl_terima DATE NULL;
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS ringkasan_isi TEXT NULL;
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS file_lampiran VARCHAR(512) NULL;
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS status_disposisi VARCHAR(50) DEFAULT 'Menunggu Disposisi';
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS tingkat_keamanan VARCHAR(50) DEFAULT 'Biasa';
            ALTER TABLE persuratan.surat_masuk ADD COLUMN IF NOT EXISTS sifat_surat VARCHAR(50) DEFAULT 'Biasa';

            -- Table 6: Lembar Disposisi Surat
            CREATE TABLE IF NOT EXISTS persuratan.disposisi_surat (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                id_surat_masuk UUID NULL,
                pemberi_disposisi_id UUID NULL,
                nama_pemberi_disposisi VARCHAR(255) NULL,
                penerima_disposisi_id UUID NULL,
                nama_penerima_disposisi VARCHAR(255) NULL,
                instruksi_disposisi TEXT NULL,
                catatan TEXT NULL,
                tgl_disposisi DATE DEFAULT CURRENT_DATE,
                batas_waktu DATE NULL,
                status VARCHAR(50) DEFAULT 'Pending',
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS id_surat_masuk UUID NULL;
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS pemberi_disposisi_id UUID NULL;
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS nama_pemberi_disposisi VARCHAR(255) NULL;
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS penerima_disposisi_id UUID NULL;
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS nama_penerima_disposisi VARCHAR(255) NULL;
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS instruksi_disposisi TEXT NULL;
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS tgl_disposisi DATE NULL;
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS batas_waktu DATE NULL;
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'Pending';

            -- Table 7: Register Surat Keluar TU
            CREATE TABLE IF NOT EXISTS persuratan.surat_keluar (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                no_agenda VARCHAR(50) NULL,
                nomor_surat VARCHAR(255) NOT NULL,
                id_kode_klasifikasi UUID NULL,
                id_jenis_surat UUID NULL,
                id_template UUID NULL,
                tujuan VARCHAR(255) NOT NULL,
                perihal VARCHAR(500) NOT NULL,
                tgl_surat DATE DEFAULT CURRENT_DATE,
                ringkasan_isi TEXT NULL,
                id_pembuat UUID NULL,
                nama_pembuat VARCHAR(255) NULL,
                id_penandatangan UUID NULL,
                nama_penandatangan VARCHAR(255) NULL,
                jabatan_penandatangan VARCHAR(255) DEFAULT 'Kepala Sekolah',
                status_surat VARCHAR(50) DEFAULT 'Diterbitkan',
                id_referensi_modul VARCHAR(100) NULL,
                nama_modul_referensi VARCHAR(100) NULL,
                file_lampiran VARCHAR(512) NULL,
                file_final_pdf VARCHAR(512) NULL,
                qr_token VARCHAR(128) NULL,
                kategori VARCHAR(100) DEFAULT 'Keluar',
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS no_agenda VARCHAR(50) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS nomor_surat VARCHAR(255) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS id_kode_klasifikasi UUID NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS id_jenis_surat UUID NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS id_template UUID NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS tujuan VARCHAR(255) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS perihal VARCHAR(500) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS tgl_surat DATE NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS ringkasan_isi TEXT NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS id_pembuat UUID NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS nama_pembuat VARCHAR(255) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS id_penandatangan UUID NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS nama_penandatangan VARCHAR(255) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS jabatan_penandatangan VARCHAR(255) DEFAULT 'Kepala Sekolah';
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS status_surat VARCHAR(50) DEFAULT 'Diterbitkan';
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS id_referensi_modul VARCHAR(100) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS nama_modul_referensi VARCHAR(100) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS file_lampiran VARCHAR(512) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS file_final_pdf VARCHAR(512) NULL;
            ALTER TABLE persuratan.surat_keluar ADD COLUMN IF NOT EXISTS qr_token VARCHAR(128) NULL;

            -- Table 8: Notifikasi / Pengajuan Surat Pemanggilan dari BK ke TU
            CREATE TABLE IF NOT EXISTS persuratan.pengajuan_surat_bk (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                id_siswa UUID NULL,
                nama_siswa VARCHAR(255) NOT NULL,
                nisn VARCHAR(50) NULL,
                kelas VARCHAR(100) NULL,
                total_poin INT DEFAULT 0,
                jenis_panggilan VARCHAR(100) NOT NULL,
                alasan_pemanggilan TEXT NOT NULL,
                rencana_tanggal_menghadap DATE NULL,
                rencana_jam_menghadap VARCHAR(30) NULL,
                ruangan VARCHAR(100) DEFAULT 'Ruang BK',
                guru_bk_pengaju VARCHAR(255) NULL,
                id_guru_bk UUID NULL,
                status_pengajuan VARCHAR(50) DEFAULT 'Menunggu Penerbitan TU',
                id_surat_keluar UUID NULL,
                nomor_surat_terbit VARCHAR(255) NULL,
                tgl_terbit_surat TIMESTAMP WITH TIME ZONE NULL,
                catatan_tu TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            -- Table 9: Verifikasi TTE QR Dokumen
            CREATE TABLE IF NOT EXISTS persuratan.tte_qr_validation (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                id_surat_keluar UUID NULL,
                qr_token VARCHAR(128) UNIQUE NULL,
                hash_dokumen VARCHAR(255) NULL,
                penandatangan VARCHAR(255) NULL,
                tgl_tanda_tangan TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                is_valid BOOLEAN NOT NULL DEFAULT TRUE,
                total_verifikasi INT DEFAULT 0,
                last_verified_at TIMESTAMP WITH TIME ZONE NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE persuratan.tte_qr_validation ADD COLUMN IF NOT EXISTS id_surat_keluar UUID NULL;
            ALTER TABLE persuratan.tte_qr_validation ADD COLUMN IF NOT EXISTS qr_token VARCHAR(128) NULL;
            ALTER TABLE persuratan.tte_qr_validation ADD COLUMN IF NOT EXISTS hash_dokumen VARCHAR(255) NULL;
            ALTER TABLE persuratan.tte_qr_validation ADD COLUMN IF NOT EXISTS penandatangan VARCHAR(255) NULL;
            ALTER TABLE persuratan.tte_qr_validation ADD COLUMN IF NOT EXISTS total_verifikasi INT DEFAULT 0;
            ALTER TABLE persuratan.tte_qr_validation ADD COLUMN IF NOT EXISTS last_verified_at TIMESTAMP WITH TIME ZONE NULL;
            ALTER TABLE persuratan.tte_qr_validation ADD COLUMN IF NOT EXISTS is_valid BOOLEAN DEFAULT TRUE;
            ALTER TABLE persuratan.tte_qr_validation ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

            -- Table 10: Relasi pada bk.pembinaan_monitoring
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS id_pengajuan_surat UUID NULL;
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS id_surat_keluar UUID NULL;
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS nomor_surat_resmi VARCHAR(100) NULL;
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS status_surat VARCHAR(50) DEFAULT 'Belum Ada';
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS tanggal_menghadap TIMESTAMP WITH TIME ZONE NULL;
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS ruangan_menghadap VARCHAR(100) NULL;
        ");

        // 3. Seed Default Master Data Persuratan
        echo "- Seeding Master Klasifikasi & Template Baku Persuratan...\n";

        // 3a. Master Kode Klasifikasi Baku Pendidikan
        $klasifikasiData = [
            ['421.1', 'Pendidikan Dasar & Menengah / Kurikulum', 'Kurikulum, silabus, KBM, dan pembelajaran'],
            ['421.2', 'Kesiswaan & Ekstrakurikuler', 'Tata tertib siswa, prestasi, beasiswa, dan ekskul'],
            ['421.3', 'Bimbingan Konseling & Kedisiplinan', 'Pembinaan siswa, pemanggilan orang tua, surat peringatan'],
            ['421.4', 'Ketenagaan & Kepegawaian Sekolah', 'Penugasan guru, surat tugas dinas, cuti, mutasi staf'],
            ['421.5', 'Sarana, Prasarana & Keuangan', 'Pengadaan, inventaris barang, BOS, dan komite'],
            ['005', 'Undangan & Rapat Dinas', 'Undangan rapat dinas, pertemuan komite, dan koordinasi'],
            ['800', 'Surat Tugas & Perjalanan Dinas', 'Surat tugas dinas luar, diklat guru, dan pengawas'],
        ];

        $stmtKlas = $pdo->prepare("
            INSERT INTO persuratan.kode_klasifikasi_surat (
                id, tenant_id, kode_klasifikasi, nama_klasifikasi, deskripsi, retensi_tahun, is_active
            ) VALUES (
                gen_random_uuid(), 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12', ?, ?, ?, 5, TRUE
            )
            ON CONFLICT DO NOTHING
        ");
        foreach ($klasifikasiData as $k) {
            $stmtKlas->execute([$k[0], $k[1], $k[2]]);
        }

        // 3b. Master Jenis Surat
        $jenisSuratData = [
            ['SP-ORTU', 'Surat Panggilan Orang Tua (BK)', 'Keluar', '{nomor}/421.3/{tenant_code}/{bulan_romawi}/{tahun}'],
            ['SP-SISWA', 'Surat Peringatan Siswa (SP 1/2/3)', 'Keluar', '{nomor}/421.3/{tenant_code}/{bulan_romawi}/{tahun}'],
            ['SK-AKTIF', 'Surat Keterangan Siswa Aktif', 'Keluar', '{nomor}/421.2/{tenant_code}/{bulan_romawi}/{tahun}'],
            ['ST-GURU', 'Surat Tugas Guru / Tenaga Kependidikan', 'Keluar', '{nomor}/800/{tenant_code}/{bulan_romawi}/{tahun}'],
            ['SU-RAPAT', 'Surat Undangan Rapat / Pertemuan', 'Keluar', '{nomor}/005/{tenant_code}/{bulan_romawi}/{tahun}'],
            ['SM-MUTASI', 'Surat Keterangan Pindah / Mutasi Siswa', 'Keluar', '{nomor}/421.2/{tenant_code}/{bulan_romawi}/{tahun}'],
        ];

        $stmtJenis = $pdo->prepare("
            INSERT INTO persuratan.jenis_surat (
                id, tenant_id, kode_jenis, nama_jenis_surat, kategori, format_penomoran, is_active
            ) VALUES (
                gen_random_uuid(), 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12', ?, ?, ?, ?, TRUE
            )
            ON CONFLICT DO NOTHING
        ");
        foreach ($jenisSuratData as $js) {
            $stmtJenis->execute([$js[0], $js[1], $js[2], $js[3]]);
        }

        // 3c. Master Template Surat
        $templateData = [
            [
                'SP-ORTU-01',
                'Surat Pemanggilan Orang Tua / Wali Siswa',
                'SURAT PEMANGGILAN ORANG TUA / WALI',
                'Pemanggilan Orang Tua / Wali Siswa',
                '<p>Dengan hormat,</p><p>Sehubungan dengan perkembangan pembinaan kedisiplinan putra/putri Bapak/Ibu:</p><table style=\"width: 100%; margin: 10px 0;\"><tr><td style=\"width: 180px;\"><strong>Nama Siswa</strong></td><td>: {nama_siswa}</td></tr><tr><td><strong>NISN / NIS</strong></td><td>: {nisn} / {nis}</td></tr><tr><td><strong>Kelas</strong></td><td>: {kelas}</td></tr><tr><td><strong>Akumulasi Poin Pelanggaran</strong></td><td>: <span style=\"color: #dc2626; font-weight: bold;\">{total_poin} Poin</span></td></tr><tr><td><strong>Keterangan Kasus</strong></td><td>: {alasan_pemanggilan}</td></tr></table><p>Maka dengan ini kami mengharap kehadiran Bapak/Ibu Orang Tua/Wali pada:</p><table style=\"width: 100%; margin: 10px 0;\"><tr><td style=\"width: 180px;\"><strong>Hari, Tanggal</strong></td><td>: {tanggal_menghadap}</td></tr><tr><td><strong>Pukul / Waktu</strong></td><td>: {jam_menghadap} WIB</td></tr><tr><td><strong>Tempat</strong></td><td>: {ruangan}</td></tr><tr><td><strong>Menghadap Kepada</strong></td><td>: Guru BK / Wali Kelas ({nama_guru_bk})</td></tr></table><p>Mengingat pentingnya koordinasi ini demi masa depan belajar putra/putri Bapak/Ibu, kami sangat mengharapkan kehadiran tepat pada waktunya.</p><p>Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.</p>',
                json_encode(['nama_siswa', 'nisn', 'nis', 'kelas', 'total_poin', 'alasan_pemanggilan', 'tanggal_menghadap', 'jam_menghadap', 'ruangan', 'nama_guru_bk', 'nama_kepala_sekolah', 'nomor_surat', 'tanggal_surat', 'nama_sekolah'])
            ],
            [
                'SK-AKTIF-01',
                'Surat Keterangan Siswa Aktif Belajar',
                'SURAT KETERANGAN SISWA AKTIF',
                'Keterangan Siswa Aktif Belajar',
                '<p>Yang bertanda tangan di bawah ini, Kepala {nama_sekolah}, dengan ini menerangkan bahwa:</p><table style=\"width: 100%; margin: 10px 0;\"><tr><td style=\"width: 180px;\"><strong>Nama Lengkap</strong></td><td>: {nama_siswa}</td></tr><tr><td><strong>NISN / NIS</strong></td><td>: {nisn} / {nis}</td></tr><tr><td><strong>Tempat, Tgl Lahir</strong></td><td>: {tempat_tanggal_lahir}</td></tr><tr><td><strong>Kelas / Tingkat</strong></td><td>: {kelas}</td></tr><tr><td><strong>Tahun Ajaran</strong></td><td>: {tahun_ajaran}</td></tr></table><p>Adalah benar-benar peserta didik yang tercatat aktif mengikuti kegiatan belajar mengajar pada semester ini di {nama_sekolah}.</p><p>Demikian surat keterangan ini kami terbitkan dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>',
                json_encode(['nama_siswa', 'nisn', 'nis', 'tempat_tanggal_lahir', 'kelas', 'tahun_ajaran', 'nama_sekolah', 'nama_kepala_sekolah', 'nip_kepala_sekolah', 'nomor_surat', 'tanggal_surat'])
            ],
            [
                'ST-GURU-01',
                'Surat Tugas Penugasan GTK / Kedinasan',
                'SURAT TUGAS',
                'Surat Tugas Kedinasan',
                '<p>Kepala {nama_sekolah} dengan ini menugaskan kepada:</p><table style=\"width: 100%; margin: 10px 0;\"><tr><td style=\"width: 180px;\"><strong>Nama GTK</strong></td><td>: {nama_guru}</td></tr><tr><td><strong>NIP / NUPTK</strong></td><td>: {nip}</td></tr><tr><td><strong>Jabatan / Tugas</strong></td><td>: {jabatan}</td></tr></table><p>Untuk melaksanakan tugas kedinasan:</p><table style=\"width: 100%; margin: 10px 0;\"><tr><td style=\"width: 180px;\"><strong>Keperluan / Agenda</strong></td><td>: {keperluan_tugas}</td></tr><tr><td><strong>Hari, Tanggal</strong></td><td>: {tanggal_tugas}</td></tr><tr><td><strong>Waktu</strong></td><td>: {waktu_tugas}</td></tr><tr><td><strong>Tempat / Lokasi</strong></td><td>: {lokasi_tugas}</td></tr></table><p>Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh rasa tanggung jawab dan melaporkan hasilnya setelah tugas selesai.</p>',
                json_encode(['nama_guru', 'nip', 'jabatan', 'keperluan_tugas', 'tanggal_tugas', 'waktu_tugas', 'lokasi_tugas', 'nama_sekolah', 'nama_kepala_sekolah', 'nomor_surat', 'tanggal_surat'])
            ]
        ];

        $stmtTpl = $pdo->prepare("
            INSERT INTO persuratan.template_surat (
                id, tenant_id, kode_template, nama_template_surat, judul_surat, perihal_default, konten_html, variabel_tersedia, is_active
            ) VALUES (
                gen_random_uuid(), 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12', ?, ?, ?, ?, ?, ?::jsonb, TRUE
            )
            ON CONFLICT DO NOTHING
        ");
        foreach ($templateData as $tp) {
            $stmtTpl->execute([$tp[0], $tp[1], $tp[2], $tp[3], $tp[4], $tp[5]]);
        }

        // 4. Daftarkan Menu Induk & Submenu Persuratan & Tata Usaha di core.menus
        echo "- Mendaftarkan Menu 'Persuratan & Tata Usaha' ke core.menus...\n";

        $menuParentId = '6c1a8f90-2234-4b5c-8977-112233445500';
        $stmtParent = $pdo->prepare("SELECT id FROM core.menus WHERE nama_menu = 'Persuratan & Tata Usaha' OR url = '/persuratan/dashboard' LIMIT 1");
        $stmtParent->execute();
        $existingParent = $stmtParent->fetchColumn();

        if (!$existingParent) {
            $stmtInsParent = $pdo->prepare("
                INSERT INTO core.menus (id, parent_id, nama_menu, url, icon, urutan)
                VALUES (?, NULL, 'Persuratan & Tata Usaha', '#', 'bi bi-envelope-paper-fill', 36)
                ON CONFLICT DO NOTHING
            ");
            $stmtInsParent->execute([$menuParentId]);
            $parentMenuId = $menuParentId;
        } else {
            $parentMenuId = $existingParent;
        }

        $submenus = [
            ['6c1a8f90-2234-4b5c-8977-112233445501', 'Dashboard Persuratan', '/persuratan/dashboard', 'bi bi-speedometer2', 361],
            ['6c1a8f90-2234-4b5c-8977-112233445502', 'Surat Masuk & Disposisi', '/persuratan/surat-masuk', 'bi bi-inbox-fill', 362],
            ['6c1a8f90-2234-4b5c-8977-112233445503', 'Surat Keluar & Register', '/persuratan/surat-keluar', 'bi bi-send-fill', 363],
            ['6c1a8f90-2234-4b5c-8977-112233445504', 'Pengajuan & Notifikasi BK', '/persuratan/pengajuan-bk', 'bi bi-bell-fill', 364],
            ['6c1a8f90-2234-4b5c-8977-112233445505', 'Generator & Template Surat', '/persuratan/template', 'bi bi-file-earmark-richtext', 365],
            ['6c1a8f90-2234-4b5c-8977-112233445506', 'Klasifikasi & Kop Surat', '/persuratan/master', 'bi bi-gear-fill', 366],
            ['6c1a8f90-2234-4b5c-8977-112233445507', 'Verifikasi Dokumen TTE QR', '/persuratan/verifikasi', 'bi bi-qr-code-scan', 367],
        ];

        $stmtSub = $pdo->prepare("
            INSERT INTO core.menus (id, parent_id, nama_menu, url, icon, urutan)
            VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT (id) DO UPDATE SET
                parent_id = EXCLUDED.parent_id,
                nama_menu = EXCLUDED.nama_menu,
                url = EXCLUDED.url,
                icon = EXCLUDED.icon,
                urutan = EXCLUDED.urutan
        ");

        $allMenuIds = [$parentMenuId];
        foreach ($submenus as $sub) {
            $stmtSub->execute([$sub[0], $parentMenuId, $sub[1], $sub[2], $sub[3], $sub[4]]);
            $allMenuIds[] = $sub[0];
        }

        // 5. Grant access in core.tenant_menu_access for ALL tenants
        $stmtTenants = $pdo->query("SELECT id FROM core.tenants");
        $tenants = $stmtTenants->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $tenants[] = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';

        $stmtTma = $pdo->prepare("
            INSERT INTO core.tenant_menu_access (id, tenant_id, menu_id)
            VALUES (gen_random_uuid(), ?, ?)
            ON CONFLICT DO NOTHING
        ");

        foreach (array_unique($tenants) as $tId) {
            foreach ($allMenuIds as $mId) {
                $stmtTma->execute([$tId, $mId]);
            }
        }

        // 6. Grant access in core.role_menu_access for TU, Admin, Super Admin, Kepala Sekolah
        $stmtRoles = $pdo->query("SELECT id FROM core.roles WHERE LOWER(nama_role) IN ('super_admin', 'superadmin', 'admin', 'operator_sekolah', 'tata_usaha', 'tu', 'kepala_sekolah', 'kepsek')");
        $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $stmtRma = $pdo->prepare("
            INSERT INTO core.role_menu_access (tenant_id, role_id, menu_id)
            VALUES ('e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12', ?, ?)
            ON CONFLICT DO NOTHING
        ");

        foreach ($roles as $rId) {
            foreach ($allMenuIds as $mId) {
                $stmtRma->execute([$rId, $mId]);
            }
        }

        echo "✔ Schema Persuratan, Notifikasi BK, dan Menu Tata Usaha Berhasil Ditingkatkan & Dikonfigurasi.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            DELETE FROM core.menus WHERE url LIKE '/persuratan/%' OR nama_menu = 'Persuratan & Tata Usaha';
            DROP TABLE IF EXISTS persuratan.pengajuan_surat_bk CASCADE;
        ");
        echo "- Rollback menu persuratan berhasil.\n";
    }
];
