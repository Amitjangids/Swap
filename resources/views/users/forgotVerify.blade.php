@extends('layouts.login')
@section('content')
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
        }, "{{__('messagePassword must be a combination of Numbers, Uppercase & Lowercase Letters.')}}");
        $("#registerform").validate();

        $(".opt_input").keyup(function () {
            if (this.value.length == this.maxLength) {
                $(this).next('label').remove();
                $(this).next('.opt_input').focus();
            }
        });
        
        var myTimer, timing = 60;
        $('#timer').html('00:'+timing);
        myTimer = setInterval(function () {
            --timing;
            if(timing < 10 && timing != 0){
                timing = "0" + timing;
            }
            $('#timer').html('00:'+timing);
            if (timing === 0) {
                $('#timer').html('<a href="javascript:void(0);" class="small-text" onclick="resetOTP()">{{__("message.Resend Code")}}</a>');
                clearInterval(myTimer);
            }
        }, 1000);

    });

    function hideerrorsucc() {
        $('.close.close-sm').click();
    }

    function resetOTP() {
        var phone = $('#phone').val();
        $.ajax({
            url: "{!! HTTP_PATH !!}/resentOtp",
            type: "POST",
            data: {'phone': phone, _token: '{{csrf_token()}}'},
            success: function (result) {
                if(result == 1){
                    alert('Resent OTP on registrated number.');
                }
            }
        });
    }


</script>

<div class="form-box-pre-register">
    <div class="bg-img-phone">{{HTML::image('public/img/front/phone-bg.png', SITE_TITLE)}}</div>
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <div class="pre-register-left-head">
                    <h1><span>{{__("message.Your New Banking")}}</span>
                        {{__("message.Experience")}} </h1>
                    <div class="join-app">
                        <p>{{__("message.Join us on mobile app")}}</p>
                        <a href="javascript:void(0);">{{HTML::image('public/img/front/apple-store.svg', SITE_TITLE)}}</a>
                        <a href="javascript:void(0);">{{HTML::image('public/img/front/g-play-store.svg', SITE_TITLE)}}</a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="pre-register-form">
                    <h4 class="form-heading">{{__("message.Verification")}}</h4>
                    <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                    <p>{{__("message.Verify your phone number by entering the 6 digit code sent to your mobile")}}</p>
                    {{ Form::open(array('method' => 'post', 'id' => 'registerform', 'class' => ' border-form')) }} 
                        <div class="form-group veri-input">

                            <input class="opt_input form-control required digits" type="text" name="otp_code" placeholder="0" maxlength="1" autocomplete="off">
                            <input class="opt_input form-control required digits" type="text" name="otp_code1" placeholder="0" maxlength="1" autocomplete="off">
                            <input class="opt_input form-control required digits" type="text" name="otp_code2" placeholder="0" maxlength="1" autocomplete="off">
                            <input class="opt_input form-control required digits" type="text" name="otp_code3" placeholder="0" maxlength="1" autocomplete="off">
                            <input class="opt_input form-control required digits" type="text" name="otp_code4" placeholder="0" maxlength="1" autocomplete="off">
                            <input class="opt_input form-control required digits" type="text" name="otp_code5" placeholder="0" maxlength="1" autocomplete="off">
                        </div>
                        <div class="text-right" id="timer">
                            <a href="javascript:void(0);" class="small-text" onclick="resetOTP()">{{__("message.Resend Code")}}</a>
                        </div>
                        <button type="submit" class="btn-grad grad-two">{{__("message.Proceed")}}</button>
                    {{ Form::close()}}
                </div>
            </div>
        </div>
    </div>
</div>


@endsection