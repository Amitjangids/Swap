@extends('layouts.login')
@section('content')
<section class="same-section login-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-content-wrapper">
                    <div class="login-content-parent otp-page">
                        <h2> <a href="/login"><img src="{{PUBLIC_PATH}}/assets/front/images/back-icon.svg"
                                    alt="image"></a>{{__('message.OTP')}}</h2>
                        <p>{{__('message.One time password has been sent to your registered email address.')}}</p>
                    </div>
                    {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin')) }}
                    <div class="login-from-parent">
                        <div class="login-otp-wrapper">
                            <div class="login-otp-boc">
                                <input class="required digits opt_input d0" type="text" name="otp1" autocomplete="off"
                                    maxlength="1">
                                <input class="required digits opt_input d1" type="text" name="otp2" autocomplete="off"
                                    maxlength="1">
                                <input class="required digits opt_input d2" type="text" name="otp3" autocomplete="off"
                                    maxlength="1">
                                <input class="required digits opt_input d3" type="text" name="otp4" autocomplete="off"
                                    maxlength="1">
                                <input class="required digits opt_input d4" type="text" name="otp5" autocomplete="off"
                                    maxlength="1">
                                <input class="required digits opt_input d5" type="text" name="otp6" autocomplete="off"
                                    maxlength="1">
                            </div>
                            <div class="resend-box">
                                <a class="resendfor" onclick="resetOTP();" href="#">{{__('message.Resend code')}}</a>
                                <span class="timer">0:59</span>
                            </div>
                        </div>

                        <div class="login-btn">
                            <button type="submit" class="btn btn-primaryx">{{__('message.Login')}}</button>
                        </div>

                        <div class="alert alert-success success_message" role="alert" style="display:none"></div>
                        <div class="alert alert-danger error_message" role="alert" style="display:none"></div>

                        <?php if (session()->has('error_message')) { ?>
                            <div class="alert alert-danger" role="alert">
                                {{Session::get('error_message')}}
                            </div>
                            <?php Session::forget('error_message');
                        } ?>

                    </div>
                    {{ Form::close()}}
                </div>
            </div>
            <div class="col-lg-6">
                <div class="login-image">
                    <img src="{{PUBLIC_PATH}}/assets/front/images/login-image.png" alt="image">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- <script>
$(document).ready(function () {
    $('.d0').focus();

    $('input').bind('paste', function (e) {
        var $start = $(this);
        var source;

        // Check for access to clipboard from window or event
        if (window.clipboardData !== undefined) {
            source = window.clipboardData;
        } else {
            source = e.originalEvent.clipboardData;
        }

        var data = source.getData("Text");
        if (data.length > 0) {
            var columns = data.split("");
            for (var i = 0; i < columns.length; i++) {
                $('.d' + i).val(columns[i]);
            }
            e.preventDefault();
        }
    });

    // Function to handle moving between fields on keyup
    $(".opt_input").on('keyup', function (e) { 
        var key = e.keyCode || e.which;
        var $input = $(this);

        if ($input.val().length === $input.attr('maxlength')) {
            $input.next('.opt_input').focus();
        } else if (key === 8 || key === 37) { // If backspace or left arrow key
            $input.prev('.opt_input').focus();
        }
    });

    // Prevent clearing fields on input change
    $(".opt_input").on('input', function () {
        if (this.value.length > this.maxLength) {
            this.value = this.value.slice(0, this.maxLength);
        }
    });

    $('.resendfor').hide();
});
</script>


<script>
$(document).ready(function () {
    $('.d0').focus();
    $('input').bind('paste', function (e) {
    var $start = $(this);
    var source
    //check for access to clipboard from window or event
    if (window.clipboardData !== undefined) {
        source = window.clipboardData
    } else 
    {
    source = e.originalEvent.clipboardData;
    }
    var data = source.getData("Text");
    if (data.length > 0) {
    var columns = data.split("");
    for (var i = 0; i < columns.length; i++) {
    $('.d'+i).focus();
    $('.d'+i).val(columns[i]);
    }
    e.preventDefault();     
    }
  });     

  $(".opt_input").keyup(function () { 
        if (this.value.length == this.maxLength) { 
            $(this).next('label').remove();
            $(this).next('.opt_input').focus();
        }
    });
    $('.resendfor').hide();	
 });
</script>

<script type="text/javascript">
    $(document).ready(function () {
    $("#loginform").validate({
        rules: {
            "phone_number": "required",
        },
        messages: {
            "phone_number": "Enter mobile number",
        },
        submitHandler: function (form) {
            form.submit();
        }
       });
    });

    function resetOTP() {
        var phone = '{{$slug}}';
        $.ajax({
            url: "{!! HTTP_PATH !!}/resendOTP",
            type: "POST",
            data: {'phone': phone, _token: '{{csrf_token()}}'},
            success: function (result) {
             countdown();
             $('.success_message').html('Resent OTP successfully.');   
             $('.success_message').show();
            }
        });
    }

    function countdown() {
        $('.resendfor').hide();	
        $('.timer').show();
        var timer2 = "0:59";
        var interval = setInterval(function() {
        var timer = timer2.split(':');
        //by parsing integer, I avoid all extra string processing
        var minutes = parseInt(timer[0], 10);
        var seconds = parseInt(timer[1], 10);
        --seconds;
        minutes = (seconds < 0) ? --minutes : minutes;
        if (minutes < 0){ 
            clearInterval(interval);
            $('.timer').hide();
            $('.resendfor').show();
        } 
        else{
            seconds = (seconds < 0) ? 59 : seconds;
            seconds = (seconds < 10) ? '0' + seconds : seconds;
            $('.timer').html(minutes + ':' + seconds);
            timer2 = minutes + ':' + seconds;
        }
        }, 1000);
    }
    countdown();
</script> -->


<script>
    $(document).ready(function () {
        $('.d0').focus(); // Focus on the first OTP field by default

        // Paste functionality to autofill OTP fields
        $('input').bind('paste', function (e) {
            var source = e.clipboardData || window.clipboardData;
            var data = source.getData("Text");

            if (data.length > 0) {
                var columns = data.split("");
                for (var i = 0; i < columns.length; i++) {
                    $('.d' + i).val(columns[i]);
                }
                e.preventDefault();
            }
        });

        // Navigate and prevent clearing of OTP fields on keyup
        $(".opt_input").on('input', function () {
            if ($(this).val().length === this.maxLength) {
                // Move to the next input if current input is filled
                $(this).next('.opt_input').focus();
            }
        });

        // Backspace navigation
        $(".opt_input").on('keydown', function (e) {
            var key = e.keyCode || e.which;

            // Move focus to previous input on backspace or left arrow key
            if ((key === 8 || key === 37) && $(this).val() === '') {
                $(this).prev('.opt_input').focus();
            }
        });

        // Countdown timer for resend OTP
        countdown();
        $('.resendfor').hide();
    });

    function resetOTP() {
        var phone = '{{$slug}}';
        $.ajax({
            url: "{!! HTTP_PATH !!}/resendOTP",
            type: "POST",
            data: { 'phone': phone, _token: '{{csrf_token()}}' },
            success: function (result) {
                countdown();
                if (result == 1) {
                    $('.success_message').html("{{__('message.Resent OTP successfully.')}}");
                    $('.success_message').show();
                } else {
                    $('.error_message').html("{{__('message.OTP is not being sent.')}}");
                    $('.error_message').show();
                }
            }
        });
    }

    function countdown() {
        $('.resendfor').hide();
        $('.timer').show();
        var timer2 = "0:59";
        var interval = setInterval(function () {
            var timer = timer2.split(':');
            var minutes = parseInt(timer[0], 10);
            var seconds = parseInt(timer[1], 10);

            --seconds;
            minutes = (seconds < 0) ? --minutes : minutes;

            if (minutes < 0) {
                clearInterval(interval);
                $('.timer').hide();
                $('.resendfor').show();
            } else {
                seconds = (seconds < 0) ? 59 : seconds;
                seconds = (seconds < 10) ? '0' + seconds : seconds;
                $('.timer').html(minutes + ':' + seconds);
                timer2 = minutes + ':' + seconds;
            }
        }, 1000);
    }
    countdown();
</script>




@endsection