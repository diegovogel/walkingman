<form wire:submit.prevent="createPlayer"
      class="flex flex-col gap-2">
    <flux:input wire:model="playerName"
                label="Player Name"
                description="Letters, numbers, hyphens (-), and underscores (_) only."/>

    <flux:button type="submit"
                 variant="primary"
                 class="w-full">Create
    </flux:button>
</form>
