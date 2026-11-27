<?php

use App\Http\Controllers\Admin\CsvExportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\InvoicePrintController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\Portal\HelpController;
use App\Http\Controllers\Portal\OverviewController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\Public\PublicCurrencyController;
use App\Http\Controllers\Public\PublicPageController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\QuotationPrintController;
use App\Http\Controllers\TicketScreenshotController;
use App\Http\Controllers\Webhooks\GithubWebhookController;
use App\Http\Middleware\RequireAdminTwoFactor;
use App\Livewire\Admin\Activity\Index as ActivityIndex;
use App\Livewire\Admin\Billing\Index as BillingIndex;
use App\Livewire\Admin\Leads\Index as LeadsIndex;
use App\Livewire\Admin\Projects\Form as ProjectForm;
use App\Livewire\Admin\Projects\Index as ProjectIndex;
use App\Livewire\Admin\Projects\InvoiceManager;
use App\Livewire\Admin\Projects\Kanban;
use App\Livewire\Admin\Projects\Negotiation as ProjectNegotiation;
use App\Livewire\Admin\Projects\Show as ProjectShow;
use App\Livewire\Admin\Quotations\Builder as QuotationBuilder;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\NotificationTemplates as NotificationTemplatesIndex;
use App\Livewire\Admin\Team\ClientPreview;
use App\Livewire\Admin\Team\Index as TeamIndex;
use App\Livewire\Portal\Handover as PortalHandover;
use App\Livewire\Portal\Invoices as PortalInvoices;
use App\Livewire\Portal\Negotiation as PortalNegotiation;
use App\Livewire\Portal\NotificationCenter as PortalNotifications;
use App\Livewire\Portal\ProjectOverview as PortalProjectOverview;
use App\Livewire\Public\StartProject as PublicStartProject;
use App\Models\Project;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// Etalase publik default (ID) & SEO sitemap (Tahap 6).
Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::post('public/currency/{currency}', PublicCurrencyController::class)->name('public.currency');

// Etalase publik bilingual berprefiks /id dan /en (T6-01 s.d. T6-10).
Route::prefix('{locale}')
    ->whereIn('locale', ['id', 'en'])
    ->name('public.')
    ->group(function () {
        Route::get('/', [PublicPageController::class, 'home'])->name('home');
        Route::get('services', [PublicPageController::class, 'services'])->name('services');
        Route::get('works', [PublicPageController::class, 'works'])->name('works');
        Route::get('works/{slug}', [PublicPageController::class, 'workShow'])->name('works.show');
        Route::get('about', [PublicPageController::class, 'about'])->name('about');
        Route::get('faq', [PublicPageController::class, 'faq'])->name('faq');
        Route::livewire('start-project', PublicStartProject::class)->name('start-project');
        Route::get('privacy', [PublicPageController::class, 'privacy'])->name('privacy');
        Route::get('terms', [PublicPageController::class, 'terms'])->name('terms');
    });

// Pilihan bahasa (ID/EN), disimpan di cookie dan users.locale.
Route::post('locale/{locale}', LocaleController::class)->name('locale.update');
Route::get('files/{file}', ProjectFileController::class)
    ->middleware(['auth', 'verified'])
    ->name('files.download');
Route::get('invoices/{invoice}/print', InvoicePrintController::class)
    ->middleware(['auth', 'verified'])
    ->name('admin.invoices.print');
Route::get('quotations/{quotation}/print', QuotationPrintController::class)
    ->middleware(['auth', 'verified'])
    ->name('quotations.print');
Route::get('tickets/{ticket}/screenshot', TicketScreenshotController::class)
    ->middleware(['auth', 'verified'])
    ->name('tickets.screenshot');
Route::get('payments/{payment}/proof', PaymentProofController::class)
    ->middleware(['auth', 'verified'])
    ->name('admin.payments.proof');

// Webhook GitHub per proyek (T5-01): tanpa sesi/CSRF; keamanan lewat tanda tangan HMAC.
Route::post('webhooks/github/{project}', GithubWebhookController::class)
    ->where('project', '[0-9A-Za-z]{26}')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware('throttle:300,1')
    ->name('webhooks.github');

/*
 * Pintu masuk tunggal setelah login. Semua alur Fortify (login, 2FA, passkey,
 * verifikasi email, registrasi) berakhir di /dashboard (config fortify.home),
 * lalu dialihkan ke dasbor sesuai peran.
 */
Route::middleware(['auth', 'verified'])->get('dashboard', function () {
    return redirect()->route(auth()->user()->role->dashboardRoute());
})->name('dashboard');

// Area kerja internal: admin & staf.
Route::middleware(['auth', 'verified', 'can:access-internal', RequireAdminTwoFactor::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('search', SearchController::class)->middleware('can:admin')->name('search');
        Route::get('exports/{type}', CsvExportController::class)->middleware('can:admin')->name('exports');

        // Project Control Room (Tahap 2b). Otorisasi detail ada di komponen Livewire (Policy).
        Route::livewire('projects', ProjectIndex::class)->name('projects.index');
        Route::livewire('projects/create', ProjectForm::class)
            ->middleware('can:create,'.Project::class)
            ->name('projects.create');
        Route::livewire('projects/{project}', ProjectShow::class)->name('projects.show');
        Route::livewire('projects/{project}/edit', ProjectForm::class)->name('projects.edit');
        Route::livewire('projects/{project}/invoices', InvoiceManager::class)->middleware('can:admin')->name('projects.invoices');
        Route::livewire('team', TeamIndex::class)->middleware('can:admin')->name('team.index');
        Route::livewire('team/client-preview/{client}', ClientPreview::class)->middleware('can:admin')->name('team.preview');
        Route::livewire('settings', SettingsIndex::class)->middleware('can:admin')->name('settings');
        Route::livewire('settings/notification-templates', NotificationTemplatesIndex::class)->middleware('can:admin')->name('settings.templates');
        Route::livewire('kanban', Kanban::class)->name('kanban');
        Route::livewire('billing', BillingIndex::class)->middleware('can:admin')->name('billing.index');
        Route::livewire('activity', ActivityIndex::class)->middleware('can:admin')->name('activity');

        // Quotation & Negosiasi (Tahap 3). Staf ditolak oleh QuotationPolicy/ChatPolicy di komponen.
        Route::livewire('leads', LeadsIndex::class)->middleware('can:admin')->name('leads.index');
        Route::livewire('projects/{project}/negotiation', ProjectNegotiation::class)->name('projects.negotiation');
        Route::livewire('projects/{project}/quotations/create', QuotationBuilder::class)->name('quotations.create');
        Route::livewire('projects/{project}/quotations/{quotation}', QuotationBuilder::class)->name('quotations.edit');
    });

// Portal klien: hanya klien.
Route::middleware(['auth', 'verified', 'can:access-portal'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/', OverviewController::class)->name('overview');
        Route::get('help', HelpController::class)->name('help');
        Route::livewire('projects/{project}', PortalProjectOverview::class)->name('projects.show');
        Route::livewire('projects/{project}/negotiation', PortalNegotiation::class)->name('projects.negotiation');
        Route::livewire('projects/{project}/invoices', PortalInvoices::class)->name('projects.invoices');
        Route::livewire('projects/{project}/handover', PortalHandover::class)->name('projects.handover');
        Route::livewire('notifications', PortalNotifications::class)->name('notifications');
    });

// Halaman contoh komponen (hanya lokal/testing).
if (app()->environment(['local', 'testing'])) {
    Route::view('design-system', 'design-system.demo')
        ->middleware(['auth', 'verified'])
        ->name('design-system');
}

require __DIR__.'/settings.php';
