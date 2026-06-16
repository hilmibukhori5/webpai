@props(['variant' => 'primary', 'href' => null])

@php
    $base = 'inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium transition';

    $variants = [
        'primary' => 'bg-indigo-600 hover:bg-indigo-700 text-white',
        'ghost' => 'bg-transparent hover:bg-slate-100 text-slate-700 border border-slate-200',
        'disabled' => 'bg-slate-100 text-slate-400 cursor-not-allowed',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href && $variant !== 'disabled')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button
        {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}
        @if ($variant === 'disabled') disabled @endif
    >
        {{ $slot }}
    </button>
@endif
