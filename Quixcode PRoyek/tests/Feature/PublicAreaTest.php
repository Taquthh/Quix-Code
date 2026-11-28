<?php

use App\Enums\Currency;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\Leads\Index;
use App\Livewire\Public\StartProject;
use App\Models\ActivityLog;
use App\Models\CaseStudy;
use App\Models\ExchangeRate;
use App\Models\Project;
use App\Models\User;
use App\Models\UserConsent;
use App\Notifications\LeadWelcomeNotification;
use App\Notifications\OpsAlertNotification;
use App\Services\ExchangeRateService;
use Database\Seeders\PublicPortfolioSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('menampilkan halaman publik bilingual /id dan /en beserta tag hreflang dan sitemap', function () {
    $published = CaseStudy::factory()->published()->create([
        'slug' => 'sistem-kasir-modern',
        'title' => ['id' => 'Sistem Kasir Modern', 'en' => 'Modern POS Platform'],
        'summary' => ['id' => 'Ringkasan ID', 'en' => 'English Summary'],
    ]);
    CaseStudy::factory()->create([
        'slug' => 'draft-tersembunyi',
        'title' => ['id' => 'Draft Rahasia', 'en' => 'Hidden Draft'],
    ]);

    $this->get('/id')
        ->assertOk()
        ->assertSee('hreflang="id"', false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('Sistem Kasir Modern')
        ->assertDontSee('Draft Rahasia');

    $this->get('/en')
        ->assertOk()
        ->assertSee('Modern POS Platform')
        ->assertDontSee('Hidden Draft');

    $this->get('/id/works/'.$published->slug)->assertOk()->assertSee('Sistem Kasir Modern');
    $this->get('/id/works/draft-tersembunyi')->assertNotFound();

    $this->get('/id/services')->assertOk();
    $this->get('/en/about')->assertOk();
    $this->get('/id/faq')->assertOk();
    $this->get('/id/privacy')->assertOk();
    $this->get('/en/terms')->assertOk();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('/id/works/sistem-kasir-modern')
        ->assertSee('/en/works/sistem-kasir-modern')
        ->assertDontSee('draft-tersembunyi');
});

it('mengganti mata uang estimasi publik dan menandainya sebagai estimasi', function () {
    ExchangeRate::factory()->create([
        'base_currency' => Currency::IDR,
        'target_currency' => Currency::USD,
        'rate' => '0.00006250',
        'fetched_at' => now(),
    ]);

    $this->from('/en/services')
        ->post(route('public.currency', 'USD'))
        ->assertRedirect('/en/services');

    $this->get('/en/services')
        ->assertOk()
        ->assertSee('Estimate')
        ->assertSee('$');
});

it('form Start a Project membuat akun Lead, mencatat UTM dan persetujuan UU PDP, serta mengirim tautan atur-password tanpa password mentah', function () {
    Notification::fake();

    Livewire::withQueryParams([
        'utm_source' => 'instagram',
        'utm_campaign' => 'launch_q4',
    ])->test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Budi Santoso')
        ->set('email', 'budi@perusahaan.co.id')
        ->set('whatsapp', '0812-9999-8888')
        ->set('service_type', 'web')
        ->set('title', 'Aplikasi Manajemen Gudang')
        ->set('description', 'Membutuhkan sistem pelacakan stok multi-cabang dan laporan harian otomatis.')
        ->set('currency', 'IDR')
        ->set('budget_reference', '25000000')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $client = User::query()->where('email', 'budi@perusahaan.co.id')->firstOrFail();
    $project = Project::query()->where('client_id', $client->id)->firstOrFail();

    expect($client->role)->toBe(UserRole::Client)
        ->and($client->whatsapp)->toBe('6281299998888')
        ->and($project->status)->toBe(ProjectStatus::Lead)
        ->and($project->lead_source)->toBe('website')
        ->and($project->utm)->toMatchArray([
            'utm_source' => 'instagram',
            'utm_campaign' => 'launch_q4',
        ])
        ->and($project->budget_reference)->toBe(25_000_000)
        ->and(UserConsent::where('user_id', $client->id)->where('type', 'privacy_policy')->exists())->toBeTrue()
        ->and(ActivityLog::where('subject_id', $project->id)->exists())->toBeTrue();

    Notification::assertSentTo($client, LeadWelcomeNotification::class, function (LeadWelcomeNotification $notification) use ($client): bool {
        $mail = $notification->toMail($client);

        return str_contains($notification->passwordSetupUrl, '/reset-password/')
            && ! str_contains(implode(' ', $mail->introLines), 'password:');
    });
});

it('menolak email sekali-pakai, bot honeypot, dan duplikat prospek terbuka', function () {
    Notification::fake();

    // 1. Email sekali-pakai ditolak
    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Penguji')
        ->set('email', 'spammer@mailinator.com')
        ->set('whatsapp', '081234567890')
        ->set('title', 'Proyek Uji')
        ->set('description', 'Deskripsi cukup panjang untuk lolos validasi minimal karakter.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasErrors(['email']);

    // 2. Bot yang mengisi honeypot tidak membuat user/proyek
    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('website_url', 'https://bot-spam.example')
        ->set('name', 'Bot')
        ->set('email', 'bot@example.org')
        ->set('whatsapp', '081200001111')
        ->set('title', 'Spam Bot')
        ->set('description', 'Deskripsi spam bot otomatis.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertSet('submitted', true);

    expect(User::where('email', 'bot@example.org')->exists())->toBeFalse();

    // 3. Duplikat prospek terbuka ditolak
    $existingClient = User::factory()->client()->create(['email' => 'klien@aktif.co.id', 'whatsapp' => '6281233334444']);
    Project::factory()->for($existingClient, 'client')->create(['status' => ProjectStatus::Lead]);

    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Klien Aktif')
        ->set('email', 'klien@aktif.co.id')
        ->set('whatsapp', '081233334444')
        ->set('title', 'Proyek Duplikat')
        ->set('description', 'Deskripsi proyek duplikat yang masih terbuka.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasErrors(['email']);
});

it('perintah rates:update menyimpan kurs manual dan memperingatkan admin bila kurs usang > 48 jam', function () {
    Notification::fake();
    $admin = User::factory()->admin()->withTwoFactor()->create();

    $this->artisan('rates:update --rate=16250')->assertSuccessful();
    expect(ExchangeRate::count())->toBe(2);

    // Simulasikan kurs usang 50 jam
    ExchangeRate::query()->update(['fetched_at' => now()->subHours(50)]);

    $this->artisan('rates:update')->assertSuccessful();

    Notification::assertSentTo($admin, OpsAlertNotification::class, fn ($n) => $n->kind === 'stale_exchange_rate');
});

it('seeder PublicPortfolioSeeder idempoten dan menghasilkan 3 studi kasus terpublikasi', function () {
    $this->seed(PublicPortfolioSeeder::class);
    $this->seed(PublicPortfolioSeeder::class);

    expect(CaseStudy::published()->count())->toBe(3);
});

it('menyajikan robots.txt dengan sitemap absolut dan halaman 404 tanpa sesi tetap tampil', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
        ->assertSee('Disallow: /admin', false);

    $this->get('/en/halaman-tidak-ada')->assertNotFound()->assertSee('Page Not Found');
    $this->get('/tidak-ada-sama-sekali')->assertNotFound();
});

it('menyertakan og:image, hreflang, dan JSON-LD pada halaman publik', function () {
    $this->get('/en/services')
        ->assertOk()
        ->assertSee('property="og:image"', false)
        ->assertSee('og-default.png', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('rel="canonical" href="'.url('/en/services').'"', false);
});

it('menolak email akun internal dan mencatat persetujuan S&K selain Kebijakan Privasi', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create(['email' => 'admin@quix.test']);

    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Penyusup')
        ->set('email', 'admin@quix.test')
        ->set('whatsapp', '081255556666')
        ->set('title', 'Proyek Sisipan')
        ->set('description', 'Mencoba menempelkan proyek ke akun admin lewat form publik.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(Project::where('client_id', $admin->id)->exists())->toBeFalse();

    Livewire::test(StartProject::class, ['locale' => 'en'])
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@acme.io')
        ->set('whatsapp', '+1 202 555 0143')
        ->set('title', 'Inventory Dashboard')
        ->set('description', 'We need a multi-warehouse inventory dashboard with daily reports.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasNoErrors();

    $jane = User::where('email', 'jane@acme.io')->firstOrFail();
    expect(UserConsent::where('user_id', $jane->id)->pluck('type')->sort()->values()->all())
        ->toBe(['privacy_policy', 'terms']);
});

it('salah ketik tidak menghabiskan jatah kirim, tetapi pengiriman valid beruntun diblokir', function () {
    Notification::fake();

    for ($i = 0; $i < 6; $i++) {
        Livewire::test(StartProject::class, ['locale' => 'id'])
            ->set('name', 'A')
            ->call('submit')
            ->assertHasErrors(['name']);
    }

    for ($i = 1; $i <= 5; $i++) {
        Livewire::test(StartProject::class, ['locale' => 'id'])
            ->set('name', 'Pengirim '.$i)
            ->set('email', "pengirim{$i}@perusahaan.co.id")
            ->set('whatsapp', '08120000000'.$i)
            ->set('title', 'Proyek nomor '.$i)
            ->set('description', 'Deskripsi proyek yang cukup panjang nomor '.$i)
            ->set('consent_privacy', true)
            ->call('submit')
            ->assertHasNoErrors();
    }

    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Pengirim 6')
        ->set('email', 'pengirim6@perusahaan.co.id')
        ->set('whatsapp', '081200000006')
        ->set('title', 'Proyek nomor 6')
        ->set('description', 'Deskripsi proyek yang cukup panjang nomor 6')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(User::where('email', 'pengirim6@perusahaan.co.id')->exists())->toBeFalse();
});

it('menjadwalkan pembaruan kurs dan menolak token captcha kosong saat diaktifkan', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains((string) $e->command, 'rates:update'));
    expect($events)->not->toBeEmpty();

    config([
        'services.captcha.driver' => 'turnstile',
        'services.captcha.secret_key' => 'secret',
        'services.captcha.site_key' => 'site',
    ]);

    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Penguji Captcha')
        ->set('email', 'captcha@perusahaan.co.id')
        ->set('whatsapp', '081211112222')
        ->set('title', 'Proyek Captcha')
        ->set('description', 'Deskripsi proyek untuk menguji captcha wajib diisi.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasErrors(['captcha_token']);
});

it('mengenali bentuk respons beberapa penyedia kurs dan menolak kurs tak wajar', function (array $payload, bool $stored) {
    config(['services.exchange_rate.url' => 'https://rates.test/latest']);
    Http::fake(['rates.test/*' => Http::response($payload)]);

    $result = app(ExchangeRateService::class)->updateFromConfiguredSource();

    expect($result !== null)->toBe($stored);

    if ($stored) {
        $usdToIdr = ExchangeRate::latestFor(Currency::USD, Currency::IDR);
        expect((float) $usdToIdr->rate)->toBeGreaterThan(16_000)->toBeLessThan(16_500);
    }
})->with([
    'open.er-api / basis IDR' => [['rates' => ['USD' => 0.0000615]], true],
    'exchangerate-api.com' => [['conversion_rates' => ['USD' => 0.0000615]], true],
    'exchangerate.host quotes' => [['quotes' => ['USDIDR' => 16260.5]], true],
    'idr_per_usd langsung' => [['idr_per_usd' => 16250], true],
    'basis EUR (USD per EUR ~1.08) ditolak' => [['rates' => ['USD' => 1.08]], false],
    'galat penyedia ditolak' => [['success' => false, 'error' => ['code' => 101]], false],
]);

it('halaman Leads admin menampilkan UTM per lead dan ringkasan sumber terbaik', function () {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    foreach ([['instagram', 'promo_des'], ['instagram', 'promo_des'], ['google', 'brand']] as [$source, $campaign]) {
        $project = Project::factory()->create(['status' => ProjectStatus::Lead]);
        $project->forceFill([
            'lead_source' => 'website',
            'utm' => ['utm_source' => $source, 'utm_campaign' => $campaign],
        ])->save();
    }

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertSee('instagram · promo_des')
        ->assertSee('google · brand')
        ->assertSee(__('quotations.leads.utm_title'));
});
