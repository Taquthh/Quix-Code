# ADR 0001: Flux vs komponen kustom

- Status: Diterima
- Tanggal: 28 November 2026
- Tugas: T0-05

## Konteks
Starter kit Livewire memasang Flux UI. Blueprint mewajibkan komponen dasar dengan empat state (normal, kosong, loading, error) dan identitas visual sendiri (token slate/emerald).

## Keputusan
- **Area publik, portal, dan admin** memakai komponen Blade kustom di `resources/views/components` (`x-card`, `x-badge-status`, `x-progress-bar`, `x-modal`, `x-table`, `x-empty-state`, `x-button`, `x-input`, `x-alert`, `x-skeleton`). Semuanya berbasis token Tailwind di `resources/css/app.css` dan lolos WCAG AA.
- **Flux dipertahankan** hanya untuk halaman bawaan starter kit (auth, pengaturan akun, sidebar admin) agar tidak menulis ulang fitur 2FA dan passkey.
- Komponen baru tidak boleh bergantung pada Flux. Bila halaman auth dirombak, komponen kustom menggantikan Flux bertahap.

## Konsekuensi
- Kontrol penuh atas kontras, teks panjang, dan i18n.
- Dua sistem tampilan hidup berdampingan sementara; `/styleguide` memuat komponen kustom sebagai acuan tunggal.
