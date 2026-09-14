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
        // 1. Buat Tabel kepegawaian.survei_guru_pertanyaan jika belum ada
        if (!Schema::hasTable('kepegawaian.survei_guru_pertanyaan')) {
            Schema::create('kepegawaian.survei_guru_pertanyaan', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('survei_id')->nullable()->index();
                $table->string('dimensi', 50)->default('Pedagogik'); // 'Pedagogik', 'Kepribadian & Sosial'
                $table->integer('nomor_urut')->default(1);
                $table->text('pertanyaan');
                $table->string('tipe_skala', 20)->default('likert_4'); // 'likert_4', 'teks_terbuka'
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tambahkan kolom pendukung di kepegawaian.survei_guru_respon
        Schema::table('kepegawaian.survei_guru_respon', function (Blueprint $table) {
            if (!Schema::hasColumn('kepegawaian.survei_guru_respon', 'kelas_id')) {
                $table->uuid('kelas_id')->nullable()->index();
            }
            if (!Schema::hasColumn('kepegawaian.survei_guru_respon', 'nama_kelas')) {
                $table->string('nama_kelas', 50)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.survei_guru_respon', 'mapel_id')) {
                $table->uuid('mapel_id')->nullable()->index();
            }
            if (!Schema::hasColumn('kepegawaian.survei_guru_respon', 'nama_mapel')) {
                $table->string('nama_mapel', 100)->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.survei_guru_respon', 'is_anonim')) {
                $table->boolean('is_anonim')->default(true);
            }
            if (!Schema::hasColumn('kepegawaian.survei_guru_respon', 'skor_detail_json')) {
                $table->jsonb('skor_detail_json')->nullable();
            }
            if (!Schema::hasColumn('kepegawaian.survei_guru_respon', 'skor_sosial_kepribadian')) {
                $table->decimal('skor_sosial_kepribadian', 3, 2)->default(3.50);
            }
            if (!Schema::hasColumn('kepegawaian.survei_guru_respon', 'predikat_kategori')) {
                $table->string('predikat_kategori', 50)->default('Sangat Baik'); // Sangat Baik (3.5-4.0), Baik (2.8-3.4), Perlu Pembinaan (<2.8)
            }
        });

        // 3. Seed 10 Indikator Pertanyaan Standar Skala Likert 4 Poin untuk Semua Tenant
        $tenants = DB::table('core.tenants')->pluck('id');
        $standardQuestions = [
            // Dimensi I: Penyampaian Materi & Cara Mengajar (Pedagogik)
            [1, 'Pedagogik', 'Guru menjelaskan tujuan pembelajaran atau materi yang akan dipelajari di awal kelas.'],
            [2, 'Pedagogik', 'Guru menyampaikan materi dengan jelas dan menggunakan bahasa yang mudah saya pahami.'],
            [3, 'Pedagogik', 'Guru menggunakan media pembelajaran yang bervariasi (slide, video, alat praktikum, atau aplikasi belajar).'],
            [4, 'Pedagogik', 'Guru memberikan kesempatan kepada siswa untuk bertanya atau berdiskusi.'],
            [5, 'Pedagogik', 'Guru memberikan umpan balik (feedback) atau mengembalikan hasil tugas/ujian yang telah diperiksa.'],
            
            // Dimensi II: Interaksi & Suasana Kelas (Kepribadian & Sosial)
            [6, 'Kepribadian & Sosial', 'Guru mengawali dan mengakhiri pembelajaran tepat waktu sesuai jadwal.'],
            [7, 'Kepribadian & Sosial', 'Guru bersikap adil dan tidak membeda-bedakan perlakuan kepada setiap siswa di kelas.'],
            [8, 'Kepribadian & Sosial', 'Guru menghargai pendapat, ide, atau jawaban dari siswa meskipun belum tepat.'],
            [9, 'Kepribadian & Sosial', 'Guru menciptakan suasana kelas yang aman, tertib, dan bebas dari ejekan/perundungan.'],
            [10, 'Kepribadian & Sosial', 'Guru mudah dihubungi atau diajak berkonsultasi jika siswa mengalami kesulitan belajar.'],
        ];

        foreach ($tenants as $tId) {
            // Cek kuesioner aktif default untuk tenant ini
            $survei = DB::table('kepegawaian.survei_guru')->where('tenant_id', $tId)->where('status', 'Aktif')->first();
            $surveiId = $survei ? $survei->id : Str::uuid()->toString();

            if (!$survei) {
                DB::table('kepegawaian.survei_guru')->insert([
                    'id'              => $surveiId,
                    'tenant_id'       => $tId,
                    'judul_survei'    => 'Survei Evaluasi Kinerja Guru oleh Siswa Semester Ganjil 2026/2027',
                    'deskripsi'       => 'Instrumen evaluasi kinerja mengajar, pedagogik, dan interaksi kelas oleh siswa secara berkala dan anonim.',
                    'sasaran_survei'  => 'Asesmen Siswa',
                    'tahun_ajaran'    => '2026/2027',
                    'semester'        => 'Ganjil',
                    'tanggal_mulai'   => now()->toDateString(),
                    'status'          => 'Aktif',
                    'is_active'       => true,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            foreach ($standardQuestions as [$nomor, $dimensi, $teks]) {
                $exists = DB::table('kepegawaian.survei_guru_pertanyaan')
                    ->where('tenant_id', $tId)
                    ->where('nomor_urut', $nomor)
                    ->exists();

                if (!$exists) {
                    DB::table('kepegawaian.survei_guru_pertanyaan')->insert([
                        'id'          => Str::uuid()->toString(),
                        'tenant_id'   => $tId,
                        'survei_id'   => $surveiId,
                        'dimensi'     => $dimensi,
                        'nomor_urut'  => $nomor,
                        'pertanyaan'  => $teks,
                        'tipe_skala'  => 'likert_4',
                        'is_active'   => true,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kepegawaian.survei_guru_pertanyaan');
    }
};
