<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. perpustakaan.perpus_bibliografi
        Schema::table('perpustakaan.perpus_bibliografi', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'kode_buku')) {
                $table->string('kode_buku', 50)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'isbn')) {
                $table->string('isbn', 30)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'judul_buku')) {
                $table->string('judul_buku', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'pengarang')) {
                $table->string('pengarang', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'penerbit')) {
                $table->string('penerbit', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'kota_terbit')) {
                $table->string('kota_terbit', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'tahun_terbit')) {
                $table->integer('tahun_terbit')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'halaman')) {
                $table->integer('halaman')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'dimensi')) {
                $table->string('dimensi', 50)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'bahasa')) {
                $table->string('bahasa', 50)->default('Indonesia');
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'nomor_klasifikasi_ddc')) {
                $table->string('nomor_klasifikasi_ddc', 50)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'nomor_panggil')) {
                $table->string('nomor_panggil', 50)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'subjek')) {
                $table->string('subjek', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'sinopsis')) {
                $table->text('sinopsis')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'lokasi_rak')) {
                $table->string('lokasi_rak', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'jumlah_eksemplar')) {
                $table->integer('jumlah_eksemplar')->default(1);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'jumlah_tersedia')) {
                $table->integer('jumlah_tersedia')->default(1);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'cover_url')) {
                $table->text('cover_url')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'ebook_url')) {
                $table->text('ebook_url')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'is_ebook')) {
                $table->boolean('is_ebook')->default(false);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'status_opac')) {
                $table->boolean('status_opac')->default(true);
            }
        });

        // 2. perpustakaan.perpus_eksemplar
        Schema::table('perpustakaan.perpus_eksemplar', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'bibliografi_id')) {
                $table->uuid('bibliografi_id')->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'barcode')) {
                $table->string('barcode', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'no_induk')) {
                $table->string('no_induk', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'lokasi_rak')) {
                $table->string('lokasi_rak', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'status_kondisi')) {
                $table->string('status_kondisi', 50)->default('Tersedia'); // Tersedia, Dipinjam, Rusak, Hilang
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'sumber_perolehan')) {
                $table->string('sumber_perolehan', 100)->nullable(); // Beli, Hibah, BOS, Droping
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'harga_beli')) {
                $table->decimal('harga_beli', 15, 2)->default(0);
            }
        });

        // 3. perpustakaan.perpus_sirkulasi
        Schema::table('perpustakaan.perpus_sirkulasi', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'nomor_transaksi')) {
                $table->string('nomor_transaksi', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'buku_id')) {
                $table->uuid('buku_id')->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'eksemplar_id')) {
                $table->uuid('eksemplar_id')->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'peminjam_type')) {
                $table->string('peminjam_type', 50)->default('Siswa'); // Siswa, Guru, Tendik, Umum
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'peminjam_id')) {
                $table->string('peminjam_id', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'nama_peminjam')) {
                $table->string('nama_peminjam', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'nomor_identitas')) {
                $table->string('nomor_identitas', 100)->nullable(); // NISN / NIP / No Anggota
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'kelas_unit')) {
                $table->string('kelas_unit', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'tanggal_pinjam')) {
                $table->date('tanggal_pinjam')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'tanggal_harus_kembali')) {
                $table->date('tanggal_harus_kembali')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'tanggal_kembali_aktual')) {
                $table->date('tanggal_kembali_aktual')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'jumlah_perpanjangan')) {
                $table->integer('jumlah_perpanjangan')->default(0);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'status_sirkulasi')) {
                $table->string('status_sirkulasi', 50)->default('Dipinjam'); // Dipinjam, Kembali, Terlambat, Hilang
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'tarif_denda_harian')) {
                $table->decimal('tarif_denda_harian', 12, 2)->default(1000);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'hari_keterlambatan')) {
                $table->integer('hari_keterlambatan')->default(0);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'denda_keterlambatan')) {
                $table->decimal('denda_keterlambatan', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'denda_dibayar')) {
                $table->decimal('denda_dibayar', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'status_denda')) {
                $table->string('status_denda', 50)->default('Nihil'); // Nihil, Belum Lunas, Lunas
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'catatan')) {
                $table->text('catatan')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'petugas_peminjaman')) {
                $table->string('petugas_peminjaman', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'petugas_pengembalian')) {
                $table->string('petugas_pengembalian', 255)->nullable();
            }
        });

        // 4. perpustakaan.perpus_anggota
        Schema::table('perpustakaan.perpus_anggota', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'no_anggota')) {
                $table->string('no_anggota', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'nama_lengkap')) {
                $table->string('nama_lengkap', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'tipe_anggota')) {
                $table->string('tipe_anggota', 50)->default('Umum'); // Siswa, Guru, Tendik, Umum, Alumni
            }
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'identitas_no')) {
                $table->string('identitas_no', 100)->nullable()->index(); // NISN, NIP, KTP
            }
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'kelas_jurusan')) {
                $table->string('kelas_jurusan', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'jenis_kelamin')) {
                $table->string('jenis_kelamin', 10)->default('L');
            }
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'no_telepon')) {
                $table->string('no_telepon', 50)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'alamat')) {
                $table->text('alamat')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_anggota', 'foto_url')) {
                $table->text('foto_url')->nullable();
            }
        });

        // 5. perpustakaan.perpus_buku_tamu
        Schema::table('perpustakaan.perpus_buku_tamu', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_buku_tamu', 'nama_pengunjung')) {
                $table->string('nama_pengunjung', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_buku_tamu', 'tipe_pengunjung')) {
                $table->string('tipe_pengunjung', 50)->default('Siswa'); // Siswa, Guru, Tendik, Tamu
            }
            if (!Schema::hasColumn('perpustakaan.perpus_buku_tamu', 'identitas_no')) {
                $table->string('identitas_no', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_buku_tamu', 'kelas_instansi')) {
                $table->string('kelas_instansi', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_buku_tamu', 'keperluan')) {
                $table->string('keperluan', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_buku_tamu', 'tanggal_kunjungan')) {
                $table->date('tanggal_kunjungan')->nullable();
            }
        });

        // 6. perpustakaan.perpus_pengaturan
        Schema::table('perpustakaan.perpus_pengaturan', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'nama_perpustakaan')) {
                $table->string('nama_perpustakaan', 255)->default('Perpustakaan Digital');
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'kepala_perpustakaan')) {
                $table->string('kepala_perpustakaan', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'nip_kepala')) {
                $table->string('nip_kepala', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'tarif_denda_per_hari')) {
                $table->decimal('tarif_denda_per_hari', 12, 2)->default(1000);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'max_hari_pinjam_siswa')) {
                $table->integer('max_hari_pinjam_siswa')->default(7);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'max_hari_pinjam_guru')) {
                $table->integer('max_hari_pinjam_guru')->default(14);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'max_buku_pinjam_siswa')) {
                $table->integer('max_buku_pinjam_siswa')->default(3);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'max_buku_pinjam_guru')) {
                $table->integer('max_buku_pinjam_guru')->default(10);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'opac_aktif')) {
                $table->boolean('opac_aktif')->default(true);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'syarat_bebas_pustaka')) {
                $table->text('syarat_bebas_pustaka')->nullable();
            }
        });

        // 7. perpustakaan.perpus_paket_buku
        Schema::table('perpustakaan.perpus_paket_buku', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_paket_buku', 'kelas_id')) {
                $table->string('kelas_id', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_paket_buku', 'tahun_ajaran')) {
                $table->string('tahun_ajaran', 50)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_paket_buku', 'mata_pelajaran')) {
                $table->string('mata_pelajaran', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_paket_buku', 'buku_id')) {
                $table->uuid('buku_id')->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_paket_buku', 'jumlah_distribusi')) {
                $table->integer('jumlah_distribusi')->default(0);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_paket_buku', 'status_distribusi')) {
                $table->string('status_distribusi', 50)->default('Terdistribusi'); // Terdistribusi, Dikembalikan
            }
        });

        // 8. perpustakaan.perpus_lokasi_rak
        Schema::table('perpustakaan.perpus_lokasi_rak', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_lokasi_rak', 'kode_rak')) {
                $table->string('kode_rak', 50)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_lokasi_rak', 'nama_rak')) {
                $table->string('nama_rak', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_lokasi_rak', 'lantai_gedung')) {
                $table->string('lantai_gedung', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_lokasi_rak', 'kapasitas_buku')) {
                $table->integer('kapasitas_buku')->default(100);
            }
        });

        // 9. perpustakaan.perpus_usulan_buku
        Schema::table('perpustakaan.perpus_usulan_buku', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_usulan_buku', 'judul_buku')) {
                $table->string('judul_buku', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_usulan_buku', 'pengarang')) {
                $table->string('pengarang', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_usulan_buku', 'penerbit')) {
                $table->string('penerbit', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_usulan_buku', 'pengusul_nama')) {
                $table->string('pengusul_nama', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_usulan_buku', 'alasan_usulan')) {
                $table->text('alasan_usulan')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_usulan_buku', 'status_usulan')) {
                $table->string('status_usulan', 50)->default('Pending'); // Pending, Disetujui, Ditolak, Terbeli
            }
        });

        // 10. perpustakaan.perpus_serial_berkala
        Schema::table('perpustakaan.perpus_serial_berkala', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_serial_berkala', 'nama_serial')) {
                $table->string('nama_serial', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_serial_berkala', 'jenis_serial')) {
                $table->string('jenis_serial', 50)->default('Majalah'); // Majalah, Jurnal, Surat Kabar
            }
            if (!Schema::hasColumn('perpustakaan.perpus_serial_berkala', 'issn')) {
                $table->string('issn', 50)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_serial_berkala', 'edisi_nomor')) {
                $table->string('edisi_nomor', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_serial_berkala', 'frekuensi_terbit')) {
                $table->string('frekuensi_terbit', 50)->default('Bulanan'); // Harian, Mingguan, Bulanan
            }
        });
    }

    public function down(): void
    {
        // Safe reversible migration
    }
};
