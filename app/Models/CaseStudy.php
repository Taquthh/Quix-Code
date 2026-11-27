<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string|null $project_id
 * @property string $slug
 * @property array<string, string> $title
 * @property array<string, string> $summary
 * @property list<string>|null $tech_stack
 * @property string|null $cover_path
 * @property bool $is_published
 * @property CarbonInterface|null $published_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 */
class CaseStudy extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = ['project_id', 'slug', 'title', 'summary', 'tech_stack', 'cover_path'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'summary' => 'array',
            'tech_stack' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Ambil teks sesuai locale aktif; fallback ke Indonesia lalu bahasa apa pun yang ada. */
    public function localized(string $field, ?string $locale = null): string
    {
        $values = (array) $this->{$field};
        $locale ??= app()->getLocale();

        return $values[$locale] ?? $values['id'] ?? (reset($values) ?: '');
    }
}
