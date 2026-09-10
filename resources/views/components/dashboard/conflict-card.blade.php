@props([
    'title',
    'window',
    'recommendation',
])

<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
    <p class="font-semibold text-red-700">{{ $title }}</p>
    <p class="mt-1 text-slate-600">Waktu bentrok: {{ $window }}</p>
    <p class="mt-2 text-slate-500">{{ $recommendation }}</p>
</div>
