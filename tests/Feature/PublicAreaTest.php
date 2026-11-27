<?php

use App\Enums\Currency;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Livewire\Public\StartProject;
use App\Models\ActivityLog;
use App\Models\CaseStudy;
use App\Models\ExchangeRate;
use App\Models\Project;
use App\Models\User;
use App\Models\UserConsent;
use App\Notifications\LeadWelcomeNotification;
use App\Notifications\OpsAlertNotification;
use Database\Seeders\PublicPortfolioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
