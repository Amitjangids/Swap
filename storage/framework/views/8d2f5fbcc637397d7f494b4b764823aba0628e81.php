<?php $__env->startSection('content'); ?>

<style type="text/css"> 
.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
}

.toggle-password i {
    font-size: 18px;
} 
</style>

<section class="same-section login-page login-driver-section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3"></div>
            <div class="col-lg-6">
                <div class="login-content-wrapper">
                    <div class="login-content-parent">
                        <a href="<?php echo e(HTTP_PATH); ?>">
                            <img src="<?php echo e(PUBLIC_PATH); ?>/assets/front/images/logo.svg" alt="image">
                        </a>
                        <!-- <h2>Login your account</h2>
                        <p>Our end-to-end payment solution is designed to deliver the best payment experience to your customers, suppliers, and employees, thereby enhancing your performance and revenue.</p> -->
                    </div>
                    <?php echo e(Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin'))); ?>


                    <div class="login-from-parent">
                        <!-- <label>Phone Number</label>
                        <div class="login-contact">
                            <div class="input-box-parent">
                                <input class="required" type="text" name="phoneNumber" placeholder="Enter phone number" minlength="8" maxlength="15">
                            </div>
                        </div> -->

                        <div class="login-contact">
                            <div class="country-box">
                                <img src="<?php echo e(PUBLIC_PATH); ?>/assets/front/images/country-flag.png" alt="image">
                                <span>+241</span>   
                            </div>
                            <div class="input-box-parent">
                                <!-- <input class="required" type="text" name="phoneNumber" placeholder="<?php echo e(__('message.Enter mobile number')); ?>"> -->
                                <input class="required" type="text" name="phoneNumber" placeholder="Enter phone number" minlength="8" maxlength="15">
                            </div>
                        </div>
                        <div class="login-btn">
                            <button type="submit" class="btn btn-primaryx">Continue</button>
                        </div>
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

<script type="text/javascript">
    $(document).ready(function () {
        $("#loginform").validate({
            rules: {
                "phoneNumber": {
                    required: true,
                    digits: true,
                    maxlength: 15,
                    minlength: 8
                }
            },
            messages: {
                "phoneNumber": {
                    required: "Enter phone number",
                    digits: "Please enter only digits",
                    minlength: "Phone number cannot be less than 8 digits",
                    maxlength: "Phone number cannot be more than 15 digits"
                }
            },
            submitHandler: function (form) {
                form.submit();
            }
        });
    });
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.login', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/users/delete-account-user.blade.php ENDPATH**/ ?>