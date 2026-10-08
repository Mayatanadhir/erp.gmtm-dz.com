@props([
    'actionUrl'      => null,
    'itemName'       => null,
    'confirmMessage' => null,
    'title'          => null,
    'method'         => 'DELETE',
    'submitText'     => null,
])

@php
    $resolvedTitle = $title ?? __('Delete');
@endphp

@if($actionUrl)
    <x-table.action
        type="delete"
        button-type="button"
        x-data
        @click.prevent="$dispatch('open-delete-modal', {
            action: {{ json_encode($actionUrl) }},
            name: {{ json_encode($itemName) }},
            title: {{ json_encode($title) }},
            message: {{ json_encode($confirmMessage) }},
            method: {{ json_encode($method) }},
            submitText: {{ json_encode($submitText) }}
        })"
        :title="$resolvedTitle"
        {{ $attributes }}
    >
        {{ $slot }}
    </x-table.action>
@else
    <x-table.action type="delete" {{ $attributes }}>
        {{ $slot }}
    </x-table.action>
@endif
