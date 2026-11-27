<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\ProjectStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\RevisionTicket;
use App\Models\User;
use App\Notifications\TicketSlaNotification;
use App\Support\ActivityLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Aturan otomatis scheduler (T5-11). Setiap aturan idempoten: aman dijalankan berulang pada hari yang sama,
 * dan setiap aksi tercatat sebagai aksi sistem (user_id null) di activity_logs.
 */
class AutomationService
{
    public function __construct(private readonly Notifier $notifier) {}

    /**
     * Jenis pengingat untuk invoice menurut selisih hari ke jatuh tempo, atau null bila hari ini bukan harinya.
     * due_soon: jatuh tempo dalam 1..N hari; due_today: hari ini; overdue: terlambat N hari atau lebih.
     */
    public static function reminderKind(CarbonInterface $dueDate, CarbonInterface $today): ?string
    {
        $lead = max(1, (int) config('quix.automation.invoice_reminder_days'));
        $days = (int) round($today->copy()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false)); // positif = di depan

        return match (true) {
            $days <= -$lead => 'overdue',
            $days === 0 => 'due_today',
            $days > 0 && $days <= $lead => 'due_soon',
            default => null,
        };
    }

    /**
     * Pengingat invoice terkirim/belum dibayar: H-N, H0, dan H+N. Satu pengingat per invoice dan jenisnya
     * (unik di invoice_reminders), jadi scheduler yang jalan berulang tidak mengirim ganda.
     */
    public function remindInvoices(): int
    {
        $today = now()->startOfDay();
        $lead = max(1, (int) config('quix.automation.invoice_reminder_days'));

        $invoices = Invoice::query()
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Unpaid->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays($lead)->toDateString())
            ->whereHas('project', fn ($q) => $q->whereNotIn('status', [ProjectStatus::Closed->value, ProjectStatus::Cancelled->value]))
            ->get();

        $count = 0;

        foreach ($invoices as $invoice) {
            $due = $invoice->getAttribute('due_date');

            if (! $due instanceof CarbonInterface) {
                continue;
            }

            $kind = self::reminderKind($due, $today);

            if ($kind === null) {
                continue;
            }

            // Catat lebih dulu: unik (invoice, jenis) menjamin paling banyak satu pengiriman walau dua proses berjalan bersamaan.
            $recorded = DB::table('invoice_reminders')->insertOrIgnore([
                'invoice_id' => $invoice->id,
                'kind' => $kind,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]) === 1;

            if (! $recorded) {
                continue;
            }

            ActivityLogger::log(ActivityAction::InvoiceReminded, $invoice, [
                'kind' => $kind,
                'number' => $invoice->invoice_number,
                'due_date' => $due->format('Y-m-d'),
            ]);

            $this->notifier->invoiceReminder($invoice, $kind);
            $count++;
        }

        return $count;
    }

    /**
     * Proyek menunggu DP yang invoice DP-nya belum dibayar lebih dari N hari sejak terbit dijeda otomatis (on_hold).
     * Tidak menjeda bila ada DP yang sudah dibayar atau sedang menunggu verifikasi admin. Admin melanjutkan lewat
     * jalur biasa (kembali ke menunggu DP).
     */
    public function holdUnpaidDownPayments(): int
    {
        $days = max(1, (int) config('quix.automation.dp_hold_after_days'));
        $cutoff = now()->subDays($days);

        $ids = Project::query()
            ->where('status', ProjectStatus::AwaitingDp->value)
            ->whereHas('invoices', fn ($q) => $q
                ->where('type', InvoiceType::Dp->value)
                ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Unpaid->value])
                ->where(fn ($w) => $w
                    ->where('issued_at', '<=', $cutoff)
                    ->orWhere(fn ($x) => $x->whereNull('issued_at')->where('created_at', '<=', $cutoff))))
            ->whereDoesntHave('invoices', fn ($q) => $q
                ->where('type', InvoiceType::Dp->value)
                ->whereIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::AwaitingVerification->value]))
            ->pluck('id');

        $held = 0;

        foreach ($ids as $id) {
            try {
                $done = DB::transaction(function () use ($id, $days): bool {
                    $project = Project::query()->lockForUpdate()->whereKey($id)->first();

                    if ($project === null || $project->status !== ProjectStatus::AwaitingDp) {
                        return false;
                    }

                    $project->transitionTo(ProjectStatus::OnHold, null, (string) trans('projects.auto_hold.dp_unpaid', ['days' => $days], 'id'));
                    ActivityLogger::changed(ActivityAction::StatusChanged, $project, ProjectStatus::AwaitingDp, ProjectStatus::OnHold);

                    return true;
                });
            } catch (InvalidArgumentException) {
                $done = false;
            }

            if ($done) {
                $held++;
            }
        }

        return $held;
    }

    /**
     * Tiket terbuka tanpa satu pun balasan tim (admin atau staf) lebih lama dari N jam dilaporkan ke admin dalam satu
     * email ringkasan. Tiket ditandai (sla_breached_at) sehingga tidak dilaporkan dua kali.
     */
    public function flagUnansweredTickets(): int
    {
        $hours = max(1, (int) config('quix.automation.ticket_sla_hours'));

        $tickets = RevisionTicket::query()
            ->whereIn('status', [TicketStatus::Open->value, TicketStatus::InProgress->value])
            ->whereNull('sla_breached_at')
            ->where('created_at', '<=', now()->subHours($hours))
            ->whereHas('project', fn ($q) => $q->whereNotIn('status', [ProjectStatus::Closed->value, ProjectStatus::Cancelled->value]))
            ->whereDoesntHave('comments', fn ($q) => $q->reorder()->whereHas('user', fn ($u) => $u->whereIn('role', [UserRole::Admin->value, UserRole::Staff->value])))
            ->with('project')
            ->orderBy('created_at')
            ->get();

        $flagged = [];

        foreach ($tickets as $ticket) {
            // Tandai dengan syarat "belum ditandai": proses yang berjalan bersamaan tidak melaporkan tiket yang sama dua kali.
            $marked = RevisionTicket::query()
                ->whereKey($ticket->id)
                ->whereNull('sla_breached_at')
                ->update(['sla_breached_at' => now()]);

            if ($marked !== 1) {
                continue;
            }

            ActivityLogger::log(ActivityAction::TicketSlaBreached, $ticket->project, [
                'ticket' => $ticket->id,
                'title' => $ticket->title,
                'hours' => $hours,
            ]);

            $flagged[] = ['title' => $ticket->title, 'project' => $ticket->project->title];
        }

        if ($flagged !== []) {
            $this->notifyAdmins($flagged, $hours);
        }

        return count($flagged);
    }

    /**
     * Hapus isi payload webhook yang sudah lewat masa simpan (purge_after, bawaan 90 hari). Baris delivery tetap ada
     * (id, status, waktu) untuk idempotensi dan audit. Delivery gagal atau belum diproses tidak disentuh agar masih bisa
     * diulang dari Control Room.
     */
    public function purgeWebhookPayloads(): int
    {
        return DB::table('webhook_deliveries')
            ->whereIn('status', ['processed', 'ignored'])
            ->whereNull('payload_purged_at')
            ->whereNotNull('purge_after')
            ->where('purge_after', '<=', now())
            ->update(['payload' => '[]', 'payload_purged_at' => now()]);
    }

    /** @param list<array{title: string, project: string}> $tickets */
    private function notifyAdmins(array $tickets, int $hours): void
    {
        $admins = User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->get();

        foreach ($admins as $admin) {
            try {
                $admin->notify((new TicketSlaNotification($tickets, $hours))->locale($admin->locale ?: 'id'));
            } catch (Throwable $e) {
                Log::error('Ringkasan SLA tiket gagal dikirim.', ['admin' => $admin->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
