# Pengaturan GitHub (T0-07)

Pengaturan ini ada di sisi GitHub, bukan di kode. Jalankan sekali setelah repo dibuat.

## Otomatis dengan GitHub CLI
```bash
gh auth login
bash scripts/github-setup.sh OWNER/quixcode
```
Skrip membuat label (`stage:0`..`stage:8`, `type:*`, `prio:*`, `area:*`, `blocked`), milestone "Tahap 0".."Tahap 8", dan branch protection `main`.

## Manual (Settings > Branches > Add rule untuk `main`)
- Require a pull request before merging (1 approval bila ada kolaborator)
- Require status checks: **qa**, **security** (dari workflow CI)
- Require branches to be up to date
- Do not allow bypassing; block force pushes; block deletions
- Settings > Code security: aktifkan Dependabot alerts dan Secret scanning (push protection)

Bukti yang diminta kriteria: coba `git push origin main` langsung, harus ditolak.
