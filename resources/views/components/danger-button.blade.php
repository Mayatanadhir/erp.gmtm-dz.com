@if($attributes->has('href'))
<a {{ $attributes->merge(['class' => 'btn-danger inline-flex items-center']) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-danger']) }}>
    {{ $slot }}
</button>
@endif
