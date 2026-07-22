---
## [Audit Keamanan & Perbaikan Variabel Undefined via PHPStan]
**Waktu**: 17:09 WIB
**Jenis**: Bug Fix / Security Audit

### Ringkasan Pekerjaan:
1. **Pemasangan Tools Audit PHPStan**:
   - Memasang `phpstan/phpstan` via Composer sebagai pengganti `progpilot` yang usang.
   - Mengintegrasikan analisis statis kode PHP untuk memindai 62 berkas di folder `app/`.
2. **Perbaikan Bug Variabel Undefined**:
   - **[NilaiRaporController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/NilaiRaporController.php)**: Memindahkan inisialisasi variabel `$kelasId = $_GET['kelas_id'] ?? '';` ke bagian atas method `getGrid()` sebelum digunakan pada pengecekan `tenant_id`.
   - **[SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)**: Menginisialisasi variabel `$raw = array_merge($_GET, json_decode(file_get_contents('php://input'), true) ?? []);` pada method `apiPreviewGenerate()` untuk mencegah `Undefined variable: $raw`.
3. **Verifikasi & Pembersihan PHPStan Level 1**:
   - Membersihkan seluruh peringatan pada **PHPStan Level 1** di berkas [BKController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BKController.php), [BukuIndukController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php), [KurikulumController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/KurikulumController.php), [NilaiRaporController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/NilaiRaporController.php), dan [PDSSController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php).
   - Memastikan `vendor/bin/phpstan analyse app --level=1` menghasilkan status `[OK] No errors` (100% lulus audit Level 1 tanpa error maupun warning).
