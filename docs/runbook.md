# Runbook

## Gate Tahap 0 (sebelum tag v0.0.1)
- [ ] `composer qa` hijau di lokal dan CI.
- [ ] Clone bersih mengikuti README sampai `/` terbuka.
- [ ] `npm run build` lolos; tidak ada permintaan ke CDN font (DevTools > Network).
- [ ] `node scripts/contrast-check.mjs` lulus; laporan di `docs/contrast-report.md` dilampirkan di PR.
- [ ] Branch protection `main` aktif (lihat `docs/github-setup.md`).
- [ ] Pemindai rahasia hijau; kunci kurs lama diputar.
- [ ] `git tag v0.0.1 && git push --tags`.

## Database lokal
- Buat ulang dev: phpMyAdmin > drop `quixcode` > create (utf8mb4_unicode_ci) > `php artisan migrate --seed`.
- Tes gagal "Unknown database `quixcode_test`": buat database tersebut di phpMyAdmin.
- Cadangan manual: `mysqldump -u root -p quixcode > backup-$(date +%F).sql`.

## Antrean
- Jalankan worker: `php artisan queue:work --queue=critical,default,notifications,pdf`.
- Job gagal: `php artisan queue:failed`, ulang `php artisan queue:retry all`.
- Contoh Supervisor: `deploy/supervisor/quix-worker.conf`.

## Penjadwal
Tambahkan satu entri cron: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`.

## Build aset
Setelah mengubah CSS/JS: `npm run build`. Jangan commit `public/build` maupun `public/hot`.

## Rotasi rahasia
APP_KEY, kata sandi DB, token GitHub, token WhatsApp, kunci kurs: ganti di penyedia, perbarui `.env` server, `php artisan config:clear`, catat tanggal di log keputusan blueprint.
