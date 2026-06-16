@props(['variant' => 'belum-eligible'])

@php
    // docs/spec.md bagian 10: 3 state eligibility (eligible-baru, eligible-lama,
    // belum-eligible) + state submission (pending/approved/rejected) pakai
    // bahasa warna yang sama biar konsisten (emerald=positif, blue=PKS Lama,
    // amber=menunggu, rose=negatif, slate=netral).
    $styles = [
        'eligible-baru' => ['bg-emerald-50', 'text-emerald-700', 'check'],
        'eligible-lama' => ['bg-blue-50', 'text-blue-700', 'check'],
        'belum-eligible' => ['bg-slate-100', 'text-slate-500', 'lock'],
        'pending' => ['bg-amber-50', 'text-amber-700', null],
        'approved' => ['bg-emerald-50', 'text-emerald-700', 'check'],
        'rejected' => ['bg-rose-50', 'text-rose-700', null],
    ];
    [$bg, $text, $icon] = $styles[$variant] ?? $styles['belum-eligible'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full {$bg} {$text}"]) }}>
    @if ($icon === 'check')
        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.415l-7.5 7.5a1 1 0 01-1.415 0l-3.5-3.5a1 1 0 111.415-1.415L8.5 12.085l6.79-6.794a1 1 0 011.415 0z" clip-rule="evenodd" />
        </svg>
    @elseif ($icon === 'lock')
        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 1a4 4 0 00-4 4v2H5a2 2 0 00-2 2v8a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-1V5a4 4 0 00-4-4zm-2 6V5a2 2 0 114 0v2H8z" clip-rule="evenodd" />
        </svg>
    @endif
    {{ $slot }}
</span>
