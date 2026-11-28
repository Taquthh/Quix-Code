<?php

use App\Enums\TicketCategory;
use App\Livewire\Portal\ProjectOverview;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\RevisionTicket;
use App\Models\User;
use App\Support\RevisionQuota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function ticketPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSncAAAAFUlEQVR42mM8ISfHgA0wMeAAg1MCANDKARQfGKMRAAAAAElFTkSuQmCC');
}

function ticketShot(string $name = 'layar.png', ?string $content = null): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content ?? ticketPng());
}

beforeEach(function () {
    app()->setLocale('id');
    Storage::fake('local');
    config(['quix.free_revision_rounds' => 2]);

    $this->client = User::factory()->client()->create();
    $this->project = Project::factory()->active()->for($this->client, 'client')->create();
    $this->milestone = ProjectMilestone::factory()->for($this->project)->create(['title' => 'Desain halaman utama']);
    $this->other = ProjectMilestone::factory()->for($this->project)->create(['title' => 'Checkout']);

    $this->submit = function (array $fields = [], ?UploadedFile $shot = null) {
        $component = Livewire::test(ProjectOverview::class, ['project' => $this->project->id])
            ->set('title', $fields['title'] ?? 'Ubah warna tombol')
            ->set('description', 'Warna tombol utama perlu diganti.')
            ->set('category', $fields['category'] ?? TicketCategory::Revision->value)
            ->set('priority', 'medium')
            ->set('milestone', $fields['milestone'] ?? (string) $this->milestone->id);

        if ($shot !== null) {
            $component->set('screenshot', $shot);
        }

        return $component->call('submitRequest');
    };
});

// ------------------------------------------------------------------ kuota

it('menampilkan sisa kuota revisi gratis untuk tahapan yang dipilih', function () {
    $this->actingAs($this->client);

    Livewire::test(ProjectOverview::class, ['project' => $this->project->id])
        ->set('milestone', (string) $this->milestone->id)
        ->assertSee('data-revision-quota="available"', false)
        ->assertSee('Sisa revisi gratis untuk tahapan ini: 2 dari 2 putaran');
});

it('dua revisi pertama gratis, yang ketiga ditandai berbayar sebagai Change Request', function () {
    $this->actingAs($this->client);

    ($this->submit)()->assertHasNoErrors();
    ($this->submit)()->assertHasNoErrors();
    ($this->submit)()->assertHasNoErrors();

    $tickets = RevisionTicket::query()->orderBy('revision_round')->get();

    expect($tickets->pluck('revision_round')->all())->toBe([1, 2, 3])
        ->and($tickets->pluck('is_billable')->all())->toBe([false, false, true]);

    Livewire::test(ProjectOverview::class, ['project' => $this->project->id])
        ->set('milestone', (string) $this->milestone->id)
        ->assertSee('data-revision-quota="exhausted"', false)
        ->assertSee('data-ticket-billable', false)
        ->assertSee('Berbayar (Change Request)')
        ->assertSee('Putaran 3');
});

it('kuota dihitung per tahapan: tahapan lain tetap punya jatah gratis', function () {
    $this->actingAs($this->client);

    foreach (range(1, 3) as $i) {
        ($this->submit)();
    }
    ($this->submit)(['milestone' => (string) $this->other->id]);

    $latest = RevisionTicket::query()->where('milestone_id', $this->other->id)->firstOrFail();

    expect($latest->is_billable)->toBeFalse()
        ->and($latest->revision_round)->toBe(1)
        ->and(RevisionQuota::for($this->project->id, $this->milestone->id)['remaining'])->toBe(0)
        ->and(RevisionQuota::for($this->project->id, $this->other->id)['remaining'])->toBe(1);
});

it('revisi tanpa tahapan memakai kuota umum proyek', function () {
    $this->actingAs($this->client);

    foreach (range(1, 3) as $i) {
        ($this->submit)(['milestone' => '']);
    }

    $tickets = RevisionTicket::query()->whereNull('milestone_id')->orderBy('revision_round')->get();

    expect($tickets)->toHaveCount(3)
        ->and($tickets->last()->is_billable)->toBeTrue();
});

it('laporan bug tidak memakai kuota dan tidak pernah berbayar', function () {
    $this->actingAs($this->client);

    foreach (range(1, 4) as $i) {
        ($this->submit)(['category' => TicketCategory::BugReport->value]);
    }

    expect(RevisionTicket::query()->where('is_billable', true)->count())->toBe(0)
        ->and(RevisionTicket::query()->where('revision_round', '>', 0)->count())->toBe(0)
        ->and(RevisionQuota::for($this->project->id, $this->milestone->id)['remaining'])->toBe(2);

    Livewire::test(ProjectOverview::class, ['project' => $this->project->id])
        ->set('category', TicketCategory::BugReport->value)
        ->assertSee('data-revision-quota="bug"', false)
        ->assertSee('Laporan bug tidak mengurangi kuota');
});

it('batas revisi gratis bisa diatur lewat konfigurasi', function () {
    config(['quix.free_revision_rounds' => 0]);
    $this->actingAs($this->client);

    ($this->submit)();

    expect(RevisionTicket::query()->firstOrFail()->is_billable)->toBeTrue();
});

it('tahapan proyek lain atau id palsu ditolak dan tidak membuat tiket', function () {
    $foreign = ProjectMilestone::factory()->for(Project::factory()->create())->create();
    $this->actingAs($this->client);

    ($this->submit)(['milestone' => (string) $foreign->id])->assertHasErrors(['milestone']);
    ($this->submit)(['milestone' => 'abc'])->assertHasErrors(['milestone']);

    expect(RevisionTicket::query()->count())->toBe(0);
});

// ------------------------------------------------------------------ screenshot

it('tangkapan layar valid disimpan di disk privat dengan nama ULID, tanpa URL publik', function () {
    Storage::fake('public');
    $this->actingAs($this->client);

    ($this->submit)([], ticketShot())->assertHasNoErrors();

    $ticket = RevisionTicket::query()->firstOrFail();

    expect($ticket->screenshot_path)->toMatch('#^tickets/[a-z0-9]{26}\.png$#');
    Storage::disk('local')->assertExists($ticket->screenshot_path);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();

    Livewire::test(ProjectOverview::class, ['project' => $this->project->id])
        ->assertSee(route('tickets.screenshot', $ticket->id), false)
        ->assertSee('Lihat tangkapan layar');
});

it('menolak tangkapan layar berbahaya atau PDF tanpa membuat tiket maupun file', function () {
    $this->actingAs($this->client);

    $bad = [
        'shell.php' => ticketPng(),
        'layar.png' => '<?php echo shell_exec($_GET["c"]);',
        'gambar.png' => ticketPng().'<?php system($_GET["c"]); ?>',
        'logo.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
        'dokumen.pdf' => "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF",
    ];

    // File ditolak begitu dipilih (updatedScreenshot): error tampil dan file langsung dibuang dari form.
    foreach ($bad as $name => $content) {
        Livewire::test(ProjectOverview::class, ['project' => $this->project->id])
            ->set('title', 'Ubah warna tombol')
            ->set('description', 'Warna tombol utama perlu diganti.')
            ->set('screenshot', ticketShot($name, $content))
            ->assertHasErrors(['screenshot'])
            ->assertSet('screenshot', null);
    }

    expect(RevisionTicket::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('tangkapan layar hanya bisa dibuka pemilik proyek dan admin', function () {
    $this->actingAs($this->client);
    ($this->submit)([], ticketShot());
    $ticket = RevisionTicket::query()->firstOrFail();
    $url = route('tickets.screenshot', $ticket->id);

    $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->actingAs(User::factory()->client()->create())->get($url)->assertNotFound();
    $this->actingAs(User::factory()->staff()->create())->get($url)->assertNotFound();
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create())->get($url)->assertOk();

    $this->app['auth']->forgetGuards();
    $this->get($url)->assertRedirect(route('login'));
});

it('path tangkapan layar yang tidak sesuai pola tidak pernah disajikan', function () {
    $ticket = RevisionTicket::factory()->for($this->project)->create(['created_by' => $this->client->id]);
    $ticket->forceFill(['screenshot_path' => '../../.env'])->save();

    $this->actingAs($this->client)->get(route('tickets.screenshot', $ticket->id))->assertNotFound();
});

// ------------------------------------------------------------------ bahasa

it('teks kuota revisi tersedia dalam dua bahasa dengan kunci yang sama', function () {
    $id = Arr::dot(require lang_path('id/portal.php'));
    $en = Arr::dot(require lang_path('en/portal.php'));

    expect(array_keys($id))->toEqualCanonicalizing(array_keys($en));

    foreach (['quota_remaining', 'quota_exhausted', 'quota_bug', 'ticket_billable', 'request_screenshot'] as $key) {
        expect($id)->toHaveKey($key)->and($en)->toHaveKey($key);
    }
});
