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
use App\Services\CaptchaVerifier;
use App\Notifications\OpsAlertNotification;
use Database\Seeders\PublicPortfolioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;
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
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertSee('hreflang="id"', false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('og:image', false)
        ->assertSee('/og-image.png', false)
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
        ->and(UserConsent::where('user_id', $client->id)->where('type', 'terms_of_service')->exists())->toBeTrue()
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
    $existingClient = User::factory()->client()->create(['email' => 'klien@aktif.co.id', 'whatsapp' => '+62 812-3333-4444']);
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

    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Nomor Sama')
        ->set('email', 'pemohon-lain@example.test')
        ->set('whatsapp', '081233334444')
        ->set('title', 'Brief Nomor Sama')
        ->set('description', 'Pengajuan ini memakai nomor WhatsApp yang sudah terbuka.')
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

it('menampilkan sumber dan UTM pada pipeline prospek admin', function () {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $client = User::factory()->client()->create();
    $lead = Project::factory()->for($client, 'client')->create([
        'status' => ProjectStatus::Lead,
        'lead_source' => 'website',
    ]);
    $lead->utm = ['utm_source' => 'instagram', 'utm_campaign' => 'summer_launch'];
    $lead->save();

    $this->actingAs($admin)
        ->get(route('admin.leads.index'))
        ->assertOk()
        ->assertSee('instagram')
        ->assertSee('summer_launch');
});

it('menjadwalkan pembaruan kurs harian', function () {
    $events = Schedule::events();

    expect(collect($events)->contains(fn ($event): bool => str_contains($event->command ?? '', 'rates:update')))->toBeTrue();
});

it('mengalihkan root ke bahasa yang dipilih dan menerbitkan robots dengan sitemap absolut', function () {
    config(['app.locale' => 'id']);

    $this->get('/')->assertRedirect('/id');

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml', false);
});

it('menolak nomor WhatsApp tanpa digit dan benturan email dengan akun internal', function () {
    Notification::fake();

    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Penguji')
        ->set('email', 'phone-test@example.test')
        ->set('whatsapp', 'abcdefgh')
        ->set('title', 'Brief Pengujian')
        ->set('description', 'Deskripsi yang cukup panjang untuk memenuhi batas validasi form.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasErrors(['whatsapp']);

    $admin = User::factory()->admin()->withTwoFactor()->create(['email' => 'admin-collision@example.test']);

    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Penguji')
        ->set('email', $admin->email)
        ->set('whatsapp', '081234567890')
        ->set('title', 'Brief Pengujian')
        ->set('description', 'Deskripsi yang cukup panjang untuk memenuhi batas validasi form.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(Project::where('client_id', $admin->id)->exists())->toBeFalse();
});

it('merender widget Turnstile saat pengaturan publik valid', function () {
    config([
        'services.captcha.driver' => 'turnstile',
        'services.captcha.site_key' => 'site-key',
        'services.captcha.secret_key' => 'secret-key',
    ]);

    $this->get('/id/start-project')
        ->assertOk()
        ->assertSee('cf-turnstile')
        ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false);
});

it('menolak CAPTCHA Turnstile yang belum dikonfigurasi dan memverifikasi action token', function () {
    config([
        'services.captcha.driver' => 'turnstile',
        'services.captcha.site_key' => 'site-key',
        'services.captcha.secret_key' => null,
    ]);

    expect(app(CaptchaVerifier::class)->verify('token'))->toBeFalse();

    config([
        'services.captcha.secret_key' => 'secret-key',
    ]);
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
            'action' => 'public_lead_intake',
        ], 200),
    ]);

    expect(app(CaptchaVerifier::class)->verify('valid-token', '203.0.113.4'))->toBeTrue();
});

it('mengirimkan ringkasan pengajuan ke klien lama tanpa tautan atur ulang kata sandi', function () {
    Notification::fake();
    $client = User::factory()->client()->create([
        'email' => 'existing-client@example.test',
        'whatsapp' => '+62 812-3456-7890',
    ]);

    Livewire::test(StartProject::class, ['locale' => 'id'])
        ->set('name', 'Klien Lama')
        ->set('email', $client->email)
        ->set('whatsapp', '081234567890')
        ->set('title', 'Brief Baru Klien')
        ->set('description', 'Deskripsi kebutuhan baru yang cukup panjang untuk pengajuan proyek.')
        ->set('consent_privacy', true)
        ->call('submit')
        ->assertHasNoErrors();

    Notification::assertSentTo($client, LeadWelcomeNotification::class, function (LeadWelcomeNotification $notification): bool {
        $mail = $notification->toMail(User::where('email', 'existing-client@example.test')->firstOrFail());

        return $notification->passwordSetupUrl === null
            && str_contains(implode(' ', $mail->introLines), 'Brief Baru Klien');
    });
});

it('meng-escape konten studi kasus sebelum menyisipkannya ke JSON-LD', function () {
    $study = CaseStudy::factory()->published()->create([
        'slug' => 'safe-json-ld',
        'title' => ['id' => 'Kasus Uji', 'en' => 'Test Case'],
        'summary' => [
            'id' => '</script><script>alert(1)</script>',
            'en' => '</script><script>alert(1)</script>',
        ],
    ]);

    $this->get('/id/works/'.$study->slug)
        ->assertOk()
        ->assertDontSee('</script><script>alert(1)</script>', false)
        ->assertSee('\\u003C/script\\u003E', false);
});

it('menampilkan halaman 404 bertema tanpa detail teknis', function () {
    config(['app.locale' => 'id']);

    $this->get('/halaman-tidak-ada')
        ->assertNotFound()
        ->assertSee('Halaman Tidak Ditemukan')
        ->assertDontSee('Stack trace');
});

it('menolak nilai kurs manual non-finite atau di luar rentang', function () {
    $this->artisan('rates:update --rate=1e309')->assertFailed();
    $this->artisan('rates:update --rate=0.5')->assertFailed();
});
