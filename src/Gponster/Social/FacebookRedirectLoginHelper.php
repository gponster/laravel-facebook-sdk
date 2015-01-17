<?php

/**
 * @author     Gponster <anhvudg@gmail.com>
 * @copyright  Copyright (c) 2014
 */
namespace Gponster\Social;

use Config;
use Session;
use Input;

// Extend FacebookRedirectLoginHelper in Facebook SDK
class FacebookRedirectLoginHelper extends \Facebook\FacebookRedirectLoginHelper {

	/**
	 *
	 * @var string Prefix to use for session variables
	 * @see $sessionPrefix in \Facebook\FacebookRedirectLoginHelper
	 */
	private $sessionPrefix = 'FBRLH_';

	protected function storeState($state) {
		Session::put($this->sessionPrefix . 'state', $state);
	}

	protected function loadState() {
		$this->state = Session::get($this->sessionPrefix . 'state');
		return $this->state;
	}

	protected function isValidRedirect() {
		return $this->getCode() && Input::has('state') && Input::get('state') == $this->state;
	}

	protected function getCode() {
		return Input::has('code') ? Input::get('code') : null;
	}

	// Fix for state value from Auth redirect not equal to session stored state value
	// Get FacebookSession via User access token from code
	public function getAccessTokenDetails($appId, $appSecret, $redirectUrl, $code) {
		$tokenUrl = 'https://graph.facebook.com/oauth/access_token?' . 'client_id=' . $appId . '&redirect_uri=' .
				 $redirectUrl . '&client_secret=' . $appSecret . '&code=' . $code;

		$response = file_get_contents($tokenUrl);
		$params = null;
		parse_str($response, $params);

		return $params;
	}
}
