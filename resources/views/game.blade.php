<x-layouts.app.simple :title="$game->title . ' Game'"
                      :page-title="$game->title">

    <div class="game__long-description mb-8">{!! $game->long_description !!}</div>

    <hr>

    @if($okToPlay)
        <livewire:dynamic-component :is="$gameComponentName"
                                    :key="$gameComponentName"/>
        {{--        <x-dynamic-component :component="$gameComponentName"/>--}}
    @else
        @auth
            <p class="mb-4">Please <a href="{{route('view-player-form')}}">create a player</a> to start playing.</p>
        @else
            <p class="mb-4">Please
                <a href="{{route('login')}}">log in</a>
                to play.
            </p>

            <p class="mb-4">
                You can also <a href="{{route('view-player-form')}}">create a player</a>
                without an account... but you might lose access to your very precious scores.
            </p>
        @endauth
    @endif

</x-layouts.app.simple>
