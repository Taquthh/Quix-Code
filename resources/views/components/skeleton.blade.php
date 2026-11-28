@props(['lines' => 3, 'block' => false])

{{-- Placeholder memuat. $block = kotak tinggi (gambar/kartu); default = baris teks. --}}
<div role="status" aria-busy="true" {{ $attributes->class('animate-pulse motion-reduce:animate-none') }}>
    @if ($block)
        <div class="h-32 w-full rounded-card bg-slate-200 dark:bg-slate-800"></div>
    @else
        <div class="space-y-3">
            @foreach (range(1, max(1, (int) $lines)) as $i)
                <div @class(['h-3 rounded bg-slate-200 dark:bg-slate-800', 'w-full' => ! $loop->last, 'w-2/3' => $loop->last])></div>
            @endforeach
        </div>
    @endif
    <span class="sr-only">{{ __('ui.loading') }}</span>
</div>
