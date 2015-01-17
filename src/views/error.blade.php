<h2 class="welcome">@lang('gponster/laravel-facebook-sdk::pages.error.desc')</h2>
@if(!empty($error))
	<p class="col-md-12 error">{{ $error }}</p>
@endif
@if(Session::has('message'))
<p class="col-md-12">{{ Session::get('message') }}</p>
@endif
