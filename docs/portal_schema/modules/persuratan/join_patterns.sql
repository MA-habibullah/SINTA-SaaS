-- ════════════════════════════════════════════════════
-- JOIN PATTERNS: Schema PERSURATAN
-- Panduan JOIN yang benar berdasarkan FK Constraint
-- ════════════════════════════════════════════════════

-- ── Tabel: persuratan.kop_surat ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.kop_surat
SELECT
    t.id,
    t.tenant_id,
    t.nama_instansi_atas,
    t.nama_sekolah,
    t.akreditasi,
    t.npsn,
    t.nss,
    t.alamat_lengkap
FROM persuratan.kop_surat t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: persuratan.kode_klasifikasi_surat ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.kode_klasifikasi_surat
SELECT
    t.id,
    t.tenant_id,
    t.parent_id,
    t.kode_klasifikasi,
    t.nama_klasifikasi,
    t.level_klasifikasi,
    t.kategori_utama,
    t.deskripsi
FROM persuratan.kode_klasifikasi_surat t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN persuratan.kode_klasifikasi_surat kode ON t.parent_id = kode.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.tenant_id DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: persuratan.jenis_surat ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.jenis_surat
SELECT
    t.id,
    t.tenant_id,
    t.kode_jenis,
    t.nama_jenis,
    t.singkatan,
    t.penomoran_format,
    t.butuh_disposisi,
    t.is_active
FROM persuratan.jenis_surat t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.tenant_id DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: persuratan.template_surat ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.template_surat
SELECT
    t.id,
    t.tenant_id,
    t.jenis_surat_id,
    t.nama_template,
    t.subjek_default,
    t.konten_html_template,
    t.orientasi_kertas,
    t.ukuran_kertas
FROM persuratan.template_surat t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN persuratan.jenis_surat jeni ON t.jenis_surat_id = jeni.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.tenant_id DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: persuratan.surat_masuk ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.surat_masuk
SELECT
    t.id,
    t.tenant_id,
    t.no_agenda_masuk,
    t.nomor_surat_asal,
    t.tgl_surat_asal,
    t.tgl_diterima,
    t.pengirim_instansi,
    t.pengirim_nama
FROM persuratan.surat_masuk t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN core.users user ON t.penerima_tujuan_id = user.id
INNER JOIN core.users user ON t.pencatat_user_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: persuratan.surat_keluar ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.surat_keluar
SELECT
    t.id,
    t.tenant_id,
    t.jenis_surat_id,
    t.kode_klasifikasi_id,
    t.metode_pembuatan,
    t.nomor_surat,
    t.no_agenda_keluar,
    t.tgl_surat
FROM persuratan.surat_keluar t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN persuratan.jenis_surat jeni ON t.jenis_surat_id = jeni.id
INNER JOIN persuratan.kode_klasifikasi_surat kode ON t.kode_klasifikasi_id = kode.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: persuratan.disposisi_surat ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.disposisi_surat
SELECT
    t.id,
    t.tenant_id,
    t.surat_masuk_id,
    t.pemberi_disposisi_id,
    t.penerima_disposisi_id,
    t.tgl_disposisi,
    t.batas_waktu_selesai,
    t.sifat_instruksi
FROM persuratan.disposisi_surat t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN persuratan.surat_masuk sura ON t.surat_masuk_id = sura.id
INNER JOIN core.users user ON t.pemberi_disposisi_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.tenant_id DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: persuratan.riwayat_paraf_persetujuan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.riwayat_paraf_persetujuan
SELECT
    t.id,
    t.tenant_id,
    t.surat_keluar_id,
    t.pemeriksa_user_id,
    t.urutan_paraf,
    t.status_paraf,
    t.catatan_revisi,
    t.tgl_paraf
FROM persuratan.riwayat_paraf_persetujuan t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN persuratan.surat_keluar sura ON t.surat_keluar_id = sura.id
INNER JOIN core.users user ON t.pemeriksa_user_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.tenant_id DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: persuratan.tte_qr_validation ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: persuratan.tte_qr_validation
SELECT
    t.id,
    t.tenant_id,
    t.surat_keluar_id,
    t.token_verifikasi,
    t.qr_code_image_url,
    t.penandatangan_nama,
    t.penandatangan_nip,
    t.waktu_ttd
FROM persuratan.tte_qr_validation t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN persuratan.surat_keluar sura ON t.surat_keluar_id = sura.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.tenant_id DESC
LIMIT 50 OFFSET 0;
