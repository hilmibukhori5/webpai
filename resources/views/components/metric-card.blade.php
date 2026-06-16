@props(['label', 'value'])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-200 p-5']) }}>
    <p class="text-sm text-slate-500">{{ $label }}</p>
    <p class="text-2xl font-heading font-semibold text-slate-900">{{ $value }}</p>
</div>
