<?php $__env->startSection('content'); ?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Gimac Transactions</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Gimac Transactions</li>
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
                    <span class="hint"><label>Search by First Name</label><?php echo e(Form::text('sender', null, ['class'=>'form-control', 'placeholder'=>'Search by First Name', 'autocomplete' => 'off'])); ?></span>
                    <!-- <span class="hint"><label>Search by sender phone</label><?php echo e(Form::text('sender_phone', null, ['class'=>'form-control', 'placeholder'=>'Search by sender phone', 'autocomplete' => 'off'])); ?></span> -->
                    <span class="hint"><label>Search by Issuertrxref No</label><?php echo e(Form::text('receiver', null, ['class'=>'form-control', 'placeholder'=>'Search by Issuertrxref No', 'autocomplete' => 'off'])); ?></span>
                    <span class="hint"><label>Search by Tel. No</label><?php echo e(Form::text('receiver_phone', null, ['class'=>'form-control', 'placeholder'=>'Search by Tel. No', 'autocomplete' => 'off'])); ?></span>
                  

                    <span class="hint">
                        <label>Select Status</label>
                        <?php global $status; ?>                        
                        <?php echo e(Form::select('type', $status,null, ['class' => 'form-control','placeholder' => 'Select Status'])); ?>

                    </span>

                    <span class="hint">
                        <label>Search by request date</label>
                        <?php echo e(Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by request date', 'autocomplete' => 'off'])); ?>

                    </span>
                    <span class="hint">
                        <label>Search by process date</label>
                        <?php echo e(Form::text('to1', null, ['id'=>'toDate1','class'=>'form-control', 'placeholder'=>'Search by process date', 'autocomplete' => 'off'])); ?>

                    </span>


                    <div class="admin_asearch row-button">
                        <div class="ad_s ajshort"><?php echo e(Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])); ?></div>
                        <div class="ad_cancel"><a href="<?php echo e(URL::to('/admin/gemic-transation')); ?>" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                <?php echo e(Form::close()); ?>


            </div>            
            <div class="m_content" id="listID">
                <?php echo $__env->make('elements.admin.transactions.gimac', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </section>

    
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/transactions/gimactransaction.blade.php ENDPATH**/ ?>