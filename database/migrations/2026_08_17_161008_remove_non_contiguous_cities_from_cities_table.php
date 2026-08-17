<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The walking man cannot walk to Alaska, Hawaii, or Puerto Rico, so those
     * cities were dropped from the seed data; this clears them from databases
     * seeded before the trim. Their locations cascade away and any trips
     * pointing at those locations keep nulls, which the app already tolerates.
     */
    public function up(): void
    {
        DB::table('cities')->whereIn('state_abbreviation', ['AK', 'HI', 'PR'])->delete();
    }

    /**
     * Deliberately a no-op: the deleted rows are re-creatable only by
     * re-seeding, and rollbacks should not resurrect partial data.
     */
    public function down(): void
    {
        //
    }
};
