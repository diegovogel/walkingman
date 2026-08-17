<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="mx-auto flex min-h-svh w-full max-w-lg flex-col px-6 py-10">
            {{ $slot }}
        </div>
        @fluxScripts
    </body>
</html>
