@extends('layouts.login')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#loginform").validate();
        $(".enterkey").keyup(function (e) {
            if (e.which == 13) {
                postform();
            }
        });
        $("#user_password").keyup(function (e) {
            if (e.which == 13) {
                postform();
            }
        });
    });

    function postform() {
        if ($("#loginform").valid()) {
            $('#btnloader').show();
            $("#loginform").submit();
        }
    }
</script>

<div class="form-box-pre-register">
    <div class="bg-img-phone">{{HTML::image('public/img/front/phone-bg.png', SITE_TITLE)}}</div>
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <div class="pre-register-left-head">
                    <h1><span>{{__('message.Your New Banking')}}</span>
                        {{__('message.Experience')}} </h1>
                    <div class="join-app">
                        <p>{{__('message.Join us on mobile app')}}</p>
                        <a href="javascript:void(0);">{{HTML::image('public/img/front/apple-store.svg', SITE_TITLE)}}</a>
                        <a href="javascript:void(0);">{{HTML::image('public/img/front/g-play-store.svg', SITE_TITLE)}}</a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="pre-register-form">
                    <h4 class="form-heading">{{__('message.Login')}}</h4>
                    <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                    {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin','url' => 'login')) }}
                    <div class="form-group">
                        <label>{{__('message.Enter your mobile number')}}</label>
                        <div class="prefx_dv">
                            <span class="prefix">+964</span>
                            {{Form::text('phone', Cookie::get('user_phone'), ['id'=>'phone','class'=>'form-control required enterkey digits', 'placeholder'=>__('message.Enter your mobile number'), 'autocomplete'=>'OFF'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{__('message.Enter your password')}}</label>
                        {{Form::input('password', 'password', Cookie::get('user_password'), array('class' => "form-control required", 'placeholder' => __('message.Enter your password'), 'id'=>'password','minlength'=>8))}}
                    </div>

                    <button class="btn-grad grad-two" type="submit">
                        {{__('message.Login')}}
                    </button>
                    {{ Form::close()}}
                    <div class="redirect-box">
                        <a href="{{URL::to('choose-account')}}">{{__('message.Sign Up Now')}}</a>
                        <a href="{{ URL::to( 'forgot-password')}}">{{__('message.Forgot Password?')}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--{{ HTML::style('public/assets/css/intlTelInput.css?ver=1.3')}}
{{ HTML::script('public/assets/js/intlTelInput.js')}}
    <script>

        var phone_number = window.intlTelInput(document.querySelector("#phone"), {
            separateDialCode: true,
             preferredCountries:false,
  onlyCountries: ['iq'],
            hiddenInput: "phone",
            utilsScript: "<?php echo HTTP_PATH; ?>/public/assets/js/utils.js"
        });
    </script>-->

@endsection