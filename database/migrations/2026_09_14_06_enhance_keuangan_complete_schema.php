<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pastikan skema keuangan ada
        DB::statement("CREATE SCHEMA IF NOT EXISTS keuangan;");

        // 2. Tabel Kas & Bank
        if (!Schema::hasTable('keuangan.kas_bank')) {
            Schema::create('keuangan.kas_bank', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('tenant_id')->nullable()->index();
                $table->string('kode_kas', 50)->nullable();
                $table->string('nama_kas', 100);
                $table->string('nomor_rekening', 100)->nullable();
                $table->string('atas_nama', 100)->nullable();
                $table->decimal('saldo_awal', 15, 2)->default(0);
                $table->decimal('saldo_saat_ini', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Tabel Pengaturan Keuangan & Kuitansi
        if (!Schema::hasTable('keuangan.transaksi_spp_pengaturan')) {
            Schema::create('keuangan.transaksi_spp_pengaturan', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('tenant_id')->nullable()->index();
                $table->string('nama_modul', 100)->default('Keuangan & SPP');
                $table->string('istilah_tagihan', 100)->default('Tagihan');
                $table->string('istilah_tunggakan', 100)->default('Tunggakan');
                $table->string('format_nomor_kuitansi', 100)->default('KW/{Y}{m}/{NUM}');
                $table->string('nama_bendahara', 150)->nullable();
                $table->string('nip_bendahara', 50)->nullable();
                $table->text('catatan_kuitansi')->nullable();
                $table->string('midtrans_client_key', 255)->nullable();
                $table->string('midtrans_server_key', 255)->nullable();
                $table->boolean('midtrans_is_production')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Enhance: Komponen Biaya / Pos Pembayaran
        Schema::table('keuangan.transaksi_spp_komponen', function (Blueprint $table) {
            if (!Schema::hasColumn('keuangan.transaksi_spp_komponen', 'kode_pos')) {
                $table->string('kode_pos', 50)->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_komponen', 'nama_pos')) {
                $table->string('nama_pos', 150)->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_komponen', 'tipe_periode')) {
                $table->string('tipe_periode', 50)->default('Bulanan'); // Bulanan, Bebas, Semester, Tahunan
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_komponen', 'urutan')) {
                $table->integer('urutan')->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_komponen', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
        });

        // 5. Enhance: Tarif Pembayaran
        Schema::table('keuangan.transaksi_spp_tarif', function (Blueprint $table) {
            if (!Schema::hasColumn('keuangan.transaksi_spp_tarif', 'pos_id')) {
                $table->uuid('pos_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tarif', 'tahun_ajaran_id')) {
                $table->uuid('tahun_ajaran_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tarif', 'tingkat')) {
                $table->string('tingkat', 20)->nullable(); // X, XI, XII, VII, VIII, IX, dll
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tarif', 'jurusan_id')) {
                $table->uuid('jurusan_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tarif', 'kelas_id')) {
                $table->uuid('kelas_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tarif', 'nominal_tarif')) {
                $table->decimal('nominal_tarif', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tarif', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
        });

        // 6. Enhance: Keringanan & Beasiswa
        Schema::table('keuangan.transaksi_spp_keringanan', function (Blueprint $table) {
            if (!Schema::hasColumn('keuangan.transaksi_spp_keringanan', 'siswa_id')) {
                $table->uuid('siswa_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_keringanan', 'pos_id')) {
                $table->uuid('pos_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_keringanan', 'tipe_potongan')) {
                $table->string('tipe_potongan', 20)->default('Nominal'); // Nominal, Persentase
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_keringanan', 'nilai_potongan')) {
                $table->decimal('nilai_potongan', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_keringanan', 'alasan')) {
                $table->text('alasan')->nullable();
            }
        });

        // 7. Enhance: Tagihan Siswa (Invoices)
        Schema::table('keuangan.transaksi_spp_tagihan', function (Blueprint $table) {
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'nomor_tagihan')) {
                $table->string('nomor_tagihan', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'siswa_id')) {
                $table->uuid('siswa_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'pos_id')) {
                $table->uuid('pos_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'tahun_ajaran_id')) {
                $table->uuid('tahun_ajaran_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'bulan')) {
                $table->integer('bulan')->nullable()->index(); // 1 - 12
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'tahun')) {
                $table->integer('tahun')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'nominal_tarif_dasar')) {
                $table->decimal('nominal_tarif_dasar', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'nominal_potongan')) {
                $table->decimal('nominal_potongan', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'total_tagihan')) {
                $table->decimal('total_tagihan', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'total_terbayar')) {
                $table->decimal('total_terbayar', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'sisa_tagihan')) {
                $table->decimal('sisa_tagihan', 15, 2)->default(0)->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'status_pembayaran')) {
                $table->string('status_pembayaran', 50)->default('Belum Bayar')->index(); // Belum Bayar, Sebagian, Lunas, Batal
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'tanggal_jatuh_tempo')) {
                $table->date('tanggal_jatuh_tempo')->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'snap_token')) {
                $table->string('snap_token', 255)->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_tagihan', 'payment_url')) {
                $table->string('payment_url', 500)->nullable();
            }
        });

        // 8. Enhance: Transaksi Pembayaran
        Schema::table('keuangan.transaksi_spp_pembayaran', function (Blueprint $table) {
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'nomor_transaksi')) {
                $table->string('nomor_transaksi', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'tagihan_id')) {
                $table->uuid('tagihan_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'siswa_id')) {
                $table->uuid('siswa_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'nominal_bayar')) {
                $table->decimal('nominal_bayar', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'metode_pembayaran')) {
                $table->string('metode_pembayaran', 50)->default('Tunai'); // Tunai, Transfer Bank, QRIS, Midtrans VA
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'kas_id')) {
                $table->uuid('kas_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'user_id_kasir')) {
                $table->uuid('user_id_kasir')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'tanggal_bayar')) {
                $table->timestampTz('tanggal_bayar')->default(DB::raw('CURRENT_TIMESTAMP'))->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'status_transaksi')) {
                $table->string('status_transaksi', 50)->default('SUCCESS')->index(); // SUCCESS, VOID
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'alasan_void')) {
                $table->text('alasan_void')->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'void_by')) {
                $table->uuid('void_by')->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'void_at')) {
                $table->timestampTz('void_at')->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'bukti_transfer_url')) {
                $table->string('bukti_transfer_url', 500)->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_pembayaran', 'catatan')) {
                $table->text('catatan')->nullable();
            }
        });

        // 9. Enhance: Audit Log Keuangan
        Schema::table('keuangan.transaksi_spp_audit_log', function (Blueprint $table) {
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'user_id')) {
                $table->uuid('user_id')->nullable()->index();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'user_role')) {
                $table->string('user_role', 100)->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'event_type')) {
                $table->string('event_type', 100)->nullable()->index(); // PAYMENT, VOID, GENERATE, EDIT_NOMINAL, DELETE
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'nominal')) {
                $table->decimal('nominal', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'old_data')) {
                $table->jsonb('old_data')->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'new_data')) {
                $table->jsonb('new_data')->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
            if (!Schema::hasColumn('keuangan.transaksi_spp_audit_log', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
        });

        // 10. Seed Default Data Pos & Kas untuk seluruh Tenant yang sudah ada
        $tenants = DB::table('core.tenants')->get();
        foreach ($tenants as $t) {
            // Seed Kas Utama jika belum ada
            $kasCount = DB::table('keuangan.kas_bank')->where('tenant_id', $t->id)->count();
            if ($kasCount === 0) {
                DB::table('keuangan.kas_bank')->insert([
                    'id'            => (string)Str::uuid(),
                    'tenant_id'     => $t->id,
                    'kode_kas'      => 'KAS-01',
                    'nama_kas'      => 'Kas Tunai Bendahara',
                    'nomor_rekening'=> null,
                    'atas_nama'     => 'Bendahara Sekolah',
                    'saldo_awal'    => 0,
                    'saldo_saat_ini'=> 0,
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                DB::table('keuangan.kas_bank')->insert([
                    'id'            => (string)Str::uuid(),
                    'tenant_id'     => $t->id,
                    'kode_kas'      => 'BANK-BSI',
                    'nama_kas'      => 'Bank BSI Penampung',
                    'nomor_rekening'=> '7123456789',
                    'atas_nama'     => $t->nama_sekolah,
                    'saldo_awal'    => 0,
                    'saldo_saat_ini'=> 0,
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            // Seed Pengaturan jika belum ada
            $settingCount = DB::table('keuangan.transaksi_spp_pengaturan')->where('tenant_id', $t->id)->count();
            if ($settingCount === 0) {
                DB::table('keuangan.transaksi_spp_pengaturan')->insert([
                    'id'                  => (string)Str::uuid(),
                    'tenant_id'           => $t->id,
                    'nama_modul'          => 'Keuangan & SPP',
                    'istilah_tagihan'     => 'Tagihan',
                    'istilah_tunggakan'   => 'Tunggakan',
                    'format_nomor_kuitansi'=> 'KW/{Y}{m}/{NUM}',
                    'nama_bendahara'      => 'Bendahara Sekolah',
                    'nip_bendahara'       => '-',
                    'catatan_kuitansi'    => 'Kuitansi ini merupakan bukti pembayaran yang sah diterbitkan secara otomatis oleh sistem SINTA-SaaS.',
                    'is_active'           => true,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            // Seed Pos Biaya Standar jika belum ada
            $posCount = DB::table('keuangan.transaksi_spp_komponen')->where('tenant_id', $t->id)->count();
            if ($posCount === 0) {
                $posSppId = (string)Str::uuid();
                DB::table('keuangan.transaksi_spp_komponen')->insert([
                    'id'            => $posSppId,
                    'tenant_id'     => $t->id,
                    'kode_pos'      => 'SPP',
                    'nama_pos'      => 'Sumbangan Pembinaan Pendidikan (SPP)',
                    'tipe_periode'  => 'Bulanan',
                    'urutan'        => 1,
                    'keterangan'    => 'Biaya SPP bulanan wajib untuk operasional pendidikan siswa.',
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $posGedungId = (string)Str::uuid();
                DB::table('keuangan.transaksi_spp_komponen')->insert([
                    'id'            => $posGedungId,
                    'tenant_id'     => $t->id,
                    'kode_pos'      => 'GEDUNG',
                    'nama_pos'      => 'Uang Gedung & Sarana Prasarana',
                    'tipe_periode'  => 'Bebas',
                    'urutan'        => 2,
                    'keterangan'    => 'Biaya pengembangan sarana dan gedung (dapat dicicil).',
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $posKegiatanId = (string)Str::uuid();
                DB::table('keuangan.transaksi_spp_komponen')->insert([
                    'id'            => $posKegiatanId,
                    'tenant_id'     => $t->id,
                    'kode_pos'      => 'KEGIATAN',
                    'nama_pos'      => 'Biaya Kegiatan & Ekstrakurikuler',
                    'tipe_periode'  => 'Semester',
                    'urutan'        => 3,
                    'keterangan'    => 'Iuran kegiatan OSIS, Pramuka, dan Ekskul per semester.',
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Rollback strategy: Drop enhanced tables
        Schema::dropIfExists('keuangan.transaksi_spp_pengaturan');
        Schema::dropIfExists('keuangan.kas_bank');
    }
};
