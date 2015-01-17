<?php

/**
 * @author     Gponster <anhvudg@gmail.com>
 * @copyright  Copyright (c) 2014
 */
namespace Gponster\Social;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use Gponster\Social\Facades\Facebook;
use Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacebookController extends Controller {

	/**
	 * The layout that should be used for responses.
	 */
	protected $layout;
	protected $validator;

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout() {
		if(! is_null($this->layout)) {
			$this->layout = \View::make($this->layout);
		}
	}

	public function __construct(UserValidatorInterface $validator) {
		$this->validator = $validator;

		$layout = Config::get('gponster/laravel-facebook-sdk::layout');
		if(! empty($layout)) {
			$this->layout = $layout;
		}

		$this->beforeFilter('csrf', [
			'on' => 'post'
		]);
	}

	/**
	 *
	 * @return \Illuminate\Support\Facades\Response
	 */
	public function getLogin() {
		$accessToken = $oldAccessToken = \Session::get('gponster_facebook.access_token'); // as string
		$expiresAt = \Session::get('gponster_facebook.access_token_exp'); // as timestamp

		if(Input::get('cancel')) {
			$this->clearSession();
			return Redirect::to('/');
		}

		$session = null;

		// Check if existing session exists from session or DB
		if($oldAccessToken) {
			// Create new session from saved access token
			$session = Facebook::getFacebookSession($oldAccessToken);
		} else {
			// No session exists
			try {
				$session = Facebook::getSessionFromRedirect();
			} catch(\Facebook\FacebookRequestException $e) {

				// When Facebook returns an error
			} catch(Exception $e) {

				// When validation fails or other local issues
			}
		}

		if(is_null($expiresAt) && $session) {
			$dt = $session->getAccessToken()
				->getExpiresAt();
			if($dt) {
				$expiresAt = $dt->getTimestamp();
			}
		}

		// Auth config
		$auth = Config::get('gponster/laravel-facebook-sdk::auth');

		// Check if a session exists
		if($session) {
			if(is_null($expiresAt) || Facebook::isLongLivedToken($expiresAt) != true) {
				// Change to long-lived session and save token to DB
				$accessToken = $session->getLongLivedSession()
					->getAccessToken();

				// Save the access token
				\Session::put('gponster_facebook',
					[
						'access_token_exp' => $accessToken->getExpiresAt()
							->getTimestamp(), 'access_token' => (string)$accessToken
					]);

				// Cast to string
				$accessToken = (string)$accessToken;
			}

			// Create session using saved token or the new one we generated at login
			$session = Facebook::getFacebookSession($accessToken);

			// Create the logout URL (logout page should destroy the session)
			// $logoutUrl = Facebook::getLogoutUrl($session, 'http://yourdomain.com/logout');

			// Get basic info on the user from Facebook.
			$me = null;
			if($oldAccessToken === $accessToken) {
				$me = \Session::get('gponster_facebook.me'); // if already in session
			}

			if(is_null($me)) {
				try {
					$me = Facebook::api($session, 'GET', '/me');

					// Put FB profile to session
					\Session::put('gponster_facebook.me', $me);
				} catch(\Facebook\FacebookRequestException $e) {
					return $this->error($e->getMessage());
				}
			}

			$email = ($me != null) ? $me->getProperty('email') : null;
			if(empty($email)) {
				// Cannot get email address
				return $this->error('Đăng ký tài khoản thất bại');
			}

			// Check user already have account or not
			$profile = Profile::where('uid', $me->getProperty('id'))->where('provider',
				'facebook')
				->first();

			// Empty profile, show the link form (create user account)
			$user = null;
			if(empty($profile)) {
				$profile = new Profile();

				$profile['uid'] = $me->getProperty('id');
				$profile['provider'] = 'facebook';
				$profile['email'] = $me->getProperty('email');
				$profile['first_name'] = $me->getProperty('first_name');
				$profile['last_name'] = $me->getProperty('last_name');
				$profile['link'] = $me->getProperty('link');
				$profile['locale'] = $me->getProperty('locale');
				$profile['display_name'] = $me->getProperty('name');
				$profile['timezone'] = $me->getProperty('timezone');
				$profile['is_verified'] = $me->getProperty('verified');

				// Find the User with the same email
				$user = $this->createUserModel()
					->where($auth['email'], $email)
					->first();

				// Create new account
				if(! $user) {
					return $this->create(
						[
							'uid' => $profile['uid'], 'email' => $profile['email'],
							'first_name' => $profile['first_name'],
							'last_name' => $profile['last_name']
						]);
				}

				// User have already confirmed email address
				if($user->{$auth['is_confirmed']}) {
					// Auto link account
					$profile['username'] = $user->username;
				} else {
					// Confirm or reset password
					return $this->link(
						[
							'username' => $user->getAttribute('username'),
							'uid' => $profile['uid'], 'email' => $email,
							'first_name' => $profile['first_name'],
							'last_name' => $profile['last_name']
						]);
				}
			} else {
				// Find user by profile
				$user = $this->createUserModel()
					->where($auth['username'], $profile['username'])
					->orWhere($auth['email'], $email)
					->first();

				// Cannot find user (why user has been deleted?)
				// NOTE: we use the new email which Facebook provided
				if(! $user) {
					return $this->create(
						[
							'uid' => $profile['uid'], 'email' => $email,
							'first_name' => $profile['first_name'],
							'last_name' => $profile['last_name']
						]);
				}
			}

			// ---------------------------------------------------------
			// Update user
			// ---------------------------------------------------------
			if(! empty($auth['first_name']) && empty($user->{$auth['first_name']})) {
				$user->{$auth['first_name']} = $profile['first_name'];
			}

			if(! empty($auth['last_name']) && empty($user->{$auth['last_name']})) {
				$user->{$auth['last_name']} = $profile['last_name'];
			}

			$user->save();

			// Update the access token and email
			$profile['email'] = $email;
			$profile['access_token'] = $accessToken;
			$profile->save();

			// Not logged for locked account
			if($user->{$auth['status']} == 0) {
				$this->clearSession();
				return $this->error(
					trans('gponster/laravel-facebook-sdk::pages.account_locked'));
			}

			// Login and clear session
			\Auth::login($user);
			$this->clearSession();

			return Redirect::to('/');
		} else {
			// No session
			$loginUrl = Facebook::getLoginUrl();
			return Redirect::to($loginUrl);
		}
	}

	protected function error($error) {
		$this->layout->title = trans('gponster/laravel-facebook-sdk::pages.error.title');
		$this->layout->content = \View::make('gponster/laravel-facebook-sdk::error')->with(
			'error', $error);
	}

	protected function create($profile) {
		$this->layout->title = trans('gponster/laravel-facebook-sdk::pages.create.title');
		$this->layout->content = \View::make('gponster/laravel-facebook-sdk::create')->with(
			'profile', $profile);
	}

	protected function link($profile) {
		$this->layout->title = trans('gponster/laravel-facebook-sdk::pages.link.title');
		$this->layout->content = \View::make('gponster/laravel-facebook-sdk::link')->with(
			'profile', $profile);
	}

	/**
	 *
	 * @return \Illuminate\Support\Facades\Response
	 */
	public function postLogin() {
		$me = \Session::get('gponster_facebook.me');
		if(! $me) {
			return $this->error('Lỗi khi liên kết tài khoản, vui lòng thử lại sau.');
		}

		// Whether linking or creating a new account
		$usernameToLink = Input::get('link_username');

		// Auth config
		$auth = Config::get('gponster/laravel-facebook-sdk::auth');

		$data = [
			$auth['email'] => $me->getProperty('email'),
			$auth['username'] => Input::get('username'),
			$auth['first_name'] => Input::get('first_name'),
			$auth['last_name'] => Input::get('last_name')
		];

		// Validation rules
		$rules = $this->validator->rules();

		if(! empty($usernameToLink)) {
			// Unset empty first name, last name (not set)
			if(! empty($data[$auth['first_name']])) {
				unset($data[$auth['first_name']]);
			}

			if(! empty($data[$auth['last_name']])) {
				unset($data[$auth['last_name']]);
			}

			unset($data[$auth['email']], $data[$auth['username']]);

			// Not validate the existing username and email
			if(isset($rules[$auth['email']])) {
				unset($rules[$auth['email']]);
			}

			if(isset($rules[$auth['username']])) {
				unset($rules[$auth['username']]);
			}
		}

		$password = Input::get('password');
		$passwordConfirmation = Input::get('password_confirmation');

		// Not set/reset password
		if(empty($password) && empty($passwordConfirmation)) {
			if(isset($rules[$auth['password']])) {
				unset($rules[$auth['password']]);
			}

			if(isset($rules[$auth['password'] . '_confirmation'])) {
				unset($rules[$auth['password'] . '_confirmation']);
			}
		} else {
			$rules = array_merge($rules,
				[
					$auth['password'] => $rules[$auth['password']] . '|confirmed',
					$auth['password'] . '_confirmation' => 'required'
				]);

			$data[$auth['password']] = $password;
			$data[$auth['password'] . '_confirmation'] = $passwordConfirmation;
		}

		$v = \Validator::make($data, $rules, $this->validator->messages());
		if(! $v->passes()) {
			\Log::warning('Create user from Facebook account fail',
				[
					'error' => $v->messages()
				]);

			return Redirect::back()->withErrors($v)
				->withInput(
				Input::except([
					'password', 'password_confirmation'
				]));
		}

		if(! empty($data[$auth['password']])) {
			$hasher = Config::get('gponster/laravel-facebook-sdk::auth.hasher');
			$data[$auth['password']] = \App::make($hasher)->make($data[$auth['password']]);

			unset($data[$auth['password'] . '_confirmation']);
		} else {
			$data[$auth['password']] = ''; // still set empty password here
		}

		$data[$auth['status']] = 1;
		$data[$auth['is_confirmed']] = 1;

		$user = null;
		if(! empty($usernameToLink)) {
			// Linking to an existing account
			$user = $this->createUserModel()
				->where($auth['username'], $usernameToLink)
				->first();

			if(! empty($data)) {
				$user->fill($data);
			}
		} else {
			// Create a new user account
			$user = $this->createUserModel($data);
		}

		// Guarded
		$user->setAttribute($auth['password'], $data[$auth['password']]);

		$saved = $user->save();

		// Have profile but not have user??
		$profile = Profile::where('uid', $me->getProperty('id'))->where('provider',
			'facebook')
			->first();
		if(is_null($profile)) {
			$profile = new Profile();
		}

		$profile['username'] = $user->username;
		$profile['uid'] = $me->getProperty('id');
		$profile['provider'] = 'facebook';
		$profile['email'] = $me->getProperty('email');
		$profile['first_name'] = $me->getProperty('first_name');
		$profile['last_name'] = $me->getProperty('last_name');
		$profile['link'] = $me->getProperty('link');
		$profile['locale'] = $me->getProperty('locale');
		$profile['display_name'] = $me->getProperty('name');
		$profile['timezone'] = $me->getProperty('timezone');
		$profile['is_verified'] = $me->getProperty('verified');

		// Update the access token
		$profile['access_token'] = \Session::get('gponster_facebook.access_token');
		$profile->save();

		// Not logged for locked account
		if($user->{$auth['status']} == 0) {
			$this->clearSession();
			return $this->error(
				trans('gponster/laravel-facebook-sdk::pages.account_locked'));
		}

		// Login and clear session
		\Auth::login($user);
		$this->clearSession();

		return Redirect::to('/');
	}

	/**
	 * Create a new instance of the model.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	protected function createUserModel($attributes = []) {
		$modelName = Config::get('gponster/laravel-facebook-sdk::auth.model');

		$class = '\\' . ltrim($modelName, '\\');

		return new $class($attributes);
	}

	private function clearSession() {
		\Session::forget('gponster_facebook');
	}
}