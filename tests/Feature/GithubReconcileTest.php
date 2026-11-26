<?php

use App\Livewire\Admin\Projects\Show;
use App\Models\Project;
use App\Models\ProjectGitCommit;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['queue.default' => 'sync', 'services.github.token' => 'token-uji']);
    app()->setLocale('id');

    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->project = Project::factory()->active()->create([
        'github_repo' => 'quixcode/demo',
        'webhook_secret' => 'rahasia',
        'progress_percent' => 10,
    ]);

    $this->sha = fn (int $n): string => str_pad(dechex($n), 40, 'b', STR_PAD_LEFT);

    $this->remote = fn (int $n, string $message, string $at = '2026-11-27T09:00:00Z') => [
        'sha' => ($this->sha)($n),
        'commit' => [
            'message' => $message,
            'author' => ['email' => 'dev@example.com', 'date' => $at],
            'committer' => ['date' => $at],
        ],
    ];
});

// ---------------------------------------------------------------- T5-08: rekonsiliasi

it('menambal commit yang webhook-nya terlewat dan menerapkan progres', function () {
    ProjectGitCommit::factory()->create(['project_id' => $this->project->id, 'commit_hash' => ($this->sha)(1)]);
    Http::fake(['api.github.com/repos/quixcode/demo/commits*' => Http::response([
        ($this->remote)(1, 'Sudah ada'),
        ($this->remote)(2, 'Terlewat [progress:50%]'),
    ])]);

    $this->artisan('github:reconcile')->expectsOutputToContain('1 commit terlewat diantrekan dari 1 proyek')->assertSuccessful();

    expect(ProjectGitCommit::where('project_id', $this->project->id)->count())->toBe(2)
        ->and($this->project->fresh()->progress_percent)->toBe(50)
        ->and(WebhookDelivery::where('delivery_id', 'like', 'reconcile-%')->count())->toBe(1);

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer token-uji')
        && $request['sha'] === 'main'
        && str_starts_with($request->url(), 'https://api.github.com/repos/quixcode/demo/commits'));
});

it('aman diulang: tidak ada delivery atau commit ganda', function () {
    Http::fake(['api.github.com/*' => Http::response([($this->remote)(2, 'Terlewat [progress:30%]')])]);

    $this->artisan('github:reconcile')->assertSuccessful();
    $this->artisan('github:reconcile')->expectsOutputToContain('0 commit terlewat')->assertSuccessful();

    expect(ProjectGitCommit::where('project_id', $this->project->id)->count())->toBe(1)
        ->and(WebhookDelivery::count())->toBe(1)
        ->and($this->project->fresh()->progress_percent)->toBe(30);
});

it('hanya memeriksa proyek yang punya repo, secret, dan masih dikerjakan', function () {
    Project::factory()->create(['github_repo' => 'quixcode/lead', 'webhook_secret' => 'x']); // lead
    Project::factory()->active()->create(['github_repo' => null, 'webhook_secret' => 'x']);
    Project::factory()->active()->create(['github_repo' => 'quixcode/tanpa-secret', 'webhook_secret' => null]);
    $this->project->delete();
    Http::fake();

    $this->artisan('github:reconcile')->expectsOutputToContain('dari 0 proyek')->assertSuccessful();

    Http::assertNothingSent();
});

it('kegagalan satu proyek tidak menghentikan proyek lain dan dilaporkan sebagai gagal', function () {
    Project::factory()->active()->create(['github_repo' => 'orang/rusak', 'webhook_secret' => 'x']);
    Http::fake([
        '*/repos/orang/rusak/commits*' => Http::response(['message' => 'Not Found'], 404),
        '*/repos/quixcode/demo/commits*' => Http::response([($this->remote)(2, 'Tetap masuk')]),
    ]);

    $this->artisan('github:reconcile')->assertExitCode(1);

    expect(ProjectGitCommit::where('project_id', $this->project->id)->count())->toBe(1);
});

it('tanpa token rekonsiliasi dilewati dengan pesan jelas', function () {
    config(['services.github.token' => null]);
    Http::fake();

    $this->artisan('github:reconcile')->expectsOutputToContain('GITHUB_TOKEN')->assertExitCode(1);

    Http::assertNothingSent();
});

it('commit tidak valid dari API diabaikan dan pesan HTML dibersihkan', function () {
    Http::fake(['api.github.com/*' => Http::response([
        ['sha' => 'bukan-sha', 'commit' => ['message' => 'rusak']],
        ($this->remote)(3, '<img src=x onerror=alert(1)>Aman'),
    ])]);

    $this->artisan('github:reconcile')->assertSuccessful();

    $commit = ProjectGitCommit::where('project_id', $this->project->id)->first();
    expect(ProjectGitCommit::where('project_id', $this->project->id)->count())->toBe(1)
        ->and($commit->commit_message)->toBe('Aman');
});

it('rekonsiliasi terdaftar di scheduler', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('github:reconcile');
});

// ---------------------------------------------------------------- T5-08: replay

it('admin mengulang delivery gagal dan hasilnya idempoten', function () {
    $delivery = WebhookDelivery::factory()->create([
        'project_id' => $this->project->id, 'delivery_id' => 'gagal-0001', 'event' => 'push',
        'status' => 'failed', 'signature_valid' => true, 'error' => 'Timeout',
        'payload' => ['ref' => 'refs/heads/main', 'commits' => [[
            'id' => ($this->sha)(7), 'message' => 'Ulang [progress:40%]', 'timestamp' => '2026-11-27T09:00:00Z', 'author_email' => 'dev@example.com',
        ]]],
    ]);

    Livewire::actingAs($this->admin)->test(Show::class, ['project' => $this->project->id])
        ->call('replayDelivery', $delivery->id)
        ->call('replayDelivery', $delivery->id); // kedua: sudah processed, tidak bisa diulang lagi

    expect($delivery->fresh()->status)->toBe('processed')
        ->and($delivery->fresh()->error)->toBeNull()
        ->and(ProjectGitCommit::where('project_id', $this->project->id)->count())->toBe(1)
        ->and($this->project->fresh()->progress_percent)->toBe(40);
});

it('delivery proyek lain, tanda tangan palsu, dan yang sudah diproses tidak bisa diulang', function () {
    $other = Project::factory()->active()->create(['github_repo' => 'quixcode/lain', 'webhook_secret' => 'x']);
    $foreign = WebhookDelivery::factory()->create(['project_id' => $other->id, 'status' => 'failed', 'signature_valid' => true]);
    $forged = WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'failed', 'signature_valid' => false]);
    $done = WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'processed', 'signature_valid' => true]);

    $component = Livewire::actingAs($this->admin)->test(Show::class, ['project' => $this->project->id]);
    foreach ([$foreign, $forged, $done] as $delivery) {
        $component->call('replayDelivery', $delivery->id);
    }

    expect($foreign->fresh()->status)->toBe('failed')
        ->and($forged->fresh()->status)->toBe('failed')
        ->and($done->fresh()->attempts)->toBe(0);
});

it('staf tidak dapat mengulang delivery', function () {
    $staff = User::factory()->staff()->create();
    $this->project->staff()->attach($staff->id);
    $delivery = WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'failed', 'signature_valid' => true]);

    Livewire::actingAs($staff)->test(Show::class, ['project' => $this->project->id])
        ->call('replayDelivery', $delivery->id)
        ->assertForbidden();

    expect($delivery->fresh()->status)->toBe('failed');
});

it('admin melihat tombol kirim ulang hanya untuk delivery yang bisa diulang', function () {
    WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'failed', 'signature_valid' => true]);

    Livewire::actingAs($this->admin)->test(Show::class, ['project' => $this->project->id])
        ->assertSee(__('projects.webhook.replay'));
});
