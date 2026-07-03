# Rencana Implementasi: Otomatisasi Hak Akses Root (Sudo) pada deploy.sh

## Tujuan
Memodifikasi script `deploy.sh` agar otomatis berjalan menggunakan hak akses root (administrator) tanpa mengharuskan pengguna mengetikkan password secara manual setiap kali *deploy*, namun dengan metode yang **aman (secure)**.

## Masalah Keamanan (Security Warning)
Menyimpan password secara eksplisit (seperti `Admin-sma-11`) di dalam file kode (`deploy.sh`) adalah **praktik yang sangat berbahaya**. Jika repositori GitHub Anda bocor atau diakses orang lain, mereka akan memiliki akses penuh (root) ke server VPS Anda.

Oleh karena itu, saya merancang 2 opsi. Saya sangat menyarankan **Opsi 1**.

---

## Opsi 1: Pendekatan Standar Industri (Sudoers NOPASSWD) - 🌟 DIREKOMENDASIKAN

Pendekatan ini **sama sekali tidak menyimpan password** di dalam kode. Sebagai gantinya, kita akan memberi tahu sistem server bahwa user `sinta` diizinkan menjalankan *khusus* script `deploy.sh` sebagai root tanpa ditanya password.

### 1. Perubahan pada `deploy.sh`:
Kita akan menambahkan baris pengecekan di baris paling atas script. Jika script tidak dijalankan sebagai root, script akan otomatis memanggil dirinya sendiri menggunakan `sudo`.

```bash
#!/bin/bash

# Pengecekan: Pastikan script berjalan sebagai root
if [ "$EUID" -ne 0 ]; then
  # Jika bukan root, panggil ulang script ini menggunakan sudo
  exec sudo "$0" "$@"
fi

# ... sisa script deploy di bawahnya ...
```

### 2. Langkah Konfigurasi (Dilakukan Manual di Server):
Anda cukup menjalankan perintah `sudo visudo` di server (hanya sekali), lalu menambahkan baris berikut di bagian paling bawah:
```text
sinta ALL=(ALL) NOPASSWD: /var/www/SINTA-SaaS/deploy.sh
```
**Keuntungan:** Bebas dari password dalam kode. Keamanan server terjamin 100%.

---

## Opsi 2: Injeksi Password via .env / Konfigurasi Lokal (Kurang Direkomendasikan)

Jika Anda tidak memiliki akses untuk mengedit `sudoers` di server, kita bisa menggunakan flag `sudo -S` untuk membaca password. Namun, password **TIDAK** akan disimpan di `deploy.sh`, melainkan di file `.env` lokal di server (yang tidak di-push ke GitHub).

### 1. Perubahan pada `deploy.sh`:
```bash
#!/bin/bash

# Baca password dari file lokal (yang diabaikan oleh git)
if [ -f "/var/www/SINTA-SaaS/.deploy_pass" ]; then
    SUDO_PASS=$(cat /var/www/SINTA-SaaS/.deploy_pass)
else
    echo "File .deploy_pass tidak ditemukan!"
    exit 1
fi

# Pengecekan root
if [ "$EUID" -ne 0 ]; then
  # Lempar password ke sudo -S
  echo "$SUDO_PASS" | sudo -S bash "$0" "$@"
  exit $?
fi

# ... sisa script deploy di bawahnya ...
```

### 2. Langkah Konfigurasi (Dilakukan Manual di Server):
Anda harus membuat file teks rahasia di server (hanya sekali):
```bash
echo "Admin-sma-11" > /var/www/SINTA-SaaS/.deploy_pass
chmod 600 /var/www/SINTA-SaaS/.deploy_pass
```

---

> [!IMPORTANT]
> **Keputusan Anda Diperlukan:** 
> Opsi mana yang ingin Anda gunakan? Sangat disarankan untuk memilih **Opsi 1** demi keamanan server VPS Anda dari serangan peretas (hacker). Jika setuju, saya akan mulai menerapkan Opsi 1 pada file `deploy.sh` Anda.
