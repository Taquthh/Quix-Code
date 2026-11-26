<?php

namespace App\Support;

use App\Enums\TicketCategory;
use App\Models\RevisionTicket;

/**
 * T4-06. Kuota putaran revisi gratis per tahapan (atau per proyek bila tidak terkait tahapan).
 * Hanya tiket kategori revisi yang menghabiskan kuota; laporan bug tidak pernah berbayar.
 */
final class RevisionQuota
{
    public static function freeRounds(): int
    {
        return max(0, (int) config('quix.free_revision_rounds', 2));
    }

    /**
     * @return array{free: int, used: int, remaining: int}
     */
    public static function for(string $projectId, ?int $milestoneId): array
    {
        $used = RevisionTicket::query()
            ->where('project_id', $projectId)
            ->where('category', TicketCategory::Revision->value)
            ->when(
                $milestoneId === null,
                fn ($query) => $query->whereNull('milestone_id'),
                fn ($query) => $query->where('milestone_id', $milestoneId),
            )
            ->count();

        $free = self::freeRounds();

        return ['free' => $free, 'used' => $used, 'remaining' => max(0, $free - $used)];
    }
}
