<?php

namespace App\Events;

use App\Models\TicketComment;
use Illuminate\Foundation\Events\Dispatchable;

/** Komentar baru pada tiket revisi. Dipicu otomatis oleh model TicketComment (T4-09). */
final class TicketCommentCreated
{
    use Dispatchable;

    public function __construct(public readonly TicketComment $comment) {}
}
