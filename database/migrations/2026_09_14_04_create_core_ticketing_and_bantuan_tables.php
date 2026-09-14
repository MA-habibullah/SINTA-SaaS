<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Kategori Tiket (Master Global)
        if (!Schema::hasTable('core.ticket_categories')) {
            Schema::create('core.ticket_categories', function (Blueprint $table) {
                $table->id();
                $table->string('nama_kategori', 100);
                $table->text('deskripsi')->nullable();
                $table->integer('sla_hours')->default(48);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tabel Tiket Bantuan (Multi-Tenant)
        if (!Schema::hasTable('core.tickets')) {
            Schema::create('core.tickets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->nullable()->index();
                $table->uuid('user_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->index();
                $table->string('nomor_tiket', 50)->nullable()->index();
                $table->string('judul', 255);
                $table->text('deskripsi');
                $table->string('urgensi', 50)->default('Sedang'); // Rendah, Sedang, Tinggi, Kritis
                $table->string('status', 50)->default('Menunggu')->index(); // Menunggu, Diproses, Selesai, Batal
                $table->text('lampiran')->nullable();
                $table->text('user_agent')->nullable();
                $table->text('last_url')->nullable();
                $table->timestampTz('sla_deadline')->nullable();
                $table->boolean('user_unread')->default(false);
                $table->boolean('admin_unread')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Foreign Keys
                $table->foreign('tenant_id')->references('id')->on('core.tenants')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('core.users')->onDelete('set null');
                $table->foreign('category_id')->references('id')->on('core.ticket_categories')->onDelete('restrict');
            });
        }

        // 3. Tabel Balasan Percakapan Tiket
        if (!Schema::hasTable('core.ticket_replies')) {
            Schema::create('core.ticket_replies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('ticket_id')->index();
                $table->uuid('user_id')->nullable()->index();
                $table->boolean('is_superadmin')->default(false);
                $table->text('pesan');
                $table->text('lampiran')->nullable();
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('ticket_id')->references('id')->on('core.tickets')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('core.users')->onDelete('set null');
            });
        }

        // 4. Tabel Template Respon Cepat (Canned Responses - Master Global)
        if (!Schema::hasTable('core.ticket_canned_responses')) {
            Schema::create('core.ticket_canned_responses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('judul', 200);
                $table->text('konten');
                $table->timestamps();
            });
        }

        // 5. Tabel FAQ / Basis Pengetahuan Bantuan (Master Global)
        if (!Schema::hasTable('core.ticket_faqs')) {
            Schema::create('core.ticket_faqs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->string('pertanyaan', 255);
                $table->text('jawaban');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('core.ticket_categories')->onDelete('set null');
            });
        }

        // Seeding Data Awal
        $catCount = DB::table('core.ticket_categories')->count();
        if ($catCount === 0) {
            DB::table('core.ticket_categories')->insert([
                [
                    'id'            => 1,
                    'nama_kategori' => 'Teknis / Sistem',
                    'deskripsi'     => 'Kendala teknis, error aplikasi, bug, atau gangguan performa sistem.',
                    'sla_hours'     => 24,
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'id'            => 2,
                    'nama_kategori' => 'Akun & Akses',
                    'deskripsi'     => 'Permintaan reset password, penyesuaian hak akses RBAC, atau akun terkunci.',
                    'sla_hours'     => 12,
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'id'            => 3,
                    'nama_kategori' => 'Keuangan & SPP',
                    'deskripsi'     => 'Permasalahan integrasi pembayaran, pos tarif tagihan, atau jurnal kas.',
                    'sla_hours'     => 24,
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'id'            => 4,
                    'nama_kategori' => 'Akademik & Rapor',
                    'deskripsi'     => 'Format rapor, jadwal pelajaran, rombel kelas, atau penginputan nilai guru.',
                    'sla_hours'     => 24,
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'id'            => 5,
                    'nama_kategori' => 'Pertanyaan Umum',
                    'deskripsi'     => 'Panduan penggunaan modul, pertanyaan fitur, dan konsultasi administrasi.',
                    'sla_hours'     => 48,
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
            ]);
        }

        // Seed Canned Responses
        $cannedCount = DB::table('core.ticket_canned_responses')->count();
        if ($cannedCount === 0) {
            DB::table('core.ticket_canned_responses')->insert([
                [
                    'id'         => (string) \Illuminate\Support\Str::uuid(),
                    'judul'      => 'Solusi Pembersihan Cache & Cookie',
                    'konten'     => "Halo Bapak/Ibu,\n\nTerima kasih telah menghubungi Tim Dukungan SINTA. Mohon coba lakukan pembersihan cache browser Anda dengan menekan kombinasi tombol Ctrl + Shift + R (Hard Refresh) atau melalui pengaturan riwayat browser, kemudian silakan login ulang.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id'         => (string) \Illuminate\Support\Str::uuid(),
                    'judul'      => 'Reset Hak Akses & Akun Guru/Staff',
                    'konten'     => "Halo Admin Sekolah,\n\nPengaturan hak akses telah disesuaikan pada sistem pusat. Silakan minta pengguna terkait untuk logout dan melakukan login kembali untuk memperbarui token sesi dan privilege modul.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id'         => (string) \Illuminate\Support\Str::uuid(),
                    'judul'      => 'Investigasi Kendala oleh Tim Pengembang',
                    'konten'     => "Halo,\n\nLaporan kendala Anda telah kami teruskan ke tim teknis/pengembang untuk proses penelusuran lebih lanjut. Kami akan memberikan pembaruan segera setelah patch/perbaikan diterapkan.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id'         => (string) \Illuminate\Support\Str::uuid(),
                    'judul'      => 'Konfirmasi Penyelesaian Masalah',
                    'konten'     => "Halo,\n\nKendala yang dilaporkan telah berhasil ditangani oleh tim dukungan sistem. Mohon konfirmasi apakah fitur sudah dapat digunakan dengan lancar di sisi Anda. Tiket ini kami tandai sebagai Selesai. Terima kasih.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // Seed Initial FAQs
        $faqCount = DB::table('core.ticket_faqs')->count();
        if ($faqCount === 0) {
            DB::table('core.ticket_faqs')->insert([
                [
                    'id'          => (string) \Illuminate\Support\Str::uuid(),
                    'category_id' => 2,
                    'pertanyaan'  => 'Bagaimana cara melakukan reset password akun guru atau staf yang lupa kata sandi?',
                    'jawaban'     => 'Admin Sekolah dapat membuka menu Konfigurasi Pengguna > Cari nama staf/guru > Klik tombol opsi Edit / Reset Password > Masukkan password baru dan simpan.',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
                [
                    'id'          => (string) \Illuminate\Support\Str::uuid(),
                    'category_id' => 1,
                    'pertanyaan'  => 'Bagaimana cara melakukan import massal data siswa baru dari file Excel?',
                    'jawaban'     => 'Buka Modul Siswa > Buku Induk Siswa > Klik tombol "Import Excel" di kanan atas > Unduh file template resmi SINTA > Isi data sesuai format > Unggah kembali file Excel tersebut.',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
                [
                    'id'          => (string) \Illuminate\Support\Str::uuid(),
                    'category_id' => 4,
                    'pertanyaan'  => 'Bagaimana alur pembagian rombel kelas dan pemetaan wali kelas untuk tahun ajaran baru?',
                    'jawaban'     => 'Buka Modul Akademik > Rombel / Kelas > Pastikan Tahun Ajaran aktif telah dipilih > Tambahkan rombel baru lalu tetapkan Guru sebagai Wali Kelas pada form rombel tersebut.',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
                [
                    'id'          => (string) \Illuminate\Support\Str::uuid(),
                    'category_id' => 3,
                    'pertanyaan'  => 'Bagaimana cara mengaktifkan integrasi pembayaran SPP online via Payment Gateway (Midtrans)?',
                    'jawaban'     => 'Buka Modul Keuangan > Pengaturan Pos Tarif & Gateway > Masukkan Server Key dan Client Key Midtrans resmi sekolah Anda > Aktifkan status toggle Payment Gateway.',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.ticket_faqs');
        Schema::dropIfExists('core.ticket_canned_responses');
        Schema::dropIfExists('core.ticket_replies');
        Schema::dropIfExists('core.tickets');
        Schema::dropIfExists('core.ticket_categories');
    }
};
