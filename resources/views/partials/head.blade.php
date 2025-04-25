<meta charset="utf-8"/>
<meta name="viewport"
      content="width=device-width, initial-scale=1.0"/>

<title>{{ $title ?? config('app.name') }}</title>

<link rel="preconnect"
      href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
      rel="stylesheet"/>
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css"
>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
