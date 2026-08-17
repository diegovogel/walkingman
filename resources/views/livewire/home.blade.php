<?php

use App\Models\Trip;
use App\Services\LifetimeStats;
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
            ->whereNotNull('origin_location_id')
            ->whereNotNull('destination_location_id')
            ->where('departure', '<=', now())
            ->where('arrival', '>=', now())
            ->latest('departure')
            ->first();

        // The trip line labels two zones but shows one date, so read every date
        // in the first of them rather than in the app's own timezone.
        $eastern = 'America/New_York';
        $pacific = 'America/Los_Angeles';

        return [
            'trip' => $trip,
            'lifetimeStats' => LifetimeStats::compile(),
            'milesRemaining' => $trip?->milesRemaining(),
            'timeRemaining' => $trip?->timeRemaining(),
            'departedOn' => $trip?->departedAt()->setTimezone($eastern),
            'arrivesEastern' => $trip?->arrivesAt()->setTimezone($eastern),
            'arrivesPacific' => $trip?->arrivesAt()->setTimezone($pacific),
        ];
    }
}; ?>

<div class="flex w-full flex-1 flex-col items-center text-center">
    <flux:heading size="xl" level="1" class="uppercase tracking-widest">{{ __('Walking Man') }}</flux:heading>

    <flux:text class="mt-3 text-lg">{{ __('Where will he go next?') }}</flux:text>

    <img src="{{ asset('images/walking-man.png') }}" alt="{{ __('The walking man sculpture') }}" class="mt-8 h-36 w-auto" />

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
                    <flux:text size="sm" variant="subtle">{{ $departedOn->format('n/j/y') }}</flux:text>
                </div>

                <div class="text-end">
                    <flux:text class="font-medium">{{ $trip->destinationLocation->full_address }}</flux:text>
                    <flux:text size="sm" variant="subtle">{{ $arrivesEastern->format('n/j/y') }}</flux:text>
                    <flux:text size="sm" variant="subtle">{{ $arrivesEastern->format('G:i') }} ET / {{ $arrivesPacific->format('G:i') }} PT</flux:text>
                </div>
            </div>
        </div>

        <flux:text class="mt-8">
            {{ __(':miles miles remaining.', ['miles' => number_format($milesRemaining)]) }}<br />
            {{ __('Arriving in :days d, :hours h, :minutes m.', $timeRemaining) }}
        </flux:text>
    @endif

    @if ($lifetimeStats)
        <flux:heading size="lg" level="2" class="mt-10 uppercase tracking-wide">{{ __('Lifetime stats') }}</flux:heading>

        <flux:text class="mt-4">
            {{ trans_choice(
                '{1}:total trip completed.|[2,*]:total trips completed.',
                $lifetimeStats->tripsCompleted,
                ['total' => number_format($lifetimeStats->tripsCompleted)],
            ) }}<br />
            {{ __(':miles miles walked.', ['miles' => number_format($lifetimeStats->milesWalked)]) }}<br />
            {{ trans_choice(
                '{0}:days walking.|{1}:count year, :days walking.|[2,*]:count years, :days walking.',
                $lifetimeStats->yearsWalking,
                ['days' => trans_choice('{1}:count day|[2,*]:count days', $lifetimeStats->daysWalking)],
            ) }}<br />
            {{ trans_choice(
                '{1}:total city in :states visited.|[2,*]:total cities in :states visited.',
                $lifetimeStats->citiesVisited,
                [
                    'total' => number_format($lifetimeStats->citiesVisited),
                    'states' => trans_choice('{1}:count state|[2,*]:count states', $lifetimeStats->statesVisited),
                ],
            ) }}
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
            <flux:link href="https://github.com/diegovogel/walkingman" external>{{ __('View on GitHub') }}</flux:link>
        </flux:text>
    </div>

    <flux:text size="sm" variant="subtle" class="mt-10">
        {{ __('*I would later learn the sculpture is one version of') }}
        "<flux:link href="https://www.julianopie.com/sculpture/2007/jack-walking" external>{{ __('Jack Walking') }}</flux:link>"
        {{ __('by Julian Opie. But to me he will always be the walking man.') }}
    </flux:text>
</div>
