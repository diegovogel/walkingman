<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('origin_location_id')->nullable()->after('id')->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->after('origin_location_id')->constrained('locations')->nullOnDelete();
        });

        $this->backfillLocationsFromCities();

        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_city_id');
            $table->dropConstrainedForeignId('destination_city_id');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('origin_city_id')->nullable()->after('id')->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->after('origin_city_id')->constrained('cities')->nullOnDelete();
        });

        DB::table('trips')
            ->join('locations as origin', 'origin.id', '=', 'trips.origin_location_id')
            ->update(['trips.origin_city_id' => DB::raw('origin.city_id')]);

        DB::table('trips')
            ->join('locations as destination', 'destination.id', '=', 'trips.destination_location_id')
            ->update(['trips.destination_city_id' => DB::raw('destination.city_id')]);

        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_location_id');
            $table->dropConstrainedForeignId('destination_location_id');
        });
    }

    /**
     * Existing trips only ever recorded which city the walking man left from and
     * headed to, so give each referenced city one location carrying its
     * coordinates and point the trips at it. The street address stays null
     * until it is geocoded; inventing one would be worse than leaving it empty.
     *
     * Uses the query builder rather than Eloquent so this keeps working if the
     * models move on.
     */
    private function backfillLocationsFromCities(): void
    {
        $cityIds = DB::table('trips')->pluck('origin_city_id')
            ->merge(DB::table('trips')->pluck('destination_city_id'))
            ->filter()
            ->unique();

        foreach (DB::table('cities')->whereIn('id', $cityIds)->get() as $city) {
            $locationId = DB::table('locations')->insertGetId([
                'city_id' => $city->id,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('trips')->where('origin_city_id', $city->id)->update(['origin_location_id' => $locationId]);
            DB::table('trips')->where('destination_city_id', $city->id)->update(['destination_location_id' => $locationId]);
        }
    }
};
