{{ HTML::style('public/assets/front/css/bootstrap.min.css')}}
{{ HTML::style('public/assets/front/css/custom.css')}}
{{ HTML::style('public/assets/front/css/media.css')}}
{{ HTML::style('public/assets/front/css/owl.carousel.min.css')}}
<style type="text/css">
    section.banner-section.password-section {
        height: 100%;
        background: transparent;
        padding: 50px 0;
        overflow-y: auto;
    }

    body {
        margin: 0;
        padding: 0;
        background: #eee;
    }

    section.same-section.login-page .row {
        justify-content: center;
    }

    .login-content-wrapper {
        padding: 30px 30px;
        border-radius: 22px;
    }

    .login-content-parent h2 {
        font-size: 30px;
        margin: 0 0 0;
    }

    .login-content-parent {
        margin: 0 0 20px;
    }

    .login-content-parent a {
        max-width: 130px;
        margin: 0 0 30px;
    }

    .login-from-parent label {
        font-size: 18px;
    }

    .login-from-parent .login-contact .input-box-parent input {
        padding: 12px;
        font-size: 16px;
        border-radius: 7px;
    }

    .login-btn {
        margin: 30px 0 0;
    }

    .login-btn .btn-primaryx {
        border-radius: 8px;
        padding: 12px 12px;
        font-size: 16px;
    }

    .otp-page h2 a img {
        max-width: 14px;
    }

    .login-content-parent p {
        font-size: 18px;
        margin: 10px 0 0;
    }

    .login-otp-boc input {
        margin-right: 5px;
    }

    .login-otp-boc input {
        border-radius: 8px;
        width: 50px;
        height: 50px;
        font-size: 16px;
    }

    .resend-box a.resendfor,
    span.timer {
        font-size: 16px;
        font-weight: 400;
    }
</style>
<section class="same-section login-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-content-wrapper">
                    <div class="login-content-parent m-0">
                        <a href="https://api.swap-africa.net"><img
                                src="https://api.swap-africa.net/public/assets/front/images/logo.svg" alt="image"></a>
                    </div>
                    <div class="alert alert-success success_message alert-dismissible" style="display:none"></div>
                    <?php if (session()->has('error_message')) { ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <strong>Error!</strong> {{Session::get('error_message')}}
                    </div>
                    <?php  Session::forget('error_message'); } ?>
                    <div class="login-content-parent otp-page">
                        <h2> <a href="/payment-login?merchantId={{$merchant}}"><img
                                    src="{{PUBLIC_PATH}}/assets/front/images/back-icon.svg" alt="image"></a> {{__('message.OTP')}}</h2>
                        <p>{{__('message.One time password has been sent to your registered phone number.')}}</p>
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
                            <button type="submit" class="btn btn-primaryx">{{__('message.Login')}} </button>
                        </div>

                    </div>
                    {{ Form::close()}}
                </div>
            </div>
        </div>
    </div>
</section>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
    $('.success_message').html("{{__('message.Resent OTP successfully')}}.<button type='button' class='btn-close' data-bs-dismiss='alert'></button>");
    $('.success_message').show();
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