# Redesign Cetak Identitas Siswa (Buku Induk)

This plan outlines the steps to redesign the print layout for "Cetak Identitas Siswa" to strictly match the physical Buku Induk format shown in the provided design photos.

## User Review Required

> [!WARNING]
> This change will completely replace the current `print_rapot.php` layout. The new layout spans multiple pages and contains extensive detailed sections (A to E) according to standard Indonesian Buku Induk formats.
> Data fields that do not exist in the database (e.g. data kesehatan per semester) will be rendered as dotted blank lines (`..... cm`, `..... kg`) for manual filling, exactly as depicted in the reference photos.

## Open Questions

> [!IMPORTANT]
> The database currently does not store details like "Jumlah saudara kandung, tiri, angkat" separately, but only `jumlah_saudara`. Also, some fields like `jarak_rumah` might be stored, but we will print them as available. For missing specific database fields, I will render dotted lines so they can be written manually after printing. Are you okay with this fallback approach?

## Proposed Changes

### BukuIndukController.php

Modify the `printRapot()` method to fetch additional data that is currently not being passed to the view but is required for the new layout.

#### [MODIFY] BukuIndukController.php
- Add a query to fetch the student's `prestasi` by joining `prestasi_siswa_anggota` with `prestasi_siswa`.
- Add a query to fetch `riwayat_beasiswa` for the student.
- Pass `$siswa['prestasi']` and `$siswa['beasiswa']` to the view.

### print_rapot.php

Completely rewrite the HTML and CSS of the print view to match the multi-page structure in the photos.

#### [MODIFY] print_rapot.php
- Update CSS for A4 print optimization, exact margins, and complex nested table/list alignments.
- Implement the header section with `Lembar ke`, `Nomor Induk Siswa`, `NIK`, etc.
- Implement **Section A (KETERANGAN SISWA)**: mapping 13 detailed fields, including nested address formats.
- Implement **Section B (KETERANGAN ORANG TUA/WALI)**: mapping Father, Mother, and Guardian details.
- Implement **Section C (PERKEMBANGAN PESERTA DIDIK)**: mapping prior education and transfer details.
- Implement **Section D (MENINGGALKAN SEKOLAH)**: mapping graduation and dropout details.
- Implement **Section E (LAIN-LAIN)**:
  - Create the 6-semester table layout for `Tinggi dan Berat Badan` (left blank with dotted lines).
  - Create the table for `Kondisi Kesehatan` (if applicable, left blank).
  - Create the `Prestasi Peserta Didik` table mapped to the `$siswa['prestasi']` data.
  - Create the `Beasiswa Peserta Didik` table mapped to the `$siswa['beasiswa']` data.
  - Add section for `Setelah Selesai Pendidikan`.
- Add the `Pas Photo Ukuran 3x4` placeholder box with specific text requirements matching the photos.

## Verification Plan

### Manual Verification
1. Open the "Buku Induk" module in the application.
2. Select a student and click the "Cetak" button under "Cetak Buku Induk" or "Identitas Siswa".
3. Verify that the generated view precisely resembles the physical paper layout from the reference photos.
4. Verify that the Print preview spans correctly across A4 pages without cutting off tables abruptly.
