<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
 * Fondasi Tahap 0: MySQL (T0-02), font lokal (T0-03), kontras (T0-04),
 * komponen dasar (T0-05), strict mode (T0-10), styleguide (T0-12), docs/env (T0-13, T0-14).
 */

// ---------------------------------------------------------------- T0-02 MySQL

test('tes berjalan di MySQL (bukan SQLite) pada database tes terpisah', function () {
    expect(DB::connection()->getDriverName())->toBe('mysql')
        ->and(DB::connection()->getDatabaseName())->toBe('quixcode_test');
});

test('CHECK constraint progress 0-100 aktif di MySQL', function () {
    $project = Project::factory()->create();

    expect(fn () => DB::table('projects')->where('id', $project->getKey())->update(['progress_percent' => 150]))
        ->toThrow(QueryException::class);
});

test('lockForUpdate menghasilkan SELECT ... FOR UPDATE di MySQL', function () {
    $sql = strtolower(Project::query()->lockForUpdate()->toSql());

    expect($sql)->toContain('for update');
});

test('konfigurasi default memakai mysql dan .env.example mendokumentasikannya', function () {
    $env = (string) file_get_contents(base_path('.env.example'));

    expect(config('database.connections.mysql.charset'))->toBe('utf8mb4')
        ->and($env)->toContain('DB_CONNECTION=mysql')
        ->and($env)->not->toContain('DB_CONNECTION=sqlite');
});

// ---------------------------------------------------------------- T0-03 font lokal

test('layout tidak memuat font atau skrip dari CDN font', function () {
    $offenders = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        $content = (string) file_get_contents($file->getPathname());
        if (preg_match('#https?://(fonts\.bunny\.net|fonts\.googleapis\.com|fonts\.gstatic\.com|use\.typekit\.net)#', $content)) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([]);
});

test('hanya Outfit dan Plus Jakarta Sans yang diimpor sebagai font', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));
    preg_match_all('#@import "@fontsource[^"]*";#', $css, $imports);

    expect($imports[0])->toBe([
        '@import "@fontsource-variable/outfit";',
        '@import "@fontsource-variable/plus-jakarta-sans";',
    ]);
    expect(file_get_contents(base_path('vite.config.js')))->not->toContain('bunny');
});

test('tidak ada folder view ganda berbeda huruf besar-kecil', function () {
    expect(is_dir(resource_path('views/Public')))->toBeFalse();
});

// ---------------------------------------------------------------- T0-04 kontras

test('laporan kontras ada dan tombol hijau memakai warna lolos WCAG AA', function () {
    expect(file_exists(base_path('docs/contrast-report.md')))->toBeTrue()
        ->and(file_exists(base_path('scripts/contrast-check.mjs')))->toBeTrue();

    // emerald-600 + teks putih hanya 3,77:1 (gagal); solid hijau berteks putih wajib emerald-700.
    $offenders = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (preg_match('/bg-emerald-600[^"\']*text-white|text-white[^"\']*bg-emerald-600/', (string) file_get_contents($file->getPathname()))) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([]);
});

// ---------------------------------------------------------------- T0-05 komponen dasar

test('x-button: varian, loading, dan disabled', function () {
    $this->blade('<x-button>Simpan</x-button>')->assertSee('Simpan')->assertSee('bg-growth', false);
    $this->blade('<x-button :loading="true">Simpan</x-button>')->assertSee('aria-busy="true"', false)->assertSee('disabled', false);
    $this->blade('<x-button href="/x">Buka</x-button>')->assertSee('href="/x"', false);
    $this->blade('<x-button href="/x" :disabled="true">Buka</x-button>')->assertDontSee('href="/x"', false);
});

test('x-input: label, hint, error, dan loading terhubung lewat aria', function () {
    $this->blade('<x-input name="judul" label="Judul" hint="Petunjuk" error="Wajib diisi" />')
        ->assertSee('for="input-judul"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="input-judul-hint input-judul-error"', false)
        ->assertSee('Wajib diisi');

    $this->blade('<x-input name="q" :loading="true" />')->assertSee('role="status"', false);
});

test('x-alert: role sesuai tipe', function () {
    $this->blade('<x-alert type="danger" title="Gagal">Isi</x-alert>')->assertSee('role="alert"', false)->assertSee('Gagal');
    $this->blade('<x-alert type="success">Isi</x-alert>')->assertSee('role="status"', false);
});

test('x-skeleton: baris dan blok', function () {
    $this->blade('<x-skeleton :lines="3" />')->assertSee('aria-busy="true"', false);
    $this->blade('<x-skeleton :block="true" />')->assertSee('h-32', false);
});

test('x-table: state loading dan error', function () {
    $this->blade('<x-table :loading="true" />')->assertSee('aria-busy="true"', false);
    $this->blade('<x-table error="Gagal memuat" />')->assertSee('role="alert"', false)->assertSee('Gagal memuat');
});

// ---------------------------------------------------------------- T0-10 strict mode

test('strict mode aktif di non-produksi (lazy loading, atribut terbuang, atribut hilang)', function () {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});

test('paket debug (Telescope/Debugbar) tidak ada di require produksi', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $require = array_keys($composer['require'] ?? []);

    expect(array_filter($require, fn ($p) => preg_match('#telescope|debugbar|ignition#i', $p)))->toBe([]);
});

// ---------------------------------------------------------------- T0-12 styleguide

test('styleguide hanya untuk pengguna masuk dan tersedia di local/testing', function () {
    expect(Route::has('styleguide'))->toBeTrue();

    $this->get(route('styleguide'))->assertRedirect(route('login'));
});

test('styleguide memuat semua komponen dengan state normal/kosong/loading/error dan teks panjang', function () {
    app()->setLocale('id');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('styleguide'))
        ->assertOk()
        ->assertSee('data-styleguide="buttons"', false)
        ->assertSee('data-styleguide="inputs"', false)
        ->assertSee('data-styleguide="alerts"', false)
        ->assertSee('data-styleguide="skeleton"', false)
        ->assertSee('data-styleguide="table-states"', false)
        ->assertSee('data-styleguide="long-text"', false)
        ->assertSee('Belum ada data')
        ->assertSee('Data tidak dapat dimuat.')
        ->assertSee('aria-busy="true"', false);
});

test('styleguide versi Inggris juga lengkap', function () {
    $this->actingAs(User::factory()->admin()->create(['locale' => 'en']))
        ->get(route('styleguide'))
        ->assertOk()
        ->assertSee('Table states')
        ->assertSee('Could not load the data.');
});

test('route styleguide hanya didaftarkan di local/testing', function () {
    $routes = (string) file_get_contents(base_path('routes/web.php'));

    expect($routes)->toMatch("/if \(app\(\)->environment\(\['local', 'testing'\]\)\) \{\s+Route::view\('styleguide'/");
});

// ---------------------------------------------------------------- T0-13 / T0-14 docs & env

test('folder docs berisi ADR pertama, runbook, dan CHANGELOG Keep a Changelog', function () {
    foreach (['docs/adr/0001-flux-vs-komponen-kustom.md', 'docs/adr/0002-sqlite-ke-mysql.md', 'docs/runbook.md', 'CHANGELOG.md', 'README.md'] as $file) {
        expect(file_exists(base_path($file)))->toBeTrue("{$file} harus ada");
    }

    expect(file_get_contents(base_path('CHANGELOG.md')))->toContain('Keep a Changelog')->toContain('## [Unreleased]');
});

test('.env.example mendokumentasikan semua variabel yang dibaca config (tanpa rahasia asli)', function () {
    $env = (string) file_get_contents(base_path('.env.example'));
    preg_match_all('/^#?\s*([A-Z][A-Z0-9_]+)=/m', $env, $m);
    $documented = array_unique($m[1]);

    // Variabel aplikasi yang wajib terdokumentasi (DB, Redis, mail, queue, storage, GitHub, WhatsApp, Sentry, Turnstile, kurs).
    $required = [
        'APP_KEY', 'APP_URL', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
        'REDIS_HOST', 'REDIS_PASSWORD', 'QUEUE_CONNECTION', 'CACHE_STORE', 'SESSION_DRIVER', 'FILESYSTEM_DISK',
        'MAIL_MAILER', 'MAIL_FROM_ADDRESS', 'GITHUB_TOKEN', 'WHATSAPP_DRIVER', 'WHATSAPP_API_TOKEN',
        'SENTRY_LARAVEL_DSN', 'CAPTCHA_DRIVER', 'CAPTCHA_SITE_KEY', 'CAPTCHA_SECRET_KEY',
        'EXCHANGE_RATE_API_URL', 'EXCHANGE_RATE_API_KEY',
    ];

    expect(array_diff($required, $documented))->toBe([]);

    // Tidak ada nilai rahasia yang tampak asli (32+ karakter heksadesimal/base64 tanpa spasi).
    expect($env)->not->toMatch('/^(APP_KEY|[A-Z_]*(SECRET|TOKEN|PASSWORD|API_KEY))=\S{24,}/m');
});

test('berkas konfigurasi tes tidak berisi kunci API asli', function () {
    $phpunit = (string) file_get_contents(base_path('phpunit.xml'));

    expect($phpunit)->not->toMatch('/access_key=[0-9a-f]{20,}/i')
        ->and($phpunit)->not->toMatch('/EXCHANGE_RATE_API_KEY" value="[0-9a-f]{20,}"/i');
});
