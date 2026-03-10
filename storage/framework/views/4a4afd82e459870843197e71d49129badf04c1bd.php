<?php $__env->startSection('content'); ?>
<style>
    .form-horizontal .form-group input + span.fee {
    color: #000;
    padding: 0;
}
    </style>
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
        
        $("#adminForm").validate();
    });
 </script>
 
<div class="content-wrapper">
    <section class="content-header">
        <h1>Add Fee Configuration</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo e(URL::to('admin/users')); ?>"><i class="fa fa-users"></i> <span>Manage Fee Configuration</span></a></li>
            <li class="active"> Add Fee Configuration</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
         
            <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
            <?php echo e(Form::open(array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"))); ?>

            <div class="form-horizontal">
                <div class="box-body">
                <div class="form-group">
                        <label class="col-sm-2 control-label">Select Fee Transaction Type<span class="require">*</span></label>
                        <?php global $typefor; ?>
                        <div class="col-sm-3">
                        <?php echo e(Form::select('type',$typefor, null, ['class'=>'form-control required alphabat', 'placeholder'=>'Select Fee Transaction Type', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>
                    <?php $data = DB::table('amount_slab')->orderBy('min_amount','asc')->get();?> 
                   
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Select Amount Slab<span class="require">*</span></label>
                        <div class="col-sm-3">
                            <?php
                            $options = [];
                            foreach($data as $row) {
                                $options[$row->id] = CURR .' '.$row->min_amount . ' - ' .CURR .' '. $row->max_amount;
                            }
                            ?>
                            <?php echo e(Form::select('amount_slab', $options, null, ['class' => 'form-control required', 'placeholder' => 'Select Amount Slab'])); ?>

                        </div>
                    </div>


                <div class="form-group">
                        <label class="col-sm-2 control-label">Fee IN <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <div class="radd"> <?php echo e(Form::radio('feetype', '0', true)); ?> <span class="fee">Percentage</span> </div>
                            <div class="radd"> <?php echo e(Form::radio('feetype', '1', false)); ?> <span class="fee">Flat Rate</span> </div>
                        </div>
                    </div> 
                    <div class="form-group">
    <label class="col-sm-2 control-label">Fee Amount<span class="require">*</span></label>
    <div class="col-sm-3">
        <?php echo e(Form::text('feecharge', null, ['class'=>'form-control required', 'placeholder'=>'Fee Amount', 'autocomplete' => 'off','onkeypress'=>"return validateFloatKeyPress(this,event);", 'onpaste' => 'return false;', 'oncopy' => 'return false;'])); ?>

    </div>
</div>
                  
                  
                    
                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info', 'onclick' => 'disable_submit()'])); ?>


                        <?php echo e(Form::reset('Reset', ['class' => 'btn btn-default canlcel_le'])); ?>

                    </div>
                </div>
            </div>
            <?php echo e(Form::close()); ?>

        </div>
    </section>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/transactionfees/add.blade.php ENDPATH**/ ?>