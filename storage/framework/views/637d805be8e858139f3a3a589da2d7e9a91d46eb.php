<?php $__env->startSection('content'); ?>
<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("alphanumeric", function(value, element) {
            return this.optional(element) || /^[\w.]+$/i.test(value);
        }, "Only letters, numbers and underscore allowed.");
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "Password must be a combination of Numbers, Uppercase & Lowercase Letters.");
        $.validator.addMethod("dollarsscents", function (value, element) {
            return this.optional(element) || /^\d{0,10}(\.\d{0,2})?$/i.test(value);
        }, "You can enter amount upto 10 digits with two decimal points.");
        $("#adminForm").validate();
    });
 </script>
 
<div class="content-wrapper">
    <section class="content-header">
        <h1>Edit Transaction Fees</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo e(URL::to('admin/transactionfees')); ?>"><i class="fa fa-exchange"></i> <span>Manage Transaction Fees</span></a></li>
            <li class="active"> Edit Transaction Fees</li>
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
                <?php global $typefor; ?>

                    <?php if($recordInfo->transaction_type == 'Deposit'): ?>
                        <?php $selectedType = 'Sell Balance'; ?>
                    <?php elseif($recordInfo->transaction_type == 'Withdraw'): ?>
                        <?php $selectedType = 'Buy Balance'; ?>
                    <?php elseif($recordInfo->transaction_type == 'Send Money'): ?>
                        <?php $selectedType = 'Send Money'; ?>
                    <?php elseif($recordInfo->transaction_type == 'Refund'): ?>
                        <?php $selectedType = 'Refund'; ?>
                    <?php else: ?>
                        <?php $selectedType = null; ?>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Select Fee Transaction Type<span class="require">*</span></label>
                        <div class="col-sm-3">
                            <?php echo e(Form::select('type', $typefor, $recordInfo->transaction_type, ['class' => 'form-control required', 'placeholder' => 'Select Fee Transaction Type', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>



                    <?php
                    $data = DB::table('amount_slab')->get();
                    $options = [];

                    foreach ($data as $row) {
                        $optionValue = $row->min_amount . '-' . $row->max_amount;
                        $options[$optionValue] = '₣ ' . $row->min_amount . ' - ₣ ' . $row->max_amount;
                    }
                    ?>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Select Amount Slab<span class="require">*</span></label>
                        <div class="col-sm-3">
                            <?php echo e(Form::select('amount_slab', $options, $recordInfo->min_amount . '-' . $recordInfo->max_amount, ['class' => 'form-control required', 'placeholder' => 'Select Amount Slab'])); ?>

                        </div>
                    </div>


       

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Fee IN <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <div class="radd"> 
                                <?php echo e(Form::radio('feetype', '0', $recordInfo->fee_type == 0, ['0' => 'percentage'])); ?> 
                                <span>Percentage</span> 
                            </div>
                            <div class="radd"> 
                                <?php echo e(Form::radio('feetype', '1', $recordInfo->fee_type == 1, ['1' => 'flat_rate'])); ?> 
                                <span>Flat Rate</span> 
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Fee Amount<span class="require">*</span></label>
                        <div class="col-sm-3">
                            <?php echo e(Form::text('feecharge', $recordInfo->fee_amount, ['class'=>'form-control required', 'placeholder'=>'Fee Amount', 'autocomplete' => 'off','onkeypress'=>"return validateFloatKeyPress(this,event);", 'onpaste' => 'return false;', 'oncopy' => 'return false;'])); ?>

                        </div>
                    </div>


                  
                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info', 'onclick' => 'disable_submit()'])); ?>

                        <a href="<?php echo e(URL::to( 'admin/transactionfees')); ?>" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            <?php echo e(Form::close()); ?>

        </div>
    </section>

    <script>

    function disable_submit()
    {
    $('.button_disable').prop('disabled', true);   
    }

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

    $(document).ready(function() {
        $('.no-paste-copy').on('paste copy', function(event) {
            event.preventDefault();
        });
    });

    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/transactionfees/edit.blade.php ENDPATH**/ ?>