@props([
    'text',
    'status' => null,
    'tone' => 'neutral',
])

@php
    $chipClass = $status ? match ($status) {
        'submitted', 'under_review' => 'chip-info',
        'needs_revision' => 'chip-wait',
        'approved', 'completed' => 'chip-ok',
        'rejected' => 'chip-bad',
        default => 'chip-neutral',
    } : match ($tone) {
        'danger' => 'chip-bad',
        'warning' => 'chip-wait',
        'info' => 'chip-info',
        'success' => 'chip-ok',
        default => 'chip-neutral',
    };
@endphp

<span {{ $attributes->class(['chip', $chipClass]) }}>{{ $text }}</span>
