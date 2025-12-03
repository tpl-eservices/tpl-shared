<x-base-layout>
  @push("head")
    <title>{{ config('app.name', 'TPL') }}</title>

    @vite(['resources/css/app.css'])
  @endpush

  {{ $slot }}
</x-base-layout>
