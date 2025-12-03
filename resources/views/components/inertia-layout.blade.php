<x-base-layout>
  @push("head")
    <title inertia>{{ config('app.name', 'TPL') }}</title>

    @vite(['resources/css/app.css'])
    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    @inertiaHead
  @endpush

  @inertia
</x-base-layout>
