<h2 class="welcome">@lang('gponster/laravel-facebook-sdk::pages.link.title')</h2>
<p class="col-md-12">@lang('gponster/laravel-facebook-sdk::pages.link.desc',
		['site_name' => Config::get('settings.site_name')])</p>
<p class="col-md-12"><b>Facebook ({{$profile['email']}})</b></p>
<p class="col-md-12">@lang('gponster/laravel-facebook-sdk::pages.link.basic_info')</p>

{{ Form::open(['url'=>Config::get('gponster/laravel-facebook-sdk::routes.link'),
		'role'=>'form', 'class'=> 'col-md-4', 'method' => 'POST']) }}

	@if($errors)
	<ul class="error">
		@if($errors->first('username'))
		<li>{{ $errors->first('username') }}</li>
		@elseif($errors->first('password'))
		<li>{{ $errors->first('password') }}</li>
		@endif
	</ul>
	@endif
	@if(Session::has('message'))
	<p class="alert">{{ Session::get('message') }}</p></p>
	@endif
	{{ Form::hidden('link_username', $profile['username']) }}
	<div class="form-group @if($errors->has('username')) has-error @endif">
		{{ Form::text('username', $profile['username'],
			['disabled'=> 'disabled', 'class'=>'form-control input-sm',
			'placeholder'=>trans('gponster/laravel-facebook-sdk::pages.username')]) }}
	</div>
	<div class="form-group @if($errors->has('first_name')) has-error @endif">
		{{ Form::text('first_name',
			!empty(Input::old('first_name')) ? Input::old('first_name') : $profile['first_name'],
			['class'=>'form-control input-sm',
			'placeholder'=>trans('gponster/laravel-facebook-sdk::pages.first_name') .
					' (' . trans('gponster/laravel-facebook-sdk::pages.optional') . ')']) }}
	</div>
	<div class="form-group @if($errors->has('last_name')) has-error @endif">
		{{ Form::text('last_name',
			!empty(Input::old('last_name')) ? Input::old('last_name') : $profile['last_name'],
			['class'=>'form-control input-sm',
			'placeholder'=>trans('gponster/laravel-facebook-sdk::pages.last_name') .
					' (' . trans('gponster/laravel-facebook-sdk::pages.optional') . ')']) }}
	</div>

	<div class="form-group">
		@lang('gponster/laravel-facebook-sdk::pages.link.password_tip',
				['site_name' => Config::get('settings.site_name')])
	</div>
	<div class="form-group @if($errors->has('password')) has-error @endif">
		{{ Form::password('password', ['class'=>'form-control input-sm',
				'placeholder'=>trans('gponster/laravel-facebook-sdk::pages.password') .
						' (' . trans('gponster/laravel-facebook-sdk::pages.optional') . ')']) }}
	</div>
	<div class="form-group @if($errors->has('password_confirmation')) has-error @endif">
		{{ Form::password('password_confirmation', ['class'=>'form-control input-sm',
				'placeholder'=>trans('gponster/laravel-facebook-sdk::pages.password_confirmation')]) }}
	</div>
	<div class="form-group">
		{{ Form::submit(trans('gponster/laravel-facebook-sdk::pages.link.submit'),
			['class'=>'btn btn-primary']) }}&nbsp;
			<a href="{{URL::to(Config::get('gponster/laravel-facebook-sdk::routes.login'))}}?{{http_build_query(['cancel' => $profile['email']])}}">@lang('gponster/laravel-facebook-sdk::pages.link.cancel')</a>
	</div>
{{ Form::close() }}