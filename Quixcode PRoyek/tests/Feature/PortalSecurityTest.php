<?php

/*
|--------------------------------------------------------------------------
| T4-11 — Tes keamanan portal klien
|--------------------------------------------------------------------------
| Menutup tiga bagian kriteria T4-11 yang berbasis Feature test:
|   1. IDOR: klien B ke ID milik klien A di SETIAP route portal dan route berkas.
|   2. Aksi Livewire dengan ID dipalsukan dari browser.
|   3. Properti publik penentu data harus #[Locked] (diaudit otomatis lewat reflection).
| E2E browser 375px ada di tests/Browser/ClientJourneyTest.php.
*/

use App\Enums\FileVisibility;
use App\Enums\MilestoneStatus;
use App\Enums\QuotationStatus;
use App\Livewire\Chat\Room;
use App\Livewire\Portal\Handover;
use App\Livewire\Portal\Invoices;
use App\Livewire\Portal\Negotiation;
use App\Livewire\Portal\NotificationCenter;
use App\Livewire\Portal\ProjectOverview;
use App\Models\Invoice;
use App\Models\MilestoneAcceptance;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectMilestone;
use App\Models\Quotation;
use App\Models\RevisionTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('id');
    Storage::fake('local');
    Storage::fake('private');

    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->staff = User::factory()->staff()->create();
    $this->owner = User::factory()->client()->create();
    $this->intruder = User::factory()->client()->create();

    $this->project = Project::factory()->active()->for($this->owner, 'client')->create(['title' => 'Proyek Rahasia Milik A']);
    $this->invoice = Invoice::factory()->for($this->project)->create();
    $this->quotation = Quotation::factory()->for($this->project)->sent()->create();
    $this->payment = Payment::factory()->for($this->invoice)->create();
    $this->file = ProjectFile::factory()->for($this->project)->create(['visibility' => FileVisibility::Client]);
    $this->ticket = RevisionTicket::factory()->for($this->project)->create(['created_by' => $this->owner->id]);
});

/**
 * Semua URL yang bisa dibuka dengan ID milik klien A. Kunci dataset = nama route.
 * Route portal.* yang belum tercantum di sini membuat tes "matriks mencakup semua route" gagal.
 *
 * @return array<string, Closure(object): string>
 */
function portalIdorUrls(): array
{
    return [
        'portal.projects.show' => fn ($t) => route('portal.projects.show', $t->project->id),
        'portal.projects.negotiation' => fn ($t) => route('portal.projects.negotiation', $t->project->id),
        'portal.projects.invoices' => fn ($t) => route('portal.projects.invoices', $t->project->id),
        'portal.projects.handover' => fn ($t) => route('portal.projects.handover', $t->project->id),
        'files.download' => fn ($t) => route('files.download', $t->file->id),
        'quotations.print' => fn ($t) => route('quotations.print', $t->quotation->id),
        'admin.invoices.print' => fn ($t) => route('admin.invoices.print', $t->invoice->id),
        'admin.payments.proof' => fn ($t) => route('admin.payments.proof', $t->payment->id),
        'tickets.screenshot' => fn ($t) => route('tickets.screenshot', $t->ticket->id),
    ];
}

/** Route portal tanpa ID milik klien (daftar saja, tetap hanya untuk klien). */
function portalGlobalRoutes(): array
{
    return ['portal.overview', 'portal.help', 'portal.notifications'];
}

it('klien lain tidak bisa membuka satu pun route yang memuat ID milik klien A', function (string $name) {
    $url = portalIdorUrls()[$name]($this);

    $response = $this->actingAs($this->intruder)->get($url);

    expect($response->status())->toBeIn([403, 404]);
    expect($response->getContent())->not->toContain('Proyek Rahasia Milik A');
})->with(array_keys(portalIdorUrls()));

it('tamu diarahkan ke login dari setiap route yang memuat ID', function (string $name) {
    $this->get(portalIdorUrls()[$name]($this))->assertRedirect(route('login'));
})->with(array_keys(portalIdorUrls()));

it('admin dan staf tidak bisa membuka route khusus klien portal', function () {
    $urls = [
        route('portal.overview'),
        route('portal.help'),
        route('portal.notifications'),
        route('portal.projects.show', $this->project->id),
        route('portal.projects.negotiation', $this->project->id),
        route('portal.projects.invoices', $this->project->id),
        route('portal.projects.handover', $this->project->id),
    ];

    foreach ([$this->admin, $this->staff] as $user) {
        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }
});

it('matriks IDOR mencakup semua route portal yang terdaftar', function () {
    $registered = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name) => str_starts_with($name, 'portal.'))
        ->values()
        ->all();

    $covered = [...portalGlobalRoutes(), ...array_filter(array_keys(portalIdorUrls()), fn ($n) => str_starts_with($n, 'portal.'))];

    expect(array_diff($registered, $covered))
        ->toBe([], 'Route portal baru wajib masuk matriks IDOR (portalIdorUrls atau portalGlobalRoutes).');
});

it('pemilik tetap bisa membuka semua route miliknya (matriks tidak menolak semuanya)', function () {
    foreach (['portal.projects.show', 'portal.projects.negotiation', 'portal.projects.invoices', 'portal.projects.handover'] as $name) {
        $this->actingAs($this->owner)->get(portalIdorUrls()[$name]($this))->assertOk();
    }
    $this->actingAs($this->owner)->get(portalIdorUrls()['quotations.print']($this))->assertOk();
    $this->actingAs($this->owner)->get(portalIdorUrls()['admin.invoices.print']($this))->assertOk();
});

/*
|--------------------------------------------------------------------------
| Aksi Livewire dengan ID dipalsukan
|--------------------------------------------------------------------------
*/

it('aksi Negotiation dengan ID quotation klien lain ditolak dan tidak mengubah apa pun', function () {
    $foreignProject = Project::factory()->for($this->intruder, 'client')->create();
    $foreign = Quotation::factory()->for($foreignProject)->sent()->create();

    $this->actingAs($this->owner);

    foreach (['startAccept', 'accept', 'reject'] as $action) {
        Livewire::test(Negotiation::class, ['project' => $this->project->id])
            ->call($action, $foreign->id)
            ->assertNotFound();
    }

    expect($foreign->fresh()->status)->toBe(QuotationStatus::Sent)
        ->and($foreignProject->fresh()->status)->toBe($foreignProject->status);
});

it('klien tidak bisa menyetujui quotation proyeknya sendiri tanpa dua langkah konfirmasi', function () {
    $this->actingAs($this->owner);

    Livewire::test(Negotiation::class, ['project' => $this->project->id])
        ->set('agreed', true)
        ->call('accept', $this->quotation->id)
        ->assertHasErrors('agreed');

    expect($this->quotation->fresh()->status)->toBe(QuotationStatus::Sent);
});

it('aksi Invoices dengan ID invoice atau proyek klien lain ditolak', function () {
    $foreignProject = Project::factory()->for($this->intruder, 'client')->create();
    $foreign = Invoice::factory()->for($foreignProject)->create();

    $this->actingAs($this->owner);

    Livewire::test(Invoices::class, ['project' => $this->project->id])
        ->call('startUpload', $foreign->id)
        ->assertNotFound();

    Livewire::test(Invoices::class, ['project' => $foreignProject->id])->assertNotFound();

    expect(Payment::query()->where('invoice_id', $foreign->id)->count())->toBe(0);
});

it('acceptMilestone dengan ID milestone klien lain atau milestone belum selesai ditolak', function () {
    $foreignProject = Project::factory()->active()->for($this->intruder, 'client')->create();
    $foreign = ProjectMilestone::factory()->for($foreignProject)->create(['status' => MilestoneStatus::Done]);
    $pending = ProjectMilestone::factory()->for($this->project)->create(['status' => MilestoneStatus::InProgress]);

    $this->actingAs($this->owner);

    Livewire::test(ProjectOverview::class, ['project' => $this->project->id])
        ->call('acceptMilestone', $foreign->id)->assertNotFound();

    Livewire::test(ProjectOverview::class, ['project' => $this->project->id])
        ->call('acceptMilestone', $pending->id)->assertForbidden();

    expect(MilestoneAcceptance::query()->count())->toBe(0);
});

it('Chat\\Room dan Handover menolak klien lain walau project-id dikirim langsung', function () {
    $this->actingAs($this->intruder);

    Livewire::test(Room::class, ['projectId' => $this->project->id])->assertNotFound();
    Livewire::test(Handover::class, ['project' => $this->project->id])->assertNotFound();
});

it('pusat notifikasi tidak membuka notifikasi klien lain', function () {
    $foreign = DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\InvoiceIssuedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->owner->getKey(),
        'data' => ['key' => 'invoice_issued'],
    ]);

    $this->actingAs($this->intruder);

    Livewire::test(NotificationCenter::class)->call('open', $foreign->id)->assertNotFound();
    Livewire::test(NotificationCenter::class)->call('markAsRead', $foreign->id)->assertNotFound();

    expect($foreign->fresh()->read_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| #[Locked]: properti penentu data tidak boleh diubah dari browser
|--------------------------------------------------------------------------
*/

it('Invoices: projectId dan payingInvoiceId terkunci', function () {
    $this->actingAs($this->owner);
    $foreign = Invoice::factory()->for(Project::factory()->for($this->intruder, 'client'))->create();

    $c = Livewire::test(Invoices::class, ['project' => $this->project->id]);
    expect(fn () => $c->set('projectId', $foreign->project_id))->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => $c->set('payingInvoiceId', $foreign->id))->toThrow(CannotUpdateLockedPropertyException::class);
});

it('Negotiation, ProjectOverview, Handover, dan Chat Room: projectId terkunci', function () {
    $this->actingAs($this->owner);
    $foreignId = Project::factory()->for($this->intruder, 'client')->create()->id;

    foreach ([
        [Negotiation::class, ['project' => $this->project->id]],
        [ProjectOverview::class, ['project' => $this->project->id]],
        [Handover::class, ['project' => $this->project->id]],
        [Room::class, ['projectId' => $this->project->id]],
    ] as [$class, $params]) {
        $c = Livewire::test($class, $params);
        expect(fn () => $c->set('projectId', $foreignId))->toThrow(CannotUpdateLockedPropertyException::class);
    }
});

it('Chat Room: penghitung polling dan penanda pesan terakhir terkunci', function () {
    $this->actingAs($this->owner);
    $c = Livewire::test(Room::class, ['projectId' => $this->project->id]);

    expect(fn () => $c->set('idleTicks', 0))->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => $c->set('lastSeenId', 0))->toThrow(CannotUpdateLockedPropertyException::class);
});

/**
 * Audit struktural: setiap komponen Livewire yang bisa dijangkau klien WAJIB mengunci properti
 * publik berakhiran "Id"/"Ids". Komponen baru yang lupa #[Locked] membuat tes ini gagal.
 * Pengecualian harus ditulis di bawah beserta alasannya.
 */
it('semua properti publik berakhiran Id pada komponen klien memakai #[Locked]', function () {
    // Hanya memilih tampilan di UI; selalu dicocokkan ke query yang sudah dibatasi ke proyek klien
    // (Negotiation::findQuotation / render), sehingga nilai palsu tidak membuka data apa pun.
    $uiOnly = [
        'App\\Livewire\\Portal\\Negotiation::confirmingId',
        'App\\Livewire\\Portal\\Negotiation::comparingId',
    ];

    $files = [
        ...glob(app_path('Livewire/Portal/*.php')),
        ...glob(app_path('Livewire/Chat/*.php')),
    ];

    $unlocked = [];

    foreach ($files as $file) {
        $class = 'App\\Livewire\\'.str_replace('/', '\\', substr($file, strlen(app_path('Livewire/')), -4));
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || $property->getDeclaringClass()->getName() !== $class) {
                continue;
            }
            if (preg_match('/Ids?$/', $property->getName()) !== 1) {
                continue;
            }
            if (in_array($class.'::'.$property->getName(), $uiOnly, true)) {
                continue;
            }
            if ($property->getAttributes(Locked::class) === []) {
                $unlocked[] = $class.'::'.$property->getName();
            }
        }
    }

    expect($unlocked)->toBe([], 'Properti ini bisa dipalsukan dari browser; tambahkan #[Locked].');
});

it('komponen Livewire klien tidak memiliki properti publik berisi model Eloquent', function () {
    // Properti model publik bisa dimanipulasi lewat payload; gunakan #[Computed] + ID terkunci.
    $files = [...glob(app_path('Livewire/Portal/*.php')), ...glob(app_path('Livewire/Chat/*.php'))];

    $offenders = [];
    foreach ($files as $file) {
        $class = 'App\\Livewire\\'.str_replace('/', '\\', substr($file, strlen(app_path('Livewire/')), -4));
        foreach ((new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $type = $property->getType();
            if ($type instanceof ReflectionNamedType && is_subclass_of($type->getName(), Model::class)) {
                $offenders[] = $class.'::'.$property->getName();
            }
        }
    }

    expect($offenders)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Unduhan berkas: visibilitas dan status
|--------------------------------------------------------------------------
*/

it('berkas berstatus private tidak bisa diunduh klien pemilik', function () {
    $private = ProjectFile::factory()->for($this->project)->create(['visibility' => FileVisibility::Private]);
    Storage::disk('private')->put($private->path, 'x');

    expect($this->actingAs($this->owner)->get(route('files.download', $private->id))->status())->toBeIn([403, 404]);
});
