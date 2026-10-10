@if($attributes->has('href'))
<a {{ $attributes->merge(['class' => 'btn-info inline-flex items-center justify-center']) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-info']) }}>
    {{ $slot }}
</button>
@endif
