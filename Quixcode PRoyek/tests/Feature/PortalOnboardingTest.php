<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Support\OnboardingSteps;
use Illuminate\Support\Arr;

beforeEach(function () {
    app()->setLocale('id');
    $this->client = User::factory()->client()->create();
    $this->other = User::factory()->client()->create();
});

function onboardingProject(User $client, ProjectStatus $status, array $attributes = []): Project
{
    $extra = $status === ProjectStatus::Cancelled ? ['cancelled_reason' => 'Dibatalkan oleh klien'] : [];

    return Project::factory()->for($client, 'client')->create(['status' => $status, ...$extra, ...$attributes]);
}

/** @return array<string, string> kunci langkah => status langkah */
function onboardingStates(User $client): array
{
    $projects = Project::query()->where('client_id', $client->id)->get();

    return collect(OnboardingSteps::for($projects) ?? [])->pluck('state', 'key')->all();
}

// ------------------------------------------------------------------ langkah awal

it('klien tanpa proyek melihat tiga langkah awal tanpa tombol aksi', function () {
    $this->actingAs($this->client)->get(route('portal.overview'))
        ->assertOk()
        ->assertSee('data-onboarding', false)
        ->assertSee('data-onboarding-step="approve_quotation"', false)
        ->assertSee('data-onboarding-step="pay_dp"', false)
        ->assertSee('data-onboarding-step="track_progress"', false)
        ->assertSee(__('help.onboarding.steps.approve_quotation.title'))
        ->assertDontSee(__('help.onboarding.steps.approve_quotation.action'));
});

it('status langkah mengikuti proyek: quotation, DP, lalu pantau progres', function (ProjectStatus $status, array $expected) {
    onboardingProject($this->client, $status);

    expect(onboardingStates($this->client))->toBe($expected);
})->with([
    'quoted' => [ProjectStatus::Quoted, ['approve_quotation' => 'current', 'pay_dp' => 'upcoming', 'track_progress' => 'upcoming']],
    'negotiating' => [ProjectStatus::Negotiating, ['approve_quotation' => 'current', 'pay_dp' => 'upcoming', 'track_progress' => 'upcoming']],
    'deal' => [ProjectStatus::Deal, ['approve_quotation' => 'done', 'pay_dp' => 'current', 'track_progress' => 'upcoming']],
    'awaiting_dp' => [ProjectStatus::AwaitingDp, ['approve_quotation' => 'done', 'pay_dp' => 'current', 'track_progress' => 'upcoming']],
    'active' => [ProjectStatus::Active, ['approve_quotation' => 'done', 'pay_dp' => 'done', 'track_progress' => 'current']],
    'on_hold' => [ProjectStatus::OnHold, ['approve_quotation' => 'done', 'pay_dp' => 'done', 'track_progress' => 'current']],
]);

it('panduan hilang setelah proyek masuk pelunasan atau serah terima', function (ProjectStatus $status) {
    onboardingProject($this->client, $status);

    expect(OnboardingSteps::for(Project::query()->where('client_id', $this->client->id)->get()))->toBeNull();

    $this->actingAs($this->client)->get(route('portal.overview'))
        ->assertOk()
        ->assertDontSee('data-onboarding', false);
})->with([ProjectStatus::AwaitingFinal, ProjectStatus::Handover, ProjectStatus::Warranty, ProjectStatus::Closed]);

it('proyek dibatalkan tidak dihitung, dan proyek paling maju yang jadi acuan', function () {
    onboardingProject($this->client, ProjectStatus::Cancelled);
    expect(onboardingStates($this->client))->toBe(['approve_quotation' => 'current', 'pay_dp' => 'upcoming', 'track_progress' => 'upcoming']);

    onboardingProject($this->client, ProjectStatus::Quoted);
    $advanced = onboardingProject($this->client, ProjectStatus::AwaitingDp);

    expect(onboardingStates($this->client)['pay_dp'])->toBe('current');

    $this->actingAs($this->client)->get(route('portal.overview'))
        ->assertOk()
        ->assertSee('href="'.route('portal.projects.invoices', $advanced->id).'"', false)
        ->assertSee(__('help.onboarding.steps.pay_dp.action'));
});

it('langkah saat ini ditandai aria-current dan tidak membocorkan proyek klien lain', function () {
    $mine = onboardingProject($this->client, ProjectStatus::Active);
    $theirs = onboardingProject($this->other, ProjectStatus::Quoted);

    $this->actingAs($this->client)->get(route('portal.overview'))
        ->assertOk()
        ->assertSee('aria-current="step"', false)
        ->assertSee('href="'.route('portal.projects.show', $mine->id).'"', false)
        ->assertDontSee(route('portal.projects.negotiation', $theirs->id), false);
});

// ------------------------------------------------------------------ halaman bantuan

it('halaman bantuan memuat FAQ singkat dan menautkannya dari beranda', function () {
    $this->actingAs($this->client)->get(route('portal.overview'))
        ->assertOk()
        ->assertSee('href="'.route('portal.help').'"', false);

    $page = $this->get(route('portal.help'))->assertOk()->assertSee('data-help="faq"', false);

    expect(substr_count($page->getContent(), 'data-faq-item'))->toBe(count(__('help.faq')));
});

it('FAQ menyebut jumlah revisi gratis dari konfigurasi', function () {
    config(['quix.free_revision_rounds' => 3]);

    $this->actingAs($this->client)->get(route('portal.help'))
        ->assertOk()
        ->assertSee('Setiap tahapan mendapat 3 putaran revisi gratis')
        ->assertDontSee(':rounds');
});

it('klien menghubungi tim lewat chat proyeknya sendiri, bukan proyek lain atau yang sudah ditutup', function () {
    $active = onboardingProject($this->client, ProjectStatus::Active, ['title' => 'Toko Online Budi']);
    $closed = onboardingProject($this->client, ProjectStatus::Closed, ['title' => 'Proyek Lama Budi']);
    $cancelled = onboardingProject($this->client, ProjectStatus::Cancelled, ['title' => 'Proyek Batal Budi']);
    $theirs = onboardingProject($this->other, ProjectStatus::Active, ['title' => 'Proyek Rahasia Klien Lain']);

    $this->actingAs($this->client)->get(route('portal.help'))
        ->assertOk()
        ->assertSee('data-support-chat="'.$active->id.'"', false)
        ->assertSee('href="'.route('portal.projects.negotiation', $active->id).'"', false)
        ->assertDontSee($closed->title)
        ->assertDontSee($cancelled->title)
        ->assertDontSee($theirs->title)
        ->assertDontSee(route('portal.projects.negotiation', $theirs->id), false);
});

it('tanpa proyek aktif halaman bantuan menjelaskannya, tanpa tombol chat', function () {
    $this->actingAs($this->client)->get(route('portal.help'))
        ->assertOk()
        ->assertSee(__('help.no_active_project'))
        ->assertDontSee('data-support-chat', false);
});

it('kanal dukungan lain hanya tampil bila diatur, dan nomor WhatsApp dibersihkan', function () {
    $this->actingAs($this->client)->get(route('portal.help'))
        ->assertOk()
        ->assertDontSee('data-support="email"', false)
        ->assertDontSee('data-support="whatsapp"', false);

    config(['quix.support.email' => 'bantuan@quix.test', 'quix.support.whatsapp' => '+62 812-3456-789']);

    $this->get(route('portal.help'))
        ->assertOk()
        ->assertSee('href="mailto:bantuan@quix.test"', false)
        ->assertSee('href="https://wa.me/628123456789"', false)
        ->assertSee('rel="noopener noreferrer"', false);
});

it('halaman bantuan hanya untuk klien: tamu ke login, staf dan admin ditolak', function () {
    $this->actingAs(User::factory()->staff()->create())->get(route('portal.help'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create())->get(route('portal.help'))->assertForbidden();

    auth()->logout();
    $this->get(route('portal.help'))->assertRedirect(route('login'));
});

it('halaman bantuan dan panduan mengikuti bahasa akun', function () {
    $client = User::factory()->client()->create(['locale' => 'en']);

    $this->actingAs($client)->get(route('portal.help'))
        ->assertOk()
        ->assertSee('Frequently asked questions')
        ->assertDontSee('Pertanyaan umum');
});

it('semua teks bantuan tersedia dalam dua bahasa dengan kunci yang sama', function () {
    $id = Arr::dot(require lang_path('id/help.php'));
    $en = Arr::dot(require lang_path('en/help.php'));

    expect(array_keys($id))->toEqualCanonicalizing(array_keys($en))
        ->and(collect($id)->filter(fn ($text) => blank($text)))->toBeEmpty()
        ->and(collect($en)->filter(fn ($text) => blank($text)))->toBeEmpty();
});
