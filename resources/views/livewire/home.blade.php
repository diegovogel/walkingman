<?php

use App\Models\Trip;
use App\Services\LifetimeStats;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.public')]
#[Title('Walking Man')]
class extends Component {
    /**
     * Held onto so that polling can watch this particular trip finish: once he
     * arrives it stops being underway, and re-running that scope would drop the
     * trip off the page rather than announce it. A trip that departs later needs
     * the reload the arrival message asks for.
     */
    #[Locked]
    public ?int $tripId = null;

    public function mount(): void
    {
        $this->tripId = Trip::underway()->latest('departure')->value('id');
    }

    public function with(): array
    {
        $trip = $this->tripId
            ? Trip::withEndpoints()
                ->with(['originLocation.city', 'destinationLocation.city'])
                ->find($this->tripId)
            : null;

        // The trip line labels two zones but shows one date, so read every date
        // in the first of them rather than in the app's own timezone.
        $eastern = 'America/New_York';
        $pacific = 'America/Los_Angeles';

        return [
            'trip' => $trip,
            'lifetimeStats' => LifetimeStats::compile(),
            // Where along the track he stands. With no trip to walk there is no
            // track either, and the midpoint leaves him centered on the page.
            'progressPercent' => $trip ? round($trip->progress() * 100, 2) : 50,
            'hasArrived' => $trip?->hasArrived() ?? false,
            'milesRemaining' => $trip?->milesRemaining(),
            'timeRemaining' => $trip?->timeRemaining(),
            'departedOn' => $trip?->departedAt()->setTimezone($eastern),
            'arrivesEastern' => $trip?->arrivesAt()->setTimezone($eastern),
            'arrivesPacific' => $trip?->arrivesAt()->setTimezone($pacific),
        ];
    }
}; ?>

{{-- Polled so he keeps walking on a page left open. Once he arrives nothing
     more can change without a reload, so the polling stops with him. --}}
<div @if ($trip && ! $hasArrived) wire:poll.60s @endif class="flex w-full flex-1 flex-col items-center text-center">
    <flux:heading size="xl" level="1" class="uppercase tracking-widest">{{ __('Walking Man') }}</flux:heading>

    <flux:text class="mt-3 text-lg">{{ __('Where will he go next?') }}</flux:text>

    <div class="mt-8 w-full [--jack-width:5.25rem]">
        {{-- The track spans the two endpoint circles' centers, inset by half of
             Jack, so that at either end of a trip his outer edge lines up with
             the address beneath him. His spot on it is then a plain percentage. --}}
        <div class="mx-[calc(var(--jack-width)/2)]">
            <div @class(['flex w-(--jack-width) -translate-x-1/2 flex-col items-center', '-mb-1.5' => $trip])
                style="margin-inline-start: {{ $progressPercent }}%">
                <img src="{{ asset('images/walking-man.png') }}" alt="{{ __('The walking man sculpture') }}" class="w-full" />

                @if ($trip)
                    {{-- Meets the line 16px down: 10px in the clear, then 6px
                         behind an endpoint circle when he is standing on one. --}}
                    <span class="h-4 w-px bg-zinc-300 dark:bg-zinc-600"></span>
                @endif
            </div>

            @if ($trip)
                <div class="flex items-center gap-2">
                    <span class="-ms-1.5 size-3 shrink-0 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                    <span class="flex-1 border-t border-dashed border-zinc-300 dark:border-zinc-600"></span>
                    <span class="-me-1.5 size-3 shrink-0 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                </div>
            @endif
        </div>

        @if ($trip)
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
        @endif
    </div>

    @if ($hasArrived)
        <flux:callout variant="success" class="mt-8">
            <flux:callout.text>{{ __('Jack has arrived! Reload the page to see his next trip.') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($trip)
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
