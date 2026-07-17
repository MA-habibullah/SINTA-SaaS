## Security Guidelines (Anti-XSS & Data Protection)
Saat menulis, memodifikasi, atau membenahi program, agen wajib selalu menerapkan langkah-langkah keamanan data krusial:
- **Pencegahan Kebocoran Kredensial**: Hapus data sensitif (seperti hash password, token, api_key) menggunakan `unset()` di PHP/sisi server sebelum mengirim data tersebut ke client-side JavaScript.
- **Anti-XSS pada Script**: Selalu gunakan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` saat menggunakan `json_encode()` di dalam tag `<script>` untuk mencegah *Script Break XSS*.
- **Anti-XSS pada Atribut HTML**: Jika data JSON disuntikkan ke dalam atribut HTML (seperti atribut `onclick="..."` atau `data-*`), wajib dibungkus dengan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` guna mencegah *Attribute Break XSS*.
- **Pencegahan SQL Injection (SQLi)**: Selalu gunakan Prepared Statements dengan Parameter Binding (menggunakan PDO/bindValue/execute) untuk setiap kueri database yang memproses input dari pengguna. Jangan pernah menggabungkan variabel langsung ke dalam string SQL (seperti `"WHERE id = " . $id`).
- **Validasi & Sanitasi Input Sisi Server**: Setiap input dari request client (GET, POST, COOKIE) wajib divalidasi tipe datanya dan disanitasi menggunakan fungsi seperti `strip_tags()`, `htmlspecialchars()`, `filter_var()`, atau regex sebelum digunakan dalam proses logika bisnis aplikasi.

## Modern Architecture & Zero Data Leakage Development
Saat merancang fitur baru atau memodifikasi modul yang ada, terapkan arsitektur modern dan aman:
- **Migrasi ke AJAX Fetch Dinamis**: Hindari mencetak data mentah dari database langsung menggunakan PHP `json_encode` di dalam blok skrip HTML (`<script>`). Seluruh pemuatan data sensitif (seperti data siswa, guru, profil sekolah, agenda, dsb.) wajib dialihkan menggunakan arsitektur dynamic fetch asinkronus (misal menggunakan Axios/fetch) pada saat komponen ter-mount di sisi klien (`onMounted`/`mounted` di Vue). Hal ini penting untuk memastikan tidak ada data rahasia yang bocor lewat perintah "View Page Source" (Ctrl+U).
- **Pengembangan dengan Ide Baru & Aman**: Setiap pembuatan fitur atau modul baru wajib dirancang menggunakan pola arsitektur modern (API-driven / dynamic rendering) dengan tetap mengutamakan keindahan estetika antarmuka (premium UI/UX) dan keamanan data yang ketat sejak fase awal perencanaan kode.
- **Standardisasi Respon API JSON**: Saat membuat API endpoint baru yang menghasilkan respon JSON, selalu gunakan format terstandardisasi: `['success' => true/false, 'data' => ..., 'error' => ...]` lengkap dengan HTTP status code yang tepat (contoh: 200 OK, 400 Bad Request, 403 Forbidden, 422 Unprocessable Entity untuk error validasi).

# Custom Rules
## Testing and Checking Files
Selalu simpan file percobaan, pengujian (*testing*), atau pengecekan (seperti file dengan awalan `test_`, `check_`, `grant_`, dsb.) HANYA ke dalam folder `C:\xampp\htdocs\SINTA-SaaS\scratch`. Jangan pernah menyimpan file-file sementara ini di *root directory* atau direktori inti aplikasi lainnya.

## Implementation Plans
Setiap kali ada rencana implementasi (*implementation plan*) yang telah diselesaikan atau dijalankan, dokumen plan tersebut wajib disimpan atau disalin (secara otomatis) sesuai judul plan perbaikannya tersebut dengan menyertakan format tanggal `YYYY-MM-DD` di awal nama berkasnya (contoh: `YYYY-MM-DD_implementation plan_Nama_Fitur.md`) dan jangan menghapus file yang sama tapi copy dengan nama yang berbeda ke dalam folder `C:\xampp\htdocs\SINTA-SaaS\docs`. Hal ini bertujuan sebagai dokumentasi sistem jangka panjang.

## Walkthroughs
Setiap kali pekerjaan diselesaikan, dokumen penjelasan hasil akhir (*walkthrough*) wajib disimpan atau disalin ke dalam folder `C:\xampp\htdocs\SINTA-SaaS\docs` sesuai judul perbaikan/walkthrough tersebut dengan menyertakan format tanggal `YYYY-MM-DD` di awal nama berkasnya (contoh: `YYYY-MM-DD_Walkthrough_Nama_Fitur.md`).

## Git Commits and Pushing
Ketika melakukan push ke repositori GitHub, selalu kelompokkan dan distribusikan perubahan ke dalam commit-commit yang terpisah secara atomik berdasarkan modul, fitur, menu, atau perbaikan bug masing-masing (jangan menggabungkan seluruh perubahan besar ke dalam satu commit tunggal).




