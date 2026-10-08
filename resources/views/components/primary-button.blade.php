@if($attributes->has('href'))
<a {{ $attributes->merge(['class' => 'btn-primary inline-flex items-center']) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary']) }}>
    {{ $slot }}
</button>
@endif
