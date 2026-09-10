@props([
    'text',
    'status' => null,
    'tone' => 'neutral',
])

@php
    $toneClass = $status ? match ($status) {
        'draft' => 'border-slate-200 bg-slate-100 text-slate-700',
        'submitted' => 'border-sky-200 bg-sky-100 text-sky-700',
        'under_review' => 'border-indigo-200 bg-indigo-100 text-indigo-700',
        'awaiting_disposition' => 'border-amber-200 bg-amber-100 text-amber-700',
        'approved' => 'border-emerald-200 bg-emerald-100 text-emerald-700',
        'needs_revision' => 'border-orange-200 bg-orange-100 text-orange-700',
        'rejected' => 'border-rose-200 bg-rose-100 text-rose-700',
        'completed' => 'border-teal-200 bg-teal-100 text-teal-700',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    } : match ($tone) {
        'danger' => 'border-red-200 bg-red-50 text-red-700',
        'warning' => 'border-orange-200 bg-orange-50 text-orange-700',
        'info' => 'border-blue-200 bg-blue-50 text-blue-700',
        default => 'border-slate-200 bg-slate-50 text-slate-600',
    };
@endphp

<span {{ $attributes->class(['rounded-full border px-3 py-1 text-xs uppercase tracking-[0.2em]', $toneClass]) }}>{{ $text }}</span>
