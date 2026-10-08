<x-app-layout>
    @isset($header)
        <x-slot name="header">
            @yield('header')
        </x-slot>
    @endisset

    @yield('content')
</x-app-layout>
