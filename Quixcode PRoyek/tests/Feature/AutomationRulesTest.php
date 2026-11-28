<?php

use App\Enums\ActivityAction;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\ProjectStatus;
use App\Enums\TicketStatus;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Project;
use App\Models\ProjectStatusHistory;
use App\Models\RevisionTicket;
use App\Models\TicketComment;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Notifications\InvoiceReminderNotification;
use App\Notifications\TicketSlaNotification;
use App\Services\AutomationService;
use App\Support\InAppNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;

beforeEach(function () {
    // Hari ini 10 Des 2026 pukul 10:00 UTC: batas H-3 / H0 / H+3, 14 hari, dan 48 jam jadi deterministik.
    $this->travelTo(Carbon::parse('2026-12-10 10:00:00'));
    app()->setLocale('id');

    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);
    $this->staff = User::factory()->staff()->create();
    $this->client = User::factory()->client()->create();

    $this->project = Project::factory()->active()->for($this->client, 'client')->create();

    /** Invoice belum dibayar dengan jatuh tempo $due (Y-m-d) di proyek $project. */
    $this->makeInvoice = fn (string $due, array $overrides = [], ?Project $project = null): Invoice => Invoice::factory()
        ->for($project ?? $this->project)
        ->create([
            'status' => InvoiceStatus::Unpaid,
            'issued_at' => now()->subDays(5),
            'due_date' => $due,
            ...$overrides,
        ]);

    /** Proyek menunggu DP dengan invoice DP terbit $daysAgo hari lalu. */
    $this->makeDpProject = function (int $daysAgo, InvoiceStatus $status = InvoiceStatus::Unpaid): Project {
        $project = Project::factory()->for($this->client, 'client')->create([
            'status' => ProjectStatus::AwaitingDp,
            'final_price' => 20_000_000,
        ]);

        Invoice::factory()->for($project)->create([
            'type' => InvoiceType::Dp,
            'status' => $status,
            'issued_at' => now()->subDays($daysAgo),
            'due_date' => now()->subDays($daysAgo)->addDays(7)->toDateString(),
        ]);

        return $project;
    };

    /** Tiket klien berumur $hoursAgo jam. */
    $this->makeTicket = fn (int $hoursAgo, array $overrides = [], ?Project $project = null): RevisionTicket => RevisionTicket::factory()->create([
        'project_id' => ($project ?? $this->project)->id,
        'created_by' => $this->client->id,
        'created_at' => now()->subHours($hoursAgo),
        ...$overrides,
    ]);
});

afterEach(fn () => $this->travelBack());

// ------------------------------------------------------------------ pengingat invoice

it('jenis pengingat mengikuti selisih hari ke jatuh tempo: H-3, H0, H+3', function (string $due, ?string $expected) {
    expect(AutomationService::reminderKind(Carbon::parse($due), Carbon::parse('2026-12-10 10:00:00')))->toBe($expected);
})->with([
    'H-3' => ['2026-12-13', 'due_soon'],
    'besok' => ['2026-12-11', 'due_soon'],
    'hari ini' => ['2026-12-10', 'due_today'],
    'kemarin: belum waktunya' => ['2026-12-09', null],
    'H+2: belum waktunya' => ['2026-12-08', null],
    'H+3' => ['2026-12-07', 'overdue'],
    'lama terlambat' => ['2026-11-01', 'overdue'],
    'masih jauh' => ['2026-12-14', null],
]);

it('invoice yang jatuh tempo dalam 3 hari, hari ini, dan terlambat 3 hari masing-masing diingatkan', function () {
    NotificationFacade::fake();

    ($this->makeInvoice)('2026-12-13');
    ($this->makeInvoice)('2026-12-10');
    ($this->makeInvoice)('2026-12-07');
    ($this->makeInvoice)('2026-12-20'); // masih jauh

    $this->artisan('invoices:remind')->expectsOutputToContain('3 pengingat invoice dikirim')->assertSuccessful();

    $kinds = [];
    NotificationFacade::assertSentToTimes($this->client, InvoiceReminderNotification::class, 3);
    NotificationFacade::assertSentTo($this->client, InvoiceReminderNotification::class, function ($n) use (&$kinds) {
        $kinds[] = $n->kind;

        return true;
    });

    expect($kinds)->toEqualCanonicalizing(['due_soon', 'due_today', 'overdue'])
        ->and(DB::table('invoice_reminders')->count())->toBe(3)
        ->and(NotificationLog::where('template', 'invoice_reminder_due_today')->firstOrFail()->status)->toBe('sent');

    NotificationFacade::assertNotSentTo($this->admin, InvoiceReminderNotification::class);
    NotificationFacade::assertNotSentTo($this->staff, InvoiceReminderNotification::class);
});

it('menjalankan ulang pada hari yang sama tidak mengirim ganda dan hanya satu catatan per invoice dan jenis', function () {
    NotificationFacade::fake();
    ($this->makeInvoice)('2026-12-12');

    Artisan::call('invoices:remind');
    Artisan::call('invoices:remind');
    Artisan::call('invoices:remind');

    NotificationFacade::assertSentToTimes($this->client, InvoiceReminderNotification::class, 1);
    expect(DB::table('invoice_reminders')->count())->toBe(1)
        ->and(ActivityLog::where('action', ActivityAction::InvoiceReminded->value)->count())->toBe(1);
});

it('satu invoice diingatkan sekali per tahap seiring hari berjalan: H-3, H0, lalu H+3', function () {
    NotificationFacade::fake();
    $invoice = ($this->makeInvoice)('2026-12-13');

    Artisan::call('invoices:remind');                       // 10 Des: H-3
    $this->travelTo(Carbon::parse('2026-12-11 10:00:00'));
    Artisan::call('invoices:remind');                       // H-2: jenis sama, tidak diulang
    $this->travelTo(Carbon::parse('2026-12-13 10:00:00'));
    Artisan::call('invoices:remind');                       // H0
    $this->travelTo(Carbon::parse('2026-12-14 10:00:00'));
    Artisan::call('invoices:remind');                       // H+1: belum waktunya
    $this->travelTo(Carbon::parse('2026-12-16 10:00:00'));
    Artisan::call('invoices:remind');                       // H+3
    Artisan::call('invoices:remind');                       // diulang: tidak ada tambahan

    $kinds = DB::table('invoice_reminders')->where('invoice_id', $invoice->id)->orderBy('id')->pluck('kind')->all();

    expect($kinds)->toBe(['due_soon', 'due_today', 'overdue']);
    NotificationFacade::assertSentToTimes($this->client, InvoiceReminderNotification::class, 3);
});

it('invoice lunas, draf, menunggu verifikasi, tanpa jatuh tempo, atau di proyek selesai dan batal tidak diingatkan', function () {
    NotificationFacade::fake();

    ($this->makeInvoice)('2026-12-12', ['status' => InvoiceStatus::Paid, 'paid_amount' => 10_000_000]);
    ($this->makeInvoice)('2026-12-12', ['status' => InvoiceStatus::Draft, 'issued_at' => null]);
    ($this->makeInvoice)('2026-12-12', ['status' => InvoiceStatus::AwaitingVerification]);
    ($this->makeInvoice)('2026-12-12', ['status' => InvoiceStatus::Void]);
    ($this->makeInvoice)('2026-12-12', ['due_date' => null]);

    $cancelled = Project::factory()->for($this->client, 'client')->create(['status' => ProjectStatus::Cancelled]);
    $closed = Project::factory()->for($this->client, 'client')->create(['status' => ProjectStatus::Closed]);
    ($this->makeInvoice)('2026-12-12', [], $cancelled);
    ($this->makeInvoice)('2026-12-12', [], $closed);

    Artisan::call('invoices:remind');

    NotificationFacade::assertNothingSent();
    expect(DB::table('invoice_reminders')->count())->toBe(0);
});

it('pengingat dicatat sebagai aksi sistem dan tidak dikirim ke klien nonaktif', function () {
    NotificationFacade::fake();
    $invoice = ($this->makeInvoice)('2026-12-12');

    Artisan::call('invoices:remind');

    $log = ActivityLog::where('action', ActivityAction::InvoiceReminded->value)->firstOrFail();
    expect($log->user_id)->toBeNull()
        ->and($log->subject_id)->toBe((string) $invoice->id)
        ->and($log->properties['kind'])->toBe('due_soon');

    $this->client->forceFill(['is_active' => false])->save();
    $second = ($this->makeInvoice)('2026-12-11');

    Artisan::call('invoices:remind');

    NotificationFacade::assertSentToTimes($this->client, InvoiceReminderNotification::class, 1);
    expect(DB::table('invoice_reminders')->where('invoice_id', $second->id)->count())->toBe(1);
});

it('pengingat nyata menyimpan lonceng dengan sisa tagihan setelah pembayaran sebagian, dan mengikuti bahasa klien', function () {
    $this->client->forceFill(['locale' => 'en'])->save();
    $invoice = ($this->makeInvoice)('2026-12-10', ['amount' => 10_000_000, 'paid_amount' => 4_000_000]);

    Artisan::call('invoices:remind');

    $stored = $this->client->fresh()->notifications()->firstOrFail();

    app()->setLocale('en');
    $en = InAppNotification::present($stored);
    app()->setLocale('id');
    $id = InAppNotification::present($stored);

    expect($stored->data['key'])->toBe('invoice_due_today')
        ->and($stored->data['amount'])->toBe(6_000_000)
        ->and($en['title'])->toBe('Invoice '.$invoice->invoice_number.' is due today')
        ->and($id['title'])->toContain('jatuh tempo hari ini')
        ->and($en['url'])->toBe(route('portal.projects.invoices', $this->project->id));
});

// ------------------------------------------------------------------ DP tak dibayar -> on_hold

it('proyek menunggu DP dijeda otomatis setelah 14 hari invoice DP belum dibayar', function () {
    $project = ($this->makeDpProject)(15);

    $this->artisan('projects:hold-unpaid-dp')->expectsOutputToContain('1 proyek dijeda')->assertSuccessful();

    $project->refresh();
    $history = ProjectStatusHistory::where('project_id', $project->id)->where('to', ProjectStatus::OnHold->value)->firstOrFail();

    expect($project->status)->toBe(ProjectStatus::OnHold)
        ->and($project->on_hold_from)->toBe('awaiting_dp')
        ->and($project->on_hold_reason)->toContain('14 hari')
        ->and($history->actor_id)->toBeNull();

    $log = ActivityLog::where('action', ActivityAction::StatusChanged->value)->where('subject_id', (string) $project->id)->firstOrFail();
    expect($log->user_id)->toBeNull()
        ->and($log->properties)->toEqual(['old' => 'awaiting_dp', 'new' => 'on_hold']);
});

it('DP yang belum 14 hari, sudah dibayar, atau menunggu verifikasi tidak dijeda', function () {
    $fresh = ($this->makeDpProject)(13);
    $awaiting = ($this->makeDpProject)(30, InvoiceStatus::AwaitingVerification);
    $paid = ($this->makeDpProject)(30, InvoiceStatus::Paid);

    $active = Project::factory()->active()->for($this->client, 'client')->create();
    Invoice::factory()->for($active)->create(['type' => InvoiceType::Dp, 'status' => InvoiceStatus::Unpaid, 'issued_at' => now()->subDays(60)]);

    Artisan::call('projects:hold-unpaid-dp');

    expect($fresh->fresh()->status)->toBe(ProjectStatus::AwaitingDp)
        ->and($awaiting->fresh()->status)->toBe(ProjectStatus::AwaitingDp)
        ->and($paid->fresh()->status)->toBe(ProjectStatus::AwaitingDp)
        ->and($active->fresh()->status)->toBe(ProjectStatus::Active);
});

it('DP yang diulang tidak menjeda dua kali, dan admin bisa melanjutkan ke menunggu DP', function () {
    $project = ($this->makeDpProject)(20);

    Artisan::call('projects:hold-unpaid-dp');
    Artisan::call('projects:hold-unpaid-dp');

    expect(ProjectStatusHistory::where('project_id', $project->id)->where('to', ProjectStatus::OnHold->value)->count())->toBe(1);

    $project->fresh()->transitionTo(ProjectStatus::AwaitingDp, $this->admin);

    expect($project->fresh()->status)->toBe(ProjectStatus::AwaitingDp)
        ->and($project->fresh()->on_hold_from)->toBeNull();
});

// ------------------------------------------------------------------ SLA tiket

it('tiket tanpa balasan tim lebih dari 48 jam dilaporkan ke admin aktif dalam satu email ringkasan', function () {
    NotificationFacade::fake();

    ($this->makeTicket)(49, ['title' => 'Tombol simpan error']);
    ($this->makeTicket)(72, ['title' => 'Logo kurang besar']);

    $this->artisan('tickets:check-sla')->expectsOutputToContain('2 tiket dilaporkan')->assertSuccessful();

    NotificationFacade::assertSentToTimes($this->admin, TicketSlaNotification::class, 1);
    NotificationFacade::assertSentTo($this->admin, TicketSlaNotification::class, fn ($n) => count($n->tickets) === 2 && $n->hours === 48);
    NotificationFacade::assertNotSentTo($this->inactiveAdmin, TicketSlaNotification::class);
    NotificationFacade::assertNotSentTo($this->staff, TicketSlaNotification::class);
    NotificationFacade::assertNotSentTo($this->client, TicketSlaNotification::class);

    expect(RevisionTicket::whereNotNull('sla_breached_at')->count())->toBe(2)
        ->and(ActivityLog::where('action', ActivityAction::TicketSlaBreached->value)->whereNull('user_id')->count())->toBe(2);
});

it('tiket yang sama hanya dilaporkan sekali, walau dijalankan berulang', function () {
    NotificationFacade::fake();
    ($this->makeTicket)(60);

    Artisan::call('tickets:check-sla');
    Artisan::call('tickets:check-sla');
    $this->travelTo(now()->addDays(3));
    Artisan::call('tickets:check-sla');

    NotificationFacade::assertSentToTimes($this->admin, TicketSlaNotification::class, 1);
});

it('tiket yang belum 48 jam, sudah dibalas admin atau staf, atau sudah selesai tidak dilaporkan', function () {
    NotificationFacade::fake();

    $young = ($this->makeTicket)(47);
    $byAdmin = ($this->makeTicket)(100);
    $byStaff = ($this->makeTicket)(100);
    $resolved = ($this->makeTicket)(100, ['status' => TicketStatus::Resolved]);
    $closedProject = Project::factory()->for($this->client, 'client')->create(['status' => ProjectStatus::Closed]);
    $inClosed = ($this->makeTicket)(100, [], $closedProject);

    TicketComment::factory()->create(['ticket_id' => $byAdmin->id, 'user_id' => $this->admin->id]);
    TicketComment::factory()->create(['ticket_id' => $byStaff->id, 'user_id' => $this->staff->id]);

    Artisan::call('tickets:check-sla');

    NotificationFacade::assertNotSentTo($this->admin, TicketSlaNotification::class);
    expect(RevisionTicket::whereNotNull('sla_breached_at')->count())->toBe(0);
});

it('komentar klien sendiri tidak dihitung sebagai balasan tim', function () {
    NotificationFacade::fake();
    $ticket = ($this->makeTicket)(60);
    TicketComment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $this->client->id]);

    Artisan::call('tickets:check-sla');

    NotificationFacade::assertSentToTimes($this->admin, TicketSlaNotification::class, 1);
    expect($ticket->fresh()->sla_breached_at)->not->toBeNull();
});

it('batas SLA bisa diatur lewat konfigurasi', function () {
    NotificationFacade::fake();
    config(['quix.automation.ticket_sla_hours' => 24]);
    ($this->makeTicket)(30);

    Artisan::call('tickets:check-sla');

    NotificationFacade::assertSentTo($this->admin, TicketSlaNotification::class, fn ($n) => $n->hours === 24);
});

// ------------------------------------------------------------------ purge payload webhook

it('payload webhook lewat masa simpan dikosongkan, barisnya tetap ada', function () {
    $old = WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'processed', 'purge_after' => now()->subDay()]);
    $ignored = WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'ignored', 'purge_after' => now()->subDay()]);
    $young = WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'processed', 'purge_after' => now()->addDays(10)]);
    $failed = WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'failed', 'purge_after' => now()->subDay()]);
    $pending = WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'received', 'purge_after' => now()->subDay()]);

    $this->artisan('webhooks:purge')->expectsOutputToContain('2 payload webhook dibersihkan')->assertSuccessful();

    expect($old->fresh()->payload)->toBe([])
        ->and($old->fresh()->payload_purged_at)->not->toBeNull()
        ->and($old->fresh()->delivery_id)->toBe($old->delivery_id)
        ->and($ignored->fresh()->payload)->toBe([])
        ->and($young->fresh()->payload)->not->toBe([])
        ->and($failed->fresh()->payload)->not->toBe([])
        ->and($pending->fresh()->payload)->not->toBe([])
        ->and($failed->fresh()->payload_purged_at)->toBeNull();
});

it('pembersihan payload aman diulang', function () {
    WebhookDelivery::factory()->create(['project_id' => $this->project->id, 'status' => 'processed', 'purge_after' => now()->subDay()]);

    expect(app(AutomationService::class)->purgeWebhookPayloads())->toBe(1)
        ->and(app(AutomationService::class)->purgeWebhookPayloads())->toBe(0);
});

it('masa simpan bawaan webhook 90 hari terpasang pada delivery baru', function () {
    expect(config('quix.webhook.purge_after_days'))->toBe(90);
});

// ------------------------------------------------------------------ penjadwalan dan teks

it('keempat aturan terdaftar di scheduler', function () {
    Artisan::call('schedule:list');
    $output = Artisan::output();

    expect($output)->toContain('invoices:remind')
        ->and($output)->toContain('projects:hold-unpaid-dp')
        ->and($output)->toContain('tickets:check-sla')
        ->and($output)->toContain('webhooks:purge');
});

it('semua teks aturan otomatis tersedia dalam dua bahasa dengan kunci yang sama', function () {
    foreach (['mail', 'notifications', 'activity', 'projects'] as $file) {
        $id = array_keys(Arr::dot(require base_path("lang/id/{$file}.php")));
        $en = array_keys(Arr::dot(require base_path("lang/en/{$file}.php")));

        expect($id)->toEqualCanonicalizing($en);
    }

    $notifications = require base_path('lang/id/notifications.php');

    foreach (['invoice_due_soon', 'invoice_due_today', 'invoice_overdue'] as $key) {
        expect($notifications['items'][$key])->toHaveKeys(['title', 'body']);
    }

    foreach (['id', 'en'] as $locale) {
        app()->setLocale($locale);

        expect(ActivityAction::InvoiceReminded->label())->not->toStartWith('activity.')
            ->and(ActivityAction::TicketSlaBreached->label())->not->toStartWith('activity.');
    }
});
