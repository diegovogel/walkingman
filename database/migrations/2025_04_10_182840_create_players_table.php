<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up() {
		Schema::create( 'players', function ( Blueprint $table ) {
			$table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // We don't cascade on delete because players can exist without an associated user.
            $table->string('name', 30)->index();
            $table->unsignedInteger('current_score')->default(0);
			$table->timestamps();
		} );
	}

	public function down() {
		Schema::dropIfExists( 'players' );
	}
};
