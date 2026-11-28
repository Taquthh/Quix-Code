<?php

namespace App\Providers;

use App\Contracts\WhatsappGateway;
use App\Events\DownPaymentInvoiceIssued;
use App\Events\InvoiceIssued;
use App\Events\PaymentRejected;
use App\Events\PaymentVerified;
use App\Events\QuotationExpiringSoon;
use App\Events\QuotationSent;
use App\Events\TicketCommentCreated;
use App\Models\Invoice;
use App\Models\OrderChat;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectMilestone;
use App\Models\Quotation;
use App\Models\RevisionTicket;
use App\Models\User;
use App\Policies\ChatPolicy;
use App\Policies\FilePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MilestonePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\TicketPolicy;
use App\Services\Notifier;
use App\Services\OpsAlerts;
use App\Services\Whatsapp\HttpWhatsappGateway;
use App\Services\Whatsapp\LogWhatsappGateway;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsappGateway::class, fn () => match (config('services.whatsapp.driver')) {
            'http' => new HttpWhatsappGateway,
            default => new LogWhatsappGateway,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(RevisionTicket::class, TicketPolicy::class);
        Gate::policy(OrderChat::class, ChatPolicy::class);
        Gate::policy(ProjectMilestone::class, MilestonePolicy::class);
        Gate::policy(ProjectFile::class, FilePolicy::class);

        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('access-internal', fn (User $user) => $user->isAdmin() || $user->isStaff());
        Gate::define('access-portal', fn (User $user) => $user->isClient());

        // Email transaksional (T3-09). Closure, bukan kelas listener, agar tidak ikut terdaftar dua kali oleh auto-discovery.
        Event::listen(QuotationSent::class, fn (QuotationSent $e) => app(Notifier::class)->quotationSent($e->quotation));
        Event::listen(QuotationExpiringSoon::class, fn (QuotationExpiringSoon $e) => app(Notifier::class)->quotationExpiring($e->quotation));
        Event::listen(DownPaymentInvoiceIssued::class, fn (DownPaymentInvoiceIssued $e) => app(Notifier::class)->downPaymentInvoice($e->invoice));

        // Email + lonceng portal (T4-09).
        Event::listen(InvoiceIssued::class, fn (InvoiceIssued $e) => app(Notifier::class)->invoiceIssued($e->invoice));
        Event::listen(PaymentVerified::class, fn (PaymentVerified $e) => app(Notifier::class)->paymentVerified($e->payment));
        Event::listen(PaymentRejected::class, fn (PaymentRejected $e) => app(Notifier::class)->paymentRejected($e->payment));
        Event::listen(TicketCommentCreated::class, fn (TicketCommentCreated $e) => app(Notifier::class)->ticketReplied($e->comment));

        // Peringatan operasional (T5-09): job gagal permanen dan antrean menumpuk.
        Event::listen(JobFailed::class, fn (JobFailed $e) => app(OpsAlerts::class)->failedJob($e->job->resolveName(), (string) $e->job->getQueue(), $e->exception));
        Event::listen(QueueBusy::class, fn (QueueBusy $e) => app(OpsAlerts::class)->queueBusy((string) config('queue.default'), (string) $e->queue, (int) $e->size));

        // Catat login terakhir (termasuk login via passkey dan 2FA).
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Mode ketat (T0-10), aktif di non-produksi: menangkap N+1 (lazy loading), atribut yang
        // diam-diam terbuang, dan akses atribut yang tidak ada. Di produksi tidak melempar galat.
        Model::shouldBeStrict(! app()->isProduction());
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        Model::preventAccessingMissingAttributes(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
