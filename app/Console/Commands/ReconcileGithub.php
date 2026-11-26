<?php

namespace App\Console\Commands;

use App\Services\GithubReconciler;
use Illuminate\Console\Command;

class ReconcileGithub extends Command
{
    protected $signature = 'github:reconcile {--project= : Batasi pada satu ID proyek}';

    protected $description = 'Tambal commit yang webhook GitHub-nya terlewat lewat GitHub API (T5-08)';

    public function handle(GithubReconciler $reconciler): int
    {
        if ((string) config('services.github.token') === '') {
            $this->error('GITHUB_TOKEN belum diisi; rekonsiliasi dilewati.');

            return self::FAILURE;
        }

        $result = $reconciler->run($this->option('project') ?: null);

        $this->info("{$result['missing']} commit terlewat diantrekan dari {$result['projects']} proyek.");

        if ($result['errors'] > 0) {
            $this->warn("{$result['errors']} proyek gagal diperiksa; lihat log aplikasi.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
