<?php

use App\Enums\ActivityAction;
use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Livewire\Admin\Projects\Show;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectGitCommit;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Support\CommitMessage;
use Livewire\Livewire;

beforeEach(function () {
    config(['queue.default' => 'sync']);
    app()->setLocale('id');

    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->client = User::factory()->client()->create();
    $this->project = Project::factory()->active()->for($this->client, 'client')->create([
        'github_repo' => 'quixcode/demo',
        'webhook_secret' => 'rahasia-proyek-a',
        'progress_percent' => 10,
    ]);

    $this->sha = fn (int $n = 1): string => str_pad(dechex($n), 40, 'a', STR_PAD_LEFT);

    $this->commit = fn (int $n, string $message, string $at = '2026-11-27T10:00:00+00:00', string $email = 'dev@example.com') => [
        'id' => ($this->sha)($n), 'message' => $message, 'timestamp' => $at, 'author' => ['email' => $email],
    ];

    /** Kirim payload bertanda tangan; $overrides mengubah repo/ref/header/secret. */
    $this->push = function (array $commits, array $overrides = []) {
        $project = $overrides['project'] ?? $this->project;
        $payload = [
            'ref' => $overrides['ref'] ?? 'refs/heads/main',
            'repository' => ['full_name' => $overrides['repo'] ?? 'quixcode/demo'],
            'commits' => $commits,
        ];
        $body = json_encode($payload);
        $secret = $overrides['secret'] ?? 'rahasia-proyek-a';
        $signature = $overrides['signature'] ?? 'sha256='.hash_hmac('sha256', $body, $secret);

        return $this->call('POST', route('webhooks.github', $project->id), [], [], [], array_filter([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GITHUB_EVENT' => $overrides['event'] ?? 'push',
            'HTTP_X_GITHUB_DELIVERY' => $overrides['delivery'] ?? fake()->uuid(),
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], fn ($v) => $v !== null), $body);
    };
});

// ---------------------------------------------------------------- T5-01 / T5-03: tanda tangan

it('menolak tanda tangan salah dengan 401 dan mencatatnya', function () {
    ($this->push)([($this->commit)(1, 'x [progress:50%]')], ['signature' => 'sha256='.str_repeat('0', 64)])->assertStatus(401);

    expect(ProjectGitCommit::count())->toBe(0)
        ->and($this->project->fresh()->progress_percent)->toBe(10)
        ->and(WebhookDelivery::where('signature_valid', false)->count())->toBe(1);
});

it('menolak permintaan tanpa tanda tangan atau ke proyek yang tidak ada', function () {
    ($this->push)([], ['signature' => ''])->assertStatus(401);

    $body = '{}';
    $this->call('POST', route('webhooks.github', '01JZZZZZZZZZZZZZZZZZZZZZZZ'), [], [], [], [
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'apa-saja'),
    ], $body)->assertStatus(401);
});

it('menolak proyek tanpa secret atau tanpa repo walau tanda tangan cocok dengan string kosong', function () {
    $this->project->forceFill(['webhook_secret' => null])->save();

    ($this->push)([], ['secret' => ''])->assertStatus(401);
});

it('merotasi secret satu proyek tanpa merusak proyek lain', function () {
    $other = Project::factory()->active()->create(['github_repo' => 'quixcode/lain', 'webhook_secret' => 'rahasia-b']);
    $this->project->forceFill(['webhook_secret' => 'rahasia-baru'])->save();

    ($this->push)([($this->commit)(1, 'a')])->assertStatus(401); // secret lama
    ($this->push)([($this->commit)(2, 'b')], ['secret' => 'rahasia-baru'])->assertStatus(202);
    ($this->push)([($this->commit)(3, 'c')], ['project' => $other, 'repo' => 'quixcode/lain', 'secret' => 'rahasia-b'])->assertStatus(202);
});

// ---------------------------------------------------------------- T5-02: idempotensi dan event

it('membalas ping dengan 200', function () {
    ($this->push)([], ['event' => 'ping'])->assertOk();
});

it('mengabaikan event selain push dengan aman', function () {
    ($this->push)([($this->commit)(1, '[progress:80%]')], ['event' => 'issues'])->assertStatus(202);

    expect($this->project->fresh()->progress_percent)->toBe(10)
        ->and(ProjectGitCommit::count())->toBe(0);
});

it('pengiriman ganda menghasilkan 202 tanpa efek ganda', function () {
    $commits = [($this->commit)(1, 'Tambah fitur [progress:40%]')];

    ($this->push)($commits, ['delivery' => 'delivery-0001'])->assertStatus(202);
    ($this->push)($commits, ['delivery' => 'delivery-0001'])->assertStatus(202);

    expect(WebhookDelivery::where('delivery_id', 'delivery-0001')->count())->toBe(1)
        ->and(ProjectGitCommit::count())->toBe(1)
        ->and($this->project->fresh()->progress_percent)->toBe(40);
});

it('mengantrekan ulang delivery yang gagal ketika GitHub mengirim ulang dengan id sama', function () {
    WebhookDelivery::factory()->create([
        'project_id' => $this->project->id, 'delivery_id' => 'delivery-gagal', 'event' => 'push',
        'status' => 'failed', 'signature_valid' => true, 'payload' => ['ref' => 'refs/heads/main', 'commits' => []],
    ]);

    ($this->push)([($this->commit)(1, 'Perbaikan [progress:30%]')], ['delivery' => 'delivery-gagal'])->assertStatus(202);

    expect(WebhookDelivery::where('delivery_id', 'delivery-gagal')->value('status'))->toBe('processed');
});

// ---------------------------------------------------------------- T5-03: sumber

it('mengabaikan repo atau cabang lain dengan 202', function () {
    ($this->push)([($this->commit)(1, '[progress:90%]')], ['repo' => 'orang/lain'])->assertStatus(202);
    ($this->push)([($this->commit)(2, '[progress:90%]')], ['ref' => 'refs/heads/develop'])->assertStatus(202);

    expect($this->project->fresh()->progress_percent)->toBe(10)
        ->and(ProjectGitCommit::count())->toBe(0);
});

// ---------------------------------------------------------------- T5-04: progres

it('memperbarui progres dan menyelesaikan milestone yang ambangnya tercapai', function () {
    $milestone = ProjectMilestone::factory()->for($this->project)->create(['target_progress' => 50, 'sort_order' => 0]);

    ($this->push)([($this->commit)(1, "Selesai modul kasir\n\n[progress:55%]")])->assertStatus(202);

    $project = $this->project->fresh();
    expect($project->progress_percent)->toBe(55)
        ->and($milestone->fresh()->status)->toBe(MilestoneStatus::Done)
        ->and($project->status)->toBe(ProjectStatus::Active);

    $log = ActivityLog::where('action', ActivityAction::ProgressChanged->value)->latest('id')->first();
    expect($log->user_id)->toBeNull()->and($log->properties['source'])->toBe('github');
});

it('menolak penurunan progres otomatis', function () {
    ($this->push)([($this->commit)(1, 'Rapikan [progress:5%]')])->assertStatus(202);

    expect($this->project->fresh()->progress_percent)->toBe(10);
});

it('mengabaikan nilai di luar 0-100 dan tetap membuang tag dari pesan', function () {
    ($this->push)([($this->commit)(1, 'Uji [progress:150%]')])->assertStatus(202);

    $commit = ProjectGitCommit::first();
    expect($this->project->fresh()->progress_percent)->toBe(10)
        ->and($commit->commit_message)->toBe('Uji')
        ->and($commit->progress_recorded)->toBeNull();
});

it('tidak mengubah progres saat proyek bukan active atau on_hold, tetapi tetap mencatat commit', function () {
    $this->project->forceFill(['status' => ProjectStatus::AwaitingDp])->save();

    ($this->push)([($this->commit)(1, 'Awal [progress:70%]')])->assertStatus(202);

    expect($this->project->fresh()->progress_percent)->toBe(10)
        ->and(ProjectGitCommit::count())->toBe(1)
        ->and(WebhookDelivery::latest('id')->first()->error)->toBe('progress_ignored_status');
});

// ---------------------------------------------------------------- T5-05: pagar 100%

it('menahan progres di 99 dan status tetap active sampai admin mengonfirmasi', function () {
    $milestone = ProjectMilestone::factory()->for($this->project)->create(['target_progress' => 100, 'sort_order' => 0]);

    ($this->push)([($this->commit)(1, 'Rilis [progress:100%]')])->assertStatus(202);

    $project = $this->project->fresh();
    expect($project->progress_percent)->toBe(99)
        ->and($project->status)->toBe(ProjectStatus::Active)
        ->and($project->progress_pending_confirm_at)->not->toBeNull()
        ->and($milestone->fresh()->status)->not->toBe(MilestoneStatus::Done);

    Livewire::actingAs($this->admin)->test(Show::class, ['project' => $project->id])
        ->assertSee(__('projects.webhook.pending_title'));
});

it('admin mengonfirmasi 100% sehingga status pindah ke awaiting_final', function () {
    $milestone = ProjectMilestone::factory()->for($this->project)->create(['target_progress' => 100, 'sort_order' => 0]);
    ($this->push)([($this->commit)(1, 'Rilis [progress:100%]')]);

    Livewire::actingAs($this->admin)->test(Show::class, ['project' => $this->project->id])
        ->call('confirmFullProgress');

    $project = $this->project->fresh();
    expect($project->status)->toBe(ProjectStatus::AwaitingFinal)
        ->and($project->progress_percent)->toBe(100)
        ->and($project->progress_pending_confirm_at)->toBeNull()
        ->and($milestone->fresh()->status)->toBe(MilestoneStatus::Done);
});

it('hanya admin yang bisa mengonfirmasi 100%', function () {
    ProjectMilestone::factory()->for($this->project)->create(['target_progress' => 100, 'sort_order' => 0]);
    ($this->push)([($this->commit)(1, 'Rilis [progress:100%]')]);

    // Klien pemilik tidak berwenang; klien lain bahkan tidak bisa membuka proyeknya.
    Livewire::actingAs($this->client)->test(Show::class, ['project' => $this->project->id])
        ->call('confirmFullProgress')
        ->assertForbidden();
    Livewire::actingAs(User::factory()->client()->create())->test(Show::class, ['project' => $this->project->id])
        ->assertNotFound();

    expect($this->project->fresh()->status)->toBe(ProjectStatus::Active);
});

// ---------------------------------------------------------------- T5-06: urutan dan commit ganda

it('menyimpan commit sesuai waktu push walau tiba terbalik dan tidak menggandakan hash yang sama', function () {
    ($this->push)([
        ($this->commit)(2, 'Baru', '2026-11-27T12:00:00+00:00'),
        ($this->commit)(1, 'Lama [progress:30%]', '2026-11-27T09:00:00+00:00'),
    ])->assertStatus(202);
    // force-push mengirim ulang commit yang sama di delivery lain
    ($this->push)([($this->commit)(1, 'Lama [progress:30%]', '2026-11-27T09:00:00+00:00')])->assertStatus(202);

    expect(ProjectGitCommit::count())->toBe(2)
        ->and($this->project->commits()->first()->commit_message)->toBe('Baru')
        ->and($this->project->fresh()->progress_percent)->toBe(30);
});

// ---------------------------------------------------------------- T5-07: sanitasi dan penulis

it('membuang HTML dari pesan dan membatasi panjangnya', function () {
    ($this->push)([($this->commit)(1, '<script>alert(1)</script><b>Judul</b> '.str_repeat('x', 800))])->assertStatus(202);

    $message = ProjectGitCommit::first()->commit_message;
    expect($message)->not->toContain('<')->and(mb_strlen($message))->toBeLessThanOrEqual(500);
});

it('memetakan author_email ke staf dan tidak membocorkan hash atau email ke klien', function () {
    $staff = User::factory()->staff()->create(['email' => 'budi@quixcode.test']);

    ($this->push)([($this->commit)(1, 'Fitur login', '2026-11-27T10:00:00+00:00', 'Budi@Quixcode.test')])->assertStatus(202);

    expect(ProjectGitCommit::first()->author_id)->toBe($staff->id);

    $this->actingAs($this->client)->get(route('portal.projects.show', $this->project))
        ->assertOk()
        ->assertSee('Fitur login')
        ->assertDontSee(($this->sha)(1))
        ->assertDontSee('budi@quixcode.test');
});

it('mem-parsing tag progres dengan benar', function () {
    expect(CommitMessage::parse('Fix [progress: 60 %]')['progress'])->toBe(60)
        ->and(CommitMessage::parse('[PROGRESS:7%] saja')['message'])->toBe('saja')
        ->and(CommitMessage::parse('a [progress:20%] b [progress:40%]')['progress'])->toBe(40)
        ->and(CommitMessage::parse('[progress:101%]')['invalid_progress'])->toBeTrue()
        ->and(CommitMessage::parse('[progress:50%]')['message'])->toBe('');
});

// ---------------------------------------------------------------- T5-03: UI secret

it('admin membuat secret baru yang tersimpan terenkripsi dan tercatat', function () {
    Livewire::actingAs($this->admin)->test(Show::class, ['project' => $this->project->id])
        ->call('rotateWebhookSecret')
        ->assertSet('revealedSecret', fn ($secret) => strlen($secret) === 48);

    expect($this->project->fresh()->webhook_secret)->not->toBe('rahasia-proyek-a')
        ->and($this->project->getRawOriginal('webhook_secret'))->not->toContain('rahasia')
        ->and(ActivityLog::where('action', ActivityAction::WebhookSecretRotated->value)->count())->toBe(1);
});

it('staf tidak dapat memutar secret webhook', function () {
    $staff = User::factory()->staff()->create();
    $this->project->staff()->attach($staff->id);

    Livewire::actingAs($staff)->test(Show::class, ['project' => $this->project->id])
        ->call('rotateWebhookSecret')
        ->assertForbidden();

    expect($this->project->fresh()->webhook_secret)->toBe('rahasia-proyek-a');
});
