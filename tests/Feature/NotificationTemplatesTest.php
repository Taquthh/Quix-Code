<?php

use App\Enums\ActivityAction;
use App\Livewire\Admin\Settings\NotificationTemplates;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\RevisionTicket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\Contracts\UsesNotificationTemplate;
use App\Notifications\DownPaymentInvoiceNotification;
use App\Notifications\InvoiceIssuedNotification;
use App\Notifications\InvoiceReminderNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentVerifiedNotification;
use App\Notifications\QuotationExpiringNotification;
use App\Notifications\QuotationSentNotification;
use App\Notifications\TicketRepliedNotification;
use App\Services\NotificationTemplateService;
use App\Services\Notifier;
use App\Support\NotificationTemplateCatalog as Catalog;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('id');

    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->staff = User::factory()->staff()->create();
    $this->client = User::factory()->client()->create(['locale' => 'id']);
    $this->project = Project::factory()->active()->for($this->client, 'client')->create();
    $this->service = app(NotificationTemplateService::class);
});

// ------------------------------------------------------------------ seeder

it('seeder membuat template bawaan ID/EN untuk email dan WhatsApp dan aman dijalankan ulang', function () {
    $this->seed(NotificationTemplateSeeder::class);

    $expected = count(Catalog::keys()) * count(Catalog::LOCALES) * count(Catalog::CHANNELS);
    expect(NotificationTemplate::count())->toBe($expected)
        ->and(NotificationTemplate::where('version', '!=', 1)->count())->toBe(0);

    // Edit admin tidak ditimpa saat seeder dijalankan lagi.
    $this->service->save('invoice_issued', 'id', 'email', 'Subjek buatan admin', 'Isi buatan admin', $this->admin);
    $this->seed(NotificationTemplateSeeder::class);

    expect(NotificationTemplate::count())->toBe($expected + 1)
        ->and($this->service->latest('invoice_issued', 'id', 'email')->subject)->toBe('Subjek buatan admin');
});

it('semua template bawaan lolos validasi dan email punya subjek sedangkan WhatsApp tidak', function () {
    foreach (Catalog::keys() as $key) {
        foreach (Catalog::LOCALES as $locale) {
            foreach (Catalog::CHANNELS as $channel) {
                $default = Catalog::defaults($key, $locale, $channel);

                expect($this->service->validate($key, $locale, $channel, $default['subject'], $default['body']))
                    ->toBe([], "{$key}/{$locale}/{$channel}");
                expect($channel === 'email' ? $default['subject'] !== '' : $default['subject'] === null)->toBeTrue();
            }
        }
    }
});

// ------------------------------------------------------------------ akses

it('hanya admin yang boleh membuka editor template', function () {
    $this->actingAs($this->admin)->get(route('admin.settings.templates'))->assertOk()->assertSee('Template notifikasi');
    $this->actingAs($this->staff)->get(route('admin.settings.templates'))->assertForbidden();
    $this->actingAs($this->client)->get(route('admin.settings.templates'))->assertForbidden();
});

it('aksi komponen menolak staf walau sesi berganti setelah halaman dimuat', function () {
    $this->actingAs($this->admin);
    $saveComponent = Livewire::test(NotificationTemplates::class);

    $this->actingAs($this->staff);
    $saveComponent->call('save')->assertForbidden();

    $this->actingAs($this->admin);
    $defaultComponent = Livewire::test(NotificationTemplates::class);

    $this->actingAs($this->staff);
    $defaultComponent->call('useDefault')->assertForbidden();

    expect(NotificationTemplate::count())->toBe(0);
});

// ------------------------------------------------------------------ simpan, versi, validasi

it('admin mengubah isi tanpa deploy dan setiap perubahan menjadi versi baru yang tercatat', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $this->actingAs($this->admin);

    Livewire::test(NotificationTemplates::class)
        ->set('templateKey', 'invoice_issued')
        ->set('templateLocale', 'id')
        ->set('templateChannel', 'email')
        ->set('subject', 'Tagihan {{number}} siap')
        ->set('body', "Halo, tagihan untuk {{project}} sebesar {{amount}}.\n\n{{due_line}}")
        ->call('save')
        ->assertHasNoErrors();

    $latest = $this->service->latest('invoice_issued', 'id', 'email');

    expect($latest->version)->toBe(2)
        ->and($latest->subject)->toBe('Tagihan {{number}} siap')
        ->and($this->service->versions('invoice_issued', 'id', 'email'))->toHaveCount(2)
        ->and(ActivityLog::where('action', ActivityAction::NotificationTemplateUpdated->value)->where('user_id', $this->admin->id)->count())->toBe(1);

    // Template bahasa dan kanal lain tidak berubah.
    expect($this->service->latest('invoice_issued', 'en', 'email')->version)->toBe(1)
        ->and($this->service->latest('invoice_issued', 'id', 'whatsapp')->version)->toBe(1);
});

it('menyimpan isi yang sama tidak menambah versi', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $this->actingAs($this->admin);

    Livewire::test(NotificationTemplates::class)
        ->set('templateKey', 'payment_verified')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->service->latest('payment_verified', 'id', 'email')->version)->toBe(1)
        ->and(ActivityLog::where('action', ActivityAction::NotificationTemplateUpdated->value)->count())->toBe(0);
});

it('variabel tidak valid ditolak dan tidak ada versi baru yang tersimpan', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $this->actingAs($this->admin);

    Livewire::test(NotificationTemplates::class)
        ->set('templateKey', 'ticket_replied')
        ->set('body', 'Halo {{name}}, saldo {{amount}} dan {{password}}.')
        ->call('save')
        ->assertHasErrors(['body']);

    Livewire::test(NotificationTemplates::class)
        ->set('templateKey', 'ticket_replied')
        ->set('subject', 'Balasan {{invoice_number}}')
        ->call('save')
        ->assertHasErrors(['subject']);

    expect($this->service->latest('ticket_replied', 'id', 'email')->version)->toBe(1);
});

it('kurung kurawal tak berpasangan, ekspresi, subjek kosong, dan isi kosong ditolak', function () {
    $key = 'quotation_sent';

    expect($this->service->validate($key, 'id', 'email', 'Subjek', 'Halo {{name'))->toHaveKey('body')
        ->and($this->service->validate($key, 'id', 'email', 'Subjek', 'Halo name}}'))->toHaveKey('body')
        ->and($this->service->validate($key, 'id', 'email', 'Subjek', 'Halo {{ name | upper }}'))->toHaveKey('body')
        ->and($this->service->validate($key, 'id', 'email', 'Subjek', 'Halo {{ $name }}'))->toHaveKey('body')
        ->and($this->service->validate($key, 'id', 'email', 'Subjek', 'Halo {{}}'))->toHaveKey('body')
        ->and($this->service->validate($key, 'id', 'email', '', 'Isi'))->toHaveKey('subject')
        ->and($this->service->validate($key, 'id', 'email', "Dua\nbaris", 'Isi'))->toHaveKey('subject')
        ->and($this->service->validate($key, 'id', 'email', 'Subjek', '   '))->toHaveKey('body')
        ->and($this->service->validate($key, 'id', 'whatsapp', null, 'Halo {{ name }}, {{url}}'))->toBe([])
        ->and($this->service->validate($key, 'id', 'whatsapp', null, str_repeat('a', 1001)))->toHaveKey('body')
        ->and($this->service->validate('kunci_asing', 'id', 'email', 'S', 'B'))->toHaveKey('key')
        ->and($this->service->validate($key, 'fr', 'email', 'S', 'B'))->toHaveKey('key')
        ->and($this->service->validate($key, 'id', 'sms', 'S', 'B'))->toHaveKey('key');
});

it('variabel hanya boleh yang tersedia untuk kunci tersebut', function () {
    // {{reason}} sah untuk payment_rejected tetapi tidak untuk invoice_issued.
    expect($this->service->validate('payment_rejected', 'id', 'email', 'S', '{{reason}}'))->toBe([])
        ->and($this->service->validate('invoice_issued', 'id', 'email', 'S', '{{reason}}'))->toHaveKey('body');
});

it('service menolak simpan langsung lewat ValidationException', function () {
    expect(fn () => $this->service->save('invoice_issued', 'id', 'email', 'S', '{{bukan_variabel}}', $this->admin))
        ->toThrow(ValidationException::class);

    expect(NotificationTemplate::count())->toBe(0);
});

it('pilihan di luar katalog dari klien dikembalikan ke nilai aman', function () {
    $this->actingAs($this->admin);

    Livewire::test(NotificationTemplates::class)
        ->set('templateKey', 'kunci_asing')
        ->assertSet('templateKey', Catalog::keys()[0])
        ->set('templateChannel', 'sms')
        ->assertSet('templateChannel', 'email');
});

it('pakai teks bawaan dan muat versi lama mengisi editor tanpa menyimpan', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $this->service->save('payment_rejected', 'id', 'email', 'Subjek v2', 'Isi v2 {{reason}}', $this->admin);
    $this->actingAs($this->admin);

    $default = Catalog::defaults('payment_rejected', 'id', 'email');

    Livewire::test(NotificationTemplates::class)
        ->set('templateKey', 'payment_rejected')
        ->assertSet('subject', 'Subjek v2')
        ->call('useDefault')
        ->assertSet('subject', $default['subject'])
        ->assertSet('body', $default['body'])
        ->call('restore', 2)
        ->assertSet('subject', 'Subjek v2');

    expect($this->service->latest('payment_rejected', 'id', 'email')->version)->toBe(2);
});

// ------------------------------------------------------------------ pratinjau

it('pratinjau mengisi variabel dengan data contoh dan memisahkan paragraf', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(NotificationTemplates::class)
        ->set('templateKey', 'invoice_issued')
        ->set('subject', 'Invoice {{number}} untuk {{project}}')
        ->set('body', "Halo {{name}}.\n\nJumlah {{amount}}.");

    $preview = $component->instance()->preview();
    $sample = Catalog::sample('id');

    expect($preview->subject)->toBe("Invoice {$sample['number']} untuk {$sample['project']}")
        ->and($preview->paragraphs)->toBe(["Halo {$sample['name']}.", "Jumlah {$sample['amount']}."]);

    $component->assertSee($sample['number']);
});

it('render mengisi variabel dalam satu lintasan dan membuang paragraf kosong', function () {
    $rendered = $this->service->render(
        "Subjek   {{ a }}\n",
        "Satu {{a}}\n\n{{kosong}}\n\nDua {{b}}",
        ['a' => '{{b}}', 'b' => 'B', 'kosong' => ''],
        3,
    );

    expect($rendered->subject)->toBe('Subjek {{b}}')
        ->and($rendered->paragraphs)->toBe(['Satu {{b}}', 'Dua B'])
        ->and($rendered->version)->toBe(3)
        ->and($rendered->text())->toBe("Satu {{b}}\n\nDua B");
});

// ------------------------------------------------------------------ pengiriman

it('email memakai template admin dan versinya tercatat di log kirim', function () {
    NotificationFacade::fake();
    $this->seed(NotificationTemplateSeeder::class);
    $this->service->save('invoice_issued', 'id', 'email', 'Tagihan {{number}} siap', "Halo {{name}}, tagihan {{project}} sebesar {{amount}}.\n\n{{due_line}}", $this->admin);

    $invoice = Invoice::factory()->for($this->project)->create();

    app(Notifier::class)->invoiceIssued($invoice);

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, function (InvoiceIssuedNotification $n) use ($invoice) {
        $mail = $n->toMail($this->client);

        return $mail->subject === "Tagihan {$invoice->invoice_number} siap"
            && $mail->introLines === [
                "Halo {$this->client->name}, tagihan {$this->project->title} sebesar {$invoice->money()->format()}.",
                __('mail.invoice_issued.due', ['date' => $invoice->dueDateLabel()]),
            ]
            && $mail->outroLines === []
            && $mail->actionText === __('mail.invoice_issued.action')
            && $mail->greeting === __('mail.greeting', ['name' => $this->client->name]);
    });

    $log = NotificationLog::firstOrFail();

    expect($log->template)->toBe('invoice_issued')
        ->and($log->template_version)->toBe(2)
        ->and($log->locale)->toBe('id')
        ->and($log->status)->toBe('sent');
});

it('perubahan template berikutnya langsung dipakai pengiriman berikutnya', function () {
    NotificationFacade::fake();
    $this->service->save('invoice_issued', 'id', 'email', 'Satu', 'Isi satu', $this->admin);
    $this->service->save('invoice_issued', 'id', 'email', 'Dua', 'Isi dua', $this->admin);

    app(Notifier::class)->invoiceIssued(Invoice::factory()->for($this->project)->create());

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, fn ($n) => $n->toMail($this->client)->subject === 'Dua');
    expect(NotificationLog::firstOrFail()->template_version)->toBe(2);
});

it('tanpa template tersimpan email tetap memakai teks bawaan dan versi log kosong', function () {
    NotificationFacade::fake();

    $invoice = Invoice::factory()->for($this->project)->create();
    app(Notifier::class)->invoiceIssued($invoice);

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, function (InvoiceIssuedNotification $n) use ($invoice) {
        return $n->toMail($this->client)->subject === __('mail.invoice_issued.subject', ['number' => $invoice->invoice_number, 'project' => $this->project->title]);
    });

    expect(NotificationLog::firstOrFail()->template_version)->toBeNull();
});

it('template bahasa lain tidak dipakai untuk penerima dengan bahasa berbeda', function () {
    NotificationFacade::fake();
    $this->service->save('invoice_issued', 'en', 'email', 'English only', 'English body', $this->admin);

    app(Notifier::class)->invoiceIssued(Invoice::factory()->for($this->project)->create());

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, fn ($n) => $n->toMail($this->client)->subject !== 'English only');
    expect(NotificationLog::firstOrFail()->template_version)->toBeNull();

    $this->client->update(['locale' => 'en']);
    NotificationLog::query()->delete();

    app(Notifier::class)->invoiceIssued(Invoice::factory()->for($this->project)->create());

    expect(NotificationLog::firstOrFail()->template_version)->toBe(1)
        ->and(NotificationLog::firstOrFail()->locale)->toBe('en');
});

it('template kanal WhatsApp tidak memengaruhi email', function () {
    NotificationFacade::fake();
    $this->service->save('invoice_issued', 'id', 'whatsapp', null, 'Hanya WhatsApp {{url}}', $this->admin);

    app(Notifier::class)->invoiceIssued(Invoice::factory()->for($this->project)->create());

    expect(NotificationLog::firstOrFail()->template_version)->toBeNull();
});

it('variabel tak dikenal di template lama di database menjadi kosong dan pengiriman tetap berjalan', function () {
    NotificationFacade::fake();
    NotificationTemplate::create(['key' => 'invoice_issued', 'locale' => 'id', 'channel' => 'email', 'subject' => 'Rusak {{tak_dikenal}}', 'body' => 'Isi {{tak_dikenal}}', 'version' => 1]);

    $invoice = Invoice::factory()->for($this->project)->create();
    app(Notifier::class)->invoiceIssued($invoice);

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, fn ($n) => $n->toMail($this->client)->subject === 'Rusak');
    expect(NotificationLog::firstOrFail()->status)->toBe('sent');
});

// ------------------------------------------------------------------ konsistensi katalog dan notifikasi

it('setiap notifikasi bertemplat memakai kunci katalog dan hanya variabel yang sah', function () {
    $invoice = Invoice::factory()->for($this->project)->create();
    $payment = Payment::factory()->for($invoice)->create(['rejection_reason' => 'Nominal berbeda']);
    $quotation = Quotation::factory()->for($this->project)->sent()->create();
    $ticket = RevisionTicket::factory()->for($this->project)->create();
    $comment = TicketComment::factory()->for($ticket, 'ticket')->create(['user_id' => $this->admin->id]);

    $notifications = [
        new QuotationSentNotification($quotation, $this->project),
        new QuotationExpiringNotification($quotation, $this->project),
        new DownPaymentInvoiceNotification($invoice, $this->project),
        new InvoiceIssuedNotification($invoice, $this->project),
        new InvoiceReminderNotification($invoice, $this->project, 'due_soon'),
        new InvoiceReminderNotification($invoice, $this->project, 'due_today'),
        new InvoiceReminderNotification($invoice, $this->project, 'overdue'),
        new PaymentVerifiedNotification($payment, $invoice, $this->project),
        new PaymentRejectedNotification($payment, $invoice, $this->project),
        new TicketRepliedNotification($comment, $ticket, $this->project),
    ];

    expect(collect($notifications)->map->templateKey()->sort()->values()->all())->toBe(collect(Catalog::keys())->sort()->values()->all());

    foreach ($notifications as $notification) {
        expect($notification)->toBeInstanceOf(UsesNotificationTemplate::class);

        $variables = $notification->templateVariables($this->client);

        // Variabel yang dihitung notifikasi harus sama persis dengan yang ditawarkan editor.
        expect(array_keys($variables))->toEqualCanonicalizing(Catalog::variables($notification->templateKey()));
    }
});

it('template bawaan menghasilkan email yang setara dengan teks lang untuk invoice terbit', function () {
    NotificationFacade::fake();
    $this->seed(NotificationTemplateSeeder::class);

    $invoice = Invoice::factory()->for($this->project)->create();
    app(Notifier::class)->invoiceIssued($invoice);

    NotificationFacade::assertSentTo($this->client, InvoiceIssuedNotification::class, function (InvoiceIssuedNotification $n) use ($invoice) {
        $mail = $n->toMail($this->client);

        return $mail->subject === __('mail.invoice_issued.subject', ['number' => $invoice->invoice_number, 'project' => $this->project->title])
            && $mail->introLines[0] === __('mail.invoice_issued.intro', ['project' => $this->project->title])
            && in_array(__('mail.invoice_issued.amount', ['amount' => $invoice->money()->format()]), $mail->introLines, true)
            && end($mail->introLines) === __('mail.invoice_issued.outro');
    });
});
