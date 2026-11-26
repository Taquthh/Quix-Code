<?php

namespace App\Models;

use App\Events\TicketCommentCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketComment extends Model
{
    use HasFactory;

    protected $fillable = ['ticket_id', 'user_id', 'body', 'attachment_path'];

    /** @var array<string, class-string> */
    protected $dispatchesEvents = ['created' => TicketCommentCreated::class];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(RevisionTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
