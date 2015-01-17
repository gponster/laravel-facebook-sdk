<?php

/**
 * @author     Gponster <anhvudg@gmail.com>
 * @copyright  Copyright (c) 2014
 */
namespace Gponster\Social;

use Config;
use Session;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Gponster\Social\FacebookRedirectLoginHelper;
use Carbon\Carbon;

// Extend Facebook SDK
class Facebook {
	/**
	 *
	 * @var FacebookSession
	 */
	private $session = null;

	public function __construct() {
		$this->startSession();

		FacebookSession::setDefaultApplication(Config::get('gponster/laravel-facebook-sdk::app_id'),
				Config::get('gponster/laravel-facebook-sdk::app_secret'));
	}

	/**
	 * Start the PHP session
	 */
	private function startSession() {
		// Because Laravel not use PHP session so leave it blank here
	}

	/**
	 * Get the redirect login helper.
	 *
	 * @return FacebookRedirectLoginHelper
	 */
	public function getRedirectLoginHelper($redirectUrl = '') {

		// Try to get config redirect URL
		if(empty($redirectUrl)) {
			$redirectUrl = $this->getDefaultRedirectUrl();
		}

		return new FacebookRedirectLoginHelper($redirectUrl);
	}

	public function getDefaultRedirectUrl() {
		$redirectUrl = \Config::get('gponster/laravel-facebook-sdk::redirect_url');

		if(strpos('http://', $redirectUrl) === 0 || strpos('https://', $redirectUrl) === 0) {
			return $redirectUrl;
		}

		return \URL::to($redirectUrl);
	}

	/**
	 * Get a login URL for redirect.
	 *
	 * @param array $scope
	 * @param string $callback_url
	 * @return string
	 */
	public function getLoginUrl($redirectUrl = '', array $scope = []) {
		if(empty($scope)) {
			$scope = \Config::get('gponster/laravel-facebook-sdk::scope');
		}

		return $this->getRedirectLoginHelper($redirectUrl)
			->getLoginUrl($scope);
	}

	/**
	 * Get the FacebookSession through an access_token.
	 *
	 * @param string $accessToken
	 * @return FacebookSession
	 */
	public function getFacebookSession($accessToken) {
		$session = new FacebookSession($accessToken);

		// Validate the access_token to make sure it's still valid
		try {
			if(! $session->validate()) {
				$session = null;
			}
		} catch(\Exception $e) {
			// Catch any exceptions
			$session = null;
		}

		return $session;
	}

	/**
	 * Get an access token from a redirect.
	 *
	 * @param string $redirectUrl
	 */
	public function getSessionFromRedirect($redirectUrl = '') {
		return $this->getRedirectLoginHelper($redirectUrl)
			->getSessionFromRedirect();
	}

	/**
	 * Determines whether or not this is a long-lived token.
	 *
	 * @return bool
	 */
	public function isLongLivedToken($expiresAt) {
		if(! $expiresAt) {
			return false;
		}

		return (new Carbon)->timestamp($expiresAt)->diffInHours(null, false) < - 2;
	}

	/**
	 * Make a request into Facebook API.
	 *
	 * @param FacebookSession $fbs
	 * @param string $method
	 * @param string $call
	 * @return FacebookRequest
	 */
	public function api(FacebookSession $fbs, $method, $call) {
		$response = (new FacebookRequest($fbs, $method, $call))->execute();
		return $graphObject = $response->getGraphObject();
	}
}
