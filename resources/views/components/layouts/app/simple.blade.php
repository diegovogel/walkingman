<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">

<header class="pt-10 px-3">
    <a class="text-center text-4xl font-extrabold uppercase block">Walking Man</a>

    <h1 class="text-2xl text-center py-6">{{$pageTitle ?? ($title ?? '')}}</h1>
</header>

<main class="px-4">{{$slot}}</main>

@fluxScripts
</body>
</html>
