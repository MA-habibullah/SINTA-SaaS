-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema AKADEMIK
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── akademik.tahun_ajaran ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.tahun_ajaran;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_ajaran, t.is_active, t.created_at, t.updated_at
FROM akademik.tahun_ajaran t
WHERE t.tenant_id = $1;

-- ── akademik.angkatan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.angkatan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_angkatan, t.is_active, t.created_at, t.updated_at
FROM akademik.angkatan t
WHERE t.tenant_id = $1;

-- ── akademik.pendidikan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.pendidikan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kode_pendidikan, t.nama_pendidikan, t.is_active, t.created_at
FROM akademik.pendidikan t
WHERE t.tenant_id = $1;

-- ── akademik.ref_kurikulum ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.ref_kurikulum;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_kurikulum, t.tipe_penilaian, t.is_active, t.created_at
FROM akademik.ref_kurikulum t
WHERE t.tenant_id = $1;

-- ── akademik.jurusan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.jurusan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kode_jurusan, t.nama_jurusan, t.is_active, t.created_at
FROM akademik.jurusan t
WHERE t.tenant_id = $1;

-- ── akademik.kelas ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.kelas;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.id_jenjang, t.id_jurusan, t.kode_kelas, t.nama_kelas
FROM akademik.kelas t
WHERE t.tenant_id = $1;

-- ── akademik.kelas_kurikulum ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.kelas_kurikulum;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kelas_id, t.tahun_ajaran, t.kurikulum_id, t.created_at
FROM akademik.kelas_kurikulum t
WHERE t.tenant_id = $1;

-- ── akademik.mata_pelajaran ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.mata_pelajaran;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kode_mapel, t.nama_mapel, t.is_active, t.created_at
FROM akademik.mata_pelajaran t
WHERE t.tenant_id = $1;

-- ── akademik.pemetaan_mapel ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.pemetaan_mapel;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_ajaran, t.semester, t.kelas_id, t.kelompok_id
FROM akademik.pemetaan_mapel t
WHERE t.tenant_id = $1;

-- ── akademik.kunci_akademik ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.kunci_akademik;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_ajaran, t.semester, t.is_locked_kurikulum, t.is_locked_nilai
FROM akademik.kunci_akademik t
WHERE t.tenant_id = $1;

-- ── akademik.detail_nilai_rapor ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.detail_nilai_rapor;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.kelas_id, t.tahun_ajaran, t.semester
FROM akademik.detail_nilai_rapor t
WHERE t.tenant_id = $1;

-- ── akademik.log_nilai_rapor ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.log_nilai_rapor;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.user_id, t.siswa_id, t.mapel_id, t.semester
FROM akademik.log_nilai_rapor t
WHERE t.tenant_id = $1;

-- ── akademik.nilai_sikap_k13 ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.nilai_sikap_k13;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tahun_ajaran, t.semester, t.predikat_spiritual
FROM akademik.nilai_sikap_k13 t
WHERE t.tenant_id = $1;

-- ── akademik.nilai_p5 ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.nilai_p5;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tahun_ajaran_id, t.semester, t.nama_projek
FROM akademik.nilai_p5 t
WHERE t.tenant_id = $1;

-- ── akademik.nilai_ujian_sekolah ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.nilai_ujian_sekolah;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.mata_pelajaran_id, t.nilai_ujian, t.tahun_ajaran
FROM akademik.nilai_ujian_sekolah t
WHERE t.tenant_id = $1;

-- ── akademik.catatan_wali_kelas ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.catatan_wali_kelas;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tahun_ajaran_id, t.semester, t.catatan
FROM akademik.catatan_wali_kelas t
WHERE t.tenant_id = $1;

-- ── akademik.semester ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.semester;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_ajaran_id, t.nama_semester, t.semester, t.tgl_mulai
FROM akademik.semester t
WHERE t.tenant_id = $1;

-- ── akademik.penugasan_mengajar ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.penugasan_mengajar;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.semester_id, t.guru_ptk_id, t.rombongan_belajar_id, t.mata_pelajaran_id
FROM akademik.penugasan_mengajar t
WHERE t.tenant_id = $1;

-- ── akademik.nilai_sumatif ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.nilai_sumatif;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.semester_id, t.rombongan_belajar_id, t.mata_pelajaran_id, t.siswa_id
FROM akademik.nilai_sumatif t
WHERE t.tenant_id = $1;

-- ── akademik.target_capaian_tp ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM akademik.target_capaian_tp;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.mata_pelajaran_id, t.tingkat_kelas, t.kode_tp, t.deskripsi_capaian_tp
FROM akademik.target_capaian_tp t
WHERE t.tenant_id = $1;
