<?php $__env->startSection('content'); ?>
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


    function validateFloatKeyPress(el, evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode;
    var number = el.value.split('.');
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
         return false;
     }
     //just one dot
     if (number.length > 1 && charCode == 46) {
         return false;
     }
     //get the carat position
     var caratPos = getSelectionStart(el);
     var dotPos = el.value.indexOf(".");
     if (caratPos > dotPos && dotPos > -1 && (number[1].length > 1)) {
         return false;
     }
     return true;
    }


window.onload = () => {
const myInput = document.getElementById('daily_limit');
myInput.onpaste = e => e.preventDefault();

const myInput1 = document.getElementById('week_limit');
myInput1.onpaste = e => e.preventDefault();

const myInput2 = document.getElementById('month_limit');
myInput2.onpaste = e => e.preventDefault();

}

</script>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Edit Transaction Limit</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo e(URL::to('admin/users/transactions-limit')); ?>"><i class="fa fa-user"></i> <span>Transaction Limit</span></a></li>
            <li class="active"> Edit Transaction Limit</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
            <?php echo e(Form::model($limit, ['method' => 'post', 'id' => 'editTransLimitFrm', 'enctype' => "multipart/form-data"])); ?>            
            <div class="form-horizontal">
                <div class="box-body">
                    
                    <!-- <div class="form-group">
                        <label class="col-sm-2 control-label">Membership Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::text('account_category', null, ['class'=>'form-control required', 'placeholder'=>'Membership Name', 'disabled' => 'true'])); ?>

                        </div>
                    </div> -->
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Membership Type <span class="require">*</span></label>
                        <div class="col-sm-10">
                         <?php $membershipList = array('1'=>'Customer','2'=>'Merchant');?>
                         <?php echo e(Form::select('category_for', $membershipList,null, ['class' => 'form-control required','placeholder' => 'Membership Type', 'disabled' => 'true'])); ?>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Daily Limit <span class="require">*</span></label>
                        <div class="col-sm-10">
                        <?php echo e(Form::text('daily_limit', null, ['class'=>'form-control required', 'placeholder'=>'Daily Limit', 'autocomplete' => 'off','maxlength' => 14,'onkeypress'=>'return validateFloatKeyPress(this,event);','id'=>'daily_limit'])); ?>

                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Week Limit <span class="require">*</span></label>
                        <div class="col-sm-10">
                        <?php echo e(Form::text('week_limit', null, ['id'=>'week_limit','class'=>'form-control required', 'placeholder'=>'Week Limit', 'autocomplete' => 'off','maxlength' => 14,'onkeypress'=>'return validateFloatKeyPress(this,event);','id'=>'week_limit'])); ?>

                        <span id="week_error" style="display: none;">Weekly limit cannot be less than daily limit.</span>

                        </div>
                    </div>                       
                     
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Month Limit <span class="require">*</span></label>
                        <div class="col-sm-10">
                        <?php echo e(Form::text('month_limit', null, ['id'=>'month_limit','class'=>'form-control required', 'placeholder'=>'Month Limit', 'autocomplete' => 'off','maxlength' => 14,'onkeypress'=>'return validateFloatKeyPress(this,event);','id'=>'month_limit'])); ?>

                        <span id="month_error" style="display: none;">Monthly limit cannot be less than weekly limit.</span>
                        </div>
                    </div>

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info','onclick' => "return validateLimits()"])); ?>

                        <a href="<?php echo e(URL::to( 'admin/users/transactions-limit')); ?>" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            <?php echo e(Form::close()); ?>

        </div>
    </section>
    <?php $__env->stopSection(); ?>




    <script type="text/javascript">
    function validateLimits() {
        var dailyLimit = parseFloat(document.getElementById('daily_limit').value);
        var weekLimit = parseFloat(document.getElementById('week_limit').value);
        var monthLimit = parseFloat(document.getElementById('month_limit').value);

        if (weekLimit < dailyLimit ) {
            document.getElementById('week_error').style.display = 'block';
            return false;
        } else {
            document.getElementById('week_error').style.display = 'none';
        }

        if (monthLimit < weekLimit ) {
            document.getElementById('month_error').style.display = 'block';
            return false;
        } else {
            document.getElementById('month_error').style.display = 'none';
        }

        return true;
    }
</script>


<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/users/editTransLimit.blade.php ENDPATH**/ ?>