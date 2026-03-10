<?php $__env->startSection('content'); ?>
<section class="same-section login-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3"></div>
            <div class="col-lg-6">
                <div class="login-content-wrapper">
                    <div class="login-content-parent otp-page">
                        <h2> <a href="<?php echo e(HTTP_PATH); ?>/login-driver"><img src="<?php echo e(PUBLIC_PATH); ?>/assets/front/images/back-icon.svg" alt="image"></a><?php echo e(__('message.OTP')); ?></h2>
                        <!-- <p><?php echo e(__('message.One time password has been sent to your registered email address.')); ?></p> -->
                    </div>
                    <?php echo e(Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin'))); ?>

                    <div class="login-from-parent">
                        <div class="login-otp-wrapper">
                            <div class="login-otp-boc">
                                <input class="required digits opt_input d0" type="text" name="otp1" autocomplete="off" maxlength="1">
                                <input class="required digits opt_input d1" type="text" name="otp2" autocomplete="off" maxlength="1">
                                <input class="required digits opt_input d2" type="text" name="otp3" autocomplete="off" maxlength="1">
                                <input class="required digits opt_input d3" type="text" name="otp4" autocomplete="off" maxlength="1">
                                <input class="required digits opt_input d4" type="text" name="otp5" autocomplete="off" maxlength="1">
                                <input class="required digits opt_input d5" type="text" name="otp6" autocomplete="off" maxlength="1">
                            </div>
                            <div class="resend-box">
                                <!-- <a class="resendfor" onclick="resetOTP();" href="#"><?php echo e(__('message.Resend code')); ?></a> -->
                                <!-- <span class="timer">0:59</span> -->
                            </div>
                        </div>

                        <div class="login-btn">
                             <button type="submit" class="btn btn-primaryx"><?php echo e(__('message.Verify')); ?></button>
                        </div>

                        <div class="alert alert-success success_message" role="alert" style="display:none"></div>

                    <?php if (session()->has('error_message')) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo e(Session::get('error_message')); ?>

                     </div>
                     <?php Session::forget('error_message'); } ?>

                    </div>
                    <?php echo e(Form::close()); ?>

                </div>
            </div>
            <div class="col-lg-3">
                <!-- <div class="login-image">
                    <img src="<?php echo e(PUBLIC_PATH); ?>/assets/front/images/login-image.png" alt="image">
                </div> -->
            </div>
        </div>
    </div>
</section>

<script>
    // On dashboard page
$(document).ready(function () {
    // Replace the current history entry (OTP screen) with dashboard
    history.replaceState(null, '', window.location.href);

    // Prevent back navigation to OTP
    window.addEventListener('popstate', function (event) {
        // This will push the user forward again
        history.go(1);
    });
});

$(document).ready(function () {
    function resetOTP() {
    var phone = '<?php echo e($slug); ?>';
    $.ajax({
        url: "<?php echo HTTP_PATH; ?>/resendEmailOtp",
        type: "POST",
        data: {'phone': phone, _token: '<?php echo e(csrf_token()); ?>'},
        success: function (result) {
            countdown(); // Restart the countdown timer
            clearOtpInputs(); // Clear OTP input fields

            $('.success_message').html("<?php echo e(__('message.Resent OTP successfully.')); ?>").show();

            // Hide message after 3 seconds
            setTimeout(function() {
                $('.success_message').fadeOut();
            }, 3000);
        }
    });
}

// Countdown timer for resend OTP
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
});




</script>

<script>
    function clearOtpInputs() {
    $('.opt_input').val(''); 
    $('.d0').focus(); 
}

function attachOtpHandlers() {
    $('.opt_input').off('paste').on('paste', function (e) {
        e.preventDefault();
        var clipboardData = e.originalEvent.clipboardData || window.clipboardData;
        var pastedData = clipboardData.getData('Text');

        if (pastedData.length === $('.opt_input').length) {
            $('.opt_input').each(function (index) {
                $(this).val(pastedData[index]);
            });
            $('.opt_input').last().focus(); // Move focus to last field
        }
    });

    // Move to next input field on input
    $(".opt_input").off('input').on('input', function () {
        var value = $(this).val();
        if (value.length === 1) {
            $(this).next('.opt_input').focus();
        }
    });

    // Handle backspace and arrow key navigation
    $(".opt_input").off('keydown').on('keydown', function (e) {
        var key = e.keyCode || e.which;

        if (key === 8 && $(this).val() === '') { // Backspace
            $(this).prev('.opt_input').focus();
        } else if (key === 37) { // Left arrow
            $(this).prev('.opt_input').focus();
        } else if (key === 39) { // Right arrow
            $(this).next('.opt_input').focus();
        }
    });

    // Handle Cut (CTRL + X)
    $(".opt_input").off('cut').on('cut', function (e) {
        setTimeout(() => {
            if ($(this).val() === '') {
                $(this).next('.opt_input').focus();
            }
        }, 10);
    });
}

// Initialize handlers on page load
$(document).ready(function () {
    attachOtpHandlers();
});
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.login', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/users/verify_delete_account.blade.php ENDPATH**/ ?>