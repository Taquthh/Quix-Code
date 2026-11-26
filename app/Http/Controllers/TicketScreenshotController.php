<?php

namespace App\Http\Controllers;

use App\Models\RevisionTicket;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** T4-06. Tangkapan layar tiket keluar hanya lewat route ini (disk privat, tanpa URL publik). */
class TicketScreenshotController
{
    public function __invoke(string $ticket): Response
    {
        $record = RevisionTicket::query()->visibleTo(auth()->user())->findOrFail($ticket);

        Gate::authorize('view', $record);

        $path = $record->screenshot_path;
        abort_unless(is_string($path) && preg_match('#^tickets/[a-z0-9]{26}\.(?:jpg|png|webp)$#D', $path) === 1, 404);

        $disk = Storage::disk((string) config('uploads.disk', 'local'));
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, ['X-Content-Type-Options' => 'nosniff']);
    }
}
