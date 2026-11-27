<?php

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Jobs\ProcessGithubPush;
use App\Models\Project;
use App\Models\ProjectGitCommit;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Notifications\OpsAlertNotification;
use App\Services\GithubPushProcessor;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $this->project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'github_repo' => 'quixcode/demo-repo',
        'webhook_secret' => 'secret-webhook-uji',
    ]);
});

it('melepaskan job kembali ke antrean saat lock proyek sedang dipegang proses lain', function () {
    $delivery = WebhookDelivery::create([
        'project_id' => $this->project->id,
        'delivery_id' => 'del-lock-1',
        'event' => 'push',
        'signature_valid' => true,
        'payload' => ['ref' => 'refs/heads/main', 'commits' => []],
        'status' => 'queued',
    ]);

    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(15)->andThrow(new LockTimeoutException);

    Cache::shouldReceive('lock')
        ->once()
        ->with("github-push:{$this->project->id}", 120)
        ->andReturn($lock);

    $job = Mockery::mock(ProcessGithubPush::class)
        ->makePartial()
        ->shouldIgnoreMissing();
    $job->deliveryId = $delivery->id;
    $job->shouldReceive('release')->once()->with(5);

    $job->handle(app(GithubPushProcessor::class));

    expect($delivery->fresh()->status)->toBe('queued');
});

it('memproses dua push bersamaan secara berurutan tanpa kehilangan commit atau menurunkan progres', function () {
    $sha1 = str_pad('1', 40, 'a', STR_PAD_LEFT);
    $sha2 = str_pad('2', 40, 'b', STR_PAD_LEFT);

    $first = WebhookDelivery::create([
        'project_id' => $this->project->id,
        'delivery_id' => 'del-concurrent-1',
        'event' => 'push',
        'signature_valid' => true,
        'payload' => [
            'ref' => 'refs/heads/main',
            'commits' => [[
                'id' => $sha1,
                'message' => 'Tahap awal [progress:35%]',
                'timestamp' => '2026-11-27T10:00:00+00:00',
                'author_email' => 'dev@quixcode.test',
            ]],
        ],
        'status' => 'received',
    ]);

    $second = WebhookDelivery::create([
        'project_id' => $this->project->id,
        'delivery_id' => 'del-concurrent-2',
        'event' => 'push',
        'signature_valid' => true,
        'payload' => [
            'ref' => 'refs/heads/main',
            'commits' => [[
                'id' => $sha2,
                'message' => 'Tahap lanjut [progress:60%]',
                'timestamp' => '2026-11-27T10:01:00+00:00',
                'author_email' => 'dev@quixcode.test',
            ]],
        ],
        'status' => 'received',
    ]);

    $processor = app(GithubPushProcessor::class);
    (new ProcessGithubPush($first->id))->handle($processor);
    (new ProcessGithubPush($second->id))->handle($processor);

    expect(ProjectGitCommit::where('project_id', $this->project->id)->count())->toBe(2)
        ->and($this->project->fresh()->progress_percent)->toBe(60)
        ->and($first->fresh()->status)->toBe('processed')
        ->and($second->fresh()->status)->toBe('processed');
});

it('mengirim peringatan operasional jika proyek aktif tidak menerima webhook selama 24 jam', function () {
    Notification::fake();
    config(['services.github.token' => 'token-uji', 'quix.alerts.stale_webhook_hours' => 24]);

    $this->project->timestamps = false;
    $this->project->forceFill(['created_at' => now()->subHours(26)])->save();
    $this->project->timestamps = true;

    Http::fake(['api.github.com/*' => Http::response([])]);

    $this->artisan('github:reconcile')->assertSuccessful();

    Notification::assertSentTo($this->admin, OpsAlertNotification::class, fn ($n) => $n->kind === 'stale_webhook');
});
