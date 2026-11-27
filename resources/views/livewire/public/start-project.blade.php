<div class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
    @php
        $t = static fn (string $key, string $idFallback, string $enFallback): string => \Illuminate\Support\Facades\Lang::has($key)
            ? (string) __($key)
            : ($locale === 'en' ? $enFallback : $idFallback);

        $serviceOptions = [
            'web' => \Illuminate\Support\Facades\Lang::has('public.services.items.web.title')
                ? (string) __('public.services.items.web.title')
                : ($locale === 'en' ? 'Web Development' : 'Pengembangan Web'),
            'automation' => \Illuminate\Support\Facades\Lang::has('public.services.items.automation.title')
                ? (string) __('public.services.items.automation.title')
                : ($locale === 'en' ? 'Business Automation' : 'Otomasi Bisnis'),
            'portal' => \Illuminate\Support\Facades\Lang::has('public.services.items.portal.title')
                ? (string) __('public.services.items.portal.title')
                : ($locale === 'en' ? 'Client Portal & Custom System' : 'Portal Klien & Sistem Kustom'),
        ];
    @endphp

    <div class="mb-8 max-w-2xl space-y-4">
        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold tracking-wide text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ $t('public.intake.badge', 'Mulai Proyek', 'Start a Project') }}
        </span>
        <h1 class="font-heading text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
            {{ $t('public.intake.heading', 'Ceritakan Kebutuhan Proyek Anda', 'Tell Us About Your Project') }}
        </h1>
        <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
            {{ $t('public.intake.subheading', 'Isi ringkasan kebutuhan di bawah ini. Tim kami akan meninjau brief Anda dan menyiapkan penawaran terstruktur beserta estimasi waktu pengerjaan.', 'Share your project brief below. Our team will review your requirements and prepare a structured quotation with timeline estimates.') }}
        </p>
    </div>

    @if ($submitted)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-6 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-950/40 sm:p-8" role="status" aria-live="polite">
            <div class="flex items-start gap-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                <div class="space-y-3">
                    <h2 class="font-heading text-xl font-semibold text-slate-900 dark:text-white">
                        {{ $t('public.intake.success_title', 'Pengajuan Proyek Berhasil Dikirim', 'Project Brief Submitted') }}
                    </h2>
                    <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">
                        {{ $submittedProjectTitle }}
                    </p>
                    <p class="text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                        {{ $t('public.intake.success_body', 'Terima kasih. Kami telah mencatat brief proyek Anda dan mengirimkan tautan pengaturan kata sandi portal klien ke alamat email Anda.', 'Thank you. We have recorded your project brief and sent a secure client portal password setup link to your email address.') }}
                    </p>
                    <div class="pt-2">
                        <a href="{{ url('/'.$locale) }}"
                           class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            {{ $t('public.intake.back_home', 'Kembali ke Beranda', 'Back to Home') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" novalidate>
            {{-- Honeypot anti-bot tersembunyi --}}
            <div class="hidden" aria-hidden="true">
                <label for="website_url">Website</label>
                <input id="website_url" type="text" wire:model="website_url" tabindex="-1" autocomplete="off" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="intake-name" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                        {{ $t('public.intake.fields.name', 'Nama Lengkap / Perusahaan', 'Full Name / Company') }} <span class="text-rose-600">*</span>
                    </label>
                    <input id="intake-name" type="text" wire:model="name" required autocomplete="name"
                           class="mt-1.5 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" />
                    @error('name')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="intake-email" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                        {{ $t('public.intake.fields.email', 'Alamat Email', 'Email Address') }} <span class="text-rose-600">*</span>
                    </label>
                    <input id="intake-email" type="email" wire:model="email" required autocomplete="email"
                           class="mt-1.5 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" />
                    @error('email')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="intake-whatsapp" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                        {{ $t('public.intake.fields.whatsapp', 'Nomor WhatsApp', 'WhatsApp Number') }} <span class="text-rose-600">*</span>
                    </label>
                    <input id="intake-whatsapp" type="tel" wire:model="whatsapp" required autocomplete="tel" placeholder="0812xxxxxxxx"
                           class="mt-1.5 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" />
                    @error('whatsapp')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="intake-service-type" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                        {{ $t('public.intake.fields.service_type', 'Jenis Layanan', 'Service Type') }} <span class="text-rose-600">*</span>
                    </label>
                    <select id="intake-service-type" wire:model="service_type"
                            class="mt-1.5 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach ($serviceOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('service_type')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="intake-title" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                    {{ $t('public.intake.fields.title', 'Judul Proyek', 'Project Title') }} <span class="text-rose-600">*</span>
                </label>
                <input id="intake-title" type="text" wire:model="title" required
                       class="mt-1.5 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" />
                @error('title')
                    <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="intake-description" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                    {{ $t('public.intake.fields.description', 'Ringkasan Kebutuhan & Fitur Utama', 'Project Brief & Key Features') }} <span class="text-rose-600">*</span>
                </label>
                <textarea id="intake-description" wire:model="description" rows="5" required
                          class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></textarea>
                @error('description')
                    <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="intake-currency" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                        {{ $t('public.intake.fields.currency', 'Mata Uang Proyek', 'Project Currency') }} <span class="text-rose-600">*</span>
                    </label>
                    <select id="intake-currency" wire:model="currency"
                            class="mt-1.5 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach ($currencies as $curr)
                            <option value="{{ $curr->value }}">{{ $curr->value }}</option>
                        @endforeach
                    </select>
                    @error('currency')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="intake-budget" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                        {{ $t('public.intake.fields.budget_reference', 'Referensi Anggaran (Opsional)', 'Budget Reference (Optional)') }}
                    </label>
                    <input id="intake-budget" type="text" inputmode="decimal" wire:model="budget_reference"
                           class="mt-1.5 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" />
                    @error('budget_reference')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <input type="hidden" wire:model="captcha_token" />
            @error('captcha_token')
                <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror

            <div class="space-y-1.5">
                <label class="flex min-h-11 cursor-pointer items-start gap-3">
                    <input type="checkbox" wire:model="consent_privacy"
                           class="mt-1 size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-950" />
                    <span class="text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                        @if ($locale === 'en')
                            I agree to the processing of my personal data in accordance with the
                            <a href="{{ url('/en/privacy') }}" class="font-medium text-emerald-700 underline hover:text-emerald-800 dark:text-emerald-400">Privacy Policy</a>
                            and
                            <a href="{{ url('/en/terms') }}" class="font-medium text-emerald-700 underline hover:text-emerald-800 dark:text-emerald-400">Terms &amp; Conditions</a>.
                        @else
                            Saya menyetujui pemrosesan data pribadi saya sesuai dengan
                            <a href="{{ url('/id/privacy') }}" class="font-medium text-emerald-700 underline hover:text-emerald-800 dark:text-emerald-400">Kebijakan Privasi</a>
                            dan
                            <a href="{{ url('/id/terms') }}" class="font-medium text-emerald-700 underline hover:text-emerald-800 dark:text-emerald-400">Syarat &amp; Ketentuan</a>.
                        @endif
                    </span>
                </label>
                @error('consent_privacy')
                    <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-xs transition hover:bg-emerald-700 disabled:opacity-60 sm:w-auto">
                    <span wire:loading.remove wire:target="submit">
                        {{ $t('public.intake.submit', 'Kirim Brief Proyek', 'Submit Project Brief') }}
                    </span>
                    <span wire:loading wire:target="submit">
                        {{ $t('public.intake.submitting', 'Mengirim...', 'Submitting...') }}
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
