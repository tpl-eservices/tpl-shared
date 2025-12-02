<x-static-layout>
  <x-slot:head>
    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    @inertiaHead
  </x-slot:head>

  @inertia
</x-static-layout>
