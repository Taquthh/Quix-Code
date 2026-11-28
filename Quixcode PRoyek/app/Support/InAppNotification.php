<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\Project;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use ValueError;

/**
 * T4-09. Isi notifikasi lonceng. Yang disimpan hanya kunci terjemahan, angka mentah, dan nama route
 * (bukan teks jadi atau URL), sehingga lonceng mengikuti bahasa akun saat dibaca, uang diformat ulang,
 * dan tautan tidak pernah berasal dari input pengguna.
 */
final class InAppNotification
{
    /**
     * @param  array{amount?: int, currency?: string, number?: string, reason?: string, version?: int|string, subject?: string}  $extra
     * @return array<string, mixed>
     */
    public static function payload(string $key, Project $project, array $extra = [], string $route = 'portal.projects.invoices'): array
    {
        return [
            'key' => $key,
            'project_id' => (string) $project->id,
            'project_title' => $project->title,
            'route' => $route,
            ...$extra,
        ];
    }

    /**
     * @return array{title: string, body: string, url: string|null}
     */
    public static function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $key = is_string($data['key'] ?? null) ? $data['key'] : '';

        if ($key === '' || ! Lang::has("notifications.items.{$key}.title")) {
            return ['title' => __('notifications.unknown'), 'body' => '', 'url' => null];
        }

        $params = [
            'project' => (string) ($data['project_title'] ?? ''),
            'number' => (string) ($data['number'] ?? ''),
            'version' => (string) ($data['version'] ?? ''),
            'reason' => (string) ($data['reason'] ?? ''),
            'subject' => (string) ($data['subject'] ?? ''),
            'amount' => self::money($data),
        ];

        $route = is_string($data['route'] ?? null) ? $data['route'] : '';
        $projectId = is_string($data['project_id'] ?? null) ? $data['project_id'] : '';
        $url = $projectId !== '' && str_starts_with($route, 'portal.') && Route::has($route)
            ? route($route, ['project' => $projectId])
            : null;

        return [
            'title' => __("notifications.items.{$key}.title", $params),
            'body' => __("notifications.items.{$key}.body", $params),
            'url' => $url,
        ];
    }

    /** @param  array<string, mixed>  $data */
    private static function money(array $data): string
    {
        if (! isset($data['amount'], $data['currency']) || ! is_int($data['amount']) || ! is_string($data['currency'])) {
            return '';
        }

        try {
            return Money::of($data['amount'], Currency::from($data['currency']))->format();
        } catch (ValueError) {
            return '';
        }
    }
}
