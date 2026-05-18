@extends('layouts.app')

@section('content')
<div class="ui-page container">
    <div class="ui-header">
        <div class="ui-header-title">
            <div class="ui-header-icon"><i class="fas fa-envelope-open-text"></i></div>
            <div>
                <div class="ui-header-text">Verify Email</div>
                <div class="ui-header-sub">Check your inbox to activate your account.</div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="ui-card">
                <div class="ui-card-head">
                    <h3 class="ui-card-title">Verify Your Email Address</h3>
                </div>

                <div class="ui-card-body">
                    @if (session('resent'))
                        <div class="ui-alert success" role="alert">
                            {{ __('A fresh verification link has been sent to your email address.') }}
                        </div>
                    @endif

                    {{ __('Before proceeding, please check your email for a verification link.') }}
                    {{ __('If you did not receive the email') }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn ui-btn ui-btn-outline p-0 m-0 align-baseline">{{ __('click here to request another') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
