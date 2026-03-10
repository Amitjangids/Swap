@extends('layouts.login')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#loginform").validate();
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
                    <h4 class="form-heading">{{__('message.Forgot Password')}}</h4>
                    <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                    {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin')) }}
                    <div class="form-group">
                        <label>{{__('message.Enter your mobile number')}}</label>
                        <div class="prefx_dv">
                            <span class="prefix">+964</span>
                            {{Form::text('phone', Cookie::get('user_phone'), ['id'=>'phone','class'=>'form-control required enterkey digits', 'placeholder'=>__('message.Enter your mobile number'), 'autocomplete'=>'OFF'])}}
                        </div>
                    </div>
                    <button type="submit" class="btn-grad grad-two">{{__('message.Submit')}}</button>
                    {{ Form::close()}}
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection