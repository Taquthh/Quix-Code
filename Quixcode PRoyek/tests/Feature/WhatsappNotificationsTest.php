<?php

use App\Contracts\WhatsappGateway;
use App\Exceptions\WhatsappGatewayException;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Project;
use App\Models\User;
use App\Notifications\InvoiceIssuedNotification;
use App\Services\NotificationTemplateService;
use App\Services\Notifier;
use App\Services\Whatsapp\HttpWhatsappGateway;
use App\Services\WhatsappCircuitBreaker;
use App\Services\WhatsappSender;
use App\Support\WhatsappConsent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/** Gateway palsu: mencatat pesan dan bisa dipaksa gagal. */
class FakeWhatsappGateway implements WhatsappGateway
{
    /** @var list<array{to: string, message: string, reference: string}> */
    public array $sent = [];

    public bool $fail = false;

    public function send(string $to, string $message, string $reference): ?string
    {
        if ($this->fail) {
            throw new WhatsappGatewayException('gateway mati');
        }

        $this->sent[] = compact('to', 'message', 'reference');

        return 'wamid-'.count($this->sent);
    }
}

beforeEach(function () {
    app()->setLocale('id');
    Cache::flush();

    config(['services.whatsapp.driver' => 'log']);
    $this->gateway = new FakeWhatsappGateway;
    app()->instance(WhatsappGateway::class, $this->gateway);

    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->client = User::factory()->client()->create(['locale' => 'id', 'whatsapp' => '0812-3456-7890']);
    (new WhatsappConsent)->grant($this->client, '127.0.0.1');
    $this->project = Project::factory()->active()->for($this->client, 'client')->create();

    $this->issue = fn () => app(Notifier::class)->invoiceIssued(Invoice::factory()->for($this->project)->create());
});

it('klien beropt-in menerima WhatsApp dari template, email tidak dikirim, dan lonceng tetap tersimpan', function () {
    NotificationFacade::fake();
    $invoice = Invoice::factory()->for($this->project)->create();

    app(Notifier::class)->invoiceIssued($invoice);

    expect($this->gateway->sent)->toHaveCount(1)
        ->and($this->gateway->sent[0]['to'])->toBe('6281234567890')
        ->and($this->gateway->sent[0]['reference'])->toBe('invoice_issued:'.$invoice->id)
        ->and($this->gateway->sent[0]['message'])->toContain($invoice->invoice_number)->toContain($this->client->name);

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, fn ($n, $channels) => $channels === ['database']);

    $log = NotificationLog::firstOrFail();
    expect(NotificationLog::count())->toBe(1)
        ->and($log->channel)->toBe('whatsapp')
        ->and($log->status)->toBe('sent')
        ->and($log->provider_message_id)->toBe('wamid-1')
        ->and($log->attempts)->toBe(1)
        ->and($log->locale)->toBe('id')
        ->and($log->sent_at)->not->toBeNull();
});

it('memakai template WhatsApp buatan admin dan mencatat versinya', function () {
    NotificationFacade::fake();
    app(NotificationTemplateService::class)->save('invoice_issued', 'id', 'whatsapp', null, 'Tagihan {{number}} untuk {{project}}: {{url}}', $this->admin);

    ($this->issue)();

    expect($this->gateway->sent[0]['message'])->toStartWith('Tagihan ')->toContain($this->project->title)
        ->and(NotificationLog::firstOrFail()->template_version)->toBe(1);
});

it('tanpa opt-in tidak ada WhatsApp dan email berjalan seperti biasa', function () {
    NotificationFacade::fake();
    (new WhatsappConsent)->revoke($this->client);

    ($this->issue)();

    expect($this->gateway->sent)->toBe([])
        ->and(NotificationLog::where('channel', 'whatsapp')->count())->toBe(0)
        ->and(NotificationLog::where('channel', 'email')->count())->toBe(1)
        ->and(NotificationLog::firstOrFail()->fallback_used)->toBeFalse();

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, fn ($n, $channels) => $channels === ['database', 'mail']);
});

it('opt-in tanpa nomor, klien nonaktif, atau driver mati tidak mengirim WhatsApp', function () {
    NotificationFacade::fake();

    $this->client->forceFill(['whatsapp' => null])->save();
    ($this->issue)();

    $this->client->forceFill(['whatsapp' => '081234567890'])->save();
    config(['services.whatsapp.driver' => 'disabled']);
    ($this->issue)();

    expect($this->gateway->sent)->toBe([])
        ->and(NotificationLog::where('channel', 'email')->count())->toBe(2);
});

it('gateway gagal mengalihkan ke email dan menandai fallback', function () {
    NotificationFacade::fake();
    $this->gateway->fail = true;

    ($this->issue)();

    $wa = NotificationLog::where('channel', 'whatsapp')->firstOrFail();
    $email = NotificationLog::where('channel', 'email')->firstOrFail();

    expect($wa->status)->toBe('failed')->and($wa->error)->toBe('gateway mati')
        ->and($email->status)->toBe('sent')->and($email->fallback_used)->toBeTrue();

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, fn ($n, $channels) => $channels === ['database', 'mail']);
});

it('kegagalan beruntun membuka sirkuit dan gateway tidak dipanggil lagi sampai sirkuit menutup', function () {
    NotificationFacade::fake();
    config(['quix.whatsapp.breaker_threshold' => 2, 'quix.whatsapp.breaker_minutes' => 10]);
    $this->gateway->fail = true;

    ($this->issue)();
    ($this->issue)();
    expect(app(WhatsappCircuitBreaker::class)->isOpen())->toBeTrue();

    $this->gateway->fail = false;
    ($this->issue)();

    expect($this->gateway->sent)->toBe([])
        ->and(NotificationLog::where('channel', 'whatsapp')->where('status', 'skipped')->where('error', 'circuit_open')->count())->toBe(1)
        ->and(NotificationLog::where('channel', 'email')->count())->toBe(3);

    // Sirkuit menutup setelah masa tunggu; pengiriman berhasil mereset hitungan.
    $this->travel(11)->minutes();
    expect(app(WhatsappCircuitBreaker::class)->isOpen())->toBeFalse();

    ($this->issue)();
    expect($this->gateway->sent)->toHaveCount(1);
    $this->travelBack();
});

it('pengiriman ulang untuk kejadian yang sama tidak mengirim WhatsApp atau email dua kali', function () {
    NotificationFacade::fake();
    $invoice = Invoice::factory()->for($this->project)->create();

    expect(app(Notifier::class)->invoiceIssued($invoice))->toBeNull();
    app(Notifier::class)->invoiceIssued($invoice);

    expect($this->gateway->sent)->toHaveCount(1)
        ->and(NotificationLog::count())->toBe(1);

    NotificationFacade::assertSentToTimes($this->client, InvoiceIssuedNotification::class, 1);
});

it('WhatsApp yang gagal untuk kejadian tertentu boleh dicoba lagi', function () {
    NotificationFacade::fake();
    $invoice = Invoice::factory()->for($this->project)->create();

    $this->gateway->fail = true;
    app(Notifier::class)->invoiceIssued($invoice);
    $this->gateway->fail = false;
    app(Notifier::class)->invoiceIssued($invoice);

    expect($this->gateway->sent)->toHaveCount(1)
        ->and(NotificationLog::where('channel', 'whatsapp')->pluck('status')->sort()->values()->all())->toBe(['failed', 'sent']);
});

it('nomor dinormalkan ke format internasional tanpa plus', function () {
    $sender = app(WhatsappSender::class);

    expect($sender->normalizePhone('0812-3456-7890'))->toBe('6281234567890')
        ->and($sender->normalizePhone('+62 812 3456 7890'))->toBe('6281234567890')
        ->and($sender->normalizePhone('628123456789'))->toBe('628123456789');
});

it('driver HTTP mengirim JSON dengan token dan melempar galat bila gateway menolak', function () {
    config(['services.whatsapp.url' => 'https://gateway.test/send', 'services.whatsapp.token' => 'rahasia']);

    Http::fake(['gateway.test/*' => Http::sequence()->push(['id' => 'abc123'], 200)->whenEmpty(Http::response(['error' => 'x'], 500))]);

    $gateway = new HttpWhatsappGateway;

    expect($gateway->send('6281234567890', 'Halo', 'ref-1'))->toBe('abc123');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer rahasia')
        && $request['to'] === '6281234567890' && $request['reference'] === 'ref-1');

    expect(fn () => $gateway->send('6281234567890', 'Halo', 'ref-2'))->toThrow(WhatsappGatewayException::class);
});
