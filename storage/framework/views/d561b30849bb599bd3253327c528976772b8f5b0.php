<?php $__env->startSection('content'); ?>
<?php
use App\Http\Controllers\Admin\AdminsController;
?>
<?php
use App\Permission;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Companies List</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Companies List</li>
        </ol>
    </section>

    <section class="content">
	<div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
        <div class="box box-info">
           <div class="admin_search">
            <?php echo e(Form::open(array('method' => 'post', 'id' => 'adminSearch'))); ?>

                <div class="form-group align_box dtpickr_inputs">
                    <span class="hints">Search by Company Name/Code, Username, Phone Number, Email Address or Date</span>
                    <span class="hint"><?php echo e(Form::text('keyword', null, ['class'=>'form-control', 'placeholder'=>'Search by keyword', 'autocomplete' => 'off'])); ?></span>
                    <span class="hint">
                        <?php echo e(Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by date', 'autocomplete' => 'off'])); ?>

                    </span>
                    <div class="admin_asearch">
                        <div class="ad_s ajshort"><?php echo e(Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])); ?></div>
                        <div class="ad_cancel"><a href="<?php echo e(URL::to('admin/admins/company-list')); ?>" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                <?php echo e(Form::close()); ?>

                <?php
                    $roles1 = AdminsController::getRoles(Session::get('adminid'));   
                ?>
                <?php $permissions = DB::table('permissions')->where('role_id',$roles1)->pluck('permission_name')->toArray();?>
                <?php if(in_array('add-company',$permissions)): ?>
                <div class="add_new_record" style="10px;"><a href="<?php echo e(URL::to('admin/admins/add-company')); ?>" class="btn btn-default"><i class="fa fa-plus"></i> Add Company</a>
                </div>
                <?php endif; ?>
             </div>            
            <div class="m_content" id="listID">
            <?php echo $__env->make('elements.admin.admins.companyList', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/admins/companyList.blade.php ENDPATH**/ ?>