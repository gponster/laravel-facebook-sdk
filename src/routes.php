<?php
/**
 * @author     Gponster <anhvudg@gmail.com>
 * @copyright  Copyright (c) 2014
 */
/*
|--------------------------------------------------------------------------
| Route
|--------------------------------------------------------------------------
|
| Route Facebook URL to our controller action.
|
*/
if(! empty(Config::get('gponster/laravel-facebook-sdk::routes'))) {

	// Check if localization support
	if($this->app['laravellocalization']) {
		Route::group(
			[
				'prefix' => $this->app['laravellocalization']->setLocale(),
				'before' => 'LaravelLocalizationRedirectFilter'
			],
			function () {
				Route::get(Config::get('gponster/laravel-facebook-sdk::routes.login'),
					'\Gponster\Social\FacebookController@getLogin');

				Route::post(Config::get('gponster/laravel-facebook-sdk::routes.link'),
					'\Gponster\Social\FacebookController@postLogin');
			});
	} else {
		Route::get(Config::get('gponster/laravel-facebook-sdk::routes.login'),
			'\Gponster\Social\FacebookController@getLogin');

		Route::post(Config::get('gponster/laravel-facebook-sdk::routes.link'),
			'\Gponster\Social\FacebookController@postLogin');
	}
}