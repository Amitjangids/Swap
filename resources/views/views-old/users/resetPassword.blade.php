@extends('layouts.login')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#loginform").validate();
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "Password must be at least 8 characters long, contains an upper case letter, a lower case letter, a number and a symbol.");
    });
</script>
<div class="form-box-pre-register">
    <div class="bg-img-phone">{{HTML::image('public/img/front/phone-bg.png', SITE_TITLE)}}</div>
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <div class="pre-register-left-head">
                    <h1><span>Your New Banking</span>
                        Experience </h1>
                    <div class="join-app">
                        <p>Join us on mobile app</p>
                        <a href="javascript:void(0);">{{HTML::image('public/img/front/apple-store.svg', SITE_TITLE)}}</a>
                        <a href="javascript:void(0);">{{HTML::image('public/img/front/g-play-store.svg', SITE_TITLE)}}</a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="pre-register-form">
                    <h4 class="form-heading">Reset Password</h4>
                    <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                    {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin')) }}
                    <div class="form-group">
                        <label>Password</label>
                        {{Form::password('password', ['class'=>'form-control required passworreq', 'placeholder' => 'New Password', 'minlength' => 8, 'id'=>'password'])}}
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        {{Form::password('confirm_password', ['class'=>'form-control required', 'placeholder' => 'Confirm Password', 'equalTo' => '#password'])}}
                    </div>
                    <button type="submit" class="btn-grad grad-two">Submit</button>
                    {{ Form::close()}}
                    <div class="redirect-box1">
                        Oops, I just remembered it! Take me back to the <a href="{{ URL::to( 'login')}}"> Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection