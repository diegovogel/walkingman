<?php

use App\Models\Trip;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.public')]
#[Title('Walking Man')]
class extends Component {
    public function with(): array
    {
        $trip = Trip::query()
            ->with(['originLocation.city', 'destinationLocation.city'])
            ->where('departure', '<=', now())
            ->latest('departure')
            ->first();

        return [
            'trip' => $trip,
            'milesRemaining' => $trip?->milesRemaining(),
            'timeRemaining' => $trip?->timeRemaining(),
        ];
    }
}; ?>

<div class="flex w-full flex-1 flex-col items-center text-center">
    <flux:heading size="xl" level="1" class="uppercase tracking-widest">{{ __('Walking Man') }}</flux:heading>

    <flux:text class="mt-3 text-lg">{{ __('Where will he go next?') }}</flux:text>

    {{-- The sculpture photo is dropped in separately, so don't ask for it until it's there. --}}
    @if (file_exists(public_path('images/walking-man.png')))
        <img src="{{ asset('images/walking-man.png') }}" alt="{{ __('The walking man sculpture') }}" class="mt-8 h-36 w-auto" />
    @endif

    @if ($trip)
        <div class="mt-8 w-full">
            <div class="flex items-center gap-2">
                <span class="size-3 shrink-0 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                <span class="flex-1 border-t border-dashed border-zinc-300 dark:border-zinc-600"></span>
                <span class="size-3 shrink-0 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
            </div>

            <div class="mt-3 flex items-start justify-between gap-6">
                <div class="text-start">
                    <flux:text class="font-medium">{{ $trip->originLocation->full_address }}</flux:text>
                    <flux:text size="sm" variant="subtle">{{ $trip->departedAt()->format('n/j/y') }}</flux:text>
                </div>

                <div class="text-end">
                    <flux:text class="font-medium">{{ $trip->destinationLocation->full_address }}</flux:text>
                    <flux:text size="sm" variant="subtle">{{ $trip->arrivesAt()->format('n/j/y') }}</flux:text>
                    <flux:text size="sm" variant="subtle">
                        {{ $trip->arrivesAt()->setTimezone('America/New_York')->format('G:i') }} ET
                        / {{ $trip->arrivesAt()->setTimezone('America/Los_Angeles')->format('G:i') }} PT
                    </flux:text>
                </div>
            </div>
        </div>

        <flux:text class="mt-8">
            {{ __(':miles miles remaining.', ['miles' => number_format($milesRemaining)]) }}<br />
            {{ __('Arriving in :days d, :hours h, :minutes m.', $timeRemaining) }}
        </flux:text>
    @endif

    <flux:separator class="my-10" />

    <flux:heading size="lg" level="2" class="uppercase tracking-wide">{{ __('What is this?') }}</flux:heading>

    <div class="mt-4 space-y-4">
        <flux:text>
            {{ __('A pastime by') }}
            <flux:link href="https://www.diego.works" external>Diego Vogel</flux:link>.
        </flux:text>

        <flux:text>
            {{ __('For years I would drive by the walking man in Lexington, KY and wonder why he was there and where he was going. Now I know.*') }}
        </flux:text>

        <flux:text>
            {{ __("Why should you play for a chance to decide where he's going next? Why wouldn't you? Pointless games are the spice of life.") }}
        </flux:text>

        <flux:text>
            <flux:link href="https://github.com/diegovogel/walkingman" external>{{ __('View on GitHub') }}</flux:link>
        </flux:text>
    </div>

    <flux:text size="sm" variant="subtle" class="mt-10">
        {{ __('*I would later learn the sculpture is one version of') }}
        "<flux:link href="https://www.julianopie.com/sculpture/2007/jack-walking" external>{{ __('Jack Walking') }}</flux:link>"
        {{ __('by Julian Opie. But to me he will always be the walking man.') }}
    </flux:text>
</div>
