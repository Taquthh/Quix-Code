<?php

use App\Jobs\ProcessGithubPush;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Notifications\OpsAlertNotification;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    app()->setLocale('id');
    Cache::flush();

    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);
    $this->staff = User::factory()->staff()->create();

    $this->failedEvent = function (string $queue = 'critical'): JobFailed {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('resolveName')->andReturn(ProcessGithubPush::class);
        $job->shouldReceive('getQueue')->andReturn($queue);

        return new JobFailed('database', $job, new RuntimeException('kaboom'));
    };
});

// ---------------------------------------------------------------- antrean terpisah

it('mendefinisikan empat antrean dengan webhook di critical', function () {
    expect(config('quix.queues'))->toBe(['critical', 'default', 'notifications', 'pdf'])
        ->and(config('quix.webhook.queue'))->toBe('critical')
        ->and((new ProcessGithubPush(1))->queue)->toBe('critical');
});

it('webhook yang valid masuk antrean critical, bukan default', function () {
    Queue::fake();
    $project = Project::factory()->active()->create(['github_repo' => 'quixcode/demo', 'webhook_secret' => 'rahasia']);
    $sha = str_repeat('c', 40);
    $body = json_encode([
        'ref' => 'refs/heads/main',
        'repository' => ['full_name' => 'quixcode/demo'],
        'commits' => [['id' => $sha, 'message' => 'x', 'timestamp' => '2026-11-27T09:00:00Z', 'author' => ['email' => 'a@b.c']]],
    ]);

    $this->call('POST', route('webhooks.github', $project->id), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_GITHUB_EVENT' => 'push',
        'HTTP_X_GITHUB_DELIVERY' => 'delivery-critical-1',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'rahasia'),
    ], $body)->assertStatus(202);

    Queue::assertPushedOn('critical', ProcessGithubPush::class);
    // QueueFake tidak punya assertNotPushedOn: pastikan tak ada job yang masuk antrean default.
    Queue::assertNotPushed(ProcessGithubPush::class, fn ($job, $queue) => $queue === 'default');
});

// ---------------------------------------------------------------- dead-letter dan peringatan

it('job yang gagal permanen memicu email ke admin aktif saja, sekali per jendela waktu', function () {
    Notification::fake();

    event(($this->failedEvent)());
    event(($this->failedEvent)()); // badai kegagalan: tidak menggandakan email

    Notification::assertSentToTimes($this->admin, OpsAlertNotification::class, 1);
    Notification::assertNotSentTo($this->inactiveAdmin, OpsAlertNotification::class);
    Notification::assertNotSentTo($this->staff, OpsAlertNotification::class);
});

it('antrean berbeda punya jendela peringatan sendiri', function () {
    Notification::fake();

    event(($this->failedEvent)('critical'));
    event(($this->failedEvent)('pdf'));

    Notification::assertSentToTimes($this->admin, OpsAlertNotification::class, 2);
});

it('antrean yang menumpuk memicu peringatan admin', function () {
    Notification::fake();

    event(new QueueBusy('database', 'critical', 500));

    Notification::assertSentTo($this->admin, OpsAlertNotification::class, fn ($n) => $n->kind === 'queue_busy' && $n->context['size'] === 500);
});

it('email peringatan tersedia dalam dua bahasa dan tidak memuat payload job', function () {
    foreach (['id', 'en'] as $locale) {
        app()->setLocale($locale);
        $mail = (new OpsAlertNotification('failed_job', ['job' => 'ProcessGithubPush', 'queue' => 'critical', 'error' => 'RuntimeException: kaboom']))->toMail($this->admin);

        expect($mail->subject)->not->toStartWith('mail.')
            ->and(implode(' ', $mail->introLines))->toContain('ProcessGithubPush')->not->toContain('mail.');
    }
});

it('kegagalan mengirim peringatan tidak mengganggu worker', function () {
    config(['mail.default' => 'mailer-tidak-ada']); // pengiriman email akan melempar galat

    expect(fn () => event(($this->failedEvent)()))->not->toThrow(Throwable::class);
});

it('job yang gagal menandai delivery sebagai failed dengan galatnya', function () {
    $delivery = WebhookDelivery::factory()->create(['status' => 'received']);

    (new ProcessGithubPush($delivery->id))->failed(new RuntimeException('boom'));

    expect($delivery->fresh()->status)->toBe('failed')->and($delivery->fresh()->error)->toBe('boom');
});

it('pemantau antrean terdaftar di scheduler untuk semua antrean', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('queue:monitor');
});
