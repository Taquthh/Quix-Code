<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $table = 'notifications_log';

    protected $fillable = ['user_id', 'channel', 'template', 'template_version', 'locale', 'reference', 'provider_message_id', 'attempts', 'fallback_used', 'status', 'sent_at', 'error'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'template_version' => 'integer', 'fallback_used' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
