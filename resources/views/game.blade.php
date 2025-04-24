<x-layouts.app.simple :title="$game->title . ' Game'"
                      :page-title="$game->title">

    <div class="game__long-description mb-8">{!! $game->long_description !!}</div>

    <flux:modal.trigger name="play-game">
        <x-button centered
                  size="large">Play
        </x-button>
    </flux:modal.trigger>

    <flux:modal name="play-game">

    </flux:modal>

    <livewire:games.scream/>
</x-layouts.app.simple>
