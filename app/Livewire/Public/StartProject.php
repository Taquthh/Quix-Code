<?php

namespace App\Livewire\Public;

use App\Enums\Currency;
use App\Http\Middleware\SetLocale;
use App\Services\CaptchaVerifier;
use App\Services\PublicLeadIntakeService;
use App\Support\DisposableEmailDomains;
use Closure;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Form intake "Start a Project" pada etalase publik (T6-02, T6-04, T6-07, T6-08).
 */
class StartProject extends Component
{
    #[Locked]
    public string $locale = 'id';

    /** @var array<string, string> */
    #[Locked]
    public array $utm = [];

    public string $name = '';

    public string $email = '';

    public string $whatsapp = '';

    public string $service_type = 'web';

    public string $title = '';

    public string $description = '';

    public string $currency = 'IDR';

    public string $budget_reference = '';

    public bool $consent_privacy = false;

    /** Field honeypot tersembunyi — manusia tidak pernah mengisinya. */
    public string $website_url = '';

    public string $captcha_token = '';

    public bool $submitted = false;

    public string $submittedProjectTitle = '';

    public function mount(string $locale = 'id'): void
    {
        $this->locale = in_array($locale, SetLocale::SUPPORTED, true) ? $locale : 'id';
        $this->currency = $this->locale === 'en' ? Currency::USD->value : Currency::IDR->value;

        $captured = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $key) {
            $val = request()->query($key);
            if (is_string($val) && trim($val) !== '') {
                $captured[$key] = Str::limit(trim($val), 120, '');
            }
        }
        $this->utm = $captured;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && DisposableEmailDomains::isDisposable($value)) {
                        $fail(__('public.intake.errors.disposable_email'));
                    }
                },
            ],
            'whatsapp' => [
                'required',
                'string',
                'min:8',
                'max:25',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $digits = is_string($value) ? (preg_replace('/\\D+/', '', $value) ?? '') : '';
                    $normalizedDigits = str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;

                    if (strlen($normalizedDigits) < 8 || strlen($normalizedDigits) > 15) {
                        $fail(__('public.intake.errors.whatsapp_invalid'));
                    }
                },
            ],
            'service_type' => ['required', Rule::in(['web', 'automation', 'portal'])],
            'title' => ['required', 'string', 'min:4', 'max:180', 'not_regex:/[\\r\\n]/'],
            'description' => ['required', 'string', 'min:15', 'max:5000'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'budget_reference' => ['nullable', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'consent_privacy' => ['accepted'],
            'captcha_token' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function submit(PublicLeadIntakeService $intake, CaptchaVerifier $captcha): void
    {
        // Honeypot: jika terisi oleh bot, selesaikan secara senyap tanpa membuat data apa pun.
        if (trim($this->website_url) !== '') {
            $this->submitted = true;
            $this->submittedProjectTitle = trim($this->title);

            return;
        }

        $ip = request()->ip() ?? 'unknown';
        $rateKey = 'public-lead-intake:'.$ip;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->addError('email', __('public.intake.errors.rate_limited'));

            return;
        }

        RateLimiter::hit($rateKey, 3600);

        $this->email = Str::lower(trim($this->email));
        $this->whatsapp = trim($this->whatsapp);
        $this->validate();

        if (! $captcha->verify($this->captcha_token, request()->ip())) {
            $this->captcha_token = '';
            $this->dispatch('quix-captcha-reset');
            $this->addError('captcha_token', __('public.intake.errors.captcha_failed'));

            return;
        }

        $project = $intake->submit([
            'name' => $this->name,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'title' => $this->title,
            'service_type' => $this->service_type,
            'description' => $this->description,
            'currency' => Currency::from($this->currency),
            'budget_reference' => trim($this->budget_reference) !== '' ? trim($this->budget_reference) : null,
            'locale' => $this->locale,
            'utm' => $this->utm,
            'ip' => request()->ip(),
        ]);

        $this->submitted = true;
        $this->submittedProjectTitle = $project->title;
    }

    public function render(): View
    {
        $suffix = '/start-project';

        /** @var view-string $view */
        $view = 'livewire.public.start-project';

        $captcha = app(CaptchaVerifier::class);

        return view($view, [
            'currencies' => Currency::cases(),
            'captchaConfigured' => $captcha->isConfigured(),
            'captchaWidgetEnabled' => $captcha->shouldRenderWidget(),
            'captchaSiteKey' => config('services.captcha.site_key'),
        ])->layout('layouts::public', [
            'locale' => $this->locale,
            'seo' => [
                'title' => __('public.seo.start_title'),
                'description' => __('public.seo.start_description'),
                'canonical' => url('/'.$this->locale.$suffix),
                'alternates' => [
                    'id' => url('/id'.$suffix),
                    'en' => url('/en'.$suffix),
                    'x-default' => url('/id'.$suffix),
                ],
                'og_locale' => $this->locale === 'en' ? 'en_US' : 'id_ID',
            ],
        ]);
    }
}
