@extends('layouts.login')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#registerform").validate();
    });
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
                    <h4 class="form-heading">{{__('message.Sign Up')}}</h4>
                    <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                    {{ Form::open(array('method' => 'post', 'id' => 'registerform', 'class' => ' border-form')) }} 

                    <div class="form-group">
                        <label>{{__('message.Enter your mobile number')}}</label>
                        <div class="prefx_dv">
                            <span class="prefix">+964</span>
                            {{Form::text('phone', '', ['id'=>'phone','class'=>'form-control required enterkey digits', 'placeholder'=>__('message.Enter your mobile number'), 'autocomplete'=>'OFF', 'minlength' => 10, 'maxlength' => 10])}}
                        </div>
                    </div>


                    <button class="btn-grad grad-two" type="submit">
                        {{__('message.Proceed')}}
                    </button>
                    {{ Form::close()}}
                    <div class="redirect-box1">
                        {{__('message.Already have an account?')}} <a href="{{URL::to('login')}}">{{__('message.Login')}}</a>
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