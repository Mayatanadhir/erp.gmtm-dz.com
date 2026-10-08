@if($attributes->has('href'))
<a {{ $attributes->merge(['class' => 'btn-secondary inline-flex items-center']) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary']) }}>
    {{ $slot }}
</button>
@endif
