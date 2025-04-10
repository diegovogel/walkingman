<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up() {
		Schema::create( 'cities', function ( Blueprint $table ) {
			$table->id();
            $table->string('name', 100);
            $table->string('state_abbreviation', 2);
            $table->string('state_name', 100);
            $table->decimal('latitude', 7, 4);
            $table->decimal('longitude', 7, 4);
			$table->timestamps();
		} );
	}

	public function down() {
		Schema::dropIfExists( 'cities' );
	}
};
