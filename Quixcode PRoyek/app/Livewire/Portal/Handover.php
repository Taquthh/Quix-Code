<?php

namespace App\Livewire\Portal;

use App\Enums\FileVisibility;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectHandover;
use App\Support\HandoverStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * T4-10: menu Handover & Berkas. Hanya menampilkan status dan alasan terkunci; logika serah terima asli di Tahap 7.
 * Data dibatasi ke proyek milik klien, dan payload (kredensial) tidak pernah dimuat atau ditampilkan.
 *
 * @property-read Project $project
 * @property-read string $state
 * @property-read Collection<int, ProjectFile> $files
 * @property-read ProjectHandover|null $handover
 */
#[Layout('layouts::portal')]
class Handover extends Component
{
    #[Locked]
    public string $projectId;

    public function mount(string $project): void
    {
        $this->projectId = $project;
        $this->authorize('view', $this->project);
    }

    #[Computed]
    public function project(): Project
    {
        return Project::query()->visibleTo(auth()->user())->findOrFail($this->projectId);
    }

    #[Computed]
    public function state(): string
    {
        return HandoverStatus::for(auth()->user(), $this->project);
    }

    /**
     * Berkas untuk klien; hanya dimuat saat serah terima terbuka. Unduhan tetap diotorisasi per file (FilePolicy).
     *
     * @return Collection<int, ProjectFile>
     */
    #[Computed]
    public function files(): Collection
    {
        if ($this->state !== HandoverStatus::READY) {
            return new Collection;
        }

        return ProjectFile::query()
            ->where('project_id', $this->projectId)
            ->where('visibility', FileVisibility::Client->value)
            ->latest()
            ->get();
    }

    #[Computed]
    public function handover(): ?ProjectHandover
    {
        if ($this->state !== HandoverStatus::READY) {
            return null;
        }

        return ProjectHandover::query()
            ->select(['id', 'project_id', 'delivery_method', 'expires_at'])
            ->where('project_id', $this->projectId)
            ->first();
    }

    public function render(): View
    {
        return view('livewire.portal.handover')->title(__('handover.title'));
    }
}
