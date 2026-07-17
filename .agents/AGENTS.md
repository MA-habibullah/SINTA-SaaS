# Custom Rules

## Security Guidelines (Anti-XSS & Data Protection)
Saat menulis, memodifikasi, atau membenahi program, agen wajib selalu menerapkan langkah-langkah keamanan data krusial:
- **Pencegahan Kebocoran Kredensial**: Hapus data sensitif (seperti hash password, token, api_key) menggunakan `unset()` di PHP/sisi server sebelum mengirim data tersebut ke client-side JavaScript.
- **Anti-XSS pada Script**: Selalu gunakan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` saat menggunakan `json_encode()` di dalam tag `<script>` untuk mencegah *Script Break XSS*.
- **Anti-XSS pada Atribut HTML**: Jika data JSON disuntikkan ke dalam atribut HTML (seperti atribut `onclick="..."` atau `data-*`), wajib dibungkus dengan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` guna mencegah *Attribute Break XSS*.

## Testing and Checking Files
Selalu simpan file percobaan, pengujian (*testing*), atau pengecekan (seperti file dengan awalan `test_`, `check_`, `grant_`, dsb.) HANYA ke dalam folder `C:\xampp\htdocs\SINTA-SaaS\scratch`. Jangan pernah menyimpan file-file sementara ini di *root directory* atau direktori inti aplikasi lainnya.

## Implementation Plans
Setiap kali ada rencana implementasi (*implementation plan*) yang telah diselesaikan atau dijalankan, dokumen plan tersebut wajib disimpan atau disalin (secara otomatis) sesuai judul plan perbaikannya dan jangan mengahpus file yang sama tapi copy dengan nama yang berbeda ke dalam folder `C:\xampp\htdocs\SINTA-SaaS\docs`. Hal ini bertujuan sebagai dokumentasi sistem jangka panjang.

## Git Commits and Pushing
Ketika melakukan push ke repositori GitHub, selalu kelompokkan dan distribusikan perubahan ke dalam commit-commit yang terpisah secara atomik berdasarkan modul, fitur, menu, atau perbaikan bug masing-masing (jangan menggabungkan seluruh perubahan besar ke dalam satu commit tunggal).

## Walkthroughs
Setiap kali pekerjaan diselesaikan, dokumen penjelasan hasil akhir (*walkthrough*) wajib disimpan atau disalin ke dalam folder `C:\xampp\htdocs\SINTA-SaaS\docs` sesuai judul perbaikan/walkthrough tersebut dengan menyertakan format tanggal `YYYY-MM-DD` di awal nama berkasnya (contoh: `YYYY-MM-DD_Walkthrough_Nama_Fitur.md`).

