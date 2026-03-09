<?php $__env->startSection('content'); ?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<style>
    div#ui-datepicker-div {
    background: #fff;
    padding: 20px 10px;
    width: 230px;
    overflow: hidden;
    box-shadow: 0 0 10px rgb(0 0 0 / 20%);
    border-radius: 5px;
}
div#ui-datepicker-div table.ui-datepicker-calendar {
    width: 100%;
}
    </style>
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
        }, "Password must be at least 8 characters long, contains an upper case letter, a lower case letter, a number and a symbol.");
        $("#adminForm").validate();

        $("#radio").click(function () {
            $(".main_section").hide();
            $("#station_sec").show();
        });
        $("#advertising").click(function () {
            $(".main_section").hide();
            $("#agency_sec").show();
        });
        $("#advertiser").click(function () {
            $(".main_section").hide();
            $("#advertiser_sec").show();
        });
        
    //     $("#city").change(function () {
    //         var cityid = $("#city").val();
    //         $("#area").load('<?php echo HTTP_PATH . '/admin/users/getarealist/' ?>' + cityid);
    //     });
    // });
</script>
<script>
    $(function () {
        $("#dob").datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: '-18Y',
            changeMonth: true,
            changeYear: true,
            yearRange: "-70:+0"
        });
    });
</script>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Edit User</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo e(URL::to('admin/users')); ?>"><i class="fa fa-user"></i> <span>Manage Users</span></a></li>
            <li class="active"> Edit User</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <?php //print_r($areaList);?>
            <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
            <?php echo e(Form::model($recordInfo, ['method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"])); ?>            
            <div class="form-horizontal">
                <div class="box-body">
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Full Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::text('name', null, ['class'=>'form-control required', 'placeholder'=>'Full Name', 'autocomplete' => 'off', 'maxlength' => 15,'oninput' => 'validateName(this)'])); ?>

                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Email Address <span class="require"></span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::text('email', null, ['class'=>'form-control ', 'placeholder'=>'Email Address', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Phone Number <span class="require">*</span></label>
                        <div class="col-sm-10">
                           
                        <?php echo e(Form::text('phone', null, ['id' => 'phone','class' => 'form-control required digits','placeholder' => 'Phone Number','autocomplete' => 'off','minlength' => '9','maxlength' => '9'])); ?>


                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Date Of Birth <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::text('dob', null, ['class'=>'form-control required ', 'placeholder'=>'Date Of Birth', 'autocomplete' => 'off','id'=>'dob','readonly'])); ?>

                        </div>
                    </div>  

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info'])); ?>

                        <a href="<?php echo e(URL::to( 'admin/users')); ?>" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            <?php echo e(Form::close()); ?>

        </div>
    </section>

    <script>
    function validateName(input) {
        // Define the allowed pattern (alphanumeric characters and spaces)
        var pattern = /^[a-zA-Z\s]*$/;
        
        // Test the input value against the pattern
        if (!pattern.test(input.value)) {
            // If invalid, remove the last entered character
            input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
        }
    }
</script>


    <?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/users/edit.blade.php ENDPATH**/ ?>