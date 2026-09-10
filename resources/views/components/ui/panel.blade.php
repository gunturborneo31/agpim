@props(['padding' => 'p-5 md:p-7'])

<section {{ $attributes->class(['card-panel', $padding]) }}>
    {{ $slot }}
</section>
