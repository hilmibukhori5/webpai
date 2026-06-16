@props(['code', 'name', 'color' => 'bg-slate-400', 'componentNames' => null, 'reason' => null, 'price' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-200 p-5 space-y-3 hover:shadow-md transition-shadow']) }}>
    <div class="flex items-start justify-between gap-2">
        <span class="inline-block {{ $color }} text-white text-xs font-semibold px-2 py-0.5 rounded-lg">
            {{ $code }}
        </span>

        @isset($badge)
            {{ $badge }}
        @endisset
    </div>

    <div>
        <h3 class="font-heading font-semibold text-slate-900">{{ $name }}</h3>
        @if ($componentNames)
            <p class="text-xs text-slate-500 mt-0.5">{{ $componentNames }}</p>
        @endif
    </div>

    @if ($reason)
        <p class="text-sm text-slate-500">{{ $reason }}</p>
    @endif

    <div class="flex items-center justify-between pt-2">
        <span class="text-sm font-medium text-slate-700">
            @if ($price)
                Rp{{ number_format($price, 0, ',', '.') }}
            @endif
        </span>

        @isset($footer)
            {{ $footer }}
        @endisset
    </div>
</div>
