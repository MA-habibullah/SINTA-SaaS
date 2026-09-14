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
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'anak_judul')) {
                $table->string('anak_judul', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'pengarang_tambahan')) {
                $table->string('pengarang_tambahan', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'edisi')) {
                $table->string('edisi', 50)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'jenis_bahan')) {
                $table->string('jenis_bahan', 50)->default('Buku Teks / Monograf');
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'deskripsi_fisik')) {
                $table->string('deskripsi_fisik', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_bibliografi', 'is_quarantine')) {
                $table->boolean('is_quarantine')->default(false);
            }
        });

        // 2. perpustakaan.perpus_eksemplar
        Schema::table('perpustakaan.perpus_eksemplar', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'nomor_panggil_item')) {
                $table->string('nomor_panggil_item', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'tipe_koleksi')) {
                $table->string('tipe_koleksi', 50)->default('Sirkulasi');
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'rfid_tag')) {
                $table->string('rfid_tag', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'tanggal_perolehan')) {
                $table->date('tanggal_perolehan')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_eksemplar', 'catatan_kondisi')) {
                $table->text('catatan_kondisi')->nullable();
            }
        });

        // 3. perpustakaan.perpus_sirkulasi
        Schema::table('perpustakaan.perpus_sirkulasi', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'kondisi_kembali')) {
                $table->string('kondisi_kembali', 50)->default('Baik');
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'denda_kerusakan')) {
                $table->decimal('denda_kerusakan', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'denda_kehilangan')) {
                $table->decimal('denda_kehilangan', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'metode_pembayaran_denda')) {
                $table->string('metode_pembayaran_denda', 50)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_sirkulasi', 'tanggal_bayar_denda')) {
                $table->timestampTz('tanggal_bayar_denda')->nullable();
            }
        });

        // 4. perpustakaan.perpus_pengaturan
        Schema::table('perpustakaan.perpus_pengaturan', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'toleransi_keterlambatan')) {
                $table->integer('toleransi_keterlambatan')->default(0);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'max_perpanjangan_siswa')) {
                $table->integer('max_perpanjangan_siswa')->default(1);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'max_perpanjangan_guru')) {
                $table->integer('max_perpanjangan_guru')->default(2);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'hitung_libur_denda')) {
                $table->boolean('hitung_libur_denda')->default(false);
            }
            if (!Schema::hasColumn('perpustakaan.perpus_pengaturan', 'format_nomor_surat_bebas')) {
                $table->string('format_nomor_surat_bebas', 100)->default('421.3/{NOMOR}/PERPUS/{TAHUN}');
            }
        });

        // 5. perpustakaan.perpus_reservasi
        Schema::table('perpustakaan.perpus_reservasi', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'buku_id')) {
                $table->uuid('buku_id')->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'eksemplar_id')) {
                $table->uuid('eksemplar_id')->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'peminjam_type')) {
                $table->string('peminjam_type', 50)->default('Siswa');
            }
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'peminjam_id')) {
                $table->string('peminjam_id', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'nama_peminjam')) {
                $table->string('nama_peminjam', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'nomor_identitas')) {
                $table->string('nomor_identitas', 100)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'tanggal_reservasi')) {
                $table->date('tanggal_reservasi')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'tanggal_berakhir')) {
                $table->date('tanggal_berakhir')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_reservasi', 'status_reservasi')) {
                $table->string('status_reservasi', 50)->default('Menunggu');
            }
        });

        // 6. perpustakaan.perpus_baca_di_tempat (Buat / lengkapi jika belum ada)
        if (!Schema::hasTable('perpustakaan.perpus_baca_di_tempat')) {
            Schema::create('perpustakaan.perpus_baca_di_tempat', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('eksemplar_id')->nullable()->index();
                $table->uuid('buku_id')->nullable()->index();
                $table->string('peminjam_id', 100)->nullable();
                $table->string('nama_pembaca', 255);
                $table->string('tipe_pembaca', 50)->default('Siswa');
                $table->string('ruang_baca', 100)->default('Ruang Baca Utama');
                $table->timestampTz('waktu_baca')->useCurrent();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 7. perpustakaan.perpus_opname
        Schema::table('perpustakaan.perpus_opname', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_opname', 'nama_sesi')) {
                $table->string('nama_sesi', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_opname', 'tanggal_mulai')) {
                $table->date('tanggal_mulai')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_opname', 'tanggal_selesai')) {
                $table->date('tanggal_selesai')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_opname', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_opname', 'status_opname')) {
                $table->string('status_opname', 50)->default('Berjalan');
            }
        });

        // 8. perpustakaan.perpus_opname_item
        if (!Schema::hasTable('perpustakaan.perpus_opname_item')) {
            Schema::create('perpustakaan.perpus_opname_item', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('opname_id')->index();
                $table->uuid('eksemplar_id')->nullable()->index();
                $table->string('barcode', 100)->index();
                $table->string('status_temuan', 50)->default('Sesuai di Rak');
                $table->string('kondisi_fisik', 50)->default('Baik');
                $table->timestampTz('scanned_at')->useCurrent();
                $table->string('petugas_scan', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 9. perpustakaan.perpus_kategori_ddc
        Schema::table('perpustakaan.perpus_kategori_ddc', function (Blueprint $table) {
            if (!Schema::hasColumn('perpustakaan.perpus_kategori_ddc', 'kode_ddc')) {
                $table->string('kode_ddc', 50)->nullable()->index();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_kategori_ddc', 'nama_klasifikasi')) {
                $table->string('nama_klasifikasi', 255)->nullable();
            }
            if (!Schema::hasColumn('perpustakaan.perpus_kategori_ddc', 'warna_label')) {
                $table->string('warna_label', 50)->default('#3b82f6');
            }
            if (!Schema::hasColumn('perpustakaan.perpus_kategori_ddc', 'deskripsi_ddc')) {
                $table->text('deskripsi_ddc')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Reversible migration
    }
};
