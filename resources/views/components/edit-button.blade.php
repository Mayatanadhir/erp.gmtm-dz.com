@props([
    'icon' => true,
])

@if($attributes->has('href'))
<a {{ $attributes->merge(['class' => 'btn-edit inline-flex items-center gap-1.5']) }}>
    @if($icon)
        <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    @endif
    <span>{{ $slot->isEmpty() ? __('Edit') : $slot }}</span>
</a>
@else
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-edit inline-flex items-center gap-1.5']) }}>
    @if($icon)
        <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    @endif
    <span>{{ $slot->isEmpty() ? __('Edit') : $slot }}</span>
</button>
@endif
