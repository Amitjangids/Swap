<?php $__env->startSection('content'); ?>
    <div class="content-wrapper">
        <section class="content-header">
            <h1>Manage Help Ticket</h1>
            <ol class="breadcrumb">
                <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li class="active"> Manage Help Ticket</li>
            </ol>
        </section>

        <section class="content">
            <div class="box box-info">
                <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
                <div class="admin_search">
                    <?php echo e(Form::open(array('method' => 'post', 'id' => 'adminSearch'))); ?>

                    <div class="form-group align_box dtpickr_inputs">

                        <span class="hint">
                            <label>Search by Ticket ID</label>
                            <?php echo e(Form::text('keyword', null, ['class' => 'form-control', 'placeholder' => 'Search by Ticket ID', 'autocomplete' => 'off'])); ?>

                        </span>
                        <span class="hint">
                            <label>Search by request date</label>
                            <?php echo e(Form::text('to', null, ['id' => 'toDate', 'class' => 'form-control', 'placeholder' => 'Search by request date', 'autocomplete' => 'off'])); ?>

                        </span>
                        <div class="admin_asearch row-button">
                            <div class="ad_s ajshort">
                                <?php echo e(Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])); ?>

                            </div>
                            <div class="ad_cancel"><a href="<?php echo e(URL::to('admin/help-ticket')); ?>"
                                    class="btn btn-default canlcel_le">Clear Search</a></div>
                        </div>
                    </div>
                    <?php echo e(Form::close()); ?>

                    <!-- <div class="add_new_record"><a href="<?php echo e(URL::to('admin/drivers/add-driver')); ?>" class="btn btn-default"><i class="fa fa-plus"></i> Add Driver</a></div> -->
                </div>
                <div class="m_content" id="listID">
                    <?php echo $__env->make('elements.admin.helpticket.index', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/helpticket/index.blade.php ENDPATH**/ ?>