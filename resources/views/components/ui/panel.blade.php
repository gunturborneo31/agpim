@props(['padding' => 'p-5 md:p-6', 'plain' => false])

{{-- plain: pembungkus tingkat halaman, tanpa kotak, supaya tidak ada kartu di dalam kartu. --}}
<section {{ $attributes->class($plain ? [] : ['card-panel', $padding]) }}>
    {{ $slot }}
</section>
