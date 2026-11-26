<?php

namespace App\Livewire\Portal;

use App\Enums\ActivityAction;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Exceptions\UnsafeUploadException;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectGitCommit;
use App\Models\ProjectMilestone;
use App\Models\RevisionTicket;
use App\Rules\SafeUpload;
use App\Support\ActivityLogger;
use App\Support\ProjectNextActions;
use App\Support\RevisionQuota;
use App\Support\SecureUpload;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts::portal')]
class ProjectOverview extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $projectId;

    public string $title = '';

    public string $description = '';

    public string $category = 'revision';

    public string $priority = 'medium';

    /** T4-06: tahapan terkait ('' = umum). Divalidasi terhadap milestone proyek ini. */
    public string $milestone = '';

    /** @var TemporaryUploadedFile|null */
    public $screenshot = null;

    public function mount(string $project): void
    {
        $this->projectId = $project;
        $this->authorize('viewAny', [RevisionTicket::class, $this->project]);
    }

    #[Computed]
    public function project(): Project
    {
        return Project::query()->visibleTo(auth()->user())->findOrFail($this->projectId);
    }

    public function submitRequest(): void
    {
        $project = $this->project;
        $this->authorize('create', [RevisionTicket::class, $project]);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'milestone' => ['nullable', 'integer', Rule::exists('project_milestones', 'id')->where('project_id', $project->id)],
            'screenshot' => ['nullable', new SafeUpload(SecureUpload::KIND_IMAGE)],
        ]);

        $milestoneId = $validated['milestone'] === null || $validated['milestone'] === '' ? null : (int) $validated['milestone'];
        $isRevision = $validated['category'] === TicketCategory::Revision->value;

        $screenshotPath = null;
        $file = $this->screenshot;

        if ($file instanceof TemporaryUploadedFile) {
            try {
                $screenshotPath = SecureUpload::store($file, 'tickets', SecureUpload::KIND_IMAGE)->path;
            } catch (UnsafeUploadException $e) {
                $this->screenshot = null;
                $this->addError('screenshot', $e->translated(SecureUpload::maxMegabytes(SecureUpload::KIND_IMAGE)));

                return;
            }
        }

        // Proyek dikunci selama menghitung kuota, jadi dua tab sekaligus tidak sama-sama mendapat putaran gratis.
        DB::transaction(function () use ($project, $validated, $milestoneId, $isRevision, $screenshotPath): void {
            Project::query()->whereKey($project->id)->lockForUpdate()->first();

            $quota = RevisionQuota::for($project->id, $milestoneId);

            $project->tickets()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'created_by' => auth()->id(),
                'milestone_id' => $milestoneId,
                'screenshot_path' => $screenshotPath,
                'revision_round' => $isRevision ? $quota['used'] + 1 : 0,
                'is_billable' => $isRevision && $quota['remaining'] <= 0,
            ]);
        });

        $this->reset('title', 'description', 'milestone', 'screenshot');
        $this->category = TicketCategory::Revision->value;
        $this->priority = TicketPriority::Medium->value;
        unset($this->tickets);

        session()->flash('portal-request-created', true);
    }

    /** Periksa tangkapan layar segera setelah dipilih; file yang ditolak langsung dibuang dari form. */
    public function updatedScreenshot(): void
    {
        try {
            $this->validateOnly('screenshot', ['screenshot' => ['required', new SafeUpload(SecureUpload::KIND_IMAGE)]]);
        } catch (ValidationException $e) {
            $this->screenshot = null;

            throw $e;
        }
    }

    /**
     * T4-06. Pratinjau kuota revisi gratis untuk tahapan yang dipilih; null untuk laporan bug (tidak memakai kuota).
     *
     * @return array{free: int, used: int, remaining: int}|null
     */
    private function quotaPreview(Project $project): ?array
    {
        if ($this->category !== TicketCategory::Revision->value) {
            return null;
        }

        $milestoneId = ctype_digit($this->milestone) ? (int) $this->milestone : null;

        return RevisionQuota::for($project->id, $milestoneId);
    }

    /**
     * T4-12. Klien menerima hasil milestone yang sudah selesai. Baris milestone dikunci di transaksi
     * dan diperiksa ulang, jadi dua tab atau klik ganda hanya menghasilkan satu penerimaan.
     */
    public function acceptMilestone(int $milestoneId): void
    {
        $project = $this->project();
        $accepted = DB::transaction(function () use ($project, $milestoneId): ?string {
            $milestone = ProjectMilestone::query()
                ->where('project_id', $project->id)
                ->lockForUpdate()
                ->findOrFail($milestoneId);
            $milestone->setRelation('project', $project);

            if ($milestone->acceptances()->exists()) {
                return null; // sudah diterima (tab lain): tidak ada yang berubah
            }

            $this->authorize('accept', $milestone);

            $acceptance = $milestone->acceptances()->create([
                'client_id' => auth()->id(),
                'accepted_at' => now(),
                'ip' => request()->ip(),
            ]);

            ActivityLogger::log(ActivityAction::MilestoneAccepted, $milestone, [
                'acceptance_id' => $acceptance->getKey(),
                'project_id' => $project->id,
                'title' => $milestone->title,
            ]);

            return $milestone->title;
        });

        if ($accepted !== null) {
            session()->flash('portal-milestone-accepted', $accepted);
        }
    }

    #[Computed]
    public function tickets()
    {
        return RevisionTicket::query()
            ->visibleTo(auth()->user())
            ->where('project_id', $this->projectId)
            ->with(['creator', 'milestone'])
            ->latest()
            ->get();
    }

    /** @return EloquentCollection<int, ProjectMilestone> */
    private function milestones(Project $project): EloquentCollection
    {
        /** @var EloquentCollection<int, ProjectMilestone> $milestones */
        $milestones = $project->milestones()->with('acceptances')->get();

        foreach ($milestones as $milestone) {
            $milestone->setRelation('project', $project);
        }

        return $milestones;
    }

    /**
     * T4-03. Linimasa pembaruan pengerjaan (diisi webhook di Tahap 5). Hash dan email penulis
     * tidak pernah diambil, jadi tidak bisa bocor ke tampilan klien.
     *
     * @return Collection<int, ProjectGitCommit>
     */
    private function commits(): Collection
    {
        return ProjectGitCommit::query()
            ->select(['id', 'project_id', 'commit_message', 'progress_recorded', 'pushed_at'])
            ->where('project_id', $this->projectId)
            ->orderByDesc('pushed_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    public function render(): View
    {
        $project = $this->project;
        $this->authorize('viewAny', [RevisionTicket::class, $project]);
        $this->authorize('viewAny', [Invoice::class, $project]);

        return view('livewire.portal.project-overview', [
            'project' => $project,
            'milestones' => $this->milestones($project),
            'invoices' => Invoice::query()
                ->visibleTo(auth()->user())
                ->where('project_id', $project->id)
                ->with('payments')
                ->orderBy('created_at')
                ->get(),
            'tickets' => $this->tickets,
            'quota' => $this->quotaPreview($project),
            'commits' => $this->commits(),
            'actions' => ProjectNextActions::for($project),
        ])->title($project->title);
    }
}
