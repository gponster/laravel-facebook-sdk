<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSocialProfilesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('social_profiles',
			function ($table) {
				$table->increments('id');
				$table->string('provider', 16);
				$table->string('uid', 32);
				$table->string('email', 32);
				$table->string('username', 32);
				$table->string('first_name', 32)
					->nullable();
				$table->string('last_name', 32)
					->nullable();
				$table->string('display_name', 64)
					->nullable();
				$table->tinyInteger('gender')
					->nullable();
				$table->integer('timezone')
					->nullable();
				$table->string('link', 255)
					->nullable();
				$table->string('locale', 16)
					->nullable();
				$table->string('access_token', 255);
				$table->string('access_token_secret', 255)
					->nullable();
				$table->integer('is_verified')
					->nullable();
				$table->timestamps();
			});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('social_profiles');
	}
}