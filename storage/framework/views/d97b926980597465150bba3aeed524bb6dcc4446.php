<?php $__env->startSection('content'); ?>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {

            $.validator.addMethod("lettersonly", function (value) {
                return /^[A-Za-z0-9\s]+$/.test(value);
            });

            $.validator.addMethod("latlong", function (value) {
                return /^-?\d+(\.\d+)?$/.test(value);
            });

            $("#adminForm").validate({
                rules: {
                    name: {
                        required: true,
                        lettersonly: true,
                        minlength: 5,
                        maxlength: 50
                    },
                    address: {
                        required: true,
                        lettersonly: true,
                        minlength: 5,
                        maxlength: 200
                    },
                    telephone: {
                        required: true,
                        digits: true,
                        minlength: 6,
                        maxlength: 15
                    },
                    latitude: {
                        required: true,
                        latlong: true,
                        min: -90,
                        max: 90
                    },
                    longitude: {
                        required: true,
                        latlong: true,
                        min: -180,
                        max: 180
                    }
                },
                messages: {
                    name: {
                        lettersonly: "Name should contain only alphabetic characters."
                    },
                    address: {
                        lettersonly: "Address should contain only alphabetic characters."
                    },
                    telephone: {
                        digits: "Telephone should contain numbers only."
                    },
                    latitude: {
                        required: "Latitude is required",
                        latlong: "Latitude must be a valid number",
                        min: "Latitude must be between -90 and 90",
                        max: "Latitude must be between -90 and 90"
                    },
                    longitude: {
                        required: "Longitude is required",
                        latlong: "Longitude must be a valid number",
                        min: "Longitude must be between -180 and 180",
                        max: "Longitude must be between -180 and 180"
                    }
                }
            });
        });
    </script>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>Edit Location</h1>
            <ol class="breadcrumb">
                <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="<?php echo e(URL::to('admin/locations')); ?>"><i class="fa fa-user-secret"></i> <span>Manage
                            Location</span></a>
                </li>
                <li class="active"> Edit Location</li>
            </ol>
        </section>
        <section class="content">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">&nbsp;</h3>
                </div>
                <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
                <?php echo e(Form::model($recordInfo, ['method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"])); ?>

                <div class="form-horizontal">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Name<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('name', null, ['class' => 'form-control required', 'placeholder' => 'Enter Name', 'autocomplete' => 'off'])); ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Address<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('address', null, ['class' => 'form-control required', 'placeholder' => 'Enter address', 'autocomplete' => 'off'])); ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Telephone<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('telephone', null, ['class' => 'form-control', 'placeholder' => 'Enter Phone', 'autocomplete' => 'off'])); ?>


                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Latitude<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('latitude', null, ['class' => 'form-control', 'placeholder' => 'Enter latitude', 'autocomplete' => 'off'])); ?>


                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Longitude<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('longitude', null, ['class' => 'form-control', 'placeholder' => 'Enter longitude', 'autocomplete' => 'off'])); ?>


                            </div>
                        </div>
                        <div class="box-footer">
                            <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info'])); ?>

                            <a href="<?php echo e(URL::to('admin/locations')); ?>" title="Cancel"
                                class="btn btn-default canlcel_le">Cancel</a>
                        </div>
                    </div>
                </div>
                <?php echo e(Form::close()); ?>

            </div>
        </section>

    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/locations/edit.blade.php ENDPATH**/ ?>