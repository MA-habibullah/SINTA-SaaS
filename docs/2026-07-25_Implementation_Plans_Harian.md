---
## [Konsolidasi Halaman Master Keuangan & SPP Berbasis Navtabs]
**Waktu**: 19:58 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Konsolidasi Halaman Master Keuangan & SPP Berbasis Navtabs

Menggabungkan empat menu manajemen keuangan (Atur Tarif & Biaya, Keringanan & Beasiswa, Generate Tagihan, dan Pengaturan Keuangan) menjadi satu halaman terpadu di bawah menu **Master Keuangan** menggunakan layout multi-tab (navtabs).

## User Review Required

> [!IMPORTANT]
> - **Perubahan Menu Sidebar:** Menu-menu dengan ID 73 (Keringanan & Beasiswa), 74 (Generate Tagihan), dan 77 (Pengaturan Keuangan) akan dinonaktifkan/dihapus dari database sidebar agar antarmuka menjadi rapi dan fokus pada satu menu Master Keuangan.
> - **Redirect URL Otomatis:** Akses manual ke URL lama (`/keuangan/keringanan`, `/keuangan/generate`, `/keuangan/pengaturan`) akan secara otomatis diarahkan (HTTP 302 Redirect) ke halaman master terpadu `/keuangan/master` demi mencegah error 404 bagi pengguna yang menyimpan bookmark URL lama.

## Open Questions
*Tidak ada.* Semua persyaratan fungsionalitas dan pemetaan tab telah didefinisikan dengan jelas oleh pengguna.

## Proposed Changes

### Database Migration

#### [NEW] [2026_07_25_00_consolidate_keuangan_master_menu.php](file:///c:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_25_00_consolidate_keuangan_master_menu.php)
Membuat file migrasi untuk memperbarui nama menu ID 72 menjadi `Master Keuangan` dan menghapus entri menu ID 73, 74, dan 77 dari database.

```php
<?php
/**
 * Migration: Konsolidasi Menu Keuangan & SPP ke Master Keuangan Berbasis Navtabs
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Rename menu ID 72 menjadi Master Keuangan
        $stmtUpdate = $pdo->prepare("UPDATE `menus` SET `nama_menu` = 'Master Keuangan', `icon` = 'bi bi-tags' WHERE `id` = 72");
        $stmtUpdate->execute();

        // 2. Hapus data akses menu lama (73, 74, 77) agar tidak muncul di sidebar
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` IN (73, 74, 77)");
        $pdo->exec("DELETE FROM `tenant_menu_access` WHERE `menu_id` IN (73, 74, 77)");
        $pdo->exec("DELETE FROM `menus` WHERE `id` IN (73, 74, 77)");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Menu keuangan berhasil dikonsolidasikan ke Master Keuangan (ID 72).\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Kembalikan nama menu 72
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Atur Tarif & Biaya', `icon` = 'bi bi-tags' WHERE `id` = 72");

        // Daftarkan ulang menu 73, 74, 77
        $menus = [
            [73, 'Keringanan & Beasiswa', '/SINTA-SaaS/keuangan/keringanan', 70, 'bi bi-award', 3],
            [74, 'Generate Tagihan', '/SINTA-SaaS/keuangan/generate', 70, 'bi bi-file-earmark-plus', 4],
            [77, 'Pengaturan Keuangan', '/SINTA-SaaS/keuangan/pengaturan', 70, 'bi bi-gear', 7]
        ];
        $stmtMenu = $pdo->prepare("INSERT INTO `menus` (id, nama_menu, url, parent_id, icon, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($menus as $m) {
            try {
                $stmtMenu->execute($m);
            } catch (\PDOException $e) {}
        }

        // Kembalikan akses default untuk role admin, operator, kepala sekolah (1, 2, 26)
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        $tenants[] = '00000000-0000-0000-0000-000000000000';
        $tenants = array_unique($tenants);

        $stmtTMA = $pdo->prepare("INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");
        $stmtRMA = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");

        foreach ($tenants as $tid) {
            foreach ([73, 74, 77] as $mid) {
                try { $stmtTMA->execute([$tid, $mid]); } catch (\PDOException $e) {}
                foreach ([1, 2, 26] as $rid) {
                    try { $stmtRMA->execute([$tid, $rid, $mid]); } catch (\PDOException $e) {}
                }
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Rollback konsolidasi menu selesai.\n";
    },
];
```

---

### Backend Logic (Controller)

#### [MODIFY] [SppController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)
1. Perbarui fungsi `master()` agar memuat `$list_komponen` saat mem-render halaman view.
2. Ubah `keringanan()`, `generate()`, dan `pengaturan()` agar mengalihkan (redirect) langsung ke `/SINTA-SaaS/keuangan/master`.

```diff
     public function master(): void {
         $db = Database::getConnection();
+        $tenantId = $this->resolveTenantId();
         
         $kelas = $db->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
         $jenjang = $db->query("SELECT id, nama_jenjang FROM jenjang ORDER BY nama_jenjang ASC")->fetchAll(PDO::FETCH_ASSOC);
         $tahunAjaran = $db->query("SELECT id, tahun_ajaran, IF(is_active = 1, 'Aktif', 'Non-Aktif') as status FROM tahun_ajaran ORDER BY tahun_ajaran DESC")->fetchAll(PDO::FETCH_ASSOC);
+        
+        $stmtKomp = $db->prepare("SELECT id, nama_komponen, is_active FROM transaksi_spp_komponen WHERE tenant_id = ? ORDER BY nama_komponen ASC");
+        $stmtKomp->execute([$tenantId]);
+        $komponen = $stmtKomp->fetchAll(PDO::FETCH_ASSOC);
 
         $this->render('keuangan/master', [
             'title' => 'Master Tarif & Biaya',
             'list_kelas' => $kelas,
             'list_jenjang' => $jenjang,
-            'list_ta' => $tahunAjaran
+            'list_ta' => $tahunAjaran,
+            'list_komponen' => $komponen
         ]);
     }
 
     public function keringanan(): void {
-        $db = Database::getConnection();
-        $tenantId = $this->resolveTenantId();
-        $stmt = $db->prepare("SELECT id, nama_komponen FROM transaksi_spp_komponen WHERE tenant_id = ? ORDER BY nama_komponen ASC");
-        $stmt->execute([$tenantId]);
-        $komponen = $stmt->fetchAll(PDO::FETCH_ASSOC);
-
-        $this->render('keuangan/keringanan', [
-            'title' => 'Keringanan & Beasiswa',
-            'list_komponen' => $komponen
-        ]);
+        header('Location: /SINTA-SaaS/keuangan/master');
+        exit();
     }
 
     public function generate(): void {
-        $db = Database::getConnection();
-        $tenantId = $this->resolveTenantId();
-        
-        $kelas = $db->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
-        $jenjang = $db->query("SELECT id, nama_jenjang FROM jenjang ORDER BY nama_jenjang ASC")->fetchAll(PDO::FETCH_ASSOC);
-        $tahunAjaran = $db->query("SELECT id, tahun_ajaran, IF(is_active = 1, 'Aktif', 'Non-Aktif') as status FROM tahun_ajaran ORDER BY tahun_ajaran DESC")->fetchAll(PDO::FETCH_ASSOC);
-        
-        $stmt = $db->prepare("SELECT id, nama_komponen, tipe_periode FROM transaksi_spp_komponen WHERE tenant_id = ? AND is_active = 1 ORDER BY nama_komponen ASC");
-        $stmt->execute([$tenantId]);
-        $komponen = $stmt->fetchAll(PDO::FETCH_ASSOC);
-
-        $this->render('keuangan/generate', [
-            'title' => 'Generate Tagihan Massal',
-            'list_kelas' => $kelas,
-            'list_jenjang' => $jenjang,
-            'list_ta' => $tahunAjaran,
-            'list_komponen' => $komponen
-        ]);
+        header('Location: /SINTA-SaaS/keuangan/master');
+        exit();
     }
 
     public function pengaturan(): void {
-        $this->render('keuangan/pengaturan', ['title' => 'Pengaturan Modul Keuangan']);
+        header('Location: /SINTA-SaaS/keuangan/master');
+        exit();
     }
```

---

### User Interface Consolidation (Views)

#### [MODIFY] [master.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php)
Menggabungkan seluruh template HTML, CSS styling, dan skrip inisialisasi Vue 3 ke dalam tab terpadu di file master.
Tabs yang akan didaftarkan:
1. **Komponen Biaya** (Tab lama)
2. **Tarif Acuan Default** (Tab lama)
3. **Keringanan dan Beasiswa** (Migrasi dari keringanan.php)
4. **Terbit Tagihan** (Migrasi dari generate.php - Tab 1)
5. **Daftar Tagihan Siswa** (Migrasi dari generate.php - Tab 2)
6. **Pengaturan Keuangan** (Migrasi dari pengaturan.php)

*(Seluruh logika JavaScript Vue akan digabungkan secara hati-hati di bawah satu setup() Vue app `#keuangan-master-app`.)*

---

## Verification Plan

### Automated Tests
1. **PHPStan Static Analysis:**
   ```powershell
   vendor/bin/phpstan analyse app/Controllers/SppController.php --level=5
   ```
   Memastikan tidak ada error sintaksis setelah perubahan metode di controller.
2. **Automated Security Audit Script:**
   ```powershell
   php scratch/tests/test_security_audit.php
   ```
   Memastikan kode baru aman dari celah XSS/SQL Injection.

### Manual Verification
1. Jalankan migrasi baru menggunakan:
   ```powershell
   php migrate.php up
   ```
2. Akses halaman `/SINTA-SaaS/keuangan/master` di web browser dan pastikan:
   * 6 buah navtabs terdaftar dan dapat diklik dengan transisi mulus.
   * Komponen Biaya dan Tarif Acuan tetap berfungsi normal.
   * Autocomplete siswa di tab "Keringanan dan Beasiswa" berfungsi dan nilai potongan terdeteksi.
   * Generate Tagihan & Preview sasaran tagihan siswa terisi saat Komponen & Tahun Ajaran dipilih.
   * Filter, pencarian, dan tombol ekspor excel di tab "Daftar Tagihan" berfungsi normal.
   * Tombol "Simpan Perubahan" di tab "Pengaturan Keuangan" berfungsi memperbarui nama modul di database.
   * Akses ke URL lama (`/keuangan/generate`, dll.) dialihkan dengan benar ke master.

---
## [Custom Navtabs Keuangan Seperti Buku Induk]
**Waktu**: 20:04 WIB
**Status**: Dieksekusi

Mengubah visualisasi navtabs Master Keuangan agar terlihat persis seperti layout tab modern di halaman Buku Induk (`views/buku_induk.php`).

### Proposed Changes

#### [MODIFY] [master.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php)
1. Pembungkusan list navtabs ke dalam `.card border-0 shadow-sm rounded-4 mb-4` dan `.nav-tabs-wrapper`.
2. Penggunaan kelas `.scrollable-nav-tabs` pada list `<ul>` dan `.border-0 fw-semibold px-3 py-2.5 fs-7 transition` pada setiap `<button>`.
3. Penambahan styling CSS khusus di block `<style>` untuk menyesuaikan track scrollbar, warna, font weight, dan penanda `.active` horizontal line berwarna biru (`#2563eb`).

---
## [Konsolidasi & Profesionalisasi Modul Perpustakaan Digital]
**Waktu**: 20:15 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Konsolidasi & Profesionalisasi Modul Perpustakaan Digital (ILS)

Menyederhanakan menu sidebar Perpustakaan yang sebelumnya memiliki 9 sub-menu menjadi **3 menu utama terklasifikasi** berbasis layout multi-tab (navtabs) modern seperti Buku Induk. Serta menambahkan fitur profesional pengadaan buku (Usulan Buku) dan Buku Tamu/Pengunjung Digital untuk kebutuhan pustakawan.

## User Review Required

> [!IMPORTANT]
> - **Penyederhanaan Menu Sidebar Perpustakaan:**
>   * Menu `81`: `Katalog & Koleksi` diubah namanya menjadi **Katalog & Inventori** (Menyatukan Katalog, Stock Opname, Lokasi Rak, Kategori DDC, dan Usulan Buku).
>   * Menu `82`: `Sirkulasi Reguler` diubah namanya menjadi **Sirkulasi & Layanan** (Menyatukan Sirkulasi Reguler, Buku Paket Pelajaran, Event Khusus, dan Denda & Billing).
>   * Menu `85`: `Keanggotaan & Bebas Pustaka` diubah namanya menjadi **Administrasi & Keanggotaan** (Menyatukan Keanggotaan, Verifikasi Bebas Pustaka, Buku Tamu/Pengunjung, Laporan Perpustakaan, dan Pengaturan).
>   * Menu `83`, `84`, `86`, `87`, `88`, `89` dinonaktifkan/dihapus dari sidebar database.
> - **Pengadaan Tabel Usulan Buku:**
>   Akan dibuat tabel baru `perpus_usulan_buku` di database untuk merekam usulan buku baru dari siswa/guru.

## Proposed Changes

### Database Migration

#### [NEW] [2026_07_25_01_consolidate_and_expand_perpustakaan_menus.php](file:///c:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_25_01_consolidate_and_expand_perpustakaan_menus.php)
1. Memperbarui nama dan ikon menu ID `81`, `82`, dan `85`.
2. Menghapus pemetaan menu redundan (`83`, `84`, `86`, `87`, `88`, `89`) agar sidebar rapi.
3. Membuat tabel `perpus_usulan_buku` untuk menyimpan usulan pengadaan buku baru.

```php
<?php
/**
 * Migration: Konsolidasi Menu Perpustakaan & Pembuatan Tabel Usulan Buku
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Rename & Re-icon menu utama perpustakaan
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Katalog & Inventori', `icon` = 'bi bi-journal-album', `urutan` = 1 WHERE `id` = 81");
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Sirkulasi & Layanan', `icon` = 'bi bi-arrow-repeat', `urutan` = 2 WHERE `id` = 82");
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Administrasi & Keanggotaan', `icon` = 'bi bi-people-fill', `urutan` = 3 WHERE `id` = 85");

        // 2. Hapus menu redundan
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` IN (83, 84, 86, 87, 88, 89)");
        $pdo->exec("DELETE FROM `tenant_menu_access` WHERE `menu_id` IN (83, 84, 86, 87, 88, 89)");
        $pdo->exec("DELETE FROM `menus` WHERE `id` IN (83, 84, 86, 87, 88, 89)");

        // 3. Buat tabel perpus_usulan_buku
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_usulan_buku` (
            `id`             CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`      CHAR(36) NOT NULL,
            `judul`          VARCHAR(255) NOT NULL,
            `pengarang`      VARCHAR(255) DEFAULT NULL,
            `penerbit`       VARCHAR(255) DEFAULT NULL,
            `pengusul_nama`  VARCHAR(255) NOT NULL,
            `tanggal_usulan` DATE NOT NULL,
            `status`         ENUM('Diajukan','Disetujui','Ditolak','Sudah Dibeli') DEFAULT 'Diajukan',
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Konsolidasi menu perpustakaan & tabel usulan buku berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Hapus tabel usulan buku
        $pdo->exec("DROP TABLE IF EXISTS `perpus_usulan_buku`;");

        // Kembalikan nama menu
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Katalog & Koleksi', `icon` = 'bi bi-book', `urutan` = 1 WHERE `id` = 81");
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Sirkulasi Reguler', `icon` = 'bi bi-arrow-repeat', `urutan` = 2 WHERE `id` = 82");
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Keanggotaan & Bebas Pustaka', `icon` = 'bi bi-people', `urutan` = 5 WHERE `id` = 85");

        // Daftar ulang menu redundan
        $menus = [
            [83, 'Buku Paket', '/SINTA-SaaS/perpustakaan/buku-paket', 'bi bi-box-seam', 80, 3],
            [84, 'Event Khusus', '/SINTA-SaaS/perpustakaan/event', 'bi bi-trophy', 80, 4],
            [86, 'Denda & Billing', '/SINTA-SaaS/perpustakaan/denda', 'bi bi-cash-coin', 80, 6],
            [87, 'Stock Opname', '/SINTA-SaaS/perpustakaan/opname', 'bi bi-qr-code-scan', 80, 7],
            [88, 'Laporan Perpustakaan', '/SINTA-SaaS/perpustakaan/laporan', 'bi bi-file-earmark-bar-graph', 80, 8],
            [89, 'Pengaturan Perpustakaan', '/SINTA-SaaS/perpustakaan/pengaturan', 'bi bi-gear', 80, 9]
        ];

        $stmtMenu = $pdo->prepare("INSERT INTO `menus` (id, nama_menu, url, icon, parent_id, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($menus as $m) {
            try {
                $stmtMenu->execute($m);
            } catch (\PDOException $e) {}
        }

        // Kembalikan hak akses
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        $tenants[] = '00000000-0000-0000-0000-000000000000';
        $tenants = array_unique($tenants);
        $roles = $pdo->query("SELECT id, nama_role FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmtTMA = $pdo->prepare("INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");
        $stmtRMA = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");

        foreach ($tenants as $tid) {
            foreach ([83, 84, 86, 87, 88, 89] as $mid) {
                try { $stmtTMA->execute([$tid, $mid]); } catch (\PDOException $e) {}
                foreach ($roles as $rid => $rname) {
                    if (in_array(strtolower((string)$rname), ['super_admin', 'superadmin', 'admin', 'operator_sekolah', 'pustakawan'], true)) {
                        try { $stmtRMA->execute([$tid, $rid, $mid]); } catch (\PDOException $e) {}
                    }
                }
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Rollback konsolidasi menu perpustakaan selesai.\n";
    }
];
```

---

### Backend Logic (Controller & Model)

#### [MODIFY] [PerpustakaanController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PerpustakaanController.php)
1. **Redirect Sub-menu Lama:** Ubah `bukuPaket()`, `eventOSN()`, `denda()`, `opname()`, `laporan()`, dan `pengaturan()` agar mengalihkan (HTTP 302 Redirect) langsung ke rute induk masing-masing:
   * `/SINTA-SaaS/perpustakaan/katalog` (untuk opname)
   * `/SINTA-SaaS/perpustakaan/sirkulasi` (untuk bukuPaket, eventOSN, denda)
   * `/SINTA-SaaS/perpustakaan/anggota` (untuk laporan, pengaturan)
2. **API Endpoint Baru:**
   * `apiGetUsulan()` & `apiSaveUsulan()` & `apiDeleteUsulan()`: Kelola usulan buku baru dari tabel `perpus_usulan_buku`.
   * `apiGetDdcCategories()`: Ambil klasifikasi DDC untuk pustakawan.
   * `apiGetVisitorLogs()`: Ambil logs kunjungan dari `perpus_buku_tamu`.
3. **Penyuntikan Data ke View:**
   * Di `katalog()`: Kirim data rak list, kategori DDC, dan usulan buku.
   * Di `sirkulasi()`: Kirim data sirkulasi, buku paket, event OSN, dan denda.
   * Di `anggota()`: Kirim data anggota, log kunjungan tamu, laporan, dan pengaturan.

#### [MODIFY] [Perpustakaan.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Models/Perpustakaan.php)
* Tambahkan fungsi-fungsi pengelola data DDC, Buku Tamu, dan Usulan Pengadaan Buku:
  * `getUsulanBukuList($tenantId)`
  * `saveUsulanBuku($tenantId, $data)`
  * `deleteUsulanBuku($tenantId, $id)`
  * `getVisitorLogs($tenantId)`
  * `getKategoriDdcList()`

---

### User Interface (Views Consolidation)

#### [MODIFY] [katalog.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/perpustakaan/katalog.php)
Penyusunan ulang berkas `katalog.php` menggunakan layout Navtabs bergaya Buku Induk:
1. **Katalog Buku:** Manajemen bibliografi & koleksi.
2. **Stock Opname:** Audit stok fisik inventaris (dari `opname.php`).
3. **Lokasi Rak:** CRUD direktori rak penataan buku.
4. **Kategori DDC:** Indeks klasifikasi desimal DDC untuk buku perpustakaan.
5. **Usulan Buku [BARU]:** Tabel persetujuan pustakawan terhadap pengadaan buku baru yang diusulkan oleh siswa/guru.

#### [MODIFY] [sirkulasi.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/perpustakaan/sirkulasi.php)
Penyusunan ulang berkas `sirkulasi.php` menggunakan layout Navtabs bergaya Buku Induk:
1. **Sirkulasi Reguler:** Logika peminjaman & pengembalian reguler.
2. **Buku Paket:** Manajemen sirkulasi buku paket pelajaran massal (dari `buku_paket.php`).
3. **Event Khusus:** Peminjaman event olimpiade/OSN (dari `event_osn.php`).
4. **Denda & Billing:** Pengelolaan tunggakan denda sirkulasi (dari `denda.php`).

#### [MODIFY] [anggota.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/perpustakaan/anggota.php)
Penyusunan ulang berkas `anggota.php` menggunakan layout Navtabs bergaya Buku Induk:
1. **Keanggotaan:** Sinkronisasi & manajemen anggota perpustakaan.
2. **Bebas Pustaka [Peningkatan]:** Tab mandiri pencarian kelayakan siswa bebas pustaka lengkap dengan print kuitansi bebas pustaka.
3. **Buku Tamu / Pengunjung [BARU]:** Riwayat log kunjungan harian perpustakaan digital.
4. **Laporan Perpustakaan:** Cetak laporan & akreditasi DDC (dari `laporan.php`).
5. **Pengaturan Perpustakaan:** Batasan hari pinjam & toggle WhatsApp/Email (dari `pengaturan.php`).

---

## Verification Plan

### Automated Tests
1. **PHPStan Static Analysis:**
   ```powershell
   vendor/bin/phpstan analyse app/Controllers/PerpustakaanController.php --level=5
   ```
2. **Automated Security Audit:**
   ```powershell
   php scratch/tests/test_security_audit.php
   ```

---
## [Akreditasi Perpustakaan Sekolah (SNP)]
**Waktu**: 20:45 WIB
**Status**: Draft

# Rencana Implementasi: Pemenuhan Standar Akreditasi (SNP) Modul Perpustakaan

Peningkatan fitur perpustakaan digital SINTA-SaaS agar sepenuhnya siap mendukung evaluasi instrumen **Standar Nasional Perpustakaan (SNP)** oleh Perpustakaan Nasional RI (Perpusnas), mencakup analisis rasio fiksi/non-fiksi, pencatatan surat kabar berkala, dan log diklat kompetensi pustakawan.

## User Review Required

> [!IMPORTANT]
> - **Penambahan Tabel Database Baru:**
>   * `perpus_serial_berkala`: untuk pencatatan surat kabar, majalah, dan berkala.
>   * `perpus_staf_kompetensi`: untuk log sertifikat diklat kompetensi kepala dan staf perpustakaan.
> - **Penyelarasan Tampilan Dashboard & Tab:**
>   * Tab baru **6. Serial & Berkala** di menu **Katalog & Inventori** (`katalog.php`).
>   * Tab baru **6. Kompetensi Pustakawan** di menu **Administrasi & Keanggotaan** (`anggota.php`).
>   * Widget rasio koleksi fiksi/non-fiksi (Standar 60/40) di **Dashboard Perpustakaan** (`dashboard.php`).

## Proposed Changes

### Database Migrations
#### [NEW] [2026_07_25_02_add_perpus_accreditation_tables.php](file:///c:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_25_02_add_perpus_accreditation_tables.php)
* Membuat tabel `perpus_serial_berkala` dan `perpus_staf_kompetensi` dengan foreign key ke `tenants(id)`.

### Backend Components
#### [MODIFY] [Perpustakaan.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Models/Perpustakaan.php)
* Menambahkan method `getAccreditationStats(string $tenantId)`.
* Menambahkan method CRUD untuk `perpus_serial_berkala`.
* Menambahkan method CRUD untuk `perpus_staf_kompetensi`.

#### [MODIFY] [PerpustakaanController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PerpustakaanController.php)
* Memperbarui `dashboard()`, `katalog()`, dan `anggota()` untuk menyuntikkan data pendukung akreditasi.
* Membuat API endpoints `/api/v1/perpustakaan/serial` dan `/api/v1/perpustakaan/kompetensi`.

### Frontend Views
#### [MODIFY] [dashboard.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/perpustakaan/dashboard.php)
* Menambahkan card visual indikator akreditasi rasio fiksi vs non-fiksi (Standar $\ge 60\%$ non-fiksi).
* Memperbarui link navigasi ke sub-menu agar langsung ke tab target.

#### [MODIFY] [katalog.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/perpustakaan/katalog.php)
* Menambahkan tab **6. Serial & Berkala** berisi tabel daftar langganan koran/majalah beserta form CRUD.

#### [MODIFY] [anggota.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/perpustakaan/anggota.php)
* Menambahkan tab **6. Kompetensi Pustakawan** berisi log diklat kompetensi pustakawan beserta form penambahan data.

## Verification Plan

### Automated Tests
* Menjalankan static analysis PHPStan:
  ```powershell
  vendor/bin/phpstan analyse --level=9
  ```
* Menjalankan suite regresi keamanan:
  ```powershell
  php scratch/tests/test_security_audit.php
  ```

### Manual Verification
* Verifikasi visualisasi rasio fiksi vs non-fiksi di Dashboard Perpustakaan.
* Verifikasi pengisian form dan penyimpanan data di tab Serial & Berkala serta tab Kompetensi Pustakawan.

