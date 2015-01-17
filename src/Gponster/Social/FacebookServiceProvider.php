<?php

/**
 * @author     Gponster <anhvudg@gmail.com>
 * @copyright  Copyright (c) 2014
 */
namespace Gponster\Social;

use Illuminate\Support\ServiceProvider;

class FacebookServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		// ---------------------------------------------------------------------
		// Notes
		// ---------------------------------------------------------------------
		// The register method is called immediately when the service provider is registered,
		// while the boot command is only called right before a request is routed.
		// So, if actions in your service provider rely on another service provider
		// already being registered, or you are overriding services bound by another provider,
		// you should use the boot method.

		// ---------------------------------------------------------------------
		// Access package configuration
		// ---------------------------------------------------------------------
		// If using namespace to get config must use syntax Config::get('vendor/package::file.option');
		// If not using namespace the syntax is Config::get('package::file.option');
		// public function package($package, $namespace = null, $path = null)
		$this->package('gponster/laravel-facebook-sdk', 'gponster/laravel-facebook-sdk',
			__DIR__ . '/../..');

		include __DIR__ . '/../../routes.php';

		// Bind the UserValidatorInterface
		$validatorName = \Config::get('gponster/laravel-facebook-sdk::auth.validator');
		if(empty($validatorName)) {
			throw new \RuntimeException('User vadidator class has not been configured.');
		}

		if(! class_exists($validatorName)) {
			throw new \RuntimeException(
				sprintf('User validator class \'%s\' does not exist.', $validatorName));
		}

		$reflClass = new \ReflectionClass($validatorName);
		if(! $reflClass->implementsInterface('Gponster\\Social\\UserValidatorInterface')) {
			throw new \RuntimeException(
				sprintf(
					'User validator class \'%s\' must implements interface Gponster\\Social\\UserValidatorInterface.',
					$validatorName));
		}

		$this->app->bind('Gponster\\Social\\UserValidatorInterface', $validatorName);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		$this->app['gponster/facebook-sdk'] = $this->app->share(
			function ($app) {
				// Create new Facebook SDK with app config and URL
				return new Facebook();
			});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array(
			'gponster/facebook-sdk'
		);
	}
}