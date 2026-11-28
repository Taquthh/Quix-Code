# Laporan kontras WCAG (T0-04)

Dibuat oleh `node scripts/contrast-check.mjs` pada 28 November 2026. Ambang: 4,5:1 untuk teks normal, 3:1 untuk komponen UI non-teks (WCAG 2.1 AA).

Temuan penting: tombol hijau berteks putih sebelumnya memakai emerald-600 (3,77:1, **gagal** untuk teks 14px). Sekarang memakai emerald-700 (5,48:1); token `--color-growth` diubah dan semua `bg-emerald-600 text-white` di view dimigrasikan. Tes `FoundationQualityTest` mencegah regresi.

```
LULUS  17.85:1  (min 4.5)  TERANG  teks utama di kartu
LULUS   7.58:1  (min 4.5)  TERANG  teks sekunder di kartu
LULUS   7.24:1  (min 4.5)  TERANG  teks sekunder di latar ivory
LULUS   5.48:1  (min 4.5)  TERANG  teks tombol utama (putih di emerald-700)
LULUS   5.48:1  (min 4.5)  TERANG  tautan/aksen teks emerald-700 di putih
LULUS  10.35:1  (min 4.5)  TERANG  tombol sekunder (slate-700 di putih)
LULUS   7.29:1  (min 4.5)  TERANG  badge sukses
LULUS   7.09:1  (min 4.5)  TERANG  badge info
LULUS   6.84:1  (min 4.5)  TERANG  badge peringatan
LULUS   7.30:1  (min 4.5)  TERANG  badge bahaya
LULUS   9.45:1  (min 4.5)  TERANG  badge netral
LULUS  17.06:1  (min 4.5)  GELAP   teks utama di kartu
LULUS   6.96:1  (min 4.5)  GELAP   teks sekunder di kartu
LULUS   7.87:1  (min 4.5)  GELAP   teks sekunder di midnight
LULUS   5.48:1  (min 4.5)  GELAP   tombol utama (putih di emerald-700)
LULUS   9.29:1  (min 4.5)  GELAP   aksen teks emerald-400 di slate-900
LULUS  11.71:1  (min 4.5)  GELAP   badge sukses
LULUS  10.71:1  (min 4.5)  GELAP   badge info
LULUS  12.38:1  (min 4.5)  GELAP   badge peringatan
LULUS   9.44:1  (min 4.5)  GELAP   badge bahaya
LULUS  11.87:1  (min 4.5)  GELAP   badge netral
LULUS   3.77:1  (min 3)  KOMPONEN  cincin fokus emerald-600 di putih (non-teks)
LULUS   9.29:1  (min 3)  KOMPONEN  cincin fokus emerald-500 di slate-900 (non-teks)

23/23 pasangan lulus
```

Catatan: latar badge tema gelap berupa warna tembus pandang di atas slate-900; perhitungan memakai slate-900 sebagai latar efektif.
Tambah pasangan baru di `PAIRS` pada skrip bila ada token warna baru, lalu jalankan ulang dan commit laporan ini.
