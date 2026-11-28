# Changelog

Semua perubahan penting didokumentasikan di sini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/)
dan versi mengikuti tag rilis pada blueprint (v0.0.1 untuk Tahap 0, dst.).

## [Unreleased]

### Ditambahkan
- Komponen dasar `x-button`, `x-input`, `x-alert`, `x-skeleton`; state `loading` dan `error` pada `x-table` (T0-05).
- Halaman `/styleguide` (hanya local/testing) dengan state normal/kosong/loading/error dan uji teks 3x lebih panjang (T0-12).
- Tes paritas bahasa ID/EN dan pemeriksa teks hardcode di Blade (T0-11).
- `.env.example` lengkap per lingkungan, `README`, `CONTRIBUTING`, `docs/` (ADR, runbook, laporan kontras), template PR/issue, CI GitHub Actions (T0-07, T0-09, T0-13, T0-14).
- Skrip `composer qa` dan `scripts/contrast-check.mjs`.

### Diubah
- Database dev dan tes berpindah dari SQLite ke MySQL; tes memakai database `quixcode_test` (T0-02).
- Tombol hijau bertulisan putih memakai emerald-700 agar lolos WCAG AA 4,5:1 (T0-04).
- Teks persetujuan legal di form Start a Project kini lewat `__()` (T6-08e).

### Dihapus
- Tautan font CDN `fonts.bunny.net` dan plugin font Vite; `public/hot`, `public/build` lama, `database.sqlite`, view `Public/` ganda dan `welcome.blade.php` bawaan (T0-03).
- Kunci API nyata dari `phpunit.xml` (T0-14). **Putar (rotate) kunci kurs lama di penyedia.**

## [0.0.1] - dijadwalkan
- Tag penutup Tahap 0 setelah gate di bawah lulus (lihat `docs/runbook.md`, bagian Gate Tahap 0).
