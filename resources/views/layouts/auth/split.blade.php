<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-slate-50 antialiased dark:bg-slate-950">
        <main class="grid min-h-dvh lg:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)]">
            <aside data-auth-panel class="relative hidden min-h-dvh flex-col justify-between overflow-hidden p-8 text-white lg:flex xl:p-14">
                <a href="{{ route('home') }}" class="relative z-10 flex w-fit items-center gap-3 font-heading text-lg font-semibold tracking-tight" wire:navigate>
                    <span class="flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/[0.08] shadow-lg shadow-black/10">
                        <x-app-logo-icon class="size-7 fill-current text-white" />
                    </span>
                    {{ config('app.name', 'Laravel') }}
                </a>

                @php
                    [$message, $author] = str(Illuminate\\Foundation\\Inspiring::quotes()->random())->explode('-');
                @endphp

                <div class="relative z-10 max-w-2xl py-16">
                    <span class="mb-6 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">
                        <span class="size-1.5 rounded-full bg-emerald-300"></span>
                        {{ config('app.name', 'Laravel') }}
                    </span>
                    <blockquote class="space-y-5">
                        <flux:heading size="lg" class="font-heading text-3xl font-medium leading-tight tracking-tight text-white xl:text-4xl">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                        <footer class="text-sm font-medium text-slate-300">{{ trim($author) }}</footer>
                    </blockquote>
                </div>

                <div class="relative z-10 flex items-center gap-2 text-xs font-medium text-slate-400">
                    <span class="size-1.5 rounded-full bg-emerald-300"></span>
                    {{ url('/') }}
                </div>
                <div class="pointer-events-none absolute -bottom-28 -end-24 size-[30rem] rounded-full border border-emerald-100/10"></div>
                <div class="pointer-events-none absolute -bottom-12 -end-8 size-[20rem] rounded-full border border-emerald-100/[0.07]"></div>
            </aside>

            <section class="flex min-w-0 flex-col justify-center px-5 py-12 sm:px-10 lg:px-12 xl:px-20">
                <a href="{{ route('home') }}" class="mx-auto mb-10 flex items-center gap-3 font-heading text-lg font-semibold tracking-tight text-slate-900 dark:text-white lg:hidden" wire:navigate>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-slate-900 text-white shadow-lg shadow-slate-900/15 dark:bg-white dark:text-slate-950">
                        <x-app-logo-icon class="size-7 fill-current" />
                    </span>
                    {{ config('app.name', 'Laravel') }}
                </a>
                <div class="mx-auto w-full max-w-[26rem]">
                    {{ $slot }}
                </div>
            </section>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
