<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark'=> ($appearance ?? 'system') == 'dark'])>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name', 'TPL') }}</title>

  <link rel="icon" href="{{ secure_asset('favicon.ico') }}" sizes="any">
  <link rel="icon" href="{{ secure_asset('favicon.svg') }}" type="image/svg+xml">
  <link rel="apple-touch-icon" href="{{ secure_asset('apple-touch-icon.png') }}">
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=Playfair+Display|IBM+Plex+Sans+Condensed:600|Roboto:900|Open+Sans:400,500,700" rel="stylesheet" />

  {{-- BiblioCommons CSS (Link Tags) --}}
  @if (!empty($bibliocommons['css']))
    {!! $bibliocommons['css'] !!}
  @endif

  @if (tplSharedAsset('css'))
      <link rel="stylesheet" href="{{ tplSharedAsset('css') }}">
  @endif

  @stack('head')
</head>

<body class="min-h-full flex flex-col font-sans antialiased">
  <div class="cp-screen-reader-shortcuts">
    <div>
      {{-- BiblioCommons Screen Reader Navigation --}}
      @if (!empty($bibliocommons['screen_reader_navigation']))
        {!! $bibliocommons['screen_reader_navigation'] !!}
      @endif
    </div>
  </div>

  {{-- BiblioCommons Header --}}
  @if (!empty($bibliocommons['header']))
    {!! $bibliocommons['header'] !!}
  @endif

  <main class="grow">
    <section class="{{ $class }}">
      {{ $slot }}
    </section>
  </main>

  {{-- BiblioCommons Footer --}}
  @if (!empty($bibliocommons['footer']))
    {!! $bibliocommons['footer'] !!}
  @endif

  {{-- Handlebars (required by BiblioCommons JS) --}}
  <script src="https://cdn.jsdelivr.net/npm/handlebars@4.7.8/dist/handlebars.min.js" crossorigin="anonymous"></script>

  {{-- jQuery (required by BiblioCommons JS) --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>

  {{-- BiblioCommons JS --}}
  @if (!empty($bibliocommons['js']))
    {!! $bibliocommons['js'] !!}
  @endif

  @stack('scripts')
</body>
</html>
