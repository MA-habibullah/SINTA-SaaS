-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema PERSURATAN
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── persuratan.kop_surat ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.kop_surat;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_instansi_atas, t.nama_sekolah, t.akreditasi, t.npsn
FROM persuratan.kop_surat t
WHERE t.tenant_id = $1;

-- ── persuratan.kode_klasifikasi_surat ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.kode_klasifikasi_surat;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.parent_id, t.kode_klasifikasi, t.nama_klasifikasi, t.level_klasifikasi
FROM persuratan.kode_klasifikasi_surat t
WHERE t.tenant_id = $1;

-- ── persuratan.jenis_surat ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.jenis_surat;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kode_jenis, t.nama_jenis, t.singkatan, t.penomoran_format
FROM persuratan.jenis_surat t
WHERE t.tenant_id = $1;

-- ── persuratan.template_surat ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.template_surat;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.jenis_surat_id, t.nama_template, t.subjek_default, t.konten_html_template
FROM persuratan.template_surat t
WHERE t.tenant_id = $1;

-- ── persuratan.surat_masuk ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.surat_masuk;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.no_agenda_masuk, t.nomor_surat_asal, t.tgl_surat_asal, t.tgl_diterima
FROM persuratan.surat_masuk t
WHERE t.tenant_id = $1;

-- ── persuratan.surat_keluar ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.surat_keluar;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.jenis_surat_id, t.kode_klasifikasi_id, t.metode_pembuatan, t.nomor_surat
FROM persuratan.surat_keluar t
WHERE t.tenant_id = $1;

-- ── persuratan.disposisi_surat ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.disposisi_surat;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.surat_masuk_id, t.pemberi_disposisi_id, t.penerima_disposisi_id, t.tgl_disposisi
FROM persuratan.disposisi_surat t
WHERE t.tenant_id = $1;

-- ── persuratan.riwayat_paraf_persetujuan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.riwayat_paraf_persetujuan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.surat_keluar_id, t.pemeriksa_user_id, t.urutan_paraf, t.status_paraf
FROM persuratan.riwayat_paraf_persetujuan t
WHERE t.tenant_id = $1;

-- ── persuratan.tte_qr_validation ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM persuratan.tte_qr_validation;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.surat_keluar_id, t.token_verifikasi, t.qr_code_image_url, t.penandatangan_nama
FROM persuratan.tte_qr_validation t
WHERE t.tenant_id = $1;
