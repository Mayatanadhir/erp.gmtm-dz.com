@if($attributes->has('href'))
<a {{ $attributes->merge(['class' => 'btn-warning inline-flex items-center justify-center']) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-warning']) }}>
    {{ $slot }}
</button>
@endif
