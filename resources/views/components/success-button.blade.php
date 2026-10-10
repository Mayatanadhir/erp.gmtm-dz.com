@if($attributes->has('href'))
<a {{ $attributes->merge(['class' => 'btn-success inline-flex items-center justify-center']) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-success']) }}>
    {{ $slot }}
</button>
@endif
