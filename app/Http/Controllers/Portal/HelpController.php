<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** T4-13. FAQ singkat dan kontak dukungan; chat tim dibuka dari proyek milik klien sendiri. */
class HelpController extends Controller
{
    public function __invoke(Request $request): View
    {
        $projects = Project::query()
            ->visibleTo($request->user())
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'status'])
            ->reject(fn (Project $project): bool => $project->status->isTerminal())
            ->values();

        return view('portal.help', [
            'projects' => $projects,
            'supportEmail' => config('quix.support.email'),
            'supportWhatsapp' => config('quix.support.whatsapp'),
        ]);
    }
}
