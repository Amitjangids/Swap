<?php $__env->startSection('content'); ?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Change Referral Bonus</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="javascript:void(0);"><i class="fa fa-cogs"></i> Configuration</a></li>
            <li class="active">Change Referral Bonus</li>
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
                        <label class="col-sm-2 control-label">Sender Referral Bonus<span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::number('referralBonusSender', $adminInfo->referralBonusSender, ['class'=>'form-control required', 'placeholder'=>'Sender Referral Bonus', 'autocomplete' => 'off','min'=>1])); ?>

                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Receiver Referral Bonus <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::number('referralBonusReceiver', $adminInfo->referralBonusReceiver, ['class'=>'form-control required', 'placeholder'=>'Receiver Referral Bonus', 'autocomplete' => 'off','min'=>1])); ?>

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
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/admins/change-referral-bonus.blade.php ENDPATH**/ ?>