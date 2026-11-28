#!/usr/bin/env node
// T0-04: pemeriksa kontras WCAG 2.x untuk pasangan warna token Quix Code.
// Jalankan: node scripts/contrast-check.mjs   (exit 1 bila ada pasangan < ambang)
// Palet = hex Tailwind (slate/emerald/sky/amber/rose/white). Tambah pasangan baru di PAIRS.

const C = {
  white: '#ffffff',
  'slate-50': '#f8fafc', 'slate-100': '#f1f5f9', 'slate-200': '#e2e8f0', 'slate-300': '#cbd5e1',
  'slate-400': '#94a3b8', 'slate-500': '#64748b', 'slate-600': '#475569', 'slate-700': '#334155',
  'slate-800': '#1e293b', 'slate-900': '#0f172a', 'slate-950': '#020617',
  'emerald-50': '#ecfdf5', 'emerald-300': '#6ee7b7', 'emerald-400': '#34d399', 'emerald-600': '#059669',
  'emerald-700': '#047857', 'emerald-800': '#065f46',
  'sky-50': '#f0f9ff', 'sky-300': '#7dd3fc', 'sky-800': '#075985',
  'amber-50': '#fffbeb', 'amber-300': '#fcd34d', 'amber-800': '#92400e',
  'rose-50': '#fff1f2', 'rose-300': '#fda4af', 'rose-800': '#9f1239',
};

const lum = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
    .map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};
const ratio = (a, b) => {
  const [l1, l2] = [lum(C[a]), lum(C[b])].sort((x, y) => y - x);
  return (l1 + 0.05) / (l2 + 0.05);
};

// [deskripsi, teks, latar, ambang]  (4.5 = teks normal, 3 = teks besar/komponen UI)
const PAIRS = [
  ['TERANG  teks utama di kartu', 'slate-900', 'white', 4.5],
  ['TERANG  teks sekunder di kartu', 'slate-600', 'white', 4.5],
  ['TERANG  teks sekunder di latar ivory', 'slate-600', 'slate-50', 4.5],
  ['TERANG  teks tombol utama (putih di emerald-700)', 'white', 'emerald-700', 4.5],
  ['TERANG  tautan/aksen teks emerald-700 di putih', 'emerald-700', 'white', 4.5],
  ['TERANG  tombol sekunder (slate-700 di putih)', 'slate-700', 'white', 4.5],
  ['TERANG  badge sukses', 'emerald-800', 'emerald-50', 4.5],
  ['TERANG  badge info', 'sky-800', 'sky-50', 4.5],
  ['TERANG  badge peringatan', 'amber-800', 'amber-50', 4.5],
  ['TERANG  badge bahaya', 'rose-800', 'rose-50', 4.5],
  ['TERANG  badge netral', 'slate-700', 'slate-100', 4.5],
  ['GELAP   teks utama di kartu', 'slate-50', 'slate-900', 4.5],
  ['GELAP   teks sekunder di kartu', 'slate-400', 'slate-900', 4.5],
  ['GELAP   teks sekunder di midnight', 'slate-400', 'slate-950', 4.5],
  ['GELAP   tombol utama (putih di emerald-700)', 'white', 'emerald-700', 4.5],
  ['GELAP   aksen teks emerald-400 di slate-900', 'emerald-400', 'slate-900', 4.5],
  ['GELAP   badge sukses', 'emerald-300', 'slate-900', 4.5],
  ['GELAP   badge info', 'sky-300', 'slate-900', 4.5],
  ['GELAP   badge peringatan', 'amber-300', 'slate-900', 4.5],
  ['GELAP   badge bahaya', 'rose-300', 'slate-900', 4.5],
  ['GELAP   badge netral', 'slate-200', 'slate-800', 4.5],
  ['KOMPONEN  cincin fokus emerald-600 di putih (non-teks)', 'emerald-600', 'white', 3],
  ['KOMPONEN  cincin fokus emerald-500 di slate-900 (non-teks)', 'emerald-400', 'slate-900', 3],
];

let fail = 0;
const rows = PAIRS.map(([d, fg, bg, min]) => {
  const r = ratio(fg, bg);
  const ok = r >= min;
  if (!ok) fail++;
  return `${ok ? 'LULUS' : 'GAGAL'}  ${r.toFixed(2).padStart(5)}:1  (min ${min})  ${d}`;
});
console.log(rows.join('\n'));
console.log(`\n${PAIRS.length - fail}/${PAIRS.length} pasangan lulus`);
process.exit(fail ? 1 : 0);
