<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Livewire\Admin\Projects\InvoiceManager;
use App\Livewire\Portal\NotificationBell;
use App\Livewire\Portal\NotificationCenter;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Project;
use App\Models\RevisionTicket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\InvoiceIssuedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentVerifiedNotification;
use App\Notifications\TicketRepliedNotification;
use App\Services\Notifier;
use App\Support\InAppNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

beforeEach(function () {
    app()->setLocale('id');

    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->staff = User::factory()->staff()->create();
    $this->client = User::factory()->client()->create(['name' => 'Budi Santoso']);
    $this->otherClient = User::factory()->client()->create();
    $this->project = Project::factory()->for($this->client, 'client')->create([
        'title' => 'Toko Online Budi',
        'status' => ProjectStatus::AwaitingDp,
    ]);

    /** Invoice DP menunggu verifikasi dengan satu bukti bayar pending. */
    $this->makePayment = function (int $invoiceAmount = 80_000, int $paymentAmount = 80_000): Payment {
        $invoice = Invoice::factory()->for($this->project)->create([
            'type' => 'dp',
            'amount' => $invoiceAmount,
            'paid_amount' => 0,
            'status' => InvoiceStatus::AwaitingVerification,
        ]);

        return Payment::factory()->for($invoice)->create(['amount' => $paymentAmount]);
    };

    /** Notifikasi lonceng jadi untuk pengguna tertentu. */
    $this->notifyBell = function (User $user, string $key = 'invoice_issued', array $overrides = []): DatabaseNotification {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => InvoiceIssuedNotification::class,
            'data' => [
                'key' => $key,
                'project_id' => (string) $this->project->id,
                'project_title' => 'Toko Online Budi',
                'route' => 'portal.projects.invoices',
                'number' => 'QC/2026/0001',
                'amount' => 80_000,
                'currency' => 'IDR',
                ...$overrides,
            ],
        ]);
    };
});

// ------------------------------------------------------------------ pemicu email + lonceng

it('admin menerbitkan invoice: klien menerima email dan notifikasi, staf dan admin tidak', function () {
    NotificationFacade::fake();

    $invoice = Invoice::factory()->for($this->project)->create([
        'type' => 'termin', 'amount' => 50_000, 'status' => InvoiceStatus::Draft, 'due_date' => null,
    ]);

    $this->actingAs($this->admin);
    Livewire::test(InvoiceManager::class, ['project' => $this->project->id])
        ->call('issueInvoice', $invoice->id)
        ->assertHasNoErrors();

    NotificationFacade::assertSentToTimes($this->client, InvoiceIssuedNotification::class, 1);
    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, fn ($n) => $n->invoice->id === $invoice->id);
    NotificationFacade::assertNotSentTo([$this->admin, $this->staff, $this->otherClient], InvoiceIssuedNotification::class);

    $log = NotificationLog::firstOrFail();
    expect($log->user_id)->toBe($this->client->id)
        ->and($log->channel)->toBe('email')
        ->and($log->template)->toBe('invoice_issued')
        ->and($log->status)->toBe('sent');
});

it('verifikasi bukti bayar memberi tahu klien: jumlah diterima dan sisa tagihan', function () {
    NotificationFacade::fake();

    $payment = ($this->makePayment)(100_000, 30_000);

    $this->actingAs($this->admin);
    Livewire::test(InvoiceManager::class, ['project' => $this->project->id])
        ->call('verifyPayment', $payment->id)
        ->assertHasNoErrors();

    NotificationFacade::assertSentToTimes($this->client, PaymentVerifiedNotification::class, 1);
    NotificationFacade::assertNotSentTo([$this->admin, $this->staff], PaymentVerifiedNotification::class);

    $invoice = $payment->invoice()->first();
    $html = (string) (new PaymentVerifiedNotification($payment->fresh(), $invoice->fresh(), $this->project))->toMail($this->client)->render();

    expect($html)->toContain('Rp 30.000')
        ->and($html)->toContain('Sisa tagihan')
        ->and($html)->toContain('Rp 70.000')
        ->and(NotificationLog::where('template', 'payment_verified')->where('status', 'sent')->count())->toBe(1);
});

it('verifikasi yang melunasi invoice menyebut lunas', function () {
    $payment = ($this->makePayment)(80_000, 80_000);

    $this->actingAs($this->admin);
    Livewire::test(InvoiceManager::class, ['project' => $this->project->id])->call('verifyPayment', $payment->id);

    $html = (string) (new PaymentVerifiedNotification($payment->fresh(), $payment->invoice()->first(), $this->project))->toMail($this->client)->render();

    expect($html)->toContain('sudah lunas')->and($html)->not->toContain('Sisa tagihan');
});

it('verifikasi yang ditolak validasi (kelebihan bayar) tidak mengirim notifikasi apa pun', function () {
    NotificationFacade::fake();

    $payment = ($this->makePayment)(100_000, 100_001);

    $this->actingAs($this->admin);
    Livewire::test(InvoiceManager::class, ['project' => $this->project->id])
        ->call('verifyPayment', $payment->id)
        ->assertHasErrors('payment');

    NotificationFacade::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

it('penolakan bukti bayar memberi tahu klien lengkap dengan alasannya', function () {
    NotificationFacade::fake();

    $payment = ($this->makePayment)(100_000, 100_000);

    $this->actingAs($this->admin);
    Livewire::test(InvoiceManager::class, ['project' => $this->project->id])
        ->set("paymentRejectionReasons.{$payment->id}", 'Bukti tidak terbaca')
        ->call('rejectPayment', $payment->id)
        ->assertHasNoErrors();

    NotificationFacade::assertSentToTimes($this->client, PaymentRejectedNotification::class, 1);

    $fresh = $payment->fresh();
    $html = (string) (new PaymentRejectedNotification($fresh, $payment->invoice()->first(), $this->project))->toMail($this->client)->render();

    expect($fresh->status)->toBe(PaymentStatus::Rejected)
        ->and($html)->toContain('Bukti tidak terbaca')
        ->and($html)->toContain(route('portal.projects.invoices', $this->project->id))
        ->and(NotificationLog::where('template', 'payment_rejected')->count())->toBe(1);
});

it('penolakan tanpa alasan gagal validasi dan tidak mengirim notifikasi', function () {
    NotificationFacade::fake();

    $payment = ($this->makePayment)();

    $this->actingAs($this->admin);
    Livewire::test(InvoiceManager::class, ['project' => $this->project->id])
        ->call('rejectPayment', $payment->id)
        ->assertHasErrors();

    NotificationFacade::assertNothingSent();
});

it('balasan admin atau staf pada tiket memberi tahu klien tanpa memuat isi balasan', function (string $role) {
    NotificationFacade::fake();

    $ticket = RevisionTicket::factory()->for($this->project)->create(['title' => 'Logo kurang besar', 'created_by' => $this->client->id]);
    $author = $role === 'admin' ? $this->admin : $this->staff;

    TicketComment::factory()->for($ticket, 'ticket')->create(['user_id' => $author->id, 'body' => 'RAHASIA-ISI-BALASAN']);

    NotificationFacade::assertSentToTimes($this->client, TicketRepliedNotification::class, 1);
    NotificationFacade::assertNotSentTo([$this->admin, $this->staff, $this->otherClient], TicketRepliedNotification::class);

    $comment = TicketComment::firstOrFail();
    $html = (string) (new TicketRepliedNotification($comment, $ticket, $this->project))->toMail($this->client)->render();

    expect($html)->toContain('Logo kurang besar')
        ->and($html)->not->toContain('RAHASIA-ISI-BALASAN')
        ->and(NotificationLog::where('template', 'ticket_replied')->count())->toBe(1);
})->with(['admin', 'staff']);

it('komentar klien sendiri pada tiketnya tidak memicu notifikasi', function () {
    NotificationFacade::fake();

    $ticket = RevisionTicket::factory()->for($this->project)->create(['created_by' => $this->client->id]);
    TicketComment::factory()->for($ticket, 'ticket')->create(['user_id' => $this->client->id]);

    NotificationFacade::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

it('akun klien nonaktif tidak menerima email maupun notifikasi', function () {
    NotificationFacade::fake();
    $this->client->forceFill(['is_active' => false])->save();

    $payment = ($this->makePayment)();
    app(Notifier::class)->paymentVerified($payment);

    NotificationFacade::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

// ------------------------------------------------------------------ pengiriman nyata: lonceng + email + bahasa

it('pengiriman nyata menyimpan notifikasi lonceng, mencatat log email, dan mengikuti bahasa klien', function () {
    $this->client->forceFill(['locale' => 'en'])->save();
    $payment = ($this->makePayment)(80_000, 80_000);

    $this->actingAs($this->admin);
    Livewire::test(InvoiceManager::class, ['project' => $this->project->id])->call('verifyPayment', $payment->id);

    $stored = $this->client->fresh()->notifications()->firstOrFail();

    app()->setLocale('en');
    $view = InAppNotification::present($stored);
    app()->setLocale('id');
    $viewId = InAppNotification::present($stored);

    expect($stored->data['key'])->toBe('payment_verified')
        ->and($stored->data['amount'])->toBe(80_000)
        ->and($stored->read_at)->toBeNull()
        ->and($view['title'])->toBe('Payment for invoice '.$payment->invoice()->first()->invoice_number.' received')
        ->and($viewId['title'])->toContain('diterima')
        ->and($view['url'])->toBe(route('portal.projects.invoices', $this->project->id))
        ->and(NotificationLog::where('template', 'payment_verified')->firstOrFail()->status)->toBe('sent')
        ->and($this->admin->notifications()->count())->toBe(0);
});

it('notifikasi lonceng tidak menyimpan teks jadi atau URL, hanya kunci dan angka', function () {
    ($this->makePayment)();
    $payment = Payment::firstOrFail();

    app(Notifier::class)->paymentRejected($payment->forceFill(['rejection_reason' => 'Nominal salah']));

    $data = $this->client->notifications()->firstOrFail()->data;

    expect($data['key'])->toBe('payment_rejected')
        ->and($data['reason'])->toBe('Nominal salah')
        ->and($data['route'])->toBe('portal.projects.invoices')
        ->and(json_encode($data))->not->toContain('http');
});

it('kegagalan email tidak menggagalkan aksi admin, dan lonceng tetap tersimpan', function () {
    Mail::extend('boom', fn () => new class extends AbstractTransport
    {
        protected function doSend(SentMessage $message): void
        {
            throw new RuntimeException('smtp down');
        }

        public function __toString(): string
        {
            return 'boom';
        }
    });
    config(['mail.default' => 'boom', 'mail.mailers.boom' => ['transport' => 'boom']]);

    $payment = ($this->makePayment)();

    $this->actingAs($this->admin);
    Livewire::test(InvoiceManager::class, ['project' => $this->project->id])
        ->call('verifyPayment', $payment->id)
        ->assertHasNoErrors();

    $log = NotificationLog::where('template', 'payment_verified')->firstOrFail();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Verified)
        ->and($this->client->notifications()->count())->toBe(1)
        ->and($log->status)->toBe('failed')
        ->and($log->error)->toContain('smtp down');
});

// ------------------------------------------------------------------ lonceng

it('lonceng menampilkan jumlah belum dibaca milik klien sendiri', function () {
    ($this->notifyBell)($this->client);
    ($this->notifyBell)($this->client, 'payment_verified');
    ($this->notifyBell)($this->otherClient);

    $this->actingAs($this->client);

    Livewire::test(NotificationBell::class)
        ->assertSee('data-notification-count', false)
        ->assertSee('2 belum dibaca');

    $this->get(route('portal.overview'))
        ->assertOk()
        ->assertSee('data-notification-bell', false)
        ->assertSee('data-unread="2"', false)
        ->assertSee(route('portal.notifications'), false);
});

it('tanpa notifikasi belum dibaca, lencana tidak tampil; angka di atas 99 diringkas', function () {
    $this->actingAs($this->client);

    Livewire::test(NotificationBell::class)->assertDontSee('data-notification-count', false);

    foreach (range(1, 100) as $i) {
        ($this->notifyBell)($this->client);
    }

    Livewire::test(NotificationBell::class)->assertSee('99+');
});

it('lonceng ada di layout portal dan tidak di area admin', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-notification-bell', false);
});

// ------------------------------------------------------------------ pusat notifikasi

it('pusat notifikasi hanya memuat milik klien sendiri, terbaru lebih dulu, dengan teks sesuai bahasa', function () {
    ($this->notifyBell)($this->client, 'invoice_issued', ['number' => 'QC/2026/0100']);
    ($this->notifyBell)($this->otherClient, 'invoice_issued', ['number' => 'QC/2026/0999']);

    $this->actingAs($this->client);

    $this->get(route('portal.notifications'))
        ->assertOk()
        ->assertSee('Invoice QC/2026/0100 terbit')
        ->assertDontSee('QC/2026/0999')
        ->assertSee('data-portal-layout', false);

    // Bahasa ditentukan middleware SetLocale dari akun, bukan dari app()->setLocale() di tes.
    $this->client->update(['locale' => 'en']);
    $this->actingAs($this->client->fresh());

    $this->get(route('portal.notifications'))->assertSee('Invoice QC/2026/0100 issued');
});

it('pusat notifikasi menampilkan empty state yang jelas', function () {
    $this->actingAs($this->client);

    Livewire::test(NotificationCenter::class)
        ->assertSee('Belum ada notifikasi')
        ->assertDontSee('data-test="mark-all-read"', false);
});

it('membuka notifikasi menandainya dibaca dan membawa klien ke halaman tagihan proyek', function () {
    $notification = ($this->notifyBell)($this->client);

    $this->actingAs($this->client);

    Livewire::test(NotificationCenter::class)
        ->call('open', $notification->id)
        ->assertRedirect(route('portal.projects.invoices', $this->project->id));

    expect($notification->fresh()->read_at)->not->toBeNull()
        ->and($this->client->unreadNotifications()->count())->toBe(0);
});

it('id notifikasi klien lain tidak bisa dibuka atau ditandai dari browser', function () {
    $foreign = ($this->notifyBell)($this->otherClient);

    $this->actingAs($this->client);

    Livewire::test(NotificationCenter::class)->call('open', $foreign->id)->assertNotFound();
    Livewire::test(NotificationCenter::class)->call('markAsRead', $foreign->id)->assertNotFound();

    expect($foreign->fresh()->read_at)->toBeNull();
});

it('tandai semua dibaca hanya memengaruhi notifikasi klien sendiri', function () {
    ($this->notifyBell)($this->client);
    ($this->notifyBell)($this->client, 'payment_verified');
    $foreign = ($this->notifyBell)($this->otherClient);

    $this->actingAs($this->client);

    Livewire::test(NotificationCenter::class)
        ->assertSee('data-test="mark-all-read"', false)
        ->call('markAllAsRead')
        ->assertDontSee('data-test="mark-all-read"', false);

    expect($this->client->unreadNotifications()->count())->toBe(0)
        ->and($foreign->fresh()->read_at)->toBeNull();
});

it('notifikasi dengan kunci tak dikenal atau route di luar portal tidak menghasilkan tautan', function () {
    $unknown = ($this->notifyBell)($this->client, 'kunci_tidak_ada');
    $evil = ($this->notifyBell)($this->client, 'invoice_issued', ['route' => 'admin.dashboard']);

    expect(InAppNotification::present($unknown)['url'])->toBeNull()
        ->and(InAppNotification::present($unknown)['title'])->toBe('Pembaruan baru')
        ->and(InAppNotification::present($evil)['url'])->toBeNull();
});

it('staf, admin, dan tamu tidak bisa membuka pusat notifikasi klien', function () {
    $this->get(route('portal.notifications'))->assertRedirect(route('login'));

    $this->actingAs($this->staff)->get(route('portal.notifications'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('portal.notifications'))->assertForbidden();
});

it('jumlah query pusat notifikasi tidak bertambah saat notifikasi bertambah (tanpa N+1)', function () {
    $this->actingAs($this->client);

    ($this->notifyBell)($this->client);
    DB::enableQueryLog();
    $this->get(route('portal.notifications'))->assertOk();
    $few = count(DB::getQueryLog());

    foreach (range(1, 15) as $i) {
        ($this->notifyBell)($this->client, 'payment_verified');
    }
    DB::flushQueryLog();
    $this->get(route('portal.notifications'))->assertOk();
    $many = count(DB::getQueryLog());

    expect($many)->toBeLessThanOrEqual($few);
});

// ------------------------------------------------------------------ bahasa

it('semua teks notifikasi tersedia dalam dua bahasa dengan kunci yang sama', function () {
    foreach (['notifications', 'mail'] as $file) {
        $id = array_keys(Arr::dot(require lang_path("id/{$file}.php")));
        $en = array_keys(Arr::dot(require lang_path("en/{$file}.php")));

        expect($en)->toEqualCanonicalizing($id);
    }
});

it('setiap jenis notifikasi lonceng yang dikirim sistem punya teks judul dan isi', function () {
    $keys = ['quotation_sent', 'quotation_expiring', 'dp_invoice', 'invoice_issued', 'payment_verified', 'payment_rejected', 'ticket_replied'];
    $id = require lang_path('id/notifications.php');
    $en = require lang_path('en/notifications.php');

    foreach ($keys as $key) {
        expect($id['items'][$key])->toHaveKeys(['title', 'body'])
            ->and($en['items'][$key])->toHaveKeys(['title', 'body']);
    }
});
