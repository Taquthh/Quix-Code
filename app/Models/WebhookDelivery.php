<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'delivery_id', 'event', 'status', 'payload',
        'received_at', 'processed_at', 'error', 'signature_valid', 'attempts', 'purge_after', 'payload_purged_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'signature_valid' => 'boolean',
            'attempts' => 'integer',
            'purge_after' => 'datetime',
            'payload_purged_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
