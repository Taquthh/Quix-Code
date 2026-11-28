# Quix Code

[![CI](https://github.com/OWNER/quixcode/actions/workflows/ci.yml/badge.svg)](https://github.com/OWNER/quixcode/actions/workflows/ci.yml)

Portal klien dan panel admin untuk studio pengembangan perangkat lunak. Laravel 13 + Livewire 4 + Tailwind 4, bilingual (ID/EN).

> Ganti `OWNER` pada badge di atas dengan nama akun/organisasi GitHub Anda.

## Prasyarat

- PHP 8.3+ (ekstensi: mbstring, pdo_mysql, intl, fileinfo, gd atau imagick)
- Composer 2
- Node.js 20+ dan npm
- **MySQL 8** lokal (mis. Laragon, XAMPP/MariaDB 10.6+ juga bisa) dengan **phpMyAdmin**
- Redis bersifat **opsional** (lihat [ADR 0002](docs/adr/0002-sqlite-ke-mysql.md))

## Setup 5 langkah

1. **Clone dan masuk folder**
   ```bash
   git clone <url-repo> quixcode && cd quixcode
   ```
2. **Buat dua database di phpMyAdmin** (collation `utf8mb4_unicode_ci`): `quixcode` (dev) dan `quixcode_test` (tes otomatis).
3. **Siapkan lingkungan**
   ```bash
   cp .env.example .env      # Windows PowerShell: copy .env.example .env
   ```
   Sesuaikan `DB_USERNAME` / `DB_PASSWORD` dengan MySQL lokal Anda (XAMPP bawaan: `root` dan kosong).
4. **Pasang dan migrasi**
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   npm install && npm run build
   ```
5. **Jalankan**
   ```bash
   composer dev
   ```
   Buka `http://localhost:8000`. Akun demo dibuat oleh `DemoSeeder` (hanya di `local`).

Pintasan: `composer setup` menjalankan langkah 4 sekaligus (setelah database dibuat).

## Perintah harian

| Perintah | Fungsi |
| --- | --- |
| `composer qa` | Pint (cek) + Larastan + seluruh tes Pest. Harus hijau sebelum PR |
| `composer lint` | Rapikan kode dengan Pint |
| `composer test:browser` | Tes browser 375px (butuh `npx playwright install`) |
| `node scripts/contrast-check.mjs` | Periksa kontras WCAG token warna |
| `/styleguide` | Galeri komponen (hanya `local`, perlu login) |

Tes berjalan di database `quixcode_test` (dipaksa oleh `phpunit.xml`), jadi data dev aman.

## Aturan kerja

- Branch dari `main`: `stage-N/Tn-nn-nama-fitur`. **Dilarang push langsung ke `main`.**
- Commit mengikuti Conventional Commits (`feat:`, `fix:`, `test:`, `docs:`, `refactor:`, `chore:`). Pesan di `main` tampil ke klien, tulis sopan.
- Satu PR = satu tugas; judul memuat ID tugas. Gunakan template PR dan pastikan CI hijau.
- Setiap teks lewat `__()` dengan padanan di `lang/id` dan `lang/en`.
- Detail: [CONTRIBUTING.md](CONTRIBUTING.md), [docs/](docs/), [CHANGELOG.md](CHANGELOG.md).

## Dokumentasi

- [docs/adr/](docs/adr/) keputusan arsitektur
- [docs/runbook.md](docs/runbook.md) operasional dan pemulihan
- [docs/contrast-report.md](docs/contrast-report.md) laporan kontras WCAG
- [docs/github-setup.md](docs/github-setup.md) branch protection, label, milestone
- [docs/blueprint/](docs/blueprint/) salinan blueprint
