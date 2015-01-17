<?php
/**
 * Configuration Facebook Connect.
 *
 * @author     Gponster <anhvudg@gmail.com>
 * @copyright  Copyright (c) 2014
 */
return array(
	/*
    |--------------------------------------------------------------------------
	| Facebook app config
	|--------------------------------------------------------------------------
	|
	| Login and request things from Facebook.
	|
	*/
	'routes' => [
		'login' => 'login/facebook', 'link' => 'login/facebook'
	],

	/*
	|--------------------------------------------------------------------------
	| In order to integrate the Facebook SDK into your site,
	| you'll need to create an app on Facebook and enter the
	| app's ID and secret here.
	|--------------------------------------------------------------------------
	| Add an app: https://developers.facebook.com/apps
	*/
	'app_id' => '782020xxxxxxxxx', 'app_secret' => '3c5f6e0xxxxxxxxxxxxxxxxxxxxxxxxx',

	'redirect_url' => 'login/facebook',

	/*
	|--------------------------------------------------------------------------
	| The default list of permissions that are
	| requested when authenticating a new user with your app.
	| The fewer, the better! Leaving this empty is the best.
	| You can overwrite this when creating the login link.
	|--------------------------------------------------------------------------
	| Example:
	|
	| 'scope' => ['email', 'user_birthday'],
	|
	| For a full list of permissions see:
	|
	| https://developers.facebook.com/docs/facebook-login/permissions
	*/
	'scope' => [
		'email'
	],

	/*
	|--------------------------------------------------------------------------
	| For a full list of locales supported by Facebook visit:
	|--------------------------------------------------------------------------
	|
	| https://www.facebook.com/translations/FacebookLocales.xml
	*/
	'locale' => 'en_US',

	'layout' => 'layouts.main',

	'auth' => [

		/*
		|--------------------------------------------------------------------------
		| Authentication Model
		|--------------------------------------------------------------------------
		|
		| When using the "Eloquent" authentication driver, we need to know which
		| Eloquent model should be used to retrieve your users. Of course, it
		| is often just the "User" model but you may use whatever you like.
		|
		*/

		'model' => 'User',

		'validator' => '\UserValidator',

		'hasher' => 'hash',

		/*
		|--------------------------------------------------------------------------
		| Email and confirmation field
		|--------------------------------------------------------------------------
		| To verify if user account exists and whether confirmed or not
		|
		*/
		'username' => 'username', 'email' => 'email', 'password' => 'password',
		'is_confirmed' => 'is_confirmed', 'status' => 'status', 'first_name' => 'first_name', 'last_name' => 'last_name'
	],

	'profile' => [
		'table' => 'social_profiles'
	]
);
