<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TripPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool {}

    public function view(User $user, Trip $trip): bool {}

    public function create(User $user): bool {}

    public function update(User $user, Trip $trip): bool {}

    public function delete(User $user, Trip $trip): bool {}

    public function restore(User $user, Trip $trip): bool {}

    public function forceDelete(User $user, Trip $trip): bool {}
}
