<x-layouts.app.simple :title="$game->title . ' Game'"
                      :page-title="$game->title">

    <div class="game__long-description mb-8">{!! $game->long_description !!}</div>

    <x-button centered
              size="large">Play
    </x-button>
</x-layouts.app.simple>
