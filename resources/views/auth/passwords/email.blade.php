@extends('layouts.app')

@section('content')
<div class="ui-page container">
    <div class="ui-header">
        <div class="ui-header-title">
            <div class="ui-header-icon"><i class="fas fa-envelope"></i></div>
            <div>
                <div class="ui-header-text">Reset Password</div>
                <div class="ui-header-sub">Send a reset link to your email.</div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="ui-card">
                <div class="ui-card-head">
                    <h3 class="ui-card-title">Reset Password</h3>
                </div>

                <div class="ui-card-body">
                    @if (session('status'))
                        <div class="ui-alert success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control ui-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn ui-btn ui-btn-primary">
                                    {{ __('Send Password Reset Link') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
