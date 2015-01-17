<?php

/**
 * @author     Gponster <anhvudg@gmail.com>
 * @copyright  Copyright (c) 2014
 */
namespace Gponster\Social;

class Profile extends \Eloquent {
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Guarded attributes
	 *
	 * @var array
	 */
	protected $guarded = [
		'email', 'uid', 'username'
	];

	public function getDates() {
		return [
			'created_at', 'updated_at'
		];
	}

	/**
	 * Create a new Eloquent model instance.
	 *
	 * @param array $attributes
	 * @return void
	 */
	public function __construct(array $attributes = array()) {
		/**
		 * The database table used by the model.
		 */
		$this->table = \Config::get('gponster/laravel-facebook-sdk::profile.table');
		parent::__construct($attributes);
	}

	public function user() {
		$auth = \Config::get('gponster/laravel-facebook-sdk::auth');
		return $this->belongsTo($auth['model'], 'username', $auth['username']);
	}
}