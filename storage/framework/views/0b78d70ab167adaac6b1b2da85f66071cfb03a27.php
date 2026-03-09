<?php $__env->startSection('content'); ?>
<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("alphanumeric", function (value, element) {
            return this.optional(element) || /^\S+$/i.test(value);
        }, "Space not allowed for username.");
        $("#adminForm").validate();
    });
</script>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Change Username</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="javascript:void(0);"><i class="fa fa-cogs"></i> Configuration</a></li>
            <li class="active">Change Username</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
            <?php echo e(Form::open(array('method' => 'post', 'id' => 'adminForm', 'class' => 'form form-signin'))); ?>

            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Current Username <span class="require"></span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::text('old_username', $adminInfo->username, ['class'=>'form-control required', 'placeholder'=>'Username', 'autocomplete' => 'off', 'readonly'=>true])); ?>

                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">New Username <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::text('new_username', null, ['class'=>'form-control required', 'placeholder'=>'New Username', 'autocomplete' => 'off', 'id'=>'newusername'])); ?>

                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Confirm Username <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::text('confirm_username', null, ['class'=>'form-control required', 'placeholder'=>'Confirm Username', 'autocomplete' => 'off', 'equalTo' => '#newusername'])); ?>

                        </div>
                    </div>
                    <div class="box-footer">
                            <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                            <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info'])); ?>

                            <a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>" class="btn btn-default canlcel_le">Cancel</a>
                        </div>
                </div>
            </div>
            <?php echo e(Form::close()); ?>

        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/admins/changeUsername.blade.php ENDPATH**/ ?>