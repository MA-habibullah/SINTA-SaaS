# Rencana Implementasi: Audit & Optimasi UI/UX Mobile-First SINTA-SaaS

Dokumen ini berisi panduan teknis dan sistematis untuk memastikan seluruh antarmuka aplikasi SINTA-SaaS 100% responsif (*Mobile-First*), rapi, dan tidak mengalami *overflow* pada layar gawai/HP.

## 1. Strategi Audit UI/UX
Langkah-langkah sistematis untuk menemukan halaman atau elemen yang "pecah":

1. **Gunakan Chrome DevTools (Device Mode):** 
   - Tekan `F12` atau `Ctrl+Shift+I`, lalu aktifkan opsi **Toggle Device Toolbar** (`Ctrl+Shift+M`).
   - Uji aplikasi pada resolusi standar: **360x800** (Mobile Kecil), **390x844** (iPhone 12/13), dan **768x1024** (Tablet/iPad).
2. **Aktifkan Outline Debugging (Mendeteksi Overflow):**
   - Sisipkan snippet CSS sementara ini ke dalam konsol browser atau file CSS global untuk melihat elemen mana yang melampaui lebar layar:
     ```css
     * { outline: 1px solid red !important; }
     ```
3. **Audit Gestur & Area Sentuh (Touch Targets):**
   - Pastikan setiap tombol (*button*) atau tautan (*link*) memiliki tinggi dan lebar minimal **44px x 44px** (standar pedoman aksesibilitas iOS/Android) agar mudah ditekan tanpa meleset.
4. **Throttle Network & Kinerja:**
   - Gunakan tab "Network" di DevTools, ubah kecepatan ke "Fast 3G" untuk memastikan elemen UI tidak terlihat hancur (FOUT/FOUC) saat memuat skrip/gaya dalam koneksi lambat.

## 2. Penanganan Komponen Kritis (Pain Points)

### A. DataTables / Tabel Grid Banyak Kolom (Matriks Buku Induk)
Tabel dengan banyak data tidak bisa dipaksa mengecil. Solusi terbaik adalah mengizinkan scroll horizontal secara spesifik pada pembungkus tabel (*wrapper*), atau mengubah baris menjadi bentuk *card* di layar kecil.

**Solusi Scroll Horizontal (Tailwind):**
```html
<div class="w-full overflow-x-auto rounded-lg shadow ring-1 ring-black ring-opacity-5">
  <table class="min-w-full divide-y divide-gray-300">
    <!-- Konten Tabel -->
  </table>
</div>
```

**Solusi Transformasi Tabel Menjadi Card di Mobile:**
Gunakan utilitas `block` pada elemen tabel khusus di layar kecil.
```html
<!-- Contoh logika struktur: -->
<table class="w-full">
  <thead class="hidden md:table-header-group">...</thead>
  <tbody class="block md:table-row-group">
    <tr class="block border-b md:table-row p-4 md:p-0">
      <td class="block md:table-cell flex justify-between">
        <span class="md:hidden font-bold">Label Kolom:</span> Isi Data
      </td>
    </tr>
  </tbody>
</table>
```

### B. Form Wizard / Multi-step Form Panjang
Formulir panjang di mobile bisa terasa melelahkan. Hindari *scrolling* vertikal yang berlebihan tanpa indikator.

**Strategi UI:**
- Tampilkan indikator proses (Progress Bar atau Step 1 of 5).
- Tumpuk *input field* secara vertikal (1 kolom per baris di mobile).

**Snippet Grid/Flexbox:**
```html
<!-- Grid yang tumpuk 1 di mobile, 2 kolom di tablet (md) -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="flex flex-col">
    <label class="mb-1 text-sm font-semibold">Nama Lengkap</label>
    <input type="text" class="px-3 py-2 border rounded-md focus:ring focus:ring-blue-200 w-full" />
  </div>
  <!-- Field lainnya -->
</div>
```

### C. Sidebar Navigation & Dropdown (Touch-Friendly)
Sidebar di desktop sebaiknya diubah menjadi menu *Off-canvas* (menggeser dari samping) atau *Bottom Navigation Bar* di mobile.

**Sidebar Off-Canvas Transisi:**
```html
<!-- Overlay Gelap (Hidden default) -->
<div class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" id="sidebar-overlay"></div>

<!-- Sidebar Utama -->
<aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out" id="sidebar">
  <nav class="flex flex-col p-4 space-y-2">
    <!-- Menu Links pastikan py-3 untuk area sentuh yang luas -->
    <a href="#" class="px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg flex items-center gap-3">
      <i class="icon-dashboard"></i> Dashboard
    </a>
  </nav>
</aside>
```

### D. Modal Pop-up & Grafik (Charts)
Modal sering kali tertutup *keyboard* virtual HP atau keluar dari viewport. Grafik (*Chart.js/ApexCharts*) harus dibuat responsif.

**Penanganan Modal:**
- Gunakan margin/padding ekstra agar modal dapat di-scroll dari dalam (internal scroll).
```html
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
  <!-- Max height dan overflow-y-auto sangat krusial -->
  <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
    <div class="p-4 border-b font-bold sticky top-0 bg-white">Judul Modal</div>
    <div class="p-4 overflow-y-auto flex-grow">
      <!-- Konten Panjang -->
    </div>
  </div>
</div>
```

**Penanganan Grafik:**
Selalu bungkus elemen `<canvas>` dalam div responsif.
```html
<div class="w-full relative h-64 md:h-96">
  <canvas id="myChart"></canvas>
</div>
```

## 3. Standar Kelas CSS (Utilitas Wajib)
Standar pedoman utilitas (menggunakan Tailwind) untuk diterapkan di seluruh SINTA-SaaS:

- **Visibility Control:**
  - `hidden md:block` -> Menyembunyikan elemen di HP, tampil di Desktop (berguna untuk gambar ilustrasi/kolom tabel tidak penting).
  - `block md:hidden` -> Menampilkan tombol hamburger atau navigasi khusus HP.
- **Flexbox & Grid Layouts:**
  - `flex flex-col md:flex-row` -> Menyusun elemen atas-bawah di HP, kiri-kanan di Desktop.
  - `grid-cols-1 md:grid-cols-3` -> Mengatur jumlah kolom konten secara responsif.
- **Spacing & Typography:**
  - `p-4 md:p-6 lg:p-8` -> Memberi ruang lega (*breathing room*) yang berbeda di tiap perangkat.
  - `text-sm md:text-base` -> Menyesuaikan ukuran font agar pas dan mudah dibaca di gawai.
- **Overflow Prevention:**
  - `break-words` atau `truncate` -> Mencegah teks panjang atau URL merusak struktur card.
  - `w-full max-w-full overflow-hidden` -> Melindungi *container* utama dari kebocoran elemen internal.

## 4. Mobile Testing Checklist (Pre-Deployment)
Sebelum melakukan *push* kode dan *deployment* ke CloudPanel, QA harus mencentang daftar berikut:

- [ ] Tidak ada elemen (tabel, gambar, teks panjang) yang membuat *horizontal scrollbar* muncul pada viewport utama (*body*).
- [ ] Seluruh tombol (*CTA, submit form*) mudah ditekan dengan ibu jari (minimal dimensi sentuh 44x44px).
- [ ] Keyboard virtual HP tidak menutupi *input field* yang sedang aktif (terutama di dalam Modal Pop-up).
- [ ] Formulir multi-step (*wizard*) terlihat rapi (1 kolom) dan terlihat progress indikatornya.
- [ ] Menu Navigasi (Hamburger) dapat dibuka, ditutup, dan berfungsi lancar pada layar *smartphone*.
- [ ] Modal Pop-up panjang bisa di-scroll tanpa me-scroll *background* di belakangnya (lock body scroll).
- [ ] Tabel Buku Induk dapat digeser ke samping dengan aman (via `overflow-x-auto`) tanpa mengganggu keseluruhan halaman.
