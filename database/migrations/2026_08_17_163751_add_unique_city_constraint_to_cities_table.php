<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A municipality is identified by (name, state); the destination picker
     * relies on that identity when it compares and creates city rows. Existing
     * duplicates (from double-seeding) are folded into their lowest-id row
     * before the constraint lands.
     */
    public function up(): void
    {
        $duplicates = DB::table('cities')
            ->select('name', 'state_abbreviation', DB::raw('MIN(id) as keep_id'))
            ->groupBy('name', 'state_abbreviation')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $redundantIds = DB::table('cities')
                ->where('name', $duplicate->name)
                ->where('state_abbreviation', $duplicate->state_abbreviation)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');

            DB::table('locations')->whereIn('city_id', $redundantIds)->update(['city_id' => $duplicate->keep_id]);
            DB::table('cities')->whereIn('id', $redundantIds)->delete();
        }

        Schema::table('cities', function (Blueprint $table) {
            $table->unique(['name', 'state_abbreviation']);
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropUnique(['name', 'state_abbreviation']);
        });
    }
};
