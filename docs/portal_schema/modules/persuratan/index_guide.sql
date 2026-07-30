-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema PERSURATAN
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── persuratan.kop_surat ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_kop_surat_tenant ON persuratan.kop_surat (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_kop_surat_created ON persuratan.kop_surat (created_at DESC);

-- ── persuratan.kode_klasifikasi_surat ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_kode_klasifikasi_surat_tenant ON persuratan.kode_klasifikasi_surat (tenant_id);

-- ✅ FK JOIN index: parent_id
CREATE INDEX IF NOT EXISTS idx_kode_klasifikasi_surat_parent_id ON persuratan.kode_klasifikasi_surat (parent_id);

-- ── persuratan.jenis_surat ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_jenis_surat_tenant ON persuratan.jenis_surat (tenant_id);

-- ── persuratan.template_surat ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_template_surat_tenant ON persuratan.template_surat (tenant_id);

-- ✅ FK JOIN index: jenis_surat_id
CREATE INDEX IF NOT EXISTS idx_template_surat_jenis_surat_id ON persuratan.template_surat (jenis_surat_id);

-- ── persuratan.surat_masuk ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_surat_masuk_tenant ON persuratan.surat_masuk (tenant_id);

-- ✅ FK JOIN index: penerima_tujuan_id
CREATE INDEX IF NOT EXISTS idx_surat_masuk_penerima_tujuan_id ON persuratan.surat_masuk (penerima_tujuan_id);

-- ✅ FK JOIN index: pencatat_user_id
CREATE INDEX IF NOT EXISTS idx_surat_masuk_pencatat_user_id ON persuratan.surat_masuk (pencatat_user_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_surat_masuk_created ON persuratan.surat_masuk (created_at DESC);

-- ── persuratan.surat_keluar ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_surat_keluar_tenant ON persuratan.surat_keluar (tenant_id);

-- ✅ FK JOIN index: jenis_surat_id
CREATE INDEX IF NOT EXISTS idx_surat_keluar_jenis_surat_id ON persuratan.surat_keluar (jenis_surat_id);

-- ✅ FK JOIN index: kode_klasifikasi_id
CREATE INDEX IF NOT EXISTS idx_surat_keluar_kode_klasifikasi_id ON persuratan.surat_keluar (kode_klasifikasi_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_surat_keluar_created ON persuratan.surat_keluar (created_at DESC);

-- ── persuratan.disposisi_surat ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_disposisi_surat_tenant ON persuratan.disposisi_surat (tenant_id);

-- ✅ FK JOIN index: surat_masuk_id
CREATE INDEX IF NOT EXISTS idx_disposisi_surat_surat_masuk_id ON persuratan.disposisi_surat (surat_masuk_id);

-- ✅ FK JOIN index: pemberi_disposisi_id
CREATE INDEX IF NOT EXISTS idx_disposisi_surat_pemberi_disposisi_id ON persuratan.disposisi_surat (pemberi_disposisi_id);

-- ── persuratan.riwayat_paraf_persetujuan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_paraf_persetujuan_tenant ON persuratan.riwayat_paraf_persetujuan (tenant_id);

-- ✅ FK JOIN index: surat_keluar_id
CREATE INDEX IF NOT EXISTS idx_riwayat_paraf_persetujuan_surat_keluar_id ON persuratan.riwayat_paraf_persetujuan (surat_keluar_id);

-- ✅ FK JOIN index: pemeriksa_user_id
CREATE INDEX IF NOT EXISTS idx_riwayat_paraf_persetujuan_pemeriksa_user_id ON persuratan.riwayat_paraf_persetujuan (pemeriksa_user_id);

-- ── persuratan.tte_qr_validation ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tte_qr_validation_tenant ON persuratan.tte_qr_validation (tenant_id);

-- ✅ FK JOIN index: surat_keluar_id
CREATE INDEX IF NOT EXISTS idx_tte_qr_validation_surat_keluar_id ON persuratan.tte_qr_validation (surat_keluar_id);
