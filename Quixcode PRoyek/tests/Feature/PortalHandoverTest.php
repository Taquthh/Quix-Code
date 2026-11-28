<?php

use App\Enums\FileVisibility;
use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Livewire\Portal\Handover;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectHandover;
use App\Models\User;
use App\Support\HandoverStatus;
use App\Support\PortalNavigation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('id');
    $this->client = User::factory()->client()->create();
    $this->otherClient = User::factory()->client()->create();

    $this->makeProject = fn (ProjectStatus $status, ?User $owner = null): Project => Project::factory()
        ->for($owner ?? $this->client, 'client')
        ->create(['title' => 'Toko Online Budi', 'status' => $status]);

    $this->clientFile = fn (Project $project, FileVisibility $visibility = FileVisibility::Client, string $path = 'files/serah-terima.zip'): ProjectFile => ProjectFile::factory()
        ->for($project)
        ->create(['visibility' => $visibility, 'path' => $path, 'size' => 2048]);
});

// ------------------------------------------------------------------ menu

it('menu Handover ada di navigasi proyek dan bilah bawah tetap maksimal lima item', function () {
    $project = ($this->makeProject)(ProjectStatus::Active);

    $keys = collect(PortalNavigation::forProject($project->id))->pluck('key')->all();
    expect($keys)->toContain('handover')
        ->and(count(PortalNavigation::bottom($project->id)))->toBeLessThanOrEqual(5)
        ->and(collect(PortalNavigation::bottom($project->id))->pluck('key')->all())->toContain('handover');

    $this->actingAs($this->client)
        ->get(route('portal.projects.show', $project->id))
        ->assertOk()
        ->assertSee('data-nav="handover"', false)
        ->assertSee(route('portal.projects.handover', $project->id), false)
        ->assertSee('Serah terima');
});

it('menu Handover tidak muncul di luar proyek', function () {
    $this->actingAs($this->client)
        ->get(route('portal.overview'))
        ->assertOk()
        ->assertDontSee('data-nav="handover"', false);
});

// ------------------------------------------------------------------ status dan alasan terkunci

it('proyek yang masih dikerjakan: serah terima belum dimulai dan tanpa bagian berkas', function () {
    $project = ($this->makeProject)(ProjectStatus::Active);

    $this->actingAs($this->client)
        ->get(route('portal.projects.handover', $project->id))
        ->assertOk()
        ->assertSee('data-portal-layout', false)
        ->assertSee('data-handover-state="not_started"', false)
        ->assertSee('Belum dimulai')
        ->assertDontSee('data-handover-files', false);
});

it('menunggu pelunasan tanpa invoice final: terkunci karena invoice final belum terbit', function () {
    $project = ($this->makeProject)(ProjectStatus::AwaitingFinal);

    $this->actingAs($this->client)
        ->get(route('portal.projects.handover', $project->id))
        ->assertOk()
        ->assertSee('data-handover-state="no_final_invoice"', false)
        ->assertSee('Terkunci: invoice final belum terbit')
        ->assertDontSee('data-handover-files', false);
});

it('invoice final belum lunas: terkunci dengan tautan ke tagihan, berkas tidak ditampilkan', function (InvoiceStatus $status) {
    $project = ($this->makeProject)(ProjectStatus::AwaitingFinal);
    Invoice::factory()->final()->for($project)->create(['status' => $status]);
    $file = ($this->clientFile)($project);

    $this->actingAs($this->client)
        ->get(route('portal.projects.handover', $project->id))
        ->assertOk()
        ->assertSee('data-handover-state="final_unpaid"', false)
        ->assertSee('Terkunci: invoice final belum lunas')
        ->assertSee(route('portal.projects.invoices', $project->id), false)
        ->assertDontSee('data-handover-files', false)
        ->assertDontSee(route('files.download', $file->id), false);
})->with([
    'terkirim' => InvoiceStatus::Sent,
    'belum dibayar' => InvoiceStatus::Unpaid,
    'menunggu verifikasi' => InvoiceStatus::AwaitingVerification,
]);

it('invoice final lunas: terbuka, hanya berkas klien yang tampil dengan tautan unduh', function () {
    $project = ($this->makeProject)(ProjectStatus::Handover);
    Invoice::factory()->final()->paid()->for($project)->create();

    $visible = ($this->clientFile)($project, FileVisibility::Client, 'files/hasil-akhir.zip');
    $private = ($this->clientFile)($project, FileVisibility::Private, 'files/internal.zip');

    $this->actingAs($this->client)
        ->get(route('portal.projects.handover', $project->id))
        ->assertOk()
        ->assertSee('data-handover-state="ready"', false)
        ->assertSee('Siap diunduh')
        ->assertSee('data-handover-files', false)
        ->assertSee(route('files.download', $visible->id), false)
        ->assertDontSee(route('files.download', $private->id), false)
        ->assertSee('Berkas ZIP')
        ->assertSee('2 KB');
});

it('serah terima terbuka tanpa berkas menampilkan empty state yang jelas', function () {
    $project = ($this->makeProject)(ProjectStatus::Handover);
    Invoice::factory()->final()->paid()->for($project)->create();

    $this->actingAs($this->client)
        ->get(route('portal.projects.handover', $project->id))
        ->assertOk()
        ->assertSee('data-empty="handover-files"', false)
        ->assertSee('Belum ada berkas');
});

it('metode serah terima tampil, tetapi payload kredensial tidak pernah dikirim ke browser', function () {
    $project = ($this->makeProject)(ProjectStatus::Handover);
    Invoice::factory()->final()->paid()->for($project)->create();
    ProjectHandover::factory()->for($project)->create(['payload' => 'kredensial-rahasia-12345']);

    $this->actingAs($this->client)
        ->get(route('portal.projects.handover', $project->id))
        ->assertOk()
        ->assertSee('Metode serah terima')
        ->assertSee('Unduh Langsung')
        ->assertDontSee('kredensial-rahasia-12345');
});

it('invoice final lunas tidak membuka serah terima bila pekerjaan belum selesai', function () {
    $project = ($this->makeProject)(ProjectStatus::Active);
    Invoice::factory()->final()->paid()->for($project)->create();

    expect(HandoverStatus::for($this->client, $project))->toBe(HandoverStatus::NOT_STARTED);
});

// ------------------------------------------------------------------ keamanan

it('klien lain tidak bisa membuka halaman handover proyek ini', function () {
    $project = ($this->makeProject)(ProjectStatus::Handover);
    Invoice::factory()->final()->paid()->for($project)->create();

    $this->actingAs($this->otherClient)
        ->get(route('portal.projects.handover', $project->id))
        ->assertNotFound();
});

it('admin dan staf tidak bisa membuka halaman handover portal, tamu diarahkan ke login', function () {
    $project = ($this->makeProject)(ProjectStatus::Handover);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('portal.projects.handover', $project->id))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->withTwoFactor()->create())
        ->get(route('portal.projects.handover', $project->id))
        ->assertForbidden();

    $this->app['auth']->forgetGuards();
    $this->get(route('portal.projects.handover', $project->id))->assertRedirect(route('login'));
});

it('id proyek di halaman handover tidak bisa dipalsukan dari browser', function () {
    $mine = ($this->makeProject)(ProjectStatus::AwaitingFinal);
    $theirs = ($this->makeProject)(ProjectStatus::Handover, $this->otherClient);

    $this->actingAs($this->client);

    $component = Livewire::test(Handover::class, ['project' => $mine->id]);

    expect(fn () => $component->set('projectId', $theirs->id))->toThrow(CannotUpdateLockedPropertyException::class);
});

it('jumlah query halaman handover tidak bertambah saat berkas bertambah (tanpa N+1)', function () {
    $project = ($this->makeProject)(ProjectStatus::Handover);
    Invoice::factory()->final()->paid()->for($project)->create();
    ($this->clientFile)($project, FileVisibility::Client, 'files/a.zip');

    $countQueries = function () use ($project) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->client)->get(route('portal.projects.handover', $project->id))->assertOk();

        return count(DB::getQueryLog());
    };

    $countQueries(); // pemanasan
    $sedikit = $countQueries();

    foreach (range(1, 8) as $i) {
        ($this->clientFile)($project, FileVisibility::Client, "files/b{$i}.zip");
    }

    expect($countQueries())->toBe($sedikit);
});

// ------------------------------------------------------------------ bahasa

it('halaman handover mengikuti bahasa akun', function () {
    $client = User::factory()->client()->create(['locale' => 'en']);
    $project = ($this->makeProject)(ProjectStatus::AwaitingFinal, $client);
    Invoice::factory()->final()->for($project)->create(['status' => InvoiceStatus::Unpaid]);

    $this->actingAs($client)
        ->get(route('portal.projects.handover', $project->id))
        ->assertOk()
        ->assertSee('Handover &amp; files', false)
        ->assertSee('Locked: final invoice not paid yet')
        ->assertSee('View invoices');
});

it('semua teks handover tersedia dalam dua bahasa dengan kunci yang sama', function () {
    $id = Arr::dot(require lang_path('id/handover.php'));
    $en = Arr::dot(require lang_path('en/handover.php'));

    expect(array_keys($id))->toEqualCanonicalizing(array_keys($en));

    $nav = fn (string $lang) => Arr::get(require lang_path("{$lang}/portal.php"), 'nav.handover');
    expect($nav('id'))->not->toBeEmpty()->and($nav('en'))->not->toBeEmpty();
});
