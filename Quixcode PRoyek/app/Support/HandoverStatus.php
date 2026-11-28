<?php

namespace App\Support;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\ProjectStatus;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * T4-10. Status serah terima untuk klien: apa yang ditampilkan di menu Handover dan mengapa terkunci.
 * Aturan buka-kunci tetap satu sumber: ability `downloadHandover` di ProjectPolicy (invoice final lunas).
 * Logika serah terima yang sebenarnya (pilih metode, arsip, kredensial) dikerjakan di Tahap 7.
 */
final class HandoverStatus
{
    /** Pekerjaan belum selesai; serah terima belum dimulai. */
    public const NOT_STARTED = 'not_started';

    /** Pekerjaan selesai, tetapi invoice final belum diterbitkan. */
    public const NO_FINAL_INVOICE = 'no_final_invoice';

    /** Invoice final sudah terbit tetapi belum lunas (termasuk menunggu verifikasi). */
    public const FINAL_UNPAID = 'final_unpaid';

    /** Invoice final lunas; berkas serah terima boleh diunduh. */
    public const READY = 'ready';

    /** Status proyek tempat serah terima sudah relevan bagi klien. */
    private const STARTED = [
        ProjectStatus::AwaitingFinal,
        ProjectStatus::Handover,
        ProjectStatus::Warranty,
        ProjectStatus::Closed,
    ];

    public static function for(User $user, Project $project): string
    {
        $status = $project->getAttribute('status');

        if (! $status instanceof ProjectStatus || ! in_array($status, self::STARTED, true)) {
            return self::NOT_STARTED;
        }

        if (Gate::forUser($user)->allows('downloadHandover', $project)) {
            return self::READY;
        }

        $hasOutstandingFinal = Invoice::query()
            ->where('project_id', $project->id)
            ->where('type', InvoiceType::Final->value)
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Unpaid->value,
                InvoiceStatus::AwaitingVerification->value,
            ])
            ->exists();

        return $hasOutstandingFinal ? self::FINAL_UNPAID : self::NO_FINAL_INVOICE;
    }

    public static function isLocked(string $state): bool
    {
        return $state !== self::READY;
    }
}
