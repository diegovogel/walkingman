<div x-data="{showPlayerForm: false}">
    @if($player)
        You're a player!
    @else
        <div x-show="!showPlayerForm">
            <livewire:auth.login/>

            <div class="py-2 text-center">Don't want an account?
                <x-button button-style="link"
                          x-on:click="showPlayerForm = true">Just create a player name.
                </x-button>
            </div>
        </div>
    @endif

    <div x-show="showPlayerForm">
        <livewire:create-player/>
    </div>
</div>
