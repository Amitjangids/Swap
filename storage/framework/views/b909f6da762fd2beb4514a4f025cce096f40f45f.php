<?php $__env->startSection('content'); ?>
<script type="text/javascript">
    $(document).ready(function () {
        $("#adminlogin").validate();
    });
</script>
<div class="login-logo"><?php echo e(HTML::image("public/img/swap.png", "",['style' => 'width: 200px; height: auto;'])); ?></div>
<div class="login-box-body">
    <p class="login-box-msg">Forgot Password</p>
    <?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo e(Form::open(array('method' => 'post', 'id' => 'adminlogin', 'class' => 'form form-signin'))); ?>

    <div class="form-group has-feedback">
        <?php echo e(Form::text('email', null, ['class'=>'form-control required email', 'placeholder'=>'Enter email address'])); ?>

        <span class="form-control-feedback"><i class="fa fa-envelope"></i></span>
    </div>
    <div class="row">
        <div class="col-xs-8">
            <div class="checkbox"><a href="<?php echo e(URL::to( 'admin/admins/login')); ?>">I remember my password</a></div>
        </div>
        <div class="col-xs-4">
            <?php echo e(Form::submit('Submit', ['class' => 'btn btn-primary btn-block btn-flat'])); ?>

        </div>
    </div>
    <?php echo e(Form::close()); ?>    
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.adminlogin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/admins/forgotPassword.blade.php ENDPATH**/ ?>