<x-layouts.app.simple :title="$game->title . ' Game'"
                      :page-title="$game->title">

    <div class="game__long-description mb-8">{!! $game->long_description !!}</div>

    @if($okToPlay)
        <x-dynamic-component :component="$gameComponentName"/>
    @else
        <p class="mb-4">Please
            <a href="{{route('login')}}">log in</a>
            to play this game.
        </p>

        <p class="mb-4">
            Don't want to create an account? All you need is a
            <a href="{{route('view-player-form')}}">player name</a>
            .
        </p>
    @endif

</x-layouts.app.simple>
