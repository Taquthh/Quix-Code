@props(['title' => null, 'padded' => true])

<section {{ $attributes->class('overflow-hidden rounded-card border border-slate-200/80 bg-white shadow-[0_1px_2px_rgb(15_23_42_/_0.06)] transition-colors duration-200 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:shadow-[0_1px_2px_rgb(0_0_0_/_0.24)] dark:hover:border-slate-700') }}>
    @if ($title || isset($header))
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 px-card py-3 dark:border-slate-800">
            @if ($title)
                <h3 class="font-heading text-base font-semibold text-slate-900 dark:text-slate-50">{{ $title }}</h3>
            @endif
            @isset($header)
                <div>{{ $header }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-card' => $padded])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-slate-200 px-card py-3 dark:border-slate-800">
            {{ $footer }}
        </footer>
    @endisset
</section>
