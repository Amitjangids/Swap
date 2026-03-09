<?php $__env->startSection('content'); ?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Referral Earnings</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Referral Earnings</li>
        </ol>
    </section>
<style>
        .row-button {
    top: 103px !important;
}
    </style>
    <section class="content">
        <div class="box box-info">
            <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
            <div class="admin_search">
                <?php echo e(Form::open(array('method' => 'post', 'id' => 'adminSearch'))); ?>

                <div class="form-group align_box dtpickr_inputs">
                    <!--<span class="hints" style="font-weight: 600;">Search</span>-->
                    <span class="hint"><label>Search by referrer name</label><?php echo e(Form::text('sender', null, ['class'=>'form-control', 'placeholder'=>'Search by referrer name', 'autocomplete' => 'off'])); ?></span>
                    <span class="hint"><label>Search by referrer phone</label><?php echo e(Form::text('sender_phone', null, ['class'=>'form-control', 'placeholder'=>'Search by referrer phone', 'autocomplete' => 'off'])); ?></span>
                    <span class="hint"><label>Search by referred name</label><?php echo e(Form::text('receiver', null, ['class'=>'form-control', 'placeholder'=>'Search by referred name', 'autocomplete' => 'off'])); ?></span>
                    <span class="hint"><label>Search by referred phone</label><?php echo e(Form::text('receiver_phone', null, ['class'=>'form-control', 'placeholder'=>'Search by referred phone', 'autocomplete' => 'off'])); ?></span>
                   <?php /* <span class="hint">
                        <?php $typeList = array('Debit' => 'Debit', 'Credit' => 'Credit', 'Request' => 'Request'); ?>                        
                        {{Form::select('type', $typeList,null, ['class' => 'form-control','placeholder' => 'Select transaction type'])}}
                    </span> */?>


<!--<span class="hint"><?php echo e(Form::text('for', null, ['class'=>'form-control', 'placeholder'=>'Search by transaction for', 'autocomplete' => 'off'])); ?></span>-->
                    
                    <span class="hint">
                        <label>Search by transaction date</label>
                        <?php echo e(Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by request date', 'autocomplete' => 'off'])); ?>

                    </span>
                    
                    <div class="admin_asearch row-button">
                        <div class="ad_s ajshort"><?php echo e(Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])); ?></div>
                        <div class="ad_cancel"><a href="<?php echo e(URL::to('admin/referral-listing')); ?>" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                <?php echo e(Form::close()); ?>

                <!--<div class="add_new_record"><a href="<?php echo e(URL::to('admin/transactions/add')); ?>" class="btn btn-default"><i class="fa fa-plus"></i> Add User</a></div>-->
            </div>            
            <div class="m_content" id="listID">
                <?php echo $__env->make('elements.admin.transactions.referral-listing', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </section>

    
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/transactions/referral-listing.blade.php ENDPATH**/ ?>