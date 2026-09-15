<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance kepegawaian.ptk_identitas
        Schema::table('kepegawaian.ptk_identitas', function (Blueprint $table) {
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'nip')) {
                $table->string('nip', 30)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'nuptk')) {
                $table->string('nuptk', 30)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'nik')) {
                $table->string('nik', 20)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'nama_lengkap')) {
                $table->string('nama_lengkap', 255)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'gelar_depan')) {
                $table->string('gelar_depan', 20)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'gelar_belakang')) {
                $table->string('gelar_belakang', 50)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'jenis_kelamin')) {
                $table->char('jenis_kelamin', 1)->default('L');
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'tempat_lahir')) {
                $table->string('tempat_lahir', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'jenis_ptk')) {
                $table->string('jenis_ptk', 50)->default('Guru Mapel');
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'status_kepegawaian')) {
                $table->string('status_kepegawaian', 50)->default('GTY');
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'jabatan')) {
                $table->string('jabatan', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'pendidikan_terakhir')) {
                $table->string('pendidikan_terakhir', 20)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'jurusan_pendidikan')) {
                $table->string('jurusan_pendidikan', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'no_hp')) {
                $table->string('no_hp', 30)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'email')) {
                $table->string('email', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'alamat_tinggal')) {
                $table->text('alamat_tinggal')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'tmt_kerja')) {
                $table->date('tmt_kerja')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.ptk_identitas', 'foto_url')) {
                $table->string('foto_url', 500)->nullable();
            }
        });

        // 2. Enhance kepegawaian.riwayat_kepangkatan
        Schema::table('kepegawaian.riwayat_kepangkatan', function (Blueprint $table) {
            if (!Schema::hasColumn('kepegawaian.riwayat_kepangkatan', 'ptk_id')) {
                $table->uuid('ptk_id')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.riwayat_kepangkatan', 'golongan_pangkat')) {
                $table->string('golongan_pangkat', 50)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.riwayat_kepangkatan', 'nomor_sk')) {
                $table->string('nomor_sk', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.riwayat_kepangkatan', 'tanggal_sk')) {
                $table->date('tanggal_sk')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.riwayat_kepangkatan', 'tmt_pangkat')) {
                $table->date('tmt_pangkat')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.riwayat_kepangkatan', 'pejabat_penetap')) {
                $table->string('pejabat_penetap', 150)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.riwayat_kepangkatan', 'gaji_pokok')) {
                $table->decimal('gaji_pokok', 14, 2)->default(0);
            }
            if (!Schema::hasColumn('kepegawaian.riwayat_kepangkatan', 'is_terakhir')) {
                $table->boolean('is_terakhir')->default(false);
            }
        });

        // 3. Enhance kepegawaian.sertifikasi_ptk
        Schema::table('kepegawaian.sertifikasi_ptk', function (Blueprint $table) {
            if (!Schema::hasColumn('kepegawaian.sertifikasi_ptk', 'ptk_id')) {
                $table->uuid('ptk_id')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.sertifikasi_ptk', 'jenis_sertifikasi')) {
                $table->string('jenis_sertifikasi', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.sertifikasi_ptk', 'nomor_peserta')) {
                $table->string('nomor_peserta', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.sertifikasi_ptk', 'nomor_sertifikat')) {
                $table->string('nomor_sertifikat', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.sertifikasi_ptk', 'tahun_sertifikasi')) {
                $table->integer('tahun_sertifikasi')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.sertifikasi_ptk', 'bidang_studi')) {
                $table->string('bidang_studi', 150)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.sertifikasi_ptk', 'lembaga_penerbit')) {
                $table->string('lembaga_penerbit', 150)->nullable();
            }
        });

        // 4. Enhance kepegawaian.lowongan_kerja
        Schema::table('kepegawaian.lowongan_kerja', function (Blueprint $table) {
            if (!Schema::hasColumn('kepegawaian.lowongan_kerja', 'judul_posisi')) {
                $table->string('judul_posisi', 200)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.lowongan_kerja', 'jenis_pekerjaan')) {
                $table->string('jenis_pekerjaan', 50)->default('Full Time');
            }
            if (!Schema::hasColumn('kepegawaian.lowongan_kerja', 'kualifikasi_pendidikan')) {
                $table->string('kualifikasi_pendidikan', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.lowongan_kerja', 'persyaratan')) {
                $table->text('persyaratan')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.lowongan_kerja', 'tanggal_buka')) {
                $table->date('tanggal_buka')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.lowongan_kerja', 'tanggal_tutup')) {
                $table->date('tanggal_tutup')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.lowongan_kerja', 'kuota_dibutuhkan')) {
                $table->integer('kuota_dibutuhkan')->default(1);
            }
            if (!Schema::hasColumn('kepegawaian.lowongan_kerja', 'status')) {
                $table->string('status', 30)->default('Buka');
            }
        });

        // 5. Enhance kepegawaian.pelamar_kerja
        Schema::table('kepegawaian.pelamar_kerja', function (Blueprint $table) {
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'lowongan_id')) {
                $table->uuid('lowongan_id')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'nama_lengkap')) {
                $table->string('nama_lengkap', 255)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'nik')) {
                $table->string('nik', 20)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'email')) {
                $table->string('email', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'no_hp')) {
                $table->string('no_hp', 30)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'pendidikan_terakhir')) {
                $table->string('pendidikan_terakhir', 20)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'jurusan')) {
                $table->string('jurusan', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'ipk')) {
                $table->decimal('ipk', 4, 2)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'pengalaman_kerja')) {
                $table->text('pengalaman_kerja')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'berkas_cv_url')) {
                $table->string('berkas_cv_url', 500)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'status_tahapan')) {
                $table->string('status_tahapan', 50)->default('Pendaftaran');
            }
            if (!Schema::hasColumn('kepegawaian.pelamar_kerja', 'catatan_seleksi')) {
                $table->text('catatan_seleksi')->nullable();
            }
        });

        // 6. Enhance smk.mitra_dudi
        Schema::table('smk.mitra_dudi', function (Blueprint $table) {
            if (!Schema::hasColumn('smk.mitra_dudi', 'nama_perusahaan')) {
                $table->string('nama_perusahaan', 255)->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'bidang_usaha')) {
                $table->string('bidang_usaha', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'alamat_perusahaan')) {
                $table->text('alamat_perusahaan')->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'kota')) {
                $table->string('kota', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'contact_person_nama')) {
                $table->string('contact_person_nama', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'contact_person_jabatan')) {
                $table->string('contact_person_jabatan', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'contact_person_hp')) {
                $table->string('contact_person_hp', 30)->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'email_perusahaan')) {
                $table->string('email_perusahaan', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'nomor_mou_kerjasama')) {
                $table->string('nomor_mou_kerjasama', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'tanggal_mulai_mou')) {
                $table->date('tanggal_mulai_mou')->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'tanggal_akhir_mou')) {
                $table->date('tanggal_akhir_mou')->nullable();
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'kuota_penerimaan_pkl')) {
                $table->integer('kuota_penerimaan_pkl')->default(5);
            }
            if (!Schema::hasColumn('smk.mitra_dudi', 'status_kerjasama')) {
                $table->string('status_kerjasama', 30)->default('Aktif');
            }
        });

        // 7. Enhance smk.pkl_penempatan
        Schema::table('smk.pkl_penempatan', function (Blueprint $table) {
            if (!Schema::hasColumn('smk.pkl_penempatan', 'siswa_id')) {
                $table->uuid('siswa_id')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'nama_siswa')) {
                $table->string('nama_siswa', 255)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'nisn')) {
                $table->string('nisn', 30)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'mitra_dudi_id')) {
                $table->uuid('mitra_dudi_id')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'nama_perusahaan')) {
                $table->string('nama_perusahaan', 255)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'kelas_id')) {
                $table->uuid('kelas_id')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'nama_kelas')) {
                $table->string('nama_kelas', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'jurusan')) {
                $table->string('jurusan', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'pembimbing_sekolah_nama')) {
                $table->string('pembimbing_sekolah_nama', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'pembimbing_dudi_nama')) {
                $table->string('pembimbing_dudi_nama', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'tanggal_mulai')) {
                $table->date('tanggal_mulai')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'tanggal_selesai')) {
                $table->date('tanggal_selesai')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'nilai_kinerja_dudi')) {
                $table->decimal('nilai_kinerja_dudi', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'nilai_laporan_sekolah')) {
                $table->decimal('nilai_laporan_sekolah', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'nilai_akhir_pkl')) {
                $table->decimal('nilai_akhir_pkl', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'status_pkl')) {
                $table->string('status_pkl', 50)->default('Sedang Berjalan');
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'sertifikat_pkl_url')) {
                $table->string('sertifikat_pkl_url', 500)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_penempatan', 'catatan_evaluasi')) {
                $table->text('catatan_evaluasi')->nullable();
            }
        });

        // 8. Enhance smk.pkl_jurnal_harian
        Schema::table('smk.pkl_jurnal_harian', function (Blueprint $table) {
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'penempatan_id')) {
                $table->uuid('penempatan_id')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'siswa_id')) {
                $table->uuid('siswa_id')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'tanggal')) {
                $table->date('tanggal')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'jam_masuk')) {
                $table->string('jam_masuk', 10)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'jam_pulang')) {
                $table->string('jam_pulang', 10)->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'kegiatan_pekerjaan')) {
                $table->text('kegiatan_pekerjaan')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'alat_bahan_digunakan')) {
                $table->text('alat_bahan_digunakan')->nullable();
            }
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'status_verifikasi')) {
                $table->string('status_verifikasi', 30)->default('Pending');
            }
            if (!Schema::hasColumn('smk.pkl_jurnal_harian', 'catatan_pembimbing')) {
                $table->text('catatan_pembimbing')->nullable();
            }
        });

        // 9. Enhance smk.ukk_penilaian
        Schema::table('smk.ukk_penilaian', function (Blueprint $table) {
            if (!Schema::hasColumn('smk.ukk_penilaian', 'siswa_id')) {
                $table->uuid('siswa_id')->nullable();
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'nama_siswa')) {
                $table->string('nama_siswa', 255)->nullable();
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'nisn')) {
                $table->string('nisn', 30)->nullable();
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'jurusan')) {
                $table->string('jurusan', 100)->nullable();
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'tahun_ajaran')) {
                $table->string('tahun_ajaran', 20)->nullable();
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'nama_paket_soal')) {
                $table->string('nama_paket_soal', 200)->nullable();
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'penguji_internal')) {
                $table->string('penguji_internal', 150)->nullable();
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'penguji_eksternal_dudi')) {
                $table->string('penguji_eksternal_dudi', 150)->nullable();
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'skor_perencanaan')) {
                $table->decimal('skor_perencanaan', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'skor_proses_kerja')) {
                $table->decimal('skor_proses_kerja', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'skor_hasil_produk')) {
                $table->decimal('skor_hasil_produk', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'skor_sikap_k3')) {
                $table->decimal('skor_sikap_k3', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'skor_total')) {
                $table->decimal('skor_total', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'predikat')) {
                $table->string('predikat', 50)->default('Kompeten');
            }
            if (!Schema::hasColumn('smk.ukk_penilaian', 'nomor_sertifikat')) {
                $table->string('nomor_sertifikat', 100)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down
    }
};
