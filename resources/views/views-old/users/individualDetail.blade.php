@extends('layouts.login')
@section('content')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("alphanumeric", function (value, element) {
            return this.optional(element) || /^[\w.]+$/i.test(value);
        }, "Only letters, numbers and underscore allowed.");
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "Password must be a combination of Numbers, Uppercase & Lowercase Letters.");
        $("#registerform").validate();

$("#city").change(function () {
            var cityid = $("#city").val();
            $("#area").load('<?php echo HTTP_PATH . '/users/getarealist/' ?>' + cityid);


        });

    });

    function hideerrorsucc() {
        $('.close.close-sm').click();
    }


</script>
<script>
    $(function () {
        $("#dob").datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: 'today',
            changeMonth: true,
            changeYear: true,
            yearRange: "-70:+0"
        });
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
                        <label>{{__('message.Date Of Birth')}}</label>
                        {{Form::text('dob', null, ['class'=>'form-control required', 'placeholder'=>__('message.Date Of Birth'), 'autocomplete' => 'off','id'=>'dob','readonly'])}}
                    </div>
                    <div class="form-group">
                        <label>{{__('message.Select City')}}</label>
                        {{Form::select('city', $cityList,null, ['id'=>'city','class' => 'form-control required','placeholder' => __('message.Select City')])}}
                    </div>
                    <div class="form-group" id="area">
                        <label>{{__('message.Select Area')}}</label>
                        {{Form::select('area', $areaList,null, ['class' => 'form-control required','placeholder' => __('message.Select Area')])}}
                    </div>
                    <button type="submit" class="btn-grad grad-two">{{__('message.Proceed')}}</button>
                    {{ Form::close()}}
                    <p class="text-center step-text">{{__('message.Step 2/3')}}</p>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection