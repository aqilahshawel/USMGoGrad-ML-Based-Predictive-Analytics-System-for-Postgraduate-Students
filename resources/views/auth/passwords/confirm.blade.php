@extends('layouts.app')

@section('content')
<div class="ui-page container">
    <div class="ui-header">
        <div class="ui-header-title">
            <div class="ui-header-icon"><i class="fas fa-lock"></i></div>
            <div>
                <div class="ui-header-text">Confirm Password</div>
                <div class="ui-header-sub">Re-enter your password to continue.</div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="ui-card">
                <div class="ui-card-head">
                    <h3 class="ui-card-title">Confirm Password</h3>
                </div>

                <div class="ui-card-body">
                    {{ __('Please confirm your password before continuing.') }}

                    <form method="POST" action="{{ route('password.confirm') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control ui-input @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn ui-btn ui-btn-primary">
                                    {{ __('Confirm Password') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn ui-btn ui-btn-outline" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
