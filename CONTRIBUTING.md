# Kontribusi

1. Ambil satu tugas dari tracker blueprint (mis. `T3-08`). Satu PR = satu tugas.
2. Buat branch: `git switch -c stage-3/T3-08-invoice-dp-idempotent`.
3. Commit kecil dengan Conventional Commits. Progres proyek ditandai `[progress:45%]` bila relevan.
4. Jalankan `composer qa` dan pastikan hijau. Tambahkan tes untuk perubahan perilaku.
5. Buka PR memakai template. Lampirkan tangkapan layar 375px dan desktop untuk perubahan UI.
6. Perbarui `CHANGELOG.md` (bagian `[Unreleased]`) dan, bila ada keputusan arsitektur, tambahkan ADR di `docs/adr/`.

Aturan keamanan: jangan commit `.env`, kunci API, atau data klien. Pemindai rahasia di CI akan menolak PR.
