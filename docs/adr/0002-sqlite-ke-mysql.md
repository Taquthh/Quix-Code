# ADR 0002: Dari SQLite ke MySQL (lokal tanpa Docker) dan Redis opsional

- Status: Diterima
- Tanggal: 28 November 2026
- Tugas: T0-02, T1-03

## Konteks
Dev awal memakai SQLite. Fitur kritis bergantung pada perilaku MySQL: `lockForUpdate` (idempotensi webhook dan pembayaran), CHECK constraint (progres 0-100, paid <= amount), kolasi `utf8mb4_unicode_ci`. Tes yang hijau di SQLite bisa gagal di produksi. Pengembang memakai **MySQL + phpMyAdmin lokal**, bukan Docker/Sail.

## Keputusan
1. Dev dan tes memakai **MySQL 8** lokal. `config/database.php` default `mysql`; `phpunit.xml` memaksa database `quixcode_test` (terpisah dari `quixcode`).
2. **Docker Compose / Sail tidak diwajibkan.** Kriteria "sail up" pada T0-02 diganti: README menjelaskan membuat dua database lewat phpMyAdmin; CI memakai service MySQL 8 sehingga lingkungan tes sama dengan produksi.
3. **Redis opsional.** Sesi, cache, dan antrean default ke driver `database` (cukup dengan MySQL). Redis dapat diaktifkan lewat `.env` (`CACHE_STORE`, `QUEUE_CONNECTION=redis`) tanpa perubahan kode; CI menyediakan Redis untuk uji kompatibilitas.
4. SQLite tidak dipakai lagi; `database.sqlite` dihapus dari repo.

## Konsekuensi
- Tes CHECK dan `lockForUpdate` bermakna (`tests/Feature/FoundationQualityTest.php`).
- Pengembang perlu MySQL 8 (atau MariaDB 10.6+) berjalan sebelum `composer qa`.
- Migrasi `down()` memakai `DROP CHECK` (sintaks MySQL); di MariaDB gunakan MySQL 8 untuk rollback.
