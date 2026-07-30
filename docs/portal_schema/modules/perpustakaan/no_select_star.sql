-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema PERPUSTAKAAN
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── perpustakaan.perpus_bibliografi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_bibliografi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.isbn, t.judul, t.judul_seri, t.edisi
FROM perpustakaan.perpus_bibliografi t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_eksemplar ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_eksemplar;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.bibliografi_id, t.barcode, t.nomor_induk, t.tanggal_masuk
FROM perpustakaan.perpus_eksemplar t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_sirkulasi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_sirkulasi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.no_transaksi, t.anggota_id, t.eksemplar_id, t.pustakawan_id
FROM perpustakaan.perpus_sirkulasi t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_anggota ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_anggota;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tipe_anggota, t.user_id, t.siswa_id, t.nisn
FROM perpustakaan.perpus_anggota t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_denda ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_denda;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.sumber_transaksi, t.sirkulasi_id, t.distribusi_id, t.event_detail_id
FROM perpustakaan.perpus_denda t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_reservasi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_reservasi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.bibliografi_id, t.anggota_id, t.tanggal_reservasi, t.tanggal_kadaluarsa
FROM perpustakaan.perpus_reservasi t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_opname ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_opname;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_sesi, t.tanggal_mulai, t.tanggal_selesai, t.status
FROM perpustakaan.perpus_opname t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_kategori_ddc ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_kategori_ddc;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.kode, t.nama, t.induk_kode, t.tingkat
FROM perpustakaan.perpus_kategori_ddc t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_lokasi_rak ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_lokasi_rak;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kode, t.nama, t.gedung, t.lantai
FROM perpustakaan.perpus_lokasi_rak t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_paket_buku ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_paket_buku;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_paket, t.kelas_id, t.jenjang, t.jurusan
FROM perpustakaan.perpus_paket_buku t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_event ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_event;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_event, t.penyelenggara, t.tgl_mulai, t.tgl_selesai
FROM perpustakaan.perpus_event t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_serial_berkala ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_serial_berkala;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_media, t.jenis, t.frekuensi, t.issn
FROM perpustakaan.perpus_serial_berkala t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_staf_kompetensi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_staf_kompetensi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_staf, t.jabatan, t.nama_kegiatan, t.penyelenggara
FROM perpustakaan.perpus_staf_kompetensi t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_notifikasi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_notifikasi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.anggota_id, t.tipe, t.pesan, t.referensi_id
FROM perpustakaan.perpus_notifikasi t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_buku_tamu ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_buku_tamu;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nisn, t.nama_pengunjung, t.kelas, t.tujuan
FROM perpustakaan.perpus_buku_tamu t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_ulasan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_ulasan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.bibliografi_id, t.anggota_id, t.rating, t.ulasan
FROM perpustakaan.perpus_ulasan t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_usulan_buku ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_usulan_buku;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.judul, t.pengarang, t.penerbit, t.pengusul_nama
FROM perpustakaan.perpus_usulan_buku t
WHERE t.tenant_id = $1;

-- ── perpustakaan.perpus_pengaturan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM perpustakaan.perpus_pengaturan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_perpustakaan, t.nomor_pokok, t.kepala_perpustakaan, t.nip_kepala
FROM perpustakaan.perpus_pengaturan t
WHERE t.tenant_id = $1;
