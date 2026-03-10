<?php $__env->startSection('content'); ?>
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

            $("#adminForm").validate();
        });
    </script>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>Add Drivers</h1>
            <ol class="breadcrumb">
                <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="<?php echo e(URL::to('admin/drivers')); ?>"><i class="fa fa-user-secret"></i> <span>Manage Drivers</span></a>
                </li>
                <li class="active"> Add Drivers</li>
            </ol>
        </section>
        <section class="content">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">&nbsp;</h3>
                </div>
                <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
                <?php echo e(Form::open(array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"))); ?>

                <div class="form-horizontal">
                    <div class="box-body">

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Name<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('name', null, ['class' => 'form-control name required', 'placeholder' => 'Enter Name', 'autocomplete' => 'off'])); ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Company Name<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('companyName', null, ['class' => 'form-control companyName required', 'placeholder' => 'Enter company name', 'autocomplete' => 'off'])); ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Email Address <span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('email', null, ['class' => 'form-control email required', 'placeholder' => 'Enter Email Address', 'autocomplete' => 'off'])); ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Phone<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('phone', null, ['class' => 'form-control phone required', 'placeholder' => 'Enter Phone', 'autocomplete' => 'off', 'maxlength' => 15, 'oninput' => 'this.value=this.value.replace(/[^0-9]/g,"")'])); ?>

                            </div>
                        </div>

                        <div class="box-footer">
                            <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                            <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info'])); ?>

                            <?php echo e(Form::reset('Reset', ['class' => 'btn btn-default canlcel_le'])); ?>

                        </div>
                    </div>
                </div>
                <?php echo e(Form::close()); ?>

            </div>
        </section>

    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/drivers/add.blade.php ENDPATH**/ ?>