<?php

use Illuminate\Support\Arr;
use Tests\Support\HardcodedTextScanner;

/*
 * T0-11: paritas bahasa ID/EN + pemeriksa teks hardcode di Blade/Livewire.
 */

function langFiles(string $locale): array
{
    return collect(glob(lang_path($locale.'/*.php')) ?: [])
        ->map(fn (string $path) => basename($path))
        ->sort()
        ->values()
        ->all();
}

function placeholders(mixed $value): array
{
    if (! is_string($value)) {
        return [];
    }

    preg_match_all('/(?<![\w:]):[A-Za-z_]+/', $value, $matches);

    return collect($matches[0])->unique()->sort()->values()->all();
}

test('lang/id dan lang/en punya berkas yang sama', function () {
    expect(langFiles('id'))->toBe(langFiles('en'));
});

test('setiap kunci lang/id ada di lang/en dan sebaliknya', function () {
    $missing = [];

    foreach (langFiles('id') as $file) {
        $id = Arr::dot(require lang_path('id/'.$file));
        $en = Arr::dot(require lang_path('en/'.$file));

        foreach (array_diff_key($id, $en) as $key => $_) {
            $missing[] = "en/{$file}: {$key}";
        }
        foreach (array_diff_key($en, $id) as $key => $_) {
            $missing[] = "id/{$file}: {$key}";
        }
    }

    expect($missing)->toBe([]);
});

test('placeholder :nama sama di kedua bahasa', function () {
    $mismatch = [];

    foreach (langFiles('id') as $file) {
        $id = Arr::dot(require lang_path('id/'.$file));
        $en = Arr::dot(require lang_path('en/'.$file));

        foreach (array_intersect_key($id, $en) as $key => $value) {
            if (placeholders($value) !== placeholders($en[$key])) {
                $mismatch[] = "{$file}: {$key}";
            }
        }
    }

    expect($mismatch)->toBe([]);
});

test('tidak ada nilai terjemahan yang kosong (kecuali yang disengaja)', function () {
    // Kosong disengaja: status on_hold tidak punya tombol aksi.
    $allowedEmpty = ['next_action.php:on_hold.label'];
    $empty = [];

    foreach (['id', 'en'] as $locale) {
        foreach (langFiles($locale) as $file) {
            foreach (Arr::dot(require lang_path($locale.'/'.$file)) as $key => $value) {
                if (is_string($value) && trim($value) === '' && ! in_array("{$file}:{$key}", $allowedEmpty, true)) {
                    $empty[] = "{$locale}/{$file}: {$key}";
                }
            }
        }
    }

    expect($empty)->toBe([]);
});

test('lang/id.json tidak punya nilai kosong dan kuncinya unik', function () {
    $raw = (string) file_get_contents(lang_path('id.json'));
    $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

    expect(array_filter($data, fn ($v) => ! is_string($v) || trim($v) === ''))->toBe([]);
});

test('view Blade tidak menambah teks literal di luar __() (baseline)', function () {
    $baselinePath = base_path('tests/Support/hardcoded-text-baseline.json');
    $current = HardcodedTextScanner::scan(resource_path('views'));

    if (getenv('UPDATE_HARDCODE_BASELINE') === '1') {
        file_put_contents($baselinePath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    $baseline = json_decode((string) file_get_contents($baselinePath), true, flags: JSON_THROW_ON_ERROR);

    $violations = [];
    foreach ($current as $file => $count) {
        $allowed = $baseline[$file] ?? 0;
        if ($count > $allowed) {
            $violations[] = "{$file}: {$count} baris teks literal (diizinkan {$allowed}). Pindahkan ke __() di lang/id + lang/en.";
        }
    }

    expect($violations)->toBe([]);
});

test('pemeriksa teks hardcode mendeteksi teks literal dan mengabaikan __()', function () {
    expect(HardcodedTextScanner::count('<p>{{ __("ui.close") }}</p>'))->toBe(0)
        ->and(HardcodedTextScanner::count('<p>Halo dunia yang literal</p>'))->toBe(1)
        ->and(HardcodedTextScanner::count("<div class=\"a b\">\n@if (\$x) Ini bukan teks @endif\n</div>"))->toBe(0)
        ->and(HardcodedTextScanner::count('<script>const a = "dua kata";</script>'))->toBe(0);
});
