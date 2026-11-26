<?php

namespace App\Support;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * T4-13. Tiga langkah awal klien baru: setujui quotation, bayar DP, pantau progres.
 * Status tiap langkah diturunkan dari status proyek yang paling maju; tidak ada data tambahan yang disimpan.
 */
final class OnboardingSteps
{
    public const STATE_DONE = 'done';

    public const STATE_CURRENT = 'current';

    public const STATE_UPCOMING = 'upcoming';

    /**
     * @param  Collection<int, Project>  $projects
     * @return list<array{key: string, state: string, route: string|null, params: array<string, string>}>|null null bila panduan tidak perlu ditampilkan lagi
     */
    public static function for(Collection $projects): ?array
    {
        $focus = $projects
            ->reject(fn (Project $project): bool => $project->status === ProjectStatus::Cancelled)
            ->sortByDesc(fn (Project $project): int => self::rank($project->status))
            ->first();

        $rank = $focus === null ? 0 : self::rank($focus->status);

        // Setelah proyek masuk pelunasan atau serah terima, klien sudah melewati alur awal.
        if ($rank >= self::rank(ProjectStatus::AwaitingFinal)) {
            return null;
        }

        $quotationDone = $rank >= self::rank(ProjectStatus::Deal);
        $dpDone = $rank >= self::rank(ProjectStatus::Active);
        $params = $focus === null ? [] : ['project' => (string) $focus->getKey()];

        $current = ! $quotationDone ? 'approve_quotation' : (! $dpDone ? 'pay_dp' : 'track_progress');

        $steps = [
            ['key' => 'approve_quotation', 'done' => $quotationDone, 'route' => 'portal.projects.negotiation'],
            ['key' => 'pay_dp', 'done' => $dpDone, 'route' => 'portal.projects.invoices'],
            ['key' => 'track_progress', 'done' => false, 'route' => 'portal.projects.show'],
        ];

        return array_map(fn (array $step): array => [
            'key' => $step['key'],
            'state' => $step['done'] ? self::STATE_DONE : ($step['key'] === $current ? self::STATE_CURRENT : self::STATE_UPCOMING),
            'route' => $focus === null ? null : $step['route'],
            'params' => $params,
        ], $steps);
    }

    private static function rank(ProjectStatus $status): int
    {
        return match ($status) {
            ProjectStatus::Lead => 0,
            ProjectStatus::Quoted => 1,
            ProjectStatus::Negotiating => 2,
            ProjectStatus::Deal => 3,
            ProjectStatus::AwaitingDp => 4,
            ProjectStatus::Active, ProjectStatus::OnHold => 5,
            ProjectStatus::AwaitingFinal => 6,
            ProjectStatus::Handover, ProjectStatus::Warranty, ProjectStatus::Closed => 7,
            ProjectStatus::Cancelled => -1,
        };
    }
}
