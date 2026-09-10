@props([
    'label',
    'value',
])

<div {{ $attributes->class(['metric-card']) }}>
    <span>{{ $label }}</span>
    <strong>{{ $value }}</strong>
</div>
