<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up() {
		Schema::create( 'trips', function ( Blueprint $table ) {
			$table->id();
            $table->foreignId('origin_city_id')->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->decimal('distance', 6);
            $table->timestamp('departure');
            $table->timestamp('arrival')->nullable();
            $table->boolean('destination_from_user')->default(false)->comment('True if destination came from a user, whether chosen or selected randomly.');
            $table->boolean('destination_is_random')->default(true)->comment('True if destination was selected randomly, whether by the app or a user.');
            $table->foreignId('user_id')->nullable()->constrained();
			$table->timestamps();
		} );
	}

	public function down() {
		Schema::dropIfExists( 'trips' );
	}
};
