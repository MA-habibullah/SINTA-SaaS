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
        // 1. Buat Tabel kepegawaian.supervisi_pembinaan
        if (!Schema::hasTable('kepegawaian.supervisi_pembinaan')) {
            Schema::create('kepegawaian.supervisi_pembinaan', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('guru_id')->nullable()->index();
                $table->string('nama_guru');
                $table->string('nip_guru', 50)->nullable();
                $table->string('mata_pelajaran', 100)->nullable();
                $table->string('kelas_rombel', 50)->nullable();
                $table->date('tanggal_supervisi');
                $table->string('tahun_ajaran', 20)->default('2026/2027');
                $table->string('semester', 10)->default('Ganjil');
                $table->string('jenis_supervisi', 50)->default('Supervisi Akademik/Kelas'); // Supervisi Akademik/Kelas, Supervisi Perangkat Ajar, Pembinaan Disiplin & Etika, Supervisi Manajerial
                $table->integer('skor_pedagogik')->default(85);
                $table->integer('skor_profesional')->default(85);
                $table->integer('skor_kepribadian')->default(90);
                $table->integer('skor_sosial')->default(90);
                $table->decimal('skor_total', 5, 2)->default(87.5);
                $table->string('predikat', 20)->default('Baik'); // Sangat Baik (>=91), Baik (76-90), Cukup (61-75), Perlu Pembinaan (<61)
                $table->text('catatan_observasi')->nullable();
                $table->text('rekomendasi_pembinaan')->nullable();
                $table->text('tindak_lanjut')->nullable();
                $table->string('status_pembinaan', 50)->default('Selesai Dibina'); // Terjadwal, Dalam Proses, Selesai Dibina, Butuh Pendampingan Khusus
                $table->string('dokumen_lampiran', 512)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Buat Tabel kepegawaian.survei_guru
        if (!Schema::hasTable('kepegawaian.survei_guru')) {
            Schema::create('kepegawaian.survei_guru', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->string('judul_survei');
                $table->text('deskripsi')->nullable();
                $table->string('sasaran_survei', 50)->default('Evaluasi Kepala Sekolah'); // Evaluasi Kepala Sekolah, Rekan Sejawat, Asesmen Kepuasan Siswa
                $table->string('tahun_ajaran', 20)->default('2026/2027');
                $table->string('semester', 10)->default('Ganjil');
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai')->nullable();
                $table->string('status', 20)->default('Aktif'); // Aktif, Ditutup, Draft
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Buat Tabel kepegawaian.survei_guru_respon
        if (!Schema::hasTable('kepegawaian.survei_guru_respon')) {
            Schema::create('kepegawaian.survei_guru_respon', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('survei_id')->index();
                $table->uuid('guru_id')->index();
                $table->string('nama_guru');
                $table->uuid('penilai_id')->nullable()->index();
                $table->string('nama_penilai')->default('Kepala Sekolah');
                $table->string('peran_penilai', 50)->default('Kepala Sekolah');
                $table->decimal('skor_pedagogik', 3, 2)->default(4.50);
                $table->decimal('skor_kedisiplinan', 3, 2)->default(4.80);
                $table->decimal('skor_komunikasi', 3, 2)->default(4.60);
                $table->decimal('skor_penguasaan_materi', 3, 2)->default(4.70);
                $table->decimal('rata_rata_skor', 3, 2)->default(4.65);
                $table->text('umpan_balik_positif')->nullable();
                $table->text('area_pengembangan')->nullable();
                $table->timestamps();
            });
        }

        // 4. Struktur Menu Dinamis di core.menus
        $parentMenu = DB::table('core.menus')->where('url', '#')->where('nama_menu', 'ILIKE', '%Kepala Sekolah%')->first();
        if (!$parentMenu) {
            $existingSupervisi = DB::table('core.menus')->where('url', '/pembinaan')->first();
            $parentId = Str::uuid()->toString();

            if ($existingSupervisi) {
                // Ubah menu yang ada menjadi parent menu Kepala Sekolah
                $parentId = $existingSupervisi->id;
                DB::table('core.menus')->where('id', $parentId)->update([
                    'nama_menu' => 'Kepala Sekolah',
                    'icon'      => 'bi bi-person-workspace',
                    'url'       => '#',
                    'urutan'    => 12,
                    'parent_id' => null,
                    'is_active' => true,
                ]);
            } else {
                DB::table('core.menus')->insert([
                    'id'        => $parentId,
                    'nama_menu' => 'Kepala Sekolah',
                    'icon'      => 'bi bi-person-workspace',
                    'url'       => '#',
                    'urutan'    => 12,
                    'parent_id' => null,
                    'is_active' => true,
                ]);
            }

            // Tambahkan Submenu 1: Pembinaan & Supervisi
            $submenu1Id = Str::uuid()->toString();
            DB::table('core.menus')->insert([
                'id'        => $submenu1Id,
                'nama_menu' => 'Pembinaan & Supervisi',
                'icon'      => 'bi bi-journal-check',
                'url'       => '/pembinaan',
                'urutan'    => 1,
                'parent_id' => $parentId,
                'is_active' => true,
            ]);

            // Tambahkan Submenu 2: Survei Guru
            $submenu2Id = Str::uuid()->toString();
            DB::table('core.menus')->insert([
                'id'        => $submenu2Id,
                'nama_menu' => 'Survei Guru',
                'icon'      => 'bi bi-ui-checks-grid',
                'url'       => '/kepala-sekolah/survei-guru',
                'urutan'    => 2,
                'parent_id' => $parentId,
                'is_active' => true,
            ]);
        } else {
            // Pastikan kedua submenu tersedia di bawah Kepala Sekolah
            $parentId = $parentMenu->id;

            $hasSub1 = DB::table('core.menus')->where('parent_id', $parentId)->where('url', '/pembinaan')->exists();
            if (!$hasSub1) {
                DB::table('core.menus')->insert([
                    'id'        => Str::uuid()->toString(),
                    'nama_menu' => 'Pembinaan & Supervisi',
                    'icon'      => 'bi bi-journal-check',
                    'url'       => '/pembinaan',
                    'urutan'    => 1,
                    'parent_id' => $parentId,
                    'is_active' => true,
                ]);
            }

            $hasSub2 = DB::table('core.menus')->where('parent_id', $parentId)->where('url', '/kepala-sekolah/survei-guru')->exists();
            if (!$hasSub2) {
                DB::table('core.menus')->insert([
                    'id'        => Str::uuid()->toString(),
                    'nama_menu' => 'Survei Guru',
                    'icon'      => 'bi bi-ui-checks-grid',
                    'url'       => '/kepala-sekolah/survei-guru',
                    'urutan'    => 2,
                    'parent_id' => $parentId,
                    'is_active' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kepegawaian.survei_guru_respon');
        Schema::dropIfExists('kepegawaian.survei_guru');
        Schema::dropIfExists('kepegawaian.supervisi_pembinaan');
    }
};
