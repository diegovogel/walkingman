<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up() {
		Schema::create( 'games', function ( Blueprint $table ) {
			$table->id();
            $table->string('title', 30);
            $table->string('short_description', 200);
            $table->string('max_possible_points')->comment('This is a string because some games have things like "???" for max points.');
            $table->string('path_to_description')->nullable()->comment('Markdown file.');
			$table->timestamps();
		} );
	}

	public function down() {
		Schema::dropIfExists( 'games' );
	}
};
