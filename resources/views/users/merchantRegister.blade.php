@extends('layouts.login')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#registerform").validate();
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "<?php echo __('message.Password must be at least 8 characters long, contains an upper case letter, a lower case letter, a number and a symbol.');?>");
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
                    <?php /* {{ Form::model($userInfo, array('method' => 'post', 'id' => 'registerform', 'class' => ' border-form')) }} */ ?>
                    {{ Form::open(array('method' => 'post', 'id' => 'registerform', 'class' => ' border-form')) }} 
                    <div class="form-group">
                        <label>{{__('message.Business Owner Name')}}</label>
                        {{Form::text('name', null, ['class'=>'form-control required', 'placeholder'=>__('message.Business Owner Name'), 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group">
                        <label>{{__('message.Business Name')}}</label>
                        {{Form::text('business_name', null, ['class'=>'form-control required', 'placeholder'=>__('message.Business Name'), 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group">
                        <label>{{__('message.Business Email Address')}}</label>
                        {{Form::text('email', null, ['class'=>'form-control email required', 'placeholder'=>__('message.Business Email Address'), 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group">
                        <label>{{__('message.Enter New Password')}}</label>
                        {{Form::password('password', ['class'=>'form-control required passworreq', 'placeholder' => __('message.Enter your password'), 'minlength' => 8, 'id'=>'password'])}}
                    </div>
                    <div class="form-group">
                        <label>{{__('message.Re-Enter Password')}}</label>
                        {{Form::password('confirm_password', ['class'=>'form-control required', 'placeholder' => __('message.Re-enter your Password'), 'equalTo' => '#password'])}}
                    </div>

                    <button class="btn-grad grad-two" type="submit">
                        {{__('message.Proceed')}}
                    </button>
                    {{ Form::close()}}
                    <p class="text-center step-text">{{__('message.Step 1/3')}}</p>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection