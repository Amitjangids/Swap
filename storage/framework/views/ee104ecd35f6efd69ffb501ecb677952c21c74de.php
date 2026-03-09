<?php $__env->startSection('content'); ?>
<div class="content-wrapper">
    <section class="content-header">

      <?php  if($slug=="customer-transaction-limit") { ?>
        <h1>User Transactions Limit</h1>
      <?php }elseif($slug=="merchant-transaction-limit") { ?>  
        <h1>Merchant Transactions Limit</h1>
      <?php }else { ?>
        <h1>Agent Transactions Limit</h1>
      <?php } ?>

      <ol class="breadcrumb">
      <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
      <li><a href="javascript:void(0);"><i class="fa fa-cogs"></i> Configuration</a></li>
      <?php  if($slug=="customer-transaction-limit") { ?>
      <li class="active">User Transactions Limit</li>
      <?php }elseif($slug=="merchant-transaction-limit") { ?>  
        <li class="active">Merchant Transactions Limit</li>
      <?php }else { ?>
        <li class="active">Agent Transactions Limit</li>
      <?php } ?>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
            <?php echo e(Form::model($adminInfo, ['method' => 'post', 'id' => 'adminForm', 'class' => 'form form-signin'])); ?>

            <div class="form-horizontal">
                <div class="box-body">

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum Deposit  Amount  <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('minDeposit', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum Deposit  Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum  Deposit Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('maxDeposit', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum  Deposit Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum Airtel Deposit  Amount  <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('minAirtel', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum Airtel Deposit Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum  Airtel Deposit Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('maxAirtel', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum Airtel Deposit Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum  Withdraw Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('minWithdraw', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum  Withdraw Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum  Withdraw Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('maxWithdraw', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum  Withdraw Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <?php if($slug!='agent-transaction-limit') {  ?>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum  SendMoney <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('minSendMoney', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum  SendMoney', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum  SendMoney <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('maxSendMoney', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum  SendMoney', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <?php } ?>



                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum Gimac Transfer <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('gimacMin', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum Gimac Transfer', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum Gimac Transfer <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('gimacMax', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum Gimac Transfer', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum Bda Transfer <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('bdaMin', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum Bda Transfer', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum Bda Transfer <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('bdaMax', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum Bda Transfer', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum Onafriq Transfer <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('onafriqa_min', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum Onafriq Transfer', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum Onafriq Transfer <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('onafriqa_max', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum Onafriq Transfer', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <?php if($slug=='customer-transaction-limit') {  ?>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum Receiving Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('moneyReceivingMin', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum Receiving Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum Receiving Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('moneyReceivingMax', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum Receiving Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <?php } ?>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum Unverified Kyc Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('unverifiedKycMin', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum Unverified Kyc Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum Unverified Kyc Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('unverifiedKycMax', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum Unverified Kyc Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <?php if($slug=='merchant-transaction-limit') {  ?>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Minimum Bulk Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('bulkMin', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Minimum Bulk Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>    

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Maximum Bulk Amount <span class="require"></span></label>
                        <div class="col-sm-9">
                            <?php echo e(Form::text('bulkMax', null, ['min'=>'1','class'=>'form-control', 'placeholder'=>'Maximum Bulk Amount', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>

                    <?php } ?>


                    <div class="box-footer">
                            <label class="col-sm-3 control-label" for="inputPassword3">&nbsp;</label>
                            <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info'])); ?>

                            <a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>" class="btn btn-default canlcel_le">Cancel</a>
                        </div>
                </div>
            </div>
            <?php echo e(Form::close()); ?>

        </div>
    </section>
</div>


<script>
    $(document).ready(function () {
        $("#adminForm").validate({
            rules: {
                minDeposit: {
                    required: true,
                    min: 1
                },
                maxDeposit: {
                    required: true,
                    min: 1
                },
                minWithdraw: {
                    required: true,
                    min: 1
                },
                maxWithdraw: {
                    required: true,
                    min: 1
                },
                <?php if($slug!='agent-transaction-limit') { ?>
                minSendMoney: {
                    required: true,
                    min: 1
                },
                maxSendMoney: {
                    required: true,
                    min: 1
                },
                <?php } ?>
                gimacMin: {
                    required: true,
                    min: 1
                },
                gimacMax: {
                    required: true,
                    min: 1
                },
                <?php if($slug=='customer-transaction-limit') { ?>
                moneyReceivingMin: {
                    required: true,
                    min: 1
                },
                moneyReceivingMax: {
                    required: true,
                    min: 1
                },
                <?php } ?>
                unverifiedKycMin: {
                    required: true,
                    min: 1
                },
                unverifiedKycMax: {
                    required: true,
                    min: 1
                },
                <?php if($slug=='merchant-transaction-limit') { ?>
                bulkMin: {
                   required: true,
                   min: 1
                },
                bulkMax: {
                   required: true,
                   min: 1
                },
                <?php } ?> 
            },
            messages: {
                minDeposit: {
                    required: "Minimum Deposit Amount is required",
                    min: "Minimum Deposit Amount must be a number greater than or equal to 1"
                },
                maxDeposit: {
                    required: "Maximum Deposit Amount is required",
                    min: "Maximum Deposit Amount must be a number greater than or equal to 1"
                },
                minWithdraw: {
                    required: "Minimum Withdraw Amount is required",
                    min: "Minimum Withdraw Amount must be a number greater than or equal to 1"
                },
                maxWithdraw: {
                    required: "Maximum Withdraw Amount is required",
                    min: "Maximum Withdraw Amount must be a number greater than or equal to 1"
                },
                <?php if($slug!='agent-transaction-limit') { ?>
                minSendMoney: {
                    required: "Minimum SendMoney is required",
                    min: "Minimum SendMoney must be a number greater than or equal to 1"
                },
                maxSendMoney: {
                    required: "Maximum SendMoney is required",
                    min: "Maximum SendMoney must be a number greater than or equal to 1"
                },
                <?php } ?>
                gimacMin: {
                    required: "Minimum Gimac Transfer is required",
                    min: "Minimum Gimac Transfer must be a number greater than or equal to 1"
                },
                gimacMax: {
                    required: "Maximum Gimac Transfer is required",
                    min: "Maximum Gimac Transfer must be a number greater than or equal to 1"
                },
                <?php if($slug=='customer-transaction-limit') { ?>
                moneyReceivingMin: {
                    required: "Minimum Receiving Amount is required",
                    min: "Minimum Receiving Amount must be a number greater than or equal to 1"
                },
                moneyReceivingMax: {
                    required: "Maximum Receiving Amount is required",
                    min: "Maximum Receiving Amount must be a number greater than or equal to 1"
                },
                <?php } ?>
                unverifiedKycMin: {
                    required: "Minimum Unverified Kyc Amount is required",
                    min: "Minimum Unverified Kyc Amount must be a number greater than or equal to 1"
                },
                unverifiedKycMax: {
                    required: "Maximum Unverified Kyc Amount is required",
                    min: "Maximum Unverified Kyc Amount must be a number greater than or equal to 1"
                },
                <?php if($slug=='merchant-transaction-limit') { ?>
                bulkMin: {
                    required: "Minimum bulk amount is required",
                    min: "Minimum bulk amount must be a number greater than or equal to 1"
                },   
                bulkMax: {
                    required: "Maximum bulk amount is required",
                    min: "Maximum bulk amount must be a number greater than or equal to 1"
                },
                <?php } ?>
            },
        });
    });
</script>


<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/admins/changeLimit.blade.php ENDPATH**/ ?>